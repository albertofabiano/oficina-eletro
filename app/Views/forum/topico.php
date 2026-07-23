<?php require __DIR__ . '/menu.php'; ?>

<div class="row g-4">
<?php require __DIR__ . '/sidebar.php'; ?>
<div class="col-lg-9">

<style>
.resposta-card { border-radius:12px; border:1px solid #e2e8f0; }
.resposta-card.melhor { border-color:#16a34a; background:#f0fdf4; }
.avatar-forum { width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:.85rem;flex-shrink:0; }
.arquivo-item { border-radius:8px; border:1px solid #e2e8f0; padding:.6rem 1rem; display:flex; align-items:center; gap:.7rem; transition:.15s; }
.arquivo-item:hover { background:#f8fafc; }
.conteudo-forum { white-space:pre-line; word-break:break-word; font-weight:400; font-size:.95rem; }
</style>

<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small">
    <li class="breadcrumb-item"><a href="<?= url('/forum') ?>">Fórum</a></li>
    <li class="breadcrumb-item">
      <a href="<?= url('/forum/categoria/' . $topico['cat_id']) ?>"><?= e($topico['cat_nome']) ?></a>
    </li>
    <li class="breadcrumb-item active text-truncate"><?= e(mb_substr($topico['titulo'], 0, 50)) ?></li>
  </ol>
</nav>

<!-- Tópico principal -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white d-flex align-items-start justify-content-between gap-2 flex-wrap py-3">
    <div class="flex-grow-1">
      <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
        <span class="badge rounded-pill" style="background:<?= e($topico['cat_cor']) ?>22;color:<?= e($topico['cat_cor']) ?>">
          <i class="bi <?= e($topico['cat_icone']) ?> me-1"></i><?= e($topico['cat_nome']) ?>
        </span>
        <?php if ($topico['resolvido']): ?>
        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Resolvido</span>
        <?php endif; ?>
        <?php if ($topico['fixado']): ?>
        <span class="badge bg-warning text-dark"><i class="bi bi-pin-fill me-1"></i>Fixado</span>
        <?php endif; ?>
        <?php if ($topico['marca'] || $topico['modelo']): ?>
        <span class="badge bg-light text-dark border">
          <i class="bi bi-cpu me-1"></i><?= e(trim($topico['marca'].' '.$topico['modelo'])) ?>
        </span>
        <?php endif; ?>
        <?php if (!empty($topico['versao_firmware'])): ?>
        <span class="badge bg-info text-dark border">
          <i class="bi bi-code-slash me-1"></i>v<?= e($topico['versao_firmware']) ?>
        </span>
        <?php endif; ?>
        <?php if (!empty($topico['url_externa'])): ?>
        <a href="<?= e($topico['url_externa']) ?>" target="_blank" rel="nofollow"
           class="badge bg-success text-white text-decoration-none">
          <i class="bi bi-download me-1"></i>Download externo
        </a>
        <?php endif; ?>
      </div>
      <h5 class="fw-bold mb-0"><?= e($topico['titulo']) ?></h5>
    </div>
    <div class="d-flex gap-2">
      <?php if ($topico['usuario_id'] == \App\Core\Auth::id() || \App\Core\Auth::perfil() === 'admin'): ?>
      <button class="btn btn-sm btn-outline-danger"
              data-method="DELETE"
              data-href="<?= url('/forum/topico/' . $topico['id'] . '/excluir') ?>"
              data-confirm="Excluir este tópico permanentemente?">
        <i class="bi bi-trash"></i>
      </button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card-body">
    <div class="d-flex gap-3">
      <!-- Avatar -->
      <div class="avatar-forum bg-primary text-white flex-shrink-0">
        <?= avatar_iniciais($topico['autor_nome']) ?>
      </div>
      <!-- Conteúdo -->
      <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          <span class="fw-semibold"><?= e($topico['autor_nome']) ?></span>
          <?php
            $perfil = $topico['autor_perfil'] ?? 'tecnico';
            $badgeClass = match($perfil) {
              'tecnico' => 'badge-perfil-tecnico',
              'admin','superadmin' => 'badge-perfil-admin',
              default => 'badge-perfil-default'
            };
            $badgeLabel = match($perfil) {
              'tecnico' => 'Técnico', 'admin','superadmin' => 'Admin',
              'gerente' => 'Gerente', default => ucfirst($perfil)
            };
          ?>
          <span class="badge rounded-pill <?= $badgeClass ?>"><?= $badgeLabel ?></span>
          <span class="text-muted small">— <?= e($topico['empresa_nome']) ?></span>
          <span class="text-muted small ms-auto">
            <i class="bi bi-clock me-1"></i><?= date_br($topico['criado_em'], true) ?>
          </span>
        </div>
        <?php
        $txt = $topico['conteudo'];
        $txt = preg_replace('/^#{1,6}\s*/m', '', $txt);
        $txt = preg_replace('/^-{3,}$/m', '', $txt);
        $txt = preg_replace('/\*\*(.+?)\*\*/s', '$1', $txt);
        $txt = preg_replace('/\*(.+?)\*/s', '$1', $txt);
        $txt = preg_replace('/\n{2,}/', "\n", $txt);
        $txt = preg_replace('/^[ \t]+/m', '', $txt);
        $txt = trim($txt);
        ?>
        <div class="conteudo-forum text-dark mb-3" style="font-weight:500;line-height:1.5"><?= nl2br(e($txt)) ?></div>

        <!-- Arquivos do tópico -->
        <?php if ($arquivos): ?>
        <div class="mb-3">
          <div class="small fw-semibold text-muted mb-2">
            <i class="bi bi-paperclip me-1"></i>Arquivos anexados:
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach ($arquivos as $arq): ?>
            <?php
              $ext = strtoupper(pathinfo($arq['nome_original'], PATHINFO_EXTENSION));
              $isImg = in_array($ext, ['JPG','JPEG','PNG','WEBP','GIF']);
              $isPdf = $ext === 'PDF';
              $icone = $isPdf ? 'bi-file-pdf text-danger' : ($isImg ? 'bi-file-image text-primary' : 'bi-file-earmark-zip text-warning');
              $size  = $arq['tamanho'] > 1024*1024 ? round($arq['tamanho']/1024/1024,1).'MB' : round($arq['tamanho']/1024).'KB';
            ?>
            <a href="<?= url('/forum/download/' . $arq['id']) ?>" class="arquivo-item text-decoration-none text-dark">
              <i class="bi <?= $icone ?> fs-4"></i>
              <div>
                <div class="fw-semibold small"><?= e($arq['nome_original']) ?></div>
                <div class="text-muted" style="font-size:.72rem"><?= $size ?> · <?= $arq['downloads'] ?> downloads</div>
              </div>
              <i class="bi bi-download text-muted ms-2"></i>
            </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <!-- Curtir tópico -->
        <div class="d-flex align-items-center gap-3 pt-2 border-top">
          <button class="btn btn-sm <?= $curtiu ? 'btn-primary' : 'btn-outline-secondary' ?> btn-curtir"
                  data-topico="<?= $topico['id'] ?>"
                  onclick="curtir(this)">
            <i class="bi bi-hand-thumbs-up me-1"></i>
            <span class="num-curtidas"><?= $topico['total_curtidas'] ?? 0 ?></span>
          </button>
          <span class="text-muted small">
            <i class="bi bi-eye me-1"></i><?= $topico['visualizacoes'] ?> visualizações
          </span>
          <span class="text-muted small">
            <i class="bi bi-chat me-1"></i><?= count($respostas) ?> resposta(s)
          </span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Respostas -->
<?php if ($respostas): ?>
<h6 class="fw-bold mb-3 text-muted">
  <i class="bi bi-chat-dots me-1"></i><?= count($respostas) ?> Resposta(s)
</h6>
<?php foreach ($respostas as $r): ?>
<div class="resposta-card card border-0 shadow-sm mb-3 <?= $r['melhor_resposta'] ? 'melhor' : '' ?>" id="resposta-<?= $r['id'] ?>">
  <?php if ($r['melhor_resposta']): ?>
  <div class="px-3 pt-2 pb-0">
    <span class="badge bg-success"><i class="bi bi-patch-check-fill me-1"></i>Melhor Resposta</span>
  </div>
  <?php endif; ?>
  <div class="card-body">
    <div class="d-flex gap-3">
      <div class="avatar-forum bg-secondary text-white">
        <?= avatar_iniciais($r['autor_nome']) ?>
      </div>
      <div class="flex-grow-1">
        <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
          <span class="fw-semibold"><?= e($r['autor_nome']) ?></span>
          <?php
            $rp = $r['autor_perfil'] ?? 'tecnico';
            $rBadge = match($rp) { 'tecnico'=>'badge-perfil-tecnico','admin','superadmin'=>'badge-perfil-admin',default=>'badge-perfil-default' };
            $rLabel = match($rp) { 'tecnico'=>'Técnico','admin','superadmin'=>'Admin','gerente'=>'Gerente',default=>ucfirst($rp) };
          ?>
          <span class="badge rounded-pill <?= $rBadge ?>"><?= $rLabel ?></span>
          <span class="text-muted small">— <?= e($r['empresa_nome']) ?></span>
          <span class="text-muted small ms-auto">
            <i class="bi bi-clock me-1"></i><?= date_br($r['criado_em'], true) ?>
          </span>
        </div>
        <div class="conteudo-forum text-dark mb-3"><?= nl2br(e($r['conteudo'])) ?></div>

        <!-- Arquivos da resposta -->
        <?php
        $arqResposta = array_filter($arquivos ?? [], fn($a) => $a['forum_resposta_id'] == $r['id']);
        if ($arqResposta): ?>
        <div class="d-flex flex-wrap gap-2 mb-3">
          <?php foreach ($arqResposta as $arq): ?>
          <?php
            $ext = strtoupper(pathinfo($arq['nome_original'], PATHINFO_EXTENSION));
            $isPdf = $ext === 'PDF';
            $isImg = in_array($ext, ['JPG','JPEG','PNG','WEBP','GIF']);
            $icone = $isPdf ? 'bi-file-pdf text-danger' : ($isImg ? 'bi-file-image text-primary' : 'bi-file-earmark-zip text-warning');
            $size  = $arq['tamanho'] > 1024*1024 ? round($arq['tamanho']/1024/1024,1).'MB' : round($arq['tamanho']/1024).'KB';
          ?>
          <a href="<?= url('/forum/download/' . $arq['id']) ?>" class="arquivo-item text-decoration-none text-dark">
            <i class="bi <?= $icone ?> fs-5"></i>
            <div>
              <div class="fw-semibold small"><?= e($arq['nome_original']) ?></div>
              <div class="text-muted" style="font-size:.72rem"><?= $size ?></div>
            </div>
            <i class="bi bi-download text-muted"></i>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Ações da resposta -->
        <div class="d-flex align-items-center gap-2 pt-2 border-top flex-wrap">
          <button class="btn btn-sm <?= $r['curtiu'] ? 'btn-primary' : 'btn-outline-secondary' ?> btn-curtir"
                  data-resposta="<?= $r['id'] ?>" onclick="curtir(this)">
            <i class="bi bi-hand-thumbs-up me-1"></i>
            <span class="num-curtidas"><?= $r['total_curtidas'] ?></span>
          </button>
          <?php if ($topico['usuario_id'] == \App\Core\Auth::id() && !$topico['resolvido']): ?>
          <button class="btn btn-sm btn-outline-success" onclick="marcarMelhor(<?= $r['id'] ?>)">
            <i class="bi bi-patch-check me-1"></i>Melhor Resposta
          </button>
          <?php endif; ?>
          <?php if ($r['empresa_id'] == \App\Core\Auth::empresaId() || \App\Core\Auth::perfil() === 'admin'): ?>
          <button class="btn btn-sm btn-outline-danger ms-auto" onclick="excluirResposta(<?= $r['id'] ?>)">
            <i class="bi bi-trash"></i>
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endforeach; ?>
<?php endif; ?>

<!-- Form responder ou CTA de login -->
<?php if (\App\Core\Auth::check()): ?>
<div class="card border-0 shadow-sm mt-4" id="formResponder">
  <div class="card-header bg-white fw-semibold">
    <i class="bi bi-reply me-2 text-primary"></i>Sua resposta
  </div>
  <div class="card-body">
    <form method="POST" action="<?= ($appUrl ?? url('')) ?>/forum/topico/<?= $topico['id'] ?>/responder"
          enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="mb-3">
        <textarea name="conteudo" class="form-control" rows="5" required
          placeholder="Escreva sua resposta, solução ou comentário..."></textarea>
      </div>
      <div class="mb-3">
        <label class="form-label small fw-semibold">
          <i class="bi bi-paperclip me-1"></i>Anexar arquivos (opcional)
        </label>
        <input type="file" name="arquivos[]" class="form-control" multiple
          accept=".jpg,.jpeg,.png,.webp,.pdf,.zip,.rar,.bin,.fw,.rom,.hex,.txt">
        <div class="form-text">Imagens, PDF, ZIP, BIN, ROM, HEX, TXT — máx 50MB por arquivo</div>
      </div>
      <button type="submit" class="btn btn-primary fw-semibold">
        <i class="bi bi-send me-1"></i>Publicar Resposta
      </button>
    </form>
  </div>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm mt-4" style="background:linear-gradient(135deg,#0f172a,#1e3a5f)">
  <div class="card-body text-center py-5 text-white">
    <i class="bi bi-chat-dots fs-1 d-block mb-3 opacity-75"></i>
    <h5 class="fw-bold">Quer responder ou compartilhar sua solução?</h5>
    <p class="text-white-50 mb-4">Cadastre-se gratuitamente e faça parte da comunidade de técnicos.</p>
    <div class="d-flex gap-3 justify-content-center">
      <a href="<?= $appUrl ?>/forum/cadastrar" class="btn btn-primary fw-semibold px-4">
        <i class="bi bi-person-plus me-1"></i>Cadastrar grátis
      </a>
      <a href="<?= $appUrl ?>/login" class="btn btn-outline-light px-4">
        <i class="bi bi-box-arrow-in-right me-1"></i>Entrar
      </a>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
const CSRF = '<?= csrf_token() ?>';

async function curtir(btn) {
  const topicoId   = btn.dataset.topico;
  const respostaId = btn.dataset.resposta;
  const resp = await fetch('<?= url('/forum/curtir') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ topico_id: topicoId || null, resposta_id: respostaId || null, csrf_token: CSRF }),
  });
  const j = await resp.json();
  if (j.success) {
    btn.className = `btn btn-sm ${j.curtiu ? 'btn-primary' : 'btn-outline-secondary'} btn-curtir`;
    btn.querySelector('.num-curtidas').textContent = j.total;
  }
}

async function marcarMelhor(id) {
  if (!confirm('Marcar esta resposta como a melhor solução?')) return;
  const resp = await fetch(`<?= url('/forum/resposta/') ?>${id}/melhor`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ csrf_token: CSRF }),
  });
  const j = await resp.json();
  if (j.success) location.reload();
  else alert('Erro: ' + (j.error ?? 'desconhecido'));
}

async function excluirResposta(id) {
  if (!confirm('Excluir esta resposta?')) return;
  const resp = await fetch(`<?= url('/forum/resposta/') ?>${id}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ _method: 'DELETE', csrf_token: CSRF }),
  });
  const j = await resp.json();
  if (j.success) document.getElementById('resposta-' + id)?.remove();
}
</script>

</div><!-- /col-lg-9 -->
</div><!-- /row -->
