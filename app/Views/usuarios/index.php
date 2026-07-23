<div class="d-flex justify-content-end mb-3">
  <a href="<?= url('/usuarios/novo') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Novo Usuário</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr><th>Nome</th><th>E-mail</th><th>Perfil</th><th>Último login</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($usuarios as $u): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:36px;height:36px;font-size:.8rem;flex-shrink:0">
                <?= avatar_iniciais($u['nome']) ?>
              </div>
              <div>
                <div class="fw-semibold"><?= e($u['nome']) ?></div>
                <div class="text-muted small"><?= e($u['telefone'] ?? '') ?></div>
              </div>
            </div>
          </td>
          <td><?= e($u['email']) ?></td>
          <td>
            <?php $cores=['superadmin'=>'danger','admin'=>'primary','gerente'=>'info','recepcionista'=>'success','tecnico'=>'warning','financeiro'=>'secondary']; ?>
            <span class="badge bg-<?= $cores[$u['perfil']] ?? 'secondary' ?>"><?= ucfirst($u['perfil']) ?></span>
          </td>
          <td><?= date_br($u['ultimo_login'], true) ?: 'Nunca' ?></td>
          <td><span class="badge bg-<?= $u['ativo'] ? 'success' : 'secondary' ?>"><?= $u['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td class="text-end">
            <a href="<?= url('/usuarios/' . $u['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <?php if ($u['id'] !== (int) ($_SESSION['usuario_id'] ?? 0)): ?>
            <a href="#" class="btn btn-sm btn-outline-danger" data-method="DELETE"
               data-href="<?= url('/usuarios/' . $u['id']) ?>"
               data-confirm="Excluir usuário <?= e($u['nome']) ?>?"><i class="bi bi-trash"></i></a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
