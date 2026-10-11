<?php

/** @var \App\Core\Router $router */

// Fixa Indica (indica.fixaos.com.br) — arquivo de rotas isolado, carregado no lugar de
// routes/web.php quando o host começa com "indica." (ver public/index.php). Nenhuma rota
// daqui existe no FixaOS principal e vice-versa.
//
// /robots.txt e /sitemap.xml precisam ser dinâmicos aqui (não arquivos físicos, que o FixaOS
// principal já tem no mesmo DocumentRoot) — o vhost do Apache
// (/etc/httpd/conf.d/indica-fixaos.conf) já força essas duas URLs a sempre passar pelo PHP
// antes de qualquer checagem de arquivo físico, então essas rotas funcionam normalmente.

$router->get('/',                    'Indica\PublicoController@home',      []);
$router->get('/categoria/{slug}',    'Indica\PublicoController@categoria', []);
$router->get('/produto/{slug}',      'Indica\PublicoController@produto',   []);
$router->get('/guias',               'Indica\PublicoController@guias',     []);
$router->get('/guias/{slug}',        'Indica\PublicoController@guia',      []);
$router->get('/busca',               'Indica\PublicoController@busca',     []);
$router->get('/ir/{slug}',           'Indica\RedirectController@ir',       []);
$router->get('/sitemap.xml',         'Indica\SitemapController@xml',       []);
$router->get('/robots.txt',          'Indica\PublicoController@robots',    []);

// Painel admin (CRUD de produtos/categorias/guias, atrás de MasterMiddleware — mesma conta
// master já usada em /master/* no FixaOS principal) é a Fase 2 do plano, ainda não
// implementada nesta rodada — rotas entram aqui quando os controllers existirem.
