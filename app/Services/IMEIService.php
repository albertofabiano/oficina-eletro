<?php

namespace App\Services;

use App\Core\DB;

/**
 * Consulta de IMEI — AGNÓSTICA de fornecedor.
 * A URL da API é um template com {imei} e {key} (ex.: imei.info), guardado em sistema_config.
 * Estratégia de custo: cache global (mesmo IMEI/modelo nunca consulta 2x) + cap mensal por empresa.
 * Sempre best-effort: nunca lança exceção pra cima.
 */
class IMEIService
{
    private static function cfg(string $chave, ?string $default = null): ?string
    {
        try {
            $st = DB::pdo()->prepare("SELECT valor FROM sistema_config WHERE chave = ?");
            $st->execute([$chave]);
            $v = $st->fetchColumn();
            return ($v === false || $v === null) ? $default : $v;
        } catch (\Throwable $e) { return $default; }
    }

    public static function apiKey(): string { return (string) self::cfg('imei_api_key', ''); }
    public static function apiUrl(): string { return (string) self::cfg('imei_api_url', ''); }
    public static function limiteMes(): int { return (int) self::cfg('imei_limite_mes', '100'); }
    public static function flagAtivo(): bool { return self::cfg('imei_ativo', '0') === '1'; }
    /** API ligada = flag ativa + chave + url com {imei}. */
    public static function ativo(): bool
    {
        return self::cfg('imei_ativo', '0') === '1'
            && self::apiKey() !== ''
            && str_contains(self::apiUrl(), '{imei}');
    }

    /** Só dígitos; IMEI válido tem 15 dígitos (com Luhn) ou aceitamos 14 (TAC+serial, sem dígito). */
    public static function limparValidar(string $imeiRaw): ?string
    {
        $n = preg_replace('/\D/', '', $imeiRaw);
        $len = strlen($n);
        if ($len === 15) return self::luhn($n) ? $n : null;
        if ($len === 14) return $n; // sem dígito verificador — segue (modelo pelo TAC ainda dá)
        return null;
    }

    private static function luhn(string $num): bool
    {
        $sum = 0; $alt = false;
        for ($i = strlen($num) - 1; $i >= 0; $i--) {
            $d = (int) $num[$i];
            if ($alt) { $d *= 2; if ($d > 9) $d -= 9; }
            $sum += $d; $alt = !$alt;
        }
        return $sum % 10 === 0;
    }

    /**
     * Consulta principal. Retorna:
     * ['ok'=>bool,'marca'=>?,'modelo'=>?,'blacklist'=>?,'blacklist_texto'=>?,'fonte'=>str,'cache'=>bool,'aviso'=>?,'erro'=>?]
     */
    public static function consultar(string $imeiRaw, ?int $empresaId): array
    {
        $imei = self::limparValidar($imeiRaw);
        if ($imei === null) {
            return ['ok' => false, 'erro' => 'IMEI inválido — confira os 15 dígitos (disque *#06# no aparelho).'];
        }
        $tac = substr($imei, 0, 8);
        $db  = DB::pdo();

        // 1) Cache exato do IMEI
        $st = $db->prepare("SELECT marca, modelo, blacklist, blacklist_texto FROM imei_consultas WHERE imei = ?");
        $st->execute([$imei]);
        if ($row = $st->fetch()) {
            return ['ok' => true, 'marca' => $row['marca'], 'modelo' => $row['modelo'],
                    'blacklist' => $row['blacklist'], 'blacklist_texto' => $row['blacklist_texto'],
                    'fonte' => 'cache', 'cache' => true];
        }

        // 2) API desligada → tenta modelo pelo TAC já conhecido (grátis); senão, orienta Anatel
        if (!self::ativo()) {
            $st2 = $db->prepare("SELECT marca, modelo FROM imei_consultas WHERE tac = ? AND modelo IS NOT NULL LIMIT 1");
            $st2->execute([$tac]);
            if ($r2 = $st2->fetch()) {
                return ['ok' => true, 'marca' => $r2['marca'], 'modelo' => $r2['modelo'],
                        'blacklist' => null, 'fonte' => 'cache-tac', 'cache' => true,
                        'aviso' => 'Modelo pela nossa base. Bloqueio/roubo: use o botão da Anatel.'];
            }
            return ['ok' => false, 'erro' => 'A consulta automática por IMEI ainda não foi ativada. Use o botão "Consultar na Anatel".', 'anatel' => true];
        }

        // 3) Cap mensal por empresa (0 = ilimitado)
        $lim = self::limiteMes();
        $mes = date('Y-m');
        if ($lim > 0 && $empresaId) {
            $u = $db->prepare("SELECT chamadas FROM imei_uso WHERE empresa_id = ? AND ano_mes = ?");
            $u->execute([$empresaId, $mes]);
            if ((int) $u->fetchColumn() >= $lim) {
                return ['ok' => false, 'limite' => true, 'erro' => "Limite de {$lim} consultas de IMEI neste mês atingido. Preencha manualmente ou consulte na Anatel."];
            }
        }

        // 4) Chamada real à API
        $r = self::chamarApi($imei);
        if (!$r['ok']) return $r;

        // 5) Grava cache + incrementa uso
        try {
            $db->prepare("INSERT INTO imei_consultas (imei, tac, marca, modelo, blacklist, blacklist_texto, fonte, payload)
                          VALUES (?,?,?,?,?,?,?,?)
                          ON DUPLICATE KEY UPDATE marca=VALUES(marca), modelo=VALUES(modelo),
                            blacklist=VALUES(blacklist), blacklist_texto=VALUES(blacklist_texto), payload=VALUES(payload)")
               ->execute([$imei, $tac, $r['marca'], $r['modelo'], $r['blacklist'], $r['blacklist_texto'], 'api', $r['payload'] ?? null]);
            if ($empresaId) {
                $db->prepare("INSERT INTO imei_uso (empresa_id, ano_mes, chamadas) VALUES (?,?,1)
                              ON DUPLICATE KEY UPDATE chamadas = chamadas + 1")->execute([$empresaId, $mes]);
            }
        } catch (\Throwable $e) { /* cache é best-effort */ }

        return ['ok' => true, 'marca' => $r['marca'], 'modelo' => $r['modelo'],
                'blacklist' => $r['blacklist'], 'blacklist_texto' => $r['blacklist_texto'],
                'fonte' => 'api', 'cache' => false];
    }

    /** Chama a API do fornecedor (URL template com {imei}/{key}) e normaliza a resposta. */
    private static function chamarApi(string $imei): array
    {
        $url = str_replace(['{imei}', '{key}'], [$imei, rawurlencode(self::apiKey())], self::apiUrl());
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_CONNECTTIMEOUT => 6,
            CURLOPT_HTTPHEADER => ['Accept: application/json'],
        ]);
        $res = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($res === false) return ['ok' => false, 'erro' => 'Falha de conexão com a API de IMEI: ' . $err];
        $j = json_decode((string) $res, true);
        if (!is_array($j)) return ['ok' => false, 'erro' => 'Resposta inesperada da API de IMEI (HTTP ' . $code . ').'];

        $marca  = self::pick($j, ['brand', 'marca', 'manufacturer', 'make']);
        $modelo = self::pick($j, ['model', 'modelo', 'modelName', 'name', 'device']);
        $blRaw  = self::pick($j, ['blacklist', 'blacklistStatus', 'gsma_blacklist', 'status', 'blacklisted']);

        if ($marca === null && $modelo === null) {
            $apiErr = self::pick($j, ['error', 'message', 'erro']);
            return ['ok' => false, 'erro' => $apiErr ? ('API: ' . $apiErr) : 'A API não retornou marca/modelo para este IMEI.'];
        }

        [$bl, $blTxt] = self::normalizarBlacklist($blRaw);
        return ['ok' => true, 'marca' => $marca, 'modelo' => $modelo,
                'blacklist' => $bl, 'blacklist_texto' => $blTxt, 'payload' => mb_substr((string) $res, 0, 60000)];
    }

    /** Procura (case-insensitive, recursivo) o primeiro valor escalar sob uma das chaves candidatas. */
    private static function pick(array $data, array $candidatas): ?string
    {
        $low = array_map('strtolower', $candidatas);
        $found = null;
        array_walk_recursive($data, function ($v, $k) use ($low, &$found) {
            if ($found !== null) return;
            if (is_scalar($v) && in_array(strtolower((string) $k), $low, true)) {
                $s = trim((string) $v);
                if ($s !== '') $found = $s;
            }
        });
        return $found;
    }

    /** Normaliza status de blacklist de vários formatos para limpo/bloqueado/desconhecido + texto. */
    private static function normalizarBlacklist(?string $raw): array
    {
        if ($raw === null || $raw === '') return [null, null];
        $l = strtolower($raw);
        if (in_array($l, ['clean', 'clear', 'no', 'false', '0', 'not blacklisted', 'not blocked'], true)
            || str_contains($l, 'clean') || str_contains($l, 'não') || str_contains($l, 'nao')) {
            return ['limpo', 'Sem registro de bloqueio (blacklist global)'];
        }
        if (in_array($l, ['blacklisted', 'blocked', 'yes', 'true', '1', 'lost', 'stolen'], true)
            || str_contains($l, 'black') || str_contains($l, 'block') || str_contains($l, 'stolen') || str_contains($l, 'lost')) {
            return ['bloqueado', 'ATENÇÃO: aparelho aparece na blacklist global (' . mb_substr($raw, 0, 120) . ')'];
        }
        return ['desconhecido', mb_substr($raw, 0, 200)];
    }

    /** Teste rápido pro painel master (consome 1 consulta real). IMEI de exemplo público. */
    public static function testar(): array
    {
        if (!self::ativo()) return ['ok' => false, 'erro' => 'Configure a URL (com {imei}), a chave e ligue "ativo" antes de testar.'];
        return self::chamarApi('490154203237518');
    }
}
