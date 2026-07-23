<?php
$corSaldo = $saldo > 0 ? 'success' : 'danger';
?>

<style>
.mp-card { transition:.2s; border:1px solid #e9ecef; }
.mp-card:hover { transform:translateY(-3px); box-shadow:0 8px 25px rgba(0,0,0,.1); }
.mp-preco { font-size:1.4rem; font-weight:800; color:#198754; }
.mp-badge-empresa { font-size:.72rem; background:#f8f9fa; border:1px solid #dee2e6; border-radius:20px; padding:2px 8px; }
.mp-filtros { background:#f8f9fa; border-radius:12px; padding:1.2rem; }
</style>

<!-- Topo: saldo + ação -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">Vitrine de Peças</h5>
    <small class="text-muted">Peças anunciadas por outras assistências</small>
  </div>
  <div class="d-flex gap-2 align-items-center">
    <span class="badge bg-<?= $corSaldo ?> fs-6">
      <i class="bi bi-coin me-1"></i><?= $saldo ?> crédito<?= $saldo !== 1 ? 's' : '' ?>
    </span>
    <a href="<?= url('/marketplace/meus-anuncios') ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-bag-check me-1"></i>Meus Anúncios
    </a>
  </div>
</div>

<!-- Filtros -->
<form method="GET" class="mp-filtros mb-4">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <input type="search" name="busca" class="form-control"
        placeholder="Buscar por título, marca, modelo..."
        value="<?= e($filtros['busca']) ?>">
    </div>
    <div class="col-md-3">
      <select name="tipo" class="form-select">
        <option value="">Todos os tipos</option>
        <?php foreach ($tipos as $t): ?>
        <option value="<?= e($t) ?>" <?= $filtros['tipo'] === $t ? 'selected' : '' ?>><?= e($t) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-3">
      <select name="marca" class="form-select">
        <option value="">Todas as marcas</option>
        <?php foreach ($marcas as $m): ?>
        <option value="<?= e($m) ?>" <?= $filtros['marca'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-primary flex-fill"><i class="bi bi-search"></i></button>
      <?php if (array_filter([$filtros['busca'],$filtros['tipo'],$filtros['marca']])): ?>
      <a href="<?= url('/marketplace') ?>" class="btn btn-outline-secondary"><i class="bi bi-x"></i></a>
      <?php endif; ?>
    </div>
  </div>
</form>

<!-- Resultado -->
<?php if (!$paginator['data']): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-bag-x fs-1 d-block mb-3 opacity-30"></i>
  <h5>Nenhuma peça encontrada</h5>
  <p>Tente outros filtros ou aguarde novos anúncios.</p>
  <a href="<?= url('/marketplace/meus-anuncios') ?>" class="btn btn-primary mt-2">
    <i class="bi bi-plus-lg me-1"></i>Anunciar minha peça
  </a>
</div>
<?php else: ?>

<div class="row g-3 mb-4">
  <?php foreach ($paginator['data'] as $item): ?>
  <?php
    $fone    = only_numbers($item['empresa_whatsapp'] ?? $item['empresa_telefone'] ?? '');
    $linkPeca = url('/pecas/' . ($item['slug'] ?? $item['id']));
    $msgWa   = urlencode("Olá! Vi seu anúncio no FixaOS e tenho interesse:\n\n📦 *{$item['titulo']}*\n💰 R$ " . number_format($item['valor'],2,',','.') . "\n🔗 {$linkPeca}\n\nAinda disponível?");
    $linkWa  = $fone ? "https://wa.me/55{$fone}?text={$msgWa}" : null;
  ?>
  <div class="col-sm-6 col-lg-4">
    <div class="card mp-card border-0 shadow-sm h-100">
      <div class="card-body d-flex flex-column p-3">

        <!-- Empresa vendedora -->
        <div class="d-flex align-items-center gap-2 mb-2">
          <?php if (!empty($item['empresa_logo'])): ?>
          <img src="<?= url('/uploads/' . e($item['empresa_logo'])) ?>" alt="logo"
               style="width:28px;height:28px;object-fit:contain;border-radius:4px">
          <?php else: ?>
          <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center fw-bold"
               style="width:28px;height:28px;font-size:.7rem">
            <?= mb_strtoupper(mb_substr($item['empresa_nome']??'?',0,1)) ?>
          </div>
          <?php endif; ?>
          <div class="min-w-0">
            <div class="mp-badge-empresa text-truncate"><?= e($item['empresa_nome']) ?></div>
            <?php if ($item['empresa_cidade']): ?>
            <div style="font-size:.7rem;color:#adb5bd">
              <i class="bi bi-geo-alt"></i> <?= e($item['empresa_cidade']) ?>/<?= e($item['empresa_uf']) ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Título e detalhes -->
        <h6 class="fw-bold mb-1"><?= e($item['titulo']) ?></h6>
        <div class="d-flex flex-wrap gap-1 mb-2">
          <?php if ($item['tipo']): ?>
          <span class="badge bg-light text-dark border" style="font-size:.7rem"><?= e($item['tipo']) ?></span>
          <?php endif; ?>
          <?php if ($item['marca']): ?>
          <span class="badge bg-light text-dark border" style="font-size:.7rem"><?= e($item['marca']) ?></span>
          <?php endif; ?>
          <?php if ($item['modelo']): ?>
          <span class="badge bg-light text-dark border" style="font-size:.7rem"><?= e($item['modelo']) ?></span>
          <?php endif; ?>
        </div>
        <?php if ($item['descricao']): ?>
        <p class="text-muted small mb-2 flex-grow-1" style="font-size:.82rem;line-height:1.4">
          <?= e(mb_substr($item['descricao'], 0, 80)) ?><?= mb_strlen($item['descricao']) > 80 ? '...' : '' ?>
        </p>
        <?php else: ?>
        <div class="flex-grow-1"></div>
        <?php endif; ?>

        <!-- Preço + botão -->
        <div class="d-flex align-items-center justify-content-between mt-auto pt-2 border-top">
          <span class="mp-preco"><?= money($item['valor']) ?></span>
          <?php if ($linkWa): ?>
          <a href="<?= $linkWa ?>" target="_blank"
             class="btn btn-success btn-sm fw-semibold">
            <i class="bi bi-whatsapp me-1"></i>Contatar
          </a>
          <?php else: ?>
          <span class="text-muted small"><i class="bi bi-telephone"></i> Sem WhatsApp</span>
          <?php endif; ?>
        </div>

      </div>
      <div class="card-footer bg-transparent border-0 pt-0 px-3 pb-2">
        <small class="text-muted">
          <i class="bi bi-clock me-1"></i>
          Anunciado em <?= date_br($item['data_criacao']) ?>
        </small>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Paginação -->
<?php if ($paginator['last_page'] > 1): ?>
<div class="d-flex justify-content-between align-items-center">
  <small class="text-muted"><?= $paginator['total'] ?> peça(s) encontrada(s)</small>
  <?= pagination($paginator, url('/marketplace')) ?>
</div>
<?php endif; ?>
<?php endif; ?>
