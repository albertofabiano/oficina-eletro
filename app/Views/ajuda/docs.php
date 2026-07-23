<?php $titulo = 'Documentação Técnica'; ?>
<style>
.docs-wrap{display:flex;gap:0;min-height:calc(100vh - 60px)}
.docs-nav{width:240px;flex-shrink:0;border-right:1px solid var(--border);background:var(--bg2);position:sticky;top:60px;height:calc(100vh - 60px);overflow-y:auto;padding:1.2rem 0}
.docs-body{flex:1;padding:3rem;max-width:860px}
.d-nav-title{font-size:.68rem;color:#374151;text-transform:uppercase;letter-spacing:.08em;padding:.4rem 1.2rem;font-weight:700;margin-top:1rem}
.d-nav-link{display:block;padding:.42rem 1.2rem;color:#6b7280;font-size:.84rem;text-decoration:none;border-left:2px solid transparent;transition:.15s}
.d-nav-link:hover,.d-nav-link.active{color:#fff;border-left-color:var(--brand)}
.d-h1{color:#fff;font-size:2rem;font-weight:900;margin-bottom:.5rem}
.d-h2{color:#fff;font-size:1.4rem;font-weight:800;margin:2.5rem 0 1rem;padding-top:1rem;border-top:1px solid var(--border)}
.d-h3{color:#f1f5f9;font-size:1rem;font-weight:700;margin:1.5rem 0 .6rem}
.d-p{color:#94a3b8;font-size:.9rem;line-height:1.8;margin-bottom:1rem}
.d-code{background:#0d1117;border:1px solid #21262d;border-radius:10px;padding:1.2rem 1.4rem;overflow-x:auto;margin:1rem 0}
.d-code pre{color:#e6edf3;font-size:.84rem;line-height:1.6;margin:0;font-family:'Fira Code','Cascadia Code',monospace}
.d-code .cm{color:#8b949e}.d-code .kw{color:#ff7b72}.d-code .cl{color:#79c0ff}
.d-code .fn{color:#d2a8ff}.d-code .st{color:#a5d6ff}.d-code .va{color:#ffa657}
.d-code .nu{color:#79c0ff}.d-code .co{color:#3fb950}
.d-table{width:100%;border-collapse:collapse;font-size:.85rem;margin:1rem 0}
.d-table th{background:#0d1117;color:#6b7280;padding:.6rem .9rem;text-align:left;border-bottom:1px solid var(--border);font-size:.76rem;text-transform:uppercase}
.d-table td{padding:.6rem .9rem;border-bottom:1px solid #1f2937;color:#cbd5e1;vertical-align:top;font-family:monospace}
.d-table td:first-child{color:#fb923c}
.d-table tr:last-child td{border-bottom:none}
.d-badge{display:inline-block;border-radius:5px;padding:.15rem .55rem;font-size:.72rem;font-weight:700}
.d-tip{background:rgba(59,130,246,.07);border:1px solid rgba(59,130,246,.2);border-radius:10px;padding:.9rem 1.1rem;margin:1rem 0;font-size:.84rem;color:#93c5fd}
.d-warn{background:rgba(249,115,22,.07);border:1px solid rgba(249,115,22,.2);border-radius:10px;padding:.9rem 1.1rem;margin:1rem 0;font-size:.84rem;color:#fb923c}
.d-section{scroll-margin-top:80px}
.method-badge{display:inline-block;border-radius:5px;padding:.1rem .5rem;font-size:.7rem;font-weight:800;margin-right:.4rem}
.get{background:rgba(34,197,94,.15);color:#4ade80}
.post{background:rgba(59,130,246,.15);color:#60a5fa}
.del{background:rgba(239,68,68,.15);color:#f87171}
@media(max-width:768px){.docs-nav{display:none}.docs-body{padding:1.5rem}}
</style>

<div class="docs-wrap">

<!-- Nav lateral -->
<div class="docs-nav">
  <div style="padding:.5rem 1.2rem 1rem;border-bottom:1px solid var(--border)">
    <div style="color:#fff;font-weight:700;font-size:.88rem">Docs Técnica</div>
    <div style="color:#374151;font-size:.72rem">FixaOS v1.0</div>
  </div>
  <?php
  $nav=[
    'Visão Geral'=>[['intro','Introdução'],['stack','Stack tecnológica']],
    'Arquitetura'=>[['estrutura','Estrutura de pastas'],['mvc','Padrão MVC'],['rotas','Sistema de rotas'],['middlewares','Middlewares']],
    'Banco de Dados'=>[['banco-tabelas','Tabelas principais'],['banco-multi','Multitenancy'],['banco-convencoes','Convenções']],
    'Controllers'=>[['ctrl-base','Controller base'],['ctrl-criar','Criar controller']],
    'Views'=>[['views-layouts','Layouts'],['views-helpers','Helpers de view']],
    'Autenticação'=>[['auth-sessao','Sessão e Auth'],['auth-google','OAuth Google'],['auth-csrf','CSRF']],
    'Rotas'=>[['rotas-web','web.php'],['rotas-params','Parâmetros dinâmicos']],
    'Deploy'=>[['deploy-laragon','Laragon (dev)'],['deploy-prod','Produção']],
  ];
  foreach($nav as$g=>$links):?>
  <div class="d-nav-title"><?=$g?></div>
  <?php foreach($links as[$id,$l]):?>
  <a href="#<?=$id?>" class="d-nav-link"><?=$l?></a>
  <?php endforeach;endforeach;?>
</div>

<!-- Conteúdo -->
<div class="docs-body">

  <div class="d-section" id="intro">
    <div class="sec-tag">Documentação Técnica</div>
    <div class="d-h1">FixaOS — Referência do Desenvolvedor</div>
    <p class="d-p">Esta documentação descreve a arquitetura, convenções e estrutura interna do FixaOS — um sistema MVC puro em PHP 8.3 sem frameworks, desenvolvido para gestão de assistências técnicas.</p>
    <div class="d-tip"><i class="bi bi-info-circle-fill me-1"></i>PHP 8.3 · MySQL 8.4 · Bootstrap 5.3 · Sem Composer obrigatório (dependências manuais).</div>
  </div>

  <div class="d-section" id="stack">
    <div class="d-h2">Stack Tecnológica</div>
    <table class="d-table">
      <thead><tr><th>Camada</th><th>Tecnologia</th><th>Versão</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Backend','PHP','8.3+'],
          ['Banco de Dados','MySQL','8.4'],
          ['Frontend CSS','Bootstrap','5.3.3'],
          ['Ícones','Bootstrap Icons','1.11.3'],
          ['Gráficos','Chart.js','4.x'],
          ['Servidor (dev)','Laragon','6.x'],
          ['Servidor (prod)','Apache / Nginx','—'],
          ['Tipografia','Inter (Google Fonts)','Variable'],
        ] as[$c,$t,$v]):?>
        <tr><td><?=$c?></td><td style="color:#e2e8f0;font-family:inherit"><?=$t?></td><td style="color:#6b7280;font-family:inherit"><?=$v?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <div class="d-section" id="estrutura">
    <div class="d-h2">Estrutura de Pastas</div>
    <div class="d-code"><pre>
oficina-eletro/
├── <span class="co">app/</span>
│   ├── <span class="co">Controllers/</span>     <span class="cm"># Lógica de cada módulo</span>
│   ├── <span class="co">Core/</span>            <span class="cm"># Kernel do framework</span>
│   │   ├── <span class="cl">Auth.php</span>      <span class="cm"># Gerenciamento de sessão</span>
│   │   ├── <span class="cl">Controller.php</span><span class="cm"># Classe base dos controllers</span>
│   │   ├── <span class="cl">DB.php</span>        <span class="cm"># Singleton PDO</span>
│   │   ├── <span class="cl">Model.php</span>     <span class="cm"># Classe base dos models</span>
│   │   └── <span class="cl">Router.php</span>    <span class="cm"># Roteador HTTP</span>
│   ├── <span class="co">Helpers/</span>
│   │   └── <span class="cl">functions.php</span> <span class="cm"># Funções globais (url, e, flash...)</span>
│   ├── <span class="co">Middleware/</span>      <span class="cm"># AuthMiddleware, GuestMiddleware...</span>
│   ├── <span class="co">Models/</span>          <span class="cm"># Modelos de dados</span>
│   └── <span class="co">Views/</span>           <span class="cm"># Templates PHP</span>
│       └── <span class="co">layouts/</span>     <span class="cm"># Layouts: main, auth, landing...</span>
├── <span class="co">config/</span>
│   ├── <span class="cl">app.php</span>          <span class="cm"># URL, nome, chave, timezone</span>
│   ├── <span class="cl">database.php</span>     <span class="cm"># Credenciais MySQL</span>
│   └── <span class="cl">google.php</span>       <span class="cm"># OAuth Google</span>
├── <span class="co">database/</span>
│   └── <span class="co">migrations/</span>      <span class="cm"># Arquivos SQL de criação</span>
├── <span class="co">public/</span>           <span class="cm"># Document root (Apache/Nginx)</span>
│   ├── <span class="cl">index.php</span>        <span class="cm"># Front controller</span>
│   ├── <span class="co">css/</span>             <span class="cm"># app.css</span>
│   └── <span class="co">js/</span>              <span class="cm"># masks.js</span>
├── <span class="co">routes/</span>
│   └── <span class="cl">web.php</span>          <span class="cm"># Todas as rotas HTTP</span>
└── <span class="co">storage/</span>
    ├── <span class="co">uploads/</span>         <span class="cm"># Imagens enviadas</span>
    └── <span class="co">logs/</span>            <span class="cm"># Logs de erro</span></pre></div>
  </div>

  <div class="d-section" id="mvc">
    <div class="d-h2">Padrão MVC</div>
    <p class="d-p">O FixaOS implementa MVC puro sem framework. O fluxo de uma requisição é:</p>
    <div class="d-code"><pre>
<span class="cm"># Requisição HTTP</span>
<span class="va">public/index.php</span>
  → <span class="cl">Router::dispatch()</span>         <span class="cm"># Encontra a rota correspondente</span>
  → <span class="cl">Middleware::handle()</span>        <span class="cm"># Verifica autenticação, CSRF etc.</span>
  → <span class="cl">Controller::method()</span>        <span class="cm"># Executa a lógica</span>
  → <span class="cl">Model::query()</span>             <span class="cm"># Acessa o banco via PDO</span>
  → <span class="cl">Controller::view()</span>          <span class="cm"># Renderiza o template PHP</span></pre></div>

    <div class="d-h3">Controller base</div>
    <div class="d-code"><pre>
<span class="kw">class</span> <span class="cl">Controller</span> {
  <span class="kw">protected function</span> <span class="fn">view</span>(<span class="va">string</span> <span class="va">$view</span>, <span class="va">array</span> <span class="va">$data</span> = [], <span class="va">string</span> <span class="va">$layout</span> = <span class="st">'main'</span>): <span class="kw">void</span>
  <span class="kw">protected function</span> <span class="fn">redirect</span>(<span class="va">string</span> <span class="va">$url</span>): <span class="kw">void</span>
  <span class="kw">protected function</span> <span class="fn">flash</span>(<span class="va">string</span> <span class="va">$key</span>, <span class="va">string</span> <span class="va">$msg</span>): <span class="kw">void</span>
  <span class="kw">protected function</span> <span class="fn">post</span>(<span class="va">string</span> <span class="va">$key</span>, <span class="va">mixed</span> <span class="va">$default</span> = <span class="kw">null</span>): <span class="va">mixed</span>
  <span class="kw">protected function</span> <span class="fn">json</span>(<span class="va">mixed</span> <span class="va">$data</span>, <span class="nu">int</span> <span class="va">$code</span> = <span class="nu">200</span>): <span class="kw">void</span>
}</pre></div>
  </div>

  <div class="d-section" id="rotas">
    <div class="d-h2">Sistema de Rotas</div>
    <p class="d-p">As rotas são definidas em <code style="color:#fb923c">routes/web.php</code>. O roteador suporta GET, POST e DELETE com parâmetros dinâmicos e middlewares.</p>
    <div class="d-code"><pre>
<span class="cm">// Rota simples</span>
<span class="va">$router</span>-><span class="fn">get</span>(<span class="st">'/dashboard'</span>, <span class="st">'DashboardController@index'</span>, [<span class="st">'AuthMiddleware'</span>]);

<span class="cm">// Rota com parâmetro dinâmico</span>
<span class="va">$router</span>-><span class="fn">get</span>(<span class="st">'/os/{id}'</span>, <span class="st">'OrdemServicoController@ver'</span>, [<span class="st">'AuthMiddleware'</span>]);

<span class="cm">// Rota POST</span>
<span class="va">$router</span>-><span class="fn">post</span>(<span class="st">'/os'</span>, <span class="st">'OrdemServicoController@salvar'</span>, [<span class="st">'AuthMiddleware'</span>]);

<span class="cm">// Rota DELETE</span>
<span class="va">$router</span>-><span class="fn">delete</span>(<span class="st">'/os/{id}'</span>, <span class="st">'OrdemServicoController@excluir'</span>, [<span class="st">'AuthMiddleware'</span>]);</pre></div>
  </div>

  <div class="d-section" id="middlewares">
    <div class="d-h2">Middlewares</div>
    <table class="d-table">
      <thead><tr><th>Middleware</th><th>Função</th></tr></thead>
      <tbody>
        <?php foreach([
          ['AuthMiddleware','Redireciona para /login se o usuário não estiver autenticado.'],
          ['GuestMiddleware','Redireciona para /dashboard se o usuário já estiver logado.'],
          ['MasterMiddleware','Restringe acesso ao painel master (admin global).'],
          ['MasterGuestMiddleware','Bloqueia acesso à tela de login master se já autenticado como master.'],
        ] as[$m,$d]):?>
        <tr><td><?=$m?></td><td style="color:#94a3b8;font-family:inherit"><?=$d?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <div class="d-section" id="banco-tabelas">
    <div class="d-h2">Tabelas Principais do Banco</div>
    <table class="d-table">
      <thead><tr><th>Tabela</th><th>Descrição</th></tr></thead>
      <tbody>
        <?php foreach([
          ['empresas','Cadastro das assistências técnicas (tenants).'],
          ['usuarios','Usuários com empresa_id, perfil e senha hash.'],
          ['clientes','Clientes PF/PJ vinculados a uma empresa.'],
          ['equipamentos','Equipamentos dos clientes com tipo, marca e modelo.'],
          ['ordens_servico','OS com status, defeito, valor e datas.'],
          ['os_status','Status personalizáveis por empresa com tipo e cor.'],
          ['os_servicos','Serviços realizados em cada OS.'],
          ['os_pecas','Peças utilizadas em cada OS.'],
          ['os_historico','Log de alterações de status das OS.'],
          ['produtos','Produtos do estoque com custo, preço e quantidade.'],
          ['movimentos_estoque','Entradas e saídas de estoque.'],
          ['fin_lancamentos','Lançamentos financeiros (receitas e despesas).'],
          ['fin_contas','Contas financeiras (caixa, banco etc.).'],
          ['fin_categorias','Categorias de lançamentos financeiros.'],
          ['agenda','Agendamentos vinculados a clientes e OS.'],
          ['crm_oportunidades','Oportunidades do pipeline de vendas.'],
          ['marketplace_pecas','Anúncios de peças no marketplace.'],
          ['master_adsense_blocos','Blocos de AdSense para marketplace e fórum.'],
          ['forum_topicos','Tópicos do fórum técnico.'],
          ['forum_respostas','Respostas nos tópicos do fórum.'],
        ] as[$t,$d]):?>
        <tr><td><?=$t?></td><td style="color:#94a3b8;font-family:inherit"><?=$d?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <div class="d-section" id="banco-multi">
    <div class="d-h2">Multitenancy</div>
    <p class="d-p">O FixaOS usa <strong style="color:#fff">multitenancy por coluna</strong>: todas as tabelas de dados possuem a coluna <code style="color:#fb923c">empresa_id</code> que isola completamente os dados entre diferentes clientes.</p>
    <div class="d-code"><pre>
<span class="cm">-- Sempre filtre por empresa_id em toda query</span>
<span class="kw">SELECT</span> * <span class="kw">FROM</span> <span class="cl">ordens_servico</span>
<span class="kw">WHERE</span> <span class="va">empresa_id</span> = <span class="nu">:empresa_id</span>
  <span class="kw">AND</span> <span class="va">status_id</span> = <span class="nu">:status</span>;

<span class="cm">-- No Model base, empresaId() já retorna o ID da sessão:</span>
<span class="va">$this</span>-><span class="fn">empresaId</span>() <span class="cm">// = Auth::empresaId()</span></pre></div>
    <div class="d-warn"><i class="bi bi-exclamation-triangle-fill me-1"></i>Nunca faça queries sem o filtro <code>empresa_id</code> em tabelas multitenant. Isso exporia dados de outras empresas.</div>
  </div>

  <div class="d-section" id="banco-convencoes">
    <div class="d-h2">Convenções do Banco</div>
    <table class="d-table">
      <thead><tr><th>Convenção</th><th>Exemplo</th></tr></thead>
      <tbody>
        <?php foreach([
          ['Nomes de tabela','snake_case plural: ordens_servico, os_status'],
          ['PK','id INT AUTO_INCREMENT PRIMARY KEY'],
          ['FK','empresa_id, cliente_id, os_id (sempre com sufixo _id)'],
          ['Timestamps','criado_em DATETIME, atualizado_em DATETIME'],
          ['Booleanos','ativo TINYINT(1) DEFAULT 1'],
          ['Valores monetários','DECIMAL(10,2) — nunca FLOAT'],
          ['Charset','utf8mb4 / utf8mb4_unicode_ci em todas as tabelas'],
        ] as[$c,$e]):?>
        <tr><td><?=$c?></td><td style="color:#94a3b8;font-family:inherit"><?=$e?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <div class="d-section" id="ctrl-base">
    <div class="d-h2">Usando o Controller Base</div>
    <div class="d-code"><pre>
<span class="kw">namespace</span> <span class="cl">App\Controllers</span>;

<span class="kw">use</span> <span class="cl">App\Core\Controller</span>;
<span class="kw">use</span> <span class="cl">App\Core\DB</span>;

<span class="kw">class</span> <span class="cl">MeuController</span> <span class="kw">extends</span> <span class="cl">Controller</span>
{
    <span class="kw">public function</span> <span class="fn">index</span>(): <span class="kw">void</span>
    {
        <span class="cm">// Query com PDO</span>
        <span class="va">$db</span>  = <span class="cl">DB</span>::<span class="fn">pdo</span>();
        <span class="va">$stmt</span> = <span class="va">$db</span>-><span class="fn">prepare</span>(<span class="st">"SELECT * FROM minha_tabela WHERE empresa_id = ?"</span>);
        <span class="va">$stmt</span>-><span class="fn">execute</span>([<span class="cl">Auth</span>::<span class="fn">empresaId</span>()]);
        <span class="va">$dados</span> = <span class="va">$stmt</span>-><span class="fn">fetchAll</span>();

        <span class="cm">// Renderizar view</span>
        <span class="va">$this</span>-><span class="fn">view</span>(<span class="st">'modulo.index'</span>, [<span class="st">'dados'</span> => <span class="va">$dados</span>], <span class="st">'main'</span>);
    }

    <span class="kw">public function</span> <span class="fn">salvar</span>(): <span class="kw">void</span>
    {
        <span class="kw">if</span> (!<span class="fn">csrf_verify</span>()) {
            <span class="va">$this</span>-><span class="fn">flash</span>(<span class="st">'error'</span>, <span class="st">'Token inválido.'</span>);
            <span class="va">$this</span>-><span class="fn">redirect</span>(<span class="fn">url</span>(<span class="st">'/modulo'</span>));
        }
        <span class="va">$nome</span> = <span class="va">$this</span>-><span class="fn">post</span>(<span class="st">'nome'</span>, <span class="st">''</span>);
        <span class="cm">// ... salvar no banco</span>
        <span class="va">$this</span>-><span class="fn">flash</span>(<span class="st">'success'</span>, <span class="st">'Salvo com sucesso!'</span>);
        <span class="va">$this</span>-><span class="fn">redirect</span>(<span class="fn">url</span>(<span class="st">'/modulo'</span>));
    }
}</pre></div>
  </div>

  <div class="d-section" id="ctrl-criar">
    <div class="d-h2">Criar um Novo Módulo</div>
    <p class="d-p">Para adicionar um novo módulo ao sistema, siga estes passos:</p>
    <div class="d-code"><pre>
<span class="cm"># 1. Criar o controller</span>
<span class="co">app/Controllers/MeuModuloController.php</span>

<span class="cm"># 2. Criar a view</span>
<span class="co">app/Views/meu_modulo/index.php</span>

<span class="cm"># 3. Registrar a rota em routes/web.php</span>
<span class="va">$router</span>-><span class="fn">get</span>(<span class="st">'/meu-modulo'</span>, <span class="st">'MeuModuloController@index'</span>, [<span class="st">'AuthMiddleware'</span>]);

<span class="cm"># 4. Adicionar link na sidebar (app/Views/layouts/main.php)</span></pre></div>
  </div>

  <div class="d-section" id="views-layouts">
    <div class="d-h2">Layouts Disponíveis</div>
    <table class="d-table">
      <thead><tr><th>Layout</th><th>Uso</th></tr></thead>
      <tbody>
        <?php foreach([
          ['main','Sistema principal com sidebar e topbar (requer login).'],
          ['auth','Tela de login — centralizado, sem sidebar.'],
          ['landing','Site público (landing page, ajuda, docs).'],
          ['master','Painel master admin com tema dark.'],
          ['setup','Onboarding pós-cadastro.'],
          ['print','Layout de impressão sem sidebar.'],
        ] as[$l,$d]):?>
        <tr><td><?=$l?></td><td style="color:#94a3b8;font-family:inherit"><?=$d?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
    <p class="d-p">Para usar um layout, passe o terceiro parâmetro no <code style="color:#fb923c">$this->view()</code>:</p>
    <div class="d-code"><pre>
<span class="va">$this</span>-><span class="fn">view</span>(<span class="st">'minha.view'</span>, <span class="va">$dados</span>, <span class="st">'landing'</span>);</pre></div>
  </div>

  <div class="d-section" id="views-helpers">
    <div class="d-h2">Helpers de View (functions.php)</div>
    <table class="d-table">
      <thead><tr><th>Função</th><th>Uso</th></tr></thead>
      <tbody>
        <?php foreach([
          ['e($str)','htmlspecialchars seguro — use em todo output de variáveis.'],
          ['url($path)','Gera URL absoluta com base em config/app.php.'],
          ['flash($key, $msg)','Armazena ou recupera mensagem de flash na sessão.'],
          ['csrf_field()','Retorna o campo hidden com token CSRF.'],
          ['csrf_verify()','Valida o token CSRF do POST atual.'],
          ['only_numbers($str)','Remove tudo que não for dígito (CPF, telefone etc.).'],
        ] as[$f,$d]):?>
        <tr><td><?=$f?></td><td style="color:#94a3b8;font-family:inherit"><?=$d?></td></tr>
        <?php endforeach;?>
      </tbody>
    </table>
  </div>

  <div class="d-section" id="auth-sessao">
    <div class="d-h2">Autenticação e Sessão</div>
    <div class="d-code"><pre>
<span class="cm">// Verificar se está logado</span>
<span class="cl">Auth</span>::<span class="fn">check</span>();          <span class="cm">// bool</span>

<span class="cm">// Dados do usuário logado</span>
<span class="cl">Auth</span>::<span class="fn">user</span>();           <span class="cm">// array</span>
<span class="cl">Auth</span>::<span class="fn">empresaId</span>();     <span class="cm">// int — ID da empresa do usuário</span>

<span class="cm">// Login / Logout</span>
<span class="cl">Auth</span>::<span class="fn">login</span>(<span class="va">$usuario</span>, <span class="va">$permissoes</span>);
<span class="cl">Auth</span>::<span class="fn">logout</span>();</pre></div>
  </div>

  <div class="d-section" id="auth-google">
    <div class="d-h2">OAuth Google</div>
    <p class="d-p">Configure as credenciais em <code style="color:#fb923c">config/google.php</code>. O fluxo é:</p>
    <div class="d-code"><pre>
<span class="cm"># 1. Usuário clica em "Entrar com Google"</span>
GET /auth/google  →  GoogleAuthController::redirectToGoogle()
    <span class="cm"># Redireciona para accounts.google.com com state aleatório</span>

<span class="cm"># 2. Google redireciona de volta com code</span>
GET /auth/google/callback  →  GoogleAuthController::callback()
    <span class="cm"># Troca code por access_token</span>
    <span class="cm"># Busca userinfo (email, name, sub)</span>
    <span class="cm"># Se usuário existe → login direto</span>
    <span class="cm"># Se não existe → redireciona para /cadastrar?via=google</span></pre></div>
    <div class="d-warn"><i class="bi bi-exclamation-triangle-fill me-1"></i>Adicione <code>http://[seu-dominio]/auth/google/callback</code> nas URIs autorizadas no Google Cloud Console.</div>
  </div>

  <div class="d-section" id="auth-csrf">
    <div class="d-h2">Proteção CSRF</div>
    <p class="d-p">Todo formulário POST deve incluir o token CSRF. O token é validado no início de cada action POST.</p>
    <div class="d-code"><pre>
<span class="cm">&lt;!-- Na view --&gt;</span>
&lt;<span class="kw">form</span> method="POST"&gt;
  &lt;?= <span class="fn">csrf_field</span>() ?&gt;
  <span class="cm">&lt;!-- campos --&gt;</span>
&lt;/<span class="kw">form</span>&gt;

<span class="cm">// No controller</span>
<span class="kw">if</span> (!<span class="fn">csrf_verify</span>()) {
    <span class="va">$this</span>-><span class="fn">flash</span>(<span class="st">'error'</span>, <span class="st">'Token inválido.'</span>);
    <span class="va">$this</span>-><span class="fn">redirect</span>(<span class="fn">url</span>(<span class="st">'/destino'</span>));
}</pre></div>
  </div>

  <div class="d-section" id="rotas-web">
    <div class="d-h2">Rotas (web.php)</div>
    <p class="d-p">Todas as rotas ficam em <code style="color:#fb923c">routes/web.php</code>. O arquivo é incluído pelo front controller (<code style="color:#fb923c">public/index.php</code>) com a instância do router disponível como <code style="color:#fb923c">$router</code>.</p>
  </div>

  <div class="d-section" id="rotas-params">
    <div class="d-h2">Parâmetros Dinâmicos</div>
    <div class="d-code"><pre>
<span class="cm">// Definição</span>
<span class="va">$router</span>-><span class="fn">get</span>(<span class="st">'/os/{id}'</span>, <span class="st">'OrdemServicoController@ver'</span>);

<span class="cm">// No controller, o parâmetro é injetado automaticamente</span>
<span class="kw">public function</span> <span class="fn">ver</span>(<span class="nu">int</span> <span class="va">$id</span>): <span class="kw">void</span>
{
    <span class="cm">// $id já está disponível</span>
}</pre></div>
  </div>

  <div class="d-section" id="deploy-laragon">
    <div class="d-h2">Ambiente de Desenvolvimento (Laragon)</div>
    <div class="d-code"><pre>
<span class="cm"># 1. Clonar/copiar o projeto para:</span>
C:\laragon\www\oficina-eletro\

<span class="cm"># 2. O Laragon cria automaticamente o virtual host:</span>
http://oficina-eletro.test

<span class="cm"># 3. Criar o banco de dados:</span>
<span class="cm"># Acesse HeidiSQL ou phpMyAdmin e crie: oficina_eletro</span>

<span class="cm"># 4. Executar as migrations em ordem:</span>
database/migrations/*.sql

<span class="cm"># 5. Configurar:</span>
config/app.php      <span class="cm"># url => 'http://oficina-eletro.test'</span>
config/database.php <span class="cm"># credenciais MySQL do Laragon</span>

<span class="cm"># 6. Acessar o setup:</span>
http://oficina-eletro.test/install</pre></div>
  </div>

  <div class="d-section" id="deploy-prod">
    <div class="d-h2">Deploy em Produção</div>
    <div class="d-code"><pre>
<span class="cm"># Apache — .htaccess no public/</span>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^ index.php [QSA,L]

<span class="cm"># Nginx</span>
location / {
    try_files <span class="va">$uri</span> <span class="va">$uri</span>/ /index.php?<span class="va">$query_string</span>;
}

<span class="cm"># config/app.php em produção</span>
<span class="st">'url'</span>   => <span class="st">'https://seudominio.com.br'</span>,
<span class="st">'debug'</span> => <span class="kw">false</span>,

<span class="cm"># Permissões de pasta</span>
chmod 755 storage/uploads
chmod 755 storage/logs</pre></div>
    <div class="d-warn"><i class="bi bi-exclamation-triangle-fill me-1"></i>Em produção, aponte o Document Root do servidor para a pasta <code>public/</code>, não para a raiz do projeto.</div>
  </div>

</div><!-- /docs-body -->
</div><!-- /docs-wrap -->

<script>
const sections=document.querySelectorAll('.d-section');
const links=document.querySelectorAll('.d-nav-link');
const observer=new IntersectionObserver(entries=>{
  entries.forEach(e=>{
    if(e.isIntersecting){
      links.forEach(l=>l.classList.remove('active'));
      const a=document.querySelector('.d-nav-link[href="#'+e.target.id+'"]');
      if(a)a.classList.add('active');
    }
  });
},{threshold:.2,rootMargin:'-60px 0px -60% 0px'});
sections.forEach(s=>observer.observe(s));
links.forEach(l=>{
  l.addEventListener('click',e=>{
    e.preventDefault();
    const t=document.querySelector(l.getAttribute('href'));
    if(t)t.scrollIntoView({behavior:'smooth',block:'start'});
  });
});
</script>
