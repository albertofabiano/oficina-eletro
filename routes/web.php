<?php

/** @var \App\Core\Router $router */

// ══════════════════════════════════════════════════════════════════
//  PÚBLICAS — sem autenticação
// ══════════════════════════════════════════════════════════════════

// Landing
$router->get('/',           'LandingController@index',    []);
$router->get('/privacidade', 'LandingController@privacidade', []);
$router->get('/termos',      'LandingController@termos',      []);
$router->get('/sitemap.xml', 'SitemapController@xml',         []);
$router->get('/ads.txt',     'LandingController@adsTxt',      []);
$router->get('/cadastrar',  'LandingController@cadastro', ['GuestMiddleware']);
$router->post('/cadastrar', 'LandingController@registrar',['GuestMiddleware']);
$router->post('/avise-me',  'LandingController@listaEspera', []);

// Auth
$router->get('/login',                'AuthController@loginForm',              ['GuestMiddleware']);
$router->post('/login',               'AuthController@login',                  ['GuestMiddleware']);
$router->get('/logout',               'AuthController@logout',                 []);
$router->get('/demo',                 'AuthController@demo',                   ['GuestMiddleware']);
$router->get('/demo/sair-para-cadastro', 'AuthController@sairParaCadastro',    []);
$router->get('/esqueci-senha',          'AuthController@esqueciSenha', ['GuestMiddleware']);
$router->post('/esqueci-senha',         'AuthController@enviarReset',  ['GuestMiddleware']);
$router->get('/redefinir-senha/{token}', 'AuthController@resetForm',   ['GuestMiddleware']);
$router->post('/redefinir-senha/{token}','AuthController@resetar',     ['GuestMiddleware']);
$router->get('/auth/google',          'GoogleAuthController@redirectToGoogle', ['GuestMiddleware']);
$router->get('/auth/google/callback', 'GoogleAuthController@callback',         []);

// Confirmação de e-mail — sem middleware: o link pode ser aberto logado (fluxo comum,
// pois o cadastro já loga automaticamente) ou deslogado (outro dispositivo/e-mail).
$router->get('/verificar-email/{token}',   'AuthController@verificarEmail',      []);
$router->post('/verificar-email/reenviar', 'AuthController@reenviarVerificacao', ['AuthMiddleware']);

// Acompanhamento público de OS (sem login)
$router->get('/os/acompanhar/{token}', 'OrdemServicoController@acompanhar', []);
$router->post('/os/acompanhar/{token}/avaliar', 'OrdemServicoController@avaliarOs', []);

// Pedidos de peças — marketplace
$router->get('/pecas/pedidos',                    'MarketplacePedidosController@index',    []);
$router->get('/pecas/pedidos/{id}',               'MarketplacePedidosController@ver',      []);
$router->post('/mentor/perguntar',                 'MentorController@perguntar',            ['AuthMiddleware']);
$router->get('/imagem/editor',                     'ImagemController@editor',               ['AuthMiddleware']);
$router->post('/imagem/processar',                 'ImagemController@processar',            ['AuthMiddleware']);
$router->get('/marketplace/pedidos',              'MarketplacePedidosController@meus',     ['AuthMiddleware']);
$router->post('/marketplace/pedidos',             'MarketplacePedidosController@criar',    ['AuthMiddleware']);
$router->post('/marketplace/pedidos/{id}/responder','MarketplacePedidosController@responder',['AuthMiddleware']);
$router->post('/marketplace/pedidos/{id}/atender','MarketplacePedidosController@atender', ['AuthMiddleware']);
$router->post('/marketplace/pedidos/{id}/cancelar','MarketplacePedidosController@cancelar',['AuthMiddleware']);

// Diretório público (rotas específicas ANTES das com parâmetro)
$router->get('/assistencias',                 'DiretorioController@encontrar', []);
$router->get('/assistencias/{uf}/{cidade}',   'DiretorioController@cidade', []);
$router->get('/assistencias/{slug}',          'DiretorioController@empresa', []);
$router->post('/assistencias/{slug}/avaliar', 'DiretorioController@avaliar', []);
$router->get('/encontrar',                    'DiretorioController@encontrarLegado', []);
$router->get('/api/geocode',                  'DiretorioController@geocode',  []);
$router->get('/api/diretorio/buscar',         'DiretorioController@buscarAjax', []);
$router->post('/reivindicar/{id}',            'DiretorioController@reivindicar', []);
$router->get('/diretorio/cadastrar',          'DiretorioController@cadastrarForm', []);
$router->post('/diretorio/cadastrar',         'DiretorioController@cadastrarSalvar', []);

// Marketplace público
$router->get('/pecas',        'MarketplaceController@publico', []);
$router->get('/pecas/{id}',   'MarketplaceController@peca',    []);

// Fórum público (específicas antes de /{id})
$router->get('/forum',                  'ForumController@publico',     []);
$router->get('/forum/cadastrar',        'ForumController@cadastro',    ['GuestMiddleware']);
$router->post('/forum/cadastrar',       'ForumController@registrar',   ['GuestMiddleware']);
$router->get('/forum/buscar',           'ForumController@buscar',      []);
$router->get('/forum/categoria/{id}',   'ForumController@categoriaPub',[]);
$router->get('/forum/topico/{id}',      'ForumController@topicoPub',   []);
$router->get('/forum/download/{id}',    'ForumController@download',    []);

// Ajuda pública
$router->get('/ajuda', 'AjudaController@central', []);

// Uploads (específica antes da genérica)
$router->get('/uploads/marketplace/{file}',      'UploadController@serveMarketplace',   []);
$router->get('/uploads/produtos/{file}',         'UploadController@serveProduto',       []);
$router->get('/uploads/fotos/{file}',            'UploadController@serveFoto',          []);
$router->get('/uploads/os_fotos/{eid}/{file}',   'UploadController@serveFotoEntrada',   []);
$router->get('/uploads/{file}',                  'UploadController@serve',              []);

// ══════════════════════════════════════════════════════════════════
//  MASTER ADMIN
// ══════════════════════════════════════════════════════════════════

$router->get('/master/login',  'MasterController@loginForm', ['MasterGuestMiddleware']);
$router->post('/master/login', 'MasterController@login',     ['MasterGuestMiddleware']);
$router->get('/master/logout', 'MasterController@logout',    []);
$router->get('/master',        'MasterController@dashboard', ['MasterMiddleware']);

// Empresas
$router->get('/master/empresas',               'MasterController@empresas',      ['MasterMiddleware']);
$router->get('/master/empresas/{id}',          'MasterController@verEmpresa',    ['MasterMiddleware']);
$router->post('/master/empresas/{id}',         'MasterController@salvarEmpresa', ['MasterMiddleware']);
$router->post('/master/empresas/{id}/toggle',  'MasterController@toggleEmpresa', ['MasterMiddleware']);
$router->post('/master/empresas/{id}/destaque','MasterController@toggleDestaque',['MasterMiddleware']);
$router->post('/master/empresas/{id}/excluir', 'MasterController@excluirEmpresa', ['MasterMiddleware']);

// WhatsApp — página de conexão (QR ao vivo)
$router->get('/master/whatsapp', 'MasterController@whatsapp', ['MasterMiddleware']);

// Usuários e admins
$router->get('/master/usuarios',          'MasterController@usuarios',    ['MasterMiddleware']);
$router->get('/master/admins',            'MasterController@admins',      ['MasterMiddleware']);
$router->post('/master/admins',           'MasterController@salvarAdmin', ['MasterMiddleware']);
$router->delete('/master/admins/{id}',    'MasterController@excluirAdmin',['MasterMiddleware']);

// Marketplace créditos
$router->get('/master/marketplace/creditos',       'MasterController@marketplaceCreditos',['MasterMiddleware']);
$router->post('/master/marketplace/creditos/{id}', 'MasterController@adicionarCreditos',  ['MasterMiddleware']);

// Adsense
$router->get('/master/adsense',  'MasterController@adsense',      ['MasterMiddleware']);
$router->post('/master/adsense', 'MasterController@salvarAdsense',['MasterMiddleware']);

// Avaliações
$router->get('/master/avaliacoes',                  'MasterController@avaliacoes',       ['MasterMiddleware']);
$router->post('/master/avaliacoes/{id}/aprovar',    'MasterController@aprovarAvaliacao', ['MasterMiddleware']);
$router->post('/master/avaliacoes/{id}/reprovar',   'MasterController@reprovarAvaliacao',['MasterMiddleware']);
$router->post('/master/avaliacoes/{id}/excluir',    'MasterController@excluirAvaliacao', ['MasterMiddleware']);
$router->post('/master/avaliacoes/{id}/manter',     'MasterController@manterAvaliacao',  ['MasterMiddleware']);
$router->post('/master/avaliacoes/{id}/remover',    'MasterController@removerAvaliacao', ['MasterMiddleware']);
$router->get('/master/feedbacks',                   'MasterController@feedbacks',        ['MasterMiddleware']);
$router->post('/master/feedbacks/{id}/status',      'MasterController@marcarFeedback',   ['MasterMiddleware']);

// Reivindicações de perfil do diretório
$router->get('/master/ia',                            'MasterController@iaConfig',             ['MasterMiddleware']);
$router->post('/master/ia',                           'MasterController@iaSalvar',             ['MasterMiddleware']);
$router->post('/master/ia/testar',                    'MasterController@iaTestar',             ['MasterMiddleware']);
$router->get('/master/imei',                          'MasterController@imeiConfig',           ['MasterMiddleware']);
$router->post('/master/imei',                         'MasterController@imeiSalvar',           ['MasterMiddleware']);
$router->post('/master/imei/testar',                  'MasterController@imeiTestar',            ['MasterMiddleware']);
$router->get('/master/interesse-nf',                  'MasterController@interesseNf',           ['MasterMiddleware']);
$router->get('/master/kb',                            'MasterController@kb',                   ['MasterMiddleware']);
$router->post('/master/kb/{id}',                      'MasterController@kbSalvar',             ['MasterMiddleware']);
$router->post('/master/kb/{id}/excluir',              'MasterController@kbExcluir',            ['MasterMiddleware']);
$router->get('/master/reivindicacoes',                'MasterController@reivindicacoes',       ['MasterMiddleware']);
$router->post('/master/reivindicacoes/{id}/aprovar',  'MasterController@aprovarReivindicacao',  ['MasterMiddleware']);
$router->post('/master/reivindicacoes/{id}/rejeitar', 'MasterController@rejeitarReivindicacao', ['MasterMiddleware']);

// Leads / lista de espera (CRM de early adopters)
$router->get('/master/leads',                'MasterController@leads',        ['MasterMiddleware']);
$router->post('/master/leads/{id}/convidar', 'MasterController@convidarLead', ['MasterMiddleware']);

// Prospecção — leads frios importados de dados abertos de CNPJ
$router->get('/master/prospeccao',                 'MasterController@prospeccao',         ['MasterMiddleware']);
$router->post('/master/prospeccao/{id}/status',     'MasterController@prospeccaoStatus',   ['MasterMiddleware']);
$router->post('/master/prospeccao/disparar',        'MasterController@prospeccaoDisparar', ['MasterMiddleware']);
$router->get('/prospeccao/descadastrar/{token}',    'MasterController@prospeccaoDescadastrar', []);
$router->get('/prospeccao/pixel/{token}',           'MasterController@prospeccaoPixel', []);

// Anúncios do diretório — prefixo /master/diretorio para não conflitar
$router->get('/master/diretorio',                          'MasterController@anunciosDiretorio', ['MasterMiddleware']);
$router->post('/master/diretorio/assinatura/{id}/ativar',  'MasterController@ativarAssinatura',  ['MasterMiddleware']);
$router->post('/master/diretorio/assinatura/{id}/cancelar','MasterController@cancelarAssinatura',['MasterMiddleware']);
$router->post('/master/diretorio/banner/{id}/aprovar',     'MasterController@aprovarBanner',     ['MasterMiddleware']);
$router->post('/master/diretorio/banner/{id}/reprovar',    'MasterController@reprovarBanner',    ['MasterMiddleware']);
$router->post('/master/diretorio/plano/{id}/toggle',       'MasterController@togglePlano',       ['MasterMiddleware']);
$router->post('/master/diretorio/planos',                  'MasterController@salvarPlano',        ['MasterMiddleware']);
$router->post('/master/diretorio/plano/{id}/excluir',      'MasterController@excluirPlano',       ['MasterMiddleware']);

// ══════════════════════════════════════════════════════════════════
//  SISTEMA — requer login
// ══════════════════════════════════════════════════════════════════

// Onboarding
$router->get('/setup',  'SetupController@onboarding', ['AuthMiddleware']);
$router->post('/setup', 'SetupController@salvar',     ['AuthMiddleware']);

// Dashboard
$router->get('/dashboard', 'DashboardController@index', ['AuthMiddleware']);
$router->post('/tutorial/visto', 'DashboardController@tutorialVisto', ['AuthMiddleware']);
$router->post('/preferencias/exibicao', 'DashboardController@salvarExibicao', ['AuthMiddleware']);
$router->post('/preferencias/chat',     'DashboardController@salvarChatConfig', ['AuthMiddleware']);
$router->post('/preferencias/previsao', 'DashboardController@salvarPrevisaoConfig', ['AuthMiddleware']);
$router->post('/preferencias/ferramentas', 'DashboardController@salvarFerramentasConfig', ['AuthMiddleware']);
$router->post('/preferencias/tema',        'DashboardController@salvarTema', ['AuthMiddleware']);

// Notificações
$router->get('/notificacoes',              'NotificacaoController@index',      ['AuthMiddleware']);
$router->get('/notificacoes/{id}/ler',     'NotificacaoController@marcarLida', ['AuthMiddleware']);
$router->post('/notificacoes/todas-lidas', 'NotificacaoController@marcarTodas',['AuthMiddleware']);
$router->get('/api/notificacoes',          'NotificacaoController@api',         ['AuthMiddleware']);
$router->get('/api/notificacoes/pendencias','NotificacaoController@pendencias', ['AuthMiddleware']);
$router->get('/api/chat/status',           'NotificacaoController@chatStatus',  ['AuthMiddleware']);
$router->post('/api/chat/lido',            'NotificacaoController@chatLido',    ['AuthMiddleware']);
$router->delete('/notificacoes/{id}',          'NotificacaoController@excluir',    ['AuthMiddleware']);
$router->post('/notificacoes/limpar-todas',    'NotificacaoController@limparTodas',['AuthMiddleware']);

// Manual
$router->get('/manual', 'AjudaController@manual', []);
$router->get('/manual/pdf', 'AjudaController@manualPdf', []);

// Clientes (específicas antes de /{id})
$router->get('/clientes',              'ClienteController@index',      ['AuthMiddleware']);
$router->get('/clientes/novo',         'ClienteController@criar',      ['AuthMiddleware']);
$router->post('/clientes',             'ClienteController@salvar',     ['AuthMiddleware']);
$router->get('/clientes/{id}',         'ClienteController@ver',        ['AuthMiddleware']);
$router->get('/clientes/{id}/editar',  'ClienteController@editar',     ['AuthMiddleware']);
$router->post('/clientes/{id}',        'ClienteController@atualizar',  ['AuthMiddleware']);
$router->post('/clientes/{id}/excluir', 'ClienteController@excluir',   ['AuthMiddleware']);
$router->get('/api/clientes',          'ClienteController@buscarAjax', ['AuthMiddleware']);
$router->get('/api/clientes/recentes', 'ClienteController@recentesAjax', ['AuthMiddleware']);
$router->get('/api/clientes/{id}/os-aberta', 'ClienteController@osAbertaAjax', ['AuthMiddleware']);
$router->get('/api/cnpj/{cnpj}',       'ClienteController@buscarCnpj',  ['AuthMiddleware']);
$router->get('/api/busca-global',      'BuscaGlobalController@buscar', ['AuthMiddleware']);
$router->post('/api/imei',             'ImeiController@consultar',      ['AuthMiddleware']);
$router->post('/api/clientes',         'ClienteController@salvarAjax', ['AuthMiddleware']);

// Configurações do Sistema (página única com abas, consolida os itens abaixo)
$router->get('/configuracoes', 'ConfiguracoesController@index', ['AuthMiddleware']);

// OS — status (específicas antes de /{id})
$router->get('/os/status',             'OsStatusController@index',     ['AuthMiddleware']);
$router->post('/os/status',            'OsStatusController@salvar',    ['AuthMiddleware']);
$router->post('/os/status/reordenar',  'OsStatusController@reordenar', ['AuthMiddleware']);
$router->delete('/os/status/{id}',     'OsStatusController@excluir',   ['AuthMiddleware']);

// OS — principais
$router->get('/os',                            'OrdemServicoController@index',            ['AuthMiddleware']);
$router->get('/os/nova',                       'OrdemServicoController@criar',            ['AuthMiddleware']);
$router->post('/os/fotos-entrada',             'OrdemServicoController@fotosEntrada',     ['AuthMiddleware']);
$router->post('/os/sincronizar-rascunho',      'OrdemServicoController@sincronizarRascunho', ['AuthMiddleware']);
$router->post('/os',                           'OrdemServicoController@salvar',           ['AuthMiddleware']);
$router->get('/os/{id}',                       'OrdemServicoController@ver',              ['AuthMiddleware']);
$router->get('/os/{id}/editar',                'OrdemServicoController@editar',           ['AuthMiddleware']);
$router->post('/os/{id}/editar',               'OrdemServicoController@atualizar',        ['AuthMiddleware']);
$router->get('/os/{id}/imprimir',              'OrdemServicoController@imprimir',         ['AuthMiddleware']);
$router->get('/os/{id}/imprimir/etiqueta',     'OrdemServicoController@imprimirEtiqueta',  ['AuthMiddleware']);
$router->get('/os/{id}/imprimir/orcamento',    'OrdemServicoController@imprimirOrcamento',['AuthMiddleware']);
$router->get('/os/{id}/imprimir/fechamento',   'OrdemServicoController@imprimirFechamento',['AuthMiddleware']);
$router->get('/os/{id}/imprimir/garantia',     'OrdemServicoController@imprimirGarantia', ['AuthMiddleware']);
$router->get('/os/{id}/imprimir/laudo',        'OrdemServicoController@imprimirLaudo',    ['AuthMiddleware']);
$router->get('/os/{id}/imprimir/sem-conserto', 'OrdemServicoController@imprimirSemConserto', ['AuthMiddleware']);
$router->post('/os/{id}/laudo-ia',              'OrdemServicoController@gerarLaudoIA',      ['AuthMiddleware']);
$router->post('/os/{id}/corrigir-texto',        'OrdemServicoController@corrigirTexto',     ['AuthMiddleware']);
$router->post('/os/{id}/whatsapp-pdf',          'OrdemServicoController@enviarPdfWhatsapp', ['AuthMiddleware']);
$router->post('/os/{id}/whatsapp-link',         'OrdemServicoController@enviarLinkWhatsapp',['AuthMiddleware']);
$router->post('/os/{id}/fotos-entrada',         'OrdemServicoController@salvarFotosEntrada', ['AuthMiddleware']);
$router->post('/os/{id}/fotos-entrada/{fotoId}/excluir', 'OrdemServicoController@excluirFotoEntrada', ['AuthMiddleware']);
$router->post('/os/{id}/recado',                'OrdemServicoController@salvarRecado',      ['AuthMiddleware']);
$router->post('/os/{id}/observacoes-internas',  'OrdemServicoController@salvarObservacoesInternas', ['AuthMiddleware']);
$router->post('/os/{id}/recado-whatsapp',       'OrdemServicoController@enviarRecadoWhatsapp',['AuthMiddleware']);
$router->post('/os/{id}/laudo',                 'OrdemServicoController@salvarLaudo',       ['AuthMiddleware']);
$router->post('/os/{id}/fechar',               'OrdemServicoController@fechar',           ['AuthMiddleware']);
$router->post('/os/{id}/reabrir',              'OrdemServicoController@reabrir',          ['AuthMiddleware']);
$router->post('/os/{id}/garantia',             'OrdemServicoController@abrirGarantia',   ['AuthMiddleware']);
$router->post('/os/{id}/finalizar-garantia',   'OrdemServicoController@finalizarGarantia',['AuthMiddleware']);
$router->post('/os/{id}/excluir',              'OrdemServicoController@excluir',          ['AuthMiddleware']);
$router->post('/os/{id}/duplicar',             'OrdemServicoController@duplicar',         ['AuthMiddleware']);
$router->post('/os/{id}/status',               'OrdemServicoController@atualizarStatus',  ['AuthMiddleware']);
$router->post('/os/{id}/servicos',             'OrdemServicoController@adicionarServico', ['AuthMiddleware']);
$router->post('/os/{id}/pecas',                'OrdemServicoController@adicionarPeca',    ['AuthMiddleware']);
$router->delete('/os/{id}/servicos/{itemId}',  'OrdemServicoController@removerServico',   ['AuthMiddleware']);
$router->delete('/os/{id}/pecas/{itemId}',     'OrdemServicoController@removerPeca',      ['AuthMiddleware']);
$router->post('/os/{id}/adiantamentos',        'OrdemServicoController@adicionarAdiantamento', ['AuthMiddleware']);
$router->delete('/os/{id}/adiantamentos/{itemId}', 'OrdemServicoController@excluirAdiantamento', ['AuthMiddleware']);
$router->get('/os/{id}/adiantamentos/{itemId}/imprimir',  'OrdemServicoController@imprimirAdiantamento',      ['AuthMiddleware']);
$router->post('/os/{id}/adiantamentos/{itemId}/whatsapp', 'OrdemServicoController@enviarAdiantamentoWhatsapp', ['AuthMiddleware']);
$router->get('/os/{id}/mensagens',             'OrdemServicoController@listarMensagens',  ['AuthMiddleware']);
$router->post('/os/{id}/mensagens',            'OrdemServicoController@enviarMensagem',   ['AuthMiddleware']);
$router->post('/os/{id}/mensagens/{msgId}/editar',  'OrdemServicoController@editarMensagem',  ['AuthMiddleware']);
$router->post('/os/{id}/mensagens/{msgId}/excluir', 'OrdemServicoController@excluirMensagem', ['AuthMiddleware']);
$router->post('/os/{id}/garantia-dias',        'OrdemServicoController@atualizarGarantia',['AuthMiddleware']);
$router->post('/os/{id}/previsao',             'OrdemServicoController@atualizarPrevisao',['AuthMiddleware']);
$router->get('/api/os/em-garantia',            'OrdemServicoController@buscarEmGarantia', ['AuthMiddleware']);
$router->get('/api/os/fechadas',               'OrdemServicoController@buscarFechadas',   ['AuthMiddleware']);
$router->get('/api/os',                        'OrdemServicoController@buscarAjax',       ['AuthMiddleware']);
$router->get('/api/agenda/conflito',           'AgendaController@conflito',               ['AuthMiddleware']);

// Scanner: celular como camera do PC (pareamento por QR + polling)
$router->post('/scanner/nova',      'ScannerController@nova',    ['AuthMiddleware']);
$router->post('/scanner/ler-etiqueta', 'ScannerController@lerDireto', ['AuthMiddleware']);
$router->post('/scanner/fotos-whatsapp-direto', 'ScannerController@enviarFotosWhatsappDireto', ['AuthMiddleware']);
$router->get('/scanner/qr',         'ScannerController@qr',      ['AuthMiddleware']);
$router->get('/scanner/status',     'ScannerController@status',  ['AuthMiddleware']);
$router->get('/scan',               'ScannerController@entrada', []);
$router->get('/scan/{token}',       'ScannerController@pagina',  []);
$router->post('/scan/{token}/foto', 'ScannerController@receber', []);
$router->post('/scan/{token}/fotos-whatsapp', 'ScannerController@enviarFotosWhatsapp', []);
$router->post('/scan/{token}/fotos-entrada', 'ScannerController@receberFotosEntrada', []);
// Sem AuthMiddleware de propósito: só pesquisa os títulos estáticos de manual_secoes() (sem
// tocar banco, nada sensível) e /manual em si é público (ver AjudaController::manual(), usa o
// layout manual_publico pra visitante deslogado) — com o middleware, a busca no manual público
// ficava quebrada em silêncio pra quem não estava logado (fetch seguia o redirect pro /login,
// virava HTML, r.json() falhava no catch vazio de buscaGlobalInput()).
$router->get('/api/busca',                     'BuscaController@buscar',                  []);

// Produtos auxiliares (API)
$router->get('/api/produto/{tipo}',                      'ProdutoAuxController@listar',                ['AuthMiddleware']);
$router->post('/api/produto/{tipo}',                     'ProdutoAuxController@salvar',                ['AuthMiddleware']);
$router->delete('/api/produto/{tipo}/{id}',              'ProdutoAuxController@excluir',               ['AuthMiddleware']);
$router->get('/api/fornecedores',                        'FornecedorController@buscarAjax',            ['AuthMiddleware']);
$router->post('/api/fornecedores',                       'FornecedorController@criarAjax',             ['AuthMiddleware']);
$router->get('/api/equip/acessorios-padrao/{equipTipo}', 'ProdutoAuxController@acessoriosPadrao',      ['AuthMiddleware']);
$router->post('/api/equip/acessorios-padrao',            'ProdutoAuxController@salvarAcessoriosPadrao',['AuthMiddleware']);

// Produtos / Estoque (específicas antes de /{id})
$router->get('/produtos',              'ProdutoController@index',      ['AuthMiddleware']);
$router->get('/produtos/novo',         'ProdutoController@criar',      ['AuthMiddleware']);
// Categorias de produto (ANTES de /produtos/{id} para não conflitar)
$router->get('/produtos/categorias',            'ProdutoCategoriasController@index',     ['AuthMiddleware']);
$router->post('/produtos/categorias',           'ProdutoCategoriasController@salvar',    ['AuthMiddleware']);
$router->post('/produtos/categorias/{id}',      'ProdutoCategoriasController@atualizar', ['AuthMiddleware']);
$router->post('/produtos/categorias/{id}/excluir','ProdutoCategoriasController@excluir', ['AuthMiddleware']);
$router->post('/produtos',             'ProdutoController@salvar',     ['AuthMiddleware']);
$router->get('/produtos/{id}/editar',  'ProdutoController@editar',     ['AuthMiddleware']);
$router->post('/produtos/{id}/entrada','ProdutoController@entrada',    ['AuthMiddleware']);
$router->post('/produtos/{id}',        'ProdutoController@atualizar',  ['AuthMiddleware']);
$router->post('/produtos/{id}/excluir','ProdutoController@excluir',    ['AuthMiddleware']);
$router->get('/api/produtos',          'ProdutoController@buscarAjax', ['AuthMiddleware']);

// Serviços cadastrados (catálogo padronizado — alimenta o autocomplete do modal de serviço da OS)
$router->get('/servicos',              'ServicosCatalogoController@index',      ['AuthMiddleware']);
$router->post('/servicos',             'ServicosCatalogoController@salvar',     ['AuthMiddleware']);
// Precisa vir ANTES de /servicos/{id} — o router casa na ordem de registro, senão
// "excluir-lote" seria interpretado como {id}.
$router->post('/servicos/excluir-lote','ServicosCatalogoController@excluirLote',['AuthMiddleware']);
$router->post('/servicos/{id}',        'ServicosCatalogoController@atualizar',  ['AuthMiddleware']);
$router->post('/servicos/{id}/excluir','ServicosCatalogoController@excluir',    ['AuthMiddleware']);
$router->get('/api/servicos',          'ServicosCatalogoController@buscarAjax', ['AuthMiddleware']);

// PDV — Frente de Caixa
$router->get('/pdv',                   'PdvController@index',       ['AuthMiddleware']);
$router->post('/pdv/finalizar',        'PdvController@finalizar',   ['AuthMiddleware']);
$router->get('/pdv/comprovante/{id}',  'PdvController@comprovante', ['AuthMiddleware']);
$router->get('/pdv/comprovante/{id}/a4',        'PdvController@imprimirA4',            ['AuthMiddleware']);
$router->post('/pdv/comprovante/{id}/whatsapp', 'PdvController@enviarComprovanteWhatsapp', ['AuthMiddleware']);

// Fornecedores (específicas antes de /{id})
$router->get('/fornecedores',              'FornecedorController@index',    ['AuthMiddleware']);
$router->get('/fornecedores/novo',         'FornecedorController@criar',    ['AuthMiddleware']);
$router->post('/fornecedores',             'FornecedorController@salvar',   ['AuthMiddleware']);
$router->get('/fornecedores/{id}/editar',  'FornecedorController@editar',   ['AuthMiddleware']);
$router->post('/fornecedores/{id}',        'FornecedorController@atualizar',['AuthMiddleware']);
$router->delete('/fornecedores/{id}',      'FornecedorController@excluir',  ['AuthMiddleware']);

// CRM
$router->get('/crm',                             'CrmController@pipeline',              ['AuthMiddleware']);
$router->post('/crm/contatos',                    'CrmController@registrarContato',      ['AuthMiddleware']);
$router->post('/crm/oportunidades',               'CrmController@criarOportunidade',     ['AuthMiddleware']);
$router->post('/crm/oportunidades/{id}/editar',   'CrmController@atualizarOportunidade', ['AuthMiddleware']);
$router->post('/crm/oportunidades/{id}/mover',    'CrmController@moverOportunidade',     ['AuthMiddleware']);
$router->post('/crm/oportunidades/{id}/excluir',  'CrmController@excluirOportunidade',   ['AuthMiddleware']);

// Financeiro — categorias ANTES de /{id} para evitar conflito
$router->get('/financeiro/categorias',          'FinanceiroCategoriasController@index',    ['AuthMiddleware']);
$router->post('/financeiro/categorias',         'FinanceiroCategoriasController@salvar',   ['AuthMiddleware']);
$router->post('/financeiro/categorias/{id}',    'FinanceiroCategoriasController@atualizar',['AuthMiddleware']);
$router->delete('/financeiro/categorias/{id}',  'FinanceiroCategoriasController@excluir',  ['AuthMiddleware']);

// Financeiro — lançamentos
$router->get('/api/financeiro/buscar',      'FinanceiroController@buscar',   ['AuthMiddleware']);
$router->get('/api/financeiro/duplicata',   'FinanceiroController@verificarDuplicata', ['AuthMiddleware']);
$router->get('/financeiro',                 'FinanceiroController@index',    ['AuthMiddleware']);
$router->post('/financeiro/inicio',         'FinanceiroController@salvarInicio', ['AuthMiddleware']);
$router->post('/financeiro',                'FinanceiroController@salvar',   ['AuthMiddleware']);
$router->get('/financeiro/{id}/editar',     'FinanceiroController@editar',   ['AuthMiddleware']);
$router->post('/financeiro/{id}/editar',    'FinanceiroController@atualizar',['AuthMiddleware']);
$router->post('/financeiro/{id}/liquidar',  'FinanceiroController@liquidar', ['AuthMiddleware']);
$router->post('/financeiro/{id}/pagar',     'FinanceiroController@pagar',    ['AuthMiddleware']);
$router->post('/financeiro/os/{id}/pagar',  'FinanceiroController@pagarOs',  ['AuthMiddleware']);
$router->delete('/financeiro/{id}',         'FinanceiroController@excluir',  ['AuthMiddleware']);

// Comissões de técnicos
$router->get('/comissoes',                   'ComissaoController@index',            ['AuthMiddleware']);
$router->get('/comissoes/nova',              'ComissaoController@criar',            ['AuthMiddleware']);
$router->post('/comissoes',                  'ComissaoController@salvar',           ['AuthMiddleware']);
$router->get('/comissoes/{id}/editar',       'ComissaoController@editar',           ['AuthMiddleware']);
$router->post('/comissoes/{id}/atualizar',   'ComissaoController@atualizar',        ['AuthMiddleware']);
$router->post('/comissoes/{id}/pagar',       'ComissaoController@pagar',            ['AuthMiddleware']);
$router->post('/comissoes/{id}/excluir',     'ComissaoController@excluir',          ['AuthMiddleware']);
$router->get('/api/comissoes/buscar-os',     'ComissaoController@buscarOs',         ['AuthMiddleware']);
$router->get('/api/comissoes/valor-servicos','ComissaoController@valorServicos',    ['AuthMiddleware']);
$router->get('/api/comissoes/percentual/{id}','ComissaoController@percentualTecnico',['AuthMiddleware']);

// Agenda
$router->get('/agenda',           'AgendaController@index',    ['AuthMiddleware']);
$router->post('/agenda',          'AgendaController@salvar',   ['AuthMiddleware']);
$router->post('/agenda/{id}',     'AgendaController@atualizar',['AuthMiddleware']);
$router->delete('/agenda/{id}',   'AgendaController@excluir',  ['AuthMiddleware']);
$router->post('/agenda/{id}/status', 'AgendaController@mudarStatus', ['AuthMiddleware']);
$router->post('/agenda/{id}/marcar-pago', 'AgendaController@marcarPago', ['AuthMiddleware']);
$router->post('/agenda/{id}/enviar-tecnico', 'AgendaController@enviarInfoTecnico', ['AuthMiddleware']);
$router->get('/api/agenda',       'AgendaController@eventos',  ['AuthMiddleware']);

// Ordem personalizada do menu lateral (por usuário)
$router->post('/menu/ordem',      'MenuController@salvarOrdem', ['AuthMiddleware']);

// Relatórios (específica antes)
$router->get('/relatorios',           'RelatorioController@index',    ['AuthMiddleware']);
$router->get('/relatorios/imprimir',  'RelatorioController@imprimir', ['AuthMiddleware']);

// Marketplace privado — categorias ANTES de /{id}
$router->get('/marketplace/categorias',                   'MarketplaceCategoriasController@index',      ['AuthMiddleware']);
$router->post('/marketplace/categorias',                  'MarketplaceCategoriasController@salvar',     ['AuthMiddleware']);
$router->post('/marketplace/categorias/{id}/excluir',     'MarketplaceCategoriasController@excluir',    ['AuthMiddleware']);
$router->post('/marketplace/categorias/{id}/toggle',      'MarketplaceCategoriasController@toggleAtivo',['AuthMiddleware']);

// Marketplace privado — anúncios
// (sem AuthMiddleware: quem cai aqui deslogado é redirecionado pra vitrine pública
// /pecas dentro do próprio index(), em vez de bater na tela de login — ver comentário lá)
$router->get('/marketplace',                        'MarketplaceController@index',       []);
$router->get('/marketplace/meus-anuncios',          'MarketplaceController@meusAnuncios',['AuthMiddleware']);
$router->post('/marketplace/anuncios',              'MarketplaceController@criar',       ['AuthMiddleware']);
$router->get('/marketplace/anuncios/{id}/editar',   'MarketplaceController@editar',      ['AuthMiddleware']);
$router->post('/marketplace/anuncios/{id}/editar',  'MarketplaceController@atualizar',   ['AuthMiddleware']);
$router->post('/marketplace/anuncios/{id}/pausar',  'MarketplaceController@pausar',      ['AuthMiddleware']);
$router->post('/marketplace/anuncios/{id}/vender',  'MarketplaceController@vender',      ['AuthMiddleware']);
$router->delete('/marketplace/anuncios/{id}',       'MarketplaceController@excluir',     ['AuthMiddleware']);

// Fórum privado — específicas antes de /{id}
$router->get('/forum/novo',                       'ForumController@criar',          ['AuthMiddleware']);
$router->post('/forum',                           'ForumController@salvar',         ['AuthMiddleware']);
$router->post('/forum/topico/{id}/responder',     'ForumController@responder',      ['AuthMiddleware']);
$router->post('/forum/topico/{id}/excluir',       'ForumController@excluirTopico',  ['AuthMiddleware']);
$router->post('/forum/curtir',                    'ForumController@curtir',         ['AuthMiddleware']);
$router->post('/forum/resposta/{id}/melhor',      'ForumController@melhorResposta', ['AuthMiddleware']);
$router->post('/forum/resposta/{id}',             'ForumController@excluirResposta',['AuthMiddleware']);

// Técnicos (específicas antes de /{id})
$router->get('/api/tecnicos',            'TecnicoController@apiListar',    ['AuthMiddleware']);
$router->get('/api/tecnicos/buscar-email','TecnicoController@apiBuscarPorEmail', ['AuthMiddleware']);
$router->post('/api/tecnicos',           'TecnicoController@apiSalvar',    ['AuthMiddleware']);
$router->post('/api/tecnicos/{id}',      'TecnicoController@apiAtualizar', ['AuthMiddleware']);
$router->post('/api/tecnicos/{id}/excluir','TecnicoController@apiExcluir', ['AuthMiddleware']);
$router->get('/tecnicos',              'TecnicoController@index',    ['AuthMiddleware']);
$router->get('/tecnicos/novo',         'TecnicoController@criar',    ['AuthMiddleware']);
$router->post('/tecnicos',             'TecnicoController@salvar',   ['AuthMiddleware']);
$router->get('/tecnicos/{id}',         'TecnicoController@ver',      ['AuthMiddleware']);
$router->get('/tecnicos/{id}/editar',  'TecnicoController@editar',   ['AuthMiddleware']);
$router->post('/tecnicos/{id}/editar', 'TecnicoController@atualizar',['AuthMiddleware']);
$router->delete('/tecnicos/{id}',      'TecnicoController@excluir',  ['AuthMiddleware']);

// Usuários (específicas antes de /{id})
$router->get('/usuarios',              'UsuarioController@index',    ['AuthMiddleware']);
$router->get('/usuarios/novo',         'UsuarioController@criar',    ['AuthMiddleware']);
$router->post('/usuarios',             'UsuarioController@salvar',   ['AuthMiddleware']);
$router->get('/usuarios/{id}/editar',  'UsuarioController@editar',   ['AuthMiddleware']);
$router->post('/usuarios/{id}',        'UsuarioController@atualizar',['AuthMiddleware']);
$router->delete('/usuarios/{id}',      'UsuarioController@excluir',  ['AuthMiddleware']);

// Editor de Imagens
$router->get('/editor-imagens',          'EditorImagensController@index', ['AuthMiddleware']);
$router->post('/editor-imagens/salvar',  'EditorImagensController@salvar',['AuthMiddleware']);

// Empresa — específicas antes das genéricas
// Migração self-service DESATIVADA (2026-07-02): migração agora é serviço assistido pago.
// $router->get('/empresa/migracao-shoficina',  'EmpresaController@migracaoShoficina',  ['AuthMiddleware']);
// $router->post('/empresa/migracao-shoficina', 'EmpresaController@executarMigracao',   ['AuthMiddleware']);
$router->get('/empresa/perfil-publico',  'EmpresaController@perfilPublico',      ['AuthMiddleware']);
$router->post('/empresa/perfil-publico', 'EmpresaController@salvarPerfilPublico',['AuthMiddleware']);
$router->post('/empresa/perfil-publico/destaque', 'EmpresaController@ativarDestaqueGratis', ['AuthMiddleware']);
$router->post('/empresa/avaliacoes/{id}/responder', 'EmpresaController@responderAvaliacao', ['AuthMiddleware']);
$router->post('/empresa/avaliacoes/{id}/contestar', 'EmpresaController@contestarAvaliacao', ['AuthMiddleware']);

// WhatsApp da empresa (conexão própria — cada loja envia do seu número)
$router->get('/empresa/whatsapp',              'EmpresaController@whatsapp',            ['AuthMiddleware']);
$router->get('/empresa/whatsapp/status',       'EmpresaController@whatsappStatus',      ['AuthMiddleware']);
$router->post('/empresa/whatsapp/desconectar', 'EmpresaController@whatsappDesconectar', ['AuthMiddleware']);
$router->post('/feedback', 'FeedbackController@enviar', ['AuthMiddleware']);
$router->post('/empresa/fotos',                'EmpresaController@uploadFoto',    ['AuthMiddleware']);
$router->post('/empresa/fotos/{id}/remover',   'EmpresaController@removerFoto',   ['AuthMiddleware']);
$router->post('/empresa/fotos/{id}/principal', 'EmpresaController@fotoPrincipal', ['AuthMiddleware']);
$router->post('/empresa/logo/remover',   'EmpresaController@removerLogo',        ['AuthMiddleware']);
$router->get('/empresa/exportar',        'EmpresaController@exportar',           ['AuthMiddleware']);
$router->get('/empresa',                 'EmpresaController@index',              ['AuthMiddleware']);
$router->post('/empresa',                'EmpresaController@salvar',             ['AuthMiddleware']);
$router->get('/empresa/logs',            'EmpresaController@logs',               ['AuthMiddleware']);
$router->post('/empresa/interesse-nf',   'EmpresaController@interesseNf',        ['AuthMiddleware']);
$router->get('/planos',                  'EmpresaController@planos',             ['AuthMiddleware']);
$router->get('/assinar/{plano}',         'PagamentoController@assinar',          ['AuthMiddleware']);
$router->get('/assinar/{plano}/{ciclo}', 'PagamentoController@assinar',          ['AuthMiddleware']);
$router->get('/comprar-credito',         'PagamentoController@comprarCredito',   ['AuthMiddleware']);
$router->get('/comprar-credito-scan-equip', 'PagamentoController@comprarCreditoScanEquip', ['AuthMiddleware']);
$router->get('/comprar-credito-scan-placa', 'PagamentoController@comprarCreditoScanPlaca', ['AuthMiddleware']);
$router->get('/pagamento/retorno',       'PagamentoController@retorno',          ['AuthMiddleware']);
$router->post('/webhook/infinitepay',    'PagamentoController@webhook',          []);
$router->post('/webhook/whatsapp-ia',    'BotController@webhook',                []);

// Publicidade no diretório (empresa)
$router->get('/empresa/publicidade',                   'DiretorioAnunciosController@index',       ['AuthMiddleware']);
$router->post('/empresa/publicidade/contratar/{id}',   'DiretorioAnunciosController@contratar',   ['AuthMiddleware']);
$router->post('/empresa/publicidade/banner/{id}',      'DiretorioAnunciosController@uploadBanner',['AuthMiddleware']);
