<?php
/*
 * "Componente de evento" compartilhado pelas 4 visões (mês, semana, dia, técnicos): decide
 * cor (por TipoEvento, por cor personalizada do evento, ou vermelho quando status=atrasado —
 * atrasado sempre tem prioridade sobre as duas, é o único sinal que não deveria sumir atrás de
 * uma escolha estética) e o miolo de conteúdo (ícone de recorrência + hora + título
 * [+ cliente]). Cada visão só decide o contêiner (pill, bloco vertical, barra horizontal) e a
 * posição.
 */

function agenda_evento_cor(array $ev): array
{
    if (($ev['status'] ?? '') === 'atrasado') {
        return \App\Enums\StatusEvento::Atrasado->config();
    }
    if (!empty($ev['cor'])) {
        return agenda_cor_personalizada($ev['cor']);
    }
    return (\App\Enums\TipoEvento::tryFrom($ev['tipo'] ?? '') ?? \App\Enums\TipoEvento::Outro)->config();
}

/** Monta a mesma estrutura light/dark que TipoEvento::config()/StatusEvento::config() devolvem,
 *  a partir de um hex escolhido livremente pelo usuário — sem a tabela de contraste
 *  pré-calibrada dos 7 tipos fixos (são infinitas cores possíveis aqui), o texto
 *  branco/escuro é decidido pela luminância relativa (WCAG), igual o preview de cor de
 *  status de OS (os_status/index.php) já faz sem cálculo nenhum, só visualmente. A cor fica
 *  igual nos dois temas — foi uma escolha deliberada do usuário, não deveria mudar sozinha ao
 *  trocar de tema como a paleta calibrada dos tipos fixos muda. */
function agenda_cor_personalizada(string $hex): array
{
    $cfg = ['pill_bg' => $hex, 'pill_texto' => agenda_texto_legivel_sobre($hex), 'barra' => $hex];
    return ['light' => $cfg, 'dark' => $cfg];
}

/** #1a1d23 (quase preto) ou #ffffff conforme a luminância relativa do fundo — mesma fórmula
 *  do W3C usada pra calcular contraste (não é uma tabela fixa, então não tem uma razão de
 *  contraste garantida como os pares pré-calibrados de TipoEvento/StatusEvento, mas cobre
 *  qualquer cor com uma leitura razoável). */
function agenda_texto_legivel_sobre(string $hex): string
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) !== 6 || !ctype_xdigit($hex)) return '#ffffff';
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    foreach ([&$r, &$g, &$b] as &$c) { $c = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4; }
    $luminancia = 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
    return $luminancia > 0.45 ? '#1a1d23' : '#ffffff';
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

/** Telefone (toque pra ligar, tel:) e endereço (toque pra abrir no mapa) do cliente — só
 *  aparecem no bloco detalhado (visão Dia), onde há espaço; é o cenário do técnico em campo
 *  no celular checando pra onde vai e ligando antes de sair. */
function agenda_evento_contato(array $ev): string
{
    $html = '';
    if (!empty($ev['cliente_telefone'])) {
        $telLimpo = preg_replace('/\D/', '', $ev['cliente_telefone']);
        $html .= '<a href="tel:' . e($telLimpo) . '" class="ag-ev-contato" onclick="event.stopPropagation()">'
               . '<i class="bi bi-telephone-fill"></i> ' . e($ev['cliente_telefone']) . '</a>';
    }
    if (!empty($ev['cliente_endereco'])) {
        $urlMapa = 'https://www.google.com/maps/search/?api=1&query=' . urlencode($ev['cliente_endereco']);
        $html .= '<a href="' . e($urlMapa) . '" '
               . 'target="_blank" rel="noopener" class="ag-ev-contato" onclick="event.stopPropagation()">'
               . '<i class="bi bi-geo-alt-fill"></i> ' . e($ev['cliente_endereco']) . '</a>';
    }
    return $html;
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
        $html .= agenda_evento_contato($ev);
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
