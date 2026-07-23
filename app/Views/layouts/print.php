<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title><?= e($os['numero'] ?? '') ?> — <?= e($os['empresa_nome'] ?? '') ?></title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; font-size: 12.5px; color: #000; background:#525659; }

.header {
  display: flex; justify-content: space-between; align-items: flex-start;
  border-bottom: 2px solid #1a1d23; padding-bottom: 10px; margin-bottom: 12px; gap: 16px;
}
.header-esquerda { display: flex; align-items: center; gap: 14px; flex: 1; }
.logo-empresa    { max-width: 160px; max-height: 70px; object-fit: contain; }
.logo-placeholder {
  width: 52px; height: 52px; border-radius: 8px;
  background: #1a1d23; display: flex; align-items: center;
  justify-content: center; color: #fff; font-size: 25.5px; font-weight: bold; flex-shrink: 0;
}
.empresa-info    { line-height: 1.5; }
.empresa-nome    { font-size: 17px; font-weight: bold; color: #1a1d23; }
.empresa-detalhe { font-size: 11.5px; color: #555; }

.header-direita  { text-align: right; flex-shrink: 0; }
.os-numero {
  font-size: 25.5px; font-weight: 900; letter-spacing: .04em;
  border: 2px solid #1a1d23; padding: 4px 12px;
  display: inline-block; margin-bottom: 4px; color: #1a1d23;
}
.os-datas { font-size: 11.5px; color: #444; line-height: 1.7; }

.section-title {
  font-weight: bold; font-size: 11.5px; background: #1a1d23;
  color: #fff; padding: 3px 8px; margin: 8px 0 4px;
  text-transform: uppercase; letter-spacing: .06em;
}
.row { display: flex; gap: 10px; margin-bottom: 6px; align-items: stretch; }
.col { flex: 1; display: flex; flex-direction: column; }

table { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
th, td { border: 2px solid #ccc; padding: 4px 7px; text-align: left; vertical-align: top; }
th { background: #f0f0f0; font-weight: bold; font-size: 11.5px; }
td { font-size: 12px; }

.info-box {
  border: 2px solid #ccc; border-radius: 4px;
  padding: 6px 10px; margin-bottom: 6px; font-size: 12px; line-height: 1.6;
  flex: 1;
}
.info-label { font-size: 10.5px; color: #777; text-transform: uppercase; letter-spacing: .05em; font-weight: bold; }

.rodape { margin-top: 20px; font-size: 11px; color: #555; border-top: 2px solid #ccc; padding-top: 8px; }
.assinaturas { display: flex; justify-content: space-around; margin-top: 30px; }
.assinatura-linha { border-top: 2px solid #000; width: 220px; padding-top: 4px; font-size: 11.5px; margin: 0 auto; text-align: center; }

.texto-entrada {
  border: 1px solid #aaa;
  border-radius: 4px;
  padding: 8px 12px;
  font-size: 11.5px;
  line-height: 1.7;
  color: #333;
  margin-top: 6px;
}
.texto-entrada h3 { font-size: 12px; font-weight: bold; margin: 4px 0 2px; }
.texto-entrada ul, .texto-entrada ol { padding-left: 1.2rem; margin: 2px 0; }
.texto-entrada p { margin: 2px 0; }

.page-break { page-break-after: always; break-after: page; }

/* Pré-visualização estilo A4: cada "folha" é uma página branca sobre o fundo cinza */
.folhas { padding: 22px 12px; }
.folha {
  background:#fff; width:210mm; min-height:297mm; box-sizing:border-box;
  margin:0 auto 22px; padding:15mm 14mm;
  box-shadow:0 6px 24px rgba(0,0,0,.45);
}
.folha:last-child { margin-bottom:0; }

@media print {
  .no-print { display: none !important; }
  body { margin: 0; padding: 0; background:#fff; }
  .folhas { padding: 0; }
  .folha { width: auto; min-height: 0; margin: 0; padding: 10mm; box-shadow: none; }
  .folha + .folha { page-break-before: always; }
  @page { margin: 0; size: A4; }
  .page-break { page-break-after: always; break-after: page; }
}
</style>
</head>
<body>

<?php
$waNum  = only_numbers($os['cliente_whats'] ?? $os['cliente_tel'] ?? '');
$waMsg  = urlencode("Olá *{$os['cliente_nome']}*! 📋\nSegue o comprovante de entrada da OS *{$os['numero']}*.\nAcesse: " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']==='on' ? 'https' : 'http') . "://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}");?>
<div class="no-print" style="background:#f0f2f5;padding:10px 16px;display:flex;align-items:center;gap:10px;border-bottom:1px solid #dee2e6;flex-wrap:wrap">
  <button onclick="window.print()" style="background:#1a1d23;color:#fff;border:none;padding:7px 20px;border-radius:6px;cursor:pointer;font-size:15px;font-weight:600">
    🖨 Imprimir / Salvar PDF
  </button>
  <?php $_waTipo = 'abertura'; include __DIR__ . '/_botao_wa_pdf.php'; ?>
  <a href="<?= url('/os/' . $os['id']) ?>" style="background:#e2e8f0;color:#1a1d23;text-decoration:none;padding:7px 18px;border-radius:6px;font-size:15px;font-weight:600;display:flex;align-items:center;gap:6px">← Voltar para a OS</a>
  <span style="margin-left:auto;font-size:12.5px;color:#888">OS <?= e($os['numero']) ?></span>
</div>

<div class="folhas">
  <div class="folha">

  <!-- CABEÇALHO -->
  <div class="header">
    <div class="header-esquerda">
      <?php if (!empty($os['emp_logo'])): ?>
        <img src="<?= url('/uploads/' . e($os['emp_logo'])) ?>" alt="Logo" class="logo-empresa">
      <?php else: ?>
        <div class="logo-placeholder"><?= mb_strtoupper(mb_substr($os['empresa_nome'] ?? 'O', 0, 1)) ?></div>
      <?php endif; ?>
      <div class="empresa-info">
        <div class="empresa-nome"><?= e($os['empresa_nome']) ?></div>
        <?php if (!empty($os['empresa_cnpj'])): ?>
        <div class="empresa-detalhe">CNPJ: <?= e($os['empresa_cnpj']) ?></div>
        <?php endif; ?>
        <?php
          $end = array_filter([$os['emp_logradouro']??'', ($os['emp_numero']??'') ? 'nº '.$os['emp_numero'] : '', $os['emp_complemento']??'', $os['emp_bairro']??'']);
          $cuf = trim(($os['emp_cidade']??'').(isset($os['emp_uf']) ? '/'.$os['emp_uf'] : ''));
        ?>
        <?php if ($end): ?>
        <div class="empresa-detalhe"><?= e(implode(', ', $end)) ?><?= $cuf ? ' — '.e($cuf) : '' ?></div>
        <?php endif; ?>
        <?php if (!empty($os['emp_tel'])): ?>
        <div class="empresa-detalhe">Tel: <?= e($os['emp_tel']) ?><?php if (!empty($os['emp_whatsapp'])): ?> | WhatsApp: <?= e($os['emp_whatsapp']) ?><?php endif; ?></div>
        <?php endif; ?>
        <?php if (!empty($os['emp_email'])): ?>
        <div class="empresa-detalhe"><?= e($os['emp_email']) ?></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="header-direita">
      <div class="os-numero">OS <?= e($os['numero']) ?></div>
      <div class="os-datas">
        <div><strong>Entrada:</strong> <?= date_br($os['data_entrada'], true) ?></div>
        <div><strong>Previsão:</strong> <?= date_br($os['data_previsao'], true) ?: '—' ?></div>
        <?php if ($os['tecnico_nome'] ?? null): ?>
        <div><strong>Técnico:</strong> <?= e($os['tecnico_nome']) ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- CLIENTE E EQUIPAMENTO -->
  <div class="row">
    <div class="col">
      <div class="section-title">Cliente</div>
      <div class="info-box">
        <div class="info-label">Nome</div>
        <div style="font-weight:bold;font-size:14px"><?= e($os['cliente_nome']) ?></div><?php if (!empty($os['cliente_contato'])): ?><div style="font-size:12px"><b>Contato:</b> <?= e($os['cliente_contato']) ?></div><?php endif; ?>
        <?php if ($os['cpf_cnpj'] ?? null): ?><div><span class="info-label">CPF/CNPJ:</span> <?= e(doc_mask($os['cpf_cnpj'])) ?></div><?php endif; ?>
        <?php if ($os['cliente_tel'] ?? null): ?><div><span class="info-label">Tel:</span> <?= e($os['cliente_tel']) ?></div><?php endif; ?>
        <?php if ($os['cliente_whats'] ?? null): ?><div><span class="info-label">WhatsApp:</span> <?= e($os['cliente_whats']) ?></div><?php endif; ?>
        <?php if ($os['cliente_email'] ?? null): ?><div><span class="info-label">E-mail:</span> <?= e($os['cliente_email']) ?></div><?php endif; ?>
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
        <div style="font-weight:bold;font-size:14px"><?= e(trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''))) ?></div>
        <div><?= e($os['equip_tipo'] ?? '') ?></div>
        <?php if ($os['numero_serie'] ?? null): ?><div><span class="info-label">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
        <?php if ($os['equip_cor'] ?? null): ?><div><span class="info-label">Cor:</span> <?= e($os['equip_cor']) ?></div><?php endif; ?>
        <?php if ($os['voltagem'] ?? null): ?><div><span class="info-label">Voltagem:</span> <?= e($os['voltagem']) ?></div><?php endif; ?>
        <div><span class="info-label">Estado de entrada:</span> <?= ucfirst($os['estado_entrada'] ?? '') ?></div>
        <?php if ($os['senha_desbloqueio'] ?? null): ?><div><span class="info-label">Senha:</span> <?= e($os['senha_desbloqueio']) ?></div><?php endif; ?>
      </div>
    </div>
  </div>

  <!-- DEFEITO -->
  <div class="section-title">Defeito relatado pelo cliente</div>
  <div class="info-box" style="min-height:32px"><?= nl2br(e($os['defeito_relatado'] ?? '')) ?></div>

  <div class="section-title">Acessórios</div>
  <div class="info-box" style="min-height:60px"><?= e($os['acessorios'] ?? '') ?></div>

  <?php if (!empty($os['observacoes_cliente'])): ?>
  <div class="section-title">Observações</div>
  <div class="info-box"><?= nl2br(e($os['observacoes_cliente'])) ?></div>
  <?php endif; ?>

  <!-- TEXTO DE INFORMAÇÕES DE ENTRADA -->
  <?php $textoEntrada = $configs['texto_entrada_equipamento'] ?? ''; ?>
  <?php if ($textoEntrada): ?>
  <div class="section-title" style="margin-top:10px">Informações de entrada de equipamento</div>
  <div class="texto-entrada"><?= $textoEntrada ?></div>
  <?php endif; ?>

  <!-- SERVIÇOS E PEÇAS -->
  <?php
    $totalServicos = array_sum(array_column($os['servicos'] ?? [], 'valor_total'));
    $totalPecas    = array_sum(array_column($os['pecas'] ?? [], 'valor_total'));
    $totalGeral    = $os['valor_total'] ?? ($totalServicos + $totalPecas);
    $temItens      = !empty($os['servicos']) || !empty($os['pecas']);
  ?>
  <?php if ($temItens): ?>
  <div class="section-title" style="margin-top:10px">Serviços e Peças</div>
  <table>
    <thead>
      <tr>
        <th style="width:50%">Descrição</th>
        <th style="width:12%;text-align:center">Qtd</th>
        <th style="width:19%;text-align:right">Valor Unit.</th>
        <th style="width:19%;text-align:right">Total</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($os['servicos'] ?? [] as $s): ?>
      <tr>
        <td><?= e($s['descricao']) ?> <span style="font-size:10.5px;color:#888">(serviço)</span></td>
        <td style="text-align:center"><?= $s['quantidade'] ?></td>
        <td style="text-align:right"><?= money($s['valor_unitario']) ?></td>
        <td style="text-align:right;font-weight:bold"><?= money($s['valor_total']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php foreach ($os['pecas'] ?? [] as $p): ?>
      <tr>
        <td><?= e($p['descricao']) ?> <span style="font-size:10.5px;color:#888">(peça)</span></td>
        <td style="text-align:center"><?= $p['quantidade'] ?></td>
        <td style="text-align:right"><?= money($p['valor_unitario']) ?></td>
        <td style="text-align:right;font-weight:bold"><?= money($p['valor_total']) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <?php if ($totalServicos > 0 && $totalPecas > 0): ?>
      <tr style="font-size:11.5px;color:#555">
        <td colspan="3" style="text-align:right">Subtotal Serviços:</td>
        <td style="text-align:right"><?= money($totalServicos) ?></td>
      </tr>
      <tr style="font-size:11.5px;color:#555">
        <td colspan="3" style="text-align:right">Subtotal Peças:</td>
        <td style="text-align:right"><?= money($totalPecas) ?></td>
      </tr>
      <?php endif; ?>
      <?php if (($os['desconto_valor'] ?? 0) > 0): ?>
      <tr style="color:#c00">
        <td colspan="3" style="text-align:right">Desconto:</td>
        <td style="text-align:right">- <?= money($os['desconto_valor']) ?></td>
      </tr>
      <?php endif; ?>
      <tr style="background:#1a1d23;color:#fff">
        <td colspan="3" style="text-align:right;font-weight:bold;font-size:14px">TOTAL</td>
        <td style="text-align:right;font-weight:bold;font-size:15px"><?= money($totalGeral) ?></td>
      </tr>
    </tfoot>
  </table>
  <?php elseif (($os['valor_total'] ?? 0) > 0): ?>
  <div class="section-title" style="margin-top:10px">Valor</div>
  <div class="info-box" style="text-align:right;font-size:16px;font-weight:bold">
    Total: <?= money($os['valor_total']) ?>
  </div>
  <?php endif; ?>

  <!-- ASSINATURAS — FIM DA PÁGINA 1 -->
  <div class="assinaturas" style="margin-top:28px">
    <div><div class="assinatura-linha">Assinatura do Cliente</div></div>
    <div><div class="assinatura-linha"><?= e($os['empresa_nome']) ?></div></div>
  </div>

  </div><!-- /folha 1 (via do cliente) -->

  <div class="folha"><!-- folha 2: via da empresa + etiquetas -->

  <!-- VIA DA EMPRESA — movida para após assinaturas (ver abaixo) -->
  <?php ob_start(); ?>
  <div style="margin-top:24px;padding:0">
    <div style="text-align:right;margin-bottom:10px">
      <span style="font-size:10.5px;font-weight:bold;color:#1a1d23">OS <?= e($os['numero']) ?> — <?= date_br($os['data_entrada'], true) ?></span>
    </div>

    <!-- Cabeçalho empresa (compacto) -->
    <div style="display:flex;justify-content:space-between;align-items:flex-start;border-bottom:2px solid #1a1d23;padding-bottom:8px;margin-bottom:10px">
      <div>
        <div style="font-size:15px;font-weight:bold;color:#1a1d23"><?= e($os['empresa_nome']) ?></div>
        <?php if (!empty($os['emp_tel'])): ?><div style="font-size:11.5px;color:#555">Tel: <?= e($os['emp_tel']) ?><?php if (!empty($os['emp_whatsapp'])): ?> | WhatsApp: <?= e($os['emp_whatsapp']) ?><?php endif; ?></div><?php endif; ?>
      </div>
      <div style="text-align:right">
        <div style="font-size:23px;font-weight:900;border:2px solid #1a1d23;padding:2px 10px;color:#1a1d23">OS <?= e($os['numero']) ?></div>
        <div style="font-size:11px;color:#444;margin-top:2px">
          Entrada: <?= date_br($os['data_entrada'], true) ?> | Previsão: <?= date_br($os['data_previsao'], true) ?: '—' ?>
        </div>
      </div>
    </div>

    <!-- Cliente + Equipamento (igual ao topo) -->
    <div class="row">
      <div class="col">
        <div class="section-title">Cliente</div>
        <div class="info-box">
          <div class="info-label">Nome</div>
          <div style="font-weight:bold;font-size:14px"><?= e($os['cliente_nome']) ?></div><?php if (!empty($os['cliente_contato'])): ?><div style="font-size:12px"><b>Contato:</b> <?= e($os['cliente_contato']) ?></div><?php endif; ?>
          <?php if ($os['cpf_cnpj']??null): ?><div><span class="info-label">CPF/CNPJ:</span> <?= e(doc_mask($os['cpf_cnpj'])) ?></div><?php endif; ?>
          <?php if ($os['cliente_tel']??null): ?><div><span class="info-label">Tel:</span> <?= e($os['cliente_tel']) ?></div><?php endif; ?>
          <?php if ($os['cliente_whats']??null): ?><div><span class="info-label">WhatsApp:</span> <?= e($os['cliente_whats']) ?></div><?php endif; ?>
          <?php if ($os['cliente_email']??null): ?><div><span class="info-label">E-mail:</span> <?= e($os['cliente_email']) ?></div><?php endif; ?>
          <?php
            $endCli2 = array_filter([$os['cli_logradouro']??'', ($os['cli_numero']??'') ? 'nº '.$os['cli_numero'] : '', $os['cli_complemento']??'', $os['cli_bairro']??'']);
            $cidCli2 = trim(($os['cli_cidade']??'').(isset($os['cli_uf']) ? '/'.$os['cli_uf'] : ''));
          ?>
          <?php if ($endCli2): ?><div style="margin-top:2px"><span class="info-label">Endereço:</span> <?= e(implode(', ', $endCli2)) ?><?= $cidCli2 ? ' — '.e($cidCli2) : '' ?></div><?php endif; ?>
        </div>
      </div>
      <div class="col">
        <div class="section-title">Equipamento</div>
        <div class="info-box">
          <div style="font-weight:bold;font-size:14px"><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?></div>
          <div><?= e($os['equip_tipo']??'') ?></div>
          <?php if ($os['numero_serie']??null): ?><div><span class="info-label">S/N:</span> <?= e($os['numero_serie']) ?></div><?php endif; ?>
          <?php if ($os['equip_cor']??null): ?><div><span class="info-label">Cor:</span> <?= e($os['equip_cor']) ?></div><?php endif; ?>
          <?php if ($os['voltagem']??null): ?><div><span class="info-label">Voltagem:</span> <?= e($os['voltagem']) ?></div><?php endif; ?>
          <div><span class="info-label">Estado de entrada:</span> <?= ucfirst($os['estado_entrada']??'') ?></div>
          <?php if ($os['senha_desbloqueio']??null): ?><div><span class="info-label">Senha:</span> <?= e($os['senha_desbloqueio']) ?></div><?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Defeito -->
    <div class="section-title">Defeito relatado pelo cliente</div>
    <div class="info-box" style="min-height:28px"><?= nl2br(e($os['defeito_relatado']??'')) ?></div>

    <div class="section-title">Acessórios</div>
    <div class="info-box" style="min-height:60px"><?= e($os['acessorios'] ?? '') ?></div>

    <?php if ($os['tecnico_nome']??null): ?>
    <div style="margin-top:6px;font-size:11px;text-align:right;color:#555">
      <strong>Técnico:</strong> <?= e($os['tecnico_nome']) ?> &nbsp;|&nbsp;
      <strong>Garantia:</strong> <?= $os['garantia_dias']??90 ?> dias após entrega
    </div>
    <?php endif; ?>
  </div>
  <?php $viaEmpresa = ob_get_clean(); ?>

  <!-- VIA DA EMPRESA (página 2) -->
  <div style="margin-top:0;padding-top:0">
    <?= $viaEmpresa ?>
  </div>

  <!-- ETIQUETAS (rodapé) -->
  <?php
    $primeiroNome = explode(' ', trim($os['cliente_nome'] ?? ''))[0];
    $telefone     = $os['cliente_whats'] ?? $os['cliente_tel'] ?? '';
    $marcaModelo  = trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''));
  ?>
  <div style="margin-top:24px;border-top:1px dashed #aaa;padding-top:12px">
    <div style="font-size:10.5px;color:#888;margin-bottom:8px;text-transform:uppercase;letter-spacing:.06em">
      ✂ Etiquetas de identificação (recortar)
    </div>
    <div style="display:flex;gap:8px">
      <?php for ($i = 0; $i < 4; $i++): ?>
      <div style="border:2px solid #1a1d23;border-radius:6px;padding:7px 10px;flex:1;background:#fff;page-break-inside:avoid;min-width:0">
        <div style="font-size:15px;font-weight:900;color:#1a1d23;letter-spacing:.02em">OS <?= e($os['numero']) ?></div>
        <div style="font-size:11.5px;font-weight:bold;margin-top:2px;color:#222"><?= e($primeiroNome) ?></div>
        <?php if ($telefone): ?><div style="font-size:10.5px;color:#555;margin-top:1px"><?= e($telefone) ?></div><?php endif; ?>
        <div style="font-size:10.5px;color:#333;margin-top:2px;font-weight:bold"><?= e($marcaModelo) ?></div>
        <div style="font-size:10px;color:#777;margin-top:1px"><?= e($os['equip_tipo'] ?? '') ?></div>
      </div>
      <?php endfor; ?>
    </div>
  </div>

  </div><!-- /folha 2 -->
</div><!-- /folhas -->
</body>
</html>
