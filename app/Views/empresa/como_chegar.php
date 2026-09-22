<?php
/* Mapa "Como chegar" na empresa -- pensado pra técnico/motorista de campo (ou o próprio cliente)
   descobrir rápido o caminho e compartilhar por WhatsApp, sem precisar digitar endereço nem
   procurar no mapa por conta própria. Rota livre (ver routes/web.php) -- qualquer usuário logado
   acessa, mesmo sem permissão de Configurações.

   Segunda parte da tela: registrar o endereço de quem pediu uma visita técnica (chamado
   recebido por WhatsApp, por exemplo) -- o dono/atendente digita o que o cliente informou por
   telefone, direto aqui, sem link nenhum enviado pro cliente. Preenchendo nome + CPF/CNPJ +
   telefone, o sistema casa (por CPF/CNPJ) com um cliente já cadastrado e atualiza o endereço
   dele, ou cria um cliente novo -- ver EmpresaController::comoChegarCliente(). A partir daí a
   tela mostra os dois trajetos possíveis: até a empresa (sempre visível) ou até a casa do
   cliente (aparece assim que o endereço é confirmado). */
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

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div>
            <div class="text-muted small text-uppercase fw-semibold" style="font-size:.7rem">Até a empresa</div>
            <div class="fw-bold"><?= e($nomeEmp) ?></div>
            <div class="text-muted small"><?= e($endereco) ?></div>
            <div class="small text-primary fw-semibold d-none" id="distEmpresa"><i class="bi bi-geo-alt-fill me-1"></i></div>
          </div>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCopiarEndereco">
            <i class="bi bi-clipboard me-1"></i>Copiar endereço
          </button>
        </div>
      </div>

      <div class="card border-0 shadow-sm mb-3 overflow-hidden">
        <div id="mapaComoChegar" style="width:100%;height:300px;background:#eef2f7"></div>
      </div>

      <div class="card border-0 shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex flex-wrap gap-2">
            <a href="https://wa.me/?text=<?= urlencode($msgCompartilhar) ?>" target="_blank" rel="noopener" class="btn btn-success">
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

      <!-- Preenchido via JS assim que o endereço do cliente é confirmado (ver #formEnderecoCliente) -->
      <div id="blocoRotaCliente" class="d-none">
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="text-muted small text-uppercase fw-semibold mb-1" style="font-size:.7rem">Até o cliente</div>
            <div class="fw-bold" id="rotaClienteNome"></div>
            <div class="text-muted small" id="rotaClienteEndereco"></div>
            <div class="small text-primary fw-semibold d-none" id="distCliente"><i class="bi bi-geo-alt-fill me-1"></i></div>
          </div>
        </div>
        <div class="card border-0 shadow-sm mb-3 overflow-hidden">
          <div id="mapaCliente" style="width:100%;height:300px;background:#eef2f7"></div>
        </div>
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body d-flex flex-wrap gap-2" id="botoesRotaCliente"></div>
        </div>
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-body">
            <div class="fw-semibold mb-2 small">Avisar quem vai até lá</div>
            <div class="d-flex flex-wrap gap-2">
              <button type="button" class="btn btn-outline-success btn-sm" id="btnEnviarTecnico" disabled>
                <i class="bi bi-whatsapp me-1"></i>Enviar pro técnico
              </button>
              <button type="button" class="btn btn-outline-success btn-sm" id="btnEnviarCliente" disabled>
                <i class="bi bi-whatsapp me-1"></i>Enviar pro cliente
              </button>
              <button type="button" class="btn btn-success btn-sm" id="btnEnviarAmbos" disabled>
                <i class="bi bi-whatsapp me-1"></i>Enviar pra ambos
              </button>
            </div>
            <div class="form-text" id="enviarAmbosAviso"></div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-5">
      <div class="card border-0 shadow-sm">
        <div class="card-body">
          <div class="fw-semibold mb-1"><i class="bi bi-telephone-inbound-fill me-1 text-primary"></i>Chamado técnico — visita ao cliente</div>
          <p class="text-muted small mb-3">Recebeu um pedido por WhatsApp e vale a pena ir até o
            cliente? Confirme os dados que ele informou aqui -- nome, CPF e telefone já cadastram
            (ou atualizam) o cliente automaticamente.</p>

          <div class="position-relative mb-3">
            <label class="form-label small fw-semibold">Já é cliente? Busque por nome, telefone ou CPF</label>
            <input type="text" id="ecBusca" class="form-control form-control-sm" autocomplete="off" placeholder="Digite pra buscar...">
            <div id="ecBuscaResultados" class="list-group position-absolute w-100 shadow" style="z-index:20;max-height:260px;overflow:auto"></div>
            <div id="ecBuscaAviso" class="form-text text-success d-none"><i class="bi bi-check-circle-fill me-1"></i>Dados preenchidos do cadastro -- edite se precisar.</div>
          </div>

          <form id="formEnderecoCliente">
            <?= csrf_field() ?>
            <input type="hidden" name="cliente_id" id="ecClienteId">
            <div class="row g-2">
              <div class="col-12">
                <label class="form-label small fw-semibold">Nome do cliente *</label>
                <input type="text" name="nome" id="ecNome" class="form-control form-control-sm" required>
              </div>
              <div class="col-7">
                <label class="form-label small fw-semibold">CPF/CNPJ</label>
                <input type="text" name="cpf_cnpj" id="ecCpf" class="form-control form-control-sm" placeholder="000.000.000-00">
              </div>
              <div class="col-5">
                <label class="form-label small fw-semibold">Telefone</label>
                <input type="text" name="telefone" id="ecTelefone" class="form-control form-control-sm" placeholder="(00) 00000-0000">
              </div>
              <?php if ($tecnicos): ?>
              <div class="col-12">
                <label class="form-label small fw-semibold">Técnico/motorista que vai até lá</label>
                <select id="ecTecnico" class="form-select form-select-sm">
                  <option value="">-- Selecione --</option>
                  <?php foreach ($tecnicos as $t): ?>
                  <option value="<?= (int) $t['id'] ?>" data-telefone="<?= e(only_numbers((string) ($t['telefone'] ?? ''))) ?>">
                    <?= e($t['nome']) ?><?= empty($t['telefone']) ? ' (sem telefone cadastrado)' : '' ?>
                  </option>
                  <?php endforeach; ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="col-5">
                <label class="form-label small fw-semibold">CEP</label>
                <input type="text" name="cep" id="ecCep" class="form-control form-control-sm" placeholder="00000-000">
                <div id="ecCepMsg" class="form-text"></div>
              </div>
              <div class="col-7">
                <label class="form-label small fw-semibold">Rua</label>
                <input type="text" name="logradouro" id="ecLogradouro" class="form-control form-control-sm">
              </div>
              <div class="col-4">
                <label class="form-label small fw-semibold">Número</label>
                <input type="text" name="numero" id="ecNumero" class="form-control form-control-sm">
              </div>
              <div class="col-8">
                <label class="form-label small fw-semibold">Bairro</label>
                <input type="text" name="bairro" id="ecBairro" class="form-control form-control-sm">
              </div>
              <div class="col-8">
                <label class="form-label small fw-semibold">Cidade</label>
                <input type="text" name="cidade" id="ecCidade" class="form-control form-control-sm">
              </div>
              <div class="col-4">
                <label class="form-label small fw-semibold">UF</label>
                <input type="text" name="uf" id="ecUf" maxlength="2" class="form-control form-control-sm text-uppercase">
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold">Referência (ponto de referência, apto, bloco...)</label>
                <input type="text" name="complemento" id="ecComplemento" class="form-control form-control-sm">
              </div>
            </div>
            <div id="ecErro" class="alert alert-danger py-2 small mt-2 d-none"></div>
            <button type="submit" class="btn btn-primary w-100 mt-3" id="ecBtnSalvar">
              <i class="bi bi-signpost-split me-1"></i>Confirmar endereço e mostrar rota
            </button>
          </form>
        </div>
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

  // ── Mapa reaproveitável (empresa e, depois, cliente) -- mesma técnica de diretorio/empresa.php:
  // Leaflet carregado só quando o container entra na tela, geocode por endereço via Nominatim,
  // com coordenadas prontas como fallback/checagem de sanidade. ──
  let _leafletCarregando = false, _leafletPronto = false;
  function carregarLeaflet(cb) {
    if (_leafletPronto) { cb(); return; }
    if (!_leafletCarregando) {
      _leafletCarregando = true;
      const css = document.createElement('link');
      css.rel = 'stylesheet';
      css.href = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.css';
      document.head.appendChild(css);
      const js = document.createElement('script');
      js.src = 'https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.min.js';
      // Dispara 'leaflet-pronto' pra avisar quem mais estava esperando (ex.: o mapa do CLIENTE
      // pediu carregarLeaflet() enquanto o script ainda estava a caminho pro mapa da empresa) --
      // sem isso, esse segundo chamador fica esperando um evento que nunca chega, e o mapa dele
      // nunca renderiza (só o innerHTML vazio fica pra sempre).
      js.onload = function () { _leafletPronto = true; cb(); document.dispatchEvent(new Event('leaflet-pronto')); };
      // Falha de rede (CDN fora do ar, firewall bloqueando) -- libera quem estiver esperando em
      // vez de deixar travado pra sempre; renderMapaComoChegar() já desiste sozinho quando `L`
      // continua indefinido. Zera _leafletCarregando pra uma tentativa futura poder tentar de novo.
      js.onerror = function () { _leafletCarregando = false; document.dispatchEvent(new Event('leaflet-pronto')); };
      document.body.appendChild(js);
    } else {
      document.addEventListener('leaflet-pronto', cb, { once: true });
    }
  }

  function renderMapaComoChegar(containerId, nome, endereco, fLat, fLng, logo, onResolvido) {
    carregarLeaflet(function () {
      if (typeof L === 'undefined') return;
      const inner = logo
        ? `<img src="${logo}" style="width:100%;height:100%;object-fit:contain;padding:3px">`
        : `<svg viewBox="0 0 200 50" style="width:100%;height:100%"><rect width="200" height="50" rx="8" fill="#1e3a5f"/><text x="100" y="34" text-anchor="middle" font-family="Arial Black,Arial,sans-serif" font-weight="900" font-size="30" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>`;
      const icon = L.divIcon({
        html: `<div style="width:48px;height:48px;border-radius:50%;background:#fff;border:3px solid #f97316;box-shadow:0 4px 14px rgba(0,0,0,.3);overflow:hidden;display:flex;align-items:center;justify-content:center">${inner}</div>`,
        className: '', iconSize: [48, 48], iconAnchor: [24, 24], popupAnchor: [0, -24]
      });
      function render(lat, lng, zoom) {
        const map = L.map(containerId, { scrollWheelZoom: false }).setView([lat, lng], zoom);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { attribution: '© OpenStreetMap' }).addTo(map);
        L.marker([lat, lng], { icon }).addTo(map).bindPopup(`<b>${nome}</b><br><span style="color:#64748b;font-size:.8rem">${endereco}</span>`).openPopup();
        setTimeout(() => map.invalidateSize(), 200);
        if (onResolvido) onResolvido(lat, lng);
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
    });
  }

  function iniciarMapaPreguicoso(containerId, nome, endereco, fLat, fLng, logo, onResolvido) {
    const el = document.getElementById(containerId);
    if (!el) return;
    if ('IntersectionObserver' in window) {
      const obs = new IntersectionObserver(function (entries) {
        if (entries.some(e => e.isIntersecting)) { renderMapaComoChegar(containerId, nome, endereco, fLat, fLng, logo, onResolvido); obs.disconnect(); }
      }, { rootMargin: '400px' });
      obs.observe(el);
    } else {
      renderMapaComoChegar(containerId, nome, endereco, fLat, fLng, logo, onResolvido);
    }
  }

  // ── Distância em tempo real (GPS do navegador) até cada destino conhecido -- atualiza
  // sozinha enquanto a pessoa se move, sem precisar recarregar a página. Distância em linha
  // reta (não é rota de carro/trânsito, só uma referência rápida de "tá longe ou perto"). ──
  let _destEmpresa = null, _destCliente = null, _geoWatchId = null, _geoErroMostrado = false;
  function haversineKm(lat1, lng1, lat2, lng2) {
    const R = 6371, toRad = function (x) { return x * Math.PI / 180; };
    const dLat = toRad(lat2 - lat1), dLng = toRad(lng2 - lng1);
    const a = Math.sin(dLat / 2) ** 2 + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;
    return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
  }
  function atualizarDistancia(elId, dest, latAgora, lngAgora) {
    const el = document.getElementById(elId);
    if (!el || !dest) return;
    const km = haversineKm(latAgora, lngAgora, dest.lat, dest.lng);
    const texto = km < 1 ? Math.round(km * 1000) + ' m' : km.toFixed(1).replace('.', ',') + ' km';
    el.innerHTML = '<i class="bi bi-geo-alt-fill me-1"></i>' + texto + ' daqui (linha reta)';
    el.classList.remove('d-none');
  }
  function iniciarDistanciaAoVivo() {
    if (!('geolocation' in navigator) || _geoWatchId !== null) return;
    _geoWatchId = navigator.geolocation.watchPosition(
      function (pos) {
        atualizarDistancia('distEmpresa', _destEmpresa, pos.coords.latitude, pos.coords.longitude);
        atualizarDistancia('distCliente', _destCliente, pos.coords.latitude, pos.coords.longitude);
      },
      function () {
        // Sem permissão/sem GPS -- não insiste, só deixa de mostrar distância (o mapa/rota
        // continuam funcionando normalmente sem ela).
      },
      { enableHighAccuracy: true, maximumAge: 15000 }
    );
  }

  iniciarMapaPreguicoso(
    'mapaComoChegar',
    <?= json_encode($nomeEmp) ?>,
    <?= json_encode($endereco) ?>,
    <?= $lat !== null ? $lat : 'null' ?>,
    <?= $lng !== null ? $lng : 'null' ?>,
    <?= json_encode(!empty($empresa['logo']) ? url('/uploads/' . $empresa['logo']) : null) ?>,
    function (lat, lng) { _destEmpresa = { lat: lat, lng: lng }; iniciarDistanciaAoVivo(); }
  );

  function montarBotoesRota(endereco) {
    const g = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(endereco);
    const w = 'https://waze.com/ul?q=' + encodeURIComponent(endereco) + '&navigate=yes';
    return `<a href="${g}" target="_blank" rel="noopener" class="btn btn-outline-primary"><i class="bi bi-google me-1"></i>Abrir no Google Maps</a>`
         + `<a href="${w}" target="_blank" rel="noopener" class="btn btn-outline-info"><i class="bi bi-signpost-split-fill me-1"></i>Abrir no Waze</a>`;
  }

  // ── CEP: autocompleta rua/bairro/cidade/UF (mesmo padrão do modal "novo cliente" no wizard
  // de OS -- ViaCEP direto do navegador, sem passar pelo backend). ──
  document.getElementById('ecCep')?.addEventListener('blur', async function () {
    const cep = this.value.replace(/\D/g, '');
    const msg = document.getElementById('ecCepMsg');
    if (cep.length !== 8) return;
    msg.textContent = 'Buscando...';
    try {
      const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const d = await r.json();
      if (d.erro) { msg.textContent = 'CEP não encontrado.'; return; }
      document.getElementById('ecLogradouro').value = d.logradouro || '';
      document.getElementById('ecBairro').value = d.bairro || '';
      document.getElementById('ecCidade').value = d.localidade || '';
      document.getElementById('ecUf').value = d.uf || '';
      msg.textContent = '';
      if (!d.logradouro) document.getElementById('ecLogradouro').focus();
      else document.getElementById('ecNumero').focus();
    } catch (e) {
      msg.textContent = 'Falha ao buscar o CEP.';
    }
  });

  // ── Busca AJAX de clientes já cadastrados (mesmo endpoint/padrão do PDV: debounce 250ms,
  // dropdown list-group posicionado absoluto) -- selecionar um preenche o formulário inteiro
  // e passa a atualizar ESSE cliente direto (por id), sem depender de casar por CPF/telefone
  // de novo em EmpresaController::comoChegarCliente(). ──
  function esc(s) { const d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  const API_CLI_CC = <?= json_encode(url('/api/clientes')) ?>;
  const ecBusca = document.getElementById('ecBusca');
  const ecBuscaResultados = document.getElementById('ecBuscaResultados');
  let ultimosClientesCC = [], timerBuscaCC = null;

  function preencherClienteEncontrado(c) {
    document.getElementById('ecClienteId').value = c.id;
    document.getElementById('ecNome').value = c.nome || '';
    document.getElementById('ecCpf').value = c.cpf_cnpj || '';
    document.getElementById('ecTelefone').value = c.telefone || c.whatsapp || '';
    document.getElementById('ecCep').value = c.cep || '';
    document.getElementById('ecLogradouro').value = c.logradouro || '';
    document.getElementById('ecNumero').value = c.numero || '';
    document.getElementById('ecBairro').value = c.bairro || '';
    document.getElementById('ecCidade').value = c.cidade || '';
    document.getElementById('ecUf').value = c.uf || '';
    document.getElementById('ecComplemento').value = c.complemento || '';
    document.getElementById('ecBuscaAviso').classList.remove('d-none');
    ecBusca.value = '';
    ecBuscaResultados.innerHTML = '';
    ultimosClientesCC = [];
  }

  // Editar Nome/CPF/Telefone à mão depois de escolher um cliente desfaz o vínculo -- passa a
  // valer o casamento normal por CPF/telefone (ou criação de um cliente novo) de novo, em vez
  // de arriscar atualizar o cadastro errado com dado que já não bate mais.
  ['ecNome', 'ecCpf', 'ecTelefone'].forEach(function (id) {
    document.getElementById(id)?.addEventListener('input', function () {
      document.getElementById('ecClienteId').value = '';
      document.getElementById('ecBuscaAviso').classList.add('d-none');
    });
  });

  ecBusca?.addEventListener('input', function () {
    clearTimeout(timerBuscaCC);
    const q = ecBusca.value.trim();
    if (q.length < 2) { ecBuscaResultados.innerHTML = ''; ultimosClientesCC = []; return; }
    timerBuscaCC = setTimeout(function () { buscarClienteCC(q); }, 250);
  });

  function buscarClienteCC(q) {
    fetch(API_CLI_CC + '?q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (lista) {
        ultimosClientesCC = lista || [];
        if (!ultimosClientesCC.length) {
          ecBuscaResultados.innerHTML = '<div class="list-group-item text-muted small">Nenhum cliente encontrado.</div>';
          return;
        }
        ecBuscaResultados.innerHTML = ultimosClientesCC.map(function (c, i) {
          return '<button type="button" class="list-group-item list-group-item-action" data-i="' + i + '">'
            + '<strong>' + esc(c.nome) + '</strong>'
            + (c.telefone || c.whatsapp ? ' <span class="text-muted small">' + esc(c.telefone || c.whatsapp) + '</span>' : '')
            + '</button>';
        }).join('');
        ecBuscaResultados.querySelectorAll('[data-i]').forEach(function (b) {
          b.addEventListener('click', function () { preencherClienteEncontrado(ultimosClientesCC[+b.dataset.i]); });
        });
      })
      .catch(function () { ecBuscaResultados.innerHTML = '<div class="list-group-item text-danger small">Falha ao buscar.</div>'; });
  }

  // ── Avisar quem vai até o cliente (técnico e/ou cliente) pelo WhatsApp da EMPRESA (API) ──
  function soDigitos(s) { return (s || '').replace(/\D/g, ''); }
  function montarMsgVisita(nomeCliente, endereco, referencia) {
    const g = 'https://www.google.com/maps/dir/?api=1&destination=' + encodeURIComponent(endereco);
    const w = 'https://waze.com/ul?q=' + encodeURIComponent(endereco) + '&navigate=yes';
    return '📍 Visita técnica -- ' + nomeCliente + '\n' + endereco
      + (referencia ? '\nReferência: ' + referencia : '')
      + '\n\nGoogle Maps: ' + g + '\nWaze: ' + w;
  }

  // Manda pelo número da PRÓPRIA empresa (Evolution API), não abre o app/site do WhatsApp de
  // quem está usando a tela -- mesmo canal já usado pra mandar mensagem/PDF pro cliente em
  // outras telas (ver OrdemServicoController::enviarLinkWhatsapp()).
  async function enviarWhatsappApi(numero, texto) {
    const r = await fetch('<?= url('/como-chegar/enviar') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
      body: 'numero=' + encodeURIComponent(numero) + '&mensagem=' + encodeURIComponent(texto),
    });
    return r.json();
  }

  let _telTecnicoAtual = '', _telClienteAtual = '', _msgVisitaAtual = '';
  function atualizarBotoesEnvio() {
    const podeTecnico = !!_telTecnicoAtual, podeCliente = !!_telClienteAtual;
    document.getElementById('btnEnviarTecnico').disabled = !podeTecnico;
    document.getElementById('btnEnviarCliente').disabled = !podeCliente;
    document.getElementById('btnEnviarAmbos').disabled = !(podeTecnico && podeCliente);
    const aviso = document.getElementById('enviarAmbosAviso');
    if (!podeTecnico && !podeCliente) aviso.textContent = 'Selecione um técnico com telefone cadastrado e/ou informe o telefone do cliente pra liberar o envio.';
    else if (!podeTecnico) aviso.textContent = 'Selecione um técnico com telefone cadastrado pra também poder avisar ele.';
    else if (!podeCliente) aviso.textContent = 'Informe o telefone do cliente pra também poder avisar ele.';
    else aviso.textContent = '';
  }

  async function enviarComFeedback(btn, destinos) {
    if (!whatsappProprioOuAvisar()) return;
    const orig = btn.innerHTML, aviso = document.getElementById('enviarAmbosAviso');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';
    aviso.className = 'form-text';
    aviso.textContent = '';
    const falhas = [];
    for (const d of destinos) {
      const j = await enviarWhatsappApi(d.numero, _msgVisitaAtual).catch(() => ({ success: false }));
      if (!j.success) falhas.push(d.rotulo + (j.error ? ' (' + j.error + ')' : ''));
    }
    if (!falhas.length) {
      aviso.className = 'form-text text-success fw-semibold';
      aviso.textContent = destinos.length > 1 ? 'Mensagem enviada pro técnico e pro cliente!' : 'Mensagem enviada!';
    } else {
      aviso.className = 'form-text text-danger fw-semibold';
      aviso.textContent = 'Falha ao enviar pra: ' + falhas.join('; ');
    }
    btn.disabled = false;
    btn.innerHTML = orig;
    atualizarBotoesEnvio();
  }
  document.getElementById('btnEnviarTecnico')?.addEventListener('click', function () {
    enviarComFeedback(this, [{ numero: _telTecnicoAtual, rotulo: 'técnico' }]);
  });
  document.getElementById('btnEnviarCliente')?.addEventListener('click', function () {
    enviarComFeedback(this, [{ numero: _telClienteAtual, rotulo: 'cliente' }]);
  });
  document.getElementById('btnEnviarAmbos')?.addEventListener('click', function () {
    enviarComFeedback(this, [{ numero: _telTecnicoAtual, rotulo: 'técnico' }, { numero: _telClienteAtual, rotulo: 'cliente' }]);
  });
  document.getElementById('ecTecnico')?.addEventListener('change', function () {
    const opt = this.selectedOptions[0];
    _telTecnicoAtual = (opt && opt.value) ? (opt.dataset.telefone || '') : '';
    atualizarBotoesEnvio();
  });

  // ── Envio do formulário: cadastra/atualiza o cliente (por CPF/CNPJ) e mostra a rota ──
  document.getElementById('formEnderecoCliente')?.addEventListener('submit', async function (e) {
    e.preventDefault();
    const btn = document.getElementById('ecBtnSalvar'), orig = btn.innerHTML;
    const erroBox = document.getElementById('ecErro');
    erroBox.classList.add('d-none');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';
    try {
      const fd = new FormData(this);
      const r = await fetch('<?= url('/como-chegar/cliente') ?>', { method: 'POST', body: fd });
      const j = await r.json();
      if (!j.ok) { erroBox.textContent = j.erro || 'Não foi possível salvar.'; erroBox.classList.remove('d-none'); return; }

      document.getElementById('rotaClienteNome').textContent = j.nome;
      document.getElementById('rotaClienteEndereco').textContent = j.endereco || '(sem endereço completo)';
      document.getElementById('botoesRotaCliente').innerHTML = j.endereco ? montarBotoesRota(j.endereco) : '';
      document.getElementById('blocoRotaCliente').classList.remove('d-none');
      if (j.endereco) {
        document.getElementById('mapaCliente').innerHTML = '';
        renderMapaComoChegar('mapaCliente', j.nome, j.endereco, null, null, null,
          function (lat, lng) { _destCliente = { lat: lat, lng: lng }; iniciarDistanciaAoVivo(); });
      }

      _telClienteAtual = soDigitos(document.getElementById('ecTelefone').value);
      _msgVisitaAtual = montarMsgVisita(j.nome, j.endereco || '(endereço incompleto)', document.getElementById('ecComplemento').value.trim());
      atualizarBotoesEnvio();

      document.getElementById('blocoRotaCliente').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } catch (e2) {
      erroBox.textContent = 'Falha de conexão. Tente de novo.';
      erroBox.classList.remove('d-none');
    }
    btn.disabled = false;
    btn.innerHTML = orig;
  });
  </script>
  <?php endif; ?>
</div>
