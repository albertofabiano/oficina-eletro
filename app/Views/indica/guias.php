<?php
// $guias vem do Indica\PublicoController@guias.
?>
<div class="pagehead">
  <div class="wrap">
    <nav class="crumbs"><a href="/">Início</a> &rsaquo; Guias</nav>
    <h1>Guias — problema e solução</h1>
    <p>Identifique o problema do seu aparelho e veja a ferramenta ou peça certa pra resolver, recomendada por quem conserta no dia a dia.</p>
  </div>
</div>

<section>
  <div class="wrap">
    <?php if (!empty($guias)): ?>
    <div class="guides">
      <?php foreach ($guias as $g): ?>
      <a href="/guias/<?= e($g['slug']) ?>" class="guide">
        <h3><?= e($g['titulo']) ?></h3>
        <?php if (!empty($g['meta_description'])): ?><p><?= e($g['meta_description']) ?></p><?php endif; ?>
        <span class="go">Ver guia completo →</span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p style="color:var(--ink-3)">Ainda não temos guias publicados.</p>
    <?php endif; ?>
  </div>
</section>
