<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class RelatorioController extends Controller
{
    public function index(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $ini = $this->get('data_inicio', date('Y-m-01'));
        $fim = $this->get('data_fim',    date('Y-m-t'));

        // OS por status
        $stmtOS = $db->prepare(
            "SELECT s.nome, s.cor, COUNT(os.id) AS total, COALESCE(SUM(os.valor_total),0) AS valor
             FROM os_status s LEFT JOIN ordens_servico os ON os.status_id = s.id AND os.empresa_id = s.empresa_id
             AND DATE(os.criado_em) BETWEEN ? AND ?
             WHERE s.empresa_id = ? GROUP BY s.id ORDER BY s.ordem"
        );
        $stmtOS->execute([$ini, $fim, $eid]);

        // Faturamento por mês (últimos 12 meses)
        $stmtFat = $db->prepare(
            "SELECT DATE_FORMAT(os.criado_em,'%Y-%m') AS mes, SUM(os.valor_total) AS total, COUNT(*) AS qtd
             FROM ordens_servico os JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? AND s.tipo IN ('concluida','entregue')
             AND os.criado_em >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             GROUP BY mes ORDER BY mes"
        );
        $stmtFat->execute([$eid]);

        // Top serviços
        $stmtSvc = $db->prepare(
            "SELECT oss.descricao, COUNT(*) AS vezes, SUM(oss.valor_total) AS receita
             FROM os_servicos oss JOIN ordens_servico os ON os.id = oss.os_id
             WHERE oss.empresa_id = ? AND DATE(os.criado_em) BETWEEN ? AND ?
             GROUP BY oss.descricao ORDER BY vezes DESC LIMIT 10"
        );
        $stmtSvc->execute([$eid, $ini, $fim]);

        // Top clientes
        $stmtCli = $db->prepare(
            "SELECT c.nome, COUNT(os.id) AS total_os, SUM(os.valor_total) AS total_gasto
             FROM clientes c JOIN ordens_servico os ON os.cliente_id = c.id
             WHERE os.empresa_id = ? AND DATE(os.criado_em) BETWEEN ? AND ?
             GROUP BY c.id ORDER BY total_gasto DESC LIMIT 10"
        );
        $stmtCli->execute([$eid, $ini, $fim]);

        // Ticket médio e totais
        $stmtRes = $db->prepare(
            "SELECT COUNT(*) AS total_os, COALESCE(SUM(os.valor_total),0) AS total_faturado,
             COALESCE(AVG(os.valor_total),0) AS ticket_medio,
             SUM(CASE WHEN s.tipo IN ('concluida','entregue') THEN 1 ELSE 0 END) AS concluidas,
             SUM(CASE WHEN s.tipo = 'cancelada' THEN 1 ELSE 0 END) AS canceladas
             FROM ordens_servico os JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? AND DATE(os.criado_em) BETWEEN ? AND ?"
        );
        $stmtRes->execute([$eid, $ini, $fim]);

        $this->view('relatorios.index', [
            'titulo'     => 'Relatórios',
            'ini'        => $ini,
            'fim'        => $fim,
            'porStatus'  => $stmtOS->fetchAll(),
            'faturamento'=> $stmtFat->fetchAll(),
            'topServicos'=> $stmtSvc->fetchAll(),
            'topClientes'=> $stmtCli->fetchAll(),
            'resumo'     => $stmtRes->fetch() ?: [],
        ]);
    }
}
