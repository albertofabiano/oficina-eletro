<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Blocos de Anúncio — Master</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body{background:#0f1117;color:#e0e0e0}
.ad-card{background:#141720;border:1px solid rgba(255,255,255,.07);border-radius:12px;margin-bottom:16px}
.ad-card .card-header{background:transparent;border-bottom:1px solid rgba(255,255,255,.07);color:#e0e0e0}
.form-label{color:#9ca3af!important}
.form-text{color:#6b7280!important}
label{color:#9ca3af!important}
.text-muted{color:#9ca3af!important}
.form-check-label{color:#9ca3af!important}
.form-control,.form-select{background:rgba(255,255,255,.05)!important;border:1px solid rgba(255,255,255,.1)!important;color:#e0e0e0!important}
.form-control::placeholder{color:#555}
.nav-tabs .nav-link{color:#6c757d;border:none;border-bottom:2px solid transparent}
.nav-tabs .nav-link.active{color:#fff;border-bottom:2px solid #dc3545;background:transparent}
.nav-tabs{border-bottom:1px solid rgba(255,255,255,.1)}
.badge-ativo{background:#16a34a}
.badge-inativo{background:#374151;color:#9ca3af}
</style>
</head>
<body>

<div class="container py-4" style="max-width:900px">
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="/master" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i></a>
    <div>
      <h4 class="fw-bold mb-0 text-white"><i class="bi bi-megaphone me-2 text-danger"></i>Blocos de Anúncio</h4>
      <small class="text-muted">Gerencie os blocos de anúncio do Marketplace e do Fórum</small>
    </div>
  </div>

  <div class="alert alert-dark border-secondary small mb-4">
    <i class="bi bi-info-circle me-1 text-info"></i>
    Cole o código do <strong>Google AdSense</strong>, banner HTML ou iframe em cada bloco. Blocos inativos não aparecem.
  </div>

  <!-- Tabs -->
  <ul class="nav nav-tabs mb-4">
    <li class="nav-item">
      <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabMarketplace">
        <i class="bi bi-shop me-1"></i>Marketplace <span class="badge bg-secondary ms-1"><?= count($marketplace) ?> blocos</span>
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabForum">
        <i class="bi bi-chat-dots me-1"></i>Fórum <span class="badge bg-secondary ms-1"><?= count($forum) ?> blocos</span>
      </button>
    </li>
    <li class="nav-item">
      <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabDiretorio">
        <i class="bi bi-geo-alt me-1"></i>Diretório <span class="badge bg-secondary ms-1"><?= count($diretorio) ?> blocos</span>
      </button>
    </li>
  </ul>

  <div class="tab-content">

    <!-- MARKETPLACE -->
    <div class="tab-pane fade show active" id="tabMarketplace">
      <p class="text-muted small mb-3">3 blocos exibidos na sidebar esquerda do Marketplace público (<code>/pecas</code>)</p>
      <?php foreach ($marketplace as $b): renderBloco($b); endforeach; ?>
    </div>

    <!-- FÓRUM -->
    <div class="tab-pane fade" id="tabForum">
      <p class="text-muted small mb-3">5 blocos exibidos na sidebar do Fórum Técnico (<code>/forum</code>)</p>
      <?php foreach ($forum as $b): renderBloco($b); endforeach; ?>
    </div>

    <!-- DIRETÓRIO -->
    <div class="tab-pane fade" id="tabDiretorio">
      <p class="text-muted small mb-3">5 blocos exibidos na área de anúncios do Diretório de Assistências (<code>/assistencias</code>)</p>
      <?php foreach ($diretorio as $b): renderBloco($b); endforeach; ?>
    </div>

  </div>
</div>

<?php function renderBloco(array $b): void { ?>
<div class="ad-card">
  <div class="card-header d-flex align-items-center justify-content-between py-2 px-3">
    <span class="fw-semibold">
      <i class="bi bi-layout-sidebar-reverse me-2 text-danger"></i>
      Bloco <?= $b['posicao'] ?> — <?= htmlspecialchars($b['local'],ENT_QUOTES,'UTF-8') ?>
    </span>
    <span class="badge <?= $b['ativo'] ? 'badge-ativo' : 'badge-inativo' ?>">
      <?= $b['ativo'] ? 'Ativo' : 'Inativo' ?>
    </span>
  </div>
  <div class="p-3">
    <div class="mb-3">
      <label class="form-label small fw-semibold text-muted">Título <span class="fw-normal">(interno)</span></label>
      <input type="text" class="form-control form-control-sm"
             id="titulo_<?= $b['local'] ?>_<?= $b['posicao'] ?>"
             value="<?= htmlspecialchars($b['titulo']??'',ENT_QUOTES,'UTF-8') ?>"
             placeholder="Ex: Banner Superior, AdSense 300x250...">
    </div>
    <div class="mb-3">
      <label class="form-label small fw-semibold text-muted">Código HTML/JS do anúncio</label>
      <textarea class="form-control" rows="5"
                id="codigo_<?= $b['local'] ?>_<?= $b['posicao'] ?>"
                placeholder="<!-- Cole aqui o código do Google AdSense -->"
                style="font-size:.78rem;font-family:monospace"><?= htmlspecialchars($b['codigo']??'',ENT_QUOTES,'UTF-8') ?></textarea>
    </div>
    <div class="d-flex align-items-center justify-content-between">
      <div class="form-check form-switch">
        <input class="form-check-input" type="checkbox"
               id="ativo_<?= $b['local'] ?>_<?= $b['posicao'] ?>"
               <?= $b['ativo'] ? 'checked' : '' ?>>
        <label class="form-check-label small text-muted"
               for="ativo_<?= $b['local'] ?>_<?= $b['posicao'] ?>">
          Exibir este bloco
        </label>
      </div>
      <button class="btn btn-danger btn-sm fw-semibold"
              onclick="salvar('<?= $b['local'] ?>',<?= $b['posicao'] ?>)"
              id="btn_<?= $b['local'] ?>_<?= $b['posicao'] ?>">
        <i class="bi bi-check-lg me-1"></i>Salvar
      </button>
    </div>
  </div>
</div>
<?php } ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
<?php
// Token CSRF da sessão atual
$tok = $_SESSION['_token'] ?? '';
?>
const CSRF = '<?= $tok ?>';

async function salvar(local, pos) {
  const key = local + '_' + pos;
  const btn  = document.getElementById('btn_' + key);
  const titulo = document.getElementById('titulo_' + key).value;
  const codigo = document.getElementById('codigo_' + key).value;
  const ativo  = document.getElementById('ativo_' + key).checked ? 1 : 0;

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';

  try {
    const resp = await fetch('/master/adsense', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({ local, posicao: pos, titulo, codigo, ativo, csrf_token: CSRF }),
    });
    const j = await resp.json();
    if (j.success) {
      btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvo!';
      btn.classList.replace('btn-danger','btn-success');
      setTimeout(() => {
        btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
        btn.classList.replace('btn-success','btn-danger');
        btn.disabled = false;
      }, 2000);
    } else {
      alert('Erro: ' + (j.error||'desconhecido'));
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
    }
  } catch(e) {
    alert('Erro de conexão');
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
  }
}
</script>
</body>
</html>
