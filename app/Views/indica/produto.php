<?php
// $produto, $relacionados vêm do Indica\PublicoController@produto.
$selos = [];
if (!empty($produto['selos'])) {
    $decoded = is_array($produto['selos']) ? $produto['selos'] : json_decode($produto['selos'], true);
    $selos = is_array($decoded) ? $decoded : [];
}
$rotulosSelo = [
    'frete_gratis'   => 'Frete grátis',
    'mais_vendido'   => 'Mais vendido',
    'oferta_do_dia'  => 'Oferta do dia',
];
$linkIr = '/ir/' . rawurlencode($produto['slug']) . '?origem=produto';
?>
<div class="fx-container">
  <nav style="padding:14px 0;font-size:.85rem;color:var(--fx-texto-sec)">
    <a href="/">Início</a>
    <?php if (!empty($produto['categoria_slug'])): ?>
      &rsaquo; <a href="/categoria/<?= e($produto['categoria_slug']) ?>"><?= e($produto['categoria_nome']) ?></a>
    <?php endif; ?>
    &rsaquo; <?= e($produto['nome']) ?>
  </nav>

  <section class="fx-section" style="padding-top:0;display:grid;grid-template-columns:minmax(0,340px) 1fr;gap:28px">
    <div class="fx-card-img" style="border:1px solid var(--fx-borda);border-radius:10px">
      <?php if (!empty($produto['imagem_url'])): ?>
        <img src="<?= e($produto['imagem_url']) ?>" alt="<?= e($produto['nome']) ?>">
      <?php endif; ?>
    </div>

    <div>
      <?php foreach ($selos as $s): if (!isset($rotulosSelo[$s])) continue; ?>
        <span class="fx-selo"><?= e($rotulosSelo[$s]) ?></span>
      <?php endforeach; ?>
      <h1 style="font-size:1.4rem;margin:8px 0 6px"><?= e($produto['nome']) ?></h1>
      <?php if (!empty($produto['especificacao_curta'])): ?>
        <p style="color:var(--fx-texto-sec);margin:0 0 14px"><?= e($produto['especificacao_curta']) ?></p>
      <?php endif; ?>

      <?php if (!empty($produto['preco'])): ?>
        <div style="font-size:1.8rem;font-weight:800;margin-bottom:4px"><?= money((float) $produto['preco']) ?></div>
        <?php if (!empty($produto['preco_atualizado_em'])): ?>
          <div style="font-size:.78rem;color:var(--fx-texto-sec);margin-bottom:16px">
            Preço consultado em <?= e(date('d/m/Y', strtotime($produto['preco_atualizado_em']))) ?> — confirme no Mercado Livre
          </div>
        <?php endif; ?>
      <?php endif; ?>

      <a class="fx-btn-ml" href="<?= e($linkIr) ?>" target="_blank" rel="nofollow sponsored noopener">
        Ver no Mercado Livre →
      </a>

      <?php if (!empty($produto['descricao'])): ?>
        <div style="margin-top:22px;line-height:1.7"><?= nl2br(e($produto['descricao'])) ?></div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (!empty($relacionados)): ?>
  <section class="fx-section">
    <h2 class="fx-section-titulo">Também indicados</h2>
    <div class="fx-grid">
      <?php foreach ($relacionados as $p): include __DIR__ . '/_card_produto.php'; endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>

<script type="application/ld+json">
<?= json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'Product',
    'name' => $produto['nome'],
    'description' => $produto['especificacao_curta'] ?: $produto['nome'],
    'image' => $produto['imagem_url'] ?: null,
    'offers' => !empty($produto['preco']) ? [
        '@type' => 'Offer',
        'price' => number_format((float) $produto['preco'], 2, '.', ''),
        'priceCurrency' => 'BRL',
        'availability' => !empty($produto['disponivel']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => indica_url($linkIr),
    ] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
