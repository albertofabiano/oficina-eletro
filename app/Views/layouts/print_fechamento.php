<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante de Entrega — OS <?= e($os['numero'] ?? '') ?></title>
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

.section-title {
  font-weight:bold;font-size:11.5px;padding:3px 8px;margin:10px 0 5px;
  text-transform:uppercase;letter-spacing:.06em;
  background:#1a1d23;color:#fff;
}
.info-box    { border:2px solid #ccc;border-radius:4px;padding:7px 10px;margin-bottom:7px;font-size:12px;line-height:1.65; }
.info-label  { font-size:10.5px;color:#777;text-transform:uppercase;letter-spacing:.05em;font-weight:bold; }
.row         { display:flex;gap:10px; }
.col         { flex:1; }

table { width:100%;border-collapse:collapse;margin-bottom:8px; }
th,td { border:2px solid #ccc;padding:4px 7px;text-align:left;vertical-align:top;font-size:12px; }
th    { background:#f0f0f0;font-weight:bold;font-size:11.5px; }
.val  { text-align:right;white-space:nowrap; }

.total-wrap { display:flex;justify-content:flex-end;margin:6px 0 10px; }
.total-box  { border:2px solid #1a1d23;padding:7px 14px;min-width:200px; }
.total-l    { display:flex;justify-content:space-between;gap:20px;font-size:12.5px;margin-bottom:2px; }
.total-l.g  { font-size:16px;font-weight:bold;border-top:1px solid #ccc;padding-top:4px;margin-top:3px; }

.texto-garantia {
  border:2px solid #ccc;border-radius:4px;padding:8px 12px;
  font-size:11px;color:#444;line-height:1.7;margin-bottom:10px;
}
.texto-garantia h3 { font-size:12px;font-weight:bold;margin:4px 0 2px; }
.texto-garantia ul, .texto-garantia ol { padding-left:1.2rem;margin:2px 0; }
.texto-garantia p  { margin:2px 0; }

.assinaturas     { display:flex;justify-content:space-around;margin-top:36px; }
.assinatura-linha { border-top:2px solid #000;width:220px;padding-top:4px;font-size:11.5px;text-align:center;margin:0 auto; }

@media print {
  .no-print { display:none!important; }
  body { margin:0; padding:10mm; }
  @page { margin:0; size:A4; }
}
</style>
</head>
<body>

<?php
$waNum  = only_numbers($os['cliente_whats'] ?? $os['cliente_tel'] ?? '');
$waMsg  = urlencode("Olá *{$os['cliente_nome']}*! 🎉\nSegue o comprovante de entrega da OS *{$os['numero']}*.\nEquipamento: " . trim(($os['equip_marca']??'').' '.($os['equip_modelo']??'')) . "\nAcesse: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");?>
<div class="no-print" style="background:#f0f2f5;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dee2e6;flex-wrap:wrap">
  <button onclick="window.print()" style="background:#1a1d23;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <?php $_waTipo = 'fechamento'; include __DIR__ . '/_botao_wa_pdf.php'; ?>
  <a href="<?= url('/os/' . $os['id']) ?>" style="color:#555;text-decoration:none;font-size:14px">← Voltar para a OS</a>
  <span style="margin-left:auto;font-size:12.5px;color:#888">Comprovante de Entrega — OS <?= e($os['numero']) ?></span>
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
          $end = array_filter([$os['emp_logradouro']??'', ($os['emp_numero']??'') ? 'nº '.$os['emp_numero'] : '']);
          $cuf = trim(($os['emp_cidade']??'').(isset($os['emp_uf']) ? '/'.$os['emp_uf'] : ''));
        ?>
        <?php if ($end): ?><div class="empresa-detalhe"><?= e(implode(', ', $end)) ?><?= $cuf ? ' — '.$cuf : '' ?></div><?php endif; ?>
        <?php if (!empty($os['emp_tel'])): ?>
        <div class="empresa-detalhe">Tel: <?= e($os['emp_tel']) ?><?php if (!empty($os['emp_whatsapp'])): ?> | WhatsApp: <?= e($os['emp_whatsapp']) ?><?php endif; ?></div>
        <?php endif; ?>
      </div>
    </div>
    <div class="header-dir">
      <div class="os-numero">OS <?= e($os['numero']) ?></div>
      <div class="doc-info">
        <div><strong>Entrada:</strong> <?= date_br($os['data_entrada'], true) ?></div>
        <div><strong>Conclusão:</strong> <?= date_br($os['data_conclusao'] ?? date('Y-m-d H:i:s'), true) ?></div>
        <?php if ($os['tecnico_nome'] ?? null): ?>
        <div><strong>Técnico:</strong> <?= e($os['tecnico_nome']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- CLIENTE + EQUIPAMENTO -->
  <div class="row">
    <div class="col">
      <div class="section-title">Cliente</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:14px"><?= e($os['cliente_nome']) ?></div><?php if (!empty($os['cliente_contato'])): ?><div style="font-size:12px"><b>Contato:</b> <?= e($os['cliente_contato']) ?></div><?php endif; ?>
        <?php if ($os['cpf_cnpj']??null): ?><div><span class="info-label">CPF/CNPJ:</span> <?= e(doc_mask($os['cpf_cnpj'])) ?></div><?php endif; ?>
        <?php if ($os['cliente_tel']??null): ?><div><span class="info-label">Tel:</span> <?= e($os['cliente_tel']) ?></div><?php endif; ?>
        <?php if ($os['cliente_whats']??null): ?><div><span class="info-label">WhatsApp:</span> <?= e($os['cliente_whats']) ?></div><?php endif; ?>
        <?php
          $endCli = array_filter([
            $os['cli_logradouro'] ?? '',
            ($os['cli_numero'] ?? '') ? 'nº '.$os['cli_numero'] : '',
            $os['cli_complemento'] ?? '',
            $os['cli_bairro'] ?? '',
          ]);
          $cidCli = trim(($os['cli_cidade'] ?? '').(isset($os['cli_uf']) ? '/'.$os['cli_uf'] : ''));
        ?>
        <?php if ($endCli): ?>
        <div style="margin-top:3px"><span class="info-label">Endereço:</span>
          <?= e(implode(', ', $endCli)) ?><?= $cidCli ? ' — '.e($cidCli) : '' ?>
          <?php if ($os['cli_cep'] ?? null): ?> — CEP <?= e($os['cli_cep']) ?><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col">
      <div class="section-title">Equipamento</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:14px"><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?></div>
        <div><?= e($os['equip_tipo']??'') ?></div>
        <?php if ($os['numero_serie']??null): ?><div><span class="info-label">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
        <?php if (!empty($os['imei'])): ?><div><span class="info-label">IMEI:</span> <?= e($os['imei']) ?></div><?php endif; ?>
        <?php if ($os['equip_cor']??null): ?><div><span class="info-label">Cor:</span> <?= e($os['equip_cor']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- DEFEITO RELATADO -->
  <div class="section-title">Defeito Relatado</div>
  <div class="info-box" style="min-height:28px"><?= nl2br(e($os['defeito_relatado']??'')) ?></div>

  <!-- SERVIÇOS -->
  <?php if (!empty($os['servicos'])): ?>
  <div class="section-title">Serviços Realizados</div>
  <table>
    <thead><tr><th>Descrição</th><th width="50">Qtd</th><th width="95" class="val">Valor Unit.</th><th width="95" class="val">Total</th></tr></thead>
    <tbody>
      <?php foreach ($os['servicos'] as $s): ?>
      <tr>
        <td><?= e($s['descricao']) ?></td>
        <td><?= $s['quantidade'] ?></td>
        <td class="val">R$ <?= number_format($s['valor_unitario'],2,',','.') ?></td>
        <td class="val">R$ <?= number_format($s['valor_total'],2,',','.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- PEÇAS -->
  <?php if (!empty($os['pecas'])): ?>
  <div class="section-title">Peças Utilizadas</div>
  <table>
    <thead><tr><th>Peça / Componente</th><th width="50">Qtd</th><th width="95" class="val">Valor Unit.</th><th width="95" class="val">Total</th></tr></thead>
    <tbody>
      <?php foreach ($os['pecas'] as $p): ?>
      <tr>
        <td><?= e($p['descricao']) ?></td>
        <td><?= $p['quantidade'] ?></td>
        <td class="val">R$ <?= number_format($p['valor_unitario'],2,',','.') ?></td>
        <td class="val">R$ <?= number_format($p['valor_total'],2,',','.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <!-- TOTAIS -->
  <?php
    $totalS  = array_sum(array_column($os['servicos']??[], 'valor_total'));
    $totalP  = array_sum(array_column($os['pecas']??[], 'valor_total'));
    $desconto= (float)($os['desconto_valor']??0);
    $total   = $os['valor_total'] ?? ($totalS + $totalP - $desconto);
    $pago    = (float)($os['valor_pago']??0);
  ?>
  <div class="total-wrap">
    <div class="total-box">
      <?php if ($totalS): ?><div class="total-l"><span>Serviços</span><span>R$ <?= number_format($totalS,2,',','.') ?></span></div><?php endif; ?>
      <?php if ($totalP): ?><div class="total-l"><span>Peças</span><span>R$ <?= number_format($totalP,2,',','.') ?></span></div><?php endif; ?>
      <?php if ($desconto > 0): ?><div class="total-l" style="color:green"><span>Desconto</span><span>- R$ <?= number_format($desconto,2,',','.') ?></span></div><?php endif; ?>
      <div class="total-l g"><span>TOTAL</span><span>R$ <?= number_format($total,2,',','.') ?></span></div>
      <?php if ($pago > 0 && $pago < $total): ?>
      <div class="total-l" style="color:green;font-size:11.5px"><span>Recebido</span><span>R$ <?= number_format($pago,2,',','.') ?></span></div>
      <div class="total-l" style="color:#dc3545;font-size:11.5px"><span>Saldo</span><span>R$ <?= number_format($total-$pago,2,',','.') ?></span></div>
      <?php elseif (($os['situacao_pagamento']??'')==='pago'): ?>
      <div style="text-align:center;color:green;font-weight:bold;font-size:12.5px;margin-top:5px;border-top:1px solid #ccc;padding-top:4px">✓ PAGO</div>
      <?php endif; ?>
    </div>
  </div>

  <!-- TERMO DE GARANTIA -->
  <?php $textoGarantia = $configs['texto_garantia'] ?? ''; ?>
  <?php if ($textoGarantia): ?>
  <div class="section-title">Termo de Garantia</div>
  <?php
    $gDias = $os['garantia_dias'] ?? 90;
    $gDe   = date_br($os['data_conclusao'] ?? date('Y-m-d'));
    $gAte  = date_br($os['garantia_ate'] ?? date('Y-m-d', strtotime('+' . $gDias . ' days')));
  ?>
  <div style="border:2px solid #198754;background:#f0faf4;border-radius:4px;padding:6px 11px;margin-bottom:6px;font-size:12px;color:#0f5132">
    <strong>Prazo de garantia: <?= $gDias ?> dias</strong> &nbsp;—&nbsp;
    válida de <strong><?= $gDe ?></strong> até <strong><?= $gAte ?></strong>.
  </div>
  <div class="texto-garantia"><?= $textoGarantia ?></div>
  <?php else: ?>
  <div class="section-title">Garantia</div>
  <div class="info-box" style="font-size:11px;color:#555">
    Garantia de <strong><?= $os['garantia_dias']??90 ?> dias</strong> a partir de <?= date_br($os['data_conclusao']??date('Y-m-d')) ?>,
    válida até <strong><?= date_br($os['garantia_ate'] ?? date('Y-m-d', strtotime('+'.($os['garantia_dias']??90).' days'))) ?></strong>.
    A garantia cobre exclusivamente o defeito reparado nas mesmas condições de uso.
  </div>
  <?php endif; ?>

  <!-- ASSINATURAS -->
  <div class="assinaturas">
    <div><div class="assinatura-linha">Assinatura do Cliente</div><div style="font-size:10.5px;text-align:center;margin-top:2px;color:#555">Recebi o equipamento em perfeito estado</div></div>
    <div><div class="assinatura-linha"><?= e($os['empresa_nome']) ?></div><div style="font-size:10.5px;text-align:center;margin-top:2px;color:#555">Responsável pela entrega</div></div>
  </div>

</div>
</body>
</html>
