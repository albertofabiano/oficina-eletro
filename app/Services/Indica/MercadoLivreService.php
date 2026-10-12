<?php

namespace App\Services\Indica;

use App\Core\DB;
use PDO;

/**
 * Integração com a API pública do Mercado Livre (OAuth2 + leitura de anúncio) — Fase 3 do
 * Fixa Indica. Usada em dois pontos: botão "Buscar dados do anúncio" no cadastro de produto
 * (Indica\AdminController) e a rotina diária de atualização de preço
 * (scripts/atualizar_precos_indica.php).
 *
 * Credenciais em config/mercadolivre.php (gitignored, ver mercadolivre.example.php pro passo
 * a passo de criar o app). Token (access/refresh) fica salvo em indica_config — mesma tabela
 * chave/valor já usada pro resto da config do Fixa Indica, sem precisar de tabela nova.
 */
class MercadoLivreService
{
    private const OAUTH_BASE = 'https://auth.mercadolivre.com.br/authorization';
    private const API_BASE   = 'https://api.mercadolibre.com';

    public static function config(): array
    {
        $path = BASE_PATH . '/config/mercadolivre.php';
        if (!file_exists($path)) return ['app_id' => '', 'client_secret' => ''];
        return require $path;
    }

    public static function configurado(): bool
    {
        $c = self::config();
        return !empty($c['app_id']) && !empty($c['client_secret']);
    }

    public static function conectado(): bool
    {
        return (bool) CatalogoService::config('ml_refresh_token');
    }

    /** Pra exibir na tela de config — não é dado sensível (não é o token em si). */
    public static function statusConexao(): array
    {
        return [
            'conectado'    => self::conectado(),
            'user_id'      => CatalogoService::config('ml_user_id'),
            'conectado_em' => CatalogoService::config('ml_conectado_em'),
        ];
    }

    private static function redirectUri(): string
    {
        return indica_url('/admin/mercadolivre/callback');
    }

    /** Monta a URL de autorização — $state é validado de volta no callback (CSRF do fluxo OAuth). */
    public static function authUrl(string $state): string
    {
        $c = self::config();
        $qs = http_build_query([
            'response_type' => 'code',
            'client_id'     => $c['app_id'],
            'redirect_uri'  => self::redirectUri(),
            'state'         => $state,
            'scope'         => 'offline_access read',
        ]);
        return self::OAUTH_BASE . '?' . $qs;
    }

    /** Troca o code (vindo do callback) por access_token + refresh_token, salva em indica_config. */
    public static function trocarCodigoPorToken(string $code): bool
    {
        $c = self::config();
        $resp = self::post('/oauth/token', [
            'grant_type'    => 'authorization_code',
            'client_id'     => $c['app_id'],
            'client_secret' => $c['client_secret'],
            'code'          => $code,
            'redirect_uri'  => self::redirectUri(),
        ]);
        if (!$resp || empty($resp['access_token'])) return false;

        self::salvarToken($resp);
        return true;
    }

    private static function salvarToken(array $resp): void
    {
        $expiraEm = date('Y-m-d H:i:s', time() + (int) ($resp['expires_in'] ?? 10800) - 60);
        $valores = [
            'ml_access_token'  => $resp['access_token'],
            'ml_refresh_token' => $resp['refresh_token'] ?? CatalogoService::config('ml_refresh_token', ''),
            'ml_token_expira_em' => $expiraEm,
            'ml_user_id'       => (string) ($resp['user_id'] ?? CatalogoService::config('ml_user_id', '')),
            'ml_conectado_em'  => CatalogoService::config('ml_conectado_em') ?: date('Y-m-d H:i:s'),
        ];
        $db = DB::pdo();
        $stmt = $db->prepare(
            "INSERT INTO indica_config (chave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)"
        );
        foreach ($valores as $chave => $valor) {
            $stmt->execute([$chave, $valor]);
        }
    }

    public static function desconectar(): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("DELETE FROM indica_config WHERE chave IN (?, ?, ?, ?, ?)");
        $stmt->execute(['ml_access_token', 'ml_refresh_token', 'ml_token_expira_em', 'ml_user_id', 'ml_conectado_em']);
    }

    /** Access token válido, renovando via refresh_token se já tiver expirado. Null se nunca conectou. */
    public static function tokenValido(): ?string
    {
        $refreshToken = CatalogoService::config('ml_refresh_token');
        if (!$refreshToken) return null;

        $expiraEm = CatalogoService::config('ml_token_expira_em');
        $accessToken = CatalogoService::config('ml_access_token');
        if ($accessToken && $expiraEm && strtotime($expiraEm) > time()) {
            return $accessToken;
        }

        $c = self::config();
        $resp = self::post('/oauth/token', [
            'grant_type'    => 'refresh_token',
            'client_id'     => $c['app_id'],
            'client_secret' => $c['client_secret'],
            'refresh_token' => $refreshToken,
        ]);
        if (!$resp || empty($resp['access_token'])) {
            error_log('[MercadoLivreService] falha ao renovar token — reconexão manual necessária em /admin/config');
            return null;
        }

        self::salvarToken($resp);
        return $resp['access_token'];
    }

    /** MLB-1234567890-titulo / MLB1234567890 / produto.mercadolivre.com.br/MLB-1234567890-... */
    public static function extrairItemId(string $urlOuId): ?string
    {
        if (preg_match('/MLB-?(\d{8,12})/i', $urlOuId, $m)) {
            return 'MLB' . $m[1];
        }
        return null;
    }

    /**
     * Busca um anúncio pelo link/id colado no cadastro de produto. Retorna null em qualquer
     * falha (link inválido, item não encontrado, sem token, erro de rede) — quem chama decide
     * a mensagem de erro, o serviço só não quebra o fluxo.
     */
    public static function buscarItem(string $urlOuId): ?array
    {
        $itemId = self::extrairItemId($urlOuId);
        if (!$itemId) return null;

        $token = self::tokenValido();
        if (!$token) return null;

        $item = self::get('/items/' . $itemId, $token, $codigo);
        if ((!$item || empty($item['id'])) && $codigo === 404) {
            // 404 em /items pode ser um ID de PRODUTO DE CATÁLOGO, não de anúncio — acontece
            // quando a URL colada é do tipo ".../p/MLB..." (página com vários vendedores
            // concorrendo pelo mesmo produto, comum em loja oficial de marca grande), que usa
            // um namespace de id diferente do item individual. Resolve pro anúncio vencedor
            // (buy box) via busca pública por catalog_product_id, e só então refaz a MESMA
            // consulta de /items de sempre — reaproveita 100% do parsing abaixo, sem duplicar.
            $itemResolvido = self::resolverItemDeCatalogo($itemId, $token);
            $item = $itemResolvido ? self::get('/items/' . $itemResolvido, $token) : null;
        }
        if (!$item || empty($item['id'])) return null;

        $imagem = null;
        if (!empty($item['pictures'][0]['secure_url'])) {
            $imagem = $item['pictures'][0]['secure_url'];
        } elseif (!empty($item['thumbnail'])) {
            $imagem = $item['thumbnail'];
        }

        return [
            'titulo'      => (string) ($item['title'] ?? ''),
            'preco'       => isset($item['price']) ? (float) $item['price'] : null,
            'disponivel'  => ($item['status'] ?? '') === 'active' && (int) ($item['available_quantity'] ?? 0) > 0,
            'imagem_url'  => $imagem,
            'permalink'   => $item['permalink'] ?? null,
        ];
    }

    /** ID de PRODUTO de catálogo (não de anúncio) → resolve pro anúncio vencedor (buy box) via
     *  busca pública por catalog_product_id. Null se não achar nenhum concorrendo por esse
     *  produto (produto saiu de linha, catálogo sem oferta ativa etc.). */
    private static function resolverItemDeCatalogo(string $catalogId, string $token): ?string
    {
        $resp = self::get('/sites/MLB/search?catalog_product_id=' . urlencode($catalogId) . '&limit=1', $token);
        $primeiroId = $resp['results'][0]['id'] ?? null;
        return is_string($primeiroId) ? $primeiroId : null;
    }

    // ── HTTP ─────────────────────────────────────────────────────────────

    private static function post(string $path, array $body): ?array
    {
        $ch = curl_init(self::API_BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded', 'Accept: application/json'],
            CURLOPT_POSTFIELDS     => http_build_query($body),
            CURLOPT_TIMEOUT        => 15,
        ]);
        return self::executar($ch, $path, $body);
    }

    /** $codigoOut (por referência, opcional) devolve o HTTP status da resposta — usado por
     *  buscarItem() pra distinguir "não encontrado" (404, pode ser id de catálogo) de qualquer
     *  outra falha, sem precisar de uma segunda chamada HTTP só pra saber o status. */
    private static function get(string $path, string $token, ?int &$codigoOut = null): ?array
    {
        $ch = curl_init(self::API_BASE . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $token, 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 15,
        ]);
        return self::executar($ch, $path, [], $codigoOut);
    }

    private static function executar($ch, string $path, array $body, ?int &$codigoOut = null): ?array
    {
        $res = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $erroCurl = curl_error($ch);
        curl_close($ch);
        $codigoOut = $code;

        if ($code >= 200 && $code < 300) {
            $j = json_decode((string) $res, true);
            return is_array($j) ? $j : null;
        }

        // Nunca loga client_secret/code/token — só o path e o corpo sem as chaves sensíveis.
        $bodySeguro = $body;
        unset($bodySeguro['client_secret'], $bodySeguro['code'], $bodySeguro['refresh_token']);
        error_log(sprintf(
            '[MercadoLivreService] falha em %s — http=%s curl_error="%s" resp=%s body=%s',
            $path, $code, $erroCurl, substr((string) $res, 0, 500), json_encode($bodySeguro, JSON_UNESCAPED_UNICODE)
        ));
        return null;
    }
}
