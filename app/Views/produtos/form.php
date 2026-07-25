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
<form method="POST" action="<?= url($editando ? '/produtos/' . $produto['id'] : '/produtos') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <!-- Foto do produto -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-camera me-1 text-primary"></i>Foto do produto</div>
    <div class="card-body text-center">
      <img id="prevFotoProd"
           src="<?= !empty($produto['imagem']) ? url('/uploads/produtos/' . e($produto['imagem'])) : '' ?>"
           class="rounded border mb-2 <?= empty($produto['imagem']) ? 'd-none' : '' ?>"
           style="max-width:220px;max-height:220px;object-fit:contain;background:#fff">
      <div>
        <label for="inputFotoProd" class="btn btn-outline-primary btn-lg">
          <i class="bi bi-camera me-1"></i> Tirar foto / escolher
        </label>
        <input type="file" name="imagem" id="inputFotoProd" accept="image/*"
               class="d-none" onchange="previewFotoProd(this)">
      </div>
      <div class="form-text"><i class="bi bi-magic me-1"></i>Redimensionada e otimizada automaticamente.</div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Identificação</div>
    <div class="card-body">
      <div class="row g-3">

        <!-- Nome do produto (destaque no topo, facilita a busca) -->
        <div class="col-12">
          <label class="form-label fw-semibold">Nome do produto *</label>
          <input type="text" name="nome" class="form-control form-control-lg"
            value="<?= e($produto['nome'] ?? '') ?>" required
            placeholder="Ex: Tela LCD 6.5, Bateria 3000mAh...">
        </div>

        <!-- Classificação: 4 selects alinhados (col-md-3 cada = 12) -->
        <!-- Estado -->
        <div class="col-md-3">
          <label class="form-label small fw-semibold d-flex justify-content-between">
            Estado
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
            Tipo
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
        <div class="col-md-3">
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

        <!-- Categoria -->
        <div class="col-md-3">
          <label class="form-label small fw-semibold d-flex justify-content-between">
            Categoria
            <button type="button" class="btn btn-link btn-sm p-0 text-muted small"
              onclick="abrirCrud('categorias','Categoria')">
              <i class="bi bi-gear"></i> Gerenciar
            </button>
          </label>
          <select name="categoria_id" id="selCategoria" class="form-select">
            <option value="">— Selecione —</option>
            <?php foreach ($categorias as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= ($produto['categoria_id']??'')==$cat['id']?'selected':'' ?>>
              <?= e($cat['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Códigos: col-md-6 + col-md-3 + col-md-3 = 12 -->
        <!-- Código de barras -->
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Código de barras</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-upc-scan"></i></span>
            <input type="text" name="codigo_barras" class="form-control"
              placeholder="EAN-13, QR..." value="<?= e($produto['codigo_barras'] ?? ($codigoSugerido ?? '')) ?>"
              onkeydown="if(event.key==='Enter'){event.preventDefault()}">
          </div>
        </div>

        <!-- Código interno (auto: 3 letras da empresa + sequencial) -->
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Código interno</label>
          <input type="text" name="codigo" class="form-control"
            value="<?= e($produto['codigo'] ?? ($codigoInternoSugerido ?? '')) ?>" placeholder="SKU, REF...">
        </div>

        <!-- Código da Peça / Placa -->
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Código da Peça / Placa</label>
          <input type="text" name="codigo_peca" class="form-control"
            value="<?= e($produto['codigo_peca'] ?? '') ?>" placeholder="Ex.: EAX64891, 32LB5500...">
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
        <div class="col-md-3">
          <label class="form-label small fw-semibold"><?= $editando ? 'Estoque atual' : 'Estoque inicial' ?></label>
          <input type="number" name="estoque_atual" class="form-control"
            value="<?= e($produto['estoque_atual'] ?? 0) ?>" step="0.001" min="0">
          <?php if ($editando): ?><div class="form-text">Ajuste manual da quantidade em estoque.</div><?php endif; ?>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Estoque mínimo</label>
          <input type="number" name="estoque_minimo" class="form-control"
            value="<?= e($produto['estoque_minimo'] ?? 0) ?>" step="0.001" min="0">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Unidade</label>
          <?php
            $unis = $unidades ?? [];
            if (!$unis) foreach (['un','kg','g','lt','ml','m','cm','par','cx','pct'] as $u) $unis[] = ['nome' => $u];
            $unidAtual = $produto['unidade'] ?? 'un';
          ?>
          <select name="unidade" id="selUnidade" class="form-select">
            <?php foreach ($unis as $u): ?>
            <option value="<?= e($u['nome']) ?>" <?= $unidAtual === $u['nome'] ? 'selected' : '' ?>><?= e($u['nome']) ?></option>
            <?php endforeach; ?>
          </select>
          <a href="#" onclick="abrirCrud('unidades','Unidade');return false;" class="form-text text-decoration-none d-inline-block mt-1"><i class="bi bi-gear"></i> Gerenciar unidades</a>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Localização</label>
          <input type="text" name="localizacao" class="form-control"
            placeholder="Prateleira A3..." value="<?= e($produto['localizacao'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Custo (R$)</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor_custo" class="form-control" placeholder="0,00"
              value="<?= e(number_format($produto['valor_custo'] ?? 0, 2, ',', '.')) ?>">
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Venda (R$)</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor_venda" class="form-control" placeholder="0,00"
              value="<?= e(number_format($produto['valor_venda'] ?? 0, 2, ',', '.')) ?>">
          </div>
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Fornecedor</label>
          <?php
            $fornNome = '';
            foreach ($fornecedores as $f) if (($produto['fornecedor_id'] ?? '') == $f['id']) $fornNome = $f['razao_social'];
          ?>
          <div class="input-group">
            <input type="text" id="fornBusca" class="form-control" list="fornLista" autocomplete="off"
                   placeholder="Buscar ou digitar novo..." value="<?= e($fornNome) ?>" oninput="fornOnInput()">
            <button type="button" class="btn btn-outline-primary" onclick="fornAdicionar()" title="Achar ou adicionar fornecedor"><i class="bi bi-plus-lg"></i></button>
          </div>
          <datalist id="fornLista">
            <?php foreach ($fornecedores as $f): ?><option data-id="<?= $f['id'] ?>" value="<?= e($f['razao_social']) ?>"></option><?php endforeach; ?>
          </datalist>
          <input type="hidden" name="fornecedor_id" id="fornId" value="<?= e($produto['fornecedor_id'] ?? '') ?>">
          <div class="form-text" id="fornMsg"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end align-items-center">
    <?php if ($editando && \App\Core\Auth::isAdmin()): ?>
    <button type="button" class="btn btn-outline-danger me-auto" data-bs-toggle="modal" data-bs-target="#modalExcluirProduto">
      <i class="bi bi-trash me-1"></i>Excluir produto
    </button>
    <?php endif; ?>
    <a href="<?= url('/produtos') ?>" class="btn btn-outline-secondary">Cancelar</a>
    <button class="btn btn-primary px-4"><?= $editando ? 'Salvar' : 'Cadastrar' ?></button>
  </div>
</form>
</div>
</div>

<?php if ($editando && \App\Core\Auth::isAdmin()): ?>
<!-- ── MODAL EXCLUIR PRODUTO (só admin) ──────────────────────────── -->
<div class="modal fade" id="modalExcluirProduto" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" action="<?= url('/produtos/' . $produto['id'] . '/excluir') ?>">
      <?= csrf_field() ?>
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Excluir <?= e($produto['nome']) ?></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-flex gap-2 mb-3">
          <i class="bi bi-trash3 fs-4"></i>
          <div><strong>Esta ação é IRREVERSÍVEL.</strong> O produto e seu histórico de movimentação de estoque serão apagados <strong>permanentemente</strong> e não poderão ser recuperados.</div>
        </div>
        <p class="mb-2 small text-muted"><i class="bi bi-shield-check me-1"></i>A exclusão fica registrada no <strong>Registro de Ações</strong> (quem excluiu e quando).</p>
        <label class="form-label small fw-semibold mb-1"><i class="bi bi-lock-fill me-1"></i>Confirme sua senha de login para excluir</label>
        <input type="password" name="senha" class="form-control" autocomplete="off" placeholder="Sua senha" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-trash me-1"></i>Excluir permanentemente</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

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

// Preview da foto do produto (ao tirar/escolher)
function previewFotoProd(input) {
  const img = document.getElementById('prevFotoProd');
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
  reader.readAsDataURL(input.files[0]);
}

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
  const selMap = {estados:'selEstado', tipos:'selTipo', marcas:'selMarca', categorias:'selCategoria', unidades:'selUnidade'};
  const sel    = document.getElementById(selMap[crudTipo]);
  if (!sel) return;
  const usaNome = crudTipo === 'unidades'; // unidade guarda o NOME como value (não o id)

  if (isNew) {
    const opt = document.createElement('option');
    opt.value = usaNome ? nome : id; opt.textContent = nome; opt.selected = true;
    sel.appendChild(opt);
  } else if (!usaNome) {
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
  // Remover do select (unidades casam por NOME; os demais por id)
  const selMap = {estados:'selEstado', tipos:'selTipo', marcas:'selMarca', categorias:'selCategoria', unidades:'selUnidade'};
  const sel = document.getElementById(selMap[crudTipo]);
  if (sel) { const val = crudTipo === 'unidades' ? nome : id; const opt = sel.querySelector(`option[value="${CSS.escape(String(val))}"]`); if (opt) opt.remove(); }
  carregarCrud();
}

// ── Fornecedor: achar ou adicionar (AJAX) ──────────────────────────────
function fornOnInput() {
  const inp = document.getElementById('fornBusca');
  const opt = document.querySelector('#fornLista option[value="' + CSS.escape(inp.value) + '"]');
  document.getElementById('fornId').value = opt ? (opt.dataset.id || '') : '';
  document.getElementById('fornMsg').textContent = '';
}
async function fornAdicionar() {
  const inp = document.getElementById('fornBusca');
  const nome = inp.value.trim();
  const msg  = document.getElementById('fornMsg');
  if (!nome) { inp.focus(); return; }
  // já existe no datalist? só seleciona
  const jaTem = document.querySelector('#fornLista option[value="' + CSS.escape(nome) + '"]');
  if (jaTem) { document.getElementById('fornId').value = jaTem.dataset.id || ''; msg.textContent = 'Fornecedor selecionado.'; return; }
  try {
    const r = await fetch('<?= url('/api/fornecedores') ?>', {
      method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':CSRF},
      body: 'nome=' + encodeURIComponent(nome)
    });
    const d = await r.json();
    if (d.id) {
      const o = document.createElement('option'); o.value = d.razao_social; o.dataset.id = d.id;
      document.getElementById('fornLista').appendChild(o);
      document.getElementById('fornId').value = d.id;
      msg.innerHTML = '<span class="text-success">' + (d.novo ? 'Novo fornecedor adicionado' : 'Fornecedor selecionado') + '.</span>';
    } else { msg.innerHTML = '<span class="text-danger">' + (d.error || 'Não foi possível adicionar.') + '</span>'; }
  } catch (e) { msg.innerHTML = '<span class="text-danger">Erro ao adicionar fornecedor.</span>'; }
}

function esc(s)   { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escJs(s) { return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
</script>
