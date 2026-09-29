<style>
.mpv-card { transition:.2s; border:1px solid #e9ecef; text-decoration:none; color:inherit; display:block; }
.mpv-card:hover { transform:translateY(-3px); box-shadow:0 8px 25px rgba(0,0,0,.1); color:inherit; }
.mpv-card, .mpv-card * { text-transform: none !important; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0">Empresas com anúncios</h5>
    <small class="text-muted">Todas as assistências do sistema que têm peças anunciadas</small>
  </div>
  <a href="<?= url('/marketplace') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Voltar pra vitrine
  </a>
</div>

<?php if (!$vendedores): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-shop-window fs-1 d-block mb-3 opacity-30"></i>
  <h5>Nenhuma empresa com anúncio ativo ainda</h5>
</div>
<?php else: ?>

<div class="row g-3">
  <?php foreach ($vendedores as $v): ?>
  <div class="col-sm-6 col-lg-4 col-xl-3">
    <a href="<?= url('/marketplace?empresa=' . (int) $v['id']) ?>" class="card mpv-card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3 p-3">
        <?php if (!empty($v['logo'])): ?>
        <img src="<?= url('/uploads/' . e($v['logo'])) ?>" alt="logo"
             style="width:44px;height:44px;object-fit:contain;border-radius:8px;flex-shrink:0">
        <?php else: ?>
        <div class="bg-primary text-white rounded d-flex align-items-center justify-content-center fw-bold"
             style="width:44px;height:44px;font-size:1rem;flex-shrink:0">
          <?= mb_strtoupper(mb_substr($v['nome_fantasia'] ?? '?', 0, 1)) ?>
        </div>
        <?php endif; ?>
        <div class="min-w-0">
          <div class="fw-bold text-truncate"><?= e($v['nome_fantasia']) ?></div>
          <?php if ($v['cidade']): ?>
          <div class="small text-muted"><i class="bi bi-geo-alt"></i> <?= e($v['cidade']) ?>/<?= e($v['uf']) ?></div>
          <?php endif; ?>
          <span class="badge bg-light text-dark border mt-1"><?= (int) $v['total_anuncios'] ?> anúncio<?= $v['total_anuncios'] != 1 ? 's' : '' ?></span>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>
