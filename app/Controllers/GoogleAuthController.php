<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;
use App\Models\Usuario;

class GoogleAuthController extends Controller
{
    private string $clientId;
    private string $clientSecret;
    private string $redirectUri;

    public function __construct()
    {
        $cfg = require BASE_PATH . '/config/google.php';
        $this->clientId     = $cfg['client_id'];
        $this->clientSecret = $cfg['client_secret'];
        $this->redirectUri  = $cfg['redirect_uri'];
    }

    public function redirectToGoogle(): void
    {
        if (!$this->clientId) {
            $this->flash('error', 'Login com Google não configurado. Configure as credenciais no painel master.');
            $this->redirect(url('/login'));
        }

        // Intenção: cadastro no diretório (grátis) tem fluxo próprio no callback.
        if (($_GET['to'] ?? '') === 'diretorio') {
            $_SESSION['google_intent'] = 'diretorio';
        } else {
            unset($_SESSION['google_intent']);
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = http_build_query([
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'response_type' => 'code',
            'scope'         => 'openid email profile',
            'access_type'   => 'online',
            'state'         => $state,
        ]);

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    public function callback(): void
    {
        $state = $_GET['state'] ?? '';
        $code  = $_GET['code']  ?? '';
        $error = $_GET['error'] ?? '';

        if ($error || !$code) {
            $this->flash('error', 'Login com Google cancelado.');
            $this->redirect(url('/login'));
        }

        if (!hash_equals($_SESSION['google_oauth_state'] ?? '', $state)) {
            $this->flash('error', 'Estado inválido. Tente novamente.');
            $this->redirect(url('/login'));
        }
        unset($_SESSION['google_oauth_state']);

        // Trocar code por token
        $tokenData = $this->fetchToken($code);
        if (!$tokenData || empty($tokenData['access_token'])) {
            $this->flash('error', 'Erro ao autenticar com Google.');
            $this->redirect(url('/login'));
        }

        // Buscar dados do usuário
        $googleUser = $this->fetchUserInfo($tokenData['access_token']);
        if (!$googleUser || empty($googleUser['email'])) {
            $this->flash('error', 'Não foi possível obter os dados do Google.');
            $this->redirect(url('/login'));
        }

        $pdo = DB::pdo();

        // Verificar se já existe usuário com esse google_id
        $usuario = $pdo->prepare(
            "SELECT u.*, e.nome_fantasia AS empresa_nome FROM usuarios u
             JOIN empresas e ON e.id = u.empresa_id
             WHERE u.google_id = ? AND u.ativo = 1 LIMIT 1"
        );
        $usuario->execute([$googleUser['sub']]);
        $user = $usuario->fetch();

        // Ou por email
        if (!$user) {
            $model = new Usuario();
            $user  = $model->findByEmailGlobal($googleUser['email']);

            // Vincular google_id se encontrou pelo email
            if ($user) {
                $pdo->prepare("UPDATE usuarios SET google_id = ? WHERE id = ?")->execute([$googleUser['sub'], $user['id']]);
            }
        }

        $intentDiretorio = (($_SESSION['google_intent'] ?? '') === 'diretorio');

        if ($user) {
            // Login direto
            $model = new Usuario();
            $perms = $model->permissoes($user['id']);
            Auth::login($user, $perms);
            $model->updateUltimoLogin($user['id']);
            unset($_SESSION['google_intent']);
            // Conta só-diretório cai no gerenciamento do perfil público.
            $destino = (($user['tipo_conta'] ?? '') === 'diretorio' || $intentDiretorio)
                ? '/empresa/perfil-publico' : '/dashboard';
            $this->redirect(url($destino));
        }

        // Usuário não encontrado — redirecionar para cadastro com dados pré-preenchidos
        $_SESSION['google_signup'] = [
            'google_id' => $googleUser['sub'],
            'nome'      => $googleUser['name']  ?? '',
            'email'     => $googleUser['email'] ?? '',
            'avatar'    => $googleUser['picture'] ?? '',
        ];

        // Cadastro no diretório tem fluxo próprio.
        if ($intentDiretorio) {
            unset($_SESSION['google_intent']);
            $this->redirect(url('/diretorio/cadastrar?via=google'));
        }
        $this->redirect(url('/cadastrar?via=google'));
    }

    private function fetchToken(string $code): ?array
    {
        $ctx = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => 'Content-Type: application/x-www-form-urlencoded',
            'content' => http_build_query([
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
                'grant_type'    => 'authorization_code',
            ]),
        ]]);

        $res = @file_get_contents('https://oauth2.googleapis.com/token', false, $ctx);
        return $res ? json_decode($res, true) : null;
    }

    private function fetchUserInfo(string $accessToken): ?array
    {
        $ctx = stream_context_create(['http' => [
            'header' => 'Authorization: Bearer ' . $accessToken,
        ]]);

        $res = @file_get_contents('https://www.googleapis.com/oauth2/v3/userinfo', false, $ctx);
        return $res ? json_decode($res, true) : null;
    }
}
