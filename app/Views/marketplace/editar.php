<?php
$imgPrincipal = $anuncio['imagem_principal'] ?? null;
$galeria      = !empty($anuncio['imagens_galeria']) ? json_decode($anuncio['imagens_galeria'], true) : [];
?>

<style>
.img-preview-wrap { position:relative; display:inline-block; }
.img-preview-wrap .btn-remover {
  position:absolute; top:4px; right:4px;
  background:rgba(220,53,69,.85); color:#fff; border:none;
  border-radius:50%; width:24px; height:24px;
  font-size:.75rem; display:flex; align-items:center; justify-content:center;
  cursor:pointer; padding:0; line-height:1;
}
.thumb-edit {
  width:100px; height:100px; object-fit:cover;
  border-radius:10px; border:2px solid #dee2e6;
}
</style>

<div class="row justify-content-center">
<div class="col-lg-8">

  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="<?= url('/marketplace/meus-anuncios') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div>
      <h5 class="fw-bold mb-0">Editar Anúncio</h5>
      <small class="text-muted">OS: <?= e($anuncio['titulo']) ?></small>
    </div>
    <span class="badge bg-<?= $saldo>0?'success':'danger' ?> ms-auto">
      <i class="bi bi-coin me-1"></i><?= $saldo ?> crédito(s)
    </span>
  </div>

  <form method="POST" action="<?= url('/marketplace/anuncios/' . $anuncio['id'] . '/editar') ?>"
        enctype="multipart/form-data">
    <?= csrf_field() ?>

    <!-- Dados básicos -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Informações da peça</div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Título *</label>
          <input type="text" name="titulo" class="form-control" required maxlength="120"
            value="<?= e($anuncio['titulo']) ?>">
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Valor (R$) *</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor" class="form-control" required
                value="<?= number_format($anuncio['valor'], 2, ',', '.') ?>">
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Tipo / Categoria</label>
            <input type="text" name="tipo" class="form-control" maxlength="60"
              value="<?= e($anuncio['tipo'] ?? '') ?>"
              list="listaCategoriasMkt" autocomplete="off">
            <datalist id="listaCategoriasMkt">
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
              <option value="LED Backlight">
              <option value="Inversor">
              <option value="Compressor">
              <option value="Motor">
              <option value="Sensor">
              <option value="Outro">
            </datalist>
            <div class="form-text">Digite ou escolha da lista.</div>
          </div>
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Marca</label>
            <input type="text" name="marca" class="form-control" maxlength="60"
              value="<?= e($anuncio['marca'] ?? '') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Modelo</label>
            <input type="text" name="modelo" class="form-control" maxlength="100"
              value="<?= e($anuncio['modelo'] ?? '') ?>">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Código da peça/placa</label>
          <input type="text" name="codigo_interno" class="form-control" maxlength="60"
            value="<?= e($anuncio['codigo_interno'] ?? '') ?>">
        </div>

        <div class="mb-0">
          <label class="form-label fw-semibold">Descrição</label>
          <textarea name="descricao" class="form-control" rows="4"><?= e($anuncio['descricao'] ?? '') ?></textarea>
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
          <div class="img-preview-wrap">
            <img src="<?= url('/uploads/marketplace/' . e($imgPrincipal)) ?>"
                 class="thumb-edit" alt="Imagem principal" style="width:140px;height:105px">
          </div>
          <div>
            <div class="fw-semibold small mb-1">Imagem atual</div>
            <div class="form-check">
              <input class="form-check-input" type="checkbox" name="remover_principal" value="1"
                id="remPrincipal" onchange="document.getElementById('prevRemPrincipal').classList.toggle('d-none',!this.checked)">
              <label class="form-check-label text-danger small" for="remPrincipal">
                Remover imagem atual
              </label>
            </div>
            <div id="prevRemPrincipal" class="alert alert-warning py-1 mt-2 small d-none">
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
          accept="image/*" onchange="previewNovaImg(this,'prevNovaPrincipal')">
        <div class="form-text"><i class="bi bi-magic me-1"></i>Qualquer tamanho — convertida para <strong>800×800px WebP</strong> com fundo branco automaticamente.</div>
        <img id="prevNovaPrincipal" src="" class="img-fluid rounded mt-2 d-none"
             style="max-height:160px;object-fit:cover">
      </div>
    </div>

    <!-- Galeria -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-images me-1 text-primary"></i>Galeria de Fotos
        <span class="text-muted fw-normal small">(máximo 3 fotos)</span>
      </div>
      <div class="card-body">

        <?php if ($galeria): ?>
        <div class="mb-3">
          <div class="small fw-semibold mb-2">Fotos atuais — marque para remover:</div>
          <div class="d-flex gap-3 flex-wrap">
            <?php foreach ($galeria as $i => $img): ?>
            <div class="text-center">
              <img src="<?= url('/uploads/marketplace/' . e($img)) ?>"
                   class="thumb-edit d-block mb-1" alt="Galeria <?= $i+1 ?>">
              <div class="form-check d-flex justify-content-center">
                <input class="form-check-input" type="checkbox"
                  name="remover_galeria[]" value="<?= e($img) ?>"
                  id="remGal<?= $i ?>" title="Remover esta foto">
                <label class="form-check-label ms-1 small text-danger" for="remGal<?= $i ?>">
                  Remover
                </label>
              </div>
            </div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <?php $vagasGaleria = 3 - count($galeria); ?>
        <?php if ($vagasGaleria > 0): ?>
        <label class="form-label small fw-semibold">
          Adicionar até <?= $vagasGaleria ?> foto<?= $vagasGaleria > 1 ? 's' : '' ?> nova<?= $vagasGaleria > 1 ? 's' : '' ?>
        </label>
        <input type="file" name="galeria[]" class="form-control" multiple
          accept="image/*" onchange="previewGaleriaEdit(this)">
        <div id="prevGaleriaEdit" class="d-flex gap-2 mt-2 flex-wrap"></div>
        <?php else: ?>
        <div class="alert alert-info py-2 small mb-0">
          Galeria cheia (3/3). Remova uma foto acima para adicionar outra.
        </div>
        <?php endif; ?>
      </div>
    </div>

    <div class="d-flex gap-2">
      <a href="<?= url('/marketplace/meus-anuncios') ?>" class="btn btn-outline-secondary flex-fill">
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
function previewNovaImg(input, id) {
  const img = document.getElementById(id);
  if (!input.files?.[0]) { img.classList.add('d-none'); return; }
  const r = new FileReader();
  r.onload = e => { img.src = e.target.result; img.classList.remove('d-none'); };
  r.readAsDataURL(input.files[0]);
}
function previewGaleriaEdit(input) {
  const box = document.getElementById('prevGaleriaEdit');
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
</script>
