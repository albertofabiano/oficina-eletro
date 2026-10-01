<?php
// Converte a marcação *negrito* do WhatsApp (texto puro) pra HTML, só pra exibir na prévia —
// mesma técnica (escapar primeiro, só depois trocar *texto* por <b>) usada quando essa prévia
// foi montada manualmente no chat antes de virar parte do painel.
$nsWaParaHtml = function (string $t): string {
    $t = e($t);
    $t = preg_replace('/\*(.+?)\*/s', '<b>$1</b>', $t);
    return nl2br($t);
};
?>
<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-megaphone me-2 text-primary"></i>Novidades do Sistema</h4>
  <p class="text-muted small mb-4" style="max-width:820px">
    Lugar único pra avisar a base de clientes já cadastrada (<code>reivindicada = 1</code> —
    tanto quem assina o sistema completo/está em trial quanto quem só reivindicou o Diretório
    grátis) sobre novidades e atualizações do FixaOS, por e-mail e por WhatsApp. Não inclui as
    fichas importadas de CNPJ sem conta nenhuma (essas usam os e-mails de prospecção/
    reivindicação, não este). O conteúdo é trocado a cada rodada de novidade — a rodada atual é
    <strong>"Pedir avaliação no Google"</strong> (<code>EmailService::novidadesSistema()</code> /
    <code>WhatsAppService::novidadesSistema()</code>). Marque quem deve receber em cada lista
    (ou use "selecionar todas") — quem já recebeu esta rodada ou não tem contato cadastrado fica
    de fora do envio mesmo que marcado.
  </p>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Empresas no sistema</div>
        <div class="fs-3 fw-bold"><?= (int) $empresasBase ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-envelope-paper me-1"></i>Já receberam por e-mail</div>
        <div class="fs-3 fw-bold text-success"><?= (int) $jaEnviadosEmail ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small"><i class="bi bi-whatsapp me-1"></i>Já receberam por WhatsApp</div>
        <div class="fs-3 fw-bold text-success"><?= (int) $jaEnviadosWhatsapp ?></div>
      </div></div>
    </div>
  </div>

  <!-- ── E-mail ───────────────────────────────────────────── -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-envelope-paper me-1 text-primary"></i>E-mail — <?= count($listaEmail) ?> contato(s)</span>
      <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#modalPreviewEmail">
        <i class="bi bi-eye me-1"></i>Ver a mensagem
      </button>
    </div>
    <form method="POST" action="<?= url('/master/novidades-sistema/disparar') ?>"
          onsubmit="return confirm('Enviar e-mail pros selecionados?');">
      <?= csrf_field() ?>
      <div class="table-responsive" style="max-height:420px">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light" style="position:sticky;top:0">
            <tr>
              <th style="width:36px"><input type="checkbox" class="form-check-input" onclick="nsToggleAll(this,'nsChkEmail')"></th>
              <th>#</th><th>Contato</th><th>E-mail</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($listaEmail as $e): ?>
            <tr>
              <td>
                <?php if (!empty($e['email']) && !$e['enviado']): ?>
                  <input type="checkbox" class="form-check-input nsChkEmail" name="ids[]" value="<?= (int) $e['id'] ?>">
                <?php else: ?>
                  <input type="checkbox" class="form-check-input" disabled>
                <?php endif; ?>
              </td>
              <td><?= (int) $e['id'] ?></td>
              <td><?= e($e['nome_contato']) ?></td>
              <td><?= $e['email'] ? e($e['email']) : '<span class="text-muted">sem e-mail</span>' ?></td>
              <td>
                <?php if (empty($e['email'])): ?>
                  <span class="text-muted small">—</span>
                <?php elseif ($e['enviado']): ?>
                  <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>já recebeu</span>
                <?php else: ?>
                  <span class="text-muted small">pendente</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-body d-flex justify-content-end border-top">
        <button class="btn btn-sm btn-primary"><i class="bi bi-send me-1"></i>Enviar e-mail selecionados</button>
      </div>
    </form>
  </div>

  <!-- ── WhatsApp ─────────────────────────────────────────── -->
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center flex-wrap gap-2">
      <span><i class="bi bi-whatsapp me-1 text-success"></i>WhatsApp — <?= count($listaWhatsapp) ?> contato(s)</span>
      <button type="button" class="btn btn-sm btn-link" data-bs-toggle="modal" data-bs-target="#modalPreviewWhatsapp">
        <i class="bi bi-eye me-1"></i>Ver a mensagem
      </button>
    </div>
    <form method="POST" action="<?= url('/master/novidades-sistema/disparar-whatsapp') ?>"
          onsubmit="return confirm('Enviar WhatsApp pros selecionados?');">
      <?= csrf_field() ?>
      <?php if (!empty($erroWhatsapp)): ?>
      <div class="alert alert-warning mb-0 mx-3 mt-2 py-2 small"><i class="bi bi-exclamation-triangle-fill me-1"></i><?= e($erroWhatsapp) ?></div>
      <?php endif; ?>
      <div class="text-muted small px-3 pt-2">
        Sai do número da <strong>plataforma</strong> (mesma instância do reset de senha por
        WhatsApp), não do WhatsApp de cada empresa.
      </div>
      <div class="table-responsive" style="max-height:420px">
        <table class="table table-sm table-hover mb-0">
          <thead class="table-light" style="position:sticky;top:0">
            <tr>
              <th style="width:36px"><input type="checkbox" class="form-check-input" onclick="nsToggleAll(this,'nsChkWhats')"></th>
              <th>#</th><th>Contato</th><th>Telefone</th><th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($listaWhatsapp as $e): ?>
            <tr>
              <td>
                <?php if (!empty($e['telefone']) && !$e['enviado']): ?>
                  <input type="checkbox" class="form-check-input nsChkWhats" name="ids[]" value="<?= (int) $e['id'] ?>">
                <?php else: ?>
                  <input type="checkbox" class="form-check-input" disabled>
                <?php endif; ?>
              </td>
              <td><?= (int) $e['id'] ?></td>
              <td><?= e($e['nome_contato']) ?></td>
              <td><?= $e['telefone'] ? e($e['telefone']) : '<span class="text-muted">sem telefone</span>' ?></td>
              <td>
                <?php if (empty($e['telefone'])): ?>
                  <span class="text-muted small">—</span>
                <?php elseif ($e['enviado']): ?>
                  <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>já recebeu</span>
                <?php else: ?>
                  <span class="text-muted small">pendente</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <div class="card-body d-flex justify-content-end border-top">
        <button class="btn btn-sm btn-success" <?= !empty($erroWhatsapp) ? 'disabled' : '' ?>><i class="bi bi-send me-1"></i>Enviar WhatsApp selecionados</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Prévia: E-mail (HTML real, dentro de um iframe — o mesmo que seria enviado) ── -->
<div class="modal fade" id="modalPreviewEmail" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-envelope-paper me-2 text-primary"></i>Prévia do e-mail</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="nsIframeEmail" style="width:100%;height:70vh;border:0" src="about:blank"></iframe>
      </div>
    </div>
  </div>
</div>

<!-- ── Prévia: WhatsApp (texto formatado + print, igual as 2 mensagens reais) ── -->
<div class="modal fade" id="modalPreviewWhatsapp" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-whatsapp me-2 text-success"></i>Prévia do WhatsApp</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" style="background:#0b141a;background-image:radial-gradient(#182229 1px,transparent 1px);background-size:14px 14px;padding:18px">
        <div style="background:#005c4b;color:#e9edef;border-radius:8px;padding:8px 10px;font-size:14px;line-height:1.45;max-width:92%;margin-left:auto">
          <?= $nsWaParaHtml($previewWhatsapp) ?>
        </div>
        <div style="background:#005c4b;border-radius:8px;padding:4px;max-width:92%;margin:10px 0 0 auto">
          <img src="<?= url('/img/screenshots/os-botao-avaliacao-google.webp') ?>" style="width:100%;display:block;border-radius:5px 5px 0 0">
          <div style="color:#e9edef;font-size:13px;padding:6px 4px 2px">É aqui que o botão aparece, na tela de qualquer OS.</div>
        </div>
      </div>
      <div class="modal-footer">
        <small class="text-muted me-auto">São 2 mensagens — texto, depois a foto, como no WhatsApp de verdade.</small>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Fechar</button>
      </div>
    </div>
  </div>
</div>

<script>
function nsToggleAll(origem, classe) {
  document.querySelectorAll('.' + classe).forEach(function (chk) { chk.checked = origem.checked; });
}
document.getElementById('modalPreviewEmail').addEventListener('show.bs.modal', function () {
  document.getElementById('nsIframeEmail').src = '<?= url('/master/novidades-sistema/preview-email') ?>';
});
document.getElementById('modalPreviewEmail').addEventListener('hidden.bs.modal', function () {
  document.getElementById('nsIframeEmail').src = 'about:blank';
});
</script>
