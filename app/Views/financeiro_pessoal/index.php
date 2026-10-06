<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$mesLabel = $mesesPt[(int) date('n')] . ' de ' . date('Y');
?>

<?php if (!$liberado): ?>
<div class="fp-card" style="text-align:center;padding:40px 24px">
  <div style="font-size:2.2rem;margin-bottom:10px">🔒</div>
  <h1 style="font-size:1.15rem;margin:0 0 8px">Financeiro pessoal ainda não está liberado</h1>
  <p class="fp-muted" style="font-size:.9rem;line-height:1.5;margin:0">
    Esse recurso é liberado pra empresas que <strong>reivindicaram</strong> a ficha no Diretório
    e estão nos planos <strong>Oficina</strong> ou <strong>Top Empresa</strong>. Fale com a FixaOS
    se quiser saber mais.
  </p>
</div>

<?php else: ?>

<div class="fp-card" style="margin-bottom:16px">
  <div class="fp-muted" style="font-size:.8rem;margin-bottom:4px">Gasto em <?= e($mesLabel) ?></div>
  <div id="fpTotalMes" class="fp-mono" style="font-weight:700;font-size:1.9rem" data-valor="<?= (float) $totalMes ?>">
    R$ <?= number_format($totalMes, 2, ',', '.') ?>
  </div>
</div>

<form id="fpForm" class="fp-card" style="margin-bottom:16px;display:flex;flex-direction:column;gap:10px">
  <?= csrf_field() ?>
  <div style="display:flex;gap:8px">
    <button type="button" class="fp-btn fp-btn-primary" id="fpTipoDespesa" data-tipo="despesa" style="flex:1">Gasto</button>
    <button type="button" class="fp-btn fp-btn-ghost" id="fpTipoReceita" data-tipo="receita" style="flex:1">Entrada</button>
  </div>
  <input type="hidden" name="tipo" id="fpTipo" value="despesa">

  <input type="text" name="descricao" id="fpDescricao" class="fp-input" placeholder="Descrição (ex.: Supermercado)" maxlength="150" required>

  <div style="display:flex;gap:8px">
    <input type="number" name="valor" id="fpValor" class="fp-input" placeholder="Valor (R$)" step="0.01" min="0.01" style="flex:1" required>
    <select name="categoria" id="fpCategoria" class="fp-select" style="flex:1">
      <?php foreach ($categorias as $chave => $c): ?>
      <option value="<?= e($chave) ?>"><?= e($c['nome']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>

  <div id="fpMsg" class="fp-muted" style="font-size:.82rem"></div>

  <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvar">Adicionar lançamento</button>
</form>

<div class="fp-muted" style="font-size:.78rem;margin-bottom:8px;text-transform:uppercase;letter-spacing:.03em">Lançamentos</div>
<div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>

<script>
(function () {
  var CATS = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
  var lista = document.getElementById('fpLista');
  var totalEl = document.getElementById('fpTotalMes');
  var form = document.getElementById('fpForm');
  var msg = document.getElementById('fpMsg');
  var btnSalvar = document.getElementById('fpBtnSalvar');
  var tipoHidden = document.getElementById('fpTipo');
  var btnDespesa = document.getElementById('fpTipoDespesa');
  var btnReceita = document.getElementById('fpTipoReceita');
  var csrfToken = '<?= csrf_token() ?>';

  function marcarTipo(tipo) {
    tipoHidden.value = tipo;
    btnDespesa.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-primary' : 'fp-btn-ghost');
    btnReceita.className = 'fp-btn ' + (tipo === 'receita' ? 'fp-btn-primary' : 'fp-btn-ghost');
  }
  btnDespesa.onclick = function () { marcarTipo('despesa'); };
  btnReceita.onclick = function () { marcarTipo('receita'); };

  function fmtValor(v) {
    return 'R$ ' + Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }
  function fmtData(dt) {
    var d = new Date(dt.replace(' ', 'T'));
    if (isNaN(d.getTime())) return dt;
    return d.toLocaleDateString('pt-BR') + ' · ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
  }

  function renderLista(lancamentos) {
    lista.innerHTML = '';
    if (!lancamentos.length) {
      lista.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;font-size:.88rem">Nenhum lançamento ainda — adicione o primeiro acima.</div>';
      return;
    }
    var mesAtual = new Date().toISOString().slice(0, 7);
    var total = 0;
    lancamentos.forEach(function (l) {
      if (l.data_hora.slice(0, 7) === mesAtual && l.tipo === 'despesa') total += parseFloat(l.valor);

      var cat = CATS[l.categoria] || { nome: l.categoria, cor: '#8C7A9E' };
      var row = document.createElement('div');
      row.className = 'fp-card';
      row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:14px 16px';
      row.innerHTML =
        '<span style="width:10px;height:10px;border-radius:50%;background:' + cat.cor + ';flex:0 0 auto"></span>' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(l.descricao) + '</div>' +
          '<div class="fp-muted fp-mono" style="font-size:.74rem;margin-top:2px">' + escapeHtml(cat.nome) + ' · ' + fmtData(l.data_hora) + (l.origem === 'foto' ? ' · 📷' : '') + '</div>' +
        '</div>' +
        '<div class="fp-mono" style="font-weight:700;font-size:.95rem;color:' + (l.tipo === 'receita' ? '#7FD9C4' : '#F5EFFA') + '">' +
          (l.tipo === 'receita' ? '+' : '−') + fmtValor(l.valor) +
        '</div>' +
        '<button type="button" aria-label="Excluir" data-id="' + l.id + '" class="fp-del" style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem;padding:4px">×</button>';
      lista.appendChild(row);
    });
    totalEl.textContent = fmtValor(total);
    lista.querySelectorAll('.fp-del').forEach(function (btn) {
      btn.onclick = function () { excluir(btn.dataset.id); };
    });
  }

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function carregar() {
    fetch('<?= url('/api/financeiro-pessoal') ?>')
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.ok) renderLista(j.lancamentos); });
  }

  function excluir(id) {
    if (!confirm('Excluir esse lançamento?')) return;
    fetch('<?= url('/financeiro-pessoal') ?>/' + id + '/excluir', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken }
    }).then(function () { carregar(); });
  }

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var descricao = document.getElementById('fpDescricao').value.trim();
    var valor = document.getElementById('fpValor').value;
    if (!descricao || !valor || parseFloat(valor) <= 0) {
      msg.innerHTML = '<span style="color:#F2A0A0">Preencha descrição e um valor válido.</span>';
      return;
    }
    btnSalvar.disabled = true;
    var orig = btnSalvar.textContent;
    btnSalvar.textContent = 'Salvando...';
    msg.textContent = '';

    fetch('<?= url('/financeiro-pessoal') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({
        tipo: tipoHidden.value,
        categoria: document.getElementById('fpCategoria').value,
        descricao: descricao,
        valor: valor
      })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        if (!j.ok) { msg.innerHTML = '<span style="color:#F2A0A0">' + (j.erro || 'Não deu pra salvar agora.') + '</span>'; return; }
        form.reset();
        marcarTipo('despesa');
        carregar();
      })
      .catch(function () {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        msg.innerHTML = '<span style="color:#F2A0A0">Falha de conexão, tenta de novo.</span>';
      });
  });

  marcarTipo('despesa');
  renderLista(<?= json_encode($lancamentos, JSON_UNESCAPED_UNICODE) ?>);
})();
</script>
<?php endif; ?>
