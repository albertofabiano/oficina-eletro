<?php $editando = !empty($produto['id']); ?>

<style>
.crud-tag { display:inline-flex;align-items:center;gap:4px;background:#f8f9fa;border:1px solid #dee2e6;
            border-radius:20px;padding:2px 10px;font-size:.78rem;cursor:pointer;transition:.15s; }
.crud-tag:hover { background:#e9ecef; }
.crud-tag .rm { color:#dc3545;font-weight:bold;margin-left:2px;line-height:1; }
.tags-wrap { display:flex;flex-wrap:wrap;gap:6px;min-height:34px;align-items:center; }
</style>

<div class="row justify-content-center">
<div class="col-lg-9">
<form method="POST" action="<?= url($editando ? '/produtos/' . $produto['id'] : '/produtos') ?>">
  <?= csrf_field() ?>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Identificação</div>
    <div class="card-body">
      <div class="row g-3">

        <!-- Código de barras -->
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Código de barras</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
            <input type="text" name="codigo_barras" class="form-control"
              placeholder="EAN-13, QR..." value="<?= e($produto['codigo_barras'] ?? '') ?>">
          </div>
        </div>

        <!-- Estado -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold d-flex justify-content-between">
            Estado do produto
            <button type="button" class="btn btn-link btn-sm p-0 text-muted small"
              onclick="abrirCrud('estados','Estado')">
              <i class="bi bi-gear"></i> Gerenciar
            </button>
          </label>
          <select name="estado_id" id="selEstado" class="form-select">
            <option value="">— Selecione —</option>
            <?php foreach ($estados as $e): ?>
            <option value="<?= $e['id'] ?>" <?= ($produto['estado_id']??'')==$e['id']?'selected':'' ?>>
              <?= e($e['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Tipo -->
        <div class="col-md-3">
          <label class="form-label small fw-semibold d-flex justify-content-between">
            Tipo de produto
            <button type="button" class="btn btn-link btn-sm p-0 text-muted small"
              onclick="abrirCrud('tipos','Tipo')">
              <i class="bi bi-gear"></i> Gerenciar
            </button>
          </label>
          <select name="tipo_id" id="selTipo" class="form-select">
            <option value="">— Selecione —</option>
            <?php foreach ($tipos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($produto['tipo_id']??'')==$t['id']?'selected':'' ?>>
              <?= e($t['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Marca -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold d-flex justify-content-between">
            Marca
            <button type="button" class="btn btn-link btn-sm p-0 text-muted small"
              onclick="abrirCrud('marcas','Marca')">
              <i class="bi bi-gear"></i> Gerenciar
            </button>
          </label>
          <select name="marca_id" id="selMarca" class="form-select">
            <option value="">— Selecione —</option>
            <?php foreach ($marcas as $m): ?>
            <option value="<?= $m['id'] ?>" <?= ($produto['marca_id']??'')==$m['id']?'selected':'' ?>>
              <?= e($m['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Modelo (nome) -->
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Modelo / Nome *</label>
          <input type="text" name="nome" class="form-control"
            value="<?= e($produto['nome'] ?? '') ?>" required
            placeholder="Ex: Tela LCD 6.5, Bateria 3000mAh...">
        </div>

        <!-- Código interno -->
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Código interno</label>
          <input type="text" name="codigo" class="form-control"
            value="<?= e($produto['codigo'] ?? '') ?>" placeholder="SKU, REF...">
        </div>

        <!-- Observação -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Observação</label>
          <textarea name="descricao" class="form-control" rows="2"
            placeholder="Detalhes técnicos, compatibilidade..."><?= e($produto['descricao'] ?? '') ?></textarea>
        </div>

      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Estoque e Preços</div>
    <div class="card-body">
      <div class="row g-3">
        <?php if (!$editando): ?>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Estoque inicial</label>
          <input type="number" name="estoque_atual" class="form-control"
            value="<?= e($produto['estoque_atual'] ?? 0) ?>" step="0.001" min="0">
        </div>
        <?php endif; ?>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Estoque mínimo</label>
          <input type="number" name="estoque_minimo" class="form-control"
            value="<?= e($produto['estoque_minimo'] ?? 0) ?>" step="0.001" min="0">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Unidade</label>
          <select name="unidade" class="form-select">
            <?php foreach (['un','kg','g','lt','ml','m','cm','par','cx','pct'] as $u): ?>
            <option value="<?= $u ?>" <?= ($produto['unidade']??'un')===$u?'selected':'' ?>><?= $u ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Localização</label>
          <input type="text" name="localizacao" class="form-control"
            placeholder="Prateleira A3..." value="<?= e($produto['localizacao'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Custo (R$)</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor_custo" class="form-control" placeholder="0,00"
              value="<?= e(number_format($produto['valor_custo'] ?? 0, 2, ',', '.')) ?>">
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Venda (R$)</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor_venda" class="form-control" placeholder="0,00"
              value="<?= e(number_format($produto['valor_venda'] ?? 0, 2, ',', '.')) ?>">
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Fornecedor</label>
          <select name="fornecedor_id" class="form-select">
            <option value="">—</option>
            <?php foreach ($fornecedores as $f): ?>
            <option value="<?= $f['id'] ?>" <?= ($produto['fornecedor_id']??'')==$f['id']?'selected':'' ?>>
              <?= e($f['razao_social']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end">
    <a href="<?= url('/produtos') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary px-4"><?= $editando ? 'Salvar' : 'Cadastrar' ?></button>
  </div>
</form>
</div>
</div>

<!-- ── OFFCANVAS CRUD ─────────────────────────────────────── -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCrud" style="width:360px">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold" id="offcanvasCrudTitulo">Gerenciar</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <!-- Formulário add/edit -->
    <div class="p-3 border-bottom bg-light">
      <div class="input-group">
        <input type="text" id="crudNome" class="form-control" placeholder="Nome..."
          onkeydown="if(event.key==='Enter'){event.preventDefault();salvarCrud()}">
        <button class="btn btn-primary" onclick="salvarCrud()" id="btnCrudSalvar">
          <i class="bi bi-check-lg"></i>
        </button>
      </div>
      <input type="hidden" id="crudId">
      <button class="btn btn-link btn-sm text-muted p-0 mt-1 d-none" id="btnCrudCancelar"
        onclick="cancelarCrud()">
        <i class="bi bi-x me-1"></i>Cancelar edição
      </button>
    </div>
    <!-- Lista -->
    <div id="crudLista" style="overflow-y:auto;max-height:calc(100vh-180px)">
      <div class="text-center text-muted py-4 small">Carregando...</div>
    </div>
  </div>
</div>

<script>
const CSRF = '<?= csrf_token() ?>';
let crudTipo = '';
let offcanvas;

window.addEventListener('load', function() {
  offcanvas = new bootstrap.Offcanvas(document.getElementById('offcanvasCrud'));
});

function abrirCrud(tipo, titulo) {
  crudTipo = tipo;
  document.getElementById('offcanvasCrudTitulo').textContent = 'Gerenciar ' + titulo + 's';
  cancelarCrud();
  carregarCrud();
  offcanvas.show();
}

async function carregarCrud() {
  const r    = await fetch(`<?= url('/api/produto/') ?>${crudTipo}`);
  const list = await r.json();
  const box  = document.getElementById('crudLista');

  if (!list.length) {
    box.innerHTML = '<div class="text-center text-muted py-4 small">Nenhum item ainda.</div>';
    return;
  }

  box.innerHTML = list.map(item => `
    <div class="d-flex align-items-center px-3 py-2 border-bottom gap-2"
         onmouseenter="this.style.background='#f8f9fa'" onmouseleave="this.style.background=''">
      <span class="flex-grow-1 small">${esc(item.nome)}</span>
      <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2"
        onclick="editarCrud(${item.id},'${escJs(item.nome)}')">
        <i class="bi bi-pencil"></i>
      </button>
      <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2"
        onclick="excluirCrud(${item.id},'${escJs(item.nome)}')">
        <i class="bi bi-trash3"></i>
      </button>
    </div>`).join('');
}

async function salvarCrud() {
  const nome = document.getElementById('crudNome').value.trim();
  const id   = document.getElementById('crudId').value;
  if (!nome) { document.getElementById('crudNome').classList.add('is-invalid'); return; }
  document.getElementById('crudNome').classList.remove('is-invalid');

  const r = await fetch(`<?= url('/api/produto/') ?>${crudTipo}`, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},
    body: JSON.stringify({id, nome})
  });
  const d = await r.json();
  if (d.success) {
    // Atualizar o select correspondente
    atualizarSelect(d.id, d.nome, id ? false : true);
    cancelarCrud();
    carregarCrud();
  }
}

function atualizarSelect(id, nome, isNew) {
  const selMap = {estados:'selEstado', tipos:'selTipo', marcas:'selMarca'};
  const sel    = document.getElementById(selMap[crudTipo]);
  if (!sel) return;

  if (isNew) {
    const opt = document.createElement('option');
    opt.value = id; opt.textContent = nome; opt.selected = true;
    sel.appendChild(opt);
  } else {
    const opt = sel.querySelector(`option[value="${id}"]`);
    if (opt) opt.textContent = nome;
  }
}

function editarCrud(id, nome) {
  document.getElementById('crudId').value   = id;
  document.getElementById('crudNome').value = nome;
  document.getElementById('crudNome').focus();
  document.getElementById('btnCrudCancelar').classList.remove('d-none');
  document.getElementById('btnCrudSalvar').innerHTML = '<i class="bi bi-check-lg"></i>';
}

function cancelarCrud() {
  document.getElementById('crudId').value   = '';
  document.getElementById('crudNome').value = '';
  document.getElementById('btnCrudCancelar').classList.add('d-none');
  document.getElementById('btnCrudSalvar').innerHTML = '<i class="bi bi-check-lg"></i>';
}

async function excluirCrud(id, nome) {
  if (!confirm(`Excluir "${nome}"?`)) return;
  await fetch(`<?= url('/api/produto/') ?>${crudTipo}/${id}`, {
    method:'DELETE', headers:{'X-CSRF-Token':CSRF,'Content-Type':'application/json'},
    body: JSON.stringify({_method:'DELETE'})
  });
  // Remover do select
  const selMap = {estados:'selEstado', tipos:'selTipo', marcas:'selMarca'};
  const sel = document.getElementById(selMap[crudTipo]);
  if (sel) { const opt = sel.querySelector(`option[value="${id}"]`); if (opt) opt.remove(); }
  carregarCrud();
}

function esc(s)   { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escJs(s) { return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
</script>
