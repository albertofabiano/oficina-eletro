<?php
/*
 * Grade mensal — <table> semântica (cabeçalho de dia da semana em <th scope="col">,
 * cada dia em <td role="gridcell"> com aria-label completo e navegação por setas
 * via roving tabindex, ver script no fim de agenda/index.php).
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
                $corCfg = agenda_evento_cor($ev);
                $hora = date('H:i', strtotime($ev['data_inicio']));
              ?>
              <button type="button" class="ag-pill" <?= agenda_evento_data_attr($ev) ?>
                      style="--ag-pill-bg-light:<?= e($corCfg['light']['pill_bg']) ?>; --ag-pill-fg-light:<?= e($corCfg['light']['pill_texto']) ?>; --ag-pill-bg-dark:<?= e($corCfg['dark']['pill_bg']) ?>; --ag-pill-fg-dark:<?= e($corCfg['dark']['pill_texto']) ?>;"
                      title="<?= e(agenda_evento_tooltip($ev)) ?>"
                      onclick="agendaCliqueEvento(event, this)"
                      onpointerdown="agendaArrastarMesInicio(event, this)"
                      onkeydown="agendaEventoKeydown(event, this)">
                <?= agenda_evento_conteudo($ev) ?>
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
