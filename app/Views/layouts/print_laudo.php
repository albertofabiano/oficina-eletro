<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Laudo Técnico <?= e($os['numero']) ?> — <?= e($os['empresa_nome']) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 12.5px; color: #000; background:#fff; }
.no-print { background:#f0f2f5;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dee2e6;flex-wrap:wrap }
.header { display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1a1d23;padding-bottom:10px;margin-bottom:12px }
.empresa-nome { font-size:17px;font-weight:bold;color:#1a1d23 }
.empresa-detalhe { font-size:11.5px;color:#555 }
.os-numero { font-size:25.5px;font-weight:900;border:2px solid #1a1d23;padding:4px 12px;display:inline-block;color:#1a1d23 }
.badge-laudo { background:#0d6efd;color:#fff;padding:3px 10px;border-radius:4px;font-size:11.5px;font-weight:bold }
.section-title { font-weight:bold;font-size:11.5px;background:#1a1d23;color:#fff;padding:3px 8px;margin:8px 0 4px;text-transform:uppercase;letter-spacing:.06em }
.info-box { border:1px solid #ccc;border-radius:4px;padding:6px 10px;margin-bottom:6px;font-size:12px;line-height:1.6;flex:1 }
.info-label { font-size:10.5px;color:#777;text-transform:uppercase;letter-spacing:.05em;font-weight:bold }
.laudo-box { border:2px solid #0d6efd;background:#f5f9ff;border-radius:6px;padding:10px 14px;margin-bottom:10px;font-size:13px;line-height:1.7 }
.laudo-box ul, .laudo-box ol { margin:0;padding-left:1.2rem }
table { width:100%;border-collapse:collapse;margin-bottom:8px }
th,td { border:1px solid #ccc;padding:5px 8px;text-align:left;vertical-align:top }
th { background:#f0f0f0;font-weight:bold;font-size:11.5px }
.total-row { background:#1a1d23;color:#fff;font-weight:bold;font-size:15px }
.assinaturas { display:flex;justify-content:space-around;margin-top:30px }
.assinatura-linha { border-top:2px solid #000;width:200px;padding-top:4px;font-size:11.5px;margin:0 auto;text-align:center }
.termos { font-size:10.5px;color:#555;line-height:1.6;margin-top:10px;border-top:1px solid #ccc;padding-top:8px }
@media print { .no-print { display:none!important } body { margin:0;padding:10mm } @page { margin:0;size:A4 } }
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()" style="background:#0d6efd;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <a href="<?= url('/os/' . $os['id']) ?>" style="background:#e2e8f0;color:#1a1d23;text-decoration:none;padding:7px 18px;border-radius:6px;font-size:15px;font-weight:600;display:flex;align-items:center;gap:6px">← Voltar para a OS</a>
  <span style="margin-left:auto;font-size:12.5px;color:#888">Laudo Técnico — OS <?= e($os['numero']) ?></span>
</div>

<div style="max-width:820px;margin:0 auto;padding:16px 14px">

  <!-- Cabeçalho -->
  <div class="header">
    <div>
      <?php if (!empty($os['emp_logo'])): ?>
      <img src="<?= url('/uploads/' . e($os['emp_logo'])) ?>" style="max-width:160px;max-height:60px;object-fit:contain;margin-bottom:6px;display:block">
      <?php endif; ?>
      <div class="empresa-nome"><?= e($os['empresa_nome']) ?></div>
      <?php if (!empty($os['empresa_cnpj'])): ?><div class="empresa-detalhe">CNPJ: <?= e($os['empresa_cnpj']) ?></div><?php endif; ?>
      <?php if (!empty($os['emp_tel'])): ?><div class="empresa-detalhe">Tel: <?= e($os['emp_tel']) ?><?php if (!empty($os['emp_whatsapp'])): ?> | WhatsApp: <?= e($os['emp_whatsapp']) ?><?php endif; ?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
      <div class="badge-laudo">LAUDO TÉCNICO</div>
      <div class="os-numero" style="margin-top:6px">OS <?= e($os['numero']) ?></div>
      <div style="font-size:11.5px;color:#444;margin-top:4px">
        Data: <?= date('d/m/Y') ?>
      </div>
    </div>
  </div>

  <!-- Cliente e Equipamento -->
  <div style="display:flex;gap:10px;margin-bottom:6px">
    <div style="flex:1;display:flex;flex-direction:column">
      <div class="section-title">Cliente</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:14px"><?= e($os['cliente_nome']) ?></div><?php if (!empty($os['cliente_contato'])): ?><div style="font-size:12px"><b>Contato:</b> <?= e($os['cliente_contato']) ?></div><?php endif; ?>
        <?php if ($os['cpf_cnpj']??null): ?><div><span class="info-label">CPF/CNPJ:</span> <?= e(doc_mask($os['cpf_cnpj'])) ?></div><?php endif; ?>
        <?php if ($os['cliente_tel']??null): ?><div><span class="info-label">Tel:</span> <?= e($os['cliente_tel']) ?></div><?php endif; ?>
        <?php if ($os['cliente_whats']??null): ?><div><span class="info-label">WhatsApp:</span> <?= e($os['cliente_whats']) ?></div><?php endif; ?>
      </div>
    </div>
    <div style="flex:1;display:flex;flex-direction:column">
      <div class="section-title">Equipamento</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:14px"><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?></div>
        <div><?= e($os['equip_tipo']??'') ?></div>
        <?php if ($os['numero_serie']??null): ?><div><span class="info-label">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
        <?php if (!empty($os['imei'])): ?><div><span class="info-label">IMEI:</span> <?= e($os['imei']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Defeito -->
  <div class="section-title">Defeito Relatado</div>
  <div class="info-box" style="min-height:24px"><?= nl2br(e($os['defeito_relatado']??'')) ?></div>

  <?php if ($os['defeito_constatado']??null): ?>
  <div class="section-title">Diagnóstico Técnico</div>
  <div class="info-box"><?= nl2br(e($os['defeito_constatado'])) ?></div>
  <?php endif; ?>

  <!-- Laudo Técnico -->
  <div class="section-title">Laudo Técnico</div>
  <div class="laudo-box"><?= $os['laudo_tecnico'] ?></div>

  <!-- Serviços -->
  <?php if (!empty($os['servicos'])): ?>
  <div class="section-title">Serviços</div>
  <table>
    <thead><tr><th style="width:60%">Descrição</th><th style="width:10%;text-align:center">Qtd</th><th style="width:15%;text-align:right">Valor Unit.</th><th style="width:15%;text-align:right">Total</th></tr></thead>
    <tbody>
      <?php foreach ($os['servicos'] as $s): ?>
      <tr>
        <td><?= e($s['descricao']) ?></td>
        <td style="text-align:center"><?= $s['quantidade'] ?></td>
        <td style="text-align:right"><?= money($s['valor_unitario']) ?></td>
        <td style="text-align:right;font-weight:bold"><?= money($s['valor_total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- Peças -->
  <?php if (!empty($os['pecas'])): ?>
  <div class="section-title">Peças / Materiais</div>
  <table>
    <thead><tr><th style="width:60%">Descrição</th><th style="width:10%;text-align:center">Qtd</th><th style="width:15%;text-align:right">Valor Unit.</th><th style="width:15%;text-align:right">Total</th></tr></thead>
    <tbody>
      <?php foreach ($os['pecas'] as $p): ?>
      <tr>
        <td><?= e($p['descricao']) ?></td>
        <td style="text-align:center"><?= $p['quantidade'] ?></td>
        <td style="text-align:right"><?= money($p['valor_unitario']) ?></td>
        <td style="text-align:right;font-weight:bold"><?= money($p['valor_total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <?php if (!empty($os['servicos']) || !empty($os['pecas'])): ?>
  <!-- Total -->
  <table>
    <tr class="total-row">
      <td colspan="3" style="text-align:right;font-size:14px">VALOR TOTAL</td>
      <td style="text-align:right;font-size:17px;width:20%"><?= money($os['valor_total']) ?></td>
    </tr>
  </table>
  <?php endif; ?>

  <!-- Assinaturas -->
  <div class="assinaturas" style="margin-top:28px">
    <div><div class="assinatura-linha">Responsável Técnico</div></div>
    <div><div class="assinatura-linha"><?= e($os['empresa_nome']) ?></div></div>
  </div>

  <div class="termos">
    <strong>Observação:</strong> Este laudo técnico descreve o diagnóstico e/ou serviço realizado no equipamento acima identificado, referente à OS <?= e($os['numero']) ?>.
  </div>

</div>
</body>
</html>
