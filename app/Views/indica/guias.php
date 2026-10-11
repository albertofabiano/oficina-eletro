<?php
// $guias vem do Indica\PublicoController@guias.
?>
<div class="fx-container">
  <section class="fx-section" style="padding-top:24px">
    <h1 class="fx-section-titulo">Guias — problema e solução</h1>
    <p style="color:var(--fx-texto-sec);max-width:640px">
      Identifique o problema do seu aparelho e veja a ferramenta ou peça certa pra resolver,
      recomendada por quem conserta no dia a dia.
    </p>
  </section>

  <section class="fx-section" style="padding-top:0">
    <?php if (!empty($guias)): ?>
    <div class="fx-grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
      <?php foreach ($guias as $g): ?>
      <a class="fx-card" href="/guias/<?= e($g['slug']) ?>" style="padding:16px">
        <strong style="display:block;margin-bottom:4px"><?= e($g['titulo']) ?></strong>
        <span style="color:var(--fx-texto-sec);font-size:.85rem">Ver guia completo →</span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p style="color:var(--fx-texto-sec)">Ainda não temos guias publicados.</p>
    <?php endif; ?>
  </section>
</div>
