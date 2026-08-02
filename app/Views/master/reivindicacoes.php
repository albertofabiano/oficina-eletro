<div class="container-fluid">
  <h4 class="mb-1"><i class="bi bi-patch-check me-2 text-warning"></i>Reivindicações de perfil</h4>
  <p class="text-muted small mb-4" style="max-width:720px">
    Donos de assistências que pediram para assumir o perfil listado no diretório público.
    Ao <strong>aprovar</strong>, a empresa vira cliente: cria-se o usuário admin (login com o e-mail e a senha que ele definiu), inicia trial de 7 dias e o selo "não reivindicado" some. Confira os dados antes de aprovar.
  </p>

  <?php if (!$pendentes): ?>
    <div class="alert alert-light border">Nenhum pedido pendente. 👍</div>
  <?php else: ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-warning bg-opacity-10 fw-semibold text-warning-emphasis">
      <i class="bi bi-hourglass-split me-1"></i><?= count($pendentes) ?> pedido(s) pendente(s)
    </div>
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0 small">
        <thead class="table-light"><tr><th>Empresa</th><th>Solicitante</th><th>Contato</th><th>Data</th><th class="text-end">Ação</th></tr></thead>
        <tbody>
        <?php foreach ($pendentes as $p): ?>
          <tr>
            <td>
              <a href="<?= url('/assistencias/' . $p['slug']) ?>" target="_blank" class="fw-semibold text-decoration-none"><?= e($p['nome_fantasia']) ?></a>
              <div class="text-muted"><?= e($p['cidade']) ?>/<?= e($p['uf']) ?></div>
            </td>
            <td><?= e($p['nome']) ?></td>
            <td><?= e($p['email']) ?><div class="text-muted"><i class="bi bi-whatsapp"></i> <?= e($p['whatsapp']) ?></div></td>
            <td class="text-muted"><?= date_br($p['criado_em']) ?></td>
            <td class="text-end" style="white-space:nowrap">
              <form method="POST" action="<?= url('/master/reivindicacoes/' . $p['id'] . '/aprovar') ?>" class="d-inline"
                    onsubmit="return confirm('Aprovar a reivindicação de <?= e($p['nome_fantasia']) ?>? A empresa virará cliente e poderá fazer login.')">
                <button class="btn btn-sm btn-success"><i class="bi bi-check-lg"></i> Aprovar</button>
              </form>
              <form method="POST" action="<?= url('/master/reivindicacoes/' . $p['id'] . '/rejeitar') ?>" class="d-inline"
                    onsubmit="return confirm('Rejeitar este pedido?')">
                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($historico): ?>
  <h6 class="text-muted mb-2">Histórico</h6>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-sm small align-middle mb-0">
        <tbody>
        <?php foreach ($historico as $h): ?>
          <tr>
            <td><?= e($h['nome_fantasia']) ?></td>
            <td class="text-muted"><?= e($h['nome']) ?> · <?= e($h['email']) ?></td>
            <td><span class="badge bg-<?= $h['status'] === 'aprovada' ? 'success' : 'secondary' ?>"><?= ucfirst($h['status']) ?></span></td>
            <td class="text-muted text-end"><?= $h['processado_em'] ? date_br($h['processado_em']) : '' ?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>
</div>
