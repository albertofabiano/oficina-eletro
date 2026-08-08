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
.ag-ev-os {
  display: inline-flex; align-items: center; gap: 3px; font-size: .68rem; font-weight: 600;
  margin-top: 2px; color: inherit; opacity: .9; text-decoration: underline;
}
.ag-ev-os:hover { opacity: 1; }

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

/* Popover de criação rápida */
.ag-popover-rapido {
  position: fixed; z-index: 1060; width: 260px;
  background: var(--surface-0, #fff); border: 1px solid var(--border, #dee2e6); border-radius: 10px;
  box-shadow: 0 8px 24px rgba(0,0,0,.18);
}
.ag-popover-rapido-topo {
  display: flex; align-items: center; justify-content: space-between;
  padding: .5rem .65rem; font-size: .8rem; font-weight: 600; color: var(--text-1, #1a1d23);
  border-bottom: 1px solid var(--border, #dee2e6);
}
.ag-popover-rapido-corpo { padding: .6rem .65rem; }
.ag-popover-rapido-rodape {
  display: flex; align-items: center; justify-content: space-between;
  padding: .5rem .65rem; border-top: 1px solid var(--border, #dee2e6);
}
.ag-popover-rapido-rodape a { font-size: .78rem; }
.ag-pill-otimista { opacity: .55; }

/* Arrastar/redimensionar/mover por teclado */
.ag-ev-bloco, .ag-ev-barra, .ag-pill { touch-action: none; } /* deixa o pointermove livre pro nosso próprio drag */
.ag-ev-resize {
  position: absolute; left: 0; right: 0; bottom: -2px; height: 7px; cursor: ns-resize;
}
.ag-ev-resize-h { left: auto; right: -2px; top: 0; bottom: 0; width: 7px; height: auto; cursor: ew-resize; }
.ag-ev-arrastando { opacity: .55; z-index: 20 !important; box-shadow: 0 4px 10px rgba(0,0,0,.25); }
.ag-cel-alvo-drop, .ag-hg-coluna-alvo-drop { outline: 2px dashed var(--accent, #0d6efd); outline-offset: -2px; }
.ag-ev-movendo { outline: 2px solid var(--accent, #0d6efd); outline-offset: 1px; }

.ag-status-movendo {
  position: fixed; z-index: 1070; bottom: 20px; left: 50%; transform: translateX(-50%);
  background: #1a1d23; color: #fff;
  padding: .5rem 1rem; border-radius: 8px; font-size: .82rem; box-shadow: 0 4px 14px rgba(0,0,0,.3);
  display: flex; align-items: center; gap: .6rem;
}
.ag-status-movendo kbd { background: rgba(255,255,255,.15); border-radius: 4px; padding: .05rem .35rem; font-size: .74rem; }

#agendaToasts {
  position: fixed; bottom: 20px; right: 20px; z-index: 2100; display: flex;
  flex-direction: column-reverse; gap: 8px; width: 320px;
}
#agendaToasts .toast { position: static; min-width: 0; }

/* Indicadores */
.ag-indicadores { display: grid; grid-template-columns: repeat(4, 1fr); gap: .6rem; margin: 0 0 1rem; }
.ag-indicador {
  display: flex; flex-direction: column; gap: .15rem; padding: .75rem .9rem; border-radius: 10px;
  background: var(--surface-0, #fff); border: 1px solid var(--border, #dee2e6); text-decoration: none;
  transition: border-color .15s;
}
.ag-indicador:hover { border-color: var(--accent, #0d6efd); }
.ag-indicador-num { font-size: 1.3rem; font-weight: 700; color: var(--text-1, #1a1d23); line-height: 1.1; }
.ag-indicador-label { font-size: .74rem; color: var(--text-3, #6c757d); }
.ag-indicador-alerta .ag-indicador-num { color: var(--danger, #dc3545); }
@media (max-width: 767.98px) {
  .ag-indicadores { grid-template-columns: repeat(2, 1fr); }
}

/* Próximos 7 dias */
.ag7-cabecalho {
  padding: .55rem 1rem; font-size: .78rem; font-weight: 700; text-transform: uppercase;
  letter-spacing: .02em; color: var(--text-3, #6c757d); background: var(--surface-1, #f8f9fa);
  border-bottom: 1px solid var(--border, #dee2e6); border-top: 1px solid var(--border, #dee2e6);
}
.ag7-linha { display: flex; align-items: stretch; gap: .75rem; padding: .6rem 1rem; border-bottom: 1px solid var(--border, #dee2e6); }
.ag7-linha:last-child { border-bottom: none; }
.ag7-data {
  flex-shrink: 0; width: 42px; display: flex; flex-direction: column; align-items: center;
  justify-content: center; border-radius: 8px; background: var(--surface-1, #f8f9fa); padding: .3rem 0;
}
.ag7-data-dia { font-size: 1.05rem; font-weight: 700; color: var(--text-1, #1a1d23); line-height: 1.1; }
.ag7-data-mes { font-size: .65rem; text-transform: uppercase; color: var(--text-3, #6c757d); }
.ag7-barra { flex-shrink: 0; width: 4px; border-radius: 2px; }
.ag7-corpo { flex: 1; min-width: 0; }
.ag7-titulo { font-weight: 600; color: var(--text-1, #1a1d23); font-size: .92rem; display: flex; align-items: center; gap: .4rem; flex-wrap: wrap; }
.ag7-os { font-size: .72rem; font-weight: 600; text-decoration: none; }
.ag7-meta { font-size: .8rem; color: var(--text-3, #6c757d); margin-top: 1px; }
.ag7-lado { flex-shrink: 0; display: flex; align-items: center; gap: .4rem; }
.ag7-status {
  font-size: .7rem; font-weight: 600; padding: .2rem .55rem; border-radius: 999px; white-space: nowrap;
  background: var(--ag7-status-bg-light); color: var(--ag7-status-fg-light);
}
[data-theme="dark"] .ag7-status { background: var(--ag7-status-bg-dark); color: var(--ag7-status-fg-dark); }
.ag7-paginacao { display: flex; align-items: center; justify-content: space-between; padding: .65rem 1rem; border-top: 1px solid var(--border, #dee2e6); }
@media (max-width: 575.98px) {
  .ag7-meta { display: block; }
  .ag7-lado { flex-direction: column; align-items: flex-end; gap: .3rem; }
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

<div id="agendaIndicadores"><?php require __DIR__ . '/_indicadores.php'; ?></div>

<div id="agendaGradeContainer">
<?php
// As 4 visões reaproveitam o mesmo componente de evento (agenda_evento_*, ver _evento.php)
// e a mesma camada de dados (já filtrada/no período certo pelo controller) — cada partial só
// decide o layout. Fica num container com id próprio pra criação rápida (popover) poder
// atualizar só essa parte depois de salvar, sem recarregar a página inteira (ver
// agendaAtualizarGrade() no script no fim da view).
$partialView = match ($view) {
    'semana'   => '_grade_semana.php',
    'dia'      => '_grade_dia.php',
    'tecnicos' => '_grade_tecnicos.php',
    default    => '_grade_mes.php',
};
require __DIR__ . '/' . $partialView;
?>
</div>

<?php require __DIR__ . '/_proximos7dias.php'; ?>

<!-- Modal novo/editar evento -->
<div class="modal fade" id="modalEvento" tabindex="-1">
  <div class="modal-dialog">
    <form class="modal-content" method="POST" id="formEvento" action="<?= url('/agenda') ?>">
      <?= csrf_field() ?>
      <input type="hidden" name="evento_id" id="fEventoId" value="">
      <input type="hidden" name="recorrencia_id" id="fRecorrenciaId" value="">
      <input type="hidden" name="recorrencia_data_original" id="fRecorrenciaData" value="">
      <input type="hidden" name="escopo_recorrencia" id="fEscopoRecorrencia" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="modalEventoTitulo">Novo evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-semibold">Título *</label>
            <input type="text" name="titulo" id="fTitulo" class="form-control" required>
          </div>

          <div class="col-md-6 position-relative">
            <label class="form-label small fw-semibold">Ordem de Serviço</label>
            <input type="text" id="fOsBusca" class="form-control form-control-sm" placeholder="Buscar por número, cliente..." autocomplete="off">
            <input type="hidden" name="os_id" id="fOsId">
            <div class="dropdown-menu p-0 w-100" id="fOsLista" style="max-height:220px;overflow:auto"></div>
          </div>
          <div class="col-md-6 position-relative">
            <label class="form-label small fw-semibold">Cliente</label>
            <input type="text" id="fClienteBusca" class="form-control form-control-sm" placeholder="Buscar cliente..." autocomplete="off">
            <input type="hidden" name="cliente_id" id="fClienteId">
            <div class="dropdown-menu p-0 w-100" id="fClienteLista" style="max-height:220px;overflow:auto"></div>
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
            <select name="usuario_id" id="fUsuarioId" class="form-select">
              <?php foreach ($usuarios as $u): ?>
              <option value="<?= $u['id'] ?>"><?= e($u['nome']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Início *</label>
            <input type="datetime-local" name="data_inicio" id="fDataInicio" class="form-control" required value="<?= date('Y-m-d\TH:i') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Fim</label>
            <input type="datetime-local" name="data_fim" id="fDataFim" class="form-control">
          </div>
          <div class="col-12 d-none" id="avisoConflito">
            <div class="alert alert-warning py-2 px-3 mb-0 small d-flex align-items-center gap-2">
              <i class="bi bi-exclamation-triangle"></i>
              <span id="avisoConflitoTexto"></span>
            </div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Cor</label>
            <input type="color" name="cor" class="form-control form-control-color" value="#0d6efd">
          </div>
          <div class="col-12">
            <label class="form-label small fw-semibold">Descrição</label>
            <textarea name="descricao" class="form-control" rows="2"></textarea>
          </div>

          <!-- Aviso de ocorrência de série (edição) — a regra em si não é editável aqui,
               só o escopo é escolhido ao salvar/excluir (ver confirmarSalvarEvento()). -->
          <div class="col-12 d-none" id="avisoRecorrencia">
            <div class="alert alert-secondary py-2 px-3 mb-0 small d-flex align-items-center gap-2">
              <i class="bi bi-arrow-repeat"></i>
              <span>Isto é parte de uma série: <strong id="avisoRecorrenciaTexto"></strong></span>
            </div>
          </div>

          <!-- Repetir (só na criação de um evento novo) -->
          <div class="col-12" id="blocoRepetir">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="repetir" value="1" id="fRepetir">
              <label class="form-check-label small fw-semibold" for="fRepetir">Repetir</label>
            </div>
            <div class="row g-2 mt-1 d-none" id="opcoesRepetir">
              <div class="col-md-7">
                <label class="form-label small">Frequência</label>
                <select name="repetir_freq" id="fRepetirFreq" class="form-select form-select-sm">
                  <option value="diaria">Diariamente</option>
                  <option value="semanal">Semanalmente</option>
                  <option value="mensal_dia">Mensalmente (no dia do mês)</option>
                  <option value="mensal_posicao" id="optMensalPosicao">Mensalmente (nessa posição)</option>
                  <option value="anual">Anualmente</option>
                </select>
              </div>
              <div class="col-md-5">
                <label class="form-label small">A cada</label>
                <div class="input-group input-group-sm">
                  <input type="number" name="repetir_intervalo" id="fRepetirIntervalo" class="form-control" value="1" min="1" max="99">
                  <span class="input-group-text" id="fRepetirIntervaloUnidade">dia(s)</span>
                </div>
              </div>
              <div class="col-12">
                <label class="form-label small d-block">Término</label>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="repetir_termino" value="nunca" id="fTerminoNunca" checked>
                    <label class="form-check-label small" for="fTerminoNunca">Nunca</label>
                  </div>
                  <div class="form-check form-check-inline mb-0">
                    <input class="form-check-input" type="radio" name="repetir_termino" value="apos" id="fTerminoApos">
                    <label class="form-check-label small" for="fTerminoApos">Após</label>
                  </div>
                  <input type="number" name="repetir_count" id="fRepetirCount" class="form-control form-control-sm" style="width:70px" value="10" min="1" disabled>
                  <span class="small text-muted">ocorrências</span>
                  <div class="form-check form-check-inline mb-0 ms-md-2">
                    <input class="form-check-input" type="radio" name="repetir_termino" value="ate" id="fTerminoAte">
                    <label class="form-check-label small" for="fTerminoAte">Até</label>
                  </div>
                  <input type="date" name="repetir_until" id="fRepetirUntil" class="form-control form-control-sm" style="width:150px" disabled>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-danger me-auto d-none" id="btnExcluirEvento" onclick="excluirEventoEmEdicao()">
          <i class="bi bi-trash me-1"></i>Excluir
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" id="btnSalvarEvento" onclick="confirmarSalvarEvento()">Salvar</button>
      </div>
    </form>
  </div>
</div>

<!-- Escolha de escopo (editar/excluir ocorrência de série) -->
<div class="modal fade" id="modalEscopoRecorrencia" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title" id="modalEscopoTitulo">Evento recorrente</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body d-grid gap-2">
        <button type="button" class="btn btn-outline-primary text-start" data-escopo="unico">Somente este evento</button>
        <button type="button" class="btn btn-outline-primary text-start" data-escopo="seguintes">Este e os seguintes</button>
        <button type="button" class="btn btn-outline-primary text-start" data-escopo="serie">Toda a série</button>
      </div>
    </div>
  </div>
</div>

<!-- Popover de criação rápida (clique em área vazia da grade) — título/hora/tipo/responsável;
     "Mais opções" abre o formulário completo (com recorrência, cliente, OS...). -->
<div class="ag-popover-rapido d-none" id="agendaPopoverRapido">
  <div class="ag-popover-rapido-topo">
    <span>Novo evento</span>
    <button type="button" class="btn-close" aria-label="Fechar" onclick="agendaFecharPopoverRapido()"></button>
  </div>
  <div class="ag-popover-rapido-corpo">
    <input type="text" id="prTitulo" class="form-control form-control-sm mb-2" placeholder="Título" maxlength="150">
    <div class="d-flex gap-2 mb-2">
      <input type="time" id="prHora" class="form-control form-control-sm">
      <select id="prTipo" class="form-select form-select-sm">
        <?php foreach (TipoEvento::cases() as $t): ?>
        <option value="<?= $t->value ?>"><?= e($t->rotulo()) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <select id="prUsuario" class="form-select form-select-sm mb-2">
      <?php foreach ($usuarios as $u): ?>
      <option value="<?= $u['id'] ?>"><?= e($u['nome']) ?></option>
      <?php endforeach; ?>
    </select>
    <div class="text-danger small d-none" id="prErro"></div>
  </div>
  <div class="ag-popover-rapido-rodape">
    <a href="#" onclick="agendaPopoverMaisOpcoes(); return false;">Mais opções</a>
    <button type="button" class="btn btn-primary btn-sm" id="prBtnSalvar" onclick="agendaPopoverSalvar()">Salvar</button>
  </div>
</div>

<!-- Toasts de sucesso/erro do arrastar/redimensionar/mover por teclado (com "Desfazer") -->
<div id="agendaToasts" aria-live="polite" aria-atomic="true"></div>

<script>
// Evento sendo editado no momento (guardado inteiro, com id/recorrencia_id/
// recorrencia_data_original/_rrule_texto) — usado por confirmarSalvarEvento() e
// excluirEventoEmEdicao() pra saber se precisa perguntar o escopo.
var eventoAtualJson = null;

function editarEvento(ev) {
  var form = document.getElementById('formEvento');
  eventoAtualJson = ev;

  // Título do modal
  document.getElementById('modalEventoTitulo').textContent = 'Editar evento';
  document.getElementById('btnSalvarEvento').textContent = 'Atualizar';
  document.getElementById('btnExcluirEvento').classList.remove('d-none');

  // Configurar form para edição
  document.getElementById('fEventoId').value = ev.id;

  // Ocorrência de série (virtual ou já materializada): a regra não é editável aqui — some o
  // toggle "Repetir" e mostra o aviso com o texto legível; o escopo (só este / seguintes /
  // série) é perguntado ao salvar/excluir.
  var ehRecorrente = !!ev.recorrente;
  document.getElementById('blocoRepetir').classList.toggle('d-none', ehRecorrente);
  document.getElementById('avisoRecorrencia').classList.toggle('d-none', !ehRecorrente);
  if (ehRecorrente) {
    document.getElementById('avisoRecorrenciaTexto').textContent = ev._rrule_texto || 'recorrente';
  }

  // Preencher campos
  form.querySelector('[name=titulo]').value      = ev.titulo || '';
  form.querySelector('[name=tipo]').value        = ev.tipo   || 'outro';
  form.querySelector('[name=usuario_id]').value  = ev.usuario_id || '';
  form.querySelector('[name=descricao]').value   = ev.descricao || '';
  form.querySelector('[name=cor]').value         = ev.cor || '#0d6efd';

  // Cliente/OS vinculados (se houver)
  document.getElementById('fClienteId').value = ev.cliente_id || '';
  document.getElementById('fClienteBusca').value = ev.cliente_nome || '';
  document.getElementById('fOsId').value = ev.os_id || '';
  document.getElementById('fOsBusca').value = ev.os_numero ? ('OS ' + ev.os_numero) : '';

  // Formatar datetime para datetime-local (YYYY-MM-DDTHH:MM)
  if (ev.data_inicio) {
    form.querySelector('[name=data_inicio]').value = ev.data_inicio.replace(' ', 'T').substring(0, 16);
  }
  if (ev.data_fim) {
    form.querySelector('[name=data_fim]').value = ev.data_fim.replace(' ', 'T').substring(0, 16);
  } else {
    form.querySelector('[name=data_fim]').value = '';
  }

  verificarConflito();
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEvento')).show();
}

// Resetar modal ao fechar
document.getElementById('modalEvento').addEventListener('hidden.bs.modal', function() {
  document.getElementById('modalEventoTitulo').textContent = 'Novo evento';
  document.getElementById('btnSalvarEvento').textContent   = 'Salvar';
  document.getElementById('fEventoId').value = '';
  document.getElementById('fRecorrenciaId').value = '';
  document.getElementById('fRecorrenciaData').value = '';
  document.getElementById('fEscopoRecorrencia').value = '';
  document.getElementById('btnExcluirEvento').classList.add('d-none');
  document.getElementById('blocoRepetir').classList.remove('d-none');
  document.getElementById('avisoRecorrencia').classList.add('d-none');
  document.getElementById('opcoesRepetir').classList.add('d-none');
  document.getElementById('fRepetir').checked = false;
  document.getElementById('avisoConflito').classList.add('d-none');
  document.getElementById('fOsLista').classList.remove('show');
  document.getElementById('fClienteLista').classList.remove('show');
  eventoAtualJson = null;
  document.getElementById('formEvento').reset();
  document.querySelector('[name=data_inicio]').value = '<?= date('Y-m-d\TH:i') ?>';
});

// Toggle "Repetir": mostra/some as opções e ajusta a unidade do "a cada N" e o rótulo da
// opção "mensal por posição" conforme a data de início escolhida (ex.: "2ª segunda-feira").
(function () {
  var chkRepetir  = document.getElementById('fRepetir');
  var opcoes      = document.getElementById('opcoesRepetir');
  var selFreq     = document.getElementById('fRepetirFreq');
  var inpIntervalo = document.getElementById('fRepetirIntervalo');
  var spanUnidade = document.getElementById('fRepetirIntervaloUnidade');
  var inpDataInicio = document.getElementById('fDataInicio');
  var optPosicao  = document.getElementById('optMensalPosicao');
  var radiosTermino = document.querySelectorAll('input[name=repetir_termino]');
  var inpCount    = document.getElementById('fRepetirCount');
  var inpUntil    = document.getElementById('fRepetirUntil');

  var unidades = { diaria: 'dia(s)', semanal: 'semana(s)', mensal_dia: 'mês(es)', mensal_posicao: 'mês(es)', anual: 'ano(s)' };

  var diasSemanaExt = ['domingo', 'segunda-feira', 'terça-feira', 'quarta-feira', 'quinta-feira', 'sexta-feira', 'sábado'];
  var diasSemanaMasc = { 0: true, 6: true };

  function atualizarRotuloPosicao() {
    if (!inpDataInicio.value) return;
    var dt = new Date(inpDataInicio.value);
    var dia = dt.getDate();
    var diaSemana = dt.getDay();
    var posicao = Math.floor((dia - 1) / 7) + 1;
    var diasNoMes = new Date(dt.getFullYear(), dt.getMonth() + 1, 0).getDate();
    var ultima = (dia + 7) > diasNoMes;
    var masc = diasSemanaMasc[diaSemana];
    var ordinal = ultima ? (masc ? 'último' : 'última') : (posicao + (masc ? 'º' : 'ª'));
    optPosicao.textContent = 'Mensalmente (' + ordinal + ' ' + diasSemanaExt[diaSemana] + ' do mês)';
  }

  chkRepetir.addEventListener('change', function () {
    opcoes.classList.toggle('d-none', !chkRepetir.checked);
  });

  selFreq.addEventListener('change', function () {
    spanUnidade.textContent = unidades[selFreq.value] || 'dia(s)';
  });

  inpDataInicio.addEventListener('change', atualizarRotuloPosicao);

  radiosTermino.forEach(function (r) {
    r.addEventListener('change', function () {
      var valor = document.querySelector('input[name=repetir_termino]:checked').value;
      inpCount.disabled = valor !== 'apos';
      inpUntil.disabled = valor !== 'ate';
    });
  });

  atualizarRotuloPosicao();
})();

// Escolha de escopo (Somente este / Este e os seguintes / Toda a série) — usada tanto pra
// editar quanto pra excluir uma ocorrência que faz parte de uma série.
function abrirEscolhaRecorrencia(acao, callback) {
  var modalEl = document.getElementById('modalEscopoRecorrencia');
  document.getElementById('modalEscopoTitulo').textContent =
    acao === 'excluir' ? 'Excluir evento recorrente' : 'Editar evento recorrente';
  var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
  var botoes = modalEl.querySelectorAll('[data-escopo]');
  function onClick(e) {
    var escopo = e.currentTarget.dataset.escopo;
    botoes.forEach(function (b) { b.removeEventListener('click', onClick); });
    modal.hide();
    callback(escopo);
  }
  botoes.forEach(function (b) { b.addEventListener('click', onClick); });
  modal.show();
}

function confirmarSalvarEvento() {
  var form = document.getElementById('formEvento');
  if (!form.reportValidity()) return;

  if (eventoAtualJson && eventoAtualJson.recorrente) {
    abrirEscolhaRecorrencia('editar', function (escopo) {
      document.getElementById('fRecorrenciaId').value = eventoAtualJson.recorrencia_id || eventoAtualJson.id;
      document.getElementById('fRecorrenciaData').value = eventoAtualJson.recorrencia_data_original || '';
      document.getElementById('fEscopoRecorrencia').value = escopo;
      form.submit();
    });
  } else {
    form.submit();
  }
}

function excluirEventoEmEdicao() {
  if (!eventoAtualJson) return;
  bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEvento')).hide();
  excluirEvento(eventoAtualJson);
}

// Exclusão (linha da lista do mês ou botão dentro do modal) — evento normal só confirma;
// ocorrência de série pergunta o escopo antes.
function excluirEvento(ev) {
  if (!ev.recorrente) {
    if (!confirm('Remover este evento?')) return;
    enviarExclusao(ev.id, null, null);
    return;
  }
  abrirEscolhaRecorrencia('excluir', function (escopo) {
    enviarExclusao(ev.recorrencia_id || ev.id, ev.recorrencia_data_original, escopo);
  });
}

function enviarExclusao(id, recorrenciaData, escopo) {
  var form = document.createElement('form');
  form.method = 'POST';
  form.action = '<?= url('/agenda') ?>/' + id;
  var campos = { _token: '<?= csrf_token() ?>', _method: 'DELETE' };
  if (recorrenciaData) {
    campos.recorrencia_data_original = recorrenciaData;
    campos.escopo_recorrencia = escopo;
  }
  Object.keys(campos).forEach(function (k) {
    var inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = k; inp.value = campos[k];
    form.appendChild(inp);
  });
  document.body.appendChild(form);
  form.submit();
}

// Busca (filtra a lista "Próximos 7 dias" pelo título) — some ícone <-> campo no mobile,
// os dois campos (desktop/mobile) ficam sincronizados.
(function () {
  var lista = document.getElementById('agendaProx7Lista');
  var inpDesktop = document.getElementById('agendaBuscaInput');
  var inpMobile  = document.getElementById('agendaBuscaInputMobile');
  var btnBusca   = document.getElementById('agendaBuscaBtn');
  var buscaExp   = document.getElementById('agendaBuscaExpandida');
  var semResultado = document.getElementById('agendaProx7SemResultadoBusca');

  function filtrar(termo) {
    if (!lista) return;
    termo = (termo || '').trim().toLowerCase();
    var visiveis = 0;
    lista.querySelectorAll('.ag7-linha').forEach(function (linha) {
      var bate = !termo || linha.dataset.titulo.indexOf(termo) !== -1;
      linha.style.display = bate ? '' : 'none';
      if (bate) visiveis++;
    });
    // Cabeçalho de dia some se nenhuma linha daquele grupo bateu com a busca.
    lista.querySelectorAll('.ag7-cabecalho').forEach(function (cab) {
      var el = cab.nextElementSibling;
      var algumaVisivel = false;
      while (el && !el.classList.contains('ag7-cabecalho')) {
        if (el.style.display !== 'none') algumaVisivel = true;
        el = el.nextElementSibling;
      }
      cab.style.display = algumaVisivel ? '' : 'none';
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

// Criação rápida a partir de qualquer visão (formulário completo) — usada pelo "Mais opções"
// do popover compacto. Data sempre preenchida, hora e técnico são opcionais.
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
  agendaAbrirPopoverRapido(event, dataIso, agendaHoraDoClique(horaIni, totalHoras, pct));
}

// Técnicos: mesma ideia, só que horizontal — e já manda o técnico da swimlane clicada.
function agendaTrilhaClick(event, dataIso, horaIni, totalHoras, usuarioId) {
  if (event.target !== event.currentTarget) return;
  var rect = event.currentTarget.getBoundingClientRect();
  var pct = (event.clientX - rect.left) / rect.width;
  agendaAbrirPopoverRapido(event, dataIso, agendaHoraDoClique(horaIni, totalHoras, pct), usuarioId);
}

function agendaHoraDoClique(horaIni, totalHoras, pct) {
  var horaClicada = horaIni + pct * totalHoras;
  var h = Math.floor(horaClicada);
  var m = Math.round((horaClicada - h) * 60 / 15) * 15; // arredonda pro quarto de hora
  if (m === 60) { m = 0; h++; }
  return String(h).padStart(2, '0') + ':' + String(m).padStart(2, '0');
}

// Grade mensal: clique em área vazia abre o popover de criação rápida com a data preenchida;
// setas do teclado navegam entre os dias (roving tabindex — só um <td> por vez
// tem tabindex="0", ver aria-label/role="gridcell" no markup).

function agendaCelClick(event, dataIso) {
  if (event.target !== event.currentTarget) return; // clique veio de um pill/botão filho, ignora
  agendaAbrirPopoverRapido(event, dataIso);
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

// Busca simples com resultados em dropdown — usada pelos campos de Cliente e OS do modal de
// evento. Sem lib nenhuma (o projeto não empacota JS): debounce + fetch + lista de botões.
function agendaCriarBusca(opts) {
  var timer = null;
  opts.inputEl.addEventListener('input', function () {
    opts.hiddenIdEl.value = '';
    clearTimeout(timer);
    var termo = opts.inputEl.value.trim();
    if (termo.length < 2) { opts.listEl.classList.remove('show'); opts.listEl.innerHTML = ''; return; }
    timer = setTimeout(function () {
      fetch(opts.url + '?q=' + encodeURIComponent(termo))
        .then(function (r) { return r.json(); })
        .then(function (itens) {
          if (!itens.length) {
            opts.listEl.innerHTML = '<div class="px-2 py-2 text-muted small">Nada encontrado</div>';
            opts.listEl.classList.add('show');
            return;
          }
          opts.listEl.innerHTML = itens.map(function (item, i) {
            return '<button type="button" class="dropdown-item small py-1" data-idx="' + i + '">' + opts.montarLinha(item) + '</button>';
          }).join('');
          opts.listEl.classList.add('show');
          opts.listEl.querySelectorAll('[data-idx]').forEach(function (btn) {
            btn.addEventListener('mousedown', function (e) { e.preventDefault(); }); // não perde o foco antes do click
            btn.addEventListener('click', function () {
              var item = itens[Number(btn.dataset.idx)];
              opts.hiddenIdEl.value = item.id;
              opts.inputEl.value = opts.montarTexto(item);
              opts.listEl.classList.remove('show');
              if (opts.aoSelecionar) opts.aoSelecionar(item);
            });
          });
        });
    }, 250);
  });
  opts.inputEl.addEventListener('blur', function () {
    setTimeout(function () { opts.listEl.classList.remove('show'); }, 150);
  });
}

(function () {
  agendaCriarBusca({
    inputEl: document.getElementById('fClienteBusca'),
    listEl: document.getElementById('fClienteLista'),
    hiddenIdEl: document.getElementById('fClienteId'),
    url: '<?= url('/api/clientes') ?>',
    montarLinha: function (c) { return c.nome + (c.telefone ? ' <span class="text-muted">· ' + c.telefone + '</span>' : ''); },
    montarTexto: function (c) { return c.nome; },
  });

  agendaCriarBusca({
    inputEl: document.getElementById('fOsBusca'),
    listEl: document.getElementById('fOsLista'),
    hiddenIdEl: document.getElementById('fOsId'),
    url: '<?= url('/api/os') ?>',
    montarLinha: function (os) {
      return 'OS ' + os.numero + ' — ' + os.cliente_nome + (os.equipamento ? ' <span class="text-muted">(' + os.equipamento + ')</span>' : '');
    },
    montarTexto: function (os) { return 'OS ' + os.numero; },
    // Selecionar uma OS preenche cliente + técnico responsável e sugere o título (só se o
    // título ainda estiver vazio — não atropela o que o usuário já digitou).
    aoSelecionar: function (os) {
      var fCliente = document.getElementById('fClienteBusca');
      var fClienteId = document.getElementById('fClienteId');
      if (os.cliente_id) {
        fClienteId.value = os.cliente_id;
        fCliente.value = os.cliente_nome || '';
      }
      var fUsuario = document.getElementById('fUsuarioId');
      if (os.tecnico_id && fUsuario.querySelector('option[value="' + os.tecnico_id + '"]')) {
        fUsuario.value = os.tecnico_id;
      }
      var fTitulo = document.getElementById('fTitulo');
      if (!fTitulo.value.trim()) fTitulo.value = os.titulo_sugerido || ('OS ' + os.numero);
      verificarConflito();
    },
  });
})();

// Aviso (não bloqueante) de conflito de horário do técnico escolhido — consulta
// /api/agenda/conflito ao trocar responsável/início/fim, com debounce.
var agendaConflitoTimer = null;
function verificarConflito() {
  clearTimeout(agendaConflitoTimer);
  agendaConflitoTimer = setTimeout(function () {
    var usuarioId = document.getElementById('fUsuarioId').value;
    var dataInicio = document.querySelector('#formEvento [name=data_inicio]').value;
    var dataFim = document.querySelector('#formEvento [name=data_fim]').value;
    var aviso = document.getElementById('avisoConflito');
    if (!usuarioId || !dataInicio) { aviso.classList.add('d-none'); return; }

    var excluirId = eventoAtualJson ? (eventoAtualJson.recorrencia_id || eventoAtualJson.id) : '';
    var params = new URLSearchParams({ usuario_id: usuarioId, data_inicio: dataInicio.replace('T', ' ') });
    if (dataFim) params.set('data_fim', dataFim.replace('T', ' '));
    if (excluirId) params.set('excluir_id', excluirId);

    fetch('<?= url('/api/agenda/conflito') ?>?' + params.toString())
      .then(function (r) { return r.json(); })
      .then(function (r) {
        if (r.conflito) {
          document.getElementById('avisoConflitoTexto').textContent =
            'Conflito de horário: ' + r.evento.titulo + ' (' + r.evento.hora + ') já está na agenda desse responsável.';
          aviso.classList.remove('d-none');
        } else {
          aviso.classList.add('d-none');
        }
      })
      .catch(function () {});
  }, 300);
}
['fUsuarioId', 'fDataInicio', 'fDataFim'].forEach(function (id) {
  var el = document.getElementById(id);
  if (el) el.addEventListener('change', verificarConflito);
});

// Popover de criação rápida (clique em área vazia de qualquer visão).
var agendaPopoverEstado = null; // { dataIso }

function agendaAbrirPopoverRapido(event, dataIso, hora, usuarioId) {
  var pop = document.getElementById('agendaPopoverRapido');
  agendaPopoverEstado = { dataIso: dataIso };

  document.getElementById('prTitulo').value = '';
  document.getElementById('prHora').value = hora || '09:00';
  document.getElementById('prTipo').value = 'outro';
  var selUsuario = document.getElementById('prUsuario');
  if (usuarioId && selUsuario.querySelector('option[value="' + usuarioId + '"]')) selUsuario.value = usuarioId;
  document.getElementById('prErro').classList.add('d-none');

  pop.classList.remove('d-none');
  agendaPosicionarPopover(pop, event);
  document.getElementById('prTitulo').focus();

  setTimeout(function () { document.addEventListener('click', agendaCliqueForaPopover); }, 0);
}

function agendaPosicionarPopover(pop, event) {
  var x = event.clientX, y = event.clientY;
  var largura = 260, altura = pop.offsetHeight || 220;
  if (x + largura > window.innerWidth - 10) x = window.innerWidth - largura - 10;
  if (y + altura > window.innerHeight - 10) y = window.innerHeight - altura - 10;
  pop.style.left = Math.max(10, x) + 'px';
  pop.style.top = Math.max(10, y) + 'px';
}

function agendaCliqueForaPopover(e) {
  var pop = document.getElementById('agendaPopoverRapido');
  if (pop.contains(e.target)) return;
  agendaFecharPopoverRapido();
}

function agendaFecharPopoverRapido() {
  document.getElementById('agendaPopoverRapido').classList.add('d-none');
  document.removeEventListener('click', agendaCliqueForaPopover);
  agendaPopoverEstado = null;
}

// "Mais opções": leva o que já foi digitado no popover pro formulário completo (recorrência,
// cliente, OS...) sem o usuário ter que redigitar.
function agendaPopoverMaisOpcoes() {
  if (!agendaPopoverEstado) return;
  var dataIso = agendaPopoverEstado.dataIso;
  var hora = document.getElementById('prHora').value;
  var usuarioId = document.getElementById('prUsuario').value;
  var titulo = document.getElementById('prTitulo').value;
  var tipo = document.getElementById('prTipo').value;
  agendaFecharPopoverRapido();
  abrirCriacaoRapida(dataIso, hora, usuarioId);
  if (titulo) document.querySelector('#formEvento [name=titulo]').value = titulo;
  document.querySelector('#formEvento [name=tipo]').value = tipo;
}

function agendaPopoverSalvar() {
  if (!agendaPopoverEstado) return;
  var titulo = document.getElementById('prTitulo').value.trim();
  var erro = document.getElementById('prErro');
  if (!titulo) {
    erro.textContent = 'Digite um título.';
    erro.classList.remove('d-none');
    document.getElementById('prTitulo').focus();
    return;
  }
  erro.classList.add('d-none');

  var dataIso = agendaPopoverEstado.dataIso;
  var hora = document.getElementById('prHora').value || '09:00';
  var btn = document.getElementById('prBtnSalvar');
  btn.disabled = true;
  btn.textContent = 'Salvando...';

  // Feedback imediato: um pill "fantasma" já aparece na célula do mês enquanto salva de
  // verdade. Semana/Dia/Técnicos não replicam aqui a matemática de posição/sobreposição por
  // hora do servidor (evita desalinhar) — pra essas visões o retorno visual vem da
  // atualização da grade logo abaixo, que já chega rápido (é só um fetch).
  var pillOtimista = agendaInserirPillOtimista(dataIso, titulo);

  var dados = new URLSearchParams();
  dados.set('_ajax', '1');
  dados.set('_token', '<?= csrf_token() ?>');
  dados.set('titulo', titulo);
  dados.set('tipo', document.getElementById('prTipo').value);
  dados.set('usuario_id', document.getElementById('prUsuario').value);
  dados.set('data_inicio', dataIso + 'T' + hora);

  fetch('<?= url('/agenda') ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: dados.toString(),
  })
    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
    .then(function (res) {
      btn.disabled = false;
      btn.textContent = 'Salvar';
      if (!res.ok || !res.json.sucesso) {
        if (pillOtimista) pillOtimista.remove();
        erro.textContent = (res.json && res.json.erro) || 'Não foi possível salvar.';
        erro.classList.remove('d-none');
        return;
      }
      agendaFecharPopoverRapido();
      agendaAtualizarGrade();
    })
    .catch(function () {
      btn.disabled = false;
      btn.textContent = 'Salvar';
      if (pillOtimista) pillOtimista.remove();
      erro.textContent = 'Falha de conexão. Tente novamente.';
      erro.classList.remove('d-none');
    });
}

function agendaInserirPillOtimista(dataIso, titulo) {
  var cel = document.querySelector('.ag-cel[data-data="' + dataIso + '"]');
  if (!cel) return null;
  var wrap = cel.querySelector('.ag-cel-eventos');
  if (!wrap) return null;
  var pill = document.createElement('div');
  pill.className = 'ag-pill ag-pill-otimista';
  pill.style.background = 'var(--surface-2, #e9ecef)';
  pill.style.color = 'var(--text-2, #495057)';
  pill.textContent = titulo;
  wrap.insertBefore(pill, wrap.firstChild);
  return pill;
}

// Atualiza só a grade (sem recarregar a página inteira): busca a própria URL atual, extrai
// #agendaGradeContainer da resposta e troca — reaproveita o render real do servidor (mesma
// query/layout de sempre) em vez de duplicar a lógica de posição/sobreposição em JS.
function agendaAtualizarGrade() {
  fetch(location.href)
    .then(function (r) { return r.text(); })
    .then(function (html) {
      var doc = new DOMParser().parseFromString(html, 'text/html');
      // Grade, indicadores e "Próximos 7 dias" podem todos mudar com uma única ação (ex.:
      // concluir um evento mexe no card "Em atraso" E some da lista) — reconcilia os três
      // com uma única busca da página atual.
      ['agendaGradeContainer', 'agendaIndicadores', 'agendaProx7Container'].forEach(function (id) {
        var novo = doc.getElementById(id);
        var atual = document.getElementById(id);
        if (novo && atual) atual.replaceWith(novo);
      });
      document.querySelectorAll('.ag-pill-mais').forEach(function (el) {
        bootstrap.Popover.getOrCreateInstance(el);
      });
    });
}

/* ── Reagendar por arrastar/soltar (mês/semana/dia/técnicos) + redimensionar + teclado ──
 * Todo evento (.ag-pill/.ag-ev-bloco/.ag-ev-barra) carrega data-evento (JSON completo, ver
 * agenda_evento_data_attr() em _evento.php) e três handlers: onclick (abre editar — suprimido
 * se acabou de arrastar), onpointerdown (inicia o arraste) e onkeydown ("m" entra em modo de
 * movimentação, setas movem, Enter confirma, Esc cancela — o mesmo drag-and-drop, sem mouse).
 * Toda mudança de data/hora/técnico passa por agendaCommitMover(), que já reaproveita o
 * fluxo de escopo de série (abrirEscolhaRecorrencia) e o /agenda via AJAX (_ajax=1).
 */

// ---- Motor de arraste (pointer events) — comum a todas as visões ----
var agendaSuprimirClique = false;
function agendaSuprimirProximoClique() {
  agendaSuprimirClique = true;
  setTimeout(function () { agendaSuprimirClique = false; }, 0);
}

function agendaCliqueEvento(event, el) {
  event.stopPropagation();
  if (agendaSuprimirClique) return; // clique disparado pelo navegador logo após soltar um arraste
  editarEvento(JSON.parse(el.dataset.evento));
}

// callbacks: { aoMover(dx, dy, clientX, clientY), aoSoltar(clientX, clientY) }
function agendaIniciarArraste(event, callbacks) {
  if (event.button !== undefined && event.button !== 0) return; // só botão esquerdo/toque primário
  var origemX = event.clientX, origemY = event.clientY;
  var moveu = false;
  var LIMIAR = 4; // px — abaixo disso é clique, não arraste

  function onMove(e) {
    var dx = e.clientX - origemX, dy = e.clientY - origemY;
    if (!moveu && (Math.abs(dx) > LIMIAR || Math.abs(dy) > LIMIAR)) moveu = true;
    if (moveu) { e.preventDefault(); callbacks.aoMover(dx, dy, e.clientX, e.clientY); }
  }
  function encerrar() {
    document.removeEventListener('pointermove', onMove);
    document.removeEventListener('pointerup', onUp);
    document.removeEventListener('pointercancel', onCancel);
  }
  function onUp(e) {
    encerrar();
    if (moveu) { agendaSuprimirProximoClique(); callbacks.aoSoltar(e.clientX, e.clientY); }
  }
  function onCancel() { encerrar(); }

  document.addEventListener('pointermove', onMove);
  document.addEventListener('pointerup', onUp);
  document.addEventListener('pointercancel', onCancel);
}

function agendaDuracaoMs(ev) {
  if (!ev.data_fim) return null;
  return new Date(ev.data_fim.replace(' ', 'T')) - new Date(ev.data_inicio.replace(' ', 'T'));
}
function agendaFormatarDatetime(d) {
  function p(n) { return String(n).padStart(2, '0'); }
  return d.getFullYear() + '-' + p(d.getMonth() + 1) + '-' + p(d.getDate()) + ' ' + p(d.getHours()) + ':' + p(d.getMinutes()) + ':' + p(d.getSeconds());
}
function agendaFormatarDataLegivel(datetimeStr) {
  var d = new Date(datetimeStr.replace(' ', 'T'));
  return d.toLocaleDateString('pt-BR') + ' ' + d.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' });
}

// ---- Mês: arrastar pill pra outro dia (só muda a data, hora fica igual) ----
function agendaArrastarMesInicio(event, el) {
  var ev = JSON.parse(el.dataset.evento);
  var celOrigem = el.closest('.ag-cel');
  var celAlvo = null;

  agendaIniciarArraste(event, {
    aoMover: function (dx, dy, clientX, clientY) {
      el.classList.add('ag-ev-arrastando');
      var alvo = document.elementFromPoint(clientX, clientY);
      var cel = alvo ? alvo.closest('.ag-cel') : null;
      if (celAlvo && celAlvo !== cel) celAlvo.classList.remove('ag-cel-alvo-drop');
      if (cel) cel.classList.add('ag-cel-alvo-drop');
      celAlvo = cel;
    },
    aoSoltar: function () {
      el.classList.remove('ag-ev-arrastando');
      if (celAlvo) celAlvo.classList.remove('ag-cel-alvo-drop');
      if (!celAlvo || celAlvo === celOrigem) return;
      var origem = new Date(ev.data_inicio.replace(' ', 'T'));
      var destino = new Date(celAlvo.dataset.data + 'T00:00:00');
      var origemDia = new Date(origem.getFullYear(), origem.getMonth(), origem.getDate());
      var deltaDias = Math.round((destino - origemDia) / 86400000);
      var novoInicio = new Date(origem); novoInicio.setDate(novoInicio.getDate() + deltaDias);
      var duracaoMs = agendaDuracaoMs(ev);
      var novoFim = duracaoMs != null ? agendaFormatarDatetime(new Date(novoInicio.getTime() + duracaoMs)) : null;
      agendaCommitMover(ev, agendaFormatarDatetime(novoInicio), novoFim, null, el);
    },
  });
}

// ---- Semana/Dia: arrastar bloco (muda dia+hora) e redimensionar a borda inferior (duração) ----
function agendaArrastarHorasInicio(event, el, horaIni, totalHoras) {
  var ev = JSON.parse(el.dataset.evento);
  var colOrigem = el.closest('.ag-hg-coluna');
  var duracaoMs = agendaDuracaoMs(ev);
  var colAlvo = null;

  agendaIniciarArraste(event, {
    aoMover: function (dx, dy, clientX, clientY) {
      el.classList.add('ag-ev-arrastando');
      var alvo = document.elementFromPoint(clientX, clientY);
      var col = alvo ? alvo.closest('.ag-hg-coluna') : null;
      if (colAlvo && colAlvo !== col) colAlvo.classList.remove('ag-hg-coluna-alvo-drop');
      if (col) col.classList.add('ag-hg-coluna-alvo-drop');
      colAlvo = col;
    },
    aoSoltar: function (clientX, clientY) {
      el.classList.remove('ag-ev-arrastando');
      if (colAlvo) colAlvo.classList.remove('ag-hg-coluna-alvo-drop');
      var colFinal = colAlvo || colOrigem;
      var rect = colFinal.getBoundingClientRect();
      var pct = Math.max(0, Math.min(1, (clientY - rect.top) / rect.height));
      var hora = agendaHoraDoClique(horaIni, totalHoras, pct);
      var novoInicio = colFinal.dataset.data + ' ' + hora + ':00';
      if (colFinal === colOrigem && novoInicio === ev.data_inicio) return;
      var novoFim = duracaoMs != null ? agendaFormatarDatetime(new Date(new Date(novoInicio.replace(' ', 'T')).getTime() + duracaoMs)) : null;
      agendaCommitMover(ev, novoInicio, novoFim, null, el);
    },
  });
}

function agendaRedimensionarInicio(event, el, horaIni, totalHoras) {
  event.stopPropagation(); // não deixa o pointerdown do bloco (mover) também disparar
  var ev = JSON.parse(el.dataset.evento);
  var col = el.closest('.ag-hg-coluna');
  var rect = col.getBoundingClientRect();

  agendaIniciarArraste(event, {
    aoMover: function () { el.classList.add('ag-ev-arrastando'); },
    aoSoltar: function (clientX, clientY) {
      el.classList.remove('ag-ev-arrastando');
      var pct = Math.max(0, Math.min(1, (clientY - rect.top) / rect.height));
      var horaFim = agendaHoraDoClique(horaIni, totalHoras, pct);
      var dataBase = ev.data_inicio.substring(0, 10);
      var novoFimDt = new Date((dataBase + ' ' + horaFim + ':00').replace(' ', 'T'));
      var inicioDt = new Date(ev.data_inicio.replace(' ', 'T'));
      if (novoFimDt <= inicioDt) novoFimDt = new Date(inicioDt.getTime() + 15 * 60000); // mínimo 15min
      agendaCommitMover(ev, ev.data_inicio, agendaFormatarDatetime(novoFimDt), null, el);
    },
  });
}

// ---- Técnicos: arrastar horizontal muda hora, entre linhas troca o responsável;
//      redimensionar a borda direita muda a duração ----
function agendaArrastarTecnicosInicio(event, el, horaIni, totalHoras) {
  var ev = JSON.parse(el.dataset.evento);
  var trilhaOrigem = el.closest('.ag-tec-linha-trilha');
  var duracaoMs = agendaDuracaoMs(ev);
  var trilhaAlvo = null;

  agendaIniciarArraste(event, {
    aoMover: function (dx, dy, clientX, clientY) {
      el.classList.add('ag-ev-arrastando');
      var alvo = document.elementFromPoint(clientX, clientY);
      var trilha = alvo ? alvo.closest('.ag-tec-linha-trilha') : null;
      if (trilhaAlvo && trilhaAlvo !== trilha) trilhaAlvo.classList.remove('ag-hg-coluna-alvo-drop');
      if (trilha) trilha.classList.add('ag-hg-coluna-alvo-drop');
      trilhaAlvo = trilha;
    },
    aoSoltar: function (clientX, clientY) {
      el.classList.remove('ag-ev-arrastando');
      if (trilhaAlvo) trilhaAlvo.classList.remove('ag-hg-coluna-alvo-drop');
      var trilhaFinal = trilhaAlvo || trilhaOrigem;
      var rect = trilhaFinal.getBoundingClientRect();
      var pct = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
      var hora = agendaHoraDoClique(horaIni, totalHoras, pct);
      var dataBase = ev.data_inicio.substring(0, 10);
      var novoInicio = dataBase + ' ' + hora + ':00';
      var novoUsuarioId = Number(trilhaFinal.dataset.usuarioId);
      if (trilhaFinal === trilhaOrigem && novoInicio === ev.data_inicio) return;
      var novoFim = duracaoMs != null ? agendaFormatarDatetime(new Date(new Date(novoInicio.replace(' ', 'T')).getTime() + duracaoMs)) : null;
      agendaCommitMover(ev, novoInicio, novoFim, novoUsuarioId, el);
    },
  });
}

function agendaRedimensionarTecnicoInicio(event, el, horaIni, totalHoras) {
  event.stopPropagation();
  var ev = JSON.parse(el.dataset.evento);
  var trilha = el.closest('.ag-tec-linha-trilha');
  var rect = trilha.getBoundingClientRect();

  agendaIniciarArraste(event, {
    aoMover: function () { el.classList.add('ag-ev-arrastando'); },
    aoSoltar: function (clientX) {
      el.classList.remove('ag-ev-arrastando');
      var pct = Math.max(0, Math.min(1, (clientX - rect.left) / rect.width));
      var horaFim = agendaHoraDoClique(horaIni, totalHoras, pct);
      var dataBase = ev.data_inicio.substring(0, 10);
      var novoFimDt = new Date((dataBase + ' ' + horaFim + ':00').replace(' ', 'T'));
      var inicioDt = new Date(ev.data_inicio.replace(' ', 'T'));
      if (novoFimDt <= inicioDt) novoFimDt = new Date(inicioDt.getTime() + 15 * 60000);
      agendaCommitMover(ev, ev.data_inicio, agendaFormatarDatetime(novoFimDt), null, el);
    },
  });
}

// ---- Mesma operação por teclado: foco no evento, "m" entra em modo de movimentação,
//      setas movem, Enter confirma, Esc cancela. Nunca é o único caminho (drag continua). ----
var agendaModoMovimento = null; // { el, ev, view, deltaDias, deltaMin, usuarioId }

function agendaEventoKeydown(event, el) {
  var emModo = agendaModoMovimento && agendaModoMovimento.el === el;

  if (!emModo && (event.key === 'm' || event.key === 'M')) {
    event.preventDefault();
    agendaEntrarModoMovimento(el);
    return;
  }
  if (!emModo) return; // Enter/Espaço seguem o comportamento nativo do <button> (abre editar)

  if (event.key === 'Escape') { event.preventDefault(); agendaCancelarModoMovimento(); return; }
  if (event.key === 'Enter') { event.preventDefault(); agendaConfirmarModoMovimento(); return; }
  if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].indexOf(event.key) !== -1) {
    event.preventDefault();
    agendaAjustarModoMovimento(event.key, event.shiftKey);
  }
}

function agendaEntrarModoMovimento(el) {
  var ev = JSON.parse(el.dataset.evento);
  var view = el.closest('.ag-grade') ? 'mes' : (el.closest('.ag-tec') ? 'tecnicos' : 'horas');
  agendaModoMovimento = { el: el, ev: ev, view: view, deltaDias: 0, deltaMin: 0, usuarioId: null };
  el.classList.add('ag-ev-movendo');
  agendaMostrarStatusMovimento();
}

function agendaAjustarModoMovimento(tecla, shift) {
  var m = agendaModoMovimento;
  if (m.view === 'mes') {
    if (tecla === 'ArrowLeft') m.deltaDias -= 1;
    if (tecla === 'ArrowRight') m.deltaDias += 1;
    if (tecla === 'ArrowUp') m.deltaDias -= 7;
    if (tecla === 'ArrowDown') m.deltaDias += 7;
  } else if (m.view === 'horas') {
    if (tecla === 'ArrowLeft') m.deltaDias -= 1;
    if (tecla === 'ArrowRight') m.deltaDias += 1;
    if (tecla === 'ArrowUp') m.deltaMin -= (shift ? 60 : 15);
    if (tecla === 'ArrowDown') m.deltaMin += (shift ? 60 : 15);
  } else if (m.view === 'tecnicos') {
    if (tecla === 'ArrowLeft') m.deltaMin -= (shift ? 60 : 15);
    if (tecla === 'ArrowRight') m.deltaMin += (shift ? 60 : 15);
    if (tecla === 'ArrowUp' || tecla === 'ArrowDown') {
      var trilhas = Array.prototype.slice.call(document.querySelectorAll('.ag-tec-linha-trilha'));
      var atualUid = m.usuarioId || m.ev.usuario_id;
      var idx = trilhas.findIndex(function (t) { return Number(t.dataset.usuarioId) === Number(atualUid); });
      var novoIdx = idx + (tecla === 'ArrowDown' ? 1 : -1);
      if (idx !== -1 && novoIdx >= 0 && novoIdx < trilhas.length) m.usuarioId = Number(trilhas[novoIdx].dataset.usuarioId);
    }
  }
  agendaMostrarStatusMovimento();
}

function agendaCalcularNovaData(m) {
  var base = new Date(m.ev.data_inicio.replace(' ', 'T'));
  base.setDate(base.getDate() + m.deltaDias);
  base.setMinutes(base.getMinutes() + m.deltaMin);
  var novoInicio = agendaFormatarDatetime(base);
  var duracaoMs = agendaDuracaoMs(m.ev);
  var novoFim = duracaoMs != null ? agendaFormatarDatetime(new Date(base.getTime() + duracaoMs)) : null;
  return { data_inicio: novoInicio, data_fim: novoFim, usuario_id: m.usuarioId };
}

function agendaMostrarStatusMovimento() {
  var m = agendaModoMovimento;
  var novo = agendaCalcularNovaData(m);
  var texto = 'Movendo "' + m.ev.titulo + '" para ' + agendaFormatarDataLegivel(novo.data_inicio);
  if (novo.usuario_id && Number(novo.usuario_id) !== Number(m.ev.usuario_id)) {
    var opt = document.querySelector('#fUsuarioId option[value="' + novo.usuario_id + '"]');
    texto += ' — ' + (opt ? opt.textContent : 'outro técnico');
  }
  var el = document.getElementById('agendaStatusMovimento');
  if (!el) {
    el = document.createElement('div');
    el.id = 'agendaStatusMovimento';
    el.className = 'ag-status-movendo';
    el.setAttribute('role', 'status');
    document.body.appendChild(el);
  }
  el.innerHTML = texto + ' — <kbd>&larr;&uarr;&darr;&rarr;</kbd> move, <kbd>Enter</kbd> confirma, <kbd>Esc</kbd> cancela';
}

function agendaCancelarModoMovimento() {
  if (!agendaModoMovimento) return;
  var el = agendaModoMovimento.el;
  el.classList.remove('ag-ev-movendo');
  var status = document.getElementById('agendaStatusMovimento');
  if (status) status.remove();
  agendaModoMovimento = null;
  el.focus();
}

function agendaConfirmarModoMovimento() {
  var m = agendaModoMovimento;
  if (!m || (m.deltaDias === 0 && m.deltaMin === 0 && !m.usuarioId)) { agendaCancelarModoMovimento(); return; }
  var novo = agendaCalcularNovaData(m);
  var ev = m.ev;
  agendaCancelarModoMovimento();
  agendaCommitMover(ev, novo.data_inicio, novo.data_fim, novo.usuario_id, null);
}

// ---- Aplica a mudança de verdade: pergunta o escopo se for série, salva via AJAX, atualiza
//      a grade e mostra toast de sucesso (com "Desfazer") ou erro (com rollback visual). ----
function agendaCommitMover(ev, novoInicio, novoFim, novoUsuarioId, elArrastado, escopoForcado) {
  function enviar(escopo) {
    var original = { data_inicio: ev.data_inicio, data_fim: ev.data_fim, usuario_id: ev.usuario_id };

    var dados = new URLSearchParams();
    dados.set('_ajax', '1');
    dados.set('_token', '<?= csrf_token() ?>');
    if (ev.id) dados.set('evento_id', ev.id);
    dados.set('titulo', ev.titulo || '');
    dados.set('descricao', ev.descricao || '');
    dados.set('tipo', ev.tipo || 'outro');
    dados.set('cor', ev.cor || '#0d6efd');
    if (ev.cliente_id) dados.set('cliente_id', ev.cliente_id);
    if (ev.os_id) dados.set('os_id', ev.os_id);
    dados.set('usuario_id', novoUsuarioId || ev.usuario_id || '');
    dados.set('data_inicio', novoInicio);
    if (novoFim) dados.set('data_fim', novoFim);
    if (ev.recorrente) {
      dados.set('recorrencia_id', ev.recorrencia_id || ev.id);
      dados.set('recorrencia_data_original', ev.recorrencia_data_original || '');
      dados.set('escopo_recorrencia', escopo || 'unico');
    }

    fetch('<?= url('/agenda') ?>', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: dados.toString(),
    })
      .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
      .then(function (res) {
        if (!res.ok || !res.json.sucesso) {
          if (elArrastado) elArrastado.classList.remove('ag-ev-arrastando');
          agendaToast((res.json && res.json.erro) || 'Não foi possível mover o evento.', 'erro');
          return;
        }
        agendaAtualizarGrade();
        // "Desfazer" reaplica o mesmo escopo já escolhido (não pergunta de novo) — exceto
        // "este e os seguintes", que divide a série em duas: desfazer não recompõe a divisão
        // de volta numa série só, só move essa ocorrência de volta pra data original.
        agendaToast('Evento reagendado.', 'sucesso', 'Desfazer', function () {
          var evRevertido = Object.assign({}, ev, { usuario_id: original.usuario_id });
          agendaCommitMover(evRevertido, original.data_inicio, original.data_fim, original.usuario_id, null, escopo === 'seguintes' ? 'unico' : escopo);
        });
      })
      .catch(function () {
        if (elArrastado) elArrastado.classList.remove('ag-ev-arrastando');
        agendaToast('Falha de conexão ao mover o evento.', 'erro');
      });
  }

  if (escopoForcado !== undefined) {
    enviar(escopoForcado);
  } else if (ev.recorrente) {
    abrirEscolhaRecorrencia('editar', enviar);
  } else {
    enviar(null);
  }
}

// ---- Toast (sucesso/erro), com botão de ação opcional ("Desfazer") ----
function agendaToast(msg, tipo, acaoTexto, acaoCallback) {
  var cont = document.getElementById('agendaToasts');
  var t = document.createElement('div');
  t.className = 'toast align-items-center text-white ' + (tipo === 'erro' ? 'bg-danger' : 'bg-success') + ' border-0 show';
  var html = '<div class="d-flex"><div class="toast-body">' + msg + '</div>';
  if (acaoTexto) html += '<button type="button" class="btn btn-sm btn-light fw-semibold my-auto me-2 ag-toast-acao">' + acaoTexto + '</button>';
  html += '<button type="button" class="btn-close btn-close-white me-2 m-auto" aria-label="Fechar"></button></div>';
  t.innerHTML = html;
  cont.appendChild(t);

  var removido = false;
  function remover() { if (removido) return; removido = true; t.remove(); }

  t.querySelector('.btn-close').addEventListener('click', remover);
  var btnAcao = t.querySelector('.ag-toast-acao');
  if (btnAcao && acaoCallback) btnAcao.addEventListener('click', function () { remover(); acaoCallback(); });

  setTimeout(remover, acaoTexto ? 8000 : 5000);
}

/* ── Ações rápidas da lista "Próximos 7 dias" (concluir/reagendar/cancelar) ── */
function agendaAcaoRapidaStatus(btn, novoStatus) {
  var ul = btn.closest('.dropdown-menu');
  agendaEnviarStatus(JSON.parse(ul.dataset.evento), novoStatus);
}

function agendaAcaoRapidaReagendar(btn) {
  var ul = btn.closest('.dropdown-menu');
  editarEvento(JSON.parse(ul.dataset.evento));
}

function agendaEnviarStatus(ev, novoStatus) {
  var statusOriginal = ev.status;
  var idAlvo = ev.recorrente ? (ev.recorrencia_id || ev.id) : ev.id;

  var dados = new URLSearchParams();
  dados.set('_ajax', '1');
  dados.set('_token', '<?= csrf_token() ?>');
  dados.set('status', novoStatus);
  if (ev.recorrente) {
    dados.set('recorrencia_data_original', ev.recorrencia_data_original || '');
    dados.set('data_inicio', ev.data_inicio);
    if (ev.data_fim) dados.set('data_fim', ev.data_fim);
  }

  fetch('<?= url('/agenda') ?>/' + idAlvo + '/status', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: dados.toString(),
  })
    .then(function (r) { return r.json().then(function (j) { return { ok: r.ok, json: j }; }); })
    .then(function (res) {
      if (!res.ok || !res.json.sucesso) {
        agendaToast((res.json && res.json.erro) || 'Não foi possível atualizar o status.', 'erro');
        return;
      }
      agendaAtualizarGrade();
      agendaToast('Status atualizado.', 'sucesso', 'Desfazer', function () {
        agendaEnviarStatus(Object.assign({}, ev, { status: novoStatus }), statusOriginal);
      });
    })
    .catch(function () { agendaToast('Falha de conexão ao atualizar o status.', 'erro'); });
}
</script>
