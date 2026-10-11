<?php

namespace App\Services\Fixa;

/**
 * Carteira Fixa — motor de datas das "formas de pagamento" (ver ROTEIRO-carteira-fixa.md do
 * usuário, §3). Porta fiel de `datas.mjs` (validado contra 9 casos reais antes de chegar aqui,
 * ver tests/fixa_formas_pagamento_test.php) — mesma aritmética, só traduzida pra PHP.
 *
 * `$forma` é um array associativo com pelo menos `tipo` ('simples'|'cartao'|'recebivel') e,
 * conforme o tipo: `dia_fechamento`/`dia_vencimento` (cartao) ou `prazo_dias` (recebivel) — o
 * mesmo shape de uma linha de `financeiro_pessoal_formas_pagamento`.
 *
 * `$dataBase` é sempre a data do EVENTO no mundo real (compra, vencimento impresso, venda),
 * nunca a data efetiva já calculada — é essa separação que mantém o resto do app sem exceção:
 * o campo que os cálculos de saldo usam é sempre a data efetiva (gravada em `pago_em`/
 * `vencimento` pelo chamador), nunca `data_evento` (que é só informativo).
 */
class FormaPagamentoService
{
    /**
     * Resolve forma + data do evento + valor + parcelas em N lançamentos (data efetiva +
     * valor rateado por parcela). Rateio de centavos: a sobra fica na primeira parcela.
     *
     * @param array{tipo:string,dia_fechamento?:?int,dia_vencimento?:?int,prazo_dias?:?int} $forma
     * @return list<array{data:string,valor:float,parcela:int,parcelas:int}>
     */
    public static function resolverParcelas(array $forma, string $dataBase, float $valor, int $parcelas = 1): array
    {
        $parcelas = max(1, $parcelas);
        $valores  = self::ratear($valor, $parcelas);
        $tipo     = $forma['tipo'] ?? 'simples';

        $out = [];
        foreach ($valores as $i => $v) {
            if ($tipo === 'cartao') {
                $fechamento = (int) ($forma['dia_fechamento'] ?? 1);
                $vencimento = (int) ($forma['dia_vencimento'] ?? 1);
                $data = self::addMeses(self::vencimentoFatura($dataBase, $fechamento, $vencimento), $i);
            } elseif ($tipo === 'recebivel') {
                $prazoDias = (int) ($forma['prazo_dias'] ?? 30);
                $data = self::addDias($dataBase, $prazoDias + 30 * $i);
            } else {
                $data = self::addMeses($dataBase, $i);
            }
            $out[] = ['data' => $data, 'valor' => $v, 'parcela' => $i + 1, 'parcelas' => $parcelas];
        }
        return $out;
    }

    /** Soma N dias a uma data Y-m-d. */
    public static function addDias(string $data, int $n): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $data);
        $d->modify($n . ' days');
        return $d->format('Y-m-d');
    }

    /** Soma N meses mantendo o dia original; encurta só quando o mês de destino não tem esse
     *  dia (ex.: 31/jan + 1 mês = 28/fev — mas 31/jan + 2 meses ancora em jan de novo, não em
     *  fev, então vira 31/mar, nunca fica preso no encurtamento do mês anterior). */
    public static function addMeses(string $data, int $n): string
    {
        $d = \DateTime::createFromFormat('Y-m-d', $data);
        $diaOriginal = (int) $d->format('d');

        $alvo = (clone $d)->modify('first day of this month')->modify($n . ' months');
        $ultimoDiaAlvo = (int) $alvo->format('t');
        $diaFinal = min($diaOriginal, $ultimoDiaAlvo);

        return $alvo->format('Y-m-') . str_pad((string) $diaFinal, 2, '0', STR_PAD_LEFT);
    }

    /** Em qual fatura cai uma compra feita em `$dataBase`, e quando essa fatura vence. */
    public static function vencimentoFatura(string $dataBase, int $fechamento, int $vencimento): string
    {
        $d    = \DateTime::createFromFormat('Y-m-d', $dataBase);
        $ano  = (int) $d->format('Y');
        $mes  = (int) $d->format('n') - 1; // 0-based, mesmo raciocínio do getMonth() em JS
        $dia  = (int) $d->format('j');

        if ($dia > $fechamento) $mes += 1;          // passou do fechamento -> próxima fatura
        if ($vencimento <= $fechamento) $mes += 1;   // fatura vence no mês seguinte ao fechamento

        $ano += intdiv($mes, 12);
        $mes = (($mes % 12) + 12) % 12; // normaliza mês negativo/>=12

        return sprintf('%04d-%02d-%02d', $ano, $mes + 1, $vencimento);
    }

    /** Rateio de centavos — a sobra vai na primeira parcela.
     * @return list<float> */
    public static function ratear(float $valor, int $n): array
    {
        $n = max(1, $n);
        $cent  = (int) round($valor * 100);
        $base  = intdiv($cent, $n);
        $resto = $cent - $base * $n;

        $out = [];
        for ($i = 0; $i < $n; $i++) {
            $out[] = ($base + ($i === 0 ? $resto : 0)) / 100;
        }
        return $out;
    }
}
