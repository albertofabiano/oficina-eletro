<?php

/**
 * Réplica isolada (sem banco) do cálculo de `serieDiaCompleta` em
 * FinanceiroPessoalController::montarResumoMensal() — o gráfico "Seu mês, dia a dia" (painel de
 * saldo acumulado + movimento diário, ver CLAUDE.md). Confirma a exigência do brief: o saldo do
 * dia de hoje tem que bater com `saldoAtual` e o saldo do último dia com `saldoPrevisto`, mesmo
 * com carryover de meses anteriores e pendências dos dois lados de "hoje" dentro do mês.
 */

require __DIR__ . '/../app/Helpers/functions.php';

$falhas = 0;
$total = 0;

function assert_igual($esperado, $real, string $msg): void
{
    global $falhas, $total;
    $total++;
    if (is_float($esperado) || is_float($real)) {
        $ok = abs($esperado - $real) < 0.001;
    } else {
        $ok = $esperado === $real;
    }
    if ($ok) {
        echo "  OK  $msg\n";
    } else {
        $falhas++;
        echo "  FALHA  $msg (esperado " . var_export($esperado, true) . ', veio ' . var_export($real, true) . ")\n";
    }
}

/**
 * Mesma fórmula do controller: dado o conjunto de lançamentos (todo o histórico do perfil, sem
 * filtro de mês), devolve [$saldoAtual, $saldoPrevisto, $serie] pro mês navegado.
 */
function montarSerieDiaCompleta(string $mesAtual, string $hoje, array $linhas): array
{
    $fimMesNav = date('Y-m-t', strtotime($mesAtual . '-01'));
    $primeiroDiaMes = $mesAtual . '-01';
    $diasNoMes = (int) date('t', strtotime($mesAtual . '-01'));

    $receitasPagasTotal = 0.0; $despesasPagasTotal = 0.0;
    $aReceberAteFim = 0.0; $aPagarAteFim = 0.0;
    $receitasPagasAntesMes = 0.0; $despesasPagasAntesMes = 0.0;
    $aReceberAntesMes = 0.0; $aPagarAntesMes = 0.0;
    $porDiaPago = []; $porDiaAberto = []; $porDiaPagoReceita = []; $porDiaAbertoReceita = [];

    foreach ($linhas as $l) {
        $valor = (float) $l['valor'];
        $pago = !empty($l['pago_em']);
        $efetiva = $l['vencimento'] ?: substr($l['data_hora'], 0, 10);
        $ymEfetiva = substr($efetiva, 0, 7);
        $ymLanc = substr($l['data_hora'], 0, 7);

        if ($l['tipo'] === 'receita') {
            if ($pago) {
                $receitasPagasTotal += $valor;
                if ($ymLanc < $mesAtual) { $receitasPagasAntesMes += $valor; }
            } elseif ($efetiva <= $fimMesNav) {
                $aReceberAteFim += $valor;
                if ($efetiva < $primeiroDiaMes) { $aReceberAntesMes += $valor; }
            }
            if ($pago && $ymLanc === $mesAtual) {
                $dia = (int) substr($l['data_hora'], 8, 2);
                $porDiaPagoReceita[$dia] = ($porDiaPagoReceita[$dia] ?? 0) + $valor;
            }
            if (!$pago && $ymEfetiva === $mesAtual) {
                $dia = (int) substr($efetiva, 8, 2);
                $porDiaAbertoReceita[$dia] = ($porDiaAbertoReceita[$dia] ?? 0) + $valor;
            }
            continue;
        }

        if ($pago) {
            $despesasPagasTotal += $valor;
            if ($ymLanc < $mesAtual) { $despesasPagasAntesMes += $valor; }
        } elseif ($efetiva <= $fimMesNav) {
            $aPagarAteFim += $valor;
            if ($efetiva < $primeiroDiaMes) { $aPagarAntesMes += $valor; }
        }
        if ($pago && $ymLanc === $mesAtual) {
            $dia = (int) substr($l['data_hora'], 8, 2);
            $porDiaPago[$dia] = ($porDiaPago[$dia] ?? 0) + $valor;
        }
        if (!$pago && $ymEfetiva === $mesAtual) {
            $dia = (int) substr($efetiva, 8, 2);
            $porDiaAberto[$dia] = ($porDiaAberto[$dia] ?? 0) + $valor;
        }
    }

    $saldoInicialContas = 1000.0;
    $saldoAtual = fixa_saldo_atual($saldoInicialContas, $receitasPagasTotal, $despesasPagasTotal);
    $saldoPrevisto = fixa_saldo_previsto($saldoAtual, $aReceberAteFim, $aPagarAteFim);

    $hojeYm = substr($hoje, 0, 7);
    $mesEhAtual = ($mesAtual === $hojeYm);
    $mesEhFuturo = ($mesAtual > $hojeYm);
    $splitDia = $mesEhAtual ? (int) substr($hoje, 8, 2) : ($mesEhFuturo ? 0 : $diasNoMes);

    $saldoInicioMes = $saldoAtual - $receitasPagasTotal + $despesasPagasTotal
        + $receitasPagasAntesMes - $despesasPagasAntesMes;

    $saldoAcc = $saldoInicioMes;
    $recAbertoAteSplit = 0.0; $despAbertoAteSplit = 0.0;
    $serie = [];
    for ($d = 1; $d <= $diasNoMes; $d++) {
        $recPagoDia = $porDiaPagoReceita[$d] ?? 0.0;
        $despPagoDia = $porDiaPago[$d] ?? 0.0;
        $recAbertoDia = $porDiaAbertoReceita[$d] ?? 0.0;
        $despAbertoDia = $porDiaAberto[$d] ?? 0.0;
        $futuro = $d > $splitDia;

        if (!$futuro) {
            $saldoAcc += $recPagoDia - $despPagoDia;
            $recAbertoAteSplit += $recAbertoDia;
            $despAbertoAteSplit += $despAbertoDia;
        } else {
            if ($d === $splitDia + 1) {
                $saldoAcc += ($aReceberAntesMes - $aPagarAntesMes) + ($recAbertoAteSplit - $despAbertoAteSplit);
            }
            $saldoAcc += $recPagoDia + $recAbertoDia - $despPagoDia - $despAbertoDia;
        }
        $serie[$d] = [
            'entrada' => round($recPagoDia + $recAbertoDia, 2),
            'pago'    => round($despPagoDia, 2),
            'aberto'  => round($despAbertoDia, 2),
            'saldo'   => round($saldoAcc, 2),
            'futuro'  => $futuro,
        ];
    }

    return [$saldoAtual, $saldoPrevisto, $splitDia, $diasNoMes, $serie];
}

echo "== Mês atual: carryover de antes do mês + pendências dos dois lados de hoje ==\n";
[$saldoAtual, $saldoPrevisto, $splitDia, $diasNoMes, $serie] = montarSerieDiaCompleta('2026-10', '2026-10-13', [
    ['tipo' => 'receita', 'valor' => 2000, 'data_hora' => '2026-09-20 10:00:00', 'vencimento' => null, 'pago_em' => '2026-09-20 10:00:00'],
    ['tipo' => 'despesa', 'valor' => 300, 'data_hora' => '2026-09-25 10:00:00', 'vencimento' => null, 'pago_em' => '2026-09-25 10:00:00'],
    ['tipo' => 'despesa', 'valor' => 150, 'data_hora' => '2026-09-28 10:00:00', 'vencimento' => '2026-09-30', 'pago_em' => null],
    ['tipo' => 'receita', 'valor' => 80, 'data_hora' => '2026-09-29 10:00:00', 'vencimento' => '2026-09-30', 'pago_em' => null],
    ['tipo' => 'receita', 'valor' => 5236.47, 'data_hora' => '2026-10-05 09:00:00', 'vencimento' => null, 'pago_em' => '2026-10-05 09:00:00'],
    ['tipo' => 'despesa', 'valor' => 42.90, 'data_hora' => '2026-10-05 10:00:00', 'vencimento' => null, 'pago_em' => '2026-10-05 10:00:00'],
    ['tipo' => 'despesa', 'valor' => 158.40, 'data_hora' => '2026-10-02 10:00:00', 'vencimento' => null, 'pago_em' => '2026-10-02 10:00:00'],
    ['tipo' => 'despesa', 'valor' => 36.71, 'data_hora' => '2026-10-10 10:00:00', 'vencimento' => '2026-10-10', 'pago_em' => null],
    ['tipo' => 'despesa', 'valor' => 337.38, 'data_hora' => '2026-10-15 10:00:00', 'vencimento' => '2026-10-15', 'pago_em' => null],
    ['tipo' => 'receita', 'valor' => 120, 'data_hora' => '2026-10-20 10:00:00', 'vencimento' => '2026-10-20', 'pago_em' => null],
    ['tipo' => 'despesa', 'valor' => 99999, 'data_hora' => '2026-11-05 10:00:00', 'vencimento' => null, 'pago_em' => null],
]);
assert_igual(13, $splitDia, 'splitDia é o dia de hoje quando o mês navegado é o atual');
assert_igual($saldoAtual, $serie[$splitDia]['saldo'], 'saldo[hoje] bate com o card "saldo atual"');
assert_igual($saldoPrevisto, $serie[$diasNoMes]['saldo'], 'saldo[último dia] bate com o card "previsto até fim do mês"');
assert_igual(5236.47, $serie[5]['entrada'], 'entrada do dia 5 é o valor combinado pago+aberto (só pago aqui)');
assert_igual(false, $serie[5]['futuro'], 'dia 5 (antes de hoje) não é futuro');
assert_igual(true, $serie[20]['futuro'], 'dia 20 (depois de hoje) é futuro/projetado');

echo "\n== Mês inteiramente no passado — sem projeção, 'aberto' nunca entra no saldo ==\n";
[$saldoAtualP, $saldoPrevistoP, $splitDiaP, $diasNoMesP, $serieP] = montarSerieDiaCompleta('2026-08', '2026-10-13', [
    ['tipo' => 'receita', 'valor' => 3000, 'data_hora' => '2026-08-05 09:00:00', 'vencimento' => null, 'pago_em' => '2026-08-05 09:00:00'],
    ['tipo' => 'despesa', 'valor' => 500, 'data_hora' => '2026-08-10 09:00:00', 'vencimento' => null, 'pago_em' => '2026-08-10 09:00:00'],
    ['tipo' => 'despesa', 'valor' => 70, 'data_hora' => '2026-08-28 09:00:00', 'vencimento' => '2026-08-28', 'pago_em' => null],
]);
assert_igual($diasNoMesP, $splitDiaP, 'mês passado: splitDia = todo o mês (nada é projeção)');
assert_igual(false, $serieP[$diasNoMesP]['futuro'], 'último dia de um mês passado nunca é "futuro"');
assert_igual(1000.0 + 3000 - 500, $serieP[$diasNoMesP]['saldo'], 'saldo final do mês passado ignora o "em aberto" vencido (só reflete o que foi pago de fato)');

echo "\n== Mês inteiramente no futuro — tudo projetado desde o dia 1, com carryover ==\n";
[$saldoAtualF, $saldoPrevistoF, $splitDiaF, $diasNoMesF, $serieF] = montarSerieDiaCompleta('2026-12', '2026-10-13', [
    ['tipo' => 'despesa', 'valor' => 150, 'data_hora' => '2026-09-28 10:00:00', 'vencimento' => '2026-09-30', 'pago_em' => null],
    ['tipo' => 'receita', 'valor' => 80, 'data_hora' => '2026-09-29 10:00:00', 'vencimento' => '2026-09-30', 'pago_em' => null],
    ['tipo' => 'despesa', 'valor' => 400, 'data_hora' => '2026-12-10 10:00:00', 'vencimento' => '2026-12-10', 'pago_em' => null],
]);
assert_igual(0, $splitDiaF, 'mês futuro: splitDia=0 (tudo projetado)');
assert_igual(true, $serieF[1]['futuro'], 'dia 1 de um mês futuro já é projetado');
assert_igual(1000.0 + 80 - 150 - 400, $serieF[$diasNoMesF]['saldo'], 'saldo final do mês futuro inclui o carryover de antes + a despesa do próprio mês');

echo "\n== Mês atual sem nenhum lançamento dentro dele — não quebra com arrays vazios ==\n";
[$saldoAtualV, $saldoPrevistoV, $splitDiaV, $diasNoMesV, $serieV] = montarSerieDiaCompleta('2026-10', '2026-10-13', [
    ['tipo' => 'receita', 'valor' => 1000, 'data_hora' => '2026-01-01 09:00:00', 'vencimento' => null, 'pago_em' => '2026-01-01 09:00:00'],
]);
assert_igual($saldoAtualV, $serieV[$splitDiaV]['saldo'], 'sem lançamento no mês: saldo[hoje] ainda bate com saldoAtual');
assert_igual($saldoPrevistoV, $serieV[$diasNoMesV]['saldo'], 'sem lançamento no mês: saldo[fim] ainda bate com saldoPrevisto');
assert_igual($saldoAtualV, $saldoPrevistoV, 'sem nada em aberto, previsto = atual');

echo "\n" . str_repeat('-', 60) . "\n";
echo "$total verificações, $falhas falha(s).\n";
exit($falhas > 0 ? 1 : 0);
