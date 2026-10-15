<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Relatório — <?= e($statusNome) ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background:#fff; }
.no-print { background:#f0f2f5;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dee2e6;flex-wrap:wrap; }
.header { display:flex;justify-content:space-between;align-items:flex-start;border-bottom:3px solid #1a1d23;padding-bottom:10px;margin-bottom:12px; }
.empresa-nome { font-size:15px;font-weight:bold;color:#1a1d23; }
.empresa-detalhe { font-size:10px;color:#555; }
.titulo-relatorio { font-size:18px;font-weight:900;color:#1a1d23; }
.periodo { font-size:11px;color:#555;margin-top:2px; }
.kpis { display:flex;gap:12px;margin-bottom:12px; }
.kpi { flex:1;border:1px solid #e2e8f0;border-radius:8px;padding:8px 12px;text-align:center; }
.kpi .val { font-size:18px;font-weight:800;color:#1a1d23; }
.kpi .lbl { font-size:9px;color:#64748b;text-transform:uppercase;font-weight:600;margin-top:2px; }
table { width:100%;border-collapse:collapse;font-size:10px; }
thead th { background:#1a1d23;color:#fff;padding:6px 8px;text-align:left;font-size:10px; }
tbody tr:nth-child(even) { background:#f8fafc; }
tbody td { padding:5px 8px;border-bottom:1px solid #e2e8f0;vertical-align:middle; }
.badge-status { border-radius:4px;padding:2px 7px;font-size:9px;font-weight:700;display:inline-block; }
.situacao-pago { color:#16a34a;font-weight:700; }
.situacao-pendente { color:#dc2626;font-weight:700; }
.total-row { background:#1a1d23!important;color:#fff;font-weight:bold; }
.total-row td { color:#fff!important;border:none!important; }
@media print { .no-print{display:none!important} body{margin:0;padding:8mm} @page{margin:0;size:A4 landscape} }
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()" style="background:#1a1d23;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <a href="<?= url('/relatorios') ?>" style="color:#555;text-decoration:none;font-size:12px">← Voltar aos Relatórios</a>
  <span style="margin-left:auto;font-size:11px;color:#888">
    <?= e($statusNome) ?> · <?= date('d/m/Y', strtotime($ini)) ?> a <?= date('d/m/Y', strtotime($fim)) ?>
  </span>
</div>

<div style="max-width:1100px;margin:0 auto;padding:14px">

  <!-- Cabeçalho -->
  <div class="header">
    <div>
      <div class="empresa-nome"><?= e($empresa['nome_fantasia'] ?? '') ?></div>
      <?php if ($empresa['cnpj'] ?? null): ?><div class="empresa-detalhe">CNPJ: <?= e($empresa['cnpj']) ?></div><?php endif; ?>
      <?php if ($empresa['telefone'] ?? null): ?><div class="empresa-detalhe">Tel: <?= e($empresa['telefone']) ?></div><?php endif; ?>
    </div>
    <div style="text-align:right">
      <div class="titulo-relatorio">📊 Relatório de OS</div>
      <div class="periodo">
        Status: <strong><?= e($statusNome) ?></strong><br>
        Período: <?= date('d/m/Y', strtotime($ini)) ?> a <?= date('d/m/Y', strtotime($fim)) ?><br>
        Gerado em: <?= date('d/m/Y H:i') ?>
      </div>
    </div>
  </div>

  <!-- KPIs -->
  <div class="kpis">
    <div class="kpi">
      <div class="val"><?= $total ?></div>
      <div class="lbl">Total de OS</div>
    </div>
    <div class="kpi">
      <div class="val" style="color:#16a34a">R$ <?= number_format($faturado, 2, ',', '.') ?></div>
      <div class="lbl">Valor Total</div>
    </div>
    <div class="kpi">
      <div class="val">R$ <?= number_format($ticket, 2, ',', '.') ?></div>
      <div class="lbl">Ticket Médio</div>
    </div>
    <div class="kpi">
      <div class="val" style="color:#16a34a"><?= count(array_filter($ordens, fn($o) => $o['situacao_pagamento'] === 'pago')) ?></div>
      <div class="lbl">Pagas</div>
    </div>
    <div class="kpi">
      <div class="val" style="color:#dc2626"><?= count(array_filter($ordens, fn($o) => $o['situacao_pagamento'] === 'pendente')) ?></div>
      <div class="lbl">Pendentes</div>
    </div>
  </div>

  <!-- Tabela de OS -->
  <?php if ($ordens): ?>
  <table>
    <thead>
      <tr>
        <th style="width:7%">Nº OS</th>
        <th style="width:18%">Cliente</th>
        <th style="width:18%">Equipamento</th>
        <th style="width:12%">Status</th>
        <th style="width:12%">Técnico</th>
        <th style="width:9%">Entrada</th>
        <th style="width:9%">Conclusão</th>
        <th style="width:8%;text-align:right">Valor</th>
        <th style="width:7%;text-align:center">Pagto</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($ordens as $os): ?>
      <tr>
        <td><strong><?= e($os['numero']) ?></strong></td>
        <td><?= e($os['cliente_nome'] ?? '—') ?></td>
        <td>
          <div><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?: '—' ?></div>
          <div style="font-size:9px;color:#64748b"><?= e($os['equip_tipo']??'') ?></div>
        </td>
        <td>
          <span class="badge-status" style="background:<?= e($os['status_cor']??'#6c757d') ?>;color:<?= e($os['status_cor_fonte']??'#fff') ?>">
            <?= e($os['status_nome']??'') ?>
          </span>
        </td>
        <td><?= e($os['tecnico_nome'] ?? '—') ?></td>
        <td><?= $os['data_entrada'] ? date('d/m/Y', strtotime($os['data_entrada'])) : '—' ?></td>
        <td><?= $os['data_conclusao'] ? date('d/m/Y', strtotime($os['data_conclusao'])) : '—' ?></td>
        <td style="text-align:right;font-weight:bold">R$ <?= number_format($os['valor_total'],2,',','.') ?></td>
        <td style="text-align:center">
          <span class="situacao-<?= $os['situacao_pagamento'] ?>">
            <?= $os['situacao_pagamento'] === 'pago' ? '✓ Pago' : '✗ Pend.' ?>
          </span>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr class="total-row">
        <td colspan="7" style="text-align:right;font-size:11px">TOTAL</td>
        <td style="text-align:right;font-size:13px">R$ <?= number_format($faturado,2,',','.') ?></td>
        <td></td>
      </tr>
    </tfoot>
  </table>
  <?php else: ?>
  <div style="text-align:center;padding:40px;color:#94a3b8">
    <div style="font-size:32px;margin-bottom:8px">📋</div>
    Nenhuma OS encontrada para o período e status selecionados.
  </div>
  <?php endif; ?>

  <div style="margin-top:16px;font-size:9px;color:#94a3b8;text-align:center;border-top:1px solid #e2e8f0;padding-top:8px">
    <?= e($empresa['nome_fantasia'] ?? '') ?> · Relatório gerado em <?= date('d/m/Y \à\s H:i') ?> · FixaOS
  </div>

</div>
</body>
</html>
