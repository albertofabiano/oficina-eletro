<?php
// $produto, $relacionados, $banners vêm do Indica\PublicoController@produto.
$selos = [];
if (!empty($produto['selos'])) {
    $decoded = is_array($produto['selos']) ? $produto['selos'] : json_decode($produto['selos'], true);
    $selos = is_array($decoded) ? $decoded : [];
}
$rotulosSelo = [
    'frete_gratis'  => 'Frete grátis',
    'mais_vendido'  => 'Mais vendido',
    'oferta_do_dia' => 'Oferta do dia',
];
$lojaAtual = $produto['loja'] ?? 'mercado_livre';
$ehAe = $lojaAtual === 'aliexpress';
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

// especificacao_curta de produto de catálogo vem com " · " entre destaques (ver
// MercadoLivreService::buscarProdutoCatalogo, main_features) — nesse caso vira lista de specs
// (chips), igual o "O que você precisa saber" do Mercado Livre; texto digitado à mão sem esse
// separador continua mostrando como parágrafo corrido, de sempre.
$especFeatures = [];
if (!empty($produto['especificacao_curta']) && str_contains($produto['especificacao_curta'], ' · ')) {
    $especFeatures = array_filter(array_map('trim', explode(' · ', $produto['especificacao_curta'])));
}
?>
<div class="wrap">
  <nav class="eyebrow" style="padding:18px 0 0;text-transform:none;letter-spacing:0;font-family:inherit;font-weight:500;color:var(--ink-3)">
    <a href="/" style="color:var(--ink-3)">Início</a>
    <?php if (!empty($produto['categoria_slug'])): ?>
      &rsaquo; <a href="/categoria/<?= e($produto['categoria_slug']) ?>" style="color:var(--ink-3)"><?= e($produto['categoria_nome']) ?></a>
    <?php endif; ?>
    &rsaquo; <?= e($produto['nome']) ?>
  </nav>

  <section class="produto-grid" style="padding-top:18px">
    <?php if (count($imagensGaleria) > 1): ?>
      <div id="carrosselProduto">
        <div class="foto-produto">
          <?php foreach ($imagensGaleria as $i => $url): ?>
            <img src="<?= e($url) ?>" alt="<?= e($produto['nome']) ?>" class="carrossel-img<?= $i === 0 ? ' ativa' : '' ?>">
          <?php endforeach; ?>
          <button type="button" class="carrossel-seta carrossel-prev" onclick="fxCarrosselMover(-1)" aria-label="Foto anterior">&lsaquo;</button>
          <button type="button" class="carrossel-seta carrossel-next" onclick="fxCarrosselMover(1)" aria-label="Próxima foto">&rsaquo;</button>
        </div>
        <div class="carrossel-thumbs">
          <?php foreach ($imagensGaleria as $i => $url): ?>
            <img src="<?= e($url) ?>" alt="" class="carrossel-thumb<?= $i === 0 ? ' ativa' : '' ?>" onclick="fxCarrosselIr(<?= $i ?>)">
          <?php endforeach; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="foto-produto">
        <?php if (!empty($produto['imagem_url'])): ?>
          <img src="<?= e($produto['imagem_url']) ?>" alt="<?= e($produto['nome']) ?>" style="position:absolute;inset:0;width:100%;height:100%;object-fit:contain;mix-blend-mode:multiply;padding:18px">
        <?php else: ?>
          <span class="sem-foto" style="margin:auto">SEM FOTO</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div>
      <span class="store <?= $ehAe ? 'ae' : 'ml' ?>"><i></i><?= $ehAe ? 'AliExpress' : 'Mercado Livre' ?></span>
      <?php foreach ($selos as $s): if (!isset($rotulosSelo[$s])) continue; ?>
        <span class="badge <?= $s === 'frete_gratis' ? 'bench' : 'hot' ?>" style="position:static;display:inline-flex;margin-left:6px"><?= e($rotulosSelo[$s]) ?></span>
      <?php endforeach; ?>

      <h1 style="font-size:1.6rem;margin:12px 0 10px"><?= e($produto['nome']) ?></h1>

      <?php if ($especFeatures): ?>
        <div style="font-size:.78rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:var(--ink-3);margin:16px 0 8px">O que você precisa saber</div>
        <div class="specs" style="margin-bottom:16px">
          <?php foreach ($especFeatures as $f): ?><span><?= e($f) ?></span><?php endforeach; ?>
        </div>
      <?php elseif (!empty($produto['especificacao_curta'])): ?>
        <p class="verdict" style="margin:0 0 16px"><?= e($produto['especificacao_curta']) ?></p>
      <?php endif; ?>

      <?php if ($produto['disponivel']): ?>
        <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;font-weight:600;color:var(--green);margin-bottom:12px">
          <span style="width:8px;height:8px;border-radius:999px;background:var(--green);display:inline-block"></span>
          <?= $ehAe ? 'Disponível no AliExpress' : 'Em estoque no Mercado Livre' ?>
        </div>
      <?php else: ?>
        <div style="display:flex;align-items:center;gap:6px;font-size:.85rem;font-weight:600;color:var(--ink-3);margin-bottom:12px">
          <span style="width:8px;height:8px;border-radius:999px;background:var(--ink-3);display:inline-block"></span>
          Indisponível no momento
        </div>
      <?php endif; ?>

      <?php if (!empty($produto['preco'])): ?>
        <div class="disp" style="font-size:2rem;font-weight:800;margin-bottom:4px"><?= money((float) $produto['preco']) ?></div>
        <?php if (!empty($produto['preco_atualizado_em'])): ?>
          <div style="font-size:.8rem;color:var(--ink-3);margin-bottom:18px">
            Preço consultado em <?= e(date('d/m/Y', strtotime($produto['preco_atualizado_em']))) ?> — confirme na loja antes de comprar
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div style="font-size:.92rem;color:var(--ink-3);margin-bottom:18px">Preço não informado aqui — confira o valor atual na loja.</div>
      <?php endif; ?>

      <a class="buy<?= $ehAe ? ' ae' : '' ?>" style="display:inline-flex;max-width:320px" href="<?= e($linkIr) ?>" target="_blank" rel="nofollow sponsored noopener">
        Ver no <?= $ehAe ? 'AliExpress' : 'Mercado Livre' ?> →
      </a>
      <div class="ml-note" style="justify-content:flex-start;margin-top:8px">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0E7A52" stroke-width="2.6" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
        <?= $ehAe ? 'Proteção ao Comprador · imposto no checkout' : 'Compra Garantida' ?>
      </div>

      <?php if (!empty($produto['descricao'])): ?>
        <div style="margin-top:26px;line-height:1.75;color:var(--ink-2)"><?= nl2br(e($produto['descricao'])) ?></div>
      <?php endif; ?>
    </div>

    <?php if (!empty($banners)): ?>
    <aside class="produto-sidebar">
      <?php foreach ($banners as $b): ?>
        <a class="banner-promo" href="<?= e($b['link']) ?>" target="_blank" rel="noopener" style="background:<?= e($b['cor']) ?>">
          <strong><?= e($b['titulo']) ?></strong>
          <?php if (!empty($b['descricao'])): ?><span><?= e($b['descricao']) ?></span><?php endif; ?>
          <span class="seta">Saiba mais →</span>
        </a>
      <?php endforeach; ?>
    </aside>
    <?php endif; ?>
  </section>

  <?php if (!empty($relacionados)): ?>
  <section>
    <div class="sec-h"><div><span class="eyebrow">Mais</span><h2 style="margin-top:9px">Também indicados</h2></div></div>
    <div class="prods">
      <?php foreach ($relacionados as $p): $origem = 'relacionado'; include __DIR__ . '/_card_produto.php'; endforeach; ?>
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
  var imgs = document.querySelectorAll('#carrosselProduto .carrossel-img');
  var thumbs = document.querySelectorAll('#carrosselProduto .carrossel-thumb');
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
