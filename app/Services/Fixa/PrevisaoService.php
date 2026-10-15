<?php

namespace App\Services\Fixa;

/**
 * Carteira Fixa — Previsão financeira: projeta saldo futuro combinando lançamentos já
 * gravados (pagos e em aberto) com ocorrências futuras de recorrentes SEM `data_fim` que ainda
 * não foram materializadas por RecorrenteService::gerarPendentes() (que só grava mês atual +
 * próximo, de propósito — ver comentário de mesesAGerar() lá, pra nunca inserir uma série
 * infinita de uma vez). Esta classe NUNCA grava nada no banco — é só leitura + simulação em
 * memória; o motor de datas de verdade pra cartão/recebível (FormaPagamentoService) já grava
 * TODAS as parcelas de uma compra de uma vez na criação dela, então parcela de cartão nunca
 * precisa ser simulada aqui, só lida.
 *
 * Mesma regra de "data efetiva" já usada em FinanceiroPessoalController::montarResumoMensal():
 * vencimento quando preenchido, senão a data do próprio lançamento. Transferência (`tipo=
 * 'transferencia'`) é sempre ignorada, mesmo critério usado em todo o resto do módulo.
 */
class PrevisaoService
{
    /** Teto de segurança pro loop de meses da simulação de recorrentes — nenhum período de
     *  previsão real deveria chegar perto disso (3 anos de meses); existe só pra nunca rodar
     *  indefinidamente se `$dataFim` vier malformado. */
    private const MESES_SIMULACAO_MAX = 36;

    /**
     * @return array{
     *   saldoAtual: float,
     *   saldoFinal: float,
     *   dias: list<array{data:string,receitas:float,despesas:float,saldo:float,negativo:bool,itens:array}>,
     *   diasNegativos: list<string>,
     *   proximasFaturas: list<array{forma_id:int,forma_nome:string,cor:?string,icone:?string,vencimento:string,total:float,qtd:int}>,
     *   incompleta: bool,
     * }
     */
    public static function projetar(\PDO $db, int $usuarioId, int $perfilId, string $dataInicio, string $dataFim): array
    {
        $saldoAtual = self::saldoAtualDoPerfil($db, $perfilId);

        $itens = self::lancamentosAbertosNoPeriodo($db, $usuarioId, $perfilId, $dataInicio, $dataFim);
        $itens = array_merge($itens, self::recorrentesSimuladasNoPeriodo($db, $usuarioId, $perfilId, $dataInicio, $dataFim));

        $porDia = [];
        for ($cursor = $dataInicio; $cursor <= $dataFim; $cursor = date('Y-m-d', strtotime($cursor . ' +1 day'))) {
            $porDia[$cursor] = ['receitas' => 0.0, 'despesas' => 0.0, 'itens' => []];
        }
        foreach ($itens as $it) {
            if (!isset($porDia[$it['data']])) continue; // defesa — não deveria acontecer
            if ($it['tipo'] === 'receita') { $porDia[$it['data']]['receitas'] += $it['valor']; }
            else { $porDia[$it['data']]['despesas'] += $it['valor']; }
            $porDia[$it['data']]['itens'][] = $it;
        }

        $dias = [];
        $acc = $saldoAtual;
        $diasNegativos = [];
        foreach ($porDia as $data => $d) {
            $acc = round($acc + $d['receitas'] - $d['despesas'], 2);
            $negativo = $acc < 0;
            if ($negativo) { $diasNegativos[] = $data; }
            $dias[] = [
                'data'     => $data,
                'receitas' => round($d['receitas'], 2),
                'despesas' => round($d['despesas'], 2),
                'saldo'    => $acc,
                'negativo' => $negativo,
                'itens'    => $d['itens'],
            ];
        }

        return [
            'saldoAtual'      => $saldoAtual,
            'saldoFinal'      => $acc,
            'dias'            => $dias,
            'diasNegativos'   => $diasNegativos,
            'proximasFaturas' => self::proximasFaturas($db, $usuarioId, $perfilId, $dataInicio, $dataFim),
            'incompleta'      => empty($itens) && !self::perfilTemAlgumLancamento($db, $usuarioId, $perfilId),
        ];
    }

    /**
     * Mesma fórmula de FinanceiroPessoalController::saldoAtualDoPerfil() — propositalmente com
     * query própria em vez de reaproveitar aquele método (privado, recebe os totais já
     * calculados pelo caller pra montarResumoMensal() não repetir a varredura de lançamentos).
     * Mudar a assinatura dele só pra ficar reaproveitável aqui teria risco de regressão no
     * Resumo, que já está em produção e testado; a FÓRMULA em si (fixa_saldo_atual() + desconto
     * de caixinhas) é a mesma função pura nos dois lugares, só chamada com dados buscados cada
     * um à sua maneira — não é uma segunda fórmula divergente, é a mesma conta calculada 2x.
     */
    private static function saldoAtualDoPerfil(\PDO $db, int $perfilId): float
    {
        $stContas = $db->prepare(
            "SELECT COALESCE(SUM(saldo_inicial), 0) FROM financeiro_pessoal_contas WHERE perfil_id = ? AND arquivada = 0"
        );
        $stContas->execute([$perfilId]);
        $saldoInicialTotal = (float) $stContas->fetchColumn();

        $stPagos = $db->prepare(
            "SELECT tipo, COALESCE(SUM(valor), 0) AS total FROM financeiro_pessoal_lancamentos
             WHERE perfil_id = ? AND pago_em IS NOT NULL AND tipo IN ('receita','despesa')
             GROUP BY tipo"
        );
        $stPagos->execute([$perfilId]);
        $receitasPagas = 0.0;
        $despesasPagas = 0.0;
        foreach ($stPagos->fetchAll() as $r) {
            if ($r['tipo'] === 'receita') { $receitasPagas = (float) $r['total']; }
            else { $despesasPagas = (float) $r['total']; }
        }

        $saldo = fixa_saldo_atual($saldoInicialTotal, $receitasPagas, $despesasPagas);
        $saldo -= CaixinhaService::saldoTotalCaixinhas($db, $perfilId) / 100;

        return round($saldo, 2);
    }

    /** Lançamentos já gravados (reais), não pagos, com data efetiva dentro do período — mesmo
     *  critério de "aReceberAteFim"/"aPagarAteFim" de montarResumoMensal(): vencimento quando
     *  preenchido, senão a data do lançamento em si. Nunca transferência. */
    private static function lancamentosAbertosNoPeriodo(\PDO $db, int $usuarioId, int $perfilId, string $dataInicio, string $dataFim): array
    {
        $st = $db->prepare(
            "SELECT id, tipo, categoria, descricao, valor, data_hora, vencimento, forma_pagamento_id
             FROM financeiro_pessoal_lancamentos
             WHERE usuario_id = ? AND perfil_id = ? AND pago_em IS NULL AND tipo IN ('receita','despesa')
               AND COALESCE(vencimento, DATE(data_hora)) BETWEEN ? AND ?"
        );
        $st->execute([$usuarioId, $perfilId, $dataInicio, $dataFim]);

        $hoje = date('Y-m-d');
        $out = [];
        foreach ($st->fetchAll() as $l) {
            $efetiva = $l['vencimento'] ?: substr($l['data_hora'], 0, 10);
            $out[] = [
                'origem'    => 'real',
                'id'        => (int) $l['id'],
                'data'      => $efetiva,
                'tipo'      => $l['tipo'],
                'categoria' => $l['categoria'],
                'descricao' => $l['descricao'],
                'valor'     => (float) $l['valor'],
                'formaId'   => $l['forma_pagamento_id'] !== null ? (int) $l['forma_pagamento_id'] : null,
                'vencido'   => $efetiva < $hoje,
            ];
        }
        return $out;
    }

    /**
     * Ocorrências futuras de recorrentes ATIVAS, SEM `data_fim`, dentro do período pedido, que
     * ainda não têm lançamento materializado (RecorrenteService::gerarPendentes() só grava mês
     * atual + próximo). Simula sem gravar nada. Recorrente COM `data_fim` é ignorada aqui de
     * propósito: ela já é materializada de uma vez só na criação (até 60 meses, ver
     * RecorrenteService::mesesAGerar()), então toda ocorrência dela dentro de qualquer período
     * razoável de previsão já está em `financeiro_pessoal_lancamentos`, lida pela função acima —
     * simular de novo duplicaria.
     *
     * Reaproveita RecorrenteService::dataVencimentoNoMes() (clamp de dia + próximo dia útil) —
     * nunca uma segunda implementação dessa conta. A checagem de "já existe?" é a MESMA query de
     * RecorrenteService::gerarPendentes() (por recorrente_id + vencimento/data_evento), então
     * uma ocorrência que a visita normal ao módulo já materializou nunca é contada duas vezes.
     */
    private static function recorrentesSimuladasNoPeriodo(\PDO $db, int $usuarioId, int $perfilId, string $dataInicio, string $dataFim): array
    {
        $st = $db->prepare(
            "SELECT * FROM financeiro_pessoal_recorrentes
             WHERE usuario_id = ? AND perfil_id = ? AND ativo = 1 AND data_fim IS NULL"
        );
        $st->execute([$usuarioId, $perfilId]);
        $recorrentes = $st->fetchAll();
        if (!$recorrentes) { return []; }

        $hoje = date('Y-m-d');
        $mesFimPeriodo = substr($dataFim, 0, 7);
        $out = [];

        foreach ($recorrentes as $r) {
            $forma = null;
            if (!empty($r['forma_pagamento_id'])) {
                $stF = $db->prepare(
                    "SELECT * FROM financeiro_pessoal_formas_pagamento WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
                );
                $stF->execute([$r['forma_pagamento_id'], $usuarioId, $perfilId]);
                $forma = $stF->fetch() ?: null;
            }

            $cursorMes = date('Y-m');
            $guard = 0;
            while ($cursorMes <= $mesFimPeriodo && $guard < self::MESES_SIMULACAO_MAX) {
                $guard++;
                $dataEvento = RecorrenteService::dataVencimentoNoMes((int) $r['dia_vencimento'], $cursorMes);
                $vencimento = $forma
                    ? FormaPagamentoService::resolverParcelas($forma, $dataEvento, (float) $r['valor'], 1)[0]['data']
                    : $dataEvento;

                $cursorMesAnterior = $cursorMes;
                $cursorMes = date('Y-m', strtotime($cursorMes . '-01 +1 month'));

                if ($vencimento < $hoje) { continue; } // nunca simula ocorrência já vencida pro passado
                if ($vencimento < $dataInicio || $vencimento > $dataFim) { continue; }
                if ($r['data_inicio'] !== null && $dataEvento < $r['data_inicio']) { continue; }

                $colunaChave = $forma ? 'data_evento' : 'vencimento';
                $existe = $db->prepare(
                    "SELECT 1 FROM financeiro_pessoal_lancamentos WHERE recorrente_id = ? AND $colunaChave = ? LIMIT 1"
                );
                $existe->execute([$r['id'], $dataEvento]);
                if ($existe->fetchColumn()) { continue; } // já materializada — já entrou pela query de lançamentos reais

                $out[] = [
                    'origem'    => 'simulado',
                    'id'        => null,
                    'data'      => $vencimento,
                    'tipo'      => $r['tipo'],
                    'categoria' => $r['categoria'],
                    'descricao' => $r['descricao'],
                    'valor'     => (float) $r['valor'],
                    'formaId'   => $forma['id'] ?? null,
                    'vencido'   => false,
                ];
                unset($cursorMesAnterior); // só usada pra clareza de leitura, sem efeito no cálculo
            }
        }

        return $out;
    }

    /**
     * Lista simplificada de "próximas faturas" (versão enxuta, decisão do usuário: agregação
     * completa com histórico fica pro item de prioridade seguinte, "fatura agregada de cartão" —
     * aqui é só o suficiente pra Previsão não esconder um compromisso grande de cartão).
     * Agrupa lançamentos REAIS (já gravados) por forma de pagamento tipo 'cartao' + data de
     * vencimento, somando o valor — cada parcela continua sendo uma linha própria no banco, só a
     * exibição soma. Não cobre recorrente simulada vinculada a forma=cartão (não existe linha no
     * banco pra agrupar ainda) — limitação aceita nesta versão simples.
     */
    private static function proximasFaturas(\PDO $db, int $usuarioId, int $perfilId, string $dataInicio, string $dataFim): array
    {
        $st = $db->prepare(
            "SELECT fp.id AS forma_id, fp.nome AS forma_nome, fp.cor, fp.icone,
                    COALESCE(l.vencimento, DATE(l.data_hora)) AS vencimento,
                    SUM(l.valor) AS total, COUNT(*) AS qtd
             FROM financeiro_pessoal_lancamentos l
             JOIN financeiro_pessoal_formas_pagamento fp ON fp.id = l.forma_pagamento_id
             WHERE l.usuario_id = ? AND l.perfil_id = ? AND l.pago_em IS NULL AND l.tipo = 'despesa'
               AND fp.tipo = 'cartao'
               AND COALESCE(l.vencimento, DATE(l.data_hora)) BETWEEN ? AND ?
             GROUP BY fp.id, vencimento
             ORDER BY vencimento ASC"
        );
        $st->execute([$usuarioId, $perfilId, $dataInicio, $dataFim]);

        $out = [];
        foreach ($st->fetchAll() as $f) {
            $out[] = [
                'forma_id'   => (int) $f['forma_id'],
                'forma_nome' => $f['forma_nome'],
                'cor'        => $f['cor'],
                'icone'      => $f['icone'],
                'vencimento' => $f['vencimento'],
                'total'      => (float) $f['total'],
                'qtd'        => (int) $f['qtd'],
            ];
        }
        return $out;
    }

    private static function perfilTemAlgumLancamento(\PDO $db, int $usuarioId, int $perfilId): bool
    {
        $st = $db->prepare("SELECT 1 FROM financeiro_pessoal_lancamentos WHERE usuario_id = ? AND perfil_id = ? LIMIT 1");
        $st->execute([$usuarioId, $perfilId]);
        return (bool) $st->fetchColumn();
    }
}
