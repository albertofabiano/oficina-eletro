<?php
$appCfg  = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($appCfg['url'], '/');
$nome    = htmlspecialchars($empresa['nome_fantasia']);
$media   = round((float)($estatisticas['media'] ?? 0), 1);
$totalAv = (int)($estatisticas['total'] ?? 0);
$cidade  = htmlspecialchars($empresa['cidade'] ?? '');
$uf      = htmlspecialchars($empresa['uf'] ?? '');
$end     = array_filter([$empresa['logradouro'] ?? '', $empresa['numero'] ?? '', $empresa['bairro'] ?? '', "$cidade/$uf"]);
$endStr  = implode(', ', $end);
$wa      = preg_replace('/\D/', '', $empresa['whatsapp_publico'] ?? $empresa['telefone'] ?? '');
// title/description/canonical/og:* reais ficam só no <head> (montados pelo controller +
// layouts/landing.php) — esses valores aqui embaixo alimentam só o JSON-LD, que É válido
// em qualquer lugar do documento. Título/meta/canonical duplicados no <body> não contam
// pra SEO (browsers/crawlers só respeitam o que está em <head>) e só causavam conflito
// com os valores corretos — ex.: o og:image daqui nunca era lido por ninguém.
$desc = $empresa['descricao_publica'] ? htmlspecialchars(mb_substr($empresa['descricao_publica'],0,160)) : "Assistência técnica $nome em $cidade/$uf. Avaliações, contato e localização.";
$url  = "$baseUrl/assistencias/{$empresa['slug']}";
?>

<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@type": "LocalBusiness",
  "name": "<?= $nome ?>",
  "description": "<?= $desc ?>",
  "url": "<?= $url ?>",
  "telephone": "<?= htmlspecialchars($empresa['telefone'] ?? '') ?>",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "<?= htmlspecialchars(($empresa['logradouro']??'').' '.($empresa['numero']??'')) ?>",
    "addressLocality": "<?= $cidade ?>",
    "addressRegion": "<?= $uf ?>",
    "postalCode": "<?= htmlspecialchars($empresa['cep'] ?? '') ?>",
    "addressCountry": "BR"
  },
  <?php if($totalAv > 0): ?>
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "<?= $media ?>",
    "reviewCount": "<?= $totalAv ?>",
    "bestRating": "5",
    "worstRating": "1"
  },
  <?php endif; ?>
  "openingHoursSpecification": [],
  "priceRange": "$$"
}
</script>

<style>
.emp-hero{background:linear-gradient(135deg,#0b0d10,#1e3a5f);position:relative;overflow:hidden}
.emp-capa{height:220px;width:100%;object-fit:cover;opacity:.35}
.emp-capa-placeholder{min-height:170px;background:linear-gradient(135deg,#1e3a5f,#0b1a2e);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:1rem 0}
.emp-header{position:relative;z-index:2;padding:20px 0 2rem}
.emp-logo-wrap{width:80px;height:80px;border-radius:14px;background:#f8fafc;border:2px solid #e2e8f0;overflow:hidden;display:flex;align-items:center;justify-content:center;padding:6px;flex-shrink:0}
.emp-logo-wrap img{width:100%;height:100%;object-fit:contain}
.emp-logo-inicial{font-size:2.5rem;font-weight:900;color:#1e3a5f}
.stars-lg{color:#f59e0b;font-size:1.2rem;letter-spacing:2px}
.bar-wrap{background:#e2e8f0;border-radius:4px;height:8px;flex:1}
.bar-fill{height:8px;border-radius:4px;background:#f97316}
.review-card{background:#fff;border:1px solid #e2e8f0;border-radius:14px;padding:1.4rem;margin-bottom:1rem}
.review-stars{color:#f59e0b;font-size:.9rem;letter-spacing:1px;margin-bottom:.3rem}
.review-nome{color:#0f172a;font-weight:700;font-size:.9rem}
.review-data{color:#94a3b8;font-size:.78rem}
.review-text{color:#374151;font-size:.9rem;line-height:1.7;margin-top:.6rem}
.serv-badge{display:inline-flex;align-items:center;gap:.4rem;background:#f0f9ff;color:#0369a1;border:1px solid #bae6fd;border-radius:8px;padding:.4rem .8rem;font-size:.83rem;font-weight:600;margin:.25rem}
.similar-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:1rem;text-decoration:none;display:block;transition:.15s}
.similar-card:hover{border-color:#f97316;transform:translateY(-2px)}
.contact-box{background:#fff;border:2px solid #e2e8f0;border-radius:16px;padding:1.5rem;position:sticky;top:80px;margin-top:0;box-shadow:0 4px 16px rgba(0,0,0,.06)}
@media(min-width:992px){.contact-col{margin-top:45px}}
.btn-wa{background:#25d366;color:#fff;border:none;border-radius:12px;padding:.9rem 1.4rem;font-weight:700;font-size:1rem;width:100%;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;transition:.2s;margin-bottom:.7rem}
.btn-wa:hover{background:#1da852;color:#fff}
.btn-tel{background:#f1f5f9;color:#1e3a5f;border:none;border-radius:12px;padding:.75rem 1.4rem;font-weight:700;font-size:.95rem;width:100%;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:.5rem;text-decoration:none;transition:.2s;margin-bottom:.7rem}
.btn-tel:hover{background:#e2e8f0;color:#1e3a5f}
.nota-star{font-size:1.8rem;cursor:pointer;color:#d1d5db;transition:.15s;padding:0 .2rem}
.nota-star.selected,.nota-star:hover{color:#f59e0b}
</style>

<!-- Breadcrumb -->
<div style="background:#fff;border-bottom:1px solid #e2e8f0;padding:.6rem 0;font-size:.82rem">
  <div class="container">
    <a href="<?= $baseUrl ?>/assistencias" style="color:#f97316;text-decoration:none">← Diretório</a>
    <span style="color:#94a3b8;margin:0 .5rem">/</span>
    <span style="color:#64748b"><?= $cidade ?>/<?= $uf ?></span>
    <span style="color:#94a3b8;margin:0 .5rem">/</span>
    <span style="color:#0f172a;font-weight:600"><?= $nome ?></span>
  </div>
</div>

<?php
// Blocos de anúncio do diretório
$_dbAdE = \App\Core\DB::pdo();
$_adEmp = [];
foreach ($_dbAdE->query("SELECT posicao, codigo, ativo FROM master_adsense_blocos WHERE local='diretorio' ORDER BY posicao")->fetchAll() as $_b)
    $_adEmp[$_b['posicao']] = $_b;
// Só mostra a fileira de anúncios se houver ao menos 1 bloco ativo com código
// E SOMENTE em perfis não reivindicados — a página do cliente é sempre limpa,
// sem anúncios (benefício de reivindicar/assinar).
$_temAnuncio = false;
if (empty($empresa['reivindicada'])) {
    foreach ($_adEmp as $_b) { if (!empty($_b['ativo']) && !empty($_b['codigo'])) { $_temAnuncio = true; break; } }
}
?>

<!-- Hero da empresa com blocos de anúncio -->
<div class="emp-hero">
  <?php if($empresa['foto_capa']): ?>
  <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($empresa['foto_capa']) ?>" class="emp-capa" alt="<?= $nome ?>">
  <?php else: ?>
  <div class="emp-capa-placeholder" style="display:flex;flex-direction:column;align-items:center;justify-content:center;gap:.5rem">
    <div style="text-align:center;padding:1.5rem 1rem 0">
      <div style="font-size:clamp(1.8rem,5vw,3rem);font-weight:900;color:#fff;letter-spacing:-.02em;line-height:1.1;text-shadow:0 2px 16px rgba(0,0,0,.4)">
        <?= htmlspecialchars($empresa['nome_fantasia']) ?>
      </div>
      <?php if($empresa['cidade']): ?>
      <div style="color:rgba(255,255,255,.6);font-size:.95rem;margin-top:.4rem">
        <i class="bi bi-geo-alt-fill me-1" style="color:#f97316"></i><?= htmlspecialchars($empresa['cidade']) ?>/<?= htmlspecialchars($empresa['uf']) ?>
      </div>
      <?php endif; ?>
    </div>
      <?php if($_temAnuncio): ?>
      <div class="container">
        <div class="row g-3 justify-content-center align-items-center" style="padding:1rem 0 0">
          <?php for($i=1;$i<=5;$i++):
            $bloco = $_adEmp[$i] ?? null;
            $ativo = $bloco && !empty($bloco['ativo']);
            $codigo = $bloco['codigo'] ?? '';
            if(!($ativo && $codigo)) continue;
          ?>
          <div class="col-12 col-sm-6 col-lg text-center"><?= $codigo ?></div>
          <?php endfor; ?>
        </div>
      </div>
      <?php endif; ?>
  </div>
  <?php endif; ?>
</div>

<div style="background:#f8fafc;padding-bottom:4rem">
<div class="container">
<div class="row g-4 align-items-start">

  <!-- Coluna principal -->
  <div class="col-lg-8">

    <!-- Header da empresa -->
    <div class="emp-header">
      <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.4rem 1.6rem;display:flex;align-items:center;gap:1.2rem;margin-bottom:1.2rem">
        <!-- Logo -->
        <div class="emp-logo-wrap" style="margin-top:0;flex-shrink:0">
          <?php if($empresa['logo']): ?>
          <img src="<?= $baseUrl ?>/uploads/<?= htmlspecialchars($empresa['logo']) ?>" alt="Logo <?= $nome ?>">
          <?php else: ?>
          <svg viewBox="0 0 200 50" style="width:100%;height:100%" aria-label="FixaOS"><rect width="200" height="50" rx="8" fill="#1e3a5f"/><text x="100" y="34" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="30" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
          <?php endif; ?>
        </div>
        <!-- Info -->
        <div style="flex:1;min-width:0">
          <h1 style="color:#0f172a;font-size:1.5rem;font-weight:900;margin-bottom:.2rem;line-height:1.2"><?= $nome ?></h1>
          <div style="color:#64748b;font-size:.88rem;display:flex;align-items:center;gap:.4rem;margin-bottom:.3rem">
            <i class="bi bi-geo-alt-fill" style="color:#f97316"></i>
            <?= $cidade ?>/<?= $uf ?>
          </div>
          <?php if($totalAv > 0): ?>
          <div class="d-flex align-items-center gap-2">
            <span class="stars-lg" style="font-size:1rem"><?php for($i=1;$i<=5;$i++) echo $i<=$media?'★':'☆'; ?></span>
            <strong style="color:#0f172a;font-size:1rem"><?= number_format($media,1) ?></strong>
            <span style="color:#64748b;font-size:.8rem">(<?= $totalAv ?> avaliação<?= $totalAv!=1?'s':'' ?>)</span>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Reivindicar (perfil não reivindicado) -->
    <?php if(!empty($_GET['reivindicado'])): ?>
    <div style="background:#ecfdf5;border:1px solid #6ee7b7;border-radius:16px;padding:1.2rem 1.5rem;margin-bottom:1.5rem;color:#065f46">
      <div style="font-weight:800;font-size:1.02rem;margin-bottom:.15rem"><i class="bi bi-check-circle-fill me-1"></i>Pedido recebido!</div>
      <div style="font-size:.88rem">Vamos confirmar seus dados e liberar seu acesso ao FixaOS em breve. Você receberá um retorno no e-mail/WhatsApp informado.</div>
    </div>
    <?php elseif(empty($empresa['reivindicada'])): ?>
    <div style="background:linear-gradient(135deg,#fff7ed,#ffedd5);border:1px solid #fdba74;border-radius:16px;padding:1.3rem 1.5rem;margin-bottom:1.5rem;display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap">
      <div style="flex:1;min-width:220px">
        <div style="font-weight:800;color:#9a3412;font-size:1.05rem;margin-bottom:.2rem"><i class="bi bi-patch-check-fill me-1"></i>É a sua empresa?</div>
        <div style="color:#7c2d12;font-size:.88rem;line-height:1.5">Este perfil foi criado a partir de dados públicos (CNPJ) e <strong>pode conter informações erradas ou desatualizadas</strong>. <strong>Reivindique grátis</strong> para corrigir os dados, adicionar logo e fotos, <strong>acompanhar quantas pessoas visitam seu perfil</strong> e responder avaliações — 7 dias grátis, sem cartão.</div>
        <div style="margin-top:.6rem;display:flex;flex-wrap:wrap;gap:.4rem .9rem;font-size:.8rem;color:#9a3412;font-weight:600">
          <span><i class="bi bi-pencil-fill me-1" style="color:#ea580c"></i>Corrija informações erradas</span>
          <span><i class="bi bi-eye-fill me-1" style="color:#ea580c"></i>Acompanhe as visitas do perfil</span>
          <span><i class="bi bi-check-circle-fill me-1" style="color:#16a34a"></i>Sua logo e fotos</span>
          <span><i class="bi bi-check-circle-fill me-1" style="color:#16a34a"></i>Página limpa, sem anúncios</span>
        </div>
      </div>
      <button type="button" onclick="document.getElementById('modalReivindicar').style.display='flex'"
              style="background:#f97316;color:#fff;border:none;border-radius:10px;padding:.75rem 1.4rem;font-weight:800;font-size:.9rem;white-space:nowrap;cursor:pointer">
        <i class="bi bi-hand-index-thumb me-1"></i>Reivindicar este perfil
      </button>
    </div>
    <?php endif; ?>

    <!-- Sobre -->
    <?php if($empresa['descricao_publica']): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.6rem;margin-bottom:1.5rem">
      <h2 style="color:#0f172a;font-size:1rem;font-weight:700;margin-bottom:.8rem"><i class="bi bi-info-circle-fill me-2" style="color:#f97316"></i>Sobre a empresa</h2>
      <p style="color:#374151;font-size:.93rem;line-height:1.8;margin:0"><?= nl2br(htmlspecialchars($empresa['descricao_publica'])) ?></p>
    </div>
    <?php endif; ?>

    <?php
      $horarioRaw = $empresa['horario_funcionamento'] ?? '';
      $horarioDias = null;
      if ($horarioRaw !== '') {
        $tentativa = json_decode($horarioRaw, true);
        if (is_array($tentativa)) $horarioDias = $tentativa;
      }
      $diasSemanaPub = ['seg'=>'Segunda','ter'=>'Terça','qua'=>'Quarta','qui'=>'Quinta','sex'=>'Sexta','sab'=>'Sábado','dom'=>'Domingo'];
      $hojeChave = ['mon'=>'seg','tue'=>'ter','wed'=>'qua','thu'=>'qui','fri'=>'sex','sat'=>'sab','sun'=>'dom'][strtolower(date('D'))] ?? null;
    ?>

    <!-- Galeria de fotos (diferencial do perfil reivindicado) -->
    <?php if(!empty($fotos)): ?>
    <style>
    .galeria-emp .g-main{display:block;border-radius:12px;overflow:hidden;aspect-ratio:16/9;cursor:pointer}
    .galeria-emp .g-main img{width:100%;height:100%;object-fit:cover;transition:.3s}
    .galeria-emp .g-main:hover img{transform:scale(1.04)}
    .g-carousel{display:flex;gap:.6rem;overflow-x:auto;scroll-snap-type:x proximity;padding-bottom:.3rem;margin-top:.6rem;-webkit-overflow-scrolling:touch}
    .g-carousel::-webkit-scrollbar{height:6px}
    .g-carousel::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:6px}
    .g-thumb{flex:1 1 0;min-width:80px;height:96px;border-radius:10px;overflow:hidden;cursor:pointer;scroll-snap-align:start;border:2px solid transparent;transition:border-color .15s}
    .g-thumb.ativa{border-color:#f97316}
    .g-thumb img{width:100%;height:100%;object-fit:cover;transition:.3s}
    .g-thumb:hover img{transform:scale(1.06)}
    .lightbox{display:none;position:fixed;inset:0;background:rgba(8,12,20,.93);z-index:3000;align-items:center;justify-content:center}
    .lightbox.aberto{display:flex}
    .lightbox img{max-width:92vw;max-height:86vh;border-radius:8px;box-shadow:0 20px 60px rgba(0,0,0,.5)}
    .lb-close{position:absolute;top:16px;right:22px;background:none;border:none;color:#fff;font-size:2.4rem;line-height:1;cursor:pointer;opacity:.85}
    .lb-close:hover{opacity:1}
    .lb-nav{position:absolute;top:50%;transform:translateY(-50%);background:rgba(255,255,255,.12);border:none;color:#fff;font-size:1.5rem;width:48px;height:48px;border-radius:50%;cursor:pointer}
    .lb-nav:hover{background:rgba(255,255,255,.25)}
    .lb-prev{left:18px}.lb-next{right:18px}
    .lb-count{position:absolute;bottom:18px;left:0;right:0;text-align:center;color:#fff;font-size:.9rem;opacity:.85}
    </style>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.6rem;margin-bottom:1.5rem">
      <h2 style="color:#0f172a;font-size:1rem;font-weight:700;margin-bottom:1rem"><i class="bi bi-images me-2" style="color:#0d9488"></i>Fotos</h2>
      <div class="galeria-emp">
        <div class="g-main" onclick="abrirLightbox(gMainIdx)">
          <img id="gMainImg" src="<?= $baseUrl ?>/uploads/fotos/<?= htmlspecialchars($fotos[0]['arquivo']) ?>" alt="Foto de <?= $nome ?>" loading="lazy">
        </div>
        <?php if(count($fotos) > 1): ?>
        <div class="g-carousel">
          <?php foreach($fotos as $idx => $ft): ?>
          <div class="g-thumb <?= $idx===0?'ativa':'' ?>" data-idx="<?= $idx ?>" onclick="selecionarFoto(<?= $idx ?>)">
            <img src="<?= $baseUrl ?>/uploads/fotos/<?= htmlspecialchars($ft['arquivo']) ?>" alt="Foto <?= $idx+1 ?> de <?= $nome ?>" loading="lazy">
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div id="lightbox" class="lightbox" onclick="fecharLightbox(event)">
      <button class="lb-close" onclick="fecharLightbox(event,true)" aria-label="Fechar">&times;</button>
      <?php if(count($fotos) > 1): ?>
      <button class="lb-nav lb-prev" onclick="navLightbox(event,-1)" aria-label="Anterior">&#10094;</button>
      <button class="lb-nav lb-next" onclick="navLightbox(event,1)" aria-label="Próxima">&#10095;</button>
      <?php endif; ?>
      <img id="lbImg" src="" alt="Foto ampliada">
      <div id="lbCount" class="lb-count"></div>
    </div>
    <script>
    var LB_FOTOS = <?= json_encode(array_map(fn($f) => $baseUrl.'/uploads/fotos/'.$f['arquivo'], $fotos)) ?>;
    var lbIdx = 0;
    var gMainIdx = 0;
    function selecionarFoto(i){
      gMainIdx = i;
      document.getElementById('gMainImg').src = LB_FOTOS[i];
      document.querySelectorAll('.g-thumb').forEach(function(el){ el.classList.toggle('ativa', +el.dataset.idx === i); });
    }
    function abrirLightbox(i){ lbIdx=i; document.getElementById('lbImg').src=LB_FOTOS[i]; document.getElementById('lbCount').textContent=(i+1)+' / '+LB_FOTOS.length; document.getElementById('lightbox').classList.add('aberto'); document.body.style.overflow='hidden'; }
    function fecharLightbox(e,forca){ if(forca || e.target.id==='lightbox'){ document.getElementById('lightbox').classList.remove('aberto'); document.body.style.overflow=''; } }
    function navLightbox(e,d){ e.stopPropagation(); lbIdx=(lbIdx+d+LB_FOTOS.length)%LB_FOTOS.length; abrirLightbox(lbIdx); }
    document.addEventListener('keydown', function(e){ var lb=document.getElementById('lightbox'); if(!lb||!lb.classList.contains('aberto'))return; if(e.key==='Escape')fecharLightbox(e,true); if(e.key==='ArrowRight')navLightbox(e,1); if(e.key==='ArrowLeft')navLightbox(e,-1); });
    </script>
    <?php endif; ?>

    <!-- Serviços -->
    <?php if($servicos): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.6rem;margin-bottom:1.5rem">
      <h2 style="color:#0f172a;font-size:1rem;font-weight:700;margin-bottom:1rem"><i class="bi bi-tools me-2" style="color:#f97316"></i>Serviços oferecidos</h2>
      <div>
        <?php foreach($servicos as $s): ?>
        <span class="serv-badge"><i class="bi <?= htmlspecialchars($s['icone']) ?>"></i><?= htmlspecialchars($s['nome']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Mapa -->
    <?php if($endStr): ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;overflow:hidden;margin-bottom:1.5rem">
      <div style="padding:1.2rem 1.6rem;border-bottom:1px solid #f1f5f9">
        <h2 style="color:#0f172a;font-size:1rem;font-weight:700;margin:0"><i class="bi bi-map-fill me-2" style="color:#f97316"></i>Localização</h2>
        <div style="color:#64748b;font-size:.85rem;margin-top:.3rem"><?= htmlspecialchars($endStr) ?></div>
      </div>
      <div id="mapaEmp" style="width:100%;height:300px;background:#eef2f7"></div>
      <script>
      (function(){
        const el = document.getElementById('mapaEmp');
        if(!el) return;
        const nome = <?= json_encode($empresa['nome_fantasia'] ?? '') ?>;
        const endereco = <?= json_encode($endStr) ?>;
        const logo = <?= json_encode($empresa['logo'] ? $baseUrl.'/uploads/'.$empresa['logo'] : null) ?>;
        const fLat = <?= $empresa['latitude']  !== null ? (float)$empresa['latitude']  : 'null' ?>;
        const fLng = <?= $empresa['longitude'] !== null ? (float)$empresa['longitude'] : 'null' ?>;

        // Carrega o Leaflet (CSS+JS ~200KB) só quando o mapa chega perto da tela.
        let carregado = false;
        function carregar(){
          if(carregado) return; carregado = true;
          const css = document.createElement('link');
          css.rel = 'stylesheet';
          css.href = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css';
          document.head.appendChild(css);
          const js = document.createElement('script');
          js.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js';
          js.onload = iniciar;
          document.body.appendChild(js);
        }
        function iniciar(){
          if(typeof L === 'undefined') return;
          const inner = logo
            ? `<img src="${logo}" style="width:100%;height:100%;object-fit:contain;padding:3px">`
            : `<svg viewBox="0 0 200 50" style="width:100%;height:100%"><rect width="200" height="50" rx="8" fill="#1e3a5f"/><text x="100" y="34" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="30" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>`;
          const icon = L.divIcon({
            html: `<div style="width:48px;height:48px;border-radius:50%;background:#fff;border:3px solid #f97316;box-shadow:0 4px 14px rgba(0,0,0,.3);overflow:hidden;display:flex;align-items:center;justify-content:center">${inner}</div>`,
            className:'', iconSize:[48,48], iconAnchor:[24,24], popupAnchor:[0,-24]
          });
          function render(lat, lng, zoom){
            const map = L.map('mapaEmp', {scrollWheelZoom:false}).setView([lat,lng], zoom);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap'}).addTo(map);
            L.marker([lat,lng], {icon}).addTo(map).bindPopup(`<b>${nome}</b><br><span style="color:#64748b;font-size:.8rem">${endereco}</span>`).openPopup();
            setTimeout(()=>map.invalidateSize(), 200);
          }
          function usarFallback(){ if(fLat && fLng) render(fLat, fLng, 14); }
          fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q=' + encodeURIComponent(endereco))
            .then(r => r.json())
            .then(d => {
              if(!d || !d[0]) { usarFallback(); return; }
              const gLat = parseFloat(d[0].lat), gLng = parseFloat(d[0].lon);
              if(fLat && fLng && (Math.abs(gLat - fLat) > 0.15 || Math.abs(gLng - fLng) > 0.15)) usarFallback();
              else render(gLat, gLng, 16);
            })
            .catch(usarFallback);
        }
        if('IntersectionObserver' in window){
          const obs = new IntersectionObserver(function(entries){
            if(entries.some(e => e.isIntersecting)){ carregar(); obs.disconnect(); }
          }, {rootMargin:'400px'});
          obs.observe(el);
        } else {
          carregar();
        }
      })();
      </script>
      <div style="padding:.8rem 1.6rem">
        <a href="https://maps.google.com/?q=<?= urlencode($endStr) ?>" target="_blank" style="color:#f97316;font-size:.85rem;font-weight:600;text-decoration:none">
          <i class="bi bi-box-arrow-up-right me-1"></i>Abrir no Google Maps
        </a>
      </div>
    </div>
    <?php endif; ?>

    <!-- Avaliações (liga/desliga em Empresa → Perfil Público) -->
    <?php if ($avaliacoesAtivas): ?>
    <div id="avaliacoes" style="background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:1.6rem;margin-bottom:1.5rem">
      <h2 style="color:#0f172a;font-size:1rem;font-weight:700;margin-bottom:1.2rem">
        <i class="bi bi-star-fill me-2" style="color:#f97316"></i>Avaliações (<?= $totalAv ?>)
      </h2>

      <?php if($totalAv > 0): ?>
      <!-- Resumo -->
      <div class="d-flex gap-4 align-items-center mb-3 p-3" style="background:#f8fafc;border-radius:12px">
        <div class="text-center" style="min-width:70px">
          <div style="font-size:3rem;font-weight:900;color:#0f172a;line-height:1"><?= number_format($media,1) ?></div>
          <div style="color:#f59e0b;font-size:1rem;letter-spacing:2px"><?php for($i=1;$i<=5;$i++) echo $i<=$media?'★':'☆'; ?></div>
          <div style="color:#64748b;font-size:.75rem"><?= $totalAv ?> avaliação<?= $totalAv!=1?'s':'' ?></div>
        </div>
        <div style="flex:1">
          <?php foreach([5,4,3,2,1] as $n):
            $cnt = (int)($estatisticas['c'.$n] ?? 0);
            $pct = $totalAv > 0 ? round($cnt/$totalAv*100) : 0;
          ?>
          <div class="d-flex align-items-center gap-2 mb-1">
            <span style="color:#64748b;font-size:.78rem;width:12px"><?= $n ?></span>
            <span style="color:#f59e0b;font-size:.7rem">★</span>
            <div class="bar-wrap"><div class="bar-fill" style="width:<?= $pct ?>%"></div></div>
            <span style="color:#94a3b8;font-size:.75rem;width:28px"><?= $cnt ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Lista de avaliações -->
      <?php foreach($avaliacoes as $av): ?>
      <div class="review-card">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <div class="review-stars"><?php for($i=1;$i<=5;$i++) echo $i<=$av['nota']?'★':'☆'; ?></div>
            <div class="review-nome">
              <?= htmlspecialchars($av['nome']) ?>
              <?php if(!empty($av['verificada'])): ?>
              <span title="Cliente atendido — avaliação verificada por Ordem de Serviço real" style="background:#dcfce7;color:#166534;font-size:.66rem;font-weight:800;padding:.12rem .45rem;border-radius:20px;margin-left:.35rem;white-space:nowrap;vertical-align:middle"><i class="bi bi-patch-check-fill"></i> VERIFICADA</span>
              <?php endif; ?>
            </div>
          </div>
          <div class="review-data"><?= date('d/m/Y', strtotime($av['criado_em'])) ?></div>
        </div>
        <?php if($av['comentario']): ?>
        <div class="review-text"><?= htmlspecialchars($av['comentario']) ?></div>
        <?php endif; ?>
        <?php if(!empty($av['resposta'])): ?>
        <div style="background:#f0fdfa;border-left:3px solid #0d9488;border-radius:8px;padding:.6rem .85rem;margin-top:.6rem">
          <div style="color:#0f766e;font-weight:700;font-size:.78rem;margin-bottom:.15rem"><i class="bi bi-reply-fill"></i> Resposta da empresa</div>
          <div style="color:#374151;font-size:.88rem"><?= nl2br(htmlspecialchars($av['resposta'])) ?></div>
        </div>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>

      <!-- Formulário de avaliação -->
      <div style="border-top:1px solid #f1f5f9;padding-top:1.4rem;margin-top:1.4rem">
        <h3 style="color:#0f172a;font-size:.95rem;font-weight:700;margin-bottom:1rem">Deixe sua avaliação</h3>

        <?php $ok=flash('success');$err=flash('error');
        if($ok): ?><div class="alert alert-success py-2 small"><?= htmlspecialchars($ok) ?></div><?php endif;
        if($err): ?><div class="alert alert-danger py-2 small"><?= htmlspecialchars($err) ?></div><?php endif; ?>

        <?php
        // Gerar CAPTCHA matemático na sessão
        if (empty($_SESSION['captcha_a']) || empty($_SESSION['captcha_b'])) {
            $_SESSION['captcha_a'] = rand(1, 12);
            $_SESSION['captcha_b'] = rand(1, 12);
        }
        $ca = $_SESSION['captcha_a'];
        $cb = $_SESSION['captcha_b'];
        ?>
        <form method="POST" action="<?= $baseUrl ?>/assistencias/<?= htmlspecialchars($empresa['slug']) ?>/avaliar">
          <?= csrf_field() ?>

          <!-- Nota -->
          <div class="mb-3">
            <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Sua nota *</label>
            <div id="starRating">
              <?php for($i=1;$i<=5;$i++): ?>
              <span class="nota-star" data-val="<?= $i ?>" onclick="setNota(<?= $i ?>)" role="button" tabindex="0" aria-label="<?= $i ?> estrela<?= $i > 1 ? 's' : '' ?>">★</span>
              <?php endfor; ?>
            </div>
            <input type="hidden" name="nota" id="notaInput" value="">
          </div>

          <!-- Nome e e-mail -->
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Seu nome *</label>
              <input type="text" name="nome" class="form-control" placeholder="João da Silva" required>
            </div>
            <div class="col-md-6">
              <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">E-mail (opcional)</label>
              <input type="email" name="email" class="form-control" placeholder="seu@email.com">
            </div>
          </div>

          <!-- Comentário -->
          <div class="mb-3">
            <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Comentário</label>
            <textarea name="comentario" class="form-control" rows="3" placeholder="Conte sua experiência com essa assistência técnica..."></textarea>
          </div>

          <!-- CAPTCHA matemático -->
          <div class="mb-3">
            <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">
              <i class="bi bi-shield-check me-1" style="color:#f97316"></i>Verificação anti-robô *
            </label>
            <div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap">
              <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:.7rem 1.2rem;font-size:1.1rem;font-weight:800;color:#0f172a;letter-spacing:.05em;user-select:none;font-family:monospace">
                <?= $ca ?> + <?= $cb ?> = ?
              </div>
              <input type="number" name="captcha" class="form-control" placeholder="Resultado"
                     style="max-width:120px" required autocomplete="off">
            </div>
            <div style="color:#94a3b8;font-size:.78rem;margin-top:.4rem">Resolva a conta acima para confirmar que você não é um robô.</div>
          </div>

          <button type="submit" style="background:#f97316;color:#fff;border:none;border-radius:10px;padding:.7rem 1.8rem;font-weight:700;cursor:pointer">
            <i class="bi bi-send-fill me-1"></i>Enviar avaliação
          </button>
        </form>
      </div>
    </div>
    <?php endif; ?>

    <!-- Similares -->
    <?php if($similares): ?>
    <div style="margin-top:1rem">
      <h2 style="color:#0f172a;font-size:1rem;font-weight:700;margin-bottom:1rem"><i class="bi bi-shop me-2" style="color:#f97316"></i>Outras assistências em <?= $cidade ?></h2>
      <div class="row g-3">
        <?php foreach($similares as $s): ?>
        <div class="col-sm-6">
          <a href="<?= $baseUrl ?>/assistencias/<?= htmlspecialchars($s['slug']) ?>" class="similar-card">
            <div style="font-weight:700;color:#0f172a;font-size:.9rem;margin-bottom:.3rem"><?= htmlspecialchars($s['nome_fantasia']) ?></div>
            <div style="color:#64748b;font-size:.78rem"><i class="bi bi-geo-alt-fill" style="color:#f97316"></i> <?= htmlspecialchars($s['cidade']??'') ?>/<?= htmlspecialchars($s['uf']??'') ?></div>
            <?php if($s['media_nota']>0): ?>
            <div style="color:#f59e0b;font-size:.8rem;margin-top:.3rem"><?php for($i=1;$i<=5;$i++) echo $i<=$s['media_nota']?'★':'☆'; ?> <?= number_format($s['media_nota'],1) ?></div>
            <?php endif; ?>
          </a>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <!-- Coluna lateral — Contato -->
  <div class="col-lg-4 contact-col">
    <div class="contact-box" style="margin-top:0">
      <h3 style="color:#0f172a;font-weight:800;font-size:1rem;margin-bottom:1.2rem">Entre em contato</h3>

      <?php if($wa): ?>
      <a href="https://wa.me/55<?= $wa ?>?text=<?= urlencode("Olá! Vi sua assistência $nome no FixaOS e gostaria de mais informações.") ?>" target="_blank" class="btn-wa">
        <i class="bi bi-whatsapp fs-5"></i> Chamar no WhatsApp
      </a>
      <?php endif; ?>

      <?php if($empresa['telefone']): ?>
      <a href="tel:+55<?= preg_replace('/\D/','',$empresa['telefone']) ?>" class="btn-tel">
        <i class="bi bi-telephone-fill"></i> <?= htmlspecialchars($empresa['telefone']) ?>
      </a>
      <?php endif; ?>

      <?php $emailContato = $empresa['email_publico'] ?: $empresa['email']; ?>
      <?php if($emailContato): ?>
      <a href="mailto:<?= htmlspecialchars($emailContato) ?>" class="btn-tel">
        <i class="bi bi-envelope-fill"></i> Enviar e-mail
      </a>
      <?php endif; ?>

      <?php if($empresa['site_url']): ?>
      <a href="<?= htmlspecialchars($empresa['site_url']) ?>" target="_blank" class="btn-tel">
        <i class="bi bi-globe2"></i> Visitar site
      </a>
      <?php endif; ?>

      <?php if($empresa['instagram']): ?>
      <a href="https://instagram.com/<?= htmlspecialchars(ltrim($empresa['instagram'],'@')) ?>" target="_blank" rel="nofollow noopener" class="btn-tel">
        <i class="bi bi-instagram"></i> @<?= htmlspecialchars(ltrim($empresa['instagram'],'@')) ?>
      </a>
      <?php endif; ?>

      <?php if(!empty($empresa['facebook'])): $fb=$empresa['facebook']; $fbUrl=preg_match('~^https?://~',$fb)?$fb:'https://facebook.com/'.ltrim($fb,'@/'); ?>
      <a href="<?= htmlspecialchars($fbUrl) ?>" target="_blank" rel="nofollow noopener" class="btn-tel">
        <i class="bi bi-facebook"></i> Facebook
      </a>
      <?php endif; ?>

      <?php if(!empty($empresa['youtube'])): $yt=$empresa['youtube']; $ytUrl=preg_match('~^https?://~',$yt)?$yt:'https://youtube.com/'.ltrim($yt,'/'); ?>
      <a href="<?= htmlspecialchars($ytUrl) ?>" target="_blank" rel="nofollow noopener" class="btn-tel">
        <i class="bi bi-youtube"></i> YouTube
      </a>
      <?php endif; ?>

      <?php if(!empty($empresa['tiktok'])): ?>
      <a href="https://tiktok.com/@<?= htmlspecialchars(ltrim($empresa['tiktok'],'@')) ?>" target="_blank" rel="nofollow noopener" class="btn-tel">
        <i class="bi bi-tiktok"></i> @<?= htmlspecialchars(ltrim($empresa['tiktok'],'@')) ?>
      </a>
      <?php endif; ?>

      <?php if($endStr): ?>
      <div style="border-top:3px solid #fdba74;margin-top:1.1rem;padding-top:1rem">
        <div style="color:#64748b;font-size:.83rem">
          <i class="bi bi-geo-alt-fill me-2" style="color:#f97316"></i><?= htmlspecialchars($endStr) ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if(!empty($empresa['especialidades'])): ?>
      <div style="border-top:3px solid #fdba74;margin-top:1.1rem;padding-top:1rem">
        <div style="color:#94a3b8;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem">Especialidades</div>
        <div>
          <?php foreach(array_filter(array_map('trim', explode(',', $empresa['especialidades']))) as $esp): ?>
          <span style="display:inline-block;background:#f97316;color:#fff;border-radius:20px;font-size:.75rem;font-weight:600;padding:.3rem .75rem;margin:0 .3rem .4rem 0"><?= htmlspecialchars(mb_strtolower($esp)) ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if($horarioDias !== null): ?>
      <div style="border-top:3px solid #fdba74;margin-top:1.1rem;padding-top:1rem">
        <div style="color:#94a3b8;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem"><i class="bi bi-clock-fill me-1" style="color:#f97316"></i>Horário de funcionamento</div>
        <div>
          <?php foreach($diasSemanaPub as $dk => $dLabel): $d = $horarioDias[$dk] ?? null; $ehHoje = $dk === $hojeChave; ?>
          <div style="display:flex;justify-content:space-between;padding:.3rem 0;<?= $ehHoje ? 'font-weight:800;color:#0f172a' : 'color:#374151' ?>;font-size:.83rem;<?= $dk!=='dom'?'border-bottom:1px solid #f8fafc':'' ?>">
            <span><?= $ehHoje ? '● ' : '' ?><?= $dLabel ?></span>
            <?php if(!empty($d['aberto'])): ?>
            <span><?= htmlspecialchars($d['abre'] ?? '') ?> às <?= htmlspecialchars($d['fecha'] ?? '') ?></span>
            <?php else: ?>
            <span style="color:#94a3b8">Fechado</span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php elseif($horarioRaw !== ''): ?>
      <div style="border-top:3px solid #fdba74;margin-top:1.1rem;padding-top:1rem">
        <div style="color:#94a3b8;font-size:.75rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem"><i class="bi bi-clock-fill me-1" style="color:#f97316"></i>Horário de funcionamento</div>
        <p style="color:#374151;font-size:.85rem;line-height:1.7;margin:0"><?= nl2br(htmlspecialchars($horarioRaw)) ?></p>
      </div>
      <?php endif; ?>

      <?php if(!empty($empresa['reivindicada']) && (int)($empresa['visitas'] ?? 0) > 0): ?>
      <div style="border-top:3px solid #fdba74;margin-top:1.1rem;padding-top:1rem">
        <div style="display:flex;align-items:center;gap:.75rem;background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:.7rem .85rem">
          <div style="width:40px;height:40px;border-radius:11px;background:#f97316;display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <i class="bi bi-eye-fill" style="color:#fff;font-size:1.15rem"></i>
          </div>
          <div style="line-height:1.05">
            <div style="font-size:1.55rem;font-weight:800;color:#9a3412"><?= number_format((int)$empresa['visitas'],0,',','.') ?></div>
            <div style="font-size:.7rem;color:#c2410c;font-weight:700;text-transform:uppercase;letter-spacing:.04em">visualizaç<?= (int)$empresa['visitas']==1?'ão':'ões' ?> no perfil</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <div style="border-top:3px solid #fdba74;margin-top:1.1rem;padding-top:1rem;text-align:center">
        <div style="color:#94a3b8;font-size:.75rem">Empresa verificada pelo</div>
        <svg width="80" height="20" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg" style="margin-top:4px"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
      </div>
    </div>

    <?php if($anuncio): ?>
    <div class="contact-box" style="margin-top:1rem;padding:.9rem">
      <div style="color:#94a3b8;font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.04em;margin-bottom:.5rem">Publicidade</div>
      <a href="<?= htmlspecialchars($anuncio['link_url'] ?: '#') ?>" target="_blank" rel="nofollow sponsored noopener" style="display:block">
        <img src="<?= htmlspecialchars(url('/uploads/' . $anuncio['imagem'])) ?>" alt="<?= htmlspecialchars($anuncio['titulo'] ?: 'Anúncio') ?>" style="width:100%;border-radius:10px;display:block">
      </a>
    </div>
    <?php endif; ?>
  </div>

</div>
</div>
</div>

<?php if(empty($empresa['reivindicada'])): ?>
<!-- Modal Reivindicar -->
<div id="modalReivindicar" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:3000;align-items:center;justify-content:center;padding:1rem">
  <div style="background:#fff;border-radius:16px;max-width:440px;width:100%;padding:1.6rem;max-height:92vh;overflow:auto">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.3rem">
      <h3 style="color:#0f172a;font-size:1.15rem;font-weight:800;margin:0;line-height:1.2">Reivindicar<br><?= $nome ?></h3>
      <button type="button" aria-label="Fechar" onclick="document.getElementById('modalReivindicar').style.display='none'" style="background:none;border:none;font-size:1.6rem;color:#94a3b8;cursor:pointer;line-height:1">&times;</button>
    </div>
    <p style="color:#64748b;font-size:.85rem;margin:.4rem 0 1rem">Confirme que você é o responsável. Vamos revisar e liberar seu acesso ao FixaOS — 7 dias grátis, sem cartão.</p>
    <p style="color:#c2410c;font-size:.78rem;margin:0 0 1rem;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px;padding:.5rem .7rem"><i class="bi bi-stars me-1"></i>Depois de reivindicar, já dá pra editar logo, descrição e horário grátis. Assinando um plano da FixaOS, sua empresa libera edição completa do perfil (cidade, redes sociais, serviços e visitas).</p>
    <form method="POST" action="<?= url('/reivindicar/'.$empresa['id']) ?>">
      <?= csrf_field() ?>
      <?php $lbl='display:block;font-size:.8rem;font-weight:700;color:#374151;margin-bottom:.2rem';
            $inp='width:100%;padding:.6rem;border:1px solid #cbd5e1;border-radius:8px;margin-bottom:.8rem;font-size:.9rem'; ?>
      <label style="<?=$lbl?>">Seu nome *</label>
      <input name="nome" required maxlength="100" style="<?=$inp?>">
      <label style="<?=$lbl?>">E-mail *</label>
      <input name="email" type="email" required maxlength="100" style="<?=$inp?>">
      <label style="<?=$lbl?>">WhatsApp *</label>
      <input name="whatsapp" required maxlength="20" placeholder="(11) 99999-9999" style="<?=$inp?>">
      <label style="<?=$lbl?>">CNPJ da empresa *</label>
      <input name="cnpj" required maxlength="18" inputmode="numeric" placeholder="00.000.000/0000-00" style="<?=$inp?>">
      <p style="font-size:.75rem;color:#94a3b8;margin:-.5rem 0 .8rem;display:flex;gap:.35rem;align-items:flex-start"><span>🔒</span><span>Usamos o CNPJ só para confirmar que a empresa é sua. Se conferir com o cadastro público, o acesso é liberado na hora; senão, passa por uma verificação rápida da nossa equipe.</span></p>
      <label style="<?=$lbl?>">Crie uma senha de acesso *</label>
      <input name="senha" type="password" required minlength="6" style="<?=$inp?>">
      <label style="display:flex;gap:.5rem;font-size:.82rem;color:#374151;margin-bottom:1rem;align-items:flex-start">
        <input type="checkbox" required style="margin-top:.2rem"> Confirmo que sou responsável por esta empresa e que os dados são verdadeiros.
      </label>
      <button type="submit" style="width:100%;background:#f97316;color:#fff;border:none;border-radius:10px;padding:.8rem;font-weight:800;font-size:.95rem;cursor:pointer">Enviar pedido de reivindicação</button>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
function setNota(n) {
  document.getElementById('notaInput').value = n;
  document.querySelectorAll('.nota-star').forEach((s,i) => {
    s.classList.toggle('selected', i < n);
  });
}
</script>
