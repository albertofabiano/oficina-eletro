<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table = '';
    protected string $primaryKey = 'id';
    protected PDO $db;

    public function __construct()
    {
        $this->db = DB::connection();
    }

    protected function empresaId(): int
    {
        return (int) ($_SESSION['empresa_id'] ?? 0);
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? AND empresa_id = ? LIMIT 1"
        );
        $stmt->execute([$id, $this->empresaId()]);
        return $stmt->fetch() ?: null;
    }

    public function all(string $orderBy = 'id', string $dir = 'DESC', int $limit = 0, int $offset = 0): array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE empresa_id = ? ORDER BY `{$orderBy}` {$dir}";
        if ($limit > 0) $sql .= " LIMIT {$limit} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$this->empresaId()]);
        return $stmt->fetchAll();
    }

    public function count(string $where = '', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE empresa_id = ?";
        $bindings = [$this->empresaId()];
        if ($where) {
            $sql .= " AND ({$where})";
            $bindings = array_merge($bindings, $params);
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        return (int) $stmt->fetchColumn();
    }

    public function insert(array $data): int
    {
        $data['empresa_id'] = $this->empresaId();
        $cols = implode(', ', array_map(fn($c) => "`{$c}`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = $this->db->prepare("INSERT INTO `{$this->table}` ({$cols}) VALUES ({$placeholders})");
        $stmt->execute(array_values($data));
        return (int) $this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($c) => "`{$c}` = ?", array_keys($data)));
        $stmt = $this->db->prepare(
            "UPDATE `{$this->table}` SET {$set} WHERE `{$this->primaryKey}` = ? AND empresa_id = ?"
        );
        return $stmt->execute([...array_values($data), $id, $this->empresaId()]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare(
            "DELETE FROM `{$this->table}` WHERE `{$this->primaryKey}` = ? AND empresa_id = ?"
        );
        return $stmt->execute([$id, $this->empresaId()]);
    }

    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function paginate(int $page, int $perPage, string $where = '', array $params = [], string $orderBy = 'id', string $dir = 'DESC'): array
    {
        $total = $this->count($where, $params);
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM `{$this->table}` WHERE empresa_id = ?";
        $bindings = [$this->empresaId()];
        if ($where) {
            $sql .= " AND ({$where})";
            $bindings = array_merge($bindings, $params);
        }
        $sql .= " ORDER BY `{$orderBy}` {$dir} LIMIT {$perPage} OFFSET {$offset}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($bindings);
        return [
            'data'         => $stmt->fetchAll(),
            'total'        => $total,
            'per_page'     => $perPage,
            'current_page' => $page,
            'last_page'    => (int) ceil($total / $perPage),
        ];
    }
}
