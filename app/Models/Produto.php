<?php

namespace App\Models;

use App\Core\Model;

class Produto extends Model
{
    protected string $table = 'produtos';

    public function listar(int $page = 1, int $perPage = 20, string $busca = '', ?int $categoria = null, bool $soBaixo = false): array
    {
        $eid = $this->empresaId();
        $where = "p.empresa_id = ? AND p.ativo = 1";
        $params = [$eid];
        if ($busca) {
            $where .= " AND (p.nome LIKE ? OR p.codigo LIKE ? OR p.codigo_barras LIKE ?)";
            array_push($params, "%{$busca}%", "%{$busca}%", "%{$busca}%");
        }
        if ($categoria) {
            $where .= " AND p.categoria_id = ?";
            $params[] = $categoria;
        }
        if ($soBaixo) {
            $where .= " AND p.estoque_atual <= p.estoque_minimo";
        }

        $stmtC = $this->db->prepare("SELECT COUNT(*) FROM produtos p WHERE {$where}");
        $stmtC->execute($params);
        $total = (int) $stmtC->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT p.*, cp.nome AS categoria_nome, f.razao_social AS fornecedor_nome
             FROM produtos p
             LEFT JOIN categorias_produto cp ON cp.id = p.categoria_id
             LEFT JOIN fornecedores f ON f.id = p.fornecedor_id
             WHERE {$where} ORDER BY p.nome LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute($params);

        return [
            'data' => $stmt->fetchAll(), 'total' => $total,
            'per_page' => $perPage, 'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function emEstoqueMinimo(): array
    {
        return $this->query(
            "SELECT * FROM produtos WHERE empresa_id = ? AND ativo = 1 AND estoque_atual <= estoque_minimo ORDER BY nome",
            [$this->empresaId()]
        );
    }

    public function buscar(string $termo): array
    {
        return $this->query(
            "SELECT id, codigo, nome, estoque_atual, unidade, valor_venda FROM produtos
             WHERE empresa_id = ? AND ativo = 1 AND (nome LIKE ? OR codigo LIKE ? OR codigo_barras LIKE ?) LIMIT 20",
            [$this->empresaId(), "%{$termo}%", "%{$termo}%", "%{$termo}%"]
        );
    }

    public function movimentar(int $produtoId, string $tipo, float $qtd, float $valorUnit, string $motivo = '', ?int $osId = null): void
    {
        $eid = $this->empresaId();
        $stmt = $this->db->prepare("SELECT estoque_atual FROM produtos WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$produtoId, $eid]);
        $saldoAnt = (float) $stmt->fetchColumn();
        $saldoPost = $tipo === 'entrada' ? $saldoAnt + $qtd : $saldoAnt - $qtd;

        $this->db->prepare(
            "INSERT INTO movimentos_estoque (empresa_id, produto_id, usuario_id, tipo, quantidade, saldo_anterior, saldo_posterior, valor_unitario, motivo, os_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([$eid, $produtoId, $_SESSION['usuario_id'] ?? null, $tipo, $qtd, $saldoAnt, $saldoPost, $valorUnit, $motivo, $osId]);

        $this->db->prepare("UPDATE produtos SET estoque_atual = ? WHERE id = ? AND empresa_id = ?")
                 ->execute([$saldoPost, $produtoId, $eid]);
    }
}
