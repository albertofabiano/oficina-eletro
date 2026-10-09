<?php

namespace App\Services\Fixa;

/**
 * Eventos recorrentes da Agenda do Carteira Fixa (pedido do usuário: "faça o mesmo [de Contas
 * recorrentes] pra editar evento") — mesmo desenho de `RecorrenteService`, só que pra um
 * compromisso sem dinheiro (ex.: "Consulta médica" todo dia 10). Um "molde" mensal
 * (financeiro_pessoal_eventos_recorrentes) que, a cada visita ao módulo, garante que os eventos
 * de verdade já existem (financeiro_pessoal_eventos, `recorrente_id` apontando de volta) — é
 * esse evento comum que já aparece na Agenda sem precisar de nenhuma mudança na tela.
 *
 * Sem `data_fim` (repete pra sempre — ex.: "Pagar fatura" todo mês), gera só mês atual +
 * próximo, igual RecorrenteService, nunca materializa uma série infinita. COM `data_fim` (janela
 * finita, ex.: 6 consultas de um tratamento), gera a janela INTEIRA de uma vez (capada em
 * MESES_GERACAO_MAX) — mesmo motivo de RecorrenteService::mesesAGerar(): sem isso, os eventos
 * mais distantes só apareceriam no calendário mês a mês, conforme o usuário fosse visitando.
 */
class EventoRecorrenteService
{
    /** Teto de segurança pra quantos meses um único gerarPendentes() materializa de uma vez
     *  pra uma recorrência com `data_fim` — mesmo raciocínio de RecorrenteService::
     *  MESES_GERACAO_MAX (defesa em dupla camada, independente da validação do controller). */
    private const MESES_GERACAO_MAX = 60;

    /** Todas as recorrências de um perfil (ativas e pausadas), mais recente primeiro. */
    public static function listar(\PDO $db, int $perfilId): array
    {
        $st = $db->prepare(
            "SELECT id, titulo, dia_mes, hora, data_inicio, data_fim, ativo, criado_em
             FROM financeiro_pessoal_eventos_recorrentes WHERE perfil_id = ? ORDER BY ativo DESC, titulo ASC"
        );
        $st->execute([$perfilId]);
        return $st->fetchAll();
    }

    public static function buscar(\PDO $db, int $id, int $usuarioId, int $perfilId): ?array
    {
        $st = $db->prepare(
            "SELECT * FROM financeiro_pessoal_eventos_recorrentes WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$id, $usuarioId, $perfilId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public static function criar(\PDO $db, int $usuarioId, int $perfilId, array $dados): int
    {
        $db->prepare(
            "INSERT INTO financeiro_pessoal_eventos_recorrentes
                (usuario_id, perfil_id, titulo, dia_mes, hora, data_inicio, data_fim, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, 1)"
        )->execute([
            $usuarioId, $perfilId, $dados['titulo'], $dados['dia_mes'], $dados['hora'],
            $dados['data_inicio'], $dados['data_fim'],
        ]);
        return (int) $db->lastInsertId();
    }

    public static function atualizar(\PDO $db, int $id, int $usuarioId, int $perfilId, array $dados): bool
    {
        $st = $db->prepare(
            "UPDATE financeiro_pessoal_eventos_recorrentes
                SET titulo = ?, dia_mes = ?, hora = ?, data_inicio = ?, data_fim = ?
              WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([
            $dados['titulo'], $dados['dia_mes'], $dados['hora'], $dados['data_inicio'], $dados['data_fim'],
            $id, $usuarioId, $perfilId,
        ]);
        return $st->rowCount() > 0 || self::buscar($db, $id, $usuarioId, $perfilId) !== null;
    }

    /** Pausar (ativo=0) só impede gerar NOVOS eventos — os já gerados antes continuam existindo
     *  normalmente. Retomar (ativo=1) volta a gerar a partir da próxima visita. */
    public static function alternarAtivo(\PDO $db, int $id, int $usuarioId, int $perfilId, bool $ativo): bool
    {
        $st = $db->prepare(
            "UPDATE financeiro_pessoal_eventos_recorrentes SET ativo = ? WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$ativo ? 1 : 0, $id, $usuarioId, $perfilId]);
        return $st->rowCount() > 0;
    }

    /** Exclui o molde — eventos já gerados por ele NÃO são apagados (ON DELETE SET NULL em
     *  `recorrente_id`), viram eventos comuns sem vínculo. */
    public static function excluir(\PDO $db, int $id, int $usuarioId, int $perfilId): bool
    {
        $st = $db->prepare(
            "DELETE FROM financeiro_pessoal_eventos_recorrentes WHERE id = ? AND usuario_id = ? AND perfil_id = ?"
        );
        $st->execute([$id, $usuarioId, $perfilId]);
        return $st->rowCount() > 0;
    }

    /** Data+hora do evento num mês específico, dia clampado pro último dia real do mês (mesma
     *  regra de RecorrenteService::dataVencimentoNoMes()). Devolve só a DATA (YYYY-MM-DD) — a
     *  hora é combinada à parte em gerarPendentes(), onde o DATETIME completo é montado. */
    public static function dataOcorrenciaNoMes(int $diaMes, string $anoMes): string
    {
        $dia = max(1, min(31, $diaMes));
        $ultimoDia = (int) date('t', strtotime($anoMes . '-01'));
        return sprintf('%s-%02d', $anoMes, min($dia, $ultimoDia));
    }

    /** Mesma lógica de RecorrenteService::mesesAGerar() — sem data_fim, só mês atual + próximo
     *  (nunca materializa série infinita); com data_fim, a janela inteira de uma vez (capada). */
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

    /**
     * Garante que toda recorrência ATIVA do perfil já tem evento gerado pros meses que lhe
     * cabem — chamado (best-effort) toda vez que o usuário abre Lançamentos/Agenda/Resumo, e
     * depois de criar/editar uma recorrência. Idempotente: antes de criar, checa se já existe
     * um evento com esse `recorrente_id` na mesma data. Retorna quantos eventos novos foram
     * criados.
     */
    public static function gerarPendentes(\PDO $db, int $usuarioId, int $perfilId): int
    {
        $st = $db->prepare(
            "SELECT * FROM financeiro_pessoal_eventos_recorrentes WHERE usuario_id = ? AND perfil_id = ? AND ativo = 1"
        );
        $st->execute([$usuarioId, $perfilId]);
        $recorrentes = $st->fetchAll();
        if (!$recorrentes) return 0;

        $hoje = date('Y-m-d');
        $criados = 0;

        foreach ($recorrentes as $r) {
            foreach (self::mesesAGerar($r, $hoje) as $anoMes) {
                $dataOcorrencia = self::dataOcorrenciaNoMes((int) $r['dia_mes'], $anoMes);
                if ($dataOcorrencia < $hoje) continue; // nunca gera ocorrência já vencida pro passado
                if ($r['data_inicio'] !== null && $dataOcorrencia < $r['data_inicio']) continue;
                if ($r['data_fim'] !== null && $dataOcorrencia > $r['data_fim']) continue;

                $existe = $db->prepare(
                    "SELECT 1 FROM financeiro_pessoal_eventos WHERE recorrente_id = ? AND DATE(data_hora) = ? LIMIT 1"
                );
                $existe->execute([$r['id'], $dataOcorrencia]);
                if ($existe->fetchColumn()) continue;

                $hora = $r['hora'] ?: '08:00:00';
                $db->prepare(
                    "INSERT INTO financeiro_pessoal_eventos (usuario_id, perfil_id, recorrente_id, titulo, data_hora)
                     VALUES (?, ?, ?, ?, ?)"
                )->execute([$usuarioId, $perfilId, $r['id'], $r['titulo'], $dataOcorrencia . ' ' . $hora]);
                $criados++;
            }
        }

        return $criados;
    }
}
