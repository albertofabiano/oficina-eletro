<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Configuração Inicial — OficinaTech</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
body { background: linear-gradient(135deg,#0f1117 0%,#0a1628 100%); min-height: 100vh; }

.step-bar { display: flex; gap: 0; margin-bottom: 2.5rem; }
.step-item {
  flex: 1; text-align: center; position: relative;
  padding-bottom: .5rem;
}
.step-item::after {
  content: ''; position: absolute; top: 18px; left: 50%; right: -50%;
  height: 2px; background: rgba(255,255,255,.15); z-index: 0;
}
.step-item:last-child::after { display: none; }
.step-circle {
  width: 36px; height: 36px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto .4rem; font-size: .85rem; font-weight: 700;
  position: relative; z-index: 1;
  border: 2px solid rgba(255,255,255,.15);
  background: rgba(255,255,255,.05); color: rgba(255,255,255,.4);
  transition: all .3s;
}
.step-item.active .step-circle   { background: #0d6efd; border-color: #0d6efd; color: #fff; }
.step-item.done .step-circle     { background: #198754; border-color: #198754; color: #fff; }
.step-label { font-size: .72rem; color: rgba(255,255,255,.4); }
.step-item.active .step-label   { color: #fff; }
.step-item.done .step-label     { color: #198754; }

.setup-card {
  background: rgba(255,255,255,.04);
  border: 1px solid rgba(255,255,255,.08);
  border-radius: 20px; padding: 2rem;
  backdrop-filter: blur(10px);
}
.section-card {
  background: rgba(255,255,255,.03);
  border: 1px solid rgba(255,255,255,.07);
  border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem;
}
.section-title { color: rgba(255,255,255,.9); font-weight: 600; margin-bottom: 1rem; font-size: .95rem; }
.form-label { color: rgba(255,255,255,.7); font-size: .85rem; }
.form-control, .form-select {
  background: rgba(255,255,255,.06) !important;
  border: 1px solid rgba(255,255,255,.12) !important;
  color: #fff !important; border-radius: 8px;
}
.form-control::placeholder { color: rgba(255,255,255,.3); }
.form-control:focus, .form-select:focus {
  box-shadow: 0 0 0 3px rgba(13,110,253,.3) !important;
  border-color: #0d6efd !important;
}
.form-select option { background: #1a1d23; color: #fff; }
.form-text { color: rgba(255,255,255,.4) !important; font-size: .78rem; }

.preview-os {
  background: rgba(13,110,253,.1);
  border: 1px solid rgba(13,110,253,.3);
  border-radius: 10px; padding: .75rem 1.2rem;
  display: flex; align-items: center; gap: .75rem;
}
.preview-num {
  font-size: 1.4rem; font-weight: 800; color: #74b0ff;
  font-family: monospace; letter-spacing: .05em;
}
.input-group-text { background: rgba(255,255,255,.06) !important; border-color: rgba(255,255,255,.12) !important; color: rgba(255,255,255,.6) !important; }

.btn-next { background: #0d6efd; border: none; color: #fff; padding: .8rem 2.5rem; border-radius: 10px; font-weight: 600; font-size: 1rem; }
.btn-next:hover { background: #0a58ca; color: #fff; }
</style>
</head>
<body class="d-flex align-items-center justify-content-center py-5">
<div class="container" style="max-width: 680px">

  <!-- Logo -->
  <div class="text-center mb-4">
    <div class="d-inline-flex align-items-center gap-2 mb-2">
      <div class="bg-primary rounded-2 d-flex align-items-center justify-content-center" style="width:36px;height:36px">
        <i class="bi bi-cpu-fill text-white"></i>
      </div>
      <span class="text-white fw-bold fs-5">OficinaTech</span>
    </div>
    <div class="text-white fw-bold fs-4">Configuração inicial</div>
    <div class="text-muted small">Leva menos de 1 minuto. Você pode alterar depois.</div>
  </div>

  <!-- Steps -->
  <div class="step-bar mb-4">
    <div class="step-item done">
      <div class="step-circle"><i class="bi bi-check-lg"></i></div>
      <div class="step-label">Conta criada</div>
    </div>
    <div class="step-item active">
      <div class="step-circle">2</div>
      <div class="step-label">Configuração</div>
    </div>
    <div class="step-item">
      <div class="step-circle">3</div>
      <div class="step-label">Pronto!</div>
    </div>
  </div>

  <!-- Flash -->
  <?php $err = flash('error'); if ($err): ?>
  <div class="alert alert-danger py-2 small mb-3"><?= e($err) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= url('/setup') ?>" id="formSetup">
    <?= csrf_field() ?>

    <!-- Dados da empresa -->
    <div class="section-card">
      <div class="section-title"><i class="bi bi-building me-2 text-primary"></i>Dados da assistência</div>
      <div class="row g-3">
        <div class="col-md-12">
          <label class="form-label fw-semibold">Nome da assistência técnica</label>
          <input type="text" name="nome_fantasia" class="form-control"
            value="<?= e(\App\Core\Auth::user()['empresa_nome'] ?? '') ?>"
            placeholder="Ex: TechFix Eletrônicos">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Telefone</label>
          <input type="text" name="telefone" class="form-control" placeholder="(00) 00000-0000" value="<?= e($configs['telefone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">WhatsApp</label>
          <input type="text" name="whatsapp" class="form-control" placeholder="(00) 00000-0000" value="<?= e($configs['whatsapp'] ?? '') ?>">
        </div>
      </div>
    </div>

    <!-- Numeração das OS -->
    <div class="section-card">
      <div class="section-title"><i class="bi bi-clipboard2-pulse me-2 text-primary"></i>Numeração das Ordens de Serviço</div>

      <input type="hidden" name="os_prefixo" value="">
      <div class="row g-3 mb-3">
        <div class="col-md-4">
          <label class="form-label fw-semibold">Dígitos</label>
          <select name="os_digitos" id="osDigitos" class="form-select">
            <?php foreach ([4,5,6,7,8] as $d): ?>
            <option value="<?= $d ?>" <?= ($configs['os_digitos'] ?? 6) == $d ? 'selected' : '' ?>><?= $d ?> dígitos</option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Zeros à esquerda</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">A partir de qual número?</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-hash"></i></span>
            <input type="number" name="os_numero_inicial" id="osNumeroInicial" class="col-md-8"
              class="form-control" min="1" max="999999"
              value="<?= e($configs['os_numero_inicial'] ?? 1) ?>"
              placeholder="1">
          </div>
          <div class="form-text">Se já tinha OS em papel, comece do número seguinte</div>
        </div>
      </div>

      <!-- Preview ao vivo -->
      <div class="preview-os" id="previewOS">
        <i class="bi bi-eye text-primary fs-5"></i>
        <div>
          <div style="color:rgba(255,255,255,.5);font-size:.75rem">Sua primeira OS vai ser:</div>
          <div class="preview-num" id="previewNum">000001</div>
        </div>
        <div class="ms-auto text-end">
          <div style="color:rgba(255,255,255,.5);font-size:.75rem">Exemplo de sequência</div>
          <div style="color:rgba(255,255,255,.4);font-size:.78rem;font-family:monospace" id="previewSeq">
            000001 → 000002 → 000003
          </div>
        </div>
      </div>
    </div>

    <!-- Configurações gerais -->
    <div class="section-card">
      <div class="section-title"><i class="bi bi-gear me-2 text-primary"></i>Configurações gerais</div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-semibold">Garantia padrão (dias)</label>
          <div class="input-group">
            <input type="number" name="garantia_padrao_dias" class="form-control"
              value="<?= e($configs['garantia_padrao_dias'] ?? 90) ?>" min="0" max="730">
            <span class="input-group-text">dias</span>
          </div>
          <div class="form-text">Aplicado automaticamente ao concluir cada OS</div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-semibold">Prazo para retirada (dias)</label>
          <div class="input-group">
            <input type="number" name="prazo_retirada_dias" class="form-control"
              value="<?= e($configs['prazo_retirada_dias'] ?? 30) ?>" min="1" max="180">
            <span class="input-group-text">dias</span>
          </div>
          <div class="form-text">Após esse prazo o aparelho pode ser cobrado por armazenagem</div>
        </div>
      </div>
    </div>

    <button type="submit" class="btn-next w-100 mt-1" id="btnSalvar">
      <i class="bi bi-rocket-takeoff me-2"></i>Concluir configuração e entrar no sistema
    </button>
    <p class="text-center mt-3" style="color:rgba(255,255,255,.3);font-size:.8rem">
      Você pode alterar tudo isso depois em <strong>Empresa → Configurações</strong>
    </p>
  </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>"></script>
<script>
function padStart(n, len) { return String(n).padStart(len, '0'); }

function atualizarPreview() {
  const digitos = parseInt(document.getElementById('osDigitos').value) || 6;
  const inicio  = parseInt(document.getElementById('osNumeroInicial').value) || 1;

  const num1 = padStart(inicio, digitos);
  const num2 = padStart(inicio + 1, digitos);
  const num3 = padStart(inicio + 2, digitos);

  document.getElementById('previewNum').textContent = num1;
  document.getElementById('previewSeq').textContent = `${num1} → ${num2} → ${num3}`;
}

['osDigitos','osNumeroInicial'].forEach(id => {
  document.getElementById(id).addEventListener('input', atualizarPreview);
});

atualizarPreview();

document.getElementById('formSetup').addEventListener('submit', function() {
  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
});
</script>
</body>
</html>
