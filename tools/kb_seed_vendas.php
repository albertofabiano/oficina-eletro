<?php
/**
 * Popula a Base de Conhecimento (kb_artigos) com artigos comerciais/vendas,
 * pra o bot de suporte por WhatsApp responder melhor a possíveis assinantes.
 * Idempotente: pula artigos cujo slug já existe. Uso: php kb_seed_vendas.php
 */
$dbCfg = require __DIR__ . '/../config/database.php';
$dbHost = $dbCfg['host'] ?? '127.0.0.1';
$dbName = $dbCfg['database'] ?? $dbCfg['dbname'] ?? 'fixaos';
$dbUser = $dbCfg['username'] ?? $dbCfg['user'] ?? 'fixaos';
$dbPass = $dbCfg['password'] ?? $dbCfg['pass'] ?? '';
$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$artigos = [
    [
        'slug' => 'o-que-e-o-fixaos',
        'categoria' => 'Comercial',
        'titulo' => 'O que é o FixaOS',
        'palavras_chave' => 'o que é, como funciona, sistema, apresentação',
        'conteudo' => "O FixaOS é um sistema completo de gestão para assistências técnicas: ordens de serviço, "
            . "clientes (CRM), controle de estoque/produtos, PDV (frente de caixa), financeiro completo com comissão "
            . "de técnicos, marketplace de peças entre assistências, e uma página pública no Google para cada "
            . "assistência atrair novos clientes.",
    ],
    [
        'slug' => 'planos-e-precos-do-fixaos',
        'categoria' => 'Comercial',
        'titulo' => 'Planos e preços do FixaOS',
        'palavras_chave' => 'preço, plano, quanto custa, valor, mensalidade, assinatura',
        'conteudo' => "O FixaOS tem 3 planos. Estoque de produtos e vitrine do Marketplace são SEMPRE ilimitados, "
            . "em todos os planos — o que muda de plano pra plano é o tamanho da equipe/operação (usuários, OS por "
            . "mês) e duas conveniências de IA (leitura automática de etiqueta por foto e Mentor IA):\n"
            . "- Autônomo: R$29,90/mês (valor de lançamento pros primeiros 300 assinantes, depois R$59,90/mês) — "
            . "2 usuários, 60 OS/mês, leitura automática de etiqueta (40 buscas/mês), Mentor IA incluído, WhatsApp "
            . "com número próprio, edita a própria página no diretório. Sem foto no cadastro geral do estoque (a "
            . "foto entra só quando o produto é publicado no Marketplace ou na vitrine do Diretório, que sempre "
            . "aceitam foto).\n"
            . "- Oficina: R$59,90/mês — tudo do Autônomo, mais 5 usuários, 150 OS/mês, destaque no diretório, "
            . "e já aceita foto direto no cadastro do estoque (não só no Marketplace/Diretório).\n"
            . "- Top Empresa: R$119,90/mês — usuários ilimitados, OS ilimitadas por mês, leitura de etiqueta e de "
            . "placa ilimitadas, destaque premium, suporte prioritário — sem teto em nada.\n"
            . "Todos os planos têm ciclo mensal, trimestral (15% de desconto) e anual (20% de desconto). "
            . "Pagamento via InfinitePay (PIX ou cartão em até 12x).",
    ],
    [
        'slug' => 'teste-gratis-como-funciona',
        'categoria' => 'Comercial',
        'titulo' => 'Teste grátis — como funciona',
        'palavras_chave' => 'teste grátis, trial, sem cartão, período gratuito, experimentar, 7 dias',
        'conteudo' => "O FixaOS oferece teste grátis de 7 dias, sem precisar de cartão de crédito. Basta se cadastrar "
            . "em fixaos.com.br/cadastrar e configurar a assistência técnica em minutos. Depois do período de teste, "
            . "para continuar usando é só escolher um dos planos.",
    ],
    [
        'slug' => 'vagas-de-lancamento-promocao',
        'categoria' => 'Comercial',
        'titulo' => 'Vagas de lançamento — promoção dos primeiros 300 assinantes',
        'palavras_chave' => 'promoção, vagas, desconto, lançamento, primeiros assinantes, 29,90',
        'conteudo' => "O plano Autônomo custa R$29,90/mês apenas para os primeiros 300 assinantes confirmados "
            . "(pagamento efetivado). Depois que essa cota se esgota, o valor sobe para R$59,90/mês. Essa contagem é "
            . "real, feita pelo próprio sistema. Se perguntarem quantas vagas restam agora, explique que não dá pra "
            . "saber esse número em tempo real por aqui, mas que a página de cadastro (fixaos.com.br/cadastrar) sempre "
            . "mostra o valor vigente atualizado.",
    ],
    [
        'slug' => 'diferenciais-do-fixaos',
        'categoria' => 'Comercial',
        'titulo' => 'Diferenciais do FixaOS comparado a outros sistemas',
        'palavras_chave' => 'diferencial, concorrente, por que escolher, vantagem, comparação',
        'conteudo' => "O FixaOS foi feito para assistências técnicas pequenas e médias (celular, TV, eletrodomésticos), "
            . "com preço muito mais acessível que sistemas genéricos de gestão de campo. Diferenciais: marketplace de "
            . "peças entre assistências técnicas, página pública no Google (SEO) que traz clientes novos, pagamento "
            . "dividido, comissão de técnico integrada ao financeiro, modo offline pra consultar OS sem internet, e "
            . "busca global dentro do sistema.",
    ],
];

$check = $pdo->prepare("SELECT COUNT(*) FROM kb_artigos WHERE slug = ?");
$insert = $pdo->prepare("INSERT INTO kb_artigos (slug, categoria, titulo, palavras_chave, conteudo, ativo) VALUES (?,?,?,?,?,1)");

$criados = 0;
$pulados = 0;
foreach ($artigos as $a) {
    $check->execute([$a['slug']]);
    if ((int) $check->fetchColumn() > 0) { $pulados++; continue; }
    $insert->execute([$a['slug'], $a['categoria'], $a['titulo'], $a['palavras_chave'], $a['conteudo']]);
    $criados++;
}

echo "KB VENDAS OK: {$criados} artigo(s) criado(s), {$pulados} já existiam (pulados).\n";
