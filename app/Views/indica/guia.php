<?php
// $guia, $blocos vêm do Indica\PublicoController@guia. Editor por blocos — ver migration 105
// (indica_guia_blocos.tipo: titulo/paragrafo/produto/aviso).
?>
<div class="pagehead">
  <div class="wrap">
    <nav class="crumbs"><a href="/">Início</a> &rsaquo; <a href="/guias">Guias</a> &rsaquo; <?= e($guia['titulo']) ?></nav>
    <h1><?= e($guia['titulo']) ?></h1>
  </div>
</div>

<section>
  <div class="wrap">
    <article style="max-width:720px">
      <?php foreach ($blocos as $bloco): ?>
        <?php if ($bloco['tipo'] === 'titulo'): ?>
          <h2 style="font-size:1.3rem;margin:30px 0 12px"><?= e($bloco['conteudo']['texto'] ?? '') ?></h2>

        <?php elseif ($bloco['tipo'] === 'paragrafo'): ?>
          <p style="line-height:1.75;margin:0 0 16px;color:var(--ink-2)"><?= nl2br(e($bloco['conteudo']['texto'] ?? '')) ?></p>

        <?php elseif ($bloco['tipo'] === 'aviso'): ?>
          <div style="background:var(--orange-soft);border:1px solid #F7C9A8;border-radius:10px;padding:14px 18px;margin:16px 0;font-size:.92rem;color:#8a3a10">
            ⚠️ <?= nl2br(e($bloco['conteudo']['texto'] ?? '')) ?>
          </div>

        <?php elseif ($bloco['tipo'] === 'produto' && !empty($bloco['produto'])): $p = $bloco['produto']; $origem = 'guia'; ?>
          <div style="margin:20px 0;max-width:280px">
            <?php include __DIR__ . '/_card_produto.php'; ?>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </article>
  </div>
</section>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $guia['titulo'],
    'datePublished' => $guia['publicado_em'] ?: $guia['criado_em'],
    'dateModified' => $guia['atualizado_em'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
