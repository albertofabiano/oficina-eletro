<?php
/**
 * Partial reutilizável — card de produto (home, categoria, busca, relacionados).
 * Espera $p (uma linha de indica_produtos) no escopo de quem incluir.
 */
$selos = [];
if (!empty($p['selos'])) {
    $decoded = is_array($p['selos']) ? $p['selos'] : json_decode($p['selos'], true);
    $selos = is_array($decoded) ? $decoded : [];
}
$rotulosSelo = [
    'frete_gratis'   => 'Frete grátis',
    'mais_vendido'   => 'Mais vendido',
    'oferta_do_dia'  => 'Oferta do dia',
];
?>
<a class="fx-card" href="/produto/<?= e($p['slug']) ?>">
  <div class="fx-card-img">
    <?php if (!empty($p['imagem_url'])): ?>
      <img src="<?= e($p['imagem_url']) ?>" alt="<?= e($p['nome']) ?>" loading="lazy">
    <?php endif; ?>
  </div>
  <div class="fx-card-body">
    <?php foreach ($selos as $s): if (!isset($rotulosSelo[$s])) continue; ?>
      <span class="fx-selo"><?= e($rotulosSelo[$s]) ?></span>
    <?php endforeach; ?>
    <p class="fx-card-nome"><?= e($p['nome']) ?></p>
    <div class="fx-card-preco">
      <?php if (!empty($p['preco'])): ?>
        <strong><?= money((float) $p['preco']) ?></strong>
      <?php else: ?>
        Ver preço no Mercado Livre
      <?php endif; ?>
    </div>
  </div>
</a>
