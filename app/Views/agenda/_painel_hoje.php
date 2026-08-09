<?php
/*
 * Painel "Hoje" — resumo operacional do dia, a primeira coisa visível ao abrir a Agenda (ver
 * AgendaController::painelHoje()). Mistura contagens de OS por status (aguardando atendimento/
 * aprovação/atrasadas) com a agenda de hoje (entregas previstas, ocupação por técnico) — as
 * duas metades do "painel de comando" da assistência. Sempre no escopo de HOJE, independente
 * da visão/data que a grade abaixo está mostrando.
 *
 * Os 3 cartões de OS não têm link de filtro porque a lista de OS filtra por status_id
 * específico, não por "tipo" (uma empresa pode ter vários status com o mesmo tipo) — levar pra
 * "/os" sem filtro ainda é navegação útil, sem fingir uma precisão que a tela de OS não suporta.
 *
 * Fica dentro de #agendaPainelHoje pra agendaAtualizarGrade() (arrastar/redimensionar/ações
 * rápidas) reconciliar as métricas junto com a grade e os indicadores, sem recarregar a
 * página inteira — senão um arraste que muda "entregas previstas hoje"/ocupação ficaria com
 * o número velho até o próximo F5.
 */
?>
<div id="agendaPainelHoje">
<div class="ag-hoje card border-0 shadow-sm mb-3">
  <div class="card-body">
    <div class="ag-hoje-cabecalho">
      <i class="bi bi-speedometer2"></i>
      <span>Hoje</span>
    </div>

    <div class="ag-hoje-stats">
      <a href="<?= e(url('/os')) ?>" class="ag-hoje-stat">
        <span class="ag-hoje-stat-num"><?= (int) $painelHoje['aguardandoAtendimento'] ?></span>
        <span class="ag-hoje-stat-label">Aguardando atendimento</span>
      </a>
      <a href="<?= e($qs(['tipo' => 'entrega', 'data' => date('Y-m-d'), 'view' => 'dia'])) ?>" class="ag-hoje-stat">
        <span class="ag-hoje-stat-num"><?= (int) $painelHoje['entregasHoje'] ?></span>
        <span class="ag-hoje-stat-label">Entregas previstas</span>
      </a>
      <a href="<?= e(url('/os')) ?>" class="ag-hoje-stat">
        <span class="ag-hoje-stat-num"><?= (int) $painelHoje['orcamentosAguardando'] ?></span>
        <span class="ag-hoje-stat-label">Orçamentos aguardando aprovação</span>
      </a>
      <a href="<?= e(url('/os')) ?>" class="ag-hoje-stat<?= $painelHoje['servicosAtrasados'] > 0 ? ' ag-hoje-stat-alerta' : '' ?>">
        <span class="ag-hoje-stat-num"><?= (int) $painelHoje['servicosAtrasados'] ?></span>
        <span class="ag-hoje-stat-label">Serviços atrasados</span>
      </a>
      <div class="ag-hoje-stat">
        <span class="ag-hoje-stat-num"><?= (int) $painelHoje['horasLivres'] ?>h</span>
        <span class="ag-hoje-stat-label">Livres hoje (todos os técnicos)</span>
      </div>
    </div>

    <?php if ($painelHoje['tecnicos']): ?>
    <div class="ag-hoje-tecnicos">
      <?php foreach ($painelHoje['tecnicos'] as $t): $sobrecarregado = $t['pct'] > 100; ?>
      <div class="ag-hoje-tecnico">
        <span class="ag-hoje-tecnico-nome"><?= e($t['nome']) ?></span>
        <div class="ag-tec-ocupacao" title="<?= e(number_format($t['horasAgendadas'], 1, ',', '.') . 'h de ' . $t['jornadaHoras'] . 'h (' . $t['pct'] . '%)') ?>">
          <div class="ag-tec-ocupacao-barra">
            <div class="ag-tec-ocupacao-fill<?= $sobrecarregado ? ' ag-tec-ocupacao-alerta' : '' ?>" style="width:<?= min(100, $t['pct']) ?>%"></div>
          </div>
          <span class="ag-tec-ocupacao-texto<?= $sobrecarregado ? ' ag-tec-ocupacao-alerta-texto' : '' ?>"><?= $t['pct'] ?>%</span>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="text-muted small mb-0 mt-2">Nenhum técnico ativo cadastrado.</p>
    <?php endif; ?>
  </div>
</div>
</div>
