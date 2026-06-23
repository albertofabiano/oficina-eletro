<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class UsuarioController extends Controller
{
    public function index(): void
    {
        $eid  = $this->empresaId();
        $stmt = DB::pdo()->prepare(
            "SELECT * FROM usuarios WHERE empresa_id = ? ORDER BY nome"
        );
        $stmt->execute([$eid]);
        $this->view('usuarios.index', [
            'titulo'   => 'Usuários',
            'usuarios' => $stmt->fetchAll(),
        ]);
    }

    public function criar(): void
    {
        $this->view('usuarios.form', ['titulo' => 'Novo Usuário', 'usuario' => []]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid   = $this->empresaId();
        $email = trim($this->post('email', ''));
        $senha = $this->post('senha', '');

        if (strlen($senha) < 6) { $this->flash('error', 'Senha mínima: 6 caracteres.'); $this->redirectBack(); }

        $stmtCheck = DB::pdo()->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND empresa_id = ?");
        $stmtCheck->execute([$email, $eid]);
        if ((int) $stmtCheck->fetchColumn() > 0) {
            $this->flash('error', 'Este e-mail já está cadastrado.');
            $this->redirectBack();
        }

        DB::pdo()->prepare(
            "INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, telefone, ativo)
             VALUES (?,?,?,?,?,?,1)"
        )->execute([
            $eid,
            trim($this->post('nome')),
            $email,
            password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]),
            $this->post('perfil', 'tecnico'),
            $this->post('telefone'),
        ]);

        $this->flash('success', 'Usuário criado!');
        $this->redirect(url('/usuarios'));
    }

    public function editar(string $id): void
    {
        $stmt = DB::pdo()->prepare("SELECT * FROM usuarios WHERE id = ? AND empresa_id = ?");
        $stmt->execute([(int) $id, $this->empresaId()]);
        $usuario = $stmt->fetch();
        if (!$usuario) { $this->flash('error', 'Usuário não encontrado.'); $this->redirect(url('/usuarios')); }
        $this->view('usuarios.form', ['titulo' => 'Editar Usuário', 'usuario' => $usuario]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid  = $this->empresaId();
        $data = [
            'nome'    => trim($this->post('nome')),
            'perfil'  => $this->post('perfil', 'tecnico'),
            'telefone'=> $this->post('telefone'),
            'ativo'   => (int) $this->post('ativo', 1),
        ];

        $senha = $this->post('senha', '');
        if ($senha) {
            if (strlen($senha) < 6) { $this->flash('error', 'Senha mínima: 6 caracteres.'); $this->redirectBack(); }
            $data['senha'] = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        DB::pdo()->prepare("UPDATE usuarios SET $set WHERE id = ? AND empresa_id = ?")
                 ->execute([...array_values($data), (int) $id, $eid]);

        $this->flash('success', 'Usuário atualizado!');
        $this->redirect(url('/usuarios'));
    }

    public function excluir(string $id): void
    {
        if ((int) $id === $this->usuarioId()) {
            $this->flash('error', 'Não é possível excluir o próprio usuário.');
            $this->redirect(url('/usuarios'));
        }
        DB::pdo()->prepare("DELETE FROM usuarios WHERE id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->empresaId()]);
        $this->flash('success', 'Usuário removido.');
        $this->redirect(url('/usuarios'));
    }
}
