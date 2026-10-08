<?php

namespace App\Services\Fixa;

/**
 * Carteira Fixa — módulo "Caixinhas": reserva de dinheiro que o usuário separa pra guardar
 * (ex. "Viagem", "Emergência"), SEM rendimento/CDI/imposto/integração bancária — o app só
 * lembra de avisar que o valor precisa ser transferido de verdade pro banco/investimento dele e
 * não mexido. Mesmo princípio de serviço estático compartilhado já usado no módulo
 * (PerfilService, RecorrenteService).
 *
 * Valores em CENTAVOS (int), diferente do resto do módulo Fixa (que guarda DECIMAL em reais em
 * `financeiro_pessoal_lancamentos`/`financeiro_pessoal_contas`) — pedido explícito do usuário.
 * Quem chama este serviço a partir de código que trabalha em reais (FixaContasController,
 * FinanceiroPessoalController) converte na própria chamada (`/ 100`).
 *
 * Deliberadamente SEPARADO de `financeiro_pessoal_lancamentos` — depósito/retirada nunca entra
 * em "Gasto no mês", ranking de categoria ou gráfico (que só iteram aquela tabela), por
 * construção, sem precisar de nenhuma checagem de tipo pra excluir.
 */
class CaixinhaService
{
    /** Saldo da caixinha = soma de depósitos − soma de retiradas, em centavos. Calculado na
     *  hora, nunca gravado em coluna — mesmo princípio de fixa_saldo_atual(). */
    public static function saldoCaixinha(\PDO $db, int $caixinhaId): int
    {
        $st = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'deposito' THEN valor_centavos ELSE -valor_centavos END), 0)
             FROM caixinha_movimentos WHERE caixinha_id = ?"
        );
        $st->execute([$caixinhaId]);
        return (int) $st->fetchColumn();
    }

    /** Saldo total guardado em TODAS as caixinhas (não-arquivadas ou não) de um perfil — usado
     *  pro desconto no "Saldo atual"/"Saldo previsto" do Resumo (soma agregada do perfil). */
    public static function saldoTotalCaixinhas(\PDO $db, int $perfilId): int
    {
        $st = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN m.tipo = 'deposito' THEN m.valor_centavos ELSE -m.valor_centavos END), 0)
             FROM caixinha_movimentos m WHERE m.perfil_id = ?"
        );
        $st->execute([$perfilId]);
        return (int) $st->fetchColumn();
    }

    /** Saldo líquido de caixinha POR CONTA (conta_id => centavos) — depósito tira da conta de
     *  origem, retirada devolve pra conta de destino; movimento sem conta (conta_id NULL, ex.
     *  excluída depois) não afeta saldo de conta nenhuma. Usado por
     *  FixaContasController::saldosPorConta() pra descontar do saldo mostrado em /contas. */
    public static function saldoPorConta(\PDO $db, array $contaIds): array
    {
        if (!$contaIds) return [];
        $ph = implode(',', array_fill(0, count($contaIds), '?'));
        $st = $db->prepare(
            "SELECT conta_id,
                    COALESCE(SUM(CASE WHEN tipo = 'deposito' THEN valor_centavos ELSE -valor_centavos END), 0) AS liquido
             FROM caixinha_movimentos WHERE conta_id IN ($ph) GROUP BY conta_id"
        );
        $st->execute($contaIds);
        $out = [];
        foreach ($st->fetchAll() as $r) { $out[(int) $r['conta_id']] = (int) $r['liquido']; }
        return $out;
    }

    /** Lista de caixinhas do perfil com saldo e um resumo do que falta transferir — usado por
     *  CaixinhasController::index(). Uma query só (GROUP BY), não N+1 por caixinha. */
    public static function listarComSaldo(\PDO $db, int $perfilId, bool $incluirArquivadas = false): array
    {
        $st = $db->prepare(
            "SELECT * FROM caixinhas WHERE perfil_id = ?" . ($incluirArquivadas ? '' : ' AND arquivada = 0') . "
             ORDER BY arquivada, created_at"
        );
        $st->execute([$perfilId]);
        $caixinhas = $st->fetchAll();
        if (!$caixinhas) return [];

        $ids = array_map(fn($c) => (int) $c['id'], $caixinhas);
        $ph  = implode(',', array_fill(0, count($ids), '?'));

        $stSaldo = $db->prepare(
            "SELECT caixinha_id,
                    COALESCE(SUM(CASE WHEN tipo = 'deposito' THEN valor_centavos ELSE -valor_centavos END), 0) AS saldo
             FROM caixinha_movimentos WHERE caixinha_id IN ($ph) GROUP BY caixinha_id"
        );
        $stSaldo->execute($ids);
        $saldos = array_column($stSaldo->fetchAll(), 'saldo', 'caixinha_id');

        $stPendente = $db->prepare(
            "SELECT DISTINCT caixinha_id FROM caixinha_movimentos
             WHERE caixinha_id IN ($ph) AND tipo = 'deposito' AND transferido_banco = 0"
        );
        $stPendente->execute($ids);
        $pendentes = array_flip($stPendente->fetchAll(\PDO::FETCH_COLUMN));

        foreach ($caixinhas as &$c) {
            $c['saldo_centavos']    = (int) ($saldos[$c['id']] ?? 0);
            $c['falta_transferir']  = isset($pendentes[$c['id']]);
        }
        unset($c);

        return $caixinhas;
    }

    /** Depósito — guarda dinheiro na caixinha, tirando (conceitualmente) da conta de origem.
     *  Não valida saldo da CONTA (o módulo não bloqueia ficar negativo em conta nenhuma, mesmo
     *  princípio já usado pros lançamentos comuns — só avisa visualmente). */
    public static function depositar(\PDO $db, int $caixinhaId, int $usuarioId, int $perfilId, int $valorCentavos, string $data, ?int $contaId, ?string $observacao): int
    {
        $db->prepare(
            "INSERT INTO caixinha_movimentos (caixinha_id, usuario_id, perfil_id, tipo, valor_centavos, data, conta_id, transferido_banco, observacao)
             VALUES (?, ?, ?, 'deposito', ?, ?, ?, 0, ?)"
        )->execute([$caixinhaId, $usuarioId, $perfilId, $valorCentavos, $data, $contaId, $observacao]);
        return (int) $db->lastInsertId();
    }

    /**
     * Retirada — devolve dinheiro da caixinha pra conta de destino. Valida que o valor não
     * passa do saldo ATUAL da caixinha antes de inserir (pedido explícito: "retirada não pode
     * ser maior que o saldo da caixinha"). Retorna null (sem inserir nada) se o valor exceder o
     * saldo — quem chama decide a mensagem de erro.
     */
    public static function retirar(\PDO $db, int $caixinhaId, int $usuarioId, int $perfilId, int $valorCentavos, string $data, ?int $contaId, ?string $observacao): ?int
    {
        if ($valorCentavos > self::saldoCaixinha($db, $caixinhaId)) return null;

        $db->prepare(
            "INSERT INTO caixinha_movimentos (caixinha_id, usuario_id, perfil_id, tipo, valor_centavos, data, conta_id, transferido_banco, observacao)
             VALUES (?, ?, ?, 'retirada', ?, ?, ?, 0, ?)"
        )->execute([$caixinhaId, $usuarioId, $perfilId, $valorCentavos, $data, $contaId, $observacao]);
        return (int) $db->lastInsertId();
    }

    /** Só pode excluir caixinha com saldo zerado — pedido explícito ("retirar antes ou
     *  arquivar"). */
    public static function podeExcluir(\PDO $db, int $caixinhaId): bool
    {
        return self::saldoCaixinha($db, $caixinhaId) === 0;
    }

    /** "Já transferi" — marca o depósito como efetivamente levado pro banco/investimento (some
     *  o selo "Falta transferir" se esse era o único pendente). Escopado por usuário/perfil, não
     *  só pelo id do movimento — defesa contra IDOR (tentar marcar o movimento de outro). */
    public static function marcarTransferido(\PDO $db, int $movimentoId, int $usuarioId, int $perfilId): bool
    {
        $st = $db->prepare(
            "UPDATE caixinha_movimentos SET transferido_banco = 1
             WHERE id = ? AND usuario_id = ? AND perfil_id = ? AND tipo = 'deposito' AND transferido_banco = 0"
        );
        $st->execute([$movimentoId, $usuarioId, $perfilId]);
        return $st->rowCount() > 0;
    }

    /** Guardado no mês navegado — depósitos − retiradas DAQUELE mês (não o saldo total), pro
     *  card "Guardado em caixinhas" do Resumo mostrar o detalhe "+ R$X guardado em {mês}". */
    public static function guardadoNoMes(\PDO $db, int $perfilId, string $mes): int
    {
        $st = $db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tipo = 'deposito' THEN valor_centavos ELSE -valor_centavos END), 0)
             FROM caixinha_movimentos WHERE perfil_id = ? AND DATE_FORMAT(data, '%Y-%m') = ?"
        );
        $st->execute([$perfilId, $mes]);
        return (int) $st->fetchColumn();
    }
}
