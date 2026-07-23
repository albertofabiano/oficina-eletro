<?php
$saldo = ($resumo['receitas_pagas'] ?? 0) - ($resumo['despesas_pagas'] ?? 0);
$fonteMap = [];
foreach ($porFonte as $f) $fonteMap[$f['fonte']] = $f['total'];
$receitaOs     = $fonteMap['os'] ?? 0;
$receitaManual = $fonteMap['lancamento'] ?? 0;
?>

<!-- ── Cards de saldo unificado ──────────────── -->
<div class="row g-3 mb-3">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">Receitas pagas</div>
      <div class="fw-bold text-success fs-5"><?= money($resumo['receitas_pagas'] ?? 0) ?></div>
      <div class="d-flex gap-1 mt-1 flex-wrap">
        <?php if ($receitaOs): ?>
        <span class="badge bg-success bg-opacity-25 text-success" style="font-size:.68rem">
          <i class="bi bi-clipboard2-check"></i> OS <?= money($receitaOs) ?>
        </span>
        <?php endif; ?>
        <?php if ($receitaManual): ?>
        <span class="badge bg-primary bg-opacity-25 text-primary" style="font-size:.68rem">
          <i class="bi bi-receipt"></i> Manual <?= money($receitaManual) ?>
        </span>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">Despesas pagas</div>
      <div class="fw-bold text-danger fs-5"><?= money($resumo['despesas_pagas'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3">
      <div class="text-muted small">A receber</div>
      <div class="fw-bold text-warning fs-5"><?= money($resumo['receitas_pendentes'] ?? 0) ?></div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm p-3 border <?= $saldo >= 0 ? 'border-success' : 'border-danger' ?>">
      <div class="text-muted small fw-semibold">SALDO UNIFICADO</div>
      <div class="fw-bold fs-4 <?= $saldo >= 0 ? 'text-success' : 'text-danger' ?>"><?= money($saldo) ?></div>
      <div class="form-text">OS + lançamentos − despesas</div>
    </div>
  </div>
</div>

<!-- ── OS fechadas com pagamento pendente ────── -->
<?php if (!empty($osPendentes)): ?>
<div class="card border-0 shadow-sm border border-warning mb-3">
  <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center">
    <span class="fw-semibold text-warning-emphasis">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      OS fechadas aguardando pagamento (<?= count($osPendentes) ?>)
    </span>
    <span class="text-muted small">Clique em "Receber" para registrar</span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th>OS</th><th>Cliente</th><th>Conclusão</th><th>Valor</th><th>Situação</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($osPendentes as $os): ?>
        <tr>
          <td><a href="<?= url('/os/' . $os['id']) ?>" class="fw-semibold">OS: <?= e($os['numero']) ?></a></td>
          <td><?= e($os['cliente_nome']) ?></td>
          <td><?= date_br($os['data_conclusao']) ?></td>
          <td class="fw-semibold"><?= money($os['valor_total']) ?></td>
          <td><span class="badge bg-<?= $os['situacao_pagamento']==='parcial'?'info':'warning' ?>"><?= ucfirst($os['situacao_pagamento']) ?></span></td>
          <td>
            <button class="btn btn-sm btn-success" onclick="receberOs(<?= $os['id'] ?>, '<?= money($os['valor_total']) ?>')">
              <i class="bi bi-cash-coin me-1"></i>Receber
            </button>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<?php if ($vencendo): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 mb-3">
  <i class="bi bi-exclamation-triangle-fill fs-5"></i>
  <strong><?= count($vencendo) ?> lançamento(s) vencido(s)!</strong>
</div>
<?php endif; ?>

<!-- ── Filtros ─────────────────────────────────── -->
<form class="card border-0 shadow-sm p-3 mb-3" method="GET">
  <div class="row g-2 align-items-end">
    <div class="col-md-2">
      <select name="tipo" class="form-select form-select-sm">
        <option value="">Todos tipos</option>
        <option value="receita" <?= ($filtros['tipo']??'')==='receita'?'selected':'' ?>>Receitas</option>
        <option value="despesa" <?= ($filtros['tipo']??'')==='despesa'?'selected':'' ?>>Despesas</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="fonte" class="form-select form-select-sm">
        <option value="">Todas origens</option>
        <option value="os"         <?= ($filtros['fonte']??'')==='os'?'selected':'' ?>>OS fechadas</option>
        <option value="lancamento" <?= ($filtros['fonte']??'')==='lancamento'?'selected':'' ?>>Lançamentos manuais</option>
      </select>
    </div>
    <div class="col-md-2">
      <select name="status" class="form-select form-select-sm">
        <option value="">Todos status</option>
        <option value="pago"     <?= ($filtros['status']??'')==='pago'?'selected':'' ?>>Pago</option>
        <option value="pendente" <?= ($filtros['status']??'')==='pendente'?'selected':'' ?>>Pendente</option>
        <option value="parcial"  <?= ($filtros['status']??'')==='parcial'?'selected':'' ?>>Parcial</option>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="data_inicio" class="form-control form-control-sm" value="<?= e($filtros['data_inicio']) ?>">
    </div>
    <div class="col-md-2">
      <input type="date" name="data_fim" class="form-control form-control-sm" value="<?= e($filtros['data_fim']) ?>">
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-outline-secondary btn-sm flex-fill"><i class="bi bi-search"></i></button>
      <button type="button" class="btn btn-primary btn-sm flex-fill" data-bs-toggle="modal" data-bs-target="#modalLanc">
        <i class="bi bi-plus-lg"></i>
      </button>
    </div>
  </div>
</form>

<!-- ── Tabela unificada ──────────────────────────── -->
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light">
        <tr><th>Origem</th><th>Descrição</th><th>Categoria</th><th>Tipo</th><th>Vencimento</th><th>Valor</th><th>Status</th><th>Pagamento</th><th></th></tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $l): ?>
        <tr>
          <td>
            <?php if ($l['fonte'] === 'os'): ?>
            <span class="badge bg-success bg-opacity-15 text-success border border-success" style="font-size:.72rem">
              <i class="bi bi-clipboard2-check"></i> OS
            </span>
            <?php else: ?>
            <span class="badge bg-primary bg-opacity-15 text-primary border border-primary" style="font-size:.72rem">
              <i class="bi bi-receipt"></i> Manual
            </span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!empty($l['numero_os'])): ?>
            <a href="<?= url('/os/' . $l['ref_id']) ?>" class="fw-semibold text-decoration-none">OS: <?= e($l['numero_os']) ?></a>
            <div class="text-muted"><?= e($l['cliente_nome'] ?? '') ?></div>
            <?php else: ?>
            <?= e($l['descricao']) ?>
            <?php if ($l['cliente_nome'] ?? null): ?><div class="text-muted"><?= e($l['cliente_nome']) ?></div><?php endif; ?>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($l['categoria_nome'] ?? null): ?>
            <span class="badge" style="background:<?= e($l['categoria_cor'] ?? '#6c757d') ?>"><?= e($l['categoria_nome']) ?></span>
            <?php else: ?>—<?php endif; ?>
          </td>
          <td><span class="badge bg-<?= $l['tipo']==='receita'?'success':'danger' ?>"><?= ucfirst($l['tipo']) ?></span></td>
          <td><?= date_br($l['data_vencimento']) ?></td>
          <td class="fw-semibold <?= $l['tipo']==='receita'?'text-success':'text-danger' ?>" style="white-space:nowrap">
            <?= money($l['valor']) ?>
            <?php if ($l['tipo']==='receita' && ($l['taxa_cartao'] ?? 0) > 0): ?>
            <div class="small text-danger" style="font-weight:400"><i class="bi bi-credit-card-2-front"></i> maquininha − <?= money($l['taxa_cartao']) ?></div>
            <div class="small" style="font-weight:600;color:#0f766e"><i class="bi bi-wallet2"></i> ganho real <?= money($l['valor'] - $l['taxa_cartao']) ?></div>
            <?php endif; ?>
          </td>
          <td>
            <?php $sm=['pendente'=>'warning','pago'=>'success','cancelado'=>'secondary','parcial'=>'info']; ?>
            <span class="badge bg-<?= $sm[$l['status']] ?? 'secondary' ?>"><?= ucfirst($l['status']) ?></span>
          </td>
          <td><?= ($l['data_pagamento'] ?? null) ? date_br($l['data_pagamento']) : '—' ?></td>
          <td class="text-end d-flex gap-1 justify-content-end">
            <?php if ($l['status']==='pendente' && $l['fonte']==='lancamento'): ?>
            <button class="btn btn-sm btn-success" onclick="pagar(<?= $l['ref_id'] ?>)" title="Marcar como pago"><i class="bi bi-check-lg"></i></button>
            <?php endif; ?>
            <?php if ($l['fonte']==='lancamento'): ?>
            <a href="<?= url('/financeiro/' . $l['ref_id']) ?>" class="btn btn-sm btn-outline-danger"
               data-method="DELETE" data-confirm="Excluir este lançamento?">
              <i class="bi bi-trash"></i>
            </a>
            <?php else: ?>
            <a href="<?= url('/os/' . $l['ref_id']) ?>" class="btn btn-sm btn-outline-secondary" title="Ver OS">
              <i class="bi bi-eye"></i>
            </a>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">Nenhum registro encontrado.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between">
    <small class="text-muted"><?= $paginator['total'] ?> registros</small>
    <?= pagination($paginator, url('/financeiro')) ?>
  </div>
  <?php endif; ?>
</div>

<!-- ── Modal receber OS ─────────────────────────── -->
<div class="modal fade" id="modalReceberOs" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Receber pagamento</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <p class="mb-3">Valor: <strong id="osValorLabel"></strong></p>
        <label class="form-label small fw-semibold">Forma de pagamento</label>
        <select id="osFormaPagto" class="form-select">
          <option value="dinheiro">Dinheiro</option>
          <option value="pix">PIX</option>
          <option value="cartao_credito">Cartão de Crédito</option>
          <option value="cartao_debito">Cartão de Débito</option>
          <option value="transferencia">Transferência</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-success" id="btnConfReceberOs"><i class="bi bi-check-lg"></i> Confirmar</button>
      </div>
    </div>
  </div>
</div>

<!-- ── Modal Lançamento Manual ──────────────────── -->
<div class="modal fade" id="modalLanc" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/financeiro') ?>">
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Novo Lançamento Manual</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label small fw-semibold">Tipo</label>
            <select name="tipo" class="form-select" required>
              <option value="receita">Receita</option>
              <option value="despesa">Despesa</option>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Conta</label>
            <select name="conta_id" class="form-select" required>
              <?php foreach ($contas as $c): ?>
              <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Descrição *</label>
            <input type="text" name="descricao" class="form-control" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Categoria</label>
            <select name="categoria_id" class="form-select">
              <option value="">—</option>
              <?php foreach ($categorias as $cat): ?>
              <option value="<?= $cat['id'] ?>"><?= e('[' . ucfirst($cat['tipo']) . '] ' . $cat['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Valor *</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor" class="form-control" placeholder="0,00" required>
            </div>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Vencimento</label>
            <input type="date" name="data_vencimento" class="form-control" value="<?= date('Y-m-d') ?>" required>
          </div>
          <div class="col-6">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status" class="form-select">
              <option value="pendente">Pendente</option>
              <option value="pago">Pago</option>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Forma de pagamento</label>
            <select name="forma_pagamento" class="form-select">
              <option value="">—</option>
              <option value="dinheiro">Dinheiro</option>
              <option value="pix">PIX</option>
              <option value="cartao_credito">Cartão de Crédito</option>
              <option value="cartao_debito">Cartão de Débito</option>
              <option value="boleto">Boleto</option>
              <option value="transferencia">Transferência</option>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
async function pagar(id) {
  const forma = prompt('Forma de pagamento:', 'pix');
  if (!forma) return;
  const r = await fetch('<?= url('/financeiro/') ?>' + id + '/pagar', {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?= csrf_token() ?>'},
    body: JSON.stringify({forma_pagamento: forma})
  });
  if ((await r.json()).success) location.reload();
}

let osIdAtual = null;
function receberOs(id, valor) {
  osIdAtual = id;
  document.getElementById('osValorLabel').textContent = valor;
  new bootstrap.Modal('#modalReceberOs').show();
}

document.getElementById('btnConfReceberOs').addEventListener('click', async function() {
  const forma = document.getElementById('osFormaPagto').value;
  const r = await fetch('<?= url('/financeiro/os/') ?>' + osIdAtual + '/pagar', {
    method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':'<?= csrf_token() ?>'},
    body: JSON.stringify({forma_pagamento: forma})
  });
  if ((await r.json()).success) location.reload();
});
</script>
