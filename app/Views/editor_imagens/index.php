<style>
.drop-zone {
  border: 3px dashed #94a3b8;
  border-radius: 16px;
  background: #f8fafc;
  transition: all .2s;
  cursor: pointer;
  min-height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}
.drop-zone:hover, .drop-zone.over { border-color: #2563eb; background: #eff6ff; }
#imgPreview { max-width: 100%; display: block; border-radius: 10px; }
#cropperImg { max-width: 100%; }
.tool-card { border-radius: 14px; border: 2px solid #e2e8f0; cursor: pointer; transition: .15s; }
.tool-card:hover { border-color: #2563eb; background: #f0f7ff; }
.tool-card.active { border-color: #2563eb; background: #eff6ff; }
.preset-tag { border-radius: 20px; padding: 4px 12px; font-size: .78rem; font-weight: 600; border: 1.5px solid #e2e8f0; cursor: pointer; background: #fff; transition: .15s; }
.preset-tag:hover, .preset-tag.ativo { border-color: #2563eb; color: #2563eb; background: #eff6ff; }
</style>

<!-- Link CSS do Cropper.js -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-image me-2 text-primary"></i>Editor de Imagens</h5>
    <small class="text-muted">Recorte, redimensione e comprima suas imagens</small>
  </div>
</div>

<div class="row g-4">

  <!-- Esquerda: Upload + Ferramentas -->
  <div class="col-lg-4">

    <!-- Upload -->
    <div class="drop-zone p-4 text-center mb-3" id="dropZone"
         onclick="document.getElementById('inputImg').click()"
         ondragover="ev.preventDefault();this.classList.add('over')"
         ondragleave="this.classList.remove('over')"
         ondrop="onDrop(event)">
      <i class="bi bi-cloud-upload fs-2 text-primary mb-2"></i>
      <div class="fw-semibold">Clique ou arraste a imagem aqui</div>
      <div class="text-muted small">JPG, PNG, WebP, GIF</div>
    </div>
    <input type="file" id="inputImg" class="d-none" accept="image/*" onchange="carregarArquivo(this)">

    <!-- Ferramentas (visível após upload) -->
    <div id="ferramentas" class="d-none">

      <!-- Info da imagem -->
      <div class="alert alert-light border small mb-3 py-2" id="infoImg"></div>

      <!-- 1. Recortar -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold small">
          <i class="bi bi-crop me-2 text-success"></i>Recortar
        </div>
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-1 mb-3">
            <span class="preset-tag ativo" onclick="setAspecto(NaN,this)">Livre</span>
            <span class="preset-tag" onclick="setAspecto(1,this)">Quadrado</span>
            <span class="preset-tag" onclick="setAspecto(16/9,this)">16:9</span>
            <span class="preset-tag" onclick="setAspecto(4/3,this)">4:3</span>
          </div>
          <button class="btn btn-success w-100 fw-semibold" id="btnCrop" onclick="ativarCrop()">
            <i class="bi bi-scissors me-1"></i>Ativar recorte na imagem
          </button>

          <!-- Painel ao vivo do recorte -->
          <div id="cropLive" class="d-none mt-3">
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small fw-semibold mb-1">Largura (px)</label>
                <input type="number" id="cropW" class="form-control form-control-sm" min="1" oninput="setCropDim('w')">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold mb-1">Altura (px)</label>
                <input type="number" id="cropH" class="form-control form-control-sm" min="1" oninput="setCropDim('h')">
              </div>
            </div>
            <div class="d-flex justify-content-between align-items-center small text-muted mb-2">
              <span>Posição: <b id="cropXY">0, 0</b></span>
              <span id="cropRatioLbl" class="badge bg-light text-dark border"></span>
            </div>
          </div>

          <div id="cropBtns" class="d-none mt-2 d-flex gap-2">
            <button class="btn btn-primary flex-fill fw-semibold" onclick="aplicarCrop()">
              <i class="bi bi-check-lg me-1"></i>Aplicar
            </button>
            <button class="btn btn-outline-danger flex-fill" onclick="cancelarCrop()">
              <i class="bi bi-x me-1"></i>Cancelar
            </button>
          </div>
        </div>
      </div>

      <!-- 2. Redimensionar -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold small">
          <i class="bi bi-arrows-angle-expand me-2 text-primary"></i>Redimensionar
        </div>
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-1 mb-3">
            <span class="preset-tag" onclick="setPresetResize(1920,1080)">Full HD</span>
            <span class="preset-tag" onclick="setPresetResize(1280,720)">HD</span>
            <span class="preset-tag" onclick="setPresetResize(800,600)">Web</span>
            <span class="preset-tag" onclick="setPresetResize(400,300)">Mini</span>
          </div>
          <div class="row g-2 mb-2">
            <div class="col-6">
              <label class="form-label small fw-semibold mb-1">Largura (px)</label>
              <input type="number" id="rW" class="form-control form-control-sm" min="1" max="8000" oninput="syncResize('w')">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold mb-1">Altura (px)</label>
              <input type="number" id="rH" class="form-control form-control-sm" min="1" max="8000" oninput="syncResize('h')">
            </div>
          </div>
          <div class="form-check form-check-sm mb-3">
            <input class="form-check-input" type="checkbox" id="proporcao" checked>
            <label class="form-check-label small" for="proporcao">Manter proporção</label>
          </div>
          <button class="btn btn-primary w-100 fw-semibold" onclick="aplicarResize()">
            <i class="bi bi-arrows-angle-expand me-1"></i>Redimensionar
          </button>
        </div>
      </div>

      <!-- 3. Compressão WebP -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold small">
          <i class="bi bi-file-zip me-2 text-danger"></i>Compressão → WebP
        </div>
        <div class="card-body p-3">
          <div class="d-flex flex-wrap gap-1 mb-3">
            <span class="preset-tag" onclick="setQual(95)">Alta (95%)</span>
            <span class="preset-tag ativo" onclick="setQual(80)">Boa (80%)</span>
            <span class="preset-tag" onclick="setQual(60)">Web (60%)</span>
            <span class="preset-tag" onclick="setQual(40)">Leve (40%)</span>
          </div>
          <label class="form-label small fw-semibold mb-1">
            Qualidade: <span id="lblQual">80</span>%
          </label>
          <input type="range" class="form-range mb-3" id="slQual" min="10" max="100" value="80"
                 oninput="document.getElementById('lblQual').textContent=this.value">
          <div id="tamanhoWebp" class="text-muted small mb-2"></div>
        </div>
      </div>

      <!-- Botões de ação -->
      <div class="d-flex flex-column gap-2">
        <button class="btn btn-outline-secondary fw-semibold" onclick="desfazer()" id="btnDesfazer" disabled>
          <i class="bi bi-arrow-counterclockwise me-1"></i>Desfazer última ação
        </button>
        <button class="btn btn-primary fw-semibold" onclick="baixar()">
          <i class="bi bi-download me-1"></i>Baixar como WebP
        </button>
        <button class="btn btn-success fw-semibold" onclick="salvar()">
          <i class="bi bi-cloud-upload me-1"></i>Salvar no sistema
        </button>
      </div>

    </div>
  </div>

  <!-- Direita: Preview -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold small d-flex justify-content-between align-items-center">
        <span><i class="bi bi-eye me-2"></i>Preview</span>
        <span id="dimensaoLabel" class="text-muted"></span>
      </div>
      <div class="card-body d-flex align-items-center justify-content-center" style="min-height:500px;background:#1e293b;border-radius:0 0 14px 14px">

        <!-- Vazio -->
        <div id="vazio" class="text-center text-white-50">
          <i class="bi bi-image fs-1 d-block mb-3 opacity-25"></i>
          <p>Nenhuma imagem carregada</p>
        </div>

        <!-- Canvas de trabalho (oculto) -->
        <canvas id="canvas" style="display:none"></canvas>

        <!-- Preview visual -->
        <div id="previewWrap" class="d-none text-center" style="max-width:100%;overflow:auto">
          <img id="imgPreview" alt="Preview">
        </div>

        <!-- Cropper.js container -->
        <div id="cropWrap" class="d-none" style="position:relative;max-height:500px;overflow:hidden;width:100%">
          <img id="cropperImg" alt="Recorte">
          <!-- Badge de dimensão ao vivo -->
          <div id="cropBadge" style="position:absolute;top:10px;left:50%;transform:translateX(-50%);background:rgba(37,99,235,.95);color:#fff;font-weight:700;font-size:.82rem;padding:5px 14px;border-radius:20px;box-shadow:0 4px 14px rgba(0,0,0,.3);pointer-events:none;z-index:20;white-space:nowrap">
            0 × 0 px
          </div>
        </div>

      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
const CSRF = '<?= csrf_token() ?>';
let canvas    = document.getElementById('canvas');
let ctx       = canvas.getContext('2d');
let historico = [];          // stack de ImageData para desfazer
let cropper   = null;
let aspecto   = NaN;
let qualidade = 0.80;

// ── Carregar ────────────────────────────────────────────────
function carregarArquivo(input) {
  if (!input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => carregarSrc(e.target.result, input.files[0]);
  reader.readAsDataURL(input.files[0]);
}
function onDrop(e) {
  e.preventDefault();
  document.getElementById('dropZone').classList.remove('over');
  const f = e.dataTransfer.files[0];
  if (f && f.type.startsWith('image/')) {
    const r = new FileReader();
    r.onload = ev => carregarSrc(ev.target.result, f);
    r.readAsDataURL(f);
  }
}
function carregarSrc(src, file) {
  const img = new Image();
  img.onload = () => {
    canvas.width  = img.width;
    canvas.height = img.height;
    ctx.drawImage(img, 0, 0);
    historico = [];
    atualizarPreview();
    document.getElementById('vazio').classList.add('d-none');
    document.getElementById('ferramentas').classList.remove('d-none');
    document.getElementById('rW').value = img.width;
    document.getElementById('rH').value = img.height;
    document.getElementById('infoImg').innerHTML =
      `<b>${file.name}</b> — ${img.width}×${img.height}px — ${Math.round(file.size/1024)} KB`;
    atualizarDimensao();
    estimarWebp();
  };
  img.src = src;
}

// ── Preview ─────────────────────────────────────────────────
function atualizarPreview() {
  const src = canvas.toDataURL('image/png');
  document.getElementById('imgPreview').src = src;
  document.getElementById('previewWrap').classList.remove('d-none');
  document.getElementById('vazio').classList.add('d-none');
  document.getElementById('btnDesfazer').disabled = historico.length === 0;
  atualizarDimensao();
  estimarWebp();
}
function atualizarDimensao() {
  document.getElementById('dimensaoLabel').textContent = canvas.width + ' × ' + canvas.height + ' px';
}

// ── Salvar histórico para desfazer ──────────────────────────
function salvarHistorico() {
  historico.push(ctx.getImageData(0, 0, canvas.width, canvas.height));
  if (historico.length > 10) historico.shift();
  document.getElementById('btnDesfazer').disabled = false;
}

// ── Desfazer ─────────────────────────────────────────────────
function desfazer() {
  if (!historico.length) return;
  const estado = historico.pop();
  canvas.width  = estado.width;
  canvas.height = estado.height;
  ctx.putImageData(estado, 0, 0);
  document.getElementById('rW').value = canvas.width;
  document.getElementById('rH').value = canvas.height;
  atualizarPreview();
  if (!historico.length) document.getElementById('btnDesfazer').disabled = true;
  notif('Ação desfeita!');
}

// ── Crop com Cropper.js ──────────────────────────────────────
function setAspecto(ratio, btn) {
  aspecto = ratio;
  document.querySelectorAll('.preset-tag').forEach(b => {
    if (b.closest('#ferramentas > .card:first-of-type') || b.closest('.card-body')) {
      if (b.onclick && b.onclick.toString().includes('setAspecto')) b.classList.remove('ativo');
    }
  });
  if (btn) {
    document.querySelectorAll('[onclick*="setAspecto"]').forEach(b => b.classList.remove('ativo'));
    btn.classList.add('ativo');
  }
  if (cropper) cropper.setAspectRatio(ratio || NaN);
}

function ativarCrop() {
  salvarHistorico();
  const imgEl = document.getElementById('cropperImg');
  imgEl.src   = canvas.toDataURL('image/png');
  document.getElementById('previewWrap').classList.add('d-none');
  document.getElementById('cropWrap').classList.remove('d-none');
  if (cropper) { cropper.destroy(); cropper = null; }
  cropper = new Cropper(imgEl, {
    aspectRatio: aspecto || NaN,
    viewMode: 1,
    dragMode: 'crop',
    guides: true,
    center: true,
    background: true,
    zoomable: true,
    zoomOnWheel: true,
    movable: true,
    rotatable: false,
    scalable: false,
    autoCropArea: 0.85,
    ready() { notif('✂️ Arraste as alças ou digite o tamanho exato!'); atualizarCropLive(); },
    crop(e) { atualizarCropLive(e.detail); }
  });
  document.getElementById('btnCrop').classList.add('d-none');
  document.getElementById('cropBtns').classList.remove('d-none');
  document.getElementById('cropLive').classList.remove('d-none');
}

// ── Leitura do recorte em tempo real ─────────────────────────
let _cropEdit = false;   // evita loop ao digitar nos inputs
function atualizarCropLive(detail) {
  if (!cropper) return;
  const d = detail || cropper.getData();
  const w = Math.max(0, Math.round(d.width));
  const h = Math.max(0, Math.round(d.height));
  const x = Math.round(d.x), y = Math.round(d.y);
  document.getElementById('cropBadge').textContent = w + ' × ' + h + ' px';
  document.getElementById('cropXY').textContent = x + ', ' + y;
  // proporção simplificada
  const lbl = document.getElementById('cropRatioLbl');
  if (w && h) {
    const g = (function gcd(a,b){return b?gcd(b,a%b):a;})(w,h);
    lbl.textContent = (w/g) + ':' + (h/g) + ' (' + (w/h).toFixed(2) + ')';
  } else lbl.textContent = '';
  if (!_cropEdit) {
    document.getElementById('cropW').value = w;
    document.getElementById('cropH').value = h;
  }
}
function setCropDim(campo) {
  if (!cropper) return;
  _cropEdit = true;
  const data = cropper.getData();
  if (campo === 'w') {
    const w = parseInt(document.getElementById('cropW').value) || 0;
    if (!isNaN(aspecto) && aspecto) document.getElementById('cropH').value = Math.round(w / aspecto);
    cropper.setData({ width: w, height: parseInt(document.getElementById('cropH').value) || data.height });
  } else {
    const h = parseInt(document.getElementById('cropH').value) || 0;
    if (!isNaN(aspecto) && aspecto) document.getElementById('cropW').value = Math.round(h * aspecto);
    cropper.setData({ width: parseInt(document.getElementById('cropW').value) || data.width, height: h });
  }
  setTimeout(() => { _cropEdit = false; }, 50);
}

function aplicarCrop() {
  if (!cropper) return;
  const croppedCanvas = cropper.getCroppedCanvas({ fillColor: '#fff' });
  canvas.width  = croppedCanvas.width;
  canvas.height = croppedCanvas.height;
  ctx.drawImage(croppedCanvas, 0, 0);
  cancelarCrop();
  document.getElementById('rW').value = canvas.width;
  document.getElementById('rH').value = canvas.height;
  atualizarPreview();
  notif('Recorte aplicado: ' + canvas.width + '×' + canvas.height + ' px ✅');
}

function cancelarCrop() {
  if (cropper) { cropper.destroy(); cropper = null; }
  document.getElementById('cropWrap').classList.add('d-none');
  document.getElementById('previewWrap').classList.remove('d-none');
  document.getElementById('btnCrop').classList.remove('d-none');
  document.getElementById('cropBtns').classList.add('d-none');
  document.getElementById('cropLive').classList.add('d-none');
}

// ── Redimensionar ────────────────────────────────────────────
function setPresetResize(w, h) {
  document.getElementById('rW').value = w;
  document.getElementById('rH').value = h;
  document.querySelectorAll('[onclick*="setPresetResize"]').forEach(b => b.classList.remove('ativo'));
  event.target.classList.add('ativo');
}
function syncResize(campo) {
  if (!document.getElementById('proporcao').checked) return;
  const ratio = canvas.height / canvas.width;
  if (campo === 'w') {
    const w = parseInt(document.getElementById('rW').value) || 0;
    document.getElementById('rH').value = Math.round(w * ratio);
  } else {
    const h = parseInt(document.getElementById('rH').value) || 0;
    document.getElementById('rW').value = Math.round(h / ratio);
  }
}
function aplicarResize() {
  const w = parseInt(document.getElementById('rW').value);
  const h = parseInt(document.getElementById('rH').value);
  if (!w || !h || w < 1 || h < 1) return notif('Informe largura e altura válidas!');
  salvarHistorico();
  const tmp = document.createElement('canvas');
  tmp.width = w; tmp.height = h;
  tmp.getContext('2d').drawImage(canvas, 0, 0, w, h);
  canvas.width = w; canvas.height = h;
  ctx.drawImage(tmp, 0, 0);
  atualizarPreview();
  notif('Redimensionado para ' + w + '×' + h + ' px ✅');
}

// ── Compressão / WebP ────────────────────────────────────────
function setQual(v) {
  document.getElementById('slQual').value = v;
  document.getElementById('lblQual').textContent = v;
  qualidade = v / 100;
  document.querySelectorAll('[onclick*="setQual"]').forEach(b => b.classList.remove('ativo'));
  event.target.classList.add('ativo');
  estimarWebp();
}
document.getElementById('slQual').addEventListener('input', function() {
  qualidade = this.value / 100;
  estimarWebp();
});
function estimarWebp() {
  if (!canvas.width) return;
  const dataUrl = canvas.toDataURL('image/webp', qualidade);
  const bytes   = Math.round((dataUrl.length - 22) * 3 / 4 / 1024);
  document.getElementById('tamanhoWebp').textContent = `Tamanho estimado: ~${bytes} KB (WebP ${Math.round(qualidade*100)}%)`;
}

// ── Baixar ───────────────────────────────────────────────────
function baixar() {
  if (!canvas.width) return;
  const link = document.createElement('a');
  link.download = 'imagem_' + Date.now() + '.webp';
  link.href     = canvas.toDataURL('image/webp', qualidade);
  link.click();
  notif('Download iniciado! ✅');
}

// ── Salvar no servidor ───────────────────────────────────────
async function salvar() {
  if (!canvas.width) return;
  const btn = event.target;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
  try {
    const resp = await fetch('<?= url('/editor-imagens/salvar') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({
        imagem: canvas.toDataURL('image/webp', qualidade),
        nome: 'editada',
        csrf_token: CSRF
      }),
    });
    const j = await resp.json();
    notif(j.success ? 'Salvo no sistema! ' + j.tamanho + ' ✅' : 'Erro: ' + j.error);
  } catch(e) { notif('Erro de conexão'); }
  finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-cloud-upload me-1"></i>Salvar no sistema';
  }
}

// ── Notificação ──────────────────────────────────────────────
function notif(msg) {
  let t = document.getElementById('_notif');
  if (!t) {
    t = document.createElement('div');
    t.id = '_notif';
    t.style.cssText = 'position:fixed;bottom:24px;right:24px;background:#1e293b;color:#fff;padding:12px 20px;border-radius:12px;font-size:.88rem;font-weight:600;z-index:9999;box-shadow:0 8px 24px rgba(0,0,0,.3);transition:opacity .3s';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.style.opacity = '1';
  clearTimeout(t._t);
  t._t = setTimeout(() => t.style.opacity = '0', 2500);
}
</script>
