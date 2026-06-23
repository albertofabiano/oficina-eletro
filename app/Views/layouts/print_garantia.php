<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Comprovante de Garantia — <?= e($os['numero'] ?? '') ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 11px; color: #000; background:#fff; }

.header {
  display:flex; justify-content:space-between; align-items:flex-start;
  border-bottom: 3px solid #6f42c1; padding-bottom: 10px; margin-bottom: 14px; gap:16px;
}
.logo-empresa    { max-width:160px; max-height:65px; object-fit:contain; }
.logo-inicial    { width:52px;height:52px;border-radius:8px;background:#6f42c1;color:#fff;
                   display:flex;align-items:center;justify-content:center;font-size:22px;font-weight:bold;flex-shrink:0; }
.empresa-nome    { font-size:15px;font-weight:bold;color:#6f42c1; }
.empresa-detalhe { font-size:10px;color:#555;line-height:1.5; }
.header-dir      { text-align:right;flex-shrink:0; }
.titulo-doc      { font-size:16px;font-weight:900;color:#6f42c1;border:2px solid #6f42c1;
                   padding:4px 12px;display:inline-block;margin-bottom:4px; }
.doc-info        { font-size:10px;color:#555;line-height:1.7; }

.section-title {
  font-weight:bold;font-size:10px;padding:3px 8px;margin:10px 0 5px;
  text-transform:uppercase;letter-spacing:.06em;
  background:#6f42c1;color:#fff;
}
.info-box   { border:1px solid #ccc;border-radius:4px;padding:7px 10px;margin-bottom:7px;font-size:10.5px;line-height:1.65; }
.info-label { font-size:9px;color:#777;text-transform:uppercase;letter-spacing:.05em;font-weight:bold; }
.row        { display:flex;gap:10px; }
.col        { flex:1; }

/* Garantia original destacada */
.garantia-origem {
  border:1px dashed #6f42c1;border-radius:6px;padding:8px 12px;
  background:#f8f0ff;margin-bottom:10px;
}
.garantia-origem-titulo { font-size:10px;color:#6f42c1;font-weight:bold;margin-bottom:4px; }

/* Checklist de integridade */
.checklist { display:grid;grid-template-columns:1fr 1fr;gap:4px;margin-top:6px; }
.check-item { display:flex;align-items:center;gap:6px;font-size:10.5px; }
.check-box  { width:14px;height:14px;border:1px solid #666;border-radius:2px;flex-shrink:0; }

/* Termos de garantia */
.termos { border:1px solid #ccc;border-radius:4px;padding:8px 12px;font-size:9.5px;color:#555;line-height:1.6; }

/* Assinaturas */
.assinaturas { display:flex;justify-content:space-around;margin-top:32px; }
.assinatura-linha { border-top:1px solid #000;width:220px;padding-top:4px;font-size:10px;text-align:center;margin:0 auto; }
.rodape { margin-top:14px;font-size:9.5px;color:#555;border-top:1px solid #ccc;padding-top:8px; }

@media print {
  .no-print { display:none!important; }
  @page { margin:12mm 10mm; }
}
</style>
</head>
<body>

<div class="no-print" style="background:#f5f0ff;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:2px solid #6f42c1">
  <button onclick="window.print()" style="background:#6f42c1;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:13px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <a href="<?= url('/os/' . $os['id']) ?>" style="color:#6f42c1;text-decoration:none;font-size:12px;font-weight:600">← Ver OS</a>
  <a href="<?= url('/os') ?>" style="color:#555;text-decoration:none;font-size:12px">← Lista de OS</a>
  <span style="margin-left:auto;font-size:11px;color:#555">OS de Garantia <?= e($os['numero']) ?></span>
</div>

<div style="max-width:820px;margin:0 auto;padding:16px 14px">

  <!-- CABEÇALHO -->
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
      <div class="titulo-doc">RETORNO EM GARANTIA</div>
      <div style="font-size:13px;font-weight:bold;color:#333;margin:4px 0">OS <?= e($os['numero']) ?></div>
      <div class="doc-info">
        <div><strong>Data de entrada:</strong> <?= date_br($os['data_entrada'], true) ?></div>
        <div><strong>Previsão:</strong> <?= date_br($os['data_previsao'], true) ?: '—' ?></div>
        <?php if ($os['tecnico_nome'] ?? null): ?>
        <div><strong>Técnico:</strong> <?= e($os['tecnico_nome']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- OS ORIGINAL DE GARANTIA -->
  <?php if (!empty($osOrigem)): ?>
  <div class="garantia-origem">
    <div class="garantia-origem-titulo">📋 Vinculada à OS Original</div>
    <div style="display:flex;justify-content:space-between;align-items:center">
      <div>
        <strong>OS <?= e($osOrigem['numero']) ?></strong>
        — Concluída em <?= date_br($osOrigem['data_conclusao'] ?? '') ?>
        — Solução: <?= e(mb_substr($osOrigem['solucao_aplicada'] ?? 'Ver OS original', 0, 80)) ?>
      </div>
      <div style="text-align:right;flex-shrink:0">
        <div style="color:#6f42c1;font-weight:bold">Garantia de <?= $osOrigem['garantia_dias'] ?> dias</div>
        <div style="font-size:10px;color:#555">Válida até <?= date_br($osOrigem['garantia_ate'] ?? '') ?></div>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <!-- CLIENTE E EQUIPAMENTO -->
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
        <?php if ($os['voltagem']??null): ?><div><span class="info-label">Voltagem:</span> <?= e($os['voltagem']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- DEFEITO RELATADO NO RETORNO -->
  <div class="section-title">Defeito Relatado no Retorno</div>
  <div class="info-box" style="min-height:30px"><?= nl2br(e($os['defeito_relatado']??'')) ?></div>

  <!-- INTEGRIDADE DO EQUIPAMENTO NA ENTRADA -->
  <div class="section-title">Revisão do Equipamento na Entrada</div>
  <div class="info-box">
    <div class="row" style="gap:16px">
      <div class="col">
        <div><span class="info-label">Estado de entrada:</span> <strong><?= ucfirst($os['estado_entrada']??'') ?></strong></div>
        <?php if ($os['acessorios']??null): ?>
        <div style="margin-top:4px"><span class="info-label">Acessórios entregues:</span> <?= e($os['acessorios']) ?></div>
        <?php endif; ?>
      </div>
      <div class="col">
        <div class="info-label" style="margin-bottom:4px">Checklist visual:</div>
        <div class="checklist">
          <?php foreach (['Tela sem trincas','Carcaça íntegra','Botões funcionando','Conectores OK','Sem líquido','Acessórios conferidos'] as $item): ?>
          <div class="check-item"><div class="check-box"></div><?= $item ?></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </div>

  <?php if ($os['observacoes_cliente']??null): ?>
  <div class="section-title">Observações</div>
  <div class="info-box"><?= nl2br(e($os['observacoes_cliente'])) ?></div>
  <?php endif; ?>

  <!-- TERMOS DE GARANTIA -->
  <div class="section-title">Termos de Garantia</div>
  <div class="termos">
    O equipamento está sendo aceito para análise em retorno de garantia referente ao serviço realizado na
    <strong>OS #<?= e($osOrigem['numero'] ?? $os['os_origem_id'] ?? '—') ?></strong>.
    A garantia cobre exclusivamente o defeito anteriormente reparado, nas mesmas condições de uso.
    <strong>Não estão cobertos:</strong> danos físicos causados após a entrega, infiltração de líquidos,
    mau uso, quedas, sobrecargas elétricas, ou defeitos não relacionados ao reparo original.
    O prazo para diagnóstico e retorno é de até <strong>15 dias úteis</strong> a partir da data de entrada.
    Equipamentos não retirados em até <?= $os['garantia_dias'] ?? 30 ?> dias após o aviso serão cobrados taxa de armazenagem.
  </div>

  <!-- RODAPÉ -->
  <div class="rodape">
    <div style="display:flex;justify-content:space-between">
      <span>Emitido em <?= date('d/m/Y \à\s H:i') ?> — <?= e($os['empresa_nome']) ?></span>
      <span><?php if (!empty($os['emp_tel'])): ?>Tel: <?= e($os['emp_tel']) ?><?php endif; ?></span>
    </div>
  </div>

  <!-- ASSINATURAS -->
  <div class="assinaturas" style="margin-top:28px">
    <div class="assinatura">
      <div class="assinatura-linha">Assinatura do Cliente</div>
      <div style="font-size:9px;text-align:center;margin-top:2px;color:#555">Declaro que entreguei o equipamento descrito acima</div>
    </div>
    <div class="assinatura">
      <div class="assinatura-linha"><?= e($os['empresa_nome']) ?></div>
      <div style="font-size:9px;text-align:center;margin-top:2px;color:#555">Responsável pelo recebimento</div>
    </div>
  </div>

</div>
</body>
</html>
