<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$regimes     = \App\Controllers\VagasController::REGIMES;
$jornadas    = \App\Controllers\VagasController::JORNADAS;
$modalidades = \App\Controllers\VagasController::MODALIDADES;
$niveis      = \App\Controllers\VagasController::NIVEIS;
?>

<script type="application/ld+json">
<?= json_encode(['@context' => 'https://schema.org', '@type' => 'SearchResultsPage', 'name' => $tituloFull, 'description' => $metaDesc], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<style>
.vg-hero{background:linear-gradient(135deg,#0b0d10 0%,#1a2744 60%,#1e3a5f 100%);padding:3.5rem 0 2.5rem}
.vg-hero h1{color:#fff;font-weight:900;font-size:1.9rem;margin-bottom:.4rem}
.vg-hero p{color:#cbd5e1;font-size:1rem;max-width:560px}
.vg-filtros{background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:1.4rem;margin-top:1.5rem}
.vg-filtros label{color:#cbd5e1;font-size:.76rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.3rem;display:block}
.vg-filtros .form-select,.vg-filtros .form-control{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff}
.vg-filtros .form-select option{background:#1a1d23;color:#fff}
.vg-filtros .form-control::placeholder{color:#94a3b8}
.vg-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:1.2rem 1.4rem;text-decoration:none;display:block;transition:.2s}
.vg-card:hover{border-color:#f97316;box-shadow:0 8px 24px rgba(249,115,22,.1);transform:translateY(-2px)}
.vg-titulo{color:#0f172a;font-weight:800;font-size:1.05rem;margin-bottom:.2rem}
.vg-empresa{color:#64748b;font-size:.84rem;margin-bottom:.5rem}
.vg-tags{display:flex;flex-wrap:wrap;gap:.35rem}
.vg-tag{background:#f1f5f9;color:#475569;border-radius:6px;font-size:.72rem;font-weight:600;padding:.22rem .6rem}
.vg-tag-regime{background:#fff7ed;color:#c2410c}
.vg-sal{color:#16a34a;font-weight:700;font-size:.82rem;margin-top:.5rem}
</style>

<section class="vg-hero">
  <div class="container">
    <h1><i class="bi bi-briefcase me-2"></i>Vagas de emprego pra assistência técnica</h1>
    <p>Vagas publicadas direto por assistências técnicas de todo o Brasil. Contato direto pelo WhatsApp da empresa — sem cadastro, sem currículo no sistema.</p>

    <form class="vg-filtros row g-3" method="GET" action="<?= url('/vagas') ?>">
      <div class="col-md-4">
        <label>Buscar</label>
        <input type="text" name="busca" class="form-control" placeholder="Cargo, palavra-chave..." value="<?= e($filtro['busca']) ?>">
      </div>
      <div class="col-md-2">
        <label>Cidade</label>
        <input type="text" name="cidade" class="form-control" value="<?= e($filtro['cidade']) ?>">
      </div>
      <div class="col-md-1">
        <label>UF</label>
        <input type="text" name="uf" maxlength="2" class="form-control" style="text-transform:uppercase" value="<?= e($filtro['uf']) ?>">
      </div>
      <div class="col-md-2">
        <label>Regime</label>
        <select name="regime" class="form-select">
          <option value="">Todos</option>
          <?php foreach ($regimes as $k => $l): ?><option value="<?= $k ?>" <?= $filtro['regime'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label>Modalidade</label>
        <select name="modalidade" class="form-select">
          <option value="">Todas</option>
          <?php foreach ($modalidades as $k => $l): ?><option value="<?= $k ?>" <?= $filtro['modalidade'] === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-1 d-flex align-items-end">
        <button class="btn btn-warning fw-semibold w-100"><i class="bi bi-search"></i></button>
      </div>
    </form>
  </div>
</section>

<div class="container py-4">
  <p class="text-muted small mb-3"><?= (int) $paginator['total'] ?> vaga<?= $paginator['total'] === 1 ? '' : 's' ?> encontrada<?= $paginator['total'] === 1 ? '' : 's' ?></p>

  <?php if (!$vagas): ?>
  <div class="text-center text-muted py-5">
    <i class="bi bi-briefcase fs-1 d-block mb-3 opacity-25"></i>
    <p>Nenhuma vaga encontrada com esses filtros.</p>
    <a href="<?= url('/vagas') ?>" class="btn btn-outline-secondary btn-sm">Limpar filtros</a>
  </div>
  <?php else: ?>
  <div class="d-flex flex-column gap-3">
    <?php foreach ($vagas as $v): ?>
    <a href="<?= url('/vagas/' . $v['id']) ?>" class="vg-card">
      <div class="vg-titulo"><?= e($v['titulo']) ?></div>
      <div class="vg-empresa"><i class="bi bi-shop me-1"></i><?= e($v['empresa_nome']) ?><?php if ($v['cidade']): ?> · <i class="bi bi-geo-alt me-1"></i><?= e(trim($v['cidade'] . ($v['uf'] ? '/' . $v['uf'] : ''))) ?><?php endif; ?></div>
      <div class="vg-tags">
        <span class="vg-tag vg-tag-regime"><?= e($regimes[$v['regime']] ?? $v['regime']) ?></span>
        <span class="vg-tag"><?= e($jornadas[$v['jornada']] ?? $v['jornada']) ?></span>
        <span class="vg-tag"><?= e($modalidades[$v['modalidade']] ?? $v['modalidade']) ?></span>
        <?php if ($v['nivel']): ?><span class="vg-tag"><?= e($niveis[$v['nivel']] ?? $v['nivel']) ?></span><?php endif; ?>
      </div>
      <?php if (!$v['salario_a_combinar'] && ($v['salario_min'] || $v['salario_max'])): ?>
      <div class="vg-sal">
        <?php if ($v['salario_min'] && $v['salario_max']): ?>
          <?= money($v['salario_min']) ?> a <?= money($v['salario_max']) ?>
        <?php else: ?>
          <?= money($v['salario_min'] ?: $v['salario_max']) ?>
        <?php endif; ?>
      </div>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="mt-4"><?= pagination($paginator, url('/vagas')) ?></div>
  <?php endif; ?>
</div>
