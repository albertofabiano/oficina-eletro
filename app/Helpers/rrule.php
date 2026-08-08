<?php
/*
 * RRULE (RFC 5545) mínimo pra recorrência de eventos de agenda — só o subconjunto que a UI
 * expõe: FREQ=DAILY|WEEKLY|MONTHLY|YEARLY, INTERVAL, COUNT ou UNTIL, e BYDAY posicional
 * (2MO, -1FR...) pra "mensal por posição do dia da semana". Sem dependência externa (sem
 * Composer no projeto — ver CLAUDE.md) e sem materializar ocorrências: agenda_rrule_expandir()
 * gera só o que cai numa janela de datas, sob demanda.
 *
 * Toda expansão usa DateTime com fuso explícito (America/Sao_Paulo) e aritmética de
 * calendário (->modify('+1 month') etc.), nunca soma de segundos — é isso que mantém o
 * horário local estável (ex.: sempre 14:00) atravessando virada de horário de verão.
 */

const AGENDA_RRULE_TZ = 'America/Sao_Paulo';

function agenda_rrule_parse(string $rrule): array
{
    $rrule = preg_replace('/^RRULE:/', '', trim($rrule));
    $partes = [];
    foreach (explode(';', $rrule) as $par) {
        if (!str_contains($par, '=')) continue;
        [$chave, $valor] = explode('=', $par, 2);
        $partes[strtoupper(trim($chave))] = trim($valor);
    }
    return $partes;
}

function agenda_rrule_until_datetime(array $regra, DateTimeZone $tz): ?DateTime
{
    if (empty($regra['UNTIL'])) return null;
    $raw = $regra['UNTIL'];
    if (preg_match('/^\d{8}$/', $raw)) {
        $dt = DateTime::createFromFormat('Ymd', $raw, $tz);
        if ($dt) $dt->setTime(23, 59, 59);
        return $dt ?: null;
    }
    if (preg_match('/^\d{8}T\d{6}Z$/', $raw)) {
        $dt = DateTime::createFromFormat('Ymd\THis\Z', $raw, new DateTimeZone('UTC'));
        if ($dt) $dt->setTimezone($tz);
        return $dt ?: null;
    }
    return null;
}

/** BYDAY posicional ("2MO", "-1FR") -> dia do mês em que isso cai, ou null se não existir. */
function agenda_rrule_dia_por_posicao(int $ano, int $mes, string $byday, DateTimeZone $tz): ?DateTime
{
    if (!preg_match('/^(-?\d+)(MO|TU|WE|TH|FR|SA|SU)$/', $byday, $m)) return null;
    $posicao = (int) $m[1];
    $mapaDia = ['SU' => 0, 'MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6];
    $diaSemanaAlvo = $mapaDia[$m[2]];

    $diasNoMes = (int) (new DateTime(sprintf('%04d-%02d-01', $ano, $mes), $tz))->format('t');
    $ocorrenciasNoMes = [];
    for ($d = 1; $d <= $diasNoMes; $d++) {
        if ((int) (new DateTime(sprintf('%04d-%02d-%02d', $ano, $mes, $d), $tz))->format('w') === $diaSemanaAlvo) {
            $ocorrenciasNoMes[] = $d;
        }
    }
    if (!$ocorrenciasNoMes) return null;

    $dia = $posicao > 0
        ? ($ocorrenciasNoMes[$posicao - 1] ?? null)
        : ($ocorrenciasNoMes[count($ocorrenciasNoMes) + $posicao] ?? null);
    if ($dia === null) return null;

    return new DateTime(sprintf('%04d-%02d-%02d', $ano, $mes, $dia), $tz);
}

/**
 * Gera as ocorrências de $rrule (com DTSTART = $dataInicio) que caem dentro de
 * [$janelaDe, $janelaAte] (datas "Y-m-d", inclusive). Respeita COUNT/UNTIL contando desde o
 * DTSTART (não só dentro da janela) e nunca materializa além do fim da janela pedida.
 *
 * Retorna lista de 'Y-m-d H:i:s' em ordem cronológica.
 */
function agenda_rrule_expandir(string $dataInicio, string $rrule, string $janelaDe, string $janelaAte): array
{
    $tz = new DateTimeZone(AGENDA_RRULE_TZ);
    $regra = agenda_rrule_parse($rrule);
    if (empty($regra['FREQ'])) return [];

    $dtStart = new DateTime($dataInicio, $tz);
    $hora = (int) $dtStart->format('H');
    $min  = (int) $dtStart->format('i');
    $seg  = (int) $dtStart->format('s');

    $freq      = $regra['FREQ'];
    $intervalo = max(1, (int) ($regra['INTERVAL'] ?? 1));
    $count     = isset($regra['COUNT']) ? max(0, (int) $regra['COUNT']) : null;
    $until     = agenda_rrule_until_datetime($regra, $tz);

    $inicioJanela = new DateTime($janelaDe . ' 00:00:00', $tz);
    $fimJanela    = new DateTime($janelaAte . ' 23:59:59', $tz);
    $limite       = ($until !== null && $until < $fimJanela) ? $until : $fimJanela;

    $ocorrencias = [];
    $n = 0;
    $seguranca = 0;
    $maxIteracoes = 20000; // trava contra regra malformada/cursor que não avança

    if ($freq === 'DAILY' || $freq === 'WEEKLY') {
        $passo  = $freq === 'DAILY' ? "{$intervalo} days" : "{$intervalo} weeks";
        $cursor = clone $dtStart;
        while ($seguranca++ < $maxIteracoes) {
            $cursor->setTime($hora, $min, $seg);
            if ($cursor > $limite) break;
            $n++;
            if ($count !== null && $n > $count) break;
            if ($cursor >= $inicioJanela) $ocorrencias[] = $cursor->format('Y-m-d H:i:s');
            $cursor->modify("+$passo");
        }
    } elseif ($freq === 'MONTHLY' || $freq === 'YEARLY') {
        $porPosicao = $freq === 'MONTHLY' && !empty($regra['BYDAY']);
        $anoC    = (int) $dtStart->format('Y');
        $mesC    = (int) $dtStart->format('n');
        $mesAlvo = $mesC; // fixo pra YEARLY
        $diaAlvo = (int) $dtStart->format('j');

        while ($seguranca++ < $maxIteracoes) {
            if ($porPosicao) {
                $candidato = agenda_rrule_dia_por_posicao($anoC, $mesC, $regra['BYDAY'], $tz);
            } else {
                $m = $freq === 'YEARLY' ? $mesAlvo : $mesC;
                $candidato = checkdate($m, $diaAlvo, $anoC)
                    ? new DateTime(sprintf('%04d-%02d-%02d', $anoC, $m, $diaAlvo), $tz)
                    : null;
            }

            if ($candidato !== null) {
                $candidato->setTime($hora, $min, $seg);
                if ($candidato > $limite) break;
                $n++;
                if ($count !== null && $n > $count) break;
                if ($candidato >= $inicioJanela) $ocorrencias[] = $candidato->format('Y-m-d H:i:s');
            } else {
                // Passo sem candidato válido (ex.: 31 de fevereiro, 29/02 em ano não bissexto)
                // — não conta como ocorrência, só verifica se já passou do limite pra parar.
                $fimDoPasso = $freq === 'YEARLY'
                    ? new DateTime(sprintf('%04d-12-31 23:59:59', $anoC), $tz)
                    : (new DateTime(sprintf('%04d-%02d-01', $anoC, $mesC), $tz))->modify('last day of this month')->setTime(23, 59, 59);
                if ($fimDoPasso > $limite) break;
            }

            if ($freq === 'YEARLY') {
                $anoC += $intervalo;
            } else {
                $mesC += $intervalo;
                while ($mesC > 12) { $mesC -= 12; $anoC++; }
                while ($mesC < 1)  { $mesC += 12; $anoC--; }
            }
        }
    }

    return $ocorrencias;
}

/** Monta o BYDAY posicional ("2MO", "-1FR") a partir de uma data — usado quando o usuário
 *  escolhe "mensal por posição do dia da semana": a posição vem da própria data de início. */
function agenda_rrule_byday_da_data(DateTime $dt): string
{
    $mapaDia = [0 => 'SU', 1 => 'MO', 2 => 'TU', 3 => 'WE', 4 => 'TH', 5 => 'FR', 6 => 'SA'];
    $diaSemana = (int) $dt->format('w');
    $dia = (int) $dt->format('j');
    $posicao = intdiv($dia - 1, 7) + 1;

    $diasNoMes = (int) $dt->format('t');
    $ultimaOcorrencia = ($dia + 7) > $diasNoMes;

    return ($ultimaOcorrencia ? '-1' : (string) $posicao) . $mapaDia[$diaSemana];
}

/** Monta a string RRULE a partir dos campos do formulário de evento. Retorna null se
 *  "repetir" não estiver marcado. $dataInicio é a data_inicio do evento ("Y-m-d H:i:s" ou
 *  compatível com strtotime), usada como DTSTART implícito pra BYDAY posicional. */
function agenda_rrule_montar(array $dados, string $dataInicio): ?string
{
    if (empty($dados['repetir'])) return null;

    $freqMap = [
        'diaria'         => 'DAILY',
        'semanal'        => 'WEEKLY',
        'mensal_dia'     => 'MONTHLY',
        'mensal_posicao' => 'MONTHLY',
        'anual'          => 'YEARLY',
    ];
    $freqForm = $dados['repetir_freq'] ?? 'diaria';
    if (!isset($freqMap[$freqForm])) return null;

    $partes = ['FREQ=' . $freqMap[$freqForm]];

    $intervalo = max(1, (int) ($dados['repetir_intervalo'] ?? 1));
    if ($intervalo > 1) $partes[] = "INTERVAL=$intervalo";

    if ($freqForm === 'mensal_posicao') {
        $dt = new DateTime($dataInicio, new DateTimeZone(AGENDA_RRULE_TZ));
        $partes[] = 'BYDAY=' . agenda_rrule_byday_da_data($dt);
    }

    $termino = $dados['repetir_termino'] ?? 'nunca';
    if ($termino === 'apos') {
        $count = max(1, (int) ($dados['repetir_count'] ?? 1));
        $partes[] = "COUNT=$count";
    } elseif ($termino === 'ate' && !empty($dados['repetir_until'])) {
        $ate = str_replace('-', '', substr((string) $dados['repetir_until'], 0, 10));
        if (preg_match('/^\d{8}$/', $ate)) $partes[] = "UNTIL=$ate";
    }

    return implode(';', $partes);
}

/** Texto legível em português da regra ("Todo dia 10, mensalmente", "Toda segunda-feira"...). */
function agenda_rrule_descricao(string $rrule, string $dataInicio): string
{
    $regra = agenda_rrule_parse($rrule);
    if (empty($regra['FREQ'])) return '';

    $tz = new DateTimeZone(AGENDA_RRULE_TZ);
    $intervalo = max(1, (int) ($regra['INTERVAL'] ?? 1));
    $dt = new DateTime($dataInicio, $tz);
    $diasSemana = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
    $meses = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];

    switch ($regra['FREQ']) {
        case 'DAILY':
            $base = $intervalo === 1 ? 'Todo dia' : "A cada $intervalo dias";
            break;

        case 'WEEKLY':
            $w = (int) $dt->format('w');
            $nomeDia = mb_strtolower($diasSemana[$w]);
            $todo = in_array($w, [0, 6], true) ? 'Todo' : 'Toda';
            $base = $intervalo === 1 ? "$todo $nomeDia" : "A cada $intervalo semanas, $nomeDia";
            break;

        case 'MONTHLY':
            if (!empty($regra['BYDAY'])) {
                $base = agenda_rrule_descricao_posicao($regra['BYDAY'], $intervalo);
            } else {
                $dia = (int) $dt->format('j');
                $base = $intervalo === 1 ? "Todo dia $dia, mensalmente" : "A cada $intervalo meses, no dia $dia";
            }
            break;

        case 'YEARLY':
            $diaMes = $dt->format('j') . ' de ' . $meses[(int) $dt->format('n')];
            $base = $intervalo === 1 ? "Todo ano em $diaMes" : "A cada $intervalo anos, em $diaMes";
            break;

        default:
            return '';
    }

    if (!empty($regra['COUNT'])) {
        $n = (int) $regra['COUNT'];
        $base .= ", por $n " . ($n === 1 ? 'vez' : 'vezes');
    } elseif (!empty($regra['UNTIL'])) {
        $untilDt = agenda_rrule_until_datetime($regra, $tz);
        if ($untilDt) $base .= ', até ' . $untilDt->format('d/m/Y');
    }

    return $base;
}

function agenda_rrule_descricao_posicao(string $byday, int $intervalo): string
{
    if (!preg_match('/^(-?\d+)(MO|TU|WE|TH|FR|SA|SU)$/', $byday, $m)) return 'Mensalmente';
    $posicao = (int) $m[1];
    $mapaDiaFem  = ['MO' => 'segunda-feira', 'TU' => 'terça-feira', 'WE' => 'quarta-feira', 'TH' => 'quinta-feira', 'FR' => 'sexta-feira'];
    $mapaDiaMasc = ['SA' => 'sábado', 'SU' => 'domingo'];
    $ehMasc  = isset($mapaDiaMasc[$m[2]]);
    $nomeDia = $mapaDiaMasc[$m[2]] ?? $mapaDiaFem[$m[2]];

    $ordinaisFem  = [1 => '1ª', 2 => '2ª', 3 => '3ª', 4 => '4ª', 5 => '5ª'];
    $ordinaisMasc = [1 => '1º', 2 => '2º', 3 => '3º', 4 => '4º', 5 => '5º'];
    if ($posicao === -1) {
        $ordinal = $ehMasc ? 'último' : 'última';
    } else {
        $ordinal = ($ehMasc ? $ordinaisMasc : $ordinaisFem)[$posicao] ?? "{$posicao}º";
    }

    $prefixo = $ehMasc
        ? ($intervalo === 1 ? 'Todo' : "A cada $intervalo meses, no")
        : ($intervalo === 1 ? 'Toda' : "A cada $intervalo meses, na");

    return "$prefixo $ordinal $nomeDia do mês";
}
