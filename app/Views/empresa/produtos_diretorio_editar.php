<?php
$imgPrincipal = $produto['imagem_principal'] ?? null;
$galeria      = $galeriaAtual ?? [];
?>

<style>
.dp-img-preview-wrap { position:relative; display:inline-block; }
.dp-thumb-edit { width:100px; height:100px; object-fit:cover; border-radius:10px; border:2px solid #dee2e6; }
</style>

<div class="row justify-content-center">
<div class="col-lg-8">

  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= url('/empresa/produtos-diretorio') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h5 class="fw-bold mb-0">Editar Produto do Diretório</h5>
      <small class="text-muted"><?= e($produto['titulo']) ?></small>
    </div>
    <span class="badge bg-primary ms-auto">
      <i class="bi bi-box-seam me-1"></i>Vagas: <?= $qtd ?>/<?= $limite ?>
    </span>
  </div>

  <form method="POST" action="<?= url('/empresa/produtos-diretorio/' . $produto['id'] . '/editar') ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- Dados básicos -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Informações do produto</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Título *</label>
          <input type="text" name="titulo" class="form-control" required maxlength="120"
            value="<?= e($produto['titulo']) ?>">
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Valor (R$) *</label>
          <div class="input-group">
            <span class="input-group-text">R$</span>
            <input type="text" name="valor" class="form-control" required
              value="<?= number_format((float) $produto['valor'], 2, ',', '.') ?>">
          </div>
        </div>

        <div class="mb-0">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="descricao" class="form-control" rows="4"><?= e($produto['descricao'] ?? '') ?></textarea>
        </div>
      </div>
    </div>

    <!-- Imagem principal -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-image me-1 text-primary"></i>Foto Principal
      </div>
      <div class="card-body">
        <?php if ($imgPrincipal): ?>
        <div class="d-flex align-items-start gap-3 mb-3">
          <div class="dp-img-preview-wrap">
            <img src="<?= url('/uploads/diretorio-produtos/' . e($imgPrincipal)) ?>"
                 class="dp-thumb-edit" alt="Imagem principal" style="width:140px;height:105px">
          </div>
          <div>
            <div class="fw-semibold small mb-1">Imagem atual</div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remover_principal" value="1"
                id="dpRemPrincipal" onchange="document.getElementById('dpPrevRemPrincipal').classList.toggle('d-none',!this.checked)">
              <label class="form-check-label text-danger small" for="dpRemPrincipal">
                Remover imagem atual
              </label>
            </div>
            <div id="dpPrevRemPrincipal" class="alert alert-warning py-1 mt-2 small d-none">
              ⚠️ A imagem será removida ao salvar.
            </div>
          </div>
        </div>
        <hr class="my-2">
        <label class="form-label small fw-semibold">Substituir por nova imagem</label>
        <?php else: ?>
        <label class="form-label fw-semibold">Adicionar foto principal</label>
        <?php endif; ?>
        <input type="file" name="imagem_principal" class="form-control"
          accept="image/*" capture="environment" onchange="previewNovaImgDP(this,'dpPrevNovaPrincipal')">
        <div class="form-text"><i class="bi bi-magic me-1"></i>Qualquer tamanho — convertida para <strong>800×800px WebP</strong> com fundo branco automaticamente.</div>
        <img id="dpPrevNovaPrincipal" src="" class="img-fluid rounded mt-2 d-none"
             style="max-height:160px;object-fit:cover">
      </div>
    </div>

    <!-- Galeria -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-images me-1 text-primary"></i>Galeria de Fotos
        <span class="text-muted fw-normal small">(máximo 2 fotos — 3 no total com a capa)</span>
      </div>
      <div class="card-body">

        <?php if ($galeria): ?>
        <div class="mb-3">
          <div class="small fw-semibold mb-2">Fotos atuais — marque para remover:</div>
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach ($galeria as $i => $img): ?>
            <div class="text-center">
              <img src="<?= url('/uploads/diretorio-produtos/' . e($img)) ?>"
                   class="dp-thumb-edit d-block mb-1" alt="Galeria <?= $i+1 ?>">
              <button type="submit" name="nova_capa" value="<?= e($img) ?>"
                class="btn btn-outline-primary btn-sm py-0 px-1 d-block w-100 mb-1" style="font-size:.72rem"
                title="Usar esta foto como capa do produto">
                <i class="bi bi-star me-1"></i>Tornar capa
              </button>
              <div class="form-check d-flex justify-content-center">
                <input class="form-check-input" type="checkbox"
                  name="remover_galeria[]" value="<?= e($img) ?>"
                  id="dpRemGal<?= $i ?>" title="Remover esta foto">
                <label class="form-check-label ms-1 small text-danger" for="dpRemGal<?= $i ?>">
                  Remover
                </label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php $vagasGaleriaDP = 2 - count($galeria); ?>
        <?php if ($vagasGaleriaDP > 0): ?>
        <label class="form-label small fw-semibold">
          Adicionar até <?= $vagasGaleriaDP ?> foto<?= $vagasGaleriaDP > 1 ? 's' : '' ?> nova<?= $vagasGaleriaDP > 1 ? 's' : '' ?>
        </label>
        <input type="file" class="form-control" multiple
          accept="image/*" onchange="previewGaleriaEditDP(this)">
        <input type="file" name="galeria[]" id="dpInputGaleriaFinal" multiple class="d-none">
        <div class="form-text">Pode escolher aos poucos, uma foto de cada vez, até o limite</div>
        <div id="dpPrevGaleriaEdit" class="d-flex gap-2 mt-2 flex-wrap"></div>
        <?php else: ?>
        <div class="alert alert-info py-2 small mb-0">
          Galeria cheia (2/2). Remova uma foto acima para adicionar outra.
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="<?= url('/empresa/produtos-diretorio') ?>" class="btn btn-outline-secondary flex-fill">
        Cancelar
      </a>
      <button type="submit" class="btn btn-primary flex-fill fw-semibold">
        <i class="bi bi-check-lg me-1"></i>Salvar alterações
      </button>
    </div>
  </form>
</div>
</div>

<script>
// Comprime no navegador antes de enviar — mesmo motivo/padrão de produtos_diretorio.php
// (comprimirImagemProdutoDiretorio()): sem isso, uma foto de câmera/celular real (5-20MB) vai
// crua pro submit e pode passar do upload_max_filesize/post_max_size do servidor, que descarta
// o arquivo em silêncio (salva sem foto nenhuma, sem erro visível pro usuário).
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

async function previewNovaImgDP(input, id) {
  if (!input.files?.[0]) { document.getElementById(id).classList.add('d-none'); return; }
  const comprimida = await comprimirImagemProdutoDiretorio(input.files[0]);
  const dt = new DataTransfer();
  dt.items.add(comprimida);
  input.files = dt.files;
  const img = document.getElementById(id);
  const r = new FileReader();
  r.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
  r.readAsDataURL(comprimida);
}
// Acumula fotos da galeria entre vários "onchange" (mesmo motivo/padrão de
// produtos_diretorio.php) — selecionar uma de cada vez SUBSTITUÍA a anterior antes, porque
// cada change de um <input type="file"> substitui input.files, nunca soma com o que já
// estava selecionado. O input visível vira só um "gatilho" sem name, sempre limpo depois de
// cada seleção; o que de fato vai no <form> é o array JS (galeriaEditDPFiles) sincronizado
// num input oculto (#dpInputGaleriaFinal, name="galeria[]") via DataTransfer.
let galeriaEditDPFiles = [];
const GALERIA_EDIT_DP_MAX = <?= (int) $vagasGaleriaDP ?>;

function sincronizarGaleriaEditDPInput() {
  const dt = new DataTransfer();
  galeriaEditDPFiles.forEach(f => dt.items.add(f));
  document.getElementById('dpInputGaleriaFinal').files = dt.files;
}

function renderGaleriaEditDPPreview() {
  const box = document.getElementById('dpPrevGaleriaEdit');
  box.innerHTML = '';
  galeriaEditDPFiles.forEach((f, i) => {
    const r = new FileReader();
    r.onload = e => {
      const wrap = document.createElement('div');
      wrap.style.cssText = 'position:relative;display:inline-block';
      wrap.innerHTML = '<img src="' + e.target.result + '" style="width:80px;height:80px;object-fit:cover;' +
        'border-radius:8px;border:2px solid #dee2e6">' +
        '<button type="button" data-i="' + i + '" class="btn btn-danger btn-sm rounded-circle p-0" ' +
        'style="position:absolute;top:-6px;right:-6px;width:22px;height:22px;font-size:.7rem;line-height:1">' +
        '<i class="bi bi-x"></i></button>';
      box.appendChild(wrap);
      wrap.querySelector('button').addEventListener('click', function () {
        galeriaEditDPFiles.splice(Number(this.dataset.i), 1);
        sincronizarGaleriaEditDPInput();
        renderGaleriaEditDPPreview();
      });
    };
    r.readAsDataURL(f);
  });
}

async function previewGaleriaEditDP(input) {
  const novos = Array.from(input.files);
  input.value = '';
  for (const f of novos) {
    if (galeriaEditDPFiles.length >= GALERIA_EDIT_DP_MAX) { alert('Máximo de ' + GALERIA_EDIT_DP_MAX + ' foto(s) na galeria.'); break; }
    galeriaEditDPFiles.push(await comprimirImagemProdutoDiretorio(f));
  }
  sincronizarGaleriaEditDPInput();
  renderGaleriaEditDPPreview();
}
</script>
