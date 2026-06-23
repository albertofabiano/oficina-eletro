<div class="d-flex justify-content-between align-items-center mb-3">
  <form class="d-flex gap-2" method="GET">
    <input type="search" name="busca" class="form-control" placeholder="Nome, CPF, telefone..." value="<?= e($busca) ?>" style="width:280px">
    <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
  </form>
  <a href="<?= url('/clientes/novo') ?>" class="btn btn-primary"><i class="bi bi-person-plus"></i> Novo Cliente</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle">
      <thead class="table-light">
        <tr>
          <th>Nome</th><th>Contato</th><th>Cidade</th><th>OS</th><th>Status</th><th>Cadastro</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $c): ?>
        <tr>
          <td>
            <a href="<?= url('/clientes/' . $c['id']) ?>" class="fw-semibold text-decoration-none"><?= e($c['nome']) ?></a>
            <?php if ($c['tipo']==='pj'): ?><span class="badge bg-secondary ms-1">PJ</span><?php endif; ?>
            <div class="small text-muted"><?= e($c['cpf_cnpj']) ?></div>
          </td>
          <td>
            <?= e($c['telefone']) ?>
            <?php if ($c['whatsapp']): ?>
            <a href="https://wa.me/55<?= only_numbers($c['whatsapp']) ?>" target="_blank" class="text-success ms-1"><i class="bi bi-whatsapp"></i></a>
            <?php endif; ?>
            <div class="small text-muted"><?= e($c['email']) ?></div>
          </td>
          <td><?= e($c['cidade']) ?> <?= e($c['uf']) ?></td>
          <td><span class="badge bg-light text-dark border"><?= $c['total_os'] ?? 0 ?> OS</span></td>
          <td>
            <?php $map=['ativo'=>'success','inativo'=>'secondary','bloqueado'=>'danger']; ?>
            <span class="badge bg-<?= $map[$c['status']] ?? 'secondary' ?>"><?= ucfirst($c['status']) ?></span>
          </td>
          <td><?= date_br($c['criado_em']) ?></td>
          <td class="text-end">
            <a href="<?= url('/clientes/' . $c['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
            <a href="<?= url('/clientes/' . $c['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="7" class="text-center text-muted py-5">Nenhum cliente encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $paginator['total'] ?> clientes</small>
    <?= pagination($paginator, url('/clientes')) ?>
  </div>
  <?php endif; ?>
</div>
