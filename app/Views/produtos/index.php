<?php $podeEditar = \App\Core\Auth::can('estoque', 'editar'); ?>
<?php if ($alertas): ?>
<div class="alert alert-warning d-flex align-items-center justify-content-between gap-2 mb-3">
  <span><i class="bi bi-exclamation-triangle-fill me-1"></i><strong><?= count($alertas) ?> produto(s)</strong> no estoque mínimo ou zerados.</span>
  <?php if (empty($soBaixo)): ?>
  <a href="<?= url('/produtos?baixo=1') ?>" class="btn btn-sm btn-warning"><i class="bi bi-funnel me-1"></i>Ver só esses</a>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="d-flex gap-2 mb-3 flex-wrap">
  <form class="d-flex flex-wrap gap-2 flex-grow-1" method="GET">
    <input type="search" name="busca" class="form-control" placeholder="Nome, código, código de barras..." value="<?= e($busca) ?>" style="flex:2 1 160px">
    <select name="categoria_id" class="form-select" style="flex:1 1 140px">
      <option value="">Todas categorias</option>
      <?php foreach ($categorias as $cat): ?>
      <option value="<?= $cat['id'] ?>"><?= e($cat['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <?php if (!empty($soBaixo)): ?><input type="hidden" name="baixo" value="1"><?php endif; ?>
    <button class="btn btn-outline-secondary flex-shrink-0"><i class="bi bi-search"></i></button>
  </form>
  <?php if (!empty($soBaixo)): ?>
  <a href="<?= url('/produtos') ?>" class="btn btn-warning flex-shrink-0"><i class="bi bi-x-lg me-1"></i>Filtro: estoque baixo</a>
  <?php endif; ?>
  <a href="<?= url('/produtos/novo') ?>" class="btn btn-primary flex-shrink-0"><i class="bi bi-plus-lg"></i> Novo Produto</a>
</div>

<style>
@media (max-width: 767.98px) {
  #produtosTabela th:last-child, #produtosTabela td:last-child {
    position: sticky; right: 0; z-index: 2;
    background: #fff;
    box-shadow: -6px 0 8px -6px rgba(0,0,0,.15);
  }
  #produtosTabela tr.table-danger td:last-child  { background: #f8d7da; }
  #produtosTabela tr.table-warning td:last-child { background: #fff3cd; }
}
</style>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table id="produtosTabela" class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th style="width:26px"></th><th>Código</th><th>Produto</th><th>Categoria</th><th>Estoque</th><th>Mínimo</th><th>Localização</th><th>Custo</th><th>Venda</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $p): ?>
        <?php
          $atual  = (float) $p['estoque_atual'];
          $minimo = (float) $p['estoque_minimo'];
          $zerado = $atual <= 0;
          $baixo  = !$zerado && $atual <= $minimo;
        ?>
        <tr class="produto-row <?= $zerado ? 'table-danger' : ($baixo ? 'table-warning' : '') ?>" style="cursor:pointer" title="Clique para ver detalhes">
          <td class="text-center text-muted"><i class="bi bi-chevron-right produto-caret" style="transition:transform .15s;font-size:.75rem"></i></td>
          <td><?= e($p['codigo']) ?></td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <?php if (!empty($p['imagem'])): ?>
              <img src="<?= url('/uploads/produtos/' . e($p['imagem'])) ?>" alt=""
                   class="rounded border flex-shrink-0" style="width:34px;height:34px;object-fit:contain;background:#fff">
              <?php else: ?>
              <span class="rounded border d-inline-flex align-items-center justify-content-center flex-shrink-0 text-muted"
                    style="width:34px;height:34px;background:#f8f9fa"><i class="bi bi-box-seam"></i></span>
              <?php endif; ?>
              <span>
                <?= e($p['nome']) ?>
                <?php if ($zerado): ?><span class="badge bg-danger ms-1">Sem estoque</span>
                <?php elseif ($baixo): ?><span class="badge bg-warning text-dark ms-1">Estoque baixo</span><?php endif; ?>
              </span>
            </div>
          </td>
          <td><?= e($p['categoria_nome'] ?? '—') ?></td>
          <td class="fw-bold <?= $zerado ? 'text-danger' : ($baixo ? 'text-warning' : '') ?>"><?= $atual + 0 ?> <?= e($p['unidade']) ?></td>
          <td><?= $minimo + 0 ?></td>
          <td><?= e($p['localizacao'] ?? '—') ?></td>
          <td><?= money($p['valor_custo']) ?></td>
          <td><?= money($p['valor_venda']) ?></td>
          <td class="text-end text-nowrap">
            <?php if ($podeEditar): ?>
            <button type="button" class="btn btn-sm btn-outline-success btn-entrada"
                    data-id="<?= $p['id'] ?>" data-nome="<?= e($p['nome']) ?>"
                    data-unidade="<?= e($p['unidade']) ?>" data-atual="<?= $atual + 0 ?>"
                    data-custo="<?= number_format((float) $p['valor_custo'], 2, ',', '') ?>"
                    title="Dar entrada / Repor estoque"><i class="bi bi-plus-circle"></i></button>
            <?php endif; ?>
            <a href="<?= url('/produtos/' . $p['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil"></i></a>
            <?php if (\App\Core\Auth::isAdmin()): ?>
            <button type="button" class="btn btn-sm btn-outline-danger btn-excluir-produto"
                    data-id="<?= $p['id'] ?>" data-nome="<?= e($p['nome']) ?>" title="Excluir"><i class="bi bi-trash"></i></button>
            <?php endif; ?>
          </td>
        </tr>
        <tr class="produto-detail" style="display:none">
          <td colspan="10" style="background:#f8fafc;border-top:0">
            <div class="d-flex flex-wrap gap-4 px-2 pt-2" style="font-size:.85rem">
              <div style="min-width:150px"><div class="text-muted" style="font-size:.72rem">Fornecedor</div><?= e($p['fornecedor_nome'] ?? '') ?: '--' ?></div>
              <div><div class="text-muted" style="font-size:.72rem">Código de barras</div><?= e($p['codigo_barras'] ?? '') ?: '--' ?></div>
              <div><div class="text-muted" style="font-size:.72rem">NCM</div><?= e($p['ncm'] ?? '') ?: '--' ?></div>
              <div><div class="text-muted" style="font-size:.72rem">Margem de lucro</div><?= $p['margem_lucro'] ? number_format((float) $p['margem_lucro'], 2, ',', '.') . '%' : '--' ?></div>
              <div><div class="text-muted" style="font-size:.72rem">Cadastrado em</div><?= date_br($p['criado_em'] ?? null, true) ?: '--' ?></div>
              <div style="flex:1;min-width:220px"><div class="text-muted" style="font-size:.72rem">Descrição</div><?= e($p['descricao'] ?? '') ?: '<span class="text-muted">—</span>' ?></div>
            </div>
            <div class="px-2 py-2 d-flex gap-2 flex-wrap align-items-center">
              <a href="<?= url('/produtos/' . $p['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Editar</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="10" class="text-center text-muted py-5">Nenhum produto.</td></tr>
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

<script>
document.querySelectorAll('tr.produto-row').forEach(function (row) {
  row.addEventListener('click', function (e) {
    if (e.target.closest('a, button')) return;
    var det = row.nextElementSibling;
    if (!det || !det.classList.contains('produto-detail')) return;
    var aberto = det.style.display !== 'none';
    det.style.display = aberto ? 'none' : 'table-row';
    var car = row.querySelector('.produto-caret');
    if (car) car.style.transform = aberto ? '' : 'rotate(90deg)';
  });
});
</script>

<?php if ($podeEditar): ?>
<!-- Modal: Dar entrada de estoque -->
<div class="modal fade" id="modalEntrada" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formEntrada">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-plus-circle text-success me-2"></i>Dar entrada de estoque</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Produto: <strong id="entProdNome"></strong><br>
          <small class="text-muted">Saldo atual: <span id="entProdAtual"></span> <span id="entProdUnid"></span></small>
        </p>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Quantidade a adicionar *</label>
            <input type="text" name="quantidade" id="entQtd" class="form-control" inputmode="decimal" placeholder="0" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Custo unitário (opcional)</label>
            <input type="text" name="valor_custo" id="entCusto" class="form-control" inputmode="decimal" placeholder="0,00">
            <div class="form-text">Atualiza o custo do produto.</div>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Motivo</label>
            <input type="text" name="motivo" class="form-control" placeholder="Ex: compra, ajuste, devolução" value="Reposição de estoque">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success"><i class="bi bi-check-lg me-1"></i>Dar entrada</button>
      </div>
    </form>
  </div>
</div>
<script>
document.querySelectorAll('.btn-entrada').forEach(function (b) {
  b.addEventListener('click', function () {
    document.getElementById('formEntrada').action = '<?= url('/produtos') ?>/' + b.dataset.id + '/entrada';
    document.getElementById('entProdNome').textContent  = b.dataset.nome;
    document.getElementById('entProdAtual').textContent = b.dataset.atual;
    document.getElementById('entProdUnid').textContent  = b.dataset.unidade;
    document.getElementById('entCusto').value = b.dataset.custo;
    document.getElementById('entQtd').value = '';
    new bootstrap.Modal(document.getElementById('modalEntrada')).show();
    setTimeout(function () { document.getElementById('entQtd').focus(); }, 300);
  });
});
</script>
<?php endif; ?>

<?php if (\App\Core\Auth::isAdmin()): ?>
<!-- ── MODAL EXCLUIR PRODUTO (só admin) ──────────────────────────── -->
<div class="modal fade" id="modalExcluirProduto" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formExcluirProduto">
      <?= csrf_field() ?>
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Excluir <span id="excluirProdNome"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-flex gap-2 mb-3">
          <i class="bi bi-trash3 fs-4"></i>
          <div><strong>Esta ação é IRREVERSÍVEL.</strong> O produto e seu histórico de movimentação de estoque serão apagados <strong>permanentemente</strong> e não poderão ser recuperados.</div>
        </div>
        <p class="mb-2 small text-muted"><i class="bi bi-shield-check me-1"></i>A exclusão fica registrada no <strong>Registro de Ações</strong> (quem excluiu e quando).</p>
        <label class="form-label small fw-semibold mb-1"><i class="bi bi-lock-fill me-1"></i>Confirme sua senha de login para excluir</label>
        <input type="password" name="senha" id="excluirProdSenha" class="form-control" autocomplete="off" placeholder="Sua senha" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-trash me-1"></i>Excluir permanentemente</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirExcluirProduto(id, nome) {
  document.getElementById('formExcluirProduto').action = '<?= url('/produtos') ?>/' + id + '/excluir';
  document.getElementById('excluirProdNome').textContent = nome;
  document.getElementById('excluirProdSenha').value = '';
  new bootstrap.Modal(document.getElementById('modalExcluirProduto')).show();
}
document.querySelectorAll('.btn-excluir-produto').forEach(function (b) {
  b.addEventListener('click', function () { abrirExcluirProduto(b.dataset.id, b.dataset.nome); });
});
</script>
<?php endif; ?>
