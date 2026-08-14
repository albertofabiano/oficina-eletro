<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Recibo de Adiantamento — OS <?= e($os['numero'] ?? '') ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 12.5px; color: #000; background:#fff; }

.header {
  display:flex; justify-content:space-between; align-items:flex-start;
  border-bottom: 2px solid #1a1d23; padding-bottom:10px; margin-bottom:14px; gap:16px;
}
.logo-empresa    { max-width:160px; max-height:65px; object-fit:contain; }
.logo-placeholder { width:52px;height:52px;border-radius:8px;background:#1a1d23;color:#fff;
                    display:flex;align-items:center;justify-content:center;font-size:25.5px;font-weight:bold;flex-shrink:0; }
.empresa-nome    { font-size:17px;font-weight:bold;color:#1a1d23; }
.empresa-detalhe { font-size:11.5px;color:#555;line-height:1.5; }
.header-dir      { text-align:right;flex-shrink:0; }
.os-numero       { font-size:23px;font-weight:900;border:2px solid #1a1d23;padding:3px 10px;display:inline-block;margin-bottom:3px;color:#1a1d23; }
.doc-info        { font-size:11.5px;color:#555;line-height:1.7; }

.doc-titulo {
  text-align:center;font-size:16px;font-weight:900;text-transform:uppercase;letter-spacing:.06em;
  background:#1a1d23;color:#fff;padding:8px;margin-bottom:14px;
}

.section-title {
  font-weight:bold;font-size:11.5px;padding:3px 8px;margin:10px 0 5px;
  text-transform:uppercase;letter-spacing:.06em;
  background:#1a1d23;color:#fff;
}
.info-box    { border:2px solid #ccc;border-radius:4px;padding:7px 10px;margin-bottom:7px;font-size:12px;line-height:1.65; }
.info-label  { font-size:10.5px;color:#777;text-transform:uppercase;letter-spacing:.05em;font-weight:bold; }
.row         { display:flex;gap:10px; }
.col         { flex:1; }

.valor-box {
  border:2px solid #198754;background:#f0faf4;border-radius:4px;padding:16px;margin:12px 0;
  text-align:center;
}
.valor-label { font-size:11.5px;color:#0f5132;text-transform:uppercase;letter-spacing:.06em;font-weight:bold; }
.valor-num   { font-size:32px;font-weight:900;color:#0f5132;margin-top:4px; }
.valor-forma { font-size:12.5px;color:#0f5132;margin-top:4px; }

.declaracao {
  border:2px solid #ccc;border-radius:4px;padding:10px 12px;font-size:12px;color:#333;
  line-height:1.75;margin:12px 0;
}

.assinaturas      { display:flex;justify-content:space-around;margin-top:44px; }
.assinatura-linha { border-top:2px solid #000;width:220px;padding-top:4px;font-size:11.5px;text-align:center;margin:0 auto; }

.rodape { margin-top:18px;font-size:10.5px;color:#888;text-align:center; }

@media print {
  .no-print { display:none!important; }
  body { margin:0; padding:10mm; }
  @page { margin:0; size:A4; }
}
</style>
</head>
<body>

<div class="no-print" style="background:#f0f2f5;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dee2e6;flex-wrap:wrap">
  <button onclick="window.print()" style="background:#1a1d23;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <button type="button" id="btnWaAdiant" onclick="enviarAdiantWa(this)"
    style="background:#25d366;color:#fff;border:none;padding:7px 18px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600;display:inline-flex;align-items:center;gap:6px">
    📲 Enviar por WhatsApp
  </button>
  <span id="waAdiantMsg" style="font-size:13px;font-weight:600"></span>
  <a href="<?= url('/os/' . $os['id']) ?>" style="color:#555;text-decoration:none;font-size:14px">← Voltar para a OS</a>
  <span style="margin-left:auto;font-size:12.5px;color:#888">Recibo de Adiantamento — OS <?= e($os['numero']) ?></span>
</div>

<div style="max-width:820px;margin:0 auto;padding:16px 14px">

  <!-- CABEÇALHO -->
  <div class="header">
    <div style="display:flex;align-items:center;gap:14px;flex:1">
      <?php if (!empty($os['emp_logo'])): ?>
        <img src="<?= url('/uploads/' . e($os['emp_logo'])) ?>" alt="Logo" class="logo-empresa">
      <?php else: ?>
        <div class="logo-placeholder"><?= mb_strtoupper(mb_substr($os['empresa_nome'] ?? 'O', 0, 1)) ?></div>
      <?php endif; ?>
      <div>
        <div class="empresa-nome"><?= e($os['empresa_nome']) ?></div>
        <?php if (!empty($os['empresa_cnpj'])): ?>
        <div class="empresa-detalhe">CNPJ: <?= e($os['empresa_cnpj']) ?></div>
        <?php endif; ?>
        <?php
          $end = array_filter([$os['emp_logradouro'] ?? '', ($os['emp_numero'] ?? '') ? 'nº ' . $os['emp_numero'] : '']);
          $cuf = trim(($os['emp_cidade'] ?? '') . (isset($os['emp_uf']) ? '/' . $os['emp_uf'] : ''));
        ?>
        <?php if ($end): ?><div class="empresa-detalhe"><?= e(implode(', ', $end)) ?><?= $cuf ? ' — ' . $cuf : '' ?></div><?php endif; ?>
        <?php if (!empty($os['emp_tel'])): ?>
        <div class="empresa-detalhe">Tel: <?= e($os['emp_tel']) ?><?php if (!empty($os['emp_whatsapp'])): ?> | WhatsApp: <?= e($os['emp_whatsapp']) ?><?php endif; ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="header-dir">
      <div class="os-numero">OS <?= e($os['numero']) ?></div>
      <div class="doc-info">
        <div><strong>Emitido em:</strong> <?= date_br(date('Y-m-d H:i:s'), true) ?></div>
      </div>
    </div>
  </div>

  <div class="doc-titulo">Recibo de Adiantamento</div>

  <!-- CLIENTE + EQUIPAMENTO -->
  <div class="row">
    <div class="col">
      <div class="section-title">Cliente</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:14px"><?= e($os['cliente_nome']) ?></div>
        <?php if ($os['cpf_cnpj'] ?? null): ?><div><span class="info-label">CPF/CNPJ:</span> <?= e(doc_mask($os['cpf_cnpj'])) ?></div><?php endif; ?>
        <?php if ($os['cliente_tel'] ?? null): ?><div><span class="info-label">Tel:</span> <?= e($os['cliente_tel']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="col">
      <div class="section-title">Equipamento</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:14px"><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></div>
        <div><?= e($os['equip_tipo'] ?? '') ?></div>
        <?php if ($os['numero_serie'] ?? null): ?><div><span class="info-label">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- VALOR RECEBIDO -->
  <?php
    $formasLabel = ['dinheiro' => 'Dinheiro', 'pix' => 'PIX', 'cartao_credito' => 'Cartão de crédito', 'cartao_debito' => 'Cartão de débito', 'transferencia' => 'Transferência', 'boleto' => 'Boleto'];
    $formaTxt = $formasLabel[$adiantamento['forma_pagamento']] ?? $adiantamento['forma_pagamento'];
    if ($adiantamento['forma_pagamento'] === 'cartao_credito' && (int) $adiantamento['parcelas'] > 1) {
        $formaTxt .= ' em ' . (int) $adiantamento['parcelas'] . 'x';
    }
  ?>
  <div class="valor-box">
    <div class="valor-label">Valor recebido como adiantamento</div>
    <div class="valor-num">R$ <?= number_format($adiantamento['valor_cobrado'], 2, ',', '.') ?></div>
    <div class="valor-forma">Pago via <?= e($formaTxt) ?> em <?= date_br($adiantamento['criado_em'], true) ?></div>
  </div>

  <!-- DECLARAÇÃO -->
  <div class="declaracao">
    Declaramos, para os devidos fins, que recebemos de <strong><?= e($os['cliente_nome']) ?></strong> a quantia de
    <strong>R$ <?= number_format($adiantamento['valor_cobrado'], 2, ',', '.') ?></strong>, a título de
    <strong>adiantamento/sinal</strong> referente ao serviço registrado na Ordem de Serviço nº
    <strong><?= e($os['numero']) ?></strong> (<?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? '')) ?: ($os['equip_tipo'] ?? 'equipamento')) ?>).
    Este valor será abatido do total da Ordem de Serviço no momento do fechamento.
  </div>

  <!-- ASSINATURAS -->
  <div class="assinaturas">
    <div><div class="assinatura-linha"><?= e($os['cliente_nome']) ?></div><div style="font-size:10.5px;text-align:center;margin-top:2px;color:#555">Cliente</div></div>
    <div><div class="assinatura-linha"><?= e($os['empresa_nome']) ?></div><div style="font-size:10.5px;text-align:center;margin-top:2px;color:#555">Responsável pelo recebimento</div></div>
  </div>

  <div class="rodape">Este recibo comprova apenas o valor adiantado — não substitui o comprovante de fechamento da Ordem de Serviço.</div>

</div>

<script>
function enviarAdiantWa(btn){
  var orig = btn.innerHTML, msg = document.getElementById('waAdiantMsg');
  btn.disabled = true; btn.style.opacity = .6; btn.innerHTML = 'Enviando…'; msg.textContent = '';
  fetch('<?= url('/os/' . $os['id'] . '/adiantamentos/' . $adiantamento['id'] . '/whatsapp') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
  })
    .then(function (r) { return r.json(); })
    .then(function (j) {
      if (!j.success) throw new Error(j.error || 'Falha no envio.');
      btn.innerHTML = '✓ Enviado'; btn.style.background = '#198754'; btn.style.opacity = 1;
      msg.style.color = '#198754'; msg.textContent = 'Recibo enviado no WhatsApp do cliente.';
    })
    .catch(function (e) {
      btn.disabled = false; btn.style.opacity = 1; btn.innerHTML = orig;
      msg.style.color = '#dc3545'; msg.textContent = e.message;
    });
}
</script>
</body>
</html>
