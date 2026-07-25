<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="mb-0 fw-bold">Equipe de Técnicos</h5>
    <div class="text-muted small"><?= count($tecnicos) ?> técnico(s) cadastrado(s)</div>
  </div>
  <a href="<?= url('/tecnicos/novo') ?>" target="_top" class="btn btn-primary">
    <i class="bi bi-person-plus-fill me-1"></i> Novo Técnico
  </a>
</div>

<?php if (!$tecnicos): ?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5 text-muted">
    <i class="bi bi-person-gear fs-1 d-block mb-3 opacity-30"></i>
    <h5>Nenhum técnico cadastrado</h5>
    <p>Cadastre o primeiro técnico para começar a atribuir ordens de serviço.</p>
    <a href="<?= url('/tecnicos/novo') ?>" target="_top" class="btn btn-primary mt-2">
      <i class="bi bi-plus-lg me-1"></i> Cadastrar técnico
    </a>
  </div>
</div>
<?php else: ?>

<div class="row g-3">
  <?php foreach ($tecnicos as $t): ?>
  <div class="col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <!-- Cabeçalho do card -->
        <div class="d-flex align-items-center gap-3 mb-3">
          <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
               style="width:52px;height:52px;font-size:1.1rem;background:<?= $t['ativo'] ? '#0d6efd' : '#adb5bd' ?>">
            <?= avatar_iniciais($t['nome']) ?>
          </div>
          <div class="flex-grow-1 min-w-0">
            <div class="fw-bold text-truncate"><?= e($t['nome']) ?></div>
            <div class="small text-muted text-truncate"><?= e($t['email']) ?></div>
            <?php if ($t['telefone']): ?>
            <div class="small text-muted"><?= e($t['telefone']) ?></div>
            <?php endif; ?>
          </div>
          <span class="badge bg-<?= $t['ativo'] ? 'success' : 'secondary' ?> flex-shrink-0">
            <?= $t['ativo'] ? 'Ativo' : 'Inativo' ?>
          </span>
        </div>

        <!-- Métricas -->
        <div class="row g-2 mb-3">
          <div class="col-4 text-center">
            <div class="fw-bold fs-5 text-primary"><?= $t['total_os'] ?? 0 ?></div>
            <div class="small text-muted" style="font-size:.72rem">Total OS</div>
          </div>
          <div class="col-4 text-center">
            <div class="fw-bold fs-5 text-success"><?= $t['os_concluidas'] ?? 0 ?></div>
            <div class="small text-muted" style="font-size:.72rem">Concluídas</div>
          </div>
          <div class="col-4 text-center">
            <div class="fw-bold text-info" style="font-size:.9rem"><?= money($t['total_faturado'] ?? 0) ?></div>
            <div class="small text-muted" style="font-size:.72rem">Faturado</div>
          </div>
        </div>

        <?php if ($t['ultima_os']): ?>
        <div class="small text-muted mb-3">
          <i class="bi bi-clock me-1"></i>Última OS: <?= date_br($t['ultima_os'], true) ?>
        </div>
        <?php endif; ?>
      </div>

      <div class="card-footer bg-white d-flex gap-2">
        <a href="<?= url('/tecnicos/' . $t['id']) ?>" target="_top" class="btn btn-outline-primary btn-sm flex-fill">
          <i class="bi bi-eye me-1"></i>Ver
        </a>
        <a href="<?= url('/tecnicos/' . $t['id'] . '/editar') ?>" target="_top" class="btn btn-outline-secondary btn-sm flex-fill">
          <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <?php if (!$t['total_os']): ?>
        <a href="<?= url('/tecnicos/' . $t['id']) ?>"
           class="btn btn-outline-danger btn-sm"
           data-method="DELETE"
           data-href="<?= url('/tecnicos/' . $t['id']) ?>"
           data-confirm="Excluir o técnico <?= e($t['nome']) ?>?">
          <i class="bi bi-trash"></i>
        </a>
        <?php else: ?>
        <button class="btn btn-outline-danger btn-sm" disabled title="Possui OS vinculadas">
          <i class="bi bi-trash"></i>
        </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<?php endif; ?>

<?php if (!empty($outrosAtendemOs)): ?>
<div class="mt-4">
  <h6 class="fw-bold mb-1">Outros usuários que também atendem OS</h6>
  <div class="text-muted small mb-3">Não são cadastrados como técnico, mas têm o toggle "Atende OS?" ligado — edite em <a href="<?= url('/usuarios') ?>" target="_top">Usuários</a>.</div>
  <div class="row g-3">
    <?php
      $labelsPerfil = ['superadmin'=>'Superadmin','admin'=>'Administrador','gerente'=>'Gerente','recepcionista'=>'Recepcionista','financeiro'=>'Financeiro','rh'=>'RH'];
    ?>
    <?php foreach ($outrosAtendemOs as $t): ?>
    <div class="col-md-6 col-lg-4">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <div class="d-flex align-items-center gap-3 mb-3">
            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                 style="width:52px;height:52px;font-size:1.1rem;background:#6c757d">
              <?= avatar_iniciais($t['nome']) ?>
            </div>
            <div class="flex-grow-1 min-w-0">
              <div class="fw-bold text-truncate"><?= e($t['nome']) ?></div>
              <div class="small text-muted text-truncate"><?= e($t['email']) ?></div>
              <?php if ($t['telefone']): ?>
              <div class="small text-muted"><?= e($t['telefone']) ?></div>
              <?php endif; ?>
            </div>
            <span class="badge bg-secondary flex-shrink-0"><?= e($labelsPerfil[$t['perfil']] ?? ucfirst($t['perfil'])) ?></span>
          </div>

          <div class="row g-2 mb-2">
            <div class="col-4 text-center">
              <div class="fw-bold fs-5 text-primary"><?= $t['total_os'] ?? 0 ?></div>
              <div class="small text-muted" style="font-size:.72rem">Total OS</div>
            </div>
            <div class="col-4 text-center">
              <div class="fw-bold fs-5 text-success"><?= $t['os_concluidas'] ?? 0 ?></div>
              <div class="small text-muted" style="font-size:.72rem">Concluídas</div>
            </div>
            <div class="col-4 text-center">
              <div class="fw-bold text-info" style="font-size:.9rem"><?= money($t['total_faturado'] ?? 0) ?></div>
              <div class="small text-muted" style="font-size:.72rem">Faturado</div>
            </div>
          </div>
        </div>
        <div class="card-footer bg-white">
          <a href="<?= url('/usuarios/' . $t['id'] . '/editar') ?>" target="_top" class="btn btn-outline-secondary btn-sm w-100">
            <i class="bi bi-pencil me-1"></i>Editar em Usuários
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
