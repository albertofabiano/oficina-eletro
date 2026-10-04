<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-geo-alt me-2 text-primary"></i>Mapa de Clientes</h4>
  <p class="text-muted small mb-4" style="max-width:820px">
    Distribuição geográfica das empresas do FixaOS, agregada por cidade (não por empresa
    individual) — a maioria das fichas do Diretório, importadas de CNPJ, só tem cidade/UF,
    sem endereço completo geocodificado. A coordenada de cada cidade vem de uma referência
    estática do IBGE (5.571 municípios), casada com a cidade/UF cadastrada de cada empresa.
    <strong>Diretório</strong> conta toda ficha listada (reivindicada ou não);
    <strong>Sistema completo</strong> só conta quem de fato reivindicou/criou conta — sem essa
    segunda exigência, fichas importadas de CNPJ nunca reivindicadas (que ficam com o valor
    padrão da coluna) apareceriam contadas aqui também.
  </p>

  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f97316;margin-right:.3rem"></span>Diretório</div>
        <div class="fs-3 fw-bold"><?= number_format($totalDiretorio, 0, ',', '.') ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#0d6efd;margin-right:.3rem"></span>Sistema completo</div>
        <div class="fs-3 fw-bold"><?= number_format($totalCompleto, 0, ',', '.') ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small" title="Uma cidade com empresa nos dois grupos conta um ponto por grupo">Pontos no mapa</div>
        <div class="fs-3 fw-bold"><?= number_format(count($pontosDiretorio) + count($pontosCompleto), 0, ',', '.') ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small" title="Cidade/UF cadastrada mas que não bateu com nenhum município do IBGE — digitação fora do padrão, abreviação, cidade de outro país etc.">Sem localização <i class="bi bi-question-circle text-muted"></i></div>
        <div class="fs-3 fw-bold text-muted"><?= number_format($semCoordenada, 0, ',', '.') ?></div>
      </div></div>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-0">
      <div id="mapaClientes" style="height:640px;border-radius:.375rem"></div>
    </div>
  </div>
</div>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css">
<script src="https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js"></script>
<script>
(function () {
  var pontosDiretorio = <?= json_encode($pontosDiretorio, JSON_UNESCAPED_UNICODE) ?>;
  var pontosCompleto  = <?= json_encode($pontosCompleto, JSON_UNESCAPED_UNICODE) ?>;

  var mapa = L.map('mapaClientes').setView([-14.2, -51.9], 4); // centro aproximado do Brasil
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    maxZoom: 18, attribution: '&copy; OpenStreetMap'
  }).addTo(mapa);

  function raioPorTotal(total) {
    // Escala não-linear (raiz quadrada) — sem isso, uma cidade com 500 empresas ficaria
    // gigantesca (proporcional direto) e engoliria visualmente as cidades vizinhas menores.
    return 4 + Math.sqrt(total) * 2.2;
  }

  function montarCamada(pontos, cor) {
    var marcadores = pontos.map(function (p) {
      var m = L.circleMarker([p.lat, p.lng], {
        radius: raioPorTotal(p.total),
        color: cor,
        weight: 1.5,
        fillColor: cor,
        fillOpacity: 0.45,
      });
      m.bindPopup(
        '<strong>' + p.cidade + ', ' + p.uf + '</strong><br>' +
        p.total + ' empresa' + (p.total === 1 ? '' : 's')
      );
      return m;
    });
    return L.layerGroup(marcadores);
  }

  var camadaDiretorio = montarCamada(pontosDiretorio, '#f97316');
  var camadaCompleto  = montarCamada(pontosCompleto, '#0d6efd');

  camadaDiretorio.addTo(mapa);
  camadaCompleto.addTo(mapa);

  L.control.layers(null, {
    'Diretório': camadaDiretorio,
    'Sistema completo': camadaCompleto,
  }, { collapsed: false }).addTo(mapa);
})();
</script>
