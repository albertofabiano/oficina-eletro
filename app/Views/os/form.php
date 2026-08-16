<style id="selExStyle">.sel-ex:has(option[value=""]:checked){color:#8a94a6}</style>
<?php $editando = !empty($os['id']); ?>
<?php $formAction = $editando ? url('/os/' . $os['id'] . '/editar') : url('/os'); ?>
<?php $fotosExistentes = $fotosExistentes ?? []; ?>

<style>
/* Offcanvas acima do modal */
#offcanvasTipos, #offcanvasMarcas, #offcanvasAcessorios, #offcanvasTecnicos { z-index: 1200 !important; }
.offcanvas-backdrop { z-index: 1190 !important; }

/* Cabeçalho da tela (Nova OS + etapa X de 4) */
.fx-wizard-header { display: flex; align-items: baseline; gap: 10px; margin-bottom: 16px; text-transform: none; }
.fx-wizard-title { font-size: 17px; font-weight: 700; color: var(--text-1); margin: 0; text-transform: none; }

/* Card do cliente selecionado (aba Cliente) — hover usava cor fixa clara
   (#f1f5f9) só pensada pro tema claro; no escuro o texto (claro, herdado do
   .card) ficava ilegível sobre esse fundo claro no hover. */
.fx-cliente-resumo-row { color: var(--text-1); }
.fx-cliente-resumo-row:hover { background: var(--surface-2); }
.fx-wizard-etapa { font-size: 12px; color: var(--text-3); }
.fx-wizard-x { margin-left: auto; background: none; border: none; color: var(--text-3); font-size: 19px; padding: 6px; display: none; line-height: 1; }

/* Trilha de etapas — nunca cinza na etapa ativa */
.os-steps {
  display: flex; gap: 0; margin-bottom: 1.75rem; background: var(--surface-2);
  border-radius: var(--radius-lg) var(--radius-lg) 0 0; box-shadow: none; overflow: hidden;
  border: 1px solid var(--border);
}
.os-step {
  flex: 1; padding: .7rem .5rem 1rem; text-align: center; cursor: default;
  font-size: .8rem; font-weight: 600; color: var(--text-3);
  border-right: 1px solid var(--border); border-bottom: 2px solid transparent;
  transition: color .2s, border-color .2s, background .2s; position: relative; user-select: none;
  text-transform: none;
}
.os-step:last-child { border-right: none; }
.os-step.active { background: var(--accent-bg); }
.os-step.clicavel { cursor: pointer; }
.os-step.clicavel:hover { color: var(--text-1); }
.os-step .step-num {
  display: inline-flex; align-items: center; justify-content: center;
  width: 26px; height: 26px; border-radius: 50%;
  background: var(--surface-2); color: var(--text-3);
  font-size: .78rem; font-weight: 700; margin: 0 auto .35rem;
  transition: background .2s, color .2s;
}
.os-step .step-label { display: block; text-transform: none; }
.os-step.active { color: var(--accent-text); border-bottom-color: var(--accent); }
.os-step.active .step-num { background: var(--accent); color: #fff; }
.os-step.done { color: var(--text-2); }
.os-step.done .step-num { background: var(--success-fill); color: #fff; }
.os-tab-pane { display: none; }
.os-tab-pane.active { display: block; }

/* Layout de duas colunas + painel de resumo persistente (aparece nas 4 etapas) */
.fx-wizard-layout { display: flex; gap: 24px; align-items: flex-start; }
.fx-wizard-main { flex: 1; min-width: 0; }
.fx-wizard-resumo {
  width: 272px; flex-shrink: 0; background: var(--surface-1); border: 1px solid var(--border);
  border-radius: var(--radius-lg); padding: 16px; position: sticky; top: 80px;
}
.fx-resumo-titulo { font-size: 10.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--text-3); margin-bottom: 12px; }
.fx-resumo-item { margin-bottom: 12px; }
.fx-resumo-item:last-child { margin-bottom: 0; }
.fx-resumo-item dt { font-size: 11px; color: var(--text-3); margin-bottom: 2px; text-transform: none; }
.fx-resumo-item dd { font-size: 13px; color: var(--text-1); margin: 0; text-transform: none; line-height: 1.35; }
.fx-resumo-item dd.vazio { color: var(--text-4); }
.fx-resumo-alerta { margin-top: 12px; padding: 10px 12px; border-radius: 8px; background: var(--warning-bg); color: var(--warning); font-size: 12px; line-height: 1.4; text-transform: none; }
.fx-resumo-alerta a { color: inherit; text-decoration: underline; }

/* Resumo colapsável — só no mobile (ver breakpoint 900px) */
.fx-wizard-resumo-mobile { display: none; }
.fx-wizard-resumo-mobile summary { cursor: pointer; font-size: 12.5px; font-weight: 600; color: var(--text-2); padding: 10px 2px; list-style: none; display: flex; align-items: center; gap: 6px; }
.fx-wizard-resumo-mobile summary::-webkit-details-marker { display: none; }
.fx-wizard-resumo-mobile[open] summary i { transform: rotate(180deg); }
.fx-wizard-resumo-mobile summary i { transition: transform .2s; margin-left: auto; }
.fx-wizard-resumo-mobile .fx-resumo-item { padding: 0 2px; }

/* Busca de cliente inline (substitui o modal nesta etapa) */
.fx-cliente-busca { position: relative; margin-bottom: 18px; }
.fx-cliente-busca i.bi-search { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-3); font-size: 16px; }
.fx-cliente-busca input {
  width: 100%; height: 46px; padding: 0 14px 0 40px; border-radius: var(--radius); border: 1px solid var(--border);
  background: var(--surface-1); color: var(--text-1); font-size: 14px; transition: border-color .15s;
}
.fx-cliente-busca input:focus { outline: none; border: 1.5px solid var(--accent); padding-left: 39.5px; }
.fx-sec-label { font-size: 10.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--text-3); margin-bottom: 8px; }
.fx-cliente-item {
  display: flex; align-items: center; gap: 12px; padding: 10px 12px; border-radius: var(--radius);
  border-left: 3px solid transparent; cursor: pointer; transition: background .12s;
}
.fx-cliente-item:hover, .fx-cliente-item.foco { background: var(--surface-2); }
.fx-cliente-item.selecionado { background: var(--accent-bg); border-left-color: var(--accent); }
.fx-cliente-avatar {
  width: 32px; height: 32px; border-radius: 50%; background: var(--accent); color: #fff;
  display: flex; align-items: center; justify-content: center; font-size: 11.5px; font-weight: 700; flex-shrink: 0;
}
.fx-cliente-info { flex: 1; min-width: 0; }
.fx-cliente-nome { display: block; font-size: 13px; color: var(--text-1); font-weight: 500; text-transform: none; }
.fx-cliente-sub { display: block; font-size: 11.5px; color: var(--text-3); text-transform: none; }
.fx-cliente-check { display: none; color: var(--accent); font-size: 16px; flex-shrink: 0; }
.fx-cliente-item.selecionado .fx-cliente-check { display: block; }
.fx-cliente-vazio { padding: 1.2rem; text-align: center; font-size: 12.5px; color: var(--text-3); text-transform: none; }
.fx-link-secundario {
  display: inline-flex; align-items: center; gap: 8px; margin: 4px 0 18px; font-size: 12.5px;
  font-weight: 600; color: var(--accent-text); text-decoration: none; text-transform: none;
  padding: 8px 14px; border-radius: 999px; border: 1.5px solid var(--accent); background: var(--accent-bg);
  transition: background .15s, color .15s, box-shadow .15s;
}
.fx-link-secundario:hover {
  text-decoration: none; background: var(--accent); color: #fff;
  box-shadow: 0 2px 10px rgba(0,0,0,.15);
}
.fx-link-secundario .fx-link-icone {
  display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px;
  border-radius: 50%; background: #fff; border: 1.5px solid var(--accent); color: var(--accent-text);
  font-size: 13px; flex-shrink: 0; transition: background .15s, color .15s, border-color .15s;
}
.fx-link-secundario:hover .fx-link-icone { background: #fff; color: var(--accent); border-color: #fff; }

/* Botões de navegação */
.os-nav { display: flex; justify-content: space-between; align-items: center; margin-top: 1.5rem; gap: 12px; }
.fx-nav-cancelar { font-size: 13px; color: var(--text-3); text-decoration: none; background: none; border: none; }
.fx-nav-cancelar:hover { color: var(--text-1); }
.fx-nav-continuar-wrap { display: flex; align-items: center; gap: 10px; }
.fx-nav-aviso { font-size: 15px; font-weight: 600; color: var(--text-2); text-transform: none; }
.fx-nav-dica { font-size: 11px; color: var(--text-4); text-transform: none; }
.fx-btn-continuar {
  border: 1px solid var(--accent-hover); border-radius: var(--radius); padding: calc(.55rem - 1px) calc(1.3rem - 1px); font-weight: 700; font-size: 1rem;
  background: var(--accent-bg); color: var(--accent-text); cursor: default; transition: background .15s, color .15s, border-color .15s;
}
.fx-btn-continuar.ativo { background: var(--accent); color: #fff; cursor: pointer; }
.fx-btn-continuar.ativo:hover { background: var(--accent-hover); }

@media (max-width: 900px) {
  .fx-wizard-resumo { display: none; }
  .fx-wizard-resumo-mobile { display: block; margin-bottom: 16px; border: 1px solid var(--border); border-radius: var(--radius); padding: 4px 12px; background: var(--surface-1); }
  .fx-wizard-x { display: inline-flex; align-items: center; justify-content: center; }
  .fx-nav-cancelar { display: none; }
  .os-nav {
    position: sticky; bottom: 0; background: var(--surface-0); margin: 1.5rem -12px -12px; padding: 12px;
    border-top: 1px solid var(--border);
  }
  .fx-nav-continuar-wrap { flex-direction: column; align-items: stretch; width: 100%; gap: 4px; }
  .fx-btn-continuar { width: 100%; padding: .8rem; min-height: 44px; }
  .fx-nav-aviso, .fx-nav-dica { text-align: center; }
  .os-steps { display: none; }
  .fx-wizard-progresso-wrap { display: block; margin-bottom: 1.25rem; }
}
.fx-wizard-progresso-wrap { display: none; }
.fx-wizard-progresso { display: flex; gap: 4px; margin-bottom: 8px; }
.fx-wizard-progresso span { flex: 1; height: 4px; border-radius: 2px; background: var(--surface-2); }
.fx-wizard-progresso span.done, .fx-wizard-progresso span.active { background: var(--accent); }
.fx-wizard-progresso-label { font-size: 11px; color: var(--text-3); text-transform: none; }

/* ── Formulário de Equipamento (modal da etapa 2) ─────────────────────── */
.fx-equip-pareamento {
  display: flex; align-items: center; gap: 10px;
  background: var(--accent-bg); border-bottom: 0.5px solid var(--border);
  padding: 10px 24px;
  flex-shrink: 0; position: relative; z-index: 2;
}
.fx-equip-pareamento > i.bi-phone { font-size: 21px; color: var(--accent-text); flex-shrink: 0; }
.fx-equip-pareamento-texto { flex: 1; min-width: 0; }
.fx-equip-pareamento-titulo { font-size: 12.5px; font-weight: 600; color: var(--text-1); text-transform: none; }
.fx-equip-pareamento-sub { font-size: 11px; color: var(--text-3); text-transform: none; }
.fx-equip-pareamento-btn {
  display: inline-flex; align-items: center; gap: 6px; flex-shrink: 0;
  background: var(--accent); color: #fff; border: none; border-radius: var(--radius);
  padding: 7px 14px; font-size: 12.5px; font-weight: 600; text-transform: none; white-space: nowrap;
}
.fx-equip-pareamento-btn:hover { background: var(--accent-hover); }

/* Alerta de validação do Equipamento (topo do modal-body) — evita ficar por baixo da faixa de pareamento */
#erroEquipamento { position: relative; z-index: 5; }

/* Botão secundário "tirar foto pelo celular" — mesma família visual do botão de pareamento, sem competir com a ação principal */
.fx-btn-celular {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--accent-bg); color: var(--accent-text); border: 1px solid var(--accent);
  border-radius: var(--radius); padding: 6px 14px; font-size: 13px; font-weight: 600;
  text-transform: none; white-space: nowrap; line-height: 1.4;
}
.fx-btn-celular:hover { background: var(--accent); color: #fff; }
.fx-btn-celular i { font-size: 13px; }

.fx-equip-secao-titulo { font-size: 12.5px; font-weight: 600; color: var(--text-1); margin-bottom: 8px; text-transform: none; }

/* Chips de tipo */
.fx-tipo-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.fx-tipo-chip {
  display: inline-flex; align-items: center; gap: 6px; padding: 8px 14px; min-height: 38px;
  border-radius: 999px; border: 0.5px solid var(--border); color: var(--text-2);
  font-size: 13px; font-weight: 500; cursor: pointer; background: var(--surface-1);
  text-transform: none; white-space: nowrap;
}
.fx-tipo-chip.selecionado { border: 1.5px solid var(--accent); background: var(--accent-bg); color: var(--accent-text); }
.fx-tipo-chip.outro { border-style: dashed; }

/* Checklist de estado de entrada */
.fx-estado-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; }
.fx-estado-item {
  display: flex; align-items: center; gap: 8px; padding: 9px 12px; min-height: 40px;
  border-radius: var(--radius); border: 0.5px solid var(--border); color: var(--text-2);
  font-size: 12.5px; cursor: pointer; text-transform: none; user-select: none;
}
.fx-estado-item .bi-check-square, .fx-estado-item .bi-square { flex-shrink: 0; }
.fx-estado-item.marcado { border: 1.5px solid var(--warning-fill); background: var(--warning-bg); color: var(--warning); font-weight: 600; }

/* Chips de acessório */
.fx-acessorio-chips { display: flex; gap: 8px; flex-wrap: wrap; }
.fx-acessorio-chip {
  display: inline-flex; align-items: center; gap: 6px; padding: 7px 13px; min-height: 36px;
  border-radius: 999px; border: 0.5px solid var(--border); color: var(--text-2);
  font-size: 12.5px; cursor: pointer; text-transform: none; background: var(--surface-1);
}
.fx-acessorio-chip .bi-check-lg { display: none; font-size: 11px; }
.fx-acessorio-chip.marcado { border: 1.5px solid var(--accent); background: var(--accent-bg); color: var(--accent-text); }
.fx-acessorio-chip.marcado .bi-check-lg { display: inline; }
.fx-acessorio-chip.novo { border-style: dashed; }
.fx-acessorio-del { margin-left: 6px; padding: 3px; color: var(--text-4); font-size: 11px; border-radius: 50%; }
.fx-acessorio-del:hover { color: var(--danger); background: var(--danger-bg); }
.fx-acessorios-contador { font-weight: 400; color: var(--text-3); font-size: 11.5px; }
.fx-acessorios-dica { font-size: 11px; color: var(--text-3); margin-top: 8px; text-transform: none; }
.fx-link-secundario-sm { font-size: 11.5px; color: var(--accent-text); text-decoration: none; text-transform: none; }
.fx-link-secundario-sm:hover { text-decoration: underline; }

/* Número de série com botão de scan embutido */
.fx-input-scan { position: relative; }
.fx-input-scan input { padding-right: 40px; }
.fx-input-scan-btn {
  position: absolute; right: 4px; top: 50%; transform: translateY(-50%);
  border: none; background: none; color: var(--accent-text); padding: 6px; line-height: 1;
}

@media (max-width: 900px) {
  .fx-tipo-chips { flex-wrap: nowrap; overflow-x: auto; padding-bottom: 4px; -webkit-overflow-scrolling: touch; }
  .fx-tipo-chip { flex-shrink: 0; }
  .fx-estado-grid { grid-template-columns: 1fr; }
  .fx-tipo-chip, .fx-acessorio-chip, .fx-estado-item { min-height: 44px; }
  #modalEquipamento .modal-footer { flex-direction: column-reverse; align-items: stretch; gap: 8px; position: sticky; bottom: 0; background: var(--surface-0); }
  #modalEquipamento .modal-footer #btnConfirmarEquipamento,
  #modalEquipamento .modal-footer #btnVoltarCliente { width: 100%; min-height: 44px; }
}
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
  <input type="hidden" name="especificacao"     id="fEspecificacao" value="<?= e($os['especificacao']  ?? '') ?>">
  <input type="hidden" name="estado_entrada"    id="fEstadoEntrada" value="<?= e($os['estado_entrada'] ?? '') ?>">
  <input type="hidden" name="estado_observacoes" id="fEstadoObservacoes" value="<?= e($os['equip_observacoes'] ?? '') ?>">
  <input type="hidden" id="fDefeitoRelatado"><!-- sem name -- o textarea envia defeito_relatado -->
  <input type="hidden" name="acessorios"        id="fAcessorios"    value="<?= e($os['acessorios']        ?? '') ?>">
  <input type="hidden" name="senha_desbloqueio" id="fSenha"         value="<?= e($os['senha_desbloqueio'] ?? '') ?>">
  <input type="hidden" name="tipo_armazenamento" id="fTipoArmazenamento" value="<?= e($os['tipo_armazenamento'] ?? '') ?>">
  <input type="hidden" name="memoria_ram"        id="fMemoriaRam"        value="<?= e($os['memoria_ram']        ?? '') ?>">
  <input type="hidden" name="placa_video"        id="fPlacaVideo"        value="<?= e($os['placa_video']        ?? '') ?>">
  <input type="hidden" name="placa_mae"          id="fPlacaMae"          value="<?= e($os['placa_mae']          ?? '') ?>">
  <input type="hidden" name="processador"        id="fProcessador"       value="<?= e($os['processador']        ?? '') ?>">

  <!-- â”€â”€ Wizard Steps â”€â”€ -->
  <!-- Cabecalho -->
  <div class="fx-wizard-header">
    <h1 class="fx-wizard-title">Nova OS</h1>
    <span class="fx-wizard-etapa">etapa <span id="etapaAtualNum">1</span> de 4</span>
    <a href="<?= url('/os') ?>" class="fx-wizard-x" title="Cancelar"><i class="bi bi-x-lg"></i></a>
  </div>

  <div class="os-steps" id="osSteps">
    <div class="os-step active clicavel" data-step="0" onclick="irParaStep(0)">
      <div class="step-num" data-num="1">1</div>
      <span class="step-label">Cliente</span>
    </div>
    <div class="os-step" data-step="1" onclick="irParaStep(1)">
      <div class="step-num" data-num="2">2</div>
      <span class="step-label">Equipamento</span>
    </div>
    <div class="os-step" data-step="2" onclick="irParaStep(2)">
      <div class="step-num" data-num="3">3</div>
      <span class="step-label">Defeito</span>
    </div>
    <div class="os-step" data-step="3" onclick="irParaStep(3)">
      <div class="step-num" data-num="4">4</div>
      <span class="step-label">Prazo e valor</span>
    </div>
  </div>

  <!-- Barra de progresso (mobile) -->
  <div class="fx-wizard-progresso-wrap">
    <div class="fx-wizard-progresso" id="wizardProgresso">
      <span class="active"></span><span></span><span></span><span></span>
    </div>
    <div class="fx-wizard-progresso-label">Etapa <span id="etapaAtualNumMobile">1</span> de 4 - <span id="etapaAtualLabelMobile">Cliente</span></div>
  </div>

  <div class="fx-wizard-layout">
  <div class="fx-wizard-main">

  <!-- ABA 1 - CLIENTE -->
  <div class="os-tab-pane active" id="step0">
    <?php if ($editando): ?>
    <div class="card shadow-sm" style="border:2px solid #C0C0C0!important">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-person-circle me-2 text-primary"></i>Cliente</span>
      </div>
      <div class="card-body" id="clienteResumo" style="min-height:120px">
        <?php if (!empty($os['cliente_nome'])): ?>
        <a href="<?= url('/clientes/' . (int)($os['cliente_id'] ?? 0) . '/editar') ?>" target="_blank"
           class="fx-cliente-resumo-row d-flex align-items-center gap-3 text-reset text-decoration-none p-2 rounded"
           title="Abrir edicao deste cliente em nova aba"
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
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <!-- Compat: alvo oculto do fluxo antigo (cadastrar cliente novo via modal ainda escreve aqui) -->
    <div id="clienteResumo" style="display:none"></div>

    <div class="fx-cliente-busca">
      <i class="bi bi-search"></i>
      <input type="text" id="clienteBusca" placeholder="Nome, telefone ou CPF" autocomplete="off">
    </div>
    <a href="#" id="linkCadastrarCliente" class="fx-link-secundario">
      <span class="fx-link-icone"><i class="bi bi-person-plus-fill"></i></span> Cadastrar novo cliente
    </a>
    <div class="fx-sec-label" id="clienteListaLabel">Atendidos recentemente</div>
    <div id="clienteLista"></div>
    <?php endif; ?>

    <div class="os-nav">
      <a href="<?= url('/os') ?>" class="fx-nav-cancelar">Cancelar</a>
      <div class="fx-nav-continuar-wrap">
        <span class="fx-nav-aviso" id="continuarAviso0" style="<?= $editando ? 'display:none' : '' ?>">Selecione um cliente para continuar</span>
        <span class="fx-nav-dica" id="continuarDica0" style="<?= $editando ? '' : 'display:none' ?>">Enter para continuar</span>
        <button type="button" class="fx-btn-continuar<?= $editando ? ' ativo' : '' ?>" id="btnContinuar0" <?= $editando ? '' : 'disabled' ?> onclick="avancarStep(0)">
          Continuar
        </button>
      </div>
    </div>
  </div>

  <!-- â•â• ABA 2 -- EQUIPAMENTO â•â• -->
  <div class="os-tab-pane" id="step1">
    <div class="card shadow-sm" style="border:2px solid #C0C0C0!important">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-cpu me-2 text-primary"></i>Equipamento</span>
        <button type="button" class="btn btn-sm btn-outline-primary" id="btnEditarEquip"
                onclick="abrirModalEquipamento()"
                style="<?= empty($os['equip_tipo']) ? 'display:none' : '' ?>">
          <i class="bi bi-pencil me-1"></i>Alterar
        </button>
      </div>
      <div class="card-body" id="equipamentoResumo" style="min-height:120px">
        <?php if (!empty($os['equip_tipo'])): ?>
        <?php renderEquipResumo($os); ?>
        <?php else: ?>
        <div class="text-center py-4 text-muted" id="equipVazio">
          <i class="bi bi-cpu fs-1 d-block mb-2 opacity-25"></i>
          <p class="mb-3">Nenhum equipamento cadastrado ainda</p>
          <button type="button" class="btn btn-outline-primary" onclick="abrirModalEquipamento()" id="btnAdicionarEquip">
            <i class="bi bi-plus-circle me-1"></i>Adicionar equipamento
          </button>
        </div>
        <?php endif; ?>
      </div>
    </div>
    <!-- Fotos do estado de entrada (comprimidas e convertidas pra webp no aparelho, ficam anexadas à OS) -->
    <div class="card shadow-sm mt-3" style="border:2px solid #C0C0C0!important">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-camera me-2 text-primary"></i>Fotos do estado de entrada</span>
        <span class="badge bg-secondary"><span id="fotosEntradaCount"><?= count($fotosExistentes) ?></span>/6</span>
      </div>
      <div class="card-body">
        <p class="text-muted small mb-2">
          <i class="bi bi-shield-check me-1 text-success"></i>
          Registre arranhões, trincas, gabinete quebrado, etc. As fotos ficam anexadas a esta OS
          como comprovação do estado de entrada.
        </p>
        <label for="inputFotosEntrada" class="btn btn-outline-primary">
          <i class="bi bi-camera me-1"></i> Adicionar foto
        </label>
        <button type="button" class="fx-btn-celular ms-2" onclick="abrirScannerFotosEntrada()">
          <i class="bi bi-phone-fill"></i> Tirar foto pelo celular
        </button>
        <input type="file" id="inputFotosEntrada" accept="image/*" multiple class="d-none"
               onchange="adicionarFotosEntrada(this)">
        <input type="hidden" name="fotos_entrada" id="fFotosEntrada" value="">
        <?php if ($fotosExistentes): ?>
        <div id="prevFotosExistentes" class="d-flex flex-wrap gap-2 mt-3">
          <?php foreach ($fotosExistentes as $f): ?>
          <div style="position:relative" data-foto-id="<?= (int) $f['id'] ?>">
            <img src="<?= url('/uploads/' . $f['arquivo']) ?>" style="width:82px;height:82px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6">
            <?php if (\App\Core\Auth::isAdmin()): ?>
            <button type="button" onclick="removerFotoExistente(<?= (int) $f['id'] ?>, this)" title="Excluir foto (só admin)"
              style="position:absolute;top:-7px;right:-7px;background:#dc3545;color:#fff;border:none;border-radius:50%;width:22px;height:22px;line-height:20px;font-size:14px;padding:0">&times;</button>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
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
            <?php if (empty($tecnicos)): ?>
            <div class="form-text text-warning">
              <i class="bi bi-exclamation-triangle-fill me-1"></i>Nenhum técnico cadastrado.
              <a href="#" onclick="abrirCrudTecnicos();return false;">Cadastrar um técnico</a>
            </div>
            <?php endif; ?>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-semibold">Previsão de entrega</label>
            <?php $previsaoPadrao = !empty($os['data_previsao']) ? date('Y-m-d', strtotime($os['data_previsao'])) : date('Y-m-d', strtotime('+' . (int) ($diasPrevisaoPadrao ?? 5) . ' days')); ?>
            <input type="date" name="data_previsao" class="form-control" value="<?= e($previsaoPadrao) ?>">
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

    <!-- Resumo colapsável (mobile) — mesmo conteúdo do painel lateral, só aparece abaixo de 900px -->
    <details class="fx-wizard-resumo-mobile">
      <summary>Resumo da OS <i class="bi bi-chevron-down"></i></summary>
      <div class="fx-resumo-item"><dt>Cliente</dt><dd class="js-resumo-cliente vazio">a preencher</dd></div>
      <div class="fx-resumo-item"><dt>Equipamento</dt><dd class="js-resumo-equip vazio">a preencher</dd></div>
      <div class="fx-resumo-item"><dt>Defeito</dt><dd class="js-resumo-defeito vazio">a preencher</dd></div>
      <div class="fx-resumo-item"><dt>Prazo e valor</dt><dd class="js-resumo-prazo vazio">a preencher</dd></div>
    </details>

    <div class="os-nav">
      <button type="button" class="btn btn-outline-secondary" onclick="irParaStep(2)">
        <i class="bi bi-arrow-left me-1"></i>Anterior
      </button>
      <button type="submit" class="btn btn-primary btn-lg fw-semibold px-5" id="btnSalvarOS">
        <i class="bi bi-check-lg me-1"></i><?= $editando ? 'Salvar OS' : 'Abrir OS' ?>
      </button>
    </div>
  </div>

  </div><!-- /fx-wizard-main -->

  <aside class="fx-wizard-resumo" id="painelResumoOS">
    <div class="fx-resumo-titulo">Resumo da OS</div>
    <div class="fx-resumo-item"><dt>Cliente</dt><dd class="js-resumo-cliente vazio">a preencher</dd></div>
    <div class="fx-resumo-item"><dt>Equipamento</dt><dd class="js-resumo-equip vazio">a preencher</dd></div>
    <div class="fx-resumo-item"><dt>Defeito</dt><dd class="js-resumo-defeito vazio">a preencher</dd></div>
    <div class="fx-resumo-item"><dt>Prazo e valor</dt><dd class="js-resumo-prazo vazio">a preencher</dd></div>
    <div class="fx-resumo-alerta" id="resumoAlertaDuplicado" style="display:none"></div>
  </aside>

  </div><!-- /fx-wizard-layout -->

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
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <div>
          <h5 class="modal-title fw-bold">Equipamento</h5>
          <p class="text-muted small mb-0">Cliente: <strong id="equipClienteNome">--</strong></p>
        </div>
        <button type="button" class="btn-close" id="btnFecharEquip"></button>
      </div>

      <!-- Faixa de pareamento: substitui os dois botões antigos -->
      <div class="fx-equip-pareamento">
        <i class="bi bi-phone"></i>
        <div class="fx-equip-pareamento-texto">
          <div class="fx-equip-pareamento-titulo">Usar o celular para preencher</div>
          <div class="fx-equip-pareamento-sub">Lê a etiqueta e tira as fotos na mesma sessão</div>
        </div>
        <button type="button" class="fx-equip-pareamento-btn" onclick="abrirScannerCelular()">
          <i class="bi bi-qr-code-scan"></i> Parear
        </button>
      </div>

      <div class="modal-body pt-3">
        <div id="erroEquipamento" class="alert alert-danger py-2 small mb-3 d-none"></div>

        <!-- Tipo de equipamento: chips -->
        <div class="fx-equip-secao">
          <div class="fx-equip-secao-titulo">Tipo de equipamento *</div>
          <div class="fx-tipo-chips" id="tipoChips"></div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-md-6">
            <label class="form-label small fw-semibold d-flex justify-content-between align-items-center">
              Tipo de equipamento
              <button type="button" class="btn btn-link btn-sm p-0 text-muted" id="btnAdicionarTipo" onclick="abrirCrudTipos()" style="visibility:hidden">
                <i class="bi bi-plus-circle"></i> Adicionar
              </button>
            </label>
            <input type="text" id="eTipoFixo" class="form-control" placeholder="Selecione o tipo acima" readonly disabled>
            <select id="eTipoSelect" class="form-select sel-ex" onchange="selecionarTipoOutro(this.value)" style="display:none">
              <option value="">Selecione o tipo</option>
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
              <option value="">Selecione a marca</option>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Modelo</label>
            <input type="text" id="eModelo" class="form-control" placeholder="Digite o modelo do equipamento">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Número de série</label>
            <div class="fx-input-scan">
              <input type="text" id="eNumeroSerie" class="form-control" placeholder="Nº de série">
              <button type="button" class="fx-input-scan-btn" onclick="abrirScannerCelular()" title="Ler pela câmera do celular">
                <i class="bi bi-upc-scan"></i>
              </button>
            </div>
          </div>
          <div id="campoCor" style="display:none" class="col-md-6">
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
          <div id="campoImei" style="display:none" class="col-md-6">
            <label class="form-label small fw-semibold">IMEI (celulares/tablets)</label>
            <input type="text" id="eImei" class="form-control" placeholder="15 dígitos" maxlength="17">
            <div class="d-flex gap-2 mt-2">
              <button type="button" class="btn btn-outline-primary btn-sm flex-fill" id="btnBuscarImei" onclick="buscarPorImei()" title="Preencher marca/modelo e checar bloqueio" style="font-size:.95rem"><i class="bi bi-search"></i> Buscar</button>
              <button type="button" class="btn btn-sm flex-fill" id="btnAnatel" onclick="anatelImei()" style="background:#009640;border:none;color:#fff;font-size:.95rem" title="Valida os 15 dígitos, copia o IMEI e abre a consulta oficial da Anatel"><i class="bi bi-shield-check me-1"></i>Consulta Anatel</button>
            </div>
            <div id="imeiResultado" class="small mt-1"></div>
          </div>
          <div id="campoSenha" style="display:none" class="col-md-6">
            <label class="form-label small fw-semibold">Senha de desbloqueio</label>
            <input type="text" id="eSenha" class="form-control" placeholder="PIN, padrão, biometria...">
          </div>
          <div id="campoVoltagem" style="display:none" class="col-md-6">
            <label class="form-label small fw-semibold">Voltagem</label>
            <select id="eVoltagem" class="form-select">
              <option value="">Selecione</option>
              <option value="110v">110V</option>
              <option value="220v">220V</option>
              <option value="bivolt">Bivolt</option>
              <option value="bateria">Bateria</option>
              <option value="outro">Outro</option>
            </select>
          </div>
          <div class="col-12" id="campoInformatica" style="display:none">
            <label class="form-label small fw-semibold mb-2 d-block">
              <i class="bi bi-cpu me-1"></i>Configurações do equipamento
            </label>
            <div class="d-flex flex-wrap gap-3">
              <div style="flex:1 1 calc(50% - .5rem);min-width:200px">
                <label class="form-label small fw-semibold">Tipo de armazenamento</label>
                <input type="text" id="eTipoArmazenamento" class="form-control" placeholder="Ex: SSD 480GB, HD 1TB">
              </div>
              <div style="flex:1 1 calc(50% - .5rem);min-width:200px">
                <label class="form-label small fw-semibold">Quantidade de memória</label>
                <input type="text" id="eMemoriaRam" class="form-control" placeholder="Ex: 8GB DDR4">
              </div>
              <div style="flex:1 1 calc(50% - .5rem);min-width:200px">
                <label class="form-label small fw-semibold">Placa de vídeo</label>
                <input type="text" id="ePlacaVideo" class="form-control" placeholder="Ex: GTX 1650 4GB">
              </div>
              <div style="flex:1 1 calc(50% - .5rem);min-width:200px">
                <label class="form-label small fw-semibold">Placa mãe</label>
                <input type="text" id="ePlacaMae" class="form-control" placeholder="Ex: Asus B450M">
              </div>
              <div style="flex:1 1 calc(50% - .5rem);min-width:200px">
                <label class="form-label small fw-semibold">Processador</label>
                <input type="text" id="eProcessador" class="form-control" placeholder="Ex: Intel i5 10ª geração">
              </div>
            </div>
          </div>
        </div>

        <!-- Estado de entrada: checklist -->
        <div class="fx-equip-secao mt-4">
          <div class="fx-equip-secao-titulo">Estado de entrada</div>
          <div class="fx-estado-grid" id="estadoChecklist"></div>
          <textarea id="eEstadoObs" class="form-control mt-2" rows="2" placeholder="Outras observações do estado"></textarea>
        </div>

        <!-- ACESSÓRIOS -->
        <div class="fx-equip-secao mt-4">
          <div class="d-flex align-items-center justify-content-between mb-2">
            <div class="fx-equip-secao-titulo mb-0">
              Acessórios que acompanham <span class="fx-acessorios-contador" id="acessoriosContador"></span>
            </div>
            <a href="#" class="fx-link-secundario-sm" onclick="abrirCrudAcessorios();return false;">gerenciar lista</a>
          </div>
          <div class="fx-acessorio-chips" id="acessorioChips"></div>
          <div class="fx-acessorios-dica">Marque o que veio junto, ou marque "Sem acessórios"</div>
        </div>
      </div>
      <div class="modal-footer border-0 pt-0">
        <button type="button" class="btn btn-outline-secondary" id="btnVoltarCliente">
          <i class="bi bi-arrow-left"></i> Cliente
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

<!-- ═══ OFFCANVAS ACESSÓRIOS (catálogo — a exclusão do catálogo mora só aqui, nunca nos chips do form) ═══ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasAcessorios" style="width:340px">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold mb-0"><i class="bi bi-tools me-2 text-primary"></i>Acessórios</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="p-3 border-bottom bg-light">
      <input type="hidden" id="editAcessorioId">
      <div class="input-group">
        <input type="text" id="editAcessorioNome" class="form-control" placeholder="Nome do acessório..." maxlength="60"
          onkeydown="if(event.key==='Enter'){event.preventDefault();salvarAcessorioCrud()}">
        <button type="button" class="btn btn-primary" onclick="salvarAcessorioCrud()" id="btnSalvarAcessorioCrud"><i class="bi bi-check-lg"></i></button>
      </div>
      <button type="button" class="btn btn-link btn-sm p-0 text-muted mt-1 d-none" id="btnCancelarEditAcessorio" onclick="cancelarEditAcessorio()">
        <i class="bi bi-x me-1"></i>Cancelar
      </button>
      <div id="erroAcessorioCrud" class="text-danger small mt-1 d-none"></div>
    </div>
    <div id="listaAcessoriosContainer" style="overflow-y:auto;max-height:calc(100vh - 180px)"></div>
  </div>
</div>

<!-- ═══ OFFCANVAS TÉCNICOS ═══ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasTecnicos" style="width:360px">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold mb-0"><i class="bi bi-person-badge me-2 text-primary"></i>Técnicos</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body p-0">
    <div class="px-3 pt-2 small text-muted"><i class="bi bi-info-circle me-1"></i>Você também pode editar os técnicos em <strong>Configurações → Técnicos</strong>.</div>
    <div class="p-3 border-bottom bg-light">
      <input type="hidden" id="editTecId">
      <input type="text" id="editTecNome" class="form-control form-control-sm mb-2" placeholder="Nome do técnico *" maxlength="100"
        onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('editTecEmail').focus()}">
      <div class="input-group input-group-sm mb-2">
        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
        <input type="email" id="editTecEmail" class="form-control" placeholder="E-mail *" maxlength="100"
          onkeydown="if(event.key==='Enter'){event.preventDefault();document.getElementById('editTecTel').focus()}"
          onblur="buscarEmailTecnico()">
      </div>
      <div class="input-group input-group-sm">
        <span class="input-group-text"><i class="bi bi-telephone"></i></span>
        <input type="text" id="editTecTel" class="form-control" placeholder="Telefone *" maxlength="20"
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
const OS_URL      = '<?= url('/os/') ?>';
const ETAPA_LABELS = ['Cliente', 'Equipamento', 'Defeito', 'Prazo e valor'];

let modalCliente, modalEquip;
let clienteSelecionado = null;
let fClienteId, fEquipamentoId;
let stepAtual = 0;
let maiorStepAlcancado = <?= $editando ? 3 : 0 ?>;
let clientesCarregados = [];
let focoClienteIdx = -1;

// â”€â”€ Wizard de steps â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
// Mesmos critérios de atualizarChecklist() — etapa i é considerada preenchida de verdade.
function stepValido(i) {
  if (i === 0) return !!document.getElementById('fClienteId')?.value;
  if (i === 1) return !!document.getElementById('fEquipTipo')?.value;
  if (i === 2) return !!(document.querySelector('textarea[name="defeito_relatado"]')?.value.trim());
  return true;
}

function irParaStep(n) {
  // Bloqueia pular direto pra uma etapa ainda não alcançada (só permite ir 1 além do limite atual)
  if (n > maiorStepAlcancado + 1) return;

  // Os cabeçalhos das etapas (1/2/3/4 no topo) chamam irParaStep(N) direto, sem passar pelo
  // avancarStep() — que é o único lugar que checava se a etapa atual estava preenchida antes
  // de avançar. Sem isso, dava pra clicar direto em "Equipamento" (ou qualquer etapa seguinte
  // já alcançada antes) sem nunca ter selecionado um cliente. Reforça aqui, no único ponto por
  // onde toda navegação do wizard passa: barra em qualquer etapa anterior a n que não esteja
  // realmente preenchida, travando na primeira incompleta.
  for (let i = 0; i < n; i++) {
    if (!stepValido(i)) {
      if (i === 0) abrirModalCliente();
      else if (i === 1) abrirModalEquipamento();
      n = i;
      break;
    }
  }

  maiorStepAlcancado = Math.max(maiorStepAlcancado, n);

  document.querySelectorAll('.os-tab-pane').forEach((el, i) => {
    el.classList.toggle('active', i === n);
  });
  document.querySelectorAll('.os-step').forEach((el, i) => {
    el.classList.remove('active', 'done');
    const numEl = el.querySelector('.step-num');
    if (i === n) {
      el.classList.add('active');
      if (numEl) numEl.innerHTML = numEl.dataset.num;
    } else if (i < n) {
      el.classList.add('done');
      if (numEl) numEl.innerHTML = '<i class="bi bi-check-lg"></i>';
    } else if (numEl) {
      numEl.innerHTML = numEl.dataset.num;
    }
  });
  const etapaNum = document.getElementById('etapaAtualNum');
  if (etapaNum) etapaNum.textContent = n + 1;
  const etapaNumMob = document.getElementById('etapaAtualNumMobile');
  if (etapaNumMob) etapaNumMob.textContent = n + 1;
  const etapaLabelMob = document.getElementById('etapaAtualLabelMobile');
  if (etapaLabelMob) etapaLabelMob.textContent = ETAPA_LABELS[n] || '';
  document.querySelectorAll('#wizardProgresso span').forEach((el, i) => {
    el.classList.toggle('done', i < n);
    el.classList.toggle('active', i === n);
  });

  stepAtual = n;
  window.scrollTo({ top: 0, behavior: 'smooth' });
  sincronizarResumoLateral();

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

// ── Painel de resumo lateral (visível nas 4 etapas) ────────────────────
function setResumoCampo(chave, valor) {
  document.querySelectorAll('.js-resumo-' + chave).forEach(function(el) {
    if (valor) { el.textContent = valor; el.classList.remove('vazio'); }
    else { el.textContent = 'a preencher'; el.classList.add('vazio'); }
  });
}

function sincronizarResumoLateral() {
  setResumoCampo('cliente', clienteSelecionado?.nome || '');

  const tipoEquip   = document.getElementById('fEquipTipo')?.value || '';
  const marcaEquip  = document.getElementById('fEquipMarca')?.value || '';
  const modeloEquip = document.getElementById('fEquipModelo')?.value || '';
  const equip = [marcaEquip, modeloEquip, tipoEquip].filter(Boolean).join(' ');
  setResumoCampo('equip', equip);

  const defeito = document.querySelector('textarea[name="defeito_relatado"]')?.value.trim() || '';
  setResumoCampo('defeito', defeito.length > 60 ? defeito.slice(0, 60) + '…' : defeito);

  const previsao = document.querySelector('input[name="data_previsao"]')?.value || '';
  let prazoTxt = '';
  if (previsao) {
    // Campo é só data (yyyy-mm-dd) — parse manual evita o fuso horário deslocar o dia
    // (new Date('2026-08-04') interpretaria como meia-noite UTC).
    const partes = previsao.split('-');
    if (partes.length === 3) {
      prazoTxt = partes[2] + '/' + partes[1] + '/' + partes[0];
    }
  }
  setResumoCampo('prazo', prazoTxt);
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

  // Tipo: casa com uma das 4 categorias fixas (TV/Celular/Notebook/Linha branca); senão cai em "+ outro"
  const tipoSalvo = v('fEquipTipo');
  const chaveFixa = tipoSalvo && Object.keys(EQUIP_CATEGORIAS).find(k => EQUIP_CATEGORIAS[k].label.toLowerCase() === tipoSalvo.toLowerCase());
  mostrarCampoTipoOutro(!chaveFixa && !!tipoSalvo);
  document.getElementById('eTipoFixo').value = tipoSalvo || '';
  marcarChipTipo(chaveFixa || (tipoSalvo ? 'outro' : null));
  if (!chaveFixa && tipoSalvo) setSelectValue('eTipoSelect', tipoSalvo);
  categoriaAtual = chaveFixa || (tipoSalvo ? detectarCategoriaTipo(tipoSalvo) : null);
  tipoAtualNome  = tipoSalvo;

  setSelectValue('eMarcaSelect', v('fEquipMarca'));
  document.getElementById('eModelo').value      = v('fEquipModelo');
  document.getElementById('eNumeroSerie').value = v('fNumeroSerie');
  const _eImei = document.getElementById('eImei'); if (_eImei) _eImei.value = v('fImei');
  setSelectValue('eCor', v('fEquipCor'));
  setSelectValue('eVoltagem', v('fVoltagem'));
  document.getElementById('eSenha').value       = v('fSenha');
  document.getElementById('eTipoArmazenamento').value = v('fTipoArmazenamento');
  document.getElementById('eMemoriaRam').value        = v('fMemoriaRam');
  document.getElementById('ePlacaVideo').value        = v('fPlacaVideo');
  document.getElementById('ePlacaMae').value          = v('fPlacaMae');
  document.getElementById('eProcessador').value       = v('fProcessador');

  // Estado de entrada: casa o texto salvo com os itens do checklist da categoria;
  // o que não bater vira observação (preserva dados de registros salvos antes desta tela).
  const cfg = configCategoria(categoriaAtual);
  const salvos = v('fEstadoEntrada').split(',').map(s => s.trim()).filter(Boolean);
  const marcados = salvos.filter(s => cfg.estado.includes(s));
  const sobras = salvos.filter(s => !cfg.estado.includes(s));
  aplicarCategoriaCampos(categoriaAtual, marcados, false);
  document.getElementById('eEstadoObs').value = v('fEstadoObservacoes') || sobras.join(', ');

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
  sel.innerHTML = '<option value="">Selecione a marca</option>';
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

// ── Tipo de equipamento: chips + categorias (heurística por nome, sem tabela nova) ──
let tiposDados = [];
let offcanvasTipos;
let categoriaAtual = null;   // 'tv' | 'celular' | 'notebook' | 'linha_branca' | null (nenhuma categoria fixa reconhecida)
let tipoAtualNome  = '';     // texto que efetivamente vai pro campo "tipo" da OS

const EQUIP_CATEGORIAS = {
  tv: {
    label: 'TV', match: /\btv\b|televis/i,
    estado: ['Tela quebrada','Sem imagem','Sinais de umidade','Não liga'],
  },
  celular: {
    label: 'Celular', match: /celular|smartphone|iphone|tablet|ipad/i,
    estado: ['Tela trincada','Tampa traseira danificada','Entrada de carga com defeito','Botões travados','Caiu na água'],
  },
  notebook: {
    label: 'Notebook', match: /notebook|laptop|computador|desktop|\bpc\b|\bcpu\b|gamer/i,
    estado: ['Dobradiça solta','Teclas faltando','Bateria estufada','Tela trincada','Lacre violado'],
  },
  linha_branca: {
    label: 'Linha branca', match: /geladeira|fog[aã]o|lava.?lou|lava.?rou|m[aá]quina de lavar|micro-?ondas|freezer|ar.?condicionado|adega|secadora/i,
    estado: ['Amassados','Ferrugem','Vazamento','Não liga','Lacre violado'],
  },
};
const CATEGORIA_OUTRO = { label: 'Outro', estado: ['Riscos/arranhões','Peça faltando','Não liga','Lacre violado'] };

function detectarCategoriaTipo(nome) {
  const s = String(nome || '');
  for (const chave of ['tv','celular','notebook','linha_branca']) {
    if (EQUIP_CATEGORIAS[chave].match.test(s)) return chave;
  }
  return null;
}
function configCategoria(chave) { return (chave && EQUIP_CATEGORIAS[chave]) || CATEGORIA_OUTRO; }

function renderTipoChips() {
  const box = document.getElementById('tipoChips');
  if (!box) return;
  const chaves = ['tv','celular','notebook','linha_branca'];
  box.innerHTML = chaves.map(chave =>
    `<div class="fx-tipo-chip" data-chave="${chave}" onclick="selecionarTipoChip('${chave}')">${esc(EQUIP_CATEGORIAS[chave].label)}</div>`
  ).join('') + `<div class="fx-tipo-chip outro" data-chave="outro" onclick="selecionarTipoChip('outro')"><i class="bi bi-three-dots"></i> Outro</div>`;
}

function marcarChipTipo(chave) {
  document.querySelectorAll('.fx-tipo-chip').forEach(el => el.classList.toggle('selecionado', el.dataset.chave === chave));
}

// Um único campo "Tipo de equipamento": mostra o input somente-leitura pros chips fixos,
// ou o select editável (com "Adicionar") quando "+ outro" está ativo — nunca os dois juntos.
function mostrarCampoTipoOutro(mostrar) {
  document.getElementById('eTipoFixo').style.display = mostrar ? 'none' : '';
  document.getElementById('eTipoSelect').style.display = mostrar ? '' : 'none';
  // visibility (não display): mantém o espaço reservado pra não desalinhar com o rótulo da Marca
  document.getElementById('btnAdicionarTipo').style.visibility = mostrar ? 'visible' : 'hidden';
}

function selecionarTipoChip(chave) {
  marcarChipTipo(chave);
  if (chave === 'outro') {
    mostrarCampoTipoOutro(true);
    document.getElementById('eTipoSelect').focus();
    return;
  }
  mostrarCampoTipoOutro(false);
  categoriaAtual = chave;
  tipoAtualNome = EQUIP_CATEGORIAS[chave].label;
  document.getElementById('eTipoFixo').value = tipoAtualNome;
  aplicarCategoriaCampos(chave);
  carregarAcessoriosPadraoParaTipo(tipoAtualNome);
}

function selecionarTipoOutro(nome) {
  tipoAtualNome = nome;
  categoriaAtual = nome ? detectarCategoriaTipo(nome) : null;
  aplicarCategoriaCampos(categoriaAtual);
  if (nome) carregarAcessoriosPadraoParaTipo(nome);
}

// Mostra/esconde os campos técnicos, a voltagem e a especificação (tela/capacidade)
// conforme a categoria detectada, e troca o checklist de estado de entrada.
// `limpar=false` só na pré-carga (abrirModalEquipamento): evita apagar dado antigo
// de um equipamento já salvo só porque a categoria detectada não "precisa" dele.
function aplicarCategoriaCampos(chave, estadoPreexistente, limpar) {
  if (limpar === undefined) limpar = true;
  const cfg = configCategoria(chave);
  const ehCelular = chave === 'celular';
  const ehNotebook = chave === 'notebook';
  const ehLinhaBranca = chave === 'linha_branca';

  const campoSenha = document.getElementById('campoSenha');
  if (campoSenha) { campoSenha.style.display = ehCelular ? '' : 'none'; if (limpar && !ehCelular) document.getElementById('eSenha').value = ''; }
  const campoImei = document.getElementById('campoImei');
  if (campoImei) { campoImei.style.display = ehCelular ? '' : 'none'; if (limpar && !ehCelular) { const ei = document.getElementById('eImei'); if (ei) ei.value = ''; } }
  const campoCor = document.getElementById('campoCor');
  if (campoCor) { campoCor.style.display = ehCelular ? '' : 'none'; if (limpar && !ehCelular) { const ec = document.getElementById('eCor'); if (ec) ec.value = 'Cor neutra'; } }

  const campoInformatica = document.getElementById('campoInformatica');
  if (campoInformatica) {
    campoInformatica.style.display = ehNotebook ? '' : 'none';
    if (limpar && !ehNotebook) ['eTipoArmazenamento','eMemoriaRam','ePlacaVideo','ePlacaMae','eProcessador'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
  }

  const campoVoltagem = document.getElementById('campoVoltagem');
  if (campoVoltagem) { campoVoltagem.style.display = ehLinhaBranca ? '' : 'none'; if (limpar && !ehLinhaBranca) document.getElementById('eVoltagem').value = ''; }

  renderChecklistEstado(cfg.estado, estadoPreexistente);
}

// ── Estado de entrada: checklist de fatos verificáveis (substitui o dropdown Bom/Regular/Ruim) ──
function renderChecklistEstado(itens, marcadosPreexistentes) {
  const box = document.getElementById('estadoChecklist');
  if (!box) return;
  const marcados = marcadosPreexistentes || [];
  box.innerHTML = (itens || CATEGORIA_OUTRO.estado).map(item => {
    const on = marcados.includes(item);
    return `<div class="fx-estado-item${on ? ' marcado' : ''}" onclick="toggleEstadoItem(this)"><i class="bi ${on ? 'bi-check-square-fill' : 'bi-square'}"></i>${esc(item)}</div>`;
  }).join('');
}
function toggleEstadoItem(el) {
  const on = el.classList.toggle('marcado');
  el.querySelector('i').className = 'bi ' + (on ? 'bi-check-square-fill' : 'bi-square');
}
function itensEstadoMarcados() {
  return Array.from(document.querySelectorAll('#estadoChecklist .fx-estado-item.marcado')).map(el => el.textContent.trim());
}

async function carregarAcessoriosPadraoParaTipo(tipo) {
  if (!tipo) return;
  try {
    const r = await fetch(`<?= url('/api/equip/acessorios-padrao/') ?>${encodeURIComponent(tipo)}`);
    const j = await r.json();
    if (j.ids && j.ids.length && !semAcessoriosAtivo) {
      // Pré-selecionar acessórios que foram usados na última OS deste tipo
      j.ids.forEach(id => {
        const item = bancoDados.find(a => a.id == id);
        if (item && !ehSemAcessorios(item.nome) && !selecionados.find(s => s.id == id)) {
          selecionados.push(item);
        }
      });
      renderAcessorioChips();
      sincronizarHidden();
    }
  } catch(e) {}
}

// ── CRUD Tipos (catálogo por empresa) ───────────────────────────────────
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

function renderSelectTipo(){
  const sel=document.getElementById('eTipoSelect'); const val=sel.value;
  sel.innerHTML='<option value="">Selecione o tipo</option>';
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
    btnUsar.onclick=()=>{document.getElementById('eTipoSelect').value=t.nome;offcanvasTipos.hide();selecionarTipoOutro(t.nome);};
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
function getTipo(){return tipoAtualNome;}

// ── Acessórios: catálogo da empresa como chips que alternam (substitui o drag-and-drop) ──
let bancoDados=[], selecionados=[], semAcessoriosAtivo=false;

async function carregarBanco() {
  const r = await fetch(`${API_AUX}/equip_acessorios`);
  bancoDados = await r.json();
}

// "Sem acessórios" NÃO é um item do catálogo (equip_acessorios) — é um chip nativo,
// fixo na tela, pra nunca duplicar e nunca poder ser excluído/renomeado por engano.
function ehSemAcessorios(nome){return String(nome||'').trim().toLowerCase()==='sem acessórios';}

function renderAcessorioChips(){
  const box=document.getElementById('acessorioChips'); if(!box) return;
  const contador=document.getElementById('acessoriosContador');
  const qtd = selecionados.length + (semAcessoriosAtivo?1:0);
  if(contador) contador.textContent = qtd ? `(${qtd})` : '';
  const catalogo = bancoDados.filter(a=>!ehSemAcessorios(a.nome));
  box.innerHTML = catalogo.map(item=>{
    const on=!!selecionados.find(s=>s.id===item.id);
    return `<div class="fx-acessorio-chip${on?' marcado':''}" data-id="${item.id}" onclick="toggleAcessorio(${item.id})"><i class="bi bi-check-lg"></i>${esc(item.nome)}<i class="bi bi-trash3 fx-acessorio-del" title="Excluir do catálogo" onclick="event.stopPropagation();excluirAcessorioInline(${item.id})"></i></div>`;
  }).join('')
    + `<div class="fx-acessorio-chip${semAcessoriosAtivo?' marcado':''}" onclick="toggleSemAcessorios()"><i class="bi bi-check-lg"></i>Sem acessórios</div>`
    + `<div class="fx-acessorio-chip novo" id="chipNovoAcessorio" onclick="ativarNovoAcessorioChip()"><i class="bi bi-plus-lg"></i> Outro</div>`;
}

async function excluirAcessorioInline(id){
  if(!confirm('Excluir este acessório do catálogo? Ele vai sumir de todas as OS futuras.')) return;
  const r=await fetch(`${API_AUX}/equip_acessorios/${id}`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({_method:'DELETE',csrf_token:CSRF})});
  const j=await r.json();
  if(j.lista) bancoDados=j.lista;
  selecionados=selecionados.filter(s=>s.id!==id);
  renderAcessorioChips(); renderListaAcessorios(); sincronizarHidden();
}

// Marcar qualquer acessório real desliga "Sem acessórios" — são mutuamente exclusivos.
function toggleAcessorio(id){
  const item=bancoDados.find(a=>a.id===id); if(!item) return;
  if(selecionados.find(s=>s.id===id)) selecionados=selecionados.filter(s=>s.id!==id);
  else { selecionados.push(item); semAcessoriosAtivo=false; }
  renderAcessorioChips(); sincronizarHidden();
}

// Marcar "Sem acessórios" zera qualquer acessório real selecionado — são mutuamente exclusivos.
function toggleSemAcessorios(){
  semAcessoriosAtivo=!semAcessoriosAtivo;
  if(semAcessoriosAtivo) selecionados=[];
  renderAcessorioChips(); sincronizarHidden();
}

// Transforma o chip "+ Outro" num campo de texto inline (sem prompt() nativo — fica no lugar).
function ativarNovoAcessorioChip(){
  const chip=document.getElementById('chipNovoAcessorio'); if(!chip) return;
  chip.onclick=null; chip.innerHTML='';
  const input=document.createElement('input');
  input.type='text'; input.placeholder='Novo acessório...'; input.maxLength=60;
  input.style.cssText='border:none;background:none;outline:none;font-size:12.5px;width:110px;color:inherit';
  chip.appendChild(input); input.focus();
  const salvar=async()=>{
    const nome=input.value.trim();
    if(!nome || ehSemAcessorios(nome) || bancoDados.find(a=>a.nome.toLowerCase()===nome.toLowerCase())){ renderAcessorioChips(); return; }
    const r=await fetch(`${API_AUX}/equip_acessorios`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({nome,csrf_token:CSRF})});
    const j=await r.json();
    if(j.lista) bancoDados=j.lista;
    const item=bancoDados.find(a=>a.nome===nome);
    if(item) { selecionados.push(item); semAcessoriosAtivo=false; }
    renderAcessorioChips(); sincronizarHidden();
  };
  input.addEventListener('blur',salvar);
  input.addEventListener('keydown',e=>{ if(e.key==='Enter'){e.preventDefault();input.blur();} if(e.key==='Escape'){input.value='';input.blur();} });
}

function sincronizarHidden(){
  document.getElementById('fAcessorios').value = semAcessoriosAtivo ? 'Sem acessórios' : selecionados.map(s=>s.nome).join(', ');
}

async function inicializarAcessorios(){
  await carregarBanco();
  // Pré-carrega os acessórios já salvos (edição/reabertura) a partir do hidden fAcessorios
  const salvo=(document.getElementById('fAcessorios').value||'').trim();
  // Compat: "sem acessórios" e "recebido sem acessórios" (texto automático de versões anteriores).
  if (salvo && (ehSemAcessorios(salvo) || salvo.toLowerCase()==='recebido sem acessórios')) {
    semAcessoriosAtivo=true; selecionados=[];
    renderAcessorioChips();
    return;
  }
  semAcessoriosAtivo=false;
  const nomesSalvos = salvo ? salvo.split(',').map(s=>s.trim()).filter(Boolean) : [];
  selecionados=nomesSalvos.map((nome,i)=>{
    const item=bancoDados.find(a=>String(a.nome).toLowerCase()===nome.toLowerCase());
    return item?{id:item.id,nome:item.nome}:{id:'sav'+i,nome};
  }).filter(item=>!ehSemAcessorios(item.nome));
  renderAcessorioChips();
}

// ── CRUD Acessórios (catálogo por empresa — excluir do catálogo só mora aqui) ──
let offcanvasAcessorios;

async function abrirCrudAcessorios(){
  await carregarBanco();
  renderListaAcessorios();
  offcanvasAcessorios.show();
}

function renderListaAcessorios(){
  const cont=document.getElementById('listaAcessoriosContainer'); cont.innerHTML='';
  const itens=bancoDados.filter(a=>!ehSemAcessorios(a.nome));
  if(!itens.length){cont.innerHTML='<div class="text-center text-muted py-4 small">Nenhum acessório cadastrado.<br>Adicione acima.</div>';return;}
  itens.forEach(a=>{
    const li=document.createElement('div'); li.className='d-flex align-items-center gap-2 px-3 py-2 border-bottom'; li.style.cssText='font-size:.85rem;transition:background .1s';
    li.onmouseenter=()=>li.style.background='#f8f9fa'; li.onmouseleave=()=>li.style.background='';
    const nome=document.createElement('span'); nome.className='flex-grow-1'; nome.textContent=a.nome;
    const acoes=document.createElement('div'); acoes.className='d-flex gap-1';
    const btnEdit=document.createElement('button'); btnEdit.type='button'; btnEdit.className='btn btn-outline-secondary btn-sm py-0 px-2'; btnEdit.innerHTML='<i class="bi bi-pencil"></i>';
    btnEdit.onclick=()=>prepararEditAcessorio(a);
    const btnDel=document.createElement('button'); btnDel.type='button'; btnDel.className='btn btn-outline-danger btn-sm py-0 px-2'; btnDel.innerHTML='<i class="bi bi-trash3"></i>';
    btnDel.onclick=async()=>{ if(!confirm('Excluir este acessório do catálogo?'))return; const r=await fetch(`${API_AUX}/equip_acessorios/${a.id}`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({_method:'DELETE',csrf_token:CSRF})}); const j=await r.json(); bancoDados=j.lista??bancoDados; selecionados=selecionados.filter(s=>s.id!==a.id); renderListaAcessorios(); renderAcessorioChips(); sincronizarHidden(); };
    acoes.append(btnEdit,btnDel); li.append(nome,acoes); cont.appendChild(li);
  });
}

async function salvarAcessorioCrud(){
  const nome=document.getElementById('editAcessorioNome').value.trim(); const id=document.getElementById('editAcessorioId').value;
  const erroEl=document.getElementById('erroAcessorioCrud');
  erroEl.classList.add('d-none'); erroEl.textContent='';
  if(!nome){document.getElementById('editAcessorioNome').classList.add('is-invalid');document.getElementById('editAcessorioNome').focus();return;}
  document.getElementById('editAcessorioNome').classList.remove('is-invalid');
  const btn=document.getElementById('btnSalvarAcessorioCrud'); btn.disabled=true;
  try {
    const r=await fetch(`${API_AUX}/equip_acessorios`,{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':CSRF},body:JSON.stringify({id:id||undefined,nome,csrf_token:CSRF})});
    const j=await r.json();
    if(!r.ok || j.error || !j.lista){
      erroEl.textContent = j.error || `Erro ao salvar (HTTP ${r.status}). Tente de novo.`;
      erroEl.classList.remove('d-none');
      return;
    }
    bancoDados=j.lista;
    renderListaAcessorios(); renderAcessorioChips(); sincronizarHidden(); cancelarEditAcessorio();
  } catch(e) {
    erroEl.textContent='Erro de conexão. Tente de novo.';
    erroEl.classList.remove('d-none');
  } finally {
    btn.disabled=false;
  }
}
function prepararEditAcessorio(a){document.getElementById('editAcessorioId').value=a.id;document.getElementById('editAcessorioNome').value=a.nome;document.getElementById('editAcessorioNome').focus();document.getElementById('btnCancelarEditAcessorio').classList.remove('d-none');}
function cancelarEditAcessorio(){document.getElementById('editAcessorioId').value='';document.getElementById('editAcessorioNome').value='';document.getElementById('btnCancelarEditAcessorio').classList.add('d-none');document.getElementById('erroAcessorioCrud').classList.add('d-none');}

// â”€â”€ Helpers â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
function esc(s){return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');}
function escJs(s){return String(s||'').replace(/\\/g,'\\\\').replace(/'/g,"\\'");}
function iniciais(nome){const p=String(nome||'U').trim().split(' ');return((p[0]||'')[0]||'').toUpperCase()+((p.length>1?(p[p.length-1]||''):'')[0]||'').toUpperCase();}

// â”€â”€ Init (após Bootstrap carregar) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
window.addEventListener('load', function() {
  modalCliente = new bootstrap.Modal(document.getElementById('modalCliente'), { backdrop: 'static' });
  modalEquip   = new bootstrap.Modal(document.getElementById('modalEquipamento'), { backdrop: 'static' });
  offcanvasTipos      = new bootstrap.Offcanvas(document.getElementById('offcanvasTipos'));
  offcanvasMarcas     = new bootstrap.Offcanvas(document.getElementById('offcanvasMarcas'));
  offcanvasAcessorios = new bootstrap.Offcanvas(document.getElementById('offcanvasAcessorios'));
  renderTipoChips();

  // Carregar tipos e marcas do banco
  carregarTiposDB();
  carregarMarcasDB();

  document.getElementById('modalEquipamento').addEventListener('shown.bs.modal', async function() {
    await inicializarAcessorios();
    // Só sugere acessórios-padrão do tipo quando NÃO há acessórios salvos (evita poluir a edição)
    if (tipoAtualNome && selecionados.length === 0) carregarAcessoriosPadraoParaTipo(tipoAtualNome);
  });

  fClienteId     = document.getElementById('fClienteId');
  fEquipamentoId = document.getElementById('fEquipamentoId');

  <?php if (!$editando): ?>
  carregarClientesRecentes();
  iniciarBuscaClienteInline();
  document.getElementById('clienteBusca')?.focus();
  document.getElementById('linkCadastrarCliente')?.addEventListener('click', function(e) {
    e.preventDefault();
    abrirModalCliente();
    setTimeout(() => document.querySelector('[data-bs-target="#tabNovo"]')?.click(), 200);
  });
  <?php endif; ?>
  document.querySelector('textarea[name="defeito_relatado"]')?.addEventListener('input', sincronizarResumoLateral);
  document.querySelector('input[name="data_previsao"]')?.addEventListener('change', sincronizarResumoLateral);

  // Busca AJAX (modal legado — cadastro de cliente novo e trocar cliente)
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
    const mostrarErro=(msg)=>{ err.textContent=msg; err.classList.remove('d-none'); err.scrollIntoView({block:'center', behavior:'smooth'}); };
    if(!tipo){mostrarErro('Selecione o tipo do equipamento.');document.getElementById('tipoChips').scrollIntoView({block:'center'});return;}
    const marcaVal=getMarca();
    if(!marcaVal){mostrarErro('Selecione a marca do equipamento.');document.getElementById('eMarcaSelect').focus();return;}
    const modeloVal=document.getElementById('eModelo').value.trim();
    if(!modeloVal){mostrarErro('Informe o modelo do equipamento.');document.getElementById('eModelo').focus();return;}
    if(!selecionados.length && !semAcessoriosAtivo){mostrarErro('Marque os acessórios recebidos, ou marque "Sem acessórios".');return;}
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
    const itensMarcados=itensEstadoMarcados();
    document.getElementById('fEstadoEntrada').value=itensMarcados.length?itensMarcados.join(', '):'Sem avarias aparentes';
    document.getElementById('fEstadoObservacoes').value=document.getElementById('eEstadoObs').value.trim();
    document.getElementById('fSenha').value=document.getElementById('eSenha').value;
    document.getElementById('fTipoArmazenamento').value=document.getElementById('eTipoArmazenamento').value;
    document.getElementById('fMemoriaRam').value=document.getElementById('eMemoriaRam').value;
    document.getElementById('fPlacaVideo').value=document.getElementById('ePlacaVideo').value;
    document.getElementById('fPlacaMae').value=document.getElementById('ePlacaMae').value;
    document.getElementById('fProcessador').value=document.getElementById('eProcessador').value;
    sincronizarHidden();
    const modelo=document.getElementById('eModelo').value;
    const ns=document.getElementById('eNumeroSerie').value;
    document.getElementById('equipamentoResumo').innerHTML=`<div class="d-flex align-items-start gap-3"><div class="bg-info bg-opacity-10 text-info rounded-2 d-flex align-items-center justify-content-center" style="width:48px;height:48px;flex-shrink:0"><i class="bi bi-cpu fs-4"></i></div><div><div class="fw-semibold fs-6">${esc(marca)} ${esc(modelo)}</div><div class="text-muted">${esc(tipo)}</div>${ns?`<div class="small text-muted">S/N: ${esc(ns)}</div>`:''}</div></div>`;
    document.getElementById('btnEditarEquip').style.display='';

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

    sincronizarResumoLateral();
    perguntarFotoEntrada();
  });

  /** Depois de confirmar o equipamento: se ainda não tem foto de entrada anexada,
   *  pergunta se quer tirar agora (pelo celular). Senão, segue direto pra próxima etapa. */
  function perguntarFotoEntrada() {
    if ((fotosEntrada.length + fotosExistentesCount) > 0) { fecharEquipESeguir(); return; }
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFotoEntradaPergunta')).show();
  }
  function fecharEquipESeguir() {
    modalEquip.hide();
    setTimeout(()=>irParaStep(2),300);
  }
  document.getElementById('btnFotoEntradaNao').addEventListener('click', function(){
    bootstrap.Modal.getInstance(document.getElementById('modalFotoEntradaPergunta')).hide();
    fecharEquipESeguir();
  });
  document.getElementById('btnFotoEntradaSim').addEventListener('click', function(){
    bootstrap.Modal.getInstance(document.getElementById('modalFotoEntradaPergunta')).hide();
    setTimeout(abrirScannerFotosEntrada, 300);
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

    // Fotos do estado de entrada (já comprimidas/webp) vão junto no POST normal do formulário
    document.getElementById('fFotosEntrada').value = JSON.stringify(fotosEntrada);

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
  });

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
  sincronizarResumoLateral();
});

// ── Fotos do estado de entrada (comprimidas e convertidas pra webp no aparelho, ficam anexadas à OS) ──
// Fora do window.load de propósito: são chamadas por atributos onchange/onclick inline no HTML,
// que só enxergam o escopo global — dentro daquele closure elas ficavam inacessíveis (ReferenceError).
let fotosEntrada = [];
let fotosExistentesCount = <?= (int) count($fotosExistentes) ?>;
const SUPORTA_WEBP = (() => {
  const c = document.createElement('canvas'); c.width = c.height = 1;
  return c.toDataURL('image/webp').startsWith('data:image/webp');
})();

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
        resolve(SUPORTA_WEBP ? c.toDataURL('image/webp', 0.72) : c.toDataURL('image/jpeg', 0.6));
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
    if (fotosEntrada.length + fotosExistentesCount >= 6) { alert('Máximo de 6 fotos.'); break; }
    if (!f.type.startsWith('image/')) continue;
    fotosEntrada.push(await comprimirFoto(f));
  }
  renderFotosEntrada();
}

/** Exclui uma foto já salva no servidor (OS em edição) — chama o backend na hora, não espera o Salvar. */
async function removerFotoExistente(fotoId, btnEl) {
  if (!confirm('Excluir esta foto? Essa ação não pode ser desfeita.')) return;
  try {
    const r = await fetch('<?= url('/os/') ?>' + <?= $editando ? (int) $os['id'] : 0 ?> + '/fotos-entrada/' + fotoId + '/excluir', {
      method: 'POST',
      headers: { 'X-CSRF-Token': CSRF }
    });
    const j = await r.json();
    if (!j.ok) { alert(j.erro || 'Não foi possível excluir a foto.'); return; }
    btnEl.closest('[data-foto-id]').remove();
    fotosExistentesCount--;
    document.getElementById('fotosEntradaCount').textContent = fotosEntrada.length + fotosExistentesCount;
  } catch (e) {
    alert('Não foi possível excluir a foto agora.');
  }
}

function renderFotosEntrada() {
  const box = document.getElementById('prevFotosEntrada');
  box.innerHTML = fotosEntrada.map((d, i) => `
    <div style="position:relative">
      <img src="${d}" style="width:82px;height:82px;object-fit:cover;border-radius:8px;border:1px solid #dee2e6">
      <button type="button" onclick="removerFotoEntrada(${i})"
        style="position:absolute;top:-7px;right:-7px;background:#dc3545;color:#fff;border:none;border-radius:50%;width:22px;height:22px;line-height:20px;font-size:14px;padding:0">&times;</button>
    </div>`).join('');
  document.getElementById('fotosEntradaCount').textContent = fotosEntrada.length + fotosExistentesCount;
}

function removerFotoEntrada(i) { fotosEntrada.splice(i, 1); renderFotosEntrada(); }

// ── Render resultados busca ──────────────────────────────────────────
function renderResultados(list,q){
  const box=document.getElementById('resultadosBusca');
  if(!list.length){box.innerHTML=`<div class="text-center text-muted py-4"><i class="bi bi-person-x fs-2 d-block mb-2 opacity-30"></i><div>Nenhum cliente encontrado para <strong>"${esc(q)}"</strong></div><button class="btn btn-outline-primary btn-sm mt-2" onclick="document.querySelector('[data-bs-target=&quot;#tabNovo&quot;]').click();document.getElementById('ncNome').value='${escJs(q)}'"><i class="bi bi-person-plus me-1"></i> Cadastrar "${esc(q)}" como novo cliente</button></div>`;return;}
  box.innerHTML=list.map(c=>`<div class="d-flex align-items-center gap-3 p-3 border-bottom" style="cursor:pointer" onmouseenter="this.classList.add('bg-light')" onmouseleave="this.classList.remove('bg-light')" onclick="selecionarCliente(${c.id},'${escJs(c.nome)}','${escJs(c.telefone||'')}','${escJs(c.cpf_cnpj||'')}')"><div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" style="width:40px;height:40px;font-size:.8rem">${iniciais(c.nome)}</div><div class="flex-grow-1"><div class="fw-semibold">${esc(c.nome)}</div><div class="small text-muted">${[c.cpf_cnpj,c.telefone,c.email].filter(Boolean).map(esc).join(' · ')}</div></div><i class="bi bi-chevron-right text-muted"></i></div>`).join('');
}

function selecionarCliente(id,nome,tel,doc){clienteSelecionado={id,nome,tel,doc};confirmarClienteEAbrirEquip();}

function confirmarClienteEAbrirEquip(){
  fClienteId.value=clienteSelecionado.id;
  // Protege o cliente escolhido contra o Android recarregar a aba (ver window._salvarRascunhoOS).
  if (window._salvarRascunhoOS) {
    window._salvarRascunhoOS({ cliente_id: clienteSelecionado.id, cliente_nome: clienteSelecionado.nome || '', cliente_tel: clienteSelecionado.tel || '', cliente_doc: clienteSelecionado.doc || '' });
  }
  document.getElementById('clienteResumo').innerHTML=`<div class="d-flex align-items-center gap-3"><div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:48px;height:48px;font-size:1rem">${iniciais(clienteSelecionado.nome)}</div><div><div class="fw-semibold fs-6"><a href="<?= url('/clientes/') ?>${clienteSelecionado.id}/editar" target="_blank" class="text-reset text-decoration-none" title="Editar cliente">${esc(clienteSelecionado.nome)} <i class="bi bi-pencil-square small text-primary"></i></a></div><div class="text-muted small">${esc(clienteSelecionado.tel||'')} ${clienteSelecionado.doc?'· '+esc(clienteSelecionado.doc):''}</div></div><?= $editando ? '' : '<button type="button" class="btn btn-sm btn-outline-secondary ms-auto" onclick="abrirModalCliente()"><i class="bi bi-arrow-repeat"></i> Trocar</button>' ?></div>`;
  document.getElementById('badgeEtapa2')?.classList.replace('bg-secondary','bg-primary');
  modalCliente.hide();
  document.getElementById('equipClienteNome').textContent=clienteSelecionado.nome;
  habilitarContinuarStep0();
  sincronizarResumoLateral();
  setTimeout(()=>{modalEquip.show();},300);
  // Avançar para aba equipamento
  irParaStep(1);
}

// ── Passo 1 (Cliente): busca inline + lista de recentes ────────────────
function primeiroNome(nome) {
  return String(nome || '').trim().split(/\s+/)[0] || '';
}

function habilitarContinuarStep0() {
  const aviso = document.getElementById('continuarAviso0');
  const dica  = document.getElementById('continuarDica0');
  const btn   = document.getElementById('btnContinuar0');
  if (aviso) aviso.style.display = 'none';
  if (dica)  dica.style.display  = '';
  if (btn) { btn.disabled = false; btn.classList.add('ativo'); }
}

async function carregarClientesRecentes() {
  const box = document.getElementById('clienteLista');
  if (!box) return;
  box.innerHTML = '<div class="fx-cliente-vazio">Carregando...</div>';
  try {
    const r = await fetch(`${API_CL}/recentes`);
    clientesCarregados = await r.json();
  } catch (e) {
    clientesCarregados = [];
  }
  const label = document.getElementById('clienteListaLabel');
  if (label) label.textContent = clientesCarregados.length ? 'Atendidos recentemente' : 'Nenhum cliente cadastrado ainda';
  exibirListaClientes(clientesCarregados);
}

function exibirListaClientes(lista) {
  const box = document.getElementById('clienteLista');
  if (!box) return;
  focoClienteIdx = -1;
  if (!lista.length) {
    box.innerHTML = '<div class="fx-cliente-vazio">Nenhum cliente encontrado.</div>';
    return;
  }
  box.innerHTML = lista.map(renderClienteItem).join('');
}

function renderClienteItem(c, i) {
  const sub = [c.cpf_cnpj, c.telefone].filter(Boolean).join(' · ');
  const selecionado = clienteSelecionado && Number(clienteSelecionado.id) === Number(c.id);
  return `<div class="fx-cliente-item${selecionado ? ' selecionado' : ''}" data-idx="${i}" tabindex="0" onclick="selecionarClienteInline(${i})">
      <div class="fx-cliente-avatar">${iniciais(c.nome)}</div>
      <div class="fx-cliente-info">
        <span class="fx-cliente-nome">${esc(c.nome)}</span>
        <span class="fx-cliente-sub">${esc(sub || 'sem contato cadastrado')}</span>
      </div>
      <i class="bi bi-check-circle-fill fx-cliente-check"></i>
    </div>`;
}

function selecionarClienteInline(idx) {
  const c = clientesCarregados[idx];
  if (!c) return;
  clienteSelecionado = { id: c.id, nome: c.nome, tel: c.telefone || '', doc: c.cpf_cnpj || '' };
  fClienteId.value = c.id;
  document.querySelectorAll('#clienteLista .fx-cliente-item').forEach((el, i) => {
    el.classList.toggle('selecionado', i === idx);
  });
  habilitarContinuarStep0();
  sincronizarResumoLateral();
  verificarOsAbertaCliente(c.id, c.nome);
}

function destacarFocoCliente(itens) {
  itens.forEach((el, i) => el.classList.toggle('foco', i === focoClienteIdx));
  if (itens[focoClienteIdx]) itens[focoClienteIdx].scrollIntoView({ block: 'nearest' });
}

function iniciarBuscaClienteInline() {
  const campo = document.getElementById('clienteBusca');
  if (!campo) return;
  let timer;

  campo.addEventListener('input', function() {
    clearTimeout(timer);
    const q = this.value.trim();
    const label = document.getElementById('clienteListaLabel');
    if (!q) {
      if (label) label.textContent = 'Atendidos recentemente';
      exibirListaClientes(clientesCarregados);
      return;
    }
    if (label) label.textContent = 'Buscando...';
    timer = setTimeout(async () => {
      try {
        const r = await fetch(`${API_CL}?q=${encodeURIComponent(q)}`);
        const lista = await r.json();
        clientesCarregados = lista;
        if (label) label.textContent = lista.length ? 'Resultados da busca' : 'Nenhum cliente encontrado';
        exibirListaClientes(lista);
      } catch (e) {
        if (label) label.textContent = 'Erro ao buscar. Tente de novo.';
      }
    }, 300);
  });

  campo.addEventListener('keydown', function(e) {
    const itens = Array.from(document.querySelectorAll('#clienteLista .fx-cliente-item'));
    if (e.key === 'ArrowDown') {
      if (!itens.length) return;
      e.preventDefault();
      focoClienteIdx = Math.min(focoClienteIdx + 1, itens.length - 1);
      destacarFocoCliente(itens);
    } else if (e.key === 'ArrowUp') {
      if (!itens.length) return;
      e.preventDefault();
      focoClienteIdx = Math.max(focoClienteIdx - 1, 0);
      destacarFocoCliente(itens);
    } else if (e.key === 'Enter') {
      if (focoClienteIdx >= 0 && itens[focoClienteIdx]) {
        e.preventDefault();
        selecionarClienteInline(focoClienteIdx);
      } else if (fClienteId.value) {
        e.preventDefault();
        avancarStep(0);
      }
    } else if (e.key === 'Escape') {
      this.value = '';
      const label = document.getElementById('clienteListaLabel');
      if (label) label.textContent = 'Atendidos recentemente';
      exibirListaClientes(clientesCarregados);
    }
  });
}

async function verificarOsAbertaCliente(id, nome) {
  const alerta = document.getElementById('resumoAlertaDuplicado');
  if (!alerta) return;
  alerta.style.display = 'none';
  try {
    const r = await fetch(`${API_CL}/${id}/os-aberta`);
    const data = await r.json();
    if (data.os) {
      alerta.innerHTML = `<i class="bi bi-exclamation-triangle-fill me-1"></i>${esc(primeiroNome(nome))} já tem a OS <a href="${OS_URL}${data.os.id}" target="_blank">#${esc(data.os.numero)}</a> em aberto.`;
      alerta.style.display = 'block';
    }
  } catch (e) {
    // silencioso — não bloqueia o fluxo de criação
  }
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

/* UX: autosave de rascunho (texto + cliente + equipamento escaneado) — só em OS nova.
   O Android costuma matar/recarregar a aba em segundo plano quando o usuário sai pra
   tirar foto da etiqueta pela câmera do sistema (comum sob pressão de memória) — sem
   isso, o cliente selecionado e os dados lidos pela IA se perdiam silenciosamente, e a
   OS voltava pro formulário em branco. window._salvarRascunhoOS é usado também por
   preencherDoScanner() e confirmarClienteEAbrirEquip(), mais abaixo neste arquivo. */
(function(){
  var editando = <?= !empty($editando) ? 'true' : 'false' ?>;
  if(editando) return;
  var KEY='fixaos_os_rascunho', nomesTexto=['defeito_relatado','observacoes_cliente','observacoes_internas'];

  function lerRascunho(){ try{ return JSON.parse(localStorage.getItem(KEY)||'null'); }catch(e){ return null; } }
  function salvarRascunho(extra){
    var d = lerRascunho() || {};
    Object.assign(d, extra, {_t: Date.now()});
    try{ localStorage.setItem(KEY, JSON.stringify(d)); }catch(e){}
  }
  window._salvarRascunhoOS = salvarRascunho;

  function els(){ return nomesTexto.map(function(n){ return document.querySelector('[name="'+n+'"]'); }).filter(Boolean); }
  var campos=els();
  campos.forEach(function(el){
    el.addEventListener('input', function(){
      var d={}; campos.forEach(function(c){ d[c.name]=c.value; }); salvarRascunho(d);
    });
  });

  try{
    var d=lerRascunho();
    if(d && d._t && (Date.now()-d._t)<7*864e5){
      var temTexto  = nomesTexto.some(function(n){ return (d[n]||'').trim(); });
      var temCliente= !!d.cliente_id;
      var temEquip  = !!(d.equip_tipo || d.equip_marca || d.equip_modelo);
      var vazios    = campos.every(function(el){ return !el.value.trim(); }) && !document.getElementById('fClienteId').value;
      if((temTexto || temCliente || temEquip) && vazios){
        var b=document.createElement('div');
        b.className='alert alert-warning d-flex align-items-center gap-2 py-2 mb-3';
        var msg = (temCliente || temEquip)
          ? 'Você tem um <strong>rascunho de OS não salva</strong> — inclusive cliente/equipamento já preenchidos — deseja restaurar de onde parou?'
          : 'Você tem um <strong>rascunho de OS não salva</strong> — deseja restaurar o texto digitado?';
        b.innerHTML='<i class="bi bi-clock-history"></i><span class="small flex-grow-1">'+msg+'</span>';
        var ok=document.createElement('button'); ok.type='button'; ok.className='btn btn-sm btn-warning'; ok.innerHTML='<i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar';
        var no=document.createElement('button'); no.type='button'; no.className='btn btn-sm btn-outline-secondary'; no.textContent='Descartar';
        ok.onclick=function(){
          campos.forEach(function(el){ if(d[el.name]!=null) el.value=d[el.name]; });
          if(temCliente){
            selecionarCliente(d.cliente_id, d.cliente_nome||'', d.cliente_tel||'', d.cliente_doc||'');
            if(temEquip){
              // Espera o modal de equipamento abrir (disparado por confirmarClienteEAbrirEquip)
              // antes de preencher os campos — mesma função usada pelo scanner de etiqueta.
              setTimeout(function(){
                preencherDoScanner({tipo:d.equip_tipo||'', marca:d.equip_marca||'', modelo:d.equip_modelo||'', serie:d.equip_serie||''});
              }, 500);
            }
          }
          b.remove();
        };
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
      info.innerHTML='<div class="small fw-semibold">'+esc(t.nome)+'</div>'+(t.email?'<div class="text-muted" style="font-size:.72rem"><i class="bi bi-envelope me-1"></i>'+esc(t.email)+'</div>':'')+(t.telefone?'<div class="text-muted" style="font-size:.72rem"><i class="bi bi-telephone me-1"></i>'+esc(t.telefone)+'</div>':'');
      var acoes=document.createElement('div'); acoes.className='d-flex gap-1 flex-shrink-0';
      var usar=document.createElement('button'); usar.type='button'; usar.className='btn btn-outline-success btn-sm py-0 px-2'; usar.title='Usar nesta OS'; usar.innerHTML='<i class="bi bi-check-lg"></i>';
      usar.onclick=function(){ document.getElementById('selTecnico').value=t.id; if(oc) oc.hide(); };
      var edit=document.createElement('button'); edit.type='button'; edit.className='btn btn-outline-secondary btn-sm py-0 px-2'; edit.innerHTML='<i class="bi bi-pencil"></i>';
      edit.onclick=function(){ document.getElementById('editTecId').value=t.id; document.getElementById('editTecNome').value=t.nome; document.getElementById('editTecEmail').value=t.email||''; document.getElementById('editTecTel').value=t.telefone||''; document.getElementById('editTecNome').focus(); document.getElementById('btnCancelarEditTec').classList.remove('d-none'); };
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
  window.buscarEmailTecnico=async function(){
    var idField=document.getElementById('editTecId'), email=document.getElementById('editTecEmail'), msg=document.getElementById('tecMsg');
    if(idField.value) return; // editando um técnico existente: não sobrescreve
    var v=email.value.trim();
    if(!v) return;
    try{
      var r=await fetch('<?= url('/api/tecnicos/buscar-email') ?>?email='+encodeURIComponent(v));
      var j=await r.json();
      if(j.encontrado){
        var nome=document.getElementById('editTecNome'), tel=document.getElementById('editTecTel');
        if(!nome.value.trim()) nome.value=j.nome||'';
        if(!tel.value.trim())  tel.value=j.telefone||'';
        msg.textContent='E-mail já cadastrado — preenchemos nome e telefone com os dados existentes.';
        msg.className='form-text text-warning';
      }
    }catch(e){}
  };
  window.salvarTecnico=async function(){
    var nome=document.getElementById('editTecNome').value.trim(), email=document.getElementById('editTecEmail').value.trim(), tel=document.getElementById('editTecTel').value.trim(), id=document.getElementById('editTecId').value, msg=document.getElementById('tecMsg');
    if(!nome){ msg.textContent='Informe o nome do técnico.'; msg.className='form-text text-danger'; return; }
    if(!email){ msg.textContent='Informe o e-mail do técnico.'; msg.className='form-text text-danger'; return; }
    if(!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){ msg.textContent='E-mail inválido.'; msg.className='form-text text-danger'; return; }
    if(!tel){ msg.textContent='Informe o telefone do técnico.'; msg.className='form-text text-danger'; return; }
    msg.textContent='';
    var url = id ? (API_TEC+'/'+id) : API_TEC;
    try{
      var r=await fetch(url,{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:new URLSearchParams({_token:CSRF,nome:nome,email:email,telefone:tel})});
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
    document.getElementById('editTecEmail').value='';
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
<!-- Captura direta: quando o próprio aparelho (mobile) tem câmera, não precisa parear com outro celular -->
<input type="file" id="inputCameraDireta" accept="image/*" capture="environment" class="d-none">
<div class="modal fade" id="modalCameraDireta" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered modal-sm">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <div class="spinner-border text-primary mb-2"></div>
        <div class="small text-muted">Lendo a etiqueta…</div>
      </div>
    </div>
  </div>
</div>
<!-- Fotos WhatsApp direto: quando o próprio aparelho (mobile) tem câmera -->
<div class="modal fade" id="modalFotosDireta" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0"><i class="bi bi-whatsapp me-1"></i>Fotografar equipamento</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p class="small text-muted mb-2">Tire até 10 fotos do estado de entrada. As fotos não ficam no sistema — vão direto pro WhatsApp da empresa e do cliente.</p>
        <label class="btn btn-outline-primary w-100 mb-2" for="inputFotosDireta"><i class="bi bi-camera-fill me-1"></i>Adicionar foto</label>
        <input id="inputFotosDireta" type="file" accept="image/*" capture="environment" multiple class="d-none">
        <div id="miniaturasFotosDireta" class="d-flex flex-wrap gap-2 mb-1"></div>
        <div class="text-end small text-muted"><span id="contagemFotosDireta">0</span>/10</div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-success w-100" id="btnEnviarFotosDireta" disabled>
          <i class="bi bi-check-lg"></i> Enviar pro WhatsApp
        </button>
      </div>
    </div>
  </div>
</div>
<!-- Pergunta se quer tirar foto do estado de entrada, ao confirmar o equipamento -->
<div class="modal fade" id="modalFotoEntradaPergunta" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body text-center py-4">
        <i class="bi bi-camera text-primary" style="font-size:2.2rem"></i>
        <h6 class="mt-2 mb-1">Tirar foto do equipamento na entrada?</h6>
        <p class="small text-muted mb-0">Registra o estado do aparelho (riscos, trincas etc.) como comprovação, direto pelo celular.</p>
      </div>
      <div class="modal-footer justify-content-center border-0 pt-0 pb-4">
        <button type="button" id="btnFotoEntradaNao" class="btn btn-outline-secondary">Não, continuar</button>
        <button type="button" id="btnFotoEntradaSim" class="btn btn-primary"><i class="bi bi-camera me-1"></i>Sim, tirar foto</button>
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
          <label class="form-label small fw-semibold mb-1">Tipo</label>
          <div id="confTipoView" class="fs-5 fw-semibold"></div>
          <input type="text" id="confTipoInput" class="form-control d-none">
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold mb-1">Marca</label>
          <div id="confMarcaView" class="fs-5 fw-semibold"></div>
          <input type="text" id="confMarcaInput" class="form-control d-none">
        </div>
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
const _osIdAtual = <?= (int) ($os['id'] ?? 0) ?>; // 0 = OS ainda não existe (está sendo criada agora)

// Se o próprio aparelho tem câmera (celular/tablet), não faz sentido pedir pra parear com
// "outro celular" via QR — usa a câmera daqui mesmo. Detecção: touch + tela estreita.
function temCameraPropria(){
  return ('ontouchstart' in window || navigator.maxTouchPoints > 0) && window.innerWidth <= 991;
}
function abrirCameraDireta(){
  document.getElementById('inputCameraDireta').click();
}
document.getElementById('inputCameraDireta').addEventListener('change', async function(){
  const arquivo = this.files && this.files[0];
  this.value = '';
  if (!arquivo) return;

  const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('modalCameraDireta'));
  modal.show();
  try{
    const fd = new FormData();
    fd.append('foto', arquivo);
    fd.append('_token', '<?= csrf_token() ?>');
    const r = await fetch('<?= url('/scanner/ler-etiqueta') ?>', { method:'POST', body: fd });
    const j = await r.json();
    modal.hide();
    if (j.ok) {
      mostrarConfirmacaoScanner(j);
    } else {
      alert(j.erro || 'Não consegui ler a etiqueta. Tente de novo.');
    }
  }catch(e){
    modal.hide();
    alert('Erro ao enviar a foto. Confira sua conexão e tente de novo.');
  }
});

// Fotografar equipamento (múltiplas fotos) direto pelo próprio aparelho, sem QR/pareamento.
var _fotosDireta = [];
function _comprimirFotoDireta(file){
  return new Promise(function(resolve){
    var reader = new FileReader();
    reader.onload = function(e){
      var img = new Image();
      img.onload = function(){
        var max = 1280, w = img.width, h = img.height;
        if (w > h && w > max) { h = Math.round(h * max / w); w = max; }
        else if (h >= w && h > max) { w = Math.round(w * max / h); h = max; }
        var c = document.createElement('canvas');
        c.width = w; c.height = h;
        var ctx = c.getContext('2d');
        ctx.fillStyle = '#fff'; ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        resolve(c.toDataURL('image/jpeg', 0.55));
      };
      img.src = e.target.result;
    };
    reader.readAsDataURL(file);
  });
}
function _renderFotosDireta(){
  var box = document.getElementById('miniaturasFotosDireta');
  var btn = document.getElementById('btnEnviarFotosDireta');
  box.innerHTML = _fotosDireta.map(function(d, i){
    return '<div class="position-relative">' +
      '<img src="' + d + '" style="width:74px;height:74px;object-fit:cover;border-radius:10px;border:1px solid #e2e8f0">' +
      '<button type="button" data-i="' + i + '" class="btn-close bg-danger position-absolute" style="top:-6px;right:-6px;width:16px;height:16px;padding:0;border-radius:50%;opacity:1" aria-label="Remover"></button>' +
      '</div>';
  }).join('');
  document.getElementById('contagemFotosDireta').textContent = _fotosDireta.length;
  btn.disabled = _fotosDireta.length === 0;
  box.querySelectorAll('button[data-i]').forEach(function(b){
    b.addEventListener('click', function(){ _fotosDireta.splice(+b.dataset.i, 1); _renderFotosDireta(); });
  });
}
document.getElementById('inputFotosDireta').addEventListener('change', async function(){
  var files = [].slice.call(this.files);
  this.value = '';
  for (var i = 0; i < files.length; i++){
    if (_fotosDireta.length >= 10) { alert('Máximo de 10 fotos.'); break; }
    if (!files[i].type.startsWith('image/')) continue;
    _fotosDireta.push(await _comprimirFotoDireta(files[i]));
  }
  _renderFotosDireta();
});
function abrirFotosDireta(){
  _fotosDireta = [];
  _renderFotosDireta();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalFotosDireta')).show();
}
document.getElementById('btnEnviarFotosDireta').addEventListener('click', async function(){
  var btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Enviando...';
  try{
    var r, j;
    if (_osIdAtual > 0) {
      // OS já existe: salva as fotos no servidor (aparecem no link de acompanhamento) e
      // manda só 1 mensagem de texto pro cliente, em vez de uma mensagem por foto.
      r = await fetch('<?= url('/os/') ?>' + _osIdAtual + '/fotos-entrada', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
        body: JSON.stringify({ fotos: _fotosDireta })
      });
      j = await r.json();
      if (j.ok) {
        bootstrap.Modal.getInstance(document.getElementById('modalFotosDireta')).hide();
        alert('Fotos salvas! O cliente recebeu o link de acompanhamento com as fotos.');
      } else {
        alert(j.erro || 'Não foi possível salvar. Tente de novo.');
      }
    } else {
      // OS ainda não existe (está sendo criada agora): sem onde salvar as fotos ainda,
      // então manda direto por WhatsApp como antes.
      var equip = (document.getElementById('fEquipMarca').value + ' ' + document.getElementById('fEquipModelo').value).trim()
                  || document.getElementById('fEquipTipo').value;
      r = await fetch('<?= url('/scanner/fotos-whatsapp-direto') ?>', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': '<?= csrf_token() ?>' },
        body: JSON.stringify({ fotos: _fotosDireta, cliente_id: fClienteId.value || '', equipamento: equip || '' })
      });
      j = await r.json();
      if (j.ok) {
        bootstrap.Modal.getInstance(document.getElementById('modalFotosDireta')).hide();
        alert('Fotos enviadas pelo WhatsApp!');
      } else {
        alert(j.erro || 'Não foi possível enviar. Tente de novo.');
      }
    }
  }catch(e){
    alert('Falha de conexão. Tente de novo.');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-check-lg"></i> Enviar pro WhatsApp';
  }
});

async function abrirScannerCelular(modo){
  _scanModo = modo || 'equipamento';
  if (temCameraPropria()) {
    if (_scanModo === 'equipamento') { return abrirCameraDireta(); }
    if (_scanModo === 'fotos_whatsapp') { return abrirFotosDireta(); }
    // fotos_entrada: já está no celular — sem pareamento, usa o mesmo seletor do botão "Adicionar foto".
    if (_scanModo === 'fotos_entrada') { return document.getElementById('inputFotosEntrada').click(); }
  }
  const modalEl = document.getElementById('modalScanner');
  const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  document.querySelector('#modalScanner .modal-title').innerHTML = _scanModo === 'fotos_whatsapp'
    ? '<i class="bi bi-whatsapp me-1"></i>Fotografar equipamento'
    : _scanModo === 'fotos_entrada'
    ? '<i class="bi bi-camera-fill me-1"></i>Fotos do estado de entrada'
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
function abrirScannerFotosEntrada(){ abrirScannerCelular('fotos_entrada'); }
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
      } else if (_scanModo === 'fotos_entrada') {
        const recebidas = (j.resultado.fotos || []).slice(0, 6 - fotosEntrada.length - fotosExistentesCount);
        fotosEntrada.push(...recebidas);
        renderFotosEntrada();
        document.getElementById('scannerStatus').innerHTML = '<span class="text-success fw-semibold">✅ '+recebidas.length+' foto(s) recebida(s)!</span>';
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
  // tipo: casa com uma das 4 categorias fixas (chip); senão cai no select "+ outro" (cria a opção se não existir)
  const setTipo = (val)=>{
    if(!val) return;
    const chaveFixa = Object.keys(EQUIP_CATEGORIAS).find(k => EQUIP_CATEGORIAS[k].label.toLowerCase() === val.toLowerCase());
    if (chaveFixa) {
      selecionarTipoChip(chaveFixa);
      flash(document.querySelector(`.fx-tipo-chip[data-chave="${chaveFixa}"]`));
      return;
    }
    const s=document.getElementById('eTipoSelect'); if(!s) return;
    let opt=[...s.options].find(o=>o.textContent.trim().toLowerCase()===val.toLowerCase() || o.value.toLowerCase()===val.toLowerCase());
    if(!opt){ opt=document.createElement('option'); opt.value=val; opt.textContent=val; s.appendChild(opt); }
    s.value=opt.value;
    selecionarTipoChip('outro');
    selecionarTipoOutro(s.value);
    flash(s);
  };
  setTipo(d.tipo);
  setMarca(d.marca);
  setInp('eModelo', d.modelo);
  setInp('eNumeroSerie', d.serie);
  // Protege contra o Android recarregar a aba logo depois de tirar a foto da etiqueta
  // (ver comentário em window._salvarRascunhoOS) — salva assim que a IA preenche, antes
  // mesmo do usuário clicar em "Confirmar equipamento".
  if (window._salvarRascunhoOS) {
    window._salvarRascunhoOS({ equip_tipo: d.tipo || '', equip_marca: d.marca || '', equip_modelo: d.modelo || '', equip_serie: d.serie || '' });
  }
}

let _scanDadosPendentes = null;
const _CONF_CAMPOS = ['tipo', 'marca', 'modelo', 'serie'];
function mostrarConfirmacaoScanner(d){
  _scanDadosPendentes = d;
  _CONF_CAMPOS.forEach(campo => {
    const view = document.getElementById('conf' + campo[0].toUpperCase() + campo.slice(1) + 'View');
    const inp  = document.getElementById('conf' + campo[0].toUpperCase() + campo.slice(1) + 'Input');
    view.textContent = d[campo] || '(vazio)';
    inp.value = d[campo] || '';
    view.classList.remove('d-none');
    inp.classList.add('d-none');
  });
  document.getElementById('btnEditarConf').classList.remove('d-none');
  document.getElementById('btnCorretoConf').innerHTML = '<i class="bi bi-check-lg"></i> Está correto';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalConfirmarScan')).show();
}
document.getElementById('btnEditarConf').addEventListener('click', function(){
  _CONF_CAMPOS.forEach(campo => {
    document.getElementById('conf' + campo[0].toUpperCase() + campo.slice(1) + 'View').classList.add('d-none');
    document.getElementById('conf' + campo[0].toUpperCase() + campo.slice(1) + 'Input').classList.remove('d-none');
  });
  this.classList.add('d-none');
  document.getElementById('btnCorretoConf').innerHTML = '<i class="bi bi-check-lg"></i> Salvar e confirmar';
  document.getElementById('confTipoInput').focus();
});
document.getElementById('btnCorretoConf').addEventListener('click', function(){
  const editando = !document.getElementById('confModeloInput').classList.contains('d-none');
  const dadosFinais = Object.assign({}, _scanDadosPendentes);
  _CONF_CAMPOS.forEach(campo => {
    dadosFinais[campo] = editando
      ? document.getElementById('conf' + campo[0].toUpperCase() + campo.slice(1) + 'Input').value.trim()
      : (_scanDadosPendentes[campo] || '');
  });
  bootstrap.Modal.getInstance(document.getElementById('modalConfirmarScan')).hide();
  preencherDoScanner(dadosFinais);
});
</script>
