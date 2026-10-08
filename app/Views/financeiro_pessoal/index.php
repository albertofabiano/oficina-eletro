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
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:10px">Seu mês, dia a dia — pago e em aberto, gasto e entrada</div>
    <div class="fp-dash-chart"><canvas id="fpChartDias"></canvas></div>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
  var ctx = document.getElementById('fpChartDias');
  if (!ctx || typeof Chart === 'undefined') return;
  var seriePago = <?= json_encode($resumo['serieDiasPago']) ?>;
  var serieAberto = <?= json_encode($resumo['serieDiasAberto']) ?>;
  var seriePagoReceita = <?= json_encode($resumo['serieDiasPagoReceita']) ?>;
  var serieAbertoReceita = <?= json_encode($resumo['serieDiasAbertoReceita']) ?>;
  var labels = seriePago.map(function (_, i) { return i + 1; });
  var hojeDia = <?= (int) date('j') ?>;
  var mesEhAtual = <?= json_encode(substr($resumo['hoje'], 0, 7) === $mes) ?>;

  function corToken(nome, fallback) {
    var v = getComputedStyle(document.documentElement).getPropertyValue(nome).trim();
    return v || fallback;
  }

  // Dia no FUTURO (só existe quando o mês navegado É o atual) pinta o "em aberto" com um tom
  // mais claro/translúcido — pedido explícito ("barras de dias futuros em tom diferente").
  function corAberto(corBase) {
    return labels.map(function (dia) {
      var futuro = mesEhAtual && dia > hojeDia;
      return futuro ? corBase + '55' : corBase + 'AA';
    });
  }

  // 2 pilhas lado a lado por dia ("stack": 'despesa'/'receita') — cada uma soma pago+aberto
  // (pedido: "entradas também no gráfico", sem perder a separação pago/aberto já pedida pro
  // gasto).
  var chart = new Chart(ctx, {
    type: 'bar',
    data: {
      labels: labels,
      datasets: [
        { label: 'Gasto pago', data: seriePago, backgroundColor: corToken('--exp', '#B83A29'), stack: 'despesa', borderRadius: 2 },
        { label: 'Gasto em aberto', data: serieAberto, backgroundColor: corAberto(corToken('--exp', '#B83A29')), stack: 'despesa', borderRadius: 2 },
        { label: 'Entrada paga', data: seriePagoReceita, backgroundColor: corToken('--inc', '#0B7A54'), stack: 'receita', borderRadius: 2 },
        { label: 'Entrada em aberto', data: serieAbertoReceita, backgroundColor: corAberto(corToken('--inc', '#0B7A54')), stack: 'receita', borderRadius: 2 }
      ]
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: {
        legend: { display: true, position: 'bottom', labels: { color: corToken('--muted', '#625470'), boxWidth: 10, font: { size: 10 } } },
        tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': R$ ' + c.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }); } } }
      },
      scales: {
        x: { stacked: true, ticks: { color: corToken('--muted', '#625470'), font: { size: 10 } }, grid: { display: false } },
        y: { stacked: true, beginAtZero: true, ticks: { color: corToken('--muted', '#625470'), font: { size: 10 }, callback: function (v) { return 'R$ ' + v; } }, grid: { color: corToken('--line', 'rgba(30,19,38,.10)') } }
      }
    }
  });

  window.addEventListener('fx-theme-change', function () {
    chart.data.datasets[0].backgroundColor = corToken('--exp', '#B83A29');
    chart.data.datasets[1].backgroundColor = corAberto(corToken('--exp', '#B83A29'));
    chart.data.datasets[2].backgroundColor = corToken('--inc', '#0B7A54');
    chart.data.datasets[3].backgroundColor = corAberto(corToken('--inc', '#0B7A54'));
    chart.options.plugins.legend.labels.color = corToken('--muted', '#625470');
    chart.options.scales.x.ticks.color = corToken('--muted', '#625470');
    chart.options.scales.y.ticks.color = corToken('--muted', '#625470');
    chart.options.scales.y.grid.color = corToken('--line', 'rgba(30,19,38,.10)');
    chart.update();
  });
})();
</script>

<?php endif; ?>
