<?php

namespace App\Middleware;

use App\Core\Auth;

class GuestMiddleware
{
    public function handle(): void
    {
        if (Auth::check()) {
            // Conta só-diretório pode ver a demonstração ao vivo mesmo já logada — é o CTA de
            // upgrade em Empresa → Perfil Público. AuthController::demo() troca a sessão pra
            // conta demo; as demais rotas de "visitante" continuam bloqueadas pra quem já
            // está autenticado.
            $uri = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
            if ($uri === '/demo' && Auth::soDiretorio()) { return; }
            header('Location: ' . url('/dashboard'));
            exit;
        }
    }
}
