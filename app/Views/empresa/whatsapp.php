<?php $conectado = ($estado ?? '') === 'open'; ?>
<div class="page-content">
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-whatsapp text-success me-2"></i>WhatsApp da Empresa</h4>
      <p class="text-muted small mb-0" style="max-width:640px">Conecte o WhatsApp da sua loja para enviar OS, orçamentos e o link de acompanhamento direto do <strong>seu número</strong> — e não do número do FixaOS.</p>
    </div>
    <a href="<?= url('/empresa/whatsapp') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Atualizar</a>
  </div>

  <div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-4">
        <?php if ($conectado): ?>
          <div class="mb-2"><span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>Conectado</span></div>
          <i class="bi bi-whatsapp text-success" style="font-size:56px"></i>
          <h5 class="fw-bold mt-2 mb-1">WhatsApp conectado!</h5>
          <?php if (!empty($numero)): ?>
            <p class="text-muted mb-3">Número: <strong><?= e(preg_replace('/^55/', '', $numero)) ?></strong></p>
          <?php else: ?>
            <p class="text-muted mb-3">Suas OS, orçamentos e links agora saem deste número.</p>
          <?php endif; ?>
          <button id="btnDesconectar" class="btn btn-outline-danger btn-sm"><i class="bi bi-power me-1"></i>Desconectar</button>

        <?php elseif (!empty($qr)): ?>
          <div class="mb-2"><span class="badge bg-warning text-dark"><i class="bi bi-arrow-repeat me-1"></i>Aguardando leitura…</span></div>
          <div class="d-inline-block bg-white p-2 rounded border"><img src="<?= $qr ?>" alt="QR Code WhatsApp" style="width:260px;height:260px;display:block"></div>
          <ol class="text-start small text-muted mt-3 mb-0" style="max-width:360px;margin:0 auto;line-height:1.8">
            <li>Abra o <strong>WhatsApp</strong> (ou WhatsApp Business) no celular da loja</li>
            <li>Toque em <strong>⋮ / Config → Aparelhos conectados</strong></li>
            <li><strong>Conectar um aparelho</strong> e aponte a câmera para o código</li>
          </ol>
          <p class="text-muted small mt-3 mb-0"><i class="bi bi-clock me-1"></i>O código expira em segundos e se renova sozinho.</p>

        <?php else: ?>
          <div class="mb-2"><span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill me-1"></i>Indisponível</span></div>
          <p class="text-muted mb-3">Não foi possível gerar o QR Code agora. Tente novamente em instantes.</p>
          <a href="<?= url('/empresa/whatsapp') ?>" class="btn btn-success btn-sm"><i class="bi bi-arrow-clockwise me-1"></i>Tentar de novo</a>
        <?php endif; ?>
        </div>
      </div>

      <?php if (!$conectado): ?>
      <p class="text-center text-muted small mt-3"><i class="bi bi-shield-lock me-1"></i>Conexão segura (igual ao WhatsApp Web). O FixaOS não lê suas conversas.</p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
(function () {
  var conectado = <?= $conectado ? 'true' : 'false' ?>;
  if (!conectado) {
    var t = setInterval(function () {
      fetch('<?= url('/empresa/whatsapp/status') ?>', { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
        .then(function (r) { return r.json(); })
        .then(function (d) { if (d && d.estado === 'open') { clearInterval(t); location.reload(); } })
        .catch(function () {});
    }, 4000);
    <?php if (!empty($qr)): ?>
    setTimeout(function () { location.reload(); }, 35000); // renova o QR antes de expirar
    <?php endif; ?>
  }
  var btn = document.getElementById('btnDesconectar');
  if (btn) btn.addEventListener('click', function () {
    if (!confirm('Desconectar o WhatsApp da empresa? As mensagens deixarão de ser enviadas até reconectar.')) return;
    btn.disabled = true;
    var fd = new FormData(); fd.append('_token', '<?= csrf_token() ?>');
    fetch('<?= url('/empresa/whatsapp/desconectar') ?>', { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function () { location.reload(); })
      .catch(function () { btn.disabled = false; });
  });
})();
</script>
