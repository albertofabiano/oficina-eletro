<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Etiquetas de Produto</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family: Arial, sans-serif; color:#000; background:#eee; }

.no-print {
  background:#f0f2f5; padding:10px 16px; display:flex; align-items:center; gap:10px;
  border-bottom:1px solid #dee2e6; flex-wrap:wrap; position:sticky; top:0;
}
.no-print button, .no-print a.voltar {
  background:#1a1d23; color:#fff; border:none; padding:7px 20px; border-radius:6px;
  cursor:pointer; font-size:15px; font-weight:600; text-decoration:none; display:inline-block;
}
.no-print a.voltar { background:transparent; color:#555; padding:7px 4px; font-weight:400; font-size:14px; }

.folha {
  background:#fff; max-width:1000px; margin:16px auto; padding:10mm;
  display:flex; flex-wrap:wrap; gap:4mm;
}

/* Etiqueta: altura MÁXIMA de 4cm (pedido do usuário) — largura fixa (6,5cm) só pra formar uma
   grade regular na folha; borda tracejada serve de guia de corte com tesoura, já que papel
   comum não tem picote. */
.etiqueta {
  width:6.5cm; height:4cm; max-height:4cm;
  border:1.5px dashed #666; border-radius:4px;
  padding:3mm 4mm; box-sizing:border-box;
  display:flex; flex-direction:column; justify-content:center; gap:1.5mm;
  overflow:hidden; page-break-inside:avoid;
}
.et-tipo {
  font-size:9px; font-weight:700; color:#666; text-transform:uppercase; letter-spacing:.05em;
  white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}
.et-principal {
  font-size:13.5px; font-weight:700; color:#000; line-height:1.2;
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
}
.et-codigo {
  font-family:'Courier New', monospace; font-size:11px; font-weight:700; color:#000;
  border-top:1px dashed #ccc; padding-top:1.5mm; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;
}

@media print {
  .no-print { display:none!important; }
  body { background:#fff; }
  .folha { margin:0; max-width:none; padding:0; }
  .etiqueta { border-color:#000; }
  @page { margin:8mm; }
}
</style>
</head>
<body>

<div class="no-print">
  <button onclick="window.print()">🖨 Imprimir</button>
  <a class="voltar" href="<?= url('/produtos') ?>">← Voltar para Produtos</a>
  <span style="margin-left:auto;font-size:12.5px;color:#888"><?= count($produtos) ?> etiqueta(s)</span>
</div>

<div class="folha">
  <?php foreach ($produtos as $p): ?>
  <?php
    $tipo      = trim((string) ($p['tipo_nome'] ?? ''));
    $marca     = trim((string) ($p['marca_nome'] ?? ''));
    $modelo    = trim((string) ($p['modelo'] ?? ''));
    $principal = trim($marca . ' ' . $modelo);
    if ($principal === '') $principal = $p['nome'];
    $codigo    = trim((string) ($p['codigo'] ?? ''));
  ?>
  <div class="etiqueta">
    <?php if ($tipo !== ''): ?><div class="et-tipo"><?= e($tipo) ?></div><?php endif; ?>
    <div class="et-principal"><?= e($principal) ?></div>
    <?php if ($codigo !== ''): ?><div class="et-codigo">Cód: <?= e($codigo) ?></div><?php endif; ?>
  </div>
  <?php endforeach; ?>
</div>

</body>
</html>
