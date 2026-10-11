<?php
// Fixa Indica — home pública. $produtos (destaque), $categorias, $guias vêm do
// Indica\PublicoController@home.
?>
<div class="fx-container">
  <section class="fx-section" style="padding-top:24px">
    <h1 class="fx-section-titulo" style="font-size:1.6rem">Ferramentas, peças e eletrônicos indicados por técnicos</h1>
    <p style="color:var(--fx-texto-sec);max-width:640px">
      Tudo o que aparece aqui é o que técnico de assistência técnica de verdade usa no dia a dia
      — com link direto pro Mercado Livre.
    </p>
  </section>

  <?php if (!empty($categorias)): ?>
  <section class="fx-section" style="padding-top:0">
    <div class="fx-grid" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr))">
      <?php foreach ($categorias as $c): ?>
      <a class="fx-card" href="/categoria/<?= e($c['slug']) ?>" style="padding:18px 10px;text-align:center;font-weight:700">
        <?php if (!empty($c['icone'])): ?><div style="font-size:1.6rem;margin-bottom:6px"><?= e($c['icone']) ?></div><?php endif; ?>
        <?= e($c['nome']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($produtos)): ?>
  <section class="fx-section">
    <h2 class="fx-section-titulo">Mais indicados pelos técnicos</h2>
    <div class="fx-grid">
      <?php foreach ($produtos as $p): include __DIR__ . '/_card_produto.php'; endforeach; ?>
    </div>
  </section>
  <?php endif; ?>

  <?php if (!empty($guias)): ?>
  <section class="fx-section">
    <h2 class="fx-section-titulo">Guias — problema e solução</h2>
    <div class="fx-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
      <?php foreach ($guias as $g): ?>
      <a class="fx-card" href="/guias/<?= e($g['slug']) ?>" style="padding:16px">
        <strong style="display:block;margin-bottom:4px"><?= e($g['titulo']) ?></strong>
        <span style="color:var(--fx-texto-sec);font-size:.85rem">Ver guia completo →</span>
      </a>
      <?php endforeach; ?>
    </div>
  </section>
  <?php endif; ?>
</div>
