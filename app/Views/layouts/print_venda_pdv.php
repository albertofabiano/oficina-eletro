<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante de Venda #<?= (int) $venda['id'] ?> — <?= e($empresa['nome_fantasia'] ?? '') ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 12.5px; color: #000; background:#fff; }
.no-print { background:#f0f2f5;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dee2e6;flex-wrap:wrap }
.header { display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1a1d23;padding-bottom:10px;margin-bottom:12px }
.empresa-nome { font-size:17px;font-weight:bold;color:#1a1d23 }
.empresa-detalhe { font-size:11.5px;color:#555 }
.venda-numero { font-size:22px;font-weight:900;border:2px solid #1a1d23;padding:4px 12px;display:inline-block;color:#1a1d23 }
.section-title { font-weight:bold;font-size:11.5px;background:#1a1d23;color:#fff;padding:3px 8px;margin:8px 0 4px;text-transform:uppercase;letter-spacing:.06em }
.info-box { border:1px solid #ccc;border-radius:4px;padding:6px 10px;margin-bottom:6px;font-size:12px;line-height:1.6 }
.info-label { font-size:10.5px;color:#777;text-transform:uppercase;letter-spacing:.05em;font-weight:bold }
table { width:100%;border-collapse:collapse;margin-bottom:8px }
th,td { border:1px solid #ccc;padding:5px 8px;text-align:left;vertical-align:top }
th { background:#f0f0f0;font-weight:bold;font-size:11.5px }
.total-row { background:#1a1d23;color:#fff;font-weight:bold;font-size:15px }
.desconto-row td { color:#c00;font-weight:bold }
.pagamento-box { border:1px solid #ccc;border-radius:4px;padding:8px 12px;margin-top:6px;font-size:12.5px;display:flex;justify-content:space-between;flex-wrap:wrap;gap:6px 24px }
.badge-pg { background:#1a1d23;color:#fff;border-radius:4px;padding:2px 10px;font-size:11.5px;font-weight:bold }
.rodape { text-align:center;font-size:11px;color:#777;margin-top:24px;border-top:1px solid #ccc;padding-top:8px }
@media print { .no-print { display:none!important } body { margin:0;padding:10mm } @page { margin:0;size:A4 } }
</style>
</head>
<body>

<?php
$formasLabel = ['dinheiro'=>'Dinheiro','pix'=>'PIX','cartao_credito'=>'Cartão de crédito','cartao_debito'=>'Cartão de débito','misto'=>'Misto','outro'=>'Outro'];
// Label de cada linha de pagamento, com "(Nx)" quando for cartão de crédito parcelado — sem
// pdv_venda_pagamentos (venda antiga, antes dessa tabela existir), cai no badge único de
// sempre a partir de venda.forma_pagamento, sem parcelas (não tem como saber quantas foram).
$labelPagamento = function (array $pg) use ($formasLabel) {
  $l = $formasLabel[$pg['forma_pagamento']] ?? $pg['forma_pagamento'];
  if ($pg['forma_pagamento'] === 'cartao_credito' && (int) $pg['parcelas'] > 1) $l .= ' (' . (int) $pg['parcelas'] . 'x)';
  return $l;
};
?>

<div class="no-print">
  <button onclick="window.print()" style="background:#0d6efd;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <button type="button" id="btnEnviarWa" onclick="enviarVendaWa(this)"
    style="background:#25d366;color:#fff;border:none;padding:7px 18px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600;display:inline-flex;align-items:center;gap:6px">
    📲 Enviar por WhatsApp
  </button>
  <span id="waMsg" style="font-size:13px;font-weight:600"></span>
  <a href="<?= url('/pdv/comprovante/' . $venda['id']) ?>" style="background:#e2e8f0;color:#1a1d23;text-decoration:none;padding:7px 18px;border-radius:6px;font-size:15px;font-weight:600;display:flex;align-items:center;gap:6px">← Voltar</a>
  <span style="margin-left:auto;font-size:12.5px;color:#888">Comprovante de venda #<?= (int) $venda['id'] ?></span>
</div>

<div style="max-width:820px;margin:0 auto;padding:16px 14px">

  <div class="header">
    <div>
      <?php if (!empty($empresa['logo'])): ?>
      <img src="<?= url('/uploads/' . e($empresa['logo'])) ?>" style="max-width:160px;max-height:60px;object-fit:contain;margin-bottom:6px;display:block">
      <?php endif; ?>
      <div class="empresa-nome"><?= e($empresa['nome_fantasia'] ?? '') ?></div>
      <?php if (!empty($empresa['cnpj'])): ?><div class="empresa-detalhe">CNPJ: <?= e($empresa['cnpj']) ?></div><?php endif; ?>
      <?php
        $endEmp = array_filter([
          $empresa['logradouro'] ?? '', ($empresa['numero'] ?? '') ? 'nº ' . $empresa['numero'] : '',
          $empresa['bairro'] ?? '', trim(($empresa['cidade'] ?? '') . (($empresa['uf'] ?? '') ? '/' . $empresa['uf'] : '')),
        ]);
      ?>
      <?php if ($endEmp): ?><div class="empresa-detalhe"><?= e(implode(', ', $endEmp)) ?></div><?php endif; ?>
      <?php if (!empty($empresa['telefone'])): ?><div class="empresa-detalhe">Tel: <?= e($empresa['telefone']) ?><?php if (!empty($empresa['whatsapp'])): ?> | WhatsApp: <?= e($empresa['whatsapp']) ?><?php endif; ?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
      <div class="venda-numero">Venda #<?= (int) $venda['id'] ?></div>
      <div style="font-size:11.5px;color:#444;margin-top:4px"><?= date_br($venda['criado_em'], true) ?></div>
    </div>
  </div>

  <div class="section-title">Cliente</div>
  <div class="info-box">
    <div style="font-weight:bold;font-size:14px"><?= e($venda['cliente_nome'] ?? 'Consumidor') ?></div>
    <?php if (!empty($venda['cliente_telefone'])): ?><div><span class="info-label">Tel:</span> <?= e($venda['cliente_telefone']) ?></div><?php endif; ?>
    <?php if (!empty($venda['cliente_whatsapp'])): ?><div><span class="info-label">WhatsApp:</span> <?= e($venda['cliente_whatsapp']) ?></div><?php endif; ?>
    <?php if (!empty($venda['vendedor'])): ?><div><span class="info-label">Atendente:</span> <?= e($venda['vendedor']) ?></div><?php endif; ?>
  </div>

  <div class="section-title">Itens</div>
  <table>
    <thead><tr><th style="width:55%">Descrição</th><th style="width:15%;text-align:center">Qtd</th><th style="width:15%;text-align:right">Valor Unit.</th><th style="width:15%;text-align:right">Total</th></tr></thead>
    <tbody>
      <?php foreach ($itens as $it): ?>
      <tr>
        <td><?= e($it['descricao']) ?></td>
        <td style="text-align:center"><?= $it['quantidade'] + 0 ?></td>
        <td style="text-align:right"><?= money($it['valor_unitario']) ?></td>
        <td style="text-align:right;font-weight:bold"><?= money($it['valor_total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>

  <table>
    <?php if (($venda['desconto'] ?? 0) > 0): ?>
    <tr><td colspan="3" style="text-align:right;border:none">Subtotal</td><td style="text-align:right;border:none"><?= money($venda['subtotal']) ?></td></tr>
    <tr class="desconto-row"><td colspan="3" style="text-align:right;border:none">Desconto</td><td style="text-align:right;border:none">- <?= money($venda['desconto']) ?></td></tr>
    <?php endif; ?>
    <tr class="total-row">
      <td colspan="3" style="text-align:right;font-size:14px">TOTAL</td>
      <td style="text-align:right;font-size:17px;width:20%"><?= money($venda['total']) ?></td>
    </tr>
  </table>

  <div class="pagamento-box">
    <?php if (count($pagamentos) > 1): ?>
    <div><span class="info-label">Forma de pagamento</span> <span class="badge-pg">Misto</span></div>
    <?php foreach ($pagamentos as $pg): ?>
    <div style="width:100%;font-size:11.5px;color:#555"><?= e($labelPagamento($pg)) ?>: <?= money($pg['valor']) ?></div>
    <?php endforeach; ?>
    <?php else: ?>
    <div><span class="info-label">Forma de pagamento</span> <span class="badge-pg"><?= e($pagamentos ? $labelPagamento($pagamentos[0]) : ($formasLabel[$venda['forma_pagamento']] ?? $venda['forma_pagamento'])) ?></span></div>
    <?php endif; ?>
    <?php if ($venda['forma_pagamento'] === 'dinheiro' && ($venda['valor_recebido'] ?? 0) > 0): ?>
    <div><span class="info-label">Recebido</span> <?= money($venda['valor_recebido']) ?></div>
    <div><span class="info-label">Troco</span> <?= money($venda['troco'] ?? 0) ?></div>
    <?php endif; ?>
  </div>

  <?php if (!empty($venda['observacoes'])): ?>
  <div class="section-title">Observações</div>
  <div class="info-box"><?= nl2br(e($venda['observacoes'])) ?></div>
  <?php endif; ?>

  <div class="rodape">Obrigado pela preferência! — Documento sem valor fiscal.</div>

</div>

<script>
async function enviarVendaWa(btn) {
  const orig = btn.innerHTML, msg = document.getElementById('waMsg');
  btn.disabled = true; btn.style.opacity = .6; btn.innerHTML = 'Enviando…'; msg.textContent = '';
  try {
    const r = await fetch('<?= url('/pdv/comprovante/' . $venda['id'] . '/whatsapp') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    });
    const j = await r.json();
    if (!j.success) throw new Error(j.error || 'Falha no envio.');
    btn.innerHTML = '✓ Enviado'; btn.style.background = '#198754'; btn.style.opacity = 1;
    msg.style.color = '#198754'; msg.textContent = 'PDF enviado no WhatsApp do cliente.';
  } catch (e) {
    btn.disabled = false; btn.style.opacity = 1; btn.innerHTML = orig;
    msg.style.color = '#dc3545'; msg.textContent = e.message;
  }
}
</script>
</body>
</html>
