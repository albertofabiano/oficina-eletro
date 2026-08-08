<?php
/*
 * 4 indicadores entre a barra de filtros e a grade — sempre no escopo do MÊS de $dataRef
 * (independente da visão atual) e respeitando os filtros ativos (ver
 * AgendaController::calcularIndicadores()). Cada um é um link que aplica o filtro
 * correspondente: "Eventos no mês" limpa os filtros (mostra tudo), "OS agendadas" e "Valor a
 * receber" filtram por tipo=ordem_servico (é a mesma base de dado dos dois números — não faz
 * sentido inventar uma dimensão de filtro só pro card de valor), "Em atraso" filtra por
 * status=atrasado.
 */
$hrefTodos     = $qs(['tipo' => null, 'status' => null, 'usuario_id' => null, 'view' => 'mes']);
$hrefOs        = $qs(['tipo' => 'ordem_servico']);
$hrefAtrasado  = $qs(['status' => 'atrasado']);
?>
<div class="ag-indicadores">
  <a href="<?= e($hrefTodos) ?>" class="ag-indicador">
    <span class="ag-indicador-num"><?= (int) $indicadores['eventosNoMes'] ?></span>
    <span class="ag-indicador-label">Eventos no mês</span>
  </a>
  <a href="<?= e($hrefOs) ?>" class="ag-indicador">
    <span class="ag-indicador-num"><?= (int) $indicadores['osAgendadas'] ?></span>
    <span class="ag-indicador-label">OS agendadas</span>
  </a>
  <a href="<?= e($hrefAtrasado) ?>" class="ag-indicador<?= $indicadores['emAtraso'] > 0 ? ' ag-indicador-alerta' : '' ?>">
    <span class="ag-indicador-num"><?= (int) $indicadores['emAtraso'] ?></span>
    <span class="ag-indicador-label">Em atraso</span>
  </a>
  <a href="<?= e($hrefOs) ?>" class="ag-indicador">
    <span class="ag-indicador-num"><?= money($indicadores['valorPendente']) ?></span>
    <span class="ag-indicador-label">A receber no mês</span>
  </a>
</div>
