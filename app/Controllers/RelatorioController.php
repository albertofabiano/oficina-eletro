<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class RelatorioController extends Controller
{
    public function imprimir(): void
    {
        $eid      = $this->empresaId();
        $db       = DB::pdo();
        $ini      = $this->get('data_inicio', date('Y-m-01'));
        $fim      = $this->get('data_fim',    date('Y-m-t'));
        $statusId = $this->get('status_id',   '');

        // Buscar status da empresa
        $stmtS = $db->prepare("SELECT * FROM os_status WHERE empresa_id = ? ORDER BY ordem");
        $stmtS->execute([$eid]);
        $statusList = $stmtS->fetchAll();

        // Montar filtro
        $where  = "os.empresa_id = ? AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?";
        $params = [$eid, $ini, $fim];

        $statusNome = 'Todos os Status';
        if ($statusId) {
            $where   .= " AND os.status_id = ?";
            $params[] = $statusId;
            foreach ($statusList as $s) {
                if ($s['id'] == $statusId) { $statusNome = $s['nome']; break; }
            }
        }

        // OS filtradas
        $stmtOS = $db->prepare(
            "SELECT os.numero, os.valor_total, os.situacao_pagamento,
             os.data_entrada, os.data_conclusao, os.defeito_relatado,
             c.nome AS cliente_nome, c.telefone AS cliente_tel,
             eq.marca AS equip_marca, eq.modelo AS equip_modelo, eq.tipo AS equip_tipo,
             s.nome AS status_nome, s.cor AS status_cor, s.cor_fonte AS status_cor_fonte,
             u.nome AS tecnico_nome
             FROM ordens_servico os
             LEFT JOIN clientes c    ON c.id  = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             LEFT JOIN os_status s   ON s.id  = os.status_id
             LEFT JOIN usuarios u    ON u.id  = os.tecnico_id
             WHERE {$where}
             ORDER BY COALESCE(os.data_entrada,os.criado_em) DESC"
        );
        $stmtOS->execute($params);
        $ordens = $stmtOS->fetchAll();

        // Totais
        $total     = count($ordens);
        $faturado  = array_sum(array_column($ordens, 'valor_total'));
        $ticket    = $total > 0 ? $faturado / $total : 0;

        // Empresa
        $empresa = $db->prepare("SELECT * FROM empresas WHERE id = ?")->execute([$eid])
            ? $db->query("SELECT * FROM empresas WHERE id = $eid")->fetch()
            : [];

        $this->view('relatorios.imprimir', [
            'titulo'      => 'Impressão de Relatório',
            'ordens'      => $ordens,
            'statusList'  => $statusList,
            'statusNome'  => $statusNome,
            'statusId'    => $statusId,
            'ini'         => $ini,
            'fim'         => $fim,
            'total'       => $total,
            'faturado'    => $faturado,
            'ticket'      => $ticket,
            'empresa'     => $empresa,
        ], 'print_relatorio');
    }

    public function index(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $ini      = $this->get('data_inicio', date('Y-m-01'));
        $fim      = $this->get('data_fim',    date('Y-m-t'));
        $statusId = (int)$this->get('status_id', 0);

        // Filtro de status adicional
        $statusFiltro      = $statusId ? " AND os.status_id = {$statusId}" : '';
        $statusFiltroSvc   = $statusId ? " AND os2.status_id = {$statusId}" : '';

        // Resumo geral
        $stmtRes = $db->prepare(
            "SELECT COUNT(*) AS total_os,
             COALESCE(SUM(os.valor_total),0) AS total_faturado,
             COALESCE(AVG(NULLIF(os.valor_total,0)),0) AS ticket_medio,
             SUM(CASE WHEN s.tipo IN ('concluida','entregue') THEN 1 ELSE 0 END) AS concluidas,
             SUM(CASE WHEN s.tipo = 'cancelada' THEN 1 ELSE 0 END) AS canceladas,
             SUM(CASE WHEN os.tipo_servico = 'garantia' THEN 1 ELSE 0 END) AS retornos_garantia,
             COALESCE(AVG(CASE WHEN os.data_conclusao IS NOT NULL
               THEN DATEDIFF(os.data_conclusao, os.data_entrada) END),0) AS tempo_medio_dias
             FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ? AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?
             {$statusFiltro}"
        );
        $stmtRes->execute([$eid, $ini, $fim]);

        // OS por status
        $statusFiltroOS = $statusId ? " AND s.id = {$statusId}" : '';
        $stmtOS = $db->prepare(
            "SELECT s.nome, s.cor, s.cor_fonte, s.id, COUNT(os.id) AS total,
             COALESCE(SUM(os.valor_total),0) AS valor
             FROM os_status s
             LEFT JOIN ordens_servico os ON os.status_id = s.id AND os.empresa_id = s.empresa_id
               AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?
             WHERE s.empresa_id = ? {$statusFiltroOS} GROUP BY s.id ORDER BY total DESC"
        );
        $stmtOS->execute([$ini, $fim, $eid]);

        // Faturamento por mês (12 meses)
        $stmtFat = $db->prepare(
            "SELECT DATE_FORMAT(COALESCE(os.data_conclusao,os.criado_em),'%Y-%m') AS mes,
             MIN(DATE_FORMAT(COALESCE(os.data_conclusao,os.criado_em),'%b/%y')) AS mes_label,
             SUM(os.valor_total) AS total, COUNT(*) AS qtd
             FROM ordens_servico os JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?
             AND COALESCE(os.data_conclusao,os.criado_em) >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
             {$statusFiltro}
             GROUP BY DATE_FORMAT(COALESCE(os.data_conclusao,os.criado_em),'%Y-%m')
             ORDER BY mes"
        );
        $stmtFat->execute([$eid]);

        // Faturamento por técnico
        $stmtTec = $db->prepare(
            "SELECT COALESCE(u.nome,'Sem técnico') AS tecnico,
             COUNT(os.id) AS total_os,
             COALESCE(SUM(os.valor_total),0) AS faturamento,
             COALESCE(AVG(NULLIF(os.valor_total,0)),0) AS ticket_medio,
             SUM(CASE WHEN s.tipo IN ('concluida','entregue') THEN 1 ELSE 0 END) AS concluidas,
             COALESCE(AVG(CASE WHEN os.data_conclusao IS NOT NULL
               THEN DATEDIFF(os.data_conclusao, os.data_entrada) END),0) AS tempo_medio
             FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             LEFT JOIN usuarios u ON u.id = os.tecnico_id
             WHERE os.empresa_id = ? AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?
             {$statusFiltro}
             GROUP BY os.tecnico_id ORDER BY faturamento DESC LIMIT 10"
        );
        $stmtTec->execute([$eid, $ini, $fim]);

        // Top defeitos relatados
        $defFiltro = $statusId ? " AND os.status_id = {$statusId}" : '';
        $stmtDefeito = $db->prepare(
            "SELECT os.defeito_relatado AS defeito, COUNT(*) AS vezes,
             COALESCE(SUM(os.valor_total),0) AS receita
             FROM ordens_servico os
             WHERE os.empresa_id = ? AND os.defeito_relatado IS NOT NULL AND os.defeito_relatado != ''
             AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?
             {$defFiltro}
             GROUP BY os.defeito_relatado ORDER BY vezes DESC LIMIT 10"
        );
        $stmtDefeito->execute([$eid, $ini, $fim]);

        // Top marcas
        $stmtMarca = $db->prepare(
            "SELECT COALESCE(eq.marca,'Não informado') AS marca,
             COUNT(os.id) AS total, COALESCE(SUM(os.valor_total),0) AS receita
             FROM ordens_servico os
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             WHERE os.empresa_id = ? AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?
             {$statusFiltro}
             GROUP BY eq.marca ORDER BY total DESC LIMIT 10"
        );
        $stmtMarca->execute([$eid, $ini, $fim]);

        // Top serviços
        $stmtSvc = $db->prepare(
            "SELECT oss.descricao, COUNT(*) AS vezes, SUM(oss.valor_total) AS receita
             FROM os_servicos oss JOIN ordens_servico os ON os.id = oss.os_id
             WHERE oss.empresa_id = ? AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?
             {$statusFiltro}
             GROUP BY oss.descricao ORDER BY receita DESC LIMIT 10"
        );
        $stmtSvc->execute([$eid, $ini, $fim]);

        // Top clientes
        $stmtCli = $db->prepare(
            "SELECT c.nome, COUNT(os.id) AS total_os,
             COALESCE(SUM(os.valor_total),0) AS total_gasto,
             MAX(COALESCE(os.data_entrada,os.criado_em)) AS ultima_os
             FROM clientes c JOIN ordens_servico os ON os.cliente_id = c.id
             WHERE os.empresa_id = ? AND DATE(COALESCE(os.data_entrada,os.criado_em)) BETWEEN ? AND ?
             {$statusFiltro}
             GROUP BY c.id ORDER BY total_gasto DESC LIMIT 10"
        );
        $stmtCli->execute([$eid, $ini, $fim]);

        // Taxa de retorno em garantia
        $stmtGar = $db->prepare(
            "SELECT COUNT(*) AS total_garantia,
             (SELECT COUNT(*) FROM ordens_servico WHERE empresa_id=? AND tipo_servico != 'garantia'
              AND DATE(COALESCE(data_entrada,criado_em)) BETWEEN ? AND ?) AS total_normal
             FROM ordens_servico WHERE empresa_id=? AND tipo_servico='garantia'
             AND DATE(COALESCE(data_entrada,criado_em)) BETWEEN ? AND ?"
        );
        $stmtGar->execute([$eid, $ini, $fim, $eid, $ini, $fim]);

        $this->view('relatorios.index', [
            'titulo'       => 'Relatórios',
            'ini'          => $ini,
            'fim'          => $fim,
            'resumo'       => $stmtRes->fetch() ?: [],
            'porStatus'    => $stmtOS->fetchAll(),
            'faturamento'  => $stmtFat->fetchAll(),
            'porTecnico'   => $stmtTec->fetchAll(),
            'topDefeitos'  => $stmtDefeito->fetchAll(),
            'topMarcas'    => $stmtMarca->fetchAll(),
            'topServicos'  => $stmtSvc->fetchAll(),
            'topClientes'  => $stmtCli->fetchAll(),
            'garantia'     => $stmtGar->fetch() ?: [],
            'statusIdFiltro' => $statusId,
        ]);
    }
}
