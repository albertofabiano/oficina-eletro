<div class="container-fluid" style="max-width:720px">
  <h4 class="mb-1"><i class="bi bi-phone-vibrate me-2 text-primary"></i>Consulta de IMEI</h4>
  <p class="text-muted small">Ao inserir o IMEI no cadastro do equipamento, o sistema preenche marca/modelo sozinho e checa bloqueio/roubo. Agnóstico de fornecedor: você cola a URL (com <code>{imei}</code> e <code>{key}</code>) e a chave. Fica só no servidor — nunca vai pro Git.</p>

  <?php $ok = flash('success'); $err = flash('error'); ?>
  <?php if ($ok): ?><div class="alert alert-success py-2"><?= e($ok) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger py-2"><?= e($err) ?></div><?php endif; ?>

  <form method="POST" action="<?= url('/master/imei') ?>" class="card border-0 shadow-sm">
    <div class="card-body">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label small fw-semibold">URL da API (template)</label>
        <input type="text" name="api_url" class="form-control" value="<?= e($apiUrl) ?>"
               placeholder="https://api.exemplo.com/check?imei={imei}&token={key}">
        <div class="form-text">Use <code>{imei}</code> onde entra o número e <code>{key}</code> onde entra a chave. Ex. (confira no painel do fornecedor): imei.info / imeicheck.com.</div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold">Chave da API</label>
        <input type="password" name="api_key" class="form-control" autocomplete="off"
               placeholder="<?= $apiKeySet ? '•••••••• (já configurada — cole uma nova só se quiser trocar)' : 'Cole aqui a chave da API' ?>">
        <div class="form-text">
          <?php if ($apiKeySet): ?><span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>Chave configurada.</span> Deixe em branco para manter a atual.
          <?php else: ?><span class="text-warning"><i class="bi bi-exclamation-triangle-fill me-1"></i>Nenhuma chave ainda.</span><?php endif; ?>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label small fw-semibold">Limite de consultas por empresa / mês</label>
          <input type="number" name="limite_mes" class="form-control" min="0" value="<?= (int) $limiteMes ?>">
          <div class="form-text">Protege seu bolso contra abuso. <strong>0 = ilimitado.</strong> O cache não conta (é grátis) — só chamadas novas à API.</div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="ativo" value="1" id="imeiAtivo" <?= $flagAtivo ? 'checked' : '' ?>>
            <label class="form-check-label fw-semibold" for="imeiAtivo">Consulta ativa</label>
          </div>
        </div>
      </div>

      <div class="mt-2">
        <?php if ($ativo): ?><span class="badge bg-success"><i class="bi bi-check-circle-fill me-1"></i>No ar</span>
        <?php else: ?><span class="badge bg-secondary">Desligada (falta URL/chave ou o toggle)</span><?php endif; ?>
      </div>

      <div class="d-flex gap-2 mt-4">
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
        <button type="button" class="btn btn-outline-success" id="btnTestar"><i class="bi bi-plug me-1"></i>Testar (consome 1 consulta)</button>
        <span id="testResult" class="align-self-center small"></span>
      </div>
    </div>
  </form>

  <div class="alert alert-light border mt-3 small">
    <i class="bi bi-info-circle me-1"></i>
    <strong>Estratégia atual:</strong> grátis pros clientes por enquanto (o custo é seu, ~R$0,05/consulta, e o cache derruba pra quase zero). O botão <strong>"Consultar na Anatel"</strong> no cadastro já funciona de graça, sem chave. O gancho pra virar recurso <strong>premium</strong> depois já está pronto — é só uma flag quando você decidir.
  </div>
</div>

<script>
document.getElementById('btnTestar')?.addEventListener('click', function(){
  var btn = this, out = document.getElementById('testResult');
  btn.disabled = true; out.innerHTML = '<span class="text-muted">Testando…</span>';
  fetch('<?= url('/master/imei/testar') ?>', { method:'POST', headers:{ 'X-CSRF-Token':'<?= csrf_token() ?>' } })
    .then(function(r){ return r.json(); })
    .then(function(j){
      out.innerHTML = j.ok
        ? '<span class="text-success"><i class="bi bi-check-circle-fill me-1"></i>OK! ' + [j.marca, j.modelo].filter(Boolean).join(' ') + '</span>'
        : '<span class="text-danger"><i class="bi bi-x-circle-fill me-1"></i>' + (j.erro||'Falhou') + '</span>';
    })
    .catch(function(){ out.innerHTML = '<span class="text-danger">Erro ao testar.</span>'; })
    .finally(function(){ btn.disabled = false; });
});
</script>
