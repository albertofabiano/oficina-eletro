<?php
/*
 * Miolo compartilhado das visões Semana e Dia — mesma mecânica (eixo vertical de horas,
 * eventos posicionados/dimensionados por horário via agenda_layout_periodo(), colunas para
 * eventos simultâneos, linha vermelha da hora atual). $diasExibidos tem 7 datas na Semana e
 * 1 na Dia; $detalheEvento liga o bloco mais informativo (mostra cliente) só na Dia, onde há
 * espaço de sobra dentro do bloco.
 */
$eventosPorDia = [];
foreach ($eventos as $ev) {
    $eventosPorDia[date('Y-m-d', strtotime($ev['data_inicio']))][] = $ev;
}

$alturaHora  = 56;
$totalHoras  = $horaFim - $horaIni;
$alturaGrade = $totalHoras * $alturaHora;

$hojeYmd      = date('Y-m-d');
$agoraHora    = (float) date('H') + (float) date('i') / 60;
$mostrarAgora = $agoraHora >= $horaIni && $agoraHora <= $horaFim;

$multiplasColunas = count($diasExibidos) > 1;
?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-0">
    <div class="ag-hg<?= $multiplasColunas ? '' : ' ag-hg-unica' ?>">
      <div class="ag-hg-cabecalho">
        <div class="ag-hg-cabecalho-horas"></div>
        <?php foreach ($diasExibidos as $dts):
            $ts = strtotime($dts);
            $isHoje = $dts === $hojeYmd;
        ?>
        <div class="ag-hg-cabecalho-dia<?= $isHoje ? ' ag-hg-hoje' : '' ?>">
          <span class="ag-hg-dia-nome"><?= $multiplasColunas ? mb_substr($diasSemanaExt[(int) date('w', $ts)], 0, 3) : $diasSemanaExt[(int) date('w', $ts)] ?></span>
          <span class="ag-hg-dia-num<?= $isHoje ? ' ag-hg-dia-num-hoje' : '' ?>"><?= (int) date('j', $ts) ?></span>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="ag-hg-corpo" style="height:<?= $alturaGrade ?>px">
        <div class="ag-hg-horas">
          <?php for ($h = (int) $horaIni; $h <= $horaFim; $h++): ?>
          <span class="ag-hora-label" style="top:<?= ($h - $horaIni) * $alturaHora ?>px"><?= sprintf('%02d:00', $h) ?></span>
          <?php endfor; ?>
        </div>

        <?php foreach ($diasExibidos as $dts):
            $isHoje = $dts === $hojeYmd;
            $doDia  = $eventosPorDia[$dts] ?? [];
            $layout = agenda_layout_periodo($doDia, $horaIni, $horaFim);
        ?>
        <div class="ag-hg-coluna<?= $isHoje ? ' ag-hg-hoje' : '' ?>" data-data="<?= $dts ?>"
             onclick="agendaColunaClick(event, '<?= $dts ?>', <?= $horaIni ?>, <?= $totalHoras ?>)">
          <?php for ($h = (int) $horaIni + 1; $h < $horaFim; $h++): ?>
          <div class="ag-hora-linha" style="top:<?= ($h - $horaIni) * $alturaHora ?>px"></div>
          <?php endfor; ?>

          <?php if ($isHoje && $mostrarAgora): ?>
          <div class="ag-linha-agora" style="top:<?= ($agoraHora - $horaIni) / $totalHoras * 100 ?>%"></div>
          <?php endif; ?>

          <?php foreach ($layout as $item):
              $ev = $item['ev'];
              $left  = $item['faixa'] / $item['totalFaixas'] * 100;
              $width = 100 / $item['totalFaixas'];
              $style = agenda_evento_style($ev)
                     . "top:{$item['posPct']}%;height:{$item['tamPct']}%;"
                     . "left:{$left}%;width:calc({$width}% - 3px);";
          ?>
          <button type="button" class="ag-ev-bloco<?= $detalheEvento ? ' ag-ev-bloco-detalhe' : '' ?>" style="<?= e($style) ?>"
                  <?= agenda_evento_data_attr($ev) ?>
                  title="<?= e(agenda_evento_tooltip($ev)) ?>"
                  onclick="agendaCliqueEvento(event, this)"
                  onpointerdown="agendaArrastarHorasInicio(event, this, <?= $horaIni ?>, <?= $totalHoras ?>)"
                  onkeydown="agendaEventoKeydown(event, this)">
            <?= agenda_evento_conteudo($ev, $detalheEvento) ?>
            <span class="ag-ev-resize" onpointerdown="agendaRedimensionarInicio(event, this.closest('.ag-ev-bloco'), <?= $horaIni ?>, <?= $totalHoras ?>)"></span>
          </button>
          <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
