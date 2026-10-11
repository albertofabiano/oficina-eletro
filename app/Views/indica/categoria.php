<?php
// $categoria, $produtos vêm do Indica\PublicoController@categoria.
?>
<div class="fx-container">
  <section class="fx-section" style="padding-top:24px">
    <h1 class="fx-section-titulo"><?= e($categoria['nome']) ?></h1>
    <?php if (!empty($categoria['texto_seo'])): ?>
      <p style="color:var(--fx-texto-sec);max-width:640px"><?= e($categoria['texto_seo']) ?></p>
    <?php endif; ?>
  </section>

  <section class="fx-section" style="padding-top:0">
    <?php if (!empty($produtos)): ?>
    <div class="fx-grid">
      <?php foreach ($produtos as $p): include __DIR__ . '/_card_produto.php'; endforeach; ?>
    </div>
    <?php else: ?>
      <p style="color:var(--fx-texto-sec)">Ainda não temos produtos nesta categoria.</p>
    <?php endif; ?>
  </section>
</div>

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
