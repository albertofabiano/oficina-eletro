<?php
// $categoria, $produtos, $lojasPresentes vêm do Indica\PublicoController@categoria.
$mostrarFiltroLoja = count($lojasPresentes ?? []) > 1;
?>
<div class="pagehead">
  <div class="wrap">
    <nav class="crumbs"><a href="/">Início</a> &rsaquo; <?= e($categoria['nome']) ?></nav>
    <h1><?= e($categoria['nome']) ?></h1>
    <?php if (!empty($categoria['texto_seo'])): ?>
      <p><?= e($categoria['texto_seo']) ?></p>
    <?php endif; ?>
  </div>
</div>

<section>
  <div class="wrap">
    <?php if (!empty($produtos)): ?>
      <?php if ($mostrarFiltroLoja): ?>
      <div class="storebar" role="group" aria-label="Filtrar por loja">
        <button class="sfilter on" data-store="all" aria-pressed="true">Todas as lojas</button>
        <button class="sfilter" data-store="ml" aria-pressed="false"><i class="ml-d"></i> Mercado Livre</button>
        <button class="sfilter" data-store="ae" aria-pressed="false"><i class="ae-d"></i> AliExpress</button>
      </div>
      <?php endif; ?>
      <div class="prods">
        <?php foreach ($produtos as $p): $origem = 'categoria'; include __DIR__ . '/_card_produto.php'; endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:var(--ink-3)">Ainda não temos produtos nesta categoria.</p>
    <?php endif; ?>
  </div>
</section>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => $categoria['nome'],
    'itemListElement' => array_map(function ($p, $i) {
        return [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => indica_url('/produto/' . $p['slug']),
            'name' => $p['nome'],
        ];
    }, $produtos, array_keys($produtos)),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

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
