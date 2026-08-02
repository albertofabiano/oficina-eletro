<?php require __DIR__ . '/menu.php'; ?>

<style>
.cat-card { border-radius:14px; border:1px solid #e2e8f0; transition:.2s; }
.cat-card:hover { transform:translateY(-2px); box-shadow:0 8px 24px rgba(0,0,0,.1); }
.cat-icon { width:52px;height:52px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:1.4rem;flex-shrink:0; }
</style>

<div class="row g-4">

<!-- Sidebar -->
<?php require __DIR__ . '/sidebar.php'; ?>

<!-- Conteúdo principal -->
<div class="col-lg-9">

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-chat-dots me-2 text-primary"></i>Fórum de Técnicos</h5>
    <small class="text-muted">Compartilhe conhecimento, dicas e arquivos com outros técnicos</small>
  </div>
  <a href="<?= url('/forum/novo') ?>" class="btn btn-primary fw-semibold">
    <i class="bi bi-plus-lg me-1"></i>Novo Tópico
  </a>
</div>

<div class="row g-3">
  <?php foreach ($categorias as $cat): ?>
  <div class="col-12">
    <a href="<?= url('/forum/categoria/' . $cat['id']) ?>" class="text-decoration-none">
      <div class="cat-card card border-0 shadow-sm p-3">
        <div class="d-flex align-items-center gap-3">
          <div class="cat-icon" style="background:<?= e($cat['cor']) ?>22;color:<?= e($cat['cor']) ?>">
            <i class="bi <?= e($cat['icone']) ?>"></i>
          </div>
          <div class="flex-grow-1 min-w-0">
            <div class="d-flex align-items-center gap-2 flex-wrap">
              <span class="fw-bold fs-6" style="color:<?= e($cat['cor']) ?>"><?= e($cat['nome']) ?></span>
            </div>
            <div class="text-muted small"><?= e($cat['descricao']) ?></div>
            <?php if ($cat['ultimo_topico']): ?>
            <div class="small text-muted mt-1">
              <i class="bi bi-clock me-1"></i>Último: <?= e(mb_substr($cat['ultimo_topico'], 0, 60)) ?>
              <span class="ms-2"><?= date_br($cat['ultimo_em']) ?></span>
            </div>
            <?php endif; ?>
          </div>
          <div class="text-center d-none d-md-block px-3" style="min-width:80px">
            <div class="fw-bold fs-5"><?= $cat['total_topicos'] ?></div>
            <div class="text-muted" style="font-size:.72rem">tópicos</div>
          </div>
          <div class="text-center d-none d-md-block px-3" style="min-width:80px">
            <div class="fw-bold fs-5"><?= $cat['total_respostas'] ?></div>
            <div class="text-muted" style="font-size:.72rem">respostas</div>
          </div>
          <i class="bi bi-chevron-right text-muted"></i>
        </div>
      </div>
    </a>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!$categorias): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-chat-dots fs-1 d-block mb-3 opacity-25"></i>
  <p>Nenhuma categoria disponível.</p>
</div>
<?php endif; ?>

</div><!-- /col-lg-9 -->
</div><!-- /row -->
