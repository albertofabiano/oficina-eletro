<div class="d-flex justify-content-between align-items-center mb-4">
  <form class="d-flex gap-2" method="GET">
    <input type="search" name="busca" class="form-control" placeholder="Nome, e-mail..." value="<?= e($busca) ?>" style="width:280px">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
  </form>
  <span class="text-muted small"><?= count($empresas) ?> empresa(s)</span>
</div>

<div class="ms-card">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Empresa</th><th>Plano</th><th>Usuários</th><th>OS</th><th>Trial até</th><th>Pago até</th><th>Status</th><th>Cadastro</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($empresas as $e): ?>
        <tr>
          <td>
            <div class="fw-semibold text-white"><?= e($e['nome_fantasia']) ?></div>
            <div class="small text-muted"><?= e($e['email']) ?> · <?= e($e['telefone']) ?></div>
          </td>
          <td>
            <?php if (!empty($e['plano_atual'])): ?>
            <span class="badge bg-success"><?= e(ucfirst($e['plano_atual'])) ?></span>
            <?php else: ?>
            <span class="badge bg-secondary">Sem plano pago</span>
            <?php endif; ?>
          </td>
          <td class="text-center"><?= $e['qtd_usuarios'] ?></td>
          <td class="text-center"><?= number_format($e['qtd_os']) ?></td>
          <td>
            <?php if ($e['trial_ate']): ?>
            <?php $dias = (int)ceil((strtotime($e['trial_ate'])-time())/86400); ?>
            <span class="badge bg-<?= $dias>7?'success':($dias>0?'warning':'danger') ?>">
              <?= $dias>0 ? date_br($e['trial_ate']).' ('.$dias.'d)' : 'Expirado' ?>
            </span>
            <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
          </td>
          <td>
            <?php if (!empty($e['licenca_ate'])): ?>
            <?php $diasPg = (int)ceil((strtotime($e['licenca_ate'])-time())/86400); ?>
            <span class="badge bg-<?= $diasPg>7?'success':($diasPg>0?'warning':'danger') ?>">
              <?= $diasPg>0 ? date_br($e['licenca_ate']).' ('.$diasPg.'d)' : 'Expirado' ?>
            </span>
            <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
          </td>
          <td>
            <span class="badge bg-<?= $e['ativo']?'success':'danger' ?>">
              <?= $e['ativo']?'Ativa':'Inativa' ?>
            </span>
          </td>
          <td class="text-muted small"><?= date_br($e['criado_em']) ?></td>
          <td>
            <a href="<?= url('/master/empresas/'.$e['id']) ?>" class="btn btn-sm btn-outline-danger">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$empresas): ?>
        <tr><td colspan="9" class="text-center text-muted py-4">Nenhuma empresa encontrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
