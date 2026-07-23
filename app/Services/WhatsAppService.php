<?php

namespace App\Services;

/**
 * Envio de mensagens via Evolution API (WhatsApp), MULTI-EMPRESA.
 * Cada empresa envia do SEU próprio WhatsApp (instância `emp_{id}`).
 * A instância `fixaos` (config) é só da plataforma (boas-vindas etc.).
 * Best-effort: qualquer falha é silenciosa e NUNCA quebra o fluxo que chamou.
 */
class WhatsAppService
{
    private static function cfg(): array
    {
        $f = BASE_PATH . '/config/whatsapp.php';
        return is_file($f) ? (require $f) : ['enabled' => false];
    }

    /** Instância Evolution dedicada a uma empresa (cada uma envia do seu número). */
    public static function instanciaEmpresa(int $empresaId): string
    {
        return 'emp_' . $empresaId;
    }

    private static function instanciaPlataforma(): string
    {
        return self::cfg()['instance'] ?? 'fixaos';
    }

    /** Normaliza número brasileiro para DDI+DDD+numero (por comprimento, evita falso 55). */
    private static function normalizar(string $numero, string $ddi): ?string
    {
        $n   = preg_replace('/\D/', '', $numero);
        $len = strlen($n);
        if ($len < 10) return null;                    // sem DDD+numero válido
        if ($len === 10 || $len === 11) return $ddi . $n; // DDD+numero, falta o DDI
        return $n;                                      // 12-13 dígitos: já tem DDI
    }

    // ───────────────────────── HTTP genérico ─────────────────────────
    private static function api(string $method, string $path, ?array $payload = null, int $timeout = 15): array
    {
        $cfg = self::cfg();
        $ch  = curl_init(rtrim($cfg['url'], '/') . $path);
        $opt = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'apikey: ' . ($cfg['api_key'] ?? '')],
            CURLOPT_TIMEOUT        => $timeout,
            CURLOPT_CONNECTTIMEOUT => 5,
        ];
        if ($payload !== null) $opt[CURLOPT_POSTFIELDS] = json_encode($payload);
        curl_setopt_array($ch, $opt);
        $resp = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['code' => $code, 'body' => is_string($resp) ? (json_decode($resp, true) ?: []) : []];
    }

    // ───────────────────────── Baixo nível por instância ─────────────────────────
    private static function sendTextInst(string $instance, string $numero, string $texto): bool
    {
        if (!empty($_SESSION['demo_mode'])) return false;
        $cfg = self::cfg();
        if (empty($cfg['enabled'])) return false;
        $num = self::normalizar($numero, $cfg['pais_ddi'] ?? '55');
        if (!$num) return false;
        $r = self::api('POST', '/message/sendText/' . $instance, ['number' => $num, 'text' => $texto], 10);
        return $r['code'] >= 200 && $r['code'] < 300;
    }

    private static function sendDocumentoInst(string $instance, string $numero, string $base64Pdf, string $fileName, string $caption): bool
    {
        if (!empty($_SESSION['demo_mode'])) return false;
        $cfg = self::cfg();
        if (empty($cfg['enabled'])) return false;
        $num = self::normalizar($numero, $cfg['pais_ddi'] ?? '55');
        if (!$num) return false;
        $r = self::api('POST', '/message/sendMedia/' . $instance, [
            'number'    => $num,
            'mediatype' => 'document',
            'mimetype'  => 'application/pdf',
            'media'     => $base64Pdf,
            'fileName'  => $fileName,
            'caption'   => $caption,
        ], 30);
        return $r['code'] >= 200 && $r['code'] < 300;
    }

    private static function statusInst(string $instance): string
    {
        if (empty(self::cfg()['enabled'])) return 'unknown';
        $r = self::api('GET', '/instance/connectionState/' . $instance, null, 10);
        return $r['body']['instance']['state'] ?? 'unknown';
    }

    private static function qrInst(string $instance): ?string
    {
        $r   = self::api('GET', '/instance/connect/' . $instance, null, 12);
        $b64 = $r['body']['base64'] ?? null;
        return (is_string($b64) && str_starts_with($b64, 'data:image')) ? $b64 : null;
    }

    /** Cria a instância se ainda não existir (idempotente — ignora erro se já existe). */
    private static function criarInst(string $instance): void
    {
        self::api('POST', '/instance/create', [
            'instanceName' => $instance,
            'integration'  => 'WHATSAPP-BAILEYS',
            'qrcode'       => true,
        ], 20);
    }

    // ───────────────────────── Plataforma (FixaOS / instância `fixaos`) ─────────────────────────
    public static function status(): string { return self::statusInst(self::instanciaPlataforma()); }
    public static function qrDataUri(): ?string { return self::qrInst(self::instanciaPlataforma()); }

    /** Mensagem de boas-vindas para uma assistência recém-cadastrada (vem do FixaOS). */
    public static function boasVindas(string $numero, string $nomeEmpresa): bool
    {
        $nome = trim($nomeEmpresa) ?: 'amigo(a)';
        $msg  = "Olá, *{$nome}*! 🎉\n\n"
              . "Que alegria ter você no *FixaOS*! A partir de agora sua assistência técnica tem um sistema completo "
              . "pra cuidar de OS, clientes, estoque e finanças — tudo num lugar só, sem complicação.\n\n"
              . "Seu *período grátis de 30 dias* já começou a contar. 😉\n\n"
              . "Qualquer dúvida, é só responder aqui — a gente adora ajudar. Conte com a gente! 💙\n"
              . "— Equipe FixaOS";
        return self::sendTextInst(self::instanciaPlataforma(), $numero, $msg);
    }

    // ───────────────────────── Por EMPRESA (cada uma no seu número) ─────────────────────────
    public static function statusEmpresa(int $empresaId): string
    {
        return self::statusInst(self::instanciaEmpresa($empresaId));
    }

    /** Garante a instância da empresa e devolve o QR pra conectar (null se já conectado). */
    public static function qrEmpresa(int $empresaId): ?string
    {
        $inst = self::instanciaEmpresa($empresaId);
        if (self::statusInst($inst) === 'open') return null;
        self::criarInst($inst);
        return self::qrInst($inst);
    }

    /** Número (só dígitos) conectado na instância da empresa, ou null. */
    public static function numeroEmpresa(int $empresaId): ?string
    {
        $inst = self::instanciaEmpresa($empresaId);
        $r = self::api('GET', '/instance/fetchInstances?instanceName=' . $inst, null, 10);
        $body = $r['body'];
        $lista = isset($body[0]) ? $body : [$body];
        foreach ($lista as $it) {
            $jid = $it['ownerJid'] ?? ($it['instance']['owner'] ?? ($it['owner'] ?? ''));
            if ($jid) return preg_replace('/@.*/', '', $jid);
        }
        return null;
    }

    /** Desconecta (logout) o WhatsApp da empresa, mantendo a instância. */
    public static function desconectarEmpresa(int $empresaId): bool
    {
        $r = self::api('DELETE', '/instance/logout/' . self::instanciaEmpresa($empresaId), null, 10);
        return $r['code'] >= 200 && $r['code'] < 300;
    }

    public static function enviarTexto(int $empresaId, string $numero, string $texto): bool
    {
        return self::sendTextInst(self::instanciaEmpresa($empresaId), $numero, $texto);
    }

    /** Envia texto pelo número da PLATAFORMA (instância `fixaos`) — usado pelo bot de suporte. */
    public static function enviarTextoPlataforma(string $numero, string $texto): bool
    {
        return self::sendTextInst(self::instanciaPlataforma(), $numero, $texto);
    }

    public static function enviarDocumento(int $empresaId, string $numero, string $base64Pdf, string $fileName, string $caption = ''): bool
    {
        return self::sendDocumentoInst(self::instanciaEmpresa($empresaId), $numero, $base64Pdf, $fileName, $caption);
    }
}
