<?php require __DIR__ . '/menu.php'; ?>

<div class="row g-4">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="col-lg-9">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb mb-1 small">
        <li class="breadcrumb-item"><a href="<?= url('/forum') ?>">Fórum</a></li>
        <li class="breadcrumb-item active"><?= e($categoria['nome']) ?></li>
      </ol>
    </nav>
    <h5 class="fw-bold mb-0">
      <i class="bi <?= e($categoria['icone']) ?> me-2" style="color:<?= e($categoria['cor']) ?>"></i>
      <?= e($categoria['nome']) ?>
    </h5>
    <small class="text-muted"><?= e($categoria['descricao']) ?></small>
  </div>
  <a href="<?= url('/forum/novo?categoria=' . $categoria['id']) ?>" class="btn btn-primary fw-semibold">
    <i class="bi bi-plus-lg me-1"></i>Novo Tópico
  </a>
</div>

<!-- Busca -->
<form method="GET" class="mb-3">
  <div class="input-group">
    <input type="search" name="busca" class="form-control"
      placeholder="Buscar tópico, marca, modelo..."
      value="<?= e($busca) ?>">
    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i></button>
    <?php if ($busca): ?>
    <a href="<?= url('/forum/categoria/' . $categoria['id']) ?>" class="btn btn-outline-secondary">
      <i class="bi bi-x"></i>
    </a>
    <?php endif; ?>
  </div>
</form>

<!-- Lista de tópicos -->
<div class="card border-0 shadow-sm">
  <?php if (!$topicos): ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-chat-square-dots fs-1 d-block mb-3 opacity-25"></i>
    <p>Nenhum tópico ainda. <a href="<?= url('/forum/novo?categoria=' . $categoria['id']) ?>">Seja o primeiro!</a></p>
  </div>
  <?php else: ?>
  <ul class="list-group list-group-flush">
    <?php foreach ($topicos as $t): ?>
    <li class="list-group-item p-3">
      <div class="d-flex align-items-start gap-3">

        <!-- Ícone status -->
        <div class="mt-1 flex-shrink-0">
          <?php if ($t['resolvido']): ?>
          <span class="badge bg-success rounded-circle p-2" title="Resolvido"><i class="bi bi-check-lg"></i></span>
          <?php elseif ($t['fixado']): ?>
          <span class="badge bg-warning text-dark rounded-circle p-2" title="Fixado"><i class="bi bi-pin-fill"></i></span>
          <?php else: ?>
          <span class="badge bg-light text-secondary rounded-circle p-2"><i class="bi bi-chat-dots"></i></span>
          <?php endif; ?>
        </div>

        <!-- Conteúdo -->
        <div class="flex-grow-1 min-w-0">
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($t['fixado']): ?>
            <span class="badge bg-warning text-dark" style="font-size:.68rem">Fixado</span>
            <?php endif; ?>
            <?php if ($t['resolvido']): ?>
            <span class="badge bg-success" style="font-size:.68rem">Resolvido</span>
            <?php endif; ?>
            <a href="<?= url('/forum/topico/' . $t['id']) ?>" class="fw-semibold text-decoration-none text-dark">
              <?= e($t['titulo']) ?>
            </a>
          </div>
          <div class="small text-muted mt-1">
            <span class="me-3"><i class="bi bi-person me-1"></i><?= e($t['autor_nome']) ?> — <?= e($t['empresa_nome']) ?></span>
            <?php if ($t['marca'] || $t['modelo']): ?>
            <span class="me-3"><i class="bi bi-cpu me-1"></i><?= e(trim($t['marca'].' '.$t['modelo'])) ?></span>
            <?php endif; ?>
            <span><i class="bi bi-clock me-1"></i><?= date_br($t['criado_em']) ?></span>
          </div>
        </div>

        <!-- Stats -->
        <div class="d-none d-md-flex gap-3 text-center flex-shrink-0">
          <div>
            <div class="fw-bold"><?= $t['total_respostas'] ?></div>
            <div class="text-muted" style="font-size:.7rem">respostas</div>
          </div>
          <div>
            <div class="fw-bold"><?= $t['visualizacoes'] ?></div>
            <div class="text-muted" style="font-size:.7rem">views</div>
          </div>
          <div>
            <div class="fw-bold"><?= $t['total_curtidas'] ?></div>
            <div class="text-muted" style="font-size:.7rem">curtidas</div>
          </div>
        </div>

      </div>
    </li>
    <?php endforeach; ?>
  </ul>

  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer bg-white d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $paginator['total'] ?> tópico(s)</small>
    <?= pagination($paginator, url('/forum/categoria/' . $categoria['id'])) ?>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div><!-- /col-lg-9 -->
</div><!-- /row -->
