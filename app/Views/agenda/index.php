<?php
use App\Enums\TipoEvento;
use App\Enums\StatusEvento;

$meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$diasSemanaExt = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$mesPrev = $mes == 1 ? 12 : $mes - 1; $anoPrev = $mes == 1 ? $ano - 1 : $ano;
$mesProx = $mes == 12 ? 1 : $mes + 1; $anoProx = $mes == 12 ? $ano + 1 : $ano;

// Subtítulo "hoje é ..." ao lado do título do mês.
$hojeExtenso = $diasSemanaExt[(int) date('w')] . ', ' . (int) date('j') . ' de '
             . mb_strtolower($meses[(int) date('n')]) . ' de ' . date('Y');

// Monta a querystring preservando mes/ano/view — só sobrescreve o que for passado.
// Usado nas setas, no "Hoje" e no seletor de visão, pra visão/mês sobreviverem juntos ao link.
$qs = function (array $overrides = []) use ($mes, $ano, $view) {
    return '?' . http_build_query(array_merge(['mes' => $mes, 'ano' => $ano, 'view' => $view], $overrides));
};

$visoes = ['mes' => 'Mês', 'semana' => 'Semana', 'dia' => 'Dia', 'tecnicos' => 'Técnicos'];
?>
<style>
.agenda-tb {
  position: sticky; top: var(--app-topbar-height, 64px); z-index: 15;
  background: var(--surface-1, #fff); border-bottom: 1px solid var(--border, #dee2e6);
  padding: .65rem 0; margin: 0 0 1rem;
}
.agenda-tb-row { display: flex; align-items: center; gap: .6rem; }
.agenda-tb-titulo { flex-shrink: 0; margin-right: .25rem; }
.agenda-tb-titulo h5 { margin: 0; font-size: 1.05rem; font-weight: 700; white-space: nowrap; color: var(--text-1, #1a1d23); }
.agenda-tb-titulo small { display: block; color: var(--text-3, #6c757d); font-size: .72rem; white-space: nowrap; }
.agenda-tb-nav { display: flex; align-items: center; gap: .35rem; flex-shrink: 0; }

.agenda-tb-visao { display: flex; border: 1px solid var(--border-strong, #ced4da); border-radius: 8px; overflow: hidden; flex-shrink: 0; }
.agenda-tb-visao a {
  padding: .32rem .7rem; font-size: .8rem; font-weight: 600; color: var(--text-2, #495057);
  text-decoration: none; border-right: 1px solid var(--border-strong, #ced4da); white-space: nowrap;
}
.agenda-tb-visao a:last-child { border-right: none; }
.agenda-tb-visao a.ativo { background: var(--accent, #0d6efd); color: #fff; }
.agenda-tb-visao a:hover:not(.ativo) { background: var(--surface-2, #f1f3f5); }

.agenda-tb-busca { flex: 1 1 160px; min-width: 120px; max-width: 220px; }
.agenda-tb-novo { flex-shrink: 0; margin-left: auto; white-space: nowrap; }

.agenda-tb-visao-select, .agenda-tb-busca-btn, .agenda-tb-busca-expandida { display: none; }

@media (max-width: 767.98px) {
  .agenda-tb-row { flex-wrap: wrap; row-gap: .5rem; }
  .agenda-tb-titulo { flex: 1 1 100%; order: 1; margin-right: 0; }
  .agenda-tb-nav { order: 2; }
  .agenda-tb-visao { display: none; }
  .agenda-tb-visao-select { display: block; order: 2; flex: 1 1 auto; max-width: 150px; }
  .agenda-tb-busca { display: none; }
  .agenda-tb-busca-btn { display: inline-flex; order: 3; }
  .agenda-tb-novo { order: 3; flex: 1 1 auto; margin-left: 0; justify-content: center; }
  .agenda-tb-busca-expandida { order: 4; flex: 1 1 100%; display: none; }
  .agenda-tb-busca-expandida.mostrar { display: block; }
}

/* Grade mensal */
.ag-grade { width: 100%; table-layout: fixed; border-collapse: collapse; }
.ag-grade th {
  padding: .5rem .25rem; font-size: .72rem; font-weight: 600; text-align: center;
  color: var(--text-3, #6c757d); border-bottom: 1px solid var(--border, #dee2e6);
}
.ag-cel {
  height: 76px; max-height: 76px; overflow: hidden; vertical-align: top;
  padding: .25rem .3rem; border: 1px solid var(--border, #dee2e6);
  background: var(--surface-0, #fff); cursor: pointer; position: relative;
}
.ag-cel:hover { background: var(--surface-2, #f1f3f5); }
.ag-cel:focus-visible { outline: 2px solid var(--accent, #0d6efd); outline-offset: -2px; }
.ag-cel-fora { background: var(--surface-1, #f8f9fa); }
.ag-cel-fora:hover { background: var(--surface-2, #f1f3f5); }
.ag-cel-fora .ag-cel-numero { color: var(--text-3, #adb5bd); }
.ag-cel-hoje { background: var(--accent-soft, rgba(13,110,253,.08)); }
.ag-cel-hoje:hover { background: var(--accent-soft, rgba(13,110,253,.14)); }

.ag-cel-topo { display: flex; align-items: center; justify-content: space-between; margin-bottom: .2rem; }
.ag-cel-numero {
  font-size: .78rem; font-weight: 600; color: var(--text-2, #495057);
  min-width: 20px; height: 20px; display: inline-flex; align-items: center; justify-content: center;
}
.ag-cel-numero-hoje {
  background: var(--accent, #0d6efd); color: #fff; border-radius: 50%;
}
.ag-feriado { color: #dc3545; font-size: .55rem; line-height: 1; }

.ag-cel-eventos { display: flex; flex-direction: column; gap: 2px; }
.ag-pill {
  display: block; font-size: .68rem; line-height: 1.35; padding: 1px 5px; border-radius: 4px;
  white-space: nowrap; overflow: hidden; text-overflow: ellipsis; text-decoration: none;
  background: var(--ag-pill-bg-light); color: var(--ag-pill-fg-light); border: none; text-align: left;
  width: 100%;
}
[data-theme="dark"] .ag-pill { background: var(--ag-pill-bg-dark); color: var(--ag-pill-fg-dark); }
.ag-pill i { font-size: .6rem; margin-right: 1px; }
.ag-pill-hora { font-weight: 600; margin-right: 2px; }
.ag-pill-mais {
  font-size: .66rem; font-weight: 600; color: var(--text-3, #6c757d); background: transparent;
  border: none; padding: 0 3px; text-align: left; width: 100%;
}
.ag-pill-mais:hover { color: var(--accent, #0d6efd); text-decoration: underline; }
.ag-pop-ev { font-size: .78rem; padding: .15rem 0; white-space: nowrap; }
.ag-pop-ev + .ag-pop-ev { border-top: 1px solid var(--border, #dee2e6); }
.ag-pop-ev .ag-pop-hora { font-weight: 600; margin-right: .3rem; }
</style>

<div class="agenda-tb" id="agendaToolbar">
  <div class="agenda-tb-row">

    <div class="agenda-tb-titulo">
      <h5><?= $meses[$mes] ?> <?= $ano ?></h5>
      <small>Hoje é <?= $hojeExtenso ?></small>
    </div>

    <div class="agenda-tb-nav">
      <a href="<?= e($qs(['mes' => $mesPrev, 'ano' => $anoPrev])) ?>" class="btn btn-outline-secondary btn-sm" aria-label="Mês anterior"><i class="bi bi-chevron-left"></i></a>
      <a href="<?= e($qs(['mes' => $mesProx, 'ano' => $anoProx])) ?>" class="btn btn-outline-secondary btn-sm" aria-label="Próximo mês"><i class="bi bi-chevron-right"></i></a>
      <a href="<?= e($qs(['mes' => date('m'), 'ano' => date('Y')])) ?>" class="btn btn-outline-primary btn-sm">Hoje</a>
    </div>

    <div class="agenda-tb-visao" role="group" aria-label="Visão do calendário">
      <?php foreach ($visoes as $v => $rotulo): ?>
      <a href="<?= e($qs(['view' => $v])) ?>" class="<?= $view === $v ? 'ativo' : '' ?>"><?= $rotulo ?></a>
      <?php endforeach; ?>
    </div>
    <select class="form-select form-select-sm agenda-tb-visao-select" aria-label="Visão do calendário"
            onchange="location.href=this.value">
      <?php foreach ($visoes as $v => $rotulo): ?>
      <option value="<?= e($qs(['view' => $v])) ?>" <?= $view === $v ? 'selected' : '' ?>><?= $rotulo ?></option>
      <?php endforeach; ?>
    </select>

    <div class="agenda-tb-busca">
      <div class="input-group input-group-sm">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
        <input type="search" id="agendaBuscaInput" class="form-control border-start-0" placeholder="Buscar evento...">
      </div>
    </div>
    <button type="button" class="btn btn-outline-secondary btn-sm agenda-tb-busca-btn" id="agendaBuscaBtn"
            aria-label="Buscar evento" title="Buscar evento">
      <i class="bi bi-search"></i>
    </button>

    <button class="btn btn-primary btn-sm agenda-tb-novo" data-bs-toggle="modal" data-bs-target="#modalEvento">
      <i class="bi bi-plus-lg me-1"></i>Novo evento
    </button>

  </div>

  <div class="agenda-tb-busca-expandida" id="agendaBuscaExpandida">
    <div class="input-group input-group-sm mt-2">
      <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
      <input type="search" id="agendaBuscaInputMobile" class="form-control border-start-0" placeholder="Buscar evento...">
    </div>
  </div>
</div>

<?php if ($view !== 'mes'):
    $placeholders = [
        'semana'   => ['icone' => 'bi-calendar-week', 'titulo' => 'Visão Semana'],
        'dia'      => ['icone' => 'bi-calendar-day',  'titulo' => 'Visão Dia'],
        'tecnicos' => ['icone' => 'bi-people',         'titulo' => 'Visão Técnicos'],
    ];
    $ph = $placeholders[$view];
?>
<div class="card border-0 shadow-sm">
  <div class="card-body text-center text-muted py-5">
    <i class="<?= $ph['icone'] ?>" style="font-size:2.5rem;opacity:.3"></i>
    <h5 class="mt-3 mb-1"><?= $ph['titulo'] ?></h5>
    <p class="mb-0">Ainda não implementada — use a visão <a href="<?= e($qs(['view' => 'mes'])) ?>">Mês</a> por enquanto.</p>
  </div>
</div>
<?php else: ?>

<?php
/*
 * Grade mensal — <table> semântica (cabeçalho de dia da semana em <th scope="col">,
 * cada dia em <td role="gridcell"> com aria-label completo e navegação por setas
 * via roving tabindex, ver script no fim da view).
 */
$primeiroDia = mktime(0, 0, 0, $mes, 1, $ano);
$diasNoMes   = (int) date('t', $primeiroDia);
$diaSemana1  = (int) date('w', $primeiroDia);
$diasNoMesPrev = (int) date('t', mktime(0, 0, 0, $mesPrev, 1, $anoPrev));

$eventosMap = [];
foreach ($eventos as $ev) {
    $eventosMap[(int) date('j', strtotime($ev['data_inicio']))][] = $ev;
}
// Ordena cada dia com "atrasado" primeiro (prioridade de exibição), depois por horário.
foreach ($eventosMap as &$doDia) {
    usort($doDia, function ($a, $b) {
        $atrasoA = ($a['status'] ?? '') === 'atrasado';
        $atrasoB = ($b['status'] ?? '') === 'atrasado';
        if ($atrasoA !== $atrasoB) return $atrasoA ? -1 : 1;
        return strcmp($a['data_inicio'], $b['data_inicio']);
    });
}
unset($doDia);

$feriados = feriados_nacionais_brasil($ano);
if ($mesPrev === 12) $feriados += feriados_nacionais_brasil($anoPrev);
if ($mesProx === 1)  $feriados += feriados_nacionais_brasil($anoProx);

$hojeDia = (int) date('j'); $hojeMes = (int) date('n'); $hojeAno = (int) date('Y');

// Monta as células em sequência (mês anterior + mês atual + mês seguinte) e depois
// fatia de 7 em 7 pra montar as linhas — evita cálculo de virada de semana duplicado.
$celulas = [];
for ($c = $diaSemana1 - 1; $c >= 0; $c--) {
    $celulas[] = ['dia' => $diasNoMesPrev - $c, 'mes' => $mesPrev, 'ano' => $anoPrev, 'foraDoMes' => true];
}
for ($d = 1; $d <= $diasNoMes; $d++) {
    $celulas[] = ['dia' => $d, 'mes' => $mes, 'ano' => $ano, 'foraDoMes' => false];
}
$diaProx = 1;
while (count($celulas) % 7 !== 0) {
    $celulas[] = ['dia' => $diaProx, 'mes' => $mesProx, 'ano' => $anoProx, 'foraDoMes' => true];
    $diaProx++;
}
$semanas = array_chunk($celulas, 7);

$primeiraCelHoje = null;
foreach ($celulas as $idx => $c) {
    if (!$c['foraDoMes'] && $c['dia'] === $hojeDia && $mes === $hojeMes && $ano === $hojeAno) { $primeiraCelHoje = $idx; break; }
}
?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-0">
    <table class="ag-grade" role="grid" aria-label="Calendário de <?= $meses[$mes] ?> de <?= $ano ?>">
      <thead>
        <tr>
          <?php foreach (array_combine(['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'], $diasSemanaExt) as $abrev => $ext): ?>
          <th scope="col" abbr="<?= e($ext) ?>"><?= $abrev ?></th>
          <?php endforeach; ?>
        </tr>
      </thead>
      <tbody>
        <?php $idxGlobal = -1; ?>
        <?php foreach ($semanas as $semana): ?>
        <tr>
          <?php foreach ($semana as $c): $idxGlobal++;
            $isHoje    = !$c['foraDoMes'] && $c['dia'] === $hojeDia && $c['mes'] === $hojeMes && $c['ano'] === $hojeAno;
            $dataIso   = sprintf('%04d-%02d-%02d', $c['ano'], $c['mes'], $c['dia']);
            $feriado   = $feriados[$dataIso] ?? null;
            $doDia     = $c['foraDoMes'] ? [] : ($eventosMap[$c['dia']] ?? []);
            $qtd       = count($doDia);
            $visiveis  = array_slice($doDia, 0, 2);
            $excedente = $qtd - count($visiveis);

            $rotuloDia = $c['dia'] . ' de ' . mb_strtolower($meses[$c['mes']]);
            $ariaPartes = [$rotuloDia];
            if ($isHoje) $ariaPartes[] = 'hoje';
            if ($feriado) $ariaPartes[] = 'feriado: ' . $feriado;
            $ariaPartes[] = $qtd === 0 ? 'nenhum evento' : ($qtd === 1 ? '1 evento' : "$qtd eventos");
            $ariaLabel = implode(', ', $ariaPartes);

            $tabindex = ($primeiraCelHoje !== null ? $idxGlobal === $primeiraCelHoje : $idxGlobal === 0) ? '0' : '-1';
          ?>
          <td class="ag-cel<?= $c['foraDoMes'] ? ' ag-cel-fora' : '' ?><?= $isHoje ? ' ag-cel-hoje' : '' ?>"
              role="gridcell" tabindex="<?= $tabindex ?>" data-data="<?= $dataIso ?>"
              aria-label="<?= e($ariaLabel) ?>"
              onclick="agendaCelClick(event, '<?= $dataIso ?>')"
              onkeydown="agendaCelKeydown(event, '<?= $dataIso ?>')">
            <div class="ag-cel-topo">
              <span class="ag-cel-numero<?= $isHoje ? ' ag-cel-numero-hoje' : '' ?>"><?= $c['dia'] ?></span>
              <?php if ($feriado): ?><span class="ag-feriado" title="Feriado: <?= e($feriado) ?>"><i class="bi bi-star-fill"></i></span><?php endif; ?>
            </div>
            <div class="ag-cel-eventos">
              <?php foreach ($visiveis as $ev):
                $atrasado = ($ev['status'] ?? '') === 'atrasado';
                if ($atrasado) {
                    $corCfg = StatusEvento::Atrasado->config();
                } else {
                    $corCfg = (TipoEvento::tryFrom($ev['tipo'] ?? '') ?? TipoEvento::Outro)->config();
                }
                $hora = date('H:i', strtotime($ev['data_inicio']));
              ?>
              <button type="button" class="ag-pill"
                      style="--ag-pill-bg-light:<?= e($corCfg['light']['pill_bg']) ?>; --ag-pill-fg-light:<?= e($corCfg['light']['pill_texto']) ?>; --ag-pill-bg-dark:<?= e($corCfg['dark']['pill_bg']) ?>; --ag-pill-fg-dark:<?= e($corCfg['dark']['pill_texto']) ?>;"
                      title="<?= e($hora . ' ' . $ev['titulo']) ?>"
                      onclick="event.stopPropagation(); editarEvento(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
                <?php if (!empty($ev['recorrente'])): ?><i class="bi bi-arrow-repeat"></i><?php endif; ?>
                <span class="ag-pill-hora"><?= $hora ?></span><?= e($ev['titulo']) ?>
              </button>
              <?php endforeach; ?>
              <?php if ($excedente > 0): ?>
              <button type="button" class="ag-pill-mais" tabindex="-1" data-bs-toggle="popover" data-bs-trigger="focus"
                      data-bs-html="true" data-bs-placement="auto"
                      data-bs-title="<?= e($rotuloDia) ?>"
                      data-bs-content="<?php foreach ($doDia as $ev2):
                          $hora2 = date('H:i', strtotime($ev2['data_inicio']));
                          echo '<div class=&quot;ag-pop-ev&quot;><span class=&quot;ag-pop-hora&quot;>' . e($hora2) . '</span>' . e($ev2['titulo']) . '</div>';
                      endforeach; ?>"
                      onclick="event.stopPropagation()">+<?= $excedente ?> mais</button>
              <?php endif; ?>
            </div>
          </td>
          <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Lista de eventos do mês -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold">Eventos de <?= $meses[$mes] ?></div>
  <div class="table-responsive">
    <table class="table table-hover mb-0 small align-middle" id="agendaTabelaEventos">
      <thead class="table-light"><tr><th>Data/Hora</th><th>Título</th><th>Tipo</th><th>Responsável</th><th>Cliente</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($eventos as $ev): ?>
        <tr data-titulo="<?= e(mb_strtolower($ev['titulo'])) ?>">
          <td><?= date_br($ev['data_inicio'], true) ?></td>
          <td class="fw-semibold"><?= e($ev['titulo']) ?></td>
          <?php $tipoEv = TipoEvento::tryFrom($ev['tipo'] ?? '') ?? TipoEvento::Outro; ?>
          <td><span class="badge bg-secondary"><?= e($tipoEv->rotulo()) ?></span></td>
          <td><?= e($ev['usuario_nome'] ?? '—') ?></td>
          <td><?= e($ev['cliente_nome'] ?? '—') ?></td>
          <td>
            <?php $sm=['agendado'=>'primary','confirmado'=>'success','em_andamento'=>'warning','cancelado'=>'danger','concluido'=>'secondary','atrasado'=>'danger']; ?>
            <span class="badge bg-<?= $sm[$ev['status']] ?? 'secondary' ?>"><?= ucfirst(str_replace('_', ' ', $ev['status'])) ?></span>
          </td>
          <td>
            <div class="d-flex gap-1">
              <button type="button" class="btn btn-sm btn-outline-primary"
                onclick="editarEvento(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
                <i class="bi bi-pencil"></i>
              </button>
              <a href="#" class="btn btn-sm btn-outline-danger" data-method="DELETE"
                 data-href="<?= url('/agenda/' . $ev['id']) ?>"
                 data-confirm="Remover este evento?"><i class="bi bi-trash"></i></a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$eventos): ?><tr id="agendaSemEventos"><td colspan="7" class="text-center text-muted py-4">Nenhum evento neste mês.</td></tr><?php endif; ?>
        <tr id="agendaSemResultadoBusca" class="d-none"><td colspan="7" class="text-center text-muted py-4">Nenhum evento encontrado com esse termo.</td></tr>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<!-- Modal novo/editar evento -->
<div class="modal fade" id="modalEvento" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formEvento" action="<?= url('/agenda') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="evento_id" id="fEventoId" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEventoTitulo">Novo evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-semibold">Título *</label>
            <input type="text" name="titulo" class="form-control" required>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Tipo</label>
            <select name="tipo" class="form-select">
              <?php foreach (TipoEvento::cases() as $t): ?>
              <option value="<?= $t->value ?>"><?= e($t->rotulo()) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Responsável</label>
            <select name="usuario_id" class="form-select">
              <?php foreach ($usuarios as $u): ?>
              <option value="<?= $u['id'] ?>"><?= e($u['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Início *</label>
            <input type="datetime-local" name="data_inicio" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Fim</label>
            <input type="datetime-local" name="data_fim" class="form-control">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Cor</label>
            <input type="color" name="cor" class="form-control form-control-color" value="#0d6efd">
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Descrição</label>
            <textarea name="descricao" class="form-control" rows="2"></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" id="btnSalvarEvento">Salvar</button>
      </div>
    </form>
  </div>
</div>

<script>
function editarEvento(ev) {
  var form = document.getElementById('formEvento');

  // Título do modal
  document.getElementById('modalEventoTitulo').textContent = 'Editar evento';
  document.getElementById('btnSalvarEvento').textContent = 'Atualizar';

  // Configurar form para edição
  document.getElementById('fEventoId').value = ev.id;

  // Preencher campos
  form.querySelector('[name=titulo]').value      = ev.titulo || '';
  form.querySelector('[name=tipo]').value        = ev.tipo   || 'outro';
  form.querySelector('[name=usuario_id]').value  = ev.usuario_id || '';
  form.querySelector('[name=descricao]').value   = ev.descricao || '';
  form.querySelector('[name=cor]').value         = ev.cor || '#0d6efd';

  // Formatar datetime para datetime-local (YYYY-MM-DDTHH:MM)
  if (ev.data_inicio) {
    form.querySelector('[name=data_inicio]').value = ev.data_inicio.replace(' ', 'T').substring(0, 16);
  }
  if (ev.data_fim) {
    form.querySelector('[name=data_fim]').value = ev.data_fim.replace(' ', 'T').substring(0, 16);
  } else {
    form.querySelector('[name=data_fim]').value = '';
  }

  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEvento')).show();
}

// Resetar modal ao fechar
document.getElementById('modalEvento').addEventListener('hidden.bs.modal', function() {
  document.getElementById('modalEventoTitulo').textContent = 'Novo evento';
  document.getElementById('btnSalvarEvento').textContent   = 'Salvar';
  document.getElementById('fEventoId').value = '';
  document.getElementById('formEvento').reset();
  document.querySelector('[name=data_inicio]').value = '<?= date('Y-m-d\TH:i') ?>';
});

// Busca (filtra a lista "Eventos do mês" pelo título) — some ícone <-> campo no mobile,
// os dois campos (desktop/mobile) ficam sincronizados.
(function () {
  var tabela = document.getElementById('agendaTabelaEventos');
  var inpDesktop = document.getElementById('agendaBuscaInput');
  var inpMobile  = document.getElementById('agendaBuscaInputMobile');
  var btnBusca   = document.getElementById('agendaBuscaBtn');
  var buscaExp   = document.getElementById('agendaBuscaExpandida');
  var semResultado = document.getElementById('agendaSemResultadoBusca');

  function filtrar(termo) {
    if (!tabela) return;
    termo = (termo || '').trim().toLowerCase();
    var linhas = tabela.querySelectorAll('tbody tr[data-titulo]');
    var visiveis = 0;
    linhas.forEach(function (tr) {
      var bate = !termo || tr.dataset.titulo.indexOf(termo) !== -1;
      tr.style.display = bate ? '' : 'none';
      if (bate) visiveis++;
    });
    if (semResultado) semResultado.classList.toggle('d-none', !termo || visiveis > 0);
  }

  [inpDesktop, inpMobile].forEach(function (inp) {
    if (!inp) return;
    inp.addEventListener('input', function () {
      if (inpDesktop && inp !== inpDesktop) inpDesktop.value = inp.value;
      if (inpMobile && inp !== inpMobile) inpMobile.value = inp.value;
      filtrar(inp.value);
    });
  });

  if (btnBusca && buscaExp) {
    btnBusca.addEventListener('click', function () {
      buscaExp.classList.toggle('mostrar');
      if (buscaExp.classList.contains('mostrar') && inpMobile) inpMobile.focus();
    });
  }
})();

// Grade mensal: clique em área vazia abre criação rápida com a data preenchida;
// setas do teclado navegam entre os dias (roving tabindex — só um <td> por vez
// tem tabindex="0", ver aria-label/role="gridcell" no markup).
function abrirCriacaoRapida(dataIso) {
  var form = document.getElementById('formEvento');
  form.querySelector('[name=data_inicio]').value = dataIso + 'T09:00';
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEvento')).show();
}

function agendaCelClick(event, dataIso) {
  if (event.target !== event.currentTarget) return; // clique veio de um pill/botão filho, ignora
  abrirCriacaoRapida(dataIso);
}

function agendaCelKeydown(event, dataIso) {
  var cel = event.currentTarget;
  if (event.target !== cel) return; // teclado veio de um pill/botão filho, ignora

  if (event.key === 'Enter' || event.key === ' ') {
    event.preventDefault();
    abrirCriacaoRapida(dataIso);
    return;
  }

  var deltas = { ArrowLeft: -1, ArrowRight: 1, ArrowUp: -7, ArrowDown: 7 };
  var celulas = Array.prototype.slice.call(document.querySelectorAll('.ag-grade .ag-cel'));
  var atual = celulas.indexOf(cel);
  var alvo = null;

  if (event.key in deltas) alvo = atual + deltas[event.key];
  else if (event.key === 'Home') alvo = 0;
  else if (event.key === 'End') alvo = celulas.length - 1;
  else return;

  if (alvo < 0 || alvo >= celulas.length) return;

  event.preventDefault();
  celulas[atual].setAttribute('tabindex', '-1');
  celulas[alvo].setAttribute('tabindex', '0');
  celulas[alvo].focus();
}

// Popover "+N mais" com a lista completa de eventos do dia
document.querySelectorAll('.ag-pill-mais').forEach(function (el) {
  bootstrap.Popover.getOrCreateInstance(el);
});
</script>
