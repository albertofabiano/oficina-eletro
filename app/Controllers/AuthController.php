<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;
use App\Models\Usuario;
use App\Services\EmailService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth.login', [], 'auth');
    }

    public function login(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/login')); }

        $email  = trim($this->post('email', ''));
        $senha  = $this->post('senha', '');

        $model = new Usuario();
        $usuario = $model->findByEmailGlobal($email);

        if (!$usuario || !password_verify($senha, $usuario['senha'])) {
            $this->flash('error', 'E-mail ou senha incorretos.');
            $this->redirect(url('/login'));
        }

        $permissoes = $model->permissoes($usuario['id']);
        Auth::login($usuario, $permissoes);
        $model->updateUltimoLogin($usuario['id']);

        $this->redirect(url('/dashboard'));
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(url('/login'));
    }

    // ── Sai do modo demonstração e vai direto pro cadastro real ──────────
    public function sairParaCadastro(): void
    {
        Auth::logout();
        $this->redirect(url('/cadastrar'));
    }

    // ── Modo demonstração: entra no painel sem cadastro ──────────────────
    public function demo(): void
    {
        $model   = new Usuario();
        $usuario = $model->findByEmailGlobal('demo@fixaos.com.br');
        if (!$usuario) {
            $this->flash('error', 'A demonstração está indisponível no momento. Tente novamente em instantes.');
            $this->redirect(url('/login'));
        }
        $permissoes = $model->permissoes($usuario['id']);
        Auth::login($usuario, $permissoes);
        $_SESSION['demo_mode'] = true;
        $this->redirect(url('/dashboard'));
    }

    // ── Recuperação de senha ─────────────────────────────────────────────
    public function esqueciSenha(): void
    {
        $this->view('auth.esqueci_senha', [], 'auth');
    }

    public function enviarReset(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/esqueci-senha')); }

        $email = trim($this->post('email', ''));
        $db = DB::pdo();
        $st = $db->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();

        // Só envia se existir — mas a resposta é sempre genérica (não revela se o e-mail está cadastrado)
        if ($u) {
            $token = bin2hex(random_bytes(32));
            $db->prepare("UPDATE usuarios SET token_reset = ?, token_reset_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?")
               ->execute([$token, $u['id']]);
            $base = rtrim((require BASE_PATH . '/config/app.php')['url'], '/');
            $link = $base . '/redefinir-senha/' . $token;
            EmailService::send($u['email'], $u['nome'], 'Redefinir sua senha — FixaOS', $this->emailResetHtml($u['nome'], $link));
        }

        $this->flash('success', 'Se este e-mail estiver cadastrado, enviamos um link para redefinir a senha. Verifique sua caixa de entrada (e o spam).');
        $this->redirect(url('/esqueci-senha'));
    }

    public function resetForm(string $token): void
    {
        $st = DB::pdo()->prepare("SELECT id FROM usuarios WHERE token_reset = ? AND token_reset_expira >= NOW() LIMIT 1");
        $st->execute([$token]);
        if (!$st->fetch()) {
            $this->flash('error', 'Link inválido ou expirado. Solicite um novo.');
            $this->redirect(url('/esqueci-senha'));
        }
        $this->view('auth.reset_senha', ['token' => $token], 'auth');
    }

    public function resetar(string $token): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/redefinir-senha/' . $token)); }

        $db = DB::pdo();
        $st = $db->prepare("SELECT id FROM usuarios WHERE token_reset = ? AND token_reset_expira >= NOW() LIMIT 1");
        $st->execute([$token]);
        $u = $st->fetch();
        if (!$u) {
            $this->flash('error', 'Link inválido ou expirado. Solicite um novo.');
            $this->redirect(url('/esqueci-senha'));
        }

        $senha = $this->post('senha', '');
        $conf  = $this->post('senha_confirma', '');
        if (strlen($senha) < 6) { $this->flash('error', 'A senha deve ter ao menos 6 caracteres.'); $this->redirect(url('/redefinir-senha/' . $token)); }
        if ($senha !== $conf)   { $this->flash('error', 'As senhas não conferem.'); $this->redirect(url('/redefinir-senha/' . $token)); }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $db->prepare("UPDATE usuarios SET senha = ?, token_reset = NULL, token_reset_expira = NULL WHERE id = ?")->execute([$hash, $u['id']]);

        $this->flash('success', 'Senha redefinida com sucesso! Faça login com a nova senha.');
        $this->redirect(url('/login'));
    }

    private function emailResetHtml(string $nome, string $link): string
    {
        $n = htmlspecialchars($nome);
        $l = htmlspecialchars($link);
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto">'
            . '<div style="background:#1e3a5f;padding:20px;text-align:center;border-radius:10px 10px 0 0">'
            . '<span style="color:#fff;font-size:22px;font-weight:900">Fixa<span style="color:#f97316">OS</span></span></div>'
            . '<div style="border:1px solid #e2e8f0;border-top:none;padding:24px;border-radius:0 0 10px 10px">'
            . '<p style="color:#0f172a;margin:0 0 12px">Olá, ' . $n . '!</p>'
            . '<p style="color:#374151;line-height:1.6;margin:0 0 8px">Recebemos um pedido para redefinir a senha da sua conta no FixaOS. Clique no botão abaixo para criar uma nova senha:</p>'
            . '<p style="text-align:center;margin:26px 0"><a href="' . $l . '" style="background:#4f46e5;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;display:inline-block">Redefinir minha senha</a></p>'
            . '<p style="color:#64748b;font-size:13px;line-height:1.6;margin:0 0 8px">O link expira em 1 hora. Se você não fez esse pedido, ignore este e-mail — sua senha continua a mesma.</p>'
            . '<p style="color:#94a3b8;font-size:12px;word-break:break-all;margin:0">Ou copie e cole no navegador: ' . $l . '</p>'
            . '</div></div>';
    }
}
