<div class="row g-3">
  <div class="col-md-7">
    <div class="ms-card">
      <div class="card-header px-4 py-3 fw-semibold text-white">Admins Master</div>
      <div class="table-responsive">
        <table class="table mb-0 align-middle">
          <thead><tr><th>Nome</th><th>E-mail</th><th>Último login</th><th>Status</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($admins as $a): ?>
            <tr>
              <td class="text-white fw-semibold"><?= e($a['nome']) ?></td>
              <td class="text-muted small"><?= e($a['email']) ?></td>
              <td class="text-muted small"><?= date_br($a['ultimo_login'], true) ?: 'Nunca' ?></td>
              <td><span class="badge bg-<?= $a['ativo']?'success':'secondary' ?>"><?= $a['ativo']?'Ativo':'Inativo' ?></span></td>
              <td class="text-end">
                <button class="btn btn-sm btn-outline-secondary"
                  onclick="editarAdmin(<?= $a['id'] ?>,'<?= addslashes(e($a['nome'])) ?>','<?= e($a['email']) ?>')">
                  <i class="bi bi-pencil"></i>
                </button>
                <?php if ($a['id'] != ($_SESSION['master_id']??0)): ?>
                <a href="<?= url('/master/admins/'.$a['id']) ?>" class="btn btn-sm btn-outline-danger"
                   data-method="DELETE" data-confirm="Excluir <?= e($a['nome']) ?>?">
                  <i class="bi bi-trash"></i>
                </a>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-md-5">
    <div class="ms-card p-4">
      <div class="fw-semibold text-white mb-3" id="formAdminTitulo">Novo Admin Master</div>
      <form method="POST" action="<?= url('/master/admins') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="id" id="adminId">
        <div class="mb-3">
          <label class="form-label text-muted small">Nome *</label>
          <input type="text" name="nome" id="adminNome" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label text-muted small">E-mail *</label>
          <input type="email" name="email" id="adminEmail" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label text-muted small" id="lblSenha">Senha *</label>
          <input type="password" name="senha" id="adminSenha" class="form-control" minlength="8" placeholder="Mínimo 8 caracteres">
          <div class="form-text text-muted small" id="senhaHint" style="display:none">Deixe vazio para manter a atual</div>
        </div>
        <div class="mb-4" id="statusDiv" style="display:none">
          <label class="form-label text-muted small">Status</label>
          <select name="ativo" class="form-select">
            <option value="1">Ativo</option>
            <option value="0">Inativo</option>
          </select>
        </div>
        <div class="d-flex gap-2">
          <button class="btn btn-danger flex-fill fw-semibold" id="btnSalvarAdmin">Criar Admin</button>
          <button type="button" class="btn btn-outline-secondary d-none" id="btnCancelarAdmin" onclick="limparFormAdmin()">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function editarAdmin(id, nome, email) {
  document.getElementById('adminId').value   = id;
  document.getElementById('adminNome').value = nome;
  document.getElementById('adminEmail').value= email;
  document.getElementById('adminEmail').readOnly = true;
  document.getElementById('formAdminTitulo').textContent = 'Editar Admin';
  document.getElementById('btnSalvarAdmin').textContent  = 'Salvar';
  document.getElementById('lblSenha').textContent = 'Nova senha';
  document.getElementById('adminSenha').required = false;
  document.getElementById('senhaHint').style.display = '';
  document.getElementById('statusDiv').style.display  = '';
  document.getElementById('btnCancelarAdmin').classList.remove('d-none');
}
function limparFormAdmin() {
  document.getElementById('adminId').value   = '';
  document.getElementById('adminNome').value = '';
  document.getElementById('adminEmail').value= '';
  document.getElementById('adminEmail').readOnly = false;
  document.getElementById('adminSenha').value= '';
  document.getElementById('adminSenha').required = true;
  document.getElementById('formAdminTitulo').textContent = 'Novo Admin Master';
  document.getElementById('btnSalvarAdmin').textContent  = 'Criar Admin';
  document.getElementById('lblSenha').textContent = 'Senha *';
  document.getElementById('senhaHint').style.display = 'none';
  document.getElementById('statusDiv').style.display  = 'none';
  document.getElementById('btnCancelarAdmin').classList.add('d-none');
}
</script>
