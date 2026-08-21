<?php
// Menu sticky do fórum — incluído em todas as views
$uriAtual = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$baseUrl   = \App\Core\Auth::check() ? rtrim(url(''), '/') : rtrim($appUrl ?? '', '/');

// Categorias vêm do banco (mesma query de sidebar.php) — antes era uma lista fixa no código,
// que ficou desatualizada em relação a forum_categorias (ex.: "Defeitos de Placa" aqui vs.
// "Dicas de Defeito" no banco/sidebar) sem ninguém perceber, porque o link em si (por id)
// continuava funcionando — só o texto mostrado é que estava errado.
$menuCats = \App\Core\DB::pdo()->query("SELECT * FROM forum_categorias WHERE ativo=1 ORDER BY ordem")->fetchAll();

function forumMenuAtivo(string $uri, string $path): string {
    return str_contains($uri, $path) ? 'active' : '';
}
?>

<style>
.forum-sticky {
  position: sticky;
  top: 0;
  z-index: 500;
  background: #225AE3;
  border-bottom: 2px solid #1a48c0;
  box-shadow: 0 3px 14px rgba(34,90,227,.35);
  margin-bottom: 1.5rem;
  margin-left: -1.5rem;
  margin-right: -1.5rem;
  padding: 0 1.5rem;
}
.forum-sticky .menu-inner {
  display: flex;
  align-items: center;
  gap: 4px;
  overflow-x: auto;
  scrollbar-width: none;
  -ms-overflow-style: none;
  padding: 8px 0;
}
.forum-sticky .menu-inner::-webkit-scrollbar { display: none; }
.forum-nav-item {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 14px;
  border-radius: 8px;
  font-size: .82rem;
  font-weight: 600;
  color: #fff;
  text-decoration: none;
  white-space: nowrap;
  transition: all .18s;
  border: 1.5px solid transparent;
  opacity: 1;
}
.forum-nav-item:hover {
  background: rgba(255,255,255,.15);
  color: #fff;
  opacity: .7;
}
.forum-nav-item.active {
  background: rgba(220,38,38,.85);
  color: #fff;
  border-color: rgba(255,255,255,.3);
  opacity: 1;
}
.forum-nav-item .cat-dot {
  width: 8px; height: 8px;
  border-radius: 50%;
  background: rgba(255,255,255,.7);
  display: inline-block;
  flex-shrink: 0;
}
.forum-nav-sep {
  width: 1px;
  height: 24px;
  background: rgba(255,255,255,.25);
  flex-shrink: 0;
  margin: 0 4px;
}
.forum-novo-btn {
  margin-left: auto;
  flex-shrink: 0;
  background: rgba(255,255,255,.2) !important;
  border-color: rgba(255,255,255,.4) !important;
  color: #fff !important;
}
.forum-novo-btn:hover {
  background: rgba(255,255,255,.35) !important;
}
</style>

<nav class="forum-sticky">
  <div class="menu-inner">

    <!-- Início -->
    <a href="<?= $baseUrl ?>/forum"
       class="forum-nav-item <?= ($uriAtual === '/forum' || $uriAtual === '/forum/') ? 'active' : '' ?>">
      <i class="bi bi-house-fill" style="font-size:.85rem"></i>
      Início
    </a>

    <div class="forum-nav-sep"></div>

    <!-- Categorias -->
    <?php foreach ($menuCats as $mc): ?>
    <a href="<?= $baseUrl ?>/forum/categoria/<?= (int) $mc['id'] ?>"
       class="forum-nav-item <?= forumMenuAtivo($uriAtual, '/forum/categoria/' . $mc['id']) ?>">
      <span class="cat-dot" style="background:<?= e($mc['cor'] ?: '#0d6efd') ?>"></span>
      <?= e($mc['nome']) ?>
    </a>
    <?php endforeach; ?>

    <div class="forum-nav-sep"></div>

    <!-- Buscar -->
    <a href="<?= $baseUrl ?>/forum/buscar"
       class="forum-nav-item <?= forumMenuAtivo($uriAtual, '/forum/buscar') ?>">
      <i class="bi bi-search" style="font-size:.85rem"></i>
      Buscar
    </a>

    <!-- Botão novo tópico -->
    <?php if (\App\Core\Auth::check()): ?>
    <a href="<?= url('/forum/novo') ?>" class="btn btn-primary btn-sm fw-semibold forum-novo-btn">
      <i class="bi bi-plus-lg me-1"></i>Novo Tópico
    </a>
    <?php endif; ?>

  </div>
</nav>
