<?php
// Sidebar do fórum com blocos de anúncio
$_db2    = \App\Core\DB::pdo();
$_adForum = [];
foreach ($_db2->query("SELECT posicao, codigo FROM master_adsense_blocos WHERE local='forum' AND ativo=1 ORDER BY posicao")->fetchAll() as $_b)
    $_adForum[$_b['posicao']] = $_b['codigo'];

$_catsForum = $_db2->query("SELECT * FROM forum_categorias WHERE ativo=1 ORDER BY ordem")->fetchAll();
$_uriF = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$_baseF = \App\Core\Auth::check() ? url('') : ($appUrl ?? '');
?>

<style>
.forum-sidebar { position:sticky; top:80px; }
.fsb-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:14px; }
.fsb-title { font-size:.72rem; font-weight:700; text-transform:uppercase; letter-spacing:.07em; color:#64748b; padding:10px 14px 6px; border-bottom:1px solid #f1f5f9; }
.fsb-link { display:flex; align-items:center; gap:8px; padding:9px 14px; font-size:.82rem; color:#334155; text-decoration:none; border-bottom:1px solid #f8fafc; transition:.12s; font-weight:500; }
.fsb-link:hover { background:#eff6ff; color:#2563eb; }
.fsb-link.fsb-ativo { background:#eff6ff; color:#2563eb; font-weight:700; border-left:3px solid #2563eb; }
.fsb-link:last-child { border-bottom:none; }
.fsb-ad { margin-bottom:14px; }
.fsb-ad-placeholder { background:#f8fafc; border:1px dashed #cbd5e1; border-radius:12px; min-height:100px; display:flex; align-items:center; justify-content:center; flex-direction:column; gap:4px; }
</style>

<div class="col-lg-3 d-none d-lg-block">
  <div class="forum-sidebar">

    <!-- Busca rápida -->
    <div class="fsb-card">
      <div class="fsb-title"><i class="bi bi-search me-1"></i>Buscar no Fórum</div>
      <div style="padding:10px 14px">
        <form method="GET" action="<?= $_baseF ?>/forum/buscar">
          <div class="input-group input-group-sm">
            <input type="search" name="q" class="form-control" placeholder="Defeito, marca, modelo...">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
          </div>
        </form>
      </div>
    </div>

    <!-- Categorias -->
    <?php if (!empty($_catsForum)): ?>
    <div class="fsb-card">
      <div class="fsb-title"><i class="bi bi-grid-3x3-gap me-1"></i>Categorias</div>
      <?php foreach ($_catsForum as $_c):
        $_ativo = ($_uriF === '/forum/categoria/'.$_c['id']) ? 'fsb-ativo' : ''; ?>
      <a class="fsb-link <?= $_ativo ?>" href="<?= $_baseF ?>/forum/categoria/<?= $_c['id'] ?>">
        <i class="bi <?= e($_c['icone'] ?: 'bi-chat-dots') ?>" style="color:<?= e($_c['cor'] ?: '#0d6efd') ?>"></i>
        <?= e($_c['nome']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Blocos de anúncio: só aparecem quando há código cadastrado em /master/adsense.
         (Com Auto Ads ligado, o Google também injeta anúncios automaticamente após aprovação.) -->
    <?php for ($p=1; $p<=2; $p++): if (!empty($_adForum[$p])): ?>
    <div class="fsb-ad"><?= $_adForum[$p] ?></div>
    <?php endif; endfor; ?>

  </div>
</div>
