<?php
$semSaldo = $qtd >= $limite;
?>

<style>
.diretprod-credito-box { background:linear-gradient(135deg,#1a1d23 0%,#212529 100%); border-radius:14px; }
.diretprod-status-ativo   { background:#d1fae5; color:#065f46; }
.diretprod-status-vendido { background:#fee2e2; color:#991b1b; }
/* Chip de tag: fundo leve + borda de cor forte (não preenchimento sólido, pra diferenciar do
   chip laranja sólido já usado em Empresa → Perfil Público/Especialidades). Cor cicla por
   índice — mesma tag sempre cai na mesma cor enquanto a ordem não mudar. */
.dp-tag { display:inline-flex; align-items:center; gap:.35rem; padding:.22rem .6rem; border-radius:999px; font-size:.78rem; font-weight:600; border:1.5px solid; white-space:nowrap; }
.dp-tag i { font-size:.7rem; cursor:pointer; opacity:.75; }
.dp-tag i:hover { opacity:1; }
.dp-tag-0 { background:#eff6ff; border-color:#3b82f6; color:#1d4ed8; }
.dp-tag-1 { background:#f0fdf4; border-color:#22c55e; color:#15803d; }
.dp-tag-2 { background:#fff7ed; border-color:#f97316; color:#c2410c; }
.dp-tag-3 { background:#faf5ff; border-color:#a855f7; color:#7e22ce; }
.dp-tag-4 { background:#fef2f2; border-color:#ef4444; color:#b91c1c; }
.dp-tag-5 { background:#f0fdfa; border-color:#14b8a6; color:#0f766e; }
.dp-tags-box { min-height:44px; cursor:text; }
.dp-tags-box input { border:0; outline:none; min-width:120px; flex:1 1 auto; background:transparent; }
</style>

<div class="d-flex align-items-center gap-3 mb-4 flex-wrap">
  <div>
    <h5 class="fw-bold mb-0"><i class="bi bi-shop-window me-2 text-primary"></i>Produtos no Diretório</h5>
    <small class="text-muted">Vitrine de até <?= $limite ?> produtos na sua página pública do Diretório</small>
  </div>
  <div class="d-flex gap-2 ms-auto">
    <?php if ($urlPublica): ?>
    <a href="<?= $urlPublica ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-eye me-1"></i>Visualizar empresa
    </a>
    <?php endif; ?>
    <a href="<?= url('/empresa/perfil-publico') ?>" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-pencil-square me-1"></i>Editar perfil
    </a>
  </div>
</div>

<?php if (!$planoCompleto): ?>
<div class="alert alert-warning d-flex gap-2 mb-4">
  <i class="bi bi-lock-fill fs-5 flex-shrink-0"></i>
  <div>
    <strong>Recurso exclusivo de plano pago ativo.</strong> Cadastrar produtos na vitrine do Diretório
    faz parte dos planos pagos da FixaOS — o teste grátis não libera este recurso.
    <div class="mt-2"><a href="<?= url('/planos') ?>" class="btn btn-sm btn-warning fw-semibold">Ver planos</a></div>
  </div>
</div>
<?php endif; ?>

<!-- Cabeçalho com vagas -->
<div class="row g-3 mb-4">
  <div class="col-md-4">
    <div class="diretprod-credito-box text-white p-4 h-100">
      <div class="d-flex justify-content-between align-items-start">
        <div>
          <div class="text-white-50 small mb-1">Vagas usadas</div>
          <div class="display-5 fw-bold"><?= $qtd ?><span class="fs-4 text-white-50">/<?= $limite ?></span></div>
          <div class="text-white-50 small">produto<?= $qtd !== 1 ? 's' : '' ?> na vitrine</div>
        </div>
        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center"
             style="width:48px;height:48px">
          <i class="bi bi-box-seam fs-4 text-white"></i>
        </div>
      </div>
      <div class="mt-3 pt-3 border-top border-secondary">
        <small class="text-white-50">Produto "vendido" continua ocupando a vaga até ser excluído</small><br>
        <small class="text-white-50">Aparece só com plano pago ativo</small>
      </div>
    </div>
  </div>
  <div class="col-md-8">
    <div class="alert alert-info mb-0 h-100 d-flex align-items-center" style="font-size:.9rem">
      <div>
        <i class="bi bi-lightbulb-fill me-1" style="color:#f59e0b"></i>
        <strong>ATENÇÃO:</strong> procure anunciar produtos que representem sua empresa —
        capinha de celular, celular usado ou novo, peças em promoção, TV usada, ou qualquer
        outro produto que identifique o que você vende. Não é um marketplace geral, é uma
        vitrine da SUA empresa no Diretório.
      </div>
    </div>
  </div>
</div>

<!-- Ações -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div></div>
  <div>
    <?php if ($planoCompleto && !$semSaldo): ?>
    <button class="btn btn-success btn-sm fw-semibold" data-bs-toggle="offcanvas" data-bs-target="#offcanvasProdutoDiretorio">
      <i class="bi bi-plus-lg me-1"></i>Cadastrar produto
    </button>
    <?php elseif ($planoCompleto): ?>
    <button class="btn btn-secondary btn-sm" disabled title="Limite de vagas atingido">
      <i class="bi bi-lock me-1"></i>Cadastrar produto (limite atingido)
    </button>
    <?php endif; ?>
  </div>
</div>

<!-- Lista de produtos -->
<?php if (!$produtos): ?>
<div class="text-center py-5 text-muted">
  <i class="bi bi-shop-window fs-1 d-block mb-3 opacity-30"></i>
  <h5>Nenhum produto cadastrado ainda</h5>
  <?php if ($planoCompleto): ?>
  <p>Cadastre até <?= $limite ?> produtos e eles aparecem na sua página pública do Diretório.</p>
  <button class="btn btn-success mt-2" data-bs-toggle="offcanvas" data-bs-target="#offcanvasProdutoDiretorio">
    <i class="bi bi-plus-lg me-1"></i>Cadastrar agora
  </button>
  <?php endif; ?>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th>Produto</th><th>Valor</th><th>Status</th><th>Data</th><th class="text-end">Ações</th></tr>
      </thead>
      <tbody>
        <?php foreach ($produtos as $item): ?>
        <tr>
          <td class="d-flex align-items-center gap-2">
            <?php if ($item['imagem_principal']): ?>
            <img src="<?= url('/uploads/diretorio-produtos/' . e($item['imagem_principal'])) ?>"
                 alt="" style="width:40px;height:40px;object-fit:cover;border-radius:6px">
            <?php endif; ?>
            <span class="fw-semibold"><?= e($item['titulo']) ?></span>
          </td>
          <td class="fw-semibold text-success"><?= money($item['valor']) ?></td>
          <td>
            <span class="badge rounded-pill diretprod-status-<?= $item['status'] ?>">
              <?= $item['status'] === 'vendido' ? 'Vendido' : 'Ativo' ?>
            </span>
          </td>
          <td class="text-muted"><?= date_br($item['criado_em']) ?></td>
          <td>
            <div class="d-flex flex-wrap gap-1 justify-content-end">
              <a href="<?= url('/empresa/produtos-diretorio/' . $item['id'] . '/editar') ?>"
                 class="btn btn-sm btn-primary">
                <i class="bi bi-pencil-square me-1"></i>Editar
              </a>
              <button class="btn btn-sm btn-outline-secondary"
                onclick="alternarVendidoProdDiretorio(<?= $item['id'] ?>)"
                title="<?= $item['status']==='ativo' ? 'Marcar como vendido' : 'Marcar como ativo' ?>">
                <i class="bi bi-<?= $item['status']==='ativo' ? 'check2-circle' : 'arrow-counterclockwise' ?>"></i>
              </button>
              <a href="#" class="btn btn-sm btn-outline-danger"
                 data-method="DELETE"
                 data-href="<?= url('/empresa/produtos-diretorio/' . $item['id']) ?>"
                 data-confirm="Excluir este produto da vitrine?">
                <i class="bi bi-trash"></i>
              </a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Offcanvas: Novo Produto -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasProdutoDiretorio" style="width:420px">
  <div class="offcanvas-header border-bottom">
    <div>
      <h5 class="offcanvas-title fw-bold mb-0">
        <i class="bi bi-box-seam me-2 text-success"></i>Cadastrar Produto
      </h5>
      <p class="text-muted small mb-0">Vagas: <strong><?= $qtd ?>/<?= $limite ?></strong></p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <form method="POST" action="<?= url('/empresa/produtos-diretorio') ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="produto_id" value="<?= e($prefill['produto_id'] ?? '') ?>">

      <?php if ($prefill): ?>
      <div class="alert alert-success d-flex gap-2 py-2 mb-3" style="font-size:.85rem">
        <i class="bi bi-box-seam flex-shrink-0 mt-1"></i>
        <div>
          Preenchido a partir do produto do seu estoque.
          <?php if (!empty($prefill['tem_foto'])): ?>
          A foto que o produto já tem no Estoque será usada aqui também — só anexe uma nova
          abaixo se quiser trocar.
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Título *</label>
        <input type="text" name="titulo" class="form-control" required maxlength="120"
          value="<?= e($prefill['titulo'] ?? '') ?>" placeholder="Ex: iPhone 12 seminovo 128GB">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Valor (R$) *</label>
        <div class="input-group">
          <span class="input-group-text">R$</span>
          <input type="text" name="valor" class="form-control" required
            value="<?= $prefill && $prefill['valor'] > 0 ? number_format((float) $prefill['valor'], 2, ',', '.') : '' ?>" placeholder="0,00">
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Descrição</label>
        <textarea name="descricao" class="form-control" rows="3"
          placeholder="Condição, garantia, forma de pagamento..."><?= e($prefill['descricao'] ?? '') ?></textarea>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Quantidade disponível</label>
        <input type="number" name="quantidade" class="form-control" min="1" step="1" value="1" style="max-width:140px">
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">
          Tags <small class="text-muted fw-normal">(Enter ou vírgula pra adicionar)</small>
        </label>
        <div id="tagsBoxDP" class="form-control d-flex flex-wrap align-items-center gap-1 dp-tags-box" onclick="document.getElementById('tagInputDP').focus()">
          <span id="tagsListaDP" class="d-flex flex-wrap gap-1"></span>
          <input type="text" id="tagInputDP" placeholder="Ex: usado, garantia, promoção">
        </div>
        <input type="hidden" name="tags" id="tagsHiddenDP">
        <div class="form-text">Ajudam o produto a ser encontrado no Google — ex: marca, modelo, condição.</div>
      </div>

      <div class="alert alert-warning d-flex gap-2 py-2 mb-3" style="font-size:.85rem">
        <i class="bi bi-lightbulb-fill flex-shrink-0 mt-1" style="color:#f59e0b"></i>
        <div>
          Fotografe em local bem iluminado, de preferência com fundo claro. O sistema
          redimensiona e converte automaticamente — pode enviar qualquer tamanho!
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">
          <i class="bi bi-image me-1 text-primary"></i>Foto principal
        </label>
        <input type="file" name="imagem_principal" class="form-control"
          accept="image/*" capture="environment" id="inputImgPrincipalDP"
          onchange="previewImgDP(this,'prevMainDP')">
        <div class="form-text">
          <i class="bi bi-magic me-1"></i>Convertida automaticamente para <strong>800×800px WebP</strong> com fundo branco.
        </div>
        <img id="prevMainDP" src="" class="img-fluid rounded mt-2 d-none" style="max-height:180px;object-fit:cover">
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">
          <i class="bi bi-images me-1 text-primary"></i>Galeria de fotos (até 2 — total 3 com a principal)
        </label>
        <input type="file" class="form-control" multiple
          accept="image/*" id="inputGaleriaDP"
          onchange="previewGaleriaDP(this)">
        <input type="file" name="galeria[]" id="inputGaleriaDPFinal" multiple class="d-none">
        <div class="form-text">Pode escolher aos poucos, uma foto de cada vez, até o limite</div>
        <div id="prevGaleriaDP" class="d-flex gap-2 mt-2 flex-wrap"></div>
      </div>

      <button type="submit" class="btn btn-success w-100 fw-semibold">
        <i class="bi bi-check-lg me-1"></i>Cadastrar no Diretório
      </button>
    </form>
  </div>
</div>

<script>
<?php if ($prefill): ?>
document.addEventListener('DOMContentLoaded', function () {
  bootstrap.Offcanvas.getOrCreateInstance(document.getElementById('offcanvasProdutoDiretorio')).show();
});
<?php endif; ?>

// Comprime no navegador antes de enviar — sem isso, uma foto de câmera/celular real (5-20MB)
// vai crua pro submit e pode passar do upload_max_filesize/post_max_size do servidor, que
// descarta o arquivo em silêncio (o produto salva sem foto nenhuma, sem erro visível pro
// usuário). Mesmo padrão já usado em produtos/form.php (comprimirImagemProd()): reduz pra no
// máx. 1280px no maior lado, reexporta como JPEG qualidade 0,8.
function comprimirImagemProdutoDiretorio(file) {
  return new Promise(resolve => {
    const reader = new FileReader();
    reader.onload = e => {
      const img = new Image();
      img.onload = () => {
        let max = 1280, w = img.width, h = img.height;
        if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
        else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
        const c = document.createElement('canvas');
        c.width = w; c.height = h;
        const ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        c.toBlob(blob => {
          resolve(blob ? new File([blob], 'foto.jpg', { type: 'image/jpeg' }) : file);
        }, 'image/jpeg', 0.8);
      };
      img.onerror = () => resolve(file); // não decodificou (formato raro) — envia o original
      img.src = e.target.result;
    };
    reader.onerror = () => resolve(file);
    reader.readAsDataURL(file);
  });
}

async function previewImgDP(input, previewId) {
  if (!input.files || !input.files[0]) { document.getElementById(previewId).classList.add('d-none'); return; }
  const comprimida = await comprimirImagemProdutoDiretorio(input.files[0]);
  const dt = new DataTransfer();
  dt.items.add(comprimida);
  input.files = dt.files;
  const img = document.getElementById(previewId);
  const reader = new FileReader();
  reader.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
  reader.readAsDataURL(comprimida);
}

// Acumula fotos da galeria entre vários "onchange" (selecionar uma de cada vez substituía a
// anterior antes, porque cada change de um <input type="file"> SUBSTITUI input.files — nunca
// soma com o que já estava selecionado). O input visível (#inputGaleriaDP) vira só um "gatilho"
// sem name, sempre limpo depois de cada seleção; o que de fato vai no <form> é o array JS
// (galeriaDPFiles) sincronizado num input oculto (#inputGaleriaDPFinal, name="galeria[]") via
// DataTransfer — mesmo padrão já usado em produtos/form.php (galeriaProdFiles).
let galeriaDPFiles = [];
const GALERIA_DP_MAX = 2;

function sincronizarGaleriaDPInput() {
  const dt = new DataTransfer();
  galeriaDPFiles.forEach(f => dt.items.add(f));
  document.getElementById('inputGaleriaDPFinal').files = dt.files;
}

function renderGaleriaDPPreview() {
  const box = document.getElementById('prevGaleriaDP');
  box.innerHTML = '';
  galeriaDPFiles.forEach((f, i) => {
    const reader = new FileReader();
    reader.onload = e => {
      const wrap = document.createElement('div');
      wrap.style.cssText = 'position:relative;display:inline-block';
      wrap.innerHTML = '<img src="' + e.target.result + '" style="width:80px;height:80px;object-fit:cover;' +
        'border-radius:8px;border:2px solid #dee2e6">' +
        '<button type="button" data-i="' + i + '" class="btn btn-danger btn-sm rounded-circle p-0" ' +
        'style="position:absolute;top:-6px;right:-6px;width:22px;height:22px;font-size:.7rem;line-height:1">' +
        '<i class="bi bi-x"></i></button>';
      box.appendChild(wrap);
      wrap.querySelector('button').addEventListener('click', function () {
        galeriaDPFiles.splice(Number(this.dataset.i), 1);
        sincronizarGaleriaDPInput();
        renderGaleriaDPPreview();
      });
    };
    reader.readAsDataURL(f);
  });
}

async function previewGaleriaDP(input) {
  const novos = Array.from(input.files);
  input.value = '';
  for (const f of novos) {
    if (galeriaDPFiles.length >= GALERIA_DP_MAX) { alert('Máximo de ' + GALERIA_DP_MAX + ' foto(s) na galeria.'); break; }
    galeriaDPFiles.push(await comprimirImagemProdutoDiretorio(f));
  }
  sincronizarGaleriaDPInput();
  renderGaleriaDPPreview();
}

// Widget de tags — mesma interação de empresa/perfil_publico.php (Enter/vírgula adiciona,
// Backspace com campo vazio remove a última), mas com o chip novo (fundo leve + borda de cor
// forte, .dp-tag-N cíclico) em vez do preenchimento sólido usado lá. Renderiza via DOM
// (createElement/textContent), nunca innerHTML com a tag concatenada, pra não abrir brecha de
// HTML injection numa tag digitada pelo usuário.
let tagsDP = [];

function renderTagsDP() {
  const box = document.getElementById('tagsListaDP');
  box.innerHTML = '';
  tagsDP.forEach((t, i) => {
    const chip = document.createElement('span');
    chip.className = 'dp-tag dp-tag-' + (i % 6);
    const txt = document.createElement('span');
    txt.textContent = t;
    const btn = document.createElement('i');
    btn.className = 'bi bi-x-circle-fill';
    btn.title = 'Remover tag';
    btn.addEventListener('click', function (ev) {
      ev.stopPropagation();
      tagsDP.splice(i, 1);
      renderTagsDP();
    });
    chip.appendChild(txt);
    chip.appendChild(btn);
    box.appendChild(chip);
  });
  document.getElementById('tagsHiddenDP').value = tagsDP.join(',');
}

function addTagDP() {
  const input = document.getElementById('tagInputDP');
  let v = input.value.replace(/,+$/, '').trim();
  input.value = '';
  if (!v) return;
  v = v.slice(0, 30);
  if (tagsDP.length >= 10) return;
  if (tagsDP.some(t => t.toLowerCase() === v.toLowerCase())) return;
  tagsDP.push(v);
  renderTagsDP();
}

document.getElementById('tagInputDP').addEventListener('keydown', function (e) {
  if (e.key === 'Enter' || e.key === ',') { e.preventDefault(); addTagDP(); }
  else if (e.key === 'Backspace' && this.value === '') { tagsDP.pop(); renderTagsDP(); }
});
document.getElementById('tagInputDP').addEventListener('blur', addTagDP);

async function alternarVendidoProdDiretorio(id) {
  const r = await fetch(`<?= url('/empresa/produtos-diretorio/') ?>${id}/vender`, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-CSRF-Token':'<?= csrf_token() ?>'},
    body: JSON.stringify({})
  });
  if ((await r.json()).success) location.reload();
}
</script>
