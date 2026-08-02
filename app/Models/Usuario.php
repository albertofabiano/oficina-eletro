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

    /**
     * Login por CNPJ/CPF: o documento é da EMPRESA, não do usuário, então só
     * autentica o titular (o usuário ativo mais antigo dela — quem a cadastrou).
     * Os demais usuários da empresa continuam logando só por e-mail.
     */
    public function findTitularByDocumento(string $documento): ?array
    {
        return $this->queryOne(
            "SELECT u.*, e.nome_fantasia AS empresa_nome FROM usuarios u
             JOIN empresas e ON e.id = u.empresa_id
             WHERE (e.cnpj = ? OR e.cpf = ?) AND u.ativo = 1
             ORDER BY u.id ASC LIMIT 1",
            [$documento, $documento]
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

    /**
     * Lista pra "Técnico responsável" na OS. Qualquer usuário ativo com o toggle "Atende OS?"
     * ligado aparece aqui — não precisa ser perfil Técnico (o dono/admin pode atender OS também,
     * usando a própria conta, sem precisar de um cadastro de técnico separado).
     */
    public function tecnicos(): array
    {
        return $this->query(
            "SELECT id, nome FROM usuarios WHERE empresa_id = ? AND ativo = 1 AND atende_os = 1 ORDER BY nome",
            [$this->empresaId()]
        );
    }
}
