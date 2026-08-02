<?php

namespace App\Services;

use App\Core\DB;

/**
 * Descobre MARCA e TIPO de um aparelho a partir do MODELO, quando a etiqueta
 * não mostra esses campos. Catálogo-primeiro (grátis) → web_search no comércio
 * brasileiro (custo travado) → aprende gravando no catálogo pra não pagar de novo.
 */
class EquipService
{
    /** @return array{marca:string,tipo:string,fonte:string}|null */
    public static function descobrirPorModelo(string $modelo): ?array
    {
        $key = self::modelKey($modelo);
        if (strlen($key) < 3) return null; // modelo curto demais pra ser confiável

        // 1) catálogo-primeiro (grátis)
        $cat = self::doCatalogo($key);
        if ($cat) {
            self::incrementaUso((int) $cat['id']);
            return ['marca' => (string) $cat['marca'], 'tipo' => (string) $cat['tipo'], 'fonte' => 'catalogo'];
        }

        // 2) busca no Brasil (custo travado)
        $r = self::buscarBR($modelo);
        if (!$r || (($r['marca'] ?? '') === '' && ($r['tipo'] ?? '') === '')) return null;

        // 3) aprende
        self::salvar($key, $modelo, $r);
        return ['marca' => $r['marca'] ?? '', 'tipo' => $r['tipo'] ?? '', 'fonte' => 'web'];
    }

    /** web_search básico + max_uses:1 = custo travado (~R$0,20). Retorna marca/tipo em MAIÚSCULAS. */
    public static function buscarBR(string $modelo): ?array
    {
        $apiKey = IAService::apiKey();
        if ($apiKey === '') return null;

        $model  = IAService::cfg('ia_modelo_visao') ?: 'claude-sonnet-5';
        $system = 'Você identifica aparelhos eletrônicos e eletrodomésticos pelo modelo, usando a busca na web E seu conhecimento. '
                . 'Esses modelos são conhecidos no varejo brasileiro; preencha marca e tipo quando tiver razoável certeza. '
                . 'Só deixe vazio se realmente não reconhecer o modelo. Responda SOMENTE com JSON válido, em MAIÚSCULAS.';
        $pergunta = "Aparelho de modelo \"{$modelo}\". Pesquise no Brasil e diga a MARCA e o TIPO do aparelho. "
                  . 'Para TVs, inclua tecnologia e polegadas no formato TV DE LED 32 — só o número, SEM aspas nem símbolo de polegada (deduza as polegadas pelo modelo). '
                  . 'Para outros, use um tipo curto (ex.: MICRO-ONDAS, NOTEBOOK, CELULAR, GELADEIRA, MONITOR). '
                  . 'Responda só com este JSON: {"marca":"","tipo":""}';

        $payload = [
            'model'      => $model,
            'max_tokens' => 200,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $pergunta]],
            'tools'      => [[
                'type'          => 'web_search_20250305',
                'name'          => 'web_search',
                'max_uses'      => 1,
                'user_location' => ['type' => 'approximate', 'country' => 'BR', 'city' => 'São Paulo', 'region' => 'São Paulo', 'timezone' => 'America/Sao_Paulo'],
            ]],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => ['content-type: application/json', 'x-api-key: ' . $apiKey, 'anthropic-version: 2023-06-01'],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code !== 200) return null;

        $j = json_decode($resp, true);
        if (!is_array($j) || empty($j['content'])) return null;
        $texto = '';
        foreach ($j['content'] as $b) {
            if (($b['type'] ?? '') === 'text') $texto .= $b['text'];
        }
        if (!preg_match('/\{.*\}/s', $texto, $m)) return null;
        $d = json_decode($m[0], true);
        if (!is_array($d)) return null;

        $up   = static fn($s) => mb_strtoupper(trim((string) $s), 'UTF-8');
        $tipo = trim(str_replace(['"', '”', '“', "'", '’'], '', $up($d['tipo'] ?? '')));
        return ['marca' => $up($d['marca'] ?? ''), 'tipo' => $tipo];
    }

    private static function modelKey(string $m): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $m));
    }

    private static function doCatalogo(string $key): ?array
    {
        $st = DB::pdo()->prepare("SELECT * FROM catalogo_modelos WHERE model_key = ? LIMIT 1");
        $st->execute([$key]);
        return $st->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private static function salvar(string $key, string $modelo, array $r): void
    {
        DB::pdo()->prepare(
            "INSERT INTO catalogo_modelos (model_key, modelo, marca, tipo, fonte, vezes_usada)
             VALUES (?, ?, ?, ?, 'web', 1)
             ON DUPLICATE KEY UPDATE marca = VALUES(marca), tipo = VALUES(tipo), vezes_usada = vezes_usada + 1"
        )->execute([$key, $modelo, $r['marca'] ?? '', $r['tipo'] ?? '']);
    }

    private static function incrementaUso(int $id): void
    {
        DB::pdo()->prepare("UPDATE catalogo_modelos SET vezes_usada = vezes_usada + 1 WHERE id = ?")->execute([$id]);
    }
}
