<?php $titulo = 'Notificações'; ?>
<div class="page-content">

  <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
    <div>
      <h4 class="fw-bold mb-1">Notificações</h4>
      <p class="text-muted small mb-0">Alertas automáticos do sistema sobre OS, financeiro, estoque e agenda.</p>
    </div>
    <button onclick="marcarTodas()" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-check-all me-1"></i>Marcar todas como lidas
    </button>
  </div>

  <?php if($notificacoes): ?>

  <?php
  $grupos = [
    'danger'  => ['label' => 'Urgentes',  'icon' => 'bi-exclamation-triangle-fill', 'cor' => '#ef4444'],
    'warning' => ['label' => 'Atenção',   'icon' => 'bi-exclamation-circle',        'cor' => '#f59e0b'],
    'success' => ['label' => 'Positivas', 'icon' => 'bi-check-circle-fill',         'cor' => '#22c55e'],
    'info'    => ['label' => 'Informações','icon' => 'bi-info-circle-fill',         'cor' => '#3b82f6'],
    'primary' => ['label' => 'Geral',     'icon' => 'bi-bell-fill',                'cor' => '#6366f1'],
  ];

  $porGrupo = [];
  foreach ($notificacoes as $n) {
    $porGrupo[$n['cor']][] = $n;
  }
  ?>

  <?php foreach($grupos as $cor => $grp): ?>
  <?php if(!empty($porGrupo[$cor])): ?>
  <div class="mb-4">
    <div class="d-flex align-items-center gap-2 mb-2">
      <i class="bi <?= $grp['icon'] ?>" style="color:<?= $grp['cor'] ?>;font-size:1rem"></i>
      <span class="fw-bold" style="color:<?= $grp['cor'] ?>"><?= $grp['label'] ?></span>
      <span class="badge rounded-pill" style="background:<?= $grp['cor'] ?>22;color:<?= $grp['cor'] ?>;font-size:.7rem"><?= count($porGrupo[$cor]) ?></span>
    </div>
    <div class="d-flex flex-column gap-2">
      <?php foreach($porGrupo[$cor] as $n): ?>
      <div class="card border-0 shadow-sm <?= !$n['lida'] ? '' : 'opacity-60' ?>"
           style="border-left:4px solid <?= $grp['cor'] ?>!important;<?= !$n['lida'] ? 'background:#fff' : 'background:#f8fafc' ?>">
        <div class="card-body py-3 px-4">
          <div class="d-flex align-items-start justify-content-between gap-3">
            <div class="d-flex align-items-start gap-3" style="flex:1">
              <div style="width:38px;height:38px;border-radius:10px;background:<?= $grp['cor'] ?>15;display:flex;align-items:center;justify-content:center;flex-shrink:0;margin-top:2px">
                <i class="bi <?= e($n['icone']) ?>" style="color:<?= $grp['cor'] ?>;font-size:1rem"></i>
              </div>
              <div style="flex:1">
                <div class="fw-semibold" style="color:#0f172a;font-size:.92rem"><?= e($n['titulo']) ?></div>
                <?php if($n['mensagem']): ?>
                <div class="text-muted small mt-1"><?= e($n['mensagem']) ?></div>
                <?php endif; ?>
                <div class="text-muted" style="font-size:.75rem;margin-top:.3rem">
                  <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($n['criado_em'])) ?>
                  <?php if(!$n['lida']): ?><span class="badge bg-primary ms-2" style="font-size:.65rem">Nova</span><?php endif; ?>
                </div>
              </div>
            </div>
            <div class="d-flex gap-2 align-items-center flex-shrink-0">
              <?php if($n['link']): ?>
              <a href="<?= url($n['link']) ?>" onclick="lerNotif(<?= $n['id'] ?>)"
                 class="btn btn-sm btn-outline-primary" style="font-size:.78rem">
                Ver <i class="bi bi-arrow-right ms-1"></i>
              </a>
              <?php endif; ?>
              <?php if(!$n['lida']): ?>
              <button onclick="lerNotif(<?= $n['id'] ?>)" class="btn btn-sm btn-outline-secondary" style="font-size:.78rem" title="Marcar como lida">
                <i class="bi bi-check"></i>
              </button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endforeach; ?>

  <?php else: ?>
  <div class="text-center py-5">
    <i class="bi bi-bell-slash" style="font-size:4rem;color:#cbd5e1;display:block;margin-bottom:1rem"></i>
    <h5 class="text-muted">Nenhuma notificação</h5>
    <p class="text-muted small">O sistema verifica automaticamente OS atrasadas, contas a vencer, estoque mínimo e muito mais.</p>
  </div>
  <?php endif; ?>

</div>

<script>
function lerNotif(id) {
  fetch('<?= url('/notificacoes/') ?>' + id + '/ler', {method:'GET'});
}

function marcarTodas() {
  fetch('<?= url('/notificacoes/todas-lidas') ?>', {
    method:'POST',
    headers:{'X-CSRF-TOKEN':'<?= csrf_token() ?>','Content-Type':'application/json'}
  }).then(()=>location.reload());
}
</script>
