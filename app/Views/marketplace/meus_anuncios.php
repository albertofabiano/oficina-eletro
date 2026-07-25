<?php
$corSaldo  = $saldo > 0 ? 'success' : 'danger';
$semSaldo  = $saldo < 1;
?>

<style>
.credito-box { background:linear-gradient(135deg,#1a1d23 0%,#212529 100%); border-radius:14px; }
.status-ativo   { background:#d1fae5; color:#065f46; }
.status-pausado { background:#fef3c7; color:#92400e; }
.status-vendido { background:#e0e7ff; color:#3730a3; }
</style>

<!-- Cabeçalho com saldo -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="credito-box text-white p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-white-50 small mb-1">Seus Créditos</div>
          <div class="display-5 fw-bold"><?= $saldo ?></div>
          <div class="text-white-50 small">crédito<?= $saldo !== 1 ? 's' : '' ?> disponível<?= $saldo !== 1 ? 'is' : '' ?></div>
        </div>
        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center"
             style="width:48px;height:48px">
          <i class="bi bi-coin fs-4 text-dark"></i>
        </div>
      </div>
      <div class="mt-3 pt-3 border-top border-secondary">
        <small class="text-white-50">1 crédito = 1 anúncio publicado</small><br>
        <small class="text-white-50">Solicite mais créditos ao administrador</small>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <!-- Histórico rápido -->
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold small">Últimas movimentações</div>
      <div class="card-body p-0">
        <?php if (empty($historico['data'])): ?>
        <div class="text-center text-muted py-3 small">Sem movimentações.</div>
        <?php else: ?>
        <ul class="list-group list-group-flush">
          <?php foreach ($historico['data'] as $h): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center py-2 small">
            <div>
              <span class="badge bg-<?= $h['tipo']==='compra'?'success':'warning text-dark' ?> me-2">
                <?= $h['tipo']==='compra' ? '+' : '' ?><?= $h['quantidade'] ?>
              </span>
              <?= e($h['justificativa']) ?>
            </div>
            <span class="text-muted"><?= date_br($h['data'], true) ?></span>
          </li>
          <?php endforeach; ?>
        </ul>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- Ações -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div class="d-flex gap-2">
    <a href="<?= url('/marketplace/meus-anuncios') ?>"
       class="btn btn-sm <?= !$status ? 'btn-primary' : 'btn-outline-secondary' ?>">Todos</a>
    <a href="?status=ativo"
       class="btn btn-sm <?= $status==='ativo' ? 'btn-success' : 'btn-outline-secondary' ?>">Ativos</a>
    <a href="?status=pausado"
       class="btn btn-sm <?= $status==='pausado' ? 'btn-warning' : 'btn-outline-secondary' ?>">Pausados</a>
    <a href="?status=vendido"
       class="btn btn-sm <?= $status==='vendido' ? 'btn-primary' : 'btn-outline-secondary' ?>">Vendidos</a>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= url('/marketplace') ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-shop me-1"></i>Ver Vitrine
    </a>
    <?php if (!$semSaldo): ?>
    <button class="btn btn-success btn-sm fw-semibold" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAnuncio">
      <i class="bi bi-plus-lg me-1"></i>Anunciar Peça
      <span class="badge bg-white text-success ms-1"><?= $saldo ?> cr.</span>
    </button>
    <?php else: ?>
    <button class="btn btn-secondary btn-sm" disabled title="Saldo insuficiente">
      <i class="bi bi-lock me-1"></i>Anunciar Peça (sem créditos)
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Aviso sem saldo -->
<?php if ($semSaldo): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
  <i class="bi bi-exclamation-triangle-fill fs-5"></i>
  <div>
    <strong>Saldo insuficiente.</strong> Você precisa de créditos para publicar anúncios.
    Entre em contato com o administrador do sistema para adquirir créditos.
  </div>
</div>
<?php endif; ?>

<!-- Lista de anúncios -->
<?php if (!$paginator['data']): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-bag-x fs-1 d-block mb-3 opacity-30"></i>
  <h5>Nenhum anúncio encontrado</h5>
  <?php if (!$semSaldo): ?>
  <p>Publique sua primeira peça e alcance outras assistências!</p>
  <button class="btn btn-success mt-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasAnuncio">
    <i class="bi bi-plus-lg me-1"></i>Anunciar agora
  </button>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th>Peça</th><th>Tipo / Marca</th><th>Valor</th><th>Status</th><th>Data</th><th class="text-end">Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $item): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($item['titulo']) ?></div>
            <?php if ($item['modelo']): ?><div class="text-muted"><?= e($item['modelo']) ?></div><?php endif; ?>
            <?php
            $appUrlMkt = require BASE_PATH . '/config/app.php';
            $baseUrlMkt = rtrim($appUrlMkt['url'], '/');
            $slugMkt = $item['slug'] ?? $item['id'];
            ?>
            <div class="mt-1">
              <a href="<?= $baseUrlMkt ?>/pecas/<?= e($slugMkt) ?>" target="_blank"
                 class="text-decoration-none" style="font-size:.72rem;color:#0d6efd">
                <i class="bi bi-link-45deg"></i>
                /pecas/<?= e($slugMkt) ?>
              </a>
            </div>
          </td>
          <td>
            <?php if ($item['tipo']): ?><div><?= e($item['tipo']) ?></div><?php endif; ?>
            <?php if ($item['marca']): ?><div class="text-muted"><?= e($item['marca']) ?></div><?php endif; ?>
          </td>
          <td class="fw-semibold text-success"><?= money($item['valor']) ?></td>
          <td>
            <span class="badge rounded-pill status-<?= $item['status'] ?>">
              <?= ucfirst($item['status']) ?>
            </span>
          </td>
          <td class="text-muted"><?= date_br($item['data_criacao']) ?></td>
          <td>
            <div class="d-flex flex-wrap gap-1 justify-content-end">
            <?php if ($item['status'] === 'ativo'): ?>
              <a href="<?= url('/pecas/' . $item['id']) ?>" target="_blank"
                 class="btn btn-sm btn-outline-info" title="Ver como o cliente vê no marketplace público">
                <i class="bi bi-eye me-1"></i>Ver
              </a>
            <?php endif; ?>
            <?php if ($item['status'] !== 'vendido'): ?>
              <a href="<?= url('/marketplace/anuncios/' . $item['id'] . '/editar') ?>"
                 class="btn btn-sm btn-primary">
                <i class="bi bi-pencil-square me-1"></i>Editar
              </a>
              <button class="btn btn-sm btn-outline-secondary"
                onclick="alterarStatus(<?= $item['id'] ?>, '<?= $item['status'] ?>')"
                title="<?= $item['status']==='ativo' ? 'Pausar' : 'Reativar' ?>">
                <i class="bi bi-<?= $item['status']==='ativo' ? 'pause-fill' : 'play-fill' ?>"></i>
              </button>
              <?php if ($item['status'] === 'ativo'): ?>
              <button class="btn btn-sm btn-outline-success"
                onclick="marcarVendido(<?= $item['id'] ?>)" title="Marcar como vendido">
                <i class="bi bi-check2-circle"></i>
              </button>
              <?php endif; ?>
            <?php endif; ?>
              <a href="#" class="btn btn-sm btn-outline-danger"
                 data-method="DELETE"
                 data-href="<?= url('/marketplace/anuncios/' . $item['id']) ?>"
                 data-confirm="Excluir este anúncio?">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between">
    <small class="text-muted"><?= $paginator['total'] ?> anúncio(s)</small>
    <?= pagination($paginator, url('/marketplace/meus-anuncios')) ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Offcanvas: Novo Anúncio -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAnuncio" style="width:420px">
  <div class="offcanvas-header border-bottom">
    <div>
      <h5 class="offcanvas-title fw-bold mb-0">
        <i class="bi bi-bag-plus me-2 text-success"></i>Anunciar Peça
      </h5>
      <p class="text-muted small mb-0">
        Custo: <strong>1 crédito</strong> &nbsp;|&nbsp; Saldo atual: <strong><?= $saldo ?></strong>
      </p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form method="POST" action="<?= url('/marketplace/anuncios') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="produto_id" id="prodVinculadoId" value="<?= e($prefill['produto_id'] ?? '') ?>">

      <?php if ($prefill): ?>
      <div class="alert alert-success d-flex gap-2 py-2 mb-3" style="font-size:.85rem">
        <i class="bi bi-box-seam flex-shrink-0 mt-1"></i>
        <div>
          Preenchido a partir do produto do seu estoque. <strong>Quantidade disponível: <?= (int) ($prefill['estoque'] ?? 0) ?></strong> — esse número aparece no anúncio.
        </div>
      </div>
      <?php endif; ?>

      <button type="button" class="btn btn-outline-primary w-100 mb-3 py-2" onclick="abrirScannerPlaca()" style="border-style:dashed">
        <i class="bi bi-phone-fill me-1"></i> Preencher pela câmera do celular
        <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">IA lê a placa</span>
      </button>

      <div class="mb-3">
        <label class="form-label fw-semibold">Título *</label>
        <input type="text" name="titulo" class="form-control" required maxlength="120"
          value="<?= e($prefill['titulo'] ?? '') ?>" placeholder="Ex: Display LCD 6.5 polegadas Samsung">
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">Valor (R$) *</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor" class="form-control" required
              value="<?= $prefill && $prefill['valor'] > 0 ? number_format((float)$prefill['valor'], 2, ',', '.') : '' ?>" placeholder="0,00">
          </div>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">Tipo / Categoria</label>
          <div class="input-group">
            <input type="text" name="tipo" id="selectTipo" class="form-control" maxlength="60"
              placeholder="Ex: Tela, Bateria, Placa" list="listaCategorias" autocomplete="off">
            <button type="button" class="btn btn-outline-primary" onclick="adicionarCategoriaAnuncio()" title="Salvar como categoria nova">
              <i class="bi bi-plus-lg"></i>
            </button>
          </div>
          <datalist id="listaCategorias">
            <?php foreach ($categorias as $c): ?>
            <option value="<?= e($c) ?>">
            <?php endforeach; ?>
            <option value="Tela / Display">
            <option value="Bateria">
            <option value="Placa Principal">
            <option value="Placa Fonte">
            <option value="Cabo Flat">
            <option value="Conector de Carga">
            <option value="Alto-falante">
            <option value="Câmera">
            <option value="Botão">
            <option value="Carcaça">
            <option value="Memória RAM">
            <option value="HD / SSD">
            <option value="Capacitor">
            <option value="CI / Chip">
            <option value="Fusível">
            <option value="Transformador">
            <option value="Resistor">
            <option value="Diodo">
            <option value="Transistor">
            <option value="LED Backlight">
            <option value="Inversor">
            <option value="Compressor">
            <option value="Motor">
            <option value="Sensor">
            <option value="Outro">
          </datalist>
          <div class="form-text">Digite ou selecione da lista. Use o <i class="bi bi-plus-lg"></i> pra salvar uma categoria nova e reaproveitar depois — ou <a href="<?= url('/marketplace/categorias') ?>" target="_blank">gerencie aqui</a>.</div>
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">Marca</label>
          <input type="text" name="marca" class="form-control" maxlength="60"
            placeholder="Ex: Samsung, LG">
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">Modelo</label>
          <input type="text" name="modelo" class="form-control" maxlength="100"
            placeholder="Ex: A52, G6, 55UM7510">
        </div>
      </div>

      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="form-label fw-semibold">Condição da peça</label>
          <select name="condicao" class="form-select">
            <option value="Testada e funcionando" selected>Testada e funcionando</option>
            <option value="Nova">Nova</option>
            <option value="Nova na embalagem">Nova na embalagem</option>
            <option value="Usada sem defeito">Usada sem defeito</option>
            <option value="Desmontada original">Desmontada original</option>
            <option value="Com defeito">Com defeito</option>
            <option value="Para retirada de peças">Para retirada de peças</option>
          </select>
        </div>
        <div class="col-6">
          <label class="form-label fw-semibold">Código da peça/placa</label>
          <input type="text" name="codigo_interno" class="form-control" maxlength="60"
            value="<?= e($prefill['codigo_interno'] ?? '') ?>" placeholder="Referência interna (opcional)">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3"
          placeholder="Estado da peça, compatibilidade, condições de envio/retirada..."><?= e($prefill['descricao'] ?? '') ?></textarea>
      </div>

      <!-- Dica de foto -->
      <div class="alert alert-warning d-flex gap-2 py-2 mb-3" style="font-size:.85rem">
        <i class="bi bi-lightbulb-fill flex-shrink-0 mt-1" style="color:#f59e0b"></i>
        <div>
          <strong>Dica para uma boa foto:</strong> Fotografe a peça em um <strong>local bem iluminado</strong>,
          de preferência com <strong>fundo branco ou claro</strong>. Isso valoriza o produto e aumenta as chances de venda.
          O sistema redimensiona e otimiza automaticamente — pode enviar qualquer tamanho!
        </div>
      </div>

      <!-- Imagens -->
      <div class="mb-3">
        <label class="form-label fw-semibold">
          <i class="bi bi-image me-1 text-primary"></i>Foto principal
        </label>
        <input type="file" name="imagem_principal" class="form-control"
          accept="image/*" id="inputImgPrincipal"
          onchange="previewImg(this,'prevMain')">
        <div class="form-text">
          <i class="bi bi-magic me-1"></i>
          Qualquer tamanho aceito — convertida automaticamente para <strong>800×800px WebP</strong> com fundo branco.
        </div>
        <img id="prevMain" src="" class="img-fluid rounded mt-2 d-none" style="max-height:180px;object-fit:cover">
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">
          <i class="bi bi-images me-1 text-primary"></i>Galeria de fotos (até 3 — total 4 com a principal)
        </label>
        <input type="file" name="galeria[]" class="form-control" multiple
          accept="image/*" id="inputGaleria"
          onchange="previewGaleria(this)">
        <div class="form-text">Selecione até 3 fotos adicionais para a galeria</div>
        <div id="prevGaleria" class="d-flex gap-2 mt-2 flex-wrap"></div>
      </div>

      <div class="alert alert-info py-2 small mb-3">
        <i class="bi bi-info-circle me-1"></i>
        Ao publicar, <strong>1 crédito</strong> será debitado do seu saldo.
        Seu WhatsApp (cadastrado na empresa) será exibido para contato.
      </div>

      <button type="submit" class="btn btn-success w-100 fw-semibold">
        <i class="bi bi-check-lg me-1"></i>Publicar anúncio (–1 crédito)
      </button>
    </form>
  </div>
</div>

<script>
<?php if ($prefill): ?>
document.addEventListener('DOMContentLoaded', function () {
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('offcanvasAnuncio')).show();
});
<?php endif; ?>

function previewImg(input, previewId) {
  const img = document.getElementById(previewId);
  if (!input.files || !input.files[0]) { img.classList.add('d-none'); return; }
  const reader = new FileReader();
  reader.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
  reader.readAsDataURL(input.files[0]);
}

function previewGaleria(input) {
  const box = document.getElementById('prevGaleria');
  box.innerHTML = '';
  const files = Array.from(input.files).slice(0, 3);
  files.forEach(file => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = document.createElement('img');
      img.src = e.target.result;
      img.style.cssText = 'width:80px;height:80px;object-fit:cover;border-radius:8px;border:2px solid #dee2e6';
      box.appendChild(img);
    };
    reader.readAsDataURL(file);
  });
}

// ── CRUD de Categorias ──────────────────────────────────────────────
function adicionarCategoria() {
  document.getElementById('novaCatWrap').classList.remove('d-none');
  document.getElementById('novaCatInput').focus();
}

function cancelarNovaCategoria() {
  document.getElementById('novaCatWrap').classList.add('d-none');
  document.getElementById('novaCatInput').value = '';
}

function confirmarNovaCategoria() {
  const val = document.getElementById('novaCatInput').value.trim();
  if (!val) return;
  const sel = document.getElementById('selectTipo');
  // Verificar se já existe
  for (let opt of sel.options) {
    if (opt.value.toLowerCase() === val.toLowerCase()) {
      sel.value = opt.value;
      cancelarNovaCategoria();
      return;
    }
  }
  // Adicionar nova opção
  const opt = new Option(val, val, true, true);
  sel.add(opt);
  sel.value = val;
  cancelarNovaCategoria();
}

document.getElementById('novaCatInput')?.addEventListener('keydown', function(e) {
  if (e.key === 'Enter') { e.preventDefault(); confirmarNovaCategoria(); }
  if (e.key === 'Escape') { cancelarNovaCategoria(); }
});

function abrirCrudCategorias() {
  const sel = document.getElementById('selectTipo');
  const cats = [];
  for (let opt of sel.options) {
    if (opt.value) cats.push(opt.value);
  }

  const lista = cats.map((c,i) => `
    <div class="d-flex align-items-center gap-2 mb-2" id="cat_row_${i}">
      <input type="text" class="form-control form-control-sm" value="${c}" id="cat_inp_${i}">
      <button class="btn btn-sm btn-success" onclick="salvarCat(${i},'${c.replace(/'/g,"\\'")}')"><i class="bi bi-check-lg"></i></button>
      <button class="btn btn-sm btn-outline-danger" onclick="excluirCat(${i},'${c.replace(/'/g,"\\'")}')"><i class="bi bi-trash"></i></button>
    </div>`).join('');

  document.getElementById('crudCatLista').innerHTML = lista || '<p class="text-muted small">Nenhuma categoria cadastrada ainda.</p>';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCrudCat')).show();
}

function salvarCat(i, antigo) {
  const novo = document.getElementById('cat_inp_' + i)?.value.trim();
  if (!novo) return;
  const sel = document.getElementById('selectTipo');
  for (let opt of sel.options) {
    if (opt.value === antigo) { opt.value = novo; opt.text = novo; }
  }
  abrirCrudCategorias();
}

function excluirCat(i, nome) {
  if (!confirm('Remover a categoria "' + nome + '" da lista?')) return;
  const sel = document.getElementById('selectTipo');
  for (let opt of sel.options) {
    if (opt.value === nome) { sel.remove(opt.index); break; }
  }
  abrirCrudCategorias();
}

async function alterarStatus(id, statusAtual) {
  const r = await fetch(`<?= url('/marketplace/anuncios/') ?>${id}/pausar`, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-Token':'<?= csrf_token() ?>'},
    body: JSON.stringify({})
  });
  if ((await r.json()).success) location.reload();
}

// Modal CRUD Categorias
document.addEventListener('DOMContentLoaded', function() {
  if (!document.getElementById('modalCrudCat')) {
    const m = document.createElement('div');
    m.innerHTML = `
    <div class="modal fade" id="modalCrudCat" tabindex="-1">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title fw-bold"><i class="bi bi-tags-fill me-2 text-primary"></i>Gerenciar Categorias</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-muted small mb-3">Edite ou remova categorias da lista. As alterações são aplicadas apenas neste formulário.</p>
            <div id="crudCatLista"></div>
            <hr>
            <div class="input-group input-group-sm">
              <input type="text" id="novaCatModal" class="form-control" placeholder="Nova categoria...">
              <button class="btn btn-primary" onclick="adicionarCatModal()"><i class="bi bi-plus-lg me-1"></i>Adicionar</button>
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
          </div>
        </div>
      </div>
    </div>`;
    document.body.appendChild(m.firstElementChild);
  }
});

function adicionarCatModal() {
  const val = document.getElementById('novaCatModal').value.trim();
  if (!val) return;
  const sel = document.getElementById('selectTipo');
  for (let opt of sel.options) {
    if (opt.value.toLowerCase() === val.toLowerCase()) return;
  }
  sel.add(new Option(val, val));
  document.getElementById('novaCatModal').value = '';
  abrirCrudCategorias();
}

async function marcarVendido(id) {
  if (!confirm('Marcar esta peça como vendida? O anúncio sairá da vitrine.')) return;
  const r = await fetch(`<?= url('/marketplace/anuncios/') ?>${id}/vender`, {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-Token':'<?= csrf_token() ?>'},
    body: JSON.stringify({})
  });
  if ((await r.json()).success) location.reload();
}
</script>

<!-- ── Scanner de placa: celular como câmera ── -->
<div class="modal fade" id="modalScannerPlaca" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0"><i class="bi bi-phone-fill me-1"></i>Ler placa pelo celular</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="small text-muted mb-2">Abra a câmera do celular (logado na mesma empresa) e escaneie o QR:</p>
        <div id="spQrBox" class="d-flex justify-content-center align-items-center mb-2" style="min-height:186px">
          <div class="spinner-border text-secondary"></div>
        </div>
        <div class="small text-muted">ou acesse <strong><?= e(parse_url(url('/'), PHP_URL_HOST)) ?>/scan</strong> e digite:</div>
        <div id="spCodigo" class="fw-bold fs-4" style="letter-spacing:.2em">••••••</div>
        <div id="spStatus" class="mt-2 small"><span class="spinner-border spinner-border-sm text-primary"></span> Aguardando o celular…</div>
      </div>
    </div>
  </div>
</div>
<script>
let _spToken = null, _spTimer = null;
async function abrirScannerPlaca(){
  const modalEl = document.getElementById('modalScannerPlaca');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  document.getElementById('spQrBox').innerHTML = '<div class="spinner-border text-secondary"></div>';
  document.getElementById('spCodigo').textContent = '••••••';
  document.getElementById('spStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> Aguardando o celular…';
  modal.show();
  modalEl.addEventListener('hidden.bs.modal', pararScannerPlaca, {once:true});
  try{
    const r = await fetch('<?= url('/scanner/nova') ?>', {method:'POST', headers:{'X-Requested-With':'XMLHttpRequest','Content-Type':'application/x-www-form-urlencoded'}, body:'modo=placa'});
    const j = await r.json();
    _spToken = j.token;
    document.getElementById('spQrBox').innerHTML = '<img src="'+j.qr+'" alt="QR Code" style="width:186px;height:186px">';
    document.getElementById('spCodigo').textContent = j.codigo;
    _spTimer = setInterval(pollScannerPlaca, 2000);
  }catch(e){
    document.getElementById('spStatus').innerHTML = '<span class="text-danger">Erro ao gerar o QR. Feche e tente de novo.</span>';
  }
}
function pararScannerPlaca(){ if(_spTimer){ clearInterval(_spTimer); _spTimer = null; } }
async function pollScannerPlaca(){
  if(!_spToken) return;
  try{
    const r = await fetch('<?= url('/scanner/status') ?>?token=' + encodeURIComponent(_spToken));
    if(!r.ok){
      if(r.status === 410){ document.getElementById('spStatus').innerHTML = '<span class="text-danger">A sessão expirou. Feche e abra de novo.</span>'; pararScannerPlaca(); }
      return;
    }
    const j = await r.json();
    if(j.status === 'processando'){
      document.getElementById('spStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> Identificando a peça na internet…';
      return;
    }
    if(j.status === 'erro'){
      pararScannerPlaca();
      document.getElementById('spStatus').innerHTML = '<span class="text-danger">'+(j.erro||'Não consegui ler a placa.')+'</span>';
      return;
    }
    if(j.status === 'pronto' && j.resultado){
      pararScannerPlaca();
      preencherDoScannerPlaca(j.resultado);
      document.getElementById('spStatus').innerHTML = '<span class="text-success fw-semibold">✅ Peça identificada! Preenchendo…</span>';
      setTimeout(()=>{ bootstrap.Modal.getInstance(document.getElementById('modalScannerPlaca')).hide(); }, 1000);
    }
  }catch(e){}
}
function preencherDoScannerPlaca(d){
  const form = document.querySelector('#offcanvasAnuncio form');
  if(!form) return;
  const flash = (el)=>{ if(!el) return; const o=el.style.transition; el.style.transition='background-color .3s'; el.style.backgroundColor='#d1fae5'; setTimeout(()=>{ el.style.backgroundColor=''; el.style.transition=o; }, 1300); };
  const set = (name,val)=>{ if(val===undefined||val===null||val==='') return; const el=form.querySelector('[name="'+name+'"]'); if(el){ el.value=val; flash(el); } };
  set('titulo', d.titulo);
  set('tipo', d.tipo);
  set('marca', d.marca);
  set('modelo', d.modelo || (d.modelos && d.modelos[0]) || '');
  set('codigo_interno', d.codigo);
  set('descricao', d.descricao);
}

// ── Salvar categoria nova (reaproveita o mesmo endpoint da tela "Categorias") ──
async function adicionarCategoriaAnuncio(){
  const inp = document.getElementById('selectTipo');
  const nome = (inp.value || '').trim();
  if (!nome) { inp.focus(); return; }
  const btn = document.querySelector('button[onclick="adicionarCategoriaAnuncio()"]');
  try{
    const r = await fetch('<?= url('/marketplace/categorias') ?>', {
      method: 'POST',
      headers: {'Content-Type':'application/x-www-form-urlencoded','X-CSRF-Token':'<?= csrf_token() ?>'},
      body: 'nome=' + encodeURIComponent(nome)
    });
    const d = await r.json();
    if (d.success) {
      const dl = document.getElementById('listaCategorias');
      if (!dl.querySelector('option[value="' + CSS.escape(nome) + '"]')) {
        const o = document.createElement('option'); o.value = nome;
        dl.prepend(o);
      }
      inp.value = nome;
      if (btn) { const old = btn.innerHTML; btn.innerHTML = '<i class="bi bi-check-lg"></i>'; setTimeout(()=>{ btn.innerHTML = old; }, 1200); }
    } else {
      alert(d.error || 'Não foi possível salvar a categoria.');
    }
  }catch(e){ alert('Erro ao salvar categoria.'); }
}
</script>
