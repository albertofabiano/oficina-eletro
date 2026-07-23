<?php
$wa  = preg_replace('/\D/', '', $pedido['contato_whatsapp'] ?? '');
$msg = urlencode("Olá! Vi seu pedido de peça \"{$pedido['titulo']}\" no FixaOS. Posso te ajudar!");
?>
<style>
body{background:#f8fafc}
.pv-card{background:#fff;border:1.5px solid #e2e8f0;border-radius:14px;padding:1.5rem;margin-bottom:1rem}
.resp-card{background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.2rem;margin-bottom:.8rem}
.badge-urgente{background:#fef2f2;color:#ef4444;border:1px solid #fecaca;border-radius:6px;font-size:.75rem;font-weight:700;padding:.25rem .7rem}
.badge-aberto{background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;border-radius:6px;font-size:.75rem;font-weight:700;padding:.25rem .7rem}
.badge-atendido{background:#f1f5f9;color:#64748b;border:1px solid #e2e8f0;border-radius:6px;font-size:.75rem;font-weight:700;padding:.25rem .7rem}
.btn-wa{background:#25d366;color:#fff;border:none;border-radius:10px;padding:.75rem 1.4rem;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:.5rem;transition:.2s}
.btn-wa:hover{background:#1da852;color:#fff}
</style>

<!-- Breadcrumb -->
<div style="background:#fff;border-bottom:1px solid #e2e8f0;padding:.6rem 0;font-size:.82rem">
  <div class="container">
    <a href="<?= $baseUrl ?>/pecas" style="color:#f97316;text-decoration:none">Marketplace</a>
    <span style="color:#94a3b8;margin:0 .5rem">/</span>
    <a href="<?= $baseUrl ?>/pecas/pedidos" style="color:#f97316;text-decoration:none">Pedidos de peças</a>
    <span style="color:#94a3b8;margin:0 .5rem">/</span>
    <span style="color:#0f172a;font-weight:600"><?= htmlspecialchars(mb_substr($pedido['titulo'],0,40)) ?></span>
  </div>
</div>

<div style="background:#f8fafc;padding:2.5rem 0;min-height:70vh">
<div class="container">
<div class="row g-4">

  <!-- Coluna principal -->
  <div class="col-lg-8">

    <!-- Pedido -->
    <div class="pv-card">
      <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
        <div>
          <span class="<?= $pedido['urgencia']==='urgente'?'badge-urgente':'badge-aberto' ?> me-2">
            <?= $pedido['urgencia']==='urgente'?'🔥 URGENTE':'✓ Aberto' ?>
          </span>
          <?php if($pedido['status']==='atendido'): ?>
          <span class="badge-atendido">✓ Atendido</span>
          <?php endif; ?>
        </div>
        <div style="color:#94a3b8;font-size:.78rem"><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></div>
      </div>

      <h1 style="color:#0f172a;font-size:1.4rem;font-weight:800;margin-bottom:.8rem"><?= htmlspecialchars($pedido['titulo']) ?></h1>

      <?php if($pedido['tipo'] || $pedido['marca'] || $pedido['modelo']): ?>
      <div class="d-flex flex-wrap gap-2 mb-3">
        <?php if($pedido['tipo']): ?><span style="background:#eff6ff;color:#1d4ed8;border-radius:6px;font-size:.78rem;font-weight:600;padding:.3rem .7rem"><?= htmlspecialchars($pedido['tipo']) ?></span><?php endif; ?>
        <?php if($pedido['marca']): ?><span style="background:#f1f5f9;color:#475569;border-radius:6px;font-size:.78rem;font-weight:600;padding:.3rem .7rem;border:1px solid #e2e8f0"><?= htmlspecialchars($pedido['marca']) ?></span><?php endif; ?>
        <?php if($pedido['modelo']): ?><span style="background:#f1f5f9;color:#475569;border-radius:6px;font-size:.78rem;font-weight:600;padding:.3rem .7rem;border:1px solid #e2e8f0"><?= htmlspecialchars($pedido['modelo']) ?></span><?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if($pedido['descricao']): ?>
      <div style="color:#374151;font-size:.93rem;line-height:1.8;white-space:pre-wrap"><?= htmlspecialchars($pedido['descricao']) ?></div>
      <?php endif; ?>

      <div style="margin-top:1.2rem;padding-top:1rem;border-top:1px solid #f1f5f9;display:flex;align-items:center;gap:.5rem">
        <i class="bi bi-building" style="color:#f97316"></i>
        <span style="color:#374151;font-weight:600"><?= htmlspecialchars($pedido['empresa_nome']) ?></span>
        <?php if($pedido['cidade']): ?>
        <span style="color:#94a3b8;font-size:.82rem">· <?= htmlspecialchars($pedido['cidade']) ?>/<?= htmlspecialchars($pedido['uf']) ?></span>
        <?php endif; ?>
      </div>
    </div>

    <!-- Respostas -->
    <h3 style="color:#0f172a;font-size:1rem;font-weight:700;margin-bottom:1rem">
      <i class="bi bi-chat-dots-fill me-2" style="color:#f97316"></i>
      <?= count($respostas) ?> resposta<?= count($respostas)!=1?'s':'' ?> da comunidade
    </h3>

    <?php foreach($respostas as $r): ?>
    <?php $rwa = preg_replace('/\D/', '', $r['whatsapp'] ?? ''); ?>
    <div class="resp-card">
      <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
        <div>
          <span style="color:#0f172a;font-weight:700"><?= htmlspecialchars($r['empresa_nome']) ?></span>
          <?php if($r['cidade']): ?>
          <span style="color:#94a3b8;font-size:.8rem"> · <?= htmlspecialchars($r['cidade']) ?>/<?= htmlspecialchars($r['uf']) ?></span>
          <?php endif; ?>
        </div>
        <div style="color:#94a3b8;font-size:.75rem"><?= date('d/m/Y H:i', strtotime($r['criado_em'])) ?></div>
      </div>
      <p style="color:#374151;font-size:.9rem;line-height:1.7;margin-bottom:.8rem"><?= nl2br(htmlspecialchars($r['mensagem'])) ?></p>
      <?php if($rwa): ?>
      <a href="https://wa.me/55<?= $rwa ?>?text=<?= urlencode('Olá! Vi sua resposta no pedido "'.$pedido['titulo'].'" no FixaOS.') ?>" target="_blank" class="btn-wa" style="font-size:.85rem;padding:.5rem 1rem">
        <i class="bi bi-whatsapp"></i>Chamar no WhatsApp
      </a>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <!-- Formulário de resposta -->
    <?php if(\App\Core\Auth::check()): ?>
    <?php
      $minhEid = \App\Core\Auth::empresaId();
      $ehMeu   = $minhEid === (int)$pedido['empresa_id'];
    ?>
    <?php if(!$ehMeu && $pedido['status']==='aberto'): ?>
    <div class="pv-card" style="margin-top:1rem">
      <h4 style="font-size:.95rem;font-weight:700;color:#0f172a;margin-bottom:1rem"><i class="bi bi-reply-fill me-2" style="color:#f97316"></i>Tenho esta peça — responder</h4>
      <form method="POST" action="<?= $baseUrl ?>/marketplace/pedidos/<?= $pedido['id'] ?>/responder">
        <?= csrf_field() ?>
        <div class="mb-3">
          <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Sua mensagem *</label>
          <textarea name="mensagem" class="form-control" rows="3" placeholder="Descreva a peça que você tem, estado, preço..." required></textarea>
        </div>
        <div class="mb-3">
          <label style="font-size:.85rem;font-weight:600;color:#374151;display:block;margin-bottom:.4rem">Seu WhatsApp para contato</label>
          <input type="text" name="whatsapp" class="form-control" placeholder="(11) 99999-9999">
        </div>
        <button type="submit" style="background:#f97316;color:#fff;border:none;border-radius:10px;padding:.75rem 1.8rem;font-weight:700;cursor:pointer">
          <i class="bi bi-send-fill me-1"></i>Enviar oferta
        </button>
      </form>
    </div>
    <?php endif; ?>
    <?php else: ?>
    <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:12px;padding:1.2rem;text-align:center;margin-top:1rem">
      <div style="color:#92400e;font-weight:600;margin-bottom:.5rem">Tem esta peça?</div>
      <a href="<?= $baseUrl ?>/login" style="background:#f97316;color:#fff;border-radius:8px;padding:.6rem 1.4rem;text-decoration:none;font-weight:700;font-size:.9rem">Entrar para responder</a>
    </div>
    <?php endif; ?>

  </div>

  <!-- Sidebar -->
  <div class="col-lg-4">
    <div class="pv-card" style="position:sticky;top:80px">
      <h5 style="font-weight:700;color:#0f172a;margin-bottom:1rem">Contato direto</h5>

      <?php if($wa): ?>
      <a href="https://wa.me/55<?= $wa ?>?text=<?= $msg ?>" target="_blank" class="btn-wa w-100 justify-content-center mb-3">
        <i class="bi bi-whatsapp fs-5"></i>WhatsApp do solicitante
      </a>
      <?php endif; ?>

      <div style="background:#f8fafc;border-radius:10px;padding:1rem">
        <div style="color:#94a3b8;font-size:.75rem;margin-bottom:.3rem;text-transform:uppercase;font-weight:600">Solicitante</div>
        <div style="color:#0f172a;font-weight:700"><?= htmlspecialchars($pedido['empresa_nome']) ?></div>
        <?php if($pedido['cidade']): ?>
        <div style="color:#64748b;font-size:.82rem"><i class="bi bi-geo-alt-fill me-1" style="color:#f97316"></i><?= htmlspecialchars($pedido['cidade']) ?>/<?= htmlspecialchars($pedido['uf']) ?></div>
        <?php endif; ?>
      </div>

      <div style="margin-top:1.2rem;text-align:center">
        <a href="<?= $baseUrl ?>/pecas/pedidos" style="color:#64748b;font-size:.82rem;text-decoration:none">← Ver todos os pedidos</a>
      </div>
    </div>
  </div>

</div>
</div>
</div>

<?php $ok=flash('success');$err=flash('error');
if($ok): ?><div class="alert alert-success position-fixed bottom-0 end-0 m-3"><?= htmlspecialchars($ok) ?></div><?php endif;
if($err): ?><div class="alert alert-danger position-fixed bottom-0 end-0 m-3"><?= htmlspecialchars($err) ?></div><?php endif; ?>
