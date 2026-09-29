<?php
/**
 * Painel do módulo Marketing (Etapa 1) — indicadores, gráfico diário, alertas e tabela de
 * campanhas, tudo lido de mkt_campaigns/mkt_daily_insights (MarketingController::painel()).
 * Mesma linguagem visual (tokens var(--*)) do dashboard principal — ver app/Views/dashboard/index.php.
 */
use App\Services\Marketing\Money;
use App\Services\Marketing\Dashboard;

$totais = $painel['totals'];
$anteriores = $painel['previous_totals'];
$dryRun = (bool) ((is_file(BASE_PATH . '/config/marketing.php') ? require BASE_PATH . '/config/marketing.php' : [])['dry_run'] ?? true);

$pct = function (?float $atual, ?float $anterior) {
    $v = Dashboard::percentChange($atual, $anterior);
    if ($v === null) return null;
    $sinal = $v > 0 ? '+' : '';
    return $sinal . number_format($v * 100, 1, ',', '.') . '%';
};

$kpis = [
    ['label' => 'Investimento',      'valor' => Money::formatCents($totais['spend_cents']),                                           'delta' => $pct($totais['spend_cents'], $anteriores['spend_cents'])],
    ['label' => 'Leads',             'valor' => number_format($totais['leads'], 0, ',', '.'),                                          'delta' => $pct($totais['leads'], $anteriores['leads'])],
    ['label' => 'Custo por lead',    'valor' => $totais['cost_per_lead_cents'] !== null ? Money::formatCents($totais['cost_per_lead_cents']) : '—', 'delta' => $pct($totais['cost_per_lead_cents'], $anteriores['cost_per_lead_cents'])],
    ['label' => 'CTR',               'valor' => $totais['ctr'] !== null ? number_format($totais['ctr'] * 100, 2, ',', '.') . '%' : '—', 'delta' => $pct($totais['ctr'], $anteriores['ctr'])],
    ['label' => 'Custo por clique',  'valor' => $totais['cost_per_click_cents'] !== null ? Money::formatCents($totais['cost_per_click_cents']) : '—', 'delta' => $pct($totais['cost_per_click_cents'], $anteriores['cost_per_click_cents'])],
    ['label' => 'CPM',               'valor' => $totais['cpm_cents'] !== null ? Money::formatCents($totais['cpm_cents']) : '—',         'delta' => $pct($totais['cpm_cents'], $anteriores['cpm_cents'])],
];
?>
<div class="fx-mkt">
<style>
.fx-mkt,.fx-mkt *{text-transform:none!important}
.fx-mkt-head{display:flex;align-items:flex-end;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.1rem}
.fx-mkt-title{font-size:18px;font-weight:700;color:var(--text-1);margin:0}
.fx-mkt-badges{display:flex;gap:.4rem;margin-top:.3rem}
.fx-mkt-badge{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px;background:var(--surface-2);color:var(--text-3);border:1px solid var(--border)}
.fx-mkt-badge.simulacao{color:#92400e;background:#fef3c7;border-color:#fde68a}
.fx-mkt-period a{padding:6px 12px;border-radius:8px;font-size:13px;font-weight:600;color:var(--text-2);text-decoration:none;border:1px solid var(--border)}
.fx-mkt-period a.ativo{background:var(--accent);color:#fff;border-color:var(--accent)}
.fx-mkt-sync{display:flex;align-items:center;gap:.6rem;margin-top:.6rem;flex-wrap:wrap}
.fx-mkt-sync-btn{display:inline-flex;align-items:center;gap:.35rem;font-size:12.5px;font-weight:600;padding:5px 11px;border-radius:8px;border:1px solid var(--border);background:var(--surface-1);color:var(--text-2);cursor:pointer}
.fx-mkt-sync-btn:disabled{opacity:.55;cursor:not-allowed}
.fx-mkt-sync-btn:not(:disabled):hover{background:var(--surface-2)}
.fx-mkt-sync-info{font-size:11.5px;color:var(--text-3)}
.fx-mkt-aprovacoes-link{display:inline-flex;align-items:center;gap:.4rem;font-size:12.5px;font-weight:600;color:var(--text-2);text-decoration:none;padding:5px 10px;border:1px solid var(--border);border-radius:8px}
.fx-mkt-aprovacoes-link:hover{background:var(--surface-2)}
.fx-mkt-aprovacoes-count{background:var(--danger-bg,#fee2e2);color:var(--danger,#dc2626);font-weight:700;font-size:11px;padding:1px 7px;border-radius:999px}
.fx-mkt-kpi-row{display:grid;grid-template-columns:repeat(6,1fr);gap:.7rem;margin-bottom:1rem}
@media (max-width:1100px){.fx-mkt-kpi-row{grid-template-columns:repeat(3,1fr)}}
@media (max-width:640px){.fx-mkt-kpi-row{grid-template-columns:repeat(2,1fr)}}
.fx-mkt-kpi{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg,10px);padding:12px 14px}
.fx-mkt-kpi-label{font-size:11.5px;font-weight:600;color:var(--text-3);margin-bottom:4px}
.fx-mkt-kpi-value{font-size:20px;font-weight:800;color:var(--text-1);line-height:1.15}
.fx-mkt-kpi-delta{font-size:11.5px;font-weight:600;margin-top:2px}
.fx-mkt-kpi-delta.pos{color:var(--success-fill,#16a34a)}
.fx-mkt-kpi-delta.neg{color:var(--danger-fill,#dc2626)}
.fx-mkt-card{background:var(--surface-1);border:1px solid var(--border);border-radius:var(--radius-lg,10px);padding:16px;margin-bottom:1rem}
.fx-mkt-card h2{font-size:14px;font-weight:700;color:var(--text-1);margin:0 0 .8rem}
.fx-mkt-alert{display:flex;align-items:center;gap:.6rem;padding:9px 12px;border-radius:8px;font-size:13px;margin-bottom:.5rem}
.fx-mkt-alert.critical{background:#fee2e2;color:#991b1b}
.fx-mkt-alert.warning{background:#fef3c7;color:#92400e}
.fx-mkt-table{width:100%;font-size:13px;border-collapse:collapse}
.fx-mkt-table th{text-align:left;color:var(--text-3);font-weight:600;font-size:11.5px;padding:6px 10px;border-bottom:1px solid var(--border)}
.fx-mkt-table td{padding:8px 10px;border-bottom:1px solid var(--border);color:var(--text-1)}
.fx-mkt-status{font-size:11px;font-weight:700;padding:2px 8px;border-radius:999px}
.fx-mkt-status.active{background:#dcfce7;color:#166534}
.fx-mkt-status.paused{background:#f1f5f9;color:#475569}
.fx-mkt-status.archived{background:#f1f5f9;color:#94a3b8}
</style>

<div class="fx-mkt-head">
  <div>
    <h1 class="fx-mkt-title">Marketing</h1>
    <div class="fx-mkt-badges">
      <?php if ($dryRun): ?><span class="fx-mkt-badge simulacao">Modo simulação</span><?php endif; ?>
      <?php if (($conta['platform'] ?? '') === 'fake'): ?><span class="fx-mkt-badge">Conta de demonstração</span><?php endif; ?>
    </div>
    <div class="fx-mkt-sync">
      <button type="button" id="btnMktSincronizar" class="fx-mkt-sync-btn" <?= $faltamSegundos > 0 ? 'disabled' : '' ?>>
        <i class="bi bi-arrow-clockwise"></i>
        <span id="mktSincronizarTexto"><?= $faltamSegundos > 0 ? "Aguarde {$faltamSegundos}s" : 'Sincronizar agora' ?></span>
      </button>
      <span class="fx-mkt-sync-info">
        Última sincronização: <?= !empty($conta['last_synced_at']) ? date('d/m H:i', strtotime($conta['last_synced_at'])) : 'nunca' ?>
      </span>
    </div>
  </div>
  <div style="display:flex;flex-direction:column;align-items:flex-end;gap:.5rem">
    <a href="<?= url('/marketing/aprovacoes') ?>" class="fx-mkt-aprovacoes-link">
      <i class="bi bi-inbox"></i> Aprovações
      <?php if ($pendentes > 0): ?><span class="fx-mkt-aprovacoes-count"><?= $pendentes ?></span><?php endif; ?>
    </a>
    <div class="fx-mkt-period">
      <?php foreach ([7, 14, 30] as $opt): ?>
        <a href="<?= url('/marketing?dias=' . $opt) ?>" class="<?= $dias === $opt ? 'ativo' : '' ?>"><?= $opt ?> dias</a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<?php if ($pendentes > 0): ?>
<div class="fx-mkt-card" style="border-color:#f59e0b;background:#fffbeb">
  <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
    <div style="display:flex;align-items:center;gap:.6rem">
      <i class="bi bi-inbox-fill" style="color:#b45309;font-size:1.2rem"></i>
      <span style="color:#78350f;font-weight:600;font-size:13.5px">
        <?= $pendentes ?> sugestão<?= $pendentes > 1 ? 'ões' : '' ?> de otimização esperando sua decisão.
      </span>
    </div>
    <a href="<?= url('/marketing/aprovacoes') ?>" style="font-size:12.5px;font-weight:700;color:#b45309;text-decoration:none">Ver e decidir →</a>
  </div>
</div>
<?php endif; ?>

<div class="fx-mkt-kpi-row">
  <?php foreach ($kpis as $k): ?>
  <div class="fx-mkt-kpi">
    <div class="fx-mkt-kpi-label"><?= e($k['label']) ?></div>
    <div class="fx-mkt-kpi-value"><?= e($k['valor']) ?></div>
    <?php if ($k['delta'] !== null): ?>
      <div class="fx-mkt-kpi-delta <?= str_starts_with($k['delta'], '+') ? 'pos' : 'neg' ?>"><?= e($k['delta']) ?> vs. período anterior</div>
    <?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

<?php if (!empty($painel['alerts'])): ?>
<div class="fx-mkt-card">
  <h2>Alertas</h2>
  <?php foreach ($painel['alerts'] as $a): ?>
    <div class="fx-mkt-alert <?= e($a['severity']) ?>">
      <i class="bi <?= $a['severity'] === 'critical' ? 'bi-exclamation-octagon-fill' : 'bi-exclamation-triangle-fill' ?>"></i>
      <div><strong><?= e($a['title']) ?></strong> — <?= e($a['description']) ?></div>
    </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="fx-mkt-card">
  <h2>Investimento e leads por dia</h2>
  <canvas id="mktChart" height="80"></canvas>
</div>

<div class="fx-mkt-card">
  <h2>Campanhas</h2>
  <table class="fx-mkt-table">
    <thead><tr><th>Campanha</th><th>Status</th><th>Investimento</th><th>Leads</th><th>Custo/lead</th><th>Orçamento diário</th></tr></thead>
    <tbody>
      <?php foreach ($painel['rows'] as $row): $c = $row['campaign']; $m = $row['metrics']; ?>
      <tr>
        <td><?= e($c['name']) ?></td>
        <td><span class="fx-mkt-status <?= e($c['status']) ?>"><?= e(['active'=>'Ativa','paused'=>'Pausada','archived'=>'Arquivada'][$c['status']] ?? $c['status']) ?></span></td>
        <td><?= e(Money::formatCents($m['spend_cents'])) ?></td>
        <td><?= (int) $m['leads'] ?></td>
        <td><?= $m['cost_per_lead_cents'] !== null ? e(Money::formatCents($m['cost_per_lead_cents'])) : '—' ?></td>
        <td><?= $c['daily_budget_cents'] !== null ? e(Money::formatCents($c['daily_budget_cents'])) : '—' ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$painel['rows']): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--text-3);padding:1.2rem">Nenhuma campanha no período.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
(function(){
  var labels = <?= json_encode(array_map(fn($p) => substr($p['date'], 8, 2) . '/' . substr($p['date'], 5, 2), $painel['series']), JSON_UNESCAPED_UNICODE) ?>;
  var gastos = <?= json_encode(array_map(fn($p) => round($p['spend_cents'] / 100, 2), $painel['series'])) ?>;
  var leads  = <?= json_encode(array_map(fn($p) => $p['leads'], $painel['series'])) ?>;
  var ctx = document.getElementById('mktChart');
  if (ctx && window.Chart) {
    new Chart(ctx, {
      data: {
        labels: labels,
        datasets: [
          { type: 'bar', label: 'Investimento (R$)', data: gastos, backgroundColor: 'rgba(37,99,235,.55)', yAxisID: 'y' },
          { type: 'line', label: 'Leads', data: leads, borderColor: '#16a34a', backgroundColor: '#16a34a', yAxisID: 'y1', tension: .3 },
        ],
      },
      options: {
        responsive: true,
        scales: {
          y:  { position: 'left',  beginAtZero: true, title: { display: true, text: 'R$' } },
          y1: { position: 'right', beginAtZero: true, grid: { drawOnChartArea: false }, title: { display: true, text: 'Leads' } },
        },
      },
    });
  }
})();

(function(){
  var btn = document.getElementById('btnMktSincronizar');
  var txt = document.getElementById('mktSincronizarTexto');
  if (!btn) return;

  var faltam = <?= (int) $faltamSegundos ?>;
  var timer = null;

  function tick() {
    if (faltam <= 0) {
      btn.disabled = false;
      txt.textContent = 'Sincronizar agora';
      if (timer) { clearInterval(timer); timer = null; }
      return;
    }
    btn.disabled = true;
    txt.textContent = 'Aguarde ' + faltam + 's';
    faltam--;
  }
  if (faltam > 0) { tick(); timer = setInterval(tick, 1000); }

  btn.addEventListener('click', function () {
    btn.disabled = true;
    txt.textContent = 'Sincronizando...';
    fetch('<?= url('/marketing/sincronizar') ?>', {
      method: 'POST',
      headers: { 'X-CSRF-Token': '<?= csrf_token() ?>' },
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (j.sucesso) { location.reload(); return; }
        alert(j.erro || 'Não foi possível sincronizar agora.');
        faltam = j.aguardar_segundos || 0;
        if (faltam > 0) { tick(); timer = setInterval(tick, 1000); }
        else { btn.disabled = false; txt.textContent = 'Sincronizar agora'; }
      })
      .catch(function () {
        alert('Falha de rede ao sincronizar.');
        btn.disabled = false;
        txt.textContent = 'Sincronizar agora';
      });
  });
})();
</script>
</div>
