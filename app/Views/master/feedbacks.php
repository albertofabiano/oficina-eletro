<?php $titulo = 'Feedbacks do Sistema'; ?>

<div id="msmain">
  <div id="mstopbar" class="d-flex align-items-center justify-content-between">
    <div class="fw-bold text-white">Feedbacks do Sistema</div>
    <small class="text-secondary">FixaOS Master</small>
  </div>

  <div class="p-4">

    <?php $ok=flash('success');$err=flash('error');
    if($ok): ?><div class="alert alert-success py-2 small"><?= e($ok) ?></div><?php endif;
    if($err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endif; ?>

    <!-- Tabs -->
    <div class="d-flex gap-2 mb-4 flex-wrap">
      <?php foreach([
        ['novo','Novos ('.$novos.')','#3b82f6'],
        ['lido','Lidos ('.$lidos.')','#22c55e'],
        ['arquivado','Arquivados ('.$arquivados.')','#6b7280'],
      ] as[$f,$l,$c]):?>
      <a href="<?= url('/master/feedbacks?filtro='.$f) ?>" class="btn btn-sm fw-semibold"
         style="<?= $filtro===$f ? "background:$c;color:#fff;border-color:$c" : 'background:rgba(255,255,255,.06);color:#9ca3af;border-color:rgba(255,255,255,.1)' ?>"><?= $l ?></a>
      <?php endforeach;?>
    </div>

    <?php if($feedbacks): ?>
    <div class="d-flex flex-column gap-3">
      <?php foreach($feedbacks as $f):
        $badge = [
          'critica'  => ['Crítica','#ef4444','bi-emoji-frown'],
          'elogio'   => ['Elogio','#22c55e','bi-emoji-smile'],
          'sugestao' => ['Sugestão','#3b82f6','bi-lightbulb'],
        ][$f['tipo']] ?? ['—','#6b7280','bi-chat'];
      ?>
      <div class="ms-card p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">
          <div style="flex:1;min-width:0">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <span style="background:<?= $badge[1] ?>22;color:<?= $badge[1] ?>;border:1px solid <?= $badge[1] ?>44;border-radius:6px;font-size:.72rem;font-weight:700;padding:.15rem .55rem"><i class="bi <?= $badge[2] ?> me-1"></i><?= $badge[0] ?></span>
              <span style="color:#fff;font-weight:700"><?= e($f['usuario_nome'] ?? 'Usuário') ?></span>
              <?php if($f['empresa_nome']): ?><span style="color:#6b7280;font-size:.78rem"><i class="bi bi-building me-1"></i><?= e($f['empresa_nome']) ?></span><?php endif; ?>
            </div>
            <div style="color:#9ca3af;font-size:.8rem;margin-bottom:.6rem">
              <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($f['criado_em'])) ?>
              <?php if($f['pagina']): ?> &middot; <i class="bi bi-link-45deg"></i> <?= e($f['pagina']) ?><?php endif; ?>
            </div>
            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.8rem 1rem;color:#e2e8f0;font-size:.9rem;line-height:1.7;white-space:pre-wrap"><?= e($f['mensagem']) ?></div>
          </div>
          <div class="d-flex flex-column gap-2" style="min-width:140px">
            <?php if($f['status'] !== 'lido'): ?>
            <form method="POST" action="<?= url('/master/feedbacks/'.$f['id'].'/status') ?>">
              <?= csrf_field() ?><input type="hidden" name="status" value="lido">
              <button class="btn btn-sm w-100 fw-bold" style="background:#22c55e;color:#fff;border:none"><i class="bi bi-check-lg me-1"></i>Marcar lido</button>
            </form>
            <?php endif; ?>
            <?php if($f['status'] !== 'arquivado'): ?>
            <form method="POST" action="<?= url('/master/feedbacks/'.$f['id'].'/status') ?>">
              <?= csrf_field() ?><input type="hidden" name="status" value="arquivado">
              <button class="btn btn-sm w-100 fw-bold" style="background:rgba(255,255,255,.08);color:#9ca3af;border:none"><i class="bi bi-archive me-1"></i>Arquivar</button>
            </form>
            <?php endif; ?>
            <?php if($f['status'] === 'arquivado'): ?>
            <form method="POST" action="<?= url('/master/feedbacks/'.$f['id'].'/status') ?>">
              <?= csrf_field() ?><input type="hidden" name="status" value="novo">
              <button class="btn btn-sm w-100 fw-bold" style="background:rgba(255,255,255,.08);color:#9ca3af;border:none"><i class="bi bi-arrow-counterclockwise me-1"></i>Reabrir</button>
            </form>
            <?php endif; ?>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="ms-card p-5 text-center">
      <i class="bi bi-chat-heart" style="font-size:3rem;color:#374151;display:block;margin-bottom:1rem"></i>
      <div style="color:#6b7280">Nenhum feedback <?= ['novo'=>'novo','lido'=>'lido','arquivado'=>'arquivado'][$filtro] ?? '' ?> no momento.</div>
    </div>
    <?php endif; ?>

  </div>
</div>
