<!-- Totais por status + garantia -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <?php foreach ($totais as $s): ?>
  <a href="?status_id=<?= $s['id'] ?>"
     class="text-decoration-none fw-semibold <?= $filtros['status_id'] == $s['id'] ? '' : 'opacity-75' ?>"
     style="background:<?= e($s['cor']) ?>;color:<?= e($s['cor_fonte'] ?? '#ffffff') ?>;padding:6px 14px;border-radius:20px;font-size:.85rem;letter-spacing:.01em">
    <?= e($s['nome']) ?> <span style="opacity:.85">(<?= $s['total'] ?>)</span>
  </a>
  <?php endforeach; ?>

  <!-- Etiqueta GARANTIA — nativa do sistema, não editável -->
  <a href="?em_garantia=1"
     class="text-decoration-none fw-semibold <?= !empty($filtros['em_garantia']) ? '' : 'opacity-75' ?>"
     style="background:#dc2626;color:#fff;padding:6px 14px;border-radius:20px;font-size:.85rem;border:2px solid #991b1b"
     title="Ordens de Serviço em garantia — etiqueta nativa do sistema">
    <i class="bi bi-shield-fill-check me-1"></i>Garantia
    <span style="opacity:.85">(<?= $totalGarantia ?? 0 ?>)</span>
    <span style="font-size:.65rem;opacity:.7;margin-left:4px" title="Etiqueta do sistema">🔒</span>
  </a>

  <!-- Etiqueta FECHADAS -->
  <a href="?fechadas=1"
     class="text-decoration-none fw-semibold <?= !empty($filtros['fechadas']) ? '' : 'opacity-75' ?>"
     style="background:#495057;color:#fff;padding:6px 14px;border-radius:20px;font-size:.85rem;border:2px solid #343a40"
     title="Ordens de Servico fechadas/entregues">
    <i class="bi bi-check-circle-fill me-1"></i>Fechadas
    <span style="opacity:.85">(<?= $totalFechadas ?? 0 ?>)</span>
  </a>

  <?php if ($filtros['status_id'] || !empty($filtros['em_garantia']) || !empty($filtros['fechadas'])): ?>
  <a href="?" class="text-decoration-none text-white fw-semibold"
     style="background:#6c757d;padding:6px 14px;border-radius:20px;font-size:.85rem">
    <i class="bi bi-x me-1"></i>Limpar
  </a>
  <?php endif; ?>
</div>

<!-- Filtros -->
<form class="card border-0 shadow-sm p-3 mb-3" method="GET">
  <div class="row g-2 align-items-end">
    <div class="col-md-3">
      <input type="search" name="busca" class="form-control" placeholder="Nº OS, cliente, CPF/CNPJ, telefone, série..." value="<?= e($filtros['busca']) ?>">
    </div>
    <div class="col-md-3">
      <select name="status_id" class="form-select" onchange="this.form.submit()">
        <option value="">Todos os status</option>
        <?php foreach ($totais as $s): ?>
        <option value="<?= $s['id'] ?>" <?= $filtros['status_id'] == $s['id'] ? 'selected' : '' ?>><?= e($s['nome']) ?> (<?= $s['total'] ?>)</option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="tecnico_id" class="form-select">
        <option value="">Todos técnicos</option>
        <?php foreach ($tecnicos as $t): ?>
        <option value="<?= $t['id'] ?>" <?= $filtros['tecnico_id'] == $t['id']?'selected':'' ?>><?= e($t['nome']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <select name="prioridade" class="form-select" onchange="this.form.submit()">
        <option value="">Toda prioridade</option>
        <?php foreach (['urgente'=>'Urgente','alta'=>'Alta','normal'=>'Normal','baixa'=>'Baixa'] as $pv=>$pl): ?>
        <option value="<?= $pv ?>" <?= ($filtros['prioridade'] ?? '') === $pv ? 'selected' : '' ?>><?= $pl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <input type="date" name="data_inicio" class="form-control" value="<?= e($filtros['data_inicio'] ?? '') ?>">
    </div>
    <div class="col-md-2">
      <input type="date" name="data_fim" class="form-control" value="<?= e($filtros['data_fim'] ?? '') ?>">
    </div>
    <div class="col-md-2 d-flex gap-2">
      <button class="btn btn-outline-secondary flex-fill"><i class="bi bi-search"></i></button>
      <a href="<?= url('/os/nova') ?>" class="btn btn-primary flex-fill"><i class="bi bi-plus-lg"></i> Nova</a>
    </div>
    <div class="col-12 col-md-auto">
      <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#modalEntradaGarantia">
        <i class="bi bi-shield-check me-1"></i> Entrada de Garantia
      </button>
    </div>
    <div class="col-12 col-md-auto">
      <button type="button" class="btn w-100 text-white" style="background:#fd7e14;border-color:#fd7e14"
              data-bs-toggle="modal" data-bs-target="#modalReabrirOs">
        <i class="bi bi-arrow-counterclockwise me-1"></i> Reabrir OS
      </button>
    </div>
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle" style="font-size:1rem">
      <thead class="table-light">
        <tr>
          <th style="width:30px"></th><th>OS</th><th>Contato</th><th>WhatsApp</th><th>Status</th><th>Valor</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $os):
          $atrasada   = $os['data_previsao'] && strtotime($os['data_previsao']) < time() && !in_array($os['status_tipo'],['concluida','entregue','cancelada']);
          $ehGarantia = $os['tipo_servico'] === 'garantia' && !empty($os['os_origem_id']);
        ?>
        <tr class="os-row <?= $atrasada ? 'table-danger' : '' ?>" style="cursor:pointer" title="Clique para ver detalhes">
          <td class="text-center text-muted"><i class="bi bi-chevron-right os-caret" style="transition:transform .15s;font-size:.75rem"></i></td>
          <td>
            <a href="<?= url('/os/' . $os['id']) ?>" class="fw-bold text-decoration-none" style="color:#1e3a5f;font-size:.95rem">OS: <?= e($os['numero']) ?></a>
            <?php if ($atrasada): ?><i class="bi bi-alarm text-danger ms-1" title="Atrasada"></i><?php endif; ?>
            <?php if ($ehGarantia): ?><span class="badge bg-danger ms-1" style="font-size:.65rem"><i class="bi bi-shield-check"></i> Garantia</span><?php endif; ?>
          </td>
          <td>
            <div style="color:#1e3a5f;font-weight:600"><?= e($os['cliente_contato'] ?? '') ?: e($os['cliente_nome']) ?></div>
            <?php $equip = trim(($os['equip_marca']??'').' '.($os['equip_modelo']??'')); ?>
            <?php if ($equip): ?><div style="color:#64748b;font-size:.82rem"><?= e($equip) ?></div><?php endif; ?>
          </td>
          <td>
            <?php if ($os['cliente_whats']): ?>
              <span class="text-success"><i class="bi bi-whatsapp me-1"></i><?= e($os['cliente_whats']) ?></span>
            <?php elseif ($os['cliente_tel']): ?>
              <span class="text-muted"><i class="bi bi-telephone me-1"></i><?= e($os['cliente_tel']) ?></span>
            <?php else: ?>
              <span class="text-muted">--</span>
            <?php endif; ?>
          </td>
          <td>
            <?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '', $os['status_cor_fonte'] ?? '#ffffff') ?>
          </td>
          <td style="white-space:nowrap"><?php if (!empty($os['fechada_sem_receita'])): ?><span style="background:#dc3545;color:#fff;font-weight:600;padding:3px 9px;border-radius:6px;font-size:.76rem"><i class="bi bi-check-circle me-1"></i>Sem Débito</span><?php elseif (($os['valor_total'] ?? 0) > 0): ?><span style="color:#16a34a;font-weight:700"><?= money($os['valor_total']) ?></span><?php elseif (!empty($os['garantia_finalizada'])): ?><span style="color:#0d9488;font-weight:700"><i class="bi bi-shield-check me-1"></i>Garantia finalizada</span><?php elseif (!in_array($os['status_tipo'] ?? '', ['aberta', 'em_andamento', 'aguardando'], true)): ?><span style="background:#dc3545;color:#fff;font-weight:600;padding:3px 9px;border-radius:6px;font-size:.76rem"><i class="bi bi-check-circle me-1"></i>Sem Débito</span><?php else: ?><span style="color:#b45309"><i class="bi bi-hourglass-split me-1"></i>Pendente</span><?php endif; ?></td>
          <td class="text-end" style="white-space:nowrap">
            <a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-outline-primary" title="Abrir OS"><i class="bi bi-eye"></i></a>
            <a href="<?= url('/os/' . $os['id'] . '/imprimir') ?>" class="btn btn-sm btn-outline-secondary" target="_blank" title="Imprimir"><i class="bi bi-printer"></i></a>
          </td>
        </tr>
        <tr class="os-detail" style="display:none">
          <td colspan="7" style="background:#f8fafc;border-top:0">
            <div class="d-flex flex-wrap gap-4 px-2 pt-2" style="font-size:.85rem">
              <div style="min-width:150px"><div class="text-muted" style="font-size:.72rem">Cliente</div><?= e($os['cliente_nome']) ?></div>
              <div style="min-width:150px"><div class="text-muted" style="font-size:.72rem">Equipamento</div><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?: '--' ?><?php if ($os['equip_tipo']??''): ?> <span class="text-muted">(<?= e($os['equip_tipo']) ?>)</span><?php endif; ?></div>
              <div><div class="text-muted" style="font-size:.72rem">Técnico</div><?= e($os['tecnico_nome'] ?? '') ?: '--' ?></div>
              <div><div class="text-muted" style="font-size:.72rem">Prioridade</div><span class="badge badge-prioridade-<?= $os['prioridade'] ?>"><?= ucfirst($os['prioridade']) ?></span></div>
              <div><div class="text-muted" style="font-size:.72rem">Entrada</div><?= date_br($os['data_entrada'] ?? null, true) ?: '--' ?></div>
              <?php if ($_SESSION['mostrar_previsao'] ?? 1): ?><div><div class="text-muted" style="font-size:.72rem">Previsão</div><?= date_br($os['data_previsao'] ?? null, true) ?: '--' ?></div><?php endif; ?>
              <?php if (!empty($os['data_conclusao'])): ?><div><div class="text-muted" style="font-size:.72rem">Fechamento</div><?= date_br($os['data_conclusao'], true) ?></div><?php endif; ?>
              <div><div class="text-muted" style="font-size:.72rem">Telefone</div><?= e($os['cliente_tel'] ?? '') ?: '--' ?></div>
              <div><div class="text-muted" style="font-size:.72rem">Pagamento</div><?php if (!empty($os['garantia_finalizada'])): ?>Garantia finalizada<?php elseif (($os['valor_total'] ?? 0) == 0 && ($os['status_tipo'] ?? '') === 'entregue'): ?><span style="color:#16a34a;font-weight:600">Sem débito</span><?php else: ?><?= ucfirst($os['situacao_pagamento'] ?? 'pendente') ?><?php endif; ?></div>
              <div style="flex:1;min-width:220px"><div class="text-muted" style="font-size:.72rem">Defeito relatado</div><?= e($os['defeito_relatado'] ?? '') ?: '<span class="text-muted">—</span>' ?></div>
              <div style="flex:1;min-width:180px">
                <?php if (!empty($os['equip_acessorios'])): ?>
                <div class="px-2 py-1 d-inline-flex align-items-start gap-2" style="background:#fff7e6;border:1px solid #f5c26b;border-radius:6px">
                  <i class="bi bi-box-seam-fill text-warning mt-1"></i>
                  <div><span class="fw-bold text-uppercase" style="font-size:.72rem;color:#b45309">Acessórios</span><div class="fw-semibold" style="color:#7c4a03"><?= e($os['equip_acessorios']) ?></div></div>
                </div>
                <?php else: ?>
                <div class="text-muted" style="font-size:.72rem">Acessórios</div><span class="text-muted">—</span>
                <?php endif; ?>
              </div>
            </div>
            <div class="px-2 py-2 d-flex gap-2 flex-wrap align-items-center">
              <a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-primary"><i class="bi bi-folder2-open me-1"></i>Ver OS</a>
              <a href="<?= url('/os/' . $os['id'] . '/editar') ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Editar</a>
              <a href="<?= url('/os/' . $os['id'] . '/imprimir') ?>" target="_blank" class="btn btn-sm btn-outline-secondary"><i class="bi bi-printer me-1"></i>Imprimir</a>
              <?php if (\App\Core\Auth::isAdmin()): ?>
              <button type="button" class="btn btn-sm ms-auto" style="background:transparent;border:1px solid #dc3545;color:#dc3545;font-weight:600"
                      onclick="abrirExcluirOs(<?= $os['id'] ?>,'<?= e($os['numero']) ?>')"><i class="bi bi-trash me-1"></i>Excluir</button>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="7" class="text-center text-muted py-5">Nenhuma OS encontrada.</td></tr>
        <?php endif; ?>
      </tbody>
      <?php if ($paginator['data']): ?>
      <tfoot>
        <tr>
          <td colspan="7" style="background:#f0f4f8;border-top:2px solid #dbe2ea">
            <div class="d-flex justify-content-between align-items-center px-2 py-1">
              <span class="fw-bold text-muted" style="font-size:.8rem;letter-spacing:.04em">TOTAL DESTA LISTA</span>
              <span class="fw-bold" style="color:#16a34a;font-size:1.1rem"><i class="bi bi-cash-coin me-2"></i><?= money($paginator['soma_valor']) ?></span>
            </div>
          </td>
        </tr>
      </tfoot>
      <?php endif; ?>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $paginator['total'] ?> OS</small>
    <?= pagination($paginator, url('/os')) ?>
  </div>
  <?php endif; ?>
</div>

<script>
// Colapse clicável nas linhas da lista de OS
document.querySelectorAll('tr.os-row').forEach(function (row) {
  row.addEventListener('click', function (e) {
    if (e.target.closest('a, button')) return; // não expande ao clicar em link/botão
    var det = row.nextElementSibling;
    if (!det || !det.classList.contains('os-detail')) return;
    var aberto = det.style.display !== 'none';
    det.style.display = aberto ? 'none' : 'table-row';
    var car = row.querySelector('.os-caret');
    if (car) car.style.transform = aberto ? '' : 'rotate(90deg)';
  });
});
</script>

<?php if (\App\Core\Auth::isAdmin()): ?>
<!-- ── MODAL EXCLUIR OS (só admin) ──────────────────────────── -->
<div class="modal fade" id="modalExcluirOs" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" method="POST" id="formExcluirOs">
      <?= csrf_field() ?>
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title"><i class="bi bi-exclamation-triangle-fill me-2"></i>Excluir OS <span id="excluirOsNum"></span></h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-danger d-flex gap-2 mb-3">
          <i class="bi bi-trash3 fs-4"></i>
          <div><strong>Esta ação é IRREVERSÍVEL.</strong> A OS, seu histórico, serviços, peças e lançamentos financeiros serão apagados <strong>permanentemente</strong> e não poderão ser recuperados.</div>
        </div>
        <p class="mb-2 small text-muted"><i class="bi bi-shield-check me-1"></i>A exclusão fica registrada no <strong>Registro de Ações</strong> (quem excluiu e quando).</p>
        <label class="form-label small fw-semibold mb-1"><i class="bi bi-lock-fill me-1"></i>Confirme sua senha de login para excluir</label>
        <input type="password" name="senha" id="excluirOsSenha" class="form-control" autocomplete="off" placeholder="Sua senha" required>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-danger fw-bold"><i class="bi bi-trash me-1"></i>Excluir permanentemente</button>
      </div>
    </form>
  </div>
</div>
<script>
function abrirExcluirOs(id, numero) {
  document.getElementById('formExcluirOs').action = '<?= url('/os/') ?>' + id + '/excluir';
  document.getElementById('excluirOsNum').textContent = numero;
  document.getElementById('excluirOsSenha').value = '';
  new bootstrap.Modal(document.getElementById('modalExcluirOs')).show();
}
</script>
<?php endif; ?>

<!-- ── MODAL ENTRADA DE GARANTIA ──────────────────────────── -->
<div class="modal fade" id="modalEntradaGarantia" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white border-0">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-shield-check me-2"></i>Entrada de Garantia
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">

        <!-- PASSO 1: Busca -->
        <div id="passosBusca">
          <div class="mb-3">
            <label class="form-label fw-semibold">Buscar OS fechada em garantia</label>
            <div class="input-group">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="buscaGarantia" class="form-control form-control-lg"
                placeholder="Nome do cliente, Nº OS, marca ou modelo do equipamento..."
                autocomplete="off">
              <span class="spinner-border spinner-border-sm text-danger m-auto me-2 d-none" id="spinnerGarantia"></span>
            </div>
            <div class="form-text">Serão exibidas apenas OS concluídas/entregues com garantia ainda válida.</div>
          </div>

          <div id="resultadosGarantia" style="max-height:340px;overflow-y:auto">
            <div class="text-center text-muted py-4" id="msgGarantia">
              <div class="spinner-border text-danger mb-2" role="status"></div>
              <div>Carregando OS em garantia...</div>
            </div>
          </div>
        </div>

        <!-- PASSO 2: Motivo do retorno -->
        <div id="passo2Garantia" style="display:none">
          <div class="alert alert-danger py-2 mb-3" id="garantiaOsInfo"></div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Motivo do retorno *</label>
            <textarea id="motivoRetorno" class="form-control" rows="3"
              placeholder="Descreva o problema que o cliente relata no retorno em garantia..."></textarea>
            <div class="form-text">Será o defeito relatado na nova OS de garantia.</div>
          </div>
        </div>

        <!-- PASSO 3: Revisão do equipamento + form final -->
        <form method="POST" id="formConfirmarGarantia" style="display:none">
          <?= csrf_field() ?>
          <input type="hidden" name="motivo_retorno" id="motivoRetornoHidden">

          <div class="alert alert-danger py-2 mb-3" id="garantiaOsInfo3"></div>

          <div class="card border-0 bg-light mb-3">
            <div class="card-header bg-transparent fw-semibold small">
              <i class="bi bi-cpu me-1 text-primary"></i>Revisão do Equipamento na Entrada
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Estado de entrada</label>
                  <select name="estado_entrada" id="gEstado" class="form-select">
                    <option value="otimo">Ótimo</option>
                    <option value="bom">Bom</option>
                    <option value="regular" selected>Regular</option>
                    <option value="ruim">Ruim</option>
                    <option value="danificado">Danificado</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Técnico responsável</label>
                  <?php $defTecG = (int)($_SESSION['usuario_id'] ?? 0); ?>
                  <select name="tecnico_id" id="gTecnico" class="form-select">
                    <option value="">— Mesmo técnico —</option>
                    <?php foreach ($tecnicos ?? [] as $t): ?>
                    <option value="<?= $t['id'] ?>" <?= $defTecG==$t['id']?'selected':'' ?>><?= e($t['nome']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <div class="d-flex align-items-center justify-content-between mb-1">
                    <label class="form-label small fw-semibold mb-0">Acessórios que acompanham</label>
                    <span class="badge bg-primary" id="gBadgeQtd">0 selecionados</span>
                  </div>
                  <input type="hidden" name="acessorios" id="gAcessorios">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <div class="small text-muted fw-semibold mb-1">Disponíveis <span class="fw-normal opacity-75">— clique para adicionar</span></div>
                      <div id="gBancoDrop" class="border rounded p-2 bg-light"
                           style="min-height:88px;display:flex;flex-wrap:wrap;gap:6px;align-content:flex-start"></div>
                      <div class="input-group input-group-sm mt-2">
                        <input type="text" id="gInputNovoAcessorio" class="form-control" placeholder="Novo acessório..." maxlength="60"
                          onkeydown="if(event.key==='Enter'){event.preventDefault();gAddAcessorioBanco()}">
                        <button type="button" class="btn btn-outline-primary" onclick="gAddAcessorioBanco()"><i class="bi bi-plus-lg"></i></button>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="small text-muted fw-semibold mb-1">Selecionados</div>
                      <div id="gSelDrop" class="border border-primary rounded p-2"
                           style="min-height:88px;display:flex;flex-wrap:wrap;gap:6px;align-content:flex-start;background:rgba(13,110,253,.04)">
                        <div id="gMsgSelVazio" class="text-muted small w-100 text-center pt-3 opacity-50">Clique nos itens ao lado</div>
                      </div>
                    </div>
                  </div>
                  <div class="form-text">Revise o que o cliente está trazendo junto ao equipamento.</div>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Observações para o cliente</label>
                  <textarea name="observacoes_cliente" class="form-control" rows="2"
                    placeholder="Prazo estimado, orientações..."></textarea>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Observações internas</label>
                  <textarea name="observacoes_internas" class="form-control" rows="2"
                    placeholder="Notas para a equipe técnica..."></textarea>
                </div>
              </div>
            </div>
          </div>

          <div class="alert alert-info py-2 mb-0 small">
            <i class="bi bi-printer me-1"></i>
            Ao confirmar, a OS de garantia será criada e você será redirecionado para a <strong>impressão do comprovante</strong>.
          </div>
        </form>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" id="btnVoltarBusca"
          style="display:none" onclick="voltarPasso()">
          <i class="bi bi-arrow-left me-1"></i>Voltar
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <!-- Passo 2: avançar para equipamento -->
        <button type="button" class="btn btn-danger fw-bold px-4" id="btnAvancarEquip"
          style="display:none" onclick="avancarParaEquip()">
          Próximo <i class="bi bi-arrow-right ms-1"></i>
        </button>
        <!-- Passo 3: confirmar e criar -->
        <button type="button" class="btn btn-danger fw-bold px-4" id="btnAbrirGarantia"
          style="display:none" onclick="confirmarGarantia()">
          <i class="bi bi-printer me-1"></i>Criar OS e Imprimir
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ── MODAL REABRIR OS ──────────────────────────── -->
<div class="modal fade" id="modalReabrirOs" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header text-white border-0" style="background:#fd7e14">
        <h5 class="modal-title fw-bold">
          <i class="bi bi-arrow-counterclockwise me-2"></i>Reabrir OS
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Buscar OS fechada</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" id="buscaReabrir" class="form-control form-control-lg"
              placeholder="Nome do cliente, Nº OS, marca ou modelo do equipamento..."
              autocomplete="off">
            <span class="spinner-border spinner-border-sm m-auto me-2 d-none" style="color:#fd7e14" id="spinnerReabrir"></span>
          </div>
          <div class="form-text">Serão exibidas apenas OS concluídas/entregues.</div>
        </div>

        <div id="resultadosReabrir" style="max-height:340px;overflow-y:auto">
          <div class="text-center text-muted py-4" id="msgReabrir">
            <div class="spinner-border mb-2" style="color:#fd7e14" role="status"></div>
            <div>Carregando OS fechadas...</div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
      </div>
    </div>
  </div>
</div>

<form method="POST" id="formReabrirOs" style="display:none">
  <?= csrf_field() ?>
</form>

<script>
let osGarantiaSelecionada = null;
let timerGarantia;

// Carrega todas ao abrir o modal
document.getElementById('modalEntradaGarantia').addEventListener('shown.bs.modal', function() {
  buscarOsGarantia('');
  document.getElementById('buscaGarantia').focus();
  gLoadAcessorios();
});

// ── Acessórios (mesmo banco de chips do cadastro de aparelho) ──────────────
const G_CSRF = '<?= csrf_token() ?>';
let gBanco = [];          // {id, nome} disponíveis no banco
let gSelecionados = [];   // {id, nome} escolhidos
const gEhSemAcess = n => String(n||'').trim().toLowerCase() === 'sem acessórios';

async function gLoadAcessorios() {
  try {
    const r = await fetch('<?= url('/api/produto/equip_acessorios') ?>');
    gBanco = (await r.json()) || [];
  } catch (e) { gBanco = []; }
  gRender();
}

function gRender() {
  const banco = document.getElementById('gBancoDrop');
  const sel   = document.getElementById('gSelDrop');
  const msg   = document.getElementById('gMsgSelVazio');
  const selIds = gSelecionados.map(s => s.id);

  banco.innerHTML = '';
  gBanco.filter(i => !selIds.includes(i.id)).forEach(item => {
    banco.appendChild(gChip(item, false));
  });
  if (!banco.children.length) banco.innerHTML = '<div class="text-muted small w-100 text-center pt-3 opacity-50">Nenhum acessório no banco</div>';

  sel.querySelectorAll('.g-chip').forEach(c => c.remove());
  gSelecionados.forEach(item => sel.appendChild(gChip(item, true)));
  msg.style.display = gSelecionados.length ? 'none' : '';

  document.getElementById('gBadgeQtd').textContent = gSelecionados.length + ' selecionado' + (gSelecionados.length !== 1 ? 's' : '');
  document.getElementById('gAcessorios').value = gSelecionados.map(s => s.nome).join(', ');
}

function gChip(item, selecionado) {
  const div = document.createElement('div');
  div.className = 'g-chip d-flex align-items-center gap-1 px-2 py-1 rounded-pill border fw-semibold';
  div.style.cssText = selecionado
    ? 'background:#0d6efd;color:#fff;font-size:.78rem;cursor:pointer;user-select:none'
    : 'background:#f8f9fa;font-size:.78rem;cursor:pointer;user-select:none';
  const span = document.createElement('span');
  span.textContent = item.nome;
  div.appendChild(span);
  const ico = document.createElement('i');
  ico.className = selecionado ? 'bi bi-x-lg' : 'bi bi-plus-lg';
  ico.style.fontSize = '.7rem';
  div.appendChild(ico);
  div.onclick = () => selecionado ? gRemover(item.id) : gSelecionar(item);
  return div;
}

function gSelecionar(item) {
  if (gEhSemAcess(item.nome)) {
    gSelecionados = [item];                                     // "sem acessórios" é exclusivo
  } else {
    gSelecionados = gSelecionados.filter(s => !gEhSemAcess(s.nome)); // acessório real tira o "sem acessórios"
    if (!gSelecionados.some(s => s.id === item.id)) gSelecionados.push(item);
  }
  gRender();
}

function gRemover(id) { gSelecionados = gSelecionados.filter(s => s.id !== id); gRender(); }

async function gAddAcessorioBanco() {
  const inp  = document.getElementById('gInputNovoAcessorio');
  const nome = inp.value.trim();
  if (!nome) return;
  // já existe no banco? só seleciona
  const existente = gBanco.find(a => a.nome.toLowerCase() === nome.toLowerCase());
  if (existente) { gSelecionar(existente); inp.value=''; return; }
  try {
    const r = await fetch('<?= url('/api/produto/equip_acessorios') ?>', {
      method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':G_CSRF},
      body: JSON.stringify({ nome, csrf_token: G_CSRF })
    });
    const j = await r.json();
    if (j && j.lista) gBanco = j.lista;
    inp.value = '';
    const novo = gBanco.find(a => a.id === j.id) || gBanco.find(a => a.nome.toLowerCase() === nome.toLowerCase());
    if (novo) gSelecionar(novo); else gRender();
  } catch (e) { alert('Não foi possível adicionar o acessório.'); }
}

document.getElementById('buscaGarantia').addEventListener('input', function() {
  clearTimeout(timerGarantia);
  timerGarantia = setTimeout(() => buscarOsGarantia(this.value.trim()), 350);
});

async function buscarOsGarantia(q) {
  const spinner = document.getElementById('spinnerGarantia');
  const box     = document.getElementById('resultadosGarantia');
  spinner.classList.remove('d-none');

  const r    = await fetch('<?= url('/api/os/em-garantia') ?>?q=' + encodeURIComponent(q));
  const list = await r.json();
  spinner.classList.add('d-none');

  if (!list.length) {
    box.innerHTML = `
      <div class="text-center text-muted py-4">
        <i class="bi bi-shield-x fs-2 d-block mb-2 opacity-30"></i>
        <div>Nenhuma OS em garantia encontrada${q ? ' para <strong>"' + esc(q) + '"</strong>' : ''}.</div>
      </div>`;
    return;
  }

  box.innerHTML = list.map(os => `
    <div class="d-flex align-items-center gap-3 p-3 border-bottom os-garantia-item"
         style="cursor:pointer"
         onmouseenter="this.classList.add('bg-light')"
         onmouseleave="this.classList.remove('bg-light')"
         onclick="selecionarOsGarantia(${os.id}, '${escJs(os.numero)}', '${escJs(os.cliente_nome)}', '${escJs(os.equip_tipo)} ${escJs(os.equip_marca)} ${escJs(os.equip_modelo)}', '${escJs(os.garantia_ate)}', ${os.dias_restantes})">
      <div class="flex-shrink-0 text-center" style="width:48px">
        <div class="fw-bold text-success" style="font-size:.8rem">#${esc(os.numero)}</div>
        <div class="badge bg-danger mt-1" style="font-size:.65rem">${os.dias_restantes}d</div>
      </div>
      <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold">${esc(os.cliente_nome)}</div>
        <div class="text-muted small">${esc(os.equip_tipo)} ${esc(os.equip_marca)} ${esc(os.equip_modelo)}</div>
        <div class="small text-success"><i class="bi bi-shield-check me-1"></i>Garantia até ${formatarData(os.garantia_ate)}</div>
      </div>
      <div class="text-end flex-shrink-0">
        <div class="small text-muted">Concluída em</div>
        <div class="small fw-semibold">${formatarData(os.data_conclusao)}</div>
        <div class="text-success fw-semibold small">${formatarDinheiro(os.valor_total)}</div>
      </div>
      <i class="bi bi-chevron-right text-muted"></i>
    </div>
  `).join('');
}

let passoAtual = 1; // 1=busca, 2=motivo, 3=equipamento

function selecionarOsGarantia(id, numero, cliente, equipamento, garantiaAte, diasRestantes) {
  osGarantiaSelecionada = id;

  const infoHtml = `
    <div class="d-flex justify-content-between align-items-start">
      <div>
        <i class="bi bi-shield-check me-1"></i>
        <strong>OS #${esc(numero)}</strong> — ${esc(cliente)}<br>
        <span class="small opacity-75">${esc(equipamento)}</span>
      </div>
      <span class="badge bg-success">${diasRestantes} dia(s) restante(s)</span>
    </div>
    <div class="mt-1 small opacity-75">Garantia válida até <strong>${formatarData(garantiaAte)}</strong></div>`;

  document.getElementById('garantiaOsInfo').innerHTML  = infoHtml;
  document.getElementById('garantiaOsInfo3').innerHTML = infoHtml;
  document.getElementById('formConfirmarGarantia').action = '<?= url('/os/') ?>' + id + '/garantia';
  document.getElementById('motivoRetorno').value = '';

  irParaPasso(2);
}

function irParaPasso(passo) {
  passoAtual = passo;

  document.getElementById('passosBusca').style.display       = passo === 1 ? '' : 'none';
  document.getElementById('passo2Garantia').style.display    = passo === 2 ? '' : 'none';
  document.getElementById('formConfirmarGarantia').style.display = passo === 3 ? '' : 'none';

  document.getElementById('btnVoltarBusca').style.display  = passo > 1 ? '' : 'none';
  document.getElementById('btnAvancarEquip').style.display = passo === 2 ? '' : 'none';
  document.getElementById('btnAbrirGarantia').style.display = passo === 3 ? '' : 'none';

  if (passo === 2) setTimeout(() => document.getElementById('motivoRetorno').focus(), 100);
}

function voltarPasso() {
  if (passoAtual === 3) { irParaPasso(2); return; }
  if (passoAtual === 2) {
    osGarantiaSelecionada = null;
    irParaPasso(1);
  }
}

function avancarParaEquip() {
  const motivo = document.getElementById('motivoRetorno').value.trim();
  if (!motivo) {
    document.getElementById('motivoRetorno').classList.add('is-invalid');
    document.getElementById('motivoRetorno').focus();
    return;
  }
  document.getElementById('motivoRetorno').classList.remove('is-invalid');
  // Copiar motivo para o campo hidden do form
  document.getElementById('motivoRetornoHidden').value = motivo;
  irParaPasso(3);
}

function confirmarGarantia() {
  const form = document.getElementById('formConfirmarGarantia');
  const btn  = document.getElementById('btnAbrirGarantia');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Criando OS...';

  // Fechar o modal antes de navegar para não bloquear o redirect
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalEntradaGarantia'));
  if (modal) {
    // Aguardar o modal fechar e então submeter
    document.getElementById('modalEntradaGarantia').addEventListener('hidden.bs.modal', function handler() {
      document.getElementById('modalEntradaGarantia').removeEventListener('hidden.bs.modal', handler);
      form.submit();
    }, { once: true });
    modal.hide();
  } else {
    form.submit();
  }
}

// Reset ao fechar
document.getElementById('modalEntradaGarantia').addEventListener('hidden.bs.modal', function() {
  osGarantiaSelecionada = null;
  irParaPasso(1);
  document.getElementById('buscaGarantia').value = '';
  document.getElementById('motivoRetorno').value = '';
  gSelecionados = [];
  document.getElementById('gAcessorios').value = '';
  document.getElementById('resultadosGarantia').innerHTML =
    '<div class="text-center text-muted py-4"><i class="bi bi-shield-check fs-2 d-block mb-2 opacity-30"></i>Aguardando busca...</div>';
});

// Helpers
function esc(s)   { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escJs(s) { return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function formatarData(d) {
  if (!d) return '—';
  const p = d.split('-');
  if (p.length === 3) return p[2]+'/'+p[1]+'/'+p[0];
  return d;
}
function formatarDinheiro(v) {
  return 'R$ ' + parseFloat(v||0).toFixed(2).replace('.',',').replace(/\B(?=(\d{3})+(?!\d))/g,'.');
}

// ── Reabrir OS ────────────────────────────────────────────────────────
let timerReabrir;

document.getElementById('modalReabrirOs').addEventListener('shown.bs.modal', function() {
  buscarOsFechadas('');
  document.getElementById('buscaReabrir').focus();
});

document.getElementById('buscaReabrir').addEventListener('input', function() {
  clearTimeout(timerReabrir);
  timerReabrir = setTimeout(() => buscarOsFechadas(this.value.trim()), 350);
});

async function buscarOsFechadas(q) {
  const spinner = document.getElementById('spinnerReabrir');
  const box     = document.getElementById('resultadosReabrir');
  spinner.classList.remove('d-none');

  const r    = await fetch('<?= url('/api/os/fechadas') ?>?q=' + encodeURIComponent(q));
  const list = await r.json();
  spinner.classList.add('d-none');

  if (!list.length) {
    box.innerHTML = `
      <div class="text-center text-muted py-4">
        <i class="bi bi-inbox fs-2 d-block mb-2 opacity-30"></i>
        <div>Nenhuma OS fechada encontrada${q ? ' para <strong>"' + esc(q) + '"</strong>' : ''}.</div>
      </div>`;
    return;
  }

  box.innerHTML = list.map(os => `
    <div class="d-flex align-items-center gap-3 p-3 border-bottom"
         style="cursor:pointer"
         onmouseenter="this.classList.add('bg-light')"
         onmouseleave="this.classList.remove('bg-light')"
         onclick="confirmarReabrir(${os.id}, '${escJs(os.numero)}', '${escJs(os.cliente_nome)}')">
      <div class="flex-shrink-0 text-center" style="width:48px">
        <div class="fw-bold" style="font-size:.8rem;color:#fd7e14">#${esc(os.numero)}</div>
      </div>
      <div class="flex-grow-1 min-w-0">
        <div class="fw-semibold">${esc(os.cliente_nome)}</div>
        <div class="text-muted small">${esc(os.equip_tipo)} ${esc(os.equip_marca)} ${esc(os.equip_modelo)}</div>
        <div class="small text-muted">${esc(os.status_nome)}</div>
      </div>
      <div class="text-end flex-shrink-0">
        <div class="small text-muted">Fechada em</div>
        <div class="small fw-semibold">${formatarData(os.data_entrega || os.data_conclusao)}</div>
      </div>
      <i class="bi bi-chevron-right text-muted"></i>
    </div>
  `).join('');
}

function confirmarReabrir(id, numero, cliente) {
  if (!confirm('Reabrir a OS #' + numero + ' (' + cliente + ')? Ela voltará ao status anterior ao fechamento.')) return;
  const form = document.getElementById('formReabrirOs');
  form.action = '<?= url('/os/') ?>' + id + '/reabrir';
  form.submit();
}
</script>

<script>
// Salva esta página de OS no cache local (IndexedDB) pra dar pra consultar sem internet depois.
if (window.FixaosOffline) {
  window.FixaosOffline.salvarListaOS(<?= json_encode(array_map(function ($o) {
      return [
          'id'              => (int) $o['id'],
          'numero'          => $o['numero'],
          'cliente_nome'    => $o['cliente_nome'],
          'cliente_tel'     => $o['cliente_tel'],
          'cliente_whats'   => $o['cliente_whats'],
          'equip_tipo'      => $o['equip_tipo'],
          'equip_marca'     => $o['equip_marca'],
          'equip_modelo'    => $o['equip_modelo'],
          'status_nome'     => $o['status_nome'],
          'status_cor'      => $o['status_cor'],
          'status_cor_fonte'=> $o['status_cor_fonte'],
          'tecnico_nome'    => $o['tecnico_nome'],
          'prioridade'      => $o['prioridade'],
          'valor_total'     => $o['valor_total'],
          'criado_em'       => $o['criado_em'],
      ];
  }, $paginator['data']), JSON_UNESCAPED_UNICODE) ?>);
}
</script>

