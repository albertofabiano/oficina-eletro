<?php
/**
 * Popula o Fixa Indica (indica.fixaos.com.br) com dados de EXEMPLO — categorias, produtos e
 * guias fictícios, tom técnico real (ferramentas/peças que uma assistência técnica usa de
 * verdade), mas com link_ml/link_afiliado deliberadamente FALSOS ('#exemplo-ajustar-depois')
 * pra nunca redirecionar de verdade pra lugar nenhum por engano.
 *
 * Fase 1 do Fixa Indica (ver CLAUDE.md/plano) — este conteúdo é placeholder. O usuário ajusta
 * pelo conteúdo real depois (manualmente ou, nas fases seguintes, pelo painel admin).
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o que faria). Pra gravar:
 *   php scripts/seed_indica_exemplo.php --aplicar
 *
 * Idempotente por slug — rodar de novo não duplica categoria/produto/guia já existente (pula
 * com aviso), então é seguro rodar mais de uma vez enquanto for ajustando o seed.
 *
 * Pra apagar tudo depois:
 *   DELETE FROM indica_produtos WHERE slug LIKE 'exemplo-%';
 *   DELETE FROM indica_guias WHERE slug LIKE 'exemplo-%';
 *   DELETE FROM indica_categorias WHERE slug LIKE 'exemplo-%';
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Categorias de exemplo
// ---------------------------------------------------------------------------------------

$categorias = [
    ['slug' => 'exemplo-ferramentas-de-solda', 'nome' => 'Ferramentas de Solda', 'icone' => '🔥', 'ordem' => 1,
        'texto_seo' => 'Estações de solda, ar quente e tudo pra retrabalho de placa, indicado por técnicos de verdade.'],
    ['slug' => 'exemplo-pecas-de-celular', 'nome' => 'Peças de Celular', 'icone' => '📱', 'ordem' => 2,
        'texto_seo' => 'Telas, baterias e conectores das marcas mais atendidas na assistência técnica.'],
    ['slug' => 'exemplo-instrumentos-de-medicao', 'nome' => 'Instrumentos de Medição', 'icone' => '📏', 'ordem' => 3,
        'texto_seo' => 'Multímetros, fontes de bancada e osciloscópios pra diagnóstico de verdade.'],
    ['slug' => 'exemplo-eletronicos-e-acessorios', 'nome' => 'Eletrônicos e Acessórios', 'icone' => '🔌', 'ordem' => 4,
        'texto_seo' => 'Carregadores, cabos e adaptadores que todo técnico recomenda pro cliente comprar.'],
];

$catIds = [];
foreach ($categorias as $c) {
    $stmt = $db->prepare("SELECT id FROM indica_categorias WHERE slug = ?");
    $stmt->execute([$c['slug']]);
    $existente = $stmt->fetchColumn();
    if ($existente) {
        echo "  [categoria já existe] {$c['nome']} (#{$existente})\n";
        $catIds[$c['slug']] = (int) $existente;
        continue;
    }
    echo "  + categoria: {$c['nome']}\n";
    if ($aplicar) {
        $ins = $db->prepare(
            "INSERT INTO indica_categorias (nome, slug, icone, texto_seo, ordem, ativa) VALUES (?, ?, ?, ?, ?, 1)"
        );
        $ins->execute([$c['nome'], $c['slug'], $c['icone'], $c['texto_seo'], $c['ordem']]);
        $catIds[$c['slug']] = (int) $db->lastInsertId();
    } else {
        $catIds[$c['slug']] = 0; // placeholder só pra simulação conseguir seguir sem gravar
    }
}

// ---------------------------------------------------------------------------------------
// Produtos de exemplo
// ---------------------------------------------------------------------------------------

$produtos = [
    // Ferramentas de solda
    ['cat' => 'exemplo-ferramentas-de-solda', 'slug' => 'exemplo-estacao-de-solda-858d', 'nome' => 'Estação de Solda Digital 858D (ar quente + ferro)',
        'especificacao' => 'Ar quente + ferro de solda na mesma base, display digital de temperatura.',
        'descricao' => "Combo mais pedido pra quem está montando a primeira bancada: ar quente pra retrabalho de componente SMD e ferro de solda tradicional na mesma estação, sem precisar comprar os dois separados.\n\nVale o investimento mesmo começando: display digital evita queimar componente por temperatura errada, e a base aquece rápido o bastante pro dia a dia de uma assistência.",
        'preco' => 389.90, 'selos' => ['mais_vendido'], 'destaque' => 1],
    ['cat' => 'exemplo-ferramentas-de-solda', 'slug' => 'exemplo-flux-gel-hgf', 'nome' => 'Flux Gel Soldagem HGF-105 10ml',
        'especificacao' => 'Flux em gel pra solda SMD, não corrosivo.',
        'descricao' => "Item de consumo que nunca pode faltar na bancada — sem flux de qualidade, a solda em componente pequeno (BGA, conector de carga) fica porosa e a chance de dessoldar sozinho depois é bem maior.",
        'preco' => 24.90, 'selos' => ['frete_gratis'], 'destaque' => 0],
    ['cat' => 'exemplo-ferramentas-de-solda', 'slug' => 'exemplo-malha-dessoldadora', 'nome' => 'Malha Dessoldadora 2mm x 1,5m',
        'especificacao' => 'Pra remover excesso de solda sem danificar a trilha.',
        'descricao' => "Junto com o flux, é o item mais usado em retrabalho — puxa a solda sobrando sem precisar de dessoldadora a vácuo pra serviço rápido.",
        'preco' => 12.50, 'selos' => [], 'destaque' => 0],

    // Peças de celular
    ['cat' => 'exemplo-pecas-de-celular', 'slug' => 'exemplo-tela-samsung-a54', 'nome' => 'Tela Completa Samsung Galaxy A54 (AMOLED)',
        'especificacao' => 'Tela AMOLED original, com aro.',
        'descricao' => "A54 é um dos aparelhos mais atendidos hoje — ter essa tela em estoque evita cliente esperando peça chegar. AMOLED original (não a cópia incell) preserva a qualidade de cor do aparelho.",
        'preco' => 459.00, 'selos' => ['mais_vendido'], 'destaque' => 1],
    ['cat' => 'exemplo-pecas-de-celular', 'slug' => 'exemplo-bateria-iphone-11', 'nome' => 'Bateria iPhone 11 3110mAh',
        'especificacao' => 'Capacidade original, com flex de instalação.',
        'descricao' => "Troca de bateria continua sendo um dos serviços mais pedidos — aparelho velho com bateria boa dura muito mais que cliente esperava.",
        'preco' => 89.90, 'selos' => ['frete_gratis'], 'destaque' => 0],
    ['cat' => 'exemplo-pecas-de-celular', 'slug' => 'exemplo-conector-carga-motog', 'nome' => 'Conector de Carga Moto G Série 50',
        'especificacao' => 'Flex com conector USB-C original.',
        'descricao' => "Defeito comum em aparelho que carrega só em certo ângulo — a maioria das vezes é esse conector, não a bateria.",
        'preco' => 34.90, 'selos' => [], 'destaque' => 0],
    ['cat' => 'exemplo-pecas-de-celular', 'slug' => 'exemplo-tela-iphone-xr', 'nome' => 'Tela Completa iPhone XR (LCD)',
        'especificacao' => 'LCD com touch, qualidade AAA.',
        'descricao' => "XR ainda tem uma base enorme de usuários — tela de qualidade AAA preserva a resposta de toque sem o custo de uma tela original.",
        'preco' => 189.00, 'selos' => ['oferta_do_dia'], 'destaque' => 1],

    // Instrumentos de medição
    ['cat' => 'exemplo-instrumentos-de-medicao', 'slug' => 'exemplo-multimetro-dt-830', 'nome' => 'Multímetro Digital DT-830',
        'especificacao' => 'Tensão, corrente, resistência e teste de diodo.',
        'descricao' => "O instrumento mais básico e mais usado de toda assistência — sem ele, nem dá pra começar a diagnosticar nada.",
        'preco' => 29.90, 'selos' => ['frete_gratis', 'mais_vendido'], 'destaque' => 1],
    ['cat' => 'exemplo-instrumentos-de-medicao', 'slug' => 'exemplo-fonte-bancada-30v10a', 'nome' => 'Fonte de Bancada Ajustável 30V 10A',
        'especificacao' => 'Display digital de tensão e corrente, proteção contra curto.',
        'descricao' => "Essencial pra testar placa fora do aparelho — a proteção contra curto evita queimar componente bom numa placa que já tinha defeito.",
        'preco' => 349.00, 'selos' => [], 'destaque' => 0],
    ['cat' => 'exemplo-instrumentos-de-medicao', 'slug' => 'exemplo-osciloscopio-ds1054z', 'nome' => 'Osciloscópio Digital 4 Canais 50MHz',
        'especificacao' => '4 canais, 50MHz, interface USB.',
        'descricao' => "Já é um investimento maior, mas pra quem faz retrabalho de placa a nível de componente, é o que separa diagnóstico preciso de tentativa e erro.",
        'preco' => 1899.00, 'selos' => [], 'destaque' => 0],

    // Eletrônicos e acessórios
    ['cat' => 'exemplo-eletronicos-e-acessorios', 'slug' => 'exemplo-carregador-turbo-25w', 'nome' => 'Carregador Turbo USB-C 25W',
        'especificacao' => 'Carregamento rápido, compatível com a maioria dos Android atuais.',
        'descricao' => "Item que a assistência sempre acaba recomendando pro cliente comprar junto — carregador genérico fraco é causa comum de bateria envelhecer rápido.",
        'preco' => 49.90, 'selos' => ['frete_gratis'], 'destaque' => 0],
    ['cat' => 'exemplo-eletronicos-e-acessorios', 'slug' => 'exemplo-pelicula-vidro-temperado', 'nome' => 'Película de Vidro Temperado Universal (kit 5un)',
        'especificacao' => 'Kit com 5 unidades, dureza 9H.',
        'descricao' => "Complemento clássico de troca de tela — item de giro rápido e margem boa pra vender junto com o serviço.",
        'preco' => 19.90, 'selos' => [], 'destaque' => 0],
];

$prodIds = [];
foreach ($produtos as $p) {
    $stmt = $db->prepare("SELECT id FROM indica_produtos WHERE slug = ?");
    $stmt->execute([$p['slug']]);
    $existente = $stmt->fetchColumn();
    if ($existente) {
        echo "  [produto já existe] {$p['nome']} (#{$existente})\n";
        $prodIds[$p['slug']] = (int) $existente;
        continue;
    }
    echo "  + produto: {$p['nome']} ({$p['cat']})\n";
    if ($aplicar) {
        $ins = $db->prepare(
            "INSERT INTO indica_produtos
                (categoria_id, nome, slug, especificacao_curta, descricao, preco, preco_atualizado_em,
                 disponivel, link_ml, link_afiliado, imagem_url, selos, destaque_home, vendedor_parceiro, ativo)
             VALUES (?, ?, ?, ?, ?, ?, NOW(), 1, '#exemplo-ajustar-depois', '#exemplo-ajustar-depois', NULL, ?, ?, 0, 1)"
        );
        $ins->execute([
            $catIds[$p['cat']],
            $p['nome'],
            $p['slug'],
            $p['especificacao'],
            $p['descricao'],
            $p['preco'],
            json_encode($p['selos'], JSON_UNESCAPED_UNICODE),
            $p['destaque'],
        ]);
        $prodIds[$p['slug']] = (int) $db->lastInsertId();
    } else {
        $prodIds[$p['slug']] = 0;
    }
}

// ---------------------------------------------------------------------------------------
// Guias de exemplo (editor por blocos — ver indica_guia_blocos)
// ---------------------------------------------------------------------------------------

$guias = [
    [
        'slug' => 'exemplo-celular-nao-carrega-o-que-fazer',
        'titulo' => 'Celular não carrega: o que verificar antes de trocar a bateria',
        'blocos' => [
            ['tipo' => 'paragrafo', 'conteudo' => ['texto' => 'Antes de trocar a bateria de cara, vale descartar duas causas bem mais comuns e mais baratas de resolver.']],
            ['tipo' => 'titulo', 'conteudo' => ['texto' => '1. Teste o cabo e o carregador primeiro']],
            ['tipo' => 'paragrafo', 'conteudo' => ['texto' => 'Um cabo USB desgastado é a causa mais comum de "carregamento lento" ou "não carrega". Troque o cabo e o carregador por um que você sabe que funciona antes de abrir o aparelho.']],
            ['tipo' => 'produto', 'conteudo' => ['produto_id' => 0]], // substituído abaixo
            ['tipo' => 'titulo', 'conteudo' => ['texto' => '2. Verifique o conector de carga']],
            ['tipo' => 'paragrafo', 'conteudo' => ['texto' => 'Poeira ou um pino torto no conector de carga causa carregamento intermitente ("carrega só se segurar num ângulo"). Uma limpeza com palito de madeira seco já resolve boa parte dos casos — se não resolver, o conector provavelmente precisa ser trocado.']],
            ['tipo' => 'aviso', 'conteudo' => ['texto' => 'Nunca use objeto metálico pra limpar o conector — risco real de curto-circuito na placa.']],
        ],
    ],
    [
        'slug' => 'exemplo-montando-a-primeira-bancada-de-solda',
        'titulo' => 'Montando a primeira bancada de solda: o essencial',
        'blocos' => [
            ['tipo' => 'paragrafo', 'conteudo' => ['texto' => 'Pra quem está começando, não precisa comprar tudo de uma vez — esses 3 itens já cobrem a maior parte do trabalho de retrabalho de placa.']],
            ['tipo' => 'produto', 'conteudo' => ['produto_id' => 0]],
            ['tipo' => 'produto', 'conteudo' => ['produto_id' => 0]],
            ['tipo' => 'produto', 'conteudo' => ['produto_id' => 0]],
        ],
        'produtosDosBlocos' => ['exemplo-estacao-de-solda-858d', 'exemplo-flux-gel-hgf', 'exemplo-malha-dessoldadora'],
    ],
];

foreach ($guias as $g) {
    $stmt = $db->prepare("SELECT id FROM indica_guias WHERE slug = ?");
    $stmt->execute([$g['slug']]);
    $existente = $stmt->fetchColumn();
    if ($existente) {
        echo "  [guia já existe] {$g['titulo']} (#{$existente})\n";
        continue;
    }
    echo "  + guia: {$g['titulo']}\n";
    if (!$aplicar) continue;

    $ins = $db->prepare(
        "INSERT INTO indica_guias (titulo, slug, status, publicado_em) VALUES (?, ?, 'publicado', NOW())"
    );
    $ins->execute([$g['titulo'], $g['slug']]);
    $guiaId = (int) $db->lastInsertId();

    // Resolve os produto_id reais dos blocos tipo 'produto', na mesma ordem em que aparecem.
    $produtosParaBlocos = $g['produtosDosBlocos'] ?? ['exemplo-carregador-turbo-25w'];
    $idxProduto = 0;
    $ordem = 0;
    foreach ($g['blocos'] as $bloco) {
        $conteudo = $bloco['conteudo'];
        if ($bloco['tipo'] === 'produto') {
            $slugProduto = $produtosParaBlocos[$idxProduto] ?? $produtosParaBlocos[0];
            $conteudo = ['produto_id' => $prodIds[$slugProduto] ?? 0];
            $idxProduto++;
        }
        $insB = $db->prepare(
            "INSERT INTO indica_guia_blocos (guia_id, ordem, tipo, conteudo) VALUES (?, ?, ?, ?)"
        );
        $insB->execute([$guiaId, $ordem, $bloco['tipo'], json_encode($conteudo, JSON_UNESCAPED_UNICODE)]);
        $ordem++;
    }
}

// ---------------------------------------------------------------------------------------
// Config padrão (aviso de afiliado etc.) — só grava se a chave ainda não existir.
// ---------------------------------------------------------------------------------------

$configsPadrao = [
    'whatsapp_contato' => '',
    'faixa_fixaos_ativa' => '1',
];
foreach ($configsPadrao as $chave => $valor) {
    $stmt = $db->prepare("SELECT 1 FROM indica_config WHERE chave = ?");
    $stmt->execute([$chave]);
    if ($stmt->fetchColumn()) continue;
    echo "  + config: {$chave}\n";
    if ($aplicar) {
        $ins = $db->prepare("INSERT INTO indica_config (chave, valor) VALUES (?, ?)");
        $ins->execute([$chave, $valor]);
    }
}

echo str_repeat('-', 78) . "\n";
echo $aplicar
    ? "Pronto — dados de exemplo gravados.\n"
    : "Simulação concluída — rode com --aplicar pra gravar de verdade.\n";
echo "\nLembrete: todo link_ml/link_afiliado foi gravado como '#exemplo-ajustar-depois' —\n";
echo "ajuste pelos links reais de afiliado do Mercado Livre antes de divulgar o site.\n";
