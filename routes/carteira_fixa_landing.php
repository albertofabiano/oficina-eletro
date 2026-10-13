<?php

// Rotas do subdomínio carteirafixa.fixaos.com.br — só a landing pública do produto, sem
// ligação nenhuma com o FixaOS (sem sessão, sem middleware). Cadastro/login de verdade
// continuam no domínio principal (/carteira-fixa/cadastrar, /carteira-fixa/login, via
// routes/web.php) — url() dentro da view sempre resolve pra lá, independente de qual domínio
// serviu esta página (ver app/Helpers/functions.php).
$router->get('/', 'CarteiraFixaLandingController@index', []);
