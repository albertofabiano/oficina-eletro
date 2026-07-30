<?php
$saldo         = ($resumo['receitas_pagas'] ?? 0) - ($resumo['despesas_pagas'] ?? 0);
$aReceber      = $resumo['receitas_pendentes'] ?? 0;
$aPagar        = $resumo['despesas_pendentes'] ?? 0;
$totalVencidos = count($vencendo ?? []);
$fonteMap      = [];
foreach ($porFonte as $f) $fonteMap[$f['fonte']] = $f['total'];
$receitaOs     = $fonteMap['os'] ?? 0;
$receitaManual = $fonteMap['lancamento'] ?? 0;
$editId        = $editando['id'] ?? null;
?>
<style>
.card-resumo { border:none; border-radius:14px; }
.card-resumo .label { font-size:.78rem; color:rgba(255,255,255,.75); }
.card-resumo .valor { font-size:1.5rem; font-weight:800; color:#fff; }
.card-resumo .sub   { font-size:.72rem; color:rgba(255,255,255,.6); }
.row-vencida td { background:#fff5f5 !important; }
.row-pendente td { background:#fffbeb !important; }
.badge-receita  { background:#d1fae5; color:#065f46; }
.badge-despesa  { background:#fee2e2; color:#991b1b; }
.badge-pago     { background:#dbeafe; color:#1e40af; }
.badge-pendente { background:#fef3c7; color:#92400e; }
.badge-cancelado{ background:#f3f4f6; color:#6b7280; }
.taxa-chevron { transition:transform .2s; display:inline-block; }
.taxa-toggle-cell { cursor:pointer; }
.taxa-toggle-cell:hover { background:rgba(0,0,0,.035); }
.taxa-toggle-cell:not(.collapsed) .taxa-chevron { transform:rotate(90deg); }

/* Só nesta página: sobe a Calculadora/Mentor o mínimo pra deixar 15px livres acima do
   botão redondo de Nova Receita/Despesa (bottom 32px + altura 56px do botão + 15px). */
#mentorFabWrap { bottom: 103px !important; }
#calcFabWrap   { bottom: 163px !important; }
</style>

<!-- ── Início do financeiro (corte de data) ── -->
<div class="d-flex justify-content-end mb-2">
  <button type="button" class="btn btn-sm <?= !empty($financeiroInicio) ? 'btn-outline-primary' : 'btn-outline-secondary' ?>" data-bs-toggle="modal" data-bs-target="#modalInicioFin">
    <i class="bi bi-calendar-range me-1"></i>
    <?php if (!empty($financeiroInicio)): ?>Financeiro conta desde <?= date('d/m/Y', strtotime($financeiroInicio)) ?><?php else: ?>Definir início do financeiro<?php endif; ?>
  </button>
</div>

<div class="modal fade" id="modalInicioFin" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <form method="POST" action="<?= url('/financeiro/inicio') ?>">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-calendar-range me-2"></i>Início do financeiro</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small mb-3">O fluxo de caixa passa a contar <strong>só a partir da data escolhida</strong>. Movimentos e OS anteriores somem do financeiro — mas <strong>continuam intactos</strong> no sistema (nada é apagado). Ideal pra começar do zero mantendo o histórico de OS. Deixe em branco para voltar a contar tudo.</p>
          <label class="form-label fw-semibold small">Contar a partir de</label>
          <input type="date" name="financeiro_inicio" class="form-control" value="<?= e($financeiroInicio ?? '') ?>">
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ── Cards de resumo ── -->
<div class="row g-3 mb-4">
  <div class="col-6 col-xl-3">
    <div class="card card-resumo p-3" style="background:linear-gradient(135deg,#059669,#10b981)">
      <div class="label">Receitas pagas</div>
      <div class="valor"><?= money($resumo['receitas_pagas'] ?? 0) ?></div>
      <div class="sub d-flex gap-2 flex-wrap mt-1">
        <?php if ($receitaOs): ?><span>OS: <?= money($receitaOs) ?></span><?php endif; ?>
        <?php if ($receitaManual): ?><span>Manual: <?= money($receitaManual) ?></span><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card card-resumo p-3" style="background:linear-gradient(135deg,#dc2626,#ef4444)">
      <div class="label">Despesas pagas</div>
      <div class="valor"><?= money($resumo['despesas_pagas'] ?? 0) ?></div>
      <div class="sub">no período</div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card card-resumo p-3" style="background:linear-gradient(135deg,<?= $totalVencidos ? '#b45309,#f59e0b' : '#0369a1,#0ea5e9' ?>)">
      <div class="label">A receber<?= $aReceber ? '' : '' ?> / A pagar</div>
      <div class="valor"><?= money($aReceber) ?> / <?= money($aPagar) ?></div>
      <div class="sub">
        <?= $totalVencidos ? "⚠️ {$totalVencidos} vencido(s)" : 'pendentes no período' ?>
      </div>
    </div>
  </div>
  <div class="col-6 col-xl-3">
    <div class="card card-resumo p-3" style="background:linear-gradient(135deg,<?= $saldo >= 0 ? '#1d4ed8,#3b82f6' : '#7c3aed,#8b5cf6' ?>)">
      <div class="label">Saldo do período</div>
      <div class="valor"><?= money($saldo) ?></div>
      <div class="sub">receitas − despesas pagas</div>
    </div>
  </div>
</div>

<!-- ── Gráfico de fluxo de caixa ── -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-2">
    <span class="fw-semibold"><i class="bi bi-graph-up-arrow me-1 text-success"></i>Fluxo de caixa</span>
    <span class="text-muted small"><?= date_br($filtros['data_inicio'] ?? date('Y-m-01')) ?> a <?= date_br($filtros['data_fim'] ?? date('Y-m-d')) ?></span>
  </div>
  <div class="card-body">
    <?php if (!empty($fluxo)): ?>
    <div style="position:relative;height:260px"><canvas id="chartFluxo"></canvas></div>
    <?php else: ?>
    <div class="text-center text-muted py-4">
      <i class="bi bi-bar-chart-line fs-3 d-block mb-2 opacity-50"></i>
      Nenhum movimento pago no período. Ajuste as datas acima para ver o histórico.
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- ── Campo de Busca AJAX ── -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2 px-3">
    <div class="d-flex align-items-center gap-2">
      <i class="bi bi-search text-muted fs-5"></i>
      <input type="text" id="buscaFinanceiro" class="form-control border-0 shadow-none fs-6"
        placeholder="Buscar lançamento por descrição, valor, categoria..."
        autocomplete="off" style="outline:none;box-shadow:none!important">
      <button class="btn btn-sm btn-outline-secondary d-none" id="btnLimparBusca"
              onclick="limparBusca()">
        <i class="bi bi-x-lg me-1"></i>Limpar
      </button>
    </div>
    <div id="infoBusca" class="text-muted small mt-1 ps-4" style="display:none"></div>
  </div>
</div>

<!-- ── Barra de ações ── -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= url('/financeiro/categorias') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-tags me-1"></i>Categorias
    </a>
    <a href="<?= url('/comissoes') ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-cash-coin me-1"></i>Comissões
    </a>
    <button class="btn btn-success btn-sm fw-semibold"
            data-bs-toggle="modal" data-bs-target="#modalLancamento"
            onclick="abrirModalLancamento('receita')">
      <i class="bi bi-plus-lg me-1"></i>Nova Receita
    </button>
    <button class="btn btn-danger btn-sm fw-semibold"
            data-bs-toggle="modal" data-bs-target="#modalLancamento"
            onclick="abrirModalLancamento('despesa')">
      <i class="bi bi-dash-lg me-1"></i>Nova Despesa
    </button>
  </div>

  <!-- Filtros de período -->
  <form method="GET" class="d-flex gap-2 align-items-center flex-wrap">
    <input type="date" name="data_inicio" class="form-control form-control-sm"
      value="<?= e($filtros['data_inicio'] ?? date('Y-m-01')) ?>">
    <span class="text-muted small">até</span>
    <input type="date" name="data_fim" class="form-control form-control-sm"
      value="<?= e($filtros['data_fim'] ?? date('Y-m-d')) ?>">
    <select name="tipo" class="form-select form-select-sm" style="width:auto">
      <option value="">Todos</option>
      <option value="receita" <?= ($filtros['tipo']??'')=='receita'?'selected':'' ?>>Receitas</option>
      <option value="despesa" <?= ($filtros['tipo']??'')=='despesa'?'selected':'' ?>>Despesas</option>
    </select>
    <select name="status" class="form-select form-select-sm" style="width:auto">
      <option value="">Todos</option>
      <option value="pago"     <?= ($filtros['status']??'')=='pago'?'selected':'' ?>>Pago</option>
      <option value="pendente" <?= ($filtros['status']??'')=='pendente'?'selected':'' ?>>Pendente</option>
    </select>
    <select name="categoria" class="form-select form-select-sm" style="width:auto;min-width:160px">
      <option value="">Todas categorias</option>
      <?php
        $catNomes = [];
        foreach ($categorias as $cat) { if (!in_array($cat['nome'], $catNomes, true)) $catNomes[] = $cat['nome']; }
        sort($catNomes);
      ?>
      <?php foreach ($catNomes as $nome): ?>
      <option value="<?= e($nome) ?>" <?= ($filtros['categoria']??'')===$nome?'selected':'' ?>><?= e($nome) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button>
    <?php if (!empty($filtros['tipo']) || !empty($filtros['status']) || !empty($filtros['categoria'])): ?>
    <a href="<?= url('/financeiro') ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-x"></i></a>
    <?php endif; ?>
  </form>
</div>

<!-- ── OS pendentes ── -->
<?php if (!empty($osPendentes)): ?>
<div class="card border-0 border-warning shadow-sm mb-3" style="border-left:4px solid #f59e0b !important">
  <div class="card-header bg-warning bg-opacity-10 d-flex justify-content-between align-items-center py-2">
    <span class="fw-semibold text-warning-emphasis small">
      <i class="bi bi-exclamation-triangle-fill me-1"></i>
      <?= count($osPendentes) ?> OS fechada(s) aguardando recebimento
    </span>
  </div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light"><tr><th>OS</th><th>Cliente</th><th>Valor</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($osPendentes as $os): ?>
        <tr>
          <td><a href="<?= url('/os/'.$os['id']) ?>" class="fw-semibold">OS <?= e($os['numero']) ?></a></td>
          <td><?= e($os['cliente_nome']) ?></td>
          <td class="fw-bold text-success"><?= money($os['valor_total']) ?></td>
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

<!-- ── Lançamentos vencidos ── -->
<?php if (!empty($vencendo)): ?>
<div class="alert alert-danger d-flex align-items-center gap-2 py-2 mb-3">
  <i class="bi bi-alarm-fill fs-5"></i>
  <div class="small">
    <strong><?= count($vencendo) ?> lançamento(s) vencido(s):</strong>
    <?= implode(', ', array_map(fn($v) => e($v['descricao']), array_slice($vencendo, 0, 3))) ?>
    <?= count($vencendo) > 3 ? ' e mais...' : '' ?>
  </div>
</div>
<?php endif; ?>


<!-- ── Tabela de lançamentos ── -->
<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0 small" id="tabelaLancamentos">
      <thead class="table-light">
        <tr>
          <th>Descrição</th>
          <th>Categoria</th>
          <th>Tipo</th>
          <th>Vencimento</th>
          <th>Valor</th>
          <th>Status</th>
          <th class="text-end">Ações</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // Taxa de cartão vira sub-item recolhível da receita de origem (OS ou venda do PDV), em
        // vez de linha solta — vale pra qualquer taxa, de qualquer origem, de qualquer empresa
        // (isolado por empresa_id na sessão). Busca as taxas do PERÍODO INTEIRO (não só da página
        // atual), porque numa empresa com bastante movimento a receita e a taxa da mesma OS podem
        // cair em páginas diferentes da lista — sem isso o colapse não achava o par.
        $chaveOrigem = function (array $r): ?string {
            if (!empty($r['os_id'])) return 'os:' . $r['os_id'];
            if (preg_match('/Venda PDV #(\d+)/', $r['descricao'], $m)) return 'pdv:' . $m[1];
            return null;
        };
        $taxasPorOrigem = [];
        foreach ($taxasCartaoPeriodo as $t) {
            $chave = $chaveOrigem($t);
            if ($chave !== null) $taxasPorOrigem[$chave][] = $t;
        }
        // Recebimento "mês a mês" gera várias receitas (1 por parcela) com a MESMA origem —
        // a taxa só pode ficar aninhada na primeira, senão duplicaria visualmente nas demais.
        $origensJaAninhadas = [];
        ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="7" class="text-center text-muted py-4">Nenhum lançamento no período.</td></tr>
        <?php endif; ?>
        <?php foreach ($paginator['data'] as $l):
          // Despesa de taxa de cartão nunca aparece solta — sempre aninhada na receita de origem,
          // esteja essa receita nesta página ou em outra.
          if ($l['tipo'] === 'despesa' && $l['categoria_nome'] === 'Taxas de cartão' && $chaveOrigem($l) !== null) continue;
          $vencida = $l['status'] === 'pendente' && $l['data_vencimento'] < date('Y-m-d');
          $chaveL  = $l['tipo'] === 'receita' ? $chaveOrigem($l) : null;
          $taxasOs = ($chaveL !== null && empty($origensJaAninhadas[$chaveL])) ? ($taxasPorOrigem[$chaveL] ?? []) : [];
          if ($taxasOs) $origensJaAninhadas[$chaveL] = true;
          $collapseId = 'taxaOrig_' . preg_replace('/\W/', '', (string) $chaveL) . '_' . $l['fonte'] . $l['ref_id'];
        ?>
        <tr class="<?= $vencida ? 'row-vencida' : ($l['status']==='pendente'?'row-pendente':'') ?>">
          <td<?= $taxasOs ? ' class="taxa-toggle-cell" data-bs-toggle="collapse" data-bs-target="#'.$collapseId.'" role="button" tabindex="0" title="Clique para recolher/expandir a taxa de cartão"' : '' ?>>
            <div class="fw-semibold">
              <?php if ($taxasOs): ?>
              <i class="bi bi-chevron-right small taxa-chevron me-1"></i>
              <?php endif; ?>
              <?= e($l['descricao']) ?>
            </div>
            <?php if ($l['cliente_nome']): ?>
            <div class="text-muted" style="font-size:.75rem"><?= e($l['cliente_nome']) ?></div>
            <?php endif; ?>
            <?php if ($l['fonte'] === 'os'): ?>
            <span class="badge" style="font-size:.68rem;background:#e2e3e5;color:#41464b">OS #<?= e($l['numero_os']) ?></span>
            <?php endif; ?>
          </td>
          <td>
            <?php if ($l['categoria_nome']): ?>
            <span class="badge rounded-pill" style="background:<?= e($l['categoria_cor']) ?>22;color:<?= e($l['categoria_cor']) ?>">
              <?= e($l['categoria_nome']) ?>
            </span>
            <?php else: ?>
            <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td>
            <span class="badge rounded-pill badge-<?= $l['tipo'] ?>">
              <?= $l['tipo'] === 'receita' ? '↑ Receita' : '↓ Despesa' ?>
            </span>
          </td>
          <td class="<?= $vencida ? 'text-danger fw-semibold' : '' ?>">
            <?= date_br($l['data_vencimento']) ?>
            <?php if ($vencida): ?><br><span class="badge bg-danger" style="font-size:.65rem">Vencida</span><?php endif; ?>
          </td>
          <td class="fw-bold <?= $l['tipo']==='receita'?'text-success':'text-danger' ?>">
            <?= money($l['valor']) ?>
          </td>
          <td>
            <span class="badge rounded-pill badge-<?= $l['status'] ?>">
              <?= ucfirst($l['status']) ?>
            </span>
            <?php if ($l['data_pagamento'] && $l['status']==='pago'): ?>
            <div class="text-muted" style="font-size:.7rem"><?= date_br($l['data_pagamento']) ?></div>
            <?php endif; ?>
          </td>
          <td class="text-end">
            <div class="d-flex gap-1 justify-content-end">
              <?php if ($l['status'] === 'pendente' && $l['fonte'] === 'lancamento'): ?>
              <button class="btn btn-sm btn-success" title="Dar baixa"
                onclick="darBaixa(<?= $l['ref_id'] ?>, '<?= money($l['valor']) ?>', '<?= e($l['descricao']) ?>')">
                <i class="bi bi-check2-circle"></i>
              </button>
              <?php endif; ?>
              <?php if ($l['fonte'] === 'lancamento'): ?>
              <a href="<?= url('/financeiro/'.$l['ref_id'].'/editar') ?>"
                 class="btn btn-sm btn-outline-primary" title="Editar">
                <i class="bi bi-pencil"></i>
              </a>
              <button class="btn btn-sm btn-outline-danger" title="Excluir"
                data-method="DELETE"
                data-href="<?= url('/financeiro/'.$l['ref_id']) ?>"
                data-confirm="Excluir este lançamento?">
                <i class="bi bi-trash"></i>
              </button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php foreach ($taxasOs as $t): ?>
        <tr class="collapse show" id="<?= $collapseId ?>">
          <td class="ps-4 border-0">
            <i class="bi bi-arrow-return-right text-muted me-1"></i>
            <span class="text-muted"><?= e($t['descricao']) ?></span>
          </td>
          <td class="border-0">
            <span class="badge rounded-pill" style="background:<?= e($t['categoria_cor']) ?>22;color:<?= e($t['categoria_cor']) ?>">
              <?= e($t['categoria_nome']) ?>
            </span>
          </td>
          <td class="border-0"><span class="badge rounded-pill badge-despesa">↓ Despesa</span></td>
          <td class="border-0"><?= date_br($t['data_vencimento']) ?></td>
          <td class="border-0 fw-bold text-danger"><?= money($t['valor']) ?></td>
          <td class="border-0"><span class="badge rounded-pill badge-<?= $t['status'] ?>"><?= ucfirst($t['status']) ?></span></td>
          <td class="border-0 text-end">
            <?php if ($t['fonte'] === 'lancamento'): ?>
            <button class="btn btn-sm btn-outline-danger" title="Excluir"
              data-method="DELETE"
              data-href="<?= url('/financeiro/'.$t['ref_id']) ?>"
              data-confirm="Excluir este lançamento?">
              <i class="bi bi-trash"></i>
            </button>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <div class="card-footer bg-light">
    <div class="d-flex flex-wrap justify-content-end align-items-center gap-3 small">
      <span class="text-muted"><?= $totaisFiltrados['qtd'] ?> lançamento(s) no filtro</span>
      <span class="fw-semibold text-success">Receitas: <?= money($totaisFiltrados['total_receitas']) ?></span>
      <span class="fw-semibold text-danger">Despesas: <?= money($totaisFiltrados['total_despesas']) ?></span>
      <span class="fw-bold <?= $totaisFiltrados['saldo'] >= 0 ? 'text-success' : 'text-danger' ?>">
        Saldo: <?= money($totaisFiltrados['saldo']) ?>
      </span>
    </div>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $paginator['total'] ?> lançamento(s)</small>
    <?= pagination($paginator, url('/financeiro')) ?>
  </div>
  <?php endif; ?>
</div>

<!-- ══ Modal Lançamento (Criar / Editar) ══ -->
<div class="modal fade" id="modalLancamento" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold" id="modalLancTitulo">Novo Lançamento</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form id="formLancamento" method="POST" action="<?= url('/financeiro') ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="_method" id="lancMethod" value="POST">
        <input type="hidden" name="tipo" id="lancTipo" value="receita">

        <div class="modal-body">
          <!-- Tipo -->
          <div class="d-flex gap-2 mb-3">
            <button type="button" id="btnReceita" onclick="setTipoLanc('receita')"
              class="btn btn-success flex-fill fw-semibold">
              <i class="bi bi-arrow-up-circle me-1"></i>Receita
            </button>
            <button type="button" id="btnDespesa" onclick="setTipoLanc('despesa')"
              class="btn btn-outline-danger flex-fill fw-semibold">
              <i class="bi bi-arrow-down-circle me-1"></i>Despesa
            </button>
          </div>

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label fw-semibold">Descrição *</label>
              <input type="text" name="descricao" id="lancDescricao" class="form-control" required maxlength="255"
                placeholder="Ex: Conta de luz, Serviço de reparo...">
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Valor *</label>
              <div class="input-group">
                <span class="input-group-text">R$</span>
                <input type="text" name="valor" id="lancValor" class="form-control" required
                  placeholder="0,00" inputmode="decimal">
              </div>
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold d-flex justify-content-between align-items-center">
                Categoria
                <button type="button" class="btn btn-link btn-sm p-0 text-primary fw-semibold"
                        style="font-size:.78rem" onclick="toggleGerenciarCats()">
                  <i class="bi bi-gear me-1"></i>Gerenciar
                </button>
              </label>
              <div class="input-group">
                <select name="categoria_id" id="lancCategoria" class="form-select">
                  <option value="">Sem categoria</option>
                </select>
                <button type="button" class="btn btn-outline-primary" title="Nova categoria"
                        onclick="abrirFormCat()">
                  <i class="bi bi-plus-lg"></i>
                </button>
              </div>

              <!-- Mini CRUD de categorias -->
              <div id="gerenciarCats" style="display:none" class="mt-2 border rounded-3 overflow-hidden">
                <div class="d-flex align-items-center justify-content-between px-3 py-2"
                     style="background:#f8fafc;border-bottom:1px solid #e2e8f0">
                  <span class="fw-semibold small">Gerenciar categorias</span>
                  <button type="button" class="btn-close btn-sm" onclick="toggleGerenciarCats()"></button>
                </div>

                <!-- Form rápido para criar/editar -->
                <div id="formCatInline" class="p-3 border-bottom" style="background:#fffbeb;display:none">
                  <input type="hidden" id="inlineCatId">
                  <div class="mb-2">
                    <input type="text" id="inlineCatNome" class="form-control form-control-sm"
                           placeholder="Nome da categoria..." maxlength="80">
                  </div>
                  <div class="d-flex gap-2 mb-2">
                    <button type="button" id="btnInlineTipoReceita"
                            class="btn btn-sm flex-fill btn-success"
                            onclick="setInlineTipo('receita')">↑ Receita</button>
                    <button type="button" id="btnInlineTipoDespesa"
                            class="btn btn-sm flex-fill btn-outline-danger"
                            onclick="setInlineTipo('despesa')">↓ Despesa</button>
                  </div>
                  <input type="hidden" id="inlineCatTipo" value="receita">
                  <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary flex-fill"
                            onclick="fecharFormCat()">Cancelar</button>
                    <button type="button" class="btn btn-sm btn-primary flex-fill fw-semibold"
                            onclick="salvarCatInline()">
                      <i class="bi bi-check-lg me-1"></i>Salvar
                    </button>
                  </div>
                </div>

                <!-- Lista de categorias -->
                <ul class="list-group list-group-flush" id="listaCatsInline"
                    style="max-height:200px;overflow-y:auto">
                  <li class="list-group-item text-muted small text-center py-3" id="liCatsVazio">
                    Nenhuma categoria ainda
                  </li>
                </ul>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Forma de pagamento</label>
              <select name="forma_pagamento" id="lancForma" class="form-select">
                <option value="">Selecione...</option>
                <option value="dinheiro">Dinheiro</option>
                <option value="pix">Pix</option>
                <option value="cartao_credito">Cartão de Crédito</option>
                <option value="cartao_debito">Cartão de Débito</option>
                <option value="boleto">Boleto</option>
                <option value="transferencia">Transferência</option>
                <option value="cheque">Cheque</option>
                <option value="outro">Outro</option>
              </select>
            </div>

            <div class="col-md-4">
              <label class="form-label fw-semibold">Vencimento *</label>
              <input type="date" name="data_vencimento" id="lancVencimento" class="form-control" required>
            </div>
            <div class="col-md-4">
              <label class="form-label fw-semibold">Status</label>
              <select name="status" id="lancStatus" class="form-select" onchange="toggleDataPagamento(this.value)">
                <option value="pendente">Pendente</option>
                <option value="pago">Pago</option>
              </select>
            </div>
            <div class="col-md-4" id="wrapDataPag" style="display:none">
              <label class="form-label fw-semibold">Data pagamento</label>
              <input type="date" name="data_pagamento" id="lancDataPag" class="form-control">
            </div>

            <div class="col-md-6">
              <label class="form-label fw-semibold">Conta</label>
              <select name="conta_id" id="lancConta" class="form-select">
                <option value="">Selecione...</option>
                <?php foreach ($contas as $c): ?>
                <option value="<?= $c['id'] ?>"><?= e($c['nome']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label fw-semibold">Observações</label>
              <input type="text" name="observacoes" id="lancObs" class="form-control" maxlength="255">
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary fw-semibold" id="btnSalvarLanc">
            <i class="bi bi-check-lg me-1"></i>Salvar Lançamento
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modal Dar Baixa -->
<div class="modal fade" id="modalBaixa" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold"><i class="bi bi-check2-circle me-2 text-success"></i>Dar Baixa</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3" id="baixaDesc"></p>
        <label class="form-label fw-semibold">Forma de recebimento</label>
        <select id="baixaForma" class="form-select">
          <option value="dinheiro">Dinheiro</option>
          <option value="pix">Pix</option>
          <option value="cartao_credito">Cartão de Crédito</option>
          <option value="cartao_debito">Cartão de Débito</option>
          <option value="boleto">Boleto</option>
          <option value="transferencia">Transferência</option>
          <option value="outro">Outro</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-semibold" onclick="confirmarBaixa()">
          <i class="bi bi-check-lg me-1"></i>Confirmar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Receber OS -->
<div class="modal fade" id="modalOs" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2 text-success"></i>Receber OS</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="text-muted small mb-3" id="osDesc"></p>
        <label class="form-label fw-semibold">Forma de pagamento</label>
        <select id="osForma" class="form-select">
          <option value="dinheiro">Dinheiro</option>
          <option value="pix">Pix</option>
          <option value="cartao_credito">Cartão de Crédito</option>
          <option value="cartao_debito">Cartão de Débito</option>
          <option value="outro">Outro</option>
        </select>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-success fw-semibold" onclick="confirmarOs()">
          <i class="bi bi-check-lg me-1"></i>Confirmar Recebimento
        </button>
      </div>
    </div>
  </div>
</div>

<!-- FAB com mini-menu -->
<div style="position:fixed;bottom:2rem;right:2rem;z-index:999;display:flex;flex-direction:column;align-items:flex-end;gap:.6rem" id="fabWrap">
  <div id="fabMenu" style="display:none;flex-direction:column;gap:.5rem;align-items:flex-end">
    <button class="btn btn-success fw-semibold shadow"
            data-bs-toggle="modal" data-bs-target="#modalLancamento"
            onclick="abrirModalLancamento('receita');fecharFab()"
            style="border-radius:50px;padding:.5rem 1.1rem;white-space:nowrap">
      <i class="bi bi-arrow-up-circle me-1"></i>Nova Receita
    </button>
    <button class="btn btn-danger fw-semibold shadow"
            data-bs-toggle="modal" data-bs-target="#modalLancamento"
            onclick="abrirModalLancamento('despesa');fecharFab()"
            style="border-radius:50px;padding:.5rem 1.1rem;white-space:nowrap">
      <i class="bi bi-arrow-down-circle me-1"></i>Nova Despesa
    </button>
  </div>
  <button onclick="toggleFab()" id="fabBtn"
          class="btn btn-primary rounded-circle shadow-lg"
          title="Novo lançamento"
          style="width:56px;height:56px;font-size:1.4rem;display:flex;align-items:center;justify-content:center;box-shadow:0 6px 20px rgba(37,99,235,.45)!important;transition:transform .2s">
    <i class="bi bi-plus-lg" id="fabIcon"></i>
  </button>
</div>

<?php
// Pré-popular modal de edição se vier de GET /financeiro/{id}/editar
$editJson = $editando ? json_encode($editando, JSON_UNESCAPED_UNICODE) : 'null';
$catJson  = json_encode($categorias, JSON_UNESCAPED_UNICODE);
?>
<script>
const CSRF    = '<?= csrf_token() ?>';
const allCats = <?= $catJson ?>;
let baixaId = null, osId = null;

// ── Gráfico de fluxo de caixa (receita x despesa x saldo acumulado) ──
document.addEventListener('DOMContentLoaded', function() {
  const dados = <?= json_encode($fluxo ?: []) ?>;
  const el = document.getElementById('chartFluxo');
  if (!el || !dados.length || typeof Chart === 'undefined') return;
  const fmtDia = s => { const p = String(s).split('-'); return p[2] + '/' + p[1]; };
  const brl = v => 'R$ ' + Number(v).toLocaleString('pt-BR', {minimumFractionDigits:2, maximumFractionDigits:2});
  let acc = 0;
  const saldoAcc = dados.map(d => acc += (Number(d.receita) - Number(d.despesa)));
  new Chart(el, {
    data: {
      labels: dados.map(d => fmtDia(d.dia)),
      datasets: [
        { type:'bar', label:'Receita', data: dados.map(d => Number(d.receita)),
          backgroundColor:'#10b98199', borderColor:'#059669', borderWidth:1, order:2 },
        { type:'bar', label:'Despesa', data: dados.map(d => Number(d.despesa)),
          backgroundColor:'#ef444499', borderColor:'#dc2626', borderWidth:1, order:2 },
        { type:'line', label:'Saldo acumulado', data: saldoAcc,
          borderColor:'#2563eb', backgroundColor:'#2563eb22', borderWidth:2,
          tension:.3, fill:true, pointRadius:2, order:1 }
      ]
    },
    options: {
      responsive:true, maintainAspectRatio:false,
      interaction:{ mode:'index', intersect:false },
      plugins:{
        legend:{ position:'bottom' },
        tooltip:{ callbacks:{ label: c => c.dataset.label + ': ' + brl(c.parsed.y) } }
      },
      scales:{ y:{ beginAtZero:true, ticks:{ callback: v => 'R$ ' + Number(v).toLocaleString('pt-BR') } } }
    }
  });
});

// Instâncias lazy — só cria após Bootstrap estar carregado
const modal = id => bootstrap.Modal.getOrCreateInstance(document.getElementById(id));

document.addEventListener('DOMContentLoaded', () => {
  // Fechar FAB ao abrir modal
  document.getElementById('modalLancamento').addEventListener('show.bs.modal', () => fecharFab());
  // Fechar FAB ao clicar fora
  document.addEventListener('click', e => {
    if (!document.getElementById('fabWrap').contains(e.target)) fecharFab();
  });
});

// FAB
function toggleFab() {
  const menu = document.getElementById('fabMenu');
  const icon = document.getElementById('fabIcon');
  const btn  = document.getElementById('fabBtn');
  const open = menu.style.display === 'flex';
  menu.style.display = open ? 'none' : 'flex';
  icon.className = open ? 'bi bi-plus-lg' : 'bi bi-x-lg';
  btn.style.transform = open ? 'rotate(0)' : 'rotate(45deg)';
}
function fecharFab() {
  document.getElementById('fabMenu').style.display = 'none';
  document.getElementById('fabIcon').className = 'bi bi-plus-lg';
  document.getElementById('fabBtn').style.transform = 'rotate(0)';
}

function setTipoLanc(tipo) {
  document.getElementById('lancTipo').value = tipo;
  document.getElementById('btnReceita').className =
    tipo === 'receita' ? 'btn btn-success flex-fill fw-semibold' : 'btn btn-outline-success flex-fill fw-semibold';
  document.getElementById('btnDespesa').className =
    tipo === 'despesa' ? 'btn btn-danger flex-fill fw-semibold' : 'btn btn-outline-danger flex-fill fw-semibold';
  // Filtrar categorias
  const sel = document.getElementById('lancCategoria');
  sel.innerHTML = '<option value="">Sem categoria</option>';
  allCats.filter(c => c.tipo === tipo && c.status === 'ativo').forEach(c => {
    sel.innerHTML += `<option value="${c.id}">${c.nome}</option>`;
  });
}

function toggleDataPagamento(val) {
  const wrap = document.getElementById('wrapDataPag');
  wrap.style.display = val === 'pago' ? 'block' : 'none';
  if (val === 'pago' && !document.getElementById('lancDataPag').value) {
    document.getElementById('lancDataPag').value = new Date().toISOString().slice(0, 10);
  }
}

function abrirModalLancamento(tipo = 'receita', lancamento = null) {
  const form = document.getElementById('formLancamento');
  form.action = lancamento
    ? `<?= url('/financeiro/') ?>${lancamento.id}/editar`
    : `<?= url('/financeiro') ?>`;
  document.getElementById('lancMethod').value = lancamento ? 'POST' : 'POST';
  document.getElementById('modalLancTitulo').textContent = lancamento ? 'Editar Lançamento' : 'Novo Lançamento';

  // Preencher campos
  const t = lancamento?.tipo ?? tipo;
  setTipoLanc(t);
  document.getElementById('lancDescricao').value  = lancamento?.descricao ?? '';
  document.getElementById('lancVencimento').value  = lancamento?.data_vencimento ?? new Date().toISOString().slice(0,10);
  document.getElementById('lancStatus').value      = lancamento?.status ?? 'pendente';
  document.getElementById('lancForma').value       = lancamento?.forma_pagamento ?? '';
  document.getElementById('lancConta').value       = lancamento?.conta_id ?? '';
  document.getElementById('lancObs').value         = lancamento?.observacoes ?? '';
  document.getElementById('lancDataPag').value     = lancamento?.data_pagamento ?? '';

  // Valor
  const rawValor = lancamento?.valor ? parseFloat(lancamento.valor).toFixed(2).replace('.', ',') : '';
  document.getElementById('lancValor').value = rawValor;

  toggleDataPagamento(lancamento?.status ?? 'pendente');

  // Categoria
  if (lancamento?.categoria_id) {
    document.getElementById('lancCategoria').value = lancamento.categoria_id;
  }

  modal('modalLancamento').show();
}

function darBaixa(id, valor, desc) {
  baixaId = id;
  document.getElementById('baixaDesc').textContent = `${desc} — ${valor}`;
  modal('modalBaixa').show();
}

async function confirmarBaixa() {
  const forma = document.getElementById('baixaForma').value;
  const resp  = await fetch(`<?= url('/financeiro/') ?>${baixaId}/liquidar`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ forma_pagamento: forma, csrf_token: CSRF }),
  });
  const json = await resp.json();
  if (json.success) { modal('modalBaixa').hide(); location.reload(); }
  else alert('Erro: ' + (json.error ?? 'desconhecido'));
}

function receberOs(id, valor) {
  osId = id;
  document.getElementById('osDesc').textContent = `Valor: ${valor}`;
  modal('modalOs').show();
}

async function confirmarOs() {
  const forma = document.getElementById('osForma').value;
  const resp  = await fetch(`<?= url('/financeiro/os/') ?>${osId}/pagar`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ forma_pagamento: forma, csrf_token: CSRF }),
  });
  const json = await resp.json();
  if (json.success) { modal('modalOs').hide(); location.reload(); }
  else alert('Erro: ' + (json.error ?? 'desconhecido'));
}

// ══ Mini CRUD de categorias inline ══════════════════════

function toggleGerenciarCats() {
  const el = document.getElementById('gerenciarCats');
  const aberto = el.style.display !== 'none';
  el.style.display = aberto ? 'none' : 'block';
  if (!aberto) renderListaCats();
}

function abrirFormCat(cat = null) {
  document.getElementById('gerenciarCats').style.display = 'block';
  document.getElementById('formCatInline').style.display = 'block';
  document.getElementById('inlineCatId').value   = cat?.id ?? '';
  document.getElementById('inlineCatNome').value = cat?.nome ?? '';
  setInlineTipo(cat?.tipo ?? document.getElementById('lancTipo').value ?? 'receita');
  setTimeout(() => document.getElementById('inlineCatNome').focus(), 100);
}

function fecharFormCat() {
  document.getElementById('formCatInline').style.display = 'none';
  document.getElementById('inlineCatNome').value = '';
  document.getElementById('inlineCatId').value   = '';
}

function setInlineTipo(tipo) {
  document.getElementById('inlineCatTipo').value = tipo;
  document.getElementById('btnInlineTipoReceita').className =
    'btn btn-sm flex-fill ' + (tipo === 'receita' ? 'btn-success' : 'btn-outline-success');
  document.getElementById('btnInlineTipoDespesa').className =
    'btn btn-sm flex-fill ' + (tipo === 'despesa' ? 'btn-danger' : 'btn-outline-danger');
}

async function salvarCatInline() {
  const id   = document.getElementById('inlineCatId').value;
  const nome = document.getElementById('inlineCatNome').value.trim();
  const tipo = document.getElementById('inlineCatTipo').value;

  if (!nome) {
    document.getElementById('inlineCatNome').focus();
    document.getElementById('inlineCatNome').classList.add('is-invalid');
    return;
  }
  document.getElementById('inlineCatNome').classList.remove('is-invalid');

  const url = id
    ? `<?= url('/financeiro/categorias/') ?>${id}`
    : `<?= url('/financeiro/categorias') ?>`;

  const resp = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ nome, tipo, cor: '#2563eb', status: 'ativo', csrf_token: CSRF }),
  });
  const json = await resp.json();

  if (json.error) { alert(json.error); return; }

  // Atualizar allCats em memória
  if (json.categorias) {
    allCats.length = 0;
    json.categorias.forEach(c => allCats.push(c));
  }

  fecharFormCat();
  renderListaCats();
  // Atualizar select de categoria no form principal
  setTipoLanc(document.getElementById('lancTipo').value);
  // Selecionar a categoria recém-criada
  if (!id && json.categorias) {
    const nova = json.categorias.filter(c => c.nome === nome && c.tipo === tipo).pop();
    if (nova) document.getElementById('lancCategoria').value = nova.id;
  }
}

async function excluirCatInline(id, nome) {
  if (!confirm(`Excluir "${nome}"?\n\nSe tiver lançamentos vinculados, será desativada.`)) return;

  const resp = await fetch(`<?= url('/financeiro/categorias/') ?>${id}`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
    body: JSON.stringify({ _method: 'DELETE', csrf_token: CSRF }),
  });
  const json = await resp.json();

  if (json.aviso) setTimeout(() => alert('ℹ️ ' + json.aviso), 200);

  if (json.categorias) {
    allCats.length = 0;
    json.categorias.forEach(c => allCats.push(c));
  }

  renderListaCats();
  setTipoLanc(document.getElementById('lancTipo').value);
}

function renderListaCats() {
  const lista = document.getElementById('listaCatsInline');
  const cats  = allCats.filter(c => c.status === 'ativo');

  lista.innerHTML = '';

  if (!cats.length) {
    lista.innerHTML = '<li class="list-group-item text-muted small text-center py-3">Nenhuma categoria ativa</li>';
    return;
  }

  cats.forEach(c => {
    const li = document.createElement('li');
    li.className = 'list-group-item d-flex align-items-center justify-content-between py-2 px-3';
    li.innerHTML = `
      <div class="d-flex align-items-center gap-2">
        <span class="badge rounded-pill badge-${c.tipo}" style="font-size:.7rem">
          ${c.tipo === 'receita' ? '↑' : '↓'}
        </span>
        <span class="small fw-semibold">${c.nome}</span>
      </div>
      <div class="d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar">
          <i class="bi bi-pencil" style="font-size:.75rem"></i>
        </button>
        <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2" title="Excluir">
          <i class="bi bi-trash" style="font-size:.75rem"></i>
        </button>
      </div>`;
    li.querySelector('.btn-outline-primary').addEventListener('click', () => abrirFormCat(c));
    li.querySelector('.btn-outline-danger').addEventListener('click', () => excluirCatInline(c.id, c.nome));
    lista.appendChild(li);
  });
}

// ── Busca AJAX — consulta o servidor em tempo real ─────────
(function() {
  const input     = document.getElementById('buscaFinanceiro');
  const btnLimpar = document.getElementById('btnLimparBusca');
  const info      = document.getElementById('infoBusca');
  const tbody     = document.querySelector('#tabelaLancamentos tbody');
  const linhasOriginais = tbody ? tbody.innerHTML : '';
  let timer, ultimaQ = '';

  if (!input) return;

  input.addEventListener('input', function() {
    clearTimeout(timer);
    const q = this.value.trim();
    btnLimpar.classList.toggle('d-none', !q);

    if (!q) { restaurar(); return; }
    if (q === ultimaQ) return;

    info.innerHTML = '<span class="spinner-border spinner-border-sm me-1 align-middle"></span> Buscando...';
    info.style.display = 'block';
    timer = setTimeout(() => buscar(q), 350);
  });

  async function buscar(q) {
    ultimaQ = q;
    try {
      const resp = await fetch(`<?= url('/api/financeiro/buscar') ?>?q=${encodeURIComponent(q)}`, {
        headers: { 'X-CSRF-Token': '<?= csrf_token() ?>' }
      });
      if (!resp.ok) throw new Error(resp.status);
      const dados = await resp.json();

      if (q !== input.value.trim()) return; // descarta resposta antiga

      if (!dados.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">
          <i class="bi bi-search fs-3 d-block mb-2 opacity-25"></i>
          Nenhum resultado para <strong>"${esc(q)}"</strong>
        </td></tr>`;
        info.innerHTML = `<i class="bi bi-x-circle text-danger me-1"></i>0 resultados para <strong>"${esc(q)}"</strong>`;
      } else {
        tbody.innerHTML = dados.map(l => renderLinha(l)).join('');
        info.innerHTML = `<i class="bi bi-check-circle text-success me-1"></i><strong>${dados.length}</strong> resultado(s) para <strong>"${esc(q)}"</strong>`;
      }
      info.style.display = 'block';
    } catch(e) {
      info.innerHTML = '<i class="bi bi-exclamation-triangle text-warning me-1"></i>Erro ao buscar.';
    }
  }

  function renderLinha(l) {
    const vencida = l.status === 'pendente' && l.data_vencimento && l.data_vencimento < '<?= date('Y-m-d') ?>';
    const cls = vencida ? 'class="row-vencida"' : (l.status==='pendente' ? 'class="row-pendente"' : '');
    const catBadge = l.categoria_nome
      ? `<span class="badge rounded-pill" style="background:${l.categoria_cor}22;color:${l.categoria_cor}">${esc(l.categoria_nome)}</span>`
      : '<span class="text-muted">—</span>';
    const tipoBadge = l.tipo === 'receita'
      ? `<span class="badge rounded-pill badge-receita">↑ Receita</span>`
      : `<span class="badge rounded-pill badge-despesa">↓ Despesa</span>`;
    const statusBadge = `<span class="badge rounded-pill badge-${l.status}">${l.status.charAt(0).toUpperCase()+l.status.slice(1)}</span>`;
    const valorClass = l.tipo==='receita' ? 'text-success' : 'text-danger';
    const valor = parseFloat(l.valor).toLocaleString('pt-BR',{style:'currency',currency:'BRL'});
    const dtVenc = l.data_vencimento ? l.data_vencimento.substring(0,10).split('-').reverse().join('/') : '—';
    const acoes = l.fonte === 'lancamento' ? `
      <a href="<?= url('/financeiro/') ?>${l.ref_id}/editar" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
      <a href="#" class="btn btn-sm btn-outline-danger" data-method="DELETE" data-href="<?= url('/financeiro/') ?>${l.ref_id}" data-confirm="Excluir este lançamento?"><i class="bi bi-trash"></i></a>
    ` : '';
    const darBaixa = l.status==='pendente' && l.fonte==='lancamento'
      ? `<button class="btn btn-sm btn-success" onclick="darBaixa(${l.ref_id},'${valor}','${esc(l.descricao)}')"><i class="bi bi-check2-circle"></i></button>` : '';

    return `<tr ${cls}>
      <td>
        <div class="fw-semibold">${esc(l.descricao)}</div>
        ${l.cliente_nome ? `<div class="text-muted" style="font-size:.75rem">${esc(l.cliente_nome)}</div>` : ''}
        ${l.fonte==='os' ? `<span class="badge" style="font-size:.68rem;background:#e2e3e5;color:#41464b">OS #${esc(l.numero_os||'')}</span>` : ''}
      </td>
      <td>${catBadge}</td>
      <td>${tipoBadge}</td>
      <td class="${vencida?'text-danger fw-semibold':''}">${dtVenc}${vencida?'<br><span class="badge bg-danger" style="font-size:.65rem">Vencida</span>':''}</td>
      <td class="fw-bold ${valorClass}">${valor}</td>
      <td>${statusBadge}</td>
      <td class="text-end"><div class="d-flex gap-1 justify-content-end">${darBaixa}${acoes}</div></td>
    </tr>`;
  }

  function esc(s) {
    return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
  }

  function restaurar() {
    tbody.innerHTML = linhasOriginais;
    info.style.display = 'none';
    ultimaQ = '';
  }

  window.limparBusca = function() {
    input.value = '';
    btnLimpar.classList.add('d-none');
    restaurar();
  };
})();

// Abrir modal de edição automaticamente se vier de GET /financeiro/{id}/editar
<?php if ($editando): ?>
document.addEventListener('DOMContentLoaded', () => {
  abrirModalLancamento('<?= $editando['tipo'] ?>', <?= $editJson ?>);
});
<?php endif; ?>
</script>
