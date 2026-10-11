<?php
// $guia, $blocos vêm do Indica\PublicoController@guia. Editor por blocos — ver migration 105
// (indica_guia_blocos.tipo: titulo/paragrafo/produto/aviso).
?>
<div class="fx-container">
  <nav style="padding:14px 0;font-size:.85rem;color:var(--fx-texto-sec)">
    <a href="/">Início</a> &rsaquo; <a href="/guias">Guias</a> &rsaquo; <?= e($guia['titulo']) ?>
  </nav>

  <article class="fx-section" style="padding-top:0;max-width:720px">
    <h1 style="font-size:1.5rem;margin-bottom:18px"><?= e($guia['titulo']) ?></h1>

    <?php foreach ($blocos as $bloco): ?>
      <?php if ($bloco['tipo'] === 'titulo'): ?>
        <h2 style="font-size:1.15rem;margin:26px 0 10px"><?= e($bloco['conteudo']['texto'] ?? '') ?></h2>

      <?php elseif ($bloco['tipo'] === 'paragrafo'): ?>
        <p style="line-height:1.7;margin:0 0 14px"><?= nl2br(e($bloco['conteudo']['texto'] ?? '')) ?></p>

      <?php elseif ($bloco['tipo'] === 'aviso'): ?>
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:12px 16px;margin:14px 0;font-size:.9rem">
          ⚠️ <?= nl2br(e($bloco['conteudo']['texto'] ?? '')) ?>
        </div>

      <?php elseif ($bloco['tipo'] === 'produto' && !empty($bloco['produto'])): $p = $bloco['produto']; ?>
        <div style="margin:18px 0;max-width:260px">
          <?php include __DIR__ . '/_card_produto.php'; ?>
        </div>
      <?php endif; ?>
    <?php endforeach; ?>
  </article>
</div>

<script type="application/ld+json">
<?= json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Article',
    'headline' => $guia['titulo'],
    'datePublished' => $guia['publicado_em'] ?: $guia['criado_em'],
    'dateModified' => $guia['atualizado_em'],
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
</script>
