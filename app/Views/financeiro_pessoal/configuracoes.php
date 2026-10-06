<?php if (!$liberado): ?>
<div class="fp-card" style="text-align:center;padding:40px 24px">
  <div style="font-size:2.2rem;margin-bottom:10px">🔒</div>
  <h1 style="font-size:1.15rem;margin:0 0 8px">Financeiro pessoal ainda não está liberado</h1>
  <p class="fp-muted" style="font-size:.9rem;line-height:1.5;margin:0 0 20px">
    Esse recurso é liberado pra empresas que <strong>reivindicaram</strong> a ficha no Diretório
    e estão nos planos <strong>Oficina</strong> ou <strong>Top Empresa</strong>. Fale com a FixaOS
    se quiser saber mais.
  </p>
  <div style="display:flex;flex-direction:column;gap:10px;max-width:280px;margin:0 auto">
    <a href="<?= url('/logout') ?>" class="fp-btn fp-btn-primary" style="text-decoration:none;display:inline-block">
      Entrar com outra conta
    </a>
    <a href="<?= url('/assistencias') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none;display:inline-block">
      Ver o Diretório
    </a>
  </div>
</div>

<?php else: ?>

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting">Configurações</h1>
    <div class="fp-faint">Sua foto aparece na barra lateral do fixa</div>
  </div>
  <a href="<?= url('/financeiro-pessoal') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none">← Voltar</a>
</div>

<?php $flashOk = flash('success'); $flashErr = flash('error'); ?>
<?php if ($flashOk): ?><div class="fp-card" style="margin-bottom:16px;color:var(--inc);font-weight:700"><?= e($flashOk) ?></div><?php endif; ?>
<?php if ($flashErr): ?><div class="fp-card" style="margin-bottom:16px;color:var(--exp);font-weight:700"><?= e($flashErr) ?></div><?php endif; ?>

<?php $avatarUrl = financeiro_pessoal_avatar_url($avatar); ?>
<div class="fp-card" style="max-width:420px">
  <div class="fp-section-titulo" style="margin-bottom:14px">Sua foto</div>
  <div style="display:flex;align-items:center;gap:16px;margin-bottom:18px">
    <div id="fpAvatarPreviewWrap" style="width:72px;height:72px;border-radius:50%;overflow:hidden;background:var(--surf2);flex:0 0 auto;display:flex;align-items:center;justify-content:center">
      <?php if ($avatarUrl): ?>
      <img id="fpAvatarPreview" src="<?= e($avatarUrl) ?>" alt="Sua foto" style="width:100%;height:100%;object-fit:cover">
      <?php else: ?>
      <span id="fpAvatarPreviewVazio" class="fp-faint" style="font-size:1.6rem">🙂</span>
      <img id="fpAvatarPreview" style="width:100%;height:100%;object-fit:cover;display:none">
      <?php endif; ?>
    </div>
    <div class="fp-faint" style="font-size:.82rem;line-height:1.5">
      JPG, PNG, WebP, GIF ou BMP, até 8MB.<br>A foto é cortada em círculo automaticamente.
    </div>
  </div>
  <form method="POST" action="<?= url('/financeiro-pessoal/configuracoes/avatar') ?>" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:10px">
    <?= csrf_field() ?>
    <input type="file" name="avatar" id="fpAvatarInput" accept="image/*" class="fp-input" required>
    <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvarAvatar">Salvar foto</button>
  </form>
</div>

<script>
(function () {
  // Prévia antes de enviar — o upload em si continua sendo um POST tradicional (sem fetch),
  // só a troca da imagem mostrada é instantânea via FileReader, mesmo padrão já usado em
  // outras telas de upload do sistema (ex.: produtos/form.php).
  var input = document.getElementById('fpAvatarInput');
  var preview = document.getElementById('fpAvatarPreview');
  var previewVazio = document.getElementById('fpAvatarPreviewVazio');
  input.addEventListener('change', function () {
    if (!input.files.length) return;
    var reader = new FileReader();
    reader.onload = function (e) {
      preview.src = e.target.result;
      preview.style.display = 'block';
      if (previewVazio) previewVazio.style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  });

  var form = input.closest('form');
  var btn = document.getElementById('fpBtnSalvarAvatar');
  form.addEventListener('submit', function () {
    btn.disabled = true;
    btn.textContent = 'Salvando...';
  });
})();
</script>

<?php endif; ?>
