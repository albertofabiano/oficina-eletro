<div class="topo"><h1>Dashboard</h1></div>

<div class="kpi-grid">
  <div class="kpi"><div class="num"><?= (int) $totais['categorias'] ?></div><div class="rotulo">Categorias ativas</div></div>
  <div class="kpi"><div class="num"><?= (int) $totais['produtos'] ?></div><div class="rotulo">Produtos ativos</div></div>
  <div class="kpi"><div class="num"><?= (int) $totais['guias'] ?></div><div class="rotulo">Guias publicados</div></div>
  <div class="kpi"><div class="num"><?= (int) $totais['cliques_30d'] ?></div><div class="rotulo">Cliques (30 dias)</div></div>
</div>

<div class="card">
  <h3 style="margin-top:0">Mais clicados (30 dias)</h3>
  <?php if (!empty($maisClicados)): ?>
  <table>
    <thead><tr><th>Produto</th><th>Cliques</th></tr></thead>
    <tbody>
      <?php foreach ($maisClicados as $m): ?>
      <tr>
        <td><a href="/produto/<?= e($m['slug']) ?>" target="_blank"><?= e($m['nome']) ?></a></td>
        <td><?= (int) $m['total'] ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p style="color:var(--texto-sec)">Nenhum clique registrado ainda nos últimos 30 dias.</p>
  <?php endif; ?>
</div>

<?php if (!empty($cliquesPorDia)): ?>
<div class="card">
  <h3 style="margin-top:0">Cliques por dia</h3>
  <table>
    <thead><tr><th>Dia</th><th>Cliques</th></tr></thead>
    <tbody>
      <?php foreach ($cliquesPorDia as $c): ?>
      <tr><td><?= e(date('d/m/Y', strtotime($c['dia']))) ?></td><td><?= (int) $c['total'] ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>
