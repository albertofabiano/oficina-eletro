<?php

namespace App\Middleware;

use App\Core\Auth;

class AuthMiddleware
{
    public function handle(): void
    {
        if (!Auth::check()) {
            header('Location: ' . url('/login'));
            exit;
        }

        // Sessão única: se essa mesma conta logou em outro dispositivo/navegador depois desta
        // sessão, o token foi sobrescrito e esta sessão é derrubada aqui. A conta de demonstração
        // é compartilhada de propósito (vários visitantes ao mesmo tempo), então fica de fora.
        if (empty($_SESSION['demo_mode']) && !Auth::sessaoValida()) {
            unset($_SESSION['usuario_id'], $_SESSION['usuario'], $_SESSION['empresa_id'], $_SESSION['permissoes'], $_SESSION['sessao_token'], $_SESSION['tipo_conta']);
            $_SESSION['flash']['error'] = 'Sua sessão foi encerrada porque esta conta foi acessada em outro dispositivo ou navegador.';
            header('Location: ' . url('/login'));
            exit;
        }

        // Conta "só diretório": acesso limitado ao perfil público + fórum da comunidade.
        // Bloqueia o sistema completo (OS, financeiro, etc.) e leva pro perfil.
        // O fórum é liberado para essas contas (membros da comunidade e perfis reivindicados).
        if (Auth::soDiretorio()) {
            $uri = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            $liberado = ['/empresa/perfil-publico', '/empresa/logo', '/empresa/exportar', '/logout', '/perfil', '/conta', '/forum'];
            $ok = false;
            foreach ($liberado as $p) { if ($uri === $p || str_starts_with($uri, $p . '/')) { $ok = true; break; } }
            if (!$ok) {
                if ($uri !== '/empresa/perfil-publico') {
                    $_SESSION['flash']['info'] = 'Sua conta é do plano Diretório. Para usar o sistema completo (OS, financeiro, estoque...), faça upgrade quando quiser.';
                }
                header('Location: ' . url('/empresa/perfil-publico'));
                exit;
            }
        }

        // Controle de acesso por papel (função). Se a URL pertence a um módulo
        // restrito e o papel do usuário não pode vê-lo, bloqueia e volta ao painel.
        $modulo = Auth::moduloDoUri($_SERVER['REQUEST_URI'] ?? '/');
        if ($modulo !== null && !Auth::can($modulo, 'ver')) {
            $_SESSION['flash']['error'] = 'Você não tem permissão para acessar essa área. Fale com o administrador da sua empresa.';
            header('Location: ' . url('/dashboard'));
            exit;
        }
    }
}
