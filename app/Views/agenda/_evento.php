<?php
/*
 * "Componente de evento" compartilhado pelas 4 visões (mês, semana, dia, técnicos): decide
 * cor (por TipoEvento, ou vermelho quando status=atrasado — sempre com prioridade sobre a
 * cor do tipo) e o miolo de conteúdo (ícone de recorrência + hora + título [+ cliente]).
 * Cada visão só decide o contêiner (pill, bloco vertical, barra horizontal) e a posição.
 */

function agenda_evento_cor(array $ev): array
{
    if (($ev['status'] ?? '') === 'atrasado') {
        return \App\Enums\StatusEvento::Atrasado->config();
    }
    return (\App\Enums\TipoEvento::tryFrom($ev['tipo'] ?? '') ?? \App\Enums\TipoEvento::Outro)->config();
}

function agenda_evento_style(array $ev): string
{
    $cor = agenda_evento_cor($ev);
    return "--ag-ev-bg-light:{$cor['light']['pill_bg']}; --ag-ev-fg-light:{$cor['light']['pill_texto']}; "
         . "--ag-ev-bg-dark:{$cor['dark']['pill_bg']}; --ag-ev-fg-dark:{$cor['dark']['pill_texto']};";
}

/** JSON do evento como atributo data-* — fonte única lida tanto pelo clique (editarEvento) quanto
 *  pelo arrastar/redimensionar e pelo modo de movimentação por teclado (ver script no fim de
 *  agenda/index.php), em vez de embutir o JSON de novo em cada onclick. */
function agenda_evento_data_attr(array $ev): string
{
    return 'data-evento="' . htmlspecialchars(json_encode($ev), ENT_QUOTES) . '"';
}

/** Tooltip (atributo title) padrão de qualquer render do evento: "HH:MM Título" e, se for
 *  recorrente, o texto legível da regra numa segunda linha (agenda_rrule_descricao(), já
 *  resolvido no controller como $ev['_rrule_texto'] pra não recalcular por ocorrência). */
function agenda_evento_tooltip(array $ev): string
{
    $hora = date('H:i', strtotime($ev['data_inicio']));
    $texto = $hora . ' ' . $ev['titulo'];
    if (!empty($ev['os_numero'])) $texto .= ' (OS ' . $ev['os_numero'] . ')';
    if (!empty($ev['recorrente']) && !empty($ev['_rrule_texto'])) {
        $texto .= ' — ' . $ev['_rrule_texto'];
    }
    return $texto;
}

/** Badge "OS 123" com link pra tela da OS — só quando o evento está vinculado a uma. Clique
 *  para (o link), não dispara o clique-em-área-vazia da célula/coluna por baixo. */
function agenda_evento_os_badge(array $ev): string
{
    if (empty($ev['os_id']) || empty($ev['os_numero'])) return '';
    return '<a href="' . e(url('/os/' . $ev['os_id'])) . '" class="ag-ev-os" onclick="event.stopPropagation()">'
         . '<i class="bi bi-clipboard-check"></i> OS ' . e($ev['os_numero']) . '</a>';
}

function agenda_evento_conteudo(array $ev, bool $detalhe = false): string
{
    $hora = date('H:i', strtotime($ev['data_inicio']));
    $html = '';
    if (!empty($ev['recorrente'])) $html .= '<i class="bi bi-arrow-repeat"></i> ';
    $html .= '<span class="ag-ev-hora">' . e($hora) . '</span> ' . e($ev['titulo']);
    if ($detalhe) {
        if (!empty($ev['cliente_nome'])) {
            $html .= '<div class="ag-ev-cliente"><i class="bi bi-person"></i> ' . e($ev['cliente_nome']) . '</div>';
        }
        $html .= agenda_evento_os_badge($ev);
    }
    return $html;
}

/**
 * Distribui eventos de um período (dia inteiro) em "colunas"/"faixas" sem sobrepor
 * visualmente eventos simultâneos — usado pelas visões Semana/Dia (eixo vertical de horas:
 * cada faixa vira uma coluna) e Técnicos (eixo horizontal: cada faixa vira uma linha
 * empilhada dentro da mesma swimlane). Cada item retorna posição/tamanho ao longo do EIXO DE
 * TEMPO em % (posPct/tamPct) e sua faixa de sobreposição (faixa/totalFaixas) — quem chama
 * decide se isso mapeia pra top/height ou left/width.
 *
 * Sobreposições são agrupadas em clusters (eventos conectados transitivamente); a largura das
 * colunas é calculada por cluster, não pro dia inteiro, pra eventos isolados não ficarem
 * artificialmente estreitos por causa de uma sobreposição em outro horário do mesmo dia.
 */
function agenda_layout_periodo(array $eventos, float $horaIni, float $horaFim): array
{
    usort($eventos, fn ($a, $b) => strtotime($a['data_inicio']) <=> strtotime($b['data_inicio']));

    $intervalos = [];
    foreach ($eventos as $idx => $ev) {
        $ini = strtotime($ev['data_inicio']);
        $fim = !empty($ev['data_fim']) ? strtotime($ev['data_fim']) : $ini + 3600;
        if ($fim <= $ini) $fim = $ini + 1800;
        $intervalos[$idx] = [$ini, $fim];
    }

    $clusters = [];
    $clusterAtual = [];
    $fimClusterAtual = null;
    foreach ($intervalos as $idx => [$ini, $fim]) {
        if ($fimClusterAtual !== null && $ini >= $fimClusterAtual) {
            $clusters[] = $clusterAtual;
            $clusterAtual = [];
            $fimClusterAtual = null;
        }
        $clusterAtual[] = $idx;
        $fimClusterAtual = $fimClusterAtual === null ? $fim : max($fimClusterAtual, $fim);
    }
    if ($clusterAtual) $clusters[] = $clusterAtual;

    $totalHoras = $horaFim - $horaIni;
    $layout = [];

    foreach ($clusters as $cluster) {
        $colunas = [];
        $colDoIdx = [];
        foreach ($cluster as $idx) {
            [$ini, $fim] = $intervalos[$idx];
            foreach ($colunas as $c => $terminoOcupado) {
                if ($terminoOcupado !== null && $terminoOcupado <= $ini) $colunas[$c] = null;
            }
            $col = null;
            foreach ($colunas as $c => $terminoOcupado) {
                if ($terminoOcupado === null) { $col = $c; break; }
            }
            if ($col === null) { $col = count($colunas); $colunas[] = null; }
            $colunas[$col] = $fim;
            $colDoIdx[$idx] = $col;
        }
        $totalColunas = max(1, count($colunas));

        foreach ($cluster as $idx) {
            [$ini, $fim] = $intervalos[$idx];
            $horaIniEv = (float) date('H', $ini) + (float) date('i', $ini) / 60;
            $horaFimEv = (float) date('H', $fim) + (float) date('i', $fim) / 60;
            if ($horaFimEv <= $horaIniEv) $horaFimEv = $horaFim;

            $posPct = max(0, min(100, ($horaIniEv - $horaIni) / $totalHoras * 100));
            $fimPct = max(0, min(100, ($horaFimEv - $horaIni) / $totalHoras * 100));

            $layout[] = [
                'ev'          => $eventos[$idx],
                'posPct'      => $posPct,
                'tamPct'      => max(3, $fimPct - $posPct),
                'faixa'       => $colDoIdx[$idx],
                'totalFaixas' => $totalColunas,
            ];
        }
    }

    return $layout;
}
