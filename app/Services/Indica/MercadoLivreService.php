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
        if ($item && !empty($item['id'])) {
            return self::formatarItem($item, $token);
        }
        if ($codigo !== 404) return null;

        // 404 em /items pode ser um ID de PRODUTO DE CATÁLOGO, não de anúncio — acontece quando
        // a URL colada é do tipo ".../p/MLB..." (página com vários vendedores concorrendo pelo
        // mesmo produto, comum em loja oficial de marca grande), que usa um namespace de id
        // diferente do item individual.
        return self::buscarProdutoCatalogo($itemId, $token);
    }

    /** @param array<string,mixed> $item resposta crua de GET /items/{id} */
    private static function formatarItem(array $item, string $token): array
    {
        $imagens = self::extrairImagens($item['pictures'] ?? [], 'secure_url');
        if (!$imagens && !empty($item['thumbnail'])) {
            $imagens = [(string) $item['thumbnail']];
        }
        $imagem = $imagens[0] ?? null;
        $imagensExtra = array_slice($imagens, 1);

        $titulo = (string) ($item['title'] ?? '');
        // Endpoint separado (GET /items/{id}/description) — o /items/{id} em si nunca traz
        // o texto da descrição. Falha aqui não derruba o resto (item já foi achado).
        $descricao = self::buscarDescricaoItem((string) $item['id'], $token);

        return [
            'titulo'              => $titulo,
            'preco'               => isset($item['price']) ? (float) $item['price'] : null,
            'disponivel'          => ($item['status'] ?? '') === 'active' && (int) ($item['available_quantity'] ?? 0) > 0,
            'imagem_url'          => $imagem,
            'imagens_extra'       => $imagensExtra,
            'permalink'           => $item['permalink'] ?? null,
            'descricao'           => $descricao,
            'especificacao_curta' => null,
            'parcial'             => false,
        ] + self::metaSeo($titulo, null, $descricao);
    }

    /**
     * Meta title/description sugeridos a partir do que a API devolveu — nunca de categoria/
     * preço, que o FixaIndica não tem como adivinhar (categoria é sua, criada à mão; preço
     * muda e o visitante já vê o valor real na própria página). $especCurta tem prioridade
     * sobre $descricao pro meta_description por já vir compacto (≤200 chars); descrição
     * completa de catálogo costuma ter várias linhas/parágrafos, não serve pra 160 chars sem
     * cortar no meio de uma frase de forma estranha.
     */
    private static function metaSeo(string $titulo, ?string $especCurta, ?string $descricao): array
    {
        $metaTitle = $titulo !== '' ? mb_substr($titulo, 0, 70) : null;

        $baseDesc = $especCurta ?: $descricao;
        $metaDescription = null;
        if ($baseDesc) {
            // Normaliza quebra de linha/espaço duplo antes de cortar — texto de descrição de
            // catálogo vem com \r\n entre parágrafos, viraria lixo visível na meta tag.
            $limpo = trim(preg_replace('/\s+/u', ' ', $baseDesc));
            if ($limpo !== '') {
                $metaDescription = mb_substr($limpo, 0, 160);
            }
        }

        return ['meta_title' => $metaTitle, 'meta_description' => $metaDescription];
    }

    /**
     * Lista de URLs de foto a partir do array `pictures` cru da API — `/items` usa a chave
     * `secure_url`, `/products` usa `url` (confirmado contra a API real, ver teste manual com
     * MLB75994421), por isso $chave é parâmetro. Limita a 8 fotos no total (capa + até 7 extra)
     * — o bastante pra um carrossel de verdade sem inflar a página com dezenas de imagens que a
     * maioria dos anúncios do ML nem tem.
     */
    private static function extrairImagens(array $pictures, string $chave, int $max = 8): array
    {
        $urls = [];
        foreach ($pictures as $p) {
            $url = trim((string) ($p[$chave] ?? ''));
            if ($url !== '' && !in_array($url, $urls, true)) {
                $urls[] = $url;
            }
            if (count($urls) >= $max) break;
        }
        return $urls;
    }

    /** Texto plano da descrição do anúncio — null se o endpoint falhar (não crítico). */
    private static function buscarDescricaoItem(string $itemId, string $token): ?string
    {
        $desc = self::get('/items/' . $itemId . '/description', $token);
        if (!$desc) return null;
        $texto = $desc['plain_text'] ?? $desc['text'] ?? null;
        return $texto !== null && trim((string) $texto) !== '' ? trim((string) $texto) : null;
    }

    /**
     * ID de PRODUTO de catálogo, não de anúncio — não existe no endpoint /items (namespace
     * diferente). Testado contra a API real: a busca pública por catalog_product_id
     * (/sites/MLB/search) está bloqueada pro Mercado Livre pra apps de terceiro (confirmado:
     * HTTP 403 "forbidden" — eles fecharam esse endpoint por causa de scraping). O endpoint de
     * produto em si (/products/{id}) FUNCIONA e dá nome/foto reais, mas não traz preço nem
     * disponibilidade de vendedor nenhum quando o catálogo não tem um vencedor de buy-box
     * definido no momento (buy_box_winner vem null — confirmado com um produto real de loja
     * oficial). Preenchimento parcial: nome e foto reais preenchidos, preço/disponibilidade
     * ficam em branco pro usuário completar olhando o próprio anúncio — melhor que falhar tudo.
     *
     * `short_description.content` e `main_features` (confirmados contra a API real, ver
     * teste manual com MLB75994421) dão descrição completa e especificação curta de verdade
     * pra esse tipo de produto — bem melhores que os de /items, que nem têm esses campos.
     */
    private static function buscarProdutoCatalogo(string $catalogId, string $token): ?array
    {
        $produto = self::get('/products/' . $catalogId, $token, $codigo);
        if (!$produto || empty($produto['id'])) return null;

        // buy_box_winner, quando preenchido, é de onde viria preço/disponibilidade — defensivo,
        // já que no teste real ele veio sempre null (catálogo sem vencedor definido); se algum
        // dia vier populado pra outro produto, aproveita sem precisar de mudança de código.
        $vencedor = $produto['buy_box_winner'] ?? null;
        $preco = (is_array($vencedor) && isset($vencedor['price'])) ? (float) $vencedor['price'] : null;

        $descricao = null;
        if (!empty($produto['short_description']['content'])) {
            $descricao = trim((string) $produto['short_description']['content']);
        }

        // especificacao_curta é VARCHAR(200) no banco — trunca aqui, não deixa o INSERT quebrar
        // (mesma categoria do bug real já corrigido em link_ml: nunca confiar que o campo de
        // origem já respeita o limite do campo de destino).
        $especCurta = null;
        if (!empty($produto['main_features']) && is_array($produto['main_features'])) {
            $textos = array_filter(array_map(
                fn ($f) => trim((string) ($f['text'] ?? '')),
                $produto['main_features']
            ));
            if ($textos) {
                $especCurta = mb_substr(implode(' · ', $textos), 0, 200);
            }
        }

        $titulo = (string) ($produto['name'] ?? '');
        $imagens = self::extrairImagens($produto['pictures'] ?? [], 'url');

        return [
            'titulo'              => $titulo,
            'preco'               => $preco,
            'disponivel'          => ($produto['status'] ?? '') === 'active',
            'imagem_url'          => $imagens[0] ?? null,
            'imagens_extra'       => array_slice($imagens, 1),
            'permalink'           => null,
            'descricao'           => $descricao,
            'especificacao_curta' => $especCurta,
            'parcial'             => true,
        ] + self::metaSeo($titulo, $especCurta, $descricao);
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
