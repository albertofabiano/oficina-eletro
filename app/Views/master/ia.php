<div class="container-fluid" style="max-width:720px">
  <h4 class="mb-1"><i class="bi bi-robot me-2 text-danger"></i>IA — Bot de Suporte</h4>
  <p class="text-muted small">Chave da API Anthropic (Claude) que alimenta o bot de suporte por WhatsApp. Fica guardada só no servidor — nunca vai pro Git nem aparece na tela.</p>

  <?php $ok = flash('success'); $err = flash('error'); ?>
  <?php if ($ok): ?><div class="alert alert-success py-2"><?= e($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endif; ?>

  <form method="POST" action="<?= url('/master/ia') ?>" class="card border-0 shadow-sm">
    <div class="card-body">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label small fw-semibold">Chave da API (sk-ant-...)</label>
        <input type="password" name="api_key" class="form-control" autocomplete="off"
               placeholder="<?= $apiKeySet ? '•••••••• (já configurada — cole uma nova só se quiser trocar)' : 'Cole aqui a chave sk-ant-...' ?>">
        <div class="form-text">
          <?php if ($apiKeySet): ?><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Chave configurada.</span> Deixe em branco para manter a atual.
          <?php else: ?><span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Nenhuma chave ainda.</span> Cole a que você gerou no console da Anthropic.<?php endif; ?>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label small fw-semibold">Modelo</label>
          <input type="text" name="modelo" class="form-control" value="<?= e($modelo) ?>">
          <div class="form-text">Padrão econômico: <code>claude-haiku-4-5-20251001</code> (barato e rápido, ótimo p/ suporte).</div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="iaAtivo" <?= $ativo ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="iaAtivo">Bot ativo</label>
          </div>
        </div>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
        <button type="button" class="btn btn-outline-success" id="btnTestar"><i class="bi bi-plug me-1"></i>Testar conexão</button>
        <span id="testResult" class="align-self-center small"></span>
      </div>
    </div>
  </form>

  <div class="alert alert-light border mt-3 small">
    <i class="bi bi-info-circle me-1"></i>Depois de salvar a chave e o teste dar <strong>OK</strong>, o próximo passo é eu construir o bot: webhook de entrada do WhatsApp → IA com a Base de Conhecimento → resposta + handoff pra humano.
  </div>
</div>

<script>
document.getElementById('btnTestar')?.addEventListener('click', function(){
  var btn = this, out = document.getElementById('testResult');
  btn.disabled = true; out.innerHTML = '<span class="text-muted">Testando…</span>';
  fetch('<?= url('/master/ia/testar') ?>', { method:'POST', headers:{ 'X-CSRF-Token':'<?= csrf_token() ?>' } })
    .then(function(r){ return r.json(); })
    .then(function(j){
      out.innerHTML = j.ok
        ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Conexão OK! ' + (j.texto||'') + '</span>'
        : '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>' + (j.erro||'Falhou') + '</span>';
    })
    .catch(function(){ out.innerHTML = '<span class="text-danger">Erro ao testar.</span>'; })
    .finally(function(){ btn.disabled = false; });
});
</script>
