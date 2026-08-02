<?php $editando = !empty($cliente['id']); ?>
<div class="row justify-content-center">
<div class="col-lg-9">
<form method="POST" action="<?= url($editando ? '/clientes/' . $cliente['id'] : '/clientes') ?>">
  <?= csrf_field() ?>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Dados Pessoais</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Tipo</label>
          <select name="tipo" class="form-select" id="tipoPessoa">
            <option value="pf" <?= ($cliente['tipo']??'pf')==='pf'?'selected':'' ?>>Pessoa Física</option>
            <option value="pj" <?= ($cliente['tipo']??'')==='pj'?'selected':'' ?>>Pessoa Jurídica</option>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nome completo / Razão social *</label>
          <input type="text" name="nome" class="form-control" value="<?= e($cliente['nome'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Pessoa de contato</label>
          <input type="text" name="contato" id="clienteContato" class="form-control" placeholder="Contato" value="<?= e($cliente['contato'] ?? '') ?>" required>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold" id="lblCpfCnpj">CPF</label>
          <div class="input-group">
            <input type="text" name="cpf_cnpj" id="cpfCnpjInput" class="form-control" value="<?= e($cliente['cpf_cnpj'] ?? '') ?>">
            <button type="button" class="btn btn-outline-primary" id="btnBuscarCnpj" title="Buscar dados na Receita Federal pelo CNPJ" style="display:none">
              <i class="bi bi-cloud-download"></i> Receita
            </button>
          </div>
          <div class="form-text" id="cnpjMsg" style="display:none"></div>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">WhatsApp</label>
          <input type="text" name="whatsapp" class="form-control" placeholder="(00) 00000-0000" value="<?= e($cliente['whatsapp'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">E-mail</label>
          <input type="email" name="email" class="form-control" value="<?= e($cliente['email'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($cliente['telefone'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Data Nascimento</label>
          <input type="date" name="data_nascimento" class="form-control" value="<?= e($cliente['data_nascimento'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Origem</label>
          <select name="origem" class="form-select">
            <?php foreach (['balcao'=>'Balcão','telefone'=>'Telefone','whatsapp'=>'WhatsApp','site'=>'Site','indicacao'=>'Indicação','outro'=>'Outro'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($cliente['origem']??'balcao')===$v?'selected':'' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold d-block">Classificação</label>
          <?php $estr = (int)($cliente['estrelas'] ?? 0); ?>
          <div id="starRating" class="d-inline-flex align-items-center gap-1" style="font-size:1.35rem;line-height:1">
            <?php for ($i=1;$i<=5;$i++): ?>
              <i class="bi bi-star<?= $i<=$estr?'-fill':'' ?> star-pick" data-v="<?= $i ?>" style="cursor:pointer;color:#f59e0b" title="<?= $i ?> estrela<?= $i>1?'s':'' ?>"></i>
            <?php endfor; ?>
            <a href="#" id="starClear" class="ms-2 text-muted text-decoration-none" style="font-size:.75rem">limpar</a>
          </div>
          <input type="hidden" name="estrelas" id="estrelasInput" value="<?= $estr ?>">
          <div class="form-text" style="font-size:.72rem">Cliente de confiança × problemático.</div>
        </div>
        <?php if ($editando): ?>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Status</label>
          <select name="status" class="form-select">
            <option value="ativo" <?= ($cliente['status']??'')==='ativo'?'selected':'' ?>>Ativo</option>
            <option value="inativo" <?= ($cliente['status']??'')==='inativo'?'selected':'' ?>>Inativo</option>
            <option value="bloqueado" <?= ($cliente['status']??'')==='bloqueado'?'selected':'' ?>>Bloqueado</option>
          </select>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Endereço</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">CEP</label>
          <input type="text" name="cep" class="form-control" id="cepInput" placeholder="00000-000" value="<?= e($cliente['cep'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" id="logradouro" value="<?= e($cliente['logradouro'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Número</label>
          <input type="text" name="numero" class="form-control" value="<?= e($cliente['numero'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Complemento</label>
          <input type="text" name="complemento" class="form-control" value="<?= e($cliente['complemento'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Bairro</label>
          <input type="text" name="bairro" class="form-control" id="bairro" value="<?= e($cliente['bairro'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Cidade</label>
          <input type="text" name="cidade" class="form-control" id="cidade" value="<?= e($cliente['cidade'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">UF</label>
          <input type="text" name="uf" class="form-control" id="uf" maxlength="2" value="<?= e($cliente['uf'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Observações</div>
    <div class="card-body">
      <textarea name="observacoes" class="form-control" rows="3"><?= e($cliente['observacoes'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/clientes') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary"><i class="bi bi-check-lg"></i> <?= $editando ? 'Salvar Alterações' : 'Cadastrar Cliente' ?></button>
  </div>
</form>
</div>
</div>

<?php /* CEP e máscaras aplicados globalmente pelo masks.js */ ?>

<script>
(function () {
  const inp  = document.getElementById('cpfCnpjInput');
  const btn  = document.getElementById('btnBuscarCnpj');
  const tipo = document.getElementById('tipoPessoa');
  const msg  = document.getElementById('cnpjMsg');
  if (!inp || !btn) return;

  function toggleBtn() { btn.style.display = (tipo && tipo.value === 'pj') ? '' : 'none'; }
  if (tipo) tipo.addEventListener('change', toggleBtn);
  toggleBtn();

  function setMsg(t, cls) { msg.textContent = t; msg.className = 'form-text ' + (cls || ''); msg.style.display = t ? '' : 'none'; }
  function put(name, val) { if (val == null || val === '') return; const el = document.querySelector('[name="' + name + '"]'); if (el) el.value = val; }

  btn.addEventListener('click', async function () {
    const cnpj = (inp.value || '').replace(/\D/g, '');
    if (cnpj.length !== 14) { setMsg('Informe um CNPJ com 14 dígitos.', 'text-danger'); return; }
    const original = btn.innerHTML;
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    setMsg('Consultando a Receita...', 'text-muted');
    try {
      const r = await fetch('<?= url('/api/cnpj/') ?>' + cnpj, { headers: { 'Accept': 'application/json' } });
      const d = await r.json();
      if (!d.success) { setMsg(d.error || 'CNPJ não encontrado.', 'text-danger'); return; }
      put('nome', d.razao_social);
      put('email', d.email);
      put('telefone', d.telefone);
      put('whatsapp', d.telefone);
      put('cep', d.cep);
      put('logradouro', d.logradouro);
      put('numero', d.numero);
      put('complemento', d.complemento);
      put('bairro', d.bairro);
      put('cidade', d.cidade);
      put('uf', d.uf);
      setMsg('✓ ' + d.razao_social + (d.situacao ? ' · ' + d.situacao : ''), 'text-success');
    } catch (e) {
      setMsg('Erro de conexão. Tente novamente.', 'text-danger');
    } finally {
      btn.disabled = false; btn.innerHTML = original;
    }
  });
})();
</script>

<script>
// Contato padrão = primeiro nome do cliente, quando o campo está vazio
(function () {
  var nome = document.querySelector('[name="nome"]');
  var contato = document.getElementById('clienteContato');
  if (!nome || !contato) return;
  function preencher() {
    if (!contato.value.trim() && nome.value.trim()) {
      var p = nome.value.trim().split(/\s+/)[0];
      contato.value = p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
    }
  }
  nome.addEventListener('blur', preencher);
  preencher();
})();
</script>

<script>
// Widget de classificação por estrelas
(function () {
  var wrap = document.getElementById('starRating');
  if (!wrap) return;
  var inp = document.getElementById('estrelasInput');
  var stars = wrap.querySelectorAll('.star-pick');
  function paint(n) {
    stars.forEach(function (s) {
      s.className = 'bi bi-star' + (parseInt(s.dataset.v, 10) <= n ? '-fill' : '') + ' star-pick';
    });
  }
  stars.forEach(function (s) {
    s.addEventListener('mouseenter', function () { paint(parseInt(s.dataset.v, 10)); });
    s.addEventListener('click', function () {
      var v = parseInt(s.dataset.v, 10);
      // clicar na estrela já marcada zera (toggle)
      if (parseInt(inp.value, 10) === v) v = 0;
      inp.value = v; paint(v);
    });
  });
  wrap.addEventListener('mouseleave', function () { paint(parseInt(inp.value, 10) || 0); });
  var clr = document.getElementById('starClear');
  if (clr) clr.addEventListener('click', function (e) { e.preventDefault(); inp.value = 0; paint(0); });
})();
</script>
