<?php
// $termo, $produtos, $lojasPresentes vêm do Indica\PublicoController@busca.
$mostrarFiltroLoja = count($lojasPresentes ?? []) > 1;
?>
<div class="pagehead">
  <div class="wrap">
    <nav class="crumbs"><a href="/">Início</a> &rsaquo; Busca</nav>
    <h1><?= $termo !== '' ? 'Resultados para "' . e($termo) . '"' : 'Buscar' ?></h1>
  </div>
</div>

<section>
  <div class="wrap">
    <?php if ($termo === ''): ?>
      <p style="color:var(--ink-3)">Digite o que você procura na busca do topo.</p>
    <?php elseif (!empty($produtos)): ?>
      <?php if ($mostrarFiltroLoja): ?>
      <div class="storebar" role="group" aria-label="Filtrar por loja">
        <button class="sfilter on" data-store="all" aria-pressed="true">Todas as lojas</button>
        <button class="sfilter" data-store="ml" aria-pressed="false"><i class="ml-d"></i> Mercado Livre</button>
        <button class="sfilter" data-store="ae" aria-pressed="false"><i class="ae-d"></i> AliExpress</button>
      </div>
      <?php endif; ?>
      <div class="prods">
        <?php foreach ($produtos as $p): $origem = 'busca'; include __DIR__ . '/_card_produto.php'; endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:var(--ink-3)">Nenhum produto encontrado para "<?= e($termo) ?>".</p>
    <?php endif; ?>
  </div>
</section>

<?php if ($mostrarFiltroLoja): ?>
<script>
(function () {
  var botoes = document.querySelectorAll('.sfilter');
  var cards = document.querySelectorAll('.prod');
  botoes.forEach(function (b) {
    b.addEventListener('click', function () {
      botoes.forEach(function (x) { x.classList.remove('on'); x.setAttribute('aria-pressed', 'false'); });
      b.classList.add('on');
      b.setAttribute('aria-pressed', 'true');
      var loja = b.dataset.store;
      cards.forEach(function (c) {
        c.hidden = !(loja === 'all' || c.dataset.store === loja);
      });
    });
  });
})();
</script>
<?php endif; ?>
