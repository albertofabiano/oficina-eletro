<div class="row g-3 mb-4">
  <!-- Cards de resumo -->
  <div class="col-6 col-md-3">
    <div class="card stat-card bg-primary text-white p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="small opacity-75">OS Abertas</div>
          <div class="fs-3 fw-bold"><?= $resumo['os_abertas'] ?? 0 ?></div>
        </div>
        <i class="bi bi-clipboard2-pulse fs-2 opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card bg-success text-white p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="small opacity-75">Concluídas (mês)</div>
          <div class="fs-3 fw-bold"><?= $resumo['os_concluidas'] ?? 0 ?></div>
        </div>
        <i class="bi bi-check-circle fs-2 opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card bg-danger text-white p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="small opacity-75">Atrasadas</div>
          <div class="fs-3 fw-bold"><?= $resumo['os_atrasadas'] ?? 0 ?></div>
        </div>
        <i class="bi bi-exclamation-circle fs-2 opacity-50"></i>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card stat-card bg-info text-white p-3">
      <div class="d-flex justify-content-between align-items-center">
        <div>
          <div class="small opacity-75">Faturamento (mês)</div>
          <div class="fs-4 fw-bold"><?= money($resumo['faturamento_mes'] ?? 0) ?></div>
        </div>
        <i class="bi bi-currency-dollar fs-2 opacity-50"></i>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Gráfico faturamento -->
  <div class="col-md-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">Faturamento — últimos 6 meses</div>
      <div class="card-body">
        <canvas id="chartFat" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- Top serviços -->
  <div class="col-md-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white border-0 fw-semibold">Top Serviços</div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($topServicos as $s): ?>
          <li class="list-group-item d-flex justify-content-between">
            <span><?= e($s['descricao']) ?></span>
            <div class="text-end">
              <span class="badge bg-primary"><?= $s['vezes'] ?>x</span>
              <div class="small text-muted"><?= money($s['receita']) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
          <?php if (!$topServicos): ?><li class="list-group-item text-muted small">Nenhum serviço ainda.</li><?php endif; ?>
        </ul>
      </div>
    </div>
  </div>
</div>

<!-- Últimas OS -->
<div class="card border-0 shadow-sm mt-3">
  <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
    <span class="fw-semibold">Últimas Ordens de Serviço</span>
    <a href="<?= url('/os/nova') ?>" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> Nova OS</a>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Nº</th><th>Cliente</th><th>Equipamento</th><th>Status</th><th>Entrada</th><th>Valor</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($ultimasOS as $os): ?>
        <tr>
          <td><strong>OS: <?= e($os['numero']) ?></strong></td>
          <td><?= e($os['cliente_nome']) ?></td>
          <td><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?><br><small class="text-muted"><?= e($os['equip_tipo'] ?? '') ?></small></td>
          <td><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '') ?></td>
          <td><?= date_br($os['data_entrada'], true) ?></td>
          <td><?= money($os['valor_total']) ?></td>
          <td><a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$ultimasOS): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Nenhuma OS cadastrada ainda.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Agenda hoje -->
<?php if ($agenda): ?>
<div class="card border-0 shadow-sm mt-3">
  <div class="card-header bg-white border-0 fw-semibold">Agenda de Hoje</div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      <?php foreach ($agenda as $ev): ?>
      <li class="list-group-item d-flex gap-3 align-items-center">
        <span class="badge bg-primary"><?= date('H:i', strtotime($ev['data_inicio'])) ?></span>
        <div>
          <div class="fw-semibold"><?= e($ev['titulo']) ?></div>
          <?php if ($ev['cliente_nome']): ?><div class="small text-muted"><?= e($ev['cliente_nome']) ?></div><?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<?php endif; ?>

<script>
const fatData = <?= json_encode($faturamento) ?>;
new Chart(document.getElementById('chartFat'), {
  type: 'bar',
  data: {
    labels: fatData.map(d => d.mes),
    datasets: [{ label: 'Faturamento R$', data: fatData.map(d => d.total), backgroundColor: '#0d6efd99', borderColor: '#0d6efd', borderWidth: 1 }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>

