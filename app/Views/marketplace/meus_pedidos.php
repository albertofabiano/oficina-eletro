<?php $titulo = 'Meus Pedidos de Peças'; ?>
<?php
$cfg = require BASE_PATH . '/config/app.php';
$baseUrl = rtrim($cfg['url'], '/');
?>
<div class="page-content">
  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1">Meus Pedidos de Peças</h4>
      <p class="text-muted small mb-0">Pedidos que você publicou na comunidade do marketplace.</p>
    </div>
    <a href="<?= $baseUrl ?>/pecas/pedidos" target="_blank" class="btn btn-primary">
      <i class="bi bi-plus-circle me-1"></i>Novo pedido
    </a>
  </div>

  <?php $ok=flash('success');$err=flash('error');
  if($ok): ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif;
  if($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

  <!-- Respostas recebidas -->
  <?php if($respostas): ?>
  <div class="card border-0 shadow-sm mb-4" style="border-left:4px solid #f97316!important">
    <div class="card-header bg-white fw-bold">
      <i class="bi bi-bell-fill me-2" style="color:#f97316"></i>Respostas recebidas
    </div>
    <div class="card-body p-0">
      <?php foreach($respostas as $r): ?>
      <div style="padding:1rem 1.2rem;border-bottom:1px solid #f1f5f9">
        <div class="d-flex justify-content-between align-items-start">
          <div>
            <span class="fw-semibold"><?= e($r['empresa_nome']) ?></span>
            <span class="text-muted small ms-2">respondeu ao pedido: <a href="<?= $baseUrl ?>/pecas/pedidos/<?= $r['pedido_id'] ?>" target="_blank" style="color:#f97316"><?= e($r['pedido_titulo']) ?></a></span>
          </div>
          <span class="text-muted small"><?= date('d/m H:i', strtotime($r['criado_em'])) ?></span>
        </div>
        <p class="text-muted small mt-1 mb-0"><?= e(mb_substr($r['mensagem'],0,100)) ?>...</p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

  <!-- Meus pedidos -->
  <?php if($pedidos): ?>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle">
        <thead class="table-light">
          <tr><th>Pedido</th><th>Urgência</th><th>Respostas</th><th>Status</th><th>Data</th><th class="text-end">Ações</th></tr>
        </thead>
        <tbody>
          <?php foreach($pedidos as $p): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($p['titulo']) ?></div>
              <?php if($p['marca'] || $p['tipo']): ?>
              <div class="text-muted small"><?= e(trim($p['tipo'].' '.$p['marca'])) ?></div>
              <?php endif; ?>
            </td>
            <td>
              <?php if($p['urgencia']==='urgente'): ?>
              <span class="badge bg-danger">🔥 Urgente</span>
              <?php else: ?>
              <span class="badge bg-success">Normal</span>
              <?php endif; ?>
            </td>
            <td>
              <span class="badge bg-primary rounded-pill"><?= $p['total_respostas'] ?></span>
            </td>
            <td>
              <?php $cores=['aberto'=>'success','atendido'=>'secondary','cancelado'=>'danger']; ?>
              <span class="badge bg-<?= $cores[$p['status']] ?>"><?= ucfirst($p['status']) ?></span>
            </td>
            <td class="text-muted small"><?= date('d/m/Y', strtotime($p['criado_em'])) ?></td>
            <td class="text-end">
              <div class="d-flex gap-1 justify-content-end">
                <a href="<?= $baseUrl ?>/pecas/pedidos/<?= $p['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-eye"></i>
                </a>
                <?php if($p['status']==='aberto'): ?>
                <form method="POST" action="<?= url('/marketplace/pedidos/'.$p['id'].'/atender') ?>">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-success" title="Marcar como atendido"><i class="bi bi-check-lg"></i></button>
                </form>
                <form method="POST" action="<?= url('/marketplace/pedidos/'.$p['id'].'/cancelar') ?>" onsubmit="return confirm('Cancelar pedido?')">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
                </form>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php else: ?>
  <div class="text-center py-5 text-muted">
    <i class="bi bi-megaphone fs-1 d-block mb-3 opacity-25"></i>
    <p>Você ainda não publicou nenhum pedido.</p>
    <a href="<?= $baseUrl ?>/pecas/pedidos" target="_blank" class="btn btn-primary">Publicar primeiro pedido</a>
  </div>
  <?php endif; ?>
</div>
