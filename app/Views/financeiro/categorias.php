<style>
.badge-receita { background:#d1fae5; color:#065f46; }
.badge-despesa { background:#fee2e2; color:#991b1b; }
.badge-ativo   { background:#dbeafe; color:#1e40af; }
.badge-inativo { background:#f3f4f6; color:#6b7280; }
.cat-dot { width:14px; height:14px; border-radius:50%; display:inline-block; flex-shrink:0; }
.tipo-btn { cursor:pointer; border:2px solid #e2e8f0; border-radius:10px; padding:.6rem 1rem; transition:all .18s; }
.tipo-btn.selected-receita { border-color:#16a34a; background:#f0fdf4; }
.tipo-btn.selected-despesa { border-color:#dc2626; background:#fef2f2; }
</style>

<!-- Cabeçalho -->
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-tags me-2 text-primary"></i>Categorias Financeiras</h5>
    <small class="text-muted">Organize suas receitas e despesas por categoria</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/financeiro') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i>Voltar
    </a>
    <button class="btn btn-primary fw-semibold"
            data-bs-toggle="modal" data-bs-target="#modalCategoria"
            onclick="abrirModal()">
      <i class="bi bi-plus-lg me-1"></i>Nova Categoria
    </button>
  </div>
</div>

<!-- Filtros rápidos -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <button class="btn btn-sm btn-outline-secondary active" onclick="filtrar('todos', this)">Todas</button>
  <button class="btn btn-sm btn-outline-success" onclick="filtrar('receita', this)">↑ Receitas</button>
  <button class="btn btn-sm btn-outline-danger"  onclick="filtrar('despesa', this)">↓ Despesas</button>
  <button class="btn btn-sm btn-outline-secondary" onclick="filtrar('inativo', this)">Inativas</button>
</div>

<!-- Tabela -->
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" id="tabelaCats">
      <thead>
        <tr>
          <th style="width:40px">Cor</th>
          <th>Nome</th>
          <th>Tipo</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody id="tbodyCategorias">
        <?php foreach ($categorias as $cat): ?>
        <tr data-id="<?= $cat['id'] ?>" data-tipo="<?= $cat['tipo'] ?>" data-status="<?= $cat['status'] ?>">
          <td><span class="cat-dot" style="background:<?= e($cat['cor'] ?? '#6c757d') ?>"></span></td>
          <td class="fw-semibold"><?= e($cat['nome']) ?></td>
          <td>
            <span class="badge rounded-pill badge-<?= $cat['tipo'] ?>">
              <?= $cat['tipo'] === 'receita' ? '↑ Receita' : '↓ Despesa' ?>
            </span>
          </td>
          <td>
            <span class="badge rounded-pill badge-<?= $cat['status'] ?>">
              <?= $cat['status'] === 'ativo' ? 'Ativa' : 'Inativa' ?>
            </span>
          </td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary me-1"
              title="Editar"
              onclick="abrirModal(<?= htmlspecialchars(json_encode($cat), ENT_QUOTES, 'UTF-8') ?>)">
              <i class="bi bi-pencil-square"></i> Editar
            </button>
            <button class="btn btn-sm btn-outline-danger"
              title="Excluir"
              onclick="confirmarExcluir(<?= $cat['id'] ?>, '<?= e($cat['nome']) ?>')">
              <i class="bi bi-trash"></i>
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$categorias): ?>
        <tr id="trVazio">
          <td colspan="5" class="text-center text-muted py-5">
            <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>
            Nenhuma categoria cadastrada ainda.<br>
            <button class="btn btn-primary btn-sm mt-3" onclick="abrirModal()">
              <i class="bi bi-plus-lg me-1"></i>Criar primeira categoria
            </button>
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-white border-top-0 text-muted small">
    <span id="totalCats"><?= count($categorias) ?></span> categoria(s) cadastrada(s)
  </div>
</div>

<!-- Botão flutuante (FAB) -->
<button class="btn btn-primary rounded-circle shadow-lg"
        data-bs-toggle="modal" data-bs-target="#modalCategoria"
        onclick="abrirModal()"
        title="Nova Categoria"
        style="position:fixed;bottom:2rem;right:2rem;width:56px;height:56px;font-size:1.4rem;z-index:999;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(37,99,235,.45)!important">
  <i class="bi bi-plus-lg"></i>
</button>

<!-- ══ Modal Criar / Editar ══ -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-labelledby="modalCatTitulo">
  <div class="modal-dialog">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h6 class="modal-title fw-bold mb-0" id="modalCatTitulo">Nova Categoria</h6>
          <small class="text-muted" id="modalCatSub">Preencha os dados abaixo</small>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">

        <!-- Alerta de erro -->
        <div id="alertaCat" class="d-none mb-3"></div>

        <input type="hidden" id="catId">

        <!-- Nome -->
        <div class="mb-4">
          <label class="form-label">Nome da categoria *</label>
          <input type="text" id="catNome" class="form-control form-control-lg"
            maxlength="80" placeholder="Ex: Aluguel, Serviços de Reparo, Material...">
          <div class="form-text">Escolha um nome claro e fácil de identificar</div>
        </div>

        <!-- Tipo -->
        <div class="mb-4">
          <label class="form-label">Tipo *</label>
          <div class="d-flex gap-3">
            <div class="tipo-btn flex-fill text-center selected-receita" id="btnTipoReceita"
                 onclick="selecionarTipo('receita')">
              <div class="fs-4">↑</div>
              <div class="fw-bold text-success">Receita</div>
              <div class="text-muted" style="font-size:.78rem">Dinheiro que entra</div>
            </div>
            <div class="tipo-btn flex-fill text-center" id="btnTipoDespesa"
                 onclick="selecionarTipo('despesa')">
              <div class="fs-4">↓</div>
              <div class="fw-bold text-danger">Despesa</div>
              <div class="text-muted" style="font-size:.78rem">Dinheiro que sai</div>
            </div>
          </div>
          <input type="hidden" id="catTipo" value="receita">
        </div>

        <!-- Cor -->
        <div class="mb-4">
          <label class="form-label">Cor de identificação</label>
          <div class="d-flex align-items-center gap-3">
            <input type="color" id="catCor" class="form-control form-control-color"
              value="#2563eb" style="width:60px;height:42px;cursor:pointer">
            <div class="d-flex gap-2 flex-wrap">
              <?php foreach (['#2563eb','#16a34a','#dc2626','#d97706','#7c3aed','#0891b2','#db2777','#ea580c','#64748b'] as $cor): ?>
              <span class="cat-dot" style="background:<?= $cor ?>;width:28px;height:28px;cursor:pointer;border:2px solid transparent;transition:.15s"
                    title="<?= $cor ?>"
                    onclick="document.getElementById('catCor').value='<?= $cor ?>';highlightCor(this)"></span>
              <?php endforeach; ?>
            </div>
          </div>
        </div>

        <!-- Status (só na edição) -->
        <div class="mb-0" id="wrapStatus" style="display:none">
          <label class="form-label">Status</label>
          <div class="d-flex gap-3">
            <div class="form-check">
              <input class="form-check-input" type="radio" name="catStatus" id="statusAtivo" value="ativo" checked>
              <label class="form-check-label fw-semibold text-success" for="statusAtivo">
                <i class="bi bi-check-circle me-1"></i>Ativa
              </label>
            </div>
            <div class="form-check">
              <input class="form-check-input" type="radio" name="catStatus" id="statusInativo" value="inativo">
              <label class="form-check-label fw-semibold text-secondary" for="statusInativo">
                <i class="bi bi-pause-circle me-1"></i>Inativa
              </label>
            </div>
          </div>
          <div class="form-text">Categorias inativas não aparecem ao criar lançamentos</div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          Cancelar
        </button>
        <button type="button" class="btn btn-primary fw-semibold px-4" id="btnSalvarCat" onclick="salvarCategoria()">
          <i class="bi bi-check-lg me-1"></i>Salvar categoria
        </button>
      </div>

    </div>
  </div>
</div>

<!-- ══ Modal Confirmar Exclusão ══ -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div class="w-100 text-center pt-2">
          <div class="bg-danger bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
               style="width:56px;height:56px">
            <i class="bi bi-trash text-danger fs-4"></i>
          </div>
          <h6 class="fw-bold mb-0">Excluir categoria?</h6>
        </div>
        <button type="button" class="btn-close position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="text-muted small mb-1">Você está prestes a excluir:</p>
        <p class="fw-bold mb-2" id="excluirNome"></p>
        <div class="alert alert-warning py-2 small mb-0">
          <i class="bi bi-info-circle me-1"></i>
          Se houver lançamentos vinculados, a categoria será <strong>desativada</strong> em vez de excluída.
        </div>
      </div>
      <div class="modal-footer border-0 pt-0 justify-content-center gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger fw-semibold" onclick="executarExcluir()">
          <i class="bi bi-trash me-1"></i>Sim, excluir
        </button>
      </div>
    </div>
  </div>
</div>

<script>
const CSRF = '<?= csrf_token() ?>';
let excluirId = null;

// Instâncias lazy — Bootstrap ainda não carregou quando este script roda
const modalCat     = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCategoria'));
const modalExcluir = () => bootstrap.Modal.getOrCreateInstance(document.getElementById('modalExcluir'));

// Quando o modal abre via data-bs-toggle sem passar categoria → reset de nova categoria
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('modalCategoria').addEventListener('show.bs.modal', function(e) {
    // Se não foi chamado via abrirModal(cat), garante que está limpo para criação
    if (!document.getElementById('catId').value) {
      abrirModal();
    }
  });
});

// ── Filtros ─────────────────────────────────────────────
function filtrar(tipo, btn) {
  document.querySelectorAll('.d-flex.gap-2.mb-3 .btn').forEach(b => b.classList.remove('active'));
  btn.classList.add('active');
  document.querySelectorAll('#tbodyCategorias tr[data-id]').forEach(tr => {
    if (tipo === 'todos')   tr.style.display = '';
    else if (tipo === 'receita' || tipo === 'despesa') tr.style.display = tr.dataset.tipo === tipo ? '' : 'none';
    else if (tipo === 'inativo') tr.style.display = tr.dataset.status === 'inativo' ? '' : 'none';
  });
}

// ── Seleção de tipo ──────────────────────────────────────
function selecionarTipo(tipo) {
  document.getElementById('catTipo').value = tipo;
  document.getElementById('btnTipoReceita').className =
    'tipo-btn flex-fill text-center' + (tipo === 'receita' ? ' selected-receita' : '');
  document.getElementById('btnTipoDespesa').className =
    'tipo-btn flex-fill text-center' + (tipo === 'despesa' ? ' selected-despesa' : '');
}

// ── Paleta de cores ──────────────────────────────────────
function highlightCor(el) {
  document.querySelectorAll('.cat-dot[onclick]').forEach(d => d.style.borderColor = 'transparent');
  el.style.borderColor = '#0f172a';
}

// ── Abrir modal ──────────────────────────────────────────
function abrirModal(cat = null) {
  // Reset
  document.getElementById('alertaCat').className = 'd-none';
  document.getElementById('catId').value    = cat?.id ?? '';
  document.getElementById('catNome').value  = cat?.nome ?? '';
  document.getElementById('catCor').value   = cat?.cor ?? '#2563eb';
  document.getElementById('wrapStatus').style.display = cat ? 'block' : 'none';
  document.getElementById('modalCatTitulo').textContent = cat ? 'Editar Categoria' : 'Nova Categoria';
  document.getElementById('modalCatSub').textContent    = cat ? `Editando: ${cat.nome}` : 'Preencha os dados abaixo';
  document.getElementById('btnSalvarCat').innerHTML     = cat
    ? '<i class="bi bi-check-lg me-1"></i>Salvar alterações'
    : '<i class="bi bi-check-lg me-1"></i>Salvar categoria';

  selecionarTipo(cat?.tipo ?? 'receita');

  if (cat) {
    document.querySelector(`input[name=catStatus][value="${cat.status}"]`).checked = true;
  }

  modalCat().show();
  setTimeout(() => document.getElementById('catNome').focus(), 350);
}

// ── Salvar (criar ou editar) ─────────────────────────────
async function salvarCategoria() {
  const id     = document.getElementById('catId').value;
  const nome   = document.getElementById('catNome').value.trim();
  const tipo   = document.getElementById('catTipo').value;
  const cor    = document.getElementById('catCor').value;
  const status = id ? document.querySelector('input[name=catStatus]:checked').value : 'ativo';
  const alerta = document.getElementById('alertaCat');

  if (!nome) {
    alerta.className = 'alert alert-danger py-2 small mb-3';
    alerta.textContent = '⚠️ Informe o nome da categoria.';
    document.getElementById('catNome').focus();
    return;
  }

  const btn = document.getElementById('btnSalvarCat');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Salvando...';

  const endpoint = id
    ? `<?= url('/financeiro/categorias/') ?>${id}`
    : `<?= url('/financeiro/categorias') ?>`;

  try {
    const resp = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({ nome, tipo, cor, status, csrf_token: CSRF }),
    });
    const json = await resp.json();

    if (json.error) {
      alerta.className = 'alert alert-danger py-2 small mb-3';
      alerta.textContent = '⚠️ ' + json.error;
      return;
    }

    if (json.aviso) {
      setTimeout(() => alert('ℹ️ ' + json.aviso), 300);
    }

    modalCat().hide();
    renderTabela(json.categorias);

  } catch(e) {
    alerta.className = 'alert alert-danger py-2 small mb-3';
    alerta.textContent = '⚠️ Erro de conexão. Tente novamente.';
  } finally {
    btn.disabled = false;
    btn.innerHTML = id
      ? '<i class="bi bi-check-lg me-1"></i>Salvar alterações'
      : '<i class="bi bi-check-lg me-1"></i>Salvar categoria';
  }
}

// ── Exclusão ─────────────────────────────────────────────
function confirmarExcluir(id, nome) {
  excluirId = id;
  document.getElementById('excluirNome').textContent = nome;
  modalExcluir().show();
}

async function executarExcluir() {
  if (!excluirId) return;

  try {
    const resp = await fetch(`<?= url('/financeiro/categorias/') ?>${excluirId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
      body: JSON.stringify({ _method: 'DELETE', csrf_token: CSRF }),
    });
    const json = await resp.json();

    modalExcluir().hide();

    if (json.aviso) {
      setTimeout(() => alert('ℹ️ ' + json.aviso), 300);
    }
    if (json.categorias !== undefined) {
      renderTabela(json.categorias);
    }
  } catch(e) {
    alert('Erro de conexão. Tente novamente.');
  }
}

// ── Renderizar tabela após operação ─────────────────────
function renderTabela(cats) {
  const tbody = document.getElementById('tbodyCategorias');
  document.getElementById('totalCats').textContent = cats.length;

  if (!cats.length) {
    tbody.innerHTML = `
      <tr id="trVazio">
        <td colspan="5" class="text-center text-muted py-5">
          <i class="bi bi-tags fs-1 d-block mb-2 opacity-25"></i>
          Nenhuma categoria cadastrada ainda.<br>
          <button class="btn btn-primary btn-sm mt-3" onclick="abrirModal()">
            <i class="bi bi-plus-lg me-1"></i>Criar primeira categoria
          </button>
        </td>
      </tr>`;
    return;
  }

  tbody.innerHTML = cats.map(c => `
    <tr data-id="${c.id}" data-tipo="${c.tipo}" data-status="${c.status}">
      <td><span class="cat-dot" style="background:${c.cor ?? '#6c757d'}"></span></td>
      <td class="fw-semibold">${c.nome}</td>
      <td><span class="badge rounded-pill badge-${c.tipo}">${c.tipo === 'receita' ? '↑ Receita' : '↓ Despesa'}</span></td>
      <td><span class="badge rounded-pill badge-${c.status}">${c.status === 'ativo' ? 'Ativa' : 'Inativa'}</span></td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-primary me-1"
          onclick='abrirModal(${JSON.stringify(c)})'>
          <i class="bi bi-pencil-square"></i> Editar
        </button>
        <button class="btn btn-sm btn-outline-danger"
          onclick="confirmarExcluir(${c.id}, '${c.nome.replace(/'/g, "\\'")}')">
          <i class="bi bi-trash"></i>
        </button>
      </td>
    </tr>
  `).join('');
}
</script>
