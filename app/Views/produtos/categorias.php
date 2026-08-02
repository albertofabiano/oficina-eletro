<?php $podeEditar = \App\Core\Auth::can('estoque', 'editar'); ?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <a href="<?= url('/produtos') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left"></i> Voltar ao Estoque</a>
</div>

<div class="row g-3">
  <!-- Nova categoria -->
  <?php if ($podeEditar): ?>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-plus-circle me-1"></i>Nova categoria</div>
      <div class="card-body">
        <form method="POST" action="<?= url('/produtos/categorias') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Nome *</label>
            <input type="text" name="nome" class="form-control" required maxlength="80" placeholder="Ex: Peças, Acessórios, Cabos...">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Categoria pai (opcional)</label>
            <select name="pai_id" class="form-select">
              <option value="">— Nenhuma (categoria principal)</option>
              <?php foreach ($categorias as $c): if ($c['pai_id']) continue; ?>
              <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="form-text">Use para criar subcategorias.</div>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Criar categoria</button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Lista -->
  <div class="col-lg-<?= $podeEditar ? '8' : '12' ?>">
    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr><th>Categoria</th><th>Categoria pai</th><th class="text-center">Produtos</th><?php if ($podeEditar): ?><th class="text-end">Ações</th><?php endif; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($categorias as $c): ?>
            <tr>
              <td class="fw-semibold">
                <?php if ($c['pai_id']): ?><i class="bi bi-arrow-return-right text-muted me-1"></i><?php endif; ?>
                <?= e($c['nome']) ?>
              </td>
              <td class="text-muted"><?= $c['pai_nome'] ? e($c['pai_nome']) : '—' ?></td>
              <td class="text-center"><span class="badge bg-secondary"><?= (int) $c['total_produtos'] ?></span></td>
              <?php if ($podeEditar): ?>
              <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit"
                        data-id="<?= $c['id'] ?>" data-nome="<?= e($c['nome']) ?>" data-pai="<?= (int) $c['pai_id'] ?>"><i class="bi bi-pencil"></i></button>
                <form method="POST" action="<?= url('/produtos/categorias/' . $c['id'] . '/excluir') ?>" class="d-inline"
                      onsubmit="return confirm('Excluir a categoria <?= e(addslashes($c['nome'])) ?>?<?= $c['total_produtos'] > 0 ? ' Os ' . (int) $c['total_produtos'] . ' produto(s) ficarão sem categoria.' : '' ?>');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (!$categorias): ?>
            <tr><td colspan="<?= $podeEditar ? '4' : '3' ?>" class="text-center text-muted py-5">
              <i class="bi bi-tags fs-3 d-block mb-2"></i>Nenhuma categoria ainda.<?php if ($podeEditar): ?> Crie a primeira ao lado.<?php endif; ?>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php if ($podeEditar): ?>
<!-- Modal editar -->
<div class="modal fade" id="modalEditCat" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formEditCat">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar categoria</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Nome *</label>
          <input type="text" name="nome" id="editNome" class="form-control" required maxlength="80">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Categoria pai (opcional)</label>
          <select name="pai_id" id="editPai" class="form-select">
            <option value="">— Nenhuma (categoria principal)</option>
            <?php foreach ($categorias as $c): if ($c['pai_id']) continue; ?>
            <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('.btn-edit').forEach(function (b) {
  b.addEventListener('click', function () {
    var f = document.getElementById('formEditCat');
    f.action = '<?= url('/produtos/categorias') ?>/' + b.dataset.id;
    document.getElementById('editNome').value = b.dataset.nome;
    var pai = document.getElementById('editPai');
    pai.value = b.dataset.pai && b.dataset.pai !== '0' ? b.dataset.pai : '';
    // não permitir escolher a si mesma como pai
    Array.prototype.forEach.call(pai.options, function (o) { o.disabled = (o.value === b.dataset.id); });
    new bootstrap.Modal(document.getElementById('modalEditCat')).show();
  });
});
</script>
<?php endif; ?>
