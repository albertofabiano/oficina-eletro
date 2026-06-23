<?php

namespace App\Models;

use App\Core\Model;

class Cliente extends Model
{
    protected string $table = 'clientes';

    public function buscar(string $termo, int $limit = 20): array
    {
        return $this->query(
            "SELECT * FROM clientes WHERE empresa_id = ? AND status != 'bloqueado'
             AND (nome LIKE ? OR cpf_cnpj LIKE ? OR telefone LIKE ? OR email LIKE ?)
             ORDER BY nome LIMIT {$limit}",
            [$this->empresaId(), "%{$termo}%", "%{$termo}%", "%{$termo}%", "%{$termo}%"]
        );
    }

    public function listarComContadores(int $page = 1, int $perPage = 20, string $busca = ''): array
    {
        $eid = $this->empresaId();
        $where = "c.empresa_id = ?";
        $params = [$eid];
        if ($busca) {
            $where .= " AND (c.nome LIKE ? OR c.cpf_cnpj LIKE ? OR c.telefone LIKE ?)";
            array_push($params, "%{$busca}%", "%{$busca}%", "%{$busca}%");
        }
        $total = (int) $this->db->prepare("SELECT COUNT(*) FROM clientes c WHERE {$where}")
                                 ->execute($params) ? $this->db->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;

        // Simpler count
        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM clientes c WHERE {$where}");
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        $offset = ($page - 1) * $perPage;
        $stmt = $this->db->prepare(
            "SELECT c.*,
             (SELECT COUNT(*) FROM ordens_servico o WHERE o.cliente_id = c.id AND o.empresa_id = c.empresa_id) AS total_os,
             (SELECT MAX(o.criado_em) FROM ordens_servico o WHERE o.cliente_id = c.id) AS ultima_os
             FROM clientes c WHERE {$where} ORDER BY c.nome LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    public function equipamentos(int $clienteId): array
    {
        return $this->query(
            "SELECT e.*, ce.nome AS categoria_nome FROM equipamentos e
             LEFT JOIN categorias_equipamento ce ON ce.id = e.categoria_id
             WHERE e.cliente_id = ? AND e.empresa_id = ? ORDER BY e.criado_em DESC",
            [$clienteId, $this->empresaId()]
        );
    }

    public function historicoOS(int $clienteId): array
    {
        return $this->query(
            "SELECT os.*, s.nome AS status_nome, s.cor AS status_cor, s.tipo AS status_tipo,
             eq.tipo AS equip_tipo, eq.marca, eq.modelo, u.nome AS tecnico_nome
             FROM ordens_servico os
             LEFT JOIN os_status s ON s.id = os.status_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             LEFT JOIN usuarios u ON u.id = os.tecnico_id
             WHERE os.cliente_id = ? AND os.empresa_id = ? ORDER BY os.criado_em DESC",
            [$clienteId, $this->empresaId()]
        );
    }

    public function contatos(int $clienteId): array
    {
        return $this->query(
            "SELECT cc.*, u.nome AS usuario_nome FROM crm_contatos cc
             LEFT JOIN usuarios u ON u.id = cc.usuario_id
             WHERE cc.cliente_id = ? AND cc.empresa_id = ? ORDER BY cc.criado_em DESC",
            [$clienteId, $this->empresaId()]
        );
    }
}
