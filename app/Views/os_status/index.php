<div class="row g-4">

  <!-- Lista de status -->
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold">Status cadastrados</span>
        <span class="text-muted small">Arraste para reordenar</span>
      </div>

      <ul class="list-group list-group-flush" id="listaStatus">
        <?php foreach ($lista as $s): ?>
        <li class="list-group-item d-flex align-items-center gap-3 py-3" data-id="<?= $s['id'] ?>">
          <!-- Handle drag -->
          <i class="bi bi-grip-vertical text-muted fs-5" style="cursor:grab"></i>

          <!-- Cor -->
          <div class="rounded-circle flex-shrink-0" style="width:14px;height:14px;background:<?= e($s['cor']) ?>"></div>

          <!-- Info -->
          <div class="flex-grow-1">
            <div class="fw-semibold"><?= e($s['nome']) ?></div>
            <div class="small text-muted">
              <?php $tipos = [
                'aberta'=>'Aberta','em_andamento'=>'Em andamento','aguardando'=>'Aguardando',
                'concluida'=>'Concluída','entregue'=>'Entregue','cancelada'=>'Cancelada'
              ]; ?>
              Tipo: <?= $tipos[$s['tipo']] ?? $s['tipo'] ?> &nbsp;•&nbsp; Ordem: <?= $s['ordem'] ?>
            </div>
          </div>

          <!-- Badge OS vinculadas -->
          <span class="badge bg-light text-dark border" title="OS vinculadas">
            <?= $s['total_os'] ?> OS
          </span>

          <!-- Preview badge -->
          <span class="badge" style="background:<?= e($s['cor']) ?>"><?= e($s['nome']) ?></span>

          <!-- Ações -->
          <div class="d-flex gap-1">
            <button class="btn btn-sm btn-outline-secondary"
              onclick="abrirEdicao(<?= $s['id'] ?>, '<?= e(addslashes($s['nome'])) ?>', '<?= e($s['cor']) ?>', '<?= e($s['tipo']) ?>')"
              title="Editar">
              <i class="bi bi-pencil"></i>
            </button>
            <?php if ($s['total_os'] == 0): ?>
            <a href="#" class="btn btn-sm btn-outline-danger"
              data-method="DELETE"
              data-href="<?= url('/os/status/' . $s['id']) ?>"
              data-confirm="Excluir o status «<?= e($s['nome']) ?>»?"
              title="Excluir">
              <i class="bi bi-trash"></i>
            </a>
            <?php else: ?>
            <button class="btn btn-sm btn-outline-danger" disabled title="Possui OS vinculadas">
              <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
          </div>
        </li>
        <?php endforeach; ?>
        <?php if (!$lista): ?>
        <li class="list-group-item text-center text-muted py-4">Nenhum status cadastrado.</li>
        <?php endif; ?>
      </ul>
    </div>

    <!-- Dica tipos -->
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold small">O que cada tipo significa?</div>
      <div class="card-body p-0">
        <table class="table table-sm mb-0 small">
          <thead class="table-light"><tr><th>Tipo</th><th>Comportamento no sistema</th></tr></thead>
          <tbody>
            <tr><td><span class="badge bg-secondary">Aberta</span></td><td>OS recém-criada, aguardando triagem</td></tr>
            <tr><td><span class="badge bg-info">Em andamento</span></td><td>Técnico trabalhando — aparece nas OS ativas</td></tr>
            <tr><td><span class="badge bg-warning text-dark">Aguardando</span></td><td>Parada (aprovação, peça, cliente) — não conta como atrasada</td></tr>
            <tr><td><span class="badge bg-success">Concluída</span></td><td>Serviço pronto — preenche data_conclusao automaticamente</td></tr>
            <tr><td><span class="badge bg-success">Entregue</span></td><td>Cliente retirou — preenche data_entrega automaticamente</td></tr>
            <tr><td><span class="badge bg-danger">Cancelada</span></td><td>OS encerrada sem conserto — sai dos relatórios de faturamento</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Formulário -->
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm sticky-top" style="top:80px">
      <div class="card-header bg-white fw-semibold" id="formTitulo">
        <i class="bi bi-plus-circle me-1 text-primary"></i> Novo Status
      </div>
      <div class="card-body">
        <form method="POST" action="<?= url('/os/status') ?>" id="formStatus">
          <?= csrf_field() ?>
          <input type="hidden" name="id" id="statusId" value="">

          <div class="mb-3">
            <label class="form-label fw-semibold">Nome do status *</label>
            <input type="text" name="nome" id="statusNome" class="form-control"
              placeholder="Ex: Em diagnóstico, Aguardando peça..." required maxlength="60">
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Tipo *</label>
            <select name="tipo" id="statusTipo" class="form-select">
              <option value="aberta">Aberta</option>
              <option value="em_andamento">Em andamento</option>
              <option value="aguardando">Aguardando</option>
              <option value="concluida">Concluída</option>
              <option value="entregue">Entregue</option>
              <option value="cancelada">Cancelada</option>
            </select>
            <div class="form-text">Define o comportamento automático no sistema.</div>
          </div>

          <div class="mb-4">
            <label class="form-label fw-semibold">Cor do badge</label>
            <div class="d-flex gap-2 align-items-center flex-wrap mb-2" id="paletaCores">
              <?php foreach ([
                '#6c757d','#0d6efd','#0dcaf0','#198754','#20c997',
                '#ffc107','#fd7e14','#dc3545','#6f42c1','#d63384','#212529'
              ] as $cor): ?>
              <div class="cor-btn rounded-circle border"
                style="width:28px;height:28px;background:<?= $cor ?>;cursor:pointer"
                data-cor="<?= $cor ?>"
                onclick="selecionarCor('<?= $cor ?>')"
                title="<?= $cor ?>"></div>
              <?php endforeach; ?>
            </div>
            <div class="input-group">
              <input type="color" name="cor" id="statusCor" class="form-control form-control-color" value="#6c757d">
              <input type="text" id="corTexto" class="form-control" value="#6c757d" maxlength="7"
                oninput="document.getElementById('statusCor').value=this.value">
              <span class="input-group-text">
                <span id="previewBadge" class="badge" style="background:#6c757d">Preview</span>
              </span>
            </div>
          </div>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary flex-fill" id="btnSalvar">
              <i class="bi bi-check-lg"></i> Salvar
            </button>
            <button type="button" class="btn btn-outline-secondary" id="btnCancelar" onclick="limparForm()" style="display:none">
              Cancelar
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
// Paleta de cores
function selecionarCor(cor) {
  document.getElementById('statusCor').value = cor;
  document.getElementById('corTexto').value  = cor;
  atualizarPreviewBadge(cor);
  document.querySelectorAll('.cor-btn').forEach(b => b.style.outline = 'none');
  document.querySelector(`.cor-btn[data-cor="${cor}"]`)?.style.setProperty('outline','3px solid #000');
}

function atualizarPreviewBadge(cor) {
  const nome = document.getElementById('statusNome').value || 'Preview';
  document.getElementById('previewBadge').style.background = cor;
  document.getElementById('previewBadge').textContent = nome;
}

document.getElementById('statusNome').addEventListener('input', function() {
  atualizarPreviewBadge(document.getElementById('statusCor').value);
});
document.getElementById('statusCor').addEventListener('input', function() {
  document.getElementById('corTexto').value = this.value;
  atualizarPreviewBadge(this.value);
});

// Abrir edição
function abrirEdicao(id, nome, cor, tipo) {
  document.getElementById('statusId').value    = id;
  document.getElementById('statusNome').value  = nome;
  document.getElementById('statusCor').value   = cor;
  document.getElementById('corTexto').value    = cor;
  document.getElementById('statusTipo').value  = tipo;
  document.getElementById('formTitulo').innerHTML = '<i class="bi bi-pencil me-1 text-warning"></i> Editando: ' + nome;
  document.getElementById('btnSalvar').innerHTML  = '<i class="bi bi-check-lg"></i> Atualizar';
  document.getElementById('btnCancelar').style.display = '';
  atualizarPreviewBadge(cor);
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function limparForm() {
  document.getElementById('statusId').value   = '';
  document.getElementById('statusNome').value = '';
  document.getElementById('statusCor').value  = '#6c757d';
  document.getElementById('corTexto').value   = '#6c757d';
  document.getElementById('statusTipo').value = 'aberta';
  document.getElementById('formTitulo').innerHTML = '<i class="bi bi-plus-circle me-1 text-primary"></i> Novo Status';
  document.getElementById('btnSalvar').innerHTML  = '<i class="bi bi-check-lg"></i> Salvar';
  document.getElementById('btnCancelar').style.display = 'none';
  atualizarPreviewBadge('#6c757d');
}

// Drag and drop para reordenar
const lista = document.getElementById('listaStatus');
if (lista && typeof Sortable !== 'undefined') {
  Sortable.create(lista, {
    handle: '.bi-grip-vertical',
    animation: 150,
    onEnd: async function() {
      const ids = [...lista.querySelectorAll('[data-id]')].map(el => el.dataset.id);
      await fetch('<?= url('/os/status/reordenar') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
        body: JSON.stringify({ ids })
      });
      // Atualiza números de ordem visualmente
      lista.querySelectorAll('[data-id]').forEach((el, i) => {
        const info = el.querySelector('.small.text-muted');
        if (info) info.textContent = info.textContent.replace(/Ordem: \d+/, 'Ordem: ' + (i + 1));
      });
    }
  });
}
</script>
