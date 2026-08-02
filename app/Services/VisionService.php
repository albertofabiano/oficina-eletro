<?php

namespace App\Services;

/**
 * Leitura de etiqueta por IA de visão (Claude), reaproveitando a mesma chave/modelo
 * do bot de suporte (IAService -> sistema_config: ia_api_key / ia_modelo).
 * Se não houver chave, retorna null e o scanner segue no modo manual.
 */
class VisionService
{
    public static function disponivel(): bool
    {
        return IAService::apiKey() !== '';
    }

    /** @return array{marca:string,modelo:string,serie:string,tipo:string}|null */
    public static function lerEtiqueta(string $caminhoImagem): ?array
    {
        if (!is_file($caminhoImagem) || IAService::apiKey() === '') return null;

        $img = self::imagemBase64($caminhoImagem);
        if (!$img) return null; // formato não suportado (ex.: HEIC) -> modo manual

        $system = 'Você lê etiquetas de aparelhos eletrônicos e eletrodomésticos e extrai os dados. '
                . 'Leia com atenção, caractere por caractere; NÃO adivinhe nem complete. '
                . 'A foto pode estar girada (texto de lado ou de cabeça para baixo) — leve isso em conta. '
                . 'Responda SOMENTE com um JSON válido, sem comentários nem texto fora do JSON.';
        $prompt = 'Extraia da etiqueta na foto: marca, modelo, número de série e o TIPO do aparelho. '
                . 'TIPO: descreva o aparelho de forma objetiva. Para TVs, SEMPRE inclua a tecnologia e o tamanho em polegadas, '
                . 'no formato "TV DE LED 32", "TV DE OLED 55" ou "TV DE LCD 40". Deduza as polegadas pelo modelo — '
                . 'quase todo modelo traz o tamanho em 2 dígitos (ex.: Samsung UN32F5500 → 32; LG 32LN5600 → 32; Philco PTV43 → 43). '
                . 'Para outros aparelhos, use um tipo curto (ex.: "MICRO-ONDAS", "NOTEBOOK", "CELULAR", "MONITOR", "SOM", "GELADEIRA"). '
                . 'MODELO: é o modelo de VENDA do aparelho — um código alfanumérico (ex.: PTV32G70RCH, UN32F5500, 48L2400, 32LN5600, KDL-47W805A), '
                . 'às vezes logo após "TV", "MODELO", "MODEL", "Model No." ou "MODELO (VENDAS)". Em etiquetas LG prefira o "MODELO (VENDAS)". '
                . 'NÃO confunda o modelo com: código de homologação Anatel (ex.: 11266-20-11928), código da placa interna (ex.: WF-R12B-UWD3), '
                . 'código de barras/EAN, nem código de produto puramente numérico (ex.: 099323097). '
                . 'SÉRIE: use o campo "No. SÉRIE", "Serial" ou "S/N". '
                . 'Copie exatamente como impresso, sem trocar letras por números parecidos. '
                . 'RESPONDA TODOS OS VALORES EM LETRAS MAIÚSCULAS. '
                . 'ATENCAO a caracteres visualmente parecidos, comuns em etiquetas pequenas/desbotadas: '
                . '"T" tem barra horizontal reta no topo e haste vertical central; "1" costuma ter uma pequena '
                . 'serifa/flag no topo esquerdo e base plana mais larga. "S" e uma curva continua em forma de S; '
                . '"5" tem topo reto/quadrado; "6" tem um laco fechado na parte de baixo. "O" (letra) e mais '
                . 'ovalada/estreita; "0" (zero) costuma ser mais estreito e pode ter um traco ou ponto no meio '
                . 'em algumas fontes. "B" tem duas barrigas fechadas; "8" tambem, mas em fonte de etiqueta '
                . 'costuma ser mais estreito e sem a haste vertical reta da esquerda que o B tem. Se ficar em '
                . 'duvida entre dois caracteres parecidos, escolha o que fizer mais sentido dado o padrao tipico '
                . 'de modelos dessa marca (letras costumam vir no inicio/meio do codigo, numeros no final). '
                . 'Responda apenas com este JSON: {"marca":"","modelo":"","serie":"","tipo":""}. '
                . 'Se algum dado não estiver legível ou visível, deixe a string vazia. Não invente.';

        $mensagens = [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $img['mime'], 'data' => $img['b64']]],
                ['type' => 'text', 'text' => $prompt],
            ],
        ]];

        // Visão dedicada: Sonnet lê etiqueta pequena/girada bem melhor que Haiku.
        // Configurável em sistema_config 'ia_modelo_visao'; usa a MESMA chave (ia_api_key).
        $modelo = IAService::cfg('ia_modelo_visao') ?: 'claude-sonnet-5';
        $r = IAService::perguntar($mensagens, $system, 400, $modelo);
        if (empty($r['ok'])) return null;

        $d = self::parseJson((string) $r['texto']);
        if (!is_array($d)) return null;

        $up = static fn($s) => mb_strtoupper(trim((string) $s), 'UTF-8');
        return [
            'marca'  => $up($d['marca']  ?? ''),
            'modelo' => $up($d['modelo'] ?? ''),
            'serie'  => $up($d['serie']  ?? ''),
            'tipo'   => trim(str_replace(['"','”','“'], '', $up($d['tipo'] ?? ''))),
        ];
    }

    /** Lê a imagem, reduz p/ no máx. 1600px e devolve JPEG base64 (economia de token/latência). */
    /**
     * Lê a serigrafia/etiqueta de uma PLACA e devolve ['codigo','tipo','marca'].
     * @return array{codigo:string,tipo:string,marca:string}|null
     */
    public static function lerPlaca(string $caminhoImagem): ?array
    {
        if (!is_file($caminhoImagem) || IAService::apiKey() === '') return null;
        $img = self::imagemBase64($caminhoImagem);
        if (!$img) return null;

        $system = 'Você lê placas de aparelhos eletrônicos (TV, etc.) e extrai o código de identificação. '
                . 'Leia com atenção, caractere por caractere; NÃO adivinhe. A foto pode estar girada. '
                . 'Responda SOMENTE com JSON válido.';
        $prompt = 'Extraia da placa na foto: o PART NUMBER / código principal '
                . '(ex.: LG "EAX69532304", Samsung "BN94-12695R", genérica "5844-A9M30B-0P00", T-CON "6870C-0414A"); '
                . 'o tipo (placa principal, fonte, T-CON, inverter, módulo); e a marca se visível. '
                . 'Prefira o código da serigrafia ou da etiqueta de serviço, copiando exatamente. '
                . 'Responda só com este JSON: {"codigo":"","tipo":"","marca":""}. Vazio se ilegível.';

        $mensagens = [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $img['mime'], 'data' => $img['b64']]],
                ['type' => 'text', 'text' => $prompt],
            ],
        ]];
        // Placa: leitura mais simples que etiqueta -> Haiku (mais barato).
        $modelo = 'claude-haiku-4-5';
        $r = IAService::perguntar($mensagens, $system, 300, $modelo);
        if (empty($r['ok'])) return null;

        $d = self::parseJson((string) $r['texto']);
        if (!is_array($d)) return null;
        return [
            'codigo' => trim((string) ($d['codigo'] ?? '')),
            'tipo'   => trim((string) ($d['tipo']   ?? '')),
            'marca'  => trim((string) ($d['marca']  ?? '')),
        ];
    }

    private static function imagemBase64(string $path): ?array
    {
        $info = @getimagesize($path);
        if (!$info) return null;
        [$w, $h] = $info;
        $mime = $info['mime'] ?? '';

        switch ($mime) {
            case 'image/jpeg': $src = @imagecreatefromjpeg($path); break;
            case 'image/png':  $src = @imagecreatefrompng($path);  break;
            case 'image/webp': $src = @imagecreatefromwebp($path); break;
            case 'image/gif':  $src = @imagecreatefromgif($path);  break;
            default: return null;
        }
        if (!$src) return null;

        // Corrige a rotação pela orientação EXIF (fotos de celular vêm deitadas/de cabeça pra baixo).
        if ($mime === 'image/jpeg' && function_exists('exif_read_data')) {
            $exif   = @exif_read_data($path);
            $orient = (int) ($exif['Orientation'] ?? 0);
            $graus  = [3 => 180, 6 => -90, 8 => 90][$orient] ?? 0;
            if ($graus !== 0) {
                $rot = @imagerotate($src, $graus, 0);
                if ($rot) { imagedestroy($src); $src = $rot; $w = imagesx($src); $h = imagesy($src); }
            }
        }

        $max   = 1280; // reduzido de 2200 -> corta ~65% do custo de token por chamada, mantendo legibilidade
        $scale = min(1, $max / max($w, $h));
        if ($scale < 1) {
            $nw = (int) ($w * $scale);
            $nh = (int) ($h * $scale);
            $dst = imagecreatetruecolor($nw, $nh);
            imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
            imagedestroy($src);
            $src = $dst;
        }

        ob_start();
        imagejpeg($src, null, 85);
        $data = ob_get_clean();
        imagedestroy($src);

        return ['mime' => 'image/jpeg', 'b64' => base64_encode($data)];
    }

    private static function parseJson(string $txt): ?array
    {
        $txt = trim($txt);
        $txt = preg_replace('/```(?:json)?|```/', '', $txt);
        if (preg_match('/\{.*\}/s', $txt, $m)) $txt = $m[0];
        $d = json_decode(trim($txt), true);
        return is_array($d) ? $d : null;
    }
}
