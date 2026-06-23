<!-- Totais por status + garantia -->
<div class="d-flex gap-2 flex-wrap mb-3">
  <?php foreach ($totais as $s): ?>
  <a href="?status_id=<?= $s['id'] ?>"
     class="badge text-decoration-none fs-6 <?= $filtros['status_id'] == $s['id'] ? 'opacity-100' : 'opacity-75' ?>"
     style="background:<?= e($s['cor']) ?>">
    <?= e($s['nome']) ?> (<?= $s['total'] ?>)
  </a>
  <?php endforeach; ?>

  <?php if (!empty($totalGarantia)): ?>
  <a href="?em_garantia=1"
     class="badge text-decoration-none fs-6 <?= !empty($filtros['em_garantia']) ? 'opacity-100' : 'opacity-75' ?>"
     style="background:#dc3545">
    <i class="bi bi-shield-check me-1"></i>Em Garantia (<?= $totalGarantia ?>)
  </a>
  <?php endif; ?>

  <?php if ($filtros['status_id'] || !empty($filtros['em_garantia'])): ?>
  <a href="?" class="badge bg-secondary text-decoration-none fs-6"><i class="bi bi-x"></i> Limpar</a>
  <?php endif; ?>
</div>

<!-- Filtros -->
<form class="card border-0 shadow-sm p-3 mb-3" method="GET">
  <div class="row g-2 align-items-end">
    <div class="col-md-4">
      <input type="search" name="busca" class="form-control" placeholder="Nº OS, cliente, equipamento, série..." value="<?= e($filtros['busca']) ?>">
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
  </div>
</form>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover mb-0 align-middle small">
      <thead class="table-light">
        <tr>
          <th>Nº</th><th>Cliente</th><th>Equipamento</th><th>Técnico</th><th>Prioridade</th><th>Status</th><th>Previsão</th><th>Valor</th><th></th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($paginator['data'] as $os):
          $atrasada   = $os['data_previsao'] && strtotime($os['data_previsao']) < time() && !in_array($os['status_tipo'],['concluida','entregue','cancelada']);
          $ehGarantia = $os['tipo_servico'] === 'garantia' && !empty($os['os_origem_id']);
        ?>
        <tr class="<?= $atrasada ? 'table-danger' : '' ?>">
          <td>
            <a href="<?= url('/os/' . $os['id']) ?>" class="fw-bold text-decoration-none">OS: <?= e($os['numero']) ?></a>
            <?php if ($atrasada): ?><i class="bi bi-alarm text-danger ms-1" title="Atrasada"></i><?php endif; ?>
            <?php if ($ehGarantia): ?><span class="badge bg-danger ms-1" style="font-size:.65rem"><i class="bi bi-shield-check"></i> Garantia</span><?php endif; ?>
          </td>
          <td>
            <?= e($os['cliente_nome']) ?>
            <?php if ($os['cliente_whats']): ?>
            <a href="https://wa.me/55<?= only_numbers($os['cliente_whats']) ?>" target="_blank" class="text-success ms-1"><i class="bi bi-whatsapp"></i></a>
            <?php endif; ?>
          </td>
          <td><?= e(trim(($os['equip_marca']??'').' '.($os['equip_modelo']??''))) ?><br><span class="text-muted"><?= e($os['equip_tipo']??'') ?></span></td>
          <td><?= e($os['tecnico_nome'] ?? '—') ?></td>
          <td><span class="badge badge-prioridade-<?= $os['prioridade'] ?>"><?= ucfirst($os['prioridade']) ?></span></td>
          <td>
            <?= badge_status_os($os['status_tipo'], $os['status_nome'], $os['status_cor'] ?? '') ?>
          </td>
          <td><?= date_br($os['data_previsao'], true) ?: '—' ?></td>
          <td><?= money($os['valor_total']) ?></td>
          <td class="text-end">
            <a href="<?= url('/os/' . $os['id']) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
            <a href="<?= url('/os/' . $os['id'] . '/imprimir') ?>" class="btn btn-sm btn-outline-secondary" target="_blank"><i class="bi bi-printer"></i></a>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$paginator['data']): ?>
        <tr><td colspan="9" class="text-center text-muted py-5">Nenhuma OS encontrada.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
  <?php if ($paginator['last_page'] > 1): ?>
  <div class="card-footer d-flex justify-content-between align-items-center">
    <small class="text-muted"><?= $paginator['total'] ?> OS</small>
    <?= pagination($paginator, url('/os')) ?>
  </div>
  <?php endif; ?>
</div>

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
            <!-- Carrega automaticamente ao abrir -->
            <div class="text-center text-muted py-4" id="msgGarantia">
              <i class="bi bi-shield-check fs-2 d-block mb-2 opacity-30"></i>
              Aguardando busca...
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
                  <select name="tecnico_id" id="gTecnico" class="form-select">
                    <option value="">— Mesmo técnico —</option>
                    <?php foreach ($tecnicos ?? [] as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= e($t['nome']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label small fw-semibold">Acessórios que acompanham</label>
                  <input type="text" name="acessorios" id="gAcessorios" class="form-control"
                    placeholder="Carregador, controle remoto, cabo HDMI...">
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

<script>
let osGarantiaSelecionada = null;
let timerGarantia;

// Carrega todas ao abrir o modal
document.getElementById('modalEntradaGarantia').addEventListener('shown.bs.modal', function() {
  buscarOsGarantia('');
  document.getElementById('buscaGarantia').focus();
});

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
  document.getElementById('formConfirmarGarantia').submit();
}

// Reset ao fechar
document.getElementById('modalEntradaGarantia').addEventListener('hidden.bs.modal', function() {
  osGarantiaSelecionada = null;
  irParaPasso(1);
  document.getElementById('buscaGarantia').value = '';
  document.getElementById('motivoRetorno').value = '';
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
</script>

