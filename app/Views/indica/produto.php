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

// Galeria = capa (imagem_url) + extras (imagens_extra, JSON) — sempre nessa ordem, sem
// duplicata (pode acontecer se alguém colar a mesma URL duas vezes na galeria manual).
$imagensGaleria = [];
if (!empty($produto['imagem_url'])) $imagensGaleria[] = $produto['imagem_url'];
$extra = $produto['imagens_extra'] ?? null;
$extra = is_array($extra) ? $extra : (json_decode((string) $extra, true) ?: []);
foreach ($extra as $url) {
    if ($url && !in_array($url, $imagensGaleria, true)) $imagensGaleria[] = $url;
}
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
    <?php if (count($imagensGaleria) > 1): ?>
      <div class="fx-carrossel" id="carrosselProduto">
        <div class="fx-carrossel-principal">
          <?php foreach ($imagensGaleria as $i => $url): ?>
            <img src="<?= e($url) ?>" alt="<?= e($produto['nome']) ?>" class="fx-carrossel-img<?= $i === 0 ? ' ativa' : '' ?>">
          <?php endforeach; ?>
          <button type="button" class="fx-carrossel-seta fx-carrossel-prev" onclick="fxCarrosselMover(-1)" aria-label="Foto anterior">&lsaquo;</button>
          <button type="button" class="fx-carrossel-seta fx-carrossel-next" onclick="fxCarrosselMover(1)" aria-label="Próxima foto">&rsaquo;</button>
        </div>
        <div class="fx-carrossel-thumbs">
          <?php foreach ($imagensGaleria as $i => $url): ?>
            <img src="<?= e($url) ?>" alt="" class="fx-carrossel-thumb<?= $i === 0 ? ' ativa' : '' ?>" onclick="fxCarrosselIr(<?= $i ?>)">
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="fx-card-img" style="border:1px solid var(--fx-borda);border-radius:10px">
        <?php if (!empty($produto['imagem_url'])): ?>
          <img src="<?= e($produto['imagem_url']) ?>" alt="<?= e($produto['nome']) ?>">
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div>
      <?php foreach ($selos as $s): if (!isset($rotulosSelo[$s])) continue; ?>
        <span class="fx-selo"><?= e($rotulosSelo[$s]) ?></span>
      <?php endforeach; ?>
      <h1 style="font-size:1.4rem;margin:8px 0 6px"><?= e($produto['nome']) ?></h1>
      <?php if (!empty($produto['especificacao_curta'])): ?>
        <p style="color:var(--fx-texto-sec);margin:0 0 14px"><?= e($produto['especificacao_curta']) ?></p>
      <?php endif; ?>

      <?php if ($produto['disponivel']): ?>
        <div style="display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:var(--fx-verde);margin-bottom:10px">
          <span style="width:8px;height:8px;border-radius:999px;background:var(--fx-verde);display:inline-block"></span>
          Em estoque no Mercado Livre
        </div>
      <?php else: ?>
        <div style="display:flex;align-items:center;gap:6px;font-size:.82rem;font-weight:600;color:var(--fx-texto-sec);margin-bottom:10px">
          <span style="width:8px;height:8px;border-radius:999px;background:var(--fx-texto-sec);display:inline-block"></span>
          Indisponível no momento
        </div>
      <?php endif; ?>

      <?php if (!empty($produto['preco'])): ?>
        <div style="font-size:1.8rem;font-weight:800;margin-bottom:4px"><?= money((float) $produto['preco']) ?></div>
        <?php if (!empty($produto['preco_atualizado_em'])): ?>
          <div style="font-size:.78rem;color:var(--fx-texto-sec);margin-bottom:16px">
            Preço consultado em <?= e(date('d/m/Y', strtotime($produto['preco_atualizado_em']))) ?> — confirme no Mercado Livre
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div style="font-size:.9rem;color:var(--fx-texto-sec);margin-bottom:16px">Preço não informado aqui — confira o valor atual no Mercado Livre.</div>
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
    'image' => $imagensGaleria ?: null,
    'offers' => !empty($produto['preco']) ? [
        '@type' => 'Offer',
        'price' => number_format((float) $produto['preco'], 2, '.', ''),
        'priceCurrency' => 'BRL',
        'availability' => !empty($produto['disponivel']) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        'url' => indica_url($linkIr),
    ] : null,
]), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>

<?php if (count($imagensGaleria) > 1): ?>
<script>
(function () {
  var imgs = document.querySelectorAll('#carrosselProduto .fx-carrossel-img');
  var thumbs = document.querySelectorAll('#carrosselProduto .fx-carrossel-thumb');
  var idx = 0;
  window.fxCarrosselIr = function (i) {
    if (!imgs.length) return;
    idx = (i + imgs.length) % imgs.length;
    imgs.forEach(function (img, j) { img.classList.toggle('ativa', j === idx); });
    thumbs.forEach(function (t, j) { t.classList.toggle('ativa', j === idx); });
  };
  window.fxCarrosselMover = function (delta) { fxCarrosselIr(idx + delta); };
})();
</script>
<?php endif; ?>
