<?php
// $termo, $produtos vêm do Indica\PublicoController@busca.
?>
<div class="fx-container">
  <section class="fx-section" style="padding-top:24px">
    <h1 class="fx-section-titulo">
      <?= $termo !== '' ? 'Resultados para "' . e($termo) . '"' : 'Buscar' ?>
    </h1>
  </section>

  <section class="fx-section" style="padding-top:0">
    <?php if ($termo === ''): ?>
      <p style="color:var(--fx-texto-sec)">Digite o que você procura na busca acima.</p>
    <?php elseif (!empty($produtos)): ?>
      <div class="fx-grid">
        <?php foreach ($produtos as $p): include __DIR__ . '/_card_produto.php'; endforeach; ?>
      </div>
    <?php else: ?>
      <p style="color:var(--fx-texto-sec)">Nenhum produto encontrado para "<?= e($termo) ?>".</p>
    <?php endif; ?>
  </section>
</div>
