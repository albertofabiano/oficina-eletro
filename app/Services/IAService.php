<?php

namespace App\Services;

use App\Core\DB;

/**
 * Integração com a IA (Anthropic / Claude) — cérebro do bot de suporte.
 * A chave e o modelo ficam em `sistema_config` (só o master edita). Nunca vão pro Git.
 */
class IAService
{
    public static function cfg(string $chave, ?string $default = null): ?string
    {
        try {
            $st = DB::pdo()->prepare("SELECT valor FROM sistema_config WHERE chave = ?");
            $st->execute([$chave]);
            $v = $st->fetchColumn();
            return ($v === false || $v === null) ? $default : $v;
        } catch (\Throwable $e) { return $default; }
    }

    public static function apiKey(): string { return (string) self::cfg('ia_api_key', ''); }
    public static function modelo(): string { return (string) self::cfg('ia_modelo', 'claude-haiku-4-5-20251001'); }
    public static function ativo(): bool    { return self::cfg('ia_ativo', '0') === '1' && self::apiKey() !== ''; }

    /**
     * Chama a IA. $mensagens = [['role'=>'user'|'assistant','content'=>...], ...].
     * Retorna ['ok'=>bool, 'texto'=>string, 'erro'=>?string].
     */
    public static function perguntar(array $mensagens, string $system = '', int $maxTokens = 600, ?string $modelo = null): array
    {
        $key = self::apiKey();
        if ($key === '') return ['ok' => false, 'texto' => '', 'erro' => 'Chave da API não configurada.'];

        $payload = [
            'model'      => $modelo ?: self::modelo(),
            'max_tokens' => $maxTokens,
            'messages'   => $mensagens,
        ];
        if ($system !== '') $payload['system'] = [[
            'type' => 'text',
            'text' => $system,
            'cache_control' => ['type' => 'ephemeral'],
        ]];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT    => 30,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $cerr = curl_error($ch);
        curl_close($ch);

        if ($res === false) return ['ok' => false, 'texto' => '', 'erro' => 'Falha de conexão: ' . $cerr];
        $j = json_decode((string) $res, true);

        if ($code >= 200 && $code < 300 && !empty($j['content'][0]['text'])) {
            return ['ok' => true, 'texto' => trim($j['content'][0]['text']), 'erro' => null];
        }
        $erro = $j['error']['message'] ?? ('HTTP ' . $code);
        return ['ok' => false, 'texto' => '', 'erro' => $erro];
    }

    /** Teste rápido de conexão (usado no painel master). */
    public static function testar(): array
    {
        return self::perguntar(
            [['role' => 'user', 'content' => 'Responda em português apenas com: OK, conexão funcionando.']],
            'Você é um verificador de conexão. Seja breve.',
            50
        );
    }
}
