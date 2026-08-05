<style>
  .img-drop{border:2px dashed #ccd2e0;border-radius:14px;padding:26px;text-align:center;cursor:pointer;transition:.15s;background:#fafbff}
  .img-drop:hover{border-color:#5b53e6;background:#f4f5ff}
  .img-drop.dragover{border-color:#5b53e6;background:#eef0ff}
  .prev-box{border:1px solid #e7e9f2;border-radius:14px;overflow:hidden;aspect-ratio:1;display:flex;align-items:center;justify-content:center;background:#f6f7fb}
  .prev-box img{max-width:100%;max-height:100%;object-fit:contain;display:block}
  .prev-box.xadrez{background-image:linear-gradient(45deg,#e2e5ee 25%,transparent 25%),linear-gradient(-45deg,#e2e5ee 25%,transparent 25%),linear-gradient(45deg,transparent 75%,#e2e5ee 75%),linear-gradient(-45deg,transparent 75%,#e2e5ee 75%);background-size:20px 20px;background-position:0 0,0 10px,10px -10px,-10px 0}
  .prev-box.branco{background:#fff}
  .prev-ph{color:#9aa0b0;font-size:13px;text-align:center;padding:20px}
  .badge-soft{background:#eef0ff;color:#4b4de0;font-weight:600}
  .prev-box.cropping{aspect-ratio:auto;height:320px;overflow:hidden}
  .prev-box.cropping img{max-width:none;max-height:none}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">

<div class="container-fluid" style="max-width:960px">
  <div class="d-flex align-items-center gap-2 mb-1">
    <h4 class="fw-bold mb-0"><i class="bi bi-magic me-2 text-primary"></i>Preparar imagem pra web</h4>
    <span class="badge badge-soft rounded-pill">SEO</span>
  </div>
  <p class="text-muted mb-4">Deixa a foto do produto/peça com cara de loja grande: padroniza o <strong>tamanho</strong>, encaixa num <strong>fundo</strong> à sua escolha e salva em <strong>WebP</strong> (leve e rápido pro Google) — sem abrir editor nenhum.</p>

  <div class="row g-4">
    <!-- Entrada -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">1. Escolha a imagem</h6>
          <div class="img-drop" id="drop">
            <i class="bi bi-cloud-arrow-up fs-1 text-secondary d-block mb-2"></i>
            <div class="fw-semibold">Toque pra escolher ou arraste aqui</div>
            <div class="text-muted small">JPG, PNG ou WebP — qualquer tamanho</div>
            <input type="file" id="fileInput" accept="image/*" hidden>
          </div>
          <div class="prev-box branco mt-3" id="boxAntes"><div class="prev-ph">A imagem original aparece aqui</div></div>
          <div id="cropInfo" class="small text-muted mt-1" style="display:none"></div>

          <div id="cropAcoes" class="d-none mt-2">
            <button class="btn btn-outline-secondary btn-sm w-100" id="btnRecortar" type="button">
              <i class="bi bi-crop me-1"></i>Recortar
            </button>
          </div>

          <div id="cropPainel" class="d-none mt-2">
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small fw-semibold mb-1">Largura (px)</label>
                <input type="number" id="cropW" class="form-control form-control-sm" min="1">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold mb-1">Altura (px)</label>
                <input type="number" id="cropH" class="form-control form-control-sm" min="1">
              </div>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary btn-sm flex-fill" id="btnAplicarCrop" type="button">
                <i class="bi bi-check-lg me-1"></i>Aplicar recorte
              </button>
              <button class="btn btn-outline-danger btn-sm flex-fill" id="btnCancelarCrop" type="button">
                <i class="bi bi-x me-1"></i>Cancelar
              </button>
            </div>
          </div>

          <h6 class="fw-semibold mt-4 mb-3">2. Ajustes</h6>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Tamanho (px)</label>
              <select class="form-select" id="tamanho">
                <option value="400x400">400 × 400</option>
                <option value="600x600">600 × 600</option>
                <option value="800x800" selected>800 × 800</option>
                <option value="1000x1000">1000 × 1000</option>
                <option value="1200x1200">1200 × 1200</option>
                <option value="900x300">900 × 300 — Logo retangular</option>
              </select>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Fundo</label>
              <select class="form-select" id="fundo">
                <option value="branco" selected>Branco</option>
                <option value="transparente">Transparente</option>
              </select>
            </div>
          </div>
          <button class="btn btn-primary w-100 mt-4 fw-semibold" id="btnProcessar" disabled>
            <i class="bi bi-magic me-1"></i>Processar imagem
          </button>
        </div>
      </div>
    </div>

    <!-- Resultado -->
    <div class="col-lg-6">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body d-flex flex-column">
          <h6 class="fw-semibold mb-3">3. Resultado</h6>
          <div class="prev-box branco" id="boxDepois" style="flex:1"><div class="prev-ph" id="phDepois">O resultado pronto pra web aparece aqui</div></div>
          <div id="infoResultado" class="d-flex justify-content-between align-items-center mt-3" style="display:none!important">
            <span class="small text-muted" id="infoTexto"></span>
            <a class="btn btn-success btn-sm fw-semibold" id="btnBaixar" download="imagem-web.webp"><i class="bi bi-download me-1"></i>Baixar WebP</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>
<script>
(function(){
  var URL_PROC='<?= url('/imagem/processar') ?>', CSRF='<?= csrf_token() ?>';
  var arquivoAtual=null;   // Blob/File que vai pro servidor (original ou recortado)
  var drop=document.getElementById('drop'), inp=document.getElementById('fileInput');
  var btn=document.getElementById('btnProcessar');
  var boxAntes=document.getElementById('boxAntes');
  var cropInfo=document.getElementById('cropInfo');
  var cropAcoes=document.getElementById('cropAcoes');
  var cropPainel=document.getElementById('cropPainel');
  var cropWInp=document.getElementById('cropW'), cropHInp=document.getElementById('cropH');
  var cropper=null, _cropEdit=false;

  drop.onclick=function(){ inp.click(); };
  ['dragover','dragenter'].forEach(e=>drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.add('dragover');}));
  ['dragleave','drop'].forEach(e=>drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.remove('dragover');}));
  drop.addEventListener('drop',function(ev){ if(ev.dataTransfer.files[0]) setArquivo(ev.dataTransfer.files[0]); });
  inp.onchange=function(){ if(inp.files[0]) setArquivo(inp.files[0]); };

  function setArquivo(f){
    if(!f.type.startsWith('image/')){ alert('Escolha um arquivo de imagem.'); return; }
    cancelarCrop();
    arquivoAtual=f; btn.disabled=false;
    boxAntes.innerHTML=''; boxAntes.className='prev-box branco';
    var img=document.createElement('img'); img.id='imgAntes';
    img.onload=function(){
      cropInfo.style.display='block';
      cropInfo.textContent='Imagem original: '+img.naturalWidth+'×'+img.naturalHeight+' px';
    };
    img.src=URL.createObjectURL(f);
    boxAntes.appendChild(img);
    cropAcoes.classList.remove('d-none');
    cropPainel.classList.add('d-none');
  }

  // ── Recortar (Cropper.js) ────────────────────────────────────
  document.getElementById('btnRecortar').onclick=function(){
    var img=document.getElementById('imgAntes');
    if(!img) return;
    boxAntes.classList.add('cropping');
    cropAcoes.classList.add('d-none');
    cropPainel.classList.remove('d-none');
    if(cropper){ cropper.destroy(); cropper=null; }
    cropper=new Cropper(img,{
      viewMode:1, dragMode:'crop', guides:true, center:true, background:true,
      zoomable:true, zoomOnWheel:true, movable:true, rotatable:false, scalable:false,
      autoCropArea:0.9,
      ready(){ atualizarCropDim(); },
      crop(e){ atualizarCropDim(e.detail); }
    });
  };

  function atualizarCropDim(detail){
    if(!cropper || _cropEdit) return;
    var d=detail||cropper.getData();
    cropWInp.value=Math.max(1,Math.round(d.width));
    cropHInp.value=Math.max(1,Math.round(d.height));
  }
  function aplicarDimInputs(){
    if(!cropper) return;
    _cropEdit=true;
    cropper.setData({ width: parseInt(cropWInp.value)||1, height: parseInt(cropHInp.value)||1 });
    setTimeout(function(){ _cropEdit=false; },50);
  }
  cropWInp.addEventListener('input', aplicarDimInputs);
  cropHInp.addEventListener('input', aplicarDimInputs);

  document.getElementById('btnAplicarCrop').onclick=function(){
    if(!cropper) return;
    var canvasRec=cropper.getCroppedCanvas({ fillColor:'#fff' });
    canvasRec.toBlob(function(blob){
      arquivoAtual=blob;
      var img=document.getElementById('imgAntes');
      img.src=canvasRec.toDataURL('image/png');
      cropInfo.textContent='Recortado: '+canvasRec.width+'×'+canvasRec.height+' px';
      encerrarCrop();
    }, 'image/png');
  };
  document.getElementById('btnCancelarCrop').onclick=cancelarCrop;

  function cancelarCrop(){ if(cropper) encerrarCrop(); }
  function encerrarCrop(){
    if(cropper){ cropper.destroy(); cropper=null; }
    boxAntes.classList.remove('cropping');
    cropPainel.classList.add('d-none');
    if(document.getElementById('imgAntes')) cropAcoes.classList.remove('d-none');
  }

  btn.onclick=function(){
    if(!arquivoAtual) return;
    btn.disabled=true; var orig=btn.innerHTML;
    btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Processando…';
    var fd=new FormData();
    fd.append('imagem',arquivoAtual,'imagem.png');
    fd.append('tamanho',document.getElementById('tamanho').value);
    fd.append('fundo',document.getElementById('fundo').value);
    fetch(URL_PROC,{method:'POST',headers:{'X-CSRF-Token':CSRF},body:fd})
      .then(function(r){return r.json();}).then(function(j){
        btn.disabled=false; btn.innerHTML=orig;
        if(!j.ok){ alert(j.erro||'Erro ao processar.'); return; }
        var transp=(document.getElementById('fundo').value==='transparente');
        var box=document.getElementById('boxDepois');
        box.className='prev-box '+(transp?'xadrez':'branco'); box.style.flex='1';
        box.innerHTML=''; var img=document.createElement('img'); img.src=j.imagem; box.appendChild(img);
        document.getElementById('btnBaixar').href=j.imagem;
        document.getElementById('infoTexto').textContent=j.largura+'×'+j.altura+' px · WebP · '+j.kb+' KB';
        document.getElementById('infoResultado').style.setProperty('display','flex','important');
      }).catch(function(){ btn.disabled=false; btn.innerHTML=orig; alert('Falha de conexão. Tente de novo.'); });
  };
})();
</script>
