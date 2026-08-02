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
</style>

<div class="container-fluid" style="max-width:960px">
  <div class="d-flex align-items-center gap-2 mb-1">
    <h4 class="fw-bold mb-0"><i class="bi bi-magic me-2 text-primary"></i>Preparar imagem pra web</h4>
    <span class="badge badge-soft rounded-pill">SEO</span>
  </div>
  <p class="text-muted mb-4">Deixa a foto do produto/peça com cara de loja grande: <strong>tira o fundo bagunçado</strong>, põe <strong>fundo branco</strong>, padroniza o tamanho e salva em <strong>WebP</strong> (leve e rápido pro Google) — sem abrir editor nenhum.</p>

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

          <h6 class="fw-semibold mt-4 mb-3">2. Ajustes</h6>
          <div class="mb-3">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" id="rmFundo" <?= $rembgOk ? '' : 'disabled' ?>>
              <label class="form-check-label fw-semibold" for="rmFundo">
                <i class="bi bi-scissors me-1"></i>Remover o fundo (IA)
              </label>
            </div>
            <?php if (!$rembgOk): ?>
              <div class="small text-warning mt-1"><i class="bi bi-exclamation-triangle me-1"></i>Remoção de fundo indisponível no momento.</div>
            <?php else: ?>
              <div class="small text-muted mt-1">Isola a peça do fundo. Leva alguns segundos.</div>
            <?php endif; ?>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <label class="form-label small fw-semibold">Tamanho (px)</label>
              <select class="form-select" id="tamanho">
                <option value="400">400 × 400</option>
                <option value="600">600 × 600</option>
                <option value="800" selected>800 × 800</option>
                <option value="1000">1000 × 1000</option>
                <option value="1200">1200 × 1200</option>
              </select>
            </div>
            <div class="col-6" id="wrapFundo" style="display:none">
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

<script>
(function(){
  var URL_PROC='<?= url('/imagem/processar') ?>', CSRF='<?= csrf_token() ?>';
  var arquivo=null;
  var drop=document.getElementById('drop'), inp=document.getElementById('fileInput');
  var btn=document.getElementById('btnProcessar'), rm=document.getElementById('rmFundo');

  drop.onclick=function(){ inp.click(); };
  ['dragover','dragenter'].forEach(e=>drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.add('dragover');}));
  ['dragleave','drop'].forEach(e=>drop.addEventListener(e,function(ev){ev.preventDefault();drop.classList.remove('dragover');}));
  drop.addEventListener('drop',function(ev){ if(ev.dataTransfer.files[0]) setArquivo(ev.dataTransfer.files[0]); });
  inp.onchange=function(){ if(inp.files[0]) setArquivo(inp.files[0]); };

  function setArquivo(f){
    if(!f.type.startsWith('image/')){ alert('Escolha um arquivo de imagem.'); return; }
    arquivo=f; btn.disabled=false;
    var box=document.getElementById('boxAntes');
    box.innerHTML=''; var img=document.createElement('img'); img.src=URL.createObjectURL(f); box.appendChild(img);
  }
  rm.addEventListener('change',function(){ document.getElementById('wrapFundo').style.display=this.checked?'':'none'; });

  btn.onclick=function(){
    if(!arquivo) return;
    btn.disabled=true; var orig=btn.innerHTML;
    btn.innerHTML='<span class="spinner-border spinner-border-sm me-1"></span>'+(rm.checked?'Removendo o fundo… (uns segundos)':'Processando…');
    var fd=new FormData();
    fd.append('imagem',arquivo);
    fd.append('tamanho',document.getElementById('tamanho').value);
    fd.append('remover_fundo',rm.checked?'1':'0');
    fd.append('fundo',document.getElementById('fundo').value);
    fetch(URL_PROC,{method:'POST',headers:{'X-CSRF-Token':CSRF},body:fd})
      .then(function(r){return r.json();}).then(function(j){
        btn.disabled=false; btn.innerHTML=orig;
        if(!j.ok){ alert(j.erro||'Erro ao processar.'); return; }
        var transp=(document.getElementById('fundo').value==='transparente' && j.removeuFundo);
        var box=document.getElementById('boxDepois');
        box.className='prev-box '+(transp?'xadrez':'branco'); box.style.flex='1';
        box.innerHTML=''; var img=document.createElement('img'); img.src=j.imagem; box.appendChild(img);
        document.getElementById('btnBaixar').href=j.imagem;
        document.getElementById('infoTexto').textContent=j.dimensao+'×'+j.dimensao+' px · WebP · '+j.kb+' KB'+(j.removeuFundo?' · fundo removido':'');
        document.getElementById('infoResultado').style.setProperty('display','flex','important');
      }).catch(function(){ btn.disabled=false; btn.innerHTML=orig; alert('Falha de conexão. Tente de novo.'); });
  };
})();
</script>
