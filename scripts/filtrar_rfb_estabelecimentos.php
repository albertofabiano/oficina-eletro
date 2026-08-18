<?php
/**
 * Filtra os arquivos "Estabelecimentos*.csv" e "Empresas*.csv" dos Dados Abertos
 * de CNPJ da Receita Federal, mantendo só empresas ATIVAS nas CNAEs de interesse
 * (assistência técnica / venda de componentes eletrônicos), e escreve um CSV
 * limpo (UTF-8, com cabeçalho) pronto pra importar_leads_cnpj.php.
 *
 * FONTE DOS DADOS (gratuita, oficial):
 *   https://arquivos.receitafederal.gov.br/dados/cnpj/dados_abertos_cnpj/
 *   Baixe o mês mais recente. Cada carga tem ~10 arquivos "Estabelecimentos0..9"
 *   e ~10 "Empresas0..9" (nome varia por competência), todos .zip contendo um
 *   .csv ponto-e-vírgula, ISO-8859-1, SEM cabeçalho. Descompacte antes de rodar.
 *
 * LAYOUT (documentado pela RFB, estável entre competências):
 *   Estabelecimentos.csv: cnpj_basico;cnpj_ordem;cnpj_dv;identificador_matriz_filial;
 *     nome_fantasia;situacao_cadastral;data_situacao_cadastral;motivo_situacao_cadastral;
 *     nome_cidade_exterior;pais;data_inicio_atividade;cnae_fiscal_principal;
 *     cnae_fiscal_secundaria;tipo_logradouro;logradouro;numero;complemento;bairro;
 *     cep;uf;municipio;ddd1;telefone1;ddd2;telefone2;ddd_fax;fax;correio_eletronico;
 *     situacao_especial;data_situacao_especial
 *   Empresas.csv: cnpj_basico;razao_social;natureza_juridica;qualificacao_responsavel;
 *     capital_social;porte;ente_federativo_responsavel
 *   Municipios.csv (tabela de referência, também nos dados abertos): codigo;nome
 *
 * AVISO: este script foi escrito a partir do layout documentado pela RFB, mas
 * NÃO foi testado contra os arquivos reais (não tenho acesso a eles neste
 * ambiente). Rode primeiro com --limite=1000 e confira a saída antes de
 * processar o dataset completo — se algum nome de coluna/ordem tiver mudado
 * na competência que você baixou, ajuste o array $COL abaixo.
 *
 * USO:
 *   php filtrar_rfb_estabelecimentos.php \
 *     --estabelecimentos=/caminho/Estabelecimentos0.csv,/caminho/Estabelecimentos1.csv,... \
 *     --empresas=/caminho/Empresas0.csv,/caminho/Empresas1.csv,... \
 *     --municipios=/caminho/Municipios.csv \
 *     --saida=/caminho/leads_filtrados.csv \
 *     [--limite=1000]
 *
 * As CNAEs filtradas (situação cadastral "02" = ATIVA) são as mesmas usadas
 * no painel /master/prospeccao: 9511800, 9521500, 4757100.
 */

$CNAES_ALVO = ['9511800', '9521500', '4757100'];
$SITUACAO_ATIVA = '02'; // código RFB pra "ATIVA"

$COL_EST = [
    'cnpj_basico' => 0, 'cnpj_ordem' => 1, 'cnpj_dv' => 2, 'nome_fantasia' => 4,
    'situacao_cadastral' => 5, 'cnae_principal' => 11, 'uf' => 19, 'municipio_cod' => 20,
    'ddd1' => 21, 'telefone1' => 22, 'correio_eletronico' => 27,
];
$COL_EMP = ['cnpj_basico' => 0, 'razao_social' => 1];
$COL_MUN = ['codigo' => 0, 'nome' => 1];

function parseArgs(array $argv): array
{
    $out = [];
    foreach ($argv as $a) {
        if (preg_match('/^--([\w]+)=(.*)$/', $a, $m)) $out[$m[1]] = $m[2];
    }
    return $out;
}

$args = parseArgs($argv);
if (empty($args['estabelecimentos']) || empty($args['saida'])) {
    fwrite(STDERR, "Uso: php filtrar_rfb_estabelecimentos.php --estabelecimentos=arq1.csv,arq2.csv --empresas=arq1.csv,... --municipios=arq.csv --saida=leads.csv [--limite=N]\n");
    exit(1);
}

$limite = isset($args['limite']) ? (int) $args['limite'] : 0;

// ── 1) Índice CNPJ básico → razão social (Empresas*.csv) ───────────────────
$razaoSocial = [];
if (!empty($args['empresas'])) {
    foreach (explode(',', $args['empresas']) as $arq) {
        $arq = trim($arq);
        if (!is_file($arq)) { fwrite(STDERR, "Aviso: arquivo de empresas não encontrado: $arq\n"); continue; }
        $fh = fopen($arq, 'r');
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (!isset($row[$COL_EMP['cnpj_basico']])) continue;
            $basico = trim($row[$COL_EMP['cnpj_basico']]);
            $razaoSocial[$basico] = trim(mb_convert_encoding($row[$COL_EMP['razao_social']] ?? '', 'UTF-8', 'ISO-8859-1'), '"');
        }
        fclose($fh);
    }
    fwrite(STDERR, "Índice de razão social: " . count($razaoSocial) . " empresas.\n");
}

// ── 2) Índice código de município → nome (Municipios.csv) ──────────────────
$municipios = [];
if (!empty($args['municipios']) && is_file($args['municipios'])) {
    $fh = fopen($args['municipios'], 'r');
    while (($row = fgetcsv($fh, 0, ';')) !== false) {
        if (!isset($row[$COL_MUN['codigo']])) continue;
        $municipios[trim($row[$COL_MUN['codigo']])] = trim(mb_convert_encoding($row[$COL_MUN['nome']] ?? '', 'UTF-8', 'ISO-8859-1'), '"');
    }
    fclose($fh);
    fwrite(STDERR, "Índice de municípios: " . count($municipios) . " cidades.\n");
}

// ── 3) Varre Estabelecimentos*.csv, filtra e escreve o CSV de saída ────────
$dirSaida = dirname($args['saida']);
if ($dirSaida !== '' && $dirSaida !== '.' && !is_dir($dirSaida)) {
    fwrite(STDERR, "Erro: diretório de saída não existe: $dirSaida (crie com mkdir -p antes de rodar)\n");
    exit(1);
}
$saida = fopen($args['saida'], 'w');
if ($saida === false) {
    fwrite(STDERR, "Erro: não foi possível abrir '{$args['saida']}' pra escrita (permissão? caminho inválido?).\n");
    exit(1);
}
fputcsv($saida, ['cnpj', 'razao_social', 'nome_fantasia', 'telefone', 'email', 'cnae', 'municipio', 'uf', 'situacao_cadastral']);

$total = 0;
$gravados = 0;
foreach (explode(',', $args['estabelecimentos']) as $arq) {
    $arq = trim($arq);
    if (!is_file($arq)) { fwrite(STDERR, "Aviso: arquivo de estabelecimentos não encontrado: $arq\n"); continue; }

    $fh = fopen($arq, 'r');
    while (($row = fgetcsv($fh, 0, ';')) !== false) {
        $total++;
        if ($limite && $gravados >= $limite) break 2;

        $cnae = trim($row[$COL_EST['cnae_principal']] ?? '');
        $situacao = trim($row[$COL_EST['situacao_cadastral']] ?? '');
        if (!in_array($cnae, $CNAES_ALVO, true) || $situacao !== $SITUACAO_ATIVA) continue;

        $basico = trim($row[$COL_EST['cnpj_basico']] ?? '');
        $ordem  = trim($row[$COL_EST['cnpj_ordem']] ?? '');
        $dv     = trim($row[$COL_EST['cnpj_dv']] ?? '');
        $cnpj   = str_pad($basico, 8, '0', STR_PAD_LEFT) . str_pad($ordem, 4, '0', STR_PAD_LEFT) . str_pad($dv, 2, '0', STR_PAD_LEFT);

        $ddd    = trim($row[$COL_EST['ddd1']] ?? '');
        $fone   = trim($row[$COL_EST['telefone1']] ?? '');
        $telefone = ($ddd && $fone) ? $ddd . $fone : '';

        $municipioCod = trim($row[$COL_EST['municipio_cod']] ?? '');

        fputcsv($saida, [
            $cnpj,
            $razaoSocial[$basico] ?? '',
            trim(mb_convert_encoding($row[$COL_EST['nome_fantasia']] ?? '', 'UTF-8', 'ISO-8859-1'), '"'),
            $telefone,
            trim(mb_convert_encoding($row[$COL_EST['correio_eletronico']] ?? '', 'UTF-8', 'ISO-8859-1')),
            $cnae,
            $municipios[$municipioCod] ?? '',
            trim($row[$COL_EST['uf']] ?? ''),
            'ATIVA',
        ]);
        $gravados++;
    }
    fclose($fh);
    fwrite(STDERR, "Processado: $arq (acumulado: $gravados leads de $total linhas lidas)\n");
}

fclose($saida);
fwrite(STDERR, "Concluído. $gravados leads gravados em {$args['saida']}.\n");
