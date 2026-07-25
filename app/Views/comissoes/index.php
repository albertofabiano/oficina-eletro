<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0">Comissões de Técnicos</h5>
  <a href="<?= url('/comissoes/nova') ?>" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Nova Comissão
  </a>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">Pendente</div>
      <div class="fw-bold text-warning fs-5"><?= money($totais['pendente']) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">Já pago</div>
      <div class="fw-bold text-success fs-5"><?= money($totais['pago']) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-4">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small fw-semibold">TOTAL</div>
      <div class="fw-bold fs-4" style="color:#1e3a5f"><?= money($totais['total']) ?></div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body">
    <form method="GET" action="<?= url('/comissoes') ?>" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Técnico</label>
        <select name="tecnico_id" class="form-select form-select-sm">
          <option value="">Todos</option>
          <?php foreach ($tecnicos as $t): ?>
          <option value="<?= $t['id'] ?>" <?= ($filtros['tecnico_id'] ?? '') == $t['id'] ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Status</label>
        <select name="pago" class="form-select form-select-sm">
          <option value="">Todos</option>
          <option value="0" <?= ($filtros['pago'] ?? '') === '0' ? 'selected' : '' ?>>Pendente</option>
          <option value="1" <?= ($filtros['pago'] ?? '') === '1' ? 'selected' : '' ?>>Pago</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold">De</label>
        <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= e($filtros['data_inicio'] ?? '') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small fw-semibold">Até</label>
        <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= e($filtros['data_fim'] ?? '') ?>">
      </div>
      <div class="col-md-1">
        <button class="btn btn-outline-primary btn-sm w-100"><i class="bi bi-search"></i></button>
      </div>
    </form>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Técnico</th>
          <th>OS</th>
          <th class="text-end">Valor do serviço</th>
          <th class="text-end">%</th>
          <th class="text-end">Comissão</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$comissoes): ?>
        <tr><td colspan="7" class="text-center text-muted py-5">
          <i class="bi bi-cash-coin fs-1 d-block mb-2 opacity-50"></i>Nenhuma comissão registrada ainda.
        </td></tr>
        <?php endif; ?>
        <?php foreach ($comissoes as $c): ?>
        <tr>
          <td class="fw-semibold"><?= e($c['tecnico_nome']) ?></td>
          <td><?= $c['os_numero'] ? 'OS: ' . e($c['os_numero']) : '<span class="text-muted">—</span>' ?></td>
          <td class="text-end"><?= money($c['valor_base']) ?></td>
          <td class="text-end"><?= number_format($c['percentual'], 2, ',', '.') ?>%</td>
          <td class="text-end fw-bold"><?= money($c['valor_comissao']) ?></td>
          <td>
            <?php if ((int) $c['pago'] === 1): ?>
              <span class="badge bg-success">Pago em <?= date_br($c['data_pagamento']) ?></span>
            <?php else: ?>
              <span class="badge bg-warning text-dark">Pendente</span>
            <?php endif; ?>
          </td>
          <td class="text-end" style="white-space:nowrap">
            <?php if ((int) $c['pago'] === 0): ?>
            <form method="POST" action="<?= url('/comissoes/' . $c['id'] . '/pagar') ?>" class="d-inline" onsubmit="return confirm('Marcar como paga e lançar R$ <?= number_format($c['valor_comissao'],2,',','.') ?> como despesa no Financeiro?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-success"><i class="bi bi-check-lg me-1"></i>Marcar paga</button>
            </form>
            <form method="POST" action="<?= url('/comissoes/' . $c['id'] . '/excluir') ?>" class="d-inline" onsubmit="return confirm('Excluir esta comissão?');">
              <?= csrf_field() ?>
              <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
            </form>
            <?php else: ?>
            <span class="text-muted small">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
