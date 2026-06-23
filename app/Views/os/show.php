<div class="row g-3">
  <!-- Principal -->
  <div class="col-md-8">
    <!-- Cabeçalho OS -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
          <span class="fw-bold fs-5">OS: <?= e($os['numero']) ?></span>
          <span class="ms-2"><?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '') ?></span>
          <span class="badge badge-prioridade-<?= $os['prioridade'] ?> ms-1"><?= ucfirst($os['prioridade']) ?></span>
          <?php if ($os['tipo_servico'] === 'garantia' && !empty($os['os_origem_id'])): ?>
          <span class="badge bg-danger ms-1"><i class="bi bi-shield-check me-1"></i>Garantia</span>
          <?php endif; ?>
          <?php if (!empty($os['em_garantia']) && $os['tipo_servico'] !== 'garantia'): ?>
          <span class="badge bg-success bg-opacity-25 text-danger border border-success ms-1" style="font-size:.72rem">
            <i class="bi bi-shield-check me-1"></i><?= $os['dias_garantia_restantes'] ?>d garantia
          </span>
          <?php endif; ?>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalStatus"><i class="bi bi-arrow-repeat"></i> Status</button>
          <a href="<?= url('/os/' . $os['id'] . '/imprimir') ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bi bi-printer"></i> Abertura</a>
          <?php if (!in_array($os['status_tipo'], ['concluida','entregue','cancelada'])): ?>
          <button class="btn btn-sm btn-success fw-semibold" data-bs-toggle="modal" data-bs-target="#modalFechar">
            <i class="bi bi-check-circle me-1"></i>Fechar OS
          </button>
          <?php else: ?>
          <a href="<?= url('/os/' . $os['id'] . '/imprimir/fechamento') ?>" class="btn btn-sm btn-success" target="_blank">
            <i class="bi bi-printer me-1"></i>Comprovante
          </a>
          <?php endif; ?>
          <a href="<?= url('/os/' . $os['id'] . '/editar') ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
        </div>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Cliente</div>
            <a href="<?= url('/clientes/' . $os['cliente_id']) ?>" class="fw-semibold text-decoration-none"><?= e($os['cliente_nome']) ?></a>
            <div class="small"><?= e($os['cliente_tel'] ?? '') ?></div>
            <?php if ($os['cliente_whats']): ?>
            <a href="https://wa.me/55<?= only_numbers($os['cliente_whats']) ?>" target="_blank" class="btn btn-success btn-sm mt-1"><i class="bi bi-whatsapp"></i> WhatsApp</a>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Equipamento</div>
            <div class="fw-semibold"><?= e($os['equip_marca'] . ' ' . $os['equip_modelo']) ?></div>
            <div><?= e($os['equip_tipo']) ?> <?= $os['equip_cor'] ? '• ' . e($os['equip_cor']) : '' ?></div>
            <?php if ($os['numero_serie']): ?><div class="small text-muted">S/N: <?= e($os['numero_serie']) ?></div><?php endif; ?>
            <?php if ($os['senha_desbloqueio']): ?><div class="small"><i class="bi bi-shield-lock"></i> <?= e($os['senha_desbloqueio']) ?></div><?php endif; ?>
          </div>
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Defeito relatado</div>
            <p class="mb-0"><?= nl2br(e($os['defeito_relatado'])) ?></p>
          </div>
          <?php if ($os['defeito_constatado']): ?>
          <div class="col-md-6">
            <div class="small text-muted fw-semibold mb-1">Defeito constatado</div>
            <p class="mb-0"><?= nl2br(e($os['defeito_constatado'])) ?></p>
          </div>
          <?php endif; ?>
          <?php if ($os['laudo_tecnico']): ?>
          <div class="col-12">
            <div class="small text-muted fw-semibold mb-1">Laudo técnico</div>
            <p class="mb-0"><?= nl2br(e($os['laudo_tecnico'])) ?></p>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div class="card-footer bg-white">
        <div class="row g-2 small text-muted">
          <div class="col-md-3"><i class="bi bi-calendar3 me-1"></i>Entrada: <?= date_br($os['data_entrada'], true) ?></div>
          <div class="col-md-3"><i class="bi bi-clock me-1"></i>Previsão: <?= date_br($os['data_previsao'], true) ?: '—' ?></div>
          <div class="col-md-3"><i class="bi bi-person-gear me-1"></i>Técnico: <?= e($os['tecnico_nome'] ?? '—') ?></div>
          <div class="col-md-3"><i class="bi bi-shield-check me-1"></i>Garantia: <?= $os['garantia_dias'] ?> dias</div>
        </div>
      </div>
    </div>

    <!-- Serviços -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold">
        Serviços Realizados
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalServico"><i class="bi bi-plus-lg"></i> Adicionar</button>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 small align-middle" id="tblServicos">
          <thead class="table-light"><tr><th>Descrição</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th>Técnico</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php foreach ($os['servicos'] as $s): ?>
            <tr>
              <td><?= e($s['descricao']) ?></td>
              <td><?= $s['quantidade'] ?></td>
              <td><?= money($s['valor_unitario']) ?></td>
              <td class="fw-semibold"><?= money($s['valor_total']) ?></td>
              <td><?= e($s['tecnico_nome'] ?? '—') ?></td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick="editarServico(<?= $s['id'] ?>, '<?= addslashes(e($s['descricao'])) ?>', <?= $s['quantidade'] ?>, <?= $s['valor_unitario'] ?>, '<?= $s['tecnico_id'] ?>')">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= url('/os/' . $os['id'] . '/servicos/' . $s['id']) ?>"
                   class="btn btn-sm btn-outline-danger"
                   data-method="DELETE"
                   data-confirm="Remover este serviço?">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$os['servicos']): ?>
            <tr><td colspan="6" class="text-center text-muted py-3">Nenhum serviço adicionado.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Peças -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center fw-semibold">
        Peças Utilizadas
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalPeca"><i class="bi bi-plus-lg"></i> Adicionar</button>
      </div>
      <div class="table-responsive">
        <table class="table mb-0 small align-middle">
          <thead class="table-light"><tr><th>Peça</th><th>Qtd</th><th>Valor Unit.</th><th>Total</th><th class="text-end">Ações</th></tr></thead>
          <tbody>
            <?php foreach ($os['pecas'] as $p): ?>
            <tr>
              <td>
                <?= e($p['descricao']) ?>
                <?php if ($p['prod_codigo']): ?><span class="badge bg-light text-muted border ms-1">#<?= e($p['prod_codigo']) ?></span><?php endif; ?>
              </td>
              <td><?= $p['quantidade'] ?></td>
              <td><?= money($p['valor_unitario']) ?></td>
              <td class="fw-semibold"><?= money($p['valor_total']) ?></td>
              <td class="text-end">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                  onclick="editarPeca(<?= $p['id'] ?>, '<?= addslashes(e($p['descricao'])) ?>', <?= $p['quantidade'] ?>, <?= $p['valor_unitario'] ?>)">
                  <i class="bi bi-pencil"></i>
                </button>
                <a href="<?= url('/os/' . $os['id'] . '/pecas/' . $p['id']) ?>"
                   class="btn btn-sm btn-outline-danger"
                   data-method="DELETE"
                   data-confirm="Remover esta peça?">
                  <i class="bi bi-trash"></i>
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
            <?php if (!$os['pecas']): ?>
            <tr><td colspan="5" class="text-center text-muted py-3">Nenhuma peça adicionada.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Garantia -->
    <?php if (!empty($os['garantia_ate']) || !empty($os['os_origem_numero'])): ?>
    <div class="card border-0 shadow-sm mb-3 <?= ($os['em_garantia'] ?? false) ? 'border-danger border' : 'border-secondary border' ?>">
      <div class="card-header d-flex justify-content-between align-items-center
                  <?= ($os['em_garantia'] ?? false) ? 'bg-danger bg-opacity-10' : 'bg-light' ?>">
        <span class="fw-semibold <?= ($os['em_garantia'] ?? false) ? 'text-danger' : 'text-muted' ?>">
          <i class="bi bi-shield-<?= ($os['em_garantia'] ?? false) ? 'check-fill' : 'x' ?> me-1"></i>
          <?php if (!empty($os['os_origem_numero'])): ?>
            OS de Garantia — vinculada à <a href="<?= url('/os/' . $os['os_origem_id']) ?>" class="fw-bold"><?= e($os['os_origem_numero']) ?></a>
          <?php else: ?>
            Garantia do Serviço
          <?php endif; ?>
        </span>
        <?php if (($os['em_garantia'] ?? false) && empty($os['os_origem_id'])): ?>
        <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalGarantia">
          <i class="bi bi-arrow-return-left me-1"></i>Registrar Retorno
        </button>
        <?php endif; ?>
      </div>
      <div class="card-body py-3">
        <div class="row g-3 align-items-center">
          <?php if (!empty($os['garantia_ate'])): ?>
          <div class="col-md-4 text-center">
            <div class="small text-muted">Válida até</div>
            <div class="fw-bold fs-5 <?= ($os['em_garantia'] ?? false) ? 'text-danger' : 'text-danger' ?>">
              <?= date_br($os['garantia_ate']) ?>
            </div>
          </div>
          <div class="col-md-4 text-center">
            <div class="small text-muted">Prazo</div>
            <div class="fw-bold"><?= $os['garantia_dias'] ?? 0 ?> dias</div>
          </div>
          <div class="col-md-4 text-center">
            <?php $dias = $os['dias_garantia_restantes'] ?? null; ?>
            <div class="small text-muted">
              <?= $dias !== null && $dias >= 0 ? 'Dias restantes' : 'Expirou há' ?>
            </div>
            <div class="fw-bold <?= ($dias !== null && $dias >= 0) ? 'text-danger' : 'text-danger' ?>">
              <?php if ($dias !== null): ?>
                <?= abs($dias) ?> dia<?= abs($dias) !== 1 ? 's' : '' ?>
              <?php else: ?>—<?php endif; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($os['motivo_retorno'])): ?>
          <div class="col-12">
            <div class="small text-muted fw-semibold">Motivo do retorno</div>
            <div class="small"><?= nl2br(e($os['motivo_retorno'])) ?></div>
          </div>
          <?php endif; ?>
        </div>

        <?php if (!($os['em_garantia'] ?? false) && empty($os['os_origem_id'])): ?>
        <div class="alert alert-warning mb-0 mt-2 py-2 small">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Garantia expirada em <?= date_br($os['garantia_ate']) ?>.
        </div>
        <?php endif; ?>

        <!-- OS filhas de garantia -->
        <?php if (!empty($os['garantias'])): ?>
        <div class="mt-3">
          <div class="small fw-semibold text-muted mb-2">Retornos de garantia registrados:</div>
          <?php foreach ($os['garantias'] as $g): ?>
          <div class="d-flex align-items-center gap-2 p-2 border rounded mb-1">
            <i class="bi bi-arrow-return-right text-warning"></i>
            <a href="<?= url('/os/' . $g['id']) ?>" class="fw-semibold text-decoration-none">OS: <?= e($g['numero']) ?></a>
            <span class="text-muted small"><?= date_br($g['criado_em']) ?></span>
            <?= badge_status_os($g['status_tipo'], $g['status_nome'], $g['status_cor'] ?? '') ?>
            <span class="ms-auto text-muted small"><?= money($g['valor_total']) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Histórico -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold">Histórico de Status</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($os['historico'] as $h): ?>
        <li class="list-group-item small">
          <div class="d-flex justify-content-between">
            <div>
              <?php if ($h['status_ant']): ?><span class="text-muted"><?= e($h['status_ant']) ?></span> → <?php endif; ?>
              <strong><?= e($h['status_nov'] ?? '') ?></strong>
              <?php if ($h['descricao']): ?> — <?= e($h['descricao']) ?><?php endif; ?>
            </div>
            <small class="text-muted"><?= date_br($h['criado_em'], true) ?> • <?= e($h['usuario_nome'] ?? 'Sistema') ?></small>
          </div>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <!-- Lateral -->
  <div class="col-md-4">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Resumo Financeiro</div>
      <div class="card-body">
        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Serviços</span><strong><?= money(array_sum(array_column($os['servicos'],'valor_total'))) ?></strong></div>
        <div class="d-flex justify-content-between mb-1"><span class="text-muted">Peças</span><strong><?= money(array_sum(array_column($os['pecas'],'valor_total'))) ?></strong></div>
        <?php if ($os['desconto_valor'] > 0): ?>
        <div class="d-flex justify-content-between mb-1 text-danger"><span>Desconto</span><strong>- <?= money($os['desconto_valor']) ?></strong></div>
        <?php endif; ?>
        <hr>
        <div class="d-flex justify-content-between fs-5"><span class="fw-bold">Total</span><strong><?= money($os['valor_total']) ?></strong></div>
        <?php if ($os['valor_pago'] > 0): ?>
        <div class="d-flex justify-content-between text-danger mt-1"><span>Pago</span><strong><?= money($os['valor_pago']) ?></strong></div>
        <div class="d-flex justify-content-between text-danger mt-1"><span>Saldo</span><strong><?= money($os['valor_total'] - $os['valor_pago']) ?></strong></div>
        <?php endif; ?>
        <?php $spMap=['pendente'=>'warning','parcial'=>'info','pago'=>'success']; ?>
        <div class="mt-2"><span class="badge bg-<?= $spMap[$os['situacao_pagamento']] ?>"><?= ucfirst($os['situacao_pagamento']) ?></span></div>
      </div>
    </div>
  </div>
</div>

<!-- Modal Status -->
<div class="modal fade" id="modalStatus" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <form class="modal-content" id="formStatus">
      <div class="modal-header"><h5 class="modal-title">Alterar Status</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <select name="status_id" class="form-select mb-2" id="novoStatus">
          <?php foreach ($statusList as $s): ?>
          <option value="<?= $s['id'] ?>" <?= $os['status_id']==$s['id']?'selected':'' ?>><?= e($s['nome']) ?></option>
          <?php endforeach; ?>
        </select>
        <textarea name="descricao" class="form-control" rows="2" placeholder="Observação (opcional)"></textarea>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL RETORNO DE GARANTIA ─────────────────────────── -->
<div class="modal fade" id="modalGarantia" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/garantia') ?>">
      <?= csrf_field() ?>
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-shield-check me-2"></i>Retorno em Garantia — OS <?= e($os['numero']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Info garantia -->
        <div class="alert alert-danger py-2 mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <i class="bi bi-shield-check me-1"></i>
              <strong>Garantia válida</strong> até <?= date_br($os['garantia_ate'] ?? '') ?>
            </div>
            <span class="badge bg-success fs-6">
              <?= $os['dias_garantia_restantes'] ?? 0 ?> dia(s) restante(s)
            </span>
          </div>
        </div>

        <!-- Equipamento -->
        <div class="mb-3 p-3 bg-light rounded small">
          <div class="fw-semibold"><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?></div>
          <div class="text-muted"><?= e($os['equip_tipo']??'') ?></div>
          <?php if ($os['numero_serie']??null): ?><div>S/N: <?= e($os['numero_serie']) ?></div><?php endif; ?>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Motivo do retorno *</label>
          <textarea name="motivo_retorno" class="form-control" rows="3" required
            placeholder="Descreva o problema que o cliente está relatando no retorno..."></textarea>
          <div class="form-text">Este texto será o defeito relatado na nova OS de garantia.</div>
        </div>

        <div class="mb-0">
          <label class="form-label fw-semibold">Técnico responsável</label>
          <select name="tecnico_id" class="form-select">
            <option value="">— Mesmo técnico (<?= e($os['tecnico_nome'] ?? 'sem técnico') ?>) —</option>
            <?php foreach ($tecnicos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($os['tecnico_id']==$t['id'])? 'selected':'' ?>>
              <?= e($t['nome']) ?>
            </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="alert alert-info mt-3 mb-0 py-2 small">
          <i class="bi bi-info-circle me-1"></i>
          Uma nova OS do tipo <strong>Garantia</strong> será aberta com valor R$ 0,00,
          vinculada a esta OS original. A garantia da nova OS herda o prazo restante.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold px-4">
          <i class="bi bi-plus-circle me-1"></i>Abrir OS de Garantia
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL FECHAR OS ───────────────────────────────────── -->
<div class="modal fade" id="modalFechar" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/fechar') ?>">
      <?= csrf_field() ?>
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-check-circle me-2"></i>Fechar Ordem de Serviço <?= e($os['numero']) ?>
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- Resumo financeiro -->
        <div class="alert alert-light border mb-4">
          <div class="row text-center g-2">
            <div class="col-4">
              <div class="text-muted small">Serviços</div>
              <div class="fw-bold"><?= money(array_sum(array_column($os['servicos'], 'valor_total'))) ?></div>
            </div>
            <div class="col-4">
              <div class="text-muted small">Peças</div>
              <div class="fw-bold"><?= money(array_sum(array_column($os['pecas'], 'valor_total'))) ?></div>
            </div>
            <div class="col-4">
              <div class="text-muted small">Total</div>
              <div class="fw-bold fs-5 text-danger"><?= money($os['valor_total']) ?></div>
            </div>
          </div>
        </div>

        <div class="row g-3">
          <!-- Laudo / Solução -->
          <div class="col-12">
            <label class="form-label fw-semibold">Solução aplicada *</label>
            <textarea name="solucao_aplicada" class="form-control" rows="2"
              required placeholder="Descreva o que foi feito para resolver o defeito..."><?= e($os['solucao_aplicada'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Laudo técnico</label>
            <textarea name="laudo_tecnico" class="form-control" rows="2"
              placeholder="Diagnóstico técnico detalhado..."><?= e($os['laudo_tecnico'] ?? '') ?></textarea>
          </div>
          <div class="col-12">
            <label class="form-label fw-semibold">Observações para o cliente</label>
            <textarea name="observacoes_cliente" class="form-control" rows="2"
              placeholder="Instruções de uso, cuidados, recomendações..."><?= e($os['observacoes_cliente'] ?? '') ?></textarea>
          </div>

          <!-- Garantia -->
          <div class="col-md-4">
            <label class="form-label fw-semibold">Garantia (dias)</label>
            <div class="input-group">
              <input type="number" name="garantia_dias" class="form-control"
                value="<?= e($os['garantia_dias'] ?? 90) ?>" min="0" id="garantiaDias"
                oninput="calcularGarantia(this.value)">
              <span class="input-group-text">dias</span>
            </div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Válida até</label>
            <input type="text" class="form-control bg-light fw-semibold text-danger"
              id="garantiaAte" readonly>
          </div>

          <!-- Pagamento -->
          <div class="col-md-4">
            <label class="form-label fw-semibold">Forma de pagamento</label>
            <select name="forma_pagamento" class="form-select">
              <option value="">— Sem registro —</option>
              <option value="dinheiro">Dinheiro</option>
              <option value="pix">PIX</option>
              <option value="cartao_credito">Cartão de Crédito</option>
              <option value="cartao_debito">Cartão de Débito</option>
              <option value="transferencia">Transferência</option>
              <option value="boleto">Boleto</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-semibold">Valor recebido (R$)</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_pago" class="form-control"
                placeholder="0,00" value="<?= $os['valor_total'] > 0 ? number_format($os['valor_total'], 2, ',', '.') : '' ?>">
            </div>
          </div>
        </div>

        <div class="alert alert-warning mt-3 mb-0 py-2 small">
          <i class="bi bi-info-circle me-1"></i>
          Ao fechar, a OS será marcada como <strong>Concluída</strong>, a data de conclusão e a garantia serão registradas, e você será redirecionado para o <strong>comprovante de entrega</strong>.
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold px-4">
          <i class="bi bi-check-circle me-1"></i>Confirmar fechamento
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL SERVIÇO ─────────────────────────────────────── -->
<div class="modal fade" id="modalServico" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/servicos') ?>" id="formServico">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="modalServicoTitulo">Novo Serviço</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="servico_id" id="svcId">
        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição *</label>
          <input type="text" name="descricao" id="svcDesc" class="form-control" required placeholder="Ex: Troca de tela, Diagnóstico...">
        </div>
        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Quantidade</label>
            <input type="text" name="quantidade" id="svcQtd" class="form-control" value="1" required placeholder="1">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Valor Unitário</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_unitario" id="svcVal" class="form-control" required placeholder="0,00">
            </div>
          </div>
        </div>
        <div class="mt-3">
          <label class="form-label fw-semibold">Técnico responsável</label>
          <select name="tecnico_id" class="form-select">
            <option value="">— Sem técnico —</option>
            <?php foreach ($tecnicos as $t): ?>
            <option value="<?= $t['id'] ?>" <?= ($os['tecnico_id'] == $t['id']) ? 'selected' : '' ?>><?= e($t['nome']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- ── MODAL PEÇA ────────────────────────────────────────── -->
<div class="modal fade" id="modalPeca" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" action="<?= url('/os/' . $os['id'] . '/pecas') ?>" id="formPeca">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title" id="modalPecaTitulo">Nova Peça</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="peca_id" id="pecaId">
        <input type="hidden" name="produto_id" id="pecaProdId">

        <div class="mb-3">
          <label class="form-label fw-semibold">Buscar no estoque</label>
          <div class="position-relative">
            <input type="text" id="pecaBusca" class="form-control" placeholder="Digite código ou nome do produto..." autocomplete="off">
            <div id="pecaSugestoes" class="list-group position-absolute w-100 shadow z-3" style="display:none;max-height:180px;overflow-y:auto;top:100%"></div>
          </div>
          <div class="form-text">Opcional — selecione para baixar o estoque automaticamente.</div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-semibold">Descrição da peça *</label>
          <input type="text" name="descricao" id="pecaDesc" class="form-control" required placeholder="Ex: Display LCD, Bateria 3000mAh...">
        </div>

        <div class="row g-3">
          <div class="col-6">
            <label class="form-label fw-semibold">Quantidade</label>
            <input type="text" name="quantidade" id="pecaQtd" class="form-control" value="1" required placeholder="1">
          </div>
          <div class="col-6">
            <label class="form-label fw-semibold">Valor Unitário</label>
            <div class="input-group">
              <span class="input-group-text">R$</span>
              <input type="text" name="valor_unitario" id="pecaVal" class="form-control" required placeholder="0,00">
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Garantia — cálculo ao vivo ────────────────────────────
function calcularGarantia(dias) {
  const d = new Date();
  d.setDate(d.getDate() + parseInt(dias || 0));
  const dia = String(d.getDate()).padStart(2,'0');
  const mes = String(d.getMonth()+1).padStart(2,'0');
  document.getElementById('garantiaAte').value = `${dia}/${mes}/${d.getFullYear()}`;
}
document.addEventListener('DOMContentLoaded', function() {
  const dias = document.getElementById('garantiaDias');
  if (dias) calcularGarantia(dias.value);
});

// ── Status via AJAX ───────────────────────────────────────
document.getElementById('formStatus').addEventListener('submit', async function(e) {
  e.preventDefault();
  const r = await fetch('<?= url('/os/' . $os['id'] . '/status') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
    body: JSON.stringify({
      status_id: document.getElementById('novoStatus').value,
      descricao: this.querySelector('[name=descricao]').value,
    })
  });
  if ((await r.json()).success) location.reload();
});

// ── Editar serviço ────────────────────────────────────────
function editarServico(id, descricao, quantidade, valor, tecnico_id) {
  document.getElementById('svcId').value    = id;
  document.getElementById('svcDesc').value  = descricao;
  document.getElementById('svcQtd').value   = quantidade;
  document.getElementById('svcVal').value   = valor;
  document.querySelector('#formServico [name="tecnico_id"]').value = tecnico_id || '';
  document.getElementById('modalServicoTitulo').textContent = 'Editar Serviço';
  document.getElementById('formServico').action = '<?= url('/os/' . $os['id'] . '/servicos') ?>';
  new bootstrap.Modal('#modalServico').show();
}

// Resetar modal ao fechar
document.getElementById('modalServico').addEventListener('hidden.bs.modal', function() {
  document.getElementById('formServico').reset();
  document.getElementById('svcId').value = '';
  document.getElementById('modalServicoTitulo').textContent = 'Novo Serviço';
});

// ── Editar peça ───────────────────────────────────────────
function editarPeca(id, descricao, quantidade, valor) {
  document.getElementById('pecaId').value   = id;
  document.getElementById('pecaDesc').value = descricao;
  document.getElementById('pecaQtd').value  = quantidade;
  document.getElementById('pecaVal').value  = valor;
  document.getElementById('modalPecaTitulo').textContent = 'Editar Peça';
  new bootstrap.Modal('#modalPeca').show();
}

document.getElementById('modalPeca').addEventListener('hidden.bs.modal', function() {
  document.getElementById('formPeca').reset();
  document.getElementById('pecaId').value    = '';
  document.getElementById('pecaProdId').value= '';
  document.getElementById('pecaBusca').value = '';
  document.getElementById('pecaSugestoes').style.display = 'none';
  document.getElementById('modalPecaTitulo').textContent = 'Nova Peça';
});

// ── Busca de produto no estoque ───────────────────────────
let pecaTimer;
document.getElementById('pecaBusca').addEventListener('input', function() {
  clearTimeout(pecaTimer);
  const q = this.value.trim();
  const box = document.getElementById('pecaSugestoes');
  if (q.length < 2) { box.style.display = 'none'; return; }

  pecaTimer = setTimeout(async () => {
    const r    = await fetch('<?= url('/api/produtos') ?>?q=' + encodeURIComponent(q));
    const list = await r.json();
    box.innerHTML = '';
    if (!list.length) { box.style.display = 'none'; return; }
    list.forEach(p => {
      const a = document.createElement('a');
      a.className = 'list-group-item list-group-item-action small py-2';
      a.href = '#';
      a.innerHTML = `<strong>${p.nome}</strong> <span class="text-muted ms-2">Estoque: ${p.estoque_atual} ${p.unidade} · R$ ${p.valor_venda}</span>`;
      a.addEventListener('click', ev => {
        ev.preventDefault();
        document.getElementById('pecaProdId').value = p.id;
        document.getElementById('pecaDesc').value   = p.nome;
        document.getElementById('pecaVal').value    = p.valor_venda;
        document.getElementById('pecaBusca').value  = p.nome;
        box.style.display = 'none';
      });
      box.appendChild(a);
    });
    box.style.display = 'block';
  }, 300);
});
document.addEventListener('click', e => {
  if (!e.target.closest('#pecaBusca') && !e.target.closest('#pecaSugestoes'))
    document.getElementById('pecaSugestoes').style.display = 'none';
});
</script>

