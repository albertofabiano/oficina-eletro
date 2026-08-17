<?php $podeEditar = \App\Core\Auth::can('estoque', 'editar'); ?>

<div class="row g-3">
  <!-- Novo serviço -->
  <?php if ($podeEditar): ?>
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold"><i class="bi bi-plus-circle me-1"></i>Novo serviço</div>
      <div class="card-body">
        <form method="POST" action="<?= url('/servicos') ?>">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Descrição *</label>
            <input type="text" name="descricao" class="form-control" required maxlength="150" placeholder="Ex: Troca de tela, Diagnóstico...">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Valor padrão</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_padrao" class="form-control" placeholder="0,00">
            </div>
            <div class="form-text">Sugerido ao escolher este serviço na OS — pode ser ajustado na hora.</div>
          </div>
          <button class="btn btn-primary w-100"><i class="bi bi-check-lg me-1"></i>Cadastrar serviço</button>
        </form>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- Lista -->
  <div class="col-lg-<?= $podeEditar ? '8' : '12' ?>">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex align-items-center gap-2 flex-wrap">
        <div class="position-relative flex-grow-1" style="min-width:200px">
          <input type="text" id="buscaServico" class="form-control form-control-sm" autocomplete="off"
                 placeholder="Buscar serviço pela descrição...">
        </div>
        <?php if ($podeEditar): ?>
        <button type="button" id="btnExcluirLote" class="btn btn-sm btn-outline-danger" style="display:none">
          <i class="bi bi-trash me-1"></i>Excluir selecionados (<span id="qtdSelecionados">0</span>)
        </button>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
          <thead class="table-light">
            <tr>
              <th>Descrição</th>
              <th class="text-end">Valor padrão</th>
              <?php if ($podeEditar): ?>
              <th class="text-end">
                <input type="checkbox" class="form-check-input align-middle me-1" id="chkTodosServicos" title="Selecionar todos">
                Ações
              </th>
              <?php endif; ?>
            </tr>
          </thead>
          <tbody id="corpoServicos">
            <?php foreach ($servicos as $s): ?>
            <tr>
              <td class="fw-semibold"><?= e($s['descricao']) ?></td>
              <td class="text-end"><?= money($s['valor_padrao']) ?></td>
              <?php if ($podeEditar): ?>
              <td class="text-end text-nowrap">
                <input type="checkbox" class="form-check-input align-middle me-2 chk-servico" value="<?= $s['id'] ?>">
                <button type="button" class="btn btn-sm btn-outline-secondary btn-edit"
                        data-id="<?= $s['id'] ?>" data-descricao="<?= e($s['descricao']) ?>" data-valor="<?= e($s['valor_padrao']) ?>"><i class="bi bi-pencil"></i></button>
                <form method="POST" action="<?= url('/servicos/' . $s['id'] . '/excluir') ?>" class="d-inline"
                      onsubmit="return confirm('Remover o serviço <?= e(addslashes($s['descricao'])) ?> do catálogo?');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                </form>
              </td>
              <?php endif; ?>
            </tr>
            <?php endforeach; ?>
            <?php if (!$servicos): ?>
            <tr><td colspan="<?= $podeEditar ? '3' : '2' ?>" class="text-center text-muted py-5">
              <i class="bi bi-tools fs-3 d-block mb-2"></i>Nenhum serviço cadastrado ainda.<?php if ($podeEditar): ?> Cadastre o primeiro ao lado.<?php endif; ?>
            </td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php if ($podeEditar): ?>
<!-- Modal editar -->
<div class="modal fade" id="modalEditServico" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formEditServico">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil me-2"></i>Editar serviço</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Descrição *</label>
          <input type="text" name="descricao" id="editDescricao" class="form-control" required maxlength="150">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Valor padrão</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor_padrao" id="editValor" class="form-control" placeholder="0,00">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>
<script>
(function () {
  var token   = '<?= csrf_token() ?>';
  var corpo   = document.getElementById('corpoServicos');
  var htmlOriginal = corpo.innerHTML;

  function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
  function brl(v) {
    v = Number(v) || 0;
    return 'R$ ' + v.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }

  // ── Editar (delegado — funciona tanto nas linhas do PHP quanto nas geradas pela busca) ──
  corpo.addEventListener('click', function (e) {
    var b = e.target.closest('.btn-edit');
    if (!b) return;
    var f = document.getElementById('formEditServico');
    f.action = '<?= url('/servicos') ?>/' + b.dataset.id;
    document.getElementById('editDescricao').value = b.dataset.descricao;
    document.getElementById('editValor').value = b.dataset.valor;
    new bootstrap.Modal(document.getElementById('modalEditServico')).show();
  });

  // ── Busca AJAX ──────────────────────────────────────────────────
  var busca = document.getElementById('buscaServico');
  var timerBusca = null;
  busca.addEventListener('input', function () {
    clearTimeout(timerBusca);
    var q = busca.value.trim();
    if (!q) { corpo.innerHTML = htmlOriginal; atualizarSelecao(); return; }
    timerBusca = setTimeout(function () { buscarServicos(q); }, 250);
  });

  function buscarServicos(q) {
    fetch('<?= url('/api/servicos') ?>?q=' + encodeURIComponent(q))
      .then(function (r) { return r.json(); })
      .then(function (lista) { renderLinhas(lista); });
  }

  function renderLinhas(lista) {
    if (!lista.length) {
      corpo.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">Nenhum serviço encontrado.</td></tr>';
      atualizarSelecao();
      return;
    }
    corpo.innerHTML = lista.map(function (s) {
      return '<tr>' +
        '<td class="fw-semibold">' + esc(s.descricao) + '</td>' +
        '<td class="text-end">' + brl(s.valor_padrao) + '</td>' +
        '<td class="text-end text-nowrap">' +
          '<input type="checkbox" class="form-check-input align-middle me-2 chk-servico" value="' + s.id + '">' +
          '<button type="button" class="btn btn-sm btn-outline-secondary btn-edit" data-id="' + s.id + '" data-descricao="' + esc(s.descricao) + '" data-valor="' + s.valor_padrao + '"><i class="bi bi-pencil"></i></button> ' +
          '<button type="button" class="btn btn-sm btn-outline-danger btn-del-um" data-id="' + s.id + '" data-descricao="' + esc(s.descricao) + '"><i class="bi bi-trash"></i></button>' +
        '</td>' +
      '</tr>';
    }).join('');
    atualizarSelecao();
  }

  // Exclusão individual das linhas geradas pela busca (as do PHP já têm <form> próprio) —
  // reaproveita o endpoint de exclusão em lote com um único id, evita duplicar rota.
  corpo.addEventListener('click', function (e) {
    var b = e.target.closest('.btn-del-um');
    if (!b) return;
    if (!confirm('Remover o serviço ' + b.dataset.descricao + ' do catálogo?')) return;
    excluirLote([Number(b.dataset.id)], function () {
      b.closest('tr').remove();
    });
  });

  // ── Seleção + exclusão em lote ──────────────────────────────────
  var btnLote = document.getElementById('btnExcluirLote');
  var qtdSpan = document.getElementById('qtdSelecionados');
  var chkTodos = document.getElementById('chkTodosServicos');

  function atualizarSelecao() {
    var marcados = corpo.querySelectorAll('.chk-servico:checked').length;
    qtdSpan.textContent = marcados;
    btnLote.style.display = marcados > 0 ? '' : 'none';
    var total = corpo.querySelectorAll('.chk-servico').length;
    chkTodos.checked = total > 0 && marcados === total;
  }
  corpo.addEventListener('change', function (e) {
    if (e.target.classList.contains('chk-servico')) atualizarSelecao();
  });
  chkTodos.addEventListener('change', function () {
    corpo.querySelectorAll('.chk-servico').forEach(function (c) { c.checked = chkTodos.checked; });
    atualizarSelecao();
  });

  function excluirLote(ids, aoTerminar) {
    fetch('<?= url('/servicos/excluir-lote') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': token },
      body: JSON.stringify({ ids: ids })
    })
      .then(function (r) { return r.json(); })
      .then(function (res) {
        if (res.sucesso) { aoTerminar(); }
        else { alert(res.erro || 'Não foi possível excluir.'); }
      })
      .catch(function () { alert('Falha de conexão.'); });
  }

  btnLote.addEventListener('click', function () {
    var ids = Array.prototype.map.call(corpo.querySelectorAll('.chk-servico:checked'), function (c) { return Number(c.value); });
    if (!ids.length) return;
    if (!confirm('Remover ' + ids.length + ' serviço(s) selecionado(s) do catálogo?')) return;
    excluirLote(ids, function () {
      corpo.querySelectorAll('.chk-servico:checked').forEach(function (c) { c.closest('tr').remove(); });
      if (!corpo.querySelector('tr')) {
        corpo.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-5"><i class="bi bi-tools fs-3 d-block mb-2"></i>Nenhum serviço cadastrado ainda.</td></tr>';
      }
      atualizarSelecao();
    });
  });
})();
</script>
<?php endif; ?>
