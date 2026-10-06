<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$mesLabel = $mesesPt[(int) date('n')] . ' de ' . date('Y');
?>

<?php if (!$liberado): ?>
<div class="fp-card" style="text-align:center;padding:40px 24px">
  <div style="font-size:2.2rem;margin-bottom:10px">🔒</div>
  <h1 style="font-size:1.15rem;margin:0 0 8px">Financeiro pessoal ainda não está liberado</h1>
  <p class="fp-muted" style="font-size:.9rem;line-height:1.5;margin:0 0 20px">
    Esse recurso é liberado pra empresas que <strong>reivindicaram</strong> a ficha no Diretório
    e estão nos planos <strong>Oficina</strong> ou <strong>Top Empresa</strong>. Fale com a FixaOS
    se quiser saber mais.
  </p>
  <div style="display:flex;flex-direction:column;gap:10px;max-width:280px;margin:0 auto">
    <a href="<?= url('/logout') ?>" class="fp-btn fp-btn-primary" style="text-decoration:none;display:inline-block">
      Entrar com outra conta
    </a>
    <a href="<?= url('/assistencias') ?>" class="fp-btn fp-btn-ghost" style="text-decoration:none;display:inline-block">
      Ver o Diretório
    </a>
  </div>
</div>

<?php else: ?>

<div class="fp-card" style="margin-bottom:16px">
  <div class="fp-muted" style="font-size:.8rem;margin-bottom:4px">Gasto em <?= e($mesLabel) ?></div>
  <div id="fpTotalMes" class="fp-mono" style="font-weight:700;font-size:1.9rem;color:var(--despesa)" data-valor="<?= (float) $totalMes ?>">
    R$ <?= number_format($totalMes, 2, ',', '.') ?>
  </div>
</div>

<form id="fpForm" class="fp-card" style="margin-bottom:16px;display:flex;flex-direction:column;gap:10px">
  <?= csrf_field() ?>
  <div id="fpEditandoAviso" class="fp-mono" style="display:none;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--accent);background:rgba(255,107,71,.1);border:1px solid rgba(255,107,71,.3);border-radius:10px;padding:8px 12px">
    <span>✎ Editando lançamento</span>
    <a href="#" id="fpCancelarEdicao" style="color:var(--text-muted);text-decoration:underline">cancelar</a>
  </div>
  <div style="display:flex;gap:8px">
    <button type="button" class="fp-btn fp-btn-primary" id="fpTipoDespesa" data-tipo="despesa" style="flex:1">Gasto</button>
    <button type="button" class="fp-btn fp-btn-ghost" id="fpTipoReceita" data-tipo="receita" style="flex:1">Entrada</button>
  </div>
  <input type="hidden" name="tipo" id="fpTipo" value="despesa">

  <input type="text" name="descricao" id="fpDescricao" class="fp-input" placeholder="Descrição (ex.: Supermercado)" maxlength="150" required>

  <div class="fp-row-valor-cat">
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

<div style="display:flex;gap:10px;align-items:flex-start">
  <div class="fp-filtros">
    <button type="button" class="fp-filtro-btn active" data-filtro="todos" title="Todos"><i class="bi bi-list-ul"></i><span>Todos</span></button>
    <button type="button" class="fp-filtro-btn" data-filtro="receita" title="Entradas"><i class="bi bi-arrow-down-circle-fill"></i><span>Entradas</span></button>
    <button type="button" class="fp-filtro-btn" data-filtro="despesa" title="Saídas"><i class="bi bi-arrow-up-circle-fill"></i><span>Saídas</span></button>
  </div>
  <div style="flex:1;min-width:0">
    <div class="fp-muted" style="font-size:.78rem;margin-bottom:8px;text-transform:uppercase;letter-spacing:.03em">Lançamentos</div>
    <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
  </div>
</div>

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
  var lancamentosAtuais = [];
  var filtroAtivo = 'todos';
  var editandoId = null;
  var editandoAviso = document.getElementById('fpEditandoAviso');
  var TEXTO_SALVAR_NOVO = 'Adicionar lançamento';
  var TEXTO_SALVAR_EDICAO = 'Salvar alterações';

  document.querySelectorAll('.fp-filtro-btn').forEach(function (btn) {
    btn.onclick = function () {
      filtroAtivo = btn.dataset.filtro;
      document.querySelectorAll('.fp-filtro-btn').forEach(function (b) { b.classList.toggle('active', b === btn); });
      aplicarFiltroEExibir();
    };
  });

  function marcarTipo(tipo) {
    tipoHidden.value = tipo;
    btnDespesa.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-ghost');
    btnReceita.className = 'fp-btn ' + (tipo === 'receita' ? 'fp-btn-receita' : 'fp-btn-ghost');
    // Botão de salvar também acompanha a cor do tipo escolhido — mesmo sistema de cores do
    // resto da tela, reforça antes do clique se o que vai ser salvo é saída ou entrada.
    btnSalvar.className = 'fp-btn ' + (tipo === 'despesa' ? 'fp-btn-despesa' : 'fp-btn-receita');
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

  function mesAtualStr() {
    return new Date().toISOString().slice(0, 7);
  }

  // Total do card "Gasto em {mês}" é sempre a soma de DESPESAS do mês, independente do
  // filtro lateral escolhido — é um KPI fixo, não deve mudar só porque o usuário clicou em
  // "Entradas" pra olhar a lista.
  function atualizarTotalMes() {
    var mesAtual = mesAtualStr();
    var total = 0;
    lancamentosAtuais.forEach(function (l) {
      if (l.data_hora.slice(0, 7) === mesAtual && l.tipo === 'despesa') total += parseFloat(l.valor);
    });
    totalEl.textContent = fmtValor(total);
  }

  var MSG_VAZIO = {
    todos: 'Nenhum lançamento em ' + '<?= e($mesLabel) ?>' + ' ainda — adicione o primeiro acima.',
    receita: 'Nenhuma entrada em ' + '<?= e($mesLabel) ?>' + ' ainda.',
    despesa: 'Nenhuma saída em ' + '<?= e($mesLabel) ?>' + ' ainda.'
  };

  // Filtra lancamentosAtuais pelo mês atual + pelo tipo escolhido na lateral (todos/receita/
  // despesa), depois manda renderizar só isso — a lista em si não sabe de filtro nenhum.
  function aplicarFiltroEExibir() {
    var mesAtual = mesAtualStr();
    var filtrados = lancamentosAtuais.filter(function (l) {
      if (l.data_hora.slice(0, 7) !== mesAtual) return false;
      if (filtroAtivo === 'todos') return true;
      return l.tipo === filtroAtivo;
    });
    renderLista(filtrados);
  }

  function renderLista(lancamentos) {
    lista.innerHTML = '';
    if (!lancamentos.length) {
      lista.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;font-size:.88rem">' + MSG_VAZIO[filtroAtivo] + '</div>';
      return;
    }
    lancamentos.forEach(function (l) {
      var cat = CATS[l.categoria] || { nome: l.categoria, cor: '#8C7A9E' };
      // Dot = categoria (identifica o quê); borda esquerda + cor do valor = tipo (identifica
      // se saiu ou entrou) — dois sinais de cor independentes, cada um respondendo uma
      // pergunta diferente ao olhar a linha.
      var tipoCor = l.tipo === 'receita' ? 'var(--receita)' : 'var(--despesa)';
      var row = document.createElement('div');
      row.className = 'fp-card';
      row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:14px 16px;border-left:3px solid ' + tipoCor;
      row.innerHTML =
        '<span style="width:10px;height:10px;border-radius:50%;background:' + cat.cor + ';flex:0 0 auto"></span>' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(l.descricao) + '</div>' +
          '<div class="fp-muted fp-mono" style="font-size:.74rem;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(cat.nome) + ' · ' + fmtData(l.data_hora) + (l.origem === 'foto' ? ' · 📷' : '') + '</div>' +
        '</div>' +
        '<div class="fp-mono" style="font-weight:700;font-size:.95rem;color:' + tipoCor + '">' +
          (l.tipo === 'receita' ? '+' : '−') + fmtValor(l.valor) +
        '</div>' +
        '<div style="display:flex;gap:2px;flex:0 0 auto">' +
          '<button type="button" aria-label="Editar" data-id="' + l.id + '" class="fp-edit" style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:.95rem;padding:4px"><i class="bi bi-pencil-fill"></i></button>' +
          '<button type="button" aria-label="Excluir" data-id="' + l.id + '" class="fp-del" style="background:transparent;border:none;color:var(--text-muted);cursor:pointer;font-size:1.1rem;padding:4px">×</button>' +
        '</div>';
      lista.appendChild(row);
    });
    lista.querySelectorAll('.fp-del').forEach(function (btn) {
      btn.onclick = function () { excluir(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-edit').forEach(function (btn) {
      btn.onclick = function () { iniciarEdicao(btn.dataset.id); };
    });
  }

  // Preenche o form com o lançamento escolhido e muda pro "modo edição" — iniciarEdicao() só
  // lê de lancamentosAtuais (já carregado), não busca de novo no servidor.
  function iniciarEdicao(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    editandoId = id;
    marcarTipo(l.tipo);
    document.getElementById('fpDescricao').value = l.descricao;
    document.getElementById('fpValor').value = l.valor;
    document.getElementById('fpCategoria').value = l.categoria;
    editandoAviso.style.display = 'flex';
    btnSalvar.textContent = TEXTO_SALVAR_EDICAO;
    msg.textContent = '';
    form.scrollIntoView({ behavior: 'smooth', block: 'start' });
  }

  function cancelarEdicao() {
    editandoId = null;
    form.reset();
    marcarTipo('despesa');
    editandoAviso.style.display = 'none';
    btnSalvar.textContent = TEXTO_SALVAR_NOVO;
    msg.textContent = '';
  }

  document.getElementById('fpCancelarEdicao').onclick = function (ev) {
    ev.preventDefault();
    cancelarEdicao();
  };

  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
  }

  function carregar() {
    fetch('<?= url('/api/financeiro-pessoal') ?>')
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        lancamentosAtuais = j.lancamentos;
        atualizarTotalMes();
        aplicarFiltroEExibir();
      });
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
    var emEdicao = editandoId !== null;
    btnSalvar.disabled = true;
    var orig = btnSalvar.textContent;
    btnSalvar.textContent = 'Salvando...';
    msg.textContent = '';

    var url = emEdicao
      ? '<?= url('/financeiro-pessoal') ?>/' + editandoId + '/atualizar'
      : '<?= url('/financeiro-pessoal') ?>';

    fetch(url, {
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
        if (!j.ok) {
          btnSalvar.textContent = orig;
          msg.innerHTML = '<span style="color:#F2A0A0">' + (j.erro || 'Não deu pra salvar agora.') + '</span>';
          return;
        }
        if (emEdicao) {
          cancelarEdicao();
        } else {
          form.reset();
          marcarTipo('despesa');
          btnSalvar.textContent = orig;
        }
        carregar();
      })
      .catch(function () {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        msg.innerHTML = '<span style="color:#F2A0A0">Falha de conexão, tenta de novo.</span>';
      });
  });

  marcarTipo('despesa');
  lancamentosAtuais = <?= json_encode($lancamentos, JSON_UNESCAPED_UNICODE) ?>;
  atualizarTotalMes();
  aplicarFiltroEExibir();
})();
</script>
<?php endif; ?>
