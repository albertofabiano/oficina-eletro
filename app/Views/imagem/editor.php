<style>
  .img-drop{border:2px dashed #ccd2e0;border-radius:12px;padding:12px 16px;display:flex;align-items:center;justify-content:center;gap:10px;cursor:pointer;transition:.15s;background:#fafbff}
  .img-drop:hover{border-color:#5b53e6;background:#f4f5ff}
  .img-drop.dragover{border-color:#5b53e6;background:#eef0ff}
  .prev-box{border:1px solid #e7e9f2;border-radius:14px;overflow:hidden;aspect-ratio:1;display:flex;align-items:center;justify-content:center;background:#f6f7fb;margin-top:15px}
  .prev-box img{max-width:100%;max-height:100%;object-fit:contain;display:block}
  .prev-box.branco{background:#fff}
  .prev-ph{color:#9aa0b0;font-size:13px;text-align:center;padding:20px}
  .badge-soft{background:#eef0ff;color:#4b4de0;font-weight:600}
  .prev-box.cropping{aspect-ratio:auto;height:320px;overflow:hidden}
  .prev-box.cropping img{max-width:none;max-height:none}
  .prev-box.resizing{aspect-ratio:auto;min-height:220px}
  .resize-handle{position:absolute;right:-8px;bottom:-8px;width:18px;height:18px;background:#5b53e6;border:2px solid #fff;border-radius:50%;cursor:nwse-resize;box-shadow:0 1px 4px rgba(0,0,0,.35);touch-action:none;z-index:5}
  .resize-badge{position:absolute;top:8px;left:50%;transform:translateX(-50%);background:rgba(37,99,235,.95);color:#fff;font-weight:700;font-size:.78rem;padding:4px 12px;border-radius:16px;pointer-events:none;white-space:nowrap;z-index:6}
</style>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css">

<div class="container-fluid" style="max-width:960px">
  <div class="d-flex align-items-center gap-2 mb-1">
    <h4 class="fw-bold mb-0"><i class="bi bi-magic me-2 text-primary"></i>Preparar imagem pra web</h4>
    <span class="badge badge-soft rounded-pill">SEO</span>
  </div>
  <p class="text-muted mb-4">Deixa a foto do produto/peça com cara de loja grande: <strong>redimensiona</strong> e <strong>recorta</strong> do jeito que você quiser e salva em <strong>WebP</strong> (leve e rápido pro Google) — sem abrir editor nenhum.</p>

  <div class="row g-4">
    <!-- Imagem -->
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">1. Escolha a imagem</h6>
          <div class="img-drop" id="drop">
            <i class="bi bi-cloud-arrow-up fs-4 text-secondary"></i>
            <div>
              <div class="fw-semibold small">Toque pra escolher ou arraste aqui</div>
              <div class="text-muted" style="font-size:.78rem">JPG, PNG ou WebP — qualquer tamanho</div>
            </div>
            <input type="file" id="fileInput" accept="image/*" hidden>
          </div>
          <div class="prev-box branco" id="boxAntes"><div class="prev-ph">A imagem original aparece aqui</div></div>
          <div id="cropInfo" class="small text-muted mt-1" style="display:none"></div>
        </div>
      </div>
    </div>

    <!-- Funções -->
    <div class="col-lg-5">
      <div class="card border-0 shadow-sm h-100">
        <div class="card-body">
          <h6 class="fw-semibold mb-3">2. Funções</h6>

          <button class="btn btn-outline-secondary btn-sm w-100" id="btnDesfazer" type="button" disabled>
            <i class="bi bi-arrow-counterclockwise me-1"></i>Desfazer última ação
          </button>
          <button class="btn btn-primary w-100 mt-3 fw-semibold" id="btnProcessar" disabled>
            <i class="bi bi-download me-1"></i>Processar e baixar WebP
          </button>

          <div class="table-responsive mt-4 mb-3">
            <table class="table table-sm small mb-0" style="font-size:.8rem">
              <thead>
                <tr class="text-muted">
                  <th>Onde vai ser usada</th>
                  <th class="text-end">Tamanho</th>
                </tr>
              </thead>
              <tbody>
                <tr><td>Logo retangular</td><td class="text-end">900 × 300</td></tr>
                <tr><td>Logo quadrado</td><td class="text-end">512 × 512</td></tr>
                <tr><td>Topo do diretório</td><td class="text-end">1920 × 480</td></tr>
                <tr><td>Capa do perfil</td><td class="text-end">1600 × 500</td></tr>
                <tr><td>Produto / peça</td><td class="text-end">1200 × 1200</td></tr>
                <tr><td>Anúncio do marketplace</td><td class="text-end">970 × 250</td></tr>
                <tr><td>Anúncio mobile</td><td class="text-end">320 × 100</td></tr>
                <tr><td>Avatar do usuário</td><td class="text-end">128 × 128</td></tr>
              </tbody>
            </table>
          </div>

          <div id="cropAcoes" class="d-none d-flex gap-2">
            <button class="btn btn-outline-secondary btn-sm flex-fill" id="btnMostrarResize" type="button">
              <i class="bi bi-arrows-angle-expand me-1"></i>Redimensionar
            </button>
            <button class="btn btn-outline-secondary btn-sm flex-fill" id="btnRecortar" type="button">
              <i class="bi bi-crop me-1"></i>Recortar
            </button>
          </div>

          <!-- Redimensionar a imagem inteira (antes do recorte) -->
          <div id="resizeBloco" class="d-none mt-3">
            <div class="small text-muted mb-2"><i class="bi bi-arrows-move me-1"></i>Arraste a alça no canto da imagem, ou digite o tamanho abaixo.</div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small fw-semibold mb-1">Largura (px)</label>
                <input type="number" id="rzW" class="form-control form-control-sm" min="1">
              </div>
              <div class="col-6">
                <label class="form-label small fw-semibold mb-1">Altura (px)</label>
                <input type="number" id="rzH" class="form-control form-control-sm" min="1">
              </div>
            </div>
            <div class="form-check form-check-sm mb-2">
              <input class="form-check-input" type="checkbox" id="rzProp" checked>
              <label class="form-check-label small" for="rzProp">Manter proporção</label>
            </div>
            <div class="d-flex gap-2">
              <button class="btn btn-primary btn-sm flex-fill" id="btnAplicarResize" type="button">
                <i class="bi bi-check-lg me-1"></i>Aplicar redimensionamento
              </button>
              <button class="btn btn-outline-danger btn-sm flex-fill" id="btnCancelarResize" type="button">
                <i class="bi bi-x me-1"></i>Cancelar
              </button>
            </div>
          </div>

          <!-- Recortar (depois do redimensionamento) -->
          <div id="cropPainel" class="d-none mt-3">
            <div class="mb-2">
              <label class="form-label small fw-semibold mb-1">Tamanho do recorte</label>
              <select class="form-select form-select-sm" id="cropTamanho">
                <option value="">Livre (arraste manualmente)</option>
                <option value="400x400">400 × 400</option>
                <option value="600x600">600 × 600</option>
                <option value="800x800">800 × 800</option>
                <option value="1000x1000">1000 × 1000</option>
                <option value="1200x1200">1200 × 1200</option>
                <option value="900x300">900 × 300 — Logo retangular</option>
              </select>
            </div>
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
  var cropTamanho=document.getElementById('cropTamanho');
  var resizeBloco=document.getElementById('resizeBloco');
  var rzW=document.getElementById('rzW'), rzH=document.getElementById('rzH'), rzProp=document.getElementById('rzProp');
  var cropper=null, _cropEdit=false, _rzRatio=1;
  var resizeWrap=null, resizeHandle=null, resizeBadge=null, _dragResize=null;
  var btnDesfazer=document.getElementById('btnDesfazer');
  var historico=[];   // pilha de {blob, src, info} — um item por ação aplicada (redimensionar/recortar)

  drop.onclick=function(){ inp.click(); };
  ['dragover','dragenter'].forEach(e=>drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.add('dragover');}));
  ['dragleave','drop'].forEach(e=>drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.remove('dragover');}));
  drop.addEventListener('drop',function(ev){ if(ev.dataTransfer.files[0]) setArquivo(ev.dataTransfer.files[0]); });
  inp.onchange=function(){ if(inp.files[0]) setArquivo(inp.files[0]); };

  function setArquivo(f){
    if(!f.type.startsWith('image/')){ alert('Escolha um arquivo de imagem.'); return; }
    cancelarCrop();
    sairModoResize();
    arquivoAtual=f; btn.disabled=false;
    boxAntes.innerHTML=''; boxAntes.className='prev-box branco';
    var img=document.createElement('img'); img.id='imgAntes';
    img.onload=function(){
      cropInfo.style.display='block';
      cropInfo.textContent='Imagem original: '+img.naturalWidth+'×'+img.naturalHeight+' px';
      img.onload=null;   // só na primeira carga — redimensionar/recortar atualizam o texto por conta própria
    };
    img.src=URL.createObjectURL(f);
    boxAntes.appendChild(img);
    cropAcoes.classList.remove('d-none');
    resizeBloco.classList.add('d-none');
    cropPainel.classList.add('d-none');
    historico=[]; btnDesfazer.disabled=true;
  }

  // ── Desfazer última ação (redimensionar/recortar) ────────────
  function pushHistorico(){
    var img=document.getElementById('imgAntes');
    if(!img) return;
    historico.push({ blob: arquivoAtual, src: img.src, info: cropInfo.textContent });
    if(historico.length>15) historico.shift();
    btnDesfazer.disabled=false;
  }
  btnDesfazer.onclick=function(){
    if(!historico.length) return;
    cancelarCrop(); sairModoResize();
    var estado=historico.pop();
    arquivoAtual=estado.blob;
    var img=document.getElementById('imgAntes');
    if(img) img.src=estado.src;
    cropInfo.textContent=estado.info;
    if(!historico.length) btnDesfazer.disabled=true;
  };

  // ── Redimensionar a imagem inteira (números ou alça direto na imagem) ─
  document.getElementById('btnMostrarResize').onclick=function(){
    var img=document.getElementById('imgAntes');
    if(!img) return;
    cancelarCrop();
    _rzRatio=img.naturalHeight/img.naturalWidth;
    rzW.value=img.naturalWidth;
    rzH.value=img.naturalHeight;
    resizeBloco.classList.remove('d-none');
    entrarModoResize();
  };
  function syncResize(campo){
    if(!rzProp.checked) return;
    if(campo==='w'){ rzH.value=Math.round((parseInt(rzW.value)||0)*_rzRatio); }
    else { rzW.value=Math.round((parseInt(rzH.value)||0)/_rzRatio); }
  }
  rzW.addEventListener('input',function(){ syncResize('w'); atualizarPreviewResize(); });
  rzH.addEventListener('input',function(){ syncResize('h'); atualizarPreviewResize(); });

  // Alça arrastável direto no canto da imagem
  function entrarModoResize(){
    var img=document.getElementById('imgAntes');
    if(!img) return;
    boxAntes.classList.add('resizing');
    resizeWrap=document.createElement('div');
    resizeWrap.style.cssText='position:relative;display:inline-block;max-width:100%';
    img.parentNode.insertBefore(resizeWrap, img);
    resizeWrap.appendChild(img);
    var rect=img.getBoundingClientRect();
    var wAtual=parseInt(rzW.value)||img.naturalWidth||1;
    resizeWrap._scale=rect.width/wAtual || 1;
    resizeHandle=document.createElement('div');
    resizeHandle.className='resize-handle';
    resizeHandle.title='Arraste para redimensionar';
    resizeWrap.appendChild(resizeHandle);
    resizeBadge=document.createElement('div');
    resizeBadge.className='resize-badge';
    resizeWrap.appendChild(resizeBadge);
    resizeHandle.addEventListener('pointerdown', iniciarDragResize);
    atualizarPreviewResize();
  }
  function sairModoResize(){
    if(!resizeWrap) return;
    var img=document.getElementById('imgAntes');
    if(img){ img.style.width=''; img.style.height=''; boxAntes.insertBefore(img, resizeWrap); }
    resizeWrap.remove();
    resizeWrap=null; resizeHandle=null; resizeBadge=null; _dragResize=null;
    boxAntes.classList.remove('resizing');
  }
  function atualizarPreviewResize(){
    if(!resizeWrap) return;
    var img=document.getElementById('imgAntes');
    var w=parseInt(rzW.value)||1, h=parseInt(rzH.value)||1;
    var scale=resizeWrap._scale||1;
    img.style.width=(w*scale)+'px';
    img.style.height=(h*scale)+'px';
    resizeBadge.textContent=w+'×'+h+' px';
  }
  function iniciarDragResize(ev){
    ev.preventDefault();
    var wAtual=parseInt(rzW.value)||1, hAtual=parseInt(rzH.value)||1;
    _dragResize={ x:ev.clientX, y:ev.clientY, w:wAtual, h:hAtual, scale:resizeWrap._scale||1 };
    resizeHandle.setPointerCapture(ev.pointerId);
    resizeHandle.addEventListener('pointermove', moverDragResize);
    resizeHandle.addEventListener('pointerup', finalizarDragResize);
  }
  function moverDragResize(ev){
    if(!_dragResize) return;
    var dx=(ev.clientX-_dragResize.x)/_dragResize.scale;
    var novoW=Math.min(8000,Math.max(20,Math.round(_dragResize.w+dx)));
    var novoH;
    if(rzProp.checked){
      novoH=Math.max(20,Math.round(novoW*(_dragResize.h/_dragResize.w)));
    } else {
      var dy=(ev.clientY-_dragResize.y)/_dragResize.scale;
      novoH=Math.min(8000,Math.max(20,Math.round(_dragResize.h+dy)));
    }
    rzW.value=novoW; rzH.value=novoH;
    atualizarPreviewResize();
  }
  function finalizarDragResize(){
    if(resizeHandle){
      resizeHandle.removeEventListener('pointermove', moverDragResize);
      resizeHandle.removeEventListener('pointerup', finalizarDragResize);
    }
    _dragResize=null;
  }

  document.getElementById('btnAplicarResize').onclick=function(){
    var img=document.getElementById('imgAntes');
    var w=parseInt(rzW.value), h=parseInt(rzH.value);
    if(!img || !w || !h || w<1 || h<1){ alert('Informe largura e altura válidas.'); return; }
    pushHistorico();
    var tmp=document.createElement('canvas'); tmp.width=w; tmp.height=h;
    tmp.getContext('2d').drawImage(img,0,0,w,h);
    tmp.toBlob(function(blob){
      arquivoAtual=blob;
      sairModoResize();
      img.src=tmp.toDataURL('image/png');
      cropInfo.textContent='Redimensionado: '+w+'×'+h+' px';
      resizeBloco.classList.add('d-none');
    }, 'image/png');
  };
  document.getElementById('btnCancelarResize').onclick=function(){
    sairModoResize();
    resizeBloco.classList.add('d-none');
  };

  // ── Recortar (Cropper.js) — feito depois do redimensionamento ─
  document.getElementById('btnRecortar').onclick=function(){
    var img=document.getElementById('imgAntes');
    if(!img) return;
    sairModoResize();
    resizeBloco.classList.add('d-none');
    boxAntes.classList.add('cropping');
    cropPainel.classList.remove('d-none');
    cropTamanho.value='';
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

  cropTamanho.addEventListener('change', function(){
    if(!cropper) return;
    if(!this.value){ cropper.setAspectRatio(NaN); return; }
    var partes=this.value.split('x');
    var w=parseInt(partes[0]), h=parseInt(partes[1]);
    _cropEdit=true;
    cropper.setAspectRatio(w/h);
    cropper.setData({ width:w, height:h });
    cropWInp.value=w; cropHInp.value=h;
    setTimeout(function(){ _cropEdit=false; },50);
  });

  document.getElementById('btnAplicarCrop').onclick=function(){
    if(!cropper) return;
    pushHistorico();
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
  }

  btn.onclick=function(){
    if(!arquivoAtual) return;
    btn.disabled=true; var orig=btn.innerHTML;
    btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>Processando…';
    var fd=new FormData();
    fd.append('imagem',arquivoAtual,'imagem.png');
    fetch(URL_PROC,{method:'POST',headers:{'X-CSRF-Token':CSRF},body:fd})
      .then(function(r){return r.json();}).then(function(j){
        btn.disabled=false; btn.innerHTML=orig;
        if(!j.ok){ alert(j.erro||'Erro ao processar.'); return; }
        var a=document.createElement('a');
        a.href=j.imagem;
        a.download='imagem-web.webp';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
      }).catch(function(){ btn.disabled=false; btn.innerHTML=orig; alert('Falha de conexão. Tente de novo.'); });
  };
})();
</script>
