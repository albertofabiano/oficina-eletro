<div class="container-fluid" style="max-width:1000px">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="mb-1"><i class="bi bi-journal-text me-2 text-danger"></i>Base de Conhecimento</h4>
      <p class="text-muted small mb-0"><?= count($artigos) ?> artigos · fonte do bot de suporte por WhatsApp e da central de ajuda. Escreva na linguagem do cliente.</p>
    </div>
    <button class="btn btn-danger btn-sm" data-bs-toggle="collapse" data-bs-target="#novoArt"><i class="bi bi-plus-lg me-1"></i>Novo artigo</button>
  </div>

  <?php $flashOk = flash('success'); $flashErr = flash('error'); ?>
  <?php if ($flashOk): ?><div class="alert alert-success py-2"><?= e($flashOk) ?></div><?php endif; ?>
  <?php if ($flashErr): ?><div class="alert alert-danger py-2"><?= e($flashErr) ?></div><?php endif; ?>

  <!-- Novo artigo -->
  <div class="collapse mb-3" id="novoArt">
    <form method="POST" action="<?= url('/master/kb/novo') ?>" class="card border-0 shadow-sm">
      <div class="card-body">
        <?= csrf_field() ?>
        <div class="row g-2">
          <div class="col-md-8"><input name="titulo" class="form-control form-control-sm" placeholder="Título do artigo" required></div>
          <div class="col-md-4"><input name="categoria" class="form-control form-control-sm" placeholder="Categoria" required></div>
          <div class="col-12"><input name="palavras_chave" class="form-control form-control-sm" placeholder="Palavras-chave (separadas por vírgula) — ajudam o bot a achar"></div>
          <div class="col-12"><textarea name="conteudo" rows="4" class="form-control form-control-sm" placeholder="Conteúdo (resposta clara, na linguagem do cliente)" required></textarea></div>
        </div>
        <div class="text-end mt-2"><button class="btn btn-danger btn-sm"><i class="bi bi-check-lg me-1"></i>Criar</button></div>
      </div>
    </form>
  </div>

  <?php $catAtual = null; foreach ($artigos as $a): ?>
    <?php if ($a['categoria'] !== $catAtual): $catAtual = $a['categoria']; ?>
    <div class="fw-bold text-uppercase text-muted small mt-4 mb-2" style="letter-spacing:.05em"><?= e($catAtual) ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm mb-2">
      <div class="card-header bg-white d-flex align-items-center justify-content-between py-2" role="button" data-bs-toggle="collapse" data-bs-target="#art<?= $a['id'] ?>">
        <span class="fw-semibold small"><?= $a['ativo'] ? '' : '<span class="badge bg-secondary me-1">inativo</span>' ?><?= e($a['titulo']) ?></span>
        <i class="bi bi-pencil text-muted"></i>
      </div>
      <div class="collapse" id="art<?= $a['id'] ?>">
        <div class="card-body">
          <form method="POST" action="<?= url('/master/kb/' . $a['id']) ?>">
            <?= csrf_field() ?>
            <div class="row g-2">
              <div class="col-md-8"><label class="form-label small mb-0">Título</label><input name="titulo" class="form-control form-control-sm" value="<?= e($a['titulo']) ?>" required></div>
              <div class="col-md-4"><label class="form-label small mb-0">Categoria</label><input name="categoria" class="form-control form-control-sm" value="<?= e($a['categoria']) ?>" required></div>
              <div class="col-12"><label class="form-label small mb-0">Palavras-chave</label><input name="palavras_chave" class="form-control form-control-sm" value="<?= e($a['palavras_chave'] ?? '') ?>"></div>
              <div class="col-12"><label class="form-label small mb-0">Conteúdo</label><textarea name="conteudo" rows="6" class="form-control form-control-sm" required><?= e($a['conteudo']) ?></textarea></div>
            </div>
            <div class="d-flex align-items-center justify-content-between mt-2">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="ativo" value="1" id="at<?= $a['id'] ?>" <?= $a['ativo'] ? 'checked' : '' ?>>
                <label class="form-check-label small" for="at<?= $a['id'] ?>">Ativo (o bot usa)</label>
              </div>
              <div class="d-flex gap-2">
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="if(confirm('Excluir este artigo?')){document.getElementById('del<?= $a['id'] ?>').submit();}"><i class="bi bi-trash3"></i></button>
                <button class="btn btn-primary btn-sm"><i class="bi bi-check-lg me-1"></i>Salvar</button>
              </div>
            </div>
          </form>
          <form method="POST" action="<?= url('/master/kb/' . $a['id'] . '/excluir') ?>" id="del<?= $a['id'] ?>" class="d-none"><?= csrf_field() ?></form>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>
