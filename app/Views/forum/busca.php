<!-- Busca no fórum -->
<div class="mb-4">
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= $appUrl ?>/forum">Fórum</a></li>
      <li class="breadcrumb-item active">Buscar</li>
    </ol>
  </nav>
  <h5 class="fw-bold mb-1"><i class="bi bi-search me-2 text-primary"></i>Busca no Fórum</h5>
</div>

<form method="GET" class="mb-4">
  <div class="input-group input-group-lg shadow-sm">
    <input type="search" name="q" class="form-control border-0"
      value="<?= e($q) ?>" placeholder="Buscar por defeito, marca, modelo..." autofocus>
    <button class="btn btn-primary px-4" type="submit"><i class="bi bi-search"></i></button>
  </div>
</form>

<?php if ($q && !$results): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-search fs-1 d-block mb-3 opacity-25"></i>
  <p>Nenhum resultado para <strong>"<?= e($q) ?>"</strong>.</p>
</div>
<?php elseif ($results): ?>
<div class="small text-muted mb-3"><?= count($results) ?> resultado(s) para <strong>"<?= e($q) ?>"</strong></div>
<div class="card border-0 shadow-sm">
  <ul class="list-group list-group-flush">
    <?php foreach ($results as $r): ?>
    <li class="list-group-item p-3">
      <div class="d-flex align-items-start gap-3">
        <div class="flex-grow-1">
          <a href="<?= $appUrl ?>/forum/topico/<?= $r['id'] ?>" class="fw-semibold text-decoration-none">
            <?= e($r['titulo']) ?>
          </a>
          <div class="small text-muted mt-1 d-flex gap-3 flex-wrap">
            <span><i class="bi bi-tag me-1"></i><?= e($r['cat_nome']) ?></span>
            <span><i class="bi bi-person me-1"></i><?= e($r['autor_nome']) ?>
              <span class="badge rounded-pill ms-1 <?= $r['autor_perfil']==='tecnico'?'badge-perfil-tecnico':'badge-perfil-default' ?>">
                <?= $r['autor_perfil']==='tecnico'?'Técnico':ucfirst($r['autor_perfil']) ?>
              </span>
            </span>
            <?php if ($r['marca'] || $r['modelo']): ?>
            <span><i class="bi bi-cpu me-1"></i><?= e(trim($r['marca'].' '.$r['modelo'])) ?></span>
            <?php endif; ?>
            <span><i class="bi bi-chat me-1"></i><?= $r['total_respostas'] ?> respostas</span>
            <span><i class="bi bi-clock me-1"></i><?= date_br($r['criado_em']) ?></span>
          </div>
        </div>
        <i class="bi bi-chevron-right text-muted mt-1"></i>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
</div>
<?php endif; ?>
