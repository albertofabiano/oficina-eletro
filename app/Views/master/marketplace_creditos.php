<div class="d-flex justify-content-between align-items-center mb-4">
  <div>
    <h5 class="text-white fw-bold mb-0">Marketplace — Créditos das Empresas</h5>
    <small style="color:#6c757d">Gerencie o saldo de créditos para publicar anúncios</small>
  </div>
  <form class="d-flex gap-2" method="GET">
    <input type="search" name="busca" class="form-control form-control-sm"
      placeholder="Buscar empresa..." value="<?= e($busca) ?>" style="width:220px">
    <button class="btn btn-sm btn-outline-secondary"><i class="bi bi-search"></i></button>
  </form>
</div>

<div class="ms-card">
  <div class="table-responsive">
    <table class="table mb-0 align-middle">
      <thead>
        <tr>
          <th>Empresa</th>
          <th class="text-center">Saldo atual</th>
          <th>Adicionar créditos</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($saldos as $s): ?>
        <tr>
          <td>
            <div class="text-white fw-semibold"><?= e($s['nome_fantasia']) ?></div>
            <div class="text-muted small"><?= e($s['email']) ?></div>
          </td>
          <td class="text-center">
            <span class="badge fs-6 <?= $s['saldo_creditos'] > 0 ? 'bg-success' : 'bg-secondary' ?>">
              <i class="bi bi-coin me-1"></i><?= $s['saldo_creditos'] ?>
            </span>
          </td>
          <td>
            <form method="POST" action="<?= url('/master/marketplace/creditos/' . $s['empresa_id']) ?>"
                  class="d-flex gap-2 align-items-center">
              <?= csrf_field() ?>
              <input type="number" name="quantidade" class="form-control form-control-sm"
                min="1" max="9999" value="10" style="width:80px"
                onclick="this.select()"
                title="Quantidade de créditos a adicionar">
              <input type="text" name="justificativa" class="form-control form-control-sm"
                placeholder="Justificativa..." value="Pacote de créditos adicionado pelo admin"
                style="width:260px">
              <button type="submit" class="btn btn-success btn-sm fw-semibold">
                <i class="bi bi-plus-lg me-1"></i>Adicionar
              </button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$saldos): ?>
        <tr><td colspan="3" class="text-center text-muted py-4">Nenhuma empresa encontrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
