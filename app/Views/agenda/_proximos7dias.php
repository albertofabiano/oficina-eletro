<?php
/*
 * Lista "Próximos 7 dias" — sempre a partir de hoje de verdade (ver AgendaController::index()),
 * sem os filtros da grade acima (é um resumo geral, não a mesma visão filtrada). Agrupada por
 * dia com cabeçalho "Hoje"/"Amanhã"/"Quinta, 13"; cada linha tem ações rápidas (concluir,
 * reagendar, abrir OS, cancelar) que salvam via AJAX e reconciliam com agendaAtualizarGrade().
 */
$hojeStr = date('Y-m-d');
$amanhaStr = date('Y-m-d', strtotime($hojeStr . ' +1 day'));
$diasSemanaExtLocal = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
$mesesAbrev = ['', 'jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez'];

$rotuloDoGrupo = function (string $dia) use ($hojeStr, $amanhaStr, $diasSemanaExtLocal) {
    if ($dia === $hojeStr) return 'Hoje';
    if ($dia === $amanhaStr) return 'Amanhã';
    $ts = strtotime($dia);
    return $diasSemanaExtLocal[(int) date('w', $ts)] . ', ' . (int) date('j', $ts);
};

$grupos = [];
foreach ($proximos7Dias as $ev) {
    $grupos[substr($ev['data_inicio'], 0, 10)][] = $ev;
}
ksort($grupos);

$qsProx = function (int $pagina) {
    $params = $_GET;
    $params['prox_pagina'] = $pagina;
    return '?' . http_build_query($params);
};
?>
<div class="card border-0 shadow-sm mt-3" id="agendaProx7Container">
  <div class="card-header bg-white fw-semibold d-flex align-items-center justify-content-between">
    <span>Próximos 7 dias</span>
    <?php if ($prox7Total > 0): ?><span class="text-muted small fw-normal"><?= $prox7Total ?> evento<?= $prox7Total === 1 ? '' : 's' ?></span><?php endif; ?>
  </div>

  <?php if (!$proximos7Dias): ?>
  <div class="text-center text-muted py-5">
    <i class="bi bi-calendar-heart" style="font-size:2.2rem;opacity:.3"></i>
    <p class="mb-3 mt-2">Nada agendado pros próximos 7 dias.</p>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalEvento">
      <i class="bi bi-plus-lg me-1"></i>Novo evento
    </button>
  </div>
  <?php else: ?>
  <div id="agendaProx7Lista">
    <?php foreach ($grupos as $dia => $eventosDia): ?>
    <div class="ag7-cabecalho"><?= e($rotuloDoGrupo($dia)) ?></div>
    <?php foreach ($eventosDia as $ev):
        $corCfg   = agenda_evento_cor($ev);
        $ts       = strtotime($ev['data_inicio']);
        $tipoEv   = \App\Enums\TipoEvento::tryFrom($ev['tipo'] ?? '') ?? \App\Enums\TipoEvento::Outro;
        $statusEv = \App\Enums\StatusEvento::tryFrom($ev['status'] ?? '') ?? \App\Enums\StatusEvento::Agendado;
        $statusCfg = $statusEv->config();
    ?>
    <div class="ag7-linha" data-titulo="<?= e(mb_strtolower($ev['titulo'])) ?>">
      <div class="ag7-data">
        <span class="ag7-data-dia"><?= (int) date('j', $ts) ?></span>
        <span class="ag7-data-mes"><?= $mesesAbrev[(int) date('n', $ts)] ?></span>
      </div>
      <?php // pill_texto (não "barra") — agenda_evento_cor() pode devolver a config de StatusEvento
            // (quando status=atrasado), que só tem pill_bg/pill_texto, sem a cor de barra dos tipos. ?>
      <div class="ag7-barra" style="background:<?= e($corCfg['light']['pill_texto']) ?>"></div>
      <div class="ag7-corpo">
        <div class="ag7-titulo">
          <?= e($ev['titulo']) ?>
          <?php if (!empty($ev['recorrente'])): ?><i class="bi bi-arrow-repeat text-muted" title="<?= e($ev['_rrule_texto'] ?? 'Recorrente') ?>"></i><?php endif; ?>
          <?php if (!empty($ev['os_id']) && !empty($ev['os_numero'])): ?>
          <a href="<?= e(url('/os/' . $ev['os_id'])) ?>" class="ag7-os"><i class="bi bi-clipboard-check"></i> OS <?= e($ev['os_numero']) ?></a>
          <?php endif; ?>
        </div>
        <div class="ag7-meta">
          <?= date('H:i', $ts) ?> · <?= e($tipoEv->rotulo()) ?><?php if (!empty($ev['usuario_nome'])): ?> · <?= e($ev['usuario_nome']) ?><?php endif; ?><?php if (!empty($ev['cliente_nome'])): ?> · <?= e($ev['cliente_nome']) ?><?php endif; ?>
        </div>
      </div>
      <div class="ag7-lado">
        <span class="ag7-status" style="--ag7-status-bg-light:<?= e($statusCfg['light']['pill_bg']) ?>; --ag7-status-fg-light:<?= e($statusCfg['light']['pill_texto']) ?>; --ag7-status-bg-dark:<?= e($statusCfg['dark']['pill_bg']) ?>; --ag7-status-fg-dark:<?= e($statusCfg['dark']['pill_texto']) ?>;">
          <?= e($statusEv->rotulo()) ?>
        </span>
        <div class="dropdown">
          <button type="button" class="btn btn-sm btn-outline-secondary border-0" data-bs-toggle="dropdown" aria-label="Ações rápidas">
            <i class="bi bi-three-dots-vertical"></i>
          </button>
          <ul class="dropdown-menu dropdown-menu-end" <?= agenda_evento_data_attr($ev) ?>>
            <li><button type="button" class="dropdown-item" onclick="agendaAcaoRapidaStatus(this, 'concluido')"><i class="bi bi-check-circle me-2"></i>Concluir</button></li>
            <li><button type="button" class="dropdown-item" onclick="agendaAcaoRapidaReagendar(this)"><i class="bi bi-calendar-event me-2"></i>Reagendar</button></li>
            <?php if (!empty($ev['os_id'])): ?>
            <li><a class="dropdown-item" href="<?= e(url('/os/' . $ev['os_id'])) ?>"><i class="bi bi-clipboard-check me-2"></i>Abrir OS</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><button type="button" class="dropdown-item text-danger" onclick="agendaAcaoRapidaStatus(this, 'cancelado')"><i class="bi bi-x-circle me-2"></i>Cancelar</button></li>
          </ul>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endforeach; ?>
    <div class="text-center text-muted py-3 d-none" id="agendaProx7SemResultadoBusca">Nenhum evento encontrado com esse termo.</div>
  </div>

  <?php if ($prox7Total > $prox7PorPagina): ?>
  <div class="ag7-paginacao">
    <a href="<?= e($qsProx($prox7Pagina - 1)) ?>#agendaProx7Container" class="btn btn-sm btn-outline-secondary<?= $prox7Pagina <= 1 ? ' disabled' : '' ?>">
      <i class="bi bi-chevron-left"></i> Anterior
    </a>
    <span class="text-muted small">Mostrando <?= count($proximos7Dias) ?> de <?= $prox7Total ?></span>
    <a href="<?= e($qsProx($prox7Pagina + 1)) ?>#agendaProx7Container" class="btn btn-sm btn-outline-secondary<?= ($prox7Pagina * $prox7PorPagina) >= $prox7Total ? ' disabled' : '' ?>">
      Próxima <i class="bi bi-chevron-right"></i>
    </a>
  </div>
  <?php endif; ?>
  <?php endif; ?>
</div>
