<?php
$totalFat   = (float)($resumo['total_faturado'] ?? 0);
$totalOS    = (int)($resumo['total_os'] ?? 0);
$ticket     = (float)($resumo['ticket_medio'] ?? 0);
$concluidas = (int)($resumo['concluidas'] ?? 0);
$canceladas = (int)($resumo['canceladas'] ?? 0);
$tempoMedio = round((float)($resumo['tempo_medio_dias'] ?? 0), 1);
$taxaConc   = $totalOS > 0 ? round($concluidas / $totalOS * 100) : 0;
$totalGarI  = (int)($garantia['total_garantia'] ?? 0);
$totalNorm  = (int)($garantia['total_normal'] ?? 0);
$taxaGar    = $totalNorm > 0 ? round($totalGarI / $totalNorm * 100, 1) : 0;
$dias       = max(1, (strtotime($fim) - strtotime($ini)) / 86400 + 1);
$porDia     = $totalFat / $dias;
?>

<style>
.kpi-card { border-radius:16px;border:none;overflow:hidden;position:relative;min-height:100px; }
.kpi-card .kpi-icon { font-size:3rem;opacity:.15;position:absolute;right:12px;bottom:8px; }
.kpi-label { font-size:.75rem;font-weight:600;opacity:.8;letter-spacing:.04em;text-transform:uppercase; }
.kpi-valor { font-size:1.7rem;font-weight:800;line-height:1.1;margin:.2rem 0; }
.kpi-sub   { font-size:.72rem;opacity:.7; }
.chart-card { border-radius:16px;border:1px solid #e2e8f0;background:#fff; }
.chart-card .chart-title { font-size:.85rem;font-weight:700;color:#1e293b;padding:1rem 1.25rem .5rem; }
.rank-item { display:flex;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #f1f5f9; }
.rank-item:last-child { border-bottom:none; }
.rank-num { width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.72rem;font-weight:800;flex-shrink:0; }
.rank-bar { height:6px;border-radius:3px;transition:.3s; }
</style>

<!-- Filtro -->
<form class="card border-0 shadow-sm p-3 mb-4" method="GET" id="formFiltro">
  <div class="row g-2 align-items-end">
    <div class="col-md-2">
      <label class="form-label small fw-semibold">De</label>
      <input type="date" name="data_inicio" class="form-control" value="<?= e($ini) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small fw-semibold">Até</label>
      <input type="date" name="data_fim" class="form-control" value="<?= e($fim) ?>">
    </div>
    <div class="col-md-3">
      <label class="form-label small fw-semibold">Status</label>
      <?php
      $optionsHtml = '<option value="">Todos os Status</option>';
      foreach ($porStatus as $s) {
          $id       = (int)($s['id'] ?? 0);
          $nome     = htmlspecialchars($s['nome'] ?? '', ENT_QUOTES, 'UTF-8');
          $total    = (int)($s['total'] ?? 0);
          $selected = ($id === ($statusIdFiltro ?? 0)) ? ' selected' : '';
          $optionsHtml .= "<option value=\"{$id}\"{$selected}>{$nome} ({$total})</option>";
      }
      ?>
      <select name="status_id" id="selectStatus" class="form-select">
        <?= $optionsHtml ?>
      </select>
    </div>
    <div class="col-md-2">
      <button class="btn btn-primary w-100 fw-semibold"><i class="bi bi-funnel me-1"></i>Filtrar</button>
    </div>
    <div class="col-md-3 d-flex gap-1 flex-wrap align-items-center">
      <a href="?data_inicio=<?= date('Y-m-01') ?>&data_fim=<?= date('Y-m-t') ?>" class="btn btn-outline-secondary btn-sm">Mês</a>
      <a href="?data_inicio=<?= date('Y-m-d',strtotime('-30 days')) ?>&data_fim=<?= date('Y-m-d') ?>" class="btn btn-outline-secondary btn-sm">30d</a>
      <a href="?data_inicio=<?= date('Y-01-01') ?>&data_fim=<?= date('Y-12-31') ?>" class="btn btn-outline-secondary btn-sm">Ano</a>
      <button type="button" class="btn btn-danger btn-sm fw-semibold ms-auto"
              onclick="imprimirRelatorio()">
        <i class="bi bi-printer me-1"></i>Imprimir
      </button>
    </div>
  </div>
</form>

<script>
function imprimirRelatorio() {
  const form   = document.getElementById('formFiltro');
  const ini    = form.querySelector('[name=data_inicio]').value;
  const fim    = form.querySelector('[name=data_fim]').value;
  const status = document.getElementById('selectStatus').value;
  let url = '<?= url('/relatorios/imprimir') ?>?data_inicio=' + ini + '&data_fim=' + fim;
  if (status) url += '&status_id=' + status;
  window.open(url, '_blank');
}
</script>

<!-- KPIs -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-2">
    <div class="kpi-card p-3 text-white" style="background:linear-gradient(135deg,#2563eb,#1d4ed8)">
      <div class="kpi-label">Total OS</div>
      <div class="kpi-valor"><?= number_format($totalOS) ?></div>
      <div class="kpi-sub"><?= $taxaConc ?>% concluídas</div>
      <i class="bi bi-clipboard2-pulse kpi-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="kpi-card p-3 text-white" style="background:linear-gradient(135deg,#16a34a,#15803d)">
      <div class="kpi-label">Faturamento</div>
      <div class="kpi-valor" style="font-size:1.3rem"><?= money($totalFat) ?></div>
      <div class="kpi-sub"><?= money($porDia) ?>/dia</div>
      <i class="bi bi-currency-dollar kpi-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="kpi-card p-3 text-white" style="background:linear-gradient(135deg,#0891b2,#0e7490)">
      <div class="kpi-label">Ticket Médio</div>
      <div class="kpi-valor" style="font-size:1.3rem"><?= money($ticket) ?></div>
      <div class="kpi-sub">por OS</div>
      <i class="bi bi-receipt kpi-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="kpi-card p-3 text-white" style="background:linear-gradient(135deg,<?= $tempoMedio > 10 ? '#dc2626,#b91c1c' : '#7c3aed,#6d28d9' ?>)">
      <div class="kpi-label">Tempo Médio</div>
      <div class="kpi-valor"><?= $tempoMedio ?>d</div>
      <div class="kpi-sub"><?= $tempoMedio > 10 ? '⚠️ acima do ideal' : '✅ dentro do prazo' ?></div>
      <i class="bi bi-clock kpi-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="kpi-card p-3 text-white" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
      <div class="kpi-label">Canceladas</div>
      <div class="kpi-valor"><?= $canceladas ?></div>
      <div class="kpi-sub"><?= $totalOS ? round($canceladas/$totalOS*100) : 0 ?>% do total</div>
      <i class="bi bi-x-circle kpi-icon"></i>
    </div>
  </div>
  <div class="col-6 col-md-2">
    <div class="kpi-card p-3 text-white" style="background:linear-gradient(135deg,<?= $taxaGar > 10 ? '#dc2626,#b91c1c' : '#64748b,#475569' ?>)">
      <div class="kpi-label">Retorno Garantia</div>
      <div class="kpi-valor"><?= $totalGarI ?></div>
      <div class="kpi-sub">taxa <?= $taxaGar ?>%</div>
      <i class="bi bi-shield-check kpi-icon"></i>
    </div>
  </div>
</div>

<!-- Linha 1: Faturamento mensal + Status -->
<div class="row g-3 mb-4">

  <div class="col-md-8">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">📈 Faturamento Mensal — últimos 12 meses</div>
      <div class="px-3 pb-3"><canvas id="chartFat" height="100"></canvas></div>
    </div>
  </div>

  <div class="col-md-4">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">🍩 OS por Status</div>
      <div class="px-3 pb-2" style="height:220px">
        <canvas id="chartStatus"></canvas>
      </div>
      <!-- Cards clicáveis por status -->
      <?php
      $urlOs = url('/os');
      $cardsHtml = '';
      foreach ($porStatus as $s) {
          $id       = (int)($s['id'] ?? 0);
          $nome     = htmlspecialchars($s['nome'] ?? '', ENT_QUOTES, 'UTF-8');
          $cor      = htmlspecialchars($s['cor'] ?? '#6c757d', ENT_QUOTES, 'UTF-8');
          $fonte    = htmlspecialchars($s['cor_fonte'] ?? '#ffffff', ENT_QUOTES, 'UTF-8');
          $total    = (int)($s['total'] ?? 0);
          $valor    = (float)($s['valor'] ?? 0);
          $opac     = $total > 0 ? '1' : '.45';
          $valorStr = $valor > 0 ? '<div style="font-size:.65rem;opacity:.75;margin-top:1px">R$ ' . number_format($valor,0,',','.') . '</div>' : '';
          $cardsHtml .= '<a href="' . $urlOs . '?status_id=' . $id . '" style="text-decoration:none;flex:1;min-width:calc(50% - 4px)">'
              . '<div style="background:' . $cor . ';color:' . $fonte . ';border-radius:10px;padding:8px 10px;cursor:pointer;transition:.15s;opacity:' . $opac . '"'
              . ' onmouseover="this.style.transform=\'translateY(-2px)\';this.style.boxShadow=\'0 6px 16px rgba(0,0,0,.2)\'"'
              . ' onmouseout="this.style.transform=\'\';this.style.boxShadow=\'\'">'
              . '<div style="font-size:1.3rem;font-weight:800;line-height:1">' . $total . '</div>'
              . '<div style="font-size:.68rem;font-weight:600;opacity:.85;margin-top:2px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' . $nome . '</div>'
              . $valorStr
              . '</div></a>';
      }
      ?>
      <div class="px-3 pb-3 d-flex flex-wrap gap-2"><?= $cardsHtml ?></div>
    </div>
  </div>

</div>

<!-- Linha 2: Técnicos -->
<?php if ($porTecnico): ?>
<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">👨‍🔧 Faturamento por Técnico</div>
      <div class="px-3 pb-3"><canvas id="chartTec" height="140"></canvas></div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">⏱️ Tempo Médio de Reparo por Técnico (dias)</div>
      <div class="px-3 pb-3"><canvas id="chartTecTempo" height="140"></canvas></div>
    </div>
  </div>
</div>
<?php endif; ?>

<!-- Linha 3: Marcas + Defeitos -->
<div class="row g-3 mb-4">

  <div class="col-md-6">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">🏷️ Marcas Mais Atendidas</div>
      <div class="px-3 pb-3">
        <?php
        $maxMarca = $topMarcas[0]['total'] ?? 1;
        $cores = ['#2563eb','#16a34a','#dc2626','#f59e0b','#7c3aed','#0891b2','#db2777','#64748b','#059669','#d97706'];
        foreach ($topMarcas as $i => $m):
          $pct = round($m['total'] / $maxMarca * 100);
          $cor = $cores[$i % count($cores)];
        ?>
        <div class="rank-item">
          <div class="rank-num text-white" style="background:<?= $cor ?>"><?= $i+1 ?></div>
          <div style="flex:1;min-width:0">
            <div style="font-size:.82rem;font-weight:600;color:#1e293b"><?= e($m['marca']) ?></div>
            <div class="rank-bar mt-1" style="background:<?= $cor ?>22;width:100%">
              <div class="rank-bar" style="background:<?= $cor ?>;width:<?= $pct ?>%"></div>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:.82rem;font-weight:700;color:#1e293b"><?= $m['total'] ?> OS</div>
            <div style="font-size:.7rem;color:#64748b"><?= money($m['receita']) ?></div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$topMarcas): ?><div class="text-muted small text-center py-3">Sem dados no período.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">🔧 Serviços Mais Realizados</div>
      <div class="px-3 pb-3"><canvas id="chartSvc" height="200"></canvas></div>
    </div>
  </div>

</div>

<!-- Linha 4: Clientes + Defeitos -->
<div class="row g-3">

  <div class="col-md-6">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">👥 Melhores Clientes</div>
      <div class="px-3 pb-3">
        <?php
        $maxCli = max(1, $topClientes[0]['total_gasto'] ?? 1);
        foreach ($topClientes as $i => $c):
          $pct = round($c['total_gasto'] / $maxCli * 100);
          $cor = $cores[$i % count($cores)];
        ?>
        <div class="rank-item">
          <div class="rank-num text-white" style="background:<?= $cor ?>"><?= $i+1 ?></div>
          <div style="flex:1;min-width:0">
            <div class="text-truncate" style="font-size:.82rem;font-weight:600;color:#1e293b"><?= e($c['nome']) ?></div>
            <div class="rank-bar mt-1" style="background:<?= $cor ?>22;width:100%">
              <div class="rank-bar" style="background:<?= $cor ?>;width:<?= $pct ?>%"></div>
            </div>
          </div>
          <div style="text-align:right;flex-shrink:0">
            <div style="font-size:.82rem;font-weight:700;color:#16a34a"><?= money($c['total_gasto']) ?></div>
            <div style="font-size:.7rem;color:#64748b"><?= $c['total_os'] ?> OS</div>
          </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$topClientes): ?><div class="text-muted small text-center py-3">Sem dados no período.</div><?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-md-6">
    <div class="chart-card shadow-sm h-100">
      <div class="chart-title">⚠️ Defeitos Mais Comuns (Top 8)</div>
      <div class="px-3 pb-3"><canvas id="chartDefeito" height="200"></canvas></div>
    </div>
  </div>

</div>

<script>
window.addEventListener('load', function() {
// ── Paleta de cores ──────────────────────────────────────────
const CORES = ['#2563eb','#16a34a','#dc2626','#f59e0b','#7c3aed','#0891b2','#db2777','#64748b','#059669','#d97706'];

// ── Faturamento mensal ───────────────────────────────────────
const fatData = <?= json_encode(array_values($faturamento)) ?>;
new Chart(document.getElementById('chartFat'), {
  type: 'bar',
  data: {
    labels: fatData.map(d => d.mes_label || d.mes),
    datasets: [
      {
        label: 'Faturamento',
        data: fatData.map(d => parseFloat(d.total)||0),
        backgroundColor: ctx => {
          const g = ctx.chart.ctx.createLinearGradient(0,0,0,280);
          g.addColorStop(0,'rgba(37,99,235,.85)');
          g.addColorStop(1,'rgba(37,99,235,.15)');
          return g;
        },
        borderColor: '#2563eb',
        borderWidth: 2,
        borderRadius: 8,
        borderSkipped: false,
        yAxisID: 'y',
      },
      {
        label: 'Qtd OS',
        data: fatData.map(d => parseInt(d.qtd)||0),
        type: 'line',
        borderColor: '#16a34a',
        backgroundColor: 'rgba(22,163,74,.08)',
        fill: true,
        borderWidth: 2.5,
        pointRadius: 5,
        pointBackgroundColor: '#16a34a',
        tension: 0.4,
        yAxisID: 'y1',
      }
    ]
  },
  options: {
    responsive: true,
    interaction: { mode: 'index', intersect: false },
    plugins: {
      legend: { position: 'top', labels: { usePointStyle: true, padding: 16 } },
      tooltip: {
        callbacks: {
          label: ctx => ctx.dataset.yAxisID === 'y'
            ? ' R$ ' + ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2})
            : ' ' + ctx.raw + ' OS'
        }
      }
    },
    scales: {
      y:  { position:'left',  beginAtZero:true, grid:{ color:'#f1f5f9' }, ticks:{ callback: v=>'R$'+Math.round(v/1000)+'k' } },
      y1: { position:'right', beginAtZero:true, grid:{ drawOnChartArea:false }, ticks:{ color:'#16a34a' } },
    }
  }
});

// ── Pizza por status ─────────────────────────────────────────
const statusData = <?= json_encode(array_values(array_filter($porStatus, fn($s) => $s['total'] > 0))) ?>;
new Chart(document.getElementById('chartStatus'), {
  type: 'pie',
  data: {
    labels: statusData.map(s => s.nome),
    datasets: [{
      data: statusData.map(s => s.total),
      backgroundColor: statusData.map(s => s.cor),
      borderWidth: 4,
      borderColor: '#fff',
      hoverBorderWidth: 5,
      hoverOffset: 12,
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    animation: { animateRotate: true, animateScale: true, duration: 900, easing: 'easeInOutQuart' },
    plugins: {
      legend: {
        display: true,
        position: 'right',
        labels: {
          boxWidth: 14,
          boxHeight: 14,
          padding: 12,
          font: { size: 12, weight: '600' },
          color: '#334155',
          filter: item => item.text && parseInt(statusData[item.index]?.total) > 0,
        }
      },
      tooltip: {
        callbacks: {
          label: ctx => {
            const tot = statusData.reduce((a,s)=>a+parseInt(s.total),0);
            return ` ${ctx.label}: ${ctx.raw} OS (${tot ? Math.round(ctx.parsed/tot*100) : 0}%)`;
          }
        }
      }
    }
  }
});

<?php if ($porTecnico): ?>
// ── Faturamento por técnico ──────────────────────────────────
const tecData = <?= json_encode(array_values($porTecnico)) ?>;
new Chart(document.getElementById('chartTec'), {
  type: 'bar',
  data: {
    labels: tecData.map(t => t.tecnico.split(' ')[0]),
    datasets: [{
      label: 'Faturamento',
      data: tecData.map(t => parseFloat(t.faturamento)||0),
      backgroundColor: CORES.slice(0,tecData.length).map(c => c+'cc'),
      borderColor: CORES.slice(0,tecData.length),
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend:{ display:false }, tooltip:{ callbacks:{ label: ctx=>' R$ '+ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2}) } } },
    scales: { x: { beginAtZero:true, ticks:{ callback: v=>'R$'+Math.round(v/1000)+'k' }, grid:{ color:'#f1f5f9' } }, y: { grid:{ display:false } } }
  }
});

// ── Tempo médio por técnico ──────────────────────────────────
new Chart(document.getElementById('chartTecTempo'), {
  type: 'bar',
  data: {
    labels: tecData.map(t => t.tecnico.split(' ')[0]),
    datasets: [{
      label: 'Dias',
      data: tecData.map(t => parseFloat(t.tempo_medio)||0),
      backgroundColor: tecData.map(t => parseFloat(t.tempo_medio) > 10 ? '#dc262699' : '#16a34a99'),
      borderColor:     tecData.map(t => parseFloat(t.tempo_medio) > 10 ? '#dc2626' : '#16a34a'),
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend:{ display:false }, tooltip:{ callbacks:{ label: ctx=>` ${ctx.raw.toFixed(1)} dias` } } },
    scales: { x: { beginAtZero:true, grid:{ color:'#f1f5f9' } }, y: { grid:{ display:false } } }
  }
});
<?php endif; ?>

// ── Top serviços ─────────────────────────────────────────────
const svcData = <?= json_encode(array_values(array_slice($topServicos,0,8))) ?>;
new Chart(document.getElementById('chartSvc'), {
  type: 'bar',
  data: {
    labels: svcData.map(s => s.descricao.length > 25 ? s.descricao.substring(0,24)+'…' : s.descricao),
    datasets: [{
      label: 'Receita',
      data: svcData.map(s => parseFloat(s.receita)||0),
      backgroundColor: CORES.slice(0,svcData.length).map(c=>c+'bb'),
      borderColor: CORES.slice(0,svcData.length),
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend:{ display:false }, tooltip:{ callbacks:{ label: ctx=>' R$ '+ctx.raw.toLocaleString('pt-BR',{minimumFractionDigits:2}) } } },
    scales: { x: { beginAtZero:true, ticks:{ callback: v=>'R$'+Math.round(v/1000)+'k' }, grid:{ color:'#f1f5f9' } }, y: { grid:{ display:false }, ticks:{ font:{ size:11 } } } }
  }
});

// ── Top defeitos ─────────────────────────────────────────────
const defData = <?= json_encode(array_values(array_slice($topDefeitos,0,8))) ?>;
new Chart(document.getElementById('chartDefeito'), {
  type: 'bar',
  data: {
    labels: defData.map(d => d.defeito.length > 30 ? d.defeito.substring(0,29)+'…' : d.defeito),
    datasets: [{
      label: 'Ocorrências',
      data: defData.map(d => parseInt(d.vezes)||0),
      backgroundColor: defData.map((d,i) => CORES[i%CORES.length]+'bb'),
      borderColor:     defData.map((d,i) => CORES[i%CORES.length]),
      borderWidth: 2,
      borderRadius: 6,
    }]
  },
  options: {
    indexAxis: 'y',
    responsive: true,
    plugins: { legend:{ display:false }, tooltip:{ callbacks:{ label: ctx=>` ${ctx.raw} ocorrência(s)` } } },
    scales: { x: { beginAtZero:true, ticks:{ stepSize:1 }, grid:{ color:'#f1f5f9' } }, y: { grid:{ display:false }, ticks:{ font:{ size:11 } } } }
  }
});
}); // fim load
</script>
