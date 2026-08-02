<?php
/**
 * Popula e publica o perfil público completo da empresa DEMO no diretório do FixaOS:
 * descrição, especialidades, horário de funcionamento, badges de serviço, galeria de
 * fotos (ilustrações SVG geradas aqui — não são fotos reais, é a empresa fictícia de
 * demonstração), e avaliações verificadas. NÃO preenche telefone/WhatsApp/e-mail/redes
 * sociais reais de propósito, pra nunca direcionar visitantes a um contato de verdade.
 * Idempotente: pode rodar de novo a qualquer momento (ex.: depois do reset automático
 * da demo, que só zera `listagem_publica` — o resto sobrevive). Uso: php tools/demo_perfil_publico.php
 */
$dbCfg = require __DIR__ . '/../config/database.php';
$dbHost = $dbCfg['host'] ?? '127.0.0.1';
$dbName = $dbCfg['database'] ?? $dbCfg['dbname'] ?? 'fixaos';
$dbUser = $dbCfg['username'] ?? $dbCfg['user'] ?? 'fixaos';
$dbPass = $dbCfg['password'] ?? $dbCfg['pass'] ?? '';
$pdo = new PDO("mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

$stmt = $pdo->prepare("SELECT id, empresa_id FROM usuarios WHERE email = ? LIMIT 1");
$stmt->execute(['demo@fixaos.com.br']);
$u = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$u) { fwrite(STDERR, "Empresa demo nao encontrada -- rode tools/demo_seed.php primeiro.\n"); exit(1); }
$eid = (int) $u['empresa_id'];

// ── Imagens (SVG ilustrado — deixa claro que é a vitrine de demonstração, sem fingir foto real) ──
$dirRaiz  = __DIR__ . '/../storage/uploads/';
$dirFotos = __DIR__ . '/../storage/uploads/fotos/';
$dirLogos = __DIR__ . '/../storage/uploads/logos/';
foreach ([$dirRaiz, $dirFotos, $dirLogos] as $d) if (!is_dir($d)) mkdir($d, 0775, true);

function svgCard(int $w, int $h, string $corA, string $corB, string $emoji, string $titulo, string $subtitulo = ''): string
{
    $t = htmlspecialchars($titulo, ENT_XML1);
    $s = htmlspecialchars($subtitulo, ENT_XML1);
    $fsEmoji = (int) round($h * 0.22);
    $fsTit   = (int) round($h * 0.062);
    $fsSub   = (int) round($h * 0.036);
    $yEmoji  = (int) round($h * 0.42);
    $yTit    = (int) round($h * 0.62);
    $ySub    = (int) round($h * 0.71);
    $sub     = $subtitulo !== '' ? "<text x=\"50%\" y=\"{$ySub}\" font-family=\"Arial, sans-serif\" font-size=\"{$fsSub}\" fill=\"rgba(255,255,255,.8)\" text-anchor=\"middle\">{$s}</text>" : '';
    return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="{$w}" height="{$h}" viewBox="0 0 {$w} {$h}">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$corA}"/>
      <stop offset="100%" stop-color="{$corB}"/>
    </linearGradient>
  </defs>
  <rect width="{$w}" height="{$h}" fill="url(#g)"/>
  <circle cx="{$w}" cy="0" r="{$h}" fill="rgba(255,255,255,.05)"/>
  <circle cx="0" cy="{$h}" r="{$h}" fill="rgba(0,0,0,.08)"/>
  <text x="50%" y="{$yEmoji}" font-size="{$fsEmoji}" text-anchor="middle" dominant-baseline="middle">{$emoji}</text>
  <text x="50%" y="{$yTit}" font-family="Arial, sans-serif" font-weight="800" font-size="{$fsTit}" fill="#fff" text-anchor="middle">{$t}</text>
  {$sub}
</svg>
SVG;
}

// Logo — monograma "AM" (Assistência Modelo) em navy/laranja, no estilo visual do FixaOS
$logoSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="300" height="300" viewBox="0 0 300 300">
  <rect width="300" height="300" rx="42" fill="#1e3a5f"/>
  <text x="50%" y="56%" font-family="Arial Black, Arial, sans-serif" font-weight="900" font-size="118" fill="#fff" text-anchor="middle">A<tspan fill="#f97316">M</tspan></text>
  <text x="50%" y="82%" font-family="Arial, sans-serif" font-weight="700" font-size="22" letter-spacing="2" fill="#fbbf24" text-anchor="middle">ASSISTÊNCIA</text>
</svg>
SVG;
file_put_contents($dirLogos . 'demo_logo.svg', $logoSvg);

// Capa (hero) — larga, cobre o topo do perfil público
// A capa é exibida num container bem mais largo que alto (object-fit:cover, ~220px de altura
// em qualquer largura de tela), então o topo/base da imagem sempre acabam cortados. Por isso o
// conteúdo fica compacto e centralizado numa faixa estreita no meio, com boa margem de segurança.
$capaSvg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1600" height="480" viewBox="0 0 1600 480">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="#1e3a5f"/>
      <stop offset="100%" stop-color="#0b1a2e"/>
    </linearGradient>
  </defs>
  <rect width="1600" height="480" fill="url(#g)"/>
  <circle cx="1600" cy="0" r="480" fill="rgba(255,255,255,.05)"/>
  <circle cx="0" cy="480" r="480" fill="rgba(0,0,0,.08)"/>
  <text x="50%" y="216" font-size="60" text-anchor="middle" dominant-baseline="middle">🛠️</text>
  <text x="50%" y="264" font-family="Arial, sans-serif" font-weight="800" font-size="34" fill="#fff" text-anchor="middle">Assistência Modelo</text>
  <text x="50%" y="296" font-family="Arial, sans-serif" font-size="17" fill="rgba(255,255,255,.8)" text-anchor="middle">Celulares · TVs · Notebooks · Eletrodomésticos</text>
</svg>
SVG;
file_put_contents($dirRaiz . 'demo_capa.svg', $capaSvg);

// Galeria (4 fotos ilustrativas — 800x800, mesmo formato do upload real)
$galeria = [
    ['demo_foto1.svg', '#1e3a5f', '#2d4f7c', '🏬', 'Fachada da Loja', 'Fácil de encontrar, no coração da cidade'],
    ['demo_foto2.svg', '#f97316', '#ea6c0a', '🔧', 'Bancada de Testes', 'Equipamento próprio pra diagnóstico preciso'],
    ['demo_foto3.svg', '#0f766e', '#0d5c56', '👥', 'Equipe Técnica', 'Time treinado e sempre disponível'],
    ['demo_foto4.svg', '#7c3aed', '#5b21b6', '🗂️', 'Balcão de Atendimento', 'Orçamento na hora, sem enrolação'],
];
foreach ($galeria as [$arquivo, $a, $b, $emoji, $tit, $sub]) {
    file_put_contents($dirFotos . $arquivo, svgCard(800, 800, $a, $b, $emoji, $tit, $sub));
}

// ── Perfil da empresa ──
$descricao = "A Assistência Modelo atua há mais de 8 anos consertando celulares, TVs, notebooks e "
    . "eletrodomésticos com transparência e agilidade. Todo orçamento é aprovado pelo cliente antes de "
    . "começar o serviço, e todo reparo sai com garantia por escrito. Equipe treinada, peças de qualidade "
    . "e acompanhamento do andamento em tempo real — sem letra miúda, sem susto na hora de pagar.";

$horario = json_encode([
    'seg' => ['aberto' => true, 'abre' => '08:00', 'fecha' => '18:00'],
    'ter' => ['aberto' => true, 'abre' => '08:00', 'fecha' => '18:00'],
    'qua' => ['aberto' => true, 'abre' => '08:00', 'fecha' => '18:00'],
    'qui' => ['aberto' => true, 'abre' => '08:00', 'fecha' => '18:00'],
    'sex' => ['aberto' => true, 'abre' => '08:00', 'fecha' => '18:00'],
    'sab' => ['aberto' => true, 'abre' => '09:00', 'fecha' => '13:00'],
    'dom' => ['aberto' => false],
], JSON_UNESCAPED_UNICODE);

// Slug fixo e previsível (bate com a URL que o "Ver minha página pública" já monta)
$slug = 'assistencia-modelo-demo-sao-paulo-sp';

$pdo->prepare("
    UPDATE empresas SET
        slug = ?,
        listagem_publica = 1,
        reivindicada = 1,
        licenca_ate = DATE_ADD(CURDATE(), INTERVAL 3650 DAY),
        descricao_publica = ?,
        especialidades = ?,
        horario_funcionamento = ?,
        logradouro = 'Rua Modelo', numero = '100', bairro = 'Centro',
        logo = ?, foto_capa = ?
    WHERE id = ?
")->execute([
    $slug,
    $descricao,
    'Celulares, TVs, Notebooks, Eletrodomésticos, Consoles de videogame',
    $horario,
    'demo_logo.svg',
    'demo_capa.svg',
    $eid,
]);

// ── Badges de serviço (empresa_servicos) — idempotente: apaga e recria, igual à tela real ──
$pdo->prepare("DELETE FROM empresa_servicos WHERE empresa_id = ?")->execute([$eid]);
$servicos = [
    ['Conserto de Celular', 'bi-phone'],
    ['Reparo de TV', 'bi-tv'],
    ['Notebooks e PCs', 'bi-laptop'],
    ['Eletrodomésticos', 'bi-house-gear'],
    ['Consoles de Videogame', 'bi-controller'],
    ['Orçamento sem Compromisso', 'bi-clipboard-check'],
];
$insServ = $pdo->prepare("INSERT INTO empresa_servicos (empresa_id, nome, icone, ordem) VALUES (?,?,?,?)");
foreach ($servicos as $i => [$nome, $icone]) $insServ->execute([$eid, $nome, $icone, $i]);

// ── Galeria (empresa_fotos) — só insere se ainda não tiver as 4 ──
$stmtQtd = $pdo->prepare("SELECT COUNT(*) FROM empresa_fotos WHERE empresa_id = ?");
$stmtQtd->execute([$eid]);
$qtdFotos = (int) $stmtQtd->fetchColumn();
if ($qtdFotos < 4) {
    $pdo->prepare("DELETE FROM empresa_fotos WHERE empresa_id = ?")->execute([$eid]);
    $insFoto = $pdo->prepare("INSERT INTO empresa_fotos (empresa_id, arquivo, principal, ordem) VALUES (?,?,?,?)");
    foreach ($galeria as $i => [$arquivo]) $insFoto->execute([$eid, $arquivo, $i === 0 ? 1 : 0, $i]);
}

// ── Avaliações verificadas (sem os_id — imunes ao reset, que recria as OS da demo) ──
$jaTemAvaliacoes = (int) $pdo->query("SELECT COUNT(*) FROM diretorio_avaliacoes WHERE empresa_id = $eid")->fetchColumn();
if ($jaTemAvaliacoes === 0) {
    $avaliacoes = [
        ['Mariana Costa Ferreira', 5, 'Troca de tela do meu celular ficou perfeita, atendimento super rápido e me mandaram o orçamento pelo WhatsApp em minutos.', 6, null],
        ['Roberto Almeida Souza', 5, 'Levei minha TV com defeito na placa e resolveram no mesmo dia. Recomendo!', 14, null],
        ['Juliana Pereira Lima', 4, 'Bom atendimento, só achei o prazo um pouco maior que o combinado.', 21, 'Obrigado pelo retorno, Juliana! Estamos sempre buscando agilizar ainda mais os prazos.'],
        ['Carlos Eduardo Santos', 5, 'Equipe muito atenciosa, expliquei o problema e já sabiam o que era. Preço justo.', 33, null],
        ['Fernanda Ribeiro Alves', 5, 'Melhor assistência da região, já é a segunda vez que uso e sempre resolve rápido.', 45, null],
    ];
    $insAv = $pdo->prepare("
        INSERT INTO diretorio_avaliacoes (empresa_id, os_id, nome, nota, comentario, aprovado, verificada, situacao, criado_em, resposta, resposta_em)
        VALUES (?, NULL, ?, ?, ?, 1, 1, 'publicada', DATE_SUB(NOW(), INTERVAL ? DAY), ?, ?)
    ");
    foreach ($avaliacoes as [$nome, $nota, $comentario, $diasAtras, $resposta]) {
        $respostaEm = $resposta ? date('Y-m-d H:i:s', strtotime("-" . max(0, $diasAtras - 2) . " days")) : null;
        $insAv->execute([$eid, $nome, $nota, $comentario, $diasAtras, $resposta, $respostaEm]);
    }
}

echo "PERFIL PUBLICO DEMO OK: empresa_id={$eid}, slug={$slug}, publicada em /assistencias/{$slug}\n";
