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

    /** Só donos (admin/superadmin) da empresa. Usado para ações destrutivas (excluir cliente/OS). */
    public static function isAdmin(): bool
    {
        return in_array(self::perfil(), ['admin', 'superadmin'], true);
    }

    /**
     * Matriz de permissões por papel (função). Cada módulo recebe:
     *   'total' = ver + criar/editar/excluir | 'ver' = só leitura | ausente = sem acesso.
     * '*' = acesso a tudo. Papéis: superadmin/admin (donos), gerente, financeiro,
     * recepcionista, tecnico. Módulos não listados aqui (dashboard, perfil próprio)
     * ficam liberados por padrão (ver moduloDoUri / AuthMiddleware).
     */
    public const MATRIZ = [
        'superadmin'    => '*',
        'admin'         => '*',
        'gerente'       => ['clientes'=>'total','os'=>'total','agenda'=>'total','crm'=>'total','estoque'=>'total','financeiro'=>'total','relatorios'=>'total','marketplace'=>'total','pdv'=>'total','config'=>'total'],
        'financeiro'    => ['clientes'=>'ver','os'=>'ver','estoque'=>'ver','financeiro'=>'total','relatorios'=>'total','pdv'=>'ver'],
        'recepcionista' => ['clientes'=>'total','os'=>'total','agenda'=>'total','crm'=>'total','estoque'=>'ver','marketplace'=>'total','pdv'=>'total'],
        'tecnico'       => ['clientes'=>'ver','os'=>'total','agenda'=>'total','estoque'=>'ver'],
    ];

    public static function can(string $modulo, string $acao = 'ver'): bool
    {
        $perfil = self::perfil();
        // Sessão sem perfil (login antigo) ou papel desconhecido: não trancar (fail-open).
        if ($perfil === '') return true;
        $regras = self::MATRIZ[$perfil] ?? null;
        if ($regras === null) return true;
        if ($regras === '*')  return true;

        $nivel = $regras[$modulo] ?? null;
        if ($nivel === null)    return false;   // módulo sem acesso p/ este papel
        if ($nivel === 'total') return true;
        return $acao === 'ver';                 // nível 'ver' = só leitura
    }

    /**
     * Descobre a que módulo uma URL pertence (pra checagem de permissão).
     * Ordem importa: prefixos mais específicos primeiro (ex: /os/status antes de /os).
     * Retorna null para rotas livres (dashboard, perfil próprio, etc.).
     */
    public static function moduloDoUri(string $uri): ?string
    {
        $uri = '/' . trim((string) parse_url($uri, PHP_URL_PATH), '/');
        $map = [
            '/usuarios'      => 'usuarios',
            '/pdv'           => 'pdv',
            '/tecnicos'      => 'config',
            '/os/status'     => 'config',
            '/configuracoes' => 'config',
            '/empresa'       => 'config',
            '/relatorios'    => 'relatorios',
            '/financeiro'    => 'financeiro',
            '/comissoes'     => 'financeiro',
            '/crm'           => 'crm',
            '/agenda'        => 'agenda',
            '/produtos'      => 'estoque',
            '/estoque'       => 'estoque',
            '/marketplace'   => 'marketplace',
            '/clientes'      => 'clientes',
            '/os'            => 'os',
        ];
        foreach ($map as $prefixo => $modulo) {
            if ($uri === $prefixo || str_starts_with($uri, $prefixo . '/')) return $modulo;
        }
        return null;
    }

    public static function login(array $usuario, array $permissoes = []): void
    {
        session_regenerate_id(true);
        $_SESSION['usuario_id']  = $usuario['id'];
        $_SESSION['empresa_id']  = $usuario['empresa_id'];
        $_SESSION['usuario']     = $usuario;
        $_SESSION['permissoes']  = $permissoes;

        // Sessão única: cada login gera um token novo e derruba qualquer sessão anterior
        // dessa mesma conta (ver sessaoValida(), chamada pelo AuthMiddleware a cada request).
        $token = bin2hex(random_bytes(32));
        $_SESSION['sessao_token'] = $token;
        try {
            DB::pdo()->prepare("UPDATE usuarios SET sessao_token = ? WHERE id = ?")->execute([$token, $usuario['id']]);
        } catch (\Throwable $e) {
            // Coluna ainda não migrada nesse ambiente: não impede o login.
        }

        // Carregar idioma e tipo de conta da empresa
        try {
            $stmt = DB::pdo()->prepare("SELECT idioma, tipo_conta FROM empresas WHERE id = ? LIMIT 1");
            $stmt->execute([$usuario['empresa_id']]);
            $emp = $stmt->fetch() ?: [];
            $_SESSION['usuario']['idioma'] = $emp['idioma'] ?? 'pt_BR';
            $_SESSION['tipo_conta']        = $emp['tipo_conta'] ?? 'completo';
        } catch (\Throwable $e) {
            $_SESSION['usuario']['idioma'] = 'pt_BR';
            $_SESSION['tipo_conta']        = 'completo';
        }
    }

    /**
     * Sessão única: confere se o token guardado nesta sessão ainda é o mais recente pra esse
     * usuário. Se outra pessoa logou com a mesma conta em outro dispositivo/navegador, o token
     * no banco foi sobrescrito e esta sessão passa a ser inválida. Sessões antigas (de antes
     * dessa coluna existir, sem token gravado) são toleradas até o próximo login.
     */
    public static function sessaoValida(): bool
    {
        $meu = $_SESSION['sessao_token'] ?? null;
        if ($meu === null) return true;
        try {
            $st = DB::pdo()->prepare("SELECT sessao_token FROM usuarios WHERE id = ?");
            $st->execute([self::id()]);
            $atual = $st->fetchColumn();
            return $atual !== false && $atual !== null && hash_equals((string) $atual, $meu);
        } catch (\Throwable $e) {
            return true; // erro de banco não deve derrubar sessões válidas
        }
    }

    /** Conta que só assinou o diretório (acesso restrito ao perfil público). */
    public static function soDiretorio(): bool
    {
        return ($_SESSION['tipo_conta'] ?? 'completo') === 'diretorio';
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
