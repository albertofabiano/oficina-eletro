<?php
/*
 * Uma swimlane horizontal por técnico ativo, eixo de horas do dia selecionado (mesma faixa
 * horaIni/horaFim da Semana/Dia — ver config/eventos_agenda.php). Reaproveita
 * agenda_layout_periodo() só que o "faixa/totalFaixas" que lá vira coluna aqui vira linha
 * empilhada dentro da própria swimlane (evento duplo-agendado pro mesmo técnico).
 *
 * Barra de ocupação: soma bruta das durações dos eventos do técnico no dia sobre a jornada
 * configurada (horaFim-horaIni) — não deduplica sobreposição de propósito, é assim que
 * "estourar 100%" sinaliza um técnico com conflito de horário (double-booking).
 */
$eventosPorUsuario = [];
foreach ($eventos as $ev) {
    $uid = (int) ($ev['usuario_id'] ?? 0);
    $eventosPorUsuario[$uid][] = $ev;
}

$totalHoras   = $horaFim - $horaIni;
$jornadaHoras = $totalHoras;
$subLinha     = 30; // px por faixa de sobreposição dentro da mesma swimlane

$ehHoje       = $dataRef === date('Y-m-d');
$agoraHora    = (float) date('H') + (float) date('i') / 60;
$mostrarAgora = $ehHoje && $agoraHora >= $horaIni && $agoraHora <= $horaFim;
?>
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body p-0">
    <div class="ag-tec">
      <div class="ag-tec-regua">
        <div class="ag-tec-regua-rotulo"></div>
        <div class="ag-tec-regua-horas">
          <?php for ($h = (int) $horaIni; $h <= $horaFim; $h++): ?>
          <span class="ag-tec-hora-label" style="left:<?= ($h - $horaIni) / $totalHoras * 100 ?>%"><?= sprintf('%02d:00', $h) ?></span>
          <?php endfor; ?>
        </div>
      </div>

      <?php if (!$usuarios): ?>
      <div class="text-center text-muted py-4">Nenhum técnico ativo cadastrado.</div>
      <?php endif; ?>

      <?php foreach ($usuarios as $u):
          $uid    = (int) $u['id'];
          $doTec  = $eventosPorUsuario[$uid] ?? [];
          $layout = agenda_layout_periodo($doTec, $horaIni, $horaFim);
          $totalFaixas  = $layout ? max(array_column($layout, 'totalFaixas')) : 1;
          $alturaTrilha = max(1, $totalFaixas) * $subLinha;

          $horasAgendadas = 0;
          foreach ($doTec as $ev) {
              $ini = strtotime($ev['data_inicio']);
              $fim = !empty($ev['data_fim']) ? strtotime($ev['data_fim']) : $ini + 3600;
              if ($fim <= $ini) $fim = $ini + 1800;
              $horasAgendadas += ($fim - $ini) / 3600;
          }
          $ocupacaoPct    = $jornadaHoras > 0 ? (int) round($horasAgendadas / $jornadaHoras * 100) : 0;
          $sobrecarregado = $ocupacaoPct > 100;
      ?>
      <div class="ag-tec-linha">
        <div class="ag-tec-linha-nome">
          <span class="ag-tec-nome"><?= e($u['nome']) ?></span>
          <div class="ag-tec-ocupacao" title="<?= e(number_format($horasAgendadas, 1, ',', '.') . 'h de ' . $jornadaHoras . 'h (' . $ocupacaoPct . '%)') ?>">
            <div class="ag-tec-ocupacao-barra">
              <div class="ag-tec-ocupacao-fill<?= $sobrecarregado ? ' ag-tec-ocupacao-alerta' : '' ?>" style="width:<?= min(100, $ocupacaoPct) ?>%"></div>
            </div>
            <span class="ag-tec-ocupacao-texto<?= $sobrecarregado ? ' ag-tec-ocupacao-alerta-texto' : '' ?>"><?= $ocupacaoPct ?>%</span>
          </div>
        </div>
        <div class="ag-tec-linha-trilha" style="height:<?= $alturaTrilha ?>px"
             onclick="agendaTrilhaClick(event, '<?= $dataRef ?>', <?= $horaIni ?>, <?= $totalHoras ?>, <?= $uid ?>)">
          <?php for ($h = (int) $horaIni + 1; $h < $horaFim; $h++): ?>
          <div class="ag-tec-hora-linha" style="left:<?= ($h - $horaIni) / $totalHoras * 100 ?>%"></div>
          <?php endfor; ?>

          <?php if ($mostrarAgora): ?>
          <div class="ag-tec-linha-agora" style="left:<?= ($agoraHora - $horaIni) / $totalHoras * 100 ?>%"></div>
          <?php endif; ?>

          <?php foreach ($layout as $item):
              $ev = $item['ev'];
              $top    = $item['faixa'] / $item['totalFaixas'] * 100;
              $height = 100 / $item['totalFaixas'];
              $style = agenda_evento_style($ev)
                     . "left:{$item['posPct']}%;width:calc({$item['tamPct']}% - 3px);"
                     . "top:{$top}%;height:calc({$height}% - 3px);";
          ?>
          <button type="button" class="ag-ev-barra" style="<?= e($style) ?>"
                  title="<?= e(date('H:i', strtotime($ev['data_inicio'])) . ' ' . $ev['titulo']) ?>"
                  onclick="event.stopPropagation(); editarEvento(<?= htmlspecialchars(json_encode($ev), ENT_QUOTES) ?>)">
            <?= agenda_evento_conteudo($ev) ?>
          </button>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
