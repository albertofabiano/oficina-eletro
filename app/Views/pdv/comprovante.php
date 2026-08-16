<?php
$formasLabel = [
  'dinheiro' => 'Dinheiro', 'pix' => 'PIX',
  'cartao_credito' => 'Cartão de crédito', 'cartao_debito' => 'Cartão de débito', 'outro' => 'Outro',
];
$endEmp = array_filter([
  $empresa['logradouro'] ?? '', ($empresa['numero'] ?? '') ? 'nº ' . $empresa['numero'] : '',
  $empresa['bairro'] ?? '', trim(($empresa['cidade'] ?? '') . (($empresa['uf'] ?? '') ? '/' . $empresa['uf'] : '')),
]);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Comprovante de Venda #<?= (int) $venda['id'] ?></title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#e9ecef; color:#111; padding:16px; }
  .barra { max-width:340px; margin:0 auto 12px; display:flex; flex-wrap:wrap; gap:8px; }
  .barra button, .barra a { flex:1 1 calc(50% - 4px); text-align:center; text-decoration:none; border:none; border-radius:8px; padding:10px 6px; font-size:12.5px; font-weight:600; cursor:pointer; }
  .btn-print { background:#0d6efd; color:#fff; }
  .btn-a4 { background:#6f42c1; color:#fff; }
  .btn-nova { background:#198754; color:#fff; }
  .btn-voltar { background:#e2e6ea; color:#333; }
  .cupom { max-width:340px; margin:0 auto; background:#fff; padding:18px 20px; border-radius:6px; box-shadow:0 2px 12px rgba(0,0,0,.12); font-size:13px; }
  .center { text-align:center; }
  .emp-nome { font-size:16px; font-weight:800; }
  .muted { color:#666; font-size:11px; line-height:1.5; }
  .divisor { border:none; border-top:1px dashed #999; margin:10px 0; }
  .item { display:flex; justify-content:space-between; gap:8px; margin-bottom:5px; }
  .item .d { flex:1; }
  .item .q { color:#666; font-size:11px; }
  .tot-linha { display:flex; justify-content:space-between; margin-bottom:3px; }
  .tot-geral { display:flex; justify-content:space-between; font-size:18px; font-weight:800; margin:6px 0; }
  .badge-pg { display:inline-block; background:#111; color:#fff; border-radius:4px; padding:2px 8px; font-size:11px; }
  @media print {
    body { background:#fff; padding:0; }
    .barra { display:none; }
    .cupom { box-shadow:none; max-width:100%; padding:0; }
  }
</style>
</head>
<body>

<div class="barra">
  <button class="btn-print" onclick="window.print()">🖨 Imprimir</button>
  <a class="btn-a4" href="<?= url('/pdv/comprovante/' . $venda['id'] . '/a4') ?>">📄 Imprimir A4 / WhatsApp</a>
  <a class="btn-nova" href="<?= url('/pdv') ?>">＋ Nova venda</a>
  <a class="btn-voltar" href="<?= url('/pdv') ?>">← Voltar</a>
</div>

<div class="cupom">
  <div class="center">
    <div class="emp-nome"><?= e($empresa['nome_fantasia'] ?? 'Minha Empresa') ?></div>
    <?php if (!empty($empresa['cnpj'])): ?><div class="muted">CNPJ: <?= e($empresa['cnpj']) ?></div><?php endif; ?>
    <?php if ($endEmp): ?><div class="muted"><?= e(implode(', ', $endEmp)) ?></div><?php endif; ?>
    <?php if (!empty($empresa['telefone'])): ?><div class="muted">Tel: <?= e($empresa['telefone']) ?></div><?php endif; ?>
  </div>

  <hr class="divisor">
  <div class="center" style="font-weight:700">COMPROVANTE DE VENDA</div>
  <div class="center muted">
    Nº <?= (int) $venda['id'] ?> &nbsp;•&nbsp; <?= date_br($venda['criado_em'], true) ?>
  </div>
  <div class="muted" style="margin-top:4px">
    <?php if (!empty($venda['cliente_nome'])): ?>Cliente: <?= e($venda['cliente_nome']) ?><br><?php else: ?>Cliente: Consumidor<br><?php endif; ?>
    <?php if (!empty($venda['vendedor'])): ?>Atendente: <?= e($venda['vendedor']) ?><?php endif; ?>
  </div>

  <hr class="divisor">
  <?php foreach ($itens as $it): ?>
  <div class="item">
    <div class="d">
      <?= e($it['descricao']) ?>
      <div class="q"><?= ($it['quantidade'] + 0) ?> x <?= money($it['valor_unitario']) ?></div>
    </div>
    <div><?= money($it['valor_total']) ?></div>
  </div>
  <?php endforeach; ?>

  <hr class="divisor">
  <?php if (($venda['desconto'] ?? 0) > 0): ?>
  <div class="tot-linha"><span>Subtotal</span><span><?= money($venda['subtotal']) ?></span></div>
  <div class="tot-linha" style="color:#c00"><span>Desconto</span><span>- <?= money($venda['desconto']) ?></span></div>
  <?php endif; ?>
  <div class="tot-geral"><span>TOTAL</span><span><?= money($venda['total']) ?></span></div>

  <div class="tot-linha"><span>Pagamento</span><span class="badge-pg"><?= $formasLabel[$venda['forma_pagamento']] ?? e($venda['forma_pagamento']) ?></span></div>
  <?php if ($venda['forma_pagamento'] === 'dinheiro' && ($venda['valor_recebido'] ?? 0) > 0): ?>
  <div class="tot-linha"><span>Recebido</span><span><?= money($venda['valor_recebido']) ?></span></div>
  <div class="tot-linha"><span>Troco</span><span><?= money($venda['troco'] ?? 0) ?></span></div>
  <?php endif; ?>

  <?php if (!empty($venda['observacoes'])): ?>
  <hr class="divisor">
  <div class="muted"><?= nl2br(e($venda['observacoes'])) ?></div>
  <?php endif; ?>

  <hr class="divisor">
  <div class="center muted">Obrigado pela preferência!<br>Documento sem valor fiscal.</div>
</div>

</body>
</html>
