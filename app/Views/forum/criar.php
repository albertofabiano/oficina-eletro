<?php require __DIR__ . '/menu.php'; ?>

<div class="row justify-content-center">
<div class="col-lg-8">

  <nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb small">
      <li class="breadcrumb-item"><a href="<?= url('/forum') ?>">Fórum</a></li>
      <li class="breadcrumb-item active">Novo Tópico</li>
    </ol>
  </nav>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">
      <i class="bi bi-plus-circle me-2 text-primary"></i>Criar Novo Tópico
    </div>
    <div class="card-body">
      <form method="POST" action="<?= url('/forum') ?>">
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label fw-semibold">Categoria *</label>
          <select name="forum_categoria_id" class="form-select" required>
            <option value="">— Selecione a categoria —</option>
            <?php foreach ($categorias as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $cat_id == $c['id'] ? 'selected' : '' ?>>
              <?= e($c['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Título *</label>
          <input type="text" name="titulo" class="form-control" required maxlength="200"
            placeholder="Descreva o problema ou assunto de forma clara...">
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <label class="form-label fw-semibold">Marca</label>
            <input type="text" name="marca" class="form-control" maxlength="80"
              placeholder="Ex: Samsung, LG...">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Modelo</label>
            <input type="text" name="modelo" class="form-control" maxlength="100"
              placeholder="Ex: UN55MU7100...">
          </div>
          <div class="col-md-4" id="campoVersao" style="display:none">
            <label class="form-label fw-semibold">Versão do Firmware</label>
            <input type="text" name="versao_firmware" class="form-control" maxlength="60"
              placeholder="Ex: T-MST12DEUC-1252.3">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição / Conteúdo *</label>
          <textarea name="conteudo" class="form-control" rows="8" required
            placeholder="Descreva detalhadamente o problema, solução, dica ou informação..."></textarea>
          <div class="form-text">Seja detalhado — quanto mais informações, mais útil para outros técnicos.</div>
        </div>

        <!-- URL externa (apenas para firmware) -->
        <div class="mb-4" id="campoUrl" style="display:none">
          <label class="form-label fw-semibold">
            <i class="bi bi-link-45deg me-1 text-primary"></i>Link para download
            <span class="text-muted fw-normal">(Google Drive, Mega, Dropbox, GitHub...)</span>
          </label>
          <input type="url" name="url_externa" class="form-control"
            placeholder="https://drive.google.com/...">
          <div class="form-text">
            <i class="bi bi-info-circle me-1"></i>
            Faça o upload do arquivo em um serviço externo e cole o link aqui. Isso evita sobrecarga no servidor.
          </div>
        </div>

        <div class="d-flex gap-2">
          <a href="<?= url('/forum') ?>" class="btn btn-outline-secondary flex-fill">Cancelar</a>
          <button type="submit" class="btn btn-primary fw-semibold flex-fill">
            <i class="bi bi-send me-1"></i>Publicar Tópico
          </button>
        </div>
      </form>
    </div>
  </div>

</div>
</div>

<script>
// Mostrar campos extras para categoria Firmware (id=2)
const catSelect = document.querySelector('[name=forum_categoria_id]');
function toggleCamposFirmware() {
  const isFirmware = catSelect.value == '2';
  document.getElementById('campoVersao').style.display = isFirmware ? 'block' : 'none';
  document.getElementById('campoUrl').style.display    = isFirmware ? 'block' : 'none';
}
catSelect.addEventListener('change', toggleCamposFirmware);
toggleCamposFirmware();
</script>
