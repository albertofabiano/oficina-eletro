<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Models\Usuario;

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
}
