<div class="row g-3">
<div class="col-lg-8">
<form method="POST" action="<?= url('/empresa') ?>" enctype="multipart/form-data">
  <?= csrf_field() ?>

  <!-- Logo da Empresa -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-image me-2"></i>Logo da Empresa</div>
    <div class="card-body">
      <div class="d-flex align-items-center gap-4 flex-wrap">

        <!-- Preview atual -->
        <div id="logoPreviewWrap">
          <?php if (!empty($empresa['logo'])): ?>
          <img src="<?= url('/uploads/' . e($empresa['logo'])) ?>"
               alt="Logo" id="logoPreviewImg"
               style="max-width:200px;max-height:100px;object-fit:contain;border-radius:8px;border:1px solid #dee2e6;padding:8px;background:#fff">
          <?php else: ?>
          <div id="logoPreviewImg" class="d-flex align-items-center justify-content-center bg-light rounded border"
               style="width:200px;height:100px;color:#adb5bd">
            <div class="text-center">
              <i class="bi bi-image fs-2 d-block"></i>
              <span class="small">Sem logo</span>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Upload -->
        <div class="flex-grow-1">
          <label class="form-label small fw-semibold">
            <?= !empty($empresa['logo']) ? 'Trocar logo' : 'Adicionar logo' ?>
          </label>
          <input type="file" name="logo" id="logoInput" class="form-control"
                 accept=".jpg,.jpeg,.png,.gif,.svg,.webp"
                 onchange="previewLogo(this)">
          <div class="form-text">
            JPG, PNG, SVG ou WebP • Máximo 2MB • Recomendado: 400×200px, fundo transparente
          </div>

          <?php if (!empty($empresa['logo'])): ?>
          <button type="button" class="btn btn-outline-danger btn-sm mt-2"
                  onclick="if(confirm('Remover a logo atual?')) document.getElementById('formRemoverLogo').submit()">
            <i class="bi bi-trash me-1"></i>Remover logo
          </button>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-building me-2"></i>Dados da Empresa</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label small fw-semibold">Razão Social</label>
          <input type="text" name="razao_social" class="form-control" value="<?= e($empresa['razao_social'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">CNPJ</label>
          <input type="text" name="cnpj" class="form-control" placeholder="00.000.000/0000-00" value="<?= e($empresa['cnpj'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nome Fantasia</label>
          <input type="text" name="nome_fantasia" class="form-control" value="<?= e($empresa['nome_fantasia'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">E-mail</label>
          <input type="email" name="email" class="form-control" value="<?= e($empresa['email'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($empresa['telefone'] ?? '') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">WhatsApp</label>
          <input type="text" name="whatsapp" class="form-control" placeholder="(00) 00000-0000" value="<?= e($empresa['whatsapp'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-geo-alt me-2"></i>Endereço</div>
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-2">
          <label class="form-label small fw-semibold">CEP</label>
          <input type="text" name="cep" class="form-control" placeholder="00000-000" value="<?= e($empresa['cep'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Logradouro</label>
          <input type="text" name="logradouro" class="form-control" value="<?= e($empresa['logradouro'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Número</label>
          <input type="text" name="numero" class="form-control" value="<?= e($empresa['numero'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">Complemento</label>
          <input type="text" name="complemento" class="form-control" value="<?= e($empresa['complemento'] ?? '') ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Bairro</label>
          <input type="text" name="bairro" class="form-control" value="<?= e($empresa['bairro'] ?? '') ?>">
        </div>
        <div class="col-md-5">
          <label class="form-label small fw-semibold">Cidade</label>
          <input type="text" name="cidade" class="form-control" value="<?= e($empresa['cidade'] ?? '') ?>">
        </div>
        <div class="col-md-2">
          <label class="form-label small fw-semibold">UF</label>
          <input type="text" name="uf" class="form-control" maxlength="2" value="<?= e($empresa['uf'] ?? '') ?>">
        </div>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-gear me-2"></i>Configurações do Sistema</div>
    <div class="card-body">
      <div class="row g-3">
        <input type="hidden" name="os_prefixo" value="">
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Dígitos</label>
          <select name="os_digitos" class="form-select">
            <?php foreach ([4,5,6,7,8] as $d): ?>
            <option value="<?= $d ?>" <?= ($configs['os_digitos'] ?? 6) == $d ? 'selected' : '' ?>><?= $d ?> dígitos</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Nº inicial das OS</label>
          <input type="number" name="os_numero_inicial" class="form-control" min="1"
            value="<?= e($configs['os_numero_inicial'] ?? 1) ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Garantia padrão</label>
          <div class="input-group">
            <input type="number" name="garantia_padrao_dias" class="form-control" min="0"
              value="<?= e($configs['garantia_padrao_dias'] ?? 90) ?>">
            <span class="input-group-text">dias</span>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Prazo retirada</label>
          <div class="input-group">
            <input type="number" name="prazo_retirada_dias" class="form-control" min="1"
              value="<?= e($configs['prazo_retirada_dias'] ?? 30) ?>">
            <span class="input-group-text">dias</span>
          </div>
        </div>
        <div class="col-md-3">
          <label class="form-label small fw-semibold">Comissão técnico</label>
          <div class="input-group">
            <input type="number" name="comissao_tecnico_percentual" class="form-control" min="0" max="100" step="0.5"
              value="<?= e($configs['comissao_tecnico_percentual'] ?? 0) ?>">
            <span class="input-group-text">%</span>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Textos dinâmicos -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-file-text me-2"></i>Textos do Sistema</div>
    <div class="card-body">

      <!-- Campo oculto que envia o HTML -->
      <input type="hidden" name="texto_entrada_equipamento" id="hiddenEntrada">
      <input type="hidden" name="texto_garantia"            id="hiddenGarantia">

      <!-- Entrada de Equipamento -->
      <div class="mb-4">
        <label class="form-label fw-semibold">Informações de entrada de equipamento</label>
        <p class="text-muted small">Aparece na impressão da OS de entrada (termos, condições de recebimento, etc.)</p>
        <div class="border rounded" id="editorEntrada">
          <div class="editor-toolbar border-bottom p-1 d-flex flex-wrap gap-1" data-target="entrada">
            <?= editorBtn('bold','bi-type-bold','Negrito') ?>
            <?= editorBtn('italic','bi-type-italic','Itálico') ?>
            <?= editorBtn('underline','bi-type-underline','Sublinhado') ?>
            <span class="border-start mx-1"></span>
            <?= editorBtn('insertUnorderedList','bi-list-ul','Lista') ?>
            <?= editorBtn('insertOrderedList','bi-list-ol','Lista numerada') ?>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','entrada','H3')" title="Título">
              <i class="bi bi-type-h3"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','entrada','P')" title="Parágrafo">
              <i class="bi bi-paragraph"></i>
            </button>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
              onclick="limparEditor('entrada')" title="Limpar">
              <i class="bi bi-eraser"></i>
            </button>
          </div>
          <div id="ceEntrada" contenteditable="true"
            class="p-3" style="min-height:120px;outline:none;font-size:.9rem;line-height:1.6"
            oninput="syncHidden('entrada')"><?= $configs['texto_entrada_equipamento'] ?? '' ?></div>
        </div>
      </div>

      <!-- Garantia -->
      <div class="mb-0">
        <label class="form-label fw-semibold">Informações sobre garantia</label>
        <p class="text-muted small">Aparece na impressão do comprovante de entrega/fechamento de OS.</p>
        <div class="border rounded" id="editorGarantia">
          <div class="editor-toolbar border-bottom p-1 d-flex flex-wrap gap-1" data-target="garantia">
            <?= editorBtn('bold','bi-type-bold','Negrito','garantia') ?>
            <?= editorBtn('italic','bi-type-italic','Itálico','garantia') ?>
            <?= editorBtn('underline','bi-type-underline','Sublinhado','garantia') ?>
            <span class="border-start mx-1"></span>
            <?= editorBtn('insertUnorderedList','bi-list-ul','Lista','garantia') ?>
            <?= editorBtn('insertOrderedList','bi-list-ol','Lista numerada','garantia') ?>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','garantia','H3')" title="Título">
              <i class="bi bi-type-h3"></i>
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary py-0 px-2"
              onclick="execCmd('formatBlock','garantia','P')" title="Parágrafo">
              <i class="bi bi-paragraph"></i>
            </button>
            <span class="border-start mx-1"></span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2"
              onclick="limparEditor('garantia')" title="Limpar">
              <i class="bi bi-eraser"></i>
            </button>
          </div>
          <div id="ceGarantia" contenteditable="true"
            class="p-3" style="min-height:120px;outline:none;font-size:.9rem;line-height:1.6"
            oninput="syncHidden('garantia')"><?= $configs['texto_garantia'] ?? '' ?></div>
        </div>
      </div>

    </div>
  </div>

  <div class="d-flex justify-content-end">
    <button class="btn btn-primary btn-lg" onclick="syncTodos()"><i class="bi bi-check-lg"></i> Salvar Configurações</button>
  </div>
</form>

<?php
function editorBtn(string $cmd, string $icon, string $title, string $editor = 'entrada'): string {
  return "<button type=\"button\" class=\"btn btn-sm btn-outline-secondary py-0 px-2\"
    onclick=\"execCmd('{$cmd}','{$editor}')\" title=\"{$title}\">
    <i class=\"bi {$icon}\"></i></button>";
}
?>

<style>
[contenteditable]:focus { box-shadow: 0 0 0 .2rem rgba(13,110,253,.15); }
[contenteditable] h3 { font-size:1rem; font-weight:700; margin:.4rem 0 .2rem; }
[contenteditable] ul, [contenteditable] ol { padding-left:1.4rem; margin:.3rem 0; }
[contenteditable] p { margin:.2rem 0; }
</style>
<script>
// ── Editor dinâmico ─────────────────────────────────────
function execCmd(cmd, editor, value = null) {
  const ce = document.getElementById('ce' + editor.charAt(0).toUpperCase() + editor.slice(1));
  ce.focus();
  document.execCommand(cmd, false, value);
  syncHidden(editor);
}

function syncHidden(editor) {
  const ce = document.getElementById('ce' + editor.charAt(0).toUpperCase() + editor.slice(1));
  document.getElementById('hidden' + editor.charAt(0).toUpperCase() + editor.slice(1)).value = ce.innerHTML;
}

function limparEditor(editor) {
  if (!confirm('Limpar todo o conteúdo?')) return;
  const ce = document.getElementById('ce' + editor.charAt(0).toUpperCase() + editor.slice(1));
  ce.innerHTML = '';
  syncHidden(editor);
}

function syncTodos() {
  syncHidden('entrada');
  syncHidden('garantia');
}

// Sincronizar antes de submeter o form
document.querySelector('form').addEventListener('submit', function() {
  syncTodos();
});

// ── Preview logo ────────────────────────────────────────
function previewLogo(input) {
  if (!input.files || !input.files[0]) return;
  const file = input.files[0];

  // Validar tamanho no client
  if (file.size > 2 * 1024 * 1024) {
    alert('Arquivo muito grande! Máximo 2MB.');
    input.value = '';
    return;
  }

  const reader = new FileReader();
  reader.onload = function(e) {
    const wrap = document.getElementById('logoPreviewWrap');
    wrap.innerHTML = `<img src="${e.target.result}" alt="Preview"
      style="max-width:200px;max-height:100px;object-fit:contain;border-radius:8px;border:1px solid #dee2e6;padding:8px;background:#fff">`;
  };
  reader.readAsDataURL(file);
}
</script>
</div>

<!-- Info plano -->
<div class="col-lg-4">
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Plano atual</div>
    <div class="card-body text-center py-4">
      <div class="badge bg-primary fs-6 mb-2"><?= strtoupper($empresa['plano'] ?? 'basico') ?></div>
      <?php if ($empresa['trial_ate'] ?? null): ?>
      <div class="text-muted small mt-2">
        Trial até: <strong><?= date_br($empresa['trial_ate']) ?></strong>
      </div>
      <?php $diasRestantes = (int) ceil((strtotime($empresa['trial_ate']) - time()) / 86400); ?>
      <?php if ($diasRestantes > 0): ?>
      <div class="alert alert-info py-2 mt-2 small"><?= $diasRestantes ?> dia(s) restantes</div>
      <?php else: ?>
      <div class="alert alert-danger py-2 mt-2 small">Trial expirado!</div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="card border-0 shadow-sm">
    <div class="card-header bg-white fw-semibold">Atalhos</div>
    <div class="list-group list-group-flush">
      <a href="<?= url('/usuarios') ?>" class="list-group-item list-group-item-action"><i class="bi bi-person-gear me-2"></i>Gerenciar Usuários</a>
      <a href="<?= url('/setup') ?>" class="list-group-item list-group-item-action"><i class="bi bi-sliders me-2"></i>Assistente de Configuração</a>
    </div>
  </div>
</div>
</div>

<?php if (!empty($empresa['logo'])): ?>
<form id="formRemoverLogo" method="POST" action="<?= url('/empresa/logo/remover') ?>" style="display:none">
  <?= csrf_field() ?>
</form>
<?php endif; ?>
