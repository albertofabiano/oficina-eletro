<?php

/** @var \App\Core\Router $router */

// ── MASTER ADMIN ──────────────────────────────────────────
$router->get('/master/login',              'MasterController@loginForm',    ['MasterGuestMiddleware']);
$router->post('/master/login',             'MasterController@login',        ['MasterGuestMiddleware']);
$router->get('/master/logout',             'MasterController@logout',       []);
$router->get('/master',                    'MasterController@dashboard',    ['MasterMiddleware']);
$router->get('/master/empresas',           'MasterController@empresas',     ['MasterMiddleware']);
$router->get('/master/empresas/{id}',      'MasterController@verEmpresa',   ['MasterMiddleware']);
$router->post('/master/empresas/{id}',     'MasterController@salvarEmpresa',['MasterMiddleware']);
$router->post('/master/empresas/{id}/toggle', 'MasterController@toggleEmpresa',['MasterMiddleware']);
$router->get('/master/usuarios',           'MasterController@usuarios',     ['MasterMiddleware']);
$router->get('/master/admins',             'MasterController@admins',       ['MasterMiddleware']);
$router->post('/master/admins',            'MasterController@salvarAdmin',  ['MasterMiddleware']);
$router->delete('/master/admins/{id}',     'MasterController@excluirAdmin', ['MasterMiddleware']);
// ──────────────────────────────────────────────────────────

// Serve arquivos de upload (logos)
$router->get('/uploads/{file}', 'UploadController@serve', []);

// Landing / Cadastro público
$router->get('/',           'LandingController@index',    []);
$router->get('/cadastrar',  'LandingController@cadastro', ['GuestMiddleware']);
$router->post('/cadastrar', 'LandingController@registrar',['GuestMiddleware']);

// Onboarding (setup pós-cadastro)
$router->get('/setup',  'SetupController@onboarding', ['AuthMiddleware']);
$router->post('/setup', 'SetupController@salvar',      ['AuthMiddleware']);

// Auth
$router->get('/login',  'AuthController@loginForm',  ['GuestMiddleware']);
$router->post('/login', 'AuthController@login',      ['GuestMiddleware']);
$router->get('/logout', 'AuthController@logout');

// Dashboard
$router->get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);

// Clientes
$router->get('/clientes',              'ClienteController@index',      ['AuthMiddleware']);
$router->get('/clientes/novo',         'ClienteController@criar',      ['AuthMiddleware']);
$router->post('/clientes',             'ClienteController@salvar',     ['AuthMiddleware']);
$router->get('/clientes/{id}',         'ClienteController@ver',        ['AuthMiddleware']);
$router->get('/clientes/{id}/editar',  'ClienteController@editar',     ['AuthMiddleware']);
$router->post('/clientes/{id}',        'ClienteController@atualizar',  ['AuthMiddleware']);
$router->delete('/clientes/{id}',      'ClienteController@excluir',    ['AuthMiddleware']);
$router->get('/api/clientes',          'ClienteController@buscarAjax', ['AuthMiddleware']);
$router->post('/api/clientes',         'ClienteController@salvarAjax', ['AuthMiddleware']);

// Status de OS
$router->get('/os/status',            'OsStatusController@index',     ['AuthMiddleware']);
$router->post('/os/status',           'OsStatusController@salvar',    ['AuthMiddleware']);
$router->post('/os/status/reordenar', 'OsStatusController@reordenar', ['AuthMiddleware']);
$router->delete('/os/status/{id}',    'OsStatusController@excluir',   ['AuthMiddleware']);

// Ordens de Serviço
$router->get('/os',                              'OrdemServicoController@index',          ['AuthMiddleware']);
$router->get('/os/nova',                         'OrdemServicoController@criar',          ['AuthMiddleware']);
$router->post('/os',                             'OrdemServicoController@salvar',         ['AuthMiddleware']);
$router->get('/os/{id}',                         'OrdemServicoController@ver',            ['AuthMiddleware']);
$router->get('/os/{id}/editar',                  'OrdemServicoController@editar',         ['AuthMiddleware']);
$router->post('/os/{id}/editar',                 'OrdemServicoController@atualizar',      ['AuthMiddleware']);
$router->get('/os/{id}/imprimir',                'OrdemServicoController@imprimir',            ['AuthMiddleware']);
$router->get('/os/{id}/imprimir/fechamento',     'OrdemServicoController@imprimirFechamento',  ['AuthMiddleware']);
$router->get('/os/{id}/imprimir/garantia',       'OrdemServicoController@imprimirGarantia',    ['AuthMiddleware']);
$router->post('/os/{id}/fechar',                 'OrdemServicoController@fechar',              ['AuthMiddleware']);
$router->get('/api/os/em-garantia',              'OrdemServicoController@buscarEmGarantia',   ['AuthMiddleware']);
$router->post('/os/{id}/garantia',               'OrdemServicoController@abrirGarantia',      ['AuthMiddleware']);
$router->post('/os/{id}/status',                 'OrdemServicoController@atualizarStatus',['AuthMiddleware']);
$router->post('/os/{id}/servicos',               'OrdemServicoController@adicionarServico',['AuthMiddleware']);
$router->post('/os/{id}/pecas',                  'OrdemServicoController@adicionarPeca',  ['AuthMiddleware']);
$router->delete('/os/{id}/servicos/{itemId}',    'OrdemServicoController@removerServico', ['AuthMiddleware']);
$router->delete('/os/{id}/pecas/{itemId}',       'OrdemServicoController@removerPeca',    ['AuthMiddleware']);

// Auxiliares de produto (estados, tipos, marcas)
$router->get('/api/produto/{tipo}',          'ProdutoAuxController@listar',  ['AuthMiddleware']);
$router->post('/api/produto/{tipo}',         'ProdutoAuxController@salvar',  ['AuthMiddleware']);
$router->delete('/api/produto/{tipo}/{id}',  'ProdutoAuxController@excluir', ['AuthMiddleware']);

// Produtos / Estoque
$router->get('/produtos',             'ProdutoController@index',      ['AuthMiddleware']);
$router->get('/produtos/novo',        'ProdutoController@criar',      ['AuthMiddleware']);
$router->post('/produtos',            'ProdutoController@salvar',     ['AuthMiddleware']);
$router->get('/produtos/{id}/editar', 'ProdutoController@editar',     ['AuthMiddleware']);
$router->post('/produtos/{id}',       'ProdutoController@atualizar',  ['AuthMiddleware']);
$router->get('/api/produtos',         'ProdutoController@buscarAjax', ['AuthMiddleware']);

// CRM
$router->get('/crm',                 'CrmController@pipeline',         ['AuthMiddleware']);
$router->post('/crm/contatos',       'CrmController@registrarContato', ['AuthMiddleware']);

// Financeiro
$router->get('/financeiro',             'FinanceiroController@index',   ['AuthMiddleware']);
$router->post('/financeiro',            'FinanceiroController@salvar',  ['AuthMiddleware']);
$router->post('/financeiro/{id}/pagar',    'FinanceiroController@pagar',    ['AuthMiddleware']);
$router->post('/financeiro/os/{id}/pagar', 'FinanceiroController@pagarOs',  ['AuthMiddleware']);
$router->delete('/financeiro/{id}',     'FinanceiroController@excluir', ['AuthMiddleware']);

// Agenda
$router->get('/agenda',         'AgendaController@index',   ['AuthMiddleware']);
$router->post('/agenda',        'AgendaController@salvar',  ['AuthMiddleware']);
$router->delete('/agenda/{id}', 'AgendaController@excluir', ['AuthMiddleware']);
$router->get('/api/agenda',     'AgendaController@eventos', ['AuthMiddleware']);

// Fornecedores
$router->get('/fornecedores',             'FornecedorController@index',    ['AuthMiddleware']);
$router->get('/fornecedores/novo',        'FornecedorController@criar',    ['AuthMiddleware']);
$router->post('/fornecedores',            'FornecedorController@salvar',   ['AuthMiddleware']);
$router->get('/fornecedores/{id}/editar', 'FornecedorController@editar',   ['AuthMiddleware']);
$router->post('/fornecedores/{id}',       'FornecedorController@atualizar',['AuthMiddleware']);
$router->delete('/fornecedores/{id}',     'FornecedorController@excluir',  ['AuthMiddleware']);

// Relatórios
$router->get('/relatorios', 'RelatorioController@index', ['AuthMiddleware']);

// Técnicos
$router->get('/tecnicos',              'TecnicoController@index',    ['AuthMiddleware']);
$router->get('/tecnicos/novo',         'TecnicoController@criar',    ['AuthMiddleware']);
$router->post('/tecnicos',             'TecnicoController@salvar',   ['AuthMiddleware']);
$router->get('/tecnicos/{id}',         'TecnicoController@ver',      ['AuthMiddleware']);
$router->get('/tecnicos/{id}/editar',  'TecnicoController@editar',   ['AuthMiddleware']);
$router->post('/tecnicos/{id}/editar', 'TecnicoController@atualizar',['AuthMiddleware']);
$router->delete('/tecnicos/{id}',      'TecnicoController@excluir',  ['AuthMiddleware']);

// Usuários
$router->get('/usuarios',             'UsuarioController@index',    ['AuthMiddleware']);
$router->get('/usuarios/novo',        'UsuarioController@criar',    ['AuthMiddleware']);
$router->post('/usuarios',            'UsuarioController@salvar',   ['AuthMiddleware']);
$router->get('/usuarios/{id}/editar', 'UsuarioController@editar',   ['AuthMiddleware']);
$router->post('/usuarios/{id}',       'UsuarioController@atualizar',['AuthMiddleware']);
$router->delete('/usuarios/{id}',     'UsuarioController@excluir',  ['AuthMiddleware']);

// Empresa
$router->get('/empresa',            'EmpresaController@index',       ['AuthMiddleware']);
$router->post('/empresa',           'EmpresaController@salvar',      ['AuthMiddleware']);
$router->post('/empresa/logo/remover', 'EmpresaController@removerLogo', ['AuthMiddleware']);
