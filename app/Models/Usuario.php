<?php

namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    protected string $table = 'usuarios';

    public function findByEmail(string $email, int $empresaId): ?array
    {
        return $this->queryOne(
            "SELECT * FROM usuarios WHERE email = ? AND empresa_id = ? AND ativo = 1 LIMIT 1",
            [$email, $empresaId]
        );
    }

    public function findByEmailGlobal(string $email): ?array
    {
        return $this->queryOne(
            "SELECT u.*, e.nome_fantasia AS empresa_nome FROM usuarios u
             JOIN empresas e ON e.id = u.empresa_id
             WHERE u.email = ? AND u.ativo = 1 LIMIT 1",
            [$email]
        );
    }

    public function permissoes(int $usuarioId): array
    {
        return $this->query(
            "SELECT * FROM usuario_permissoes WHERE usuario_id = ?",
            [$usuarioId]
        );
    }

    public function updateUltimoLogin(int $id): void
    {
        $this->db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id = ?")->execute([$id]);
    }

    public function tecnicos(): array
    {
        return $this->query(
            "SELECT id, nome FROM usuarios WHERE empresa_id = ? AND perfil IN ('tecnico','admin','gerente') AND ativo = 1 AND atende_os = 1 ORDER BY nome",
            [$this->empresaId()]
        );
    }
}
