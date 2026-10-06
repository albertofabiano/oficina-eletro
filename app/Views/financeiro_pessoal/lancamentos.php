<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];

$anoMesPartes = explode('-', $mes);
$mesLabel = $mesesPt[(int) $anoMesPartes[1]] . ' de ' . $anoMesPartes[0];
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

<div class="fp-page-header">
  <div>
    <h1 class="fp-greeting">Lançamentos</h1>
    <div class="fp-faint">Todas as entradas e saídas de <?= e($mesLabel) ?></div>
  </div>
  <nav class="fp-month-nav" aria-label="Navegar entre meses">
    <a href="<?= url('/financeiro-pessoal/lancamentos') ?>?mes=<?= e($mesAnteriorNav) ?>" class="fp-month-btn" aria-label="Mês anterior"><?= fp_icone('chevron-left') ?></a>
    <span class="fp-mono fp-month-label"><?= e(ucfirst($mesLabel)) ?></span>
    <a href="<?= url('/financeiro-pessoal/lancamentos') ?>?mes=<?= e($mesProximoNav) ?>" class="fp-month-btn" aria-label="Próximo mês"><?= fp_icone('chevron-right') ?></a>
  </nav>
</div>

<!-- Sem gatilho próprio de "criar" nesta tela (mesma decisão já tomada no Resumo, ver
     index.php) — este modal só abre pelo botão "Editar" de um lançamento já existente (ver
     iniciarEdicao() no script). Mesmos ids de sempre (fpForm/fpTipo*/fpDescricao/fpValor/
     fpCategoria/fpMsg/fpBtnSalvar/fpEditandoAviso/fpCancelarEdicao) — mesma lógica de JS de
     salvar/cancelar edição do Resumo, copiada aqui (as duas telas têm cada uma seu próprio
     <form>/modal, não compartilham DOM entre páginas). -->
<div class="fp-modal-backdrop" id="modalLancamento">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Lançamento</strong>
      <button type="button" class="fp-modal-close" id="btnFecharLancamento" aria-label="Fechar">×</button>
    </div>
    <form id="fpForm" style="display:flex;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <div id="fpEditandoAviso" class="fp-mono" style="display:none;align-items:center;justify-content:space-between;font-size:.8rem;color:var(--accent);background:var(--accentSoft);border:1px solid var(--accentLine);border-radius:10px;padding:8px 12px">
        <span>✎ Editando lançamento</span>
        <a href="#" id="fpCancelarEdicao" style="color:var(--muted);text-decoration:underline">cancelar</a>
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

      <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvar">Salvar alterações</button>
    </form>
  </div>
</div>

<section class="fp-lanc-col" aria-labelledby="fpLancTitulo">
  <div style="display:flex;gap:10px;align-items:flex-start">
    <div class="fp-filtros">
      <button type="button" class="fp-filtro-btn active" data-filtro="todos" title="Todos"><?= fp_icone('list-ul') ?><span>Todos</span></button>
      <button type="button" class="fp-filtro-btn" data-filtro="receita" title="Entradas"><?= fp_icone('arrow-down-circle-fill') ?><span>Entradas</span></button>
      <button type="button" class="fp-filtro-btn" data-filtro="despesa" title="Saídas"><?= fp_icone('arrow-up-circle-fill') ?><span>Saídas</span></button>
    </div>
    <div style="flex:1;min-width:0">
      <h2 id="fpLancTitulo" class="fp-section-titulo" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.03em;margin:0 0 8px">Lançamentos</h2>
      <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
    </div>
  </div>
</section>

<script>
(function () {
  var CATS = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
  var MES_SELECIONADO = <?= json_encode($mes) ?>;
  // Ícone usado dentro do HTML montado via JS (renderLista()) — mesmo fp_icone() do PHP, sem
  // CDN (área isolada do resto do FixaOS, só o login é compartilhado).
  var FP_SVG = {
    'chevron-down': <?= json_encode(fp_icone('chevron-down')) ?>
  };
  var lista = document.getElementById('fpLista');
  var modalLancamento = document.getElementById('modalLancamento');
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
  // Estado de colapso de cada lançamento (card individual, não um grupo) — só client-side
  // (não persiste entre recargas), chave id do lançamento. Mesmo comportamento já usado no
  // Resumo (ver index.php).
  var lancColapsados = {};
  var editandoAviso = document.getElementById('fpEditandoAviso');

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
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  function mesAtualStr() {
    return MES_SELECIONADO;
  }

  var MSG_VAZIO = {
    todos: 'Nenhum lançamento em ' + '<?= e($mesLabel) ?>' + ' ainda.',
    receita: 'Nenhuma entrada em ' + '<?= e($mesLabel) ?>' + ' ainda.',
    despesa: 'Nenhuma saída em ' + '<?= e($mesLabel) ?>' + ' ainda.'
  };

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
      var cat = CATS[l.categoria] || { nome: l.categoria, cor: 'var(--muted)' };
      var tipoCor = l.tipo === 'receita' ? 'var(--inc)' : 'var(--exp)';
      var aberto = !!lancColapsados[l.id];

      var card = document.createElement('div');
      card.className = 'fp-lanc-card';
      card.style.borderLeft = '3px solid ' + tipoCor;

      var header = document.createElement('div');
      header.className = 'fp-lanc-header';
      header.innerHTML =
        '<span style="width:10px;height:10px;border-radius:50%;background:' + cat.cor + ';flex:0 0 auto"></span>' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(l.descricao) + (l.origem === 'ocr' ? ' <span class="fp-chip fp-chip-muted" style="margin-left:4px">OCR</span>' : '') + '</div>' +
          '<div class="fp-faint fp-mono" style="font-size:.74rem;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(cat.nome) + ' · ' + fmtData(l.data_hora) + (l.origem === 'foto' ? ' · 📷' : '') + '</div>' +
        '</div>' +
        '<div class="fp-mono" style="font-weight:700;font-size:.95rem;color:' + tipoCor + '">' +
          (l.tipo === 'receita' ? '+' : '−') + fmtValor(l.valor) +
        '</div>' +
        '<span class="fp-lanc-chevron" aria-hidden="true" style="transform:rotate(' + (aberto ? '180' : '0') + 'deg)">' + FP_SVG['chevron-down'] + '</span>';
      // Mesmo padrão do Resumo: sem botão nenhum dentro do header, o clique inteiro alterna
      // expandir/recolher sem precisar checar o que foi clicado.
      header.onclick = function () {
        lancColapsados[l.id] = !aberto;
        renderLista(lancamentos);
      };
      card.appendChild(header);

      var corpo = document.createElement('div');
      corpo.className = 'fp-lanc-corpo' + (aberto ? ' show' : '');
      corpo.innerHTML =
        '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;padding-top:10px;border-top:1px solid var(--line)">' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-edit" data-id="' + l.id + '">Editar</button>' +
          '<a href="<?= url('/financeiro-pessoal/categorias') ?>" class="fp-btn fp-btn-ghost fp-btn-sm" style="flex:1;min-width:150px;text-decoration:none;text-align:center">Criar ou editar categoria</a>' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-del" data-id="' + l.id + '" style="color:var(--exp)">Excluir</button>' +
        '</div>';
      card.appendChild(corpo);

      lista.appendChild(card);
    });

    lista.querySelectorAll('.fp-del').forEach(function (btn) {
      btn.onclick = function () { excluir(btn.dataset.id); };
    });
    lista.querySelectorAll('.fp-edit').forEach(function (btn) {
      btn.onclick = function () { iniciarEdicao(btn.dataset.id); };
    });
  }

  function iniciarEdicao(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    editandoId = id;
    marcarTipo(l.tipo);
    document.getElementById('fpDescricao').value = l.descricao;
    document.getElementById('fpValor').value = l.valor;
    document.getElementById('fpCategoria').value = l.categoria;
    editandoAviso.style.display = 'flex';
    msg.textContent = '';
    abrirModal(modalLancamento);
  }

  function cancelarEdicao() {
    editandoId = null;
    form.reset();
    marcarTipo('despesa');
    editandoAviso.style.display = 'none';
    msg.textContent = '';
    fecharModal(modalLancamento);
  }

  document.getElementById('fpCancelarEdicao').onclick = function (ev) {
    ev.preventDefault();
    cancelarEdicao();
  };

  // Único gatilho deste modal é "Editar" (iniciarEdicao(), já abre ele) — o "×" só precisa
  // fechar do mesmo jeito que cancelar edição faria.
  document.getElementById('btnFecharLancamento').onclick = function () {
    cancelarEdicao();
  };

  function carregar() {
    fetch('<?= url('/api/financeiro-pessoal') ?>?mes=' + encodeURIComponent(MES_SELECIONADO))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        lancamentosAtuais = j.lancamentos;
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

  // Edição é a única ação que esta tela envia pro servidor (não existe "+ Adicionar" aqui,
  // mesma decisão do Resumo) — mas o form continua genérico (salvar()/atualizar() do
  // controller servem os dois casos), então o submit cobre o emEdicao=false por completude,
  // sem expor nenhum jeito de chegar nele pela UI desta tela.
  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var descricao = document.getElementById('fpDescricao').value.trim();
    var valor = document.getElementById('fpValor').value;
    if (!descricao || !valor || parseFloat(valor) <= 0) {
      msg.innerHTML = '<span style="color:var(--exp)">Preencha descrição e um valor válido.</span>';
      return;
    }
    var emEdicao = editandoId !== null;
    btnSalvar.disabled = true;
    var orig = btnSalvar.textContent;
    btnSalvar.textContent = 'Salvando...';
    msg.textContent = '';

    var saveUrl = emEdicao
      ? '<?= url('/financeiro-pessoal') ?>/' + editandoId + '/atualizar'
      : '<?= url('/financeiro-pessoal') ?>';

    fetch(saveUrl, {
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
          msg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>';
          return;
        }
        if (emEdicao) {
          cancelarEdicao();
        } else {
          form.reset();
          marcarTipo('despesa');
          btnSalvar.textContent = orig;
          fecharModal(modalLancamento);
        }
        carregar();
      })
      .catch(function () {
        btnSalvar.disabled = false;
        btnSalvar.textContent = orig;
        msg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  marcarTipo('despesa');
  lancamentosAtuais = <?= json_encode($lancamentos, JSON_UNESCAPED_UNICODE) ?>;
  aplicarFiltroEExibir();

  // Abrir/fechar modal (CSS puro, sem Bootstrap JS nesta área isolada) — mesmo helper usado
  // no Resumo (ver index.php), copiado aqui porque as duas telas não compartilham <script>.
  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }
})();
</script>
<?php endif; ?>
