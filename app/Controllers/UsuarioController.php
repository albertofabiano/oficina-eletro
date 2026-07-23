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

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->flash('error', 'E-mail inválido.'); $this->redirectBack(); }
        if (strlen($senha) < 6) { $this->flash('error', 'Senha mínima: 6 caracteres.'); $this->redirectBack(); }

        // Limite de usuários do plano (dormente se cobrança off).
        $stCnt = DB::pdo()->prepare("SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND ativo = 1");
        $stCnt->execute([$eid]);
        $msgLim = limite_plano_atingido($eid, 'max_usuarios', (int) $stCnt->fetchColumn());
        if ($msgLim) { $this->flash('error', $msgLim . ' 👉 Veja os planos em Configurações → Planos.'); $this->redirectBack(); }

        // E-mail precisa ser único no sistema TODO (o login é global por e-mail;
        // repetir o mesmo e-mail em empresas diferentes torna login/recuperação ambíguos).
        $stmtCheck = DB::pdo()->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ((int) $stmtCheck->fetchColumn() > 0) {
            $this->flash('error', 'Este e-mail já está em uso por outro usuário. Cada pessoa precisa de um e-mail próprio.');
            $this->redirectBack();
        }

        DB::pdo()->prepare(
            "INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, telefone, atende_os, ativo)
             VALUES (?,?,?,?,?,?,?,1)"
        )->execute([
            $eid,
            trim($this->post('nome')),
            $email,
            password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]),
            $this->post('perfil', 'tecnico'),
            $this->post('telefone'),
            $this->post('atende_os') ? 1 : 0,
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
        $novoPerfil = $this->post('perfil', 'tecnico');
        $novoAtivo  = (int) $this->post('ativo', 1);
        $novoAtendeOs = $this->post('atende_os') ? 1 : 0;

        // Proteção: nunca deixar a empresa sem nenhum admin/superadmin ativo.
        if (!in_array($novoPerfil, ['admin', 'superadmin'], true) || $novoAtivo === 0) {
            $stmtAtual = DB::pdo()->prepare("SELECT perfil FROM usuarios WHERE id = ? AND empresa_id = ?");
            $stmtAtual->execute([(int) $id, $eid]);
            $perfilAtual = $stmtAtual->fetchColumn();
            if (in_array($perfilAtual, ['admin', 'superadmin'], true)) {
                $stmtOutros = DB::pdo()->prepare(
                    "SELECT COUNT(*) FROM usuarios WHERE empresa_id = ? AND id != ? AND perfil IN ('admin','superadmin') AND ativo = 1"
                );
                $stmtOutros->execute([$eid, (int) $id]);
                if ((int) $stmtOutros->fetchColumn() === 0) {
                    $this->flash('error', 'Não é possível remover ou desativar o perfil de administrador deste usuário: a empresa ficaria sem nenhum administrador ativo. Promova outro usuário a administrador antes.');
                    $this->redirectBack();
                    return;
                }
            }
        }

        $data = [
            'nome'      => trim($this->post('nome')),
            'perfil'    => $novoPerfil,
            'telefone'  => $this->post('telefone'),
            'atende_os' => $novoAtendeOs,
            'ativo'     => $novoAtivo,
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
