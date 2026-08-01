<?php

namespace App\Models;

use App\Core\Model;

class Dashboard extends Model
{
    protected string $table = 'ordens_servico';

    public function resumo(): array
    {
        $eid = $this->empresaId();

        // OS do mês atual
        $stmt = $this->db->prepare(
            "SELECT
             COUNT(*) AS total_os_mes,
             SUM(CASE WHEN s.tipo IN ('aberta','em_andamento','aguardando') THEN 1 ELSE 0 END) AS os_abertas,
             SUM(CASE WHEN s.tipo IN ('concluida','entregue') AND MONTH(os.data_conclusao)=MONTH(CURDATE()) AND YEAR(os.data_conclusao)=YEAR(CURDATE()) THEN 1 ELSE 0 END) AS os_concluidas,
             SUM(CASE WHEN os.data_previsao < NOW() AND s.tipo NOT IN ('concluida','entregue','cancelada') THEN 1 ELSE 0 END) AS os_atrasadas,
             COALESCE(SUM(CASE WHEN s.tipo IN ('concluida','entregue') AND MONTH(COALESCE(os.data_conclusao,os.criado_em))=MONTH(CURDATE()) AND YEAR(COALESCE(os.data_conclusao,os.criado_em))=YEAR(CURDATE()) THEN os.valor_total ELSE 0 END),0) AS faturamento_mes,
             COUNT(CASE WHEN s.tipo NOT IN ('concluida','entregue','cancelada') THEN 1 END) AS os_em_aberto_total
             FROM ordens_servico os
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?"
        );
        $stmt->execute([$eid]);
        $res = $stmt->fetch() ?: [];

        // Total de clientes
        $res['total_clientes'] = (int)$this->db->prepare("SELECT COUNT(*) FROM clientes WHERE empresa_id = ?")->execute([$eid])
            ? $this->db->query("SELECT COUNT(*) FROM clientes WHERE empresa_id = $eid")->fetchColumn()
            : 0;

        // Novos clientes no mês
        $stmt2 = $this->db->prepare("SELECT COUNT(*) FROM clientes WHERE empresa_id=? AND MONTH(criado_em)=MONTH(CURDATE()) AND YEAR(criado_em)=YEAR(CURDATE())");
        $stmt2->execute([$eid]);
        $res['novos_clientes_mes'] = (int)$stmt2->fetchColumn();

        // Alertas de estoque
        $stmt3 = $this->db->prepare("SELECT COUNT(*) FROM produtos WHERE empresa_id=? AND ativo=1 AND estoque_atual<=estoque_minimo");
        $stmt3->execute([$eid]);
        $res['alertas_estoque'] = (int)$stmt3->fetchColumn();

        // Financeiro: a receber e a pagar
        $stmtFin = $this->db->prepare(
            "SELECT
             SUM(CASE WHEN tipo='receita' AND status='pendente' THEN valor ELSE 0 END) AS a_receber,
             SUM(CASE WHEN tipo='despesa' AND status='pendente' THEN valor ELSE 0 END) AS a_pagar,
             SUM(CASE WHEN tipo='receita' AND status='pago' AND MONTH(data_pagamento)=MONTH(CURDATE()) AND YEAR(data_pagamento)=YEAR(CURDATE()) THEN valor ELSE 0 END) AS recebido_mes,
             SUM(CASE WHEN tipo='receita' AND status='pendente' AND data_vencimento < CURDATE() THEN valor ELSE 0 END) AS vencido
             FROM fin_lancamentos WHERE empresa_id=?"
        );
        $stmtFin->execute([$eid]);
        $fin = $stmtFin->fetch() ?: [];
        $res['a_receber']    = (float)($fin['a_receber'] ?? 0);
        $res['a_pagar']      = (float)($fin['a_pagar'] ?? 0);
        $res['recebido_mes'] = (float)($fin['recebido_mes'] ?? 0);
        $res['fin_vencido']  = (float)($fin['vencido'] ?? 0);

        // OS por status (para gráfico de pizza)
        $stmtStatus = $this->db->prepare(
            "SELECT s.id, s.nome, s.cor, s.tipo, COUNT(os.id) AS total
             FROM os_status s
             LEFT JOIN ordens_servico os ON os.status_id = s.id AND os.empresa_id = s.empresa_id
             WHERE s.empresa_id = ?
             GROUP BY s.id ORDER BY s.ordem"
        );
        $stmtStatus->execute([$eid]);
        $res['por_status'] = $stmtStatus->fetchAll();

        return $res;
    }

    public function ultimasOS(int $limit = 8): array
    {
        return $this->query(
            "SELECT os.*, c.nome AS cliente_nome,
             eq.tipo AS equip_tipo, eq.marca AS equip_marca, eq.modelo AS equip_modelo,
             s.nome AS status_nome, s.cor AS status_cor, s.tipo AS status_tipo
             FROM ordens_servico os
             LEFT JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? ORDER BY os.criado_em DESC LIMIT {$limit}",
            [$this->empresaId()]
        );
    }

    public function faturamentoPorMes(int $meses = 6): array
    {
        return $this->query(
            "SELECT
             DATE_FORMAT(COALESCE(os.data_conclusao,os.criado_em),'%Y-%m') AS mes,
             MIN(DATE_FORMAT(COALESCE(os.data_conclusao,os.criado_em),'%b/%y')) AS mes_label,
             SUM(os.valor_total) AS total,
             COUNT(os.id) AS qtd
             FROM ordens_servico os
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? AND s.tipo IN ('concluida','entregue')
             AND COALESCE(os.data_conclusao,os.criado_em) >= DATE_SUB(NOW(), INTERVAL {$meses} MONTH)
             GROUP BY DATE_FORMAT(COALESCE(os.data_conclusao,os.criado_em),'%Y-%m')
             ORDER BY mes",
            [$this->empresaId()]
        );
    }

    public function agendaHoje(): array
    {
        return $this->query(
            "SELECT a.*, c.nome AS cliente_nome, u.nome AS usuario_nome
             FROM agenda a LEFT JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.empresa_id = ? AND DATE(a.data_inicio) = CURDATE() AND a.status != 'cancelado'
             ORDER BY a.data_inicio",
            [$this->empresaId()]
        );
    }

    public function topServicos(int $mes = 0, int $limit = 5): array
    {
        $cond = $mes ? "AND MONTH(os.criado_em)={$mes} AND YEAR(os.criado_em)=YEAR(CURDATE())" : "";
        return $this->query(
            "SELECT oss.descricao, COUNT(*) AS vezes, SUM(oss.valor_total) AS receita
             FROM os_servicos oss
             JOIN ordens_servico os ON os.id = oss.os_id
             WHERE oss.empresa_id = ? {$cond}
             GROUP BY oss.descricao ORDER BY vezes DESC LIMIT {$limit}",
            [$this->empresaId()]
        );
    }

    public function osVencendoHoje(): array
    {
        return $this->query(
            "SELECT os.*, c.nome AS cliente_nome, s.nome AS status_nome, s.cor AS status_cor, s.tipo AS status_tipo
             FROM ordens_servico os
             LEFT JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?
               AND DATE(os.data_previsao) <= CURDATE()
               AND s.tipo NOT IN ('concluida','entregue','cancelada')
             ORDER BY os.data_previsao ASC LIMIT 5",
            [$this->empresaId()]
        );
    }

    /** Resumo do fluxo de OS: entradas × saídas (concluídas), em aberto e tempo médio de conclusão. */
    public function fluxoOsResumo(): array
    {
        $eid = $this->empresaId();

        $e = $this->db->prepare(
            "SELECT
               COALESCE(SUM(data_entrada >= CURDATE()),0) AS ent_hoje,
               COALESCE(SUM(data_entrada >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)),0) AS ent_7d,
               COALESCE(SUM(data_entrada >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)),0) AS ent_30d
             FROM ordens_servico WHERE empresa_id = ?"
        );
        $e->execute([$eid]); $ent = $e->fetch();

        $s = $this->db->prepare(
            "SELECT
               COALESCE(SUM(o.data_conclusao >= CURDATE()),0) AS sai_hoje,
               COALESCE(SUM(o.data_conclusao >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)),0) AS sai_7d,
               COALESCE(SUM(o.data_conclusao >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)),0) AS sai_30d,
               AVG(CASE WHEN o.data_conclusao >= DATE_SUB(CURDATE(), INTERVAL 90 DAY)
                        THEN DATEDIFF(o.data_conclusao, o.data_entrada) END) AS tempo_medio
             FROM ordens_servico o JOIN os_status s ON s.id = o.status_id
             WHERE o.empresa_id = ? AND s.tipo IN ('concluida','entregue')"
        );
        $s->execute([$eid]); $sai = $s->fetch();

        $a = $this->db->prepare(
            "SELECT COUNT(*) FROM ordens_servico o JOIN os_status s ON s.id = o.status_id
             WHERE o.empresa_id = ? AND s.tipo NOT IN ('concluida','entregue','cancelada')"
        );
        $a->execute([$eid]);
        $emAberto = (int) $a->fetchColumn();

        // Prontas p/ retirada: conserto pronto, aguardando o cliente buscar.
        // Casa pelo status "Pronto p/ Retirada" (nome), não todo 'concluida' — evita pegar histórico concluído.
        $pr = $this->db->prepare(
            "SELECT COUNT(*) FROM ordens_servico o JOIN os_status s ON s.id = o.status_id
             WHERE o.empresa_id = ? AND s.nome LIKE '%retirada%'
               AND s.tipo NOT IN ('entregue','cancelada')"
        );
        $pr->execute([$eid]);

        return [
            'ent_hoje'    => (int) $ent['ent_hoje'], 'ent_7d' => (int) $ent['ent_7d'], 'ent_30d' => (int) $ent['ent_30d'],
            'sai_hoje'    => (int) $sai['sai_hoje'], 'sai_7d' => (int) $sai['sai_7d'], 'sai_30d' => (int) $sai['sai_30d'],
            'em_aberto'      => $emAberto,
            'prontas_retirar'=> (int) $pr->fetchColumn(),
            'tempo_medio' => $sai['tempo_medio'] !== null ? round((float) $sai['tempo_medio'], 1) : null,
        ];
    }

    /** Entradas e saídas (concluídas) por dia — para o gráfico de fluxo. */
    public function fluxoOsDiario(int $dias = 30): array
    {
        $eid = $this->empresaId();
        $ent = $this->query(
            "SELECT DATE(data_entrada) d, COUNT(*) n FROM ordens_servico
             WHERE empresa_id = ? AND data_entrada >= DATE_SUB(CURDATE(), INTERVAL {$dias} DAY)
             GROUP BY DATE(data_entrada)", [$eid]);
        $sai = $this->query(
            "SELECT DATE(o.data_conclusao) d, COUNT(*) n FROM ordens_servico o
             JOIN os_status s ON s.id = o.status_id
             WHERE o.empresa_id = ? AND s.tipo IN ('concluida','entregue')
               AND o.data_conclusao >= DATE_SUB(CURDATE(), INTERVAL {$dias} DAY)
             GROUP BY DATE(o.data_conclusao)", [$eid]);
        $mE = []; foreach ($ent as $r) $mE[$r['d']] = (int) $r['n'];
        $mS = []; foreach ($sai as $r) $mS[$r['d']] = (int) $r['n'];
        $out = [];
        for ($i = $dias; $i >= 0; $i--) {
            $dia = date('Y-m-d', strtotime("-{$i} days"));
            $out[] = ['label' => date('d/m', strtotime($dia)), 'entradas' => $mE[$dia] ?? 0, 'saidas' => $mS[$dia] ?? 0];
        }
        return $out;
    }

    /** OS recebidas × concluídas por mês (12 meses) — para o gráfico de tendência. */
    public function osPorMes(int $meses = 12): array
    {
        $eid = $this->empresaId();
        $ent = $this->query(
            "SELECT DATE_FORMAT(data_entrada,'%Y-%m') m, COUNT(*) n FROM ordens_servico
             WHERE empresa_id = ? AND data_entrada >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL " . ($meses - 1) . " MONTH)
             GROUP BY m", [$eid]);
        $con = $this->query(
            "SELECT DATE_FORMAT(o.data_conclusao,'%Y-%m') m, COUNT(*) n FROM ordens_servico o
             JOIN os_status s ON s.id = o.status_id
             WHERE o.empresa_id = ? AND s.tipo IN ('concluida','entregue')
               AND o.data_conclusao >= DATE_SUB(DATE_FORMAT(CURDATE(),'%Y-%m-01'), INTERVAL " . ($meses - 1) . " MONTH)
             GROUP BY m", [$eid]);
        $mE = []; foreach ($ent as $r) $mE[$r['m']] = (int) $r['n'];
        $mC = []; foreach ($con as $r) $mC[$r['m']] = (int) $r['n'];
        $out = [];
        for ($i = $meses - 1; $i >= 0; $i--) {
            $ts = strtotime(date('Y-m-01') . " -{$i} months");
            $m  = date('Y-m', $ts);
            $out[] = ['label' => date('M/y', $ts), 'entradas' => $mE[$m] ?? 0, 'concluidas' => $mC[$m] ?? 0];
        }
        return $out;
    }
}
