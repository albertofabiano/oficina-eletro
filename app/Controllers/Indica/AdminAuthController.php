<?php

namespace App\Controllers\Indica;

use App\Core\Controller;
use App\Core\DB;

/**
 * Login do painel admin do Fixa Indica (/admin/login) — mesma CONTA de master_admins já usada
 * em /master/* no FixaOS principal, mas sessão própria (cookie por domínio, ver
 * App\Middleware\Indica\MasterMiddleware) — por isso não reaproveita MasterController::login()
 * direto (que redireciona pra url('/master'), domínio errado aqui).
 */
class AdminAuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('indica.admin.login', [
            'tituloFull' => 'Login — Fixa Indica Admin',
            'noindex'    => true,
        ], 'indica_admin_login');
    }

    public function login(): void
    {
        if (!csrf_verify()) {
            $this->flash('error', 'Token inválido.');
            $this->redirect(indica_url('/admin/login'));
            return;
        }

        $email = trim((string) $this->post('email', ''));
        $senha = (string) $this->post('senha', '');

        $stmt = DB::pdo()->prepare("SELECT * FROM master_admins WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($senha, $admin['senha'])) {
            $this->flash('error', 'Credenciais inválidas.');
            $this->redirect(indica_url('/admin/login'));
            return;
        }

        session_regenerate_id(true);
        $_SESSION['master_id']    = $admin['id'];
        $_SESSION['master_nome']  = $admin['nome'];
        $_SESSION['master_email'] = $admin['email'];

        $this->redirect(indica_url('/admin'));
    }

    public function logout(): void
    {
        unset($_SESSION['master_id'], $_SESSION['master_nome'], $_SESSION['master_email']);
        $this->redirect(indica_url('/admin/login'));
    }
}
