<div class="row g-3">

  <!-- Perfil lateral -->
  <div class="col-md-4">

    <!-- Card perfil -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body text-center py-4">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center fw-bold text-white mb-3"
             style="width:72px;height:72px;font-size:1.6rem;background:<?= $tecnico['ativo'] ? '#0d6efd' : '#adb5bd' ?>">
          <?= avatar_iniciais($tecnico['nome']) ?>
        </div>
        <h5 class="fw-bold mb-1"><?= e($tecnico['nome']) ?></h5>
        <div class="text-muted small mb-2"><?= e($tecnico['email']) ?></div>
        <?php if ($tecnico['telefone']): ?>
        <div class="small mb-2">
          <i class="bi bi-telephone text-muted me-1"></i><?= e($tecnico['telefone']) ?>
          <a href="https://wa.me/55<?= only_numbers($tecnico['telefone']) ?>"
             target="_blank" class="ms-1 text-success">
            <i class="bi bi-whatsapp"></i>
          </a>
        </div>
        <?php endif; ?>
        <span class="badge bg-<?= $tecnico['ativo'] ? 'success' : 'secondary' ?> mb-3">
          <?= $tecnico['ativo'] ? 'Ativo' : 'Inativo' ?>
        </span>
        <div class="small text-muted">
          Cadastrado em <?= date_br($tecnico['criado_em']) ?>
        </div>
        <?php if ($tecnico['ultimo_login']): ?>
        <div class="small text-muted">Último acesso: <?= date_br($tecnico['ultimo_login'], true) ?></div>
        <?php endif; ?>
      </div>
      <div class="card-footer bg-white d-flex gap-2">
        <a href="<?= url('/tecnicos/' . $tecnico['id'] . '/editar') ?>"
           class="btn btn-outline-primary btn-sm flex-fill">
          <i class="bi bi-pencil me-1"></i>Editar
        </a>
        <a href="<?= url('/os/nova') ?>" class="btn btn-primary btn-sm flex-fill">
          <i class="bi bi-plus-lg me-1"></i>Nova OS
        </a>
      </div>
    </div>

    <!-- Métricas -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold small">Desempenho</div>
      <div class="card-body p-0">
        <div class="list-group list-group-flush">
          <div class="list-group-item d-flex justify-content-between">
            <span class="text-muted small">Total de OS</span>
            <span class="fw-bold"><?= $metricas['total_os'] ?? 0 ?></span>
          </div>
          <div class="list-group-item d-flex justify-content-between">
            <span class="text-muted small">Concluídas</span>
            <span class="fw-bold text-success"><?= $metricas['concluidas'] ?? 0 ?></span>
          </div>
          <div class="list-group-item d-flex justify-content-between">
            <span class="text-muted small">Em aberto</span>
            <span class="fw-bold text-warning"><?= $metricas['em_aberto'] ?? 0 ?></span>
          </div>
          <div class="list-group-item d-flex justify-content-between">
            <span class="text-muted small">Total faturado</span>
            <span class="fw-bold text-primary"><?= money($metricas['faturado'] ?? 0) ?></span>
          </div>
          <div class="list-group-item d-flex justify-content-between">
            <span class="text-muted small">Ticket médio</span>
            <span class="fw-bold"><?= money($metricas['ticket_medio'] ?? 0) ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Top serviços -->
    <?php if ($top): ?>
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold small">Serviços mais realizados</div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($top as $svc): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center small">
            <span><?= e($svc['descricao']) ?></span>
            <div class="text-end">
              <span class="badge bg-primary"><?= $svc['vezes'] ?>x</span>
              <div class="text-muted" style="font-size:.7rem"><?= money($svc['receita']) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
    <?php endif; ?>
  </div>

  <!-- Histórico OS -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold">
        Ordens de Serviço
        <span class="badge bg-secondary"><?= count($ordens) ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
          <thead class="table-light">
            <tr>
              <th>Nº</th>
              <th>Cliente</th>
              <th>Equipamento</th>
              <th>Status</th>
              <th>Valor</th>
              <th>Data</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($ordens as $os): ?>
            <tr>
              <td><a href="<?= url('/os/' . $os['id']) ?>" class="fw-semibold">OS: <?= e($os['numero']) ?></a></td>
              <td><?= e($os['cliente_nome']) ?></td>
              <td>
                <?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?>
                <div class="text-muted"><?= e($os['equip_tipo'] ?? '') ?></div>
              </td>
              <td><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '') ?></td>
              <td><?= money($os['valor_total']) ?></td>
              <td><?= date_br($os['criado_em']) ?></td>
              <td>
                <a href="<?= url('/os/' . $os['id']) ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$ordens): ?>
            <tr>
              <td colspan="7" class="text-center text-muted py-4">
                Nenhuma OS atribuída a este técnico.
              </td>
            </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
