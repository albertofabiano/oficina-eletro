<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$titulo  = 'Encontre Assistências Técnicas — FixaOS';
$metaDesc= 'Diretório de assistências técnicas verificadas. Encontre a melhor assistência por cidade, tipo de equipamento e avaliações dos clientes.';

// Carregar blocos: banners pagos por empresas têm prioridade, depois AdSense master
$_dbAd = \App\Core\DB::pdo();
$_adDir = [];
// Blocos master (AdSense)
foreach ($_dbAd->query("SELECT posicao, codigo, ativo, titulo FROM master_adsense_blocos WHERE local='diretorio' ORDER BY posicao")->fetchAll() as $_b)
    $_adDir[$_b['posicao']] = ['tipo'=>'adsense', 'ativo'=>$_b['ativo'], 'codigo'=>$_b['codigo'], 'titulo'=>$_b['titulo']];
// Banners pagos por empresas (sobrepõem o AdSense na mesma posição)
foreach ($_dbAd->query("SELECT b.*, e.nome_fantasia, e.slug FROM diretorio_banners b JOIN diretorio_assinaturas a ON a.id=b.assinatura_id JOIN empresas e ON e.id=b.empresa_id WHERE b.aprovado=1 AND a.status='ativo' AND (a.data_fim IS NULL OR a.data_fim >= CURDATE())")->fetchAll() as $_bb)
    $_adDir[$_bb['posicao']] = ['tipo'=>'empresa', 'banner'=>$_bb];
?>
<style>
:root{--brand:#0d9488}
.dir-hero{background:linear-gradient(135deg,#0B1220 0%,#16264A 55%,#1E3A5F 100%);padding:4rem 0 3rem;position:relative;overflow:hidden}
.dir-hero::before{content:'';position:absolute;inset:0;background:radial-gradient(ellipse 70% 60% at 72% 45%,rgba(13,148,136,.20),transparent 70%)}
.dir-search-bar{background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:16px;padding:1rem 1.2rem;display:flex;gap:.8rem;flex-wrap:wrap;align-items:center;margin-top:2rem;backdrop-filter:blur(8px)}
.dir-input{background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);color:#fff;border-radius:10px;padding:.65rem 1rem;font-size:.9rem;flex:1;min-width:180px}
.dir-input::placeholder{color:#6b7280}
.dir-input:focus{outline:none;border-color:rgba(20,184,166,.6);background:rgba(255,255,255,.1)}
.dir-input option{background:#16264A;color:#fff}
.btn-buscar{background:#0d9488;color:#fff;border:none;border-radius:10px;padding:.65rem 1.6rem;font-weight:700;cursor:pointer;white-space:nowrap;transition:.2s}
.btn-buscar:hover{background:#0f766e}

/* Cards */
.dir-card{background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;transition:.2s;text-decoration:none;display:block;height:100%}
.dir-card:hover{transform:translateY(-4px);box-shadow:0 16px 40px rgba(15,42,74,.16);border-color:#0d9488}
.dir-card-capa{height:130px;background:linear-gradient(135deg,#1e3a5f,#0b1a2e);position:relative;overflow:hidden}
.dir-card-capa img{width:100%;height:100%;object-fit:cover}
.dir-card-logo{width:100px;height:48px;border-radius:10px;background:#fff;border:3px solid #fff;box-shadow:0 4px 12px rgba(0,0,0,.18);position:absolute;bottom:-20px;left:50%;transform:translateX(-50%);display:flex;align-items:center;justify-content:center;overflow:hidden;padding:5px;margin-top:20px}
.dir-card-logo img{max-width:100%;max-height:100%;width:auto;height:auto;object-fit:contain}
.dir-card-body{padding:1.8rem 1.2rem 1.2rem;margin-top:.6rem;text-align:center;display:flex;flex-direction:column;align-items:center;justify-content:center;min-height:140px}
.dir-card-nome{color:#0f172a;font-weight:800;font-size:1rem;margin-bottom:.2rem}
.dir-card-loc{color:#64748b;font-size:.8rem;display:flex;align-items:center;justify-content:center;gap:.3rem;margin-bottom:.6rem}
.dir-card-desc{color:#475569;font-size:.82rem;line-height:1.6;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin-bottom:.8rem;text-align:center}
.stars-row{display:flex;align-items:center;justify-content:center;gap:.4rem}
.stars{color:#f59e0b;font-size:.85rem;letter-spacing:1px}
.stars-count{color:#64748b;font-size:.78rem}
.dir-tag{display:inline-block;background:#f1f5f9;color:#475569;border-radius:6px;font-size:.72rem;padding:.2rem .55rem;margin:.2rem .15rem 0 0}

/* Sidebar filtros */
.filter-box{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.4rem;margin-bottom:1.2rem}
.filter-title{color:#0f172a;font-weight:700;font-size:.88rem;margin-bottom:.8rem;display:flex;align-items:center;gap:.4rem}
.filter-btn{display:block;width:100%;text-align:left;background:none;border:none;padding:.4rem .6rem;border-radius:8px;font-size:.85rem;color:#475569;cursor:pointer;transition:.15s}
.filter-btn:hover,.filter-btn.active{background:#f0fdfa;color:#0f766e;font-weight:600}

/* Chip serviços */
.serv-chip{background:#f1f5f9;color:#16264A;border-radius:20px;padding:.35rem .9rem;font-size:.8rem;font-weight:600;cursor:pointer;border:1px solid #e2e8f0;transition:.15s;text-decoration:none;display:inline-block;margin:.2rem}
.serv-chip:hover,.serv-chip.active{background:#0d9488;color:#fff;border-color:#0d9488}

.empty-state{text-align:center;padding:4rem 2rem;color:#94a3b8}
</style>

<!-- SEO Meta -->
<title><?= $titulo ?></title>
<meta name="description" content="<?= $metaDesc ?>">
<link rel="canonical" href="<?= $baseUrl ?>/assistencias">
<meta property="og:title" content="<?= $titulo ?>">
<meta property="og:description" content="<?= $metaDesc ?>">
<meta property="og:type" content="website">
<script type="application/ld+json">
{"@context":"https://schema.org","@type":"ItemList","name":"Diretório de Assistências Técnicas","description":"<?= $metaDesc ?>","numberOfItems":<?= $total ?>}
</script>

<!-- Hero + Busca -->
<div class="dir-hero">
  <div class="container position-relative">
    <div class="text-center">
      <div style="display:inline-flex;align-items:center;gap:6px;background:rgba(13,148,136,.15);border:1px solid rgba(20,184,166,.35);color:#5eead4;border-radius:100px;font-size:.78rem;font-weight:600;padding:.35rem 1rem;margin-bottom:1rem">
        <i class="bi bi-shield-check"></i> Assistências técnicas verificadas
      </div>
      <h1 style="color:#fff;font-size:clamp(1.8rem,4vw,3rem);font-weight:900;line-height:1.15;margin-bottom:.5rem">
        Encontre a melhor<br><span style="color:#2dd4bf">assistência técnica</span> perto de você
      </h1>
      <p style="color:#94a3b8;font-size:1rem;max-width:520px;margin:auto">
        <?= $total ?> assistências cadastradas, avaliadas por clientes reais.
      </p>
    </div>

    <form method="GET" action="<?= $baseUrl ?>/assistencias">
      <div class="dir-search-bar">
        <input type="text" name="busca" class="dir-input" placeholder="Buscar por nome, serviço..." value="<?= htmlspecialchars($busca) ?>" style="flex:2">
        <input type="text" name="cep" id="dirCep" class="dir-input" placeholder="CEP" maxlength="9" inputmode="numeric" value="<?= htmlspecialchars($_GET['cep'] ?? '') ?>" style="max-width:110px">
        <select name="estado" class="dir-input" style="max-width:110px">
          <option value="">Estado</option>
          <?php foreach(['AC','AL','AP','AM','BA','CE','DF','ES','GO','MA','MT','MS','MG','PA','PB','PR','PE','PI','RJ','RN','RS','RO','RR','SC','SP','SE','TO'] as $uf): ?>
          <option value="<?= $uf ?>" <?= $estado===$uf?'selected':'' ?>><?= $uf ?></option>
          <?php endforeach; ?>
        </select>
        <input type="text" name="cidade" class="dir-input" placeholder="Cidade" value="<?= htmlspecialchars($cidade) ?>" style="max-width:140px">
        <input type="text" name="bairro" class="dir-input" placeholder="Bairro" value="<?= htmlspecialchars($bairro ?? '') ?>" style="max-width:140px">
        <button type="submit" class="btn-buscar"><i class="bi bi-search me-1"></i>Buscar</button>
      </div>
    </form>
    <script>
    (function(){
      var cep = document.getElementById('dirCep');
      if(!cep) return;
      cep.addEventListener('blur', function(){
        var v = this.value.replace(/\D/g,'');
        if(v.length !== 8) return;
        var form = this.closest('form');
        fetch('https://viacep.com.br/ws/'+v+'/json/')
          .then(function(r){ return r.json(); })
          .then(function(d){
            if(!d || d.erro) return;
            if(d.uf){ var s=form.querySelector('select[name="estado"]'); if(s) s.value=d.uf; }
            if(d.localidade){ var c=form.querySelector('input[name="cidade"]'); if(c) c.value=d.localidade; }
            if(d.bairro){ var b=form.querySelector('input[name="bairro"]'); if(b) b.value=d.bairro; }
          })
          .catch(function(){});
      });
    })();
    </script>

    <!-- Perto de mim (geolocalização) com raio ajustável 0–5km -->
    <div class="text-center mt-3">
      <div style="display:inline-flex;flex-direction:column;gap:.55rem;background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:16px;padding:.9rem 1.3rem;min-width:280px;backdrop-filter:blur(6px)">
        <div style="color:#cbd5e1;font-size:.82rem"><i class="bi bi-geo-alt-fill me-1" style="color:#5eead4"></i>Raio de busca: <strong id="raioVal" style="color:#5eead4">5 km</strong></div>
        <input type="range" id="raioMim" min="0" max="5" step="1" value="5" style="width:100%;accent-color:#14b8a6;cursor:pointer">
        <div style="display:flex;justify-content:space-between;color:#64748b;font-size:.66rem"><span>Todas</span><span>1</span><span>2</span><span>3</span><span>4</span><span>5km</span></div>
        <button type="button" id="btnPertoMim" style="background:#0d9488;border:none;color:#fff;border-radius:100px;padding:.6rem 1.4rem;font-size:.87rem;font-weight:700;cursor:pointer;transition:.2s">
          <i class="bi bi-crosshair me-1"></i>Buscar perto de mim
        </button>
      </div>
    </div>
    <script>
    (function(){
      var b = document.getElementById('btnPertoMim');
      var s = document.getElementById('raioMim');
      var lbl = document.getElementById('raioVal');
      if(!b || !s) return;
      function upd(){ lbl.textContent = (s.value === '0') ? 'Todas' : s.value + ' km'; }
      s.addEventListener('input', upd); upd();
      var txt = b.innerHTML;
      b.addEventListener('click', function(){
        if(!navigator.geolocation){ alert('Seu navegador não suporta localização. Use a busca por CEP.'); return; }
        b.disabled = true; b.style.opacity = '.7';
        b.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Localizando você...';
        navigator.geolocation.getCurrentPosition(function(pos){
          window.location.href = '<?= $baseUrl ?>/encontrar?lat=' + pos.coords.latitude + '&lng=' + pos.coords.longitude + '&raio=' + s.value;
        }, function(){
          b.disabled = false; b.style.opacity = '1'; b.innerHTML = txt;
          alert('Não consegui acessar sua localização. Permita o acesso ao GPS ou use a busca por CEP.');
        }, {enableHighAccuracy:true, timeout:9000});
      });
    })();
    </script>

    <!-- Chips de serviços populares -->
    <?php if ($servicos): ?>
    <div class="text-center mt-3">
      <?php foreach(array_slice($servicos,0,8) as $s): ?>
      <a href="<?= $baseUrl ?>/assistencias?servico=<?= urlencode($s['nome']) ?>" class="serv-chip <?= $serv===$s['nome']?'active':'' ?>">
        <?= htmlspecialchars($s['nome']) ?>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Blocos de Anúncio do Diretório -->
<div style="background:#0f1117;padding:1.2rem 0;border-bottom:1px solid rgba(255,255,255,.06)">
  <div class="container">
    <div class="row g-3 justify-content-center align-items-center">
      <?php for($i=1;$i<=5;$i++):
        $bloco = $_adDir[$i] ?? null;
        $ativo = $bloco && ($bloco['ativo'] ?? false);
        $codigo = $bloco['codigo'] ?? '';
      ?>
      <div class="col-12 col-sm-6 col-lg text-center">
        <?php
        $bloco = $_adDir[$i] ?? null;
        if ($bloco && $bloco['tipo'] === 'empresa'):
          $bn = $bloco['banner'];
        ?>
          <a href="<?= htmlspecialchars($bn['link_url'] ?: ($baseUrl.'/assistencias/'.$bn['slug'])) ?>" target="_blank" style="display:block">
            <?php if($bn['imagem']): ?>
            <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($bn['imagem']) ?>" alt="<?= htmlspecialchars($bn['titulo']??'') ?>"
                 style="max-width:100%;max-height:90px;border-radius:8px;object-fit:contain">
            <?php else: ?>
            <div style="border:1.5px solid rgba(249,115,22,.5);border-radius:10px;padding:.8rem;min-height:90px;display:flex;align-items:center;justify-content:center;color:#fb923c;font-weight:700;font-size:.85rem">
              <?= htmlspecialchars($bn['titulo'] ?? $bn['nome_fantasia']) ?>
            </div>
            <?php endif; ?>
          </a>
        <?php elseif($bloco && $bloco['tipo']==='adsense' && $bloco['ativo'] && $bloco['codigo']): ?>
          <?= $bloco['codigo'] ?>
        <?php else: ?>
          <div style="border:1.5px dashed rgba(249,115,22,.35);border-radius:10px;padding:1rem .5rem;min-height:90px;display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.3rem">
            <i class="bi bi-megaphone" style="color:#f97316;font-size:1.4rem"></i>
            <div style="color:#f97316;font-size:.72rem;font-weight:700;letter-spacing:.05em">ANÚNCIO <?= $i ?></div>
            <div style="color:#4b5563;font-size:.68rem">Disponível</div>
          </div>
        <?php endif; ?>
      </div>
      <?php endfor; ?>
    </div>
  </div>
</div>

<!-- Conteúdo principal -->
<div style="background:#f8fafc;padding:3rem 0;min-height:60vh">
  <div class="container">
    <div class="row g-4">

      <!-- Sidebar filtros -->
      <div class="col-lg-3 d-none d-lg-block">
        <div class="filter-box">
          <div class="filter-title"><i class="bi bi-funnel-fill" style="color:#f97316"></i>Filtrar por estado</div>
          <a href="<?= $baseUrl ?>/assistencias<?= $busca?'?busca='.urlencode($busca):'' ?>" class="filter-btn <?= !$estado?'active':'' ?>">Todos os estados</a>
          <?php
          $estados_com = array_unique(array_column($cidades, 'uf'));
          sort($estados_com);
          foreach($estados_com as $uf): ?>
          <a href="<?= $baseUrl ?>/assistencias?estado=<?= $uf ?><?= $busca?'&busca='.urlencode($busca):'' ?>" class="filter-btn <?= $estado===$uf?'active':'' ?>"><?= $uf ?></a>
          <?php endforeach; ?>
        </div>

        <?php if($servicos): ?>
        <div class="filter-box">
          <div class="filter-title"><i class="bi bi-tools" style="color:#f97316"></i>Serviços</div>
          <?php foreach($servicos as $s): ?>
          <a href="<?= $baseUrl ?>/assistencias?servico=<?= urlencode($s['nome']) ?>" class="filter-btn <?= $serv===$s['nome']?'active':'' ?>">
            <?= htmlspecialchars($s['nome']) ?>
            <span style="color:#94a3b8;font-size:.75rem">(<?= $s['total'] ?>)</span>
          </a>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

      <!-- Grid de empresas -->
      <div class="col-lg-9">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
          <div style="color:#475569;font-size:.9rem">
            <strong style="color:#0f172a"><?= $total ?></strong> assistência<?= $total!=1?'s':'' ?> encontrada<?= $total!=1?'s':'' ?>
            <?php if($busca||$estado||$cidade||$serv): ?>
            — <a href="<?= $baseUrl ?>/assistencias" style="color:#f97316;text-decoration:none;font-size:.85rem">Limpar filtros</a>
            <?php endif; ?>
          </div>
        </div>

        <?php if($empresas): ?>
        <div class="row g-4">
          <?php foreach($empresas as $emp): ?>
          <div class="col-sm-6 col-xl-4">
            <a href="<?= $baseUrl ?>/assistencias/<?= htmlspecialchars($emp['slug']) ?>" class="dir-card" style="<?= $emp['em_destaque'] ? 'border-color:#f59e0b!important;box-shadow:0 0 0 2px #f59e0b33' : '' ?>">
              <?php if($emp['em_destaque']): ?>
              <div style="position:absolute;top:10px;right:10px;z-index:3">
                <?php if($emp['diretorio_destaque']==='premium'): ?>
                <span style="background:linear-gradient(135deg,#f59e0b,#d97706);color:#fff;font-size:.68rem;font-weight:800;padding:.25rem .7rem;border-radius:20px">⭐ PREMIUM</span>
                <?php else: ?>
                <span style="background:linear-gradient(135deg,#fbbf24,#f59e0b);color:#7c2d12;font-size:.68rem;font-weight:800;padding:.25rem .7rem;border-radius:20px">★ DESTAQUE</span>
                <?php endif; ?>
              </div>
              <?php endif; ?>
              <div class="dir-card-capa">
                <?php if($emp['foto_capa']): ?>
                <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($emp['foto_capa']) ?>" alt="<?= htmlspecialchars($emp['nome_fantasia']) ?>">
                <?php else: ?>
                <div style="width:100%;height:100%;background:linear-gradient(135deg,<?= ['#1e3a5f','#0d1f3c','#1a2744','#0b1836'][crc32($emp['nome_fantasia'])%4] ?>,#0b0d10);display:flex;align-items:center;justify-content:center">
                  <i class="bi bi-tools" style="font-size:2.5rem;color:rgba(255,255,255,.15)"></i>
                </div>
                <?php endif; ?>
                <div class="dir-card-logo">
                  <?php if($emp['logo']): ?>
                  <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($emp['logo']) ?>" alt="Logo">
                  <?php else: ?>
                  <svg viewBox="0 0 200 50" style="width:100%;height:100%" aria-label="FixaOS"><rect width="200" height="50" rx="8" fill="#1e3a5f"/><text x="100" y="34" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="30" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
                  <?php endif; ?>
                </div>
              </div>
              <div class="dir-card-body">
                <div class="dir-card-nome"><?= htmlspecialchars($emp['nome_fantasia']) ?></div>
                <?php if(empty($emp['reivindicada'])): ?>
                <div><span title="Perfil listado a partir de dados públicos (CNPJ). A empresa ainda não confirmou os dados."
                     style="display:inline-block;background:#fef3c7;color:#92400e;font-size:.62rem;font-weight:700;padding:.08rem .5rem;border-radius:20px;margin:.15rem 0">
                  <i class="bi bi-patch-question"></i> Não reivindicado
                </span></div>
                <?php endif; ?>
                <div class="dir-card-loc">
                  <i class="bi bi-geo-alt-fill" style="color:#f97316"></i>
                  <?= htmlspecialchars($emp['cidade'] ?? '') ?><?= $emp['uf'] ? '/' . $emp['uf'] : '' ?>
                </div>
                <?php if($emp['descricao_publica']): ?>
                <div class="dir-card-desc"><?= htmlspecialchars($emp['descricao_publica']) ?></div>
                <?php endif; ?>
                <div class="stars-row">
                  <?php
                  $media = round((float)$emp['media_nota'], 1);
                  $total_av = (int)$emp['total_avaliacoes'];
                  ?>
                  <span class="stars">
                    <?php for($i=1;$i<=5;$i++) echo $i<=$media?'★':'☆'; ?>
                  </span>
                  <span style="color:#0f172a;font-size:.85rem;font-weight:700"><?= $media > 0 ? number_format($media,1) : '—' ?></span>
                  <span class="stars-count">(<?= $total_av ?> avaliação<?= $total_av!=1?'s':'' ?>)</span>
                </div>
                <?php if($emp['especialidades']): ?>
                <div class="mt-2">
                  <?php foreach(array_slice(explode(',', $emp['especialidades']),0,3) as $esp): ?>
                  <span class="dir-tag"><?= htmlspecialchars(trim($esp)) ?></span>
                  <?php endforeach; ?>
                </div>
                <?php endif; ?>
              </div>
            </a>
          </div>
          <?php endforeach; ?>
        </div>

        <!-- Paginação -->
        <?php $totalPags = (int)ceil($total/$limit); if($totalPags > 1): ?>
        <div class="mt-4 d-flex justify-content-center">
          <?= paginacao_condensada($pag, $totalPags, function($p) use($baseUrl,$busca,$estado,$cidade,$serv){
                $q = array_filter(['busca'=>$busca,'estado'=>$estado,'cidade'=>$cidade,'servico'=>$serv,'pag'=>$p>1?$p:null]);
                return $baseUrl.'/assistencias'.($q?'?'.http_build_query($q):'');
              }) ?>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <div class="empty-state">
          <i class="bi bi-search" style="font-size:3rem;display:block;margin-bottom:1rem"></i>
          <h4 style="color:#475569">Nenhuma assistência encontrada</h4>
          <p style="font-size:.9rem">Tente outros termos ou <a href="<?= $baseUrl ?>/assistencias" style="color:#f97316">limpe os filtros</a>.</p>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</div>
