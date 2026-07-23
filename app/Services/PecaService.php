<?php

namespace App\Services;

use App\Core\DB;

/**
 * Identificação de peça/placa de eletrônico a partir da foto.
 *
 * Fluxo: VisionService lê o part number → catálogo compartilhado (grátis) →
 * se não achar, busca no comércio brasileiro (web_search, custo travado) e
 * grava no catálogo pra próxima consulta ser de graça. Só eletrônico.
 */
class PecaService
{
    /** Foto da placa → dados estruturados prontos pro anúncio/estoque. */
    public static function identificar(string $caminhoImagem): ?array
    {
        $leitura = VisionService::lerPlaca($caminhoImagem);
        if (!$leitura) return null;
        return self::resolver($leitura);
    }

    /** Part number digitado à mão → dados estruturados (fallback quando a foto não lê). */
    public static function porCodigo(string $partNumber, string $tipo = ''): ?array
    {
        $pn = trim($partNumber);
        if ($pn === '') return null;
        return self::resolver(['codigo' => $pn, 'tipo' => $tipo, 'marca' => '']);
    }

    /** Catálogo-primeiro (grátis) → busca no Brasil → grava no catálogo compartilhado. */
    private static function resolver(array $leitura): array
    {
        $pn = $leitura['codigo'] ?? '';
        if ($pn === '') {
            // sem código legível → devolve o que deu pra ler (preenchimento manual)
            return self::normalizar($leitura, [], 'foto', 'ia');
        }

        // 1) catálogo-primeiro (grátis)
        $cat = self::doCatalogo($pn);
        if ($cat) {
            self::incrementaUso((int) $cat['id']);
            return self::normalizar($leitura, $cat, 'catalogo', $cat['confianca'] ?? 'ia');
        }

        // 2) busca no Brasil (custo travado: web_search max_uses:1)
        $busca = self::buscarBR($pn, $leitura['tipo'] ?? '') ?? [];
        $dados = self::normalizar($leitura, $busca, 'web', 'ia');

        // 3) grava no catálogo compartilhado
        self::salvarCatalogo($pn, $dados, null);
        return $dados;
    }

    /** Monta o array de saída padronizado, combinando leitura + extra (catálogo ou web). */
    private static function normalizar(array $leitura, array $extra, string $fonte, string $confianca): array
    {
        $dec = static function ($v): array {
            if (is_array($v)) return array_values(array_filter(array_map('trim', $v)));
            if (is_string($v) && $v !== '') { $j = json_decode($v, true); return is_array($j) ? $j : array_filter(array_map('trim', explode(',', $v))); }
            return [];
        };
        $modelos = $dec($extra['modelos'] ?? $extra['modelos_compativeis'] ?? []);
        $cross   = $dec($extra['cross_ref'] ?? []);

        $codigo = $leitura['codigo'] ?? '';
        $tipo   = ($leitura['tipo']  ?? '') ?: ($extra['tipo']  ?? '');
        $tipo   = trim(preg_replace('/\s*\(.*?\)/u', '', $tipo)); // tira "(Main Board)" etc.
        $marca  = ($extra['marca']   ?? '') ?: ($leitura['marca'] ?? '');
        // se ninguém trouxe a marca, tenta inferir pelo prefixo do part number
        if ($marca === '' && $codigo !== '') $marca = self::marcaPorCodigo($codigo);

        // modelo "principal" (primeiro compatível) — vai no campo Modelo do anúncio
        $modelo = $modelos[0] ?? '';

        // título: sempre começa pelo Tipo → Tipo Marca Modelo - Código, em Caixa de Título
        $partes = array_filter([$tipo ?: 'Placa', $marca, $modelo]);
        $titulo = trim(implode(' ', $partes));
        if ($codigo !== '') $titulo .= ' - ' . $codigo;
        $titulo = self::tituloCaso($titulo);

        $tecnologia = $extra['tecnologia'] ?? '';
        $tamanho    = $extra['tamanho'] ?? '';

        // descrição sugerida (compatibilidade) pro anúncio
        $desc = '';
        if ($modelos) $desc .= 'Compatível com: ' . implode(', ', $modelos) . '. ';
        if ($cross)   $desc .= 'Códigos equivalentes: ' . implode(', ', $cross) . '. ';
        $extras = array_filter([$tecnologia, $tamanho]);
        if ($extras)  $desc .= implode(' · ', $extras) . '.';

        return [
            'codigo'      => $codigo,
            'titulo'      => $titulo,
            'tipo'        => $tipo,
            'marca'       => $marca,
            'modelo'      => $modelo,
            'tecnologia'  => $tecnologia,
            'tamanho'     => $tamanho,
            'painel'      => $extra['painel'] ?? '',
            'modelos'     => $modelos,
            'cross_ref'   => $cross,
            'descricao'   => trim($desc),
            'fonte'       => $fonte,
            'confianca'   => $confianca,
        ];
    }

    /**
     * Caixa de Título: cada palavra em minúscula com a 1ª letra maiúscula.
     * Ex.: "PLACA principal TV LG 32LN5600" → "Placa Principal Tv Lg 32Ln5600".
     */
    private static function tituloCaso(string $s): string
    {
        return preg_replace_callback('/\S+/u', static function ($m) {
            $w = mb_strtolower($m[0], 'UTF-8');
            return preg_replace_callback('/\p{L}/u', static fn($x) => mb_strtoupper($x[0], 'UTF-8'), $w, 1);
        }, $s);
    }

    /** Infere a marca pelo prefixo do part number (fallback quando a busca não traz). */
    private static function marcaPorCodigo(string $codigo): string
    {
        $c = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $codigo));
        // só prefixos inequívocos, pra não errar a marca
        $mapa = [
            '/^BN\d/'                                  => 'Samsung',
            '/^(EAX|EBT|EBR|EBU|EAY|EAB|EAE|EBL|EAJ|6870|6871|6091)/' => 'LG',
            '/^715G/'                                  => 'Philips',
            '/^(TNP|TXN|TZZ)/'                         => 'Panasonic',
        ];
        foreach ($mapa as $re => $marca) {
            if (preg_match($re, $c)) return $marca;
        }
        return '';
    }

    /**
     * Busca o part number no comércio brasileiro e devolve os dados de compatibilidade.
     * web_search básico + max_uses:1 = custo travado (~R$0,20/consulta).
     */
    public static function buscarBR(string $partNumber, string $tipo = ''): ?array
    {
        $key = IAService::apiKey();
        if ($key === '') return null;

        $modelo = IAService::cfg('ia_modelo_visao') ?: 'claude-sonnet-5';
        $system = 'Você identifica peças de eletrônico (placas de TV, fontes, etc.) pesquisando no comércio '
                . 'brasileiro (Mercado Livre, Shopee, Magalu, OLX). NÃO invente dados: se não encontrar, deixe o campo vazio. '
                . 'Responda SOMENTE com JSON válido, sem texto fora do JSON.';
        $pergunta = "Peça com part number \"{$partNumber}\""
                  . ($tipo ? " (tipo: {$tipo})" : '') . ". "
                  . 'Pesquise no Brasil e diga: marca, tipo da peça, em quais MODELOS de aparelho ela é usada, '
                  . 'tamanho/polegadas quando aplicável, tecnologia (LED/OLED/LCD), código do painel/tela, e códigos equivalentes (cross reference). '
                  . 'Responda só com este JSON: '
                  . '{"marca":"","tipo":"","modelos":[],"tamanho":"","tecnologia":"","painel":"","cross_ref":[]}';

        $payload = [
            'model'      => $modelo,
            'max_tokens' => 600,
            'system'     => $system,
            'messages'   => [['role' => 'user', 'content' => $pergunta]],
            'tools'      => [[
                'type'          => 'web_search_20250305',
                'name'          => 'web_search',
                'max_uses'      => 1,
                'allowed_domains' => ['mercadolivre.com.br', 'shopee.com.br', 'magazineluiza.com.br', 'olx.com.br', 'americanas.com.br'],
                'user_location' => ['type' => 'approximate', 'country' => 'BR', 'city' => 'São Paulo', 'region' => 'São Paulo', 'timezone' => 'America/Sao_Paulo'],
            ]],
        ];

        $ch = curl_init('https://api.anthropic.com/v1/messages');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_HTTPHEADER     => [
                'content-type: application/json',
                'x-api-key: ' . $key,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload),
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($resp === false || $code !== 200) return null;

        $j = json_decode($resp, true);
        if (!is_array($j) || empty($j['content'])) return null;

        // concatena os blocos de texto da resposta
        $texto = '';
        foreach ($j['content'] as $bloco) {
            if (($bloco['type'] ?? '') === 'text') $texto .= $bloco['text'];
        }
        $dados = self::extrairJson($texto);
        return is_array($dados) ? $dados : null;
    }

    /** Extrai o primeiro objeto JSON de um texto (a IA às vezes embrulha em ```json). */
    private static function extrairJson(string $txt): ?array
    {
        if (preg_match('/\{.*\}/s', $txt, $m)) {
            $d = json_decode($m[0], true);
            if (is_array($d)) return $d;
        }
        return null;
    }

    // ─────────────────────────── Catálogo compartilhado ───────────────────────────

    public static function pnKey(string $pn): string
    {
        return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $pn));
    }

    /** Consulta o catálogo pelo part number normalizado. */
    public static function doCatalogo(string $pn): ?array
    {
        $key = self::pnKey($pn);
        if ($key === '') return null;
        $st = DB::pdo()->prepare("SELECT * FROM catalogo_pecas WHERE pn_key = ? LIMIT 1");
        $st->execute([$key]);
        $r = $st->fetch(\PDO::FETCH_ASSOC);
        return $r ?: null;
    }

    /** Grava/atualiza o catálogo compartilhado (confiança 'ia'; humano confirma depois). */
    public static function salvarCatalogo(string $pn, array $dados, ?int $eid): void
    {
        $key = self::pnKey($pn);
        if ($key === '') return;

        $sql = "INSERT INTO catalogo_pecas
                    (pn_key, part_number, tipo, marca, tecnologia, tamanho, painel,
                     modelos_compativeis, cross_ref, confianca, fonte, criado_por_empresa, vezes_usada)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'ia', 'web', ?, 1)
                ON DUPLICATE KEY UPDATE
                    tipo = VALUES(tipo), marca = VALUES(marca), tecnologia = VALUES(tecnologia),
                    tamanho = VALUES(tamanho), painel = VALUES(painel),
                    modelos_compativeis = VALUES(modelos_compativeis), cross_ref = VALUES(cross_ref),
                    vezes_usada = vezes_usada + 1";
        DB::pdo()->prepare($sql)->execute([
            $key,
            $pn,
            $dados['tipo'] ?? '',
            $dados['marca'] ?? '',
            $dados['tecnologia'] ?? '',
            $dados['tamanho'] ?? '',
            $dados['painel'] ?? '',
            json_encode($dados['modelos'] ?? [], JSON_UNESCAPED_UNICODE),
            json_encode($dados['cross_ref'] ?? [], JSON_UNESCAPED_UNICODE),
            $eid,
        ]);
    }

    private static function incrementaUso(int $id): void
    {
        DB::pdo()->prepare("UPDATE catalogo_pecas SET vezes_usada = vezes_usada + 1 WHERE id = ?")->execute([$id]);
    }
}
