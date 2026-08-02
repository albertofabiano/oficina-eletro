<?php

namespace App\Models;

use App\Core\Model;
use App\Core\DB;

class Marketplace extends Model
{
    protected string $table = 'marketplace_anuncios';

    // ── Saldo de créditos ─────────────────────────────────────────────────

    public function saldo(?int $empresaId = null): int
    {
        $eid  = $empresaId ?? $this->empresaId();
        $stmt = $this->db->prepare(
            "SELECT saldo_creditos FROM marketplace_creditos WHERE empresa_id = ?"
        );
        $stmt->execute([$eid]);
        $row = $stmt->fetch();

        if (!$row) {
            // Inicializa saldo zerado se não existir
            $this->db->prepare(
                "INSERT IGNORE INTO marketplace_creditos (empresa_id, saldo_creditos) VALUES (?, 0)"
            )->execute([$eid]);
            return 0;
        }
        return (int) $row['saldo_creditos'];
    }

    public function adicionarCreditos(int $empresaId, int $quantidade, string $justificativa, ?int $usuarioId = null): bool
    {
        $this->db->prepare(
            "INSERT INTO marketplace_creditos (empresa_id, saldo_creditos)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE saldo_creditos = saldo_creditos + VALUES(saldo_creditos)"
        )->execute([$empresaId, $quantidade]);

        $this->registrarHistorico($empresaId, 'compra', $quantidade, $justificativa, null, $usuarioId);
        return true;
    }

    public function consumirCredito(int $empresaId): bool
    {
        // Debita 1 crédito atomicamente — falha se saldo < 1
        $stmt = $this->db->prepare(
            "UPDATE marketplace_creditos
             SET saldo_creditos = saldo_creditos - 1
             WHERE empresa_id = ? AND saldo_creditos > 0"
        );
        $stmt->execute([$empresaId]);
        return $stmt->rowCount() > 0;
    }

    public function registrarHistorico(
        int $empresaId,
        string $tipo,
        int $quantidade,
        string $justificativa,
        ?int $anuncioId = null,
        ?int $usuarioId = null
    ): void {
        $this->db->prepare(
            "INSERT INTO marketplace_historico_creditos
             (empresa_id, tipo, quantidade, justificativa, anuncio_id, usuario_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        )->execute([$empresaId, $tipo, $quantidade, $justificativa, $anuncioId, $usuarioId]);
    }

    public function historico(int $page = 1, int $perPage = 20): array
    {
        $eid    = $this->empresaId();
        $offset = ($page - 1) * $perPage;

        $stmtC = $this->db->prepare(
            "SELECT COUNT(*) FROM marketplace_historico_creditos WHERE empresa_id = ?"
        );
        $stmtC->execute([$eid]);
        $total = (int) $stmtC->fetchColumn();

        $stmt = $this->db->prepare(
            "SELECT h.*, a.titulo AS anuncio_titulo
             FROM marketplace_historico_creditos h
             LEFT JOIN marketplace_anuncios a ON a.id = h.anuncio_id
             WHERE h.empresa_id = ?
             ORDER BY h.data DESC
             LIMIT {$perPage} OFFSET {$offset}"
        );
        $stmt->execute([$eid]);

        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }

    // ── Anúncios — Vitrine global ─────────────────────────────────────────

    public function vitrine(int $page, int $perPage, array $filtros = []): array
    {
        $eid  = $this->empresaId();
        $where  = "a.status = 'ativo'";
        $params = [];

        // Não mostrar os próprios anúncios na vitrine
        if (!empty($filtros['ocultar_proprios'])) {
            $where .= " AND a.empresa_id_vendedor != ?";
            $params[] = $eid;
        }
        if (!empty($filtros['tipo'])) {
            $where .= " AND a.tipo LIKE ?";
            $params[] = "%{$filtros['tipo']}%";
        }
        if (!empty($filtros['marca'])) {
            $where .= " AND a.marca LIKE ?";
            $params[] = "%{$filtros['marca']}%";
        }
        if (!empty($filtros['modelo'])) {
            $where .= " AND a.modelo LIKE ?";
            $params[] = "%{$filtros['modelo']}%";
        }
        if (!empty($filtros['busca'])) {
            $b = "%{$filtros['busca']}%";
            $where .= " AND (a.titulo LIKE ? OR a.descricao LIKE ? OR a.marca LIKE ? OR a.modelo LIKE ?)";
            array_push($params, $b, $b, $b, $b);
        }

        $stmtC = $this->db->prepare(
            "SELECT COUNT(*) FROM marketplace_anuncios a WHERE {$where}"
        );
        $stmtC->execute($params);
        $total  = (int) $stmtC->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT a.*,
             e.nome_fantasia AS empresa_nome,
             e.whatsapp      AS empresa_whatsapp,
             e.telefone      AS empresa_telefone,
             e.cidade        AS empresa_cidade,
             e.uf            AS empresa_uf,
             e.logo          AS empresa_logo
             FROM marketplace_anuncios a
             JOIN empresas e ON e.id = a.empresa_id_vendedor
             WHERE {$where}
             ORDER BY a.data_criacao DESC
             LIMIT {$perPage} OFFSET {$offset}"
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

    // ── Anúncios — Meus anúncios ──────────────────────────────────────────

    public function meusAnuncios(int $page, int $perPage, string $status = ''): array
    {
        $eid    = $this->empresaId();
        $where  = "empresa_id_vendedor = ?";
        $params = [$eid];

        if ($status) {
            $where .= " AND status = ?";
            $params[] = $status;
        }

        $stmtC = $this->db->prepare(
            "SELECT COUNT(*) FROM marketplace_anuncios WHERE {$where}"
        );
        $stmtC->execute($params);
        $total  = (int) $stmtC->fetchColumn();
        $offset = ($page - 1) * $perPage;

        $stmt = $this->db->prepare(
            "SELECT * FROM marketplace_anuncios WHERE {$where}
             ORDER BY data_criacao DESC
             LIMIT {$perPage} OFFSET {$offset}"
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

    public function findAnuncio(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM marketplace_anuncios WHERE id = ? AND empresa_id_vendedor = ?"
        );
        $stmt->execute([$id, $this->empresaId()]);
        return $stmt->fetch() ?: null;
    }

    public function criarAnuncio(array $data): int
    {
        $data['empresa_id_vendedor'] = $this->empresaId();
        $data['status']              = 'ativo';

        $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $vals = implode(', ', array_fill(0, count($data), '?'));

        $this->db->prepare("INSERT INTO marketplace_anuncios ({$cols}) VALUES ({$vals})")
                 ->execute(array_values($data));

        return (int) $this->db->lastInsertId();
    }

    public function alterarStatus(int $id, string $status): bool
    {
        $stmt = $this->db->prepare(
            "UPDATE marketplace_anuncios SET status = ?
             WHERE id = ? AND empresa_id_vendedor = ?"
        );
        $stmt->execute([$status, $id, $this->empresaId()]);
        return $stmt->rowCount() > 0;
    }

    // ── Filtros disponíveis ───────────────────────────────────────────────

    public function tiposDisponiveis(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT tipo FROM marketplace_anuncios
             WHERE status = 'ativo' AND tipo IS NOT NULL AND tipo != ''
             ORDER BY tipo"
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    public function marcasDisponiveis(): array
    {
        $stmt = $this->db->query(
            "SELECT DISTINCT marca FROM marketplace_anuncios
             WHERE status = 'ativo' AND marca IS NOT NULL AND marca != ''
             ORDER BY marca"
        );
        return $stmt->fetchAll(\PDO::FETCH_COLUMN);
    }

    // ── Master: listar saldos de todas as empresas ─────────────────────────

    public function saldosTodas(string $busca = ''): array
    {
        $where  = '1=1';
        $params = [];
        if ($busca) {
            $where .= " AND (e.nome_fantasia LIKE ? OR e.email LIKE ?)";
            array_push($params, "%{$busca}%", "%{$busca}%");
        }

        $stmt = $this->db->prepare(
            "SELECT e.id AS empresa_id, e.nome_fantasia, e.email,
             COALESCE(mc.saldo_creditos, 0) AS saldo_creditos
             FROM empresas e
             LEFT JOIN marketplace_creditos mc ON mc.empresa_id = e.id
             WHERE {$where}
             ORDER BY e.nome_fantasia"
        );
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
