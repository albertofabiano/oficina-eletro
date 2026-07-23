<?php
  $origBadge = function(string $o): string {
    return match($o) {
      'landing'       => '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">Landing</span>',
      'reivindicacao' => '<span class="badge bg-warning bg-opacity-10 text-warning-emphasis border border-warning border-opacity-25">Reivindicação</span>',
      'diretorio'     => '<span class="badge" style="background:rgba(13,148,136,.1);color:#0d9488;border:1px solid rgba(13,148,136,.25)">Diretório</span>',
      'forum'         => '<span class="badge bg-info bg-opacity-10 text-info-emphasis border border-info border-opacity-25">Fórum</span>',
      default         => '<span class="badge bg-secondary bg-opacity-10 text-secondary">'.e(ucfirst($o)).'</span>',
    };
  };
?>
<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-person-lines-fill me-2 text-success"></i>Leads — lista de espera</h4>
  <p class="text-muted small mb-4" style="max-width:760px">
    Todos que demonstraram interesse no FixaOS: cadastros na <strong>landing</strong>, donos que
    <strong>reivindicaram o perfil</strong> no diretório e contatos do <strong>fórum</strong>.
    Este é o seu funil de early adopters — marque quem você já convidou para não perder o fio.
  </p>

  <!-- KPIs -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Total de leads</div>
        <div class="fs-3 fw-bold"><?= (int)$kpis['total'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">A convidar</div>
        <div class="fs-3 fw-bold text-success"><?= (int)$kpis['pendentes'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Já convidados</div>
        <div class="fs-3 fw-bold text-secondary"><?= (int)$kpis['convidados'] ?></div>
      </div></div>
    </div>
    <div class="col-6 col-md">
      <div class="card border-0 shadow-sm h-100"><div class="card-body py-3">
        <div class="text-muted small">Últimos 7 dias</div>
        <div class="fs-3 fw-bold text-primary">+<?= (int)$kpis['ultimos7'] ?></div>
      </div></div>
    </div>
  </div>

  <!-- Filtro por origem -->
  <?php $tabs = ['' => 'Todos ('.(int)$kpis['total'].')', 'landing' => 'Landing ('.(int)$kpis['landing'].')', 'reivindicacao' => 'Reivindicação ('.(int)$kpis['reivindicacao'].')', 'diretorio' => 'Diretório ('.(int)$kpis['diretorio'].')', 'forum' => 'Fórum ('.(int)$kpis['forum'].')']; ?>
  <ul class="nav nav-pills mb-3 small">
    <?php foreach ($tabs as $k => $lbl): ?>
      <li class="nav-item">
        <a class="nav-link py-1 <?= $filtro === $k ? 'active' : 'text-muted' ?>"
           href="<?= url('/master/leads' . ($k ? '?origem='.$k : '')) ?>"><?= $lbl ?></a>
      </li>
    <?php endforeach; ?>
  </ul>

  <?php if (!$leads): ?>
    <div class="alert alert-light border">Nenhum lead nesta visão ainda.</div>
  <?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr><th>E-mail</th><th>Origem</th><th>Empresa / Contato</th><th>Entrou</th><th>Status</th><th class="text-end">Ação</th></tr>
        </thead>
        <tbody>
        <?php foreach ($leads as $l): ?>
          <tr class="<?= $l['convidado'] ? 'opacity-75' : '' ?>">
            <td>
              <a href="mailto:<?= e($l['email']) ?>" class="fw-semibold text-decoration-none"><?= e($l['email']) ?></a>
            </td>
            <td><?= $origBadge($l['origem']) ?></td>
            <td>
              <?php if ($l['empresa_nome']): ?>
                <a href="<?= url('/assistencias/' . $l['empresa_slug']) ?>" target="_blank" class="text-decoration-none"><?= e($l['empresa_nome']) ?></a>
                <?php if ($l['reiv_nome']): ?><div class="text-muted"><?= e($l['reiv_nome']) ?><?= $l['reiv_whats'] ? ' · <i class="bi bi-whatsapp"></i> '.e($l['reiv_whats']) : '' ?></div><?php endif; ?>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
            <td class="text-muted" style="white-space:nowrap"><?= date_br($l['criado_em']) ?></td>
            <td>
              <?php if ($l['convidado']): ?>
                <span class="badge bg-success"><i class="bi bi-check-lg"></i> Convidado</span>
                <?php if ($l['convidado_em']): ?><div class="text-muted" style="font-size:.72rem"><?= date_br($l['convidado_em']) ?></div><?php endif; ?>
              <?php else: ?>
                <span class="badge bg-warning text-dark">Aguardando</span>
              <?php endif; ?>
            </td>
            <td class="text-end" style="white-space:nowrap">
              <form method="POST" action="<?= url('/master/leads/' . $l['id'] . '/convidar' . ($filtro ? '?origem='.$filtro : '')) ?>" class="d-inline">
                <?php if ($l['convidado']): ?>
                  <button class="btn btn-sm btn-outline-secondary" title="Desfazer marcação"><i class="bi bi-arrow-counterclockwise"></i></button>
                <?php else: ?>
                  <button class="btn btn-sm btn-success"><i class="bi bi-send-check"></i> Convidei</button>
                <?php endif; ?>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <p class="text-muted small mt-2 mb-0"><i class="bi bi-info-circle me-1"></i>Dica: o botão <strong>Convidei</strong> só registra que você já falou com o lead — não dispara e-mail automático.</p>
  <?php endif; ?>
</div>
