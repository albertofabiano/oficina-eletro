<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Etiqueta interna — OS <?= e($os['numero'] ?? '') ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 12.5px; color:#000; background:#525659; }

.section-title { font-weight:bold; font-size:11.5px; background:#1a1d23; color:#fff; padding:2px 8px; margin:4px 0 2px; text-transform:uppercase; letter-spacing:.06em; }
.row { display:flex; gap:10px; margin-bottom:4px; }
.col { flex:1; }
.info-box { border:2px solid #ccc; border-radius:4px; padding:4px 9px; font-size:12px; line-height:1.4; }
.info-label { font-size:10.5px; color:#777; text-transform:uppercase; letter-spacing:.05em; font-weight:bold; }

.folhas { padding: 22px 12px; }
.folha { background:#fff; width:210mm; min-height:297mm; box-sizing:border-box; margin:0 auto; padding:15mm 14mm; box-shadow:0 6px 24px rgba(0,0,0,.45); }

@media print {
  .no-print { display:none !important; }
  body { background:#fff; margin:0; padding:0; }
  .folhas { padding:0; }
  .folha { width:auto; min-height:0; margin:0; padding:5mm 8mm; box-shadow:none; }
  @page { margin:0; size:A4; }
}
</style>
</head>
<body>

<!-- Barra (não imprime) -->
<div class="no-print" style="background:#f0f2f5;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dee2e6">
  <button onclick="window.print()" style="background:#1a1d23;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600">🖨 Imprimir / Salvar PDF</button>
  <a href="<?= url('/os/' . $os['id']) ?>" style="background:#e2e8f0;color:#1a1d23;text-decoration:none;padding:7px 18px;border-radius:6px;font-size:15px;font-weight:600">← Voltar para a OS</a>
  <span style="margin-left:auto;font-size:12.5px;color:#888">Etiqueta interna — OS <?= e($os['numero']) ?></span>
</div>

<div class="folhas">
  <div class="folha">

    <!-- Etiquetas — faixa do TOPO (recortar para colar nos equipamentos) -->
    <?php
      $primeiroNome = explode(' ', trim($os['cliente_nome'] ?? ''))[0];
      $telefone     = $os['cliente_whats'] ?? $os['cliente_tel'] ?? '';
      $marcaModelo  = trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''));
      $ehGarantia   = !empty($os['os_origem_id']);
      $osOrigemNum  = $os['os_origem_numero'] ?? $os['os_origem_id'] ?? '';
    ?>
    <div style="font-size:10.5px;color:#888;margin-bottom:5px;text-transform:uppercase;letter-spacing:.06em">Etiquetas de identificação<?php if ($ehGarantia): ?> — <span style="color:#dc3545;font-weight:bold">GARANTIA</span><?php endif; ?> (recortar)</div>
    <div style="display:flex;gap:8px">
      <?php for ($i = 0; $i < 4; $i++): ?>
      <div style="border:2px solid <?= $ehGarantia ? '#dc3545' : '#1a1d23' ?>;border-radius:6px;padding:5px 9px;flex:1;background:#fff;page-break-inside:avoid;min-width:0">
        <?php if ($ehGarantia): ?><div style="font-size:9px;font-weight:900;color:#dc3545;letter-spacing:.03em">GARANTIA<?php if ($osOrigemNum): ?> · orig OS <?= e($osOrigemNum) ?><?php endif; ?></div><?php endif; ?>
        <div style="font-size:15px;font-weight:900;color:#1a1d23;letter-spacing:.02em">OS <?= e($os['numero']) ?></div>
        <div style="font-size:11.5px;font-weight:bold;margin-top:2px;color:#222"><?= e($primeiroNome) ?></div>
        <?php if ($telefone): ?><div style="font-size:10.5px;color:#555;margin-top:1px"><?= e($telefone) ?></div><?php endif; ?>
        <div style="font-size:10.5px;color:#333;margin-top:2px;font-weight:bold"><?= e($marcaModelo) ?></div>
        <div style="font-size:10px;color:#777;margin-top:1px"><?= e($os['equip_tipo'] ?? '') ?></div>
      </div>
      <?php endfor; ?>
    </div>

    <!-- Linha de corte -->
    <div style="border-top:2px dashed #999;margin:10px 0 2px"></div>
    <div style="text-align:center;font-size:9px;color:#999;letter-spacing:.15em;margin-bottom:8px">RECORTE AQUI</div>

    <!-- Cabeçalho empresa (compacto) -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #1a1d23;padding-bottom:5px;margin-bottom:6px">
      <div>
        <div style="font-size:15px;font-weight:bold;color:#1a1d23"><?= e($os['empresa_nome']) ?></div>
        <?php if (!empty($os['emp_tel'])): ?><div style="font-size:11.5px;color:#555">Tel: <?= e($os['emp_tel']) ?><?php if (!empty($os['emp_whatsapp'])): ?> | WhatsApp: <?= e($os['emp_whatsapp']) ?><?php endif; ?></div><?php endif; ?>
      </div>
      <div style="text-align:right">
        <div style="font-size:23px;font-weight:900;border:2px solid <?= $ehGarantia ? '#dc3545' : '#1a1d23' ?>;padding:2px 10px;color:<?= $ehGarantia ? '#dc3545' : '#1a1d23' ?>">OS <?= e($os['numero']) ?></div>
        <?php if ($ehGarantia): ?><div style="font-size:12px;font-weight:900;color:#dc3545;margin-top:2px">RETORNO EM GARANTIA<?php if ($osOrigemNum): ?> — OS <?= e($osOrigemNum) ?><?php endif; ?></div><?php endif; ?>
        <div style="font-size:11px;color:#444;margin-top:2px">Entrada: <?= date_br($os['data_entrada'], true) ?> | Previsão: <?= date_br($os['data_previsao'], true) ?: '—' ?></div>
      </div>
    </div>

    <!-- Cliente + Equipamento -->
    <div class="row">
      <div class="col">
        <div class="section-title">Cliente</div>
        <div class="info-box">
          <div style="font-weight:bold;font-size:14px"><?= e($os['cliente_nome']) ?></div>
          <?php if (!empty($os['cliente_contato'])): ?><div style="font-size:12px"><b>Contato:</b> <?= e($os['cliente_contato']) ?></div><?php endif; ?>
          <?php if ($os['cliente_whats'] ?? $os['cliente_tel'] ?? null): ?><div><span class="info-label">Tel:</span> <?= e($os['cliente_whats'] ?? $os['cliente_tel']) ?></div><?php endif; ?>
          <?php
            $cliEnd = trim(($os['cli_logradouro'] ?? '') . (!empty($os['cli_numero']) ? ', ' . $os['cli_numero'] : ''));
            if (!empty($os['cli_complemento'])) $cliEnd .= ' - ' . $os['cli_complemento'];
            $cliEnd2 = trim(implode(' - ', array_filter([$os['cli_bairro'] ?? '', trim(($os['cli_cidade'] ?? '') . (!empty($os['cli_uf']) ? '/' . $os['cli_uf'] : ''))])));
          ?>
          <?php if ($cliEnd || $cliEnd2 || !empty($os['cli_cep'])): ?>
          <div style="font-size:11.5px">
            <span class="info-label">End.:</span>
            <?= e($cliEnd) ?><?= $cliEnd && $cliEnd2 ? ' - ' : '' ?><?= e($cliEnd2) ?><?= !empty($os['cli_cep']) ? ' - CEP ' . e($os['cli_cep']) : '' ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="col">
        <div class="section-title">Equipamento</div>
        <div class="info-box">
          <div style="font-weight:bold;font-size:14px"><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></div>
          <div><?= e($os['equip_tipo'] ?? '') ?></div>
          <?php if ($os['numero_serie'] ?? null): ?><div><span class="info-label">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
          <?php if (!empty($os['imei'])): ?><div><span class="info-label">IMEI:</span> <?= e($os['imei']) ?></div><?php endif; ?>
          <?php if ($os['equip_cor'] ?? null): ?><div><span class="info-label">Cor:</span> <?= e($os['equip_cor']) ?></div><?php endif; ?>
          <?php if ($os['processador'] ?? null): ?><div><span class="info-label">Processador:</span> <?= e($os['processador']) ?></div><?php endif; ?>
          <?php if ($os['memoria_ram'] ?? null): ?><div><span class="info-label">Memória:</span> <?= e($os['memoria_ram']) ?></div><?php endif; ?>
          <?php if ($os['tipo_armazenamento'] ?? null): ?><div><span class="info-label">Armazenamento:</span> <?= e($os['tipo_armazenamento']) ?></div><?php endif; ?>
          <?php if ($os['placa_video'] ?? null): ?><div><span class="info-label">Placa de vídeo:</span> <?= e($os['placa_video']) ?></div><?php endif; ?>
          <?php if ($os['placa_mae'] ?? null): ?><div><span class="info-label">Placa mãe:</span> <?= e($os['placa_mae']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Defeito -->
    <div class="section-title">Defeito relatado pelo cliente</div>
    <div class="info-box" style="min-height:20px"><?= nl2br(e($os['defeito_relatado'] ?? '')) ?></div>

    <?php if (!empty($os['acessorios'])): ?>
    <div class="section-title">Acessórios</div>
    <div class="info-box"><?= e($os['acessorios']) ?></div>
    <?php endif; ?>

    <?php if ($os['tecnico_nome'] ?? null): ?>
    <div style="margin-top:6px;font-size:11px;text-align:right;color:#555">
      <strong>Técnico:</strong> <?= e($os['tecnico_nome']) ?> &nbsp;|&nbsp; <strong>Garantia:</strong> <?= $os['garantia_dias'] ?? 90 ?> dias após entrega
    </div>
    <?php endif; ?>

    <!-- Área de preenchimento do técnico (uso interno, à mão) -->
    <div class="section-title" style="margin-top:6px">Serviços</div>
    <div class="info-box" style="padding:1px 8px 0"><?= str_repeat('<div style="border-bottom:1px solid #bbb;height:21px;margin-bottom:5px"></div>', 2) ?></div>

    <div class="section-title">Peças / Placas</div>
    <div class="info-box" style="padding:1px 8px 0"><?= str_repeat('<div style="border-bottom:1px solid #bbb;height:21px;margin-bottom:5px"></div>', 2) ?></div>

  </div>
</div>
</body>
</html>
