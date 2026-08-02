<div class="d-flex gap-3 overflow-auto pb-3">
  <?php foreach ($estagios as $estagio): ?>
  <div class="flex-shrink-0" style="width:280px">
    <div class="card border-0 shadow-sm">
      <div class="card-header fw-semibold d-flex justify-content-between align-items-center" style="border-left:4px solid <?= e($estagio['cor']) ?>">
        <span><?= e($estagio['nome']) ?></span>
        <span class="badge bg-secondary"><?= count($oportunidades[$estagio['id']] ?? []) ?></span>
      </div>
      <div class="card-body p-2" style="min-height:200px">
        <?php foreach ($oportunidades[$estagio['id']] ?? [] as $op): ?>
        <div class="card mb-2 border shadow-sm">
          <div class="card-body p-2">
            <div class="fw-semibold small"><?= e($op['titulo']) ?></div>
            <div class="text-muted" style="font-size:.75rem"><?= e($op['cliente_nome']) ?></div>
            <?php if ($op['valor_estimado']): ?>
            <div class="text-success fw-bold small mt-1"><?= money($op['valor_estimado']) ?></div>
            <?php endif; ?>
            <div class="d-flex justify-content-between align-items-center mt-1">
              <div class="progress flex-grow-1 me-2" style="height:4px">
                <div class="progress-bar" style="width:<?= $op['probabilidade'] ?>%"></div>
              </div>
              <small class="text-muted"><?= $op['probabilidade'] ?>%</small>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
