<?php
/**
 * Popula o Marketplace de Peças da empresa DEMO: créditos, histórico e alguns
 * anúncios fictícios (com imagens SVG geradas na hora, sem fingir foto real).
 * Não toca em `empresas.whatsapp`, então o botão de contato do comprador não
 * aparece na vitrine pública -- sem risco de expor um número de verdade.
 * Idempotente: só cria os anúncios se a empresa ainda não tiver nenhum.
 * Uso: php tools/demo_marketplace.php
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

// ── Créditos: garante saldo pra sempre poder testar "Anunciar Peça" na demo ──
$pdo->prepare("
    INSERT INTO marketplace_creditos (empresa_id, saldo_creditos) VALUES (?, 20)
    ON DUPLICATE KEY UPDATE saldo_creditos = GREATEST(saldo_creditos, 20)
")->execute([$eid]);

$jaTemHistorico = (int) $pdo->query("SELECT COUNT(*) FROM marketplace_historico_creditos WHERE empresa_id = $eid")->fetchColumn();
if ($jaTemHistorico === 0) {
    $pdo->prepare("
        INSERT INTO marketplace_historico_creditos (empresa_id, tipo, quantidade, justificativa, anuncio_id, usuario_id)
        VALUES (?, 'compra', 20, 'Créditos de boas-vindas', NULL, NULL)
    ")->execute([$eid]);
}

// ── Já tem anúncios? Não duplica. ──
$jaTemAnuncios = (int) $pdo->query("SELECT COUNT(*) FROM marketplace_anuncios WHERE empresa_id_vendedor = $eid")->fetchColumn();
if ($jaTemAnuncios > 0) {
    echo "MARKETPLACE DEMO: ja tem {$jaTemAnuncios} anuncio(s), nada a fazer.\n";
    exit(0);
}

// ── Imagens (SVG ilustrado, mesmo estilo do perfil público) ──
$dir = __DIR__ . '/../storage/uploads/marketplace/';
if (!is_dir($dir)) mkdir($dir, 0775, true);

function svgPeca(string $arquivo, string $corA, string $corB, string $emoji, string $titulo): void
{
    global $dir;
    $t = htmlspecialchars($titulo, ENT_XML1);
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="800" viewBox="0 0 800 800">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$corA}"/>
      <stop offset="100%" stop-color="{$corB}"/>
    </linearGradient>
  </defs>
  <rect width="800" height="800" fill="url(#g)"/>
  <circle cx="800" cy="0" r="260" fill="rgba(255,255,255,.06)"/>
  <circle cx="0" cy="800" r="260" fill="rgba(0,0,0,.1)"/>
  <text x="50%" y="360" font-size="180" text-anchor="middle" dominant-baseline="middle">{$emoji}</text>
  <text x="50%" y="470" font-family="Arial, sans-serif" font-weight="800" font-size="38" fill="#fff" text-anchor="middle">{$t}</text>
</svg>
SVG;
    file_put_contents($dir . $arquivo, $svg);
}

// ── Anúncios fictícios ──
$pecas = [
    [
        'arquivo' => 'demo-tela-iphone11.svg', 'corA' => '#1e3a5f', 'corB' => '#2d4f7c', 'emoji' => '📱',
        'titulo' => 'Tela Frontal iPhone 11 Original', 'tipo' => 'Tela/Display', 'marca' => 'Apple', 'modelo' => 'iPhone 11',
        'codigo' => 'TL-IPH11-001', 'valor' => 280.00,
        'descricao' => 'Peça original retirada de aparelho recondicionado, testada e 100% funcional. Sem risco, sem manchas. Envio pra todo o Brasil ou retirada na loja.',
    ],
    [
        'arquivo' => 'demo-placa-a32.svg', 'corA' => '#0f766e', 'corB' => '#0d5c56', 'emoji' => '🔩',
        'titulo' => 'Placa-mãe Samsung Galaxy A32', 'tipo' => 'Placa-mãe', 'marca' => 'Samsung', 'modelo' => 'Galaxy A32',
        'codigo' => 'PM-A32-002', 'valor' => 190.00,
        'descricao' => 'Placa testada em bancada, sem sinais de oxidação. Ideal pra quem já tem a carcaça e precisa só da placa.',
    ],
    [
        'arquivo' => 'demo-bateria-dell.svg', 'corA' => '#f97316', 'corB' => '#ea6c0a', 'emoji' => '🔋',
        'titulo' => 'Bateria Notebook Dell Inspiron 15', 'tipo' => 'Bateria', 'marca' => 'Dell', 'modelo' => 'Inspiron 15',
        'codigo' => 'BT-DELLI15-003', 'valor' => 145.00,
        'descricao' => 'Bateria nova, lacrada, compatível com toda a linha Inspiron 15. Garantia de 90 dias contra defeito de fábrica.',
    ],
    [
        'arquivo' => 'demo-flex-iphone12.svg', 'corA' => '#7c3aed', 'corB' => '#5b21b6', 'emoji' => '🔌',
        'titulo' => 'Flex de Carga iPhone 12', 'tipo' => 'Flex/Conector', 'marca' => 'Apple', 'modelo' => 'iPhone 12',
        'codigo' => 'FX-IPH12-004', 'valor' => 65.00,
        'descricao' => 'Flex de carga + microfone, peça nova. Envio no mesmo dia útil pra pedidos até 16h.',
    ],
    [
        'arquivo' => 'demo-fonte-atx.svg', 'corA' => '#0369a1', 'corB' => '#075985', 'emoji' => '⚡',
        'titulo' => 'Fonte ATX 500W Corsair CX500', 'tipo' => 'Fonte', 'marca' => 'Corsair', 'modelo' => 'CX500',
        'codigo' => 'FT-CX500-005', 'valor' => 210.00,
        'descricao' => 'Fonte seminova, poucos meses de uso, sem ruído. Testada com carga real antes do envio.',
    ],
    [
        'arquivo' => 'demo-wifi-tv.svg', 'corA' => '#be123c', 'corB' => '#9f1239', 'emoji' => '📡',
        'titulo' => 'Módulo Wi-Fi Smart TV Samsung', 'tipo' => 'Módulo/Placa', 'marca' => 'Samsung', 'modelo' => 'Series 5',
        'codigo' => 'WF-SMTV-006', 'valor' => 98.00,
        'descricao' => 'Módulo de Wi-Fi original retirado de TV com tela quebrada. Placa 100% funcional, testada.',
    ],
];

function gerarSlugSimples(string $titulo, PDO $pdo): string
{
    $mapa = ['á'=>'a','à'=>'a','ã'=>'a','â'=>'a','é'=>'e','ê'=>'e','í'=>'i','ó'=>'o','ô'=>'o','õ'=>'o','ú'=>'u','ç'=>'c'];
    $base = mb_strtolower(strtr(trim($titulo), $mapa), 'UTF-8');
    $base = preg_replace('/[^a-z0-9\s-]/', '', $base);
    $base = trim(preg_replace('/[\s-]+/', '-', $base), '-') ?: 'peca';
    $slug = $base; $i = 2;
    $check = $pdo->prepare("SELECT COUNT(*) FROM marketplace_anuncios WHERE slug = ?");
    while (true) {
        $check->execute([$slug]);
        if ((int) $check->fetchColumn() === 0) return $slug;
        $slug = $base . '-' . $i++;
    }
}

$insAnuncio = $pdo->prepare("
    INSERT INTO marketplace_anuncios
        (empresa_id_vendedor, titulo, descricao, tipo, marca, modelo, codigo_interno, valor, imagem_principal, status, slug)
    VALUES (?,?,?,?,?,?,?,?,?, 'ativo', ?)
");
$insHist = $pdo->prepare("
    INSERT INTO marketplace_historico_creditos (empresa_id, tipo, quantidade, justificativa, anuncio_id, usuario_id)
    VALUES (?, 'consumo', -1, ?, ?, NULL)
");

foreach ($pecas as $p) {
    svgPeca($p['arquivo'], $p['corA'], $p['corB'], $p['emoji'], $p['titulo']);
    $slug = gerarSlugSimples($p['titulo'], $pdo);
    $insAnuncio->execute([
        $eid, $p['titulo'], $p['descricao'], $p['tipo'], $p['marca'], $p['modelo'],
        $p['codigo'], $p['valor'], $p['arquivo'], $slug,
    ]);
    $anuncioId = (int) $pdo->lastInsertId();
    $insHist->execute([$eid, 'Anúncio: ' . $p['titulo'], $anuncioId]);
}

echo "MARKETPLACE DEMO OK: empresa_id={$eid}, " . count($pecas) . " anuncio(s) criado(s), 20 creditos.\n";
