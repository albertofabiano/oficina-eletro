<?php

namespace App\Controllers\Indica;

use App\Core\Controller;
use App\Services\Indica\MercadoLivreService;

/**
 * Fluxo OAuth2 com o Mercado Livre (conectar/callback/desconectar) + o endpoint AJAX que o
 * cadastro de produto chama pro botão "Buscar dados do anúncio". Separado de AdminController
 * (que já cuida de categorias/produtos/guias/config) por ser um assunto bem diferente — só
 * autenticação e consulta de item, sem CRUD próprio.
 */
class AdminMercadoLivreController extends Controller
{
    public function conectar(): void
    {
        if (!MercadoLivreService::configurado()) {
            $this->flash('error', 'Config do Mercado Livre não preenchida ainda — veja config/mercadolivre.example.php no servidor.');
            $this->redirect(indica_url('/admin/config'));
            return;
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['ml_oauth_state'] = $state;

        $this->redirect(MercadoLivreService::authUrl($state));
    }

    public function callback(): void
    {
        $code  = (string) $this->get('code', '');
        $state = (string) $this->get('state', '');
        $stateEsperado = $_SESSION['ml_oauth_state'] ?? '';
        unset($_SESSION['ml_oauth_state']);

        if ($code === '' || $state === '' || !hash_equals($stateEsperado, $state)) {
            $this->flash('error', 'Autorização do Mercado Livre inválida ou expirada — tente conectar de novo.');
            $this->redirect(indica_url('/admin/config'));
            return;
        }

        if (!MercadoLivreService::trocarCodigoPorToken($code)) {
            $this->flash('error', 'Não foi possível concluir a conexão com o Mercado Livre. Confira o client_id/client_secret em config/mercadolivre.php.');
            $this->redirect(indica_url('/admin/config'));
            return;
        }

        $this->flash('success', 'Conectado ao Mercado Livre com sucesso.');
        $this->redirect(indica_url('/admin/config'));
    }

    public function desconectar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(indica_url('/admin/config')); return; }

        MercadoLivreService::desconectar();
        $this->flash('success', 'Desconectado do Mercado Livre.');
        $this->redirect(indica_url('/admin/config'));
    }

    /** GET /admin/produtos/buscar-ml?url=... — AJAX, chamado pelo botão no form de produto. */
    public function buscarMl(): void
    {
        $url = trim((string) $this->get('url', ''));
        if ($url === '') {
            $this->json(['erro' => 'Cole o link do anúncio primeiro.'], 400);
            return;
        }

        if (!MercadoLivreService::conectado()) {
            $this->json(['erro' => 'Conecte o Mercado Livre em Configurações antes de usar a busca automática.'], 400);
            return;
        }

        $item = MercadoLivreService::buscarItem($url);
        if (!$item) {
            $this->json(['erro' => 'Não consegui ler esse anúncio. Confira se o link é o do anúncio original (não o link encurtado de afiliado) e se ele ainda está ativo.'], 404);
            return;
        }

        $this->json($item);
    }
}
