<?php

namespace App\Services;

/**
 * Envio de e-mail via SMTP (cliente próprio, sem dependências externas).
 * Suporta SSL (porta 465) e STARTTLS (porta 587), AUTH LOGIN.
 * Best-effort: qualquer falha é silenciosa e NUNCA quebra o fluxo que chamou.
 */
class EmailService
{
    /** Quando true, registra a conversa SMTP em self::$log (uso: diagnóstico via tools/test-email.php). */
    public static bool $debug = false;
    /** @var string[] Log da última conversa SMTP quando $debug está ligado. */
    public static array $log = [];

    private static function cfg(): array
    {
        $f = BASE_PATH . '/config/email.php';
        return is_file($f) ? (require $f) : ['enabled' => false];
    }

    /**
     * Envia um e-mail HTML. Retorna true em sucesso.
     * $anexos: lista de ['filename'=>, 'mime'=>, 'data'=> (binário)].
     */
    public static function send(string $toEmail, string $toName, string $assunto, string $html, array $anexos = []): bool
    {
        // Modo demonstração nunca envia comunicação real (evita spam pela conta demo).
        if (!empty($_SESSION['demo_mode'])) return false;

        $cfg = self::cfg();
        if (empty($cfg['enabled']) || empty($cfg['host'])) return false;
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return false;

        $host   = $cfg['host'];
        $port   = (int) ($cfg['port'] ?? 465);
        $secure = $cfg['secure'] ?? 'ssl';
        $user   = $cfg['username'] ?? '';
        $pass   = $cfg['password'] ?? '';
        $fromE  = $cfg['from_email'] ?? $user;
        $fromN  = $cfg['from_name'] ?? 'FixaOS';

        if (self::$debug) self::$log = [];
        $logLine = function (string $s): void { if (self::$debug) self::$log[] = rtrim($s); };

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) { $logLine("CONEXÃO FALHOU em {$remote}: [{$errno}] {$errstr}"); return false; }
        stream_set_timeout($fp, 15);

        $read = function () use ($fp, $logLine): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break; // fim da resposta multiline
            }
            $logLine('S: ' . $data);
            return $data;
        };
        $cmd = function (string $c) use ($fp, $read, $logLine): string {
            // não loga o conteúdo de credenciais em base64
            $logLine('C: ' . (preg_match('~^[A-Za-z0-9+/=]{8,}$~', $c) ? '****** (base64)' : $c));
            fwrite($fp, $c . "\r\n");
            return $read();
        };

        $ok = true;
        try {
            $read();                                   // 220 banner
            $ehlo = $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'fixaos.com.br'));

            if ($secure === 'tls') {
                $cmd('STARTTLS');
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return false; }
                $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'fixaos.com.br'));
            }

            if ($user !== '') {
                $cmd('AUTH LOGIN');
                $r = $cmd(base64_encode($user));
                $r = $cmd(base64_encode($pass));
                if (strncmp($r, '235', 3) !== 0) { $ok = false; }
            }

            if ($ok) {
                $cmd('MAIL FROM:<' . $fromE . '>');
                $cmd('RCPT TO:<' . $toEmail . '>');
                $cmd('DATA');

                $headers  = 'From: ' . self::mime($fromN) . ' <' . $fromE . ">\r\n";
                $headers .= 'To: ' . self::mime($toName) . ' <' . $toEmail . ">\r\n";
                $headers .= 'Subject: ' . self::mime($assunto) . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= 'Date: ' . date('r') . "\r\n";

                if (!empty($anexos)) {
                    $boundary = 'fixaos_' . bin2hex(random_bytes(12));
                    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
                    $corpo  = "--{$boundary}\r\n";
                    $corpo .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
                    $corpo .= $html . "\r\n";
                    foreach ($anexos as $att) {
                        $fn    = self::mime($att['filename'] ?? 'anexo');
                        $mimeA = $att['mime'] ?? 'application/octet-stream';
                        $corpo .= "--{$boundary}\r\n";
                        $corpo .= "Content-Type: {$mimeA}; name=\"{$fn}\"\r\n";
                        $corpo .= "Content-Transfer-Encoding: base64\r\n";
                        $corpo .= "Content-Disposition: attachment; filename=\"{$fn}\"\r\n\r\n";
                        $corpo .= chunk_split(base64_encode($att['data'] ?? '')) . "\r\n";
                    }
                    $corpo .= "--{$boundary}--";
                    $body = $corpo;
                } else {
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $body = $html;
                }

                $body = preg_replace('/^\./m', '..', $body);   // dot-stuffing
                $r = $cmd($headers . "\r\n" . $body . "\r\n.");
                if (strncmp($r, '250', 3) !== 0) $ok = false;
            }
            $cmd('QUIT');
        } catch (\Throwable $e) {
            $ok = false;
        }
        if (is_resource($fp)) fclose($fp);
        return $ok;
    }

    private static function mime(string $s): string
    {
        return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    /**
     * Registro fotográfico do estado de entrada do equipamento.
     * As fotos vão apenas como ANEXO — não são armazenadas no servidor.
     * $ctx: empresa, cliente, equipamento, os, data.
     */
    public static function fotosEntrada(string $toEmail, string $toName, array $ctx, array $anexos): bool
    {
        $emp    = htmlspecialchars($ctx['empresa'] ?? 'FixaOS', ENT_QUOTES, 'UTF-8');
        $cli    = htmlspecialchars($ctx['cliente'] ?? '—', ENT_QUOTES, 'UTF-8');
        $equip  = htmlspecialchars($ctx['equipamento'] ?? '—', ENT_QUOTES, 'UTF-8');
        $os     = htmlspecialchars((string)($ctx['os'] ?? ''), ENT_QUOTES, 'UTF-8');
        $quando = htmlspecialchars($ctx['data'] ?? date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
        $qtd    = count($anexos);
        $linhaOs = $os !== '' ? "<tr><td style='padding:4px 0;color:#64748b'>OS</td><td style='padding:4px 0;text-align:right;font-weight:600'>#{$os}</td></tr>" : '';

        $html = <<<HTML
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:28px 12px"><tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06)">
      <tr><td style="background:#1e3a5f;padding:22px 28px;color:#fff">
        <span style="font-size:22px;font-weight:900">Fixa<span style="color:#f97316">OS</span></span>
        <div style="font-size:13px;color:#cbd5e1;margin-top:2px">📸 Registro fotográfico de entrada</div>
      </td></tr>
      <tr><td style="padding:26px 28px">
        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155">
          Segue o <strong>registro do estado do equipamento na entrada</strong>, com <strong>{$qtd} foto(s)</strong> em anexo.
          Guarde este e-mail — ele comprova o estado do aparelho no momento do recebimento.
        </p>
        <table width="100%" style="font-size:14px;color:#0f172a;border-top:1px solid #e2e8f0;margin-top:8px">
          <tr><td style="padding:4px 0;color:#64748b">Empresa</td><td style="padding:4px 0;text-align:right;font-weight:600">{$emp}</td></tr>
          <tr><td style="padding:4px 0;color:#64748b">Cliente</td><td style="padding:4px 0;text-align:right;font-weight:600">{$cli}</td></tr>
          <tr><td style="padding:4px 0;color:#64748b">Equipamento</td><td style="padding:4px 0;text-align:right;font-weight:600">{$equip}</td></tr>
          {$linhaOs}
          <tr><td style="padding:4px 0;color:#64748b">Data/hora</td><td style="padding:4px 0;text-align:right;font-weight:600">{$quando}</td></tr>
        </table>
      </td></tr>
      <tr><td style="padding:18px 28px;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px">
        © FixaOS — as fotos não ficam armazenadas no sistema, apenas neste e-mail.
      </td></tr>
    </table>
  </td></tr></table>
</body></html>
HTML;

        $assunto = 'Estado de entrada — ' . ($ctx['equipamento'] ?? 'Equipamento')
                 . ($os !== '' ? " (OS #{$os})" : '') . ' — ' . ($ctx['cliente'] ?? '');
        return self::send($toEmail, $toName, $assunto, $html, $anexos);
    }

    /** E-mail de boas-vindas para uma assistência recém-cadastrada. */
    public static function boasVindas(string $email, string $nomeEmpresa): bool
    {
        $cfg = require BASE_PATH . '/config/app.php';
        $painel = rtrim($cfg['url'], '/') . '/dashboard';
        $nome = htmlspecialchars(trim($nomeEmpresa) ?: 'amigo(a)', ENT_QUOTES, 'UTF-8');

        $html = self::template($nome, $painel);
        return self::send($email, $nomeEmpresa, "Bem-vindo(a) ao FixaOS, {$nome}! 🎉", $html);
    }

    /** Agradecimento + guia detalhado de edição, enviado a quem reivindica o perfil no diretório. */
    public static function perfilReivindicado(string $email, string $nome, string $empresaNome): bool
    {
        $cfg   = require BASE_PATH . '/config/app.php';
        $login = rtrim($cfg['url'], '/') . '/login';
        $n     = htmlspecialchars(explode(' ', trim($nome))[0] ?: 'amigo(a)', ENT_QUOTES, 'UTF-8');
        $emp   = htmlspecialchars(trim($empresaNome) ?: 'sua empresa', ENT_QUOTES, 'UTF-8');
        $html  = self::templateReivindicado($n, $emp, $login);
        return self::send($email, $nome, "Bem-vindo ao FixaOS — o perfil da {$emp} agora é seu! 🎉", $html);
    }

    private static function templateReivindicado(string $nome, string $emp, string $login): string
    {
        $item = function (string $ic, string $titulo, string $desc): string {
            return '<tr>
                <td valign="top" style="width:30px;font-size:18px;padding:7px 8px 7px 0">' . $ic . '</td>
                <td style="padding:7px 0;font-size:14px;line-height:1.55;color:#475569">
                  <strong style="color:#0f172a">' . $titulo . '</strong><br>' . $desc . '
                </td></tr>';
        };
        $edicao =
            $item('📸', 'Fotos', 'Uma foto principal + galeria de até 4 fotos da sua assistência (fachada, bancada, equipe).') .
            $item('📝', 'Descrição', 'Apresente sua história, especialidades e o que faz sua assistência ser diferente.') .
            $item('🔧', 'Serviços', 'O que você conserta — celular, TV, notebook, geladeira, ar-condicionado…') .
            $item('📍', 'Endereço', 'Aparece no mapa e na busca por CEP, cidade e raio de distância.') .
            $item('📱', 'Contato', 'Telefone, WhatsApp e e-mail públicos, com botão de contato direto.') .
            $item('🌐', 'Redes sociais', 'Facebook, Instagram, YouTube e TikTok.');
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <tr><td style="background:#1e3a5f;padding:26px 32px;text-align:center">
          <span style="font-size:26px;font-weight:900;color:#fff;letter-spacing:-.5px">Fixa<span style="color:#f97316">OS</span></span>
        </td></tr>
        <tr><td style="padding:34px 32px 6px">
          <h1 style="margin:0 0 8px;font-size:22px;color:#0f172a">Olá, {$nome}! 🎉</h1>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#475569">
            Você acaba de <strong>reivindicar o perfil da {$emp}</strong> no diretório do FixaOS. A partir de agora, essa
            página é <strong>sua para gerenciar</strong> — e é de graça. Obrigado por confiar na gente! 💙
          </p>

          <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin:0 0 22px">
            <p style="margin:0;font-size:14px;line-height:1.6;color:#1e3a5f">
              <strong>Como acessar:</strong> entre em <a href="{$login}" style="color:#2563eb;font-weight:bold">{$login}</a>
              com o seu e-mail e a senha que você criou. Você cai direto em <strong>“Minha Empresa na Web”</strong>.
            </p>
          </div>

          <p style="margin:0 0 6px;font-size:16px;font-weight:bold;color:#0f172a">O que você pode editar no seu perfil:</p>
          <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 22px">{$edicao}</table>

          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;margin:0 0 24px">
            <p style="margin:0 0 6px;font-size:14px;font-weight:bold;color:#166534">Por que vale a pena manter atualizado:</p>
            <p style="margin:0;font-size:13.5px;line-height:1.6;color:#166534">
              ✔ Sua assistência fica <strong>encontrável no Google</strong> (SEO otimizado)<br>
              ✔ Clientes te acham por <strong>proximidade</strong> (busca por CEP e raio)<br>
              ✔ Você recebe <strong>avaliações verificadas</strong> de clientes reais
            </p>
          </div>

          <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#475569">
            E tem mais chegando: o <strong>sistema completo de gestão</strong> (ordens de serviço, financeiro, estoque, PDV,
            WhatsApp…) está nos ajustes finais. <strong>Você será um dos primeiros convidados</strong> a testar — a gente
            te avisa por aqui. 😉
          </p>

          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 26px"><tr><td style="border-radius:12px;background:#f97316">
            <a href="{$login}" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:700;color:#fff;text-decoration:none;border-radius:12px">▶ Gerenciar meu perfil</a>
          </td></tr></table>

          <p style="margin:0;font-size:14px;color:#475569">Qualquer dúvida, é só responder este e-mail.<br>Com carinho,<br><strong>Equipe FixaOS</strong> 💙</p>
        </td></tr>
        <tr><td style="padding:22px 32px;border-top:1px solid #e2e8f0;text-align:center">
          <p style="margin:0;font-size:12px;color:#94a3b8">© FixaOS — Gestão para assistências técnicas · fixaos.com.br</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
    }

    private static function template(string $nome, string $painel): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <tr><td style="background:#1e3a5f;padding:28px 32px;text-align:center">
          <span style="font-size:26px;font-weight:900;color:#fff;letter-spacing:-.5px">Fixa<span style="color:#f97316">OS</span></span>
        </td></tr>
        <tr><td style="padding:36px 32px 8px">
          <h1 style="margin:0 0 8px;font-size:22px;color:#0f172a">Olá, {$nome}! 🎉</h1>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#475569">
            Seja muito bem-vindo(a) à família <strong>FixaOS</strong>. Você acaba de dar um passo e tanto pra organizar
            sua assistência técnica de verdade.
          </p>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#475569">
            Aqui você encontra tudo o que precisa — <strong>ordens de serviço, clientes, estoque, financeiro</strong> e
            até uma página pública pra seus clientes te acharem no Google. E o melhor: você aprende a usar em minutos.
          </p>
          <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569">
            Seu <strong>teste grátis de 30 dias</strong> já está rolando. Aproveite sem pressa. 😉
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 28px"><tr><td style="border-radius:12px;background:#f97316">
            <a href="{$painel}" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:700;color:#fff;text-decoration:none;border-radius:12px">▶ Acessar meu painel</a>
          </td></tr></table>
          <p style="margin:0 0 6px;font-size:14px;line-height:1.7;color:#475569">
            Precisa de uma mãozinha? É só responder este e-mail — estamos aqui pra você.
          </p>
          <p style="margin:0;font-size:14px;color:#475569">Com carinho,<br><strong>Equipe FixaOS</strong> 💙</p>
        </td></tr>
        <tr><td style="padding:24px 32px;border-top:1px solid #e2e8f0;text-align:center">
          <p style="margin:0;font-size:12px;color:#94a3b8">© FixaOS — Gestão para assistências técnicas</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
    }
}
