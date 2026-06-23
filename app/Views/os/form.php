<?php $editando = !empty($os['id']); ?>
<?php $formAction = $editando ? url('/os/' . $os['id'] . '/editar') : url('/os'); ?>

<style>
#bancoDrop, #selecionadosDrop { transition: background .15s, border-color .15s; }
#bancoDrop.drag-over { background: #e8f4fd !important; border-color: #0d6efd !important; }
#selecionadosDrop.drag-over { background: #d0e8ff !important; border-color: #0a58ca !important; }
.chip-banco:hover { border-color: #0d6efd !important; background: #e8f0fe !important; }
.chip-sel:hover   { background: #0a58ca !important; }
</style>

<form method="POST" action="<?= $formAction ?>" id="formOS">
  <?= csrf_field() ?>

  <!-- Campos ocultos preenchidos pelos modais -->
  <input type="hidden" name="cliente_id"    id="fClienteId"    value="<?= e($os['cliente_id']    ?? '') ?>">
  <input type="hidden" name="equipamento_id" id="fEquipamentoId" value="<?= e($os['equipamento_id'] ?? '') ?>">
  <!-- Campos do equipamento (enviados junto ao salvar OS) -->
  <input type="hidden" name="categoria_id"          id="fCategoriaId">
  <input type="hidden" name="equip_tipo"             id="fEquipTipo">
  <input type="hidden" name="equip_marca"            id="fEquipMarca">
  <input type="hidden" name="equip_modelo"           id="fEquipModelo">
  <input type="hidden" name="numero_serie"           id="fNumeroSerie">
  <input type="hidden" name="imei"                   id="fImei">
  <input type="hidden" name="equip_cor"              id="fEquipCor">
  <input type="hidden" name="voltagem"               id="fVoltagem">
  <input type="hidden" name="estado_entrada"         id="fEstadoEntrada">
  <input type="hidden" name="defeito_relatado"       id="fDefeitoRelatado">
  <input type="hidden" name="acessorios"             id="fAcessorios">
  <input type="hidden" name="senha_desbloqueio"      id="fSenha">

  <div class="row g-3">
    <!-- COLUNA PRINCIPAL -->
    <div class="col-lg-8">

      <!-- ETAPA 1: Cliente -->
      <div class="card border-0 shadow-sm mb-3" id="cardCliente">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><span class="badge bg-primary me-2">1</span>Cliente</span>
          <?php if (!$editando): ?>
          <button type="button" class="btn btn-primary btn-sm" onclick="abrirModalCliente()">
            <i class="bi bi-person-search"></i> Selecionar / Cadastrar
          </button>
          <?php endif; ?>
        </div>
        <div class="card-body" id="clienteResumo">
          <?php if (!empty($os['cliente_nome'])): ?>
          <div class="d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:42px;height:42px">
              <?= avatar_iniciais($os['cliente_nome']) ?>
            </div>
            <div>
              <div class="fw-semibold"><?= e($os['cliente_nome']) ?></div>
              <div class="text-muted small"><?= e($os['cliente_tel'] ?? '') ?></div>
            </div>
          </div>
          <?php else: ?>
          <div class="text-center py-3 text-muted" id="clienteVazio">
            <i class="bi bi-person-plus fs-2 d-block mb-2 opacity-50"></i>
            Clique em <strong>Selecionar / Cadastrar</strong> para escolher o cliente
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ETAPA 2: Equipamento -->
      <div class="card border-0 shadow-sm mb-3" id="cardEquipamento">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <span class="fw-semibold"><span class="badge bg-secondary me-2" id="badgeEtapa2">2</span>Equipamento</span>
          <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditarEquip" onclick="abrirModalEquipamento()" style="<?= empty($os['cliente_id']) ? 'display:none' : '' ?>">
            <i class="bi bi-pencil"></i> Alterar
          </button>
        </div>
        <div class="card-body" id="equipamentoResumo">
          <?php if (!empty($os['equip_tipo'])): ?>
          <?php renderEquipResumo($os); ?>
          <?php else: ?>
          <div class="text-center py-3 text-muted" id="equipVazio">
            <i class="bi bi-cpu fs-2 d-block mb-2 opacity-50"></i>
            Selecione o cliente primeiro para cadastrar o equipamento
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ETAPA 3: Defeito e observações -->
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><span class="badge bg-secondary me-2">3</span>Defeito e Observações</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Defeito relatado pelo cliente *</label>
            <textarea name="defeito_relatado" class="form-control" rows="3" required><?= e($os['defeito_relatado'] ?? '') ?></textarea>
          </div>
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Observações para o cliente</label>
              <textarea name="observacoes_cliente" class="form-control" rows="2"><?= e($os['observacoes_cliente'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Observações internas</label>
              <textarea name="observacoes_internas" class="form-control" rows="2"><?= e($os['observacoes_internas'] ?? '') ?></textarea>
            </div>
          </div>

          <?php if ($editando): ?>
          <hr class="my-3">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Defeito constatado (técnico)</label>
            <textarea name="defeito_constatado" class="form-control" rows="2" placeholder="O que o técnico encontrou..."><?= e($os['defeito_constatado'] ?? '') ?></textarea>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Laudo técnico</label>
            <textarea name="laudo_tecnico" class="form-control" rows="2" placeholder="Diagnóstico detalhado..."><?= e($os['laudo_tecnico'] ?? '') ?></textarea>
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Solução aplicada</label>
            <textarea name="solucao_aplicada" class="form-control" rows="2" placeholder="O que foi feito para resolver..."><?= e($os['solucao_aplicada'] ?? '') ?></textarea>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div><!-- /col-lg-8 -->

    <!-- COLUNA LATERAL -->
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold">Configurações da OS</div>
        <div class="card-body">
          <div class="mb-3">
            <label class="form-label small fw-semibold">Tipo de serviço</label>
            <select name="tipo_servico" class="form-select">
              <?php foreach (['orcamento'=>'Orçamento','conserto'=>'Conserto','garantia'=>'Garantia','manutencao'=>'Manutenção','instalacao'=>'Instalação'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($os['tipo_servico']??'conserto')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Prioridade</label>
            <select name="prioridade" class="form-select">
              <?php foreach (['baixa'=>'Baixa','normal'=>'Normal','alta'=>'Alta','urgente'=>'Urgente'] as $v=>$l): ?>
              <option value="<?= $v ?>" <?= ($os['prioridade']??'normal')===$v?'selected':'' ?>><?= $l ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Status</label>
            <select name="status_id" class="form-select">
              <?php foreach ($statusList as $s): ?>
              <option value="<?= $s['id'] ?>" <?= ($os['status_id']??$status_inicial['id']??0)==$s['id']?'selected':'' ?>><?= e($s['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Técnico responsável</label>
            <select name="tecnico_id" class="form-select">
              <option value="">Sem técnico</option>
              <?php foreach ($tecnicos as $t): ?>
              <option value="<?= $t['id'] ?>" <?= ($os['tecnico_id']??0)==$t['id']?'selected':'' ?>><?= e($t['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Previsão de entrega</label>
            <?php $previsaoPadrao = !empty($os['data_previsao']) ? $os['data_previsao'] : date('Y-m-d\TH:i', strtotime('+5 days')); ?>
            <input type="datetime-local" name="data_previsao" class="form-control" value="<?= e($previsaoPadrao) ?>">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold">Garantia (dias)</label>
            <div class="input-group">
              <input type="number" name="garantia_dias" class="form-control" value="<?= e($os['garantia_dias'] ?? 90) ?>">
              <span class="input-group-text">dias</span>
            </div>
          </div>
        </div>
      </div>

      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary btn-lg" id="btnSalvarOS">
          <i class="bi bi-check-lg"></i> <?= $editando ? 'Salvar OS' : 'Abrir OS' ?>
        </button>
        <a href="<?= url('/os') ?>" class="btn btn-outline-secondary">Cancelar</a>
      </div>
    </div>
  </div>
</form>

<?php function renderEquipResumo(array $os): void { ?>
<div class="d-flex align-items-start gap-3">
  <div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
    <i class="bi bi-cpu fs-5"></i>
  </div>
  <div>
    <div class="fw-semibold"><?= e(trim(($os['equip_marca']??$os['fEquipMarca']??'').' '.($os['equip_modelo']??$os['fEquipModelo']??''))) ?></div>
    <div class="text-muted small"><?= e($os['equip_tipo']??$os['fEquipTipo']??'') ?></div>
    <?php $ns = $os['numero_serie'] ?? $os['fNumeroSerie'] ?? ''; if($ns): ?>
    <div class="small text-muted">S/N: <?= e($ns) ?></div>
    <?php endif; ?>
  </div>
</div>
<?php } ?>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL 1 — CLIENTE
═══════════════════════════════════════════════════════════════════ -->
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

        <!-- Tabs -->
        <ul class="nav nav-pills mb-3" id="tabsCliente">
          <li class="nav-item">
            <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tabBuscar">
              <i class="bi bi-search me-1"></i> Buscar cliente
            </button>
          </li>
          <li class="nav-item">
            <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tabNovo">
              <i class="bi bi-person-plus me-1"></i> Cadastrar novo
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
                  <label class="form-label small fw-semibold" id="ncLblDoc">CPF</label>
                  <input type="text" name="cpf_cnpj" id="ncCpf" class="form-control" placeholder="000.000.000-00">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">Telefone</label>
                  <input type="text" name="telefone" id="ncTelefone" class="form-control" placeholder="(00) 00000-0000">
                </div>
                <div class="col-md-4">
                  <label class="form-label small fw-semibold">WhatsApp</label>
                  <input type="text" name="whatsapp" id="ncWhatsapp" class="form-control" placeholder="(00) 00000-0000">
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

                <!-- Separador endereço -->
                <div class="col-12">
                  <div class="d-flex align-items-center gap-2 my-1">
                    <div class="border-bottom flex-grow-1"></div>
                    <span class="text-muted small px-2 flex-shrink-0"><i class="bi bi-geo-alt me-1"></i>Endereço</span>
                    <div class="border-bottom flex-grow-1"></div>
                  </div>
                </div>

                <!-- CEP com autopreenchimento -->
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
          <i class="bi bi-check-lg me-1"></i> Cadastrar e continuar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════════
     MODAL 2 — EQUIPAMENTO
═══════════════════════════════════════════════════════════════════ -->
<div class="modal fade" id="modalEquipamento" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold">Equipamento</h5>
          <p class="text-muted small mb-0">Cliente: <strong id="equipClienteNome">—</strong></p>
        </div>
        <button type="button" class="btn-close" id="btnFecharEquip"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
              Tipo de equipamento *
              <button type="button" class="btn btn-link btn-sm p-0 text-muted"
                onclick="abrirCrudTipos()" title="Gerenciar tipos">
                <i class="bi bi-gear-fill"></i> Gerenciar
              </button>
            </label>
            <select id="eTipoSelect" class="form-select" onchange="toggleTipoCustom(this.value)" required>
              <option value="">— Selecione o tipo —</option>
            </select>
            <input type="text" id="eTipoCustom" class="form-control mt-2 d-none"
              placeholder="Descreva o tipo de equipamento..." id="eTipo">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Marca</label>
            <select id="eMarcaSelect" class="form-select" onchange="toggleMarcaCustom(this.value)">
              <option value="">— Selecione —</option>
              <optgroup label="Celulares / Tablets">
                <option>Samsung</option>
                <option>Apple</option>
                <option>Motorola</option>
                <option>Xiaomi</option>
                <option>LG</option>
                <option>Nokia</option>
                <option>Sony</option>
                <option>Huawei</option>
                <option>ASUS</option>
                <option>Realme</option>
                <option>OnePlus</option>
              </optgroup>
              <optgroup label="Televisores">
                <option>Samsung</option>
                <option>LG</option>
                <option>Sony</option>
                <option>Philips</option>
                <option>Panasonic</option>
                <option>TCL</option>
                <option>Hisense</option>
                <option>AOC</option>
              </optgroup>
              <optgroup label="Notebooks / PCs">
                <option>Dell</option>
                <option>HP</option>
                <option>Lenovo</option>
                <option>ASUS</option>
                <option>Acer</option>
                <option>Apple</option>
                <option>Microsoft</option>
                <option>Samsung</option>
              </optgroup>
              <optgroup label="Eletrodomésticos">
                <option>Brastemp</option>
                <option>Consul</option>
                <option>Electrolux</option>
                <option>LG</option>
                <option>Samsung</option>
                <option>Panasonic</option>
                <option>Philco</option>
                <option>Midea</option>
                <option>Philips</option>
                <option>Oster</option>
                <option>Arno</option>
              </optgroup>
              <optgroup label="Impressoras / Periféricos">
                <option>HP</option>
                <option>Epson</option>
                <option>Canon</option>
                <option>Brother</option>
                <option>Lexmark</option>
              </optgroup>
              <optgroup label="Videogames">
                <option>Sony PlayStation</option>
                <option>Microsoft Xbox</option>
                <option>Nintendo</option>
                <option>Atari</option>
              </optgroup>
              <option value="__outra__">✏ Outra marca...</option>
            </select>
            <input type="text" id="eMarcaCustom" class="form-control mt-2 d-none"
              placeholder="Digite a marca" oninput="syncMarca(this.value)">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Modelo</label>
            <input type="text" id="eModelo" class="form-control" placeholder="Galaxy S22, 55UM7510, Brastemp Frost Free...">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Cor</label>
            <input type="text" id="eCor" class="form-control" placeholder="Preto, Branco...">
          </div>
          <div class="col-md-5">
            <label class="form-label small fw-semibold">Número de série</label>
            <input type="text" id="eNumeroSerie" class="form-control" placeholder="S/N impresso no aparelho">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold">IMEI (celulares)</label>
            <input type="text" id="eImei" class="form-control" placeholder="15 dígitos">
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold">Voltagem</label>
            <select id="eVoltagem" class="form-select">
              <option value="">—</option>
              <option value="110v">110V</option>
              <option value="220v">220V</option>
              <option value="bivolt">Bivolt</option>
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
          <div class="col-md-4">
            <label class="form-label small fw-semibold">Senha de desbloqueio</label>
            <input type="text" id="eSenha" class="form-control" placeholder="PIN, padrão, biometria...">
          </div>
          <!-- ACESSÓRIOS — Drag and Drop -->
          <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-2">
              <label class="form-label small fw-semibold mb-0">Acessórios que acompanham</label>
              <span class="badge bg-primary" id="badgeQtdSel">0 selecionados</span>
            </div>

            <div class="row g-2">
              <!-- BANCO DE ACESSÓRIOS -->
              <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="small text-muted fw-semibold">Disponíveis</span>
                  <span class="small text-muted">Arraste → ou clique</span>
                </div>
                <div id="bancoDrop"
                  class="border rounded p-2 bg-light"
                  style="min-height:130px;display:flex;flex-wrap:wrap;gap:6px;align-content:flex-start"
                  ondragover="event.preventDefault()" ondrop="dropParaBanco(event)">
                </div>

                <!-- Adicionar novo acessório -->
                <div class="input-group input-group-sm mt-2">
                  <input type="text" id="inputNovoAcessorio" class="form-control"
                    placeholder="Novo acessório..." maxlength="60"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();adicionarAoBanco()}">
                  <button type="button" class="btn btn-outline-primary" onclick="adicionarAoBanco()" title="Adicionar">
                    <i class="bi bi-plus-lg"></i>
                  </button>
                </div>
              </div>

              <!-- SELECIONADOS -->
              <div class="col-md-6">
                <div class="d-flex align-items-center justify-content-between mb-1">
                  <span class="small text-muted fw-semibold">Selecionados</span>
                  <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2" onclick="limparSelecionados()" title="Limpar tudo">
                    <i class="bi bi-x-lg"></i> Limpar
                  </button>
                </div>
                <div id="selecionadosDrop"
                  class="border border-primary rounded p-2"
                  style="min-height:130px;display:flex;flex-wrap:wrap;gap:6px;align-content:flex-start;background:rgba(13,110,253,.04)"
                  ondragover="event.preventDefault()" ondrop="dropParaSelecionados(event)">
                  <div id="msgSelecionadosVazio" class="text-muted small w-100 text-center pt-3 opacity-50">
                    <i class="bi bi-box-arrow-in-right d-block fs-4 mb-1"></i>
                    Arraste itens aqui ou clique neles
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

<!-- ═══════════════════════════════════════════════════════════════════
     OFFCANVAS — CRUD TIPOS DE EQUIPAMENTO
═══════════════════════════════════════════════════════════════════ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTipos" style="width:380px">
  <div class="offcanvas-header border-bottom">
    <div>
      <h5 class="offcanvas-title fw-bold mb-0">
        <i class="bi bi-cpu me-2 text-primary"></i>Tipos de Equipamento
      </h5>
      <p class="text-muted small mb-0">Gerencie os tipos disponíveis no select</p>
    </div>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>

  <div class="offcanvas-body p-0">
    <!-- Formulário de criação/edição -->
    <div class="p-3 border-bottom bg-light">
      <label class="form-label small fw-semibold" id="lblFormTipo">Novo tipo</label>
      <div class="input-group">
        <input type="hidden" id="editTipoId">
        <select id="editTipoGrupo" class="form-select" style="max-width:140px">
          <option value="Eletrônicos">Eletrônicos</option>
          <option value="Eletrodomésticos">Eletrodomésticos</option>
          <option value="Informática">Informática</option>
          <option value="Áudio e Vídeo">Áudio e Vídeo</option>
          <option value="Outros">Outros</option>
        </select>
        <input type="text" id="editTipoNome" class="form-control" placeholder="Nome do tipo..." maxlength="60"
          onkeydown="if(event.key==='Enter'){event.preventDefault();salvarTipo()}">
        <button type="button" class="btn btn-primary" onclick="salvarTipo()" id="btnSalvarTipo">
          <i class="bi bi-check-lg"></i>
        </button>
      </div>
      <button type="button" class="btn btn-link btn-sm p-0 text-muted mt-1 d-none" id="btnCancelarEditTipo" onclick="cancelarEditTipo()">
        <i class="bi bi-x me-1"></i>Cancelar edição
      </button>
    </div>

    <!-- Busca -->
    <div class="p-3 pb-2">
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-search"></i></span>
        <input type="text" id="buscaTipo" class="form-control" placeholder="Filtrar tipos..."
          oninput="filtrarTipos(this.value)">
      </div>
    </div>

    <!-- Lista agrupada -->
    <div id="listaTiposContainer" style="overflow-y:auto;max-height:calc(100vh - 280px)">
      <!-- renderizado por JS -->
    </div>
  </div>

  <div class="offcanvas-footer border-top p-3">
    <button type="button" class="btn btn-outline-danger btn-sm w-100"
      onclick="if(confirm('Restaurar todos os tipos padrão? Seus tipos personalizados serão mantidos.')) restaurarTiposPadrao()">
      <i class="bi bi-arrow-counterclockwise me-1"></i> Restaurar padrões
    </button>
  </div>
</div>

<script>
const CSRF   = '<?= csrf_token() ?>';
const API_CL = '<?= url('/api/clientes') ?>';

let modalCliente, modalEquip;
let clienteSelecionado = null;
let fClienteId, fEquipamentoId;

// ─── Abrir modal cliente (chamado pelo botão inline) ─────────────────
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

function abrirModalEquipamento() {
  if (!modalEquip) return;
  document.getElementById('equipClienteNome').textContent = clienteSelecionado?.nome || '—';
  modalEquip.show();
}

// ─── Busca AJAX ──────────────────────────────────────────────────────
// ═══════════════════════════════════════════════════════════════════
// TIPOS DE EQUIPAMENTO — Select + CRUD via Offcanvas
// ═══════════════════════════════════════════════════════════════════

const TIPOS_STORAGE = 'oficina_tipos_equip';

const TIPOS_PADRAO = [
  // Eletrônicos
  { id: 1,  nome: 'Celular / Smartphone',   grupo: 'Eletrônicos' },
  { id: 2,  nome: 'Tablet',                  grupo: 'Eletrônicos' },
  { id: 3,  nome: 'Smartwatch / Relógio',    grupo: 'Eletrônicos' },
  { id: 4,  nome: 'Fone de Ouvido',          grupo: 'Eletrônicos' },
  { id: 5,  nome: 'Caixa de Som',            grupo: 'Eletrônicos' },
  // Áudio e Vídeo
  { id: 10, nome: 'Televisor (TV)',           grupo: 'Áudio e Vídeo' },
  { id: 11, nome: 'Monitor',                  grupo: 'Áudio e Vídeo' },
  { id: 12, nome: 'Projetor',                 grupo: 'Áudio e Vídeo' },
  { id: 13, nome: 'Home Theater',             grupo: 'Áudio e Vídeo' },
  { id: 14, nome: 'Receiver / Amplificador',  grupo: 'Áudio e Vídeo' },
  { id: 15, nome: 'DVD / Blu-ray',            grupo: 'Áudio e Vídeo' },
  { id: 16, nome: 'Câmera Fotográfica',       grupo: 'Áudio e Vídeo' },
  { id: 17, nome: 'Câmera de Segurança',      grupo: 'Áudio e Vídeo' },
  { id: 18, nome: 'Drone',                    grupo: 'Áudio e Vídeo' },
  // Informática
  { id: 20, nome: 'Notebook / Laptop',        grupo: 'Informática' },
  { id: 21, nome: 'Desktop / PC',             grupo: 'Informática' },
  { id: 22, nome: 'All-in-One',               grupo: 'Informática' },
  { id: 23, nome: 'Impressora',               grupo: 'Informática' },
  { id: 24, nome: 'Roteador / Modem',         grupo: 'Informática' },
  { id: 25, nome: 'Switch / Hub',             grupo: 'Informática' },
  { id: 26, nome: 'No-break / UPS',           grupo: 'Informática' },
  { id: 27, nome: 'Videogame / Console',      grupo: 'Informática' },
  // Eletrodomésticos
  { id: 30, nome: 'Geladeira / Refrigerador', grupo: 'Eletrodomésticos' },
  { id: 31, nome: 'Freezer',                  grupo: 'Eletrodomésticos' },
  { id: 32, nome: 'Máquina de Lavar',         grupo: 'Eletrodomésticos' },
  { id: 33, nome: 'Secadora',                 grupo: 'Eletrodomésticos' },
  { id: 34, nome: 'Lava e Seca',              grupo: 'Eletrodomésticos' },
  { id: 35, nome: 'Lava-louças',              grupo: 'Eletrodomésticos' },
  { id: 36, nome: 'Micro-ondas',              grupo: 'Eletrodomésticos' },
  { id: 37, nome: 'Forno Elétrico',           grupo: 'Eletrodomésticos' },
  { id: 38, nome: 'Fogão',                    grupo: 'Eletrodomésticos' },
  { id: 39, nome: 'Coifa / Depurador',        grupo: 'Eletrodomésticos' },
  { id: 40, nome: 'Ar Condicionado',          grupo: 'Eletrodomésticos' },
  { id: 41, nome: 'Ventilador',               grupo: 'Eletrodomésticos' },
  { id: 42, nome: 'Purificador de Água',      grupo: 'Eletrodomésticos' },
  { id: 43, nome: 'Aspirador de Pó',          grupo: 'Eletrodomésticos' },
  { id: 44, nome: 'Ferro de Passar',          grupo: 'Eletrodomésticos' },
  { id: 45, nome: 'Batedeira / Liquidificador', grupo: 'Eletrodomésticos' },
  { id: 46, nome: 'Cafeteira',                grupo: 'Eletrodomésticos' },
  // Outros
  { id: 50, nome: 'Fritadeira Airfryer',      grupo: 'Outros' },
  { id: 51, nome: 'Máquina de Cartão',        grupo: 'Outros' },
  { id: 52, nome: 'Leitor de Código de Barras', grupo: 'Outros' },
];

let tiposDados   = [];
let proximoTipoId = 200;
let offcanvasTipos;

function carregarTipos() {
  try {
    const saved = localStorage.getItem(TIPOS_STORAGE);
    if (saved) {
      tiposDados = JSON.parse(saved);
    } else {
      tiposDados = JSON.parse(JSON.stringify(TIPOS_PADRAO));
      salvarTipos();
    }
  } catch(e) {
    tiposDados = JSON.parse(JSON.stringify(TIPOS_PADRAO));
  }
  proximoTipoId = Math.max(...tiposDados.map(t => t.id), 199) + 1;
}

function salvarTipos() {
  localStorage.setItem(TIPOS_STORAGE, JSON.stringify(tiposDados));
}

function renderSelectTipo() {
  const sel = document.getElementById('eTipoSelect');
  const val = sel.value;
  sel.innerHTML = '<option value="">— Selecione o tipo —</option>';

  const grupos = {};
  tiposDados.forEach(t => {
    if (!grupos[t.grupo]) grupos[t.grupo] = [];
    grupos[t.grupo].push(t);
  });

  Object.entries(grupos).forEach(([grupo, tipos]) => {
    const grp = document.createElement('optgroup');
    grp.label = grupo;
    tipos.forEach(t => {
      const opt = document.createElement('option');
      opt.value = t.nome;
      opt.textContent = t.nome;
      if (t.nome === val) opt.selected = true;
      grp.appendChild(opt);
    });
    sel.appendChild(grp);
  });

  const outro = document.createElement('option');
  outro.value = '__outro__';
  outro.textContent = '✏ Outro tipo...';
  sel.appendChild(outro);
}

function renderListaTipos(filtro = '') {
  const cont = document.getElementById('listaTiposContainer');
  cont.innerHTML = '';

  const grupos = {};
  tiposDados
    .filter(t => !filtro || t.nome.toLowerCase().includes(filtro.toLowerCase()) || t.grupo.toLowerCase().includes(filtro.toLowerCase()))
    .forEach(t => {
      if (!grupos[t.grupo]) grupos[t.grupo] = [];
      grupos[t.grupo].push(t);
    });

  if (!Object.keys(grupos).length) {
    cont.innerHTML = '<div class="text-center text-muted py-4 small">Nenhum tipo encontrado.</div>';
    return;
  }

  Object.entries(grupos).forEach(([grupo, tipos]) => {
    const header = document.createElement('div');
    header.className = 'px-3 py-1 bg-light border-bottom border-top';
    header.style.cssText = 'font-size:.72rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.06em';
    header.textContent = grupo;
    cont.appendChild(header);

    tipos.forEach(t => {
      const li = document.createElement('div');
      li.className = 'tipo-item d-flex align-items-center gap-2 px-3 py-2 border-bottom';
      li.style.cssText = 'font-size:.85rem;transition:background .1s';
      li.onmouseenter = () => li.style.background = '#f8f9fa';
      li.onmouseleave = () => li.style.background = '';

      const nome = document.createElement('span');
      nome.className = 'flex-grow-1';
      nome.textContent = t.nome;

      const acoes = document.createElement('div');
      acoes.className = 'd-flex gap-1';

      const btnUsar = document.createElement('button');
      btnUsar.type = 'button';
      btnUsar.className = 'btn btn-outline-success btn-sm py-0 px-2';
      btnUsar.innerHTML = '<i class="bi bi-check-lg"></i>';
      btnUsar.title = 'Usar este tipo';
      btnUsar.onclick = () => {
        document.getElementById('eTipoSelect').value = t.nome;
        document.getElementById('eTipoCustom').classList.add('d-none');
        offcanvasTipos.hide();
      };

      const btnEdit = document.createElement('button');
      btnEdit.type = 'button';
      btnEdit.className = 'btn btn-outline-secondary btn-sm py-0 px-2';
      btnEdit.innerHTML = '<i class="bi bi-pencil"></i>';
      btnEdit.title = 'Editar';
      btnEdit.onclick = () => prepararEditTipo(t);

      const btnDel = document.createElement('button');
      btnDel.type = 'button';
      btnDel.className = 'btn btn-outline-danger btn-sm py-0 px-2';
      btnDel.innerHTML = '<i class="bi bi-trash3"></i>';
      btnDel.title = 'Excluir';
      btnDel.onclick = () => excluirTipo(t.id);

      acoes.append(btnUsar, btnEdit, btnDel);
      li.append(nome, acoes);
      cont.appendChild(li);
    });
  });
}

function salvarTipo() {
  const nome  = document.getElementById('editTipoNome').value.trim();
  const grupo = document.getElementById('editTipoGrupo').value;
  const id    = document.getElementById('editTipoId').value;

  if (!nome) {
    document.getElementById('editTipoNome').classList.add('is-invalid');
    document.getElementById('editTipoNome').focus();
    return;
  }
  document.getElementById('editTipoNome').classList.remove('is-invalid');

  if (id) {
    const item = tiposDados.find(t => t.id == id);
    if (item) { item.nome = nome; item.grupo = grupo; }
  } else {
    tiposDados.push({ id: proximoTipoId++, nome, grupo });
  }

  salvarTipos();
  renderSelectTipo();
  renderListaTipos(document.getElementById('buscaTipo').value);
  cancelarEditTipo();
}

function prepararEditTipo(t) {
  document.getElementById('editTipoId').value    = t.id;
  document.getElementById('editTipoNome').value  = t.nome;
  document.getElementById('editTipoGrupo').value = t.grupo;
  document.getElementById('editTipoNome').focus();
  document.getElementById('lblFormTipo').textContent   = 'Editando tipo';
  document.getElementById('btnSalvarTipo').innerHTML   = '<i class="bi bi-check-lg"></i>';
  document.getElementById('btnCancelarEditTipo').classList.remove('d-none');
}

function cancelarEditTipo() {
  document.getElementById('editTipoId').value    = '';
  document.getElementById('editTipoNome').value  = '';
  document.getElementById('editTipoGrupo').value = 'Eletrônicos';
  document.getElementById('lblFormTipo').textContent  = 'Novo tipo';
  document.getElementById('btnCancelarEditTipo').classList.add('d-none');
}

function excluirTipo(id) {
  if (!confirm('Excluir este tipo de equipamento?')) return;
  tiposDados = tiposDados.filter(t => t.id !== id);
  salvarTipos();
  renderSelectTipo();
  renderListaTipos(document.getElementById('buscaTipo').value);
}

function filtrarTipos(q) {
  renderListaTipos(q);
}

function restaurarTiposPadrao() {
  const custom = tiposDados.filter(t => t.id >= 200);
  tiposDados = [...JSON.parse(JSON.stringify(TIPOS_PADRAO)), ...custom];
  salvarTipos();
  renderSelectTipo();
  renderListaTipos();
}

function abrirCrudTipos() {
  renderListaTipos();
  offcanvasTipos.show();
}

function toggleTipoCustom(val) {
  const custom = document.getElementById('eTipoCustom');
  if (val === '__outro__') {
    custom.classList.remove('d-none');
    custom.focus();
  } else {
    custom.classList.add('d-none');
    custom.value = '';
  }
}

function getTipo() {
  const sel = document.getElementById('eTipoSelect');
  if (sel.value === '__outro__') return document.getElementById('eTipoCustom').value.trim();
  return sel.value;
}

// ═══════════════════════════════════════════════════════════════════
// ACESSÓRIOS — Drag and Drop com CRUD
// ═══════════════════════════════════════════════════════════════════

const STORAGE_KEY = 'oficina_acessorios_banco';

const ACESSORIOS_PADRAO = [
  // TV / Vídeo
  { id: 1,  nome: 'Controle Remoto',       grupo: 'TV' },
  { id: 2,  nome: 'Cabo de Força',          grupo: 'TV' },
  { id: 3,  nome: 'Base / Pedestal',        grupo: 'TV' },
  { id: 4,  nome: 'Cabo HDMI',              grupo: 'TV' },
  { id: 5,  nome: 'Manual do Usuário',      grupo: 'TV' },
  { id: 6,  nome: 'Óculos 3D',              grupo: 'TV' },
  { id: 7,  nome: 'Suporte de Parede',      grupo: 'TV' },
  { id: 8,  nome: 'Pilhas do Controle',     grupo: 'TV' },
  { id: 9,  nome: 'Cabo AV / RCA',          grupo: 'TV' },
  { id: 10, nome: 'Adaptador Wi-Fi',        grupo: 'TV' },
  { id: 11, nome: 'Conversor Digital',      grupo: 'TV' },
  { id: 12, nome: 'Controle Universal',     grupo: 'TV' },
  { id: 13, nome: 'Cabo Coaxial',           grupo: 'TV' },
  // Celular / Tablet
  { id: 20, nome: 'Carregador',             grupo: 'Celular' },
  { id: 21, nome: 'Cabo USB',               grupo: 'Celular' },
  { id: 22, nome: 'Capa Protetora',         grupo: 'Celular' },
  { id: 23, nome: 'Película / Vidro',       grupo: 'Celular' },
  { id: 24, nome: 'Fone de Ouvido',         grupo: 'Celular' },
  { id: 25, nome: 'SIM Card',               grupo: 'Celular' },
  { id: 26, nome: 'Caneta Stylus',          grupo: 'Celular' },
  // Notebook
  { id: 30, nome: 'Fonte / Carregador',     grupo: 'Notebook' },
  { id: 31, nome: 'Mouse',                  grupo: 'Notebook' },
  { id: 32, nome: 'Mochila / Bolsa',        grupo: 'Notebook' },
  { id: 33, nome: 'Suporte',               grupo: 'Notebook' },
  // Eletrodomésticos
  { id: 40, nome: 'Prateleiras',            grupo: 'Eletro' },
  { id: 41, nome: 'Gavetas',               grupo: 'Eletro' },
  { id: 42, nome: 'Borracha de Vedação',    grupo: 'Eletro' },
  { id: 43, nome: 'Mangueira',             grupo: 'Eletro' },
  { id: 44, nome: 'Controle Remoto (AC)',   grupo: 'Eletro' },
  { id: 45, nome: 'Pé Nivelador',          grupo: 'Eletro' },
];

let bancoDados    = [];
let selecionados  = [];
let dragItem      = null;
let dragSource    = null; // 'banco' | 'sel'
let proximoId     = 100;

function carregarBanco() {
  try {
    const saved = localStorage.getItem(STORAGE_KEY);
    bancoDados = saved ? JSON.parse(saved) : JSON.parse(JSON.stringify(ACESSORIOS_PADRAO));
  } catch(e) {
    bancoDados = JSON.parse(JSON.stringify(ACESSORIOS_PADRAO));
  }
  proximoId = Math.max(...bancoDados.map(a => a.id), 99) + 1;
}

function salvarBanco() {
  localStorage.setItem(STORAGE_KEY, JSON.stringify(bancoDados));
}

function renderBanco() {
  const el = document.getElementById('bancoDrop');
  el.innerHTML = '';
  bancoDados.forEach(item => {
    const chip = criarChip(item, 'banco');
    el.appendChild(chip);
  });
}

function renderSelecionados() {
  const el  = document.getElementById('selecionadosDrop');
  const msg = document.getElementById('msgSelecionadosVazio');
  el.querySelectorAll('.chip-sel').forEach(c => c.remove());

  selecionados.forEach(item => {
    const chip = criarChip(item, 'sel');
    el.appendChild(chip);
  });

  msg.style.display = selecionados.length ? 'none' : '';
  document.getElementById('badgeQtdSel').textContent = selecionados.length + ' selecionado' + (selecionados.length !== 1 ? 's' : '');
}

function criarChip(item, origem) {
  const div = document.createElement('div');
  div.className = 'chip-' + origem + ' d-flex align-items-center gap-1 px-2 py-1 rounded-pill border fw-semibold';
  div.style.cssText = origem === 'banco'
    ? 'background:#f8f9fa;font-size:.78rem;cursor:grab;user-select:none'
    : 'background:#0d6efd;color:#fff;font-size:.78rem;cursor:grab;user-select:none';
  div.setAttribute('draggable', 'true');
  div.dataset.id     = item.id;
  div.dataset.nome   = item.nome;
  div.dataset.origem = origem;

  // Ícone grip
  const grip = document.createElement('i');
  grip.className = 'bi bi-grip-vertical opacity-50';
  div.appendChild(grip);

  // Nome (editável no banco via duplo clique)
  const span = document.createElement('span');
  span.textContent = item.nome;
  if (origem === 'banco') {
    span.title = 'Duplo clique para editar';
    span.addEventListener('dblclick', () => editarAcessorio(item.id, span, div));
  }
  div.appendChild(span);

  // Botão ação
  const btn = document.createElement('button');
  btn.type = 'button';
  btn.style.cssText = 'border:none;background:none;padding:0;line-height:1;' + (origem === 'banco' ? 'color:#dc3545' : 'color:#fff');

  if (origem === 'banco') {
    btn.innerHTML = '<i class="bi bi-trash3" style="font-size:.7rem"></i>';
    btn.title = 'Remover do banco';
    btn.addEventListener('click', (e) => { e.stopPropagation(); removerDoBanco(item.id); });
  } else {
    btn.innerHTML = '<i class="bi bi-x-lg" style="font-size:.7rem"></i>';
    btn.title = 'Remover da seleção';
    btn.addEventListener('click', (e) => { e.stopPropagation(); removerDosSelecionados(item.id); });
  }
  div.appendChild(btn);

  // Click para mover entre zonas
  div.addEventListener('click', (e) => {
    if (e.target.closest('button') || e.target.closest('input')) return;
    if (origem === 'banco') moverParaSel(item);
    else removerDosSelecionados(item.id);
  });

  // Drag events
  div.addEventListener('dragstart', (e) => {
    dragItem   = item;
    dragSource = origem;
    div.style.opacity = '.4';
    e.dataTransfer.effectAllowed = 'move';
  });
  div.addEventListener('dragend', () => { div.style.opacity = '1'; });

  return div;
}

function moverParaSel(item) {
  if (!selecionados.find(s => s.id === item.id)) {
    selecionados.push(item);
    renderSelecionados();
    sincronizarHidden();
  }
}

function removerDosSelecionados(id) {
  selecionados = selecionados.filter(s => s.id !== id);
  renderSelecionados();
  sincronizarHidden();
}

function removerDoBanco(id) {
  if (!confirm('Remover este acessório do banco?')) return;
  bancoDados = bancoDados.filter(a => a.id !== id);
  selecionados = selecionados.filter(s => s.id !== id);
  salvarBanco();
  renderBanco();
  renderSelecionados();
  sincronizarHidden();
}

function dropParaSelecionados(e) {
  e.preventDefault();
  document.getElementById('selecionadosDrop').classList.remove('drag-over');
  if (!dragItem) return;
  if (dragSource === 'banco') moverParaSel(dragItem);
  dragItem = null;
}

function dropParaBanco(e) {
  e.preventDefault();
  document.getElementById('bancoDrop').classList.remove('drag-over');
  if (!dragItem) return;
  if (dragSource === 'sel') removerDosSelecionados(dragItem.id);
  dragItem = null;
}

// Highlight das zonas ao arrastar sobre elas
document.addEventListener('dragover', function(e) {
  const banco = document.getElementById('bancoDrop');
  const sel   = document.getElementById('selecionadosDrop');
  if (banco && sel && dragItem) {
    banco.classList.toggle('drag-over', banco.contains(e.target) || e.target === banco);
    sel.classList.toggle('drag-over',   sel.contains(e.target)   || e.target === sel);
  }
});

function limparSelecionados() {
  selecionados = [];
  renderSelecionados();
  sincronizarHidden();
}

function adicionarAoBanco() {
  const input = document.getElementById('inputNovoAcessorio');
  const nome  = input.value.trim();
  if (!nome) { input.focus(); return; }
  if (bancoDados.find(a => a.nome.toLowerCase() === nome.toLowerCase())) {
    input.classList.add('is-invalid');
    setTimeout(() => input.classList.remove('is-invalid'), 1500);
    return;
  }
  const item = { id: proximoId++, nome, grupo: 'Custom' };
  bancoDados.push(item);
  salvarBanco();
  renderBanco();
  input.value = '';
  input.focus();
  // Seleciona automaticamente o novo item
  setTimeout(() => moverParaSel(item), 50);
}

function editarAcessorio(id, span, chip) {
  const atual = span.textContent;
  const input = document.createElement('input');
  input.type  = 'text';
  input.value = atual;
  input.className = 'form-control form-control-sm';
  input.style.cssText = 'width:100px;font-size:.75rem;padding:1px 4px';
  span.replaceWith(input);
  input.focus();
  input.select();

  const salvar = () => {
    const novo = input.value.trim() || atual;
    const item = bancoDados.find(a => a.id === id);
    if (item) item.nome = novo;
    const selItem = selecionados.find(s => s.id === id);
    if (selItem) selItem.nome = novo;
    salvarBanco();
    renderBanco();
    renderSelecionados();
    sincronizarHidden();
  };
  input.addEventListener('blur', salvar);
  input.addEventListener('keydown', e => { if (e.key === 'Enter') input.blur(); if (e.key === 'Escape') { input.value = atual; input.blur(); } });
}

function sincronizarHidden() {
  document.getElementById('fAcessorios').value = selecionados.map(s => s.nome).join(', ');
}

function inicializarAcessorios() {
  selecionados = [];
  carregarBanco();
  renderBanco();
  renderSelecionados();
}

// ─── Marca: toggle campo customizado ────────────────────────────────
function toggleMarcaCustom(val) {
  const custom = document.getElementById('eMarcaCustom');
  if (val === '__outra__') {
    custom.classList.remove('d-none');
    custom.focus();
  } else {
    custom.classList.add('d-none');
    custom.value = '';
  }
}
function syncMarca(val) {
  // mantém select em "Outra" enquanto digita no campo livre
}
function getMarca() {
  const sel = document.getElementById('eMarcaSelect');
  if (sel.value === '__outra__') return document.getElementById('eMarcaCustom').value.trim();
  return sel.value;
}

// ─── Helpers (disponíveis globalmente, sem depender do Bootstrap) ────
function esc(s)   { return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;'); }
function escJs(s) { return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'"); }
function iniciais(nome) {
  const p = String(nome||'U').trim().split(' ');
  return ((p[0]||'')[0]||'').toUpperCase() + ((p.length>1?(p[p.length-1]||''):'')[0]||'').toUpperCase();
}

// ─── Tudo que depende do Bootstrap roda APÓS o carregamento ──────────
window.addEventListener('load', function() {

  // Inicializa modais
  modalCliente = new bootstrap.Modal(document.getElementById('modalCliente'), { backdrop: 'static' });
  modalEquip   = new bootstrap.Modal(document.getElementById('modalEquipamento'), { backdrop: 'static' });

  // Offcanvas de tipos
  offcanvasTipos = new bootstrap.Offcanvas(document.getElementById('offcanvasTipos'));

  // Carrega tipos e popula select
  carregarTipos();
  renderSelectTipo();

  // Inicializa acessórios quando o modal de equip abrir
  document.getElementById('modalEquipamento').addEventListener('shown.bs.modal', function() {
    inicializarAcessorios();
  });
  fClienteId     = document.getElementById('fClienteId');
  fEquipamentoId = document.getElementById('fEquipamentoId');

  // ─── Busca AJAX ────────────────────────────────────────────────────
  let timerBusca;
  document.getElementById('campoBusca').addEventListener('input', function() {
    clearTimeout(timerBusca);
    const q = this.value.trim();
    const spinner = document.getElementById('spinnerBusca');

    if (q.length < 2) {
      document.getElementById('resultadosBusca').innerHTML = `
        <div class="text-center text-muted py-4">
          <i class="bi bi-person-lines-fill fs-2 d-block mb-2 opacity-30"></i>
          Digite para buscar clientes cadastrados
        </div>`;
      return;
    }

    spinner.classList.remove('d-none');
    timerBusca = setTimeout(async () => {
      const r    = await fetch(`${API_CL}?q=${encodeURIComponent(q)}`);
      const list = await r.json();
      spinner.classList.add('d-none');
      renderResultados(list, q);
    }, 300);
  });

  // ─── Tabs: mostrar/ocultar botão "Cadastrar e continuar" ───────────
  document.querySelectorAll('[data-bs-target="#tabNovo"]').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('btnSalvarCliente').style.display = '');
  });
  document.querySelectorAll('[data-bs-target="#tabBuscar"]').forEach(btn => {
    btn.addEventListener('click', () => document.getElementById('btnSalvarCliente').style.display = 'none');
  });

  // ─── Salvar novo cliente via AJAX ──────────────────────────────────
  document.getElementById('btnSalvarCliente').addEventListener('click', async function() {
    const nome = document.getElementById('ncNome').value.trim();
    if (!nome) {
      document.getElementById('ncNome').classList.add('is-invalid');
      document.getElementById('ncNome').focus();
      return;
    }
    document.getElementById('ncNome').classList.remove('is-invalid');

    const spinner = document.getElementById('spinnerSalvarCliente');
    spinner.classList.remove('d-none');
    this.disabled = true;

    const body = {
      _token:      CSRF,
      tipo:        document.getElementById('ncTipo').value,
      nome,
      cpf_cnpj:    document.getElementById('ncCpf').value,
      telefone:    document.getElementById('ncTelefone').value,
      whatsapp:    document.getElementById('ncWhatsapp').value,
      email:       document.getElementById('ncEmail').value,
      origem:      document.getElementById('ncOrigem').value,
      cep:         document.getElementById('ncCep').value,
      logradouro:  document.getElementById('ncLogradouro').value,
      numero:      document.getElementById('ncNumero').value,
      complemento: document.getElementById('ncComplemento').value,
      bairro:      document.getElementById('ncBairro').value,
      cidade:      document.getElementById('ncCidade').value,
      uf:          document.getElementById('ncUf').value,
    };

    try {
      const r    = await fetch(API_CL, { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF}, body:JSON.stringify(body) });
      const data = await r.json();

      if (data.error) {
        document.getElementById('erroNovoCliente').textContent = data.error;
        document.getElementById('erroNovoCliente').classList.remove('d-none');
      } else {
        const c = data.cliente;
        clienteSelecionado = { id: c.id, nome: c.nome, tel: c.telefone||'', doc: c.cpf_cnpj||'' };
        confirmarClienteEAbrirEquip();
      }
    } catch(err) {
      document.getElementById('erroNovoCliente').textContent = 'Erro de conexão. Tente novamente.';
      document.getElementById('erroNovoCliente').classList.remove('d-none');
    }

    spinner.classList.add('d-none');
    this.disabled = false;
  });

  // ─── Confirmar equipamento ─────────────────────────────────────────
  document.getElementById('btnConfirmarEquipamento').addEventListener('click', function() {
    const tipo = getTipo();
    const err  = document.getElementById('erroEquipamento');

    if (!tipo) {
      err.textContent = 'Selecione ou informe o tipo do equipamento.';
      err.classList.remove('d-none');
      document.getElementById('eTipoSelect').focus();
      return;
    }
    err.classList.add('d-none');

    const marca  = getMarca();

    document.getElementById('fCategoriaId').value = '';
    document.getElementById('fEquipTipo').value    = tipo;
    document.getElementById('fEquipMarca').value    = marca;
    document.getElementById('fEquipModelo').value   = document.getElementById('eModelo').value;
    document.getElementById('fNumeroSerie').value   = document.getElementById('eNumeroSerie').value;
    document.getElementById('fImei').value          = document.getElementById('eImei').value;
    document.getElementById('fEquipCor').value      = document.getElementById('eCor').value;
    document.getElementById('fVoltagem').value      = document.getElementById('eVoltagem').value;
    document.getElementById('fEstadoEntrada').value = document.getElementById('eEstado').value;
    document.getElementById('fSenha').value         = document.getElementById('eSenha').value;
    // fAcessorios já é atualizado em tempo real pelo drag-and-drop (sincronizarHidden)

    const modelo = document.getElementById('eModelo').value;
    const ns     = document.getElementById('eNumeroSerie').value;

    document.getElementById('equipamentoResumo').innerHTML = `
      <div class="d-flex align-items-start gap-3">
        <div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:42px;height:42px;flex-shrink:0">
          <i class="bi bi-cpu fs-5"></i>
        </div>
        <div>
          <div class="fw-semibold">${esc(marca)} ${esc(modelo)}</div>
          <div class="text-muted small">${esc(tipo)}</div>
          ${ns ? `<div class="small text-muted">S/N: ${esc(ns)}</div>` : ''}
        </div>
      </div>`;

    modalEquip.hide();
    setTimeout(() => document.querySelector('[name="defeito_relatado"]').focus(), 300);
  });

  // ─── Voltar / Fechar equipamento ───────────────────────────────────
  document.getElementById('btnVoltarCliente').addEventListener('click', function() {
    modalEquip.hide();
    setTimeout(() => modalCliente.show(), 300);
  });
  document.getElementById('btnFecharEquip').addEventListener('click', function() {
    modalEquip.hide();
  });

  // ─── Validação do form principal ───────────────────────────────────
  document.getElementById('formOS').addEventListener('submit', function(e) {
    if (!fClienteId.value) {
      e.preventDefault();
      abrirModalCliente();
      return;
    }
    const btn = document.getElementById('btnSalvarOS');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
  });

  // ─── Máscaras nos campos do modal novo cliente ─────────────────────
  if (typeof IMask !== 'undefined') {
    IMask(document.getElementById('ncTelefone'), { mask:[{mask:'(00) 0000-0000'},{mask:'(00) 00000-0000'}] });
    IMask(document.getElementById('ncWhatsapp'), { mask:[{mask:'(00) 0000-0000'},{mask:'(00) 00000-0000'}] });
    IMask(document.getElementById('ncCpf'),      { mask:[{mask:'000.000.000-00',maxLength:11},{mask:'00.000.000/0000-00'}] });
  }

  document.getElementById('ncTipo').addEventListener('change', function() {
    document.getElementById('ncLblDoc').textContent = this.value === 'pj' ? 'CNPJ' : 'CPF';
    document.getElementById('ncCpf').placeholder    = this.value === 'pj' ? '00.000.000/0000-00' : '000.000.000-00';
  });

  // ─── Máscara e autopreenchimento do CEP ──────────────────────────
  if (typeof IMask !== 'undefined') {
    IMask(document.getElementById('ncCep'), { mask: '00000-000' });
  }

  document.getElementById('ncCep').addEventListener('blur', async function() {
    const cep     = this.value.replace(/\D/g, '');
    const spinner = document.getElementById('ncCepSpinner');
    const msg     = document.getElementById('ncCepMsg');

    if (cep.length !== 8) return;

    spinner.classList.remove('d-none');
    this.disabled = true;
    msg.textContent = '';

    try {
      const r = await fetch(`https://viacep.com.br/ws/${cep}/json/`);
      const d = await r.json();

      if (d.erro) {
        msg.textContent = '⚠ CEP não encontrado.';
        msg.className   = 'form-text text-danger';
      } else {
        const campos = {
          ncLogradouro: d.logradouro  || '',
          ncBairro:     d.bairro      || '',
          ncCidade:     d.localidade  || '',
          ncUf:         d.uf          || '',
          ncComplemento:d.complemento || '',
        };
        for (const [id, val] of Object.entries(campos)) {
          const el = document.getElementById(id);
          if (el && val) {
            el.value = val;
            el.classList.add('border-success');
            setTimeout(() => el.classList.remove('border-success'), 2500);
          }
        }
        msg.textContent = '✅ Endereço preenchido!';
        msg.className   = 'form-text text-success';
        setTimeout(() => msg.textContent = '', 3000);
        // Foca no número
        setTimeout(() => document.getElementById('ncNumero').focus(), 100);
      }
    } catch (e) {
      msg.textContent = 'Erro ao buscar CEP.';
      msg.className   = 'form-text text-danger';
    }

    spinner.classList.add('d-none');
    this.disabled = false;
  });

  <?php if (!empty($os['cliente_id'])): ?>
  clienteSelecionado = { id: <?= (int)$os['cliente_id'] ?>, nome: '<?= addslashes($os['cliente_nome'] ?? '') ?>' };
  <?php endif; ?>

}); // fim window.addEventListener('load')

// ─── renderResultados (global, pois é chamada por onclick inline) ────
function renderResultados(list, q) {
  const box = document.getElementById('resultadosBusca');
  if (!list.length) {
    box.innerHTML = `
      <div class="text-center text-muted py-4">
        <i class="bi bi-person-x fs-2 d-block mb-2 opacity-30"></i>
        <div>Nenhum cliente encontrado para <strong>"${esc(q)}"</strong></div>
        <button class="btn btn-outline-primary btn-sm mt-2"
          onclick="document.querySelector('[data-bs-target=&quot;#tabNovo&quot;]').click();document.getElementById('ncNome').value='${escJs(q)}'">
          <i class="bi bi-person-plus me-1"></i> Cadastrar "${esc(q)}" como novo cliente
        </button>
      </div>`;
    return;
  }

  box.innerHTML = list.map(c => `
    <div class="d-flex align-items-center gap-3 p-3 border-bottom"
         style="cursor:pointer" onmouseenter="this.classList.add('bg-light')" onmouseleave="this.classList.remove('bg-light')"
         onclick="selecionarCliente(${c.id},'${escJs(c.nome)}','${escJs(c.telefone||'')}','${escJs(c.cpf_cnpj||'')}')">
      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
           style="width:40px;height:40px;font-size:.8rem">${iniciais(c.nome)}</div>
      <div class="flex-grow-1">
        <div class="fw-semibold">${esc(c.nome)}</div>
        <div class="small text-muted">${[c.cpf_cnpj,c.telefone,c.email].filter(Boolean).map(esc).join(' · ')}</div>
      </div>
      <i class="bi bi-chevron-right text-muted"></i>
    </div>
  `).join('');
}

function selecionarCliente(id, nome, tel, doc) {
  clienteSelecionado = { id, nome, tel, doc };
  confirmarClienteEAbrirEquip();
}

function confirmarClienteEAbrirEquip() {
  fClienteId.value = clienteSelecionado.id;

  document.getElementById('clienteResumo').innerHTML = `
    <div class="d-flex align-items-center gap-3">
      <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold"
           style="width:42px;height:42px">${iniciais(clienteSelecionado.nome)}</div>
      <div>
        <div class="fw-semibold">${esc(clienteSelecionado.nome)}</div>
        <div class="text-muted small">${esc(clienteSelecionado.tel||'')} ${clienteSelecionado.doc ? '· ' + esc(clienteSelecionado.doc) : ''}</div>
      </div>
      <button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="abrirModalCliente()">
        <i class="bi bi-arrow-repeat"></i> Trocar
      </button>
    </div>`;

  document.getElementById('badgeEtapa2').classList.replace('bg-secondary','bg-primary');
  document.getElementById('btnEditarEquip').style.display = '';

  modalCliente.hide();
  document.getElementById('equipClienteNome').textContent = clienteSelecionado.nome;
  setTimeout(() => { modalEquip.show(); setTimeout(() => document.getElementById('eTipo').focus(), 400); }, 300);
}
</script>
