<?php
/**
 * Dashboard — redesenhado para o sistema de temas (claro/escuro/automático).
 * Escopo: só esta view. Cores só por --token (public/css/tokens.css).
 */

// ── Dados já existentes, sem novas consultas de negócio ────────────────
$osEmAberto   = (int) ($resumo['os_em_aberto_total'] ?? 0);
$atrasadas    = (int) ($resumo['os_atrasadas'] ?? 0);
$concluidas   = (int) ($resumo['os_concluidas'] ?? 0);
$totalMes     = (int) ($resumo['total_os_mes'] ?? 0);
$totalClientes= (int) ($resumo['total_clientes'] ?? 0);
$alertasEstq  = (int) ($resumo['alertas_estoque'] ?? 0);
$vencidoFin   = (float) ($resumo['fin_vencido'] ?? 0);

// "Prontos p/ retirada" = OS cujo status tem tipo 'concluida' (bate com a lista "OS por status").
// Reaproveita $resumo['por_status'], que já foi buscado — nenhuma consulta nova.
$prontos = 0;
foreach (($resumo['por_status'] ?? []) as $st) {
    if (($st['tipo'] ?? '') === 'concluida') $prontos += (int) $st['total'];
}

// Tela vazia de verdade: nenhuma OS foi criada ainda (não é "sem OS este mês").
$telaVazia = empty($ultimasOS) && $osEmAberto === 0 && $totalMes === 0;

// Data de início do financeiro (corte), se configurada — pra não deixar implícito
// por que "Faturado"/"A receber" podem estar zerados. Leitura simples, sem alterar
// a consulta original do resumo financeiro.
$financeiroInicio = null;
try {
    $stF = \App\Core\DB::pdo()->prepare("SELECT financeiro_inicio FROM empresas WHERE id = ?");
    $stF->execute([\App\Core\Auth::empresaId()]);
    $v = (string) ($stF->fetchColumn() ?: '');
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) $financeiroInicio = date_br($v);
} catch (\Throwable $e) { /* card some sem a informação — não é crítico */ }

// Agrupamento de "OS por status" nos 5 significados do novo design.
// tipo → cor: aberta=cinza, em_andamento=azul, aguardando=âmbar, concluida=verde, cancelada=vermelho.
// tipo 'entregue' (Fechado) sai da lista principal e vira uma linha de rodapé.
$corPorTipo = [
    'aberta'       => 'var(--text-3)',
    'em_andamento' => 'var(--accent)',
    'aguardando'   => 'var(--warning-fill)',
    'concluida'    => 'var(--success-fill)',
    'cancelada'    => 'var(--danger-fill)',
];
$statusAtivos = [];
$fechadoTotal = 0;
foreach (($resumo['por_status'] ?? []) as $st) {
    if (($st['tipo'] ?? '') === 'entregue') { $fechadoTotal += (int) $st['total']; continue; }
    if (!$st['total']) continue;
    $statusAtivos[] = $st;
}
$totalAtivoStatus = array_sum(array_column($statusAtivos, 'total')) ?: 1;

// Cabeçalho da página — data por extenso em pt-BR (atualiza no cliente, como o relógio da topbar).
$diasSemanaPt = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
$mesesPt      = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
$agora = new \DateTime('now');
$dataHeaderInicial = $diasSemanaPt[(int) $agora->format('w')] . ', ' . (int) $agora->format('j') . ' de ' . $mesesPt[(int) $agora->format('n') - 1] . ' · ' . $agora->format('H:i');
?>
<div class="fx-dash">
<style>
/* Escopo do dashboard: nada aqui usa hex fora de --token (public/css/tokens.css).
   Também neutraliza a preferência "texto em maiúsculas" nesta tela — caixa alta
   apaga o contorno das palavras, o que vai contra a legibilidade que este
   redesenho busca (ver relatório). As outras telas continuam respeitando a
   preferência do usuário normalmente. */
.fx-dash, .fx-dash * { text-transform: none !important; }

.fx-dash-head { display:flex; align-items:flex-end; justify-content:space-between; flex-wrap:wrap; gap:.75rem; margin-bottom:1.1rem; }
.fx-dash-title { font-size:18px; font-weight:700; color:var(--text-1); margin:0; }
.fx-dash-sub   { font-size:12px; color:var(--text-3); margin-top:2px; }

.fx-kpi-row { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:.75rem; }
@media (max-width:900px) { .fx-kpi-row { grid-template-columns:repeat(2,1fr); } }

.fx-kpi { background:var(--surface-1); border:1px solid var(--border); border-radius:var(--radius-lg); padding:14px 16px; }
.fx-kpi-label { font-size:12.5px; font-weight:600; color:var(--text-3); margin-bottom:4px; }
.fx-kpi-value { font-size:26px; font-weight:800; color:var(--text-1); line-height:1.15; }
.fx-kpi-value .fx-kpi-value-muted { font-size:15px; font-weight:600; color:var(--text-3); }
.fx-kpi-sub { font-size:12px; color:var(--text-3); margin-top:2px; }
.fx-kpi-danger  { border-left:3px solid var(--danger-fill); background:var(--danger-bg); }
.fx-kpi-danger  .fx-kpi-value { color:var(--danger-fill); }
.fx-kpi-success { border-left:3px solid var(--success-fill); background:var(--success-bg); }
.fx-kpi-success .fx-kpi-value { color:var(--success-fill); }
.fx-kpi-accent  { border-left:3px solid var(--accent); background:var(--accent-bg); }
.fx-kpi-accent  .fx-kpi-value { color:var(--accent); }
.fx-kpi-warning { border-left:3px solid var(--warning-fill); background:var(--warning-bg); }
.fx-kpi-warning .fx-kpi-value { color:var(--warning-fill); }

.fx-kpi-row-secondary .fx-kpi-value { font-size:18px; font-weight:700; }
.fx-kpi-row-secondary .fx-kpi-label { font-size:11.5px; }

.fx-card { background:var(--surface-1); border:1px solid var(--border); border-radius:var(--radius-lg); overflow:hidden; }
.fx-card-head { padding:.85rem 1.1rem; font-weight:600; font-size:.92rem; color:var(--text-1); border-bottom:1px solid var(--border); display:flex; align-items:center; justify-content:space-between; gap:.5rem; flex-wrap:wrap; }
.fx-card-body { padding:1rem 1.1rem; }
.fx-card-body.p-0 { padding:0; }

.fx-status-row { display:block; padding:.6rem 1.1rem; text-decoration:none; color:inherit; border-bottom:1px solid var(--border); }
.fx-status-row:last-child { border-bottom:none; }
.fx-status-row:hover { background:var(--surface-2); }
.fx-status-top { display:flex; align-items:center; justify-content:space-between; gap:.5rem; margin-bottom:5px; }
.fx-status-name { display:flex; align-items:center; gap:8px; font-size:.85rem; color:var(--text-1); font-weight:500; }
.fx-status-dot { width:9px; height:9px; border-radius:50%; flex-shrink:0; }
.fx-status-count { font-size:.85rem; font-weight:700; color:var(--text-1); }
.fx-status-bar-track { height:4px; border-radius:2px; background:var(--surface-2); overflow:hidden; }
.fx-status-bar-fill { height:100%; border-radius:2px; }
.fx-status-footer { padding:.6rem 1.1rem; font-size:.78rem; color:var(--text-3); border-top:1px solid var(--border); background:var(--surface-2); }
.fx-status-footer a { color:inherit; text-decoration:none; }
.fx-status-footer a:hover { color:var(--text-1); }

.fx-flow-box { background:var(--surface-2); border-radius:var(--radius); padding:.6rem; text-align:center; height:100%; }
.fx-flow-box .lbl { font-size:.78rem; color:var(--text-3); }
.fx-flow-box .val { font-weight:800; font-size:1.4rem; line-height:1.15; color:var(--text-1); }
.fx-flow-box .sub { font-size:.68rem; color:var(--text-3); margin-top:2px; }
.fx-flow-accent .val { color:var(--accent); }
.fx-flow-success .val { color:var(--success); }

.fx-table { width:100%; font-size:.85rem; color:var(--text-1); border-collapse:collapse; }
.fx-table thead th { text-align:left; font-weight:600; color:var(--text-3); font-size:.75rem; padding:.55rem .9rem; border-bottom:1px solid var(--border); }
.fx-table tbody td { padding:.6rem .9rem; border-bottom:1px solid var(--border); vertical-align:middle; }
.fx-table tbody tr:last-child td { border-bottom:none; }
.fx-table tbody tr:hover { background:var(--surface-2); }
.fx-muted { color:var(--text-3); }

.fx-empty { text-align:center; padding:3.5rem 1.5rem; }
.fx-empty i { font-size:2.6rem; color:var(--text-4); }
.fx-empty h5 { color:var(--text-1); font-weight:700; margin:.9rem 0 .35rem; }
.fx-empty p { color:var(--text-3); max-width:420px; margin:0 auto 1.1rem; }
</style>

<div class="fx-dash-head">
  <div>
    <h1 class="fx-dash-title">Dashboard</h1>
    <div class="fx-dash-sub" id="fxDashData"><?= e($dataHeaderInicial) ?></div>
  </div>
</div>

<?php if ($telaVazia): ?>

<div class="fx-card">
  <div class="fx-empty">
    <i class="bi bi-clipboard2-pulse"></i>
    <h5>Ainda não há nenhuma Ordem de Serviço</h5>
    <p>Os números e gráficos deste painel aparecem automaticamente conforme você vai abrindo e fechando OS. Que tal criar a primeira agora?</p>
    <a href="<?= url('/os/nova') ?>" class="btn btn-primary fw-semibold"><i class="bi bi-plus-lg me-1"></i>Abrir minha primeira OS</a>
  </div>
</div>

<?php else: ?>

<!-- ── Fileira primária: o que exige ação hoje ── -->
<div class="fx-kpi-row">
  <div class="fx-kpi fx-kpi-accent">
    <div class="fx-kpi-label">OS em aberto</div>
    <div class="fx-kpi-value"><?= number_format($osEmAberto) ?></div>
  </div>
  <div class="fx-kpi fx-kpi-danger">
    <div class="fx-kpi-label">Atrasadas</div>
    <div class="fx-kpi-value"><?= number_format($atrasadas) ?></div>
  </div>
  <div class="fx-kpi fx-kpi-success">
    <div class="fx-kpi-label">Concluídas no mês</div>
    <div class="fx-kpi-value"><?= number_format($concluidas) ?> <span class="fx-kpi-value-muted">/ <?= number_format($totalMes) ?></span></div>
  </div>
  <div class="fx-kpi fx-kpi-success">
    <div class="fx-kpi-label">Prontos p/ retirada</div>
    <div class="fx-kpi-value"><?= number_format($prontos) ?></div>
  </div>
</div>

<!-- ── Fileira secundária: contexto ── -->
<div class="fx-kpi-row fx-kpi-row-secondary">
  <div class="fx-kpi fx-kpi-success">
    <div class="fx-kpi-label">Faturado no mês</div>
    <div class="fx-kpi-value"><?= money($resumo['faturamento_mes'] ?? 0) ?></div>
    <?php if ($financeiroInicio): ?><div class="fx-kpi-sub">desde <?= e($financeiroInicio) ?></div><?php endif; ?>
  </div>
  <div class="fx-kpi fx-kpi-warning">
    <div class="fx-kpi-label">A receber</div>
    <div class="fx-kpi-value"><?= money($resumo['a_receber'] ?? 0) ?></div>
    <?php if ($vencidoFin > 0): ?><div class="fx-kpi-sub" style="color:var(--danger)">vencido: <?= money($vencidoFin) ?></div>
    <?php else: ?><div class="fx-kpi-sub">de OS já fechadas</div><?php endif; ?>
  </div>
  <div class="fx-kpi fx-kpi-warning">
    <div class="fx-kpi-label">Estoque em mínimo</div>
    <div class="fx-kpi-value"><?= number_format($alertasEstq) ?></div>
    <div class="fx-kpi-sub">produto(s)</div>
  </div>
  <div class="fx-kpi fx-kpi-accent">
    <div class="fx-kpi-label">Clientes</div>
    <div class="fx-kpi-value"><?= number_format($totalClientes) ?></div>
    <div class="fx-kpi-sub">+<?= (int) ($resumo['novos_clientes_mes'] ?? 0) ?> este mês</div>
  </div>
</div>

<!-- ── Gráfico + Status ── -->
<div class="row g-3 mb-3">

  <div class="col-lg-8">
    <div class="fx-card h-100">
      <div class="fx-card-head">
        <span><i class="bi bi-graph-up-arrow me-2" style="color:var(--accent)"></i>Ordens de serviço</span>
      </div>
      <div class="fx-card-body">
        <canvas id="chartOsMes" height="110"></canvas>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="fx-card h-100">
      <div class="fx-card-head">OS por status</div>
      <div class="fx-card-body p-0">
        <?php if (!$statusAtivos): ?>
          <div class="p-3 text-center fx-muted small">Nenhuma OS ativa.</div>
        <?php else: foreach ($statusAtivos as $st):
          $pct = round(((int) $st['total'] / $totalAtivoStatus) * 100);
          $cor = $corPorTipo[$st['tipo']] ?? 'var(--text-3)';
        ?>
        <a class="fx-status-row" href="<?= url('/os?status_id=' . (int) $st['id']) ?>">
          <div class="fx-status-top">
            <span class="fx-status-name"><span class="fx-status-dot" style="background:<?= $cor ?>"></span><?= e($st['nome']) ?></span>
            <span class="fx-status-count"><?= (int) $st['total'] ?></span>
          </div>
          <div class="fx-status-bar-track"><div class="fx-status-bar-fill" style="width:<?= $pct ?>%;background:<?= $cor ?>"></div></div>
        </a>
        <?php endforeach; endif; ?>
      </div>
      <?php if ($fechadoTotal > 0): ?>
      <div class="fx-status-footer">
        <a href="<?= url('/os?fechadas=1') ?>">Fechado · <?= $fechadoTotal ?> (fora do total ativo)</a>
      </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<!-- ── Fluxo de OS: Entradas × Saídas ── -->
<?php $fr = $fluxoResumo ?? []; $acumulando = ($fr['ent_30d'] ?? 0) > ($fr['sai_30d'] ?? 0); ?>
<div class="row g-3 mb-3">
  <div class="col-12">
    <div class="fx-card">
      <div class="fx-card-head">
        <span><i class="bi bi-arrow-left-right me-2" style="color:var(--accent)"></i>Fluxo de OS <span class="fx-muted fw-normal">— entradas × saídas (30 dias)</span></span>
        <span class="badge" style="background:<?= $acumulando ? 'var(--warning-bg)' : 'var(--success-bg)' ?>;color:<?= $acumulando ? 'var(--warning)' : 'var(--success)' ?>">
          <?= $acumulando ? 'Entrando mais do que saindo' : 'Dando conta do fluxo' ?>
        </span>
      </div>
      <div class="fx-card-body">
        <div class="row g-2 mb-3">
          <div class="col-6 col-md">
            <div class="fx-flow-box fx-flow-accent">
              <div class="lbl"><i class="bi bi-box-arrow-in-down"></i> Entradas</div>
              <div class="val"><?= $fr['ent_30d'] ?? 0 ?></div>
              <div class="sub">hoje <?= $fr['ent_hoje'] ?? 0 ?> · 7d <?= $fr['ent_7d'] ?? 0 ?></div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="fx-flow-box fx-flow-success">
              <div class="lbl"><i class="bi bi-box-arrow-up"></i> Concluídas</div>
              <div class="val"><?= $fr['sai_30d'] ?? 0 ?></div>
              <div class="sub">hoje <?= $fr['sai_hoje'] ?? 0 ?> · 7d <?= $fr['sai_7d'] ?? 0 ?></div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="fx-flow-box fx-flow-success">
              <div class="lbl"><i class="bi bi-bag-check"></i> Prontas p/ retirar</div>
              <div class="val"><?= $fr['prontas_retirar'] ?? 0 ?></div>
              <div class="sub">aguardando o cliente</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="fx-flow-box">
              <div class="lbl"><i class="bi bi-tools"></i> Em aberto</div>
              <div class="val"><?= $fr['em_aberto'] ?? 0 ?></div>
              <div class="sub">na oficina</div>
            </div>
          </div>
          <div class="col-6 col-md">
            <div class="fx-flow-box">
              <div class="lbl"><i class="bi bi-clock-history"></i> Tempo médio</div>
              <div class="val"><?= (isset($fr['tempo_medio']) && $fr['tempo_medio'] !== null) ? $fr['tempo_medio'] . ' d' : '—' ?></div>
              <div class="sub">entrada → conclusão</div>
            </div>
          </div>
        </div>
        <canvas id="chartFluxo" height="90"></canvas>
      </div>
    </div>
  </div>
</div>

<!-- ── Últimas OS + Top Serviços ── -->
<div class="row g-3 mb-3">

  <div class="col-lg-8">
    <div class="fx-card">
      <div class="fx-card-head">
        <span>Últimas ordens de serviço</span>
        <a href="<?= url('/os/nova') ?>" class="btn btn-primary btn-sm fw-semibold"><i class="bi bi-plus-lg me-1"></i>Nova OS</a>
      </div>
      <div class="fx-card-body p-0" style="overflow-x:auto">
        <table class="fx-table">
          <thead><tr><th>Nº</th><th>Cliente</th><th>Equipamento</th><th>Status</th><th>Valor</th><th></th></tr></thead>
          <tbody>
            <?php foreach ($ultimasOS as $os): ?>
            <tr>
              <td class="fw-bold">OS <?= e($os['numero']) ?></td>
              <td><?= e($os['cliente_nome'] ?? '—') ?></td>
              <td>
                <div><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?: '—' ?></div>
                <div class="fx-muted" style="font-size:.72rem"><?= e($os['equip_tipo'] ?? '') ?></div>
              </td>
              <td><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '', $os['status_cor_fonte'] ?? '#ffffff') ?></td>
              <td class="fw-semibold"><?= money($os['valor_total']) ?></td>
              <td><a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="fx-card h-100">
      <div class="fx-card-head">Top serviços</div>
      <div class="fx-card-body p-0">
        <?php if (!$topServicos): ?>
        <div class="p-3 text-center fx-muted small">Nenhum serviço ainda.</div>
        <?php else: foreach ($topServicos as $i => $s): ?>
        <div class="fx-status-row" style="cursor:default">
          <div class="d-flex align-items-center gap-2">
            <span class="badge rounded-pill" style="background:var(--accent-bg);color:var(--accent-text);min-width:22px"><?= $i + 1 ?></span>
            <div class="flex-grow-1 min-w-0">
              <div class="small fw-semibold text-truncate" style="color:var(--text-1)"><?= e($s['descricao']) ?></div>
              <div class="fx-muted" style="font-size:.72rem"><?= $s['vezes'] ?>x · <?= money($s['receita']) ?></div>
            </div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

</div>

<!-- ── OS atrasadas/vencendo ── -->
<?php if (!empty($vencendo)): ?>
<div class="fx-card mb-3" style="border-left:3px solid var(--danger-fill)">
  <div class="fx-card-head" style="color:var(--danger)"><i class="bi bi-alarm-fill me-2"></i><?= count($vencendo) ?> OS com prazo vencido</div>
  <div class="fx-card-body p-0" style="overflow-x:auto">
    <table class="fx-table">
      <thead><tr><th>Nº</th><th>Cliente</th><th>Previsão</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($vencendo as $os): ?>
        <tr>
          <td class="fw-bold">OS <?= e($os['numero']) ?></td>
          <td><?= e($os['cliente_nome'] ?? '—') ?></td>
          <td style="color:var(--danger)" class="fw-semibold"><?= date_br($os['data_previsao'], true) ?></td>
          <td><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '', $os['status_cor_fonte'] ?? '#ffffff') ?></td>
          <td><a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-danger"><i class="bi bi-eye"></i></a></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Agenda hoje -->
<?php if ($agenda): ?>
<div class="fx-card">
  <div class="fx-card-head"><i class="bi bi-calendar3 me-2" style="color:var(--accent)"></i>Agenda de hoje</div>
  <div class="fx-card-body p-0">
    <?php foreach ($agenda as $ev): ?>
    <div class="fx-status-row" style="cursor:default">
      <div class="d-flex gap-3 align-items-center">
        <span class="badge rounded-pill" style="background:var(--accent-bg);color:var(--accent-text)"><?= date('H:i', strtotime($ev['data_inicio'])) ?></span>
        <div>
          <div class="fw-semibold small" style="color:var(--text-1)"><?= e($ev['titulo']) ?></div>
          <?php if ($ev['cliente_nome']): ?><div class="fx-muted" style="font-size:.75rem"><?= e($ev['cliente_nome']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php endif; // fim do "else" da tela vazia ?>

<script>
(function () {
  // ── Data do cabeçalho (mantém "viva", como o relógio da topbar) ──
  var diasSemanaPt = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];
  var mesesPt = ['janeiro','fevereiro','março','abril','maio','junho','julho','agosto','setembro','outubro','novembro','dezembro'];
  function atualizarDataHeader() {
    var el = document.getElementById('fxDashData');
    if (!el) return;
    var n = new Date();
    var hh = String(n.getHours()).padStart(2, '0'), mm = String(n.getMinutes()).padStart(2, '0');
    el.textContent = diasSemanaPt[n.getDay()] + ', ' + n.getDate() + ' de ' + mesesPt[n.getMonth()] + ' · ' + hh + ':' + mm;
  }
  setInterval(atualizarDataHeader, 30000);

  <?php if (!$telaVazia): ?>
  // ── Gráficos: cores lidas dos tokens de tema, recriados quando o tema muda ──
  function fxVar(name) { return getComputedStyle(document.documentElement).getPropertyValue(name).trim(); }

  var osMesDataFull = <?= json_encode($osPorMes ?? []) ?>;
  var fluxoData = <?= json_encode($fluxoDiario ?? []) ?>;
  var chartOsMes = null, chartFluxo = null;

  function osMesJanela() {
    // Se há menos de 6 meses com movimento, corta os meses zerados do início —
    // não faz sentido ocupar 83% do gráfico com histórico que não existe.
    var comMovimento = osMesDataFull.filter(function (d) { return (d.entradas || 0) > 0 || (d.concluidas || 0) > 0; });
    if (comMovimento.length === 0 || comMovimento.length >= 6) return osMesDataFull.slice();
    var i0 = osMesDataFull.findIndex(function (d) { return (d.entradas || 0) > 0 || (d.concluidas || 0) > 0; });
    return osMesDataFull.slice(i0);
  }

  function renderCharts() {
    if (typeof Chart === 'undefined') return;

    var corTexto2 = fxVar('--text-2'), corTexto3 = fxVar('--text-3'), corBorda = fxVar('--border');
    var corSurface1 = fxVar('--surface-1'), corTexto1 = fxVar('--text-1');
    var corAccent = fxVar('--accent'), corSuccess = fxVar('--success-fill');

    // ── OS por mês: barras agrupadas (contagem mensal não pede interpolação) ──
    var cv1 = document.getElementById('chartOsMes');
    if (cv1) {
      if (chartOsMes) chartOsMes.destroy();
      var dados = osMesJanela();
      var idxAtual = dados.length - 1; // o último mês da janela é sempre o mês corrente (parcial)
      chartOsMes = new Chart(cv1, {
        type: 'bar',
        data: {
          labels: dados.map(function (d) { return d.label; }),
          datasets: [
            {
              label: 'Recebidas',
              data: dados.map(function (d) { return d.entradas; }),
              backgroundColor: dados.map(function (d, i) { return i === idxAtual ? corAccent + '55' : corAccent; }),
              borderColor: corAccent,
              borderWidth: function (ctx) { return ctx.dataIndex === idxAtual ? 2 : 0; },
              borderDash: function (ctx) { return ctx.dataIndex === idxAtual ? [4, 3] : []; },
              borderRadius: 4,
              maxBarThickness: 28
            },
            {
              label: 'Concluídas',
              data: dados.map(function (d) { return d.concluidas; }),
              backgroundColor: dados.map(function (d, i) { return i === idxAtual ? corSuccess + '55' : corSuccess; }),
              borderColor: corSuccess,
              borderWidth: function (ctx) { return ctx.dataIndex === idxAtual ? 2 : 0; },
              borderDash: function (ctx) { return ctx.dataIndex === idxAtual ? [4, 3] : []; },
              borderRadius: 4,
              maxBarThickness: 28
            }
          ]
        },
        options: {
          responsive: true,
          interaction: { mode: 'index', intersect: false },
          plugins: {
            legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 8, padding: 16, color: corTexto2 } },
            tooltip: {
              backgroundColor: corSurface1, titleColor: corTexto1, bodyColor: corTexto2,
              borderColor: corBorda, borderWidth: 1, padding: 10,
              callbacks: {
                afterTitle: function (items) { return items[0].dataIndex === idxAtual ? 'mês em curso (parcial)' : ''; }
              }
            }
          },
          scales: {
            y: { beginAtZero: true, ticks: { precision: 0, color: corTexto3 }, grid: { color: corBorda } },
            x: { ticks: { color: corTexto3 }, grid: { display: false } }
          }
        }
      });
    }

    // ── Fluxo diário (30 dias) ──
    var cv2 = document.getElementById('chartFluxo');
    if (cv2) {
      if (chartFluxo) chartFluxo.destroy();
      chartFluxo = new Chart(cv2, {
        type: 'bar',
        data: {
          labels: fluxoData.map(function (d) { return d.label; }),
          datasets: [
            { label: 'Entradas', data: fluxoData.map(function (d) { return d.entradas; }), backgroundColor: corAccent, borderRadius: 3 },
            { label: 'Concluídas', data: fluxoData.map(function (d) { return d.saidas; }), backgroundColor: corSuccess, borderRadius: 3 }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: { position: 'top', labels: { color: corTexto2 } },
            tooltip: { backgroundColor: corSurface1, titleColor: corTexto1, bodyColor: corTexto2, borderColor: corBorda, borderWidth: 1 }
          },
          scales: {
            x: { ticks: { maxTicksLimit: 15, color: corTexto3 }, grid: { display: false } },
            y: { beginAtZero: true, ticks: { precision: 0, stepSize: 1, color: corTexto3 }, grid: { color: corBorda } }
          }
        }
      });
    }
  }

  if (typeof Chart !== 'undefined') renderCharts();
  else window.addEventListener('load', renderCharts);
  window.addEventListener('fx-theme-change', renderCharts);
  <?php endif; ?>
})();
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

</div>
