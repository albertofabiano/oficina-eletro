<?php
/** @var string $state  Estado da conexão: open|connecting|close|unknown */
/** @var string|null $qr  QR Code em data URI, ou null */
$conectado = $state === 'open';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php if (!$conectado): ?><meta http-equiv="refresh" content="15"><?php endif; ?>
<title>Conectar WhatsApp — FixaOS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<style>
  body { margin:0; min-height:100vh; background:#0f1115; color:#e5e7eb;
         font-family:'Segoe UI',system-ui,sans-serif; display:flex;
         align-items:center; justify-content:center; padding:24px; }
  .card { background:#1a1d23; border:1px solid #2d3139; border-radius:18px;
          padding:32px; max-width:420px; width:100%; text-align:center;
          box-shadow:0 12px 40px rgba(0,0,0,.4); }
  .brand { font-size:24px; font-weight:800; margin-bottom:4px; }
  .brand span { color:#f97316; }
  .sub { color:#9ca3af; font-size:.9rem; margin-bottom:20px; }
  .qr-wrap { background:#fff; border-radius:14px; padding:16px; display:inline-block; line-height:0; }
  .qr-wrap img { width:280px; height:280px; display:block; }
  .badge-state { display:inline-flex; align-items:center; gap:6px; padding:6px 14px;
                 border-radius:999px; font-size:.8rem; font-weight:600; margin-bottom:18px; }
  .st-connecting { background:rgba(234,179,8,.15); color:#fbbf24; }
  .st-close { background:rgba(239,68,68,.15); color:#f87171; }
  .st-open { background:rgba(34,197,94,.15); color:#4ade80; }
  .steps { text-align:left; color:#cbd5e1; font-size:.86rem; line-height:1.7; margin:18px 0 0; padding-left:20px; }
  .ok-icon { font-size:64px; color:#22c55e; margin:8px 0 16px; }
  .muted { color:#6b7280; font-size:.75rem; margin-top:18px; }
  .btn-refresh { display:inline-block; margin-top:16px; background:#25d366; color:#062e14;
                 font-weight:700; text-decoration:none; padding:10px 20px; border-radius:10px; font-size:.85rem; }
</style>
</head>
<body>
  <div class="card">
    <div class="brand">Fixa<span>OS</span></div>
    <div class="sub">Conexão do WhatsApp Business</div>

    <?php if ($conectado): ?>
      <div class="badge-state st-open"><i class="bi bi-check-circle-fill"></i> Conectado</div>
      <div class="ok-icon"><i class="bi bi-whatsapp"></i></div>
      <p style="font-size:1rem;color:#e5e7eb;margin:0 0 6px"><strong>WhatsApp conectado com sucesso!</strong></p>
      <p style="color:#9ca3af;font-size:.88rem;margin:0">As mensagens de boas-vindas já vão sair automaticamente a cada novo cadastro.</p>

    <?php elseif ($qr): ?>
      <div class="badge-state st-<?= $state === 'connecting' ? 'connecting' : 'close' ?>">
        <i class="bi bi-arrow-repeat"></i> Aguardando leitura…
      </div>
      <div class="qr-wrap"><img src="<?= $qr ?>" alt="QR Code WhatsApp"></div>
      <ol class="steps">
        <li>Abra o <strong>WhatsApp Business</strong> no celular</li>
        <li>Toque em <strong>⋮</strong> → <strong>Aparelhos conectados</strong></li>
        <li>Toque em <strong>Conectar um aparelho</strong></li>
        <li>Aponte a câmera para este QR Code</li>
      </ol>
      <p class="muted"><i class="bi bi-clock"></i> O código se renova sozinho a cada 15 segundos.</p>

    <?php else: ?>
      <div class="badge-state st-close"><i class="bi bi-exclamation-triangle-fill"></i> Indisponível</div>
      <p style="color:#9ca3af;font-size:.9rem">Não foi possível gerar o QR Code agora. A instância pode estar reiniciando.</p>
      <a class="btn-refresh" href="<?= url('/master/whatsapp') ?>"><i class="bi bi-arrow-clockwise"></i> Tentar de novo</a>
    <?php endif; ?>
  </div>
</body>
</html>
