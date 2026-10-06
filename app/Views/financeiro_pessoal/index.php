<?php
$mesesPt = [1=>'janeiro',2=>'fevereiro',3=>'março',4=>'abril',5=>'maio',6=>'junho',
    7=>'julho',8=>'agosto',9=>'setembro',10=>'outubro',11=>'novembro',12=>'dezembro'];
$diasPt = ['domingo','segunda-feira','terça-feira','quarta-feira','quinta-feira','sexta-feira','sábado'];

$anoMesPartes = explode('-', $mes);
$mesLabel = $mesesPt[(int) $anoMesPartes[1]] . ' de ' . $anoMesPartes[0];
$dataHojeLabel = $diasPt[(int) date('w')] . ', ' . date('j') . ' de ' . $mesesPt[(int) date('n')];
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
    <h1 class="fp-greeting"><?= e($saudacao) ?></h1>
    <div class="fp-faint"><?= e($dataHojeLabel) ?></div>
  </div>
  <nav class="fp-month-nav" aria-label="Navegar entre meses">
    <a href="<?= url('/financeiro-pessoal') ?>?mes=<?= e($mesAnteriorNav) ?>" class="fp-month-btn" aria-label="Mês anterior"><i class="bi bi-chevron-left" aria-hidden="true"></i></a>
    <span class="fp-mono fp-month-label"><?= e(ucfirst($mesLabel)) ?></span>
    <a href="<?= url('/financeiro-pessoal') ?>?mes=<?= e($mesProximoNav) ?>" class="fp-month-btn" aria-label="Próximo mês"><i class="bi bi-chevron-right" aria-hidden="true"></i></a>
  </nav>
</div>

<?php if ($itemAtrasado): ?>
<div class="fp-alert-atraso" role="alert">
  <span class="fp-alert-dot" aria-hidden="true"></span>
  <div class="fp-alert-texto">
    <strong><?= e($itemAtrasado['nome']) ?></strong> está atrasada há <?= (int) $itemAtrasado['dias_atraso'] ?> dia<?= ((int) $itemAtrasado['dias_atraso']) === 1 ? '' : 's' ?>
    · R$ <?= number_format((float) $itemAtrasado['valor'], 2, ',', '.') ?>
  </div>
  <button type="button" class="fp-btn fp-btn-despesa" id="btnMarcarAtrasadaPaga" data-id="<?= (int) $itemAtrasado['id'] ?>">Marcar como paga</button>
</div>
<?php endif; ?>

<?php
  $saldoMesInicial = $totalReceitas - $totalMes;
  $saldoPositivoInicial = $saldoMesInicial >= 0;
  $pctGastoInicial = $totalReceitas > 0 ? (int) round(($totalMes / $totalReceitas) * 100) : null;
?>
<div class="fp-kpis-3">
  <div class="fp-card fp-card-saldo">
    <div class="fp-muted" style="font-size:.8rem;margin-bottom:6px;display:flex;justify-content:space-between;align-items:center;gap:8px">
      <span>Saldo de <?= e($mesLabel) ?></span>
      <span id="fpSaldoBadge" class="fp-badge <?= $saldoPositivoInicial ? 'fp-badge-inc' : 'fp-badge-exp' ?>"><?= $saldoPositivoInicial ? 'Positivo' : 'Negativo' ?></span>
    </div>
    <div id="fpSaldo" class="fp-mono fp-saldo-num" style="color:<?= $saldoPositivoInicial ? 'var(--inc)' : 'var(--exp)' ?>">
      <?= $saldoPositivoInicial ? '' : '−' ?>R$ <?= number_format(abs($saldoMesInicial), 2, ',', '.') ?>
    </div>
    <div class="fp-bar-track" style="margin-top:12px">
      <div id="fpSaldoBar" class="fp-bar-fill" style="width:<?= $pctGastoInicial !== null ? min(100, max(0, $pctGastoInicial)) : 0 ?>%"></div>
    </div>
    <div id="fpSaldoCaption" class="fp-faint" style="font-size:.76rem;margin-top:6px">
      <?= $pctGastoInicial !== null ? 'Você gastou ' . $pctGastoInicial . '% do que entrou neste mês' : 'Nenhuma entrada registrada neste mês ainda' ?>
    </div>
  </div>
  <div class="fp-card">
    <div class="fp-muted" style="font-size:.78rem;margin-bottom:4px">Entrada em <?= e($mesLabel) ?></div>
    <div id="fpTotalReceitas" class="fp-mono" style="font-weight:700;font-size:1.55rem;color:var(--inc)" data-valor="<?= (float) $totalReceitas ?>">
      R$ <?= number_format($totalReceitas, 2, ',', '.') ?>
    </div>
  </div>
  <div class="fp-card">
    <div class="fp-muted" style="font-size:.78rem;margin-bottom:4px">Saída em <?= e($mesLabel) ?></div>
    <div id="fpTotalMes" class="fp-mono" style="font-weight:700;font-size:1.55rem;color:var(--exp)" data-valor="<?= (float) $totalMes ?>">
      R$ <?= number_format($totalMes, 2, ',', '.') ?>
    </div>
  </div>
</div>

<div class="fp-form-scan-row">
  <form id="fpForm" class="fp-card" style="display:flex;flex-direction:column;gap:10px">
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

    <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvar">Adicionar lançamento</button>
  </form>

  <button type="button" class="fp-card fp-scan-cta" id="btnEscanearConta">
    <div class="fp-scan-cta-icon" aria-hidden="true"><i class="bi bi-qr-code-scan"></i></div>
    <div class="fp-scan-cta-texto">
      <div class="fp-scan-cta-titulo">Escanear conta</div>
      <div class="fp-scan-cta-sub">OCR lê valor, vencimento e categoria do papel, da foto ou do print</div>
    </div>
  </button>
</div>

<div class="fp-main-cols">

  <section class="fp-contas-col" aria-labelledby="fpContasTitulo">
    <div class="fp-contas-header">
      <div>
        <h2 id="fpContasTitulo" class="fp-section-titulo">Contas e débitos</h2>
        <div id="fpContasResumo" class="fp-faint fp-mono" style="font-size:.78rem">
          R$ <?= number_format($totalAberto, 2, ',', '.') ?> em aberto · R$ <?= number_format($totalProx7Dias, 2, ',', '.') ?> vencem nos próximos 7 dias
        </div>
      </div>
      <button type="button" class="fp-btn fp-btn-primary" id="btnNovaLista">+ Nova lista</button>
    </div>

    <form id="formNovaLista" class="fp-card" style="display:none;margin-bottom:14px;flex-direction:column;gap:10px">
      <?= csrf_field() ?>
      <input type="text" name="nome" id="novaListaNome" class="fp-input" placeholder="Nome da lista (ex.: Contas da casa)" maxlength="80" required>
      <div style="display:flex;gap:16px;align-items:center;font-size:.88rem">
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="tipoLista" value="despesa" checked> Despesas
        </label>
        <label style="display:flex;align-items:center;gap:6px;cursor:pointer">
          <input type="radio" name="tipoLista" value="debito"> Débitos
        </label>
      </div>
      <div style="display:flex;gap:8px;justify-content:flex-end">
        <button type="button" class="fp-btn fp-btn-ghost" id="btnCancelarNovaLista">Cancelar</button>
        <button type="submit" class="fp-btn fp-btn-primary">Criar</button>
      </div>
      <div id="novaListaMsg" class="fp-muted" style="font-size:.82rem"></div>
    </form>

    <div id="fpListasContainer"></div>
  </section>

  <section class="fp-lanc-col" aria-labelledby="fpLancTitulo">
    <div style="display:flex;gap:10px;align-items:flex-start">
      <div class="fp-filtros">
        <button type="button" class="fp-filtro-btn active" data-filtro="todos" title="Todos"><i class="bi bi-list-ul"></i><span>Todos</span></button>
        <button type="button" class="fp-filtro-btn" data-filtro="receita" title="Entradas"><i class="bi bi-arrow-down-circle-fill"></i><span>Entradas</span></button>
        <button type="button" class="fp-filtro-btn" data-filtro="despesa" title="Saídas"><i class="bi bi-arrow-up-circle-fill"></i><span>Saídas</span></button>
      </div>
      <div style="flex:1;min-width:0">
        <h2 id="fpLancTitulo" class="fp-section-titulo" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.03em;margin:0 0 8px">Lançamentos</h2>
        <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
      </div>
    </div>
  </section>

</div>

<!-- Escanear conta: pareamento com o celular por QR (desktop) — mesmo mecanismo genérico
     de ScannerController/scanner_sessoes já usado em outras telas do FixaOS, modo
     'financeiro_conta'. Em celular/tablet (feTemCameraPropria()), pula o QR e abre a câmera
     direto, mesma lógica já usada em os/show.php. Sem Bootstrap JS nesta área (layout próprio,
     "grana"), por isso modal próprio em CSS puro, não bootstrap.Modal. -->
<input id="scanInputDireto" type="file" accept="image/*" capture="environment" style="display:none">

<div class="fp-modal-backdrop" id="modalScanQr">
  <div class="fp-modal" style="max-width:360px;text-align:center">
    <div class="fp-modal-header">
      <strong>📷 Escanear conta</strong>
      <button type="button" class="fp-modal-close" id="btnFecharScanQr" aria-label="Fechar">×</button>
    </div>
    <p class="fp-muted" style="font-size:.85rem;margin:0 0 10px">Abra a câmera do celular (logado na mesma conta) e escaneie:</p>
    <div id="scanQrBox" style="display:flex;justify-content:center;align-items:center;min-height:186px;background:var(--surf2);border-radius:12px"></div>
    <p class="fp-faint" style="font-size:.78rem;margin:10px 0 2px">ou acesse <strong><?= e(parse_url(url('/'), PHP_URL_HOST) ?: 'o site') ?>/scan</strong> e digite:</p>
    <div id="scanCodigo" class="fp-mono" style="font-weight:800;font-size:1.3rem;letter-spacing:.2em">••••••</div>
    <div id="scanStatus" class="fp-faint" style="margin-top:10px;font-size:.84rem">Aguardando o celular…</div>
  </div>
</div>

<div class="fp-modal-backdrop" id="modalRevisaoConta">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Revisar antes de inserir</strong>
      <button type="button" class="fp-modal-close" id="btnFecharRevisao" aria-label="Fechar">×</button>
    </div>
    <img id="revisaoFotoImg" src="" alt="Foto da conta escaneada" style="width:100%;max-height:200px;object-fit:contain;border-radius:12px;background:var(--surf2);margin-bottom:12px">
    <form id="formRevisaoConta" style="display:flex;flex-direction:column;gap:10px">
      <input type="text" id="revisaoDescricao" class="fp-input" placeholder="Descrição (ex.: Conta de luz)" maxlength="150" required>
      <div class="fp-row-valor-cat">
        <input type="number" id="revisaoValor" class="fp-input" placeholder="Valor (R$)" step="0.01" min="0.01" style="flex:1" required>
        <select id="revisaoCategoria" class="fp-select" style="flex:1">
          <?php foreach ($categorias as $chave => $c): ?>
          <option value="<?= e($chave) ?>"><?= e($c['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div style="display:flex;flex-direction:column;gap:6px;font-size:.88rem">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="radio" name="revisaoModo" id="revisaoModoLista" value="lista" checked> Conta a pagar (entra numa lista)
        </label>
        <div id="revisaoListaBloco" style="display:flex;flex-direction:column;gap:8px;padding-left:24px">
          <select id="revisaoLista" class="fp-select"></select>
          <input type="date" id="revisaoVencimento" class="fp-input" placeholder="Vencimento">
        </div>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="radio" name="revisaoModo" id="revisaoModoPago" value="pago"> Gasto já pago (entra direto nos lançamentos)
        </label>
      </div>

      <div id="revisaoMsg" class="fp-muted" style="font-size:.82rem"></div>
      <button type="submit" class="fp-btn fp-btn-primary" id="btnRevisaoSalvar">Inserir no sistema</button>
    </form>
  </div>
</div>

<script>
(function () {
  var CATS = <?= json_encode($categorias, JSON_UNESCAPED_UNICODE) ?>;
  var MES_SELECIONADO = <?= json_encode($mes) ?>;
  var lista = document.getElementById('fpLista');
  var totalDespesaEl = document.getElementById('fpTotalMes');
  var totalReceitaEl = document.getElementById('fpTotalReceitas');
  var saldoEl = document.getElementById('fpSaldo');
  var saldoBadgeEl = document.getElementById('fpSaldoBadge');
  var saldoBarEl = document.getElementById('fpSaldoBar');
  var saldoCaptionEl = document.getElementById('fpSaldoCaption');
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
  function fmtDataCurta(dt) {
    // dt no formato YYYY-MM-DD (vencimento de item) — "Dia DD", sem depender de Date()/fuso.
    var p = dt.split('-');
    return p.length === 3 ? p[2] : dt;
  }
  function escapeHtml(s) {
    var d = document.createElement('div');
    d.textContent = s == null ? '' : s;
    return d.innerHTML;
  }

  function mesAtualStr() {
    return MES_SELECIONADO;
  }

  // Os 3 KPIs (Saldo/Entrada/Saída) são sempre a soma do MÊS SELECIONADO (não "hoje"),
  // independente do filtro lateral escolhido na lista de lançamentos.
  function atualizarTotais() {
    var mesAtual = mesAtualStr();
    var despesas = 0;
    var receitas = 0;
    lancamentosAtuais.forEach(function (l) {
      if (l.data_hora.slice(0, 7) !== mesAtual) return;
      if (l.tipo === 'despesa') despesas += parseFloat(l.valor);
      else receitas += parseFloat(l.valor);
    });
    totalDespesaEl.textContent = fmtValor(despesas);
    totalReceitaEl.textContent = fmtValor(receitas);
    var saldo = receitas - despesas;
    var saldoNeg = saldo < 0;
    saldoEl.textContent = (saldoNeg ? '−' : '') + fmtValor(Math.abs(saldo));
    saldoEl.style.color = saldoNeg ? 'var(--exp)' : 'var(--inc)';
    saldoBadgeEl.textContent = saldoNeg ? 'Negativo' : 'Positivo';
    saldoBadgeEl.className = 'fp-badge ' + (saldoNeg ? 'fp-badge-exp' : 'fp-badge-inc');
    if (receitas > 0) {
      var pct = Math.round((despesas / receitas) * 100);
      saldoBarEl.style.width = Math.min(100, Math.max(0, pct)) + '%';
      saldoCaptionEl.textContent = 'Você gastou ' + pct + '% do que entrou neste mês';
    } else {
      saldoBarEl.style.width = '0%';
      saldoCaptionEl.textContent = 'Nenhuma entrada registrada neste mês ainda';
    }
  }

  var MSG_VAZIO = {
    todos: 'Nenhum lançamento em ' + '<?= e($mesLabel) ?>' + ' ainda — adicione o primeiro acima.',
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
      var row = document.createElement('div');
      row.className = 'fp-card';
      row.style.cssText = 'display:flex;align-items:center;gap:12px;padding:14px 16px;border-left:3px solid ' + tipoCor;
      row.innerHTML =
        '<span style="width:10px;height:10px;border-radius:50%;background:' + cat.cor + ';flex:0 0 auto"></span>' +
        '<div style="flex:1;min-width:0">' +
          '<div style="font-size:.92rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(l.descricao) + (l.origem === 'ocr' ? ' <span class="fp-chip fp-chip-muted" style="margin-left:4px">OCR</span>' : '') + '</div>' +
          '<div class="fp-faint fp-mono" style="font-size:.74rem;margin-top:2px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + escapeHtml(cat.nome) + ' · ' + fmtData(l.data_hora) + (l.origem === 'foto' ? ' · 📷' : '') + '</div>' +
        '</div>' +
        '<div class="fp-mono" style="font-weight:700;font-size:.95rem;color:' + tipoCor + '">' +
          (l.tipo === 'receita' ? '+' : '−') + fmtValor(l.valor) +
        '</div>' +
        '<div style="display:flex;gap:2px;flex:0 0 auto">' +
          '<button type="button" aria-label="Editar lançamento" data-id="' + l.id + '" class="fp-edit" style="background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:.95rem;padding:4px;min-width:36px;min-height:36px"><i class="bi bi-pencil-fill"></i></button>' +
          '<button type="button" aria-label="Excluir lançamento" data-id="' + l.id + '" class="fp-del" style="background:transparent;border:none;color:var(--muted);cursor:pointer;font-size:1.1rem;padding:4px;min-width:36px;min-height:36px">×</button>' +
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

  function carregar() {
    fetch('<?= url('/api/financeiro-pessoal') ?>?mes=' + encodeURIComponent(MES_SELECIONADO))
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        lancamentosAtuais = j.lancamentos;
        atualizarTotais();
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
  atualizarTotais();
  aplicarFiltroEExibir();

  // ────────────────────────────────────────────────────────────────────
  // Contas e débitos — listas recolhíveis, itens, marcar/desmarcar pago.
  // ────────────────────────────────────────────────────────────────────
  var listasContainer = document.getElementById('fpListasContainer');
  var listasAtuais = <?= json_encode($listas, JSON_UNESCAPED_UNICODE) ?>;

  function diffDias(vencimento) {
    var hoje = new Date();
    hoje.setHours(0, 0, 0, 0);
    var p = vencimento.split('-');
    var v = new Date(parseInt(p[0], 10), parseInt(p[1], 10) - 1, parseInt(p[2], 10));
    return Math.round((v - hoje) / 86400000);
  }

  function statusItem(item) {
    if (item.pago_em) return { texto: 'Paga', classe: 'fp-chip-inc' };
    if (!item.vencimento) return { texto: 'Sem data', classe: 'fp-chip-muted' };
    var dias = diffDias(item.vencimento);
    if (dias < 0) return { texto: 'Atrasada', classe: 'fp-chip-danger' };
    if (dias === 0) return { texto: 'Vence hoje', classe: 'fp-chip-warn' };
    if (dias <= 3) return { texto: 'Em ' + dias + ' dia' + (dias === 1 ? '' : 's'), classe: 'fp-chip-warn' };
    return { texto: 'Dia ' + fmtDataCurta(item.vencimento), classe: 'fp-chip-muted' };
  }

  function renderListas() {
    listasContainer.innerHTML = '';
    if (!listasAtuais.length) {
      listasContainer.innerHTML = '<div class="fp-card fp-muted" style="text-align:center;font-size:.88rem">Nenhuma lista ainda — crie a primeira em "+ Nova lista".</div>';
      atualizarResumoContas();
      return;
    }
    listasAtuais.forEach(function (l) {
      var card = document.createElement('div');
      card.className = 'fp-card fp-lista-card';
      card.style.padding = '0';

      var itens = l.itens || [];
      var pagos = itens.filter(function (i) { return i.pago_em; }).length;
      var totalAbertoLista = itens.reduce(function (s, i) { return s + (i.pago_em ? 0 : parseFloat(i.valor)); }, 0);
      var pct = itens.length ? Math.round((pagos / itens.length) * 100) : 0;
      var icone = l.tipo === 'debito' ? 'bi-credit-card-2-front-fill' : 'bi-receipt';
      var badgeClasse = l.tipo === 'debito' ? 'fp-badge-debt' : 'fp-badge-accent';
      var badgeTexto = l.tipo === 'debito' ? 'DÉBITOS' : 'DESPESAS';

      // O cabeçalho inteiro é clicável (toggle), mas "excluir lista" precisa do próprio
      // <button> — <button> dentro de <button> é HTML inválido (o navegador fecha o de fora
      // no primeiro aninhado), por isso o wrapper é um <div> com dois botões irmãos: um
      // grande (toggle, cobre quase tudo) e um pequeno de excluir, por cima.
      var headerWrap = document.createElement('div');
      headerWrap.className = 'fp-lista-header-wrap';

      var header = document.createElement('button');
      header.type = 'button';
      header.className = 'fp-lista-header';
      header.setAttribute('aria-expanded', l.aberta == 1 ? 'true' : 'false');
      header.innerHTML =
        '<i class="bi ' + icone + '" aria-hidden="true"></i>' +
        '<div class="fp-lista-header-texto">' +
          '<div class="fp-lista-nome">' + escapeHtml(l.nome) + ' <span class="fp-badge ' + badgeClasse + '">' + badgeTexto + '</span></div>' +
          '<div class="fp-faint" style="font-size:.74rem">' + pagos + ' de ' + itens.length + ' pagas</div>' +
        '</div>' +
        '<div class="fp-lista-header-valor">' +
          '<div class="fp-mono" style="font-weight:700">' + fmtValor(totalAbertoLista) + '</div>' +
          '<div class="fp-faint" style="font-size:.7rem">em aberto</div>' +
        '</div>' +
        '<i class="bi bi-chevron-down fp-lista-chevron" aria-hidden="true" style="transform:rotate(' + (l.aberta == 1 ? '180' : '0') + 'deg)"></i>';
      header.onclick = function () { toggleLista(l.id, header); };
      headerWrap.appendChild(header);

      var btnExcluirLista = document.createElement('button');
      btnExcluirLista.type = 'button';
      btnExcluirLista.className = 'fp-lista-excluir';
      btnExcluirLista.setAttribute('aria-label', 'Excluir lista ' + l.nome);
      btnExcluirLista.innerHTML = '<i class="bi bi-trash3" aria-hidden="true"></i>';
      btnExcluirLista.onclick = function () { excluirLista(l.id, l.nome); };
      headerWrap.appendChild(btnExcluirLista);

      card.appendChild(headerWrap);

      var bar = document.createElement('div');
      bar.className = 'fp-bar-track';
      bar.style.cssText = 'margin:0 16px;height:4px';
      bar.innerHTML = '<div class="fp-bar-fill" style="width:' + pct + '%"></div>';
      card.appendChild(bar);

      var corpo = document.createElement('div');
      corpo.className = 'fp-lista-corpo';
      corpo.style.display = l.aberta == 1 ? 'block' : 'none';

      if (!itens.length) {
        var vazio = document.createElement('div');
        vazio.className = 'fp-faint';
        vazio.style.cssText = 'padding:12px 16px;font-size:.84rem';
        vazio.textContent = 'Nenhum item ainda.';
        corpo.appendChild(vazio);
      }
      itens.forEach(function (item) {
        var st = statusItem(item);
        var row = document.createElement('div');
        row.className = 'fp-item-row';
        var circleAriaLabel = item.pago_em ? 'Desmarcar "' + item.nome + '" como paga' : 'Marcar "' + item.nome + '" como paga';
        row.innerHTML =
          '<button type="button" class="fp-item-circle' + (item.pago_em ? ' pago' : '') + '" aria-pressed="' + (item.pago_em ? 'true' : 'false') + '" aria-label="' + escapeHtml(circleAriaLabel) + '">' +
            (item.pago_em ? '<i class="bi bi-check-lg" aria-hidden="true"></i>' : '') +
          '</button>' +
          '<div class="fp-item-texto">' +
            '<div class="' + (item.pago_em ? 'fp-item-nome pago' : 'fp-item-nome') + '">' + escapeHtml(item.nome) + (item.parcela ? ' · ' + escapeHtml(item.parcela) : '') + '</div>' +
            '<div class="fp-faint" style="font-size:.74rem">' + (item.vencimento ? 'vence ' + fmtDataCurta(item.vencimento) + '/' + item.vencimento.slice(5, 7) : 'sem vencimento') + '</div>' +
          '</div>' +
          '<span class="fp-chip ' + st.classe + '">' + st.texto + '</span>' +
          '<span class="fp-mono fp-item-valor">' + fmtValor(item.valor) + '</span>' +
          '<button type="button" class="fp-item-del" aria-label="Excluir ' + escapeHtml(item.nome) + '">×</button>';
        row.querySelector('.fp-item-circle').onclick = function () { togglePagoItem(item); };
        row.querySelector('.fp-item-del').onclick = function () { excluirItem(item.id); };
        corpo.appendChild(row);
      });

      var rodape = document.createElement('div');
      rodape.className = 'fp-lista-rodape';
      rodape.innerHTML =
        '<button type="button" class="fp-btn fp-btn-primary fp-btn-sm" id="btnScanLista' + l.id + '">Escanear para esta lista</button>' +
        '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm" id="btnAddItem' + l.id + '">+ Adicionar manualmente</button>';
      corpo.appendChild(rodape);

      var itemForm = document.createElement('form');
      itemForm.className = 'fp-item-form';
      itemForm.id = 'itemForm' + l.id;
      itemForm.style.display = 'none';
      var optsCategoria = Object.keys(CATS).map(function (k) { return '<option value="' + k + '">' + escapeHtml(CATS[k].nome) + '</option>'; }).join('');
      itemForm.innerHTML =
        '<input type="text" class="fp-input item-nome" placeholder="Nome (ex.: Energia elétrica)" maxlength="120">' +
        '<div class="fp-row-valor-cat">' +
          '<input type="number" class="fp-input item-valor" placeholder="Valor (R$)" step="0.01" min="0.01" style="flex:1">' +
          '<input type="date" class="fp-input item-vencimento" style="flex:1">' +
        '</div>' +
        '<select class="fp-select item-categoria">' + optsCategoria + '</select>' +
        '<div class="fp-faint item-msg" style="font-size:.8rem"></div>' +
        '<div style="display:flex;gap:8px;justify-content:flex-end">' +
          '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm item-cancelar">Cancelar</button>' +
          '<button type="submit" class="fp-btn fp-btn-primary fp-btn-sm">Adicionar</button>' +
        '</div>';
      corpo.appendChild(itemForm);

      card.appendChild(corpo);
      listasContainer.appendChild(card);

      document.getElementById('btnScanLista' + l.id).onclick = function () { abrirScan(l.id); };
      document.getElementById('btnAddItem' + l.id).onclick = function () {
        itemForm.style.display = itemForm.style.display === 'none' ? 'flex' : 'none';
      };
      itemForm.querySelector('.item-cancelar').onclick = function () {
        itemForm.style.display = 'none';
        itemForm.reset();
      };
      itemForm.addEventListener('submit', function (ev) {
        ev.preventDefault();
        criarItem(l.id, itemForm);
      });
    });
    atualizarResumoContas();
  }

  function atualizarResumoContas() {
    var aberto = 0;
    var prox7 = 0;
    var hoje = new Date(); hoje.setHours(0, 0, 0, 0);
    var em7 = new Date(hoje.getTime() + 7 * 86400000);
    listasAtuais.forEach(function (l) {
      (l.itens || []).forEach(function (i) {
        if (i.pago_em) return;
        aberto += parseFloat(i.valor);
        if (i.vencimento) {
          var dias = diffDias(i.vencimento);
          if (dias >= 0 && i.vencimento <= em7.toISOString().slice(0, 10)) prox7 += parseFloat(i.valor);
        }
      });
    });
    document.getElementById('fpContasResumo').textContent = fmtValor(aberto) + ' em aberto · ' + fmtValor(prox7) + ' vencem nos próximos 7 dias';
  }

  function carregarListas() {
    return fetch('<?= url('/api/financeiro-pessoal/listas') ?>')
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) return;
        listasAtuais = j.listas;
        renderListas();
      });
  }

  function toggleLista(id, headerEl) {
    var l = listasAtuais.filter(function (x) { return x.id === id; })[0];
    if (!l) return;
    var novaAberta = l.aberta == 1 ? 0 : 1;
    l.aberta = novaAberta;
    renderListas();
    fetch('<?= url('/financeiro-pessoal/listas') ?>/' + id + '/toggle', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: 'aberta=' + novaAberta
    }).catch(function () {});
  }

  function excluirLista(id, nome) {
    if (!confirm('Excluir a lista "' + nome + '" e todos os itens dela? Lançamentos já gerados por itens pagos não são apagados.')) return;
    fetch('<?= url('/financeiro-pessoal/listas') ?>/' + id + '/excluir', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken }
    }).then(function () { carregarListas(); });
  }

  function togglePagoItem(item, recarregarPagina) {
    var url = '<?= url('/financeiro-pessoal/itens') ?>/' + item.id + '/' + (item.pago_em ? 'despagar' : 'pagar');
    fetch(url, { method: 'POST', headers: { 'X-CSRF-Token': csrfToken } })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { alert(j.erro || 'Não deu pra atualizar agora.'); return; }
        if (recarregarPagina) { window.location.reload(); return; }
        carregarListas();
        carregar();
      });
  }

  function excluirItem(id) {
    if (!confirm('Excluir este item?')) return;
    fetch('<?= url('/financeiro-pessoal/itens') ?>/' + id + '/excluir', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrfToken }
    }).then(function () { carregarListas(); });
  }

  function criarItem(listaId, formEl) {
    var nome = formEl.querySelector('.item-nome').value.trim();
    var valor = formEl.querySelector('.item-valor').value;
    var vencimento = formEl.querySelector('.item-vencimento').value;
    var categoria = formEl.querySelector('.item-categoria').value;
    var msgEl = formEl.querySelector('.item-msg');
    if (!nome || !valor || parseFloat(valor) <= 0) {
      msgEl.innerHTML = '<span style="color:var(--exp)">Preencha nome e um valor válido.</span>';
      return;
    }
    fetch('<?= url('/financeiro-pessoal/listas') ?>/' + listaId + '/itens', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ nome: nome, valor: valor, vencimento: vencimento, categoria: categoria })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { msgEl.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra adicionar agora.') + '</span>'; return; }
        carregarListas();
      });
  }

  // ────────────────────────────────────────────────────────────────────
  // Escanear conta — câmera pelo celular. No PC, parea por QR (mesmo
  // mecanismo genérico de ScannerController, modo 'financeiro_conta');
  // em celular/tablet, abre a câmera direto (mesmo aparelho que já está
  // com a tela aberta). Ainda sem leitura automática de valor/vencimento
  // (sem OCR/código de barras nesta rodada) — a foto só serve de
  // referência enquanto o usuário preenche o formulário de revisão.
  // ────────────────────────────────────────────────────────────────────
  var scanInputDireto = document.getElementById('scanInputDireto');
  var modalScanQr = document.getElementById('modalScanQr');
  var modalRevisaoConta = document.getElementById('modalRevisaoConta');
  var scanListaAlvo = null;
  var scanToken = null;
  var scanTimer = null;

  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }

  function temCameraPropria() {
    return ('ontouchstart' in window || navigator.maxTouchPoints > 0) && window.innerWidth <= 991;
  }

  function comprimirImagem(file) {
    var suportaWebp = (function () {
      var c = document.createElement('canvas'); c.width = c.height = 1;
      return c.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    })();
    return new Promise(function (resolve) {
      var reader = new FileReader();
      reader.onload = function (e) {
        var img = new Image();
        img.onload = function () {
          var max = 1280, w = img.width, h = img.height;
          if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
          else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
          var c = document.createElement('canvas');
          c.width = w; c.height = h;
          var ctx = c.getContext('2d');
          ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
          ctx.drawImage(img, 0, 0, w, h);
          resolve(suportaWebp ? c.toDataURL('image/webp', 0.78) : c.toDataURL('image/jpeg', 0.7));
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  function abrirScan(listaId) {
    scanListaAlvo = listaId || null;
    if (temCameraPropria()) { scanInputDireto.click(); return; }
    abrirModalQr();
  }
  document.getElementById('btnEscanearConta').onclick = function () { abrirScan(null); };

  scanInputDireto.addEventListener('change', function () {
    if (!scanInputDireto.files.length) return;
    comprimirImagem(scanInputDireto.files[0]).then(function (dataUrl) { abrirRevisao(dataUrl); });
    scanInputDireto.value = '';
  });

  function abrirModalQr() {
    document.getElementById('scanQrBox').innerHTML = '';
    document.getElementById('scanCodigo').textContent = '••••••';
    document.getElementById('scanStatus').textContent = 'Gerando QR…';
    abrirModal(modalScanQr);

    fetch('<?= url('/scanner/nova') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: 'modo=financeiro_conta'
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        scanToken = j.token;
        document.getElementById('scanQrBox').innerHTML = '<img src="' + j.qr + '" alt="QR Code" style="width:186px;height:186px">';
        document.getElementById('scanCodigo').textContent = j.codigo;
        document.getElementById('scanStatus').textContent = 'Aguardando o celular…';
        scanTimer = setInterval(pollScan, 2000);
      })
      .catch(function () {
        document.getElementById('scanStatus').innerHTML = '<span style="color:var(--exp)">Erro ao gerar o QR. Feche e tente de novo.</span>';
      });
  }

  function pollScan() {
    if (!scanToken) return;
    fetch('<?= url('/scanner/status') ?>?token=' + encodeURIComponent(scanToken))
      .then(function (r) {
        if (!r.ok) {
          if (r.status === 410) {
            document.getElementById('scanStatus').innerHTML = '<span style="color:var(--exp)">A sessão expirou. Feche e tente de novo.</span>';
            clearInterval(scanTimer); scanTimer = null;
          }
          return null;
        }
        return r.json();
      })
      .then(function (j) {
        if (!j || j.status !== 'pronto' || !j.resultado) return;
        clearInterval(scanTimer); scanTimer = null;
        if (j.erro) {
          document.getElementById('scanStatus').innerHTML = '<span style="color:var(--exp)">' + j.erro + '</span>';
          setTimeout(function () { fecharModal(modalScanQr); }, 1500);
          return;
        }
        var fotos = j.resultado.fotos || [];
        document.getElementById('scanStatus').innerHTML = '<span style="color:var(--inc);font-weight:700">✅ Foto recebida!</span>';
        setTimeout(function () {
          fecharModal(modalScanQr);
          if (fotos.length) abrirRevisao(fotos[0]);
        }, 700);
      });
  }

  document.getElementById('btnFecharScanQr').onclick = function () {
    if (scanTimer) { clearInterval(scanTimer); scanTimer = null; }
    fecharModal(modalScanQr);
  };

  // ── Revisão: nada entra no sistema sem o usuário conferir/completar os campos ──────────
  var modoListaRadio = document.getElementById('revisaoModoLista');
  var modoPagoRadio = document.getElementById('revisaoModoPago');
  var revisaoListaBloco = document.getElementById('revisaoListaBloco');
  var revisaoLista = document.getElementById('revisaoLista');
  var btnRevisaoSalvar = document.getElementById('btnRevisaoSalvar');
  var revisaoMsg = document.getElementById('revisaoMsg');

  function atualizarModoRevisao() {
    revisaoListaBloco.style.display = modoListaRadio.checked ? 'flex' : 'none';
  }
  modoListaRadio.onchange = atualizarModoRevisao;
  modoPagoRadio.onchange = atualizarModoRevisao;

  function atualizarTextoBotaoRevisao() {
    var v = parseFloat(document.getElementById('revisaoValor').value) || 0;
    btnRevisaoSalvar.textContent = v > 0 ? 'Inserir ' + fmtValor(v) + ' no sistema' : 'Inserir no sistema';
  }
  document.getElementById('revisaoValor').addEventListener('input', atualizarTextoBotaoRevisao);

  function abrirRevisao(fotoDataUrl) {
    document.getElementById('revisaoFotoImg').src = fotoDataUrl;
    document.getElementById('revisaoDescricao').value = '';
    document.getElementById('revisaoValor').value = '';
    document.getElementById('revisaoVencimento').value = '';
    document.getElementById('revisaoCategoria').value = 'outros';
    revisaoMsg.textContent = '';
    atualizarTextoBotaoRevisao();

    revisaoLista.innerHTML = listasAtuais.map(function (l) {
      return '<option value="' + l.id + '">' + escapeHtml(l.nome) + '</option>';
    }).join('');

    if (!listasAtuais.length) {
      // Sem lista nenhuma ainda — não tem onde guardar "conta a pagar", cai pra "já pago".
      modoPagoRadio.checked = true;
      modoListaRadio.disabled = true;
    } else {
      modoListaRadio.disabled = false;
      if (scanListaAlvo) { revisaoLista.value = String(scanListaAlvo); modoListaRadio.checked = true; }
      else { modoListaRadio.checked = true; }
    }
    atualizarModoRevisao();
    abrirModal(modalRevisaoConta);
    document.getElementById('revisaoDescricao').focus();
  }

  document.getElementById('btnFecharRevisao').onclick = function () { fecharModal(modalRevisaoConta); };

  document.getElementById('formRevisaoConta').addEventListener('submit', function (ev) {
    ev.preventDefault();
    var descricao = document.getElementById('revisaoDescricao').value.trim();
    var valor = document.getElementById('revisaoValor').value;
    var categoria = document.getElementById('revisaoCategoria').value;
    if (!descricao || !valor || parseFloat(valor) <= 0) {
      revisaoMsg.innerHTML = '<span style="color:var(--exp)">Preencha descrição e um valor válido.</span>';
      return;
    }

    btnRevisaoSalvar.disabled = true;
    var modo = modoPagoRadio.checked ? 'pago' : 'lista';

    if (modo === 'pago') {
      fetch('<?= url('/financeiro-pessoal') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
        body: new URLSearchParams({ tipo: 'despesa', categoria: categoria, descricao: descricao, valor: valor, origem: 'foto' })
      })
        .then(function (r) { return r.json(); })
        .then(function (j) {
          btnRevisaoSalvar.disabled = false;
          if (!j.ok) { revisaoMsg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>'; return; }
          fecharModal(modalRevisaoConta);
          carregar();
        })
        .catch(function () {
          btnRevisaoSalvar.disabled = false;
          revisaoMsg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
        });
      return;
    }

    var listaId = revisaoLista.value;
    if (!listaId) {
      btnRevisaoSalvar.disabled = false;
      revisaoMsg.innerHTML = '<span style="color:var(--exp)">Escolha uma lista.</span>';
      return;
    }
    var vencimento = document.getElementById('revisaoVencimento').value;
    fetch('<?= url('/financeiro-pessoal/listas') ?>/' + listaId + '/itens', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ nome: descricao, valor: valor, vencimento: vencimento, categoria: categoria })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnRevisaoSalvar.disabled = false;
        if (!j.ok) { revisaoMsg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra adicionar agora.') + '</span>'; return; }
        fecharModal(modalRevisaoConta);
        carregarListas();
      })
      .catch(function () {
        btnRevisaoSalvar.disabled = false;
        revisaoMsg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  var btnMarcarAtrasada = document.getElementById('btnMarcarAtrasadaPaga');
  if (btnMarcarAtrasada) {
    btnMarcarAtrasada.onclick = function () {
      togglePagoItem({ id: btnMarcarAtrasada.dataset.id, pago_em: null }, true);
    };
  }

  // "+ Nova lista" — form inline, mesmo padrão de abrir/fechar já usado no resto do app.
  var btnNovaLista = document.getElementById('btnNovaLista');
  var formNovaLista = document.getElementById('formNovaLista');
  var novaListaMsg = document.getElementById('novaListaMsg');
  btnNovaLista.onclick = function () {
    formNovaLista.style.display = formNovaLista.style.display === 'none' ? 'flex' : 'none';
    if (formNovaLista.style.display === 'flex') document.getElementById('novaListaNome').focus();
  };
  document.getElementById('btnCancelarNovaLista').onclick = function () {
    formNovaLista.style.display = 'none';
    formNovaLista.reset();
    novaListaMsg.textContent = '';
  };
  formNovaLista.addEventListener('submit', function (ev) {
    ev.preventDefault();
    var nome = document.getElementById('novaListaNome').value.trim();
    var tipo = formNovaLista.querySelector('input[name="tipoLista"]:checked').value;
    if (!nome) { novaListaMsg.innerHTML = '<span style="color:var(--exp)">Dê um nome pra lista.</span>'; return; }
    fetch('<?= url('/financeiro-pessoal/listas') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ nome: nome, tipo: tipo })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) { novaListaMsg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra criar agora.') + '</span>'; return; }
        formNovaLista.style.display = 'none';
        formNovaLista.reset();
        novaListaMsg.textContent = '';
        carregarListas();
      });
  });

  renderListas();
})();
</script>
<?php endif; ?>
