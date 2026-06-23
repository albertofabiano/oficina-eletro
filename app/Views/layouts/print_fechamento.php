<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante de Entrega — <?= e($os['numero'] ?? '') ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background:#fff; }

/* ── Header ───────────────────────────────────── */
.header {
  display:flex; justify-content:space-between; align-items:flex-start;
  border-bottom: 3px solid #198754; padding-bottom: 10px; margin-bottom: 14px; gap:16px;
}
.logo-empresa   { max-width:160px; max-height:65px; object-fit:contain; }
.logo-inicial   { width:52px;height:52px;border-radius:8px;background:#198754;color:#fff;
                  display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:bold;flex-shrink:0; }
.empresa-nome   { font-size:15px;font-weight:bold;color:#198754; }
.empresa-detalhe{ font-size:10px;color:#555;line-height:1.5; }
.header-dir     { text-align:right;flex-shrink:0; }
.titulo-doc     { font-size:18px;font-weight:900;color:#198754;border:2px solid #198754;
                  padding:4px 12px;display:inline-block;margin-bottom:4px; }
.os-ref         { font-size:13px;font-weight:bold;color:#333;margin-bottom:4px; }
.doc-info       { font-size:10px;color:#555;line-height:1.7; }

/* ── Seções ───────────────────────────────────── */
.section-title {
  font-weight:bold;font-size:10px;padding:3px 8px;margin:10px 0 5px;
  text-transform:uppercase;letter-spacing:.06em;
  background:#198754;color:#fff;
}
.info-box       { border:1px solid #ccc;border-radius:4px;padding:7px 10px;margin-bottom:7px;font-size:10.5px;line-height:1.65; }
.info-label     { font-size:9px;color:#777;text-transform:uppercase;letter-spacing:.05em;font-weight:bold; }
.row            { display:flex;gap:10px; }
.col            { flex:1; }

/* ── Tabelas ──────────────────────────────────── */
table { width:100%;border-collapse:collapse;margin-bottom:8px; }
th,td { border:1px solid #ccc;padding:4px 7px;text-align:left;vertical-align:top;font-size:10.5px; }
th    { background:#f0f0f0;font-weight:bold;font-size:10px; }
.val  { text-align:right;white-space:nowrap; }

/* ── Totais ───────────────────────────────────── */
.total-wrap { display:flex;justify-content:flex-end;margin:8px 0; }
.total-box  { border:2px solid #198754;padding:8px 16px;min-width:210px; }
.total-l    { display:flex;justify-content:space-between;gap:24px;font-size:11px;margin-bottom:3px; }
.total-l.g  { font-size:15px;font-weight:bold;border-top:1px solid #ccc;padding-top:5px;margin-top:4px;color:#198754; }

/* ── Garantia ─────────────────────────────────── */
.garantia-box {
  border:2px solid #198754;border-radius:6px;padding:12px 16px;
  background:#f0fff4;margin:10px 0;
}
.garantia-titulo { font-size:13px;font-weight:bold;color:#198754;margin-bottom:6px; }
.garantia-datas  { display:flex;gap:32px; }
.garantia-item   { text-align:center; }
.garantia-item .label { font-size:9px;color:#777;text-transform:uppercase;letter-spacing:.05em; }
.garantia-item .data  { font-size:14px;font-weight:bold;color:#198754; }

/* ── Assinaturas ──────────────────────────────── */
.assinaturas    { display:flex;justify-content:space-around;margin-top:36px; }
.assinatura-linha { border-top:1px solid #000;width:220px;padding-top:4px;font-size:10px;text-align:center;margin:0 auto; }
.rodape         { margin-top:16px;font-size:9.5px;color:#555;border-top:1px solid #ccc;padding-top:8px; }

/* ── Print ────────────────────────────────────── */
@media print {
  .no-print { display:none!important; }
  @page { margin:12mm 10mm; }
}
</style>
</head>
<body>

<!-- Barra de ação (some ao imprimir) -->
<div class="no-print" style="background:#f0fff4;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:2px solid #198754">
  <button onclick="window.print()" style="background:#198754;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <a href="<?= url('/os/' . $os['id']) ?>" style="color:#198754;text-decoration:none;font-size:12px;font-weight:600">← Voltar para a OS</a>
  <span style="margin-left:auto;font-size:11px;color:#555">Comprovante de Entrega • OS <?= e($os['numero']) ?></span>
</div>

<div style="max-width:820px;margin:0 auto;padding:16px 14px">

  <!-- ── CABEÇALHO ─────────────────────────────── -->
  <div class="header">
    <div style="display:flex;align-items:center;gap:14px;flex:1">
      <?php if (!empty($os['emp_logo'])): ?>
        <img src="<?= url('/uploads/' . e($os['emp_logo'])) ?>" alt="Logo" class="logo-empresa">
      <?php else: ?>
        <div class="logo-inicial"><?= mb_strtoupper(mb_substr($os['empresa_nome'] ?? 'O', 0, 1)) ?></div>
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
      <div class="titulo-doc">COMPROVANTE DE ENTREGA</div>
      <div class="os-ref">OS <?= e($os['numero']) ?></div>
      <div class="doc-info">
        <div><strong>Entrada:</strong> <?= date_br($os['data_entrada'], true) ?></div>
        <div><strong>Conclusão:</strong> <?= date_br($os['data_conclusao'] ?? date('Y-m-d H:i:s'), true) ?></div>
        <?php if (!empty($os['tecnico_nome'])): ?>
        <div><strong>Técnico:</strong> <?= e($os['tecnico_nome']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── CLIENTE E EQUIPAMENTO ─────────────────── -->
  <div class="row">
    <div class="col">
      <div class="section-title">Cliente</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:12px"><?= e($os['cliente_nome']) ?></div>
        <?php if ($os['cpf_cnpj']??null): ?><div><span class="info-label">CPF/CNPJ:</span> <?= e($os['cpf_cnpj']) ?></div><?php endif; ?>
        <?php if ($os['cliente_tel']??null): ?><div><span class="info-label">Tel:</span> <?= e($os['cliente_tel']) ?></div><?php endif; ?>
        <?php if ($os['cliente_whats']??null): ?><div><span class="info-label">WhatsApp:</span> <?= e($os['cliente_whats']) ?></div><?php endif; ?>
      </div>
    </div>
    <div class="col">
      <div class="section-title">Equipamento</div>
      <div class="info-box">
        <div style="font-weight:bold;font-size:12px"><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?></div>
        <div><span class="info-label">Tipo:</span> <?= e($os['equip_tipo']??'') ?></div>
        <?php if ($os['numero_serie']??null): ?><div><span class="info-label">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
        <?php if ($os['equip_cor']??null): ?><div><span class="info-label">Cor:</span> <?= e($os['equip_cor']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- ── PROBLEMA RELATADO ─────────────────────── -->
  <div class="section-title">Defeito Relatado</div>
  <div class="info-box"><?= nl2br(e($os['defeito_relatado']??'')) ?></div>

  <!-- ── SOLUÇÃO E LAUDO ───────────────────────── -->
  <?php if (!empty($os['solucao_aplicada']) || !empty($os['laudo_tecnico'])): ?>
  <div class="section-title">Serviço Executado</div>
  <div class="info-box">
    <?php if (!empty($os['solucao_aplicada'])): ?>
    <div><span class="info-label">Solução aplicada:</span></div>
    <div style="margin-bottom:4px"><?= nl2br(e($os['solucao_aplicada'])) ?></div>
    <?php endif; ?>
    <?php if (!empty($os['laudo_tecnico'])): ?>
    <div><span class="info-label">Laudo técnico:</span></div>
    <div><?= nl2br(e($os['laudo_tecnico'])) ?></div>
    <?php endif; ?>
  </div>
  <?php endif; ?>

  <!-- ── SERVIÇOS ───────────────────────────────── -->
  <?php if (!empty($os['servicos'])): ?>
  <div class="section-title">Serviços Realizados</div>
  <table>
    <thead>
      <tr><th>Descrição do Serviço</th><th width="50">Qtd</th><th width="95" class="val">Valor Unit.</th><th width="95" class="val">Total</th></tr>
    </thead>
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

  <!-- ── PEÇAS ──────────────────────────────────── -->
  <?php if (!empty($os['pecas'])): ?>
  <div class="section-title">Peças e Componentes Utilizados</div>
  <table>
    <thead>
      <tr><th>Peça / Componente</th><th width="50">Qtd</th><th width="95" class="val">Valor Unit.</th><th width="95" class="val">Total</th></tr>
    </thead>
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

  <!-- ── TOTAIS ─────────────────────────────────── -->
  <?php
    $totalS = array_sum(array_column($os['servicos']??[], 'valor_total'));
    $totalP = array_sum(array_column($os['pecas']??[], 'valor_total'));
    $total  = $os['valor_total'] ?? ($totalS + $totalP);
    $pago   = $os['valor_pago'] ?? 0;
    $sitPgt = $os['situacao_pagamento'] ?? 'pendente';
  ?>
  <div class="total-wrap">
    <div class="total-box">
      <?php if ($totalS): ?><div class="total-l"><span>Serviços</span><span>R$ <?= number_format($totalS,2,',','.') ?></span></div><?php endif; ?>
      <?php if ($totalP): ?><div class="total-l"><span>Peças</span><span>R$ <?= number_format($totalP,2,',','.') ?></span></div><?php endif; ?>
      <?php if ($os['desconto_valor']??0): ?><div class="total-l" style="color:green"><span>Desconto</span><span>- R$ <?= number_format($os['desconto_valor'],2,',','.') ?></span></div><?php endif; ?>
      <div class="total-l g"><span>TOTAL</span><span>R$ <?= number_format($total,2,',','.') ?></span></div>
      <?php if ($pago > 0): ?>
      <div class="total-l" style="color:#198754;font-size:10px"><span>✓ Recebido</span><span>R$ <?= number_format($pago,2,',','.') ?></span></div>
      <?php endif; ?>
      <?php if ($sitPgt === 'pago'): ?>
      <div style="text-align:center;color:#198754;font-weight:bold;font-size:12px;margin-top:6px;border-top:1px solid #ccc;padding-top:4px">✓ PAGO</div>
      <?php elseif ($sitPgt === 'parcial'): ?>
      <div class="total-l" style="color:#fd7e14;font-size:10px"><span>Saldo restante</span><span>R$ <?= number_format($total - $pago,2,',','.') ?></span></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- ── GARANTIA ───────────────────────────────── -->
  <div class="garantia-box">
    <div class="garantia-titulo"><i>✓</i> Garantia do Serviço</div>
    <div class="garantia-datas">
      <div class="garantia-item">
        <div class="label">Data de conclusão</div>
        <div class="data"><?= date_br($os['data_conclusao'] ?? date('Y-m-d')) ?></div>
      </div>
      <div class="garantia-item">
        <div class="label">Prazo de garantia</div>
        <div class="data"><?= $os['garantia_dias'] ?? 90 ?> dias</div>
      </div>
      <div class="garantia-item">
        <div class="label">Garantia válida até</div>
        <div class="data" style="font-size:16px">
          <?php
            $garantiaAte = $os['garantia_ate'] ?? date('Y-m-d', strtotime('+'.($os['garantia_dias']??90).' days', strtotime($os['data_conclusao'] ?? 'now')));
            echo date_br($garantiaAte);
          ?>
        </div>
      </div>
    </div>
    <div style="margin-top:8px;font-size:10px;color:#555">
      A garantia cobre apenas o defeito reparado, nas mesmas condições de uso. Não cobre danos físicos, líquidos, ou mau uso após a entrega.
    </div>
  </div>

  <!-- ── OBSERVAÇÕES ────────────────────────────── -->
  <?php if (!empty($os['observacoes_cliente'])): ?>
  <div class="section-title">Recomendações / Observações</div>
  <div class="info-box"><?= nl2br(e($os['observacoes_cliente'])) ?></div>
  <?php endif; ?>

  <!-- ── RODAPÉ ─────────────────────────────────── -->
  <div class="rodape">
    <div style="display:flex;justify-content:space-between">
      <span>Emitido em <?= date('d/m/Y \à\s H:i') ?> por <?= e($os['empresa_nome']) ?></span>
      <span><?php if (!empty($os['emp_tel'])): ?>Tel: <?= e($os['emp_tel']) ?><?php endif; ?></span>
    </div>
  </div>

  <!-- ── ASSINATURAS ────────────────────────────── -->
  <div class="assinaturas" style="margin-top:32px">
    <div class="assinatura">
      <div class="assinatura-linha">Assinatura do Cliente</div>
      <div style="font-size:9px;text-align:center;margin-top:2px;color:#555">Recebi o equipamento em perfeito estado</div>
    </div>
    <div class="assinatura">
      <div class="assinatura-linha"><?= e($os['empresa_nome']) ?></div>
      <div style="font-size:9px;text-align:center;margin-top:2px;color:#555">Responsável pela entrega</div>
    </div>
  </div>

</div>
</body>
</html>
