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
    <div class="fp-faint"><?= e($dataHojeLabel) ?> · perfil "<?= e($perfil['nome']) ?>"</div>
  </div>
  <nav class="fp-month-nav" aria-label="Navegar entre meses">
    <a href="<?= url('/financeiro-pessoal') ?>?mes=<?= e($mesAnteriorNav) ?>" class="fp-month-btn" aria-label="Mês anterior"><?= fp_icone('chevron-left') ?></a>
    <span class="fp-mono fp-month-label"><?= e(ucfirst($mesLabel)) ?></span>
    <a href="<?= url('/financeiro-pessoal') ?>?mes=<?= e($mesProximoNav) ?>" class="fp-month-btn" aria-label="Próximo mês"><?= fp_icone('chevron-right') ?></a>
  </nav>
</div>

<?php
  $catMaior = $resumo['maiorGasto']
      ? ($categorias[$resumo['maiorGasto']['categoria']] ?? ['nome' => financeiro_pessoal_categoria_humanizar($resumo['maiorGasto']['categoria']), 'cor' => 'var(--muted)'])
      : null;

  // Badge de variação % vs. mês anterior — mesmo componente pros 3 cards que comparam (Gasto/
  // Recebido/Saldo, pedido explícito: "comparação com o mês anterior também em Recebido e
  // Saldo", antes só existia em Gasto). $inverte=true pra Recebido/Saldo, onde SUBIR é bom
  // (verde), diferente de Gasto, onde subir é ruim (vermelho).
  $badgeVariacao = function (?int $pct, bool $inverte = false): string {
      if ($pct === null) return '';
      $ruim = $inverte ? $pct < 0 : $pct > 0;
      $cor = $ruim ? 'var(--exp)' : 'var(--inc)';
      $fundo = $ruim ? 'var(--expSoft)' : 'var(--incSoft)';
      return '<div style="display:inline-flex;align-items:center;gap:4px;background:' . $fundo . ';border-radius:999px;padding:3px 9px">'
          . '<span class="fp-mono" style="font-weight:700;font-size:.74rem;color:' . $cor . '">' . ($pct > 0 ? '+' : '') . $pct . '%</span>'
          . '</div>';
  };
?>

<div class="fp-dash-kpis">

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Gasto em <?= e($mesLabel) ?></div>
    <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap">
      <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:var(--exp)">R$ <?= number_format($resumo['gastoPagoMes'], 2, ',', '.') ?></div>
      <?= $badgeVariacao($resumo['variacaoGastoPct']) ?>
    </div>
    <?php if ($resumo['gastoAbertoMes'] > 0): ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">+ R$ <?= number_format($resumo['gastoAbertoMes'], 2, ',', '.') ?> em aberto</div>
    <?php endif; ?>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Recebido em <?= e($mesLabel) ?></div>
    <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap">
      <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:var(--inc)">R$ <?= number_format($resumo['recebidoPagoMes'], 2, ',', '.') ?></div>
      <?= $badgeVariacao($resumo['variacaoRecebidoPct'], true) ?>
    </div>
    <?php if ($resumo['recebidoAbertoMes'] > 0): ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">+ R$ <?= number_format($resumo['recebidoAbertoMes'], 2, ',', '.') ?> a receber</div>
    <?php endif; ?>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Saldo de <?= e($mesLabel) ?></div>
    <?php $saldoMesNeg = $resumo['saldoMesAtual'] < 0; ?>
    <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap">
      <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:<?= $saldoMesNeg ? 'var(--exp)' : 'var(--inc)' ?>"><?= $saldoMesNeg ? '−' : '' ?>R$ <?= number_format(abs($resumo['saldoMesAtual']), 2, ',', '.') ?></div>
      <?= $badgeVariacao($resumo['variacaoSaldoPct'], true) ?>
    </div>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">recebido (pago + a receber) − gasto (pago + em aberto)</div>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Saldo atual</div>
    <?php $saldoAtualNeg = $resumo['saldoAtual'] < 0; ?>
    <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:<?= $saldoAtualNeg ? 'var(--exp)' : 'var(--inc)' ?>"><?= $saldoAtualNeg ? '−' : '' ?>R$ <?= number_format(abs($resumo['saldoAtual']), 2, ',', '.') ?></div>
    <?php $saldoPrevNeg = $resumo['saldoPrevisto'] < 0; ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">previsto até fim do mês: <?= $saldoPrevNeg ? '−' : '' ?>R$ <?= number_format(abs($resumo['saldoPrevisto']), 2, ',', '.') ?></div>
  </div>

  <!-- Caixinhas (reserva de dinheiro pra guardar, ver CaixinhaService) — card inteiro é um
       link pra /financeiro-pessoal/caixinhas, mesmo padrão "card clicável" já usado noutros
       pontos do sistema (ex. card de destaque do Diretório). -->
  <a href="<?= url('/financeiro-pessoal/caixinhas') ?>" class="fp-card" style="text-decoration:none;color:inherit;display:block">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Guardado em caixinhas</div>
    <div class="fp-mono" style="font-weight:700;font-size:1.7rem;color:var(--accent)">R$ <?= number_format($resumo['caixinhasTotal'], 2, ',', '.') ?></div>
    <?php if ($resumo['caixinhasGuardadoMes'] != 0): ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">
      <?= $resumo['caixinhasGuardadoMes'] > 0 ? '+' : '−' ?> R$ <?= number_format(abs($resumo['caixinhasGuardadoMes']), 2, ',', '.') ?> em <?= e($mesLabel) ?>
    </div>
    <?php else: ?>
    <div class="fp-mono fp-muted" style="font-size:.74rem;margin-top:4px">Ver caixinhas →</div>
    <?php endif; ?>
  </a>

</div>

<div class="fp-dash-main">

  <div class="fp-card">
    <div class="fp-viz-head">
      <div>
        <div class="fp-viz-title">Seu mês, dia a dia</div>
        <div class="fp-viz-sub fp-muted"><?= e(ucfirst($mesLabel)) ?><?= $resumo['splitDia'] > 0 && $resumo['splitDia'] < count($resumo['serieDiaCompleta']) ? ' · projeção a partir de ' . sprintf('%02d', $resumo['splitDia'] + 1) . '/' . e($anoMesPartes[1]) : '' ?></div>
      </div>
      <div class="fp-viz-spacer"></div>
      <div class="fp-viz-legend" id="fpVizLegenda">
        <span><i class="fp-viz-key line" style="background:var(--saldo)"></i>Saldo</span>
        <span><i class="fp-viz-key" style="background:var(--entrada)"></i>Entrada</span>
        <span><i class="fp-viz-key" style="background:var(--gasto)"></i>Gasto pago</span>
        <span><i class="fp-viz-key" style="background:var(--gasto-aberto)"></i>Em aberto</span>
      </div>
      <div class="fp-viz-switch" role="group" aria-label="Formato">
        <button type="button" id="fpVizBtnGrafico" aria-pressed="true">Gráfico</button>
        <button type="button" id="fpVizBtnTabela" aria-pressed="false">Tabela</button>
      </div>
    </div>

    <div class="fp-viz-plot-wrap" id="fpVizPainelGrafico">
      <svg id="fpVizSvg" viewBox="0 0 980 412" role="img" aria-label="Saldo acumulado e movimentação diária de <?= e($mesLabel) ?>">
        <title>Saldo acumulado e movimentação diária — <?= e($mesLabel) ?></title>
      </svg>
      <div class="fp-viz-tip" id="fpVizTip"></div>
    </div>

    <div id="fpVizPainelTabela" hidden>
      <div class="fp-viz-tabela-caption fp-muted">Movimentação de <?= e($mesLabel) ?>. Valores em reais.</div>
      <div class="fp-viz-tabela-wrap">
        <table class="fp-viz-table">
          <thead>
            <tr><th scope="col">Dia</th><th scope="col">Entradas</th><th scope="col">Gasto pago</th><th scope="col">Em aberto</th><th scope="col">Saldo</th></tr>
          </thead>
          <tbody id="fpVizCorpoTabela"></tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="fp-card">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:14px">Por categoria (gasto do mês)</div>
    <?php if (!$resumo['porCategoria']): ?>
    <div class="fp-muted" style="font-size:.88rem;text-align:center;padding:12px 0">Nenhum gasto registrado neste mês ainda.</div>
    <?php else: ?>
    <?php $maiorValorCat = max($resumo['porCategoria']); $totalCat = array_sum($resumo['porCategoria']); ?>
    <div style="display:flex;flex-direction:column;gap:13px">
      <?php foreach ($resumo['porCategoria'] as $chave => $valor): ?>
      <?php
        $c   = $categorias[$chave] ?? ['nome' => financeiro_pessoal_categoria_humanizar($chave), 'cor' => 'var(--muted)'];
        $pct = $maiorValorCat > 0 ? round(($valor / $maiorValorCat) * 100) : 0;
        $pctDoTotal = $totalCat > 0 ? round(($valor / $totalCat) * 100) : 0;
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

<!-- Pedido do usuário: no lugar da lista completa (que já existe em /financeiro-pessoal/
     lancamentos, sem precisar duplicar modal/JS aqui de novo), duas listas compactas — o que
     está prestes a vencer e o que acabou de ser lançado — com link "Ver todos". -->
<div class="fp-dash-main" style="margin-top:20px">

  <div class="fp-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div class="fp-muted" style="font-size:.8rem">Próximos vencimentos</div>
      <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" class="fp-faint" style="font-size:.78rem;text-decoration:underline">Ver todos →</a>
    </div>
    <?php if (!$proximosVencimentos): ?>
    <div class="fp-muted" style="font-size:.88rem;text-align:center;padding:12px 0">Nada em aberto no momento 🎉</div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($proximosVencimentos as $l): ?>
      <?php
        $cat = $categorias[$l['categoria']] ?? ['nome' => financeiro_pessoal_categoria_humanizar($l['categoria']), 'cor' => 'var(--muted)'];
        $efetiva = $l['vencimento'] ?: substr($l['data_hora'], 0, 10);
        $vencido = $efetiva < date('Y-m-d');
        $tipoCor = $l['tipo'] === 'receita' ? 'var(--inc)' : 'var(--exp)';
      ?>
      <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit">
        <span style="width:8px;height:8px;border-radius:50%;background:<?= e($cat['cor']) ?>;flex:0 0 auto"></span>
        <div style="flex:1;min-width:0">
          <div style="font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($l['descricao']) ?></div>
          <div class="fp-faint fp-mono" style="font-size:.72rem;color:<?= $vencido ? 'var(--exp)' : null ?>"><?= $vencido ? 'Venceu ' : 'Vence ' ?><?= date_br($efetiva) ?></div>
        </div>
        <div class="fp-mono" style="font-weight:700;font-size:.86rem;color:<?= $tipoCor ?>"><?= $l['tipo'] === 'receita' ? '+' : '−' ?>R$ <?= number_format((float) $l['valor'], 2, ',', '.') ?></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <div class="fp-card">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
      <div class="fp-muted" style="font-size:.8rem">Últimos lançamentos</div>
      <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" class="fp-faint" style="font-size:.78rem;text-decoration:underline">Ver todos →</a>
    </div>
    <?php if (!$ultimosLancamentos): ?>
    <div class="fp-muted" style="font-size:.88rem;text-align:center;padding:12px 0">Nenhum lançamento ainda.</div>
    <?php else: ?>
    <div style="display:flex;flex-direction:column;gap:10px">
      <?php foreach ($ultimosLancamentos as $l): ?>
      <?php
        $cat = $categorias[$l['categoria']] ?? ['nome' => financeiro_pessoal_categoria_humanizar($l['categoria']), 'cor' => 'var(--muted)'];
        $tipoCor = $l['tipo'] === 'receita' ? 'var(--inc)' : 'var(--exp)';
        $pago = !empty($l['pago_em']);
      ?>
      <a href="<?= url('/financeiro-pessoal/lancamentos') ?>" style="display:flex;align-items:center;gap:10px;text-decoration:none;color:inherit">
        <span style="width:8px;height:8px;border-radius:50%;background:<?= e($cat['cor']) ?>;flex:0 0 auto"></span>
        <div style="flex:1;min-width:0">
          <div style="font-size:.88rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($l['descricao']) ?></div>
          <div class="fp-faint fp-mono" style="font-size:.72rem">
            <?= e($cat['nome']) ?> ·
            <?= date_br($l['data_hora'], !empty($l['hora_informada'])) ?>
            <?= $pago ? '· <span style="color:var(--inc)">Pago</span>' : '' ?>
          </div>
        </div>
        <div class="fp-mono" style="font-weight:700;font-size:.86rem;color:<?= $tipoCor ?>"><?= $l['tipo'] === 'receita' ? '+' : '−' ?>R$ <?= number_format((float) $l['valor'], 2, ',', '.') ?></div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
/* "Seu mês, dia a dia" — SVG puro, sem biblioteca (Chart.js achatava todo gasto numa barra
   de 1-2px quando havia uma entrada grande no mês — escala única não serve pra grandezas tão
   diferentes). Cor via var(--token) direto no atributo fill/stroke do SVG — o navegador
   resolve isso ao vivo, então a troca de tema (fx-theme-change) não precisa recolorir nada
   daqui na mão, diferente do Chart.js (que baqueava hex num canvas). */
(function () {
  var dados = <?= json_encode($resumo['serieDiaCompleta']) ?>;
  var splitDia = <?= (int) $resumo['splitDia'] ?>;
  var maiorEntrada = <?= json_encode($resumo['maiorEntrada']) ?>;
  var mesLabelExtenso = <?= json_encode($mesesPt[(int) $anoMesPartes[1]]) ?>;
  var svg = document.getElementById('fpVizSvg');
  if (!svg || !dados.length) return;

  var diasNoMes = dados.length;
  // "Hoje" só é um marco real quando o mês navegado é o atual (0 < splitDia < diasNoMes) —
  // mês inteiramente passado (splitDia=diasNoMes) ou futuro (splitDia=0) não ganha o ponto/
  // linha de "hoje", só o traço sólido (passado) ou tracejado (futuro) já basta.
  var mostrarHoje = splitDia > 0 && splitDia < diasNoMes;

  var brl = function (n) { return n.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' }); };
  var curto = function (n) { return Math.abs(n) >= 1000 ? (n / 1000).toFixed(1).replace('.', ',') + 'k' : n.toFixed(0); };

  /* ------------------------------------------------------------------ geometria (porta fiel
     da referência validada — grafico-mes.html) */
  var L = 58, R = 20, W = 980;
  var TOPO = { y: 34, h: 186 };
  var BASE = { y: 268, h: 112 };
  var faixa = (W - L - R) / diasNoMes;
  var cx = function (d) { return L + faixa * (d - 0.5); };

  var saldos = dados.map(function (p) { return p.saldo; });
  var sMin = Math.min.apply(null, saldos), sMax = Math.max.apply(null, saldos);
  var folga = (sMax - sMin) * 0.22 || 1;
  var sLo = sMin - folga, sHi = sMax + folga;
  var ys = function (v) { return TOPO.y + TOPO.h - ((v - sLo) / (sHi - sLo)) * TOPO.h; };

  // Entrada é evento raro e grande, gasto é muitos e pequenos — cada um normalizado pelo
  // próprio máximo, em painéis/proporções distintas, pra nenhum achatar o outro.
  var maxEntrada = Math.max.apply(null, dados.map(function (p) { return p.entrada; }).concat([1]));
  var maxGasto = Math.max.apply(null, dados.map(function (p) { return p.pago + p.aberto; }).concat([1]));
  var acima = BASE.h * 0.38, abaixo = BASE.h * 0.62;
  var zeroY = BASE.y + acima;
  var yEntrada = function (v) { return acima * (v / maxEntrada) * 0.92; };
  var yGasto = function (v) { return abaixo * (v / maxGasto) * 0.92; };

  var NS = 'http://www.w3.org/2000/svg';
  var el = function (tag, attrs, texto) {
    var n = document.createElementNS(NS, tag);
    for (var k in attrs) { n.setAttribute(k, attrs[k]); }
    if (texto !== undefined) { n.textContent = texto; }
    svg.appendChild(n);
    return n;
  };

  /** Retângulo com as duas pontas de fora arredondadas, ancorado na linha zero. */
  function barra(x, y, w, h, r, sentido) {
    r = Math.min(r, w / 2, h);
    return sentido === 'cima'
      ? 'M' + x + ',' + (y + h) + ' L' + x + ',' + (y + r) + ' Q' + x + ',' + y + ' ' + (x + r) + ',' + y + ' L' + (x + w - r) + ',' + y + ' Q' + (x + w) + ',' + y + ' ' + (x + w) + ',' + (y + r) + ' L' + (x + w) + ',' + (y + h) + ' Z'
      : 'M' + x + ',' + y + ' L' + (x + w) + ',' + y + ' L' + (x + w) + ',' + (y + h - r) + ' Q' + (x + w) + ',' + (y + h) + ' ' + (x + w - r) + ',' + (y + h) + ' L' + (x + r) + ',' + (y + h) + ' Q' + x + ',' + (y + h) + ' ' + x + ',' + (y + h - r) + ' Z';
  }

  /* ------------------------------------------------------------- painel 1: saldo acumulado */
  el('text', { x: L, y: 18, class: 'fp-viz-band-label' }, 'SALDO ACUMULADO');

  for (var i = 0; i <= 3; i++) {
    var v = sLo + ((sHi - sLo) / 3) * i;
    var y = ys(v);
    el('line', { x1: L, x2: W - R, y1: y, y2: y, class: 'fp-viz-grid-line' });
    el('text', { x: L - 10, y: y + 4, 'text-anchor': 'end', class: 'fp-viz-tick' }, 'R$ ' + curto(v));
  }

  var ate = dados.filter(function (p) { return !p.futuro; });
  var dep = dados.filter(function (p) { return p.futuro; });
  var linha = function (pts) {
    return pts.map(function (p, i) { return (i ? 'L' : 'M') + cx(p.dia).toFixed(1) + ' ' + ys(p.saldo).toFixed(1); }).join(' ');
  };

  if (ate.length) {
    var baseY = TOPO.y + TOPO.h;
    var ultimoAteDia = ate[ate.length - 1].dia;
    // área preenchida só sob o trecho já realizado — o projetado não merece o mesmo peso visual
    el('path', {
      d: linha(ate) + ' L ' + cx(ultimoAteDia).toFixed(1) + ' ' + baseY + ' L ' + cx(1).toFixed(1) + ' ' + baseY + ' Z',
      fill: 'var(--saldo-fill)'
    });
    el('path', { d: linha(ate), fill: 'none', stroke: 'var(--saldo)', 'stroke-width': 2.5, 'stroke-linejoin': 'round', 'stroke-linecap': 'round' });
  }
  if (dep.length) {
    // M seguido de outro par de coordenadas (sem comando) é lineto implícito — mesmo truque
    // da referência: liga o trecho tracejado exatamente onde o sólido termina, sem costura.
    var pontoPartida = ate.length ? ate[ate.length - 1] : dep[0];
    el('path', {
      d: 'M ' + cx(pontoPartida.dia) + ' ' + ys(pontoPartida.saldo) + ' ' + linha(dep).slice(1),
      fill: 'none', stroke: 'var(--saldo)', 'stroke-width': 2, 'stroke-dasharray': '5 4', 'stroke-linecap': 'round', opacity: .62
    });
  }

  // marca cada entrada na linha — é onde o salário vira degrau, não paredão
  dados.filter(function (p) { return p.entrada > 0; }).forEach(function (p) {
    el('circle', { cx: cx(p.dia), cy: ys(p.saldo), r: 5.5, fill: 'var(--entrada)', stroke: 'var(--surf)', 'stroke-width': 2 });
  });

  // 3 rótulos diretos, exatamente: maior entrada, saldo de hoje (ou início, sem "hoje" no mês),
  // saldo previsto no fim do mês navegado — nenhum outro valor impresso no gráfico.
  if (maiorEntrada) {
    var pMe = dados[maiorEntrada.dia - 1];
    el('text', { x: cx(pMe.dia) + 10, y: ys(pMe.saldo) - 24, class: 'fp-viz-rotulo' }, '+' + brl(maiorEntrada.valor));
    el('text', { x: cx(pMe.dia) + 10, y: ys(pMe.saldo) - 11, class: 'fp-viz-rotulo-cx' }, maiorEntrada.descricao);
  }

  if (mostrarHoje) {
    var pHoje = dados[splitDia - 1];
    el('circle', { cx: cx(splitDia), cy: ys(pHoje.saldo), r: 5, fill: 'var(--saldo)', stroke: 'var(--surf)', 'stroke-width': 2 });
    el('text', { x: cx(splitDia) - 10, y: ys(pHoje.saldo) + 17, 'text-anchor': 'end', class: 'fp-viz-rotulo-cx' }, 'HOJE');
    el('text', { x: cx(splitDia) - 10, y: ys(pHoje.saldo) + 32, 'text-anchor': 'end', class: 'fp-viz-rotulo' }, brl(pHoje.saldo));
  }

  var pFim = dados[diasNoMes - 1];
  el('circle', { cx: cx(diasNoMes), cy: ys(pFim.saldo), r: 4.5, fill: 'var(--surf)', stroke: 'var(--saldo)', 'stroke-width': 2.5 });
  el('text', { x: cx(diasNoMes), y: ys(pFim.saldo) - 21, 'text-anchor': 'end', class: 'fp-viz-rotulo-cx' }, mostrarHoje ? 'PREVISTO' : 'SALDO FINAL');
  el('text', { x: cx(diasNoMes), y: ys(pFim.saldo) - 7, 'text-anchor': 'end', class: 'fp-viz-rotulo' }, brl(pFim.saldo));

  /* ------------------------------------------------------- painel 2: movimento diário */
  el('text', { x: L, y: BASE.y - 14, class: 'fp-viz-band-label' }, 'ENTRADAS E GASTOS DO DIA');
  el('line', { x1: L, x2: W - R, y1: zeroY, y2: zeroY, class: 'fp-viz-zero-line' });

  var larg = Math.min(13, faixa - 7);
  var raio = 3;

  dados.forEach(function (p) {
    var x = cx(p.dia) - larg / 2;

    if (p.entrada > 0) {
      var hE = Math.max(3, yEntrada(p.entrada));
      el('path', { d: barra(x, zeroY - hE, larg, hE, raio, 'cima'), fill: 'var(--entrada)' });
    }

    var total = p.pago + p.aberto;
    if (total > 0) {
      var yAtual = zeroY;
      if (p.pago > 0) {
        var hP = Math.max(2.5, yGasto(p.pago));
        el('path', { d: barra(x, yAtual, larg, hP, p.aberto > 0 ? 0 : raio, 'baixo'), fill: 'var(--gasto)' });
        yAtual += hP + (p.aberto > 0 ? 2 : 0); // 2px de respiro entre as faixas, nunca borda
      }
      if (p.aberto > 0) {
        var hA = Math.max(2.5, yGasto(p.aberto));
        el('path', { d: barra(x, yAtual, larg, hA, raio, 'baixo'), fill: 'var(--gasto-aberto)' });
      }
    }
  });

  /* --------------------------------------------------------------- eixo e linha de "hoje" */
  dados.forEach(function (p) {
    if (p.dia % 2 === 0 || p.dia === 1 || p.dia === splitDia) {
      el('text', {
        x: cx(p.dia), y: BASE.y + BASE.h + 20, 'text-anchor': 'middle',
        class: 'fp-viz-axis-day' + (mostrarHoje && p.dia === splitDia ? ' hoje' : '')
      }, p.dia);
    }
  });

  if (mostrarHoje) {
    var xHoje = cx(splitDia) + faixa / 2;
    el('line', { x1: xHoje, x2: xHoje, y1: TOPO.y - 10, y2: BASE.y + BASE.h + 4, class: 'fp-viz-hoje-rule' });
    el('text', { x: xHoje + 6, y: TOPO.y - 14, class: 'fp-viz-hoje-chip' }, 'PROJETADO →');
  }

  /* ------------------------------------------------------------------------------- hover */
  var cross = el('line', { class: 'fp-viz-crosshair', y1: TOPO.y - 10, y2: BASE.y + BASE.h + 4 });
  var tip = document.getElementById('fpVizTip');

  var hit = el('rect', { x: L, y: TOPO.y - 14, width: W - L - R, height: BASE.y + BASE.h + 18 - TOPO.y, class: 'fp-viz-hit' });

  hit.addEventListener('mousemove', function (ev) {
    var caixa = svg.getBoundingClientRect();
    var escala = W / caixa.width;
    var x = (ev.clientX - caixa.left) * escala;
    var dia = Math.min(diasNoMes, Math.max(1, Math.ceil((x - L) / faixa)));
    var p = dados[dia - 1];

    cross.setAttribute('x1', cx(dia));
    cross.setAttribute('x2', cx(dia));
    cross.style.opacity = .35;

    var linhas = [];
    if (p.entrada) linhas.push('<div class="l"><span><i style="background:var(--entrada)"></i>Entrada</span><span class="v">' + brl(p.entrada) + '</span></div>');
    if (p.pago) linhas.push('<div class="l"><span><i style="background:var(--gasto)"></i>Gasto pago</span><span class="v">' + brl(p.pago) + '</span></div>');
    if (p.aberto) linhas.push('<div class="l"><span><i style="background:var(--gasto-aberto)"></i>Em aberto</span><span class="v">' + brl(p.aberto) + '</span></div>');
    if (!linhas.length) linhas.push('<div class="l" style="opacity:.6">Sem movimento</div>');
    linhas.push('<div class="l" style="margin-top:6px;padding-top:6px;border-top:1px solid var(--line)"><span><i style="background:var(--saldo)"></i>Saldo' + (p.futuro ? ' previsto' : '') + '</span><span class="v">' + brl(p.saldo) + '</span></div>');

    tip.innerHTML = '<b>' + String(dia).padStart(2, '0') + ' de ' + mesLabelExtenso + '</b>' + linhas.join('');
    tip.style.opacity = 1;
    tip.style.left = (cx(dia) / escala) + 'px';
    tip.style.top = (ys(p.saldo) / escala - 14) + 'px';
  });

  hit.addEventListener('mouseleave', function () {
    cross.style.opacity = 0;
    tip.style.opacity = 0;
  });

  /* ------------------------------------------------------------------------------- tabela */
  var corpo = document.getElementById('fpVizCorpoTabela');
  if (corpo) {
    corpo.innerHTML = dados.map(function (p) {
      return '<tr>'
        + '<td>' + String(p.dia).padStart(2, '0') + (mostrarHoje && p.dia === splitDia ? ' · hoje' : '') + '</td>'
        + '<td>' + (p.entrada ? brl(p.entrada) : '—') + '</td>'
        + '<td>' + (p.pago ? brl(p.pago) : '—') + '</td>'
        + '<td>' + (p.aberto ? brl(p.aberto) : '—') + '</td>'
        + '<td>' + brl(p.saldo) + (p.futuro ? ' *' : '') + '</td>'
        + '</tr>';
    }).join('');
  }

  var bg = document.getElementById('fpVizBtnGrafico');
  var bt = document.getElementById('fpVizBtnTabela');
  var pg = document.getElementById('fpVizPainelGrafico');
  var pt = document.getElementById('fpVizPainelTabela');
  var lg = document.getElementById('fpVizLegenda');

  function mostrar(grafico) {
    pg.hidden = !grafico; pt.hidden = grafico; if (lg) lg.hidden = !grafico;
    bg.setAttribute('aria-pressed', String(grafico));
    bt.setAttribute('aria-pressed', String(!grafico));
  }
  if (bg && bt) {
    bg.addEventListener('click', function () { mostrar(true); });
    bt.addEventListener('click', function () { mostrar(false); });
  }
})();
</script>

<?php endif; ?>
