<?php
/**
 * Filtra os arquivos "Estabelecimentos*.csv" e "Empresas*.csv" dos Dados Abertos
 * de CNPJ da Receita Federal, mantendo só empresas ATIVAS nas CNAEs de interesse
 * (assistência técnica / venda de componentes eletrônicos), e escreve um CSV
 * limpo (UTF-8, com cabeçalho) pronto pra importar_leads_cnpj.php.
 *
 * FONTE DOS DADOS (gratuita, oficial):
 *   gov.br/receitafederal → Acesso à Informação → Dados Abertos → Cadastros →
 *   Cadastro Nacional da Pessoa Jurídica (CNPJ) → recurso "Inscrições no CNPJ".
 *   Baixe o mês mais recente. Cada carga tem ~10 arquivos "Estabelecimentos0..9"
 *   e ~10 "Empresas0..9", todos .zip contendo um .csv ponto-e-vírgula,
 *   ISO-8859-1, SEM cabeçalho. Descompacte antes de rodar — os arquivos de
 *   dentro do zip NÃO têm extensão .csv nem nome "Estabelecimentos0"/"Empresas0"
 *   (ex.: "K3241.K03200Y0.D60808.ESTABELE", "K3241.K03200Y0.D60808.EMPRECSV",
 *   "F.K03200$Z.D60808.MUNICCSV") — renomeie pra Estabelecimentos{N}.csv,
 *   Empresas{N}.csv, Municipios.csv antes de rodar este script.
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
 * MEMÓRIA: os arquivos de Empresas*.csv somados passam de vários GB (é o cadastro
 * de TODAS as pessoas jurídicas do Brasil) — carregar tudo num array do PHP estoura
 * o memory_limit fácil. Por isso a ordem de processamento é invertida: primeiro filtra
 * Estabelecimentos*.csv (bem mais seletivo, CNAE + situação ativa) e guarda em memória
 * só os poucos milhares de CNPJs que batem no filtro; só DEPOIS varre Empresas*.csv,
 * mas ignorando (sem guardar) qualquer linha cujo CNPJ básico não esteja entre os já
 * filtrados. Memória fica proporcional ao número de LEADS, não ao cadastro nacional
 * inteiro.
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

$dirSaida = dirname($args['saida']);
if ($dirSaida !== '' && $dirSaida !== '.' && !is_dir($dirSaida)) {
    fwrite(STDERR, "Erro: diretório de saída não existe: $dirSaida (crie com mkdir -p antes de rodar)\n");
    exit(1);
}

// ── 1) Município: tabela de referência pequena, cabe inteira em memória ────
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

// ── 2) Varre Estabelecimentos*.csv primeiro — filtra por CNAE + situação ativa
//      e guarda em memória só quem bateu (evita carregar Empresas*.csv inteiro depois).
$leads = [];             // lista de leads já filtrados, faltando só a razão social
$basicosNecessarios = []; // set (cnpj_basico => true) dos CNPJs que precisam de razão social
$total = 0;
foreach (explode(',', $args['estabelecimentos']) as $arq) {
    $arq = trim($arq);
    if (!is_file($arq)) { fwrite(STDERR, "Aviso: arquivo de estabelecimentos não encontrado: $arq\n"); continue; }

    $fh = fopen($arq, 'r');
    while (($row = fgetcsv($fh, 0, ';')) !== false) {
        $total++;
        if ($limite && count($leads) >= $limite) break 2;

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

        $leads[] = [
            'basico'         => $basico,
            'cnpj'           => $cnpj,
            'nome_fantasia'  => trim(mb_convert_encoding($row[$COL_EST['nome_fantasia']] ?? '', 'UTF-8', 'ISO-8859-1'), '"'),
            'telefone'       => $telefone,
            'email'          => trim(mb_convert_encoding($row[$COL_EST['correio_eletronico']] ?? '', 'UTF-8', 'ISO-8859-1')),
            'cnae'           => $cnae,
            'municipio_cod'  => trim($row[$COL_EST['municipio_cod']] ?? ''),
            'uf'             => trim($row[$COL_EST['uf']] ?? ''),
        ];
        $basicosNecessarios[$basico] = true;
    }
    fclose($fh);
    fwrite(STDERR, "Processado: $arq (acumulado: " . count($leads) . " leads de $total linhas lidas)\n");
}

// ── 3) Índice CNPJ básico → razão social (Empresas*.csv), só dos CNPJs filtrados ──
$razaoSocial = [];
if (!empty($args['empresas']) && $basicosNecessarios) {
    foreach (explode(',', $args['empresas']) as $arq) {
        $arq = trim($arq);
        if (!is_file($arq)) { fwrite(STDERR, "Aviso: arquivo de empresas não encontrado: $arq\n"); continue; }
        $fh = fopen($arq, 'r');
        while (($row = fgetcsv($fh, 0, ';')) !== false) {
            if (!isset($row[$COL_EMP['cnpj_basico']])) continue;
            $basico = trim($row[$COL_EMP['cnpj_basico']]);
            if (!isset($basicosNecessarios[$basico])) continue; // fora do filtro, não guarda
            $razaoSocial[$basico] = trim(mb_convert_encoding($row[$COL_EMP['razao_social']] ?? '', 'UTF-8', 'ISO-8859-1'), '"');
        }
        fclose($fh);
    }
    fwrite(STDERR, "Índice de razão social: " . count($razaoSocial) . " de " . count($basicosNecessarios) . " CNPJs filtrados.\n");
}

// ── 4) Escreve o CSV de saída ────────────────────────────────────────────
$saida = fopen($args['saida'], 'w');
if ($saida === false) {
    fwrite(STDERR, "Erro: não foi possível abrir '{$args['saida']}' pra escrita (permissão? caminho inválido?).\n");
    exit(1);
}
fputcsv($saida, ['cnpj', 'razao_social', 'nome_fantasia', 'telefone', 'email', 'cnae', 'municipio', 'uf', 'situacao_cadastral']);

foreach ($leads as $l) {
    fputcsv($saida, [
        $l['cnpj'],
        $razaoSocial[$l['basico']] ?? '',
        $l['nome_fantasia'],
        $l['telefone'],
        $l['email'],
        $l['cnae'],
        $municipios[$l['municipio_cod']] ?? '',
        $l['uf'],
        'ATIVA',
    ]);
}
fclose($saida);

fwrite(STDERR, "Concluído. " . count($leads) . " leads gravados em {$args['saida']}.\n");
