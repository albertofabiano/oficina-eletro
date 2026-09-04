<?php
$editando = !empty($produto['id']);
$galeriaProd = !empty($produto['imagens_galeria']) ? (json_decode($produto['imagens_galeria'], true) ?: []) : [];
$vagasGaleriaProd = 3 - count($galeriaProd);
?>

<style>
.crud-tag { display:inline-flex;align-items:center;gap:4px;background:#f8f9fa;border:1px solid #dee2e6;
            border-radius:20px;padding:2px 10px;font-size:.78rem;cursor:pointer;transition:.15s; }
.crud-tag:hover { background:#e9ecef; }
.crud-tag .rm { color:#dc3545;font-weight:bold;margin-left:2px;line-height:1; }
.tags-wrap { display:flex;flex-wrap:wrap;gap:6px;min-height:34px;align-items:center; }
.thumb-prod-edit { width:100px;height:100px;object-fit:cover;border-radius:10px;border:2px solid #dee2e6; }
</style>

<div class="row justify-content-center">
<div class="col-lg-9">
<form method="POST" id="formProduto" action="<?= url($editando ? '/produtos/' . $produto['id'] : '/produtos') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

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
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Garantia (dias) <span class="text-danger">*</span></label>
          <input type="number" name="garantia_dias" class="form-control" required min="0" step="1"
            placeholder="90" value="<?= e($produto['garantia_dias'] ?? 90) ?>">
          <div class="form-text">0 = sem garantia. Some automaticamente à descrição do item quando vendido no PDV.</div>
        </div>
        <div class="col-md-3">
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

  <!-- Foto do produto -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-camera me-1 text-primary"></i>Foto do produto</div>
    <div class="card-body">

      <?php if (!empty($produto['imagem'])): ?>
      <div class="d-flex align-items-start gap-3 mb-3">
        <div class="img-preview-wrap position-relative d-inline-block">
          <img src="<?= url('/uploads/produtos/' . e($produto['imagem'])) ?>"
               class="thumb-prod-edit rounded border" alt="Foto atual">
        </div>
        <div>
          <div class="fw-semibold small mb-1">Foto de capa atual</div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remover_imagem" value="1"
              id="remImagemProd" onchange="document.getElementById('prevRemImagemProd').classList.toggle('d-none',!this.checked)">
            <label class="form-check-label text-danger small" for="remImagemProd">
              Remover imagem atual
            </label>
          </div>
          <div id="prevRemImagemProd" class="alert alert-warning py-1 mt-2 small d-none">
            ⚠️ A imagem será removida ao salvar.
          </div>
        </div>
      </div>
      <hr class="my-2">
      <label class="form-label small fw-semibold">Substituir foto de capa</label>
      <?php else: ?>
      <label class="form-label fw-semibold">Foto de capa</label>
      <?php endif; ?>

      <div>
        <label for="inputFotoProd" class="btn btn-outline-primary">
          <i class="bi bi-camera me-1"></i> Foto de capa
        </label>
        <input type="file" name="imagem" id="inputFotoProd" accept="image/*" capture="environment"
               class="d-none" onchange="previewFotoProd(this)">
      </div>
      <div class="form-text mb-2"><i class="bi bi-magic me-1"></i>Redimensionada e otimizada automaticamente.</div>
      <img id="prevFotoProd" src="" class="rounded border mt-2 d-none"
           style="max-width:180px;max-height:180px;object-fit:contain;background:#fff">

      <hr class="my-3">

      <div class="fw-semibold small mb-2">
        <i class="bi bi-images me-1 text-primary"></i>Galeria de Fotos
        <span class="text-muted fw-normal">(até 3 fotos, além da capa)</span>
      </div>

      <?php if ($galeriaProd): ?>
      <div class="mb-3">
        <div class="small text-muted mb-2">Fotos atuais — marque para remover ou defina como capa:</div>
        <div class="d-flex gap-3 flex-wrap">
          <?php foreach ($galeriaProd as $i => $img): ?>
          <div class="text-center">
            <img src="<?= url('/uploads/produtos/' . e($img)) ?>"
                 class="thumb-prod-edit d-block mb-1" alt="Galeria <?= $i + 1 ?>">
            <button type="submit" name="nova_capa" value="<?= e($img) ?>"
              class="btn btn-outline-primary btn-sm py-0 px-1 d-block w-100 mb-1" style="font-size:.72rem"
              title="Usar esta foto como capa do produto">
              <i class="bi bi-star me-1"></i>Tornar capa
            </button>
            <div class="form-check d-flex justify-content-center">
              <input class="form-check-input" type="checkbox"
                name="remover_galeria[]" value="<?= e($img) ?>"
                id="remGalProd<?= $i ?>" title="Remover esta foto">
              <label class="form-check-label ms-1 small text-danger" for="remGalProd<?= $i ?>">
                Remover
              </label>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <?php if ($vagasGaleriaProd > 0): ?>
      <label class="form-label small fw-semibold">
        Adicionar até <?= $vagasGaleriaProd ?> foto<?= $vagasGaleriaProd > 1 ? 's' : '' ?> nova<?= $vagasGaleriaProd > 1 ? 's' : '' ?> à galeria
      </label>
      <input type="file" name="galeria[]" class="form-control" multiple
        accept="image/*" onchange="previewGaleriaProd(this)">
      <div id="prevGaleriaProd" class="d-flex gap-2 mt-2 flex-wrap"></div>
      <?php else: ?>
      <div class="alert alert-info py-2 small mb-0">
        Galeria cheia (3/3). Remova uma foto acima para adicionar outra.
      </div>
      <?php endif; ?>

    </div>
  </div>

  <div class="d-flex gap-2 justify-content-end align-items-center">
    <?php if ($editando): ?>
    <a href="<?= url('/marketplace/meus-anuncios?produto_id=' . $produto['id']) ?>" class="btn btn-outline-success me-auto">
      <i class="bi bi-shop-window me-1"></i>Anunciar no Diretório
    </a>
    <?php endif; ?>
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

// Preview da foto de capa do produto (ao tirar/escolher)
function previewFotoProd(input) {
  const img = document.getElementById('prevFotoProd');
  if (!input.files || !input.files[0]) return;
  const reader = new FileReader();
  reader.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
  reader.readAsDataURL(input.files[0]);
}

// Preview das novas fotos de galeria (multi-arquivo)
function previewGaleriaProd(input) {
  const box = document.getElementById('prevGaleriaProd');
  box.innerHTML = '';
  Array.from(input.files).slice(0, 3).forEach(f => {
    const r = new FileReader();
    r.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #dee2e6';
      box.appendChild(img);
    };
    r.readAsDataURL(f);
  });
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
