<!-- Filtro de período -->
<form class="card border-0 shadow-sm p-3 mb-4" method="GET">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Data início</label>
      <input type="date" name="data_inicio" class="form-control" value="<?= e($ini) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Data fim</label>
      <input type="date" name="data_fim" class="form-control" value="<?= e($fim) ?>">
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100"><i class="bi bi-search"></i> Filtrar</button>
    </div>
    <div class="col-md-4 text-end">
      <?php foreach ([['mes','Este mês','?data_inicio='.date('Y-m-01').'&data_fim='.date('Y-m-t')],['ano','Este ano','?data_inicio='.date('Y-01-01').'&data_fim='.date('Y-12-31')]] as [$k,$l,$q]): ?>
      <a href="<?= url('/relatorios') . $q ?>" class="btn btn-outline-secondary btn-sm"><?= $l ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</form>

<!-- Cards resumo -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3 text-center">
      <div class="text-muted small">Total de OS</div>
      <div class="fs-2 fw-bold text-primary"><?= $resumo['total_os'] ?? 0 ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3 text-center">
      <div class="text-muted small">Faturamento</div>
      <div class="fs-4 fw-bold text-success"><?= money($resumo['total_faturado'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3 text-center">
      <div class="text-muted small">Ticket Médio</div>
      <div class="fs-4 fw-bold text-info"><?= money($resumo['ticket_medio'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3 text-center">
      <div class="text-muted small">Concluídas / Canceladas</div>
      <div class="fs-4 fw-bold">
        <span class="text-success"><?= $resumo['concluidas'] ?? 0 ?></span>
        <span class="text-muted fs-6"> / </span>
        <span class="text-danger"><?= $resumo['canceladas'] ?? 0 ?></span>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <!-- Faturamento por mês -->
  <div class="col-md-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Faturamento mensal (últimos 12 meses)</div>
      <div class="card-body"><canvas id="chartFat" height="130"></canvas></div>
    </div>
  </div>
  <!-- OS por status -->
  <div class="col-md-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">OS por Status</div>
      <div class="card-body">
        <?php foreach ($porStatus as $s): ?>
        <div class="d-flex align-items-center gap-2 mb-2">
          <div class="rounded" style="width:12px;height:12px;background:<?= e($s['cor']) ?>;flex-shrink:0"></div>
          <div class="flex-grow-1 small"><?= e($s['nome']) ?></div>
          <span class="badge" style="background:<?= e($s['cor']) ?>"><?= $s['total'] ?></span>
          <span class="text-muted small"><?= money($s['valor']) ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <!-- Top serviços -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Top 10 Serviços</div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
          <thead class="table-light"><tr><th>Serviço</th><th>Vezes</th><th>Receita</th></tr></thead>
          <tbody>
            <?php foreach ($topServicos as $s): ?>
            <tr>
              <td><?= e($s['descricao']) ?></td>
              <td><span class="badge bg-primary"><?= $s['vezes'] ?>x</span></td>
              <td><?= money($s['receita']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$topServicos): ?><tr><td colspan="3" class="text-muted text-center">Sem dados.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <!-- Top clientes -->
  <div class="col-md-6">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Top 10 Clientes</div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
          <thead class="table-light"><tr><th>Cliente</th><th>OS</th><th>Total gasto</th></tr></thead>
          <tbody>
            <?php foreach ($topClientes as $c): ?>
            <tr>
              <td><?= e($c['nome']) ?></td>
              <td><span class="badge bg-secondary"><?= $c['total_os'] ?></span></td>
              <td class="fw-semibold text-success"><?= money($c['total_gasto']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$topClientes): ?><tr><td colspan="3" class="text-muted text-center">Sem dados.</td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
const fatData = <?= json_encode($faturamento) ?>;
new Chart(document.getElementById('chartFat'), {
  type: 'bar',
  data: {
    labels: fatData.map(d => d.mes),
    datasets: [{
      label: 'Faturamento R$', data: fatData.map(d => d.total),
      backgroundColor: '#0d6efd88', borderColor: '#0d6efd', borderWidth: 1
    }]
  },
  options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
});
</script>
