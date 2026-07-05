<?php if ($alertas): ?>
<div class="alert alert-warning d-flex align-items-center gap-2 mb-3">
  <i class="bi bi-exclamation-triangle-fill"></i>
  <strong><?= count($alertas) ?> produto(s) abaixo do estoque mínimo!</strong>
</div>
<?php endif; ?>

<div class="d-flex flex-wrap gap-2 mb-3">
  <form class="d-flex flex-wrap gap-2 flex-grow-1" method="GET">
    <input type="search" name="busca" class="form-control" placeholder="Nome, código..." value="<?= e($busca) ?>" style="flex:2 1 160px">
    <select name="categoria_id" class="form-select" style="flex:1 1 140px">
      <option value="">Todas categorias</option>
      <?php foreach ($categorias as $cat): ?>
      <option value="<?= $cat['id'] ?>"><?= e($cat['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-outline-secondary flex-shrink-0"><i class="bi bi-search"></i></button>
  </form>
  <a href="<?= url('/produtos/novo') ?>" class="btn btn-primary flex-shrink-0"><i class="bi bi-plus-lg"></i> Novo Produto</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th>Código</th><th>Produto</th><th>Categoria</th><th>Estoque</th><th>Mínimo</th><th>Localização</th><th>Custo</th><th>Venda</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $p): ?>
        <?php $baixo = $p['estoque_atual'] <= $p['estoque_minimo']; ?>
        <tr class="<?= $baixo ? 'table-warning' : '' ?>">
          <td><?= e($p['codigo']) ?></td>
          <td>
            <?= e($p['nome']) ?>
            <?php if ($baixo): ?><i class="bi bi-exclamation-triangle text-warning ms-1"></i><?php endif; ?>
          </td>
          <td><?= e($p['categoria_nome'] ?? '—') ?></td>
          <td class="fw-bold <?= $baixo ? 'text-danger' : '' ?>"><?= $p['estoque_atual'] ?> <?= e($p['unidade']) ?></td>
          <td><?= $p['estoque_minimo'] ?></td>
          <td><?= e($p['localizacao'] ?? '—') ?></td>
          <td><?= money($p['valor_custo']) ?></td>
          <td><?= money($p['valor_venda']) ?></td>
          <td class="text-end">
            <a href="<?= url('/produtos/' . $p['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">Nenhum produto.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between">
    <small class="text-muted"><?= $paginator['total'] ?> produtos</small>
    <?= pagination($paginator, url('/produtos')) ?>
  </div>
  <?php endif; ?>
</div>
