<?php

namespace App\Models;

use App\Core\Model;

class Financeiro extends Model
{
    protected string $table = 'fin_lancamentos';

    // ─── SQL do UNION — público para uso no controller ─────────────────
    public function sqlUnificadoPublico(): string { return $this->sqlUnificado(); }

    // ─── Data de início do financeiro (para exibir na tela) ───────────────
    public function getInicio(): ?string { return $this->financeiroInicio(); }

    // ─── Data de início do financeiro (corte): ignora movimento anterior ──
    private function financeiroInicio(): ?string
    {
        $st = $this->db->prepare("SELECT financeiro_inicio FROM empresas WHERE id = ?");
        $st->execute([$this->empresaId()]);
        $v = (string) ($st->fetchColumn() ?: '');
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : null;
    }

    // ─── SQL do UNION (evita VIEW para não ter problema de collation) ─────
    private function sqlUnificado(): string
    {
        $ini   = $this->financeiroInicio();
        $flCut = $ini ? " AND fl.data_vencimento >= '{$ini}'" : '';
        $osCut = $ini ? " AND os.data_conclusao >= '{$ini}'" : '';
        return "
        SELECT
          fl.empresa_id,
          fl.id                          AS ref_id,
          'lancamento'                   AS fonte,
          fl.tipo,
          fl.descricao,
          fl.valor,
          fl.data_vencimento,
          fl.data_pagamento,
          fl.status,
          fl.forma_pagamento,
          fl.os_id,
          fl.cliente_id,
          IFNULL(fc.nome,'')             AS categoria_nome,
          IFNULL(fc.cor,'#6c757d')       AS categoria_cor,
          IFNULL(c.nome,'')              AS cliente_nome,
          NULL                           AS numero_os,
          (SELECT COALESCE(SUM(t.valor),0) FROM fin_lancamentos t
             JOIN fin_categorias fc2 ON fc2.id = t.categoria_id
            WHERE t.os_id = fl.os_id AND t.empresa_id = fl.empresa_id
              AND t.tipo = 'despesa' AND fc2.nome = 'Taxas de cartão') AS taxa_cartao
        FROM fin_lancamentos fl
        LEFT JOIN fin_categorias fc ON fc.id = fl.categoria_id
        LEFT JOIN clientes c ON c.id = fl.cliente_id
        WHERE 1=1{$flCut}

        UNION ALL

        SELECT
          os.empresa_id,
          os.id                          AS ref_id,
          'os'                           AS fonte,
          'receita'                      AS tipo,
          CONCAT(os.numero, ' - ', c.nome) AS descricao,
          os.valor_total                 AS valor,
          DATE(os.data_conclusao)        AS data_vencimento,
          DATE(os.data_conclusao)        AS data_pagamento,
          os.situacao_pagamento          AS status,
          os.forma_pagamento_fechamento  AS forma_pagamento,
          os.id                          AS os_id,
          os.cliente_id,
          'Servicos'                     AS categoria_nome,
          '#198754'                      AS categoria_cor,
          c.nome                         AS cliente_nome,
          os.numero                      AS numero_os,
          0                              AS taxa_cartao
        FROM ordens_servico os
        JOIN os_status s ON s.id = os.status_id
        JOIN clientes c   ON c.id = os.cliente_id
        WHERE s.tipo IN ('concluida','entregue')
          AND os.valor_total > 0{$osCut}
          -- Só cobre OS antiga que foi PAGA mas ficou sem lançamento (antes desse fluxo existir).
          -- Nunca pendente/parcial aqui — o Financeiro não mostra promessa de pagamento (regra fixa).
          AND os.situacao_pagamento = 'pago'
          AND NOT EXISTS (
            SELECT 1 FROM fin_lancamentos fl2
            WHERE fl2.os_id = os.id
              AND fl2.tipo = 'receita'
              AND fl2.empresa_id = os.empresa_id
          )
        ";
    }

    // ─── Lista unificada paginada ─────────────────────────────────────────
    public function listarUnificado(int $page, int $perPage, array $filtros = []): array
    {
        $eid     = $this->empresaId();
        $conds   = [];
        $params  = [$eid, $eid];   // um para cada SELECT do UNION

        // Monta condições aplicadas ao wrapper externo
        $where = "u.empresa_id = ?";
        $wParams = [$eid];

        if (!empty($filtros['tipo']))        { $where .= " AND u.tipo = ?";             $wParams[] = $filtros['tipo']; }
        if (!empty($filtros['status']))      { $where .= " AND u.status = ?";           $wParams[] = $filtros['status']; }
        if (!empty($filtros['fonte']))       { $where .= " AND u.fonte = ?";            $wParams[] = $filtros['fonte']; }
        if (!empty($filtros['categoria']))   { $where .= " AND u.categoria_nome = ?";   $wParams[] = $filtros['categoria']; }
        if (!empty($filtros['data_inicio'])) { $where .= " AND u.data_vencimento >= ?"; $wParams[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim']))    { $where .= " AND u.data_vencimento <= ?"; $wParams[] = $filtros['data_fim']; }

        $inner = $this->sqlUnificado();

        // Contar
        $stmtC = $this->db->prepare(
            "SELECT COUNT(*) FROM ({$inner}) u WHERE {$where}"
        );
        $stmtC->execute($wParams);
        $total  = (int) $stmtC->fetchColumn();
        $offset = ($page - 1) * $perPage;

        // Buscar
        $stmt = $this->db->prepare(
            "SELECT u.* FROM ({$inner}) u
             WHERE {$where}
             ORDER BY COALESCE(u.data_vencimento, CURDATE()) DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($wParams);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    // ─── Totais do filtro atual (todas as páginas, não só a página exibida) ──
    public function totaisFiltrados(array $filtros = []): array
    {
        $eid   = $this->empresaId();
        $where = "u.empresa_id = ?";
        $wParams = [$eid];

        if (!empty($filtros['tipo']))        { $where .= " AND u.tipo = ?";             $wParams[] = $filtros['tipo']; }
        if (!empty($filtros['status']))      { $where .= " AND u.status = ?";           $wParams[] = $filtros['status']; }
        if (!empty($filtros['fonte']))       { $where .= " AND u.fonte = ?";            $wParams[] = $filtros['fonte']; }
        if (!empty($filtros['categoria']))   { $where .= " AND u.categoria_nome = ?";   $wParams[] = $filtros['categoria']; }
        if (!empty($filtros['data_inicio'])) { $where .= " AND u.data_vencimento >= ?"; $wParams[] = $filtros['data_inicio']; }
        if (!empty($filtros['data_fim']))    { $where .= " AND u.data_vencimento <= ?"; $wParams[] = $filtros['data_fim']; }

        $inner = $this->sqlUnificado();
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) AS qtd,
                    SUM(CASE WHEN u.tipo='receita' THEN u.valor ELSE 0 END) AS total_receitas,
                    SUM(CASE WHEN u.tipo='despesa' THEN u.valor ELSE 0 END) AS total_despesas
             FROM ({$inner}) u WHERE {$where}"
        );
        $stmt->execute($wParams);
        $row = $stmt->fetch() ?: [];

        $receitas = (float) ($row['total_receitas'] ?? 0);
        $despesas = (float) ($row['total_despesas'] ?? 0);

        return [
            'qtd'            => (int) ($row['qtd'] ?? 0),
            'total_receitas' => $receitas,
            'total_despesas' => $despesas,
            'saldo'          => $receitas - $despesas,
        ];
    }

    /**
     * Todas as despesas de "Taxas de cartão" da empresa no período — SEM paginação.
     * Usado pra aninhar a taxa dentro da receita da mesma OS/venda no Financeiro, mesmo quando
     * a receita e a taxa caem em páginas diferentes da lista (empresa com bastante movimento).
     */
    public function taxasCartaoNoPeriodo(string $inicio, string $fim): array
    {
        $inner = $this->sqlUnificado();
        $stmt = $this->db->prepare(
            "SELECT u.* FROM ({$inner}) u
             WHERE u.empresa_id = ? AND u.tipo = 'despesa' AND u.categoria_nome = 'Taxas de cartão'
               AND u.data_vencimento BETWEEN ? AND ?
             ORDER BY u.data_vencimento DESC"
        );
        $stmt->execute([$this->empresaId(), $inicio, $fim]);
        return $stmt->fetchAll();
    }

    // ─── Saldo unificado ──────────────────────────────────────────────────
    public function saldoUnificado(string $inicio, string $fim): array
    {
        $eid   = $this->empresaId();
        $inner = $this->sqlUnificado();

        $stmt = $this->db->prepare(
            "SELECT
             SUM(CASE WHEN u.tipo='receita' AND u.status='pago'
                      AND u.data_vencimento BETWEEN ? AND ? THEN u.valor ELSE 0 END) AS receitas_pagas,
             SUM(CASE WHEN u.tipo='despesa' AND u.status='pago'
                      AND u.data_vencimento BETWEEN ? AND ? THEN u.valor ELSE 0 END) AS despesas_pagas,
             SUM(CASE WHEN u.tipo='receita' AND u.status='pendente'
                      AND u.data_vencimento BETWEEN ? AND ? THEN u.valor ELSE 0 END) AS receitas_pendentes,
             SUM(CASE WHEN u.tipo='despesa' AND u.status='pendente'
                      AND u.data_vencimento BETWEEN ? AND ? THEN u.valor ELSE 0 END) AS despesas_pendentes,
             SUM(CASE WHEN u.fonte='lancamento' AND u.tipo='receita' AND u.status='pago'
                      AND u.data_vencimento BETWEEN ? AND ? THEN u.valor ELSE 0 END) AS receita_lancamentos,
             SUM(CASE WHEN u.fonte='os' AND u.tipo='receita' AND u.status='pago'
                      AND u.data_vencimento BETWEEN ? AND ? THEN u.valor ELSE 0 END) AS receita_os
             FROM ({$inner}) u WHERE u.empresa_id = ?"
        );
        $stmt->execute([$inicio,$fim, $inicio,$fim, $inicio,$fim, $inicio,$fim, $inicio,$fim, $inicio,$fim, $eid]);
        return $stmt->fetch() ?: [];
    }

    public function resumo(string $inicio, string $fim): array
    {
        return $this->saldoUnificado($inicio, $fim);
    }

    // ─── Receita por fonte ────────────────────────────────────────────────
    public function receitaPorFonte(string $inicio, string $fim): array
    {
        $eid   = $this->empresaId();
        $inner = $this->sqlUnificado();

        $stmt = $this->db->prepare(
            "SELECT u.fonte,
             SUM(CASE WHEN u.tipo='receita' AND u.status='pago' THEN u.valor ELSE 0 END) AS total
             FROM ({$inner}) u
             WHERE u.empresa_id = ? AND u.data_vencimento BETWEEN ? AND ?
             GROUP BY u.fonte"
        );
        $stmt->execute([$eid, $inicio, $fim]);
        return $stmt->fetchAll();
    }

    // ─── OS pendentes de pagamento ────────────────────────────────────────
    public function osPendentes(): array
    {
        return $this->query(
            "SELECT os.*, c.nome AS cliente_nome
             FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             JOIN clientes c ON c.id = os.cliente_id
             WHERE os.empresa_id = ? AND s.tipo IN ('concluida','entregue')
               AND os.situacao_pagamento IN ('pendente','parcial')
               AND os.valor_total > 0
             ORDER BY os.data_conclusao DESC",
            [$this->empresaId()]
        );
    }

    // ─── Lançamentos vencidos ─────────────────────────────────────────────
    public function vencendoHoje(): array
    {
        return $this->query(
            "SELECT fl.*, c.nome AS cliente_nome FROM fin_lancamentos fl
             LEFT JOIN clientes c ON c.id = fl.cliente_id
             WHERE fl.empresa_id = ? AND fl.status = 'pendente'
               AND fl.data_vencimento <= CURDATE()
             ORDER BY fl.data_vencimento",
            [$this->empresaId()]
        );
    }

    // ─── Fluxo de caixa ───────────────────────────────────────────────────
    public function fluxoCaixa(string $inicio, string $fim): array
    {
        $eid   = $this->empresaId();
        $inner = $this->sqlUnificado();

        $stmt = $this->db->prepare(
            "SELECT DATE(u.data_vencimento) AS dia,
             SUM(CASE WHEN u.tipo='receita' AND u.status='pago' THEN u.valor ELSE 0 END) AS receita,
             SUM(CASE WHEN u.tipo='despesa' AND u.status='pago' THEN u.valor ELSE 0 END) AS despesa
             FROM ({$inner}) u
             WHERE u.empresa_id = ? AND u.data_vencimento BETWEEN ? AND ?
             GROUP BY DATE(u.data_vencimento) ORDER BY dia"
        );
        $stmt->execute([$eid, $inicio, $fim]);
        return $stmt->fetchAll();
    }

    public function contas(): array
    {
        return $this->query(
            "SELECT * FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY nome",
            [$this->empresaId()]
        );
    }

    public function categorias(string $tipo = ''): array
    {
        $sql = "SELECT * FROM fin_categorias WHERE empresa_id = ?";
        $p   = [$this->empresaId()];
        if ($tipo) { $sql .= " AND tipo = ?"; $p[] = $tipo; }
        return $this->query($sql . " ORDER BY tipo, nome", $p);
    }

    public function listar(int $page, int $perPage, array $filtros = []): array
    {
        return $this->listarUnificado($page, $perPage, $filtros);
    }
}
