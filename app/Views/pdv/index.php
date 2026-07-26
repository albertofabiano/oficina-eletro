<div id="pdvCsrf" style="display:none"><?= csrf_field() ?></div>

<div class="row g-3">
  <!-- Coluna esquerda: busca + carrinho -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-body">
        <label class="form-label small fw-semibold mb-1"><i class="bi bi-upc-scan me-1"></i>Buscar produto (nome, código ou código de barras)</label>
        <div class="position-relative">
          <input type="text" id="busca" class="form-control form-control-lg" autocomplete="off"
                 placeholder="Digite e tecle Enter para adicionar o primeiro resultado...">
          <div id="resultados" class="list-group position-absolute w-100 shadow" style="z-index:20;max-height:320px;overflow:auto"></div>
        </div>
        <button type="button" id="btnAvulso" class="btn btn-sm btn-outline-secondary mt-2">
          <i class="bi bi-plus-circle"></i> Adicionar item avulso (não cadastrado)
        </button>
      </div>
    </div>

    <div class="card border-0 shadow-sm">
      <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
          <thead class="table-light">
            <tr><th>Item</th><th style="width:120px">Qtd</th><th class="text-end" style="width:110px">Unit.</th><th class="text-end" style="width:110px">Total</th><th style="width:40px"></th></tr>
          </thead>
          <tbody id="carrinhoBody">
            <tr id="carrinhoVazio"><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-3 d-block mb-2"></i>Carrinho vazio. Busque um produto acima.</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Coluna direita: pagamento -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm" style="position:sticky;top:1rem">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-cash-coin me-1"></i>Pagamento</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Cliente (opcional)</label>
          <input type="hidden" id="clienteId" value="">
          <div id="clienteVazioPdv" class="d-flex gap-2">
            <div class="position-relative flex-grow-1">
              <input type="text" id="buscaCliente" class="form-control" autocomplete="off" placeholder="Buscar cliente por nome, telefone ou CPF...">
              <div id="resultadosCliente" class="list-group position-absolute w-100 shadow" style="z-index:20;max-height:260px;overflow:auto"></div>
            </div>
            <button type="button" class="btn btn-outline-primary flex-shrink-0" id="btnNovoClientePdv" title="Cadastrar novo cliente">
              <i class="bi bi-person-plus"></i>
            </button>
          </div>
          <div id="clienteSelecionadoPdv" class="d-flex align-items-center justify-content-between border rounded p-2 mt-1" style="display:none">
            <div>
              <div class="fw-semibold small" id="clienteSelNome"></div>
              <div class="text-muted small" id="clienteSelTel"></div>
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnLimparCliente" title="Remover / vender sem cadastro">
              <i class="bi bi-x-lg"></i>
            </button>
          </div>
        </div>

          <div class="mb-2">
            <label class="form-label small fw-semibold">Desconto (R$)</label>
            <input type="text" id="desconto" class="form-control" inputmode="decimal" placeholder="0,00" value="">
          </div>

          <div class="d-flex justify-content-between align-items-center mb-1">
            <label class="form-label small fw-semibold mb-0">Pagamento</label>
            <span class="small" id="pdvRestante"></span>
          </div>
          <div id="pagamentosPdv"></div>
          <button type="button" id="btnAddPagamento" class="btn btn-sm btn-outline-secondary w-100 mb-2">
            <i class="bi bi-plus-circle me-1"></i>Dividir em outra forma de pagamento
          </button>

          <div id="pdvRepassarWrap" class="mb-2 small d-flex align-items-center gap-2 flex-wrap" style="display:none">
            <span class="fw-semibold">Taxa de cartão por conta de:</span>
            <div class="form-check form-check-inline m-0"><input class="form-check-input" type="radio" name="pdvRepassar" value="0" id="pdvRepEmpresa" checked><label class="form-check-label" for="pdvRepEmpresa">Empresa</label></div>
            <div class="form-check form-check-inline m-0"><input class="form-check-input" type="radio" name="pdvRepassar" value="1" id="pdvRepCliente"><label class="form-check-label" for="pdvRepCliente">Cliente</label></div>
          </div>

          <div id="boxRecebido" class="mb-2" style="display:none">
            <label class="form-label small fw-semibold">Valor recebido (R$)</label>
            <input type="text" id="recebido" class="form-control" inputmode="decimal" placeholder="0,00">
          </div>

        <hr>
        <div class="d-flex justify-content-between small text-muted"><span>Subtotal</span><span id="lblSubtotal">R$ 0,00</span></div>
        <div class="d-flex justify-content-between small text-muted" id="linhaDesc" style="display:none!important"><span>Desconto</span><span id="lblDesc">R$ 0,00</span></div>
        <div class="d-flex justify-content-between align-items-center mt-1">
          <span class="fw-bold fs-5">TOTAL</span><span class="fw-bold fs-3 text-success" id="lblTotal">R$ 0,00</span>
        </div>
        <div class="d-flex justify-content-between small" id="linhaTroco" style="display:none"><span>Troco</span><span id="lblTroco" class="fw-semibold">R$ 0,00</span></div>

        <button type="button" id="btnFinalizar" class="btn btn-success btn-lg w-100 mt-3" disabled>
          <i class="bi bi-check2-circle me-1"></i>Finalizar venda
        </button>
        <button type="button" id="btnLimpar" class="btn btn-link btn-sm w-100 text-muted mt-1">Limpar carrinho</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Novo Cliente (rápido, PDV) -->
<div class="modal fade" id="modalNovoClientePdv" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="formNovoClientePdv" novalidate>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-person-plus text-primary me-2"></i>Novo cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          <label class="form-label small fw-semibold">Nome *</label>
          <input type="text" name="nome" id="ncpNome" class="form-control" required placeholder="Nome do cliente">
        </div>
        <div class="row g-2">
          <div class="col-6">
            <label class="form-label small fw-semibold">Telefone / WhatsApp</label>
            <input type="text" name="telefone" id="ncpTelefone" class="form-control" placeholder="(00) 00000-0000">
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">CPF / CNPJ (opcional)</label>
            <input type="text" name="cpf_cnpj" id="ncpDoc" class="form-control" placeholder="Opcional">
          </div>
        </div>
        <div class="alert alert-danger py-2 small mt-3 d-none" id="erroNovoClientePdv"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary" id="btnSalvarClientePdv">
          <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerClientePdv"></span>Salvar e selecionar
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var API = '<?= url('/api/produtos') ?>';
  var FINALIZAR = '<?= url('/pdv/finalizar') ?>';
  var token = document.querySelector('#pdvCsrf input[name="_token"]').value;
  var carrinho = [];

  function brl(v) {
    v = Number(v) || 0;
    return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  function num(str) { return parseFloat(String(str).replace(/\./g, '').replace(',', '.')) || 0; }

  var busca = document.getElementById('busca');
  var resultados = document.getElementById('resultados');
  var ultimosResultados = [];
  var timer = null;

  busca.addEventListener('input', function () {
    clearTimeout(timer);
    var q = busca.value.trim();
    if (q.length < 2) { resultados.innerHTML = ''; ultimosResultados = []; return; }
    timer = setTimeout(function () { buscar(q); }, 250);
  });
  busca.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') { e.preventDefault(); if (ultimosResultados.length) { addProduto(ultimosResultados[0]); limparBusca(); } }
  });

  function buscar(q) {
    fetch(API + '?q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (lista) {
        ultimosResultados = lista || [];
        if (!ultimosResultados.length) { resultados.innerHTML = '<div class="list-group-item text-muted small">Nenhum produto encontrado.</div>'; return; }
        resultados.innerHTML = ultimosResultados.map(function (p, i) {
          var semEstoque = Number(p.estoque_atual) <= 0;
          return '<button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" data-i="' + i + '">' +
            '<span><strong>' + esc(p.nome) + '</strong>' + (p.codigo ? ' <span class="text-muted small">' + esc(p.codigo) + '</span>' : '') +
            '<br><span class="small ' + (semEstoque ? 'text-danger' : 'text-muted') + '">Estoque: ' + (Number(p.estoque_atual) + 0) + ' ' + esc(p.unidade || '') + (semEstoque ? ' (sem estoque)' : '') + '</span></span>' +
            '<span class="fw-semibold text-success">' + brl(p.valor_venda) + '</span></button>';
        }).join('');
        resultados.querySelectorAll('[data-i]').forEach(function (b) {
          b.addEventListener('click', function () { addProduto(ultimosResultados[+b.dataset.i]); limparBusca(); });
        });
      });
  }
  function limparBusca() { busca.value = ''; resultados.innerHTML = ''; ultimosResultados = []; busca.focus(); }

  document.getElementById('btnAvulso').addEventListener('click', function () {
    var desc = prompt('Descrição do item avulso:');
    if (!desc) return;
    var val = prompt('Valor unitário (R$):', '0,00');
    if (val === null) return;
    carrinho.push({ produto_id: null, descricao: desc.trim(), quantidade: 1, valor_unitario: num(val), estoque: Infinity });
    render();
  });

  function addProduto(p) {
    var estoque = Number(p.estoque_atual);
    if (estoque <= 0) { alert('Produto sem estoque — a venda seria bloqueada. Dê entrada no estoque primeiro.'); return; }
    var ex = carrinho.find(function (i) { return i.produto_id === Number(p.id); });
    if (ex) {
      if (ex.quantidade + 1 > estoque) { alert('Estoque disponível: ' + estoque); return; }
      ex.quantidade++;
    } else {
      carrinho.push({ produto_id: Number(p.id), descricao: p.nome, quantidade: 1, valor_unitario: Number(p.valor_venda), estoque: estoque });
    }
    render();
  }

  function render() {
    var body = document.getElementById('carrinhoBody');
    if (!carrinho.length) {
      body.innerHTML = '<tr id="carrinhoVazio"><td colspan="5" class="text-center text-muted py-5"><i class="bi bi-cart3 fs-3 d-block mb-2"></i>Carrinho vazio. Busque um produto acima.</td></tr>';
    } else {
      body.innerHTML = carrinho.map(function (it, i) {
        return '<tr>' +
          '<td>' + esc(it.descricao) + (it.produto_id ? '' : ' <span class="badge bg-secondary">avulso</span>') + '</td>' +
          '<td><input type="number" min="0.001" step="1" value="' + it.quantidade + '" data-qi="' + i + '" class="form-control form-control-sm" style="width:90px"></td>' +
          '<td class="text-end">' + brl(it.valor_unitario) + '</td>' +
          '<td class="text-end fw-semibold">' + brl(it.quantidade * it.valor_unitario) + '</td>' +
          '<td class="text-end"><button type="button" class="btn btn-sm btn-outline-danger" data-rem="' + i + '"><i class="bi bi-trash"></i></button></td>' +
          '</tr>';
      }).join('');
      body.querySelectorAll('[data-qi]').forEach(function (inp) {
        inp.addEventListener('input', function () {
          var i = +inp.dataset.qi, q = parseFloat(inp.value.replace(',', '.')) || 0;
          if (carrinho[i].estoque !== Infinity && q > carrinho[i].estoque) { q = carrinho[i].estoque; inp.value = q; alert('Estoque disponível: ' + carrinho[i].estoque); }
          carrinho[i].quantidade = q; totais();
        });
      });
      body.querySelectorAll('[data-rem]').forEach(function (b) {
        b.addEventListener('click', function () { carrinho.splice(+b.dataset.rem, 1); render(); });
      });
    }
    totais();
  }

  function totais() {
    var sub = carrinho.reduce(function (s, i) { return s + i.quantidade * i.valor_unitario; }, 0);
    var desc = num(document.getElementById('desconto').value);
    if (desc > sub) desc = sub;
    var total = sub - desc;
    document.getElementById('lblSubtotal').textContent = brl(sub);
    document.getElementById('lblDesc').textContent = '- ' + brl(desc);
    document.getElementById('linhaDesc').style.display = desc > 0 ? 'flex' : 'none';
    document.getElementById('lblTotal').textContent = brl(total);

    var unicaDinheiro = linhasPag.length === 1 && linhasPag[0].forma === 'dinheiro';
    document.getElementById('boxRecebido').style.display = unicaDinheiro ? 'block' : 'none';
    var receb = num(document.getElementById('recebido').value);
    var troco = (unicaDinheiro && receb > total) ? receb - total : 0;
    document.getElementById('linhaTroco').style.display = troco > 0 ? 'flex' : 'none';
    document.getElementById('lblTroco').textContent = brl(troco);

    document.getElementById('btnFinalizar').disabled = carrinho.length === 0 || total < 0;
    atualizarRestantePdv(total);
  }

  // ── Pagamento dividido: lista de linhas (forma + valor, parcelas/taxa quando cartão) ──
  var TAXAS_PDV = <?= json_encode(json_decode(($taxasCartao ?? '') ?: '{}', true) ?: new \stdClass()) ?>;
  var linhasPag = [{ forma: 'dinheiro', valor: '' }];
  function pdvBrl(n){ return (isFinite(n)?n:0).toLocaleString('pt-BR',{style:'currency',currency:'BRL'}); }
  function taxaPadrao(forma, parcelas){
    if (forma === 'cartao_debito') return TAXAS_PDV.debito || 0;
    if (forma === 'cartao_credito') { var c = TAXAS_PDV.credito || {}; return c[parcelas] !== undefined ? c[parcelas] : 0; }
    return 0;
  }
  function renderPagamentosPdv(){
    var cont = document.getElementById('pagamentosPdv');
    var temCartao = linhasPag.some(function(l){ return l.forma === 'cartao_credito' || l.forma === 'cartao_debito'; });
    document.getElementById('pdvRepassarWrap').style.display = temCartao ? 'flex' : 'none';

    cont.innerHTML = linhasPag.map(function (lin, i) {
      var ehCredito = lin.forma === 'cartao_credito';
      var ehCartao  = ehCredito || lin.forma === 'cartao_debito';
      var parcelasOpts = '';
      if (ehCredito) {
        var cred = TAXAS_PDV.credito || {};
        for (var p = 1; p <= 12; p++) {
          if (cred[p] === undefined && p !== 1) continue;
          parcelasOpts += '<option value="' + p + '"' + (Number(lin.parcelas) === p ? ' selected' : '') + '>' + p + 'x' + (cred[p] !== undefined ? ' — ' + parseFloat(cred[p]).toFixed(2).replace('.', ',') + '%' : '') + '</option>';
        }
      }
      return '<div class="border rounded p-2 mb-2" data-linha="' + i + '">' +
        (linhasPag.length > 1 ? '<div class="d-flex justify-content-end mb-1"><button type="button" class="btn btn-sm btn-link text-danger p-0 lh-1 pdv-rem-linha" data-i="' + i + '"><i class="bi bi-x-lg"></i></button></div>' : '') +
        '<div class="row g-2">' +
          '<div class="col-6"><select class="form-select form-select-sm pdv-linha-forma" data-i="' + i + '">' +
            '<option value="dinheiro"' + (lin.forma==='dinheiro'?' selected':'') + '>Dinheiro</option>' +
            '<option value="pix"' + (lin.forma==='pix'?' selected':'') + '>PIX</option>' +
            '<option value="cartao_credito"' + (lin.forma==='cartao_credito'?' selected':'') + '>Cartão crédito</option>' +
            '<option value="cartao_debito"' + (lin.forma==='cartao_debito'?' selected':'') + '>Cartão débito</option>' +
            '<option value="outro"' + (lin.forma==='outro'?' selected':'') + '>Outro</option>' +
          '</select></div>' +
          '<div class="col-6"><input type="text" class="form-control form-control-sm pdv-linha-valor" data-i="' + i + '" inputmode="decimal" placeholder="0,00" value="' + (lin.valor || '') + '"></div>' +
        '</div>' +
        (ehCartao ? (
          '<div class="row g-2 mt-1">' +
            (ehCredito ? '<div class="col-6"><select class="form-select form-select-sm pdv-linha-parcelas" data-i="' + i + '">' + parcelasOpts + '</select></div>' : '') +
            '<div class="col-' + (ehCredito ? '6' : '12') + '"><div class="input-group input-group-sm"><input type="number" class="form-control pdv-linha-taxa" data-i="' + i + '" min="0" max="100" step="0.01" value="' + (lin.taxa != null ? lin.taxa : '') + '"><span class="input-group-text">%</span></div></div>' +
          '</div>'
        ) : '') +
      '</div>';
    }).join('');

    cont.querySelectorAll('.pdv-linha-forma').forEach(function (sel) {
      sel.addEventListener('change', function () {
        var i = +this.dataset.i;
        linhasPag[i].forma = this.value;
        if (this.value === 'cartao_credito' || this.value === 'cartao_debito') {
          linhasPag[i].parcelas = linhasPag[i].parcelas || 1;
          linhasPag[i].taxa = taxaPadrao(this.value, linhasPag[i].parcelas);
        }
        renderPagamentosPdv(); totais();
      });
    });
    cont.querySelectorAll('.pdv-linha-valor').forEach(function (inp) {
      inp.addEventListener('input', function () { linhasPag[+this.dataset.i].valor = this.value; atualizarRestantePdv(); });
    });
    cont.querySelectorAll('.pdv-linha-parcelas').forEach(function (sel) {
      sel.addEventListener('change', function () {
        var i = +this.dataset.i;
        linhasPag[i].parcelas = this.value;
        linhasPag[i].taxa = taxaPadrao(linhasPag[i].forma, this.value);
        renderPagamentosPdv();
      });
    });
    cont.querySelectorAll('.pdv-linha-taxa').forEach(function (inp) {
      inp.addEventListener('input', function () { linhasPag[+this.dataset.i].taxa = this.value; });
    });
    cont.querySelectorAll('.pdv-rem-linha').forEach(function (b) {
      b.addEventListener('click', function () { linhasPag.splice(+this.dataset.i, 1); renderPagamentosPdv(); totais(); });
    });
  }
  document.getElementById('btnAddPagamento').addEventListener('click', function () {
    linhasPag.push({ forma: 'dinheiro', valor: '' });
    renderPagamentosPdv(); totais();
  });
  function atualizarRestantePdv(total){
    if (typeof total !== 'number'){ var sub = carrinho.reduce(function(s,i){return s+i.quantidade*i.valor_unitario;},0); total = sub - num(document.getElementById('desconto').value); if (total<0) total=0; }
    var soma = linhasPag.reduce(function (s, l) { return s + num(l.valor); }, 0);
    var restante = total - soma;
    var el = document.getElementById('pdvRestante');
    if (restante > 0.004) { el.className = 'small text-danger fw-semibold'; el.textContent = 'Falta ' + brl(restante); }
    else { el.className = 'small text-success fw-semibold'; el.textContent = 'Coberto ✓'; }
  }
  renderPagamentosPdv();

  ['desconto', 'recebido'].forEach(function (id) { document.getElementById(id).addEventListener('input', totais); });
  document.getElementById('btnLimpar').addEventListener('click', function () { if (confirm('Limpar o carrinho?')) { carrinho = []; render(); } });

  document.getElementById('btnFinalizar').addEventListener('click', function () {
    if (!carrinho.length) return;
    var btn = this; btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Registrando...';
    var pagamentosEnvio = linhasPag
      .filter(function (l) { return l.forma && num(l.valor) > 0; })
      .map(function (l) { return { forma: l.forma, valor: num(l.valor), parcelas: l.parcelas || 1, taxa: num(l.taxa) }; });
    var fd = new FormData();
    fd.append('_token', token);
    fd.append('itens', JSON.stringify(carrinho));
    fd.append('cliente_id', document.getElementById('clienteId').value);
    fd.append('desconto', document.getElementById('desconto').value || '0');
    fd.append('pagamentos', JSON.stringify(pagamentosEnvio));
    fd.append('valor_recebido', document.getElementById('recebido').value || '0');
    fd.append('cartao_repassar', (document.querySelector('input[name="pdvRepassar"]:checked') || {}).value || '0');
    fetch(FINALIZAR, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.ok) { window.location.href = res.redirect; }
        else { alert(res.erro || 'Erro ao finalizar.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Finalizar venda'; }
      })
      .catch(function () { alert('Falha de conexão.'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i>Finalizar venda'; });
  });

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

  // ── Cliente (busca + cadastro rápido, opcional) ──
  var API_CLI = '<?= url('/api/clientes') ?>';
  var buscaCliente = document.getElementById('buscaCliente');
  var resultadosCliente = document.getElementById('resultadosCliente');
  var ultimosClientes = [];
  var timerCli = null;

  function selecionarCliente(c) {
    document.getElementById('clienteId').value = c.id;
    document.getElementById('clienteSelNome').textContent = c.nome;
    document.getElementById('clienteSelTel').textContent = c.telefone || c.whatsapp || '';
    document.getElementById('clienteVazioPdv').style.display = 'none';
    document.getElementById('clienteSelecionadoPdv').style.display = '';
    buscaCliente.value = ''; resultadosCliente.innerHTML = ''; ultimosClientes = [];
  }

  buscaCliente.addEventListener('input', function () {
    clearTimeout(timerCli);
    var q = buscaCliente.value.trim();
    if (q.length < 2) { resultadosCliente.innerHTML = ''; ultimosClientes = []; return; }
    timerCli = setTimeout(function () { buscarCliente(q); }, 250);
  });

  function buscarCliente(q) {
    fetch(API_CLI + '?q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (lista) {
        ultimosClientes = lista || [];
        if (!ultimosClientes.length) { resultadosCliente.innerHTML = '<div class="list-group-item text-muted small">Nenhum cliente encontrado.</div>'; return; }
        resultadosCliente.innerHTML = ultimosClientes.map(function (c, i) {
          return '<button type="button" class="list-group-item list-group-item-action" data-i="' + i + '">' +
            '<strong>' + esc(c.nome) + '</strong>' +
            (c.telefone ? ' <span class="text-muted small">' + esc(c.telefone) + '</span>' : '') +
            '</button>';
        }).join('');
        resultadosCliente.querySelectorAll('[data-i]').forEach(function (b) {
          b.addEventListener('click', function () { selecionarCliente(ultimosClientes[+b.dataset.i]); });
        });
      });
  }

  document.getElementById('btnLimparCliente').addEventListener('click', function () {
    document.getElementById('clienteId').value = '';
    document.getElementById('clienteSelecionadoPdv').style.display = 'none';
    document.getElementById('clienteVazioPdv').style.display = '';
    buscaCliente.focus();
  });

  var modalNovoClientePdv = null;
  document.getElementById('btnNovoClientePdv').addEventListener('click', function () {
    if (!modalNovoClientePdv) modalNovoClientePdv = new bootstrap.Modal(document.getElementById('modalNovoClientePdv'));
    document.getElementById('formNovoClientePdv').reset();
    document.getElementById('erroNovoClientePdv').classList.add('d-none');
    document.getElementById('ncpNome').value = buscaCliente.value.trim();
    modalNovoClientePdv.show();
  });

  document.getElementById('formNovoClientePdv').addEventListener('submit', function (e) {
    e.preventDefault();
    var btn = document.getElementById('btnSalvarClientePdv');
    var erro = document.getElementById('erroNovoClientePdv');
    erro.classList.add('d-none');
    btn.disabled = true;
    document.getElementById('spinnerClientePdv').classList.remove('d-none');
    var fd = new FormData();
    fd.append('_token', token);
    fd.append('nome', document.getElementById('ncpNome').value.trim());
    fd.append('telefone', document.getElementById('ncpTelefone').value.trim());
    fd.append('cpf_cnpj', document.getElementById('ncpDoc').value.trim());
    fetch(API_CLI, { method: 'POST', body: fd })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        btn.disabled = false;
        document.getElementById('spinnerClientePdv').classList.add('d-none');
        if (res.success && res.cliente) {
          modalNovoClientePdv.hide();
          selecionarCliente(res.cliente);
        } else {
          erro.textContent = res.error || 'Não foi possível salvar o cliente.';
          erro.classList.remove('d-none');
        }
      })
      .catch(function () {
        btn.disabled = false;
        document.getElementById('spinnerClientePdv').classList.add('d-none');
        erro.textContent = 'Falha de conexão.';
        erro.classList.remove('d-none');
      });
  });

  busca.focus();
})();
</script>
