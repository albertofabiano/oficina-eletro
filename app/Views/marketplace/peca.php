<?php
$cfg     = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($cfg['url'], '/');
$slugUrl = $peca['slug'] ?? $peca['id'];
$url     = $baseUrl . '/pecas/' . $slugUrl;

$titulo  = htmlspecialchars($peca['titulo'], ENT_QUOTES, 'UTF-8');
$desc    = $peca['descricao']
    ? htmlspecialchars(mb_substr($peca['descricao'], 0, 160), ENT_QUOTES, 'UTF-8')
    : "Peça {$peca['tipo']} {$peca['marca']} disponível no FixaOS Marketplace. Contate o vendedor via WhatsApp.";
$preco   = number_format($peca['valor'], 2, '.', '');

$imgPrincipal = $peca['imagem_principal'] ?? null;
$galeria = !empty($peca['imagens_galeria']) ? json_decode($peca['imagens_galeria'], true) : [];
$todasImagens = array_filter(array_merge($imgPrincipal ? [$imgPrincipal] : [], $galeria));

$wa  = preg_replace('/\D/', '', $peca['empresa_whatsapp'] ?? $peca['empresa_telefone'] ?? '');
$tel = preg_replace('/\D/', '', $peca['empresa_telefone'] ?? '');
$msgWa = urlencode('Olá! Vi seu anúncio no FixaOS Marketplace e tenho interesse:' . "\n\n" . '📦 *' . $peca['titulo'] . '*' . "\n" . '💰 R$ ' . number_format($peca['valor'],2,',','.') . "\n" . '🔗 ' . $url . "\n\n" . 'Ainda disponível?');

$end = array_filter([
    ($peca['empresa_logradouro'] ?? '') . ($peca['empresa_numero'] ? ', '.$peca['empresa_numero'] : ''),
    $peca['empresa_bairro'] ?? '',
    ($peca['empresa_cidade'] ?? '') . ($peca['empresa_uf'] ? '/'.$peca['empresa_uf'] : ''),
]);
$endStr = implode(' · ', array_filter($end));
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $titulo ?> — R$ <?= number_format($peca['valor'],2,',','.') ?> | FixaOS Marketplace</title>
<meta name="description" content="<?= $desc ?>">
<meta name="robots" content="index, follow">
<link rel="canonical" href="<?= $url ?>">
<meta property="og:type"        content="product">
<meta property="og:title"       content="<?= $titulo ?>">
<meta property="og:description" content="<?= $desc ?>">
<meta property="og:url"         content="<?= $url ?>">
<meta property="og:site_name"   content="FixaOS Marketplace">
<?php if($imgPrincipal): ?><meta property="og:image" content="<?= $baseUrl ?>/uploads/marketplace/<?= $imgPrincipal ?>"><?php endif; ?>
<meta property="product:price:amount"   content="<?= $preco ?>">
<meta property="product:price:currency" content="BRL">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"Product","name":"<?= addslashes($peca['titulo']) ?>","description":"<?= addslashes($peca['descricao']??'') ?>","brand":{"@type":"Brand","name":"<?= addslashes($peca['marca']??'') ?>"},"offers":{"@type":"Offer","priceCurrency":"BRL","price":"<?= $preco ?>","availability":"https://schema.org/InStock","url":"<?= $url ?>","seller":{"@type":"LocalBusiness","name":"<?= addslashes($peca['empresa_nome']) ?>"}}}
</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
<style>
*{box-sizing:border-box}
body{font-family:'Inter',sans-serif;background:#f8fafc;color:#0f172a;-webkit-font-smoothing:antialiased}

/* NAV */
.top-nav{background:#1e3a5f;padding:.7rem 0;position:sticky;top:0;z-index:99;box-shadow:0 2px 12px rgba(0,0,0,.3)}

/* GALERIA */
.gallery-main{aspect-ratio:4/3;border-radius:16px;overflow:hidden;background:#f1f5f9;border:1px solid #e2e8f0;cursor:zoom-in;position:relative}
.gallery-main img{width:100%;height:100%;object-fit:contain;transition:transform .4s ease}
.gallery-main:hover img{transform:scale(1.06)}
.gallery-no-img{width:100%;aspect-ratio:4/3;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);border-radius:16px;display:flex;align-items:center;justify-content:center;border:1px solid #e2e8f0}
.thumb-row{display:flex;gap:8px;margin-top:10px;flex-wrap:wrap}
.thumb{width:70px;height:70px;border-radius:10px;overflow:hidden;border:2px solid #e2e8f0;cursor:pointer;flex-shrink:0;transition:.15s}
.thumb:hover,.thumb.active{border-color:#f97316}
.thumb img{width:100%;height:100%;object-fit:cover}

/* PRODUTO INFO */
.preco-big{font-size:2.2rem;font-weight:900;color:#16a34a;line-height:1}
.badge-tipo{background:#eff6ff;color:#1d4ed8;border-radius:6px;font-size:.75rem;font-weight:700;padding:.3rem .7rem}
.badge-marca{background:#f1f5f9;color:#475569;border-radius:6px;font-size:.75rem;font-weight:600;padding:.3rem .7rem;border:1px solid #e2e8f0}

/* CARD VENDEDOR */
.seller-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:16px;overflow:hidden}
.seller-header{background:linear-gradient(135deg,#1e3a5f,#2563eb);padding:1.2rem 1.4rem;display:flex;align-items:center;gap:.9rem}
.seller-logo{width:52px;height:52px;border-radius:12px;background:#fff;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0}
.seller-logo img{width:100%;height:100%;object-fit:contain;padding:4px}
.seller-logo span{font-size:1.4rem;font-weight:900;color:#1e3a5f}
.seller-nome{color:#fff;font-weight:800;font-size:1rem}
.seller-loc{color:rgba(255,255,255,.7);font-size:.8rem;margin-top:.15rem}
.seller-body{padding:1.2rem 1.4rem}
.btn-wa{background:#25d366;color:#fff;border:none;border-radius:12px;padding:.85rem 1.4rem;font-weight:800;font-size:1rem;width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;transition:.2s;margin-bottom:.6rem}
.btn-wa:hover{background:#1da852;color:#fff;transform:translateY(-1px)}
.btn-tel{background:#f1f5f9;color:#1e3a5f;border:1px solid #e2e8f0;border-radius:12px;padding:.7rem 1.4rem;font-weight:700;font-size:.92rem;width:100%;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;transition:.2s;margin-bottom:.6rem}
.btn-tel:hover{background:#e2e8f0;color:#1e3a5f}

/* TRUST BADGES */
.trust-badge{display:flex;align-items:center;gap:.4rem;font-size:.78rem;color:#64748b;padding:.4rem 0;border-bottom:1px solid #f1f5f9}
.trust-badge:last-child{border:none}
.trust-badge i{color:#16a34a}

/* SIMILARES */
.sim-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;text-decoration:none;display:block;transition:.2s}
.sim-card:hover{border-color:#f97316;transform:translateY(-2px);box-shadow:0 8px 24px rgba(0,0,0,.08)}
.sim-img{height:110px;background:#f8fafc;display:flex;align-items:center;justify-content:center;overflow:hidden}
.sim-img img{width:100%;height:100%;object-fit:cover}

/* BREADCRUMB */
.bc{background:#fff;border-bottom:1px solid #e2e8f0;padding:.6rem 0;font-size:.82rem}
</style>
<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-2477643924516118" crossorigin="anonymous"></script>
</head>
<body>

<!-- Navbar -->
<nav class="top-nav">
  <div class="container d-flex align-items-center justify-content-between">
    <a href="<?= $baseUrl ?>/pecas" class="d-flex align-items-center text-decoration-none">
      <svg width="100" height="25" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
      <span style="color:rgba(255,255,255,.5);margin:0 .5rem">/</span>
      <span style="color:#94a3b8;font-size:.82rem">Marketplace</span>
    </a>
    <div class="d-flex gap-2">
      <a href="<?= $baseUrl ?>/login"    class="btn btn-sm btn-outline-light" style="font-size:.8rem">Entrar</a>
      <a href="<?= $baseUrl ?>/cadastrar" class="btn btn-sm" style="background:#f97316;color:#fff;font-weight:700;font-size:.8rem">Anunciar</a>
    </div>
  </div>
</nav>

<!-- Breadcrumb -->
<div class="bc">
  <div class="container">
    <a href="<?= $baseUrl ?>/pecas" style="color:#f97316;text-decoration:none">← Marketplace</a>
    <?php if($peca['tipo']): ?>
    <span style="color:#94a3b8;margin:0 .4rem">/</span>
    <a href="<?= $baseUrl ?>/pecas?tipo=<?= urlencode($peca['tipo']) ?>" style="color:#64748b;text-decoration:none"><?= htmlspecialchars($peca['tipo']) ?></a>
    <?php endif; ?>
    <span style="color:#94a3b8;margin:0 .4rem">/</span>
    <span style="color:#0f172a;font-weight:600"><?= htmlspecialchars(mb_substr($peca['titulo'],0,50)) ?></span>
  </div>
</div>

<!-- Conteúdo principal -->
<div class="container py-4">
<div class="row g-4">

  <!-- COLUNA ESQUERDA — Galeria -->
  <div class="col-lg-5">

    <?php if($todasImagens): ?>
    <div class="gallery-main" id="galleryMain">
      <img id="imgMain" src="<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars(reset($todasImagens)) ?>" alt="<?= $titulo ?>">
    </div>
    <?php if(count($todasImagens) > 1): ?>
    <div class="thumb-row">
      <?php foreach($todasImagens as $i => $img): ?>
      <div class="thumb <?= $i===0?'active':'' ?>" onclick="trocarImg('<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars($img) ?>', this)">
        <img src="<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars($img) ?>" alt="">
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div class="gallery-no-img">
      <div class="text-center text-muted">
        <i class="bi bi-image" style="font-size:4rem;opacity:.2;display:block"></i>
        <div style="font-size:.85rem;margin-top:.5rem">Sem foto</div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Compartilhar -->
    <div class="mt-3 d-flex gap-2 flex-wrap">
      <a href="https://wa.me/?text=<?= urlencode($titulo.' - R$ '.number_format($peca['valor'],2,',','.').' - '.$url) ?>" target="_blank"
         class="btn btn-sm btn-outline-success">
        <i class="bi bi-whatsapp me-1"></i>Compartilhar
      </a>
      <button onclick="navigator.clipboard.writeText('<?= $url ?>');this.innerHTML='<i class=\'bi bi-check me-1\'></i>Copiado!'" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-link-45deg me-1"></i>Copiar link
      </button>
    </div>
  </div>

  <!-- COLUNA CENTRAL — Info do produto -->
  <div class="col-lg-4">

    <!-- Badges -->
    <div class="d-flex flex-wrap gap-2 mb-3">
      <?php if($peca['tipo']): ?><span class="badge-tipo"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($peca['tipo']) ?></span><?php endif; ?>
      <?php if($peca['marca']): ?><span class="badge-marca"><?= htmlspecialchars($peca['marca']) ?></span><?php endif; ?>
    </div>

    <!-- Título -->
    <h1 style="font-size:1.5rem;font-weight:800;color:#0f172a;line-height:1.25;margin-bottom:.5rem">
      <?= htmlspecialchars($peca['titulo']) ?>
    </h1>

    <!-- Modelo / código -->
    <?php if($peca['modelo']): ?>
    <div style="color:#64748b;font-size:.88rem;margin-bottom:.3rem">
      <i class="bi bi-cpu me-1"></i>Modelo: <strong style="color:#374151"><?= htmlspecialchars($peca['modelo']) ?></strong>
    </div>
    <?php endif; ?>
    <?php if($peca['codigo_interno']): ?>
    <div style="color:#64748b;font-size:.85rem;margin-bottom:.8rem">
      <i class="bi bi-upc me-1"></i>Código: <code style="background:#f1f5f9;padding:.1rem .4rem;border-radius:4px;color:#374151"><?= htmlspecialchars($peca['codigo_interno']) ?></code>
    </div>
    <?php endif; ?>

    <!-- Preço -->
    <div style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:1px solid #bbf7d0;border-radius:14px;padding:1.2rem;margin:1rem 0">
      <div style="color:#166534;font-size:.78rem;font-weight:600;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.3rem">Preço</div>
      <div class="preco-big">R$ <?= number_format($peca['valor'],2,',','.') ?></div>
      <div style="color:#16a34a;font-size:.78rem;margin-top:.3rem"><i class="bi bi-shield-check me-1"></i>Negociação direta com o vendedor</div>
    </div>

    <!-- Descrição -->
    <?php if($peca['descricao']): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1.1rem;margin-bottom:1rem">
      <div style="font-weight:700;font-size:.85rem;color:#374151;margin-bottom:.5rem"><i class="bi bi-file-text me-1" style="color:#f97316"></i>Descrição</div>
      <div style="color:#475569;font-size:.9rem;line-height:1.7;white-space:pre-wrap"><?= htmlspecialchars($peca['descricao']) ?></div>
    </div>
    <?php endif; ?>

    <!-- URL amigável -->
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.7rem 1rem;font-size:.75rem;color:#94a3b8">
      <i class="bi bi-link-45deg me-1"></i>
      <span style="color:#64748b"><?= $baseUrl ?>/pecas/</span><strong style="color:#0f172a"><?= htmlspecialchars($slugUrl) ?></strong>
    </div>
  </div>

  <!-- COLUNA DIREITA — Vendedor -->
  <div class="col-lg-3">
    <div class="seller-card">
      <div class="seller-header">
        <div class="seller-logo">
          <?php if($peca['empresa_logo']): ?>
          <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($peca['empresa_logo']) ?>" alt="Logo">
          <?php else: ?>
          <span><?= strtoupper(substr($peca['empresa_nome'],0,1)) ?></span>
          <?php endif; ?>
        </div>
        <div>
          <div class="seller-nome"><?= htmlspecialchars($peca['empresa_nome']) ?></div>
          <?php if($peca['empresa_cidade']): ?>
          <div class="seller-loc"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($peca['empresa_cidade']) ?>/<?= htmlspecialchars($peca['empresa_uf']) ?></div>
          <?php endif; ?>
        </div>
      </div>

      <div class="seller-body">
        <?php if($wa): ?>
        <a href="https://wa.me/55<?= $wa ?>?text=<?= $msgWa ?>" target="_blank" class="btn-wa">
          <i class="bi bi-whatsapp fs-5"></i>Chamar no WhatsApp
        </a>
        <?php endif; ?>

        <?php if($tel && $tel !== $wa): ?>
        <a href="tel:+55<?= $tel ?>" class="btn-tel">
          <i class="bi bi-telephone-fill"></i><?= htmlspecialchars($peca['empresa_telefone']) ?>
        </a>
        <?php endif; ?>

        <?php if(!$wa && !$tel): ?>
        <div class="text-center py-2 text-muted small">Contato não informado</div>
        <?php endif; ?>

        <!-- Trust badges -->
        <div class="mt-3">
          <div class="trust-badge"><i class="bi bi-check-circle-fill"></i>Empresa verificada no FixaOS</div>
          <?php if($endStr): ?>
          <div class="trust-badge"><i class="bi bi-geo-alt-fill"></i><?= htmlspecialchars($endStr) ?></div>
          <?php endif; ?>
          <div class="trust-badge"><i class="bi bi-shield-fill-check"></i>Negociação direta, sem intermediários</div>
        </div>

        <?php if($endStr && $peca['empresa_cidade']): ?>
        <a href="https://maps.google.com/?q=<?= urlencode($endStr) ?>" target="_blank"
           class="btn btn-sm btn-outline-secondary w-100 mt-3" style="font-size:.8rem">
          <i class="bi bi-map me-1"></i>Ver no mapa
        </a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Anunciar -->
    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:14px;padding:1rem;text-align:center;margin-top:1rem">
      <div style="color:#92400e;font-weight:700;font-size:.85rem;margin-bottom:.4rem">Tem peças para vender?</div>
      <div style="color:#b45309;font-size:.78rem;margin-bottom:.7rem">Anuncie no FixaOS e alcance centenas de assistências</div>
      <a href="<?= $baseUrl ?>/cadastrar" class="btn btn-sm w-100" style="background:#f97316;color:#fff;font-weight:700">
        <i class="bi bi-megaphone me-1"></i>Anunciar grátis
      </a>
    </div>
  </div>

</div>

<!-- Produtos similares -->
<?php
$db  = \App\Core\DB::pdo();
$sim = $db->prepare("SELECT a.*, e.cidade, e.uf FROM marketplace_anuncios a JOIN empresas e ON e.id=a.empresa_id_vendedor WHERE a.status='ativo' AND a.id != ? AND (a.tipo=? OR a.marca=?) ORDER BY RAND() LIMIT 4");
$sim->execute([$peca['id'], $peca['tipo'], $peca['marca']]);
$similares = $sim->fetchAll();
if($similares):
?>
<hr class="my-4">
<h2 style="font-size:1.1rem;font-weight:800;margin-bottom:1.2rem"><i class="bi bi-grid me-2" style="color:#f97316"></i>Produtos similares</h2>
<div class="row g-3">
  <?php foreach($similares as $s): ?>
  <div class="col-6 col-md-3">
    <a href="<?= $baseUrl ?>/pecas/<?= htmlspecialchars($s['slug'] ?? $s['id']) ?>" class="sim-card">
      <div class="sim-img">
        <?php if($s['imagem_principal']): ?>
        <img src="<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars($s['imagem_principal']) ?>" alt="">
        <?php else: ?>
        <i class="bi bi-image" style="font-size:2rem;color:#cbd5e1"></i>
        <?php endif; ?>
      </div>
      <div style="padding:.8rem">
        <div style="font-size:.85rem;font-weight:700;color:#0f172a;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.3rem"><?= htmlspecialchars($s['titulo']) ?></div>
        <div style="font-size:.78rem;color:#64748b;margin-bottom:.4rem"><?= htmlspecialchars($s['cidade']) ?>/<?= htmlspecialchars($s['uf']) ?></div>
        <div style="font-size:1rem;font-weight:800;color:#16a34a">R$ <?= number_format($s['valor'],2,',','.') ?></div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

</div><!-- /container -->

<footer style="background:#1e3a5f;color:#94a3b8;padding:1.5rem 0;margin-top:3rem;font-size:.82rem;text-align:center">
  <div class="container">
    <svg width="80" height="20" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" style="margin-bottom:.5rem;display:inline-block"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
    <div style="margin-top:.3rem">Marketplace de Peças para Assistências Técnicas · <a href="<?= $baseUrl ?>/pecas" style="color:#60a5fa;text-decoration:none">Ver todos os anúncios</a></div>
  </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function trocarImg(src, thumb) {
  document.getElementById('imgMain').src = src;
  document.querySelectorAll('.thumb').forEach(t => t.classList.remove('active'));
  thumb.classList.add('active');
}
</script>
</body>
</html>
