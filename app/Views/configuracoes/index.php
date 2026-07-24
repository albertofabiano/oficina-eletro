<?php
$defaultTab = $abaInicial ?? ($podeConfig ? 'empresa' : ($podeUsuarios ? 'usuarios' : null));
function cfgTabAtiva(string $chave, ?string $default): bool { return $chave === $default; }
?>
<div class="d-flex align-items-center gap-2 mb-3">
  <h4 class="fw-bold mb-0"><i class="bi bi-gear-fill me-2 text-secondary"></i>Configurações do Sistema</h4>
</div>

<style>
#cfgTabs .nav-link { color: #475569; font-weight: 600; font-size: .88rem; border-radius: 999px; padding: .45rem 1rem; }
#cfgTabs .nav-link.active { background: #1e3a5f; color: #fff; }
#cfgTabs .nav-link:not(.active):hover { background: #f0f2f5; }
#cfgTabs .nav-item { position: relative; }
#cfgTabs .nav-item + .nav-item::before { content: ''; position: absolute; left: -5px; top: 50%; transform: translateY(-50%); width: 1px; height: 18px; background: #d7dce3; }
.cfg-iframe-wrap { border: 1px solid #e5e9f0; border-radius: 12px; overflow: hidden; background: #fff; }
.cfg-iframe { width: 100%; border: 0; display: block; height: 420px; transition: height .15s ease; }
.cfg-pane-card { border: 1px solid #e5e9f0; border-radius: 12px; padding: 1.5rem; max-width: 560px; }
</style>

<ul class="nav nav-pills flex-wrap gap-2 mb-4" id="cfgTabs" role="tablist">
  <?php if ($podeConfig): ?>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('tecnicos', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-tecnicos" type="button"><i class="bi bi-tools me-1"></i>Técnicos</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('status', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-status" type="button"><i class="bi bi-tags me-1"></i>Status de OS</button></li>
  <?php if ($isAdmin): ?>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('chat', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-chat" type="button"><i class="bi bi-chat-dots me-1"></i>Chat da Equipe</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('previsao', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-previsao" type="button"><i class="bi bi-clock-history me-1"></i>Previsão de Entrega</button></li>
  <?php endif; ?>
  <?php endif; ?>
  <?php if ($podeUsuarios): ?>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('usuarios', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-usuarios" type="button"><i class="bi bi-person-gear me-1"></i>Usuários</button></li>
  <?php endif; ?>
  <?php if ($podeConfig): ?>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('exibicao', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-exibicao" type="button"><i class="bi bi-fonts me-1"></i>Exibição do Texto</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('empresa', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-empresa" type="button"><i class="bi bi-building me-1"></i>Empresa</button></li>
  <li class="nav-item" role="presentation"><button class="nav-link <?= cfgTabAtiva('imagens', $defaultTab) ? 'active' : '' ?>" data-bs-toggle="pill" data-bs-target="#tab-imagens" type="button"><i class="bi bi-image me-1"></i>Editor de Imagens</button></li>
  <?php endif; ?>
</ul>

<div class="tab-content" id="cfgTabsContent">

  <?php if ($podeConfig): ?>
  <div class="tab-pane fade <?= cfgTabAtiva('tecnicos', $defaultTab) ? 'show active' : '' ?>" id="tab-tecnicos">
    <div class="cfg-iframe-wrap"><iframe class="cfg-iframe" data-src="<?= url('/tecnicos') ?>?painel=1" title="Técnicos"></iframe></div>
  </div>
  <div class="tab-pane fade <?= cfgTabAtiva('status', $defaultTab) ? 'show active' : '' ?>" id="tab-status">
    <div class="cfg-iframe-wrap"><iframe class="cfg-iframe" data-src="<?= url('/os/status') ?>?painel=1" title="Status de OS"></iframe></div>
  </div>

  <?php if ($isAdmin): ?>
  <div class="tab-pane fade <?= cfgTabAtiva('chat', $defaultTab) ? 'show active' : '' ?>" id="tab-chat">
    <div class="cfg-pane-card">
      <p class="text-muted small mb-3">Ligue ou desligue o chat interno da equipe (as conversas amarradas a cada OS + o sino no topo). Vale para <strong>toda a empresa</strong>.</p>
      <div class="form-check form-switch fs-5 mb-1">
        <input class="form-check-input" type="checkbox" id="cfgChatToggle" role="switch" <?= $chatHabilitado ? 'checked' : '' ?>>
        <label class="form-check-label fw-semibold" for="cfgChatToggle" id="cfgChatToggleLabel"><?= $chatHabilitado ? 'Chat ativado' : 'Chat desativado' ?></label>
      </div>
      <div class="text-muted small mb-3">Desligar esconde o sino de conversas e a caixa de chat nas OS. As mensagens já existentes não são apagadas.</div>

      <hr>
      <div class="fw-semibold small text-muted mb-2">Avisos sonoros</div>
      <div class="form-check form-switch mb-2">
        <input class="form-check-input" type="checkbox" id="cfgChatSomToggle" role="switch" <?= $chatSom ? 'checked' : '' ?>>
        <label class="form-check-label" for="cfgChatSomToggle"><i class="bi bi-volume-up me-1"></i>Aviso sonoro ao chegar mensagem</label>
      </div>
      <div class="form-check form-switch mb-3">
        <input class="form-check-input" type="checkbox" id="cfgChatInsistenteToggle" role="switch" <?= $chatInsistente ? 'checked' : '' ?>>
        <label class="form-check-label" for="cfgChatInsistenteToggle"><i class="bi bi-alarm me-1"></i>Repetir o aviso a cada 10s enquanto não lida</label>
      </div>
      <button type="button" class="btn btn-primary" id="cfgBtnSalvarChat"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      <span class="text-success small ms-2 d-none" id="cfgChatSalvoMsg"><i class="bi bi-check-circle-fill me-1"></i>Salvo</span>
    </div>
  </div>

  <div class="tab-pane fade <?= cfgTabAtiva('previsao', $defaultTab) ? 'show active' : '' ?>" id="tab-previsao">
    <div class="cfg-pane-card">
      <p class="text-muted small mb-3">Mostra ou esconde o campo de previsão de entrega nas Ordens de Serviço, para toda a empresa.</p>
      <div class="form-check form-switch fs-5">
        <input class="form-check-input" type="checkbox" id="cfgPrevisaoToggle" role="switch" <?= $mostrarPrevisao ? 'checked' : '' ?>>
        <label class="form-check-label fw-semibold" for="cfgPrevisaoToggle" id="cfgPrevisaoLabel"><?= $mostrarPrevisao ? 'Previsão de entrega visível' : 'Previsão de entrega oculta' ?></label>
      </div>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <?php if ($podeUsuarios): ?>
  <div class="tab-pane fade <?= cfgTabAtiva('usuarios', $defaultTab) ? 'show active' : '' ?>" id="tab-usuarios">
    <div class="cfg-iframe-wrap"><iframe class="cfg-iframe" data-src="<?= url('/usuarios') ?>?painel=1" title="Usuários"></iframe></div>
  </div>
  <?php endif; ?>

  <?php if ($podeConfig): ?>
  <div class="tab-pane fade <?= cfgTabAtiva('exibicao', $defaultTab) ? 'show active' : '' ?>" id="tab-exibicao">
    <div class="cfg-pane-card">
      <p class="text-muted small mb-3">Escolha como as informações do sistema aparecem na tela. Muda só a exibição — os dados continuam salvos exatamente como foram digitados.</p>
      <div class="d-flex flex-column gap-2 mb-3">
        <label class="border rounded p-3 d-flex align-items-center gap-3 m-0" style="cursor:pointer">
          <input type="radio" name="cfgPrefExib" value="0" <?= $textoMaiusculo ? '' : 'checked' ?>>
          <span style="text-transform:none"><span class="fw-semibold d-block">Texto normal</span><span class="text-muted small">Maria Souza · iPhone 12</span></span>
        </label>
        <label class="border rounded p-3 d-flex align-items-center gap-3 m-0" style="cursor:pointer">
          <input type="radio" name="cfgPrefExib" value="1" <?= $textoMaiusculo ? 'checked' : '' ?>>
          <span><span class="fw-semibold d-block">TUDO EM MAIÚSCULAS</span><span class="text-muted small" style="text-transform:uppercase">Maria Souza · iPhone 12</span></span>
        </label>
      </div>
      <button type="button" class="btn btn-primary" id="cfgBtnSalvarExib"><i class="bi bi-check-lg me-1"></i>Salvar</button>
    </div>
  </div>

  <div class="tab-pane fade <?= cfgTabAtiva('empresa', $defaultTab) ? 'show active' : '' ?>" id="tab-empresa">
    <div class="cfg-iframe-wrap"><iframe class="cfg-iframe" data-src="<?= url('/empresa') ?>?painel=1" title="Empresa"></iframe></div>
  </div>

  <div class="tab-pane fade <?= cfgTabAtiva('imagens', $defaultTab) ? 'show active' : '' ?>" id="tab-imagens">
    <div class="cfg-iframe-wrap"><iframe class="cfg-iframe" data-src="<?= url('/imagem/editor') ?>?painel=1" title="Editor de Imagens"></iframe></div>
  </div>
  <?php endif; ?>

</div>

<script>
(function () {
  function carregarIframe(pane) {
    var iframe = pane.querySelector('iframe.cfg-iframe');
    if (!iframe || iframe.dataset.loaded) return;
    iframe.src = iframe.dataset.src;
    iframe.dataset.loaded = '1';
  }
  // Aba ativa inicial já carrega direto.
  document.querySelectorAll('#cfgTabsContent .tab-pane.active').forEach(carregarIframe);

  // Ao trocar de aba, carrega o iframe daquela aba (lazy).
  document.querySelectorAll('#cfgTabs [data-bs-toggle="pill"]').forEach(function (btn) {
    btn.addEventListener('shown.bs.tab', function (e) {
      var alvo = document.querySelector(e.target.getAttribute('data-bs-target'));
      if (alvo) carregarIframe(alvo);
    });
  });

  // Os iframes (layout "painel") avisam a altura real via postMessage — ajusta sem scroll interno.
  window.addEventListener('message', function (e) {
    if (e.origin !== window.location.origin || !e.data || !e.data.fixaosPainelAltura) return;
    document.querySelectorAll('iframe.cfg-iframe').forEach(function (f) {
      if (f.contentWindow === e.source) {
        f.style.height = Math.max(240, e.data.fixaosPainelAltura + 24) + 'px';
      }
    });
  });
})();

// ── Chat da equipe ──
document.getElementById('cfgChatToggle')?.addEventListener('change', function () {
  document.getElementById('cfgChatToggleLabel').textContent = this.checked ? 'Chat ativado' : 'Chat desativado';
});
document.getElementById('cfgBtnSalvarChat')?.addEventListener('click', function () {
  var hab = document.getElementById('cfgChatToggle')?.checked ? '1' : '0';
  var som = document.getElementById('cfgChatSomToggle')?.checked ? '1' : '0';
  var ins = document.getElementById('cfgChatInsistenteToggle')?.checked ? '1' : '0';
  var btn = this; btn.disabled = true;
  fetch('<?= url('/preferencias/chat') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: 'habilitado=' + hab + '&som=' + som + '&insistente=' + ins
  }).then(function (r) { return r.json(); }).then(function () {
    btn.disabled = false;
    var msg = document.getElementById('cfgChatSalvoMsg');
    msg.classList.remove('d-none');
    setTimeout(function () { msg.classList.add('d-none'); }, 2000);
  }).catch(function () { btn.disabled = false; });
});

// ── Previsão de entrega ──
document.getElementById('cfgPrevisaoToggle')?.addEventListener('change', function () {
  var el = this; el.disabled = true;
  fetch('<?= url('/preferencias/previsao') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: 'mostrar=' + (el.checked ? '1' : '0')
  }).then(function (r) { return r.json(); }).then(function () {
    document.getElementById('cfgPrevisaoLabel').textContent = el.checked ? 'Previsão de entrega visível' : 'Previsão de entrega oculta';
    el.disabled = false;
  }).catch(function () { el.disabled = false; el.checked = !el.checked; });
});

// ── Exibição do texto ──
document.getElementById('cfgBtnSalvarExib')?.addEventListener('click', function () {
  var val = document.querySelector('input[name="cfgPrefExib"]:checked')?.value || '0';
  var btn = this; btn.disabled = true;
  fetch('<?= url('/preferencias/exibicao') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: 'maiusculo=' + val
  }).then(function (r) { return r.json(); }).then(function () { location.reload(); }).catch(function () { btn.disabled = false; });
});
</script>
