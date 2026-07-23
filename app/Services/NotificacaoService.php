<?php

namespace App\Services;

use App\Core\DB;

class NotificacaoService
{
    private \PDO $db;
    private int $empresaId;

    public function __construct(int $empresaId)
    {
        $this->db        = DB::pdo();
        $this->empresaId = $empresaId;
    }

    // ── Criar notificação ────────────────────────────────────────────────
    public static function criar(int $empresaId, string $tipo, string $titulo, string $mensagem = '', string $link = '', string $icone = 'bi-bell', string $cor = 'primary', ?int $usuarioId = null): void
    {
        $db = DB::pdo();

        // Evitar duplicatas recentes (mesma empresa + tipo + link nas últimas 6h)
        if ($link) {
            $dup = $db->prepare("SELECT COUNT(*) FROM notificacoes WHERE empresa_id=? AND tipo=? AND link=? AND criado_em > DATE_SUB(NOW(), INTERVAL 6 HOUR)");
            $dup->execute([$empresaId, $tipo, $link]);
            if ($dup->fetchColumn() > 0) return;
        }

        $db->prepare("INSERT INTO notificacoes (empresa_id, usuario_id, tipo, titulo, mensagem, link, icone, cor) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([$empresaId, $usuarioId, $tipo, $titulo, $mensagem, $link, $icone, $cor]);
    }

    // ── Gerar notificações automáticas ───────────────────────────────────
    public function gerarTodas(): void
    {
        $this->verificarOsAtrasadas();
        $this->verificarOsAguardandoAprovacao();
        $this->verificarGarantiasVencendo();
        $this->verificarContasVencer();
        $this->verificarEstoqueMinimo();
        $this->verificarAgendaHoje();
        $this->verificarPrazoRetirada();
    }

    // OS com status "Aguardando Aprovação" há mais de 2 dias
    private function verificarOsAguardandoAprovacao(): void
    {
        $stmt = $this->db->prepare("
            SELECT os.id, os.numero, os.empresa_id, c.nome AS cliente_nome
            FROM ordens_servico os
            JOIN os_status s ON s.id = os.status_id
            JOIN clientes c ON c.id = os.cliente_id
            WHERE os.empresa_id = ?
              AND s.tipo = 'aguardando'
              AND os.atualizado_em < DATE_SUB(NOW(), INTERVAL 2 DAY)
              AND os.status_id NOT IN (SELECT id FROM os_status WHERE tipo IN ('entregue','cancelada'))
        ");
        $stmt->execute([$this->empresaId]);
        foreach ($stmt->fetchAll() as $os) {
            self::criar(
                $this->empresaId,
                'os_aguardando',
                "OS {$os['numero']} aguardando há mais de 2 dias",
                "Cliente {$os['cliente_nome']} ainda não aprovou o orçamento.",
                "/os/{$os['id']}",
                'bi-clock-history',
                'warning'
            );
        }
    }

    // OS com data de previsão ultrapassada
    private function verificarOsAtrasadas(): void
    {
        $stmt = $this->db->prepare("
            SELECT os.id, os.numero, os.data_previsao, c.nome AS cliente_nome
            FROM ordens_servico os
            JOIN clientes c ON c.id = os.cliente_id
            JOIN os_status s ON s.id = os.status_id
            WHERE os.empresa_id = ?
              AND os.data_previsao IS NOT NULL
              AND os.data_previsao < CURDATE()
              AND s.tipo NOT IN ('entregue','cancelada','concluida')
        ");
        $stmt->execute([$this->empresaId]);
        foreach ($stmt->fetchAll() as $os) {
            self::criar(
                $this->empresaId,
                'os_atrasada',
                "OS {$os['numero']} atrasada",
                "Previsão era " . date('d/m/Y', strtotime($os['data_previsao'])) . " — Cliente: {$os['cliente_nome']}",
                "/os/{$os['id']}",
                'bi-exclamation-triangle-fill',
                'danger'
            );
        }
    }

    // Garantias vencendo em 7 dias
    private function verificarGarantiasVencendo(): void
    {
        $stmt = $this->db->prepare("
            SELECT os.id, os.numero, os.garantia_ate, c.nome AS cliente_nome
            FROM ordens_servico os
            JOIN clientes c ON c.id = os.cliente_id
            WHERE os.empresa_id = ?
              AND os.garantia_ate IS NOT NULL
              AND os.garantia_ate BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
              AND os.situacao_pagamento = 'pago'
        ");
        $stmt->execute([$this->empresaId]);
        foreach ($stmt->fetchAll() as $os) {
            self::criar(
                $this->empresaId,
                'garantia_vencendo',
                "Garantia da OS {$os['numero']} vence em breve",
                "Cliente {$os['cliente_nome']} — Garantia até " . date('d/m/Y', strtotime($os['garantia_ate'])),
                "/os/{$os['id']}",
                'bi-shield-exclamation',
                'warning'
            );
        }
    }

    // Contas a pagar/receber vencendo em 3 dias
    private function verificarContasVencer(): void
    {
        $stmt = $this->db->prepare("
            SELECT id, descricao, valor, data_vencimento, tipo
            FROM fin_lancamentos
            WHERE empresa_id = ?
              AND status = 'pendente'
              AND data_vencimento IS NOT NULL
              AND data_vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
        ");
        $stmt->execute([$this->empresaId]);
        foreach ($stmt->fetchAll() as $c) {
            $tipo = $c['tipo'] === 'despesa' ? 'Conta a pagar' : 'Recebimento';
            $cor  = $c['tipo'] === 'despesa' ? 'danger' : 'success';
            self::criar(
                $this->empresaId,
                'conta_vencer',
                "$tipo vencendo: " . date('d/m/Y', strtotime($c['data_vencimento'])),
                "{$c['descricao']} — R$ " . number_format($c['valor'], 2, ',', '.'),
                "/financeiro/{$c['id']}/editar",
                $c['tipo'] === 'despesa' ? 'bi-cash-stack' : 'bi-currency-dollar',
                $cor
            );
        }
    }

    // Contas vencidas (atrasadas)
    private function verificarContasAtrasadas(): void
    {
        $stmt = $this->db->prepare("
            SELECT id, descricao, valor, data_vencimento, tipo
            FROM fin_lancamentos
            WHERE empresa_id = ?
              AND status = 'pendente'
              AND data_vencimento < CURDATE()
        ");
        $stmt->execute([$this->empresaId]);
        foreach ($stmt->fetchAll() as $c) {
            self::criar(
                $this->empresaId,
                'conta_atrasada',
                "Conta vencida: {$c['descricao']}",
                "Venceu em " . date('d/m/Y', strtotime($c['data_vencimento'])) . " — R$ " . number_format($c['valor'], 2, ',', '.'),
                "/financeiro/{$c['id']}/editar",
                'bi-exclamation-circle-fill',
                'danger'
            );
        }
    }

    // Estoque mínimo atingido
    private function verificarEstoqueMinimo(): void
    {
        $stmt = $this->db->prepare("
            SELECT id, nome, estoque_atual, estoque_minimo
            FROM produtos
            WHERE empresa_id = ?
              AND estoque_minimo > 0
              AND estoque_atual <= estoque_minimo
              AND ativo = 1
        ");
        $stmt->execute([$this->empresaId]);
        foreach ($stmt->fetchAll() as $p) {
            self::criar(
                $this->empresaId,
                'estoque_minimo',
                "Estoque mínimo: {$p['nome']}",
                "Restam apenas {$p['estoque_atual']} unidade(s). Mínimo configurado: {$p['estoque_minimo']}.",
                "/produtos/{$p['id']}/editar",
                'bi-box-seam',
                'warning'
            );
        }
    }

    // Agendamentos do dia
    private function verificarAgendaHoje(): void
    {
        $stmt = $this->db->prepare("
            SELECT a.id, a.titulo, a.data_inicio, c.nome AS cliente_nome
            FROM agenda a
            LEFT JOIN clientes c ON c.id = a.cliente_id
            WHERE a.empresa_id = ?
              AND DATE(a.data_inicio) = CURDATE()
              AND a.data_inicio > NOW()
              AND a.status != 'cancelado'
        ");
        $stmt->execute([$this->empresaId]);
        $total = $stmt->rowCount();
        if ($total > 0) {
            $rows = $stmt->fetchAll();
            self::criar(
                $this->empresaId,
                'agenda_hoje',
                "$total compromisso(s) hoje",
                "Você tem $total agendamento(s) para hoje.",
                "/agenda",
                'bi-calendar-event-fill',
                'info'
            );
        }
    }

    // Equipamentos prontos para retirada há mais de X dias
    private function verificarPrazoRetirada(): void
    {
        $stmt = $this->db->prepare("
            SELECT os.id, os.numero, os.data_conclusao, c.nome AS cliente_nome,
                   DATEDIFF(NOW(), os.data_conclusao) AS dias
            FROM ordens_servico os
            JOIN os_status s ON s.id = os.status_id
            JOIN clientes c ON c.id = os.cliente_id
            WHERE os.empresa_id = ?
              AND s.tipo = 'concluida'
              AND os.data_conclusao IS NOT NULL
              AND os.data_conclusao <= NOW() - INTERVAL 7 DAY
              AND os.data_conclusao >= NOW() - INTERVAL 60 DAY
            ORDER BY os.data_conclusao DESC
            LIMIT 100
        ");
        $stmt->execute([$this->empresaId]);
        foreach ($stmt->fetchAll() as $os) {
            self::criar(
                $this->empresaId,
                'retirada_pendente',
                "OS {$os['numero']} aguardando retirada há {$os['dias']} dias",
                "Cliente {$os['cliente_nome']} ainda não retirou o equipamento.",
                "/os/{$os['id']}",
                'bi-hourglass-split',
                'warning'
            );
        }
    }

    // ── Buscar notificações ──────────────────────────────────────────────
    public static function buscar(int $empresaId, int $limit = 15): array
    {
        $db = DB::pdo();
        $stmt = $db->prepare("
            SELECT * FROM notificacoes
            WHERE empresa_id = ?
            ORDER BY lida ASC, criado_em DESC
            LIMIT $limit
        ");
        $stmt->execute([$empresaId]);
        return $stmt->fetchAll();
    }

    public static function contar(int $empresaId): int
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notificacoes WHERE empresa_id = ? AND lida = 0");
        $stmt->execute([$empresaId]);
        return (int)$stmt->fetchColumn();
    }

    public static function marcarLida(int $id, int $empresaId): void
    {
        DB::pdo()->prepare("UPDATE notificacoes SET lida = 1 WHERE id = ? AND empresa_id = ?")->execute([$id, $empresaId]);
    }

    public static function marcarTodasLidas(int $empresaId): void
    {
        DB::pdo()->prepare("UPDATE notificacoes SET lida = 1 WHERE empresa_id = ?")->execute([$empresaId]);
    }

    public static function excluir(int $id, int $empresaId): void
    {
        DB::pdo()->prepare("DELETE FROM notificacoes WHERE id = ? AND empresa_id = ?")->execute([$id, $empresaId]);
    }

    public static function limparTodas(int $empresaId): void
    {
        DB::pdo()->prepare("DELETE FROM notificacoes WHERE empresa_id = ?")->execute([$empresaId]);
    }

    public static function limpar(int $empresaId): void
    {
        DB::pdo()->prepare("DELETE FROM notificacoes WHERE empresa_id = ? AND lida = 1 AND criado_em < DATE_SUB(NOW(), INTERVAL 30 DAY)")->execute([$empresaId]);
    }
}
