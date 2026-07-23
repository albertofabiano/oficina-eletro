<div class="page-content">
  <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
    <div>
      <h4 class="fw-bold mb-1"><i class="bi bi-clock-history me-2 text-primary"></i>Log de Ações</h4>
      <p class="text-muted small mb-0">Registro de ações dos usuários da empresa (auditoria). Mostrando as 300 mais recentes.</p>
    </div>
    <a href="<?= url('/empresa') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Voltar</a>
  </div>

  <form class="card border-0 shadow-sm p-3 mb-3" method="GET">
    <div class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1">Usuário</label>
        <select name="usuario" class="form-select" onchange="this.form.submit()">
          <option value="">Todos</option>
          <?php foreach ($usuarios as $u): ?>
          <option value="<?= $u['id'] ?>" <?= (int)$fUser === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold mb-1">Módulo</label>
        <select name="modulo" class="form-select" onchange="this.form.submit()">
          <option value="">Todos</option>
          <?php foreach ($modulos as $m): ?>
          <option value="<?= e($m) ?>" <?= $fMod === $m ? 'selected' : '' ?>><?= e(ucfirst($m)) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <?php if ($fUser || $fMod !== ''): ?>
      <div class="col-md-2">
        <a href="<?= url('/empresa/logs') ?>" class="btn btn-outline-secondary w-100"><i class="bi bi-x-lg me-1"></i>Limpar</a>
      </div>
      <?php endif; ?>
    </div>
  </form>

  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover mb-0 align-middle" style="font-size:.9rem">
        <thead class="table-light">
          <tr><th>Data / hora</th><th>Usuário</th><th>Módulo</th><th>Ação</th><th>Detalhes</th><th>IP</th></tr>
        </thead>
        <tbody>
          <?php
            $corAcao = ['excluir'=>'danger','criar'=>'success','fechar'=>'primary','editar'=>'warning','reabrir'=>'info'];
          ?>
          <?php foreach ($logs as $l):
            $det = '';
            if (!empty($l['detalhes'])) {
              $dec = json_decode($l['detalhes'], true);
              $det = is_array($dec) ? ($dec['texto'] ?? '') : (string) $l['detalhes'];
            }
          ?>
          <tr>
            <td style="white-space:nowrap"><?= date_br($l['criado_em'], true) ?></td>
            <td><?= e($l['usuario_nome'] ?? '—') ?></td>
            <td><span class="badge bg-light text-dark border text-uppercase" style="font-size:.7rem"><?= e($l['modulo']) ?></span></td>
            <td><span class="badge bg-<?= $corAcao[$l['acao']] ?? 'secondary' ?>"><?= e($l['acao']) ?></span></td>
            <td><?= e($det) ?><?php if (!empty($l['registro_id'])): ?> <span class="text-muted">#<?= (int)$l['registro_id'] ?></span><?php endif; ?></td>
            <td class="text-muted" style="font-size:.8rem"><?= e($l['ip'] ?? '') ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (!$logs): ?>
          <tr><td colspan="6" class="text-center text-muted py-4">Nenhuma ação registrada ainda.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
