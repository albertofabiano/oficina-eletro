<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <form class="d-flex gap-2 flex-grow-1" method="GET" style="min-width:200px;max-width:420px">
    <input type="search" name="busca" class="form-control" placeholder="Nome, CNPJ, e-mail..." value="<?= e($busca) ?>">
    <button class="btn btn-outline-secondary flex-shrink-0"><i class="bi bi-search"></i></button>
  </form>
  <a href="<?= url('/fornecedores/novo') ?>" class="btn btn-primary flex-shrink-0"><i class="bi bi-plus-lg"></i> Novo Fornecedor</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th>Fornecedor</th><th>CNPJ/CPF</th><th>Contato</th><th>Cidade</th><th>Status</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $f): ?>
        <tr>
          <td>
            <div class="fw-semibold"><?= e($f['razao_social']) ?></div>
            <?php if ($f['nome_fantasia']): ?><div class="text-muted small"><?= e($f['nome_fantasia']) ?></div><?php endif; ?>
          </td>
          <td><?= e($f['cnpj_cpf']) ?></td>
          <td>
            <?= e($f['telefone']) ?>
            <?php if ($f['email']): ?><div class="text-muted"><?= e($f['email']) ?></div><?php endif; ?>
          </td>
          <td><?= e($f['cidade']) ?> <?= e($f['uf']) ?></td>
          <td><span class="badge bg-<?= $f['ativo'] ? 'success' : 'secondary' ?>"><?= $f['ativo'] ? 'Ativo' : 'Inativo' ?></span></td>
          <td class="text-end">
            <a href="<?= url('/fornecedores/' . $f['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <a href="#" class="btn btn-sm btn-outline-danger" data-method="DELETE"
               data-href="<?= url('/fornecedores/' . $f['id']) ?>"
               data-confirm="Excluir este fornecedor?"><i class="bi bi-trash"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="6" class="text-center text-muted py-5">Nenhum fornecedor cadastrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between">
    <small class="text-muted"><?= $paginator['total'] ?> fornecedores</small>
    <?= pagination($paginator, url('/fornecedores')) ?>
  </div>
  <?php endif; ?>
</div>
