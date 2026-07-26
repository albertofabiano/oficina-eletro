<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<!-- Google tag (gtag.js) — rastreamento de conversão do Google Ads -->
<script async src="https://www.googletagmanager.com/gtag/js?id=AW-18351792124"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', 'AW-18351792124');
  // Conversão "Inscrição" — dispara aqui pois esta tela só aparece uma vez, logo após a conta ser criada.
  gtag('event', 'conversion', {'send_to': 'AW-18351792124/V9VyCJHDhdccEPy_6K5E', 'value': 1.0, 'currency': 'BRL'});
</script>
<title>Configuração Inicial — FixaOS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap">
<style>
*{box-sizing:border-box}
body{
  font-family:'Inter',sans-serif;
  background:#0b0d10;
  min-height:100vh;
  display:flex;
  align-items:center;
  justify-content:center;
  padding:2rem 0;
  position:relative;
  overflow-x:hidden;
}
body::before{
  content:'';
  position:fixed;inset:0;
  background:
    radial-gradient(ellipse 80% 60% at 20% 20%, rgba(37,99,235,.1) 0%, transparent 60%),
    radial-gradient(ellipse 60% 50% at 80% 80%, rgba(249,115,22,.08) 0%, transparent 60%);
  pointer-events:none;
}

.setup-wrap{width:100%;max-width:660px;margin:auto;position:relative;z-index:1;padding:0 1rem}

/* Steps */
.steps{display:flex;align-items:center;justify-content:center;gap:0;margin-bottom:2.5rem}
.step{display:flex;flex-direction:column;align-items:center;gap:.35rem;position:relative}
.step-line{flex:1;height:2px;background:rgba(255,255,255,.1);min-width:60px}
.step-line.done{background:#22c55e}
.step-circle{
  width:40px;height:40px;border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-weight:800;font-size:.88rem;
  border:2px solid rgba(255,255,255,.12);
  background:rgba(255,255,255,.04);color:rgba(255,255,255,.3);
  transition:.3s;
}
.step.done .step-circle{background:#22c55e;border-color:#22c55e;color:#fff}
.step.active .step-circle{background:#f97316;border-color:#f97316;color:#fff;box-shadow:0 0 20px rgba(249,115,22,.4)}
.step-lbl{font-size:.7rem;font-weight:600;color:rgba(255,255,255,.3);text-align:center}
.step.done .step-lbl{color:#22c55e}
.step.active .step-lbl{color:#fff}

/* Cards */
.setup-card{
  background:rgba(255,255,255,.03);
  border:1px solid rgba(255,255,255,.08);
  border-radius:20px;
  padding:1.8rem;
  margin-bottom:1.2rem;
  transition:.2s;
}
.setup-card:hover{border-color:rgba(255,255,255,.12)}
.sec-title{
  display:flex;align-items:center;gap:.6rem;
  color:#fff;font-weight:700;font-size:.95rem;
  margin-bottom:1.2rem;padding-bottom:.8rem;
  border-bottom:1px solid rgba(255,255,255,.07);
}
.sec-title .icon-wrap{
  width:34px;height:34px;border-radius:8px;
  display:flex;align-items:center;justify-content:center;
  font-size:1rem;flex-shrink:0;
}

/* Inputs */
.form-label{color:#94a3b8;font-size:.82rem;font-weight:600;margin-bottom:.4rem}
.form-control,.form-select{
  background:rgba(255,255,255,.05) !important;
  border:1px solid rgba(255,255,255,.1) !important;
  color:#fff !important;border-radius:10px;
  padding:.65rem 1rem;font-size:.9rem;
}
.form-control::placeholder{color:#4b5563 !important}
.form-control:focus,.form-select:focus{
  box-shadow:0 0 0 3px rgba(249,115,22,.25) !important;
  border-color:#f97316 !important;
  background:rgba(255,255,255,.07) !important;
}
.form-select option{background:#1a1d23;color:#fff}
.form-text{color:#4b5563 !important;font-size:.76rem;margin-top:.3rem}
.input-group-text{
  background:rgba(255,255,255,.05) !important;
  border:1px solid rgba(255,255,255,.1) !important;
  color:#6b7280 !important;border-radius:10px;
}

/* Preview OS */
.preview-os{
  background:linear-gradient(135deg,rgba(37,99,235,.12),rgba(37,99,235,.05));
  border:1px solid rgba(37,99,235,.25);
  border-radius:12px;padding:.9rem 1.2rem;
  display:flex;align-items:center;gap:1rem;margin-top:.8rem;
}
.preview-num{font-size:1.6rem;font-weight:900;color:#60a5fa;font-family:monospace;letter-spacing:.05em}

/* Botão final */
.btn-finish{
  background:linear-gradient(135deg,#f97316,#ea6c0a);
  border:none;color:#fff;
  padding:.9rem 2rem;border-radius:14px;
  font-weight:800;font-size:1rem;width:100%;
  transition:.2s;box-shadow:0 8px 24px rgba(249,115,22,.35);
  display:flex;align-items:center;justify-content:center;gap:.5rem;
}
.btn-finish:hover{transform:translateY(-2px);box-shadow:0 12px 32px rgba(249,115,22,.45)}

/* Boas-vindas box */
.welcome-box{
  background:linear-gradient(135deg,rgba(249,115,22,.1),rgba(37,99,235,.08));
  border:1px solid rgba(249,115,22,.2);
  border-radius:16px;padding:1.4rem 1.6rem;margin-bottom:1.8rem;
  display:flex;align-items:center;gap:1.2rem;
}
</style>
</head>
<body>

<div class="setup-wrap">

  <!-- Logo -->
  <div class="text-center mb-4">
    <svg width="120" height="30" viewBox="0 0 200 50" xmlns="http://www.w3.org/2000/svg"><rect width="200" height="50" fill="#1e3a5f"/><text x="100" y="37" text-anchor="middle" font-family="Arial Black,sans-serif" font-weight="900" font-size="35" textLength="180" lengthAdjust="spacingAndGlyphs" fill="#fff">Fixa<tspan fill="#f97316">OS</tspan></text></svg>
  </div>

  <!-- Steps -->
  <div class="steps">
    <div class="step done">
      <div class="step-circle"><i class="bi bi-check-lg"></i></div>
      <div class="step-lbl">Conta criada</div>
    </div>
    <div class="step-line done"></div>
    <div class="step active">
      <div class="step-circle">2</div>
      <div class="step-lbl">Configuração</div>
    </div>
    <div class="step-line"></div>
    <div class="step">
      <div class="step-circle">3</div>
      <div class="step-lbl">Pronto!</div>
    </div>
  </div>

  <!-- Boas-vindas -->
  <div class="welcome-box">
    <div style="font-size:2.2rem">🎉</div>
    <div>
      <div style="color:#fff;font-weight:800;font-size:1rem">Bem-vindo ao FixaOS!</div>
      <div style="color:#94a3b8;font-size:.85rem;margin-top:.2rem">Configure sua assistência técnica em 1 minuto. Você pode ajustar tudo depois.</div>
    </div>
  </div>

  <!-- Flash -->
  <?php $err = flash('error'); if ($err): ?>
  <div class="alert alert-danger py-2 small mb-3 rounded-3"><?= e($err) ?></div>
  <?php endif; ?>

  <form method="POST" action="<?= url('/setup') ?>" id="formSetup">
    <?= csrf_field() ?>

    <!-- Dados da empresa -->
    <div class="setup-card">
      <div class="sec-title">
        <div class="icon-wrap" style="background:rgba(59,130,246,.15)"><i class="bi bi-building-fill" style="color:#60a5fa"></i></div>
        Dados da sua assistência técnica
      </div>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Nome da assistência *</label>
          <input type="text" name="nome_fantasia" class="form-control"
            value="<?= e(\App\Core\Auth::user()['empresa_nome'] ?? '') ?>"
            placeholder="Ex: TechFix Eletrônicos">
        </div>
        <div class="col-md-6">
          <label class="form-label">Telefone</label>
          <input type="text" name="telefone" class="form-control" placeholder="(11) 00000-0000" value="<?= e($configs['telefone'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">WhatsApp</label>
          <input type="text" name="whatsapp" class="form-control" placeholder="(11) 00000-0000" value="<?= e($configs['whatsapp'] ?? '') ?>">
        </div>
      </div>
    </div>

    <!-- Numeração OS -->
    <div class="setup-card">
      <div class="sec-title">
        <div class="icon-wrap" style="background:rgba(249,115,22,.15)"><i class="bi bi-clipboard2-pulse-fill" style="color:#fb923c"></i></div>
        Numeração das Ordens de Serviço
      </div>

      <input type="hidden" name="os_prefixo" value="">
      <div class="row g-3 mb-3">
        <div class="col-md-5">
          <label class="form-label">Quantidade de dígitos</label>
          <select name="os_digitos" id="osDigitos" class="form-select">
            <?php foreach([4,5,6,7,8] as $d): ?>
            <option value="<?= $d ?>" <?= ($configs['os_digitos']??6)==$d?'selected':'' ?>><?= $d ?> dígitos</option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Zeros à esquerda no número</div>
        </div>
        <div class="col-md-7">
          <label class="form-label">Iniciar a partir do número</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-hash"></i></span>
            <input type="number" name="os_numero_inicial" id="osNumeroInicial" class="form-control"
              min="1" max="999999" value="<?= e($configs['os_numero_inicial']??1) ?>" placeholder="1">
          </div>
          <div class="form-text">Se já usava OS em papel, comece do próximo número</div>
        </div>
      </div>

      <!-- Preview -->
      <div class="preview-os">
        <div style="background:rgba(96,165,250,.1);border-radius:8px;padding:.5rem .8rem">
          <i class="bi bi-eye-fill" style="color:#60a5fa;font-size:1.2rem"></i>
        </div>
        <div style="flex:1">
          <div style="color:#4b5563;font-size:.72rem;margin-bottom:.1rem">Sua primeira OS será:</div>
          <div class="preview-num" id="previewNum">000001</div>
        </div>
        <div style="text-align:right">
          <div style="color:#4b5563;font-size:.7rem">Sequência</div>
          <div style="color:#374151;font-size:.78rem;font-family:monospace;margin-top:.1rem" id="previewSeq">000001 → 000002</div>
        </div>
      </div>
    </div>

    <!-- Config gerais -->
    <div class="setup-card">
      <div class="sec-title">
        <div class="icon-wrap" style="background:rgba(34,197,94,.12)"><i class="bi bi-gear-fill" style="color:#4ade80"></i></div>
        Configurações gerais
      </div>
      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Garantia padrão (dias)</label>
          <div class="input-group">
            <input type="number" name="garantia_padrao_dias" class="form-control"
              value="<?= e($configs['garantia_padrao_dias']??90) ?>" min="0" max="730">
            <span class="input-group-text">dias</span>
          </div>
          <div class="form-text">Aplicado automaticamente ao concluir cada OS</div>
        </div>
        <div class="col-md-6">
          <label class="form-label">Prazo para retirada (dias)</label>
          <div class="input-group">
            <input type="number" name="prazo_retirada_dias" class="form-control"
              value="<?= e($configs['prazo_retirada_dias']??30) ?>" min="1" max="180">
            <span class="input-group-text">dias</span>
          </div>
          <div class="form-text">Após esse prazo o aparelho pode ser cobrado</div>
        </div>
      </div>
    </div>

    <!-- O que você vai ter -->
    <div style="background:rgba(255,255,255,.02);border:1px solid rgba(255,255,255,.06);border-radius:16px;padding:1.2rem 1.4rem;margin-bottom:1.4rem">
      <div style="color:#6b7280;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.07em;margin-bottom:.8rem">O que você ganha ao entrar</div>
      <div class="row g-2">
        <?php foreach([
          ['bi-clipboard2-pulse','#60a5fa','OS ilimitadas'],
          ['bi-people-fill','#4ade80','CRM de clientes'],
          ['bi-graph-up-arrow','#c084fc','Financeiro completo'],
          ['bi-geo-alt-fill','#fb923c','Página pública no Google'],
          ['bi-shop','#fbbf24','Marketplace de peças'],
          ['bi-star-fill','#f472b6','Sistema de avaliações'],
        ] as[$ic,$c,$t]):?>
        <div class="col-6 col-md-4">
          <div style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;color:#94a3b8">
            <i class="bi <?=$ic?>" style="color:<?=$c?>;font-size:.9rem"></i><?=$t?>
          </div>
        </div>
        <?php endforeach;?>
      </div>
    </div>

    <!-- Botão -->
    <button type="submit" class="btn-finish" id="btnSalvar">
      <i class="bi bi-rocket-takeoff-fill fs-5"></i>
      Concluir e entrar no sistema
    </button>

    <p class="text-center mt-3" style="color:#374151;font-size:.78rem">
      <i class="bi bi-info-circle me-1"></i>
      Você pode alterar essas configurações depois em <strong style="color:#64748b">Empresa → Configurações</strong>
    </p>
  </form>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/imask@7.6.1/dist/imask.min.js"></script>
<script src="<?= url('/js/masks.js') ?>?v=<?= filemtime(BASE_PATH.'/public/js/masks.js') ?>"></script>
<script>
function pad(n, len) { return String(n).padStart(len, '0'); }

function atualizarPreview() {
  const d = parseInt(document.getElementById('osDigitos').value) || 6;
  const i = parseInt(document.getElementById('osNumeroInicial').value) || 1;
  document.getElementById('previewNum').textContent = pad(i, d);
  document.getElementById('previewSeq').textContent = pad(i,d) + ' → ' + pad(i+1,d);
}

['osDigitos','osNumeroInicial'].forEach(id => {
  document.getElementById(id)?.addEventListener('input', atualizarPreview);
});
atualizarPreview();

document.getElementById('formSetup')?.addEventListener('submit', function() {
  const btn = document.getElementById('btnSalvar');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Configurando...';
});
</script>
</body>
</html>
