<?php

namespace App\Controllers\Indica;

use App\Core\Controller;
use App\Services\Indica\CatalogoService;

/**
 * Site público do Fixa Indica (indica.fixaos.com.br). Sem AuthMiddleware nenhum — é tudo
 * público, sem login (ver routes/indica.php). Layout próprio: layouts/indica_publico.php.
 */
class PublicoController extends Controller
{
    public function home(): void
    {
        $categorias = CatalogoService::categoriasAtivas();
        $destaque = CatalogoService::produtosDestaque(12);
        if (count($destaque) < 8) {
            $exceto = array_map(fn ($p) => (int) $p['id'], $destaque);
            $destaque = array_merge($destaque, CatalogoService::produtosRecentes(12 - count($destaque), $exceto));
        }
        $guias = CatalogoService::guiasPublicados(4);

        $this->view('indica.home', [
            'categoriasMenu' => $categorias,
            'categorias'     => $categorias,
            'produtos'       => $destaque,
            'guias'          => $guias,
            'tituloFull'     => 'Fixa Indica — ferramentas, peças e eletrônicos indicados por técnicos',
            'metaDesc'       => 'Ferramentas, peças e eletrônicos indicados por técnicos de assistência técnica, com link direto pro Mercado Livre.',
        ], 'indica_publico');
    }

    public function categoria(string $slug): void
    {
        $categoria = CatalogoService::categoriaPorSlug($slug);
        if (!$categoria) {
            http_response_code(404);
            $this->view('indica.home', [
                'categoriasMenu' => CatalogoService::categoriasAtivas(),
                'categorias'     => CatalogoService::categoriasAtivas(),
                'produtos'       => [],
                'guias'          => [],
                'noindex'        => true,
                'tituloFull'     => 'Categoria não encontrada — Fixa Indica',
            ], 'indica_publico');
            return;
        }

        $produtos = CatalogoService::produtosPorCategoria((int) $categoria['id']);

        $this->view('indica.categoria', [
            'categoriasMenu' => CatalogoService::categoriasAtivas(),
            'categoria'      => $categoria,
            'produtos'       => $produtos,
            'tituloFull'     => $categoria['nome'] . ' — Fixa Indica',
            'metaDesc'       => $categoria['texto_seo'] ?: ('Os melhores produtos de ' . $categoria['nome'] . ' indicados por técnicos, com link direto pro Mercado Livre.'),
        ], 'indica_publico');
    }

    public function produto(string $slug): void
    {
        $produto = CatalogoService::produtoPorSlug($slug);
        if (!$produto) {
            http_response_code(404);
            $this->view('indica.home', [
                'categoriasMenu' => CatalogoService::categoriasAtivas(),
                'categorias'     => CatalogoService::categoriasAtivas(),
                'produtos'       => [],
                'guias'          => [],
                'noindex'        => true,
                'tituloFull'     => 'Produto não encontrado — Fixa Indica',
            ], 'indica_publico');
            return;
        }

        $relacionados = CatalogoService::produtosRelacionados((int) $produto['id'], $produto['categoria_id'] ? (int) $produto['categoria_id'] : null);

        $this->view('indica.produto', [
            'categoriasMenu' => CatalogoService::categoriasAtivas(),
            'produto'        => $produto,
            'relacionados'   => $relacionados,
            'banners'        => CatalogoService::bannersAtivos(),
            'tituloFull'     => $produto['meta_title'] ?: ($produto['nome'] . ' — Fixa Indica'),
            'metaDesc'       => $produto['meta_description'] ?: ($produto['especificacao_curta'] ?: ('Confira ' . $produto['nome'] . ', indicado por técnicos, com link direto pro Mercado Livre.')),
            'ogImage'        => $produto['imagem_url'] ?: null,
        ], 'indica_publico');
    }

    public function guias(): void
    {
        $guias = CatalogoService::guiasPublicados(60);

        $this->view('indica.guias', [
            'categoriasMenu' => CatalogoService::categoriasAtivas(),
            'guias'          => $guias,
            'tituloFull'     => 'Guias — problema e solução — Fixa Indica',
            'metaDesc'       => 'Guias práticos de técnicos: identifique o problema do seu aparelho e veja a ferramenta ou peça certa pra resolver.',
        ], 'indica_publico');
    }

    public function guia(string $slug): void
    {
        $guia = CatalogoService::guiaPorSlug($slug);
        if (!$guia) {
            http_response_code(404);
            $this->view('indica.guias', [
                'categoriasMenu' => CatalogoService::categoriasAtivas(),
                'guias'          => CatalogoService::guiasPublicados(60),
                'noindex'        => true,
                'tituloFull'     => 'Guia não encontrado — Fixa Indica',
            ], 'indica_publico');
            return;
        }

        $blocos = CatalogoService::blocosDoGuia((int) $guia['id']);

        $this->view('indica.guia', [
            'categoriasMenu' => CatalogoService::categoriasAtivas(),
            'guia'           => $guia,
            'blocos'         => $blocos,
            'tituloFull'     => $guia['meta_title'] ?: ($guia['titulo'] . ' — Fixa Indica'),
            'metaDesc'       => $guia['meta_description'] ?: ('Guia prático: ' . $guia['titulo']),
        ], 'indica_publico');
    }

    public function busca(): void
    {
        $q = trim((string) $this->get('q', ''));
        $produtos = $q !== '' ? CatalogoService::buscarProdutos($q) : [];

        $this->view('indica.busca', [
            'categoriasMenu' => CatalogoService::categoriasAtivas(),
            'termo'          => $q,
            'produtos'       => $produtos,
            'noindex'        => true,
            'tituloFull'     => ($q !== '' ? ('Busca: ' . $q . ' — ') : 'Busca — ') . 'Fixa Indica',
        ], 'indica_publico');
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /busca\n";
        echo "Sitemap: " . indica_url('/sitemap.xml') . "\n";
        exit;
    }
}
