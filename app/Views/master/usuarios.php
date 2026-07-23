<div class="d-flex justify-content-between align-items-center mb-4">
  <form class="d-flex gap-2" method="GET">
    <input type="search" name="busca" class="form-control" placeholder="Nome ou e-mail..." value="<?= e($busca) ?>" style="width:280px">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
  </form>
  <span class="text-muted small"><?= count($usuarios) ?> usuário(s)</span>
</div>

<div class="ms-card">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Nome</th><th>E-mail</th><th>Empresa</th><th>Perfil</th><th>Último login</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td class="text-white"><?= e($u['nome']) ?></td>
          <td class="text-muted small"><?= e($u['email']) ?></td>
          <td>
            <a href="<?= url('/master/empresas/'.$u['empresa_id']) ?>" class="text-danger small text-decoration-none">
              <?= e($u['empresa_nome']) ?>
            </a>
          </td>
          <td><span class="badge bg-secondary" style="font-size:.72rem"><?= ucfirst($u['perfil']) ?></span></td>
          <td class="text-muted small"><?= date_br($u['ultimo_login'], true) ?: 'Nunca' ?></td>
          <td><span class="badge bg-<?= $u['ativo']?'success':'secondary' ?>"><?= $u['ativo']?'Ativo':'Inativo' ?></span></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$usuarios): ?>
        <tr><td colspan="6" class="text-center text-muted py-4">Nenhum usuário encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
