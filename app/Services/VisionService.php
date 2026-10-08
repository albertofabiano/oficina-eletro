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

    /**
     * Lê uma foto de conta/boleto/comprovante (Financeiro pessoal) e extrai os dados pra
     * pré-preencher o formulário de revisão — Etapa 4 (controle de custo do scanner): tenta
     * primeiro no modelo PADRÃO (Haiku, mais barato) e só reenvia a MESMA foto pro modelo
     * ESCALONADO (Sonnet) se faltar valor/vencimento/código ou a confiança vier baixa em
     * algum campo — nunca escalona por escalonar, só quando o resultado do Haiku não dá pra
     * confiar. Cada tentativa (Haiku, e a de Sonnet se acontecer) é registrada em
     * IAUsoService::registrar(), pra aparecer na tela de custo do Master.
     *
     * Ainda não decodifica QR Pix nem código de barras de verdade (não há biblioteca de leitura
     * de código neste projeto) — pede pro modelo TRANSCREVER os dígitos/texto impressos, que é
     * bem menos confiável que uma leitura de símbolo de verdade pra números longos (código de
     * barras tem 47-48 dígitos) — por isso `confianca.codigo` existe, pra sinalizar quando essa
     * transcrição não deve ser confiada sem conferência manual.
     *
     * @param string[] $categoriasValidas chaves de App\Services\Fixa\PerfilService::categoriasDoPerfil()
     * @return array{descricao:string,valor:float,vencimento:string,categoria:string,beneficiario:string,codigo_barras:string,pix_copia_cola:string,confianca:array{valor:string,vencimento:string,codigo:string}}|null
     */
    public static function lerConta(string $caminhoImagem, array $categoriasValidas, int $usuarioId = 0, int $empresaId = 0): ?array
    {
        if (!is_file($caminhoImagem) || IAService::apiKey() === '') return null;

        $img = self::imagemBase64($caminhoImagem);
        if (!$img) return null;

        $listaCategorias = implode(', ', $categoriasValidas);
        $system = 'Você lê fotos de contas, boletos e comprovantes de pagamento (conta de luz, água, '
                . 'gás, internet, telefone, cartão de crédito, condomínio, aluguel, mensalidade, '
                . 'assinatura etc.) e extrai os dados pra lançar num controle financeiro pessoal. '
                . 'Leia com atenção; NÃO invente um valor, data ou código que não esteja visível na '
                . 'foto. Responda SOMENTE com um JSON válido, sem comentários nem texto fora do JSON.';
        $prompt = 'Extraia da foto: '
                . '"descricao" (nome curto do que é a conta — use um rótulo claro tipo "Energia elétrica", '
                . '"Fatura do cartão", "Condomínio"; se não houver um rótulo óbvio, use o nome do '
                . 'beneficiário/empresa impresso); '
                . '"valor" (o valor TOTAL A PAGAR, número com ponto decimal, ex.: 187.40 — se houver '
                . 'valor com e sem desconto/multa, use o valor principal cobrado); '
                . '"vencimento" (data de vencimento, formato AAAA-MM-DD; string vazia "" se não houver '
                . 'data de vencimento visível na foto); '
                . '"categoria" (escolha EXATAMENTE uma destas palavras, sem mudar a grafia: ' . $listaCategorias . '); '
                . '"beneficiario" (nome de quem recebe o pagamento, impresso na conta — string vazia '
                . 'se não houver); '
                . '"codigo_barras" (os dígitos da linha digitável do código de barras/boleto, só '
                . 'números, sem espaço nem ponto — string vazia se não houver boleto na foto); '
                . '"pix_copia_cola" ("Pix Copia e Cola" impresso — normalmente um texto longo tipo '
                . '"00020126..." — string vazia se não houver); '
                . '"confianca" (objeto {"valor":"alta|baixa","vencimento":"alta|baixa","codigo":"alta|baixa"} '
                . '— "baixa" quando o número/data/código está borrado, cortado, muito pequeno ou você não '
                . 'tem certeza da leitura; "codigo" se refere ao código de barras/Pix juntos — "alta" se '
                . 'não havia código nenhum pra ler). '
                . 'Responda só com: {"descricao":"","valor":0,"vencimento":"","categoria":"","beneficiario":"",'
                . '"codigo_barras":"","pix_copia_cola":"","confianca":{"valor":"alta","vencimento":"alta","codigo":"alta"}}.';

        $mensagens = [[
            'role'    => 'user',
            'content' => [
                ['type' => 'image', 'source' => ['type' => 'base64', 'media_type' => $img['mime'], 'data' => $img['b64']]],
                ['type' => 'text', 'text' => $prompt],
            ],
        ]];

        $modeloPadrao     = IAService::cfg('ia_modelo_visao_fixa_padrao')     ?: 'claude-haiku-4-5-20251001';
        $modeloEscalonado = IAService::cfg('ia_modelo_visao_fixa_escalonado') ?: 'claude-sonnet-5-5';

        $resultado = self::tentarLerConta($mensagens, $system, $modeloPadrao, $categoriasValidas, $usuarioId, $empresaId, 'fixa_scanner_conta');
        if ($resultado === null) return null;

        if (self::contaPrecisaEscalonar($resultado)) {
            $resultadoEscalonado = self::tentarLerConta($mensagens, $system, $modeloEscalonado, $categoriasValidas, $usuarioId, $empresaId, 'fixa_scanner_conta_escalonado');
            if ($resultadoEscalonado !== null) $resultado = $resultadoEscalonado;
        }

        return $resultado;
    }

    /** Uma tentativa de lerConta() com um modelo específico — chamado 1x (Haiku) ou 2x (Haiku + Sonnet). */
    private static function tentarLerConta(array $mensagens, string $system, string $modelo, array $categoriasValidas, int $usuarioId, int $empresaId, string $contexto): ?array
    {
        $r = IAService::perguntar($mensagens, $system, 400, $modelo);
        IAUsoService::registrar($usuarioId ?: null, $empresaId ?: null, $modelo, $contexto, $r['usage'] ?? []);
        if (empty($r['ok'])) return null;

        $d = self::parseJson((string) $r['texto']);
        if (!is_array($d)) return null;

        $vencimento = self::normalizarData((string) ($d['vencimento'] ?? ''));
        $categoria = (string) ($d['categoria'] ?? '');
        if (!in_array($categoria, $categoriasValidas, true)) $categoria = '';

        return [
            'descricao'      => trim((string) ($d['descricao'] ?? '')),
            'valor'          => (float) ($d['valor'] ?? 0),
            'vencimento'     => $vencimento,
            'categoria'      => $categoria,
            'beneficiario'   => trim((string) ($d['beneficiario'] ?? '')),
            'codigo_barras'  => preg_replace('/\D/', '', (string) ($d['codigo_barras'] ?? '')),
            'pix_copia_cola' => trim((string) ($d['pix_copia_cola'] ?? '')),
            'confianca'      => [
                'valor'      => (($d['confianca']['valor']      ?? '') === 'baixa') ? 'baixa' : 'alta',
                'vencimento' => (($d['confianca']['vencimento'] ?? '') === 'baixa') ? 'baixa' : 'alta',
                'codigo'     => (($d['confianca']['codigo']     ?? '') === 'baixa') ? 'baixa' : 'alta',
            ],
        ];
    }

    /** Escalona pro Sonnet quando falta valor/vencimento, ou a confiança veio baixa em
     *  qualquer campo (pedido explícito: "faltar valor, vencimento ou código, ou a resposta
     *  vier inconsistente" — confiança baixa JÁ É o modelo dizendo "não tenho certeza"). */
    private static function contaPrecisaEscalonar(array $resultado): bool
    {
        if ($resultado['valor'] <= 0) return true;
        if ($resultado['vencimento'] === '') return true;
        foreach ($resultado['confianca'] as $c) { if ($c === 'baixa') return true; }
        return false;
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

        $max   = 1568; // máximo recomendado pela própria Anthropic pra imagem de visão (Etapa 4) — além
                       // disso o lado maior é redimensionado de qualquer forma antes de calcular tokens
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

    /**
     * Normaliza a data de vencimento pra AAAA-MM-DD — o prompt pede esse formato, mas o
     * modelo às vezes devolve DD/MM/AAAA (é o formato que está impresso na própria conta
     * brasileira, então é natural ele "ecoar" o que viu) ou um ISO com hora grudada
     * ("2026-10-15T00:00:00"). Antes disso era um único regex estrito que exigia o formato
     * exato — qualquer coisa fora disso virava string vazia, apagando uma data que a IA tinha
     * lido certo, só porque não bateu a formatação. Aceita os formatos plausíveis e valida com
     * checkdate() (o regex antigo nem validava se a data existia de verdade).
     */
    private static function normalizarData(string $txt): string
    {
        $txt = trim($txt);
        if ($txt === '') return '';
        if (str_contains($txt, 'T')) $txt = explode('T', $txt)[0];

        if (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $txt, $m)) {
            [$y, $mo, $d] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : '';
        }
        // DD/MM/AAAA ou DD-MM-AAAA — formato que a própria conta brasileira imprime.
        if (preg_match('/^(\d{1,2})[\/\-](\d{1,2})[\/\-](\d{4})$/', $txt, $m)) {
            [$d, $mo, $y] = [(int) $m[1], (int) $m[2], (int) $m[3]];
            return checkdate($mo, $d, $y) ? sprintf('%04d-%02d-%02d', $y, $mo, $d) : '';
        }

        return '';
    }
}
