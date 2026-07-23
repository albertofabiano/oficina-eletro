<style>
.cat-row { border-radius:10px;border:1px solid #e2e8f0;padding:10px 14px;margin-bottom:8px;display:flex;align-items:center;gap:12px;background:#fff;transition:.15s; }
.cat-row:hover { border-color:#94a3b8; }
.cat-icon-prev { width:36px;height:36px;border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;flex-shrink:0; }
.icone-opt { font-size:1.1rem;cursor:pointer;padding:6px;border-radius:6px;border:1.5px solid #e2e8f0;background:#fff;transition:.13s; }
.icone-opt:hover,.icone-opt.sel { border-color:#2563eb;background:#eff6ff; }
</style>

<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-tags me-2 text-primary"></i>Categorias do Marketplace</h5>
    <small class="text-muted">Organize as peças por categoria na sua loja</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/marketplace/meus-anuncios') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
    <button class="btn btn-primary fw-semibold" onclick="abrirModal()">
      <i class="bi bi-plus-lg me-1"></i>Nova Categoria
    </button>
  </div>
</div>

<!-- Lista -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-3" id="listaCats">
    <?php foreach ($categorias as $c): ?>
    <div class="cat-row" id="cat-<?= $c['id'] ?>">
      <div class="cat-icon-prev" style="background:<?= e($c['cor']) ?>22;color:<?= e($c['cor']) ?>">
        <i class="bi <?= e($c['icone']) ?>"></i>
      </div>
      <div class="flex-grow-1">
        <div class="fw-semibold"><?= e($c['nome']) ?></div>
        <div class="text-muted small"><?= e($c['icone']) ?></div>
      </div>
      <span class="badge rounded-pill <?= $c['ativo'] ? 'bg-success' : 'bg-secondary' ?>"
            style="font-size:.7rem"><?= $c['ativo'] ? 'Ativa' : 'Inativa' ?></span>
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-primary" onclick="abrirModal(<?= htmlspecialchars(json_encode($c), ENT_QUOTES, 'UTF-8') ?>)">
          <i class="bi bi-pencil"></i>
        </button>
        <button class="btn btn-sm btn-outline-<?= $c['ativo'] ? 'warning' : 'success' ?>"
                onclick="toggle(<?= $c['id'] ?>)" title="<?= $c['ativo'] ? 'Desativar' : 'Ativar' ?>">
          <i class="bi bi-<?= $c['ativo'] ? 'pause' : 'play' ?>"></i>
        </button>
        <button class="btn btn-sm btn-outline-danger" onclick="excluir(<?= $c['id'] ?>, '<?= e($c['nome']) ?>')">
          <i class="bi bi-trash"></i>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
    <?php if (!$categorias): ?>
    <div class="text-center text-muted py-4">
      <i class="bi bi-tags fs-2 d-block mb-2 opacity-25"></i>
      Nenhuma categoria. Clique em <strong>Nova Categoria</strong> para começar.
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal -->
<div class="modal fade" id="modalCat" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="modalCatTitulo">Nova Categoria</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="catId">
        <div id="catAlerta" class="d-none mb-3"></div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Nome *</label>
          <input type="text" id="catNome" class="form-control" maxlength="80" placeholder="Ex: Placa Principal, Display...">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Cor</label>
          <div class="d-flex align-items-center gap-2 flex-wrap">
            <input type="color" id="catCor" class="form-control form-control-color" value="#0d6efd" style="width:52px;height:38px">
            <?php foreach (['#dc2626','#0d6efd','#16a34a','#f59e0b','#7c3aed','#0891b2','#db2777','#64748b'] as $cor): ?>
            <div style="width:26px;height:26px;border-radius:50%;background:<?= $cor ?>;cursor:pointer;border:2px solid transparent;transition:.13s"
                 onclick="document.getElementById('catCor').value='<?= $cor ?>'" title="<?= $cor ?>"></div>
            <?php endforeach; ?>
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Ícone</label>
          <div class="d-flex flex-wrap gap-2" id="paletaIcones">
            <?php foreach (['bi-tag','bi-cpu','bi-lightning-charge','bi-display','bi-lightbulb','bi-motherboard','bi-battery-charging','bi-plug','bi-phone','bi-laptop','bi-tools','bi-box','bi-camera','bi-speaker','bi-tv','bi-hdd','bi-wifi','bi-broadcast'] as $ic): ?>
            <button type="button" class="icone-opt" data-ic="<?= $ic ?>" onclick="selecionarIcone('<?= $ic ?>', this)">
              <i class="bi <?= $ic ?>"></i>
            </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" id="catIcone" value="bi-tag">
        </div>

        <!-- Preview -->
        <div class="p-2 rounded-2 d-flex align-items-center gap-2" style="background:#f8fafc;border:1px solid #e2e8f0">
          <div id="prevIcon" style="width:32px;height:32px;border-radius:7px;display:flex;align-items:center;justify-content:center;background:#0d6efd22;color:#0d6efd;font-size:1rem">
            <i class="bi bi-tag"></i>
          </div>
          <span id="prevNome" class="fw-semibold small text-muted">Nome da categoria</span>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary fw-semibold" id="btnSalvarCat" onclick="salvarCat()">
          <i class="bi bi-check-lg me-1"></i>Salvar
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF = '<?= csrf_token() ?>';
let iconeAtual = 'bi-tag';

function abrirModal(cat = null) {
  document.getElementById('catId').value    = cat?.id ?? '';
  document.getElementById('catNome').value  = cat?.nome ?? '';
  document.getElementById('catCor').value   = cat?.cor ?? '#0d6efd';
  document.getElementById('catAlerta').className = 'd-none';
  selecionarIcone(cat?.icone ?? 'bi-tag');
  document.getElementById('modalCatTitulo').textContent = cat ? 'Editar Categoria' : 'Nova Categoria';
  document.getElementById('prevNome').textContent = cat?.nome ?? 'Nome da categoria';
  atualizarPreview();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCat')).show();
  setTimeout(() => document.getElementById('catNome').focus(), 300);
}

function selecionarIcone(ic, btn = null) {
  iconeAtual = ic;
  document.getElementById('catIcone').value = ic;
  document.querySelectorAll('.icone-opt').forEach(b => b.classList.remove('sel'));
  if (btn) btn.classList.add('sel');
  else document.querySelector(`.icone-opt[data-ic="${ic}"]`)?.classList.add('sel');
  atualizarPreview();
}

function atualizarPreview() {
  const cor  = document.getElementById('catCor').value;
  const nome = document.getElementById('catNome').value || 'Nome da categoria';
  const prev = document.getElementById('prevIcon');
  prev.style.background = cor + '22';
  prev.style.color = cor;
  prev.innerHTML = `<i class="bi ${iconeAtual}"></i>`;
  document.getElementById('prevNome').textContent = nome;
}

document.getElementById('catNome').addEventListener('input', atualizarPreview);
document.getElementById('catCor').addEventListener('input', atualizarPreview);

async function salvarCat() {
  const nome = document.getElementById('catNome').value.trim();
  const alerta = document.getElementById('catAlerta');
  if (!nome) {
    alerta.className = 'alert alert-danger py-2 small mb-3';
    alerta.textContent = 'Informe o nome da categoria.';
    return;
  }
  const btn = document.getElementById('btnSalvarCat');
  btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';

  const resp = await fetch('<?= url('/marketplace/categorias') ?>', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},
    body: JSON.stringify({
      id: document.getElementById('catId').value,
      nome,
      icone: iconeAtual,
      cor: document.getElementById('catCor').value,
      csrf_token: CSRF
    })
  });
  const j = await resp.json();
  if (j.success) {
    bootstrap.Modal.getInstance(document.getElementById('modalCat')).hide();
    renderLista(j.categorias);
  } else {
    alerta.className = 'alert alert-danger py-2 small mb-3';
    alerta.textContent = j.error ?? 'Erro ao salvar.';
  }
  btn.disabled = false; btn.innerHTML = '<i class="bi bi-check-lg me-1"></i>Salvar';
}

async function toggle(id) {
  const resp = await fetch(`<?= url('/marketplace/categorias/') ?>${id}/toggle`, {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},
    body: JSON.stringify({csrf_token:CSRF})
  });
  const j = await resp.json();
  if (j.success) renderLista(j.categorias);
}

async function excluir(id, nome) {
  if (!confirm(`Excluir a categoria "${nome}"?`)) return;
  const resp = await fetch(`<?= url('/marketplace/categorias/') ?>${id}/excluir`, {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},
    body: JSON.stringify({csrf_token:CSRF})
  });
  const j = await resp.json();
  if (j.success) renderLista(j.categorias);
}

function renderLista(cats) {
  const box = document.getElementById('listaCats');
  if (!cats.length) {
    box.innerHTML = `<div class="text-center text-muted py-4"><i class="bi bi-tags fs-2 d-block mb-2 opacity-25"></i>Nenhuma categoria ainda.</div>`;
    return;
  }
  box.innerHTML = cats.map(c => `
    <div class="cat-row">
      <div class="cat-icon-prev" style="background:${c.cor}22;color:${c.cor}">
        <i class="bi ${c.icone}"></i>
      </div>
      <div class="flex-grow-1">
        <div class="fw-semibold">${c.nome}</div>
        <div class="text-muted small">${c.icone}</div>
      </div>
      <span class="badge rounded-pill ${c.ativo?'bg-success':'bg-secondary'}" style="font-size:.7rem">${c.ativo?'Ativa':'Inativa'}</span>
      <div class="d-flex gap-1">
        <button class="btn btn-sm btn-outline-primary" onclick='abrirModal(${JSON.stringify(c)})'><i class="bi bi-pencil"></i></button>
        <button class="btn btn-sm btn-outline-${c.ativo?'warning':'success'}" onclick="toggle(${c.id})"><i class="bi bi-${c.ativo?'pause':'play'}"></i></button>
        <button class="btn btn-sm btn-outline-danger" onclick="excluir(${c.id},'${c.nome.replace(/'/g,"\\'")}}')"><i class="bi bi-trash"></i></button>
      </div>
    </div>`).join('');
}
</script>
