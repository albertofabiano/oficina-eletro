<?php
$meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$mesPrev = $mes == 1 ? 12 : $mes - 1; $anoPrev = $mes == 1 ? $ano - 1 : $ano;
$mesProx = $mes == 12 ? 1 : $mes + 1; $anoProx = $mes == 12 ? $ano + 1 : $ano;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex align-items-center gap-3">
    <a href="?mes=<?= $mesPrev ?>&ano=<?= $anoPrev ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-left"></i></a>
    <h5 class="mb-0 fw-bold"><?= $meses[$mes] ?> <?= $ano ?></h5>
    <a href="?mes=<?= $mesProx ?>&ano=<?= $anoProx ?>" class="btn btn-outline-secondary btn-sm"><i class="bi bi-chevron-right"></i></a>
    <a href="?mes=<?= date('m') ?>&ano=<?= date('Y') ?>" class="btn btn-outline-primary btn-sm">Hoje</a>
  </div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalEvento">
    <i class="bi bi-plus-lg"></i> Novo evento
  </button>
</div>

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
    <table class="table table-hover mb-0 small align-middle">
      <thead class="table-light"><tr><th>Data/Hora</th><th>Título</th><th>Tipo</th><th>Responsável</th><th>Cliente</th><th>Status</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($eventos as $ev): ?>
        <tr>
          <td><?= date_br($ev['data_inicio'], true) ?></td>
          <td class="fw-semibold"><?= e($ev['titulo']) ?></td>
          <td><span class="badge bg-secondary"><?= ucfirst($ev['tipo']) ?></span></td>
          <td><?= e($ev['usuario_nome'] ?? '—') ?></td>
          <td><?= e($ev['cliente_nome'] ?? '—') ?></td>
          <td>
            <?php $sm=['agendado'=>'primary','confirmado'=>'success','cancelado'=>'danger','concluido'=>'secondary']; ?>
            <span class="badge bg-<?= $sm[$ev['status']] ?? 'secondary' ?>"><?= ucfirst($ev['status']) ?></span>
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
        <?php if (!$eventos): ?><tr><td colspan="7" class="text-center text-muted py-4">Nenhum evento neste mês.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Modal novo/editar evento -->
<div class="modal fade" id="modalEvento" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formEvento" action="<?= url('/agenda') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="evento_id" id="fEventoId" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEventoTitulo">Novo Evento</h5>
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
              <?php foreach (['visita'=>'Visita','coleta'=>'Coleta','entrega'=>'Entrega','reuniao'=>'Reunião','ligacao'=>'Ligação','os'=>'OS','outro'=>'Outro'] as $v=>$l): ?>
              <option value="<?= $v ?>"><?= $l ?></option>
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
  document.getElementById('modalEventoTitulo').textContent = 'Editar Evento';
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
  document.getElementById('modalEventoTitulo').textContent = 'Novo Evento';
  document.getElementById('btnSalvarEvento').textContent   = 'Salvar';
  document.getElementById('fEventoId').value = '';
  document.getElementById('formEvento').reset();
  document.querySelector('[name=data_inicio]').value = '<?= date('Y-m-d\TH:i') ?>';
});
</script>
