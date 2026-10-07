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

<!-- Fixa Fase 1 (PF/PJ) — gerenciar perfis (Pessoal + eventuais PJ/outro Pessoal). Criar/editar/
     arquivar aqui, mesmo padrão simples de form+redirect das outras telas deste módulo; trocar
     QUAL perfil está ativo agora é feito pelo seletor do topo (layouts/financeiro_pessoal.php),
     não aqui. -->
<div class="fp-card" style="max-width:420px;margin-top:16px">
  <div class="fp-section-titulo" style="margin-bottom:4px">Perfis</div>
  <p class="fp-faint" style="font-size:.82rem;line-height:1.5;margin:0 0 14px">
    Separe o financeiro Pessoal de um MEI/empresa que você também administra — cada perfil tem
    suas próprias contas, categorias e lançamentos.
  </p>
  <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:14px">
    <?php foreach ($perfis as $p): ?>
    <div class="fp-card<?= !empty($p['arquivado']) ? ' fp-conta-arquivada' : '' ?>" style="display:flex;align-items:center;gap:10px;padding:10px 12px">
      <span style="width:12px;height:12px;border-radius:50%;background:<?= e($p['cor']) ?>;flex:0 0 auto"></span>
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:.9rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= $p['tipo'] === 'pj' ? '🏢 ' : '👤 ' ?><?= e($p['nome']) ?>
          <?php if (!empty($p['arquivado'])): ?><span class="fp-chip fp-chip-muted" style="margin-left:6px">Arquivado</span><?php endif; ?>
        </div>
        <?php if (!empty($p['documento'])): ?>
        <div class="fp-faint fp-mono" style="font-size:.74rem"><?= e($p['tipo'] === 'pj' ? 'CNPJ' : 'CPF') ?>: <?= e(documento_mascara($p['documento'])) ?></div>
        <?php endif; ?>
      </div>
      <button type="button" class="fp-btn fp-btn-ghost fp-btn-sm btn-editar-perfil"
        data-id="<?= (int) $p['id'] ?>" data-tipo="<?= e($p['tipo']) ?>" data-nome="<?= e($p['nome']) ?>"
        data-documento="<?= e($p['documento'] ?? '') ?>" data-cor="<?= e($p['cor']) ?>" data-regime="<?= e($p['regime'] ?? '') ?>">Editar</button>
      <form method="POST" action="<?= url('/financeiro-pessoal/perfis') ?>/<?= (int) $p['id'] ?>/arquivar"
        onsubmit="return <?= !empty($p['arquivado']) ? 'true' : "confirm('Arquivar o perfil &quot;" . e(addslashes($p['nome'])) . "&quot;? Os lançamentos continuam guardados, só some do seletor.')" ?>;">
        <?= csrf_field() ?>
        <input type="hidden" name="arquivar" value="<?= !empty($p['arquivado']) ? '0' : '1' ?>">
        <button type="submit" class="fp-btn fp-btn-ghost fp-btn-sm"><?= !empty($p['arquivado']) ? 'Reativar' : 'Arquivar' ?></button>
      </form>
    </div>
    <?php endforeach; ?>
  </div>
  <button type="button" class="fp-btn fp-btn-primary fp-btn-sm" id="btnNovoPerfil">+ Novo perfil</button>
</div>

<div class="fp-modal-backdrop" id="modalPerfil">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong id="modalPerfilTitulo">Novo perfil</strong>
      <button type="button" class="fp-modal-close" id="btnFecharPerfil" aria-label="Fechar">×</button>
    </div>
    <form id="formPerfil" method="POST" action="<?= url('/financeiro-pessoal/perfis') ?>" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <div id="perfilTipoWrap" style="display:flex;gap:8px">
        <button type="button" class="fp-btn fp-btn-primary" id="perfilTipoPf" data-tipo="pf" style="flex:1">👤 Pessoal (CPF)</button>
        <button type="button" class="fp-btn fp-btn-ghost" id="perfilTipoPj" data-tipo="pj" style="flex:1">🏢 Empresa (CNPJ)</button>
      </div>
      <input type="hidden" name="tipo" id="perfilTipo" value="pf">
      <input type="text" name="nome" id="perfilNome" class="fp-input" placeholder="Nome do perfil (ex.: Pessoal, Minha Oficina MEI)" maxlength="80" required>
      <input type="text" name="documento" id="perfilDocumento" class="fp-input" placeholder="CPF ou CNPJ (opcional)" maxlength="18">
      <div id="perfilRegimeWrap" style="display:none">
        <label for="perfilRegime" class="fp-faint" style="font-size:.78rem;display:block;margin-bottom:4px">Regime tributário</label>
        <select name="regime" id="perfilRegime" class="fp-select">
          <option value="mei">MEI</option>
          <option value="simples">Simples Nacional</option>
          <option value="presumido">Lucro Presumido</option>
          <option value="outro">Outro</option>
        </select>
      </div>
      <div style="display:flex;align-items:center;gap:10px">
        <label for="perfilCor" class="fp-faint" style="font-size:.84rem">Cor</label>
        <input type="color" name="cor" id="perfilCor" value="#8C7CFF" style="width:48px;height:38px;padding:2px;border-radius:10px;border:1.5px solid var(--line);background:var(--input)">
      </div>
      <div id="perfilMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary">Salvar</button>
    </form>
  </div>
</div>

<script>
(function () {
  var modal = document.getElementById('modalPerfil');
  var form = document.getElementById('formPerfil');
  var titulo = document.getElementById('modalPerfilTitulo');
  var tipoWrap = document.getElementById('perfilTipoWrap');
  var tipoHidden = document.getElementById('perfilTipo');
  var btnPf = document.getElementById('perfilTipoPf');
  var btnPj = document.getElementById('perfilTipoPj');
  var regimeWrap = document.getElementById('perfilRegimeWrap');
  var URL_CRIAR = <?= json_encode(url('/financeiro-pessoal/perfis')) ?>;

  // Máscara CPF/CNPJ dinâmica (alterna pela quantidade de dígitos, sem depender do toggle
  // Pessoal/Empresa acima — mesmo padrão "campo CPF ou CNPJ" já usado em masks.js do sistema
  // principal, reaplicado aqui porque este módulo não carrega aquele arquivo — ver comentário
  // de isolamento mais abaixo nesta view). O servidor já normaliza pra só dígitos ao salvar
  // (FinanceiroPessoalController::perfilCriar()/perfilAtualizar()), então a máscara aqui é só
  // visual — não precisa desfazer os pontos/traço antes de enviar.
  var perfilDocInput = document.getElementById('perfilDocumento');
  var perfilDocMask = typeof IMask !== 'undefined' ? IMask(perfilDocInput, {
    mask: [
      { mask: '000.000.000-00' },
      { mask: '00.000.000/0000-00' },
    ],
    dispatch: function (appended, dynamicMasked) {
      var digitos = (dynamicMasked.value + appended).replace(/\D/g, '');
      return dynamicMasked.compiledMasks[digitos.length > 11 ? 1 : 0];
    },
  }) : null;

  function abrirModal() { modal.classList.add('show'); }
  function fecharModal() { modal.classList.remove('show'); }

  function marcarTipo(tipo) {
    tipoHidden.value = tipo;
    btnPf.className = 'fp-btn ' + (tipo === 'pf' ? 'fp-btn-primary' : 'fp-btn-ghost');
    btnPj.className = 'fp-btn ' + (tipo === 'pj' ? 'fp-btn-primary' : 'fp-btn-ghost');
    regimeWrap.style.display = tipo === 'pj' ? 'block' : 'none';
  }
  btnPf.onclick = function () { marcarTipo('pf'); };
  btnPj.onclick = function () { marcarTipo('pj'); };

  document.getElementById('btnNovoPerfil').onclick = function () {
    titulo.textContent = 'Novo perfil';
    form.reset();
    if (perfilDocMask) { perfilDocMask.unmaskedValue = ''; }
    marcarTipo('pf');
    tipoWrap.style.display = 'flex';
    document.getElementById('perfilCor').value = '#8C7CFF';
    form.action = URL_CRIAR;
    abrirModal();
  };

  document.querySelectorAll('.btn-editar-perfil').forEach(function (btn) {
    btn.onclick = function () {
      titulo.textContent = 'Editar perfil';
      // Tipo não muda depois de criado (categorias padrão já foram semeadas pra esse tipo) —
      // escondido na edição, mesmo princípio já usado em Categorias.
      tipoWrap.style.display = 'none';
      marcarTipo(btn.dataset.tipo);
      document.getElementById('perfilNome').value = btn.dataset.nome;
      if (perfilDocMask) { perfilDocMask.unmaskedValue = btn.dataset.documento || ''; }
      else { document.getElementById('perfilDocumento').value = btn.dataset.documento; }
      document.getElementById('perfilCor').value = btn.dataset.cor;
      if (btn.dataset.regime) { document.getElementById('perfilRegime').value = btn.dataset.regime; }
      form.action = URL_CRIAR + '/' + btn.dataset.id + '/atualizar';
      abrirModal();
    };
  });

  document.getElementById('btnFecharPerfil').onclick = fecharModal;
})();
</script>

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
