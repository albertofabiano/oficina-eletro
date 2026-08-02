<!-- Cards de métricas -->
<div class="row g-3 mb-4">
  <?php $cards = [
    ['bi-building','text-danger',   $metricas['total_empresas'],'Empresas ativas'],
    ['bi-people',  'text-primary',  $metricas['total_usuarios'],'Usuários totais'],
    ['bi-clipboard2-pulse','text-warning',$metricas['total_os'],'OS registradas'],
    ['bi-person',  'text-info',     $metricas['total_clientes'],'Clientes'],
    ['bi-plus-circle','text-success',$metricas['os_hoje'],   'OS hoje'],
    ['bi-clock',   'text-warning',  $metricas['em_trial'],  'Em trial'],
  ]; ?>
  <?php foreach($cards as [$icon,$cor,$val,$label]): ?>
  <div class="col-6 col-md-2">
    <div class="ms-card p-3 text-center">
      <i class="bi <?= $icon ?> <?= $cor ?> fs-3 d-block mb-1"></i>
      <div class="fw-bold fs-4 text-white"><?= number_format($val) ?></div>
      <div class="small text-muted"><?= $label ?></div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-4">
  <!-- Gráfico OS por dia -->
  <div class="col-md-8">
    <div class="ms-card p-3">
      <div class="fw-semibold text-white mb-3">OS criadas — últimos 30 dias</div>
      <canvas id="chartOs" height="100"></canvas>
    </div>
  </div>
  <!-- Distribuição por plano -->
  <div class="col-md-4">
    <div class="ms-card p-3">
      <div class="fw-semibold text-white mb-3">Distribuição por plano</div>
      <?php
        $planos = ['basico'=>0,'profissional'=>0,'enterprise'=>0];
        foreach ($empresas as $e) if (isset($planos[$e['plano']])) $planos[$e['plano']]++;
        $total = array_sum($planos) ?: 1;
      ?>
      <?php foreach ($planos as $plano => $qtd): ?>
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge badge-plano-<?= $plano ?>"><?= ucfirst($plano) ?></span>
        <div class="flex-grow-1 mx-2">
          <div class="progress" style="height:6px;background:rgba(255,255,255,.1)">
            <div class="progress-bar bg-<?= $plano==='basico'?'secondary':($plano==='profissional'?'primary':'purple') ?>"
                 style="width:<?= round($qtd/$total*100) ?>%"></div>
          </div>
        </div>
        <span class="text-white small"><?= $qtd ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Tabela de empresas -->
<div class="ms-card">
  <div class="card-header d-flex justify-content-between align-items-center py-3 px-4">
    <span class="fw-semibold text-white">Empresas cadastradas</span>
    <a href="<?= url('/master/empresas') ?>" class="btn btn-danger btn-sm">Ver todas</a>
  </div>
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead><tr><th>Empresa</th><th>Plano</th><th>Usuários</th><th>OS</th><th>Trial</th><th>Cadastro</th><th></th></tr></thead>
      <tbody>
        <?php foreach (array_slice($empresas, 0, 10) as $e): ?>
        <tr>
          <td>
            <div class="fw-semibold text-white"><?= e($e['nome_fantasia']) ?></div>
            <div class="small text-muted"><?= e($e['email']) ?></div>
          </td>
          <td><span class="badge badge-plano-<?= $e['plano'] ?>"><?= ucfirst($e['plano']) ?></span></td>
          <td><?= $e['qtd_usuarios'] ?></td>
          <td><?= number_format($e['qtd_os']) ?></td>
          <td>
            <?php if ($e['trial_ate']): ?>
            <?php $dias = (int)ceil((strtotime($e['trial_ate'])-time())/86400); ?>
            <span class="badge bg-<?= $dias>0?'warning':'danger' ?>"><?= $dias>0?$dias.'d':'Expirado' ?></span>
            <?php else: ?><span class="text-muted small">—</span><?php endif; ?>
          </td>
          <td class="text-muted small"><?= date_br($e['criado_em']) ?></td>
          <td>
            <a href="<?= url('/master/empresas/' . $e['id']) ?>" class="btn btn-sm btn-outline-secondary">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
const osData = <?= json_encode($osPorDia) ?>;
new Chart(document.getElementById('chartOs'), {
  type:'line',
  data:{
    labels: osData.map(d=>d.dia),
    datasets:[{
      label:'OS', data: osData.map(d=>d.total),
      borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,.15)',
      tension:.4, fill:true, pointRadius:3
    }]
  },
  options:{
    plugins:{legend:{display:false}},
    scales:{
      x:{ticks:{color:'#555',maxTicksLimit:10},grid:{color:'rgba(255,255,255,.05)'}},
      y:{ticks:{color:'#555'},grid:{color:'rgba(255,255,255,.05)'},beginAtZero:true}
    }
  }
});
</script>
