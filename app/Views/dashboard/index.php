<style>
.stat-card { border-radius:14px;border:none;overflow:hidden;position:relative; }
.stat-card .icon { font-size:2.5rem;opacity:.2;position:absolute;right:16px;bottom:10px; }
.stat-card .label { font-size:.8rem;opacity:.8;font-weight:500; }
.stat-card .valor { font-size:1.8rem;font-weight:800;line-height:1.1; }
.stat-card .sub   { font-size:.75rem;opacity:.7;margin-top:2px; }
.kpi-row { display:flex;flex-direction:column;gap:2px; }
</style>

<?php
$saldo   = ($resumo['recebido_mes'] ?? 0) - 0;
$vencido = $resumo['fin_vencido'] ?? 0;
?>

<!-- ── Linha 1: KPIs principais ── -->
<div class="row g-3 mb-3">

  <div class="col-6 col-md-3">
    <div class="stat-card p-3 text-white h-100" style="background:linear-gradient(135deg,#2563eb,#1d4ed8)">
      <div class="kpi-row">
        <div class="label">OS em Aberto</div>
        <div class="valor"><?= number_format($resumo['os_em_aberto_total'] ?? 0) ?></div>
        <div class="sub"><?= $resumo['os_atrasadas'] ?? 0 ?> atrasada(s)</div>
      </div>
      <i class="bi bi-clipboard2-pulse icon"></i>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card p-3 text-white h-100" style="background:linear-gradient(135deg,#16a34a,#15803d)">
      <div class="kpi-row">
        <div class="label">Concluídas no Mês</div>
        <div class="valor"><?= number_format($resumo['os_concluidas'] ?? 0) ?></div>
        <div class="sub">de <?= $resumo['total_os_mes'] ?? 0 ?> total no mês</div>
      </div>
      <i class="bi bi-check-circle icon"></i>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card p-3 text-white h-100" style="background:linear-gradient(135deg,#0891b2,#0e7490)">
      <div class="kpi-row">
        <div class="label">Faturado no Mês</div>
        <div class="valor" style="font-size:1.3rem"><?= money($resumo['faturamento_mes'] ?? 0) ?></div>
        <div class="sub">Recebido: <?= money($resumo['recebido_mes'] ?? 0) ?></div>
      </div>
      <i class="bi bi-currency-dollar icon"></i>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="stat-card p-3 text-white h-100" style="background:linear-gradient(135deg,<?= $vencido > 0 ? '#dc2626,#b91c1c' : '#7c3aed,#6d28d9' ?>)">
      <div class="kpi-row">
        <div class="label">A Receber</div>
        <div class="valor" style="font-size:1.3rem"><?= money($resumo['a_receber'] ?? 0) ?></div>
        <div class="sub"><?= $vencido > 0 ? '⚠️ Vencido: ' . money($vencido) : 'A Pagar: ' . money($resumo['a_pagar'] ?? 0) ?></div>
      </div>
      <i class="bi bi-<?= $vencido > 0 ? 'alarm' : 'wallet2' ?> icon"></i>
    </div>
  </div>

</div>

<!-- ── Linha 2: KPIs secundários ── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center" style="width:44px;height:44px">
          <i class="bi bi-people text-primary fs-5"></i>
        </div>
        <div>
          <div class="text-muted small">Total de Clientes</div>
          <div class="fw-bold fs-5"><?= number_format($resumo['total_clientes'] ?? 0) ?></div>
          <div class="text-muted" style="font-size:.72rem">+<?= $resumo['novos_clientes_mes'] ?? 0 ?> este mês</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-<?= ($resumo['alertas_estoque'] ?? 0) > 0 ? 'warning' : 'success' ?> bg-opacity-10 d-flex align-items-center justify-content-center" style="width:44px;height:44px">
          <i class="bi bi-box-seam text-<?= ($resumo['alertas_estoque'] ?? 0) > 0 ? 'warning' : 'success' ?> fs-5"></i>
        </div>
        <div>
          <div class="text-muted small">Alertas Estoque</div>
          <div class="fw-bold fs-5"><?= $resumo['alertas_estoque'] ?? 0 ?></div>
          <div class="text-muted" style="font-size:.72rem">produto(s) em mínimo</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center" style="width:44px;height:44px">
          <i class="bi bi-alarm text-danger fs-5"></i>
        </div>
        <div>
          <div class="text-muted small">OS Atrasadas</div>
          <div class="fw-bold fs-5 text-danger"><?= $resumo['os_atrasadas'] ?? 0 ?></div>
          <div class="text-muted" style="font-size:.72rem">prazo vencido</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-info bg-opacity-10 d-flex align-items-center justify-content-center" style="width:44px;height:44px">
          <i class="bi bi-calendar3 text-info fs-5"></i>
        </div>
        <div>
          <div class="text-muted small">Agenda Hoje</div>
          <div class="fw-bold fs-5"><?= count($agenda) ?></div>
          <div class="text-muted" style="font-size:.72rem">compromisso(s)</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ── Linha 3: Gráfico + Status ── -->
<div class="row g-3 mb-3">

  <!-- Gráfico OS 12 meses -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-graph-up-arrow text-primary me-2"></i>Ordens de Serviço — Últimos 12 Meses</span>
        <span class="badge bg-primary">Recebidas × Concluídas</span>
      </div>
      <div class="card-body">
        <canvas id="chartOsMes" height="100"></canvas>
      </div>
    </div>
  </div>

  <!-- OS por Status -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">OS por Status</div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach (($resumo['por_status'] ?? []) as $st): ?>
          <?php if (!$st['total']) continue; ?>
          <li class="list-group-item d-flex align-items-center justify-content-between py-2">
            <div class="d-flex align-items-center gap-2">
              <span class="rounded-circle" style="width:10px;height:10px;background:<?= e($st['cor']) ?>;display:inline-block"></span>
              <span class="small"><?= e($st['nome']) ?></span>
            </div>
            <span class="badge rounded-pill" style="background:<?= e($st['cor']) ?>"><?= $st['total'] ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
    </div>
  </div>

</div>

<!-- ── Fluxo de OS: Entradas × Saídas ── -->
<?php $fr = $fluxoResumo ?? []; $acumulando = ($fr['ent_30d'] ?? 0) > ($fr['sai_30d'] ?? 0); ?>
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span><i class="bi bi-arrow-left-right me-2 text-primary"></i>Fluxo de OS — Entradas × Saídas <span class="text-muted fw-normal">(30 dias)</span></span>
        <span class="badge <?= $acumulando ? 'bg-warning text-dark' : 'bg-success' ?>">
          <?= $acumulando ? '⚠️ Entrando mais do que saindo — acumulando serviço' : '✅ Dando conta do fluxo' ?>
        </span>
      </div>
      <div class="card-body">
        <div class="row g-2 mb-3">
          <div class="col-6 col-md">
            <div class="rounded p-2 text-center h-100" style="background:#eff6ff">
              <div class="text-muted small"><i class="bi bi-box-arrow-in-down"></i> Entradas</div>
              <div class="fw-bold text-primary" style="font-size:1.5rem;line-height:1.1"><?= $fr['ent_30d'] ?? 0 ?></div>
              <div class="text-muted" style="font-size:.7rem">hoje <?= $fr['ent_hoje'] ?? 0 ?> · 7d <?= $fr['ent_7d'] ?? 0 ?></div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="rounded p-2 text-center h-100" style="background:#ecfdf5">
              <div class="text-muted small"><i class="bi bi-box-arrow-up"></i> Concluídas</div>
              <div class="fw-bold text-success" style="font-size:1.5rem;line-height:1.1"><?= $fr['sai_30d'] ?? 0 ?></div>
              <div class="text-muted" style="font-size:.7rem">hoje <?= $fr['sai_hoje'] ?? 0 ?> · 7d <?= $fr['sai_7d'] ?? 0 ?></div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="rounded p-2 text-center h-100" style="background:#dcfce7;border:1px solid rgba(22,163,74,.35)">
              <div class="text-muted small"><i class="bi bi-bag-check"></i> Prontas p/ retirar</div>
              <div class="fw-bold" style="font-size:1.5rem;line-height:1.1;color:#15803d"><?= $fr['prontas_retirar'] ?? 0 ?></div>
              <div class="text-muted" style="font-size:.7rem">aguardando o cliente</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="rounded p-2 text-center h-100" style="background:#fef9c3">
              <div class="text-muted small"><i class="bi bi-tools"></i> Em aberto</div>
              <div class="fw-bold" style="font-size:1.5rem;line-height:1.1;color:#a16207"><?= $fr['em_aberto'] ?? 0 ?></div>
              <div class="text-muted" style="font-size:.7rem">na oficina</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="rounded p-2 text-center h-100" style="background:#f5f3ff">
              <div class="text-muted small"><i class="bi bi-clock-history"></i> Tempo médio</div>
              <div class="fw-bold" style="font-size:1.5rem;line-height:1.1;color:#6d28d9"><?= (isset($fr['tempo_medio']) && $fr['tempo_medio'] !== null) ? $fr['tempo_medio'] . ' d' : '—' ?></div>
              <div class="text-muted" style="font-size:.7rem">entrada → conclusão</div>
            </div>
          </div>
        </div>
        <canvas id="chartFluxo" height="90"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ── Linha 4: Últimas OS + Top Serviços ── -->
<div class="row g-3 mb-3">

  <!-- Últimas OS -->
  <div class="col-md-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Últimas Ordens de Serviço</span>
        <a href="<?= url('/os/nova') ?>" class="btn btn-primary btn-sm fw-semibold">
          <i class="bi bi-plus-lg me-1"></i>Nova OS
        </a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 small align-middle">
          <thead class="table-light">
            <tr><th>Nº</th><th>Cliente</th><th>Equipamento</th><th>Status</th><th>Valor</th><th></th></tr>
          </thead>
          <tbody>
            <?php foreach ($ultimasOS as $os): ?>
            <tr>
              <td class="fw-bold">OS: <?= e($os['numero']) ?></td>
              <td><?= e($os['cliente_nome'] ?? '—') ?></td>
              <td>
                <div><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?: '—' ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= e($os['equip_tipo'] ?? '') ?></div>
              </td>
              <td><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '', $os['status_cor_fonte'] ?? '#ffffff') ?></td>
              <td class="fw-semibold"><?= money($os['valor_total']) ?></td>
              <td><a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$ultimasOS): ?>
            <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma OS cadastrada ainda.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Top Serviços -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold">Top Serviços</div>
      <div class="card-body p-0">
        <ul class="list-group list-group-flush">
          <?php foreach ($topServicos as $i => $s): ?>
          <li class="list-group-item d-flex align-items-center gap-2 py-2">
            <span class="badge rounded-pill bg-primary" style="min-width:22px"><?= $i+1 ?></span>
            <div class="flex-grow-1 min-w-0">
              <div class="small fw-semibold text-truncate"><?= e($s['descricao']) ?></div>
              <div class="text-muted" style="font-size:.72rem"><?= $s['vezes'] ?>x · <?= money($s['receita']) ?></div>
            </div>
          </li>
          <?php endforeach; ?>
          <?php if (!$topServicos): ?>
          <li class="list-group-item text-muted small text-center py-3">Nenhum serviço ainda.</li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </div>

</div>

<!-- ── OS atrasadas/vencendo ── -->
<?php if (!empty($vencendo)): ?>
<div class="card border-0 shadow-sm mb-3" style="border-left:4px solid #dc2626 !important">
  <div class="card-header bg-white fw-semibold text-danger">
    <i class="bi bi-alarm-fill me-2"></i><?= count($vencendo) ?> OS com prazo vencido
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light"><tr><th>Nº</th><th>Cliente</th><th>Previsão</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($vencendo as $os): ?>
        <tr class="table-danger">
          <td class="fw-bold">OS: <?= e($os['numero']) ?></td>
          <td><?= e($os['cliente_nome'] ?? '—') ?></td>
          <td class="text-danger fw-semibold"><?= date_br($os['data_previsao'], true) ?></td>
          <td><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '', $os['status_cor_fonte'] ?? '#ffffff') ?></td>
          <td><a href="<?= url('/os/'.$os['id']) ?>" class="btn btn-sm btn-danger"><i class="bi bi-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Agenda hoje -->
<?php if ($agenda): ?>
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-calendar3 me-2 text-primary"></i>Agenda de Hoje
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      <?php foreach ($agenda as $ev): ?>
      <li class="list-group-item d-flex gap-3 align-items-center py-2">
        <span class="badge bg-primary rounded-pill"><?= date('H:i', strtotime($ev['data_inicio'])) ?></span>
        <div>
          <div class="fw-semibold small"><?= e($ev['titulo']) ?></div>
          <?php if ($ev['cliente_nome']): ?><div class="text-muted" style="font-size:.75rem"><?= e($ev['cliente_nome']) ?></div><?php endif; ?>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  </div>
</div>
<?php endif; ?>

<script>
const osMesData = <?= json_encode($osPorMes ?? []) ?>;
function __initDashCharts() {
  if (typeof Chart === 'undefined') return; // Chart.js ainda não carregou — espera o window.load
  (function () {
  const cv = document.getElementById('chartOsMes');
  if (!cv) return;
  const g = cv.getContext('2d').createLinearGradient(0, 0, 0, 260);
  g.addColorStop(0, 'rgba(37,99,235,.30)');
  g.addColorStop(1, 'rgba(37,99,235,0)');
  const g2 = cv.getContext('2d').createLinearGradient(0, 0, 0, 260);
  g2.addColorStop(0, 'rgba(22,163,74,.18)');
  g2.addColorStop(1, 'rgba(22,163,74,0)');
  new Chart(cv, {
    type: 'line',
    data: {
      labels: osMesData.map(d => d.label),
      datasets: [
        {
          label: 'Recebidas',
          data: osMesData.map(d => d.entradas),
          borderColor: '#2563eb',
          backgroundColor: g,
          fill: true,
          tension: .35,
          borderWidth: 2.5,
          pointRadius: 2,
          pointHoverRadius: 5,
          pointBackgroundColor: '#2563eb',
        },
        {
          label: 'Concluídas',
          data: osMesData.map(d => d.concluidas),
          borderColor: '#16a34a',
          backgroundColor: g2,
          fill: true,
          tension: .35,
          borderWidth: 2.5,
          pointRadius: 2,
          pointHoverRadius: 5,
          pointBackgroundColor: '#16a34a',
        }
      ]
    },
    options: {
      responsive: true,
      interaction: { mode: 'index', intersect: false },
      plugins: {
        legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 16 } }
      },
      scales: {
        y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: 'rgba(0,0,0,.05)' } },
        x: { grid: { display: false } }
      }
    }
  });
})();

const fluxoData = <?= json_encode($fluxoDiario ?? []) ?>;
if (document.getElementById('chartFluxo')) new Chart(document.getElementById('chartFluxo'), {
  type: 'bar',
  data: {
    labels: fluxoData.map(d => d.label),
    datasets: [
      { label: 'Entradas', data: fluxoData.map(d => d.entradas), backgroundColor: 'rgba(59,130,246,.75)', borderRadius: 3 },
      { label: 'Concluídas', data: fluxoData.map(d => d.saidas), backgroundColor: 'rgba(34,197,94,.75)', borderRadius: 3 }
    ]
  },
  options: {
    responsive: true,
    plugins: { legend: { position: 'top' } },
    scales: { x: { ticks: { maxTicksLimit: 15 } }, y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1 } } }
  }
});
}
if (typeof Chart !== 'undefined') __initDashCharts();
else window.addEventListener('load', __initDashCharts);
</script>

<?php if (!empty($mostrarTutorial)): ?>
<!-- ── Tutorial de primeiros passos (exibe 1x por usuário) ── -->
<div class="modal fade" id="modalTutorial" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:16px;overflow:hidden">
      <div class="text-white p-4" style="background:linear-gradient(135deg,#2563eb,#1d4ed8)">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="small opacity-75 mb-1" id="tutPasso">Passo 1</div>
            <h4 class="mb-0 fw-bold" id="tutTitulo">Bem-vindo(a)!</h4>
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" title="Pular" aria-label="Pular"></button>
        </div>
      </div>
      <div class="modal-body p-4">
        <div class="text-center mb-3"><i id="tutIcone" class="bi bi-rocket-takeoff" style="font-size:3rem;color:#2563eb"></i></div>
        <p class="text-center mb-0" id="tutTexto" style="font-size:1.02rem;color:#374151;line-height:1.6"></p>
        <div id="tutAcao" class="text-center mt-3"></div>
        <div class="d-flex justify-content-center gap-2 mt-4" id="tutDots"></div>
      </div>
      <div class="modal-footer border-0 px-4 pb-4 pt-0">
        <button type="button" class="btn btn-link text-muted text-decoration-none me-auto" data-bs-dismiss="modal">Pular</button>
        <button type="button" class="btn btn-outline-secondary" id="tutVoltar">Voltar</button>
        <button type="button" class="btn btn-primary px-4" id="tutProximo">Próximo</button>
      </div>
    </div>
  </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const passos = [
    { icone: 'bi-rocket-takeoff', titulo: 'Bem-vindo(a) ao FixaOS! 👋',
      texto: 'Vamos dar os primeiros passos pra você começar com tudo funcionando. Leva 2 minutinhos.' },
    { icone: 'bi-building-gear', titulo: '1. Configure sua empresa',
      texto: 'Adicione o <b>logo</b>, o <b>endereço</b> e o <b>telefone</b> da sua assistência. Esses dados aparecem nas <b>impressões de OS</b> e no seu <b>perfil público</b> no diretório.',
      acao: { txt: 'Abrir Configurações', href: '<?= url('/empresa') ?>', icone: 'bi-gear' } },
    { icone: 'bi-file-earmark-text', titulo: '2. Revise os textos dos documentos',
      texto: 'Ainda em Configurações, confira os <b>textos de entrada e de garantia</b> que saem nos comprovantes — deixe com as regras da sua loja.' },
    { icone: 'bi-clipboard-plus', titulo: '3. Crie sua primeira OS',
      texto: 'Cadastre um cliente e abra sua primeira <b>Ordem de Serviço</b> — é o coração do sistema.',
      acao: { txt: 'Criar primeira OS', href: '<?= url('/os/nova') ?>', icone: 'bi-plus-lg' } },
    { icone: 'bi-check2-circle', titulo: 'Pronto pra começar! ✅',
      texto: 'Explore o menu: <b>Clientes</b>, <b>Estoque</b>, <b>PDV</b> e <b>Financeiro</b>. Precisou de ajuda? Tem o <b>Manual</b> no menu. Bom trabalho!' }
  ];
  const el = id => document.getElementById(id);
  const CSRF = '<?= csrf_token() ?>';
  let i = 0, marcado = false, modal = null;

  function marcarVisto() {
    if (marcado) return; marcado = true;
    fetch('<?= url('/tutorial/visto') ?>', { method: 'POST', headers: { 'X-CSRF-Token': CSRF }, keepalive: true }).catch(() => {});
  }

  el('tutDots').innerHTML = passos.map((_, k) => '<span class="tut-dot" data-k="' + k + '"></span>').join('');
  const st = document.createElement('style');
  st.textContent = '.tut-dot{width:8px;height:8px;border-radius:50%;background:#d1d5db;transition:.2s;display:inline-block}.tut-dot.on{background:#2563eb;width:22px;border-radius:4px}';
  document.head.appendChild(st);

  function render() {
    const p = passos[i];
    el('tutPasso').textContent = 'Passo ' + (i + 1) + ' de ' + passos.length;
    el('tutTitulo').innerHTML = p.titulo;
    el('tutIcone').className = 'bi ' + p.icone;
    el('tutTexto').innerHTML = p.texto;
    el('tutAcao').innerHTML = p.acao
      ? '<a href="' + p.acao.href + '" class="btn btn-lg btn-primary" id="tutLink"><i class="bi ' + p.acao.icone + ' me-1"></i>' + p.acao.txt + '</a>'
      : '';
    if (p.acao) el('tutLink').addEventListener('click', marcarVisto);
    el('tutVoltar').style.visibility = i === 0 ? 'hidden' : 'visible';
    el('tutProximo').innerHTML = i === passos.length - 1 ? '<i class="bi bi-check-lg"></i> Concluir' : 'Próximo <i class="bi bi-arrow-right"></i>';
    document.querySelectorAll('.tut-dot').forEach((d, k) => d.classList.toggle('on', k === i));
  }

  el('tutProximo').addEventListener('click', function () {
    if (i < passos.length - 1) { i++; render(); }
    else { marcarVisto(); if (modal) modal.hide(); }
  });
  el('tutVoltar').addEventListener('click', function () { if (i > 0) { i--; render(); } });
  el('modalTutorial').addEventListener('hidden.bs.modal', marcarVisto);

  render();

  let tries = 0;
  (function start() {
    if (!window.bootstrap || !window.bootstrap.Modal) {
      if (tries++ < 40) return setTimeout(start, 120);
      return;
    }
    modal = new bootstrap.Modal(el('modalTutorial'));
    modal.show();
  })();
});
</script>
<?php endif; ?>
