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
    <div class="fp-faint">Sua foto aparece na barra lateral do Fixa</div>
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
      <!-- Mesmo fallback do quadrado da marca na sidebar: se o arquivo não carregar, cai pro
           emoji de sempre em vez do ícone de "imagem quebrada" do navegador. -->
      <span id="fpAvatarPreviewVazio" class="fp-faint" style="font-size:1.6rem;display:none">🙂</span>
      <img id="fpAvatarPreview" src="<?= e($avatarUrl) ?>" alt="Sua foto" style="width:100%;height:100%;object-fit:cover" onerror="this.style.display='none';document.getElementById('fpAvatarPreviewVazio').style.display='block'">
      <?php else: ?>
      <span id="fpAvatarPreviewVazio" class="fp-faint" style="font-size:1.6rem">🙂</span>
      <img id="fpAvatarPreview" style="width:100%;height:100%;object-fit:cover;display:none">
      <?php endif; ?>
    </div>
    <div class="fp-faint" style="font-size:.82rem;line-height:1.5">
      JPG, PNG, WebP, GIF ou BMP, até 8MB.<br>Arraste e use o zoom pra ajustar o enquadramento.
    </div>
  </div>
  <form method="POST" action="<?= url('/financeiro-pessoal/configuracoes/avatar') ?>" enctype="multipart/form-data" style="display:flex;flex-direction:column;gap:10px">
    <?= csrf_field() ?>
    <input type="file" name="avatar" id="fpAvatarInput" accept="image/*" class="fp-input" required>
    <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvarAvatar">Salvar foto</button>
  </form>
</div>

<!-- Sino de notificação (eventos da Agenda chegando no horário) — pedido do usuário: liga/
     desliga som+popup, e configura quanto tempo o popup fica na tela. O sino em si (badge +
     painel) continua funcionando mesmo com isso desligado; só o alerta ativo (som + toast)
     depende deste toggle, ver layouts/financeiro_pessoal.php. -->
<div class="fp-card" style="max-width:420px;margin-top:16px">
  <div class="fp-section-titulo" style="margin-bottom:4px">Notificações</div>
  <p class="fp-faint" style="font-size:.82rem;line-height:1.5;margin:0 0 16px">
    Avisa quando um evento da sua Agenda chega no horário.
  </p>
  <form method="POST" action="<?= url('/financeiro-pessoal/configuracoes/notificacoes') ?>" style="display:flex;flex-direction:column;gap:14px">
    <?= csrf_field() ?>
    <label style="display:flex;align-items:center;gap:10px;cursor:pointer">
      <input type="checkbox" name="notif_som" value="1" <?= $notifSom ? 'checked' : '' ?> style="width:18px;height:18px;accent-color:var(--accent);cursor:pointer">
      <span style="font-size:.9rem">Tocar som e mostrar aviso na tela</span>
    </label>
    <div>
      <label for="fpNotifTempo" style="display:block;font-size:.82rem;color:var(--muted);margin-bottom:6px">Tempo que o aviso fica na tela (segundos)</label>
      <input type="number" name="notif_tempo" id="fpNotifTempo" class="fp-input" min="2" max="30" value="<?= (int) $notifTempo ?>" style="max-width:120px">
    </div>
    <button type="submit" class="fp-btn fp-btn-primary" style="align-self:flex-start">Salvar preferências</button>
  </form>
</div>

<!-- Enquadramento: recorte + zoom em canvas puro (sem lib nenhuma — mesma isolação do resto
     do "fixa", nunca carregou Bootstrap JS ou Cropper.js como o resto do FixaOS carrega pro
     editor de logo). Resultado é sempre um quadrado (não precisa recortar em círculo de
     verdade — a exibição já arredonda via CSS object-fit+border-radius em todo lugar que o
     avatar aparece), então o canvas de saída não precisa de transparência. -->
<div class="fp-modal-backdrop" id="modalAvatarCrop">
  <div class="fp-modal" style="max-width:360px;text-align:center">
    <div class="fp-modal-header">
      <strong>Ajustar enquadramento</strong>
      <button type="button" class="fp-modal-close" id="btnFecharAvatarCrop" aria-label="Fechar">×</button>
    </div>
    <p class="fp-faint" style="font-size:.78rem;margin:0 0 10px">Arraste a foto pra posicionar e use o controle abaixo pra aproximar.</p>
    <div id="avatarCropViewport" style="width:240px;height:240px;border-radius:50%;overflow:hidden;background:var(--surf2);margin:0 auto;position:relative;cursor:grab;border:2px solid var(--line)">
      <img id="avatarCropImg" style="position:absolute;max-width:none;user-select:none;-webkit-user-drag:none" draggable="false" alt="">
    </div>
    <input type="range" id="avatarCropZoom" min="0" max="100" value="0" style="width:100%;margin-top:16px">
    <div style="display:flex;gap:8px;justify-content:center;margin-top:14px">
      <button type="button" class="fp-btn fp-btn-ghost" id="btnCancelarAvatarCrop">Cancelar</button>
      <button type="button" class="fp-btn fp-btn-primary" id="btnAplicarAvatarCrop">Aplicar</button>
    </div>
  </div>
</div>

<script>
(function () {
  var input = document.getElementById('fpAvatarInput');
  var preview = document.getElementById('fpAvatarPreview');
  var previewVazio = document.getElementById('fpAvatarPreviewVazio');
  var modal = document.getElementById('modalAvatarCrop');
  var viewport = document.getElementById('avatarCropViewport');
  var cropImg = document.getElementById('avatarCropImg');
  var zoomSlider = document.getElementById('avatarCropZoom');
  var VIEWPORT = 240;  // mesmo valor do width/height inline do #avatarCropViewport
  var OUTPUT = 480;    // resolução do arquivo final salvo

  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }

  // Estado do recorte atual — natural* vem do arquivo escolhido; minScale é o zoom em que a
  // imagem já cobre o círculo inteiro (equivalente a object-fit:cover), serve de piso: nunca
  // deixa dar zoom OUT a ponto de sobrar fundo vazio dentro do círculo.
  var naturalW = 0, naturalH = 0, minScale = 1, scale = 1, offsetX = 0, offsetY = 0;

  function aplicarTransform() {
    var dispW = naturalW * scale;
    var dispH = naturalH * scale;
    var left = (VIEWPORT - dispW) / 2 + offsetX;
    var top = (VIEWPORT - dispH) / 2 + offsetY;
    cropImg.style.width = dispW + 'px';
    cropImg.style.height = dispH + 'px';
    cropImg.style.left = left + 'px';
    cropImg.style.top = top + 'px';
  }

  // Nunca deixa a imagem descolar da borda do círculo (sobraria fundo vazio) — clampa o
  // deslocamento aos limites exatos em que a imagem ainda cobre o viewport inteiro.
  function clampOffsets() {
    var dispW = naturalW * scale;
    var dispH = naturalH * scale;
    var maxX = Math.max(0, (dispW - VIEWPORT) / 2);
    var maxY = Math.max(0, (dispH - VIEWPORT) / 2);
    offsetX = Math.min(maxX, Math.max(-maxX, offsetX));
    offsetY = Math.min(maxY, Math.max(-maxY, offsetY));
  }

  function setScale(novaScale) {
    scale = Math.max(minScale, novaScale);
    clampOffsets();
    aplicarTransform();
  }

  zoomSlider.addEventListener('input', function () {
    // Slider 0–100 mapeado pra [minScale, minScale*3] — zoom até 3x o "cobre tudo" inicial,
    // suficiente pra aproximar o rosto numa foto bem aberta sem precisar de um teto maior.
    var t = zoomSlider.value / 100;
    setScale(minScale + t * (minScale * 3 - minScale));
  });

  // Arrastar (mouse e touch) — um listener só, cobrindo os dois via pointer events.
  var arrastando = false, inicioX = 0, inicioY = 0, offsetInicialX = 0, offsetInicialY = 0;
  viewport.addEventListener('pointerdown', function (ev) {
    arrastando = true;
    viewport.style.cursor = 'grabbing';
    inicioX = ev.clientX; inicioY = ev.clientY;
    offsetInicialX = offsetX; offsetInicialY = offsetY;
    viewport.setPointerCapture(ev.pointerId);
  });
  viewport.addEventListener('pointermove', function (ev) {
    if (!arrastando) return;
    offsetX = offsetInicialX + (ev.clientX - inicioX);
    offsetY = offsetInicialY + (ev.clientY - inicioY);
    clampOffsets();
    aplicarTransform();
  });
  function pararArraste() { arrastando = false; viewport.style.cursor = 'grab'; }
  viewport.addEventListener('pointerup', pararArraste);
  viewport.addEventListener('pointercancel', pararArraste);

  var arquivoOriginalNome = 'foto.jpg';

  function abrirCropAvatar(file) {
    arquivoOriginalNome = file.name || 'foto.jpg';
    var reader = new FileReader();
    reader.onload = function (e) {
      cropImg.onload = function () {
        naturalW = cropImg.naturalWidth;
        naturalH = cropImg.naturalHeight;
        minScale = Math.max(VIEWPORT / naturalW, VIEWPORT / naturalH);
        offsetX = 0; offsetY = 0;
        zoomSlider.value = 0;
        setScale(minScale);
        abrirModal(modal);
      };
      cropImg.src = e.target.result;
    };
    reader.readAsDataURL(file);
  }

  input.addEventListener('change', function () {
    if (!input.files.length) return;
    abrirCropAvatar(input.files[0]);
  });

  function aplicarCropAvatar() {
    // Mesma matemática de aplicarTransform(), só que resolvendo o retângulo de origem (em
    // pixel NATURAL da imagem) que cai dentro do viewport, pra recortar exatamente o que a
    // pessoa vê no círculo — escalado pra resolução de saída (OUTPUT).
    var dispW = naturalW * scale;
    var dispH = naturalH * scale;
    var left = (VIEWPORT - dispW) / 2 + offsetX;
    var top = (VIEWPORT - dispH) / 2 + offsetY;
    var srcX = -left / scale;
    var srcY = -top / scale;
    var srcSize = VIEWPORT / scale;

    var canvas = document.createElement('canvas');
    canvas.width = OUTPUT; canvas.height = OUTPUT;
    var ctx = canvas.getContext('2d');
    ctx.drawImage(cropImg, srcX, srcY, srcSize, srcSize, 0, 0, OUTPUT, OUTPUT);

    canvas.toBlob(function (blob) {
      if (!blob) return;
      var file = new File([blob], arquivoOriginalNome.replace(/\.[^.]+$/, '') + '.jpg', { type: 'image/jpeg' });
      var dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;

      preview.src = canvas.toDataURL('image/jpeg', 0.9);
      preview.style.display = 'block';
      if (previewVazio) previewVazio.style.display = 'none';

      fecharModal(modal);
    }, 'image/jpeg', 0.9);
  }

  document.getElementById('btnAplicarAvatarCrop').onclick = aplicarCropAvatar;
  document.getElementById('btnFecharAvatarCrop').onclick = function () { fecharModal(modal); input.value = ''; };
  document.getElementById('btnCancelarAvatarCrop').onclick = function () { fecharModal(modal); input.value = ''; };

  var form = input.closest('form');
  var btn = document.getElementById('fpBtnSalvarAvatar');
  form.addEventListener('submit', function (ev) {
    // Sem foto recortada nenhuma (abriu o seletor e cancelou o crop) o input volta vazio —
    // barra o submit aqui em vez de deixar o servidor rejeitar com "escolha uma foto".
    if (!input.files.length) {
      ev.preventDefault();
      return;
    }
    btn.disabled = true;
    btn.textContent = 'Salvando...';
  });
})();
</script>

<?php endif; ?>
