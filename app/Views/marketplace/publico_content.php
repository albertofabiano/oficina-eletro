<?php
// View de conteúdo — usada dentro do layout main (usuário logado)
$busca    = htmlspecialchars($filtros['busca'] ?? '', ENT_QUOTES, 'UTF-8');
$tipoFilt = htmlspecialchars($filtros['tipo']  ?? '', ENT_QUOTES, 'UTF-8');
$marcaFilt= htmlspecialchars($filtros['marca'] ?? '', ENT_QUOTES, 'UTF-8');

// Carregar blocos de anúncio
$db = \App\Core\DB::pdo();
$adBlocos = $db->query("SELECT * FROM master_adsense_blocos WHERE ativo=1 ORDER BY posicao")->fetchAll();
$adMap    = [];
foreach ($adBlocos as $b) $adMap[$b['posicao']] = $b['codigo'];

// Categorias (tipos e marcas para sidebar)
$sidebarTipos  = array_slice($tipos, 0, 15);
$sidebarMarcas = array_slice($marcas, 0, 12);
?>

<style>
.mp-card { transition:.2s; border:1.5px solid #cbd5e1 !important; border-radius:12px; }
.mp-card:hover { transform:translateY(-3px); box-shadow:0 10px 28px rgba(0,0,0,.12); }
.mp-preco { font-size:1.3rem; font-weight:800; color:#198754; }
.mp-img { width:100%; aspect-ratio:4/3; object-fit:cover; border-radius:10px 10px 0 0; }
.mp-img-placeholder { width:100%; aspect-ratio:4/3; border-radius:10px 10px 0 0; background:linear-gradient(135deg,#f0f2f5,#e2e6ea); display:flex; align-items:center; justify-content:center; color:#adb5bd; }
.badge-tipo { background:#e8f0fe; color:#1a56db; font-size:.72rem; }

/* Sidebar sticky */
.mp-sidebar { position:sticky; top:80px; }
.mp-sidebar-card { border-radius:12px; border:1px solid #e2e8f0; background:#fff; overflow:hidden; margin-bottom:16px; }
.mp-sidebar-title { font-size:.75rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#64748b; padding:12px 14px 8px; border-bottom:1px solid #f1f5f9; }
.mp-cat-link { display:block; padding:8px 14px; font-size:.82rem; color:#334155; text-decoration:none; border-bottom:1px solid #f8fafc; transition:.13s; font-weight:500; }
.mp-cat-link:hover { background:#eff6ff; color:#2563eb; }
.mp-cat-link.ativo { background:#eff6ff; color:#2563eb; font-weight:700; border-left:3px solid #2563eb; }
.mp-cat-link:last-child { border-bottom:none; }
.ad-bloco { border-radius:12px; overflow:hidden; background:#f8fafc; border:1px dashed #cbd5e1; min-height:100px; display:flex; align-items:center; justify-content:center; margin-bottom:16px; }
.ad-bloco.tem-codigo { background:transparent; border:none; display:block; }
</style>

<!-- Layout com sidebar -->
<div id="mpBody" class="row g-4">

  <!-- SIDEBAR ESQUERDA -->
  <div class="col-lg-3 d-none d-lg-block">
    <div class="mp-sidebar">

      <!-- Busca rápida -->
      <div class="mp-sidebar-card">
        <div class="mp-sidebar-title"><i class="bi bi-search me-1"></i>Buscar</div>
        <div class="p-3">
          <form method="GET" action="<?= $baseUrl ?>/pecas" class="mp-ajax-form">
            <div class="input-group input-group-sm">
              <input type="search" name="busca" class="form-control border-end-0"
                     placeholder="Peça, marca, modelo..." value="<?= $busca ?>">
              <button class="btn btn-primary border-start-0" type="submit">
                <i class="bi bi-search"></i>
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Categorias (Tipos) -->
      <?php if ($sidebarTipos): ?>
      <div class="mp-sidebar-card">
        <div class="mp-sidebar-title"><i class="bi bi-tag me-1"></i>Tipo de Peça</div>
        <a href="<?= $baseUrl ?>/pecas" class="mp-cat-link <?= !$tipoFilt && !$marcaFilt ? 'ativo' : '' ?>">
          Todos os tipos
        </a>
        <?php foreach ($sidebarTipos as $t): ?>
        <a href="<?= $baseUrl ?>/pecas?tipo=<?= urlencode($t) ?>"
           class="mp-cat-link <?= $tipoFilt === $t ? 'ativo' : '' ?>">
          <?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Marcas -->
      <?php if ($sidebarMarcas): ?>
      <div class="mp-sidebar-card">
        <div class="mp-sidebar-title"><i class="bi bi-cpu me-1"></i>Marca</div>
        <?php foreach ($sidebarMarcas as $m): ?>
        <a href="<?= $baseUrl ?>/pecas?marca=<?= urlencode($m) ?>"
           class="mp-cat-link <?= $marcaFilt === $m ? 'ativo' : '' ?>">
          <?= htmlspecialchars($m, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Bloco de anúncio 1 -->
      <?php if (!empty($adMap[1])): ?>
      <div class="ad-bloco tem-codigo"><?= $adMap[1] ?></div>
      <?php else: ?>
      <div class="ad-bloco text-muted small text-center flex-column gap-1">
        <i class="bi bi-megaphone fs-4 opacity-25 d-block"></i>
        <span style="font-size:.72rem">Espaço publicitário</span>
      </div>
      <?php endif; ?>

      <!-- Bloco de anúncio 2 -->
      <?php if (!empty($adMap[2])): ?>
      <div class="ad-bloco tem-codigo"><?= $adMap[2] ?></div>
      <?php else: ?>
      <div class="ad-bloco text-muted small text-center flex-column gap-1">
        <i class="bi bi-megaphone fs-4 opacity-25 d-block"></i>
        <span style="font-size:.72rem">Espaço publicitário</span>
      </div>
      <?php endif; ?>

      <!-- Bloco de anúncio 3 -->
      <?php if (!empty($adMap[3])): ?>
      <div class="ad-bloco tem-codigo"><?= $adMap[3] ?></div>
      <?php else: ?>
      <div class="ad-bloco text-muted small text-center flex-column gap-1">
        <i class="bi bi-megaphone fs-4 opacity-25 d-block"></i>
        <span style="font-size:.72rem">Espaço publicitário</span>
      </div>
      <?php endif; ?>

    </div>
  </div>

  <!-- CONTEÚDO PRINCIPAL -->
  <div class="col-lg-9">

    <!-- Cabeçalho -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
      <div>
        <h5 class="fw-bold mb-0"><i class="bi bi-shop me-2 text-primary"></i><?= $empresaNome ? 'Anúncios de ' . e($empresaNome) : 'Marketplace de Peças' ?></h5>
        <small class="text-muted">
          <?= $paginator['total'] ?> peça(s) disponível(is)
          <?php if ($empresaNome): ?> — <a class="mp-ajax-link" href="<?= url('/pecas') ?>">Ver todo o marketplace</a><?php endif; ?>
        </small>
      </div>
      <a href="<?= url('/marketplace/meus-anuncios') ?>" class="btn btn-primary btn-sm fw-semibold">
        <i class="bi bi-bag-check me-1"></i>Meus Anúncios
      </a>
    </div>

    <!-- Filtros mobile (somente em telas pequenas) -->
    <div class="d-lg-none mb-3">
      <form method="GET" action="<?= $baseUrl ?>/pecas" class="mp-ajax-form d-flex gap-2 flex-wrap">
        <input type="search" name="busca" class="form-control form-control-sm flex-grow-1"
               placeholder="Buscar..." value="<?= $busca ?>">
        <select name="tipo" class="form-select form-select-sm" style="width:auto" onchange="this.form.requestSubmit()">
          <option value="">Todos os tipos</option>
          <?php foreach ($tipos as $t): ?>
          <option value="<?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?>" <?= $tipoFilt===$t?'selected':'' ?>><?= htmlspecialchars($t, ENT_QUOTES, 'UTF-8') ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button>
      </form>
    </div>

    <!-- Grid de produtos -->
    <?php if (!$paginator['data']): ?>
    <div class="text-center py-5 text-muted">
      <i class="bi bi-search fs-1 d-block mb-3 opacity-30"></i>
      <h5>Nenhuma peça encontrada</h5>
      <a class="mp-ajax-link" href="<?= $baseUrl ?>/pecas">Ver todas as peças</a>
    </div>
    <?php else: ?>
    <div class="row g-3 mb-4">
      <?php foreach ($paginator['data'] as $item): ?>
      <div class="col-sm-6 col-xl-4">
        <a href="<?= $baseUrl ?>/pecas/<?= $item['id'] ?>" class="text-decoration-none text-dark">
          <div class="card mp-card h-100">
            <?php if (!empty($item['imagem_principal'])): ?>
            <img src="<?= $baseUrl ?>/uploads/marketplace/<?= htmlspecialchars($item['imagem_principal'], ENT_QUOTES, 'UTF-8') ?>"
                 class="mp-img" alt="<?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?>" loading="lazy">
            <?php else: ?>
            <div class="mp-img-placeholder"><i class="bi bi-image" style="font-size:2.5rem"></i></div>
            <?php endif; ?>

            <div class="card-body d-flex flex-column p-3">
              <div class="d-flex flex-wrap gap-1 mb-2">
                <?php if ($item['tipo']): ?>
                <span class="badge badge-tipo"><?= htmlspecialchars($item['tipo'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
                <?php if ($item['marca']): ?>
                <span class="badge bg-light text-dark border" style="font-size:.7rem"><?= htmlspecialchars($item['marca'], ENT_QUOTES, 'UTF-8') ?></span>
                <?php endif; ?>
              </div>
              <h6 class="fw-bold mb-1 flex-grow-1"><?= htmlspecialchars($item['titulo'], ENT_QUOTES, 'UTF-8') ?></h6>
              <?php if ($item['modelo']): ?>
              <div class="text-muted small mb-2"><?= htmlspecialchars($item['modelo'], ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
              <?php if ($item['descricao']): ?>
              <p class="text-muted small mb-2" style="font-size:.8rem;line-height:1.4">
                <?= htmlspecialchars(mb_substr($item['descricao'], 0, 70), ENT_QUOTES, 'UTF-8') ?><?= mb_strlen($item['descricao']) > 70 ? '...' : '' ?>
              </p>
              <?php endif; ?>
              <div class="d-flex justify-content-between align-items-end mt-auto pt-2 border-top">
                <span class="mp-preco"><?= 'R$ ' . number_format($item['valor'],2,',','.') ?></span>
                <small class="text-muted">
                  <i class="bi bi-geo-alt"></i>
                  <?= htmlspecialchars($item['empresa_cidade'], ENT_QUOTES, 'UTF-8') ?>/<?= htmlspecialchars($item['empresa_uf'], ENT_QUOTES, 'UTF-8') ?>
                </small>
              </div>
            </div>

            <div class="card-footer bg-transparent border-top-0 px-3 pb-3">
              <div class="w-100 d-flex align-items-center justify-content-center gap-2 py-2 px-3 rounded-3 fw-semibold"
                   style="background:linear-gradient(135deg,#16a34a,#15803d);color:#fff;font-size:.82rem;cursor:pointer">
                <i class="bi bi-eye me-1"></i>Ver detalhes e contato
              </div>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach; ?>
    </div>

    <?php if ($paginator['last_page'] > 1): ?>
    <?= pagination($paginator, $baseUrl . '/pecas') ?>
    <?php endif; ?>
    <?php endif; ?>

  </div><!-- /col principal -->

</div><!-- /row / #mpBody -->

<script src="<?= url('/js/marketplace-ajax.js') ?>"></script>
