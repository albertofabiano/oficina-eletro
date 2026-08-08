<?php
use App\Enums\TipoEvento;

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

<!-- Calendário simples -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-0">
    <div class="row g-0 text-center">
      <?php foreach (['Dom','Seg','Ter','Qua','Qui','Sex','Sáb'] as $d): ?>
      <div class="col border-bottom py-2 small fw-semibold text-muted"><?= $d ?></div>
      <?php endforeach; ?>
    </div>
    <?php
    $primeiroDia = mktime(0,0,0,$mes,1,$ano);
    $diasNoMes   = date('t', $primeiroDia);
    $diaSemana   = (int) date('w', $primeiroDia);
    $eventosMap  = [];
    foreach ($eventos as $ev) { $eventosMap[date('j', strtotime($ev['data_inicio']))][] = $ev; }
    $dia = 1; $hoje = (int) date('j'); $mesAtual = date('n') == $mes && date('Y') == $ano;
    echo '<div class="row g-0">';
    for ($cel = 0; $cel < $diaSemana; $cel++) echo '<div class="col border py-2" style="min-height:90px"></div>';
    while ($dia <= $diasNoMes) {
        $isHoje = $mesAtual && $dia === $hoje;
        echo '<div class="col border py-2 px-1" style="min-height:90px">';
        echo '<div class="' . ($isHoje ? 'bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center' : '') . ' fw-semibold mb-1" style="' . ($isHoje ? 'width:26px;height:26px;font-size:.8rem' : '') . '">' . $dia . '</div>';
        foreach ($eventosMap[$dia] ?? [] as $ev) {
            $cor = e($ev['cor'] ?? '#0d6efd');
            echo '<div class="rounded px-1 mb-1 text-white small text-truncate" style="background:' . $cor . ';font-size:.72rem" title="' . e($ev['titulo']) . '">' . e($ev['titulo']) . '</div>';
        }
        echo '</div>';
        $dia++;
        if (($dia + $diaSemana - 1) % 7 === 0 && $dia <= $diasNoMes) echo '</div><div class="row g-0">';
    }
    $restante = 7 - (($diasNoMes + $diaSemana) % 7);
    if ($restante < 7) for ($i = 0; $i < $restante; $i++) echo '<div class="col border py-2" style="min-height:90px"></div>';
    echo '</div>';
    ?>
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
</script>
