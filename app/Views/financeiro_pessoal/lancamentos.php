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

<!-- Pedido do usuário: esta tela ganhou de volta os dois gatilhos de criar (Adicionar/
     Escanear) que o Resumo não tem mais (ver index.php) — "+ Adicionar" abre o modal vazio,
     "Escanear conta" abre a câmera (celular) ou um QR de pareamento (computador) e cai no
     formulário de revisão antes de gravar qualquer coisa. -->
<div class="fp-acoes-rapidas" style="display:flex;gap:10px;margin-bottom:20px;flex-wrap:wrap">
  <button type="button" class="fp-btn fp-btn-scan" id="btnEscanearConta" style="flex:0 0 auto;display:inline-flex;align-items:center;gap:8px">
    <?= fp_icone('qr-code-scan') ?> Escanear conta
  </button>
  <button type="button" class="fp-btn fp-btn-primary" id="btnNovoLancamento" style="flex:0 0 auto">+ Adicionar lançamento</button>
</div>

<!-- Dois gatilhos abrem este modal agora: "+ Adicionar lançamento" (vazio) e "Editar" de um
     lançamento já existente (ver iniciarEdicao() no script). Mesmos ids de sempre (fpForm/
     fpTipo*/fpDescricao/fpValor/fpCategoria/fpMsg/fpBtnSalvar/fpEditandoAviso/
     fpCancelarEdicao) — mesma lógica de JS de salvar/cancelar edição do Resumo, copiada aqui
     (as duas telas têm cada uma seu próprio <form>/modal, não compartilham DOM entre
     páginas). -->
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
      <!-- Pedido do usuário (print do select de Categoria vazio): um jeito rápido de criar
           categoria sem perder o que já foi digitado no formulário. Abre numa aba nova
           (target=_blank) de propósito — fechar essa aba e voltar aqui mantém descrição/valor
           já preenchidos; a nova categoria só aparece na próxima vez que este modal abrir
           (não dá pra atualizar o <select> de um formulário que já está aberto sem recarregar
           a página). -->
      <a href="<?= url('/financeiro-pessoal/categorias') ?>" target="_blank" rel="noopener" class="fp-faint" style="font-size:.78rem;text-decoration:underline;align-self:flex-start;margin-top:-4px">+ Nova categoria</a>

      <div id="fpMsg" class="fp-muted" style="font-size:.82rem"></div>

      <button type="submit" class="fp-btn fp-btn-primary" id="fpBtnSalvar">Adicionar lançamento</button>
    </form>
  </div>
</div>

<section class="fp-lanc-col" aria-labelledby="fpLancTitulo">
  <div style="display:flex;gap:10px;align-items:flex-start">
    <div class="fp-filtros">
      <button type="button" class="fp-filtro-btn" data-filtro="receita" title="Entradas"><?= fp_icone('arrow-down-circle-fill') ?><span>Entradas</span></button>
      <button type="button" class="fp-filtro-btn" data-filtro="despesa" title="Saídas"><?= fp_icone('arrow-up-circle-fill') ?><span>Saídas</span></button>
    </div>
    <div style="flex:1;min-width:0">
      <h2 id="fpLancTitulo" class="fp-section-titulo" style="font-size:.78rem;text-transform:uppercase;letter-spacing:.03em;margin:0 0 8px">Lançamentos</h2>
      <div id="fpLista" style="display:flex;flex-direction:column;gap:8px"></div>
    </div>
  </div>
</section>

<!-- Escanear conta: pareamento com o celular por QR (desktop) — mesmo mecanismo genérico
     de ScannerController/scanner_sessoes já usado em outras telas do FixaOS, modo
     'financeiro_conta'. Em celular/tablet (temCameraPropria()), pula o QR e abre a câmera
     direto. Sem Bootstrap JS nesta área (layout próprio, "grana"/"fixa"), por isso modal
     próprio em CSS puro, não bootstrap.Modal. -->
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

<!-- Revisão simplificada (sem "modo" de conta a pagar numa lista — "Contas e débitos" foi
     removido do sistema de propósito, ver commits anteriores) — toda foto escaneada aqui
     sempre vira uma despesa direto nos lançamentos, nunca grava nada antes do usuário
     conferir/completar os campos e confirmar. -->
<div class="fp-modal-backdrop" id="modalRevisaoConta">
  <div class="fp-modal">
    <div class="fp-modal-header">
      <strong>Revisar antes de inserir</strong>
      <button type="button" class="fp-modal-close" id="btnFecharRevisao" aria-label="Fechar">×</button>
    </div>
    <img id="revisaoFotoImg" src="" alt="Foto da conta escaneada" style="width:100%;max-height:200px;object-fit:contain;border-radius:12px;background:var(--surf2);margin-bottom:8px">
    <div id="revisaoLendoAviso" class="fp-faint" style="display:none;font-size:.8rem;margin-bottom:10px">🔎 Lendo a conta automaticamente…</div>
    <form id="formRevisaoConta" style="display:flex;flex-direction:column;gap:10px">
      <input type="text" id="revisaoDescricao" class="fp-input" placeholder="Descrição (ex.: Conta de luz)" maxlength="150" required>
      <div>
        <div class="fp-row-valor-cat">
          <input type="number" id="revisaoValor" class="fp-input" placeholder="Valor (R$)" step="0.01" min="0.01" style="flex:1" required>
          <select id="revisaoCategoria" class="fp-select" style="flex:1">
            <?php foreach ($categorias as $chave => $c): ?>
            <option value="<?= e($chave) ?>"><?= e($c['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div id="revisaoConfiancaValor" class="fp-faint" style="display:none;font-size:.74rem;margin-top:4px"></div>
      </div>
      <div>
        <!-- Data do PAGAMENTO, não vencimento — sem lista de contas a pagar nesta rodada, a
             foto sempre vira um gasto já realizado; padrão hoje, troca na mão se for de um
             gasto de dias atrás. -->
        <input type="date" id="revisaoDataPagamento" class="fp-input" placeholder="Data do pagamento">
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
  var TEXTO_SALVAR_NOVO = 'Adicionar lançamento';
  var TEXTO_SALVAR_EDICAO = 'Salvar alterações';

  // Sem botão "Todos" (removido, pedido do usuário) — os dois que sobraram (Entradas/Saídas)
  // viraram togglável: clicar no já ativo desliga o filtro (volta pra 'todos', nenhum ícone
  // destacado), em vez de precisar de um terceiro botão só pra "ver tudo" de novo.
  document.querySelectorAll('.fp-filtro-btn').forEach(function (btn) {
    btn.onclick = function () {
      filtroAtivo = filtroAtivo === btn.dataset.filtro ? 'todos' : btn.dataset.filtro;
      document.querySelectorAll('.fp-filtro-btn').forEach(function (b) {
        b.classList.toggle('active', b.dataset.filtro === filtroAtivo);
      });
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
  // Mesma lógica de financeiro_pessoal_categoria_humanizar() (app/Helpers/functions.php) —
  // só usada quando a categoria do lançamento não bate com nenhuma de CATS (órfã), pra nunca
  // mostrar a chave crua ("alimentacao") direto na tela.
  function humanizarCategoria(chave) {
    return String(chave || '').replace(/[_-]/g, ' ').replace(/\b\w/g, function (c) { return c.toUpperCase(); });
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
      var cat = CATS[l.categoria] || { nome: humanizarCategoria(l.categoria), cor: 'var(--muted)' };
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

      // Chips clicáveis em vez de <select> — o select nativo abre o popup de opções com
      // renderização do próprio sistema operacional (fundo/realce que a CSS do site não
      // alcança), destoando feio do tema escuro. Chip por categoria, com o ponto colorido já
      // usado no resto da tela; clicar no corpo do chip já troca a categoria na hora. Cada
      // chip também tem um lápis (pedido do usuário: "quero editar a que já existe aqui") que
      // abre um formulário de nome/cor pra corrigir aquela categoria sem sair da tela — <span>
      // por fora (não <button>, evita aninhar <button> dentro de <button>) com role de botão
      // pra seleção, e um <button> de verdade só pro lápis.
      var catChipsHtml = Object.keys(CATS).map(function (k) {
        var ativo = k === l.categoria;
        return '<span class="fp-cat-chip' + (ativo ? ' active' : '') + '" data-id="' + l.id + '" data-cat="' + k + '" role="button" tabindex="0">' +
          '<span class="fp-cat-chip-dot" style="background:' + CATS[k].cor + '"></span>' + escapeHtml(CATS[k].nome) +
          '<button type="button" class="fp-cat-chip-edit" data-id="' + l.id + '" data-cat="' + k + '" title="Editar categoria" aria-label="Editar categoria ' + escapeHtml(CATS[k].nome) + '">✎</button>' +
        '</span>';
      }).join('');

      var corpo = document.createElement('div');
      corpo.className = 'fp-lanc-corpo' + (aberto ? ' show' : '');
      corpo.innerHTML =
        '<div style="padding-top:10px;border-top:1px solid var(--line)">' +
          '<div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">' +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-edit" data-id="' + l.id + '">Editar</button>' +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-del" data-id="' + l.id + '" style="color:var(--exp)">Excluir</button>' +
          '</div>' +
          '<div class="fp-faint" style="font-size:.74rem;margin-top:12px;margin-bottom:6px">Categoria</div>' +
          '<div style="display:flex;gap:6px;flex-wrap:wrap">' + catChipsHtml + '</div>' +
          '<div class="fp-cat-edit-form" data-id="' + l.id + '" style="display:none;gap:8px;align-items:center;margin-top:8px;flex-wrap:wrap">' +
            '<input type="text" class="fp-input fp-cat-edit-nome" placeholder="Nome da categoria" maxlength="40" style="flex:1;min-width:140px;padding:8px 12px;font-size:.85rem">' +
            '<input type="color" class="fp-cat-edit-cor" style="width:38px;height:38px;padding:2px;border-radius:8px;border:1.5px solid var(--line);background:var(--input);cursor:pointer">' +
            '<button type="button" class="fp-btn fp-btn-primary fp-btn-sm fp-cat-edit-salvar">Salvar</button>' +
            '<button type="button" class="fp-btn fp-btn-ghost fp-btn-sm fp-cat-edit-cancelar">Cancelar</button>' +
            '<span class="fp-cat-edit-msg fp-faint" style="font-size:.78rem;width:100%"></span>' +
          '</div>' +
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
    lista.querySelectorAll('.fp-cat-chip').forEach(function (btn) {
      btn.onclick = function () {
        if (btn.classList.contains('active')) return;
        trocarCategoria(btn.dataset.id, btn.dataset.cat);
      };
      btn.onkeydown = function (ev) {
        if (ev.key === 'Enter' || ev.key === ' ') { ev.preventDefault(); btn.click(); }
      };
    });
    lista.querySelectorAll('.fp-cat-chip-edit').forEach(function (btn) {
      btn.onclick = function (ev) {
        ev.stopPropagation();
        abrirEdicaoCategoriaInline(btn.dataset.id, btn.dataset.cat);
      };
    });
    lista.querySelectorAll('.fp-cat-edit-cancelar').forEach(function (btn) {
      btn.onclick = function () { btn.closest('.fp-cat-edit-form').style.display = 'none'; };
    });
    lista.querySelectorAll('.fp-cat-edit-salvar').forEach(function (btn) {
      btn.onclick = function () { salvarEdicaoCategoriaInline(btn.closest('.fp-cat-edit-form')); };
    });
  }

  // Troca só a categoria direto do chip dentro do card colapsado — reaproveita o mesmo
  // endpoint de editar() (atualizar() exige tipo/descricao/valor junto, não tem PATCH parcial
  // no servidor), mandando os valores que já estão em lancamentosAtuais sem abrir o modal.
  function trocarCategoria(id, novaCategoria) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    fetch('<?= url('/financeiro-pessoal') ?>/' + id + '/atualizar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ tipo: l.tipo, categoria: novaCategoria, descricao: l.descricao, valor: l.valor })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) { if (j.ok) carregar(); });
  }

  // Abre o formulário de editar nome/cor da categoria clicada no lápis — prepara com o que já
  // está salvo. Cor pode ser uma CSS var (categoria padrão, ex. "var(--cat-lazer)") que o
  // <input type="color"> não entende; nesse caso cai num hex neutro (escolher e salvar uma cor
  // nova "promove" a categoria pra hex fixo, mesmo efeito colateral já aceito na tela cheia de
  // Categorias).
  function abrirEdicaoCategoriaInline(lancId, chave) {
    var formEl = lista.querySelector('.fp-cat-edit-form[data-id="' + lancId + '"]');
    var info = CATS[chave];
    if (!formEl || !info) return;
    formEl.dataset.chave = chave;
    formEl.querySelector('.fp-cat-edit-nome').value = info.nome;
    formEl.querySelector('.fp-cat-edit-cor').value = /^#[0-9a-fA-F]{6}$/.test(info.cor) ? info.cor : '#7A6A88';
    formEl.querySelector('.fp-cat-edit-msg').textContent = '';
    formEl.style.display = 'flex';
    formEl.querySelector('.fp-cat-edit-nome').focus();
  }

  // Salva nome/cor da categoria sendo editada — atualiza CATS em memória e re-renderiza a
  // lista inteira (não só este card), pra todo chip que usa essa categoria (em qualquer
  // lançamento) já refletir o nome/cor novos sem precisar de F5.
  function salvarEdicaoCategoriaInline(formEl) {
    var chave = formEl.dataset.chave;
    var info = CATS[chave];
    var msgEl = formEl.querySelector('.fp-cat-edit-msg');
    if (!info || !info.id) {
      msgEl.textContent = 'Categoria não encontrada.';
      msgEl.style.color = 'var(--exp)';
      return;
    }
    var nome = formEl.querySelector('.fp-cat-edit-nome').value.trim();
    var cor = formEl.querySelector('.fp-cat-edit-cor').value;
    if (!nome) {
      msgEl.textContent = 'Dê um nome pra categoria.';
      msgEl.style.color = 'var(--exp)';
      return;
    }
    msgEl.textContent = '';
    fetch('<?= url('/api/financeiro-pessoal/categorias') ?>/' + info.id + '/editar', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ nome: nome, cor: cor })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        if (!j.ok) {
          msgEl.textContent = j.erro || 'Não deu pra salvar.';
          msgEl.style.color = 'var(--exp)';
          return;
        }
        CATS[chave].nome = j.nome;
        CATS[chave].cor = j.cor;
        aplicarFiltroEExibir();
      });
  }

  function iniciarEdicao(id) {
    var l = lancamentosAtuais.filter(function (x) { return String(x.id) === String(id); })[0];
    if (!l) return;
    editandoId = id;
    marcarTipo(l.tipo);
    document.getElementById('fpDescricao').value = l.descricao;
    document.getElementById('fpValor').value = l.valor;
    // Lançamento antigo pode ter uma categoria que não existe mais (renomeada/excluída) ou
    // vazia (resíduo de antes do fallback 'outros' no servidor) — nesse caso .value não acha
    // nenhuma <option> e o select fica sem nada marcado (selectedIndex -1), aparecendo em
    // branco pro usuário. Cai na primeira opção disponível em vez de deixar vazio.
    var catSelect = document.getElementById('fpCategoria');
    catSelect.value = l.categoria;
    if (catSelect.selectedIndex === -1 && catSelect.options.length) {
      catSelect.selectedIndex = 0;
    }
    editandoAviso.style.display = 'flex';
    btnSalvar.textContent = TEXTO_SALVAR_EDICAO;
    msg.textContent = '';
    abrirModal(modalLancamento);
  }

  function cancelarEdicao() {
    editandoId = null;
    form.reset();
    marcarTipo('despesa');
    editandoAviso.style.display = 'none';
    btnSalvar.textContent = TEXTO_SALVAR_NOVO;
    msg.textContent = '';
    fecharModal(modalLancamento);
  }

  document.getElementById('fpCancelarEdicao').onclick = function (ev) {
    ev.preventDefault();
    cancelarEdicao();
  };

  // "+ Adicionar lançamento" abre limpo (cancelarEdicao() já garante estado zerado, mesmo
  // que o modal tenha ficado em modo edição de uma vez anterior); "×" fecha do mesmo jeito.
  document.getElementById('btnNovoLancamento').onclick = function () {
    cancelarEdicao();
    abrirModal(modalLancamento);
  };
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

  // ────────────────────────────────────────────────────────────────────
  // Escanear conta — câmera pelo celular. No PC, parea por QR (mesmo
  // mecanismo genérico de ScannerController, modo 'financeiro_conta');
  // em celular/tablet, abre a câmera direto (mesmo aparelho que já está
  // com a tela aberta).
  // ────────────────────────────────────────────────────────────────────
  var scanInputDireto = document.getElementById('scanInputDireto');
  var modalScanQr = document.getElementById('modalScanQr');
  var modalRevisaoConta = document.getElementById('modalRevisaoConta');
  var scanToken = null;
  var scanTimer = null;

  function temCameraPropria() {
    return ('ontouchstart' in window || navigator.maxTouchPoints > 0) && window.innerWidth <= 991;
  }

  // Rejeita (em vez de travar pra sempre) quando o navegador não consegue DECODIFICAR o
  // arquivo escolhido — antes não tinha onerror nenhum aqui: escolher uma foto da GALERIA
  // num formato que o <img>/canvas do navegador não lê (ex.: HEIC — bem comum em fotos já
  // salvas no aparelho, diferente da captura direta da câmera, que o navegador sempre
  // normaliza pra JPEG) fazia o img.onload nunca disparar — a Promise ficava pendurada pra
  // sempre, o modal de revisão nunca chegava a abrir, e o botão "Escanear conta" parecia
  // simplesmente não ter feito nada (bug relatado pelo usuário: "falha de conexão" ao
  // escolher da galeria do celular — a causa real nunca foi rede, era decodificação).
  function comprimirImagem(file) {
    var suportaWebp = (function () {
      var c = document.createElement('canvas'); c.width = c.height = 1;
      return c.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    })();
    return new Promise(function (resolve, reject) {
      var reader = new FileReader();
      reader.onerror = function () { reject(new Error('Não deu pra ler o arquivo escolhido.')); };
      reader.onload = function (e) {
        var img = new Image();
        img.onerror = function () { reject(new Error('Formato de imagem não suportado.')); };
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

  function abrirScan() {
    if (temCameraPropria()) { scanInputDireto.click(); return; }
    abrirModalQr();
  }
  document.getElementById('btnEscanearConta').onclick = abrirScan;

  scanInputDireto.addEventListener('change', function () {
    if (!scanInputDireto.files.length) return;
    comprimirImagem(scanInputDireto.files[0])
      .then(function (dataUrl) {
        abrirRevisao(dataUrl, null, true); // abre já em "lendo..." — mesmo aparelho, sem QR
        fetch('<?= url('/financeiro-pessoal/ocr-conta') ?>', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
          body: 'foto=' + encodeURIComponent(dataUrl)
        })
          .then(function (r) { return r.json(); })
          .then(function (j) { aplicarExtraido(j.ok ? j.extraido : null); })
          .catch(function () { aplicarExtraido(null); });
      })
      .catch(function () {
        // Mesma causa mais provável documentada acima (HEIC/formato não suportado) — orienta
        // pro caminho que sempre funciona (câmera, que o navegador já normaliza pra JPEG) em
        // vez de só dizer "deu erro".
        alert('Não conseguimos abrir essa foto (formato não suportado pelo navegador). Tente tirar uma foto nova pela câmera, ou escolher outra imagem (JPG/PNG) da galeria.');
      });
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
          if (fotos.length) abrirRevisao(fotos[0], j.resultado.extraido || null, false);
        }, 700);
      });
  }

  document.getElementById('btnFecharScanQr').onclick = function () {
    if (scanTimer) { clearInterval(scanTimer); scanTimer = null; }
    fecharModal(modalScanQr);
  };

  // ── Revisão: nada entra no sistema sem o usuário conferir/completar os campos ──────────
  var revisaoDataPagamento = document.getElementById('revisaoDataPagamento');
  var btnRevisaoSalvar = document.getElementById('btnRevisaoSalvar');
  var revisaoMsg = document.getElementById('revisaoMsg');

  function atualizarTextoBotaoRevisao() {
    var v = parseFloat(document.getElementById('revisaoValor').value) || 0;
    btnRevisaoSalvar.textContent = v > 0 ? 'Inserir ' + fmtValor(v) + ' no sistema' : 'Inserir no sistema';
  }
  document.getElementById('revisaoValor').addEventListener('input', atualizarTextoBotaoRevisao);

  // categoriaSugerida guarda o que a IA (ou a regra aprendida) sugeriu nesta revisão — serve
  // só pra comparar com a categoria final no submit e decidir se vale gravar uma correção
  // nova (ver talvezAprenderCategoria() no submit, mais abaixo).
  var categoriaSugerida = null;
  var revisaoLendoAviso = document.getElementById('revisaoLendoAviso');
  var revisaoConfValor = document.getElementById('revisaoConfiancaValor');

  function abrirRevisao(fotoDataUrl, extraido, carregando) {
    document.getElementById('revisaoFotoImg').src = fotoDataUrl;
    document.getElementById('revisaoDescricao').value = '';
    document.getElementById('revisaoValor').value = '';
    // Padrão: data do pagamento = hoje — cobre o caso comum (foto tirada na hora da compra);
    // o usuário troca na mão se a conta escaneada for de um gasto de dias atrás.
    revisaoDataPagamento.value = new Date().toISOString().slice(0, 10);
    document.getElementById('revisaoCategoria').value = 'outros';
    revisaoMsg.textContent = '';
    revisaoConfValor.style.display = 'none';
    categoriaSugerida = null;
    atualizarTextoBotaoRevisao();

    abrirModal(modalRevisaoConta);

    revisaoLendoAviso.style.display = carregando ? 'block' : 'none';
    if (!carregando) {
      aplicarExtraido(extraido);
      document.getElementById('revisaoDescricao').focus();
    }
  }

  // Preenche os campos com o que a IA leu da foto (ou limpa o aviso de "lendo..." se não
  // conseguiu/não tem IA configurada — nesse caso o formulário segue vazio, preenchimento
  // manual de sempre, sem erro nenhum pro usuário).
  function aplicarExtraido(extraido) {
    revisaoLendoAviso.style.display = 'none';
    if (!extraido) { document.getElementById('revisaoDescricao').focus(); return; }

    if (extraido.descricao) document.getElementById('revisaoDescricao').value = extraido.descricao;
    if (extraido.valor > 0) document.getElementById('revisaoValor').value = extraido.valor.toFixed(2);
    if (extraido.categoria) {
      document.getElementById('revisaoCategoria').value = extraido.categoria;
      categoriaSugerida = extraido.categoria;
    }
    atualizarTextoBotaoRevisao();

    if (extraido.confianca && extraido.valor > 0) {
      var baixa = extraido.confianca.valor === 'baixa';
      revisaoConfValor.style.display = 'block';
      revisaoConfValor.innerHTML = baixa
        ? '<span style="color:var(--warn)">⚠ confira o valor, a leitura não ficou clara</span>'
        : '<span style="color:var(--inc)">✓ lido automaticamente</span>';
    }
    document.getElementById('revisaoDescricao').focus();
  }

  // Só grava a correção quando a IA de fato sugeriu algo (categoriaSugerida não-nulo) E o
  // usuário trocou pra outra — sem isso aprenderia até quando a categoria já veio certa.
  // Fire-and-forget: não bloqueia o fluxo de inserir, não mostra erro se falhar.
  function talvezAprenderCategoria(descricao, categoriaEscolhida) {
    if (!categoriaSugerida || categoriaSugerida === categoriaEscolhida || !descricao) return;
    fetch('<?= url('/financeiro-pessoal/aprender-categoria') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ beneficiario: descricao, categoria: categoriaEscolhida })
    }).catch(function () {});
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
    // Sem hora digitada pelo usuário (só o <input type="date">, "YYYY-MM-DD") — o servidor
    // já aceita isso direto em data_hora (strtotime() entende data sem hora, MySQL completa
    // com 00:00:00). Vazio/inválido cai no fallback de sempre do servidor (agora).
    var dataPagamento = revisaoDataPagamento.value;
    fetch('<?= url('/financeiro-pessoal') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': csrfToken },
      body: new URLSearchParams({ tipo: 'despesa', categoria: categoria, descricao: descricao, valor: valor, origem: 'foto', data_hora: dataPagamento })
    })
      .then(function (r) { return r.json(); })
      .then(function (j) {
        btnRevisaoSalvar.disabled = false;
        if (!j.ok) { revisaoMsg.innerHTML = '<span style="color:var(--exp)">' + (j.erro || 'Não deu pra salvar agora.') + '</span>'; return; }
        talvezAprenderCategoria(descricao, categoria);
        fecharModal(modalRevisaoConta);
        carregar();
      })
      .catch(function () {
        btnRevisaoSalvar.disabled = false;
        revisaoMsg.innerHTML = '<span style="color:var(--exp)">Falha de conexão, tenta de novo.</span>';
      });
  });

  // Abrir/fechar modal (CSS puro, sem Bootstrap JS nesta área isolada) — mesmo helper usado
  // no Resumo (ver index.php), copiado aqui porque as duas telas não compartilham <script>.
  function abrirModal(el) { el.classList.add('show'); }
  function fecharModal(el) { el.classList.remove('show'); }
})();
</script>
<?php endif; ?>
