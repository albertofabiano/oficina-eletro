<?php

namespace App\Models;

use App\Core\Model;

class Dashboard extends Model
{
    protected string $table = 'ordens_servico';

    public function resumo(): array
    {
        $eid = $this->empresaId();

        $stmt = $this->db->prepare(
            "SELECT
             COUNT(*) AS total_os_mes,
             SUM(CASE WHEN s.tipo IN ('aberta','em_andamento','aguardando') THEN 1 ELSE 0 END) AS os_abertas,
             SUM(CASE WHEN s.tipo = 'concluida' THEN 1 ELSE 0 END) AS os_concluidas,
             SUM(CASE WHEN os.data_previsao < NOW() AND s.tipo NOT IN ('concluida','entregue','cancelada') THEN 1 ELSE 0 END) AS os_atrasadas,
             COALESCE(SUM(CASE WHEN s.tipo IN ('concluida','entregue') THEN os.valor_total ELSE 0 END),0) AS faturamento_mes
             FROM ordens_servico os
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? AND MONTH(os.criado_em) = MONTH(CURDATE()) AND YEAR(os.criado_em) = YEAR(CURDATE())"
        );
        $stmt->execute([$eid]);
        $res = $stmt->fetch() ?: [];

        $stmt2 = $this->db->prepare(
            "SELECT COUNT(*) FROM clientes WHERE empresa_id = ? AND MONTH(criado_em) = MONTH(CURDATE())"
        );
        $stmt2->execute([$eid]);
        $res['novos_clientes_mes'] = (int) $stmt2->fetchColumn();

        $stmt3 = $this->db->prepare(
            "SELECT COUNT(*) FROM produtos WHERE empresa_id = ? AND ativo = 1 AND estoque_atual <= estoque_minimo"
        );
        $stmt3->execute([$eid]);
        $res['alertas_estoque'] = (int) $stmt3->fetchColumn();

        return $res;
    }

    public function ultimasOS(int $limit = 10): array
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
            "SELECT DATE_FORMAT(criado_em,'%Y-%m') AS mes, SUM(valor_total) AS total
             FROM ordens_servico os
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? AND s.tipo IN ('concluida','entregue')
             AND os.criado_em >= DATE_SUB(NOW(), INTERVAL {$meses} MONTH)
             GROUP BY DATE_FORMAT(criado_em,'%Y-%m') ORDER BY mes",
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
        $cond = $mes ? "AND MONTH(os.criado_em) = {$mes} AND YEAR(os.criado_em) = YEAR(CURDATE())" : "";
        return $this->query(
            "SELECT oss.descricao, COUNT(*) AS vezes, SUM(oss.valor_total) AS receita
             FROM os_servicos oss
             JOIN ordens_servico os ON os.id = oss.os_id
             WHERE oss.empresa_id = ? {$cond}
             GROUP BY oss.descricao ORDER BY vezes DESC LIMIT {$limit}",
            [$this->empresaId()]
        );
    }
}
