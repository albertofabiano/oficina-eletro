<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$mesLabel = $mesesPt[(int) date('n')] . ' de ' . date('Y');
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

<div class="fp-card" style="margin-bottom:16px">
  <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px">Gasto em <?= e($mesLabel) ?></div>
  <div style="display:flex;align-items:baseline;gap:10px;flex-wrap:wrap">
    <div class="fp-mono" style="font-weight:700;font-size:1.9rem">R$ <?= number_format($resumo['totalMes'], 2, ',', '.') ?></div>
    <?php if ($resumo['variacaoPct'] !== null): ?>
    <?php $subiu = $resumo['variacaoPct'] > 0; ?>
    <div style="display:flex;align-items:center;gap:4px;background:<?= $subiu ? 'rgba(232,72,74,.16)' : 'rgba(139,209,122,.16)' ?>;border-radius:999px;padding:3px 9px">
      <span class="fp-mono" style="font-weight:700;font-size:.78rem;color:<?= $subiu ? '#F29B9C' : '#8BD17A' ?>"><?= $subiu ? '+' : '' ?><?= $resumo['variacaoPct'] ?>%</span>
    </div>
    <?php endif; ?>
  </div>
  <?php if ($resumo['totalMesAnterior'] > 0): ?>
  <div class="fp-mono fp-muted" style="font-size:.78rem;margin-top:4px">vs. R$ <?= number_format($resumo['totalMesAnterior'], 2, ',', '.') ?> no mês passado</div>
  <?php endif; ?>
</div>

<div class="fp-card" style="margin-bottom:16px">
  <div class="fp-muted" style="font-size:.8rem;margin-bottom:10px">Seu mês, dia a dia</div>
  <div style="height:140px"><canvas id="fpChartDias"></canvas></div>
</div>

<?php if ($resumo['maiorGasto']): ?>
<?php $catMaior = $categorias[$resumo['maiorGasto']['categoria']] ?? ['nome' => $resumo['maiorGasto']['categoria'], 'cor' => '#8C7A9E']; ?>
<div class="fp-card" style="margin-bottom:16px;display:flex;align-items:center;gap:12px">
  <span style="width:10px;height:10px;border-radius:50%;background:<?= e($catMaior['cor']) ?>;flex:0 0 auto"></span>
  <div style="flex:1;min-width:0">
    <div class="fp-muted" style="font-size:.78rem">Maior gasto do mês</div>
    <div style="font-size:.95rem;font-weight:700;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($resumo['maiorGasto']['descricao']) ?></div>
  </div>
  <div class="fp-mono" style="font-weight:700">R$ <?= number_format($resumo['maiorGasto']['valor'], 2, ',', '.') ?></div>
</div>
<?php endif; ?>

<div class="fp-card">
  <div class="fp-muted" style="font-size:.8rem;margin-bottom:14px">Por categoria</div>
  <?php if (!$resumo['porCategoria']): ?>
  <div class="fp-muted" style="font-size:.88rem;text-align:center;padding:12px 0">Nenhum gasto registrado neste mês ainda.</div>
  <?php else: ?>
  <?php $maiorValorCat = max($resumo['porCategoria']); ?>
  <div style="display:flex;flex-direction:column;gap:13px">
    <?php foreach ($resumo['porCategoria'] as $chave => $valor): ?>
    <?php
      $c   = $categorias[$chave] ?? ['nome' => $chave, 'cor' => '#8C7A9E'];
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
      <div style="height:6px;border-radius:3px;background:rgba(245,239,250,.08);overflow:hidden">
        <div style="width:<?= $pct ?>%;height:100%;background:<?= e($c['cor']) ?>"></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
  var ctx = document.getElementById('fpChartDias');
  if (!ctx || typeof Chart === 'undefined') return;
  var serie = <?= json_encode($resumo['serieDias']) ?>;
  var labels = serie.map(function (_, i) { return i + 1; });
  new Chart(ctx, {
    type: 'bar',
    data: { labels: labels, datasets: [{ data: serie, backgroundColor: '#FF6B47', borderRadius: 2 }] },
    options: {
      responsive: true, maintainAspectRatio: false,
      plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return 'R$ ' + c.parsed.y.toLocaleString('pt-BR', { minimumFractionDigits: 2 }); } } } },
      scales: {
        x: { ticks: { color: '#8C7A9E', font: { size: 10 } }, grid: { display: false } },
        y: { beginAtZero: true, ticks: { color: '#8C7A9E', font: { size: 10 } }, grid: { color: 'rgba(245,239,250,.06)' } }
      }
    }
  });
})();
</script>
<?php endif; ?>
