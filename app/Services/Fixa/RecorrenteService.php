<?php

namespace App\Services\Fixa;

/**
 * Contas recorrentes do Carteira Fixa (pedido do usuário: "cadastro de conta recorrente, para
 * aluguel por exemplo, linkando com a agenda e notificando"). Um "molde" mensal
 * (financeiro_pessoal_recorrentes) que, a cada visita do usuário ao módulo, garante que o mês
 * atual e o próximo já têm um lançamento de verdade gerado (financeiro_pessoal_lancamentos,
 * `recorrente_id` apontando de volta) — é esse lançamento comum que já alimenta a Agenda (ver
 * FinanceiroPessoalController::buscarVencimentosDoMes(), que lê vencimento direto de
 * `_lancamentos`, sem precisar de nenhuma mudança) e o alerta de vencido (alertasVencidosAjax(),
 * mesma query de sempre). O único reforço novo é um evento em `financeiro_pessoal_eventos`
 * (ligado via `lancamento_id`) na data do vencimento, pra disparar o sino (ver migration
 * 080_financeiro_pessoal_notificacoes.sql) no dia em que a conta vence.
 *
 * Deliberadamente simples (mensal fixo, dia do mês 1–31 com clamp pro último dia de mês curto)
 * — sem RRULE. `data_inicio`/`data_fim` (migration 097) definem a janela de vigência — início
 * trava geração antes da data (uma recorrência cadastrada hoje não inventa ocorrência passada);
 * fim é opcional, "Repetir por quantos meses" na UI (pensado pra compra parcelada no cartão),
 * NULL = sem fim. Sem fim, mantém o princípio de "nunca materializar ocorrência pra sempre" já
 * documentado no sistema principal: cada geração garante só mês atual + próximo, nunca um
 * backlog inteiro. COM fim (janela finita, curta por natureza — ver RECORRENTE_PARCELAS_MAX no
 * controller), já materializa a janela INTEIRA de uma vez (ver mesesAGerar()) — é o que faz as
 * parcelas futuras aparecerem no calendário sem precisar visitar o módulo todo mês.
 */
class RecorrenteService
{
    /** Todas as recorrências de um perfil (ativas e pausadas), mais recente primeiro. */
    public static function listar(\PDO $db, int $perfilId): array
    {
        $st = $db->prepare(
            "SELECT id, conta_id, tipo, categoria, descricao, notas, valor, dia_vencimento,
                    data_inicio, data_fim, ativo, criado_em
             FROM financeiro_pessoal_recorrentes WHERE perfil_id = ? ORDER BY ativo DESC, descricao ASC"
        );
        $st->execute([$perfilId]);
        return $st->fetchAll();
    }

    public static function buscar(\PDO $db, int $id, int $usuarioId, int $perfilId): ?array
    {
        $st = $db->prepare(
            "SELECT * FROM financeiro_pessoal_recorrentes WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$id, $usuarioId, $perfilId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function criar(\PDO $db, int $usuarioId, int $perfilId, array $dados): int
    {
        $db->prepare(
            "INSERT INTO financeiro_pessoal_recorrentes
                (usuario_id, perfil_id, conta_id, tipo, categoria, descricao, notas, valor,
                 dia_vencimento, data_inicio, data_fim, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        )->execute([
            $usuarioId, $perfilId, $dados['conta_id'], $dados['tipo'], $dados['categoria'],
            $dados['descricao'], $dados['notas'], $dados['valor'], $dados['dia_vencimento'],
            $dados['data_inicio'], $dados['data_fim'],
        ]);
        return (int) $db->lastInsertId();
    }

    public static function atualizar(\PDO $db, int $id, int $usuarioId, int $perfilId, array $dados): bool
    {
        $st = $db->prepare(
            "UPDATE financeiro_pessoal_recorrentes
                SET conta_id = ?, tipo = ?, categoria = ?, descricao = ?, notas = ?, valor = ?,
                    dia_vencimento = ?, data_inicio = ?, data_fim = ?
              WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([
            $dados['conta_id'], $dados['tipo'], $dados['categoria'], $dados['descricao'],
            $dados['notas'], $dados['valor'], $dados['dia_vencimento'],
            $dados['data_inicio'], $dados['data_fim'], $id, $usuarioId, $perfilId,
        ]);
        return $st->rowCount() > 0 || self::buscar($db, $id, $usuarioId, $perfilId) !== null;
    }

    /** Pausar (ativo=0) só impede gerar NOVOS lançamentos — os já gerados antes continuam
     *  existindo normalmente, exatamente como qualquer outro lançamento. Retomar (ativo=1) volta
     *  a gerar a partir da próxima visita, nunca recupera retroativamente meses pausados. */
    public static function alternarAtivo(\PDO $db, int $id, int $usuarioId, int $perfilId, bool $ativo): bool
    {
        $st = $db->prepare(
            "UPDATE financeiro_pessoal_recorrentes SET ativo = ? WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$ativo ? 1 : 0, $id, $usuarioId, $perfilId]);
        return $st->rowCount() > 0;
    }

    /** Exclui o molde — lançamentos já gerados por ele NÃO são apagados (ON DELETE SET NULL em
     *  `recorrente_id`), viram lançamentos comuns sem vínculo, igual qualquer outro editado à
     *  mão depois. */
    public static function excluir(\PDO $db, int $id, int $usuarioId, int $perfilId): bool
    {
        $st = $db->prepare(
            "DELETE FROM financeiro_pessoal_recorrentes WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$id, $usuarioId, $perfilId]);
        return $st->rowCount() > 0;
    }

    /** Dia do mês clampado pro último dia real do mês (ex.: dia 31 configurado, fevereiro só
     *  tem 28/29 — cai no último dia, nunca estoura pro mês seguinte). */
    public static function dataVencimentoNoMes(int $dia, string $anoMes): string
    {
        $dia = max(1, min(31, $dia));
        $ultimoDia = (int) date('t', strtotime($anoMes . '-01'));
        return sprintf('%s-%02d', $anoMes, min($dia, $ultimoDia));
    }

    /**
     * Garante que toda recorrência ATIVA do perfil já tem lançamento gerado pros meses que lhe
     * cabem — chamado (best-effort, nunca derruba a página se falhar) toda vez que o usuário
     * abre Lançamentos/Agenda/Resumo, e também depois de criar/editar uma recorrência (ver
     * FinanceiroPessoalController::recorrenteSalvar()/recorrenteAtualizar()). Idempotente: antes
     * de criar, checa se já existe um lançamento com esse `recorrente_id` cujo vencimento cai
     * naquele mês — reentrância (dois page loads quase simultâneos) na pior hipótese faz a MESMA
     * checagem duas vezes, nunca duplica (a consulta de existência sempre roda antes do INSERT,
     * mesmo padrão simples já usado no resto deste módulo pra evitar duplicata sem precisar de
     * lock).
     *
     * Deliberadamente não gera pro mês atual se o dia de vencimento JÁ PASSOU e a recorrência
     * acabou de ser criada depois disso — nunca inventa uma ocorrência retroativa; só o próximo
     * mês em diante. Retorna quantos lançamentos novos foram criados.
     */
    public static function gerarPendentes(\PDO $db, int $usuarioId, int $perfilId): int
    {
        $st = $db->prepare(
            "SELECT * FROM financeiro_pessoal_recorrentes WHERE usuario_id = ? AND perfil_id = ? AND ativo = 1"
        );
        $st->execute([$usuarioId, $perfilId]);
        $recorrentes = $st->fetchAll();
        if (!$recorrentes) return 0;

        $hoje = date('Y-m-d');
        $criados = 0;

        foreach ($recorrentes as $r) {
            foreach (self::mesesAGerar($r, $hoje) as $anoMes) {
                $vencimento = self::dataVencimentoNoMes((int) $r['dia_vencimento'], $anoMes);
                if ($vencimento < $hoje) continue; // nunca gera ocorrência já vencida pro passado
                // Janela de vigência (opcional) — NULL em qualquer um dos dois significa "sem
                // limite" (recorrência antiga, de antes desta coluna existir, ou "fim" deixado
                // em branco de propósito na UI).
                if ($r['data_inicio'] !== null && $vencimento < $r['data_inicio']) continue;
                if ($r['data_fim'] !== null && $vencimento > $r['data_fim']) continue;

                $existe = $db->prepare(
                    "SELECT 1 FROM financeiro_pessoal_lancamentos WHERE recorrente_id = ? AND vencimento = ? LIMIT 1"
                );
                $existe->execute([$r['id'], $vencimento]);
                if ($existe->fetchColumn()) continue;

                $contaId = self::contaValidaOuPadrao($db, $perfilId, $r['conta_id'] !== null ? (int) $r['conta_id'] : null);

                $db->prepare(
                    "INSERT INTO financeiro_pessoal_lancamentos
                        (usuario_id, perfil_id, conta_id, recorrente_id, tipo, categoria, descricao,
                         observacao, valor, data_hora, vencimento, hora_informada, origem)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'manual')"
                )->execute([
                    $usuarioId, $perfilId, $contaId, $r['id'], $r['tipo'], $r['categoria'], $r['descricao'],
                    $r['notas'], $r['valor'], $vencimento . ' 00:00:00', $vencimento,
                ]);
                $lancamentoId = (int) $db->lastInsertId();
                $criados++;

                $tipoLabel = $r['tipo'] === 'receita' ? 'recebimento' : 'pagamento';
                $titulo = $r['descricao'] . ' — ' . $tipoLabel . ' de R$ ' . number_format((float) $r['valor'], 2, ',', '.') . ' vence hoje';
                $db->prepare(
                    "INSERT INTO financeiro_pessoal_eventos (usuario_id, perfil_id, lancamento_id, titulo, data_hora)
                     VALUES (?, ?, ?, ?, ?)"
                )->execute([$usuarioId, $perfilId, $lancamentoId, mb_substr($titulo, 0, 150), $vencimento . ' 08:00:00']);
            }
        }

        return $criados;
    }

    /** Teto de segurança pra quantos meses um único `gerarPendentes()` materializa de uma vez
     *  pra uma recorrência com `data_fim` — defesa em dupla camada: o controller já valida isso
     *  no formulário (FinanceiroPessoalController::RECORRENTE_PARCELAS_MAX), mas esta função é
     *  pública e usada por mais de um caller (criar/atualizar/visita normal ao módulo), então
     *  nunca confia só na validação de um lugar só pra evitar uma rajada de INSERT gigante. */
    private const MESES_GERACAO_MAX = 60;

    /**
     * Lista de "YYYY-MM" candidatos pra UMA recorrência. Sem `data_fim` (repete pra sempre),
     * mantém o comportamento de sempre: só mês atual + próximo, materializado aos poucos a cada
     * visita — nunca inventa uma série infinita de uma vez só. COM `data_fim` (janela finita,
     * ex.: compra parcelada no cartão em N vezes), gera a janela inteira de uma tacada — é
     * exatamente o caso em que materializar tudo de uma vez é seguro (prazo curto e conhecido de
     * antemão) e necessário (sem isso, as parcelas mais distantes só apareceriam no calendário
     * mês a mês, conforme o usuário fosse visitando o módulo — bug relatado: "não aparece nos
     * meses seguintes do calendário").
     */
    private static function mesesAGerar(array $r, string $hoje): array
    {
        if ($r['data_fim'] === null) {
            return [date('Y-m'), date('Y-m', strtotime('+1 month'))];
        }

        $inicio = ($r['data_inicio'] !== null && $r['data_inicio'] > $hoje) ? $r['data_inicio'] : $hoje;
        $mesInicio = substr($inicio, 0, 7);
        $mesFim = substr($r['data_fim'], 0, 7);
        if ($mesInicio > $mesFim) return [];

        $meses = [];
        $cursor = $mesInicio . '-01';
        while (substr($cursor, 0, 7) <= $mesFim && count($meses) < self::MESES_GERACAO_MAX) {
            $meses[] = substr($cursor, 0, 7);
            $cursor = date('Y-m-d', strtotime($cursor . ' +1 month'));
        }
        return $meses;
    }

    /** Mesma regra de fallback já usada em FinanceiroPessoalController::contaValidaOuPadrao() —
     *  réplica estática porque aqui não há uma instância de controller/request em andamento
     *  (geração roda em nome do usuário, não de uma submissão de formulário específica). */
    private static function contaValidaOuPadrao(\PDO $db, int $perfilId, ?int $contaId): ?int
    {
        $contas = PerfilService::contasDoPerfil($db, $perfilId);
        if (!$contas) return null;
        foreach ($contas as $c) {
            if ($contaId !== null && (int) $c['id'] === $contaId) return $contaId;
        }
        return (int) $contas[0]['id'];
    }
}
