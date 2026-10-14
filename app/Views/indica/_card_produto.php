<?php
/**
 * Partial reutilizável — card de produto (home, categoria, busca, relacionados, bloco de guia).
 * Espera $p (uma linha de indica_produtos, com `loja`) no escopo de quem incluir; $origem
 * (opcional) rotula de onde veio o clique em /ir/{slug} pra estatística (ver indica_cliques).
 */
$selos = [];
if (!empty($p['selos'])) {
    $decoded = is_array($p['selos']) ? $p['selos'] : json_decode($p['selos'], true);
    $selos = is_array($decoded) ? $decoded : [];
}
$rotulosSelo = [
    'frete_gratis'  => 'Frete grátis',
    'mais_vendido'  => 'Mais vendido',
    'oferta_do_dia' => 'Oferta do dia',
];
$lojaAtual = $p['loja'] ?? 'mercado_livre';
$ehAe = $lojaAtual === 'aliexpress';
$origemClick = $origem ?? 'card';
$linkIr = '/ir/' . rawurlencode($p['slug']) . '?origem=' . rawurlencode($origemClick);
$primeiroSelo = $selos[0] ?? null;
?>
<article class="prod" data-store="<?= $ehAe ? 'ae' : 'ml' ?>">
  <a href="/produto/<?= e($p['slug']) ?>" class="shot">
    <?php if ($primeiroSelo && isset($rotulosSelo[$primeiroSelo])): ?>
      <span class="badge <?= $primeiroSelo === 'frete_gratis' ? 'bench' : 'hot' ?>"><?= e($rotulosSelo[$primeiroSelo]) ?></span>
    <?php elseif (!empty($p['destaque_home'])): ?>
      <span class="badge">Destaque</span>
    <?php endif; ?>
    <?php if (!empty($p['imagem_url'])): ?>
      <img src="<?= e($p['imagem_url']) ?>" alt="<?= e($p['nome']) ?>" loading="lazy">
    <?php else: ?>
      <span class="sem-foto">SEM FOTO</span>
    <?php endif; ?>
  </a>
  <div class="body">
    <div class="meta">
      <span class="store <?= $ehAe ? 'ae' : 'ml' ?>"><i></i><?= $ehAe ? 'AliExpress' : 'Mercado Livre' ?></span>
      <span class="ship"><?= $ehAe ? 'Importado' : 'Entrega nacional' ?></span>
    </div>
    <a href="/produto/<?= e($p['slug']) ?>"><h3><?= e($p['nome']) ?></h3></a>
    <?php if (!empty($p['preco'])): ?>
      <div class="preco"><strong><?= money((float) $p['preco']) ?></strong><span>Confira no <?= $ehAe ? 'AliExpress' : 'Mercado Livre' ?></span></div>
    <?php endif; ?>
    <?php if (!empty($p['especificacao_curta']) && !str_contains($p['especificacao_curta'], ' · ')): ?>
      <p class="verdict"><?= e($p['especificacao_curta']) ?></p>
    <?php elseif (!empty($p['especificacao_curta'])): ?>
      <div class="specs">
        <?php foreach (array_slice(array_filter(array_map('trim', explode(' · ', $p['especificacao_curta']))), 0, 3) as $f): ?>
          <span><?= e($f) ?></span>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (empty($p['disponivel'])): ?>
      <div style="font-size:11.5px;color:var(--ink-3);font-weight:600">Indisponível no momento</div>
    <?php endif; ?>
    <a class="buy<?= $ehAe ? ' ae' : '' ?>" href="<?= e($linkIr) ?>" target="_blank" rel="nofollow sponsored noopener">
      Ver no <?= $ehAe ? 'AliExpress' : 'Mercado Livre' ?> →
    </a>
    <span class="ml-note">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="#0E7A52" stroke-width="2.6" stroke-linecap="round"><path d="M20 6 9 17l-5-5"/></svg>
      <?= $ehAe ? 'Proteção ao Comprador · imposto no checkout' : 'Compra Garantida' ?>
    </span>
  </div>
</article>
