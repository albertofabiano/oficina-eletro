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

// Painel admin — mesma CONTA de master_admins já usada em /master/* no FixaOS principal, mas
// sessão própria por domínio (ver App\Middleware\Indica\MasterMiddleware). Login/logout ficam
// de fora do guard (senão ninguém conseguiria logar); o resto fica atrás de
// Indica\MasterMiddleware.
$router->get('/admin/login',  'Indica\AdminAuthController@loginForm', ['Indica\MasterGuestMiddleware']);
$router->post('/admin/login', 'Indica\AdminAuthController@login',     ['Indica\MasterGuestMiddleware']);
$router->get('/admin/logout', 'Indica\AdminAuthController@logout',    []);

$router->get('/admin',  'Indica\AdminController@dashboard', ['Indica\MasterMiddleware']);

$router->get('/admin/categorias',                'Indica\AdminController@categorias',        ['Indica\MasterMiddleware']);
$router->post('/admin/categorias',               'Indica\AdminController@categoriaSalvar',    ['Indica\MasterMiddleware']);
$router->post('/admin/categorias/{id}/atualizar','Indica\AdminController@categoriaAtualizar', ['Indica\MasterMiddleware']);
$router->post('/admin/categorias/{id}/excluir',  'Indica\AdminController@categoriaExcluir',   ['Indica\MasterMiddleware']);

$router->get('/admin/produtos',                'Indica\AdminController@produtos',        ['Indica\MasterMiddleware']);
$router->get('/admin/produtos/novo',           'Indica\AdminController@produtoForm',     ['Indica\MasterMiddleware']);
$router->get('/admin/produtos/{id}/editar',    'Indica\AdminController@produtoForm',     ['Indica\MasterMiddleware']);
$router->post('/admin/produtos',               'Indica\AdminController@produtoSalvar',   ['Indica\MasterMiddleware']);
$router->post('/admin/produtos/{id}/atualizar','Indica\AdminController@produtoAtualizar',['Indica\MasterMiddleware']);
$router->post('/admin/produtos/{id}/excluir',  'Indica\AdminController@produtoExcluir',  ['Indica\MasterMiddleware']);

$router->get('/admin/guias',                'Indica\AdminController@guias',        ['Indica\MasterMiddleware']);
$router->get('/admin/guias/novo',           'Indica\AdminController@guiaForm',     ['Indica\MasterMiddleware']);
$router->get('/admin/guias/{id}/editar',    'Indica\AdminController@guiaForm',     ['Indica\MasterMiddleware']);
$router->post('/admin/guias',               'Indica\AdminController@guiaSalvar',   ['Indica\MasterMiddleware']);
$router->post('/admin/guias/{id}/atualizar','Indica\AdminController@guiaAtualizar',['Indica\MasterMiddleware']);
$router->post('/admin/guias/{id}/excluir',  'Indica\AdminController@guiaExcluir',  ['Indica\MasterMiddleware']);

$router->get('/admin/config',  'Indica\AdminController@config',      ['Indica\MasterMiddleware']);
$router->post('/admin/config', 'Indica\AdminController@configSalvar',['Indica\MasterMiddleware']);
