<?php $titulo = 'Moderação de Avaliações'; ?>

<div id="msmain">
  <div id="mstopbar" class="d-flex align-items-center justify-content-between">
    <div class="fw-bold text-white">Moderação de Avaliações</div>
    <small class="text-secondary">FixaOS Master</small>
  </div>

  <div class="p-4">

    <?php $ok=flash('success');$err=flash('error');
    if($ok): ?><div class="alert alert-success py-2 small"><?= e($ok) ?></div><?php endif;
    if($err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endif; ?>

    <!-- KPIs -->
    <div class="row g-3 mb-4">
      <?php foreach([
        ['Pendentes',  $pendentes,   '#f59e0b', 'bi-hourglass-split', 'pendentes'],
        ['Aprovadas',  $aprovadas,   '#22c55e', 'bi-check-circle-fill','aprovadas'],
        ['Reprovadas', $reprovadas,  '#ef4444', 'bi-x-circle-fill',   'reprovadas'],
        ['Contestadas',$contestadas, '#a855f7', 'bi-flag-fill',       'contestadas'],
      ] as[$label,$count,$cor,$icon,$f]):?>
      <div class="col-md-3">
        <a href="<?= url('/master/avaliacoes?filtro='.$f) ?>" class="text-decoration-none">
          <div class="ms-card p-3 d-flex align-items-center gap-3 <?= $filtro===$f?'border border-2':''; ?>" style="<?= $filtro===$f?"border-color:$cor!important":'' ?>">
            <div style="width:44px;height:44px;border-radius:10px;background:<?= $cor ?>22;display:flex;align-items:center;justify-content:center">
              <i class="bi <?= $icon ?>" style="color:<?= $cor ?>;font-size:1.3rem"></i>
            </div>
            <div>
              <div style="font-size:1.6rem;font-weight:900;color:#fff;line-height:1"><?= $count ?></div>
              <div style="color:#6b7280;font-size:.8rem"><?= $label ?></div>
            </div>
          </div>
        </a>
      </div>
      <?php endforeach;?>
    </div>

    <!-- Tabs -->
    <div class="d-flex gap-2 mb-4">
      <?php foreach([
        ['pendentes','Pendentes','#f59e0b'],
        ['aprovadas','Aprovadas','#22c55e'],
        ['reprovadas','Reprovadas','#ef4444'],
        ['contestadas','Contestadas','#a855f7'],
      ] as[$f,$l,$c]):?>
      <a href="<?= url('/master/avaliacoes?filtro='.$f) ?>"
         class="btn btn-sm fw-semibold"
         style="<?= $filtro===$f ? "background:$c;color:#fff;border-color:$c" : 'background:rgba(255,255,255,.06);color:#9ca3af;border-color:rgba(255,255,255,.1)' ?>">
        <?= $l ?>
      </a>
      <?php endforeach;?>
    </div>

    <!-- Lista de avaliações -->
    <?php if($avaliacoes): ?>
    <div class="d-flex flex-column gap-3">
      <?php foreach($avaliacoes as $av): ?>
      <div class="ms-card p-4">
        <div class="d-flex align-items-start justify-content-between gap-3 flex-wrap">

          <!-- Info -->
          <div style="flex:1;min-width:0">
            <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
              <!-- Estrelas -->
              <span style="color:#f59e0b;font-size:1rem;letter-spacing:2px">
                <?php for($i=1;$i<=5;$i++) echo $i<=$av['nota']?'★':'☆'; ?>
              </span>
              <span style="color:#fff;font-weight:700"><?= e($av['nome']) ?></span>
              <?php if(!empty($av['verificada'])): ?>
              <span style="background:#22c55e22;color:#22c55e;border:1px solid #22c55e44;border-radius:6px;font-size:.68rem;font-weight:700;padding:.1rem .45rem"><i class="bi bi-patch-check-fill"></i> Verificada (OS <?= (int)$av['os_id'] ?>)</span>
              <?php endif; ?>
              <?php if($av['email']): ?>
              <span style="color:#6b7280;font-size:.78rem"><?= e($av['email']) ?></span>
              <?php endif; ?>
            </div>

            <div style="color:#9ca3af;font-size:.8rem;margin-bottom:.6rem">
              <i class="bi bi-building me-1"></i>
              <a href="<?= url('/assistencias/'.$av['empresa_slug']) ?>" target="_blank" style="color:#60a5fa;text-decoration:none">
                <?= e($av['empresa_nome']) ?>
              </a>
              &nbsp;·&nbsp;
              <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($av['criado_em'])) ?>
            </div>

            <?php if($av['comentario']): ?>
            <div style="background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:.8rem 1rem;color:#e2e8f0;font-size:.9rem;line-height:1.7">
              "<?= e($av['comentario']) ?>"
            </div>
            <?php else: ?>
            <div style="color:#6b7280;font-size:.85rem;font-style:italic">Sem comentário.</div>
            <?php endif; ?>

            <?php if($av['situacao']==='contestada' && !empty($av['contestacao_motivo'])): ?>
            <div style="background:#a855f715;border:1px solid #a855f744;border-radius:10px;padding:.7rem 1rem;color:#e9d5ff;font-size:.85rem;margin-top:.6rem">
              <i class="bi bi-flag-fill me-1" style="color:#a855f7"></i><strong>Contestada pela empresa:</strong> <?= e($av['contestacao_motivo']) ?>
            </div>
            <?php endif; ?>
          </div>

          <!-- Ações -->
          <div class="d-flex flex-column gap-2" style="min-width:140px">
            <?php if($av['situacao']==='contestada'): ?>
            <form method="POST" action="<?= url('/master/avaliacoes/'.$av['id'].'/manter') ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm w-100 fw-bold" style="background:#22c55e;color:#fff;border:none" title="Negar contestação e manter no ar">
                <i class="bi bi-check-lg me-1"></i>Manter no ar
              </button>
            </form>
            <form method="POST" action="<?= url('/master/avaliacoes/'.$av['id'].'/remover') ?>" onsubmit="return confirm('Aceitar a contestação e remover a avaliação do público?')">
              <?= csrf_field() ?>
              <button class="btn btn-sm w-100 fw-bold" style="background:#a855f7;color:#fff;border:none" title="Aceitar contestação e ocultar do público">
                <i class="bi bi-eye-slash me-1"></i>Remover
              </button>
            </form>
            <?php else: ?>
            <?php if($av['aprovado'] != 1): ?>
            <form method="POST" action="<?= url('/master/avaliacoes/'.$av['id'].'/aprovar') ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm w-100 fw-bold" style="background:#22c55e;color:#fff;border:none">
                <i class="bi bi-check-lg me-1"></i>Aprovar
              </button>
            </form>
            <?php endif; ?>

            <?php if($av['aprovado'] != 2): ?>
            <form method="POST" action="<?= url('/master/avaliacoes/'.$av['id'].'/reprovar') ?>">
              <?= csrf_field() ?>
              <button class="btn btn-sm w-100 fw-bold" style="background:#f59e0b;color:#000;border:none">
                <i class="bi bi-slash-circle me-1"></i>Reprovar
              </button>
            </form>
            <?php endif; ?>
            <?php endif; ?>

            <form method="POST" action="<?= url('/master/avaliacoes/'.$av['id'].'/excluir') ?>"
                  onsubmit="return confirm('Excluir permanentemente?')">
              <?= csrf_field() ?>
              <button class="btn btn-sm w-100 fw-bold" style="background:#ef4444;color:#fff;border:none">
                <i class="bi bi-trash me-1"></i>Excluir
              </button>
            </form>
          </div>

        </div>

        <!-- Badge status -->
        <div class="mt-2">
          <?php if($av['situacao']==='contestada'): ?>
          <span style="background:#a855f722;color:#a855f7;border:1px solid #a855f744;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
            <i class="bi bi-flag-fill me-1"></i>Contestada — oculta do público
          </span>
          <?php elseif($av['situacao']==='oculta'): ?>
          <span style="background:#6b728022;color:#9ca3af;border:1px solid #6b728044;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
            <i class="bi bi-eye-slash me-1"></i>Removida (oculta)
          </span>
          <?php elseif($av['aprovado'] == 0): ?>
          <span style="background:#f59e0b22;color:#f59e0b;border:1px solid #f59e0b44;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
            <i class="bi bi-hourglass-split me-1"></i>Aguardando moderação
          </span>
          <?php elseif($av['aprovado'] == 1): ?>
          <span style="background:#22c55e22;color:#22c55e;border:1px solid #22c55e44;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
            <i class="bi bi-check-circle-fill me-1"></i>Publicada
          </span>
          <?php else: ?>
          <span style="background:#ef444422;color:#ef4444;border:1px solid #ef444444;border-radius:6px;font-size:.72rem;font-weight:700;padding:.2rem .6rem">
            <i class="bi bi-x-circle-fill me-1"></i>Reprovada
          </span>
          <?php endif; ?>
        </div>

      </div>
      <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="ms-card p-5 text-center">
      <i class="bi bi-star" style="font-size:3rem;color:#374151;display:block;margin-bottom:1rem"></i>
      <div style="color:#6b7280">Nenhuma avaliação <?= ['pendentes'=>'pendente','aprovadas'=>'aprovada','reprovadas'=>'reprovada','contestadas'=>'contestada'][$filtro] ?? '' ?> no momento.</div>
    </div>
    <?php endif; ?>

  </div>
</div>
