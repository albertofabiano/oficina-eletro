<style>
.crm-col-body { min-height: 200px; transition: background .12s; }
.crm-col-body.drag-over { background: #eef2ff; outline: 2px dashed #6366f1; outline-offset: -2px; }
.crm-card { cursor: grab; user-select: none; }
.crm-card:active { cursor: grabbing; }
.crm-card.dragging { opacity: .4; }
</style>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="text-muted small">Arraste um card entre as colunas pra mudar o estágio, ou clique nele pra editar.</div>
  <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalOportunidade" onclick="abrirModalOportunidade(null)">
    <i class="bi bi-plus-lg me-1"></i>Nova oportunidade
  </button>
</div>

<div class="d-flex gap-3 overflow-auto pb-3">
  <?php foreach ($estagios as $estagio): ?>
  <div class="flex-shrink-0" style="width:280px">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center" style="border-left:4px solid <?= e($estagio['cor']) ?>">
        <span><?= e($estagio['nome']) ?></span>
        <span class="badge bg-secondary"><?= count($oportunidades[$estagio['id']] ?? []) ?></span>
      </div>
      <div class="card-body p-2 crm-col-body" data-estagio-id="<?= (int) $estagio['id'] ?>" data-estagio-tipo="<?= e($estagio['tipo']) ?>">
        <?php foreach ($oportunidades[$estagio['id']] ?? [] as $op): ?>
        <div class="card mb-2 border shadow-sm crm-card" draggable="true" data-id="<?= (int) $op['id'] ?>"
             data-op="<?= e(json_encode([
                'id'                       => (int) $op['id'],
                'titulo'                   => $op['titulo'],
                'cliente_nome'             => $op['cliente_nome'],
                'estagio_id'               => (int) $op['estagio_id'],
                'valor_estimado'           => $op['valor_estimado'],
                'probabilidade'            => (int) $op['probabilidade'],
                'data_fechamento_prevista' => $op['data_fechamento_prevista'],
                'descricao'                => $op['descricao'],
                'motivo_perda'             => $op['motivo_perda'],
             ], JSON_UNESCAPED_UNICODE)) ?>">
          <div class="card-body p-2">
            <div class="fw-semibold small"><?= e($op['titulo']) ?></div>
            <div class="text-muted" style="font-size:.75rem"><?= e($op['cliente_nome']) ?></div>
            <?php if ($op['valor_estimado']): ?>
            <div class="text-success fw-bold small mt-1"><?= money($op['valor_estimado']) ?></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-1">
              <div class="progress flex-grow-1 me-2" style="height:4px">
                <div class="progress-bar" style="width:<?= $op['probabilidade'] ?>%"></div>
              </div>
              <small class="text-muted"><?= $op['probabilidade'] ?>%</small>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- ── MODAL Nova/Editar Oportunidade ─────────────────────────── -->
<div class="modal fade" id="modalOportunidade" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formOportunidade" action="<?= url('/crm/oportunidades') ?>">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="tituloModalOportunidade">Nova oportunidade</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <div class="mb-3" id="wrapClienteBusca">
          <label class="form-label fw-semibold">Cliente *</label>
          <input type="hidden" name="cliente_id" id="opClienteId">
          <div id="opClienteVazio">
            <input type="search" class="form-control" id="opBuscaCliente" placeholder="Buscar cliente pelo nome, telefone...">
            <div id="opResultadosCliente" class="list-group mt-1" style="max-height:160px;overflow-y:auto"></div>
          </div>
          <div id="opClienteSelecionado" class="d-none d-flex align-items-center justify-content-between border rounded p-2">
            <span id="opClienteSelNome" class="fw-semibold small"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="opBtnLimparCliente" title="Trocar cliente"><i class="bi bi-x"></i></button>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Título *</label>
          <input type="text" name="titulo" id="opTitulo" class="form-control" maxlength="150" required placeholder="Ex.: Troca de tela — iPhone 12">
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Valor estimado</label>
            <input type="text" name="valor_estimado" id="opValor" class="form-control" placeholder="0,00">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Probabilidade (%)</label>
            <input type="number" name="probabilidade" id="opProbabilidade" class="form-control" min="0" max="100" value="50">
          </div>
        </div>

        <div class="row g-2 mb-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Estágio</label>
            <select name="estagio_id" id="opEstagio" class="form-select">
              <?php foreach ($estagios as $e): ?>
              <option value="<?= (int) $e['id'] ?>" data-tipo="<?= e($e['tipo']) ?>"><?= e($e['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Previsão de fechamento</label>
            <input type="date" name="data_fechamento_prevista" id="opDataPrevista" class="form-control">
          </div>
        </div>

        <div class="mb-3" id="opWrapMotivo" style="display:none">
          <label class="form-label fw-semibold">Motivo da perda</label>
          <input type="text" name="motivo_perda" id="opMotivoPerda" class="form-control" maxlength="255" placeholder="Ex.: Cliente achou caro">
        </div>

        <div class="mb-1">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="descricao" id="opDescricao" class="form-control" rows="2"></textarea>
        </div>

      </div>
      <div class="modal-footer justify-content-between">
        <button type="button" class="btn btn-outline-danger d-none" id="opBtnExcluir"><i class="bi bi-trash me-1"></i>Excluir</button>
        <div class="ms-auto">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Salvar</button>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
(function () {
  var API_CLI  = '<?= url('/api/clientes') ?>';
  var URL_BASE = '<?= url('/crm/oportunidades') ?>';
  var CSRF     = '<?= csrf_token() ?>';

  var form        = document.getElementById('formOportunidade');
  var clienteId    = document.getElementById('opClienteId');
  var buscaCliente = document.getElementById('opBuscaCliente');
  var resultados   = document.getElementById('opResultadosCliente');
  var clienteVazio  = document.getElementById('opClienteVazio');
  var clienteSel    = document.getElementById('opClienteSelecionado');
  var clienteSelNome= document.getElementById('opClienteSelNome');
  var btnExcluir    = document.getElementById('opBtnExcluir');
  var btnLimparCli  = document.getElementById('opBtnLimparCliente');
  var wrapMotivo    = document.getElementById('opWrapMotivo');
  var estagioSelect = document.getElementById('opEstagio');
  var timerCli = null;

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

  function selecionarCliente(c) {
    clienteId.value = c.id;
    clienteSelNome.textContent = c.nome + (c.telefone ? ' — ' + c.telefone : '');
    clienteVazio.classList.add('d-none');
    clienteSel.classList.remove('d-none');
    buscaCliente.value = ''; resultados.innerHTML = '';
  }
  document.getElementById('opBtnLimparCliente').addEventListener('click', function () {
    clienteId.value = '';
    clienteSel.classList.add('d-none');
    clienteVazio.classList.remove('d-none');
  });
  buscaCliente.addEventListener('input', function () {
    clearTimeout(timerCli);
    var q = buscaCliente.value.trim();
    if (q.length < 2) { resultados.innerHTML = ''; return; }
    timerCli = setTimeout(function () {
      fetch(API_CLI + '?q=' + encodeURIComponent(q)).then(function (r) { return r.json(); }).then(function (lista) {
        if (!lista.length) { resultados.innerHTML = '<div class="list-group-item text-muted small">Nenhum cliente encontrado.</div>'; return; }
        resultados.innerHTML = lista.map(function (c, i) {
          return '<button type="button" class="list-group-item list-group-item-action" data-i="' + i + '"><strong>' + esc(c.nome) + '</strong>' +
            (c.telefone ? ' <span class="text-muted small">' + esc(c.telefone) + '</span>' : '') + '</button>';
        }).join('');
        resultados.querySelectorAll('[data-i]').forEach(function (b) {
          b.addEventListener('click', function () { selecionarCliente(lista[+b.dataset.i]); });
        });
      });
    }, 250);
  });

  function toggleMotivo() {
    var opt = estagioSelect.options[estagioSelect.selectedIndex];
    wrapMotivo.style.display = (opt && opt.dataset.tipo === 'perdido') ? '' : 'none';
  }
  estagioSelect.addEventListener('change', toggleMotivo);

  window.abrirModalOportunidade = function (op) {
    form.reset();
    document.getElementById('opMotivoPerda').value = '';
    if (op) {
      document.getElementById('tituloModalOportunidade').textContent = 'Editar oportunidade';
      form.action = URL_BASE + '/' + op.id + '/editar';
      clienteId.value = ''; // não é reenviado na edição — não editamos o cliente
      clienteVazio.classList.add('d-none');
      clienteSel.classList.remove('d-none');
      clienteSelNome.textContent = op.cliente_nome || '';
      btnLimparCli.classList.add('d-none');
      document.getElementById('opTitulo').value = op.titulo || '';
      document.getElementById('opValor').value = op.valor_estimado || '';
      document.getElementById('opProbabilidade').value = op.probabilidade;
      document.getElementById('opDataPrevista').value = op.data_fechamento_prevista || '';
      document.getElementById('opDescricao').value = op.descricao || '';
      document.getElementById('opMotivoPerda').value = op.motivo_perda || '';
      estagioSelect.value = op.estagio_id;
      btnExcluir.classList.remove('d-none');
      btnExcluir.onclick = function () {
        if (!confirm('Excluir esta oportunidade? Não dá pra desfazer.')) return;
        var f = document.createElement('form');
        f.method = 'POST'; f.action = URL_BASE + '/' + op.id + '/excluir';
        f.innerHTML = '<?= csrf_field() ?>';
        document.body.appendChild(f); f.submit();
      };
    } else {
      document.getElementById('tituloModalOportunidade').textContent = 'Nova oportunidade';
      form.action = URL_BASE;
      clienteId.value = '';
      clienteVazio.classList.remove('d-none');
      clienteSel.classList.add('d-none');
      btnLimparCli.classList.remove('d-none');
      btnExcluir.classList.add('d-none');
    }
    toggleMotivo();
  };

  form.addEventListener('submit', function (e) {
    if (form.action === URL_BASE && !clienteId.value) {
      e.preventDefault();
      alert('Selecione um cliente.');
    }
  });

  // ── Drag-and-drop entre colunas ──
  document.querySelectorAll('.crm-card').forEach(function (card) {
    card.addEventListener('dragstart', function (e) {
      e.dataTransfer.setData('text/plain', card.dataset.id);
      card.classList.add('dragging');
    });
    card.addEventListener('dragend', function () { card.classList.remove('dragging'); });
    card.addEventListener('click', function () {
      abrirModalOportunidade(JSON.parse(card.dataset.op));
      var modal = new bootstrap.Modal(document.getElementById('modalOportunidade'));
      modal.show();
    });
  });

  document.querySelectorAll('.crm-col-body').forEach(function (col) {
    col.addEventListener('dragover', function (e) { e.preventDefault(); col.classList.add('drag-over'); });
    col.addEventListener('dragleave', function () { col.classList.remove('drag-over'); });
    col.addEventListener('drop', function (e) {
      e.preventDefault();
      col.classList.remove('drag-over');
      var id = e.dataTransfer.getData('text/plain');
      if (!id) return;
      var estagioId = col.dataset.estagioId;
      var motivo = '';
      if (col.dataset.estagioTipo === 'perdido') {
        motivo = prompt('Motivo da perda (opcional):') || '';
      }
      fetch(URL_BASE + '/' + id + '/mover', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': CSRF },
        body: 'estagio_id=' + encodeURIComponent(estagioId) + '&motivo_perda=' + encodeURIComponent(motivo)
      }).then(function (r) { return r.json(); }).then(function (j) {
        if (j.ok) { location.reload(); } else { alert(j.erro || 'Não foi possível mover.'); }
      }).catch(function () { alert('Falha de conexão.'); });
    });
  });
})();
</script>
