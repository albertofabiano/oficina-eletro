<?php
use App\Enums\TipoEvento;
use App\Enums\StatusEvento;

require_once __DIR__ . '/_evento.php';

$meses = ['','Janeiro','Fevereiro','Março','Abril','Maio','Junho','Julho','Agosto','Setembro','Outubro','Novembro','Dezembro'];
$diasSemanaExt = ['Domingo','Segunda-feira','Terça-feira','Quarta-feira','Quinta-feira','Sexta-feira','Sábado'];
$mesPrev = $mes == 1 ? 12 : $mes - 1; $anoPrev = $mes == 1 ? $ano - 1 : $ano;
$mesProx = $mes == 12 ? 1 : $mes + 1; $anoProx = $mes == 12 ? $ano + 1 : $ano;

// Faixa de horas exibida nas visões Semana/Dia/Técnicos (também é a "jornada" da barra de
// ocupação da Técnicos) — configurável em config/eventos_agenda.php, não chumbada aqui.
$cfgAgenda = require BASE_PATH . '/config/eventos_agenda.php';
$horaIni = (float) $cfgAgenda['jornada']['hora_inicio'];
$horaFim = (float) $cfgAgenda['jornada']['hora_fim'];

// Subtítulo "hoje é ..." — mostrado em toda visão, ao lado do título.
$hojeExtenso = $diasSemanaExt[(int) date('w')] . ', ' . (int) date('j') . ' de '
             . mb_strtolower($meses[(int) date('n')]) . ' de ' . date('Y');

$tiposTodos = array_map(fn (TipoEvento $t) => $t->value, TipoEvento::cases());

// Monta a querystring preservando data/view/filtros ativos — só sobrescreve o que for
// passado. Usado em toda navegação da tela (setas, "Hoje", seletor de visão, chips e
// dropdowns de filtro) pra nada se perder ao trocar de data/visão nem ao dar refresh/
// compartilhar o link. Overrides com valor null removem o parâmetro (usado pra "Todos"/
// "Limpar filtros"). $dataRef é a data de referência única da tela (ver AgendaController) —
// é o que sobrevive ao trocar de visão.
$qs = function (array $overrides = []) use ($dataRef, $view, $tiposAtivos, $tiposTodos, $statusAtivo, $usuarioFiltro) {
    $base = [
        'data'       => $dataRef,
        'view'       => $view,
        'tipo'       => count($tiposAtivos) === count($tiposTodos) ? null : implode(',', $tiposAtivos),
        'status'     => $statusAtivo,
        'usuario_id' => $usuarioFiltro > 0 ? $usuarioFiltro : null,
    ];
    $params = array_filter(array_merge($base, $overrides), fn ($v) => $v !== null && $v !== '');
    return '?' . http_build_query($params);
};

$visoes = ['mes' => 'Mês', 'semana' => 'Semana', 'dia' => 'Dia', 'tecnicos' => 'Técnicos'];
$usuariosPorId = array_column($usuarios, 'nome', 'id');

// Título, subtítulo de navegação e alvo das setas anterior/próximo — dependem da visão
// (mês navega por mês, semana por semana, dia/técnicos por dia), mas todos operam em cima da
// mesma $dataRef, então trocar de visão nunca perde a data que estava sendo olhada.
if ($view === 'semana') {
    $iTs = strtotime($inicioSemana); $fTs = strtotime($fimSemana);
    $iDia = (int) date('j', $iTs); $iMes = (int) date('n', $iTs); $iAno = (int) date('Y', $iTs);
    $fDia = (int) date('j', $fTs); $fMes = (int) date('n', $fTs); $fAno = (int) date('Y', $fTs);
    if ($iMes === $fMes && $iAno === $fAno) {
        $tituloPeriodo = "$iDia – $fDia de " . mb_strtolower($meses[$iMes]) . " de $iAno";
    } elseif ($iAno === $fAno) {
        $tituloPeriodo = "$iDia de " . mb_strtolower($meses[$iMes]) . " – $fDia de " . mb_strtolower($meses[$fMes]) . " de $iAno";
    } else {
        $tituloPeriodo = "$iDia de " . mb_strtolower($meses[$iMes]) . " de $iAno – $fDia de " . mb_strtolower($meses[$fMes]) . " de $fAno";
    }
    $navPrev = date('Y-m-d', strtotime($inicioSemana . ' -7 days'));
    $navProx = date('Y-m-d', strtotime($inicioSemana . ' +7 days'));
    $navLabelPrev = 'Semana anterior'; $navLabelProx = 'Próxima semana';
} elseif ($view === 'dia' || $view === 'tecnicos') {
    $dTs = strtotime($dataRef);
    $tituloPeriodo = $diasSemanaExt[(int) date('w', $dTs)] . ', ' . (int) date('j', $dTs) . ' de '
                   . mb_strtolower($meses[(int) date('n', $dTs)]) . ' de ' . date('Y', $dTs);
    $navPrev = date('Y-m-d', strtotime($dataRef . ' -1 day'));
    $navProx = date('Y-m-d', strtotime($dataRef . ' +1 day'));
    $navLabelPrev = 'Dia anterior'; $navLabelProx = 'Próximo dia';
} else {
    $tituloPeriodo = $meses[$mes] . ' ' . $ano;
    $navPrev = sprintf('%04d-%02d-01', $anoPrev, $mesPrev);
    $navProx = sprintf('%04d-%02d-01', $anoProx, $mesProx);
    $navLabelPrev = 'Mês anterior'; $navLabelProx = 'Próximo mês';
}
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

/* Barra de filtros */
.ag-filtros {
  display: flex; align-items: center; gap: .5rem; flex-wrap: wrap;
  padding: .6rem 0; margin: 0 0 1rem; border-bottom: 1px solid var(--border, #dee2e6);
}
.ag-filtros-chips { display: flex; gap: .4rem; flex-wrap: wrap; }
.ag-chip {
  display: inline-flex; align-items: center; gap: .3rem; padding: .28rem .65rem; border-radius: 999px;
  font-size: .74rem; font-weight: 600; text-decoration: none; border: 1px solid var(--border-strong, #ced4da);
  color: var(--text-3, #6c757d); background: transparent; white-space: nowrap; line-height: 1.2;
}
.ag-chip i { font-size: .8rem; }
.ag-chip-ativo { background: var(--ag-chip-bg-light); border-color: var(--ag-chip-bg-light); color: #fff; }
[data-theme="dark"] .ag-chip-ativo { background: var(--ag-chip-bg-dark); border-color: var(--ag-chip-bg-dark); color: #1a1d23; }
.ag-chip:hover:not(.ag-chip-ativo) { background: var(--surface-2, #f1f3f5); }

.ag-filtro-dd .dropdown-toggle { font-size: .78rem; }
.ag-filtro-dd .dropdown-item.active { background: var(--accent, #0d6efd); }

.ag-filtro-limpar {
  display: inline-flex; align-items: center; gap: .3rem; font-size: .78rem; font-weight: 600;
  color: var(--text-3, #6c757d); text-decoration: none; margin-left: auto; white-space: nowrap;
  padding: .28rem .1rem;
}
.ag-filtro-limpar:hover { color: var(--danger, #dc3545); }

@media (max-width: 767.98px) {
  .ag-filtros { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; }
  .ag-filtros-chips { flex-wrap: nowrap; }
  .ag-filtro-limpar { margin-left: .5rem; }
}

/* Visões Semana/Dia (eixo vertical de horas) — miolo compartilhado em _grade_horas.php */
.ag-hg-cabecalho { display: flex; border-bottom: 1px solid var(--border, #dee2e6); }
.ag-hg-cabecalho-horas { width: 52px; flex-shrink: 0; }
.ag-hg-cabecalho-dia { flex: 1; min-width: 0; text-align: center; padding: .5rem .25rem; }
.ag-hg-cabecalho-dia.ag-hg-hoje { background: var(--accent-soft, rgba(13,110,253,.08)); }
.ag-hg-dia-nome { display: block; font-size: .68rem; font-weight: 600; color: var(--text-3, #6c757d); text-transform: uppercase; }
.ag-hg-dia-num {
  display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px;
  font-size: .9rem; font-weight: 700; color: var(--text-1, #1a1d23);
}
.ag-hg-dia-num-hoje { background: var(--accent, #0d6efd); color: #fff; border-radius: 50%; }

.ag-hg-corpo { display: flex; position: relative; }
.ag-hg-horas { width: 52px; flex-shrink: 0; position: relative; }
.ag-hora-label {
  position: absolute; right: 6px; transform: translateY(-50%);
  font-size: .68rem; color: var(--text-3, #6c757d); white-space: nowrap;
}
.ag-hg-coluna { flex: 1; min-width: 0; position: relative; border-left: 1px solid var(--border, #dee2e6); cursor: pointer; }
.ag-hg-coluna.ag-hg-hoje { background: var(--accent-soft, rgba(13,110,253,.04)); }
.ag-hora-linha { position: absolute; left: 0; right: 0; border-top: 1px dashed var(--border, #dee2e6); pointer-events: none; }
.ag-linha-agora { position: absolute; left: 0; right: 0; height: 2px; background: var(--danger, #dc3545); z-index: 5; pointer-events: none; }
.ag-linha-agora::before {
  content: ''; position: absolute; left: -4px; top: -3px; width: 8px; height: 8px;
  border-radius: 50%; background: var(--danger, #dc3545);
}

.ag-ev-bloco {
  position: absolute; overflow: hidden; border: none; border-radius: 4px; padding: 2px 5px;
  font-size: .68rem; line-height: 1.25; text-align: left; cursor: pointer; z-index: 2;
  background: var(--ag-ev-bg-light); color: var(--ag-ev-fg-light);
}
[data-theme="dark"] .ag-ev-bloco { background: var(--ag-ev-bg-dark); color: var(--ag-ev-fg-dark); }
.ag-ev-bloco .ag-ev-hora { font-weight: 600; }
.ag-ev-bloco-detalhe { font-size: .78rem; padding: 5px 8px; }
.ag-ev-bloco-detalhe .ag-ev-cliente { font-size: .7rem; opacity: .85; display: flex; align-items: center; gap: 3px; margin-top: 2px; }

.ag-hg-unica .ag-hg-cabecalho-dia { text-align: left; padding-left: .75rem; }
.ag-hg-unica .ag-hg-dia-nome { font-size: .75rem; }

/* Visão Técnicos (eixo horizontal de horas, uma swimlane por técnico) */
.ag-tec-regua { display: flex; border-bottom: 1px solid var(--border, #dee2e6); padding-bottom: .4rem; margin-bottom: .25rem; }
.ag-tec-regua-rotulo { width: 180px; flex-shrink: 0; }
.ag-tec-regua-horas { flex: 1; position: relative; height: 16px; }
.ag-tec-hora-label { position: absolute; transform: translateX(-50%); font-size: .68rem; color: var(--text-3, #6c757d); white-space: nowrap; }

.ag-tec-linha { display: flex; border-bottom: 1px solid var(--border, #dee2e6); }
.ag-tec-linha:last-child { border-bottom: none; }
.ag-tec-linha-nome {
  width: 180px; flex-shrink: 0; padding: .5rem .6rem; display: flex; flex-direction: column;
  justify-content: center; gap: .3rem; border-right: 1px solid var(--border, #dee2e6);
}
.ag-tec-nome { font-size: .82rem; font-weight: 600; color: var(--text-1, #1a1d23); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.ag-tec-ocupacao { display: flex; align-items: center; gap: .4rem; }
.ag-tec-ocupacao-barra { flex: 1; height: 6px; border-radius: 3px; background: var(--surface-2, #e9ecef); overflow: hidden; }
.ag-tec-ocupacao-fill { height: 100%; background: var(--accent, #0d6efd); border-radius: 3px; }
.ag-tec-ocupacao-fill.ag-tec-ocupacao-alerta { background: var(--danger, #dc3545); }
.ag-tec-ocupacao-texto { font-size: .66rem; font-weight: 600; color: var(--text-3, #6c757d); white-space: nowrap; }
.ag-tec-ocupacao-texto.ag-tec-ocupacao-alerta-texto { color: var(--danger, #dc3545); }

.ag-tec-linha-trilha { flex: 1; position: relative; cursor: pointer; min-height: 30px; }
.ag-tec-hora-linha { position: absolute; top: 0; bottom: 0; border-left: 1px dashed var(--border, #dee2e6); pointer-events: none; }
.ag-tec-linha-agora { position: absolute; top: 0; bottom: 0; width: 2px; background: var(--danger, #dc3545); z-index: 5; pointer-events: none; }

.ag-ev-barra {
  position: absolute; overflow: hidden; border: none; border-radius: 4px; padding: 2px 6px;
  font-size: .68rem; line-height: 1.3; text-align: left; cursor: pointer; white-space: nowrap;
  background: var(--ag-ev-bg-light); color: var(--ag-ev-fg-light);
}
[data-theme="dark"] .ag-ev-barra { background: var(--ag-ev-bg-dark); color: var(--ag-ev-fg-dark); }

@media (max-width: 767.98px) {
  .ag-tec-regua-rotulo, .ag-tec-linha-nome { width: 120px; }
}
</style>

<div class="agenda-tb" id="agendaToolbar">
  <div class="agenda-tb-row">

    <div class="agenda-tb-titulo">
      <h5><?= e($tituloPeriodo) ?></h5>
      <small>Hoje é <?= $hojeExtenso ?></small>
    </div>

    <div class="agenda-tb-nav">
      <a href="<?= e($qs(['data' => $navPrev])) ?>" class="btn btn-outline-secondary btn-sm" aria-label="<?= e($navLabelPrev) ?>"><i class="bi bi-chevron-left"></i></a>
      <a href="<?= e($qs(['data' => $navProx])) ?>" class="btn btn-outline-secondary btn-sm" aria-label="<?= e($navLabelProx) ?>"><i class="bi bi-chevron-right"></i></a>
      <a href="<?= e($qs(['data' => date('Y-m-d')])) ?>" class="btn btn-outline-primary btn-sm">Hoje</a>
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

<div class="ag-filtros">
  <div class="ag-filtros-chips" role="group" aria-label="Filtrar por tipo de evento">
    <?php foreach (TipoEvento::cases() as $t):
        $ativo = in_array($t->value, $tiposAtivos, true);
        $novaLista = $ativo
            ? array_values(array_diff($tiposAtivos, [$t->value]))
            : array_values(array_merge($tiposAtivos, [$t->value]));
        $cfgT = $t->config();
    ?>
    <a href="<?= e($qs(['tipo' => count($novaLista) === count($tiposTodos) ? null : implode(',', $novaLista)])) ?>"
       class="ag-chip<?= $ativo ? ' ag-chip-ativo' : '' ?>"
       style="--ag-chip-bg-light:<?= e($cfgT['light']['barra']) ?>; --ag-chip-bg-dark:<?= e($cfgT['dark']['barra']) ?>;"
       aria-pressed="<?= $ativo ? 'true' : 'false' ?>">
      <i class="bi <?= e($cfgT['icone']) ?>"></i><?= e($cfgT['rotulo']) ?>
    </a>
    <?php endforeach; ?>
  </div>

  <div class="dropdown ag-filtro-dd">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" data-bs-auto-close="outside">
      <i class="bi bi-person-badge me-1"></i><?= $usuarioFiltro > 0 ? e($usuariosPorId[$usuarioFiltro] ?? 'Técnico') : 'Todos os técnicos' ?>
    </button>
    <div class="dropdown-menu p-2" style="min-width:230px">
      <input type="search" class="form-control form-control-sm mb-2" id="agendaFiltroTecnicoBusca" placeholder="Buscar técnico...">
      <ul class="list-unstyled mb-0" id="agendaFiltroTecnicoLista" style="max-height:220px;overflow:auto">
        <li><a class="dropdown-item rounded<?= $usuarioFiltro === 0 ? ' active' : '' ?>" href="<?= e($qs(['usuario_id' => null])) ?>">Todos os técnicos</a></li>
        <?php foreach ($usuarios as $u): ?>
        <li data-nome="<?= e(mb_strtolower($u['nome'])) ?>">
          <a class="dropdown-item rounded<?= $usuarioFiltro === (int) $u['id'] ? ' active' : '' ?>" href="<?= e($qs(['usuario_id' => $u['id']])) ?>"><?= e($u['nome']) ?></a>
        </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>

  <div class="dropdown ag-filtro-dd">
    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
      <i class="bi bi-flag me-1"></i><?= $statusAtivo !== null ? e(StatusEvento::from($statusAtivo)->rotulo()) : 'Todos os status' ?>
    </button>
    <ul class="dropdown-menu">
      <li><a class="dropdown-item<?= $statusAtivo === null ? ' active' : '' ?>" href="<?= e($qs(['status' => null])) ?>">Todos os status</a></li>
      <?php foreach (StatusEvento::cases() as $s): ?>
      <li><a class="dropdown-item<?= $statusAtivo === $s->value ? ' active' : '' ?>" href="<?= e($qs(['status' => $s->value])) ?>"><?= e($s->rotulo()) ?></a></li>
      <?php endforeach; ?>
    </ul>
  </div>

  <?php if ($temFiltroAtivo): ?>
  <a href="<?= e($qs(['tipo' => null, 'status' => null, 'usuario_id' => null])) ?>" class="ag-filtro-limpar">
    <i class="bi bi-x-circle"></i>Limpar filtros
  </a>
  <?php endif; ?>
</div>

<?php if ($temFiltroAtivo && !$eventos): ?>
<div class="alert d-flex align-items-center justify-content-between gap-3 mb-3" style="background:var(--surface-1,#f8f9fa);border:1px solid var(--border,#dee2e6);">
  <div class="d-flex align-items-center gap-2 text-muted">
    <i class="bi bi-funnel" style="font-size:1.1rem"></i>
    <span>Nenhum evento com esses filtros.</span>
  </div>
  <a href="<?= e($qs(['tipo' => null, 'status' => null, 'usuario_id' => null])) ?>" class="btn btn-sm btn-outline-secondary">Limpar filtros</a>
</div>
<?php endif; ?>

<?php
// As 4 visões reaproveitam o mesmo componente de evento (agenda_evento_*, ver _evento.php)
// e a mesma camada de dados (já filtrada/no período certo pelo controller) — cada partial só
// decide o layout.
$partialView = match ($view) {
    'semana'   => '_grade_semana.php',
    'dia'      => '_grade_dia.php',
    'tecnicos' => '_grade_tecnicos.php',
    default    => '_grade_mes.php',
};
require __DIR__ . '/' . $partialView;
?>

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

// Criação rápida a partir de qualquer visão: data sempre preenchida, hora e técnico são
// opcionais (Semana/Dia calculam a hora do clique; Técnicos também já manda o usuario_id).
function abrirCriacaoRapida(dataIso, hora, usuarioId) {
  var form = document.getElementById('formEvento');
  form.querySelector('[name=data_inicio]').value = dataIso + 'T' + (hora || '09:00');
  if (usuarioId) form.querySelector('[name=usuario_id]').value = usuarioId;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEvento')).show();
}

// Semana/Dia: clique em área vazia da coluna calcula a hora pela posição vertical do clique.
function agendaColunaClick(event, dataIso, horaIni, totalHoras) {
  if (event.target !== event.currentTarget) return; // clique veio de um bloco de evento, ignora
  var rect = event.currentTarget.getBoundingClientRect();
  var pct = (event.clientY - rect.top) / rect.height;
  abrirCriacaoRapida(dataIso, agendaHoraDoClique(horaIni, totalHoras, pct));
}

// Técnicos: mesma ideia, só que horizontal — e já manda o técnico da swimlane clicada.
function agendaTrilhaClick(event, dataIso, horaIni, totalHoras, usuarioId) {
  if (event.target !== event.currentTarget) return;
  var rect = event.currentTarget.getBoundingClientRect();
  var pct = (event.clientX - rect.left) / rect.width;
  abrirCriacaoRapida(dataIso, agendaHoraDoClique(horaIni, totalHoras, pct), usuarioId);
}

function agendaHoraDoClique(horaIni, totalHoras, pct) {
  var horaClicada = horaIni + pct * totalHoras;
  var h = Math.floor(horaClicada);
  var m = Math.round((horaClicada - h) * 60 / 15) * 15; // arredonda pro quarto de hora
  if (m === 60) { m = 0; h++; }
  return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
}

// Grade mensal: clique em área vazia abre criação rápida com a data preenchida;
// setas do teclado navegam entre os dias (roving tabindex — só um <td> por vez
// tem tabindex="0", ver aria-label/role="gridcell" no markup).

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

// Busca dentro do dropdown de filtro por técnico
(function () {
  var inp = document.getElementById('agendaFiltroTecnicoBusca');
  var lista = document.getElementById('agendaFiltroTecnicoLista');
  if (!inp || !lista) return;
  inp.addEventListener('input', function () {
    var termo = inp.value.trim().toLowerCase();
    lista.querySelectorAll('li[data-nome]').forEach(function (li) {
      li.style.display = (!termo || li.dataset.nome.indexOf(termo) !== -1) ? '' : 'none';
    });
  });
  inp.addEventListener('click', function (e) { e.stopPropagation(); });
})();
</script>
