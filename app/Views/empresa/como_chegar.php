<?php
/* Mapa "Como chegar" na empresa -- pensado pra técnico/motorista de campo (ou o próprio cliente)
   descobrir rápido o caminho e compartilhar por WhatsApp, sem precisar digitar endereço nem
   procurar no mapa por conta própria. Rota livre (ver routes/web.php) -- qualquer usuário logado
   acessa, mesmo sem permissão de Configurações. */
$temEndereco = trim((string) $endereco) !== '';
$temCoords   = $empresa['latitude'] !== null && $empresa['longitude'] !== null;
$lat = $temCoords ? (float) $empresa['latitude']  : null;
$lng = $temCoords ? (float) $empresa['longitude'] : null;
$nomeEmp = $empresa['nome_fantasia'] ?? 'Assistência';

$googleUrl = $temCoords
    ? 'https://www.google.com/maps/dir/?api=1&destination=' . $lat . ',' . $lng
    : 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($endereco);
$wazeUrl = $temCoords
    ? 'https://waze.com/ul?ll=' . $lat . ',' . $lng . '&navigate=yes'
    : 'https://waze.com/ul?q=' . urlencode($endereco) . '&navigate=yes';

$msgCompartilhar = "📍 Como chegar até a {$nomeEmp}:\n{$endereco}\n\n"
    . "Google Maps: {$googleUrl}\nWaze: {$wazeUrl}";
?>
<div class="container-fluid py-3">
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-signpost-2-fill fs-4 text-primary"></i>
    <h1 class="h4 fw-bold mb-0">Como chegar</h1>
  </div>

  <?php if (!$temEndereco): ?>
  <div class="alert alert-warning d-flex align-items-start gap-2">
    <i class="bi bi-exclamation-triangle-fill fs-5"></i>
    <div>
      Sua empresa ainda não tem endereço cadastrado.
      <a href="<?= url('/empresa') ?>" class="fw-semibold">Cadastre em Configurações → Empresa</a>
      pra liberar o mapa e os links de "como chegar".
    </div>
  </div>
  <?php else: ?>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
      <div>
        <div class="fw-bold"><?= e($nomeEmp) ?></div>
        <div class="text-muted small"><?= e($endereco) ?></div>
      </div>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopiarEndereco">
        <i class="bi bi-clipboard me-1"></i>Copiar endereço
      </button>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3 overflow-hidden">
    <div id="mapaComoChegar" style="width:100%;height:340px;background:#eef2f7"></div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body">
      <div class="fw-semibold mb-2">Enviar pra quem vai até aí</div>
      <p class="text-muted small mb-3">Cliente, motorista ou técnico de campo -- escolha o
        contato direto no WhatsApp que abrir, ou copie o endereço e cole onde precisar.</p>
      <div class="d-flex flex-wrap gap-2">
        <a href="https://wa.me/?text=<?= urlencode($msgCompartilhar) ?>" target="_blank" rel="noopener"
           class="btn btn-success">
          <i class="bi bi-whatsapp me-1"></i>Enviar por WhatsApp
        </a>
        <a href="<?= e($googleUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-primary">
          <i class="bi bi-google me-1"></i>Abrir no Google Maps
        </a>
        <a href="<?= e($wazeUrl) ?>" target="_blank" rel="noopener" class="btn btn-outline-info">
          <i class="bi bi-signpost-split-fill me-1"></i>Abrir no Waze
        </a>
      </div>
    </div>
  </div>

  <script>
  document.getElementById('btnCopiarEndereco')?.addEventListener('click', async function () {
    const btn = this, orig = btn.innerHTML;
    try {
      await navigator.clipboard.writeText(<?= json_encode($endereco) ?>);
      btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Copiado!';
    } catch (e) {
      btn.innerHTML = '<i class="bi bi-x-lg me-1"></i>Não foi possível copiar';
    }
    setTimeout(() => { btn.innerHTML = orig; }, 1800);
  });

  (function () {
    const el = document.getElementById('mapaComoChegar');
    if (!el) return;
    const nome = <?= json_encode($nomeEmp) ?>;
    const endereco = <?= json_encode($endereco) ?>;
    const logo = <?= json_encode(!empty($empresa['logo']) ? url('/uploads/' . $empresa['logo']) : null) ?>;
    const fLat = <?= $lat !== null ? $lat : 'null' ?>;
    const fLng = <?= $lng !== null ? $lng : 'null' ?>;

    let carregado = false;
    function carregar() {
      if (carregado) return; carregado = true;
      const css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css';
      document.head.appendChild(css);
      const js = document.createElement('script');
      js.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js';
      js.onload = iniciar;
      document.body.appendChild(js);
    }
    function iniciar() {
      if (typeof L === 'undefined') return;
      const inner = logo
        ? `<img src="${logo}" style="width:100%;height:100%;object-fit:contain;padding:3px">`
        : `<svg viewBox="0 0 200 50" style="width:100%;height:100%"><rect width="200" height="50" rx="8" fill="#1e3a5f"/><text x="100" y="34" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="30" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>`;
      const icon = L.divIcon({
        html: `<div style="width:48px;height:48px;border-radius:50%;background:#fff;border:3px solid #f97316;box-shadow:0 4px 14px rgba(0,0,0,.3);overflow:hidden;display:flex;align-items:center;justify-content:center">${inner}</div>`,
        className: '', iconSize: [48, 48], iconAnchor: [24, 24], popupAnchor: [0, -24]
      });
      function render(lat, lng, zoom) {
        const map = L.map('mapaComoChegar', { scrollWheelZoom: false }).setView([lat, lng], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        L.marker([lat, lng], { icon }).addTo(map).bindPopup(`<b>${nome}</b><br><span style="color:#64748b;font-size:.8rem">${endereco}</span>`).openPopup();
        setTimeout(() => map.invalidateSize(), 200);
      }
      function usarFallback() { if (fLat && fLng) render(fLat, fLng, 14); }
      if (endereco) {
        fetch('https://nominatim.openstreetmap.org/search?format=json&limit=1&countrycodes=br&q=' + encodeURIComponent(endereco))
          .then(r => r.json())
          .then(d => {
            if (!d || !d[0]) { usarFallback(); return; }
            const gLat = parseFloat(d[0].lat), gLng = parseFloat(d[0].lon);
            if (fLat && fLng && (Math.abs(gLat - fLat) > 0.15 || Math.abs(gLng - fLng) > 0.15)) usarFallback();
            else render(gLat, gLng, 16);
          })
          .catch(usarFallback);
      } else {
        usarFallback();
      }
    }
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver(function (entries) {
        if (entries.some(e => e.isIntersecting)) { carregar(); obs.disconnect(); }
      }, { rootMargin: '400px' });
      obs.observe(el);
    } else {
      carregar();
    }
  })();
  </script>
  <?php endif; ?>
</div>
