<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$diasPt = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];

$anoMesPartes = explode('-', $mes);
$mesLabel = $mesesPt[(int) $anoMesPartes[1]] . ' de ' . $anoMesPartes[0];
$dataHojeLabel = $diasPt[(int) date('w')] . ', ' . date('j') . ' de ' . $mesesPt[(int) date('n')];
?>

<?php if (!$liberado): ?>
<div class="fp-card" style="text-align:center;padding:40px 24px">
  <div style="font-size:2.2rem;margin-bottom:10px">🔒</div>
  <h1 style="font-size:1.15rem;margin:0 0 8px">Financeiro pessoal ainda não está liberado</h1>
  <p class="fp-muted" style="font-size:.9rem;line-height:1.5;margin:0 0 20px">
    Esse recurso é liberado pra empresas que <strong>reivindicaram</strong> a ficha no Diretório
    e estão nos planos <strong>Oficina</strong> ou <strong>Top Empresa</strong>. Fale com a FixaOS
    se quiser saber mais.
  </p>
  <div style="display:flex;flex-direction:column;gap:10px;max-width:280px;margin:0 auto">
    <a href="<?= url('/logout') ?>" class="fp-btn fp-btn-primary" style="text-decoration:none;display:inline-block">
      Entrar com outra conta
    </a>
    <a href="<?= url('/assistencias') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none;display:inline-block">
      Ver o Diretório
    </a>
  </div>
</div>

<?php else: ?>

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting"><?= e($saudacao) ?></h1>
    <div class="fp-faint"><?= e($dataHojeLabel) ?></div>
  </div>
  <nav class="fp-month-nav" aria-label="Navegar entre meses">
    <a href="<?= url('/financeiro-pessoal') ?>?mes=<?= e($mesAnteriorNav) ?>" class="fp-month-btn" aria-label="Mês anterior"><?= fp_icone('chevron-left') ?></a>
    <span class="fp-mono fp-month-label"><?= e(ucfirst($mesLabel)) ?></span>
    <a href="<?= url('/financeiro-pessoal') ?>?mes=<?= e($mesProximoNav) ?>" class="fp-month-btn" aria-label="Próximo mês"><?= fp_icone('chevron-right') ?></a>
  </nav>
</div>

<?php
  // Resumo (ex-tela própria /financeiro-pessoal/dashboard, "o dashboard vai ficar no lugar
  // dela" — pedido do usuário) — $resumo/$mesLabel já vêm computados pro $mes navegado desta
  // mesma página (ver FinanceiroPessoalController::index()), nenhum cálculo de data duplicado
  // aqui.
  $catMaior = $resumo['maiorGasto']
      ? ($categorias[$resumo['maiorGasto']['categoria']] ?? ['nome' => $resumo['maiorGasto']['categoria'], 'cor' => 'var(--muted)'])
      : null;
  $saldoNegativo = $resumo['saldoMes'] < 0;
?>

<div class="fp-dash-kpis">

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Gasto em <?= e($mesLabel) ?></div>
    <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap">
      <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:var(--exp)">R$ <?= number_format($resumo['totalMes'], 2, ',', '.') ?></div>
      <?php if ($resumo['variacaoPct'] !== null): ?>
      <?php $subiu = $resumo['variacaoPct'] > 0; ?>
      <div style="display:flex;align-items:center;gap:4px;background:<?= $subiu ? 'var(--expSoft)' : 'var(--incSoft)' ?>;border-radius:999px;padding:3px 9px">
        <span class="fp-mono" style="font-weight:700;font-size:.74rem;color:<?= $subiu ? 'var(--exp)' : 'var(--inc)' ?>"><?= $subiu ? '+' : '' ?><?= $resumo['variacaoPct'] ?>%</span>
      </div>
      <?php endif; ?>
    </div>
    <?php if ($resumo['totalMesAnterior'] > 0): ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">vs. R$ <?= number_format($resumo['totalMesAnterior'], 2, ',', '.') ?> no mês passado</div>
    <?php endif; ?>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Recebido em <?= e($mesLabel) ?></div>
    <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:var(--inc)">R$ <?= number_format($resumo['totalReceitas'], 2, ',', '.') ?></div>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px"><?= $resumo['qtdLancamentos'] ?> lançamento<?= $resumo['qtdLancamentos'] === 1 ? '' : 's' ?> no mês</div>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Saldo do mês</div>
    <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:<?= $saldoNegativo ? 'var(--exp)' : 'var(--inc)' ?>"><?= $saldoNegativo ? '−' : '' ?>R$ <?= number_format(abs($resumo['saldoMes']), 2, ',', '.') ?></div>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">recebido − gasto</div>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Maior gasto do mês</div>
    <?php if ($catMaior): ?>
    <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:var(--exp)">R$ <?= number_format($resumo['maiorGasto']['valor'], 2, ',', '.') ?></div>
    <div style="display:flex;align-items:center;gap:6px;margin-top:4px;min-width:0">
      <span style="width:8px;height:8px;border-radius:50%;background:<?= e($catMaior['cor']) ?>;flex:0 0 auto"></span>
      <span style="font-size:.78rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($resumo['maiorGasto']['descricao']) ?></span>
    </div>
    <?php else: ?>
    <div class="fp-muted" style="font-size:.88rem;padding:8px 0">Nenhum gasto ainda.</div>
    <?php endif; ?>
  </div>

</div>

<div class="fp-dash-main">

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:10px">Seu mês, dia a dia</div>
    <div class="fp-dash-chart"><canvas id="fpChartDias"></canvas></div>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:14px">Por categoria</div>
    <?php if (!$resumo['porCategoria']): ?>
    <div class="fp-muted" style="font-size:.88rem;text-align:center;padding:12px 0">Nenhum gasto registrado neste mês ainda.</div>
    <?php else: ?>
    <?php $maiorValorCat = max($resumo['porCategoria']); ?>
    <div style="display:flex;flex-direction:column;gap:13px">
      <?php foreach ($resumo['porCategoria'] as $chave => $valor): ?>
      <?php
        $c   = $categorias[$chave] ?? ['nome' => $chave, 'cor' => 'var(--muted)'];
        $pct = $maiorValorCat > 0 ? round(($valor / $maiorValorCat) * 100) : 0;
        $pctDoTotal = $resumo['totalMes'] > 0 ? round(($valor / $resumo['totalMes']) * 100) : 0;
      ?>
      <div>
        <div style="display:flex;align-items:center;gap:9px;margin-bottom:6px">
          <span style="width:8px;height:8px;border-radius:50%;background:<?= e($c['cor']) ?>;flex:0 0 auto"></span>
          <span style="flex:1;font-size:.88rem"><?= e($c['nome']) ?></span>
          <span class="fp-mono fp-muted" style="font-size:.76rem"><?= $pctDoTotal ?>%</span>
          <span class="fp-mono" style="font-size:.86rem;font-weight:700;min-width:70px;text-align:right">R$ <?= number_format($valor, 2, ',', '.') ?></span>
        </div>
        <div style="height:6px;border-radius:3px;background:var(--line);overflow:hidden">
          <div style="width:<?= $pct ?>%;height:100%;background:<?= e($c['cor']) ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
  var ctx = document.getElementById('fpChartDias');
  if (!ctx || typeof Chart === 'undefined') return;
  var serie = <?= json_encode($resumo['serieDias']) ?>;
  var labels = serie.map(function (_, i) { return i + 1; });

  // Canvas não lê custom property via var() — resolve o valor real a cada vez que o tema
  // muda, pra barra/eixos/grade continuarem com a cor certa do tema ativo, não só na carga
  // inicial da página.
  function corToken(nome, fallback) {
    var v = getComputedStyle(document.documentElement).getPropertyValue(nome).trim();
    return v || fallback;
  }

  var chart = new Chart(ctx, {
    type: 'bar',
    // Barra na mesma cor de "despesa" do resto da tela (não mais o laranja de marca) — a
    // série é só gasto por dia, então reforça o mesmo sistema de cores em vez de usar um
    // terceiro tom.
    data: { labels: labels, datasets: [{ data: serie, backgroundColor: corToken('--exp', '#B83A29'), borderRadius: 2 }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return 'R$ ' + c.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }); } } } },
      scales: {
        x: { ticks: { color: corToken('--muted', '#625470'), font: { size: 10 } }, grid: { display: false } },
        y: { beginAtZero: true, ticks: { color: corToken('--muted', '#625470'), font: { size: 10 } }, grid: { color: corToken('--line', 'rgba(30,19,38,.10)') } }
      }
    }
  });

  window.addEventListener('fx-theme-change', function () {
    chart.data.datasets[0].backgroundColor = corToken('--exp', '#B83A29');
    chart.options.scales.x.ticks.color = corToken('--muted', '#625470');
    chart.options.scales.y.ticks.color = corToken('--muted', '#625470');
    chart.options.scales.y.grid.color = corToken('--line', 'rgba(30,19,38,.10)');
    chart.update();
  });
})();
</script>

<?php
  $saldoMesInicial = $totalReceitas - $totalMes;
  $saldoPositivoInicial = $saldoMesInicial >= 0;
  $pctGastoInicial = $totalReceitas > 0 ? (int) round(($totalMes / $totalReceitas) * 100) : null;
?>
<div class="fp-kpis-3">
  <div class="fp-card fp-card-saldo">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;gap:8px">
      <span>Saldo de <?= e($mesLabel) ?></span>
      <span id="fpSaldoBadge" class="fp-badge <?= $saldoPositivoInicial ? 'fp-badge-inc' : 'fp-badge-exp' ?>"><?= $saldoPositivoInicial ? 'Positivo' : 'Negativo' ?></span>
    </div>
    <div id="fpSaldo" class="fp-mono fp-saldo-num" style="color:<?= $saldoPositivoInicial ? 'var(--inc)' : 'var(--exp)' ?>">
      <?= $saldoPositivoInicial ? '' : '−' ?>R$ <?= number_format(abs($saldoMesInicial), 2, ',', '.') ?>
    </div>
    <div class="fp-bar-track" style="margin-top:12px">
      <div id="fpSaldoBar" class="fp-bar-fill" style="width:<?= $pctGastoInicial !== null ? min(100, max(0, $pctGastoInicial)) : 0 ?>%"></div>
    </div>
    <div id="fpSaldoCaption" class="fp-faint" style="font-size:.76rem;margin-top:6px">
      <?= $pctGastoInicial !== null ? 'Você gastou ' . $pctGastoInicial . '% do que entrou neste mês' : 'Nenhuma entrada registrada neste mês ainda' ?>
    </div>
  </div>
  <div class="fp-card">
    <div class="fp-muted" style="font-size:.78rem;margin-bottom:4px">Entrada em <?= e($mesLabel) ?></div>
    <div id="fpTotalReceitas" class="fp-mono" style="font-weight:700;font-size:1.55rem;color:var(--inc)" data-valor="<?= (float) $totalReceitas ?>">
      R$ <?= number_format($totalReceitas, 2, ',', '.') ?>
    </div>
  </div>
  <div class="fp-card">
    <div class="fp-muted" style="font-size:.78rem;margin-bottom:4px">Saída em <?= e($mesLabel) ?></div>
    <div id="fpTotalMes" class="fp-mono" style="font-weight:700;font-size:1.55rem;color:var(--exp)" data-valor="<?= (float) $totalMes ?>">
      R$ <?= number_format($totalMes, 2, ',', '.') ?>
    </div>
  </div>
</div>

<!-- Sem gatilho próprio de "criar" nesta tela (pedido do usuário: sem Adicionar/Escanear
     conta/Contas e débitos aqui) — este modal só abre mais pelo botão "Editar" de um
     lançamento já existente (ver iniciarEdicao() no script). Mesmos ids de sempre dentro do
     form (fpForm/fpTipo*/fpDescricao/fpValor/fpCategoria/fpMsg/fpBtnSalvar/fpEditandoAviso/
     fpCancelarEdicao) — toda a lógica de JS de salvar/cancelar edição continua igual. -->
<div class="fp-modal-backdrop" id="modalLancamento">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Lançamento</strong>
      <button type="button" class="fp-modal-close" id="btnFecharLancamento" aria-label="Fechar">×</button>
    </div>
    <form id="fpForm" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <div id="fpEditandoAviso" class="fp-mono" style="display:none;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--accent);background:var(--accentSoft);border:1px solid var(--accentLine);border-radius:10px;padding:8px 12px">
        <span>✎ Editando lançamento</span>
        <a href="#" id="fpCancelarEdicao" style="color:var(--muted);text-decoration:underline">cancelar</a>
      </div>
      <div style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-primary" id="fpTipoDespesa" data-tipo="despesa" style="flex:1">Gasto</button>
        <button type="button" class="fp-btn fp-btn-ghost" id="fpTipoReceita" data-tipo="receita" style="flex:1">Entrada</button>
      </div>
      <input type="hidden" name="tipo" id="fpTipo" value="despesa">

      <input type="text" name="descricao" id="fpDescricao" class="fp-input" placeholder="Descrição (ex.: Supermercado)" maxlength="150" required>

      <div class="fp-row-valor-cat">
        <input type="number" name="valor" id="fpValor" class="fp-input" placeholder="Valor (R$)" step="0.01" min="0.01" style="flex:1" required>
        <select name="categoria" id="fpCategoria" class="fp-select" style="flex:1">
          <?php foreach ($categorias as $chave => $c): ?>
          <option value="<?= e($chave) ?>"><?= e($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div id="fpMsg" class="fp-muted" style="font-size:.82rem"></div>

      <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvar">Adicionar lançamento</button>
    </form>
  </div>
</div>

<section class="fp-lanc-col" aria-labelledby="fpLancTitulo">
  <div style="display:flex;gap:10px;align-items:flex-start">
    <div class="fp-filtros">
      <button type="button" class="fp-filtro-btn active" data-filtro="todos" title="Todos"><?= fp_icone('list-ul') ?><span>Todos</span></button>
      <button type="button" class="fp-filtro-btn" data-filtro="receita" title="Entradas"><?= fp_icone('arrow-down-circle-fill') ?><span>Entradas</span></button>
      <button type="button" class="fp-filtro-btn" data-filtro="despesa" title="Saídas"><?= fp_icone('arrow-up-circle-fill') ?><span>Saídas</span></button>
    </div>
    <div style="flex:1;min-width:0">
      <h2 id="fpLancTitulo" class="fp-section-titulo" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.03em;margin:0 0 8px">Lançamentos</h2>
      <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
    </div>
  </div>
</section>

<script>
(function () {
  var CATS = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
  var MES_SELECIONADO = <?= json_encode($mes) ?>;
  // Ícone usado dentro do HTML montado via JS (renderLista()) — mesmo fp_icone() do PHP, sem
  // CDN (área isolada do resto do FixaOS, só o login é compartilhado).
  var FP_SVG = {
    'chevron-down': <?= json_encode(fp_icone('chevron-down')) ?>
  };
  var lista = document.getElementById('fpLista');
  var totalDespesaEl = document.getElementById('fpTotalMes');
  var totalReceitaEl = document.getElementById('fpTotalReceitas');
  var saldoEl = document.getElementById('fpSaldo');
  var saldoBadgeEl = document.getElementById('fpSaldoBadge');
  var saldoBarEl = document.getElementById('fpSaldoBar');
  var saldoCaptionEl = document.getElementById('fpSaldoCaption');
  var modalLancamento = document.getElementById('modalLancamento');
  var form = document.getElementById('fpForm');
  var msg = document.getElementById('fpMsg');
  var btnSalvar = document.getElementById('fpBtnSalvar');
  var tipoHidden = document.getElementById('fpTipo');
  var btnDespesa = document.getElementById('fpTipoDespesa');
  var btnReceita = document.getElementById('fpTipoReceita');
  var csrfToken = '<?= csrf_token() ?>';
  var lancamentosAtuais = [];
  var filtroAtivo = 'todos';
  var editandoId = null;
  // Estado de colapso de cada lançamento (card individual, não um grupo) — só client-side
  // (não persiste entre recargas, nada foi pedido sobre lembrar), chave id do lançamento.
  var lancColapsados = {};
  var editandoAviso = document.getElementById('fpEditandoAviso');
  var TEXTO_SALVAR_NOVO = 'Adicionar lançamento';
  var TEXTO_SALVAR_EDICAO = 'Salvar alterações';

  document.querySelectorAll('.fp-filtro-btn').forEach(function (btn) {
    btn.onclick = function () {
      filtroAtivo = btn.dataset.filtro;
      document.querySelectorAll('.fp-filtro-btn').forEach(function (b) { b.classList.toggle('active', b === btn); });
      aplicarFiltroEExibir();
    };
  });

  function marcarTipo(tipo) {
    tipoHidden.value = tipo;
    btnDespesa.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-ghost');
    btnReceita.className = 'fp-btn ' + (tipo === 'receita' ? 'fp-btn-receita' : 'fp-btn-ghost');
    btnSalvar.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-receita');
  }
  btnDespesa.onclick = function () { marcarTipo('despesa'); };
  btnReceita.onclick = function () { marcarTipo('receita'); };

  function fmtValor(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function fmtData(dt) {
    var d = new Date(dt.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dt;
    return d.toLocaleDateString('pt-BR') + ' · ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }
  function fmtDataCurta(dt) {
    // dt no formato YYYY-MM-DD (vencimento de item) — "Dia DD", sem depender de Date()/fuso.
    var p = dt.split('-');
    return p.length === 3 ? p[2] : dt;
  }
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  function mesAtualStr() {
    return MES_SELECIONADO;
  }

  // Os 3 KPIs (Saldo/Entrada/Saída) são sempre a soma do MÊS SELECIONADO (não "hoje"),
  // independente do filtro lateral escolhido na lista de lançamentos.
  function atualizarTotais() {
    var mesAtual = mesAtualStr();
    var despesas = 0;
    var receitas = 0;
    lancamentosAtuais.forEach(function (l) {
      if (l.data_hora.slice(0, 7) !== mesAtual) return;
      if (l.tipo === 'despesa') despesas += parseFloat(l.valor);
      else receitas += parseFloat(l.valor);
    });
    totalDespesaEl.textContent = fmtValor(despesas);
    totalReceitaEl.textContent = fmtValor(receitas);
    var saldo = receitas - despesas;
    var saldoNeg = saldo < 0;
    saldoEl.textContent = (saldoNeg ? '−' : '') + fmtValor(Math.abs(saldo));
    saldoEl.style.color = saldoNeg ? 'var(--exp)' : 'var(--inc)';
    saldoBadgeEl.textContent = saldoNeg ? 'Negativo' : 'Positivo';
    saldoBadgeEl.className = 'fp-badge ' + (saldoNeg ? 'fp-badge-exp' : 'fp-badge-inc');
    if (receitas > 0) {
      var pct = Math.round((despesas / receitas) * 100);
      saldoBarEl.style.width = Math.min(100, Math.max(0, pct)) + '%';
      saldoCaptionEl.textContent = 'Você gastou ' + pct + '% do que entrou neste mês';
    } else {
      saldoBarEl.style.width = '0%';
      saldoCaptionEl.textContent = 'Nenhuma entrada registrada neste mês ainda';
    }
  }

  var MSG_VAZIO = {
    todos: 'Nenhum lançamento em ' + '<?= e($mesLabel) ?>' + ' ainda — adicione o primeiro acima.',
    receita: 'Nenhuma entrada em ' + '<?= e($mesLabel) ?>' + ' ainda.',
    despesa: 'Nenhuma saída em ' + '<?= e($mesLabel) ?>' + ' ainda.'
  };

  function aplicarFiltroEExibir() {
    var mesAtual = mesAtualStr();
    var filtrados = lancamentosAtuais.filter(function (l) {
      if (l.data_hora.slice(0, 7) !== mesAtual) return false;
      if (filtroAtivo === 'todos') return true;
      return l.tipo === filtroAtivo;
    });
    renderLista(filtrados);
  }

  function renderLista(lancamentos) {
    lista.innerHTML = '';
    if (!lancamentos.length) {
      lista.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;font-size:.88rem">' + MSG_VAZIO[filtroAtivo] + '</div>';
      return;
    }

    lancamentos.forEach(function (l) {
      var cat = CATS[l.categoria] || { nome: l.categoria, cor: 'var(--muted)' };
      var tipoCor = l.tipo === 'receita' ? 'var(--inc)' : 'var(--exp)';
      var aberto = !!lancColapsados[l.id];

      var card = document.createElement('div');
      card.className = 'fp-lanc-card';
      card.style.borderLeft = '3px solid ' + tipoCor;

      var header = document.createElement('div');
      header.className = 'fp-lanc-header';
      header.innerHTML =
        '<span style="width:10px;height:10px;border-radius:50%;background:' + cat.cor + ';flex:0 0 auto"></span>' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(l.descricao) + (l.origem === 'ocr' ? ' <span class="fp-chip fp-chip-muted" style="margin-left:4px">OCR</span>' : '') + '</div>' +
          '<div class="fp-faint fp-mono" style="font-size:.74rem;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(cat.nome) + ' · ' + fmtData(l.data_hora) + (l.origem === 'foto' ? ' · 📷' : '') + '</div>' +
        '</div>' +
        '<div class="fp-mono" style="font-weight:700;font-size:.95rem;color:' + tipoCor + '">' +
          (l.tipo === 'receita' ? '+' : '−') + fmtValor(l.valor) +
        '</div>' +
        '<span class="fp-lanc-chevron" aria-hidden="true" style="transform:rotate(' + (aberto ? '180' : '0') + 'deg)">' + FP_SVG['chevron-down'] + '</span>';
      // Editar/excluir/categoria saíram do cabeçalho e foram pro corpo colapsável (pedido do
      // usuário) — o header não tem mais botão nenhum dentro dele, então o clique inteiro
      // alterna expandir/recolher sem precisar checar o que foi clicado.
      header.onclick = function () {
        lancColapsados[l.id] = !aberto;
        renderLista(lancamentos);
      };
      card.appendChild(header);

      var corpo = document.createElement('div');
      corpo.className = 'fp-lanc-corpo' + (aberto ? ' show' : '');
      corpo.innerHTML =
        '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding-top:10px;border-top:1px solid var(--line)">' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-edit" data-id="' + l.id + '">Editar</button>' +
          '<a href="<?= url('/financeiro-pessoal/categorias') ?>" class="fp-btn fp-btn-ghost fp-btn-sm" style="flex:1;min-width:150px;text-decoration:none;text-align:center">Criar ou editar categoria</a>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-del" data-id="' + l.id + '" style="color:var(--exp)">Excluir</button>' +
        '</div>';
      card.appendChild(corpo);

      lista.appendChild(card);
    });

    lista.querySelectorAll('.fp-del').forEach(function (btn) {
      btn.onclick = function () { excluir(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-edit').forEach(function (btn) {
      btn.onclick = function () { iniciarEdicao(btn.dataset.id); };
    });
  }

  function iniciarEdicao(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    editandoId = id;
    marcarTipo(l.tipo);
    document.getElementById('fpDescricao').value = l.descricao;
    document.getElementById('fpValor').value = l.valor;
    document.getElementById('fpCategoria').value = l.categoria;
    editandoAviso.style.display = 'flex';
    btnSalvar.textContent = TEXTO_SALVAR_EDICAO;
    msg.textContent = '';
    abrirModal(modalLancamento);
  }

  function cancelarEdicao() {
    editandoId = null;
    form.reset();
    marcarTipo('despesa');
    editandoAviso.style.display = 'none';
    btnSalvar.textContent = TEXTO_SALVAR_NOVO;
    msg.textContent = '';
    fecharModal(modalLancamento);
  }

  document.getElementById('fpCancelarEdicao').onclick = function (ev) {
    ev.preventDefault();
    cancelarEdicao();
  };

  // Único gatilho restante deste modal é "Editar" (iniciarEdicao(), já abre ele) — o "×"
  // só precisa fechar do mesmo jeito que cancelar edição faria.
  document.getElementById('btnFecharLancamento').onclick = function () {
    cancelarEdicao();
  };

  function carregar() {
    fetch('<?= url('/api/financeiro-pessoal') ?>?mes=' + encodeURIComponent(MES_SELECIONADO))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        lancamentosAtuais = j.lancamentos;
        atualizarTotais();
        aplicarFiltroEExibir();
      });
  }

  function excluir(id) {
    if (!confirm('Excluir esse lançamento?')) return;
    fetch('<?= url('/financeiro-pessoal') ?>/' + id + '/excluir', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken }
    }).then(function () { carregar(); });
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var descricao = document.getElementById('fpDescricao').value.trim();
    var valor = document.getElementById('fpValor').value;
    if (!descricao || !valor || parseFloat(valor) <= 0) {
      msg.innerHTML = '<span style="color:var(--exp)">Preencha descrição e um valor válido.</span>';
      return;
    }
    var emEdicao = editandoId !== null;
    btnSalvar.disabled = true;
    var orig = btnSalvar.textContent;
    btnSalvar.textContent = 'Salvando...';
    msg.textContent = '';

    var saveUrl = emEdicao
      ? '<?= url('/financeiro-pessoal') ?>/' + editandoId + '/atualizar'
      : '<?= url('/financeiro-pessoal') ?>';

    fetch(saveUrl, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({
        tipo: tipoHidden.value,
        categoria: document.getElementById('fpCategoria').value,
        descricao: descricao,
        valor: valor
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnSalvar.disabled = false;
        if (!j.ok) {
          btnSalvar.textContent = orig;
          msg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>';
          return;
        }
        if (emEdicao) {
          cancelarEdicao();
        } else {
          form.reset();
          marcarTipo('despesa');
          btnSalvar.textContent = orig;
          fecharModal(modalLancamento);
        }
        carregar();
      })
      .catch(function () {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        msg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  marcarTipo('despesa');
  lancamentosAtuais = <?= json_encode($lancamentos, JSON_UNESCAPED_UNICODE) ?>;
  atualizarTotais();
  aplicarFiltroEExibir();

  // Abrir/fechar modal (CSS puro, sem Bootstrap JS nesta área isolada) — usado pelo
  // modal de lançamento (iniciarEdicao()/cancelarEdicao()/btnFecharLancamento).
  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }
})();
</script>
<?php endif; ?>
