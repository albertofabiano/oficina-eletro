<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante de Entrada — OS <?= e($os['numero'] ?? '') ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
@page { margin: 0; size: A4; }
body { font-family: Arial, sans-serif; font-size: 12.5px; color:#111; background:#fff; padding: 9mm 12mm; }
.wrap { margin: 0 auto; }

.header { display:flex; justify-content:space-between; align-items:flex-start; border-bottom:2px solid #1a1d23; padding-bottom:10px; margin-bottom:12px; gap:16px; }
.header-esq { display:flex; align-items:center; gap:14px; }
.logo-empresa { max-width:150px; max-height:64px; object-fit:contain; }
.logo-ph { width:50px; height:50px; border-radius:8px; background:#1a1d23; color:#fff; font-size:24px; font-weight:bold; text-align:center; line-height:50px; }
.emp-nome { font-size:16px; font-weight:bold; color:#1a1d23; }
.emp-det  { font-size:11px; color:#555; line-height:1.5; }
.os-numero { font-size:23px; font-weight:900; letter-spacing:.04em; border:2px solid #1a1d23; padding:3px 11px; display:inline-block; color:#1a1d23; }
.os-datas { font-size:11px; color:#444; line-height:1.6; margin-top:3px; }

.banner { background:#dcfce7; border:1.5px solid #16a34a; border-radius:8px; padding:8px 12px; margin-bottom:9px; }
.banner b { color:#15803d; font-size:14px; }
.banner .sub { color:#166534; font-size:11.5px; margin-top:2px; }

.sec { font-weight:bold; font-size:11px; background:#1a1d23; color:#fff; padding:3px 8px; margin:6px 0 3px; text-transform:uppercase; letter-spacing:.06em; }
.row { display:flex; gap:10px; margin-bottom:5px; }
.col { flex:1; }
.box { border:1.5px solid #ccc; border-radius:4px; padding:5px 9px; font-size:12px; line-height:1.5; }
.lbl { font-size:10px; color:#777; text-transform:uppercase; letter-spacing:.05em; font-weight:bold; }

.termos { border:1px solid #bbb; border-radius:4px; padding:6px 11px; font-size:10.5px; line-height:1.45; color:#333; margin-top:3px; }
.termos p { margin:1px 0; }

.track { background:#eff6ff; border:1.5px solid #3b82f6; border-radius:8px; padding:8px 12px; margin-top:10px; }
.track b { color:#1d4ed8; }
.track a { color:#1d4ed8; word-break:break-all; }

.rodape { margin-top:7px; border-top:1.5px dashed #bbb; padding-top:6px; font-size:10.5px; color:#666; line-height:1.45; text-align:center; }
</style>
</head>
<body>
<div class="wrap">

  <!-- Cabeçalho -->
  <div class="header">
    <div class="header-esq">
      <?php if (!empty($os['emp_logo'])): ?>
        <img src="<?= url('/uploads/' . e($os['emp_logo'])) ?>" alt="Logo" class="logo-empresa">
      <?php else: ?>
        <div class="logo-ph"><?= mb_strtoupper(mb_substr($os['empresa_nome'] ?? 'O', 0, 1)) ?></div>
      <?php endif; ?>
      <div>
        <div class="emp-nome"><?= e($os['empresa_nome']) ?></div>
        <?php if (!empty($os['emp_tel'])): ?><div class="emp-det">Tel: <?= e($os['emp_tel']) ?><?php if (!empty($os['emp_whatsapp'])): ?> · WhatsApp: <?= e($os['emp_whatsapp']) ?><?php endif; ?></div><?php endif; ?>
        <?php if (!empty($os['emp_email'])): ?><div class="emp-det"><?= e($os['emp_email']) ?></div><?php endif; ?>
      </div>
    </div>
    <div style="text-align:right">
      <div class="os-numero">OS <?= e($os['numero']) ?></div>
      <div class="os-datas">
        <div><strong>Entrada:</strong> <?= date_br($os['data_entrada'], true) ?></div>
        <div><strong>Previsão:</strong> <?= date_br($os['data_previsao'], true) ?: 'a definir' ?></div>
      </div>
    </div>
  </div>

  <!-- Banner: recebido para orçamento -->
  <div class="banner">
    <b>Equipamento recebido para orçamento</b>
    <div class="sub">Olá <?= e(($os['cliente_contato'] ?? '') ?: explode(' ', trim($os['cliente_nome'] ?? ''))[0]) ?>! Recebemos seu equipamento e ele já está na fila para avaliação. Assim que o orçamento estiver pronto, avisamos você.</div>
  </div>

  <!-- Cliente + Equipamento -->
  <div class="row">
    <div class="col">
      <div class="sec">Cliente</div>
      <div class="box">
        <div style="font-weight:bold;font-size:13px"><?= e($os['cliente_nome']) ?></div>
        <?php if ($os['cliente_whats'] ?? $os['cliente_tel'] ?? null): ?><div><span class="lbl">Contato:</span> <?= e($os['cliente_whats'] ?? $os['cliente_tel']) ?></div><?php endif; ?>
        <?php if ($os['cpf_cnpj'] ?? null): ?><div><span class="lbl">CPF/CNPJ:</span> <?= e(doc_mask($os['cpf_cnpj'])) ?></div><?php endif; ?>
        <?php
          $endCliWa = array_filter([
            $os['cli_logradouro'] ?? '',
            ($os['cli_numero'] ?? '') ? 'nº '.$os['cli_numero'] : '',
            $os['cli_complemento'] ?? '',
            $os['cli_bairro'] ?? '',
          ]);
          $cidCliWa = trim(($os['cli_cidade'] ?? '').(isset($os['cli_uf']) ? '/'.$os['cli_uf'] : ''));
        ?>
        <?php if ($endCliWa): ?>
        <div style="margin-top:3px"><span class="lbl">Endereço:</span>
          <?= e(implode(', ', $endCliWa)) ?><?= $cidCliWa ? ' — '.e($cidCliWa) : '' ?>
          <?php if ($os['cli_cep'] ?? null): ?> — CEP <?= e($os['cli_cep']) ?><?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="col">
      <div class="sec">Equipamento</div>
      <div class="box">
        <div style="font-weight:bold;font-size:13px"><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></div>
        <div><?= e($os['equip_tipo'] ?? '') ?><?= ($os['equip_cor'] ?? null) ? ' · ' . e($os['equip_cor']) : '' ?></div>
        <?php if ($os['numero_serie'] ?? null): ?><div><span class="lbl">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
        <?php if (!empty($os['imei'])): ?><div><span class="lbl">IMEI:</span> <?= e($os['imei']) ?></div><?php endif; ?>
        <div><span class="lbl">Estado de entrada:</span> <?= ucfirst($os['estado_entrada'] ?? '—') ?></div>
      </div>
    </div>
  </div>

  <!-- Defeito -->
  <div class="sec">Defeito relatado</div>
  <div class="box" style="min-height:28px"><?= nl2br(e($os['defeito_relatado'] ?? '')) ?></div>

  <div class="sec">Acessórios entregues</div>
  <div class="box" style="min-height:60px"><?= e($os['acessorios'] ?? '') ?></div>

  <!-- Termos de entrada -->
  <?php $textoEntrada = $configs['texto_entrada_equipamento'] ?? ''; ?>
  <?php if ($textoEntrada): ?>
  <div class="sec" style="margin-top:10px">Informações de entrada</div>
  <div class="termos"><?= $textoEntrada ?></div>
  <?php endif; ?>

  <!-- Link de acompanhamento -->
  <?php if (!empty($os['token_publico'])): $linkAcomp = url('/os/acompanhar/' . $os['token_publico']); ?>
  <div class="track">
    <b>Acompanhe sua OS em tempo real</b>
    <div style="margin-top:2px">Veja o andamento, o orçamento e o valor pelo link (sem precisar de senha):</div>
    <div style="margin-top:3px"><a href="<?= $linkAcomp ?>"><?= $linkAcomp ?></a></div>
  </div>
  <?php endif; ?>

  <!-- Rodapé digital -->
  <div class="rodape">
    Comprovante digital de entrada · OS <?= e($os['numero']) ?> · <?= date_br($os['data_entrada'], true) ?><br>
    <?= e($os['empresa_nome']) ?><?php if (!empty($os['emp_tel'])): ?> · <?= e($os['emp_tel']) ?><?php endif; ?><br>
    <span style="color:#999">Documento enviado por WhatsApp — não requer assinatura. Guarde este comprovante para a retirada do equipamento.</span>
  </div>

</div>
</body>
</html>
