<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$titulo  = 'Encontrar Assistência Técnica Perto de Você';
$metaDesc= 'Encontre assistências técnicas próximas a você. Filtre por CEP, cidade, estado ou raio de distância.';
?>
<title><?= $titulo ?></title>
<meta name="description" content="<?= $metaDesc ?>">
<link rel="canonical" href="<?= $baseUrl ?>/assistencias">
<meta property="og:title" content="<?= $titulo ?>">
<meta property="og:description" content="<?= $metaDesc ?>">

<script type="application/ld+json">
{"@context":"https://schema.org","@type":"SearchResultsPage","name":"<?= $titulo ?>","description":"<?= $metaDesc ?>"}
</script>

<style>
:root{--brand:#0d9488}
/* Hero busca */
.search-hero{background:linear-gradient(135deg,#0b0d10 0%,#1a2744 60%,#1e3a5f 100%);padding:4rem 0 0;position:relative;overflow:hidden}
.search-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 70% at 30% 60%,rgba(13,148,136,.1),transparent 60%)}

/* Card de busca */
.search-card{background:rgba(255,255,255,.04);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,.1);border-radius:20px;padding:2rem;margin-bottom:-2rem;position:relative;z-index:2}
.s-label{color:#cbd5e1;font-size:.78rem;font-weight:700;text-transform:uppercase;letter-spacing:.06em;margin-bottom:.4rem;display:block}
.s-input{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);color:#fff;border-radius:10px;padding:.7rem 1rem;font-size:.92rem;width:100%;transition:.2s}
.s-input::placeholder{color:#94a3b8;opacity:1}
.s-input:focus{outline:none;border-color:rgba(13,148,136,.6);background:rgba(255,255,255,.1);color:#fff}
.s-input option{background:#1a1d23;color:#fff}
.btn-search{background:#0d9488;color:#fff;border:none;border-radius:12px;padding:.75rem 2rem;font-weight:800;font-size:1rem;cursor:pointer;transition:.2s;white-space:nowrap}
.btn-search:hover{background:#0f766e;transform:translateY(-1px)}

/* Raio slider */
.raio-slider{-webkit-appearance:none;width:100%;height:6px;border-radius:3px;background:linear-gradient(to right,#0d9488 0%,#0d9488 var(--pct,50%),rgba(255,255,255,.15) var(--pct,50%));outline:none}
.raio-slider::-webkit-slider-thumb{-webkit-appearance:none;width:20px;height:20px;border-radius:50%;background:#0d9488;cursor:pointer;box-shadow:0 2px 8px rgba(13,148,136,.5)}
.raio-labels{display:flex;justify-content:space-between;color:#8595ad;font-size:.7rem;margin-top:.3rem}

/* Filtros ativos */
.filtro-tag{display:inline-flex;align-items:center;gap:.4rem;background:rgba(13,148,136,.10);border:1px solid rgba(13,148,136,.35);color:#0f766e;border-radius:20px;font-size:.78rem;font-weight:600;padding:.3rem .8rem}
.filtro-tag button{background:none;border:none;color:#0f766e;cursor:pointer;padding:0;line-height:1;font-size:.9rem;opacity:.7}
.filtro-tag button:hover{opacity:1}

/* Cards resultado */
.res-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:16px;transition:.2s;text-decoration:none;display:flex;gap:1rem;padding:1.2rem;align-items:flex-start}
.res-card:hover{border-color:#0d9488;box-shadow:0 8px 24px rgba(13,148,136,.1);transform:translateY(-2px)}
.res-logo{width:64px;height:64px;border-radius:12px;flex-shrink:0;background:#f1f5f9;display:flex;align-items:center;justify-content:center;overflow:hidden;border:1px solid #e2e8f0}
.res-logo img{width:100%;height:100%;object-fit:contain;padding:4px}
.res-logo span{font-size:1.6rem;font-weight:900;color:#1e3a5f}
.res-nome{color:#0f172a;font-weight:800;font-size:1rem;margin-bottom:.2rem}
.res-loc{color:#64748b;font-size:.82rem;display:flex;align-items:center;gap:.3rem}
.res-dist{background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:20px;font-size:.72rem;font-weight:700;padding:.2rem .7rem}
.res-stars{color:#f59e0b;font-size:.85rem;letter-spacing:1px}
.res-servs{display:flex;flex-wrap:wrap;gap:.25rem;margin-top:.5rem}
.res-serv-tag{background:#f1f5f9;color:#475569;border-radius:5px;font-size:.7rem;padding:.15rem .5rem}
.destaque-badge{background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#7c2d12;font-size:.65rem;font-weight:800;padding:.15rem .55rem;border-radius:20px;margin-left:.4rem}
.premium-badge{background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.65rem;font-weight:800;padding:.15rem .55rem;border-radius:20px;margin-left:.4rem}
/* Card: link esticado + contatos clicáveis */
.res-card{position:relative}
.res-link{position:absolute;inset:0;z-index:1;border-radius:16px}
.res-main{flex:1;min-width:0}
.res-side{flex-shrink:0;min-width:132px;display:flex;flex-direction:column;align-items:flex-end;gap:.45rem;text-align:right}
.res-tel{position:relative;z-index:2;display:inline-flex;align-items:center;gap:.4rem;background:#f0fdfa;color:#0f766e;border:1px solid #99f6e4;border-radius:8px;font-size:.8rem;font-weight:700;padding:.4rem .7rem;text-decoration:none;white-space:nowrap}
.res-tel:hover{background:#ccfbf1}
.res-wa{position:relative;z-index:2;display:inline-flex;align-items:center;gap:.4rem;background:#25d366;color:#fff;border-radius:8px;font-size:.8rem;font-weight:700;padding:.4rem .7rem;text-decoration:none;white-space:nowrap}
.res-wa:hover{background:#1eb455}
/* Alternador lista/grade */
.view-toggle{display:inline-flex;border:1px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#fff}
.view-toggle button{background:#fff;border:none;padding:.42rem .62rem;color:#94a3b8;cursor:pointer;font-size:1rem;line-height:1;display:flex;align-items:center}
.view-toggle button.ativo{background:#0d9488;color:#fff}
/* Container: lista x grade */
#resultados.lista-view{display:flex;flex-direction:column;gap:.85rem}
#resultados.grid-view{display:grid;grid-template-columns:repeat(auto-fill,minmax(290px,1fr));gap:1rem}
.grid-view .res-card{flex-direction:column;align-items:stretch}
.grid-view .res-logo{width:64px;height:64px;margin:0 auto .2rem}
.grid-view .res-side{align-items:stretch;text-align:left;min-width:0;width:100%;margin-top:.9rem;padding-top:.9rem;border-top:1px solid #f1f5f9}
.grid-view .res-tel,.grid-view .res-wa{justify-content:center}
.grid-view .res-card.is-destaque{box-shadow:0 6px 22px rgba(245,158,11,.20)}
.grid-view .res-card.is-destaque::before{content:'';position:absolute;top:0;left:0;right:0;height:4px;background:linear-gradient(90deg,#fbbf24,#f59e0b,#d97706);border-radius:16px 16px 0 0}

/* Mapa */
#mapa{height:400px;border-radius:16px;border:1px solid #e2e8f0;overflow:hidden}
#mapaContainer{position:relative}
#mapaContainer.mapa-full{position:fixed;inset:0;z-index:3000;margin:0!important;background:#fff;padding:0}
#mapaContainer.mapa-full #mapa{height:100vh;border-radius:0;border:none}
.btn-mapa-full{position:absolute;top:12px;right:12px;z-index:1000;background:#fff;border:1px solid #cbd5e1;border-radius:8px;padding:.45rem .8rem;font-size:.82rem;font-weight:700;color:#1e3a5f;cursor:pointer;box-shadow:0 2px 10px rgba(0,0,0,.18)}
.btn-mapa-full:hover{background:#f8fafc}

/* Serviços chips */
.serv-chip{background:#f1f5f9;color:#1e3a5f;border-radius:20px;padding:.3rem .85rem;font-size:.78rem;font-weight:600;cursor:pointer;border:1px solid #e2e8f0;transition:.15s;text-decoration:none;display:inline-block}
.serv-chip:hover,.serv-chip.ativo{background:#0d9488;color:#fff;border-color:#0d9488}

/* Seletor de ordenação / nota */
.ord-select{background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:.4rem .7rem;font-size:.82rem;color:#475569;font-weight:600;cursor:pointer;outline:none}
.ord-select:focus{border-color:#0d9488}

.empty-state{text-align:center;padding:4rem 2rem;color:#94a3b8}

/* Loading */
.loading-overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);z-index:999;display:none;align-items:center;justify-content:center}
.loading-overlay.show{display:flex}
.spinner{width:48px;height:48px;border:4px solid rgba(255,255,255,.2);border-top-color:#0d9488;border-radius:50%;animation:spin .8s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}

@media(max-width:768px){.search-card{padding:1.2rem}.res-card{flex-direction:column}}
</style>

<!-- Loading overlay -->
<div class="loading-overlay" id="loadingOverlay"><div class="spinner"></div></div>

<!-- Hero + Formulário de busca -->
<div class="search-hero">
  <div class="container position-relative pb-5">

    <!-- Título -->
    <div class="text-center mb-4">
      <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(13,148,136,.15);border:1px solid rgba(13,148,136,.3);color:#5eead4;border-radius:100px;font-size:.78rem;font-weight:600;padding:.35rem 1rem;margin-bottom:1rem">
        <i class="bi bi-geo-alt-fill"></i> Busca por localização
      </div>
      <h1 style="color:#fff;font-size:clamp(1.8rem,4vw,2.8rem);font-weight:900;line-height:1.15;margin-bottom:.5rem">
        Encontre a assistência técnica<br><span style="color:#2dd4bf">mais perto de você</span>
      </h1>
      <p style="color:#94a3b8;font-size:.95rem">
        <strong style="color:#fff"><?= $total ?></strong> assistências cadastradas. Filtre por localização, serviço e distância.
      </p>
    </div>

    <!-- Card de busca principal -->
    <div class="search-card">
      <form method="GET" action="<?= $baseUrl ?>/assistencias" id="formBusca">
        <input type="hidden" name="lat" id="inputLat" value="<?= htmlspecialchars($_GET['lat'] ?? '') ?>">
        <input type="hidden" name="lng" id="inputLng" value="<?= htmlspecialchars($_GET['lng'] ?? '') ?>">

        <div class="row g-3 align-items-end">

          <!-- CEP -->
          <div class="col-12 col-md-2">
            <label class="s-label"><i class="bi bi-geo-alt me-1"></i>CEP</label>
            <div class="position-relative">
              <input type="text" id="inputCep" name="cep" class="s-input" placeholder="00000-000"
                     value="<?= htmlspecialchars($cep ? substr($cep,0,5).'-'.substr($cep,5) : '') ?>"
                     maxlength="9" oninput="mascaraCep(this)">
              <div id="cepStatus" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:.8rem"></div>
            </div>
          </div>

          <!-- Cidade -->
          <div class="col-12 col-md-2">
            <label class="s-label"><i class="bi bi-building me-1"></i>Cidade</label>
            <input type="text" name="cidade" id="inputCidade" class="s-input" placeholder="São Paulo"
                   value="<?= htmlspecialchars($cidade) ?>">
          </div>

          <!-- Bairro -->
          <div class="col-12 col-md-2">
            <label class="s-label"><i class="bi bi-signpost-2 me-1"></i>Bairro</label>
            <input type="text" name="bairro" id="inputBairro" class="s-input" placeholder="Centro, Vila..."
                   value="<?= htmlspecialchars($bairro) ?>">
          </div>

          <!-- Estado -->
          <div class="col-6 col-md-1">
            <label class="s-label">UF</label>
            <select name="estado" id="inputEstado" class="s-input">
              <option value="">—</option>
              <?php foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
              <option value="<?= $uf ?>" <?= $estado===$uf?'selected':'' ?>><?= $uf ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <!-- Serviço -->
          <div class="col-6 col-md-3">
            <label class="s-label"><i class="bi bi-tools me-1"></i>Tipo de serviço</label>
            <input type="text" name="servico" class="s-input" placeholder="Celular, TV, Notebook..."
                   value="<?= htmlspecialchars($serv) ?>" list="servicosList" id="inputServico">
            <datalist id="servicosList">
              <?php foreach($servicos as $s): ?>
              <option value="<?= htmlspecialchars($s['nome']) ?>">
              <?php endforeach; ?>
            </datalist>
          </div>

          <!-- Botão buscar -->
          <div class="col-12 col-md-2">
            <button type="submit" class="btn-search w-100">
              <i class="bi bi-search me-1"></i>Buscar
            </button>
          </div>
        </div>

        <!-- Raio de distância -->
        <div class="mt-3 pt-3" style="border-top:1px solid rgba(255,255,255,.07)">
          <div class="d-flex align-items-center gap-3 flex-wrap">
            <label class="s-label mb-0" style="white-space:nowrap">
              <i class="bi bi-circle me-1"></i>Raio de distância:
              <strong id="raioLabel" style="color:#0d9488"><?= $raio > 0 ? ($raio < 1 ? (int)round($raio*1000).' m' : rtrim(rtrim(number_format($raio,1,',',''),'0'),',').' km') : 'Qualquer' ?></strong>
            </label>
            <div style="flex:1;min-width:200px">
              <input type="range" name="raio" id="raioSlider" class="raio-slider"
                     min="0" max="5" step="0.5" value="<?= $raio ?>"
                     oninput="atualizarRaio(this.value)">
              <div class="raio-labels">
                <span>Todas</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5km</span>
              </div>
            </div>
            <?php if(!$lat && !$lng): ?>
            <small style="color:#64748b;font-size:.72rem"><i class="bi bi-info-circle me-1"></i>Escolha o raio e use sua localização (ou CEP)</small>
            <?php endif; ?>

            <!-- Usar minha localização -->
            <button type="button" onclick="usarMinhaLocalizacao()" class="btn btn-outline-secondary btn-sm" style="color:#94a3b8;border-color:rgba(255,255,255,.15);white-space:nowrap">
              <i class="bi bi-crosshair me-1"></i>Usar minha localização
            </button>
          </div>
        </div>
      </form>
    </div>

  </div>
</div>

<!-- Chips de serviços populares -->
<div style="background:#1a1d23;padding:1rem 0;border-bottom:1px solid rgba(255,255,255,.06)">
  <div class="container">
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <span style="color:#6b7280;font-size:.78rem;font-weight:600;white-space:nowrap">Popular:</span>
      <?php foreach(array_slice($servicos,0,10) as $s): ?>
      <a href="<?= $baseUrl ?>/assistencias?servico=<?= urlencode($s['nome']) ?><?= $estado?'&estado='.$estado:'' ?><?= $cidade?'&cidade='.urlencode($cidade):'' ?>"
         class="serv-chip <?= $serv===$s['nome']?'ativo':'' ?>">
        <?= htmlspecialchars($s['nome']) ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Conteúdo principal -->
<div style="background:#f8fafc;padding:2.5rem 0;min-height:60vh">
<div class="container">

  <!-- Busca rápida (AJAX) por nome + CTA de cadastro -->
  <style>
    .cta-cadastro{transition:transform .15s ease,box-shadow .15s ease}
    .cta-cadastro:hover{transform:translateY(-2px);box-shadow:0 14px 30px rgba(217,119,6,.48)}
    .cta-cadastro::after{content:'';position:absolute;top:0;left:-70%;width:45%;height:100%;background:linear-gradient(120deg,transparent,rgba(255,255,255,.5),transparent);transform:skewX(-20deg);animation:ctaShine 3.4s ease-in-out infinite}
    @keyframes ctaShine{0%{left:-70%}45%{left:140%}100%{left:140%}}
    @media(max-width:640px){.cta-cadastro{width:100%;justify-content:center}}
  </style>
  <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;margin-bottom:1.6rem">
    <div style="flex:1;min-width:280px">
      <label for="brInput" style="display:block;color:#334155;font-weight:700;font-size:.9rem;margin-bottom:.5rem">
        <i class="bi bi-lightning-charge-fill" style="color:#0d9488"></i> Busque por nome, cidade, bairro ou CEP
      </label>
      <div style="position:relative">
        <i class="bi bi-search" style="position:absolute;left:1rem;top:50%;transform:translateY(-50%);color:#94a3b8;pointer-events:none"></i>
        <input type="text" id="brInput" autocomplete="off" placeholder="Nome, cidade, bairro ou CEP (ex: Timetec, Tatuí, 18270-000…)"
               style="width:100%;padding:.85rem 2.6rem;border:1px solid #cbd5e1;border-radius:12px;font-size:1rem;background:#fff;outline:none"
               onfocus="this.style.borderColor='#0d9488';this.style.boxShadow='0 0 0 3px rgba(13,148,136,.12)'"
               onblur="this.style.borderColor='#cbd5e1';this.style.boxShadow='none'">
        <div id="brSpin" style="display:none;position:absolute;right:1rem;top:50%;transform:translateY(-50%)">
          <div class="spinner-border spinner-border-sm" style="color:#0d9488;width:1.1rem;height:1.1rem"></div>
        </div>
        <div id="brResultados" role="listbox"
             style="display:none;position:absolute;left:0;right:0;top:100%;margin-top:.4rem;background:#fff;border:1px solid #e2e8f0;border-radius:12px;box-shadow:0 14px 34px rgba(15,23,42,.14);z-index:60;overflow:hidden;max-height:440px;overflow-y:auto"></div>
      </div>
    </div>
    <button type="button" id="brGeoBtn" title="Buscar assistências perto de mim"
            style="display:flex;align-items:center;gap:.5rem;background:#fff;border:1px solid #cbd5e1;color:#0d9488;padding:.85rem 1.1rem;border-radius:12px;font-weight:700;white-space:nowrap;cursor:pointer;height:fit-content">
      <i class="bi bi-crosshair"></i> Perto de mim
    </button>
    <a href="<?= $baseUrl ?>/diretorio/cadastrar" class="cta-cadastro"
       style="display:flex;align-items:center;gap:.75rem;text-decoration:none;background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%);color:#fff;padding:.8rem 1.5rem;border-radius:12px;font-weight:800;box-shadow:0 8px 22px rgba(217,119,6,.38);white-space:nowrap;position:relative;overflow:hidden">
      <i class="bi bi-shop-window" style="font-size:1.5rem"></i>
      <span style="line-height:1.2;text-align:left">
        <span style="display:block;font-size:.72rem;font-weight:600;opacity:.92">Sua empresa ainda não está aqui?</span>
        <span style="display:block;font-size:1.02rem">Cadastre-se grátis <i class="bi bi-arrow-right-circle-fill"></i></span>
      </span>
    </a>
  </div>
  <script>
  (function(){
    var inp=document.getElementById('brInput'), box=document.getElementById('brResultados'), spin=document.getElementById('brSpin');
    var BASE='<?= $baseUrl ?>', timer=null, ctrl=null, ativo=-1, itens=[];
    function esc(s){return (s||'').replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});}
    function fechar(){box.style.display='none';ativo=-1;}
    function render(){
      if(!itens.length){
        box.innerHTML='<div style="padding:1rem 1.1rem;color:#64748b;font-size:.9rem">Nenhuma empresa com esse nome. <a href="'+BASE+'/diretorio/cadastrar" style="color:#0d9488;font-weight:700">Cadastrar a minha</a></div>';
        box.style.display='block';return;
      }
      box.innerHTML=itens.map(function(e,i){
        var logo=e.logo?'<img src="'+esc(e.logo)+'" alt="" style="width:100%;height:100%;object-fit:contain;padding:3px">':'<span style="font-weight:800;color:#1e3a5f">'+esc(e.inicial)+'</span>';
        var nota=e.aval>0?'<span style="color:#f59e0b"><i class="bi bi-star-fill"></i> '+e.nota.toFixed(1).replace('.',',')+'</span> <span style="color:#94a3b8">('+e.aval+')</span>':'<span style="color:#94a3b8">Sem avaliações</span>';
        var badge=e.destaque?'<span style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.6rem;font-weight:800;padding:.1rem .4rem;border-radius:6px;margin-left:.4rem;vertical-align:middle">DESTAQUE</span>':'';
        var local=e.local?'<i class="bi bi-geo-alt"></i> '+esc(e.local)+' · ':'';
        var dist=(e.distancia!=null)?'<span style="color:#0d9488;font-weight:700"> · '+String(e.distancia).replace('.',',')+' km</span>':'';
        return '<a href="'+esc(e.url)+'" class="br-item" data-i="'+i+'" style="display:flex;gap:.75rem;align-items:center;padding:.7rem 1rem;text-decoration:none;border-bottom:1px solid #f1f5f9;background:'+(i===ativo?'#f0fdfa':'#fff')+'">'
          +'<div style="width:44px;height:44px;border-radius:10px;background:#f1f5f9;border:1px solid #e2e8f0;display:flex;align-items:center;justify-content:center;overflow:hidden;flex-shrink:0">'+logo+'</div>'
          +'<div style="min-width:0;flex:1">'
          +'<div style="font-weight:700;color:#0f172a;font-size:.92rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+esc(e.nome)+badge+'</div>'
          +'<div style="font-size:.8rem;color:#64748b;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">'+local+nota+dist+'</div>'
          +'</div><i class="bi bi-chevron-right" style="color:#cbd5e1;flex-shrink:0"></i></a>';
      }).join('');
      box.style.display='block';
    }
    function buscarUrl(params){
      if(ctrl)ctrl.abort();
      ctrl=('AbortController' in window)?new AbortController():null;
      spin.style.display='block';
      var qs=Object.keys(params).map(function(k){return k+'='+encodeURIComponent(params[k]);}).join('&');
      fetch(BASE+'/api/diretorio/buscar?'+qs,ctrl?{signal:ctrl.signal}:{})
        .then(function(r){return r.json();})
        .then(function(d){spin.style.display='none';itens=d.itens||[];ativo=-1;render();})
        .catch(function(){spin.style.display='none';});
    }
    function buscarPorCep(cep){
      spin.style.display='block';
      fetch(BASE+'/api/diretorio/geocode?cep='+cep)
        .then(function(r){return r.json();})
        .then(function(d){
          if(d.lat&&d.lng){buscarUrl({lat:d.lat,lng:d.lng});}
          else{spin.style.display='none';itens=[];ativo=-1;render();}
        })
        .catch(function(){spin.style.display='none';});
    }
    function buscarPertoDeMim(){
      if(!navigator.geolocation){alert('Geolocalização não disponível.');return;}
      spin.style.display='block';
      navigator.geolocation.getCurrentPosition(function(pos){
        buscarUrl({lat:pos.coords.latitude,lng:pos.coords.longitude});
      }, function(){spin.style.display='none';alert('Não foi possível obter sua localização.');});
    }
    function buscar(q){
      var cep=q.replace(/\D/g,'');
      if(cep.length===8){buscarPorCep(cep);return;}
      buscarUrl({q:q});
    }
    document.getElementById('brGeoBtn').addEventListener('click',buscarPertoDeMim);
    inp.addEventListener('input',function(){
      var q=this.value.trim();clearTimeout(timer);
      if(q.length<2){fechar();if(ctrl)ctrl.abort();spin.style.display='none';return;}
      timer=setTimeout(function(){buscar(q);},250);
    });
    inp.addEventListener('keydown',function(e){
      if(box.style.display==='none')return;
      if(e.key==='ArrowDown'){e.preventDefault();ativo=Math.min(ativo+1,itens.length-1);render();var el=box.querySelector('[data-i="'+ativo+'"]');if(el)el.scrollIntoView({block:'nearest'});}
      else if(e.key==='ArrowUp'){e.preventDefault();ativo=Math.max(ativo-1,0);render();var el2=box.querySelector('[data-i="'+ativo+'"]');if(el2)el2.scrollIntoView({block:'nearest'});}
      else if(e.key==='Enter'){if(ativo>=0&&itens[ativo]){e.preventDefault();window.location.href=itens[ativo].url;}}
      else if(e.key==='Escape'){fechar();}
    });
    inp.addEventListener('focus',function(){if(itens.length&&this.value.trim().length>=2)box.style.display='block';});
    document.addEventListener('click',function(e){if(!box.contains(e.target)&&e.target!==inp)fechar();});
  })();
  </script>


  <?php if(!empty($raioIgnorado) || !empty($servicoIgnorado) || !empty($bairroIgnorado)): ?>
  <div style="background:#fffbeb;border:1px solid #fde68a;border-radius:12px;padding:.8rem 1.1rem;margin-bottom:1rem;color:#92400e;font-size:.88rem">
    <i class="bi bi-info-circle-fill me-1"></i>
    <?php if(!empty($servicoIgnorado)): ?>
      Nenhuma assistência<?= $bairro ? ' em <strong>'.e($bairro).'</strong>' : ($cidade ? ' em <strong>'.e($cidade).'</strong>' : '') ?> tem <strong>"<?= e($serv) ?>"</strong> cadastrado como serviço — mostrando as <strong>demais da região</strong> (elas podem atender mesmo assim; confirme direto com a empresa).
    <?php elseif(!empty($bairroIgnorado)): ?>
      Não achamos assistências no bairro <strong><?= e($bairro) ?></strong> — mostrando as de <strong><?= e($cidade ?: 'toda a região') ?></strong>.
    <?php else: ?>
      Não achamos assistências a menos de <strong><?= $raio < 1 ? (int)round($raio*1000).' m' : rtrim(rtrim(number_format($raio,1,',',''),'0'),',').' km' ?></strong> daí — mostrando as <strong>mais próximas da região</strong>. (A distância exata depende do cadastro de cada empresa.)
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- Filtros ativos + total -->
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <span style="color:#0f172a;font-weight:700"><?= $total ?> assistência<?= $total!=1?'s':'' ?> encontrada<?= $total!=1?'s':'' ?></span>
      <?php if($cidade): ?><span class="filtro-tag"><i class="bi bi-building"></i><?= e($cidade) ?><button onclick="removerFiltro('cidade')">×</button></span><?php endif; ?>
      <?php if($bairro): ?><span class="filtro-tag"><i class="bi bi-signpost-2"></i><?= e($bairro) ?><button onclick="removerFiltro('bairro')">×</button></span><?php endif; ?>
      <?php if($estado): ?><span class="filtro-tag"><i class="bi bi-map"></i><?= e($estado) ?><button onclick="removerFiltro('estado')">×</button></span><?php endif; ?>
      <?php if($serv): ?><span class="filtro-tag"><i class="bi bi-tools"></i><?= e($serv) ?><button onclick="removerFiltro('servico')">×</button></span><?php endif; ?>
      <?php if($raio > 0): ?><span class="filtro-tag"><i class="bi bi-circle"></i><?= $raio < 1 ? (int)round($raio*1000).' m' : rtrim(rtrim(number_format($raio,1,',',''),'0'),',').' km' ?><button onclick="removerFiltro('raio')">×</button></span><?php endif; ?>
      <?php if($busca||$cidade||$bairro||$estado||$serv||$raio): ?>
      <a href="<?= $baseUrl ?>/assistencias" style="color:#0d9488;font-size:.82rem;text-decoration:none">Limpar tudo</a>
      <?php endif; ?>
    </div>
    <form method="GET" action="<?= $baseUrl ?>/assistencias" class="d-flex gap-2 align-items-center flex-wrap" id="formOrdenar">
      <?php foreach(['busca'=>$busca,'cep'=>$cep,'estado'=>$estado,'cidade'=>$cidade,'bairro'=>$bairro,'raio'=>$raio,'servico'=>$serv,'lat'=>$lat,'lng'=>$lng] as $k=>$v): if(!empty($v)): ?>
      <input type="hidden" name="<?= $k ?>" value="<?= htmlspecialchars($v) ?>">
      <?php endif; endforeach; ?>
      <div class="view-toggle" role="group" aria-label="Modo de exibição">
        <button type="button" id="btnLista" title="Ver em lista" aria-label="Ver em lista" onclick="setView('lista')"><i class="bi bi-list-ul"></i></button>
        <button type="button" id="btnGrid" class="ativo" title="Ver em grade" aria-label="Ver em grade" onclick="setView('grid')"><i class="bi bi-grid-3x3-gap-fill"></i></button>
      </div>
      <select name="ordenar" class="ord-select" title="Ordenar" onchange="document.getElementById('formOrdenar').submit()">
        <option value="relevancia" <?= $ordenar==='relevancia'?'selected':'' ?>>Mais relevantes</option>
        <option value="avaliacao"  <?= $ordenar==='avaliacao'?'selected':'' ?>>Melhor avaliadas</option>
        <option value="avaliacoes" <?= $ordenar==='avaliacoes'?'selected':'' ?>>Mais avaliadas</option>
        <?php if($lat && $lng): ?>
        <option value="proximas"   <?= $ordenar==='proximas'?'selected':'' ?>>Mais próximas</option>
        <?php endif; ?>
        <option value="az"         <?= $ordenar==='az'?'selected':'' ?>>Nome (A–Z)</option>
      </select>
      <select name="nota_min" class="ord-select" title="Nota mínima" onchange="document.getElementById('formOrdenar').submit()">
        <option value="0"   <?= !$notaMin?'selected':'' ?>>Qualquer nota</option>
        <option value="3"   <?= (float)$notaMin===3.0?'selected':'' ?>>3★ ou mais</option>
        <option value="4"   <?= (float)$notaMin===4.0?'selected':'' ?>>4★ ou mais</option>
        <option value="4.5" <?= (float)$notaMin===4.5?'selected':'' ?>>4,5★ ou mais</option>
      </select>
      <button type="button" onclick="alternarMapa()" class="btn btn-sm btn-outline-secondary" id="btnMapa">
        <i class="bi bi-map me-1"></i>Ver no mapa
      </button>
    </form>
  </div>

  <!-- Mapa (oculto por padrão) -->
  <div id="mapaContainer" style="display:none;margin-bottom:1.5rem">
    <button type="button" class="btn-mapa-full" onclick="toggleMapaGrande()" id="btnMapaGrande">
      <i class="bi bi-arrows-fullscreen me-1"></i>Tela cheia
    </button>
    <div id="mapa"></div>
    <div style="color:#64748b;font-size:.78rem;margin-top:.5rem;text-align:center">
      <i class="bi bi-info-circle me-1"></i>Mapa exibe empresas com localização cadastrada.
    </div>
  </div>

  <!-- Grid de resultados -->
  <?php if($empresas): ?>
  <div id="resultados" class="grid-view">
    <?php foreach($empresas as $emp):
      $mediaEmp = round((float)$emp['media_nota'], 1);
      $distancia = isset($emp['distancia_km']) ? round((float)$emp['distancia_km'], 1) : null;
      $telNum = preg_replace('/\D/','', $emp['telefone'] ?? '');
      $waNum  = preg_replace('/\D/','', $emp['whatsapp_publico'] ?? '');
      if(!$waNum) $waNum = $telNum;
      $telFmt = '';
      if(strlen($telNum) >= 10){
        $ddd = substr($telNum,0,2); $r = substr($telNum,2);
        $telFmt = '('.$ddd.') '.(strlen($r) >= 9 ? substr($r,0,5).'-'.substr($r,5,4) : substr($r,0,4).'-'.substr($r,4,4));
      }
      $waLink = strlen($waNum) >= 10 ? 'https://wa.me/55'.$waNum : '';
    ?>
    <div class="res-card <?= $emp['em_destaque'] ? 'is-destaque' : '' ?>"
         style="<?= $emp['em_destaque'] ? 'border-color:#f59e0b' : '' ?>">
      <a href="<?= $baseUrl ?>/assistencias/<?= htmlspecialchars($emp['slug']) ?>" class="res-link" aria-label="Ver <?= htmlspecialchars($emp['nome_fantasia']) ?>"></a>

      <!-- Logo -->
      <div class="res-logo">
        <?php if($emp['logo']): ?>
        <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($emp['logo']) ?>" alt="Logo">
        <?php else: ?>
        <svg viewBox="0 0 200 50" style="width:100%;height:100%" aria-label="FixaOS"><rect width="200" height="50" rx="8" fill="#1e3a5f"/><text x="100" y="34" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="30" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
        <?php endif; ?>
      </div>

      <!-- Info -->
      <div class="res-main">
        <div class="d-flex align-items-center flex-wrap gap-1 mb-1">
          <div class="res-nome"><?= htmlspecialchars($emp['nome_fantasia']) ?></div>
          <?php if($emp['em_destaque']): ?>
            <?= $emp['diretorio_destaque']==='premium' ? '<span class="premium-badge">⭐ PREMIUM</span>' : '<span class="destaque-badge">🔥 DESTAQUE</span>' ?>
          <?php endif; ?>
        </div>

        <div class="d-flex align-items-center gap-3 flex-wrap mb-1">
          <div class="res-loc">
            <i class="bi bi-geo-alt-fill" style="color:#0d9488"></i>
            <?= htmlspecialchars($emp['cidade'] ?? '') ?><?= $emp['uf'] ? '/'.$emp['uf'] : '' ?>
            <?php if($emp['bairro']): ?>— <?= htmlspecialchars($emp['bairro']) ?><?php endif; ?>
          </div>
          <?php if($distancia !== null): ?>
          <span class="res-dist"><i class="bi bi-arrow-up-right me-1"></i><?= $distancia ?>km de distância</span>
          <?php endif; ?>
        </div>

        <?php $endRua = trim(($emp['logradouro'] ?? '').($emp['numero'] ? ', '.$emp['numero'] : '')); ?>
        <?php if($endRua): ?>
        <div style="color:#64748b;font-size:.8rem;margin-bottom:.4rem">
          <i class="bi bi-signpost-split" style="color:#94a3b8"></i> <?= htmlspecialchars($endRua) ?>
        </div>
        <?php endif; ?>

        <?php if($emp['descricao_publica']): ?>
        <div style="color:#64748b;font-size:.83rem;line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.5rem">
          <?= htmlspecialchars($emp['descricao_publica']) ?>
        </div>
        <?php endif; ?>

        <?php if($emp['especialidades']): ?>
        <div class="res-servs">
          <?php foreach(array_slice(explode(',',$emp['especialidades']),0,4) as $esp): ?>
          <span class="res-serv-tag"><?= htmlspecialchars(trim($esp)) ?></span>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Avaliação e contato -->
      <div class="res-side">
        <?php if($emp['total_avaliacoes'] > 0): ?>
        <div>
          <div class="res-stars"><?php for($i=1;$i<=5;$i++) echo $i<=$mediaEmp?'★':'☆'; ?></div>
          <div style="color:#0f172a;font-weight:800;font-size:1.05rem"><?= number_format($mediaEmp,1) ?> <span style="color:#94a3b8;font-weight:600;font-size:.72rem">· <?= $emp['total_avaliacoes'] ?> aval.</span></div>
        </div>
        <?php else: ?>
        <div style="color:#94a3b8;font-size:.78rem">Sem avaliações</div>
        <?php endif; ?>

        <?php if($telFmt): ?>
        <a href="tel:+55<?= $telNum ?>" class="res-tel"><i class="bi bi-telephone-fill"></i> <?= $telFmt ?></a>
        <?php endif; ?>
        <?php if($waLink): ?>
        <a href="<?= $waLink ?>" target="_blank" rel="nofollow noopener" class="res-wa"><i class="bi bi-whatsapp"></i> WhatsApp</a>
        <?php endif; ?>
      </div>

    </div>
    <?php endforeach; ?>
  </div>

  <script>
  function setView(m){
    var c=document.getElementById('resultados'); if(!c) return;
    var grid = (m==='grid');
    c.className = grid ? 'grid-view' : 'lista-view';
    var bg=document.getElementById('btnGrid'), bl=document.getElementById('btnLista');
    if(bg) bg.classList.toggle('ativo', grid);
    if(bl) bl.classList.toggle('ativo', !grid);
    try{ localStorage.setItem('dirView', m); }catch(e){}
  }
  (function(){ try{ var m=localStorage.getItem('dirView'); if(m==='lista') setView('lista'); }catch(e){} })();
  </script>

  <!-- Paginação -->
  <?php if($totalPags > 1):
    $qBase = array_filter(['busca'=>$busca,'cep'=>$cep,'estado'=>$estado,'cidade'=>$cidade,'bairro'=>$bairro,'raio'=>$raio,'servico'=>$serv,'lat'=>$lat,'lng'=>$lng,'ordenar'=>$ordenar,'nota_min'=>$notaMin]); ?>
  <div class="mt-4 d-flex justify-content-center">
    <?= paginacao_condensada($pag, $totalPags, fn($p) => $baseUrl.'/assistencias?'.http_build_query($p>1 ? ($qBase + ['pag'=>$p]) : $qBase)) ?>
  </div>
  <?php endif; ?>

  <?php else: ?>
  <div class="empty-state">
    <i class="bi bi-search" style="font-size:3.5rem;display:block;margin-bottom:1rem;color:#cbd5e1"></i>
    <h4 style="color:#475569;margin-bottom:.5rem">Nenhuma assistência encontrada</h4>
    <p style="font-size:.9rem">Tente ampliar o raio de busca ou usar filtros diferentes.</p>
    <div class="d-flex gap-2 justify-content-center flex-wrap mt-3">
      <a href="<?= $baseUrl ?>/assistencias" class="btn btn-outline-secondary">Limpar filtros</a>
      <a href="<?= $baseUrl ?>/diretorio/cadastrar" class="btn" style="background:#0d9488;color:#fff;font-weight:700"><i class="bi bi-shop-window me-1"></i>É a sua empresa? Cadastre grátis</a>
    </div>
  </div>
  <?php endif; ?>

</div>
</div>

<!-- Cadastre sua empresa (autônomos / sem CNPJ) -->
<div style="background:linear-gradient(135deg,#16264A,#1E3A5F);padding:2.6rem 1rem;text-align:center">
  <div class="container">
    <h3 style="color:#fff;font-weight:800;font-size:1.35rem;margin-bottom:.4rem">Sua empresa não está no diretório?</h3>
    <p style="color:#9fb0cc;margin-bottom:1.3rem">Autônomo ou sem CNPJ? Cadastre sua assistência <strong style="color:#5eead4">grátis</strong> e seja encontrado no Google.</p>
    <a href="<?= $baseUrl ?>/diretorio/cadastrar" style="background:#0d9488;color:#fff;padding:.85rem 2rem;border-radius:12px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem">
      <i class="bi bi-shop-window"></i> Cadastrar minha empresa
    </a>
  </div>
</div>

<!-- Leaflet.js para mapa -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
const BASE_URL = '<?= $baseUrl ?>';
let mapa = null;
let mapaAberto = false;

// Dados das empresas para o mapa
const empresasData = <?= json_encode(array_map(fn($e) => [
  'lat'      => $e['latitude'],
  'lng'      => $e['longitude'],
  'nome'     => $e['nome_fantasia'],
  'cidade'   => $e['cidade'],
  'uf'       => $e['uf'],
  'endereco' => trim(implode(', ', array_filter([
                  trim(($e['logradouro'] ?? '').' '.($e['numero'] ?? '')),
                  $e['bairro'] ?? '',
                  ($e['cidade'] ?? '').'/'.($e['uf'] ?? '')
                ], fn($v) => trim($v, ' /') !== ''))),
  'slug'     => $e['slug'],
  'nota'     => round((float)$e['media_nota'], 1),
  'avals'    => (int)$e['total_avaliacoes'],
  'logo'     => $e['logo'] ? $baseUrl.'/uploads/'.$e['logo'] : null,
  'inicial'  => strtoupper(substr($e['nome_fantasia'],0,1)),
  'destaque' => $e['diretorio_destaque'] ?? 'none',
  'wa'       => !empty($e['whatsapp_publico']) || !empty($e['telefone']),
], array_filter($mapaEmpresas ?? $empresas, fn($e) => $e['latitude'] && $e['longitude']))) ?>;

// ── Máscara CEP ────────────────────────────────────────────────────────
function mascaraCep(input) {
  let v = input.value.replace(/\D/g,'');
  if(v.length > 5) v = v.substring(0,5) + '-' + v.substring(5,8);
  input.value = v;
  if(v.replace(/\D/g,'').length === 8) buscarCep(v.replace(/\D/g,''));
}

// ── Buscar CEP via API ─────────────────────────────────────────────────
async function buscarCep(cep) {
  const status = document.getElementById('cepStatus');
  status.innerHTML = '<span style="color:#f59e0b">⟳</span>';
  try {
    const res = await fetch(BASE_URL + '/api/geocode?cep=' + cep);
    const data = await res.json();
    if(data.error) { status.innerHTML = '<span style="color:#ef4444">✗</span>'; return; }

    document.getElementById('inputCidade').value = data.cidade || '';
    document.getElementById('inputEstado').value  = data.estado || '';

    if(data.lat && data.lng) {
      document.getElementById('inputLat').value = data.lat;
      document.getElementById('inputLng').value = data.lng;
      // Ativar slider de raio
      const slider = document.getElementById('raioSlider');
      slider.disabled = false;
      slider.removeAttribute('title');
      status.innerHTML = '<span style="color:#22c55e">✓</span>';
    } else {
      status.innerHTML = '<span style="color:#f59e0b">✓</span>';
    }
  } catch(e) { status.innerHTML = '<span style="color:#ef4444">✗</span>'; }
}

// ── Raio slider ────────────────────────────────────────────────────────
function atualizarRaio(v) {
  v = parseFloat(v);
  const label = document.getElementById('raioLabel');
  label.textContent = v === 0 ? 'Qualquer' : (v < 1 ? (v * 1000) + ' m' : String(v).replace('.', ',') + ' km');
  const pct = (v / 5) * 100;
  document.getElementById('raioSlider').style.setProperty('--pct', pct + '%');
}
atualizarRaio(<?= $raio ?>);

// ── Minha localização ──────────────────────────────────────────────────
function usarMinhaLocalizacao() {
  if(!navigator.geolocation) { alert('Geolocalização não disponível.'); return; }
  navigator.geolocation.getCurrentPosition(pos => {
    document.getElementById('inputLat').value = pos.coords.latitude;
    document.getElementById('inputLng').value = pos.coords.longitude;
    const slider = document.getElementById('raioSlider');
    slider.disabled = false;
    if(slider.value == 0) { slider.value = 5; atualizarRaio(5); }
    document.getElementById('formBusca').submit();
  }, () => alert('Não foi possível obter sua localização.'));
}

// Ao buscar com um raio escolhido mas sem localização/CEP, pega a localização automaticamente.
(function(){
  var form = document.getElementById('formBusca');
  if(!form) return;
  form.addEventListener('submit', function(e){
    var raio = parseFloat(document.getElementById('raioSlider').value) || 0;
    var temLat = document.getElementById('inputLat').value;
    var cep = (document.getElementById('inputCep').value || '').replace(/\D/g,'');
    if(raio > 0 && !temLat && cep.length !== 8 && navigator.geolocation){
      e.preventDefault();
      navigator.geolocation.getCurrentPosition(function(pos){
        document.getElementById('inputLat').value = pos.coords.latitude;
        document.getElementById('inputLng').value = pos.coords.longitude;
        form.submit();
      }, function(){ form.submit(); }, {enableHighAccuracy:true, timeout:8000});
    }
  });
})();

// ── Mapa Leaflet ───────────────────────────────────────────────────────
function alternarMapa() {
  const container = document.getElementById('mapaContainer');
  mapaAberto = !mapaAberto;
  container.style.display = mapaAberto ? 'block' : 'none';
  document.getElementById('btnMapa').innerHTML = mapaAberto
    ? '<i class="bi bi-list me-1"></i>Ver lista'
    : '<i class="bi bi-map me-1"></i>Ver no mapa';

  if(mapaAberto) {
    if(!mapa) iniciarMapa();
    // Leaflet precisa recalcular o tamanho quando o container sai de display:none
    setTimeout(() => { if(mapa){ mapa.invalidateSize(); reenquadrarMapa(); } }, 250);
  }
}

function toggleMapaGrande() {
  const c = document.getElementById('mapaContainer');
  const full = c.classList.toggle('mapa-full');
  document.getElementById('btnMapaGrande').innerHTML = full
    ? '<i class="bi bi-fullscreen-exit me-1"></i>Sair da tela cheia'
    : '<i class="bi bi-arrows-fullscreen me-1"></i>Tela cheia';
  document.body.style.overflow = full ? 'hidden' : '';
  setTimeout(() => { if(mapa){ mapa.invalidateSize(); reenquadrarMapa(); } }, 200);
}
document.addEventListener('keydown', e => {
  if(e.key === 'Escape' && document.getElementById('mapaContainer').classList.contains('mapa-full')) toggleMapaGrande();
});

function iniciarMapa() {
  const lat  = <?= $lat ?: -15.788 ?>;
  const lng  = <?= $lng ?: -47.879 ?>;
  const zoom = <?= $lat ? 11 : 5 ?>;

  mapa = L.map('mapa').setView([lat, lng], zoom);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© OpenStreetMap'
  }).addTo(mapa);

  // Pin do usuário
  <?php if($lat && $lng): ?>
  L.circle([<?= $lat ?>, <?= $lng ?>], {
    color: '#0d9488', fillColor: '#0d9488', fillOpacity: 0.15,
    radius: <?= max($raio * 1000, 500) ?>
  }).addTo(mapa).bindPopup('Você está aqui');
  L.marker([<?= $lat ?>, <?= $lng ?>], {
    icon: L.divIcon({html:'<div style="background:#0d9488;width:14px;height:14px;border-radius:50%;border:3px solid #fff;box-shadow:0 2px 6px rgba(0,0,0,.3)"></div>',iconSize:[14,14],iconAnchor:[7,7]})
  }).addTo(mapa);
  <?php endif; ?>

  // Pins das empresas (agrupados em cluster p/ nao empilhar)
  window.mapaCluster = L.markerClusterGroup({ maxClusterRadius: 50, chunkedLoading: true, spiderfyOnMaxZoom: true });
  empresasData.forEach(e => {
    if(!e.lat || !e.lng) return;

    const borderColor = (e.destaque === 'premium' || e.destaque === 'basico') ? '#f59e0b' : '#1e3a5f';
    // Sem logo proprio -> usa a marca FixaOS (versao quadrada empilhada)
    const imgHtml = e.logo
      ? `<img src="${e.logo}" style="width:100%;height:100%;object-fit:contain;border-radius:50%;padding:4px">`
      : `<svg viewBox="0 0 200 50" style="width:100%;height:100%"><rect width="200" height="50" rx="8" fill="#1e3a5f"/><text x="100" y="34" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="30" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>`;

    const badgeHtml = e.destaque !== 'none'
      ? `<div style="position:absolute;top:-6px;right:-6px;background:${borderColor};color:#fff;border-radius:50%;width:16px;height:16px;display:flex;align-items:center;justify-content:center;font-size:9px;font-weight:900;border:1.5px solid #fff">${e.destaque==='premium'?'★':'🔥'}</div>`
      : '';

    const pinHtml = `
      <div style="position:relative;cursor:pointer" onclick="window.open('${BASE_URL}/assistencias/${e.slug}','_blank')">
        <div style="
          width:52px;height:52px;border-radius:50%;
          background:#fff;
          border:3px solid ${borderColor};
          box-shadow:0 4px 16px rgba(0,0,0,.25);
          display:flex;align-items:center;justify-content:center;
          overflow:hidden;position:relative;
        ">${imgHtml}</div>
        ${badgeHtml}
        <div style="
          position:absolute;bottom:-7px;left:50%;transform:translateX(-50%);
          width:0;height:0;
          border-left:7px solid transparent;
          border-right:7px solid transparent;
          border-top:9px solid ${borderColor};
        "></div>
      </div>`;

    const icon = L.divIcon({
      html: pinHtml,
      className: '',
      iconSize: [52, 61],
      iconAnchor: [26, 61],
      popupAnchor: [0, -65],
    });

    const starsHtml = '★'.repeat(Math.round(e.nota)) + '☆'.repeat(5 - Math.round(e.nota));

    window.mapaCluster.addLayer(L.marker([e.lat, e.lng], {icon}).bindPopup(`
      <div style="min-width:180px;font-family:system-ui,sans-serif">
        <div style="font-weight:800;font-size:.95rem;color:#0f172a;margin-bottom:.2rem">${e.nome}</div>
        <div style="color:#64748b;font-size:.8rem;margin-bottom:.4rem">📍 ${e.cidade}/${e.uf}</div>
        ${e.nota > 0 ? `<div style="color:#f59e0b;font-size:.85rem">${starsHtml} <span style="color:#374151;font-weight:700">${e.nota.toFixed(1)}</span> <span style="color:#94a3b8;font-size:.75rem">(${e.avals})</span></div>` : '<div style="color:#94a3b8;font-size:.8rem">Sem avaliações</div>'}
        <a href="${BASE_URL}/assistencias/${e.slug}" target="_blank"
           style="display:block;margin-top:.6rem;background:#0d9488;color:#fff;border-radius:8px;padding:.4rem .8rem;text-align:center;text-decoration:none;font-size:.8rem;font-weight:700">
          Ver perfil →
        </a>
        <a href="https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(e.endereco || (e.nome + ', ' + e.cidade + ', ' + e.uf))}" target="_blank"
           style="display:block;margin-top:.4rem;background:#1e3a5f;color:#fff;border-radius:8px;padding:.4rem .8rem;text-align:center;text-decoration:none;font-size:.8rem;font-weight:700">
          🧭 Como chegar
        </a>
      </div>
    `, {maxWidth: 220}));
  });
  mapa.addLayer(window.mapaCluster);

  reenquadrarMapa();
}

// Enquadra o mapa nas empresas (também chamado após invalidateSize ao abrir)
function reenquadrarMapa() {
  if(!mapa) return;
  <?php if(!$lat): ?>
  const pts = empresasData.filter(e => e.lat && e.lng).map(e => [e.lat, e.lng]);
  if(pts.length) mapa.fitBounds(pts, {padding:[40,40], maxZoom:13});
  <?php endif; ?>
}

// ── Remover filtro ─────────────────────────────────────────────────────
function removerFiltro(campo) {
  const url = new URL(window.location.href);
  url.searchParams.delete(campo);
  if(campo === 'raio') { url.searchParams.delete('lat'); url.searchParams.delete('lng'); }
  window.location.href = url.toString();
}

// ── Submitar com loading ───────────────────────────────────────────────
document.getElementById('formBusca').addEventListener('submit', () => {
  document.getElementById('loadingOverlay').classList.add('show');
});
</script>
