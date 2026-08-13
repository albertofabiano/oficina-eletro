<?php $podeEditar = \App\Core\Auth::can('estoque', 'editar'); ?>

<div class="row g-3">
  <!-- Novo serviço -->
  <?php if ($podeEditar): ?>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-plus-circle me-1"></i>Novo serviço</div>
      <div class="card-body">
        <form method="POST" action="<?= url('/servicos') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Descrição *</label>
            <input type="text" name="descricao" class="form-control" required maxlength="150" placeholder="Ex: Troca de tela, Diagnóstico...">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Valor padrão</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_padrao" class="form-control" placeholder="0,00">
            </div>
            <div class="form-text">Sugerido ao escolher este serviço na OS — pode ser ajustado na hora.</div>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Cadastrar serviço</button>
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
            <tr><th>Descrição</th><th class="text-end">Valor padrão</th><?php if ($podeEditar): ?><th class="text-end">Ações</th><?php endif; ?></tr>
          </thead>
          <tbody>
            <?php foreach ($servicos as $s): ?>
            <tr>
              <td class="fw-semibold"><?= e($s['descricao']) ?></td>
              <td class="text-end"><?= money($s['valor_padrao']) ?></td>
              <?php if ($podeEditar): ?>
              <td class="text-end text-nowrap">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit"
                        data-id="<?= $s['id'] ?>" data-descricao="<?= e($s['descricao']) ?>" data-valor="<?= e($s['valor_padrao']) ?>"><i class="bi bi-pencil"></i></button>
                <form method="POST" action="<?= url('/servicos/' . $s['id'] . '/excluir') ?>" class="d-inline"
                      onsubmit="return confirm('Remover o serviço <?= e(addslashes($s['descricao'])) ?> do catálogo?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (!$servicos): ?>
            <tr><td colspan="<?= $podeEditar ? '3' : '2' ?>" class="text-center text-muted py-5">
              <i class="bi bi-tools fs-3 d-block mb-2"></i>Nenhum serviço cadastrado ainda.<?php if ($podeEditar): ?> Cadastre o primeiro ao lado.<?php endif; ?>
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
<div class="modal fade" id="modalEditServico" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formEditServico">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar serviço</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Descrição *</label>
          <input type="text" name="descricao" id="editDescricao" class="form-control" required maxlength="150">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Valor padrão</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor_padrao" id="editValor" class="form-control" placeholder="0,00">
          </div>
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
    var f = document.getElementById('formEditServico');
    f.action = '<?= url('/servicos') ?>/' + b.dataset.id;
    document.getElementById('editDescricao').value = b.dataset.descricao;
    document.getElementById('editValor').value = b.dataset.valor;
    new bootstrap.Modal(document.getElementById('modalEditServico')).show();
  });
});
</script>
<?php endif; ?>
