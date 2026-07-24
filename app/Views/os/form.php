<style id="selExStyle">.sel-ex:has(option[value=""]:checked){color:#8a94a6}</style>
<?php $editando = !empty($os['id']); ?>
<?php $formAction = $editando ? url('/os/' . $os['id'] . '/editar') : url('/os'); ?>

<style>
/* Offcanvas acima do modal */
#offcanvasTipos, #offcanvasMarcas, #offcanvasTecnicos { z-index: 1200 !important; }
.offcanvas-backdrop { z-index: 1190 !important; }

/* Drag & drop */
#bancoDrop, #selecionadosDrop { transition: background .15s, border-color .15s; }
#bancoDrop.drag-over { background: #e8f4fd !important; border-color: #0d6efd !important; }
#selecionadosDrop.drag-over { background: #d0e8ff !important; border-color: #0a58ca !important; }
.chip-banco:hover { border-color: #0d6efd !important; background: #e8f0fe !important; }
.chip-sel:hover   { background: #0a58ca !important; }

/* Wizard de abas */
.os-steps {
  display: flex;
  gap: 0;
  margin-bottom: 2rem;
  background: #fff;
  border-radius: 14px;
  box-shadow: 0 1px 4px rgba(0,0,0,.07);
  overflow: hidden;
  border: 2px solid #C0C0C0;
}
.os-step {
  flex: 1;
  padding: .85rem .5rem;
  text-align: center;
  cursor: pointer;
  font-size: .8rem;
  font-weight: 600;
  color: #94a3b8;
  border-right: 2px solid #C0C0C0;
  transition: all .2s;
  position: relative;
  user-select: none;
}
.os-step:last-child { border-right: none; }
.os-step .step-num {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 26px; height: 26px;
  border-radius: 50%;
  background: #e2e8f0;
  color: #64748b;
  font-size: .78rem;
  font-weight: 700;
  margin: 0 auto .3rem;
  transition: all .2s;
}
.os-step .step-label { display: block; }
.os-step:hover {
  background: #C4C4C4;
  border-right: 2px solid #C0C0C0;
}
.os-step.active {
  background: #C4C4C4;
  color: #2563eb;
  border-right: 2px solid #C0C0C0;
  border-bottom: 3px solid #C0C0C0;
}
.os-step.active .step-num {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 4px 10px rgba(37,99,235,.35);
}
.os-step.done {
  color: #16a34a;
}
.os-step.done .step-num {
  background: #16a34a;
  color: #fff;
}
.os-tab-pane { display: none; }
.os-tab-pane.active { display: block; }

/* Botões de navegação */
.os-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; }
</style>

<form method="POST" action="<?= $formAction ?>" id="formOS">
  <?= csrf_field() ?>

  <!-- Campos ocultos -->
  <input type="hidden" name="cliente_id"       id="fClienteId"     value="<?= e($os['cliente_id']    ?? '') ?>">
  <input type="hidden" name="equipamento_id"   id="fEquipamentoId" value="<?= e($os['equipamento_id'] ?? '') ?>">
  <input type="hidden" name="categoria_id"     id="fCategoriaId"   value="<?= e($os['categoria_id']   ?? '') ?>">
  <input type="hidden" name="equip_tipo"        id="fEquipTipo"     value="<?= e($os['equip_tipo']     ?? '') ?>">
  <input type="hidden" name="equip_marca"       id="fEquipMarca"    value="<?= e($os['equip_marca']    ?? '') ?>">
  <input type="hidden" name="equip_modelo"      id="fEquipModelo"   value="<?= e($os['equip_modelo']   ?? '') ?>">
  <input type="hidden" name="numero_serie"      id="fNumeroSerie"   value="<?= e($os['numero_serie']   ?? '') ?>">
  <input type="hidden" name="imei"              id="fImei"          value="<?= e($os['imei']           ?? '') ?>">
  <input type="hidden" name="equip_cor"         id="fEquipCor"      value="<?= e($os['equip_cor']      ?? '') ?>">
  <input type="hidden" name="voltagem"          id="fVoltagem"      value="<?= e($os['voltagem']       ?? '') ?>">
  <input type="hidden" name="estado_entrada"    id="fEstadoEntrada" value="<?= e($os['estado_entrada'] ?? '') ?>">
  <input type="hidden" id="fDefeitoRelatado"><!-- sem name -- o textarea envia defeito_relatado -->
  <input type="hidden" name="acessorios"        id="fAcessorios"    value="<?= e($os['acessorios']        ?? '') ?>">
  <input type="hidden" name="senha_desbloqueio" id="fSenha"         value="<?= e($os['senha_desbloqueio'] ?? '') ?>">

  <!-- â”€â”€ Wizard Steps â”€â”€ -->
  <div class="os-steps" id="osSteps">
    <div class="os-step active" data-step="0" onclick="irParaStep(0)">
      <div class="step-num"><i class="bi bi-person"></i></div>
      <span class="step-label">Cliente</span>
    </div>
    <div class="os-step" data-step="1" onclick="irParaStep(1)">
      <div class="step-num"><i class="bi bi-cpu"></i></div>
      <span class="step-label">Equipamento</span>
    </div>
    <div class="os-step" data-step="2" onclick="irParaStep(2)">
      <div class="step-num"><i class="bi bi-tools"></i></div>
      <span class="step-label">Defeito</span>
    </div>
    <div class="os-step" data-step="3" onclick="irParaStep(3)">
      <div class="step-num"><i class="bi bi-gear"></i></div>
      <span class="step-label">Configurações</span>
    </div>
  </div>

  <!-- â•â• ABA 1 -- CLIENTE â•â• -->
  <div class="os-tab-pane active" id="step0">
    <div class="card shadow-sm" style="border:2px solid #C0C0C0!important">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-person-circle me-2 text-primary"></i>Selecione o cliente</span>
        <?php if (!$editando): ?>
        <button type="button" class="btn btn-primary" onclick="abrirModalCliente()">
          <i class="bi bi-person-search me-1"></i>Selecionar / Cadastrar
        </button>
        <?php endif; ?>
      </div>
      <div class="card-body" id="clienteResumo" style="min-height:120px">
        <?php if (!empty($os['cliente_nome'])): ?>
        <a href="<?= url('/clientes/' . (int)($os['cliente_id'] ?? 0) . '/editar') ?>" target="_blank"
           class="d-flex align-items-center gap-3 text-reset text-decoration-none p-2 rounded"
           title="Abrir edição deste cliente em nova aba"
           onmouseenter="this.style.background='#f1f5f9'" onmouseleave="this.style.background='transparent'"
           style="transition:background .15s">
          <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:48px;height:48px;font-size:1rem">
            <?= avatar_iniciais($os['cliente_nome']) ?>
          </div>
          <div>
            <div class="fw-semibold fs-6"><?= e($os['cliente_nome']) ?></div>
            <div class="text-muted small"><?= e($os['cliente_tel'] ?? '') ?></div>
          </div>
          <span class="ms-auto text-primary small fw-semibold text-nowrap"><i class="bi bi-pencil-square me-1"></i>Editar cliente</span>
        </a>
        <?php else: ?>
        <div class="text-center py-4 text-muted" id="clienteVazio">
          <i class="bi bi-person-plus fs-1 d-block mb-2 opacity-25"></i>
          <p class="mb-3">Clique no botão acima para buscar ou cadastrar um cliente</p>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="os-nav">
      <a href="<?= url('/os') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-x me-1"></i>Cancelar
      </a>
      <button type="button" class="btn btn-primary" onclick="avancarStep(0)">
        Próximo: Equipamento <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- â•â• ABA 2 -- EQUIPAMENTO â•â• -->
  <div class="os-tab-pane" id="step1">
    <div class="card shadow-sm" style="border:2px solid #C0C0C0!important">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-cpu me-2 text-primary"></i>Equipamento</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditarEquip"
                onclick="abrirModalEquipamento()"
                style="<?= empty($os['cliente_id']) ? 'display:none' : '' ?>">
          <i class="bi bi-pencil me-1"></i>Alterar
        </button>
      </div>
      <div class="card-body" id="equipamentoResumo" style="min-height:120px">
        <?php if (!empty($os['equip_tipo'])): ?>
        <?php renderEquipResumo($os); ?>
        <?php else: ?>
        <div class="text-center py-4 text-muted" id="equipVazio">
          <i class="bi bi-cpu fs-1 d-block mb-2 opacity-25"></i>
          <p class="mb-3">Selecione o cliente primeiro para cadastrar o equipamento</p>
          <button type="button" class="btn btn-outline-primary" onclick="abrirModalEquipamento()" id="btnAdicionarEquip" style="display:none">
            <i class="bi bi-plus-circle me-1"></i>Adicionar equipamento
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <!-- Fotos do estado de entrada (enviadas por e-mail, NÃO armazenadas) -->
    <div class="card shadow-sm mt-3" style="border:2px solid #C0C0C0!important">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-camera me-2 text-primary"></i>Fotos do estado de entrada</span>
        <span class="badge bg-secondary"><span id="fotosEntradaCount">0</span>/6</span>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-2">
          <i class="bi bi-shield-check me-1 text-success"></i>
          Registre arranhões, trincas, gabinete quebrado, etc. As fotos <strong>não ficam no sistema</strong> —
          são enviadas por e-mail para a <strong>empresa e o cliente</strong>, servindo como comprovação do estado de entrada.
        </p>
        <label for="inputFotosEntrada" class="btn btn-outline-primary">
          <i class="bi bi-camera me-1"></i> Adicionar foto
        </label>
        <input type="file" id="inputFotosEntrada" accept="image/*" multiple class="d-none"
               onchange="adicionarFotosEntrada(this)">
        <div id="prevFotosEntrada" class="d-flex flex-wrap gap-2 mt-3"></div>
      </div>
    </div>

    <div class="os-nav">
      <button type="button" class="btn btn-outline-secondary" onclick="irParaStep(0)">
        <i class="bi bi-arrow-left me-1"></i>Anterior
      </button>
      <button type="button" class="btn btn-primary" onclick="irParaStep(2)">
        Próximo: Defeito <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- â•â• ABA 3 -- DEFEITO â•â• -->
  <div class="os-tab-pane" id="step2">
    <div class="card shadow-sm" style="border:2px solid #C0C0C0!important mb-3">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-tools me-2 text-primary"></i>Defeito e Observações
      </div>
      <div class="card-body">
        <div class="mb-3">
          <label class="form-label fw-semibold">Defeito relatado pelo cliente *</label>
          <textarea name="defeito_relatado" class="form-control" rows="4"
            placeholder="O que o cliente disse que está errado com o equipamento..."
            required><?= e($os['defeito_relatado'] ?? '') ?></textarea>
        </div>
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Observações para o cliente</label>
            <textarea name="observacoes_cliente" class="form-control" rows="3"
              placeholder="Algo que o cliente precisa saber..."><?= e($os['observacoes_cliente'] ?? '') ?></textarea>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Observações internas</label>
            <textarea name="observacoes_internas" class="form-control" rows="3"
              placeholder="Anotações internas (não aparece para o cliente)..."><?= e($os['observacoes_internas'] ?? '') ?></textarea>
          </div>
        </div>

        <?php if ($editando): ?>
        <hr class="my-3">
        <div class="mb-3">
          <label class="form-label fw-semibold">Defeito constatado (técnico)</label>
          <textarea name="defeito_constatado" class="form-control" rows="2"
            placeholder="O que o técnico encontrou..."><?= e($os['defeito_constatado'] ?? '') ?></textarea>
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Laudo técnico</label>
          <textarea name="laudo_tecnico" class="form-control" rows="2"
            placeholder="Diagnóstico detalhado..."><?= e($os['laudo_tecnico'] ?? '') ?></textarea>
        </div>
        <div class="mb-0">
          <label class="form-label fw-semibold">Solução aplicada</label>
          <textarea name="solucao_aplicada" class="form-control" rows="2"
            placeholder="O que foi feito para resolver..."><?= e($os['solucao_aplicada'] ?? '') ?></textarea>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <div class="os-nav">
      <button type="button" class="btn btn-outline-secondary" onclick="irParaStep(1)">
        <i class="bi bi-arrow-left me-1"></i>Anterior
      </button>
      <button type="button" class="btn btn-primary" onclick="irParaStep(3)">
        Próximo: Configurações <i class="bi bi-arrow-right ms-1"></i>
      </button>
    </div>
  </div>

  <!-- â•â• ABA 4 -- CONFIGURACOES â•â• -->
  <div class="os-tab-pane" id="step3">
    <div class="card shadow-sm" style="border:2px solid #C0C0C0!important mb-3">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-gear me-2 text-primary"></i>Configurações da OS
      </div>
      <div class="card-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label fw-semibold">Tipo de serviço</label>
            <select name="tipo_servico" class="form-select">
              <?php foreach (['orcamento'=>'Orçamento','conserto'=>'Conserto','garantia'=>'Garantia','manutencao'=>'Manutenção','instalacao'=>'Instalação'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($os['tipo_servico']??'conserto')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Prioridade</label>
            <select name="prioridade" class="form-select">
              <?php foreach (['baixa'=>'Baixa','normal'=>'Normal','alta'=>'Alta','urgente'=>'Urgente'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($os['prioridade']??'normal')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Status</label>
            <select name="status_id" class="form-select">
              <?php foreach ($statusList as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($os['status_id']??$status_inicial['id']??0)==$s['id']?'selected':'' ?>><?= e($s['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Técnico responsável</label>
            <div class="input-group">
              <?php
                $idsTecnicos = array_column($tecnicos, 'id');
                $usuarioLogadoId = (int) ($_SESSION['usuario_id'] ?? 0);
                $defTec = ($os['tecnico_id'] ?? 0) ?: (in_array($usuarioLogadoId, $idsTecnicos, true) ? $usuarioLogadoId : 0);
              ?>
              <select name="tecnico_id" id="selTecnico" class="form-select">
                <option value="">Sem técnico</option>
                <?php foreach ($tecnicos as $t): ?>
                <option value="<?= $t['id'] ?>" <?= $defTec==$t['id']?'selected':'' ?>><?= e($t['nome']) ?></option>
                <?php endforeach; ?>
              </select>
              <button type="button" class="btn btn-outline-primary" onclick="abrirCrudTecnicos()" title="Adicionar / gerenciar técnicos"><i class="bi bi-person-gear"></i></button>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Previsão de entrega</label>
            <?php $previsaoPadrao = !empty($os['data_previsao']) ? $os['data_previsao'] : date('Y-m-d\TH:i', strtotime('+5 days')); ?>
            <input type="datetime-local" name="data_previsao" class="form-control" value="<?= e($previsaoPadrao) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Garantia</label>
            <div class="input-group">
              <input type="number" name="garantia_dias" class="form-control" value="<?= e($os['garantia_dias'] ?? 90) ?>">
              <span class="input-group-text">dias</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Resumo antes de salvar -->
    <div class="card shadow-sm" style="border:2px solid #C0C0C0!important mb-3" id="cardResumoFinal">
      <div class="card-header bg-white fw-semibold">
        <i class="bi bi-clipboard2-check me-2 text-success"></i>Resumo da OS
      </div>
      <div class="card-body">
        <div class="row g-2 small">
          <div class="col-6">
            <span class="text-muted">Cliente:</span>
            <span class="fw-semibold ms-1" id="resumoCliente">--</span>
          </div>
          <div class="col-6">
            <span class="text-muted">Equipamento:</span>
            <span class="fw-semibold ms-1" id="resumoEquip">--</span>
          </div>
        </div>
      </div>
    </div>

    <!-- Checklist de pré-requisitos -->
    <div id="checklistOS" class="card shadow-sm" style="border:2px solid #C0C0C0!important mb-3" style="display:none">
      <div class="card-header bg-white fw-semibold small">
        <i class="bi bi-clipboard2-check me-2"></i>Antes de abrir a OS, verifique:
      </div>
      <div class="card-body py-2 px-3" id="checklistBody"></div>
    </div>

    <!-- Alerta de validação (erros ao submeter) -->
    <div id="alertaValidacao" class="d-none mb-3"></div>

    <div class="os-nav">
      <button type="button" class="btn btn-outline-secondary" onclick="irParaStep(2)">
        <i class="bi bi-arrow-left me-1"></i>Anterior
      </button>
      <button type="submit" class="btn btn-primary btn-lg fw-semibold px-5" id="btnSalvarOS">
        <i class="bi bi-check-lg me-1"></i><?= $editando ? 'Salvar OS' : 'Abrir OS' ?>
      </button>
    </div>
  </div>

</form>

<?php function renderEquipResumo(array $os): void { ?>
<div class="d-flex align-items-start gap-3">
  <div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0">
    <i class="bi bi-cpu fs-4"></i>
  </div>
  <div>
    <div class="fw-semibold fs-6"><?= e(trim(($os['equip_marca']??$os['fEquipMarca']??'').' '.($os['equip_modelo']??$os['fEquipModelo']??''))) ?></div>
    <div class="text-muted"><?= e($os['equip_tipo']??$os['fEquipTipo']??'') ?></div>
    <?php $ns = $os['numero_serie'] ?? $os['fNumeroSerie'] ?? ''; if($ns): ?>
    <div class="small text-muted">S/N: <?= e($ns) ?></div>
    <?php endif; ?>
  </div>
</div>
<?php } ?>

<!-- â•â•â• MODAL CLIENTE â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal fade" id="modalCliente" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold">Cliente</h5>
          <p class="text-muted small mb-0">Busque um cliente existente ou cadastre um novo</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body pt-2">
        <ul class="nav nav-pills mb-3" id="tabsCliente">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabBuscar">
              <i class="bi bi-search me-1"></i>Buscar cliente
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabNovo">
              <i class="bi bi-person-plus me-1"></i>Cadastrar novo
            </button>
          </li>
        </ul>
        <div class="tab-content">
          <!-- TAB BUSCAR -->
          <div class="tab-pane fade show active" id="tabBuscar">
            <div class="input-group mb-3">
              <span class="input-group-text"><i class="bi bi-search"></i></span>
              <input type="text" id="campoBusca" class="form-control form-control-lg"
                placeholder="Digite nome, CPF ou telefone..." autocomplete="off">
              <span class="spinner-border spinner-border-sm text-primary m-auto me-2 d-none" id="spinnerBusca"></span>
            </div>
            <div id="resultadosBusca" style="max-height:320px;overflow-y:auto">
              <div class="text-center text-muted py-4" id="msgBusca">
                <i class="bi bi-person-lines-fill fs-2 d-block mb-2 opacity-30"></i>
                Digite para buscar clientes cadastrados
              </div>
            </div>
          </div>
          <!-- TAB NOVO CLIENTE -->
          <div class="tab-pane fade" id="tabNovo">
            <form id="formNovoCliente" novalidate>
              <div class="row g-3">
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">Tipo</label>
                  <select name="tipo" class="form-select" id="ncTipo">
                    <option value="pf">Pessoa Física</option>
                    <option value="pj">Pessoa Jurídica</option>
                  </select>
                </div>
                <div class="col-md-9">
                  <label class="form-label small fw-semibold">Nome completo / Razão social *</label>
                  <input type="text" name="nome" id="ncNome" class="form-control" required placeholder="Nome do cliente">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Pessoa de contato</label>
                  <input type="text" name="contato" id="ncContato" class="form-control" placeholder="Contato" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold" id="ncLblDoc">CPF</label>
                  <div class="input-group">
                    <input type="text" name="cpf_cnpj" id="ncCpf" class="form-control" placeholder="000.000.000-00">
                    <button type="button" class="btn btn-outline-primary" id="ncBtnCnpj" title="Buscar dados na Receita Federal pelo CNPJ" style="display:none">
                      <i class="bi bi-cloud-download me-1"></i>Receita
                    </button>
                  </div>
                  <div id="ncCnpjMsg" class="form-text"></div>
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">WhatsApp</label>
                  <input type="text" name="whatsapp" id="ncWhatsapp" class="form-control" placeholder="(00) 00000-0000">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Telefone</label>
                  <input type="text" name="telefone" id="ncTelefone" class="form-control" placeholder="(00) 00000-0000">
                </div>
                <div class="col-md-8">
                  <label class="form-label small fw-semibold">E-mail</label>
                  <input type="email" name="email" id="ncEmail" class="form-control" placeholder="email@exemplo.com">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Origem</label>
                  <select name="origem" id="ncOrigem" class="form-select">
                    <option value="balcao">Balcão</option>
                    <option value="telefone">Telefone</option>
                    <option value="whatsapp">WhatsApp</option>
                    <option value="indicacao">Indicação</option>
                    <option value="site">Site</option>
                    <option value="outro">Outro</option>
                  </select>
                </div>
                <div class="col-12">
                  <div class="d-flex align-items-center gap-2 my-1">
                    <div class="border-bottom flex-grow-1"></div>
                    <span class="text-muted small px-2 flex-shrink-0"><i class="bi bi-geo-alt me-1"></i>Endereço</span>
                    <div class="border-bottom flex-grow-1"></div>
                  </div>
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">CEP</label>
                  <div class="position-relative">
                    <input type="text" id="ncCep" class="form-control" placeholder="00000-000" maxlength="9">
                    <span class="spinner-border spinner-border-sm text-primary position-absolute d-none"
                          id="ncCepSpinner" style="right:10px;top:50%;transform:translateY(-50%)"></span>
                  </div>
                  <div id="ncCepMsg" class="form-text"></div>
                </div>
                <div class="col-md-7">
                  <label class="form-label small fw-semibold">Logradouro</label>
                  <input type="text" id="ncLogradouro" class="form-control" placeholder="Rua, Av, Travessa...">
                </div>
                <div class="col-md-2">
                  <label class="form-label small fw-semibold">Número</label>
                  <input type="text" id="ncNumero" class="form-control" placeholder="Nº">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Complemento</label>
                  <input type="text" id="ncComplemento" class="form-control" placeholder="Apto, Bloco...">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Bairro</label>
                  <input type="text" id="ncBairro" class="form-control">
                </div>
                <div class="col-md-3">
                  <label class="form-label small fw-semibold">Cidade</label>
                  <input type="text" id="ncCidade" class="form-control">
                </div>
                <div class="col-md-1">
                  <label class="form-label small fw-semibold">UF</label>
                  <input type="text" id="ncUf" class="form-control" maxlength="2">
                </div>
              </div>
              <div id="erroNovoCliente" class="alert alert-danger py-2 small mt-3 d-none"></div>
            </form>
          </div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarCliente" style="display:none">
          <span class="spinner-border spinner-border-sm me-1 d-none" id="spinnerSalvarCliente"></span>
          <i class="bi bi-check-lg me-1"></i>Cadastrar e continuar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- â•â•â• MODAL EQUIPAMENTO â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="modal fade" id="modalEquipamento" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold">Equipamento</h5>
          <p class="text-muted small mb-0">Cliente: <strong id="equipClienteNome">--</strong></p>
        </div>
        <button type="button" class="btn-close" id="btnFecharEquip"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-12">
            <button type="button" class="btn btn-primary w-100 d-flex align-items-center justify-content-center gap-2 fw-bold text-white"
                    onclick="abrirScannerCelular()" style="padding:.9rem">
              <i class="bi bi-phone-fill fs-5"></i> Preencher pela câmera do celular
            </button>
          </div>
          <div class="col-12">
            <button type="button" class="btn btn-success w-100 d-flex align-items-center justify-content-center gap-2 fw-bold text-white"
                    onclick="abrirScannerFotosWhatsapp()" style="padding:.9rem">
              <i class="bi bi-whatsapp fs-5"></i> Fotografar equipamento pelo celular e enviar por WhatsApp
            </button>
            <div class="form-text small mt-1">
              <i class="bi bi-shield-check text-success me-1"></i>As fotos não ficam no sistema — vão direto pro WhatsApp da empresa e do cliente.
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
              Tipo de equipamento *
              <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="abrirCrudTipos()">
                <i class="bi bi-plus-circle"></i> Adicionar
              </button>
            </label>
            <select id="eTipoSelect" class="form-select sel-ex" required onchange="verificarSenha();carregarAcessoriosPadraoParaTipo(this.value)">
              <option value="">Ex: TV de LED 32</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
              Marca
              <button type="button" class="btn btn-link btn-sm p-0 text-muted" onclick="abrirCrudMarcas()">
                <i class="bi bi-plus-circle"></i> Adicionar
              </button>
            </label>
            <select id="eMarcaSelect" class="form-select sel-ex">
              <option value="">Ex: Samsung</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Modelo</label>
            <input type="text" id="eModelo" class="form-control" placeholder="Digite o modelo do equipamento">
          </div>
          <div class="col-md-3" id="campoCor" style="display:none">
            <label class="form-label small fw-semibold">Cor</label>
            <select id="eCor" class="form-select">
              <option value="Cor neutra" selected>Cor neutra</option>
              <?php foreach([
                'Preto','Branco','Cinza','Prata','Dourado','Rose Gold',
                'Azul','Vermelho','Verde','Amarelo','Roxo','Laranja',
                'Rosa','Bege','Marrom','Transparente','Outra'
              ] as $cor): ?>
              <option value="<?= $cor ?>"><?= $cor ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Número de série</label>
            <input type="text" id="eNumeroSerie" class="form-control" placeholder="Nº de série">
          </div>
          <div class="col-md-8" id="campoImei" style="display:none">
            <label class="form-label small fw-semibold">IMEI (celulares/tablets)</label>
            <div class="input-group">
              <input type="text" id="eImei" class="form-control" placeholder="15 dígitos" maxlength="17">
              <button type="button" class="btn btn-outline-primary" id="btnBuscarImei" onclick="buscarPorImei()" title="Preencher marca/modelo e checar bloqueio"><i class="bi bi-search"></i></button>
              <button type="button" class="btn" id="btnAnatel" onclick="anatelImei()" style="background:#009640;border:none;color:#fff" title="Valida os 15 dígitos, copia o IMEI e abre a consulta oficial da Anatel"><i class="bi bi-shield-check me-1"></i>Consulta Anatel</button>
            </div>
            <div id="imeiResultado" class="small mt-1"></div>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Voltagem</label>
            <select id="eVoltagem" class="form-select">
              <option value="">--</option>
              <option value="110v">110V</option>
              <option value="220v">220V</option>
              <option value="bivolt" selected>Bivolt</option>
              <option value="bateria">Bateria</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Estado de entrada</label>
            <select id="eEstado" class="form-select">
              <option value="otimo">Ótimo</option>
              <option value="bom">Bom</option>
              <option value="regular" selected>Regular</option>
              <option value="ruim">Ruim</option>
              <option value="danificado">Danificado</option>
            </select>
          </div>
          <div class="col-md-4" id="campoSenha" style="display:none">
            <label class="form-label small fw-semibold">Senha de desbloqueio</label>
            <input type="text" id="eSenha" class="form-control" placeholder="PIN, padrão, biometria...">
          </div>
          <!-- ACESSÓRIOS -->
          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label small fw-semibold mb-0">Acessórios que acompanham</label>
              <span class="badge bg-primary" id="badgeQtdSel">0 selecionados</span>
            </div>
            <div class="row g-2">
              <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="small text-muted fw-semibold">Disponíveis</span>
                  <span class="small text-muted">Arraste ou clique</span>
                </div>
                <div id="bancoDrop" class="border rounded p-2 bg-light"
                     style="min-height:130px;display:flex;flex-wrap:wrap;gap:6px;align-content:flex-start"
                     ondragover="event.preventDefault()" ondrop="dropParaBanco(event)"></div>
                <div class="input-group input-group-sm mt-2">
                  <input type="text" id="inputNovoAcessorio" class="form-control"
                    placeholder="Novo acessório..." maxlength="60"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();adicionarAoBanco()}">
                  <button type="button" class="btn btn-outline-primary" onclick="adicionarAoBanco()">
                    <i class="bi bi-plus-lg"></i>
                  </button>
                </div>
              </div>
              <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="small text-muted fw-semibold">Selecionados</span>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="limparSelecionados()">
                    <i class="bi bi-x-lg"></i> Limpar
                  </button>
                </div>
                <div id="selecionadosDrop" class="border border-primary rounded p-2"
                     style="min-height:130px;display:flex;flex-wrap:wrap;gap:6px;align-content:flex-start;background:rgba(13,110,253,.04)"
                     ondragover="event.preventDefault()" ondrop="dropParaSelecionados(event)">
                  <div id="msgSelecionadosVazio" class="text-muted small w-100 text-center pt-3 opacity-50">
                    <i class="bi bi-box-arrow-in-right d-block fs-4 mb-1"></i>Arraste itens aqui ou clique neles
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div id="erroEquipamento" class="alert alert-danger py-2 small mt-3 d-none"></div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" id="btnVoltarCliente">
          <i class="bi bi-arrow-left"></i> Voltar ao cliente
        </button>
        <button type="button" class="btn btn-primary" id="btnConfirmarEquipamento">
          <i class="bi bi-check-lg"></i> Confirmar equipamento
        </button>
      </div>
    </div>
  </div>
</div>

<!-- â•â•â• OFFCANVAS MARCAS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasMarcas" style="width:340px">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold mb-0"><i class="bi bi-tag me-2 text-primary"></i>Marcas</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="p-3 border-bottom bg-light">
      <input type="hidden" id="editMarcaId">
      <div class="input-group">
        <input type="text" id="editMarcaNome" class="form-control" placeholder="Nome da marca..." maxlength="60"
          onkeydown="if(event.key==='Enter'){event.preventDefault();salvarMarca()}">
        <button type="button" class="btn btn-primary" onclick="salvarMarca()"><i class="bi bi-check-lg"></i></button>
      </div>
      <button type="button" class="btn btn-link btn-sm p-0 text-muted mt-1 d-none" id="btnCancelarEditMarca" onclick="cancelarEditMarca()">
        <i class="bi bi-x me-1"></i>Cancelar
      </button>
    </div>
    <div id="listaMarcasContainer" style="overflow-y:auto;max-height:calc(100vh - 180px)"></div>
  </div>
</div>

<!-- â•â•â• OFFCANVAS TIPOS â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTipos" style="width:340px">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold mb-0"><i class="bi bi-cpu me-2 text-primary"></i>Tipos de Equipamento</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="p-3 border-bottom bg-light">
      <input type="hidden" id="editTipoId">
      <div class="input-group">
        <input type="text" id="editTipoNome" class="form-control" placeholder="Nome do tipo..." maxlength="60"
          onkeydown="if(event.key==='Enter'){event.preventDefault();salvarTipo()}">
        <button type="button" class="btn btn-primary" onclick="salvarTipo()" id="btnSalvarTipo"><i class="bi bi-check-lg"></i></button>
      </div>
      <button type="button" class="btn btn-link btn-sm p-0 text-muted mt-1 d-none" id="btnCancelarEditTipo" onclick="cancelarEditTipo()">
        <i class="bi bi-x me-1"></i>Cancelar
      </button>
    </div>
    <div id="listaTiposContainer" style="overflow-y:auto;max-height:calc(100vh - 180px)"></div>
  </div>
</div>

<!-- ═══ OFFCANVAS TÉCNICOS ═══ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTecnicos" style="width:360px">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>Técnicos</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="p-3 border-bottom bg-light">
      <input type="hidden" id="editTecId">
      <input type="text" id="editTecNome" class="form-control form-control-sm mb-2" placeholder="Nome do técnico *" maxlength="100"
        onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('editTecTel').focus()}">
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
        <input type="text" id="editTecTel" class="form-control" placeholder="Telefone (opcional)" maxlength="20"
          onkeydown="if(event.key==='Enter'){event.preventDefault();salvarTecnico()}">
        <button type="button" class="btn btn-primary" onclick="salvarTecnico()"><i class="bi bi-check-lg"></i></button>
      </div>
      <div id="tecMsg" class="form-text"></div>
      <button type="button" class="btn btn-link btn-sm p-0 text-muted mt-1 d-none" id="btnCancelarEditTec" onclick="cancelarEditTecnico()"><i class="bi bi-x me-1"></i>Cancelar edição</button>
    </div>
    <div id="listaTecnicosContainer" style="overflow-y:auto;max-height:calc(100vh - 220px)"></div>
  </div>
</div>

<script>
const CSRF        = '<?= csrf_token() ?>';
const API_CL      = '<?= url('/api/clientes') ?>';
const API_AUX     = '<?= url('/api/produto') ?>';

let modalCliente, modalEquip;
let clienteSelecionado = null;
let fClienteId, fEquipamentoId;
let stepAtual = 0;

// â”€â”€ Wizard de steps â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function irParaStep(n) {
  document.querySelectorAll('.os-tab-pane').forEach((el, i) => {
    el.classList.toggle('active', i === n);
  });
  document.querySelectorAll('.os-step').forEach((el, i) => {
    el.classList.remove('active', 'done');
    if (i === n) el.classList.add('active');
    if (i < n)  el.classList.add('done');
  });
  stepAtual = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });

  // Atualizar resumo e checklist na última aba
  if (n === 3) { atualizarResumoFinal(); setTimeout(atualizarChecklist, 100); }
}

function avancarStep(atual) {
  if (atual === 0 && !document.getElementById('fClienteId').value) {
    abrirModalCliente();
    return;
  }
  irParaStep(atual + 1);
}

function atualizarChecklist() {
  const checks = [
    {
      ok:    !!document.getElementById('fClienteId')?.value,
      label: 'Cliente selecionado',
      step:  0,
      acao:  () => setTimeout(abrirModalCliente, 300),
    },
    {
      ok:    !!document.getElementById('fEquipTipo')?.value,
      label: 'Equipamento informado',
      step:  1,
      acao:  () => setTimeout(abrirModalEquipamento, 300),
    },
    {
      ok:    !!(document.querySelector('textarea[name="defeito_relatado"]')?.value.trim()),
      label: 'Defeito relatado preenchido',
      step:  2,
      acao:  () => setTimeout(() => document.querySelector('textarea[name="defeito_relatado"]')?.focus(), 300),
    },
  ];

  const todoOk = checks.every(c => c.ok);
  const card   = document.getElementById('checklistOS');
  const body   = document.getElementById('checklistBody');

  if (todoOk) {
    card.style.display = 'none';
    return;
  }

  card.style.display = 'block';
  body.innerHTML = checks.map((c, i) => `
    <div class="d-flex align-items-center gap-2 py-1" style="font-size:.88rem">
      ${c.ok
        ? `<i class="bi bi-check-circle-fill text-success fs-5"></i>
           <span class="text-success fw-semibold">${c.label}</span>`
        : `<i class="bi bi-x-circle-fill text-danger fs-5"></i>
           <span class="text-danger fw-semibold">${c.label}</span>
           <a href="#" class="ms-auto btn btn-sm btn-outline-danger py-0 px-2"
              onclick="event.preventDefault();irParaStep(${c.step});checkAcoes[${i}]()">
             <i class="bi bi-arrow-right me-1"></i>Preencher
           </a>`
      }
    </div>`).join('');

  window.checkAcoes = checks.map(c => c.acao);
}

function atualizarResumoFinal() {
  const nomeCliente = clienteSelecionado?.nome || '--';
  const tipoEquip   = document.getElementById('fEquipTipo')?.value || '';
  const marcaEquip  = document.getElementById('fEquipMarca')?.value || '';
  const modeloEquip = document.getElementById('fEquipModelo')?.value || '';
  const equip = [marcaEquip, modeloEquip, tipoEquip].filter(Boolean).join(' ') || '--';
  document.getElementById('resumoCliente').textContent = nomeCliente;
  document.getElementById('resumoEquip').textContent   = equip;
}

// â”€â”€ Modais de cliente e equipamento â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function abrirModalCliente() {
  if (!modalCliente) return;
  document.getElementById('campoBusca').value = '';
  document.getElementById('resultadosBusca').innerHTML = `
    <div class="text-center text-muted py-4">
      <i class="bi bi-person-lines-fill fs-2 d-block mb-2 opacity-30"></i>
      Digite para buscar clientes cadastrados
    </div>`;
  modalCliente.show();
  setTimeout(() => document.getElementById('campoBusca').focus(), 400);
}

// Define o valor de um <select>; se a opção não existir (ex: tipo/marca customizado), cria.
function setSelectValue(id, val) {
  const sel = document.getElementById(id);
  if (!sel || !val) return;
  if (![...sel.options].some(o => o.value === val)) {
    const opt = document.createElement('option');
    opt.value = val; opt.textContent = val;
    sel.appendChild(opt);
  }
  sel.value = val;
}

function abrirModalEquipamento() {
  if (!modalEquip) return;
  document.getElementById('equipClienteNome').textContent =
    clienteSelecionado?.nome || document.getElementById('fClienteNome')?.value || '--';
  // Pré-preencher com o equipamento atual (edição) a partir dos campos hidden
  const v = id => (document.getElementById(id)?.value || '');
  setSelectValue('eTipoSelect', v('fEquipTipo'));
  setSelectValue('eMarcaSelect', v('fEquipMarca'));
  document.getElementById('eModelo').value      = v('fEquipModelo');
  document.getElementById('eNumeroSerie').value = v('fNumeroSerie');
  const _eImei = document.getElementById('eImei'); if (_eImei) _eImei.value = v('fImei');
  setSelectValue('eCor', v('fEquipCor'));
  setSelectValue('eVoltagem', v('fVoltagem'));
  setSelectValue('eEstado', v('fEstadoEntrada'));
  document.getElementById('eSenha').value       = v('fSenha');
  if (typeof verificarSenha === 'function') verificarSenha();
  modalEquip.show();
}

// â”€â”€ CRUD Marcas (banco de dados) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let marcasDados = [];
let offcanvasMarcas;

async function abrirCrudMarcas() {
  await carregarMarcasDB();
  renderListaMarcas();
  offcanvasMarcas.show();
  setTimeout(() => document.getElementById('editMarcaNome').focus(), 400);
}

async function carregarMarcasDB() {
  const r = await fetch(`${API_AUX}/equip_marcas`);
  marcasDados = await r.json();
  // Sincronizar select
  const sel = document.getElementById('eMarcaSelect');
  const val = sel.value;
  sel.innerHTML = '<option value="">Ex: Samsung</option>';
  marcasDados.forEach(m => {
    const opt = document.createElement('option');
    opt.value = m.nome; opt.textContent = m.nome;
    if (m.nome === val) opt.selected = true;
    sel.appendChild(opt);
  });
}

function renderListaMarcas() {
  const cont = document.getElementById('listaMarcasContainer');
  if (!marcasDados.length) { cont.innerHTML = '<div class="text-center text-muted py-4 small">Nenhuma marca cadastrada.<br>Adicione acima.</div>'; return; }
  cont.innerHTML = '';
  marcasDados.forEach(m => {
    const li = document.createElement('div');
    li.className = 'd-flex align-items-center px-3 py-2 border-bottom gap-2';
    li.onmouseenter = () => li.style.background = '#f8f9fa';
    li.onmouseleave = () => li.style.background = '';
    const nome = document.createElement('span'); nome.className = 'flex-grow-1 small'; nome.textContent = m.nome;
    const acoes = document.createElement('div'); acoes.className = 'd-flex gap-1';
    const btnUsar = document.createElement('button'); btnUsar.type='button'; btnUsar.className='btn btn-outline-success btn-sm py-0 px-2'; btnUsar.innerHTML='<i class="bi bi-check-lg"></i>';
    btnUsar.onclick = () => { document.getElementById('eMarcaSelect').value = m.nome; offcanvasMarcas.hide(); };
    const btnEdit = document.createElement('button'); btnEdit.type='button'; btnEdit.className='btn btn-outline-secondary btn-sm py-0 px-2'; btnEdit.innerHTML='<i class="bi bi-pencil"></i>';
    btnEdit.onclick = () => { document.getElementById('editMarcaId').value=m.id; document.getElementById('editMarcaNome').value=m.nome; document.getElementById('editMarcaNome').focus(); document.getElementById('btnCancelarEditMarca').classList.remove('d-none'); };
    const btnDel = document.createElement('button'); btnDel.type='button'; btnDel.className='btn btn-outline-danger btn-sm py-0 px-2'; btnDel.innerHTML='<i class="bi bi-trash3"></i>';
    btnDel.onclick = async () => {
      if (!confirm('Excluir esta marca?')) return;
      const r = await fetch(`${API_AUX}/equip_marcas/${m.id}`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF}, body:JSON.stringify({_method:'DELETE',csrf_token:CSRF}) });
      const j = await r.json();
      marcasDados = j.lista ?? [];
      renderListaMarcas();
      await carregarMarcasDB();
    };
    acoes.append(btnUsar,btnEdit,btnDel); li.append(nome,acoes); cont.appendChild(li);
  });
}

async function salvarMarca() {
  const nome = document.getElementById('editMarcaNome').value.trim();
  const id   = document.getElementById('editMarcaId').value;
  if (!nome) { document.getElementById('editMarcaNome').classList.add('is-invalid'); return; }
  document.getElementById('editMarcaNome').classList.remove('is-invalid');
  const r = await fetch(`${API_AUX}/equip_marcas`, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF}, body:JSON.stringify({id:id||undefined,nome,csrf_token:CSRF}) });
  const j = await r.json();
  marcasDados = j.lista ?? marcasDados;
  cancelarEditMarca();
  renderListaMarcas();
  await carregarMarcasDB();
}
function cancelarEditMarca(){document.getElementById('editMarcaId').value='';document.getElementById('editMarcaNome').value='';document.getElementById('btnCancelarEditMarca').classList.add('d-none');}
function getMarca(){return document.getElementById('eMarcaSelect').value;}

// ── Consulta por IMEI: preenche marca/modelo + status de bloqueio ──
function imeiLuhnOk(n){
  let s=0,alt=false;
  for(let i=n.length-1;i>=0;i--){ let d=+n[i]; if(alt){ d*=2; if(d>9)d-=9; } s+=d; alt=!alt; }
  return s%10===0;
}
function copiarTexto(t){
  try{ navigator.clipboard.writeText(t); return; }catch(e){}
  try{ const x=document.createElement('textarea'); x.value=t; document.body.appendChild(x); x.select(); document.execCommand('copy'); x.remove(); }catch(_){}
}
function anatelImei(){
  const imei=(document.getElementById('eImei').value||'').replace(/\D/g,'');
  const out=document.getElementById('imeiResultado');
  const err=m=>{ if(out) out.innerHTML='<span class="text-danger"><i class="bi bi-exclamation-triangle-fill"></i> '+m+'</span>'; };
  if(imei.length===0){ err('Digite o IMEI (15 dígitos) antes de consultar a Anatel.'); return; }
  if(imei.length<15){ err('Faltam dígitos — o IMEI tem 15 e você digitou '+imei.length+'. Confira (disque *#06#).'); return; }
  if(imei.length>15){ err('Dígitos a mais — o IMEI tem 15 e você digitou '+imei.length+'. Remova o excedente.'); return; }
  if(!imeiLuhnOk(imei)){ err('O número não confere (dígito verificador). Reveja os 15 dígitos.'); return; }
  copiarTexto(imei);
  modalImeiCopiado(imei);
}
function fecharModalImei(){ const o=document.getElementById('imeiCopiadoOverlay'); if(o) o.remove(); }
function modalImeiCopiado(imei){
  fecharModalImei();
  const url='https://www.gov.br/anatel/pt-br/assuntos/celular-legal/consulte-sua-situacao';
  const ov=document.createElement('div');
  ov.id='imeiCopiadoOverlay';
  ov.style.cssText='position:fixed;inset:0;z-index:3000;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:1rem';
  ov.innerHTML='<div style="background:#fff;border-top:5px solid #16a34a;border-radius:14px;max-width:430px;width:100%;padding:1.6rem;text-align:center;box-shadow:0 20px 60px rgba(0,0,0,.4)">'
    +'<i class="bi bi-clipboard-check-fill" style="font-size:2.6rem;color:#16a34a"></i>'
    +'<h5 class="mt-2 mb-1" style="color:#15803d;font-weight:800">IMEI copiado!</h5>'
    +'<p class="text-muted small mb-3">Copiei <b>'+imei+'</b>. Na página da Anatel: <b>cole no campo (Ctrl+V)</b>, marque o "não sou robô" e clique Consultar.</p>'
    +'<a href="'+url+'" target="_blank" rel="noopener" class="btn btn-success w-100 fw-bold" onclick="fecharModalImei()"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir consulta da Anatel</a>'
    +'<button type="button" class="btn btn-link btn-sm w-100 mt-1 text-muted" onclick="fecharModalImei()">Fechar</button>'
    +'</div>';
  ov.addEventListener('click', e=>{ if(e.target===ov) fecharModalImei(); });
  document.body.appendChild(ov);
}
function imeiSetMarca(nome){
  if(!nome) return;
  const sel=document.getElementById('eMarcaSelect');
  let opt=Array.from(sel.options).find(o=>String(o.value).toLowerCase()===String(nome).toLowerCase());
  if(!opt){ opt=new Option(nome,nome); sel.add(opt); }
  sel.value=opt.value;
}
function buscarPorImei(){
  const imei=(document.getElementById('eImei').value||'').trim();
  const out=document.getElementById('imeiResultado');
  const btn=document.getElementById('btnBuscarImei');
  if(imei.replace(/\D/g,'').length<14){ out.innerHTML='<span class="text-danger">Digite os 15 dígitos do IMEI (disque *#06#).</span>'; return; }
  btn.disabled=true; out.innerHTML='<span class="text-muted"><span class="spinner-border spinner-border-sm"></span> Consultando…</span>';
  fetch('<?= url('/api/imei') ?>', {
    method:'POST',
    headers:{'Content-Type':'application/json','X-CSRF-Token':'<?= csrf_token() ?>'},
    body: JSON.stringify({imei: imei})
  }).then(r=>r.json()).then(j=>{
    if(j.ok){
      if(j.marca) imeiSetMarca(j.marca);
      if(j.modelo) document.getElementById('eModelo').value=j.modelo;
      let bl='';
      if(j.blacklist==='bloqueado') bl=' <span class="badge bg-danger"><i class="bi bi-exclamation-triangle-fill"></i> Bloqueado/roubo</span>';
      else if(j.blacklist==='limpo') bl=' <span class="badge bg-success"><i class="bi bi-check-circle-fill"></i> Sem bloqueio</span>';
      else if(j.blacklist==='desconhecido') bl=' <span class="badge bg-secondary">Status indefinido</span>';
      const aviso=j.aviso?' <span class="text-muted">'+j.aviso+'</span>':'';
      out.innerHTML='<span class="text-success"><i class="bi bi-check-circle-fill"></i> '+[j.marca,j.modelo].filter(Boolean).join(' ')+'</span>'+bl+aviso;
    } else {
      out.innerHTML='<span class="text-warning"><i class="bi bi-exclamation-circle"></i> '+(j.erro||'Não encontrado')+'</span>';
    }
  }).catch(()=>{ out.innerHTML='<span class="text-danger">Erro ao consultar.</span>'; })
    .finally(()=>{ btn.disabled=false; });
}

// â”€â”€ CRUD Tipos (banco de dados) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let tiposDados = [];
let offcanvasTipos;

async function abrirCrudTipos() {
  await carregarTiposDB();
  renderListaTipos();
  offcanvasTipos.show();
}

async function carregarTiposDB() {
  const r = await fetch(`${API_AUX}/equip_tipos`);
  tiposDados = await r.json();
  renderSelectTipo();
}

function verificarSenha(){
  const tipo=document.getElementById('eTipoSelect').value.toLowerCase();
  const campo=document.getElementById('campoSenha');
  if(!campo)return;
  const ehCelular=tipo.includes('celular')||tipo.includes('smartphone')||tipo.includes('tablet')||tipo.includes('iphone')||tipo.includes('ipad');
  campo.style.display=ehCelular?'':'none';
  if(!ehCelular)document.getElementById('eSenha').value='';
  // IMEI só faz sentido para celular/tablet — some nos demais tipos.
  const campoImei=document.getElementById('campoImei');
  if(campoImei){campoImei.style.display=ehCelular?'':'none';if(!ehCelular){const ei=document.getElementById('eImei');if(ei)ei.value='';}}
  // Cor só faz sentido para celular/tablet — some nos demais tipos.
  const campoCor=document.getElementById('campoCor');
  if(campoCor){campoCor.style.display=ehCelular?'':'none';if(!ehCelular){const ec=document.getElementById('eCor');if(ec)ec.value='Cor neutra';}}
  // Carregar acessórios padrão para este tipo
  carregarAcessoriosPadraoParaTipo(document.getElementById('eTipoSelect').value);
}

async function carregarAcessoriosPadraoParaTipo(tipo) {
  if (!tipo) return;
  try {
    const r = await fetch(`<?= url('/api/equip/acessorios-padrao/') ?>${encodeURIComponent(tipo)}`);
    const j = await r.json();
    if (j.ids && j.ids.length) {
      // Pré-selecionar acessórios que foram usados na última OS deste tipo
      j.ids.forEach(id => {
        const item = bancoDados.find(a => a.id == id);
        if (item && !selecionados.find(s => s.id == id)) {
          selecionados.push(item);
        }
      });
      renderSelecionados();
      sincronizarHidden();
    }
  } catch(e) {}
}

function renderSelectTipo(){
  const sel=document.getElementById('eTipoSelect'); const val=sel.value;
  sel.innerHTML='<option value="">Ex: TV de LED 32</option>';
  tiposDados.forEach(t=>{const opt=document.createElement('option');opt.value=t.nome;opt.textContent=t.nome;if(t.nome===val)opt.selected=true;sel.appendChild(opt);});
}

function renderListaTipos(){
  const cont=document.getElementById('listaTiposContainer'); cont.innerHTML='';
  if(!tiposDados.length){cont.innerHTML='<div class="text-center text-muted py-4 small">Nenhum tipo cadastrado.<br>Adicione acima.</div>';return;}
  tiposDados.forEach(t=>{
    const li=document.createElement('div'); li.className='d-flex align-items-center gap-2 px-3 py-2 border-bottom'; li.style.cssText='font-size:.85rem;transition:background .1s';
    li.onmouseenter=()=>li.style.background='#f8f9fa'; li.onmouseleave=()=>li.style.background='';
    const nome=document.createElement('span'); nome.className='flex-grow-1'; nome.textContent=t.nome;
    const acoes=document.createElement('div'); acoes.className='d-flex gap-1';
    const btnUsar=document.createElement('button'); btnUsar.type='button'; btnUsar.className='btn btn-outline-success btn-sm py-0 px-2'; btnUsar.innerHTML='<i class="bi bi-check-lg"></i>';
    btnUsar.onclick=()=>{document.getElementById('eTipoSelect').value=t.nome;offcanvasTipos.hide();verificarSenha();};
    const btnEdit=document.createElement('button'); btnEdit.type='button'; btnEdit.className='btn btn-outline-secondary btn-sm py-0 px-2'; btnEdit.innerHTML='<i class="bi bi-pencil"></i>';
    btnEdit.onclick=()=>prepararEditTipo(t);
    const btnDel=document.createElement('button'); btnDel.type='button'; btnDel.className='btn btn-outline-danger btn-sm py-0 px-2'; btnDel.innerHTML='<i class="bi bi-trash3"></i>';
    btnDel.onclick=async()=>{ if(!confirm('Excluir este tipo?'))return; const r=await fetch(`${API_AUX}/equip_tipos/${t.id}`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({_method:'DELETE',csrf_token:CSRF})}); const j=await r.json(); tiposDados=j.lista??[]; renderListaTipos(); renderSelectTipo(); };
    acoes.append(btnUsar,btnEdit,btnDel); li.append(nome,acoes); cont.appendChild(li);
  });
}

async function salvarTipo(){
  const nome=document.getElementById('editTipoNome').value.trim(); const id=document.getElementById('editTipoId').value;
  if(!nome){document.getElementById('editTipoNome').classList.add('is-invalid');document.getElementById('editTipoNome').focus();return;}
  document.getElementById('editTipoNome').classList.remove('is-invalid');
  const r=await fetch(`${API_AUX}/equip_tipos`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({id:id||undefined,nome,csrf_token:CSRF})});
  const j=await r.json(); tiposDados=j.lista??tiposDados;
  renderSelectTipo(); renderListaTipos(); cancelarEditTipo();
}
function prepararEditTipo(t){document.getElementById('editTipoId').value=t.id;document.getElementById('editTipoNome').value=t.nome;document.getElementById('editTipoNome').focus();document.getElementById('btnCancelarEditTipo').classList.remove('d-none');}
function cancelarEditTipo(){document.getElementById('editTipoId').value='';document.getElementById('editTipoNome').value='';document.getElementById('btnCancelarEditTipo').classList.add('d-none');}
function getTipo(){return document.getElementById('eTipoSelect').value;}

// â”€â”€ ACESSÓRIOS (banco de dados) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
let bancoDados=[],selecionados=[],dragItem=null,dragSource=null;

async function carregarBanco() {
  const r = await fetch(`${API_AUX}/equip_acessorios`);
  bancoDados = await r.json();
}

async function salvarBanco() {
  // Não faz nada -- acessórios são salvos individualmente via API
}
function renderBanco(){const el=document.getElementById('bancoDrop');el.innerHTML='';bancoDados.forEach(item=>{el.appendChild(criarChip(item,'banco'));});}
function renderSelecionados(){const el=document.getElementById('selecionadosDrop');const msg=document.getElementById('msgSelecionadosVazio');el.querySelectorAll('.chip-sel').forEach(c=>c.remove());selecionados.forEach(item=>{el.appendChild(criarChip(item,'sel'));});msg.style.display=selecionados.length?'none':'';document.getElementById('badgeQtdSel').textContent=selecionados.length+' selecionado'+(selecionados.length!==1?'s':'');}
function criarChip(item,origem){const div=document.createElement('div');div.className='chip-'+origem+' d-flex align-items-center gap-1 px-2 py-1 rounded-pill border fw-semibold';div.style.cssText=origem==='banco'?'background:#f8f9fa;font-size:.78rem;cursor:grab;user-select:none':'background:#0d6efd;color:#fff;font-size:.78rem;cursor:grab;user-select:none';div.setAttribute('draggable','true');div.dataset.id=item.id;div.dataset.nome=item.nome;div.dataset.origem=origem;const grip=document.createElement('i');grip.className='bi bi-grip-vertical opacity-50';div.appendChild(grip);const span=document.createElement('span');span.textContent=item.nome;if(origem==='banco'){span.title='Duplo clique para editar';span.addEventListener('dblclick',()=>editarAcessorio(item.id,span,div));}div.appendChild(span);const btn=document.createElement('button');btn.type='button';btn.style.cssText='border:none;background:none;padding:0;line-height:1;'+(origem==='banco'?'color:#dc3545':'color:#fff');if(origem==='banco'){btn.innerHTML='<i class="bi bi-trash3" style="font-size:.7rem"></i>';btn.title='Remover do banco';btn.addEventListener('click',(e)=>{e.stopPropagation();removerDoBanco(item.id);});}else{btn.innerHTML='<i class="bi bi-x-lg" style="font-size:.7rem"></i>';btn.title='Remover da seleção';btn.addEventListener('click',(e)=>{e.stopPropagation();removerDosSelecionados(item.id);});}if(!(origem==='banco'&&ehSemAcessorios(item.nome)))div.appendChild(btn);div.addEventListener('click',(e)=>{if(e.target.closest('button')||e.target.closest('input'))return;if(origem==='banco')moverParaSel(item);else removerDosSelecionados(item.id);});div.addEventListener('dragstart',(e)=>{dragItem=item;dragSource=origem;div.style.opacity='.4';e.dataTransfer.effectAllowed='move';});div.addEventListener('dragend',()=>{div.style.opacity='1';});return div;}
function ehSemAcessorios(nome){return String(nome||'').trim().toLowerCase()==='sem acessórios';}
function moverParaSel(item){
  if(selecionados.find(s=>s.id===item.id))return;
  if(ehSemAcessorios(item.nome)){
    selecionados=[item];                 // "sem acessórios" é exclusivo: zera os outros
  }else{
    selecionados=selecionados.filter(s=>!ehSemAcessorios(s.nome)); // acessório real remove o "sem acessórios"
    selecionados.push(item);
  }
  renderSelecionados();sincronizarHidden();
}
function removerDosSelecionados(id){selecionados=selecionados.filter(s=>s.id!==id);renderSelecionados();sincronizarHidden();}
async function removerDoBanco(id){
  const it=bancoDados.find(a=>a.id===id);
  if(it&&ehSemAcessorios(it.nome)){alert('A etiqueta "sem acessórios" não pode ser excluída.');return;}
  if(!confirm('Remover este acessório do banco?'))return;
  const r=await fetch(`${API_AUX}/equip_acessorios/${id}`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({_method:'DELETE',csrf_token:CSRF})});
  const j=await r.json();
  if(j.lista) bancoDados=j.lista;
  selecionados=selecionados.filter(s=>s.id!==id);
  renderBanco();renderSelecionados();sincronizarHidden();
}
function dropParaSelecionados(e){e.preventDefault();document.getElementById('selecionadosDrop').classList.remove('drag-over');if(!dragItem)return;if(dragSource==='banco')moverParaSel(dragItem);dragItem=null;}
function dropParaBanco(e){e.preventDefault();document.getElementById('bancoDrop').classList.remove('drag-over');if(!dragItem)return;if(dragSource==='sel')removerDosSelecionados(dragItem.id);dragItem=null;}
document.addEventListener('dragover',function(e){const banco=document.getElementById('bancoDrop');const sel=document.getElementById('selecionadosDrop');if(banco&&sel&&dragItem){banco.classList.toggle('drag-over',banco.contains(e.target)||e.target===banco);sel.classList.toggle('drag-over',sel.contains(e.target)||e.target===sel);}});
function limparSelecionados(){selecionados=[];renderSelecionados();sincronizarHidden();}
async function adicionarAoBanco(){
  const input=document.getElementById('inputNovoAcessorio'); const nome=input.value.trim();
  if(!nome){input.focus();return;}
  if(bancoDados.find(a=>a.nome.toLowerCase()===nome.toLowerCase())){input.classList.add('is-invalid');setTimeout(()=>input.classList.remove('is-invalid'),1500);return;}
  const r=await fetch(`${API_AUX}/equip_acessorios`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({nome,csrf_token:CSRF})});
  const j=await r.json();
  if(j.lista) bancoDados=j.lista;
  renderBanco(); input.value=''; input.focus();
  const item=bancoDados.find(a=>a.nome===nome);
  if(item) setTimeout(()=>moverParaSel(item),50);
}
function editarAcessorio(id,span,chip){const it=bancoDados.find(a=>a.id===id);if(it&&ehSemAcessorios(it.nome))return;const atual=span.textContent;const input=document.createElement('input');input.type='text';input.value=atual;input.className='form-control form-control-sm';input.style.cssText='width:100px;font-size:.75rem;padding:1px 4px';span.replaceWith(input);input.focus();input.select();const salvar=()=>{const novo=input.value.trim()||atual;const item=bancoDados.find(a=>a.id===id);if(item)item.nome=novo;const selItem=selecionados.find(s=>s.id===id);if(selItem)selItem.nome=novo;salvarBanco();renderBanco();renderSelecionados();sincronizarHidden();};input.addEventListener('blur',salvar);input.addEventListener('keydown',e=>{if(e.key==='Enter')input.blur();if(e.key==='Escape'){input.value=atual;input.blur();}});}
function sincronizarHidden(){document.getElementById('fAcessorios').value=selecionados.map(s=>s.nome).join(', ');}
async function inicializarAcessorios(){
  await carregarBanco();
  // Pré-carrega os acessórios já salvos (edição/reabertura) a partir do hidden fAcessorios
  const salvos=(document.getElementById('fAcessorios').value||'').split(',').map(s=>s.trim()).filter(Boolean);
  selecionados=salvos.map((nome,i)=>{
    const item=bancoDados.find(a=>String(a.nome).toLowerCase()===nome.toLowerCase());
    return item?{id:item.id,nome:item.nome}:{id:'sav'+i,nome};
  });
  renderBanco();renderSelecionados();
}

// â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function escJs(s){return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");}
function iniciais(nome){const p=String(nome||'U').trim().split(' ');return((p[0]||'')[0]||'').toUpperCase()+((p.length>1?(p[p.length-1]||''):'')[0]||'').toUpperCase();}

// â”€â”€ Init (após Bootstrap carregar) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
window.addEventListener('load', function() {
  modalCliente = new bootstrap.Modal(document.getElementById('modalCliente'), { backdrop: 'static' });
  modalEquip   = new bootstrap.Modal(document.getElementById('modalEquipamento'), { backdrop: 'static' });
  offcanvasTipos  = new bootstrap.Offcanvas(document.getElementById('offcanvasTipos'));
  offcanvasMarcas = new bootstrap.Offcanvas(document.getElementById('offcanvasMarcas'));

  // Carregar tipos e marcas do banco
  carregarTiposDB();
  carregarMarcasDB();

  document.getElementById('modalEquipamento').addEventListener('shown.bs.modal', async function() {
    await inicializarAcessorios();
    // Só sugere acessórios-padrão do tipo quando NÃO há acessórios salvos (evita poluir a edição)
    const tipoAtual = document.getElementById('eTipoSelect').value;
    if (tipoAtual && selecionados.length === 0) carregarAcessoriosPadraoParaTipo(tipoAtual);
  });

  fClienteId     = document.getElementById('fClienteId');
  fEquipamentoId = document.getElementById('fEquipamentoId');

  // Busca AJAX
  let timerBusca;
  document.getElementById('campoBusca').addEventListener('input', function() {
    clearTimeout(timerBusca);
    const q = this.value.trim();
    if (q.length < 2) { document.getElementById('resultadosBusca').innerHTML='<div class="text-center text-muted py-4"><i class="bi bi-person-lines-fill fs-2 d-block mb-2 opacity-30"></i>Digite para buscar clientes cadastrados</div>'; return; }
    document.getElementById('spinnerBusca').classList.remove('d-none');
    timerBusca = setTimeout(async () => {
      const r=await fetch(`${API_CL}?q=${encodeURIComponent(q)}`);
      const list=await r.json();
      document.getElementById('spinnerBusca').classList.add('d-none');
      renderResultados(list, q);
    }, 300);
  });

  // Tabs cliente
  document.querySelectorAll('[data-bs-target="#tabNovo"]').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('btnSalvarCliente').style.display = '');
  });
  document.querySelectorAll('[data-bs-target="#tabBuscar"]').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('btnSalvarCliente').style.display = 'none');
  });

  // Salvar novo cliente
  document.getElementById('btnSalvarCliente').addEventListener('click', async function() {
    const nome = document.getElementById('ncNome').value.trim();
    if (!nome) { document.getElementById('ncNome').classList.add('is-invalid'); document.getElementById('ncNome').focus(); return; }
    document.getElementById('ncNome').classList.remove('is-invalid');
    if (window.docValido && !window.docValido(document.getElementById('ncCpf').value)) {
      const err = document.getElementById('erroNovoCliente');
      err.textContent = 'CPF/CNPJ inválido — confira os dígitos.'; err.classList.remove('d-none');
      document.getElementById('ncCpf').classList.add('is-invalid'); document.getElementById('ncCpf').focus();
      return;
    }
    const spinner = document.getElementById('spinnerSalvarCliente');
    spinner.classList.remove('d-none'); this.disabled=true;
    const body={_token:CSRF,tipo:document.getElementById('ncTipo').value,nome,cpf_cnpj:document.getElementById('ncCpf').value,telefone:document.getElementById('ncTelefone').value,whatsapp:document.getElementById('ncWhatsapp').value,contato:document.getElementById('ncContato').value,email:document.getElementById('ncEmail').value,origem:document.getElementById('ncOrigem').value,cep:document.getElementById('ncCep').value,logradouro:document.getElementById('ncLogradouro').value,numero:document.getElementById('ncNumero').value,complemento:document.getElementById('ncComplemento').value,bairro:document.getElementById('ncBairro').value,cidade:document.getElementById('ncCidade').value,uf:document.getElementById('ncUf').value};
    try {
      const r=await fetch(API_CL,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify(body)});
      const data=await r.json();
      if(data.error){document.getElementById('erroNovoCliente').textContent=data.error;document.getElementById('erroNovoCliente').classList.remove('d-none');}
      else{const c=data.cliente;clienteSelecionado={id:c.id,nome:c.nome,tel:c.telefone||'',doc:c.cpf_cnpj||''};confirmarClienteEAbrirEquip();}
    } catch(err) {
      document.getElementById('erroNovoCliente').textContent='Erro de conexão. Tente novamente.';
      document.getElementById('erroNovoCliente').classList.remove('d-none');
    }
    spinner.classList.add('d-none'); this.disabled=false;
  });

  // Confirmar equipamento
  document.getElementById('btnConfirmarEquipamento').addEventListener('click', function() {
    const tipo=getTipo(); const err=document.getElementById('erroEquipamento');
    if(!tipo){err.textContent='Selecione ou informe o tipo do equipamento.';err.classList.remove('d-none');document.getElementById('eTipoSelect').focus();return;}
    err.classList.add('d-none');
    const marca=getMarca();
    document.getElementById('fCategoriaId').value='';
    document.getElementById('fEquipTipo').value=tipo;
    document.getElementById('fEquipMarca').value=marca;
    document.getElementById('fEquipModelo').value=document.getElementById('eModelo').value;
    document.getElementById('fNumeroSerie').value=document.getElementById('eNumeroSerie').value;
    document.getElementById('fImei').value=document.getElementById('eImei').value;
    document.getElementById('fEquipCor').value=document.getElementById('eCor').value;
    document.getElementById('fVoltagem').value=document.getElementById('eVoltagem').value;
    document.getElementById('fEstadoEntrada').value=document.getElementById('eEstado').value;
    document.getElementById('fSenha').value=document.getElementById('eSenha').value;
    const modelo=document.getElementById('eModelo').value;
    const ns=document.getElementById('eNumeroSerie').value;
    document.getElementById('equipamentoResumo').innerHTML=`<div class="d-flex align-items-start gap-3"><div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0"><i class="bi bi-cpu fs-4"></i></div><div><div class="fw-semibold fs-6">${esc(marca)} ${esc(modelo)}</div><div class="text-muted">${esc(tipo)}</div>${ns?`<div class="small text-muted">S/N: ${esc(ns)}</div>`:''}</div></div>`;
    document.getElementById('btnAdicionarEquip') && (document.getElementById('btnAdicionarEquip').style.display='none');

    // Salvar acessórios selecionados como padrão para este tipo
    if (tipo && selecionados.length) {
      fetch('<?= url('/api/equip/acessorios-padrao') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({
          equip_tipo: tipo,
          acessorios_ids: selecionados.map(s => s.id),
          csrf_token: CSRF
        })
      });
    }

    modalEquip.hide();
    setTimeout(()=>irParaStep(2),300);
  });

  // Voltar / fechar equip
  document.getElementById('btnVoltarCliente').addEventListener('click',function(){modalEquip.hide();setTimeout(()=>modalCliente.show(),300);});
  document.getElementById('btnFecharEquip').addEventListener('click',function(){modalEquip.hide();});

  // Validação submit
  document.getElementById('formOS').addEventListener('submit', function(e) {
    const erros = [];

    // 1. Cliente obrigatório
    if (!fClienteId.value) {
      erros.push({ msg: 'Nenhum cliente selecionado.', step: 0, acao: () => setTimeout(abrirModalCliente, 300) });
    }

    // 2. Equipamento obrigatório
    if (!document.getElementById('fEquipTipo').value) {
      erros.push({ msg: 'Tipo de equipamento não informado.', step: 1, acao: () => setTimeout(abrirModalEquipamento, 300) });
    }

    // 3. Defeito relatado obrigatório
    const defeito = document.querySelector('textarea[name="defeito_relatado"]')?.value.trim();
    if (!defeito) {
      erros.push({ msg: 'Informe o defeito relatado pelo cliente.', step: 2, acao: () => setTimeout(() => document.querySelector('textarea[name="defeito_relatado"]').focus(), 300) });
    }

    if (erros.length) {
      e.preventDefault();

      const alerta = document.getElementById('alertaValidacao');
      alerta.className = 'alert alert-danger mb-3';
      alerta.innerHTML = `
        <div class="fw-semibold mb-2"><i class="bi bi-exclamation-triangle-fill me-2"></i>Corrija os campos abaixo para continuar:</div>
        <ul class="mb-0 ps-3">
          ${erros.map((err, i) => `
            <li>
              ${err.msg}
              <a href="#" class="ms-2 fw-semibold text-danger small"
                 onclick="event.preventDefault();irParaStep(${err.step});erroAcoes[${i}]()">
                <i class="bi bi-arrow-right me-1"></i>Corrigir
              </a>
            </li>`).join('')}
        </ul>`;
      alerta.scrollIntoView({ behavior: 'smooth', block: 'center' });

      // Guardar ações para os links inline
      window.erroAcoes = erros.map(e => e.acao);
      return;
    }

    // Tudo ok
    const btn = document.getElementById('btnSalvarOS');
    document.getElementById('alertaValidacao').className = 'd-none';
    try { localStorage.removeItem('fixaos_os_rascunho'); } catch(_e) {}

    // Se há fotos do estado de entrada, envia por e-mail ANTES de salvar (nada é armazenado)
    if (fotosEntrada.length > 0) {
      e.preventDefault();
      btn.disabled = true;
      btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando fotos...';
      enviarFotosEntrada().finally(() => {
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
        document.getElementById('formOS').submit(); // submit nativo — não redispara o handler
      });
      return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
  });

  // ── Fotos do estado de entrada (comprimidas no aparelho; e-mail; sem storage) ──
  let fotosEntrada = [];

  function comprimirFoto(file) {
    return new Promise(resolve => {
      const reader = new FileReader();
      reader.onload = e => {
        const img = new Image();
        img.onload = () => {
          const max = 1280;
          let w = img.width, h = img.height;
          if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
          else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
          const c = document.createElement('canvas');
          c.width = w; c.height = h;
          const ctx = c.getContext('2d');
          ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
          ctx.drawImage(img, 0, 0, w, h);
          resolve(c.toDataURL('image/jpeg', 0.55));
        };
        img.src = e.target.result;
      };
      reader.readAsDataURL(file);
    });
  }

  async function adicionarFotosEntrada(input) {
    const files = [...input.files];
    input.value = '';
    for (const f of files) {
      if (fotosEntrada.length >= 6) { alert('Máximo de 6 fotos.'); break; }
      if (!f.type.startsWith('image/')) continue;
      fotosEntrada.push(await comprimirFoto(f));
    }
    renderFotosEntrada();
  }

  function renderFotosEntrada() {
    const box = document.getElementById('prevFotosEntrada');
    box.innerHTML = fotosEntrada.map((d, i) => `
      <div style="position:relative">
        <img src="${d}" style="width:82px;height:82px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6">
        <button type="button" onclick="removerFotoEntrada(${i})"
          style="position:absolute;top:-7px;right:-7px;background:#dc3545;color:#fff;border:none;border-radius:50%;width:22px;height:22px;line-height:20px;font-size:14px;padding:0">&times;</button>
      </div>`).join('');
    document.getElementById('fotosEntradaCount').textContent = fotosEntrada.length;
  }

  function removerFotoEntrada(i) { fotosEntrada.splice(i, 1); renderFotosEntrada(); }

  async function enviarFotosEntrada() {
    try {
      const equip = (document.getElementById('fEquipMarca').value + ' ' + document.getElementById('fEquipModelo').value).trim()
                    || document.getElementById('fEquipTipo').value;
      const r = await fetch('<?= url('/os/fotos-entrada') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
        body: JSON.stringify({
          cliente_id: fClienteId.value,
          equipamento: equip,
          numero: '<?= e($os['numero'] ?? '') ?>',
          fotos: fotosEntrada,
          csrf_token: CSRF
        })
      });
      const j = await r.json();
      if (!j || !j.success) baixarFotosBackup(j && j.error);
    } catch (err) {
      baixarFotosBackup();
    }
  }

  function baixarFotosBackup(motivo) {
    fotosEntrada.forEach((d, i) => {
      const a = document.createElement('a');
      a.href = d; a.download = `estado-entrada-${i + 1}.jpg`;
      document.body.appendChild(a); a.click(); a.remove();
    });
    alert('Não foi possível enviar as fotos por e-mail' + (motivo ? ' (' + motivo + ')' : '') +
          '.\nAs fotos foram baixadas no seu aparelho como backup — guarde-as.');
  }

  // Máscaras
  if(typeof IMask!=='undefined'){
    IMask(document.getElementById('ncTelefone'),{mask:[{mask:'(00) 0000-0000'},{mask:'(00) 00000-0000'}]});
    IMask(document.getElementById('ncWhatsapp'),{mask:[{mask:'(00) 0000-0000'},{mask:'(00) 00000-0000'}]});
    IMask(document.getElementById('ncCpf'),{mask:[{mask:'000.000.000-00',maxLength:11},{mask:'00.000.000/0000-00'}]});
    IMask(document.getElementById('ncCep'),{mask:'00000-000'});
  }

  // Contato padrão = primeiro nome do cliente, quando vazio
  document.getElementById('ncNome').addEventListener('blur', function(){
    var c = document.getElementById('ncContato');
    if (c && !c.value.trim() && this.value.trim()) {
      var p = this.value.trim().split(/\s+/)[0];
      c.value = p.charAt(0).toUpperCase() + p.slice(1).toLowerCase();
    }
  });

  document.getElementById('ncTipo').addEventListener('change',function(){
    var pj=this.value==='pj';
    document.getElementById('ncLblDoc').textContent=pj?'CNPJ':'CPF';
    document.getElementById('ncCpf').placeholder=pj?'00.000.000/0000-00':'000.000.000-00';
    document.getElementById('ncBtnCnpj').style.display=pj?'':'none';
  });

  // Buscar dados da empresa na Receita Federal (CNPJ) — mesmo endpoint do cadastro avulso
  document.getElementById('ncBtnCnpj').addEventListener('click',async function(){
    var inp=document.getElementById('ncCpf'), msg=document.getElementById('ncCnpjMsg');
    var cnpj=(inp.value||'').replace(/\D/g,'');
    if(cnpj.length!==14){ msg.textContent='Informe um CNPJ com 14 dígitos.'; msg.className='form-text text-danger'; return; }
    var orig=this.innerHTML; this.disabled=true; this.innerHTML='<span class="spinner-border spinner-border-sm"></span>';
    msg.textContent='Consultando a Receita...'; msg.className='form-text text-muted';
    function put(id,val){ if(val==null||val==='')return; var el=document.getElementById(id); if(el) el.value=val; }
    try{
      var r=await fetch('<?= url('/api/cnpj/') ?>'+cnpj,{headers:{'Accept':'application/json'}});
      var d=await r.json();
      if(!d.success){ msg.textContent=d.error||'CNPJ não encontrado.'; msg.className='form-text text-danger'; }
      else{
        put('ncNome',d.razao_social); put('ncEmail',d.email); put('ncTelefone',d.telefone); put('ncWhatsapp',d.telefone);
        put('ncCep',d.cep); put('ncLogradouro',d.logradouro); put('ncNumero',d.numero); put('ncComplemento',d.complemento);
        put('ncBairro',d.bairro); put('ncCidade',d.cidade); put('ncUf',d.uf);
        msg.textContent='✓ '+d.razao_social+(d.situacao?' · '+d.situacao:''); msg.className='form-text text-success';
      }
    }catch(e){ msg.textContent='Erro de conexão. Tente novamente.'; msg.className='form-text text-danger'; }
    this.disabled=false; this.innerHTML=orig;
  });

  document.getElementById('ncCep').addEventListener('blur',async function(){
    const cep=this.value.replace(/\D/g,''); const spinner=document.getElementById('ncCepSpinner'); const msg=document.getElementById('ncCepMsg');
    if(cep.length!==8)return;
    spinner.classList.remove('d-none'); this.disabled=true; msg.textContent='';
    try{
      const r=await fetch(`https://viacep.com.br/ws/${cep}/json/`); const d=await r.json();
      if(d.erro){msg.textContent='âš  CEP não encontrado.';msg.className='form-text text-danger';}
      else{const campos={ncLogradouro:d.logradouro||'',ncBairro:d.bairro||'',ncCidade:d.localidade||'',ncUf:d.uf||'',ncComplemento:d.complemento||''};for(const[id,val]of Object.entries(campos)){const el=document.getElementById(id);if(el&&val){el.value=val;el.classList.add('border-success');setTimeout(()=>el.classList.remove('border-success'),2500);}}msg.textContent='✅ Endereço preenchido!';msg.className='form-text text-success';setTimeout(()=>msg.textContent='',3000);setTimeout(()=>document.getElementById('ncNumero').focus(),100);}
    }catch(e){msg.textContent='Erro ao buscar CEP.';msg.className='form-text text-danger';}
    spinner.classList.add('d-none'); this.disabled=false;
  });

  <?php if (!empty($os['cliente_id'])): ?>
  clienteSelecionado={id:<?= (int)$os['cliente_id'] ?>,nome:'<?= addslashes($os['cliente_nome']??'') ?>'};
  <?php endif; ?>
});

// â”€â”€ Render resultados busca â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function renderResultados(list,q){
  const box=document.getElementById('resultadosBusca');
  if(!list.length){box.innerHTML=`<div class="text-center text-muted py-4"><i class="bi bi-person-x fs-2 d-block mb-2 opacity-30"></i><div>Nenhum cliente encontrado para <strong>"${esc(q)}"</strong></div><button class="btn btn-outline-primary btn-sm mt-2" onclick="document.querySelector('[data-bs-target=&quot;#tabNovo&quot;]').click();document.getElementById('ncNome').value='${escJs(q)}'"><i class="bi bi-person-plus me-1"></i> Cadastrar "${esc(q)}" como novo cliente</button></div>`;return;}
  box.innerHTML=list.map(c=>`<div class="d-flex align-items-center gap-3 p-3 border-bottom" style="cursor:pointer" onmouseenter="this.classList.add('bg-light')" onmouseleave="this.classList.remove('bg-light')" onclick="selecionarCliente(${c.id},'${escJs(c.nome)}','${escJs(c.telefone||'')}','${escJs(c.cpf_cnpj||'')}')"><div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width:40px;height:40px;font-size:.8rem">${iniciais(c.nome)}</div><div class="flex-grow-1"><div class="fw-semibold">${esc(c.nome)}</div><div class="small text-muted">${[c.cpf_cnpj,c.telefone,c.email].filter(Boolean).map(esc).join(' · ')}</div></div><i class="bi bi-chevron-right text-muted"></i></div>`).join('');
}

function selecionarCliente(id,nome,tel,doc){clienteSelecionado={id,nome,tel,doc};confirmarClienteEAbrirEquip();}

function confirmarClienteEAbrirEquip(){
  fClienteId.value=clienteSelecionado.id;
  document.getElementById('clienteResumo').innerHTML=`<div class="d-flex align-items-center gap-3"><div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:48px;height:48px;font-size:1rem">${iniciais(clienteSelecionado.nome)}</div><div><div class="fw-semibold fs-6"><a href="<?= url('/clientes/') ?>${clienteSelecionado.id}/editar" target="_blank" class="text-reset text-decoration-none" title="Editar cliente">${esc(clienteSelecionado.nome)} <i class="bi bi-pencil-square small text-primary"></i></a></div><div class="text-muted small">${esc(clienteSelecionado.tel||'')} ${clienteSelecionado.doc?'· '+esc(clienteSelecionado.doc):''}</div></div><?= $editando ? '' : '<button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="abrirModalCliente()"><i class="bi bi-arrow-repeat"></i> Trocar</button>' ?></div>`;
  document.getElementById('badgeEtapa2')?.classList.replace('bg-secondary','bg-primary');
  document.getElementById('btnEditarEquip').style.display='';
  const btnAdd=document.getElementById('btnAdicionarEquip');
  if(btnAdd) btnAdd.style.display='';
  modalCliente.hide();
  document.getElementById('equipClienteNome').textContent=clienteSelecionado.nome;
  setTimeout(()=>{modalEquip.show();},300);
  // Avançar para aba equipamento
  irParaStep(1);
}
</script>

<style>#resultadosBusca .kb-sel{background:#e7f1ff}</style>
<script>
/* UX: navegar a busca de cliente pelo teclado (setas + Enter) */
(function(){
  var campo=document.getElementById('campoBusca'), box=document.getElementById('resultadosBusca');
  if(!campo||!box) return;
  var sel=-1;
  function itens(){ return box.querySelectorAll('div[onclick^="selecionarCliente"]'); }
  function pintar(){ var its=itens(); its.forEach(function(el,i){ el.classList.toggle('kb-sel', i===sel); }); if(sel>=0&&its[sel]) its[sel].scrollIntoView({block:'nearest'}); }
  new MutationObserver(function(){ sel = itens().length ? 0 : -1; pintar(); }).observe(box,{childList:true});
  campo.addEventListener('keydown', function(e){
    var its=itens(); if(!its.length) return;
    if(e.key==='ArrowDown'){ e.preventDefault(); sel=Math.min(sel+1,its.length-1); pintar(); }
    else if(e.key==='ArrowUp'){ e.preventDefault(); sel=Math.max(sel-1,0); pintar(); }
    else if(e.key==='Enter'){ e.preventDefault(); var alvo=its[sel<0?0:sel]; if(alvo) alvo.click(); }
  });
})();

/* UX: autosave do texto (rascunho) — só em OS nova */
(function(){
  var editando = <?= !empty($editando) ? 'true' : 'false' ?>;
  if(editando) return;
  var KEY='fixaos_os_rascunho', nomes=['defeito_relatado','observacoes_cliente','observacoes_internas'];
  function els(){ return nomes.map(function(n){ return document.querySelector('[name="'+n+'"]'); }).filter(Boolean); }
  var campos=els(); if(!campos.length) return;
  function salvar(){ var d={_t:Date.now()}; campos.forEach(function(el){ d[el.name]=el.value; }); try{ localStorage.setItem(KEY, JSON.stringify(d)); }catch(e){} }
  campos.forEach(function(el){ el.addEventListener('input', salvar); });
  try{
    var d=JSON.parse(localStorage.getItem(KEY)||'null');
    if(d && d._t && (Date.now()-d._t)<7*864e5){
      var temTexto=nomes.some(function(n){ return (d[n]||'').trim(); });
      var vazios=campos.every(function(el){ return !el.value.trim(); });
      if(temTexto && vazios){
        var b=document.createElement('div');
        b.className='alert alert-warning d-flex align-items-center gap-2 py-2 mb-3';
        b.innerHTML='<i class="bi bi-clock-history"></i><span class="small flex-grow-1">Você tem um <strong>rascunho de OS não salva</strong> — deseja restaurar o texto digitado?</span>';
        var ok=document.createElement('button'); ok.type='button'; ok.className='btn btn-sm btn-warning'; ok.innerHTML='<i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar';
        var no=document.createElement('button'); no.type='button'; no.className='btn btn-sm btn-outline-secondary'; no.textContent='Descartar';
        ok.onclick=function(){ campos.forEach(function(el){ if(d[el.name]!=null) el.value=d[el.name]; }); b.remove(); };
        no.onclick=function(){ try{localStorage.removeItem(KEY);}catch(e){} b.remove(); };
        b.appendChild(ok); b.appendChild(no);
        var form=document.getElementById('formOS'); form.parentNode.insertBefore(b, form);
      }
    }
  }catch(e){}
})();
</script>

<script>
/* CRUD de técnicos inline (offcanvas no form de OS) */
(function(){
  var API_TEC='<?= url('/api/tecnicos') ?>', oc=null, dados=[];
  function esc(s){ return (s||'').replace(/[&<>"]/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c]; }); }
  async function carregar(){
    try{ var r=await fetch(API_TEC); var j=await r.json(); dados=j.tecnicos||[]; }catch(e){ dados=[]; }
    var sel=document.getElementById('selTecnico'); if(!sel) return;
    var val=sel.value || '<?= (int)($_SESSION['usuario_id'] ?? 0) ?>'; // nunca em branco: cai no operador logado
    sel.innerHTML='<option value="">Sem técnico</option>';
    dados.forEach(function(t){ var o=document.createElement('option'); o.value=t.id; o.textContent=t.nome; if(String(t.id)===String(val)) o.selected=true; sel.appendChild(o); });
  }
  function render(){
    var cont=document.getElementById('listaTecnicosContainer');
    if(!dados.length){ cont.innerHTML='<div class="text-center text-muted py-4 small">Nenhum técnico cadastrado.<br>Adicione acima.</div>'; return; }
    cont.innerHTML='';
    dados.forEach(function(t){
      var li=document.createElement('div'); li.className='d-flex align-items-center px-3 py-2 border-bottom gap-2';
      var info=document.createElement('div'); info.className='flex-grow-1';
      info.innerHTML='<div class="small fw-semibold">'+esc(t.nome)+'</div>'+(t.telefone?'<div class="text-muted" style="font-size:.72rem"><i class="bi bi-telephone me-1"></i>'+esc(t.telefone)+'</div>':'');
      var acoes=document.createElement('div'); acoes.className='d-flex gap-1 flex-shrink-0';
      var usar=document.createElement('button'); usar.type='button'; usar.className='btn btn-outline-success btn-sm py-0 px-2'; usar.title='Usar nesta OS'; usar.innerHTML='<i class="bi bi-check-lg"></i>';
      usar.onclick=function(){ document.getElementById('selTecnico').value=t.id; if(oc) oc.hide(); };
      var edit=document.createElement('button'); edit.type='button'; edit.className='btn btn-outline-secondary btn-sm py-0 px-2'; edit.innerHTML='<i class="bi bi-pencil"></i>';
      edit.onclick=function(){ document.getElementById('editTecId').value=t.id; document.getElementById('editTecNome').value=t.nome; document.getElementById('editTecTel').value=t.telefone||''; document.getElementById('editTecNome').focus(); document.getElementById('btnCancelarEditTec').classList.remove('d-none'); };
      var del=document.createElement('button'); del.type='button'; del.className='btn btn-outline-danger btn-sm py-0 px-2'; del.innerHTML='<i class="bi bi-trash3"></i>';
      del.onclick=async function(){ if(!confirm('Remover o técnico "'+t.nome+'"?')) return; try{ var r=await fetch(API_TEC+'/'+t.id+'/excluir',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({_token:CSRF})}); var j=await r.json(); if(j.error){ alert(j.error); return; } }catch(e){} await carregar(); render(); };
      acoes.appendChild(usar); acoes.appendChild(edit); acoes.appendChild(del);
      li.appendChild(info); li.appendChild(acoes); cont.appendChild(li);
    });
  }
  window.abrirCrudTecnicos=async function(){
    if(!oc) oc=new bootstrap.Offcanvas(document.getElementById('offcanvasTecnicos'));
    await carregar(); render(); oc.show();
    setTimeout(function(){ document.getElementById('editTecNome').focus(); },400);
  };
  window.salvarTecnico=async function(){
    var nome=document.getElementById('editTecNome').value.trim(), tel=document.getElementById('editTecTel').value.trim(), id=document.getElementById('editTecId').value, msg=document.getElementById('tecMsg');
    if(!nome){ msg.textContent='Informe o nome do técnico.'; msg.className='form-text text-danger'; return; }
    msg.textContent='';
    var url = id ? (API_TEC+'/'+id) : API_TEC;
    try{
      var r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({_token:CSRF,nome:nome,telefone:tel})});
      var j=await r.json();
      if(j.error){ msg.textContent=j.error; msg.className='form-text text-danger'; return; }
      var novoId=(!id && j.tecnico)?j.tecnico.id:id;
      cancelarEditTecnico();
      await carregar(); render();
      if(novoId){ document.getElementById('selTecnico').value=novoId; }
    }catch(e){ msg.textContent='Erro de conexão. Tente novamente.'; msg.className='form-text text-danger'; }
  };
  window.cancelarEditTecnico=function(){
    document.getElementById('editTecId').value='';
    document.getElementById('editTecNome').value='';
    document.getElementById('editTecTel').value='';
    document.getElementById('tecMsg').textContent='';
    document.getElementById('btnCancelarEditTec').classList.add('d-none');
  };
})();
</script>

<!-- ── Scanner: celular como câmera do PC ──────────────────── -->
<div class="modal fade" id="modalScanner" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0"><i class="bi bi-phone-fill me-1"></i>Preencher pelo celular</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center">
        <p class="small text-muted mb-2">Abra a câmera do celular (logado no FixaOS na mesma empresa) e escaneie:</p>
        <div id="scannerQrBox" class="d-flex justify-content-center align-items-center mb-2" style="min-height:186px">
          <div class="spinner-border text-secondary"></div>
        </div>
        <div class="small text-muted">ou acesse <strong><?= e(parse_url(url('/'), PHP_URL_HOST)) ?>/scan</strong> e digite:</div>
        <div id="scannerCodigo" class="fw-bold fs-4" style="letter-spacing:.2em">••••••</div>
        <div id="scannerStatus" class="mt-2 small"><span class="spinner-border spinner-border-sm text-primary"></span> Aguardando o celular…</div>
      </div>
    </div>
  </div>
</div>
<!-- Confirmacao dos dados lidos pela IA -->
<div class="modal fade" id="modalConfirmarScan" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0"><i class="bi bi-magic me-1"></i>Confira os dados lidos</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-3">A IA leu estes dados da etiqueta. Confira antes de continuar.</p>
        <div class="mb-3">
          <label class="form-label small fw-semibold mb-1">Modelo</label>
          <div id="confModeloView" class="fs-5 fw-semibold"></div>
          <input type="text" id="confModeloInput" class="form-control d-none">
        </div>
        <div class="mb-2">
          <label class="form-label small fw-semibold mb-1">Numero de serie</label>
          <div id="confSerieView" class="fs-5 fw-semibold"></div>
          <input type="text" id="confSerieInput" class="form-control d-none">
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" id="btnEditarConf" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-pencil"></i> Editar
        </button>
        <button type="button" id="btnCorretoConf" class="btn btn-success btn-sm">
          <i class="bi bi-check-lg"></i> Está correto
        </button>
      </div>
    </div>
  </div>
</div>
<script>
let _scanToken = null, _scanTimer = null, _scanModo = 'equipamento';
async function abrirScannerCelular(modo){
  _scanModo = modo || 'equipamento';
  const modalEl = document.getElementById('modalScanner');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  document.querySelector('#modalScanner .modal-title').innerHTML = _scanModo === 'fotos_whatsapp'
    ? '<i class="bi bi-whatsapp me-1"></i>Fotografar equipamento'
    : '<i class="bi bi-phone-fill me-1"></i>Preencher pelo celular';
  document.getElementById('scannerQrBox').innerHTML = '<div class="spinner-border text-secondary"></div>';
  document.getElementById('scannerCodigo').textContent = '••••••';
  document.getElementById('scannerStatus').innerHTML = '<span class="spinner-border spinner-border-sm text-primary"></span> Aguardando o celular…';
  modal.show();
  modalEl.addEventListener('hidden.bs.modal', pararScanner, {once:true});
  try{
    const body = new URLSearchParams({ modo: _scanModo });
    if (_scanModo === 'fotos_whatsapp') {
      body.set('cliente_id', fClienteId.value || '');
      const equip = (document.getElementById('fEquipMarca').value + ' ' + document.getElementById('fEquipModelo').value).trim()
                    || document.getElementById('fEquipTipo').value;
      body.set('equipamento', equip || '');
    }
    const r = await fetch('<?= url('/scanner/nova') ?>', {
      method:'POST',
      headers:{'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded'},
      body
    });
    const j = await r.json();
    _scanToken = j.token;
    document.getElementById('scannerQrBox').innerHTML = '<img src="'+j.qr+'" alt="QR Code" style="width:186px;height:186px">';
    document.getElementById('scannerCodigo').textContent = j.codigo;
    _scanTimer = setInterval(pollScanner, 2000);
  }catch(e){
    document.getElementById('scannerStatus').innerHTML = '<span class="text-danger">Erro ao gerar o QR. Feche e tente de novo.</span>';
  }
}
function abrirScannerFotosWhatsapp(){ abrirScannerCelular('fotos_whatsapp'); }
function pararScanner(){ if(_scanTimer){ clearInterval(_scanTimer); _scanTimer = null; } }
async function pollScanner(){
  if(!_scanToken) return;
  try{
    const r = await fetch('<?= url('/scanner/status') ?>?token=' + encodeURIComponent(_scanToken));
    if(!r.ok){
      if(r.status === 410){ document.getElementById('scannerStatus').innerHTML = '<span class="text-danger">A sessão expirou. Feche e abra de novo.</span>'; pararScanner(); }
      return;
    }
    const j = await r.json();
    if(j.status === 'pronto' && j.resultado){
      pararScanner();
      if(j.erro){
        document.getElementById('scannerStatus').innerHTML = '<span class="text-warning"><i class="bi bi-exclamation-triangle"></i> '+j.erro+'</span>';
        setTimeout(()=>{ bootstrap.Modal.getInstance(document.getElementById('modalScanner')).hide(); alert(j.erro); }, 1500);
      } else if (_scanModo === 'fotos_whatsapp') {
        document.getElementById('scannerStatus').innerHTML = '<span class="text-success fw-semibold">✅ Fotos enviadas pelo WhatsApp!</span>';
        setTimeout(()=>{ bootstrap.Modal.getInstance(document.getElementById('modalScanner')).hide(); }, 1200);
      } else {
        mostrarConfirmacaoScanner(j.resultado);
        document.getElementById('scannerStatus').innerHTML = '<span class="text-success fw-semibold">✅ Recebido! Preenchendo…</span>';
        setTimeout(()=>{ bootstrap.Modal.getInstance(document.getElementById('modalScanner')).hide(); }, 900);
      }
    }
  }catch(e){}
}
function preencherDoScanner(d){
  const flash = (el)=>{ if(!el) return; const o=el.style.transition; el.style.transition='background-color .3s'; el.style.backgroundColor='#d1fae5'; setTimeout(()=>{ el.style.backgroundColor=''; el.style.transition=o; }, 1200); };
  const setInp = (id,val)=>{ if(!val) return; const el=document.getElementById(id); if(el){ el.value=val; flash(el); } };
  // marca: usa/cria a opção; se vier vazia, avisa "SELECIONE MARCA"
  const setMarca = (val)=>{ const s=document.getElementById('eMarcaSelect'); if(!s) return;
    if(val){
      let opt=[...s.options].find(o=>o.value.toLowerCase()===val.toLowerCase() || o.textContent.trim().toLowerCase()===val.toLowerCase());
      if(!opt){ opt=document.createElement('option'); opt.value=val; opt.textContent=val; s.appendChild(opt); }
      s.value=opt.value; flash(s);
    } else {
      const ph=s.querySelector('option[value=""]'); if(ph){ ph.textContent='SELECIONE MARCA'; }
      s.value=''; flash(s);
    }
  };
  // tipo: usa a opção existente (case-insensitive); se não existir, cria; se vier vazio, avisa "SELECIONE O TIPO"
  const setTipo = (val)=>{ const s=document.getElementById('eTipoSelect'); if(!s) return;
    if(val){
      let opt=[...s.options].find(o=>o.textContent.trim().toLowerCase()===val.toLowerCase() || o.value.toLowerCase()===val.toLowerCase());
      if(!opt){ opt=document.createElement('option'); opt.value=val; opt.textContent=val; s.appendChild(opt); }
      s.value=opt.value; s.dispatchEvent(new Event('change')); flash(s);
    } else {
      const ph=s.querySelector('option[value=""]'); if(ph){ ph.textContent='SELECIONE O TIPO'; }
      s.value=''; flash(s);
    }
  };
  setTipo(d.tipo);
  setMarca(d.marca);
  setInp('eModelo', d.modelo);
  setInp('eNumeroSerie', d.serie);
}

let _scanDadosPendentes = null;
function mostrarConfirmacaoScanner(d){
  _scanDadosPendentes = d;
  const mView = document.getElementById('confModeloView');
  const sView = document.getElementById('confSerieView');
  const mInp  = document.getElementById('confModeloInput');
  const sInp  = document.getElementById('confSerieInput');
  mView.textContent = d.modelo || '(vazio)';
  sView.textContent = d.serie  || '(vazio)';
  mInp.value = d.modelo || '';
  sInp.value = d.serie  || '';
  mView.classList.remove('d-none'); sView.classList.remove('d-none');
  mInp.classList.add('d-none');     sInp.classList.add('d-none');
  document.getElementById('btnEditarConf').classList.remove('d-none');
  document.getElementById('btnCorretoConf').innerHTML = '<i class="bi bi-check-lg"></i> Está correto';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarScan')).show();
}
document.getElementById('btnEditarConf').addEventListener('click', function(){
  document.getElementById('confModeloView').classList.add('d-none');
  document.getElementById('confSerieView').classList.add('d-none');
  document.getElementById('confModeloInput').classList.remove('d-none');
  document.getElementById('confSerieInput').classList.remove('d-none');
  this.classList.add('d-none');
  document.getElementById('btnCorretoConf').innerHTML = '<i class="bi bi-check-lg"></i> Salvar e confirmar';
  document.getElementById('confModeloInput').focus();
});
document.getElementById('btnCorretoConf').addEventListener('click', function(){
  const editando = !document.getElementById('confModeloInput').classList.contains('d-none');
  const modelo = editando ? document.getElementById('confModeloInput').value.trim() : (_scanDadosPendentes.modelo || '');
  const serie  = editando ? document.getElementById('confSerieInput').value.trim()  : (_scanDadosPendentes.serie  || '');
  const dadosFinais = Object.assign({}, _scanDadosPendentes, { modelo, serie });
  bootstrap.Modal.getInstance(document.getElementById('modalConfirmarScan')).hide();
  preencherDoScanner(dadosFinais);
});
</script>
