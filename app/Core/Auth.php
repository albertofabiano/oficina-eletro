<?php

namespace App\Core;

class Auth
{
    public static function check(): bool
    {
        return isset($_SESSION['usuario_id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['usuario'] ?? null;
    }

    public static function id(): int
    {
        return (int) ($_SESSION['usuario_id'] ?? 0);
    }

    public static function empresaId(): int
    {
        return (int) ($_SESSION['empresa_id'] ?? 0);
    }

    public static function perfil(): string
    {
        return $_SESSION['usuario']['perfil'] ?? '';
    }

    public static function can(string $modulo, string $acao = 'ver'): bool
    {
        $perfil = self::perfil();
        if (in_array($perfil, ['superadmin', 'admin'])) return true;

        $permissoes = $_SESSION['permissoes'] ?? [];
        foreach ($permissoes as $p) {
            if ($p['modulo'] === $modulo) {
                $acoes = explode(',', $p['acao']);
                return in_array($acao, $acoes);
            }
        }
        return false;
    }

    public static function login(array $usuario, array $permissoes = []): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario_id']  = $usuario['id'];
        $_SESSION['empresa_id']  = $usuario['empresa_id'];
        $_SESSION['usuario']     = $usuario;
        $_SESSION['permissoes']  = $permissoes;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params['path'], $params['domain'],
                $params['secure'], $params['httponly']
            );
        }
        session_destroy();
    }
}
