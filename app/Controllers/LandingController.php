<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class LandingController extends Controller
{
    /**
     * Fase "Em Breve": o cadastro aberto do sistema completo fica FECHADO.
     * Só clientes atuais entram pelo login. Leads chegam pela lista de espera,
     * pela reivindicação do diretório e pelo fórum. Vire para true no lançamento.
     */
    private const CADASTRO_ABERTO = true;

    public function index(): void
    {
        $this->view('landing.index', [
            'tituloFull' => 'FixaOS — Sistema de Gestão para Assistência Técnica | Ordem de Serviço, PDV e Diretório',
            'metaDesc'   => 'Sistema completo para assistência técnica: ordens de serviço, PDV, clientes, estoque, financeiro, agenda e página no Google. Teste grátis 15 dias, sem cartão.',
        ], 'landing');
    }

    /**
     * Lista de espera do lançamento ("Avise-me quando lançar").
     * Captura só o e-mail (com honeypot anti-bot) e guarda em lista_espera.
     */
    public function listaEspera(): void
    {
        if (!csrf_verify()) { $this->redirect(url('/')); }
        // Honeypot: bots preenchem o campo escondido "website".
        if (trim((string) $this->post('website', '')) !== '') { $this->redirect(url('/')); }

        $email = trim(mb_strtolower($this->post('email', '')));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Informe um e-mail válido.');
            $this->redirect(url('/#lista-espera'));
        }

        $db = DB::pdo();
        $db->exec("CREATE TABLE IF NOT EXISTS lista_espera (
          id INT AUTO_INCREMENT PRIMARY KEY,
          email VARCHAR(150) NOT NULL,
          origem VARCHAR(40) NOT NULL DEFAULT 'landing',
          convidado TINYINT NOT NULL DEFAULT 0,
          convidado_em TIMESTAMP NULL,
          criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uq_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        // INSERT IGNORE: e-mail repetido não gera erro nem duplica.
        $db->prepare("INSERT IGNORE INTO lista_espera (email, origem) VALUES (?, 'landing')")
           ->execute([mb_substr($email, 0, 150)]);

        $this->redirect(url('/?avisado=1#lista-espera'));
    }

    public function cadastro(): void
    {
        // Cadastro de diretório (grátis) segue por fluxo próprio, mesmo com o sistema fechado.
        if (($_GET['tipo'] ?? '') === 'diretorio') {
            $this->redirect(url('/diretorio/cadastrar'));
        }
        // Fase "Em Breve": cadastro aberto fechado — manda pra lista de espera.
        if (!self::CADASTRO_ABERTO) {
            $this->flash('info', 'O cadastro aberto ainda não está liberado. Entre na lista de espera que avisamos você no lançamento! 🚀');
            $this->redirect(url('/#lista-espera'));
        }
        $planos = require BASE_PATH . '/config/planos.php';
        $planoAutonomo = null;
        foreach ($planos['planos'] as $p) {
            if ($p['codigo'] === 'autonomo') { $planoAutonomo = $p; break; }
        }
        $vagas = ($planoAutonomo && !empty($planoAutonomo['vagas_promo'])) ? plano_vagas_info($planoAutonomo) : null;

        $this->view('landing.cadastro', ['planoAutonomo' => $planoAutonomo, 'vagas' => $vagas], 'landing');
    }

    public function privacidade(): void
    {
        $this->view('landing.privacidade', ['titulo' => 'Política de Privacidade'], 'landing');
    }

    public function termos(): void
    {
        $this->view('landing.termos', ['titulo' => 'Termos de Uso'], 'landing');
    }

    /**
     * ads.txt do Google AdSense (Authorized Digital Sellers).
     * Autoriza a conta a vender o inventário do site e serve como verificacao de propriedade.
     */
    public function adsTxt(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "google.com, pub-2477643924516118, DIRECT, f08c47fec0942fa0\n";
        exit;
    }

    public function registrar(): void
    {
        // Fase "Em Breve": bloqueia criação de conta completa mesmo via POST direto.
        if (!self::CADASTRO_ABERTO) {
            $this->flash('info', 'O cadastro aberto ainda não está liberado. Entre na lista de espera! 🚀');
            $this->redirect(url('/#lista-espera'));
        }

        if (!csrf_verify()) {
            $this->flash('error', 'Token inválido.');
            $this->redirect(url('/cadastrar'));
        }

        // Honeypot anti-bot: campo escondido que só robôs preenchem.
        // Se veio preenchido, descarta silenciosamente (sem criar nada, sem avisar o bot).
        if (trim((string) $this->post('website', '')) !== '') {
            $this->redirect(url('/'));
        }

        $nome     = trim($this->post('nome_fantasia', ''));
        $razao    = trim($this->post('razao_social', '')) ?: $nome;
        $cnpj     = only_numbers($this->post('cnpj', ''));
        $email    = trim($this->post('email', ''));
        $telefone = $this->post('telefone', '');
        $cidade   = $this->post('cidade', '');
        $uf       = $this->post('uf', '');
        $senha    = $this->post('senha', '');
        $confirm  = $this->post('senha_confirm', '');
        $admNome  = trim($this->post('adm_nome', ''));
        $googleId = trim($this->post('google_id', ''));
        $viaGoogle = !empty($googleId);
        $tipoConta = $this->post('tipo_conta') === 'diretorio' ? 'diretorio' : 'completo';

        // Validações
        if (!$nome || !$email || !$admNome) {
            $this->flash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect(url('/cadastrar'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'E-mail inválido.');
            $this->redirect(url('/cadastrar'));
        }
        if (!$viaGoogle) {
            if (strlen($senha) < 6) {
                $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.');
                $this->redirect(url('/cadastrar'));
            }
            if ($senha !== $confirm) {
                $this->flash('error', 'As senhas não conferem.');
                $this->redirect(url('/cadastrar'));
            }
        }

        $db = DB::pdo();

        // Verificar e-mail duplicado
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $stmtCheck->execute([$email]);
        if ((int) $stmtCheck->fetchColumn() > 0) {
            $this->flash('error', 'Este e-mail já está cadastrado. Faça login ou use outro e-mail.');
            $this->redirect(url('/cadastrar'));
        }

        // Criar empresa
        // O campo do cadastro é "Telefone / WhatsApp" (um só) — grava o mesmo número
        // nas duas colunas para que a tela de edição da empresa já venha preenchida.
        $stmtE = $db->prepare(
            "INSERT INTO empresas (razao_social, nome_fantasia, cnpj, email, telefone, whatsapp, cidade, uf, tipo_conta, plano, trial_ate, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'profissional', DATE_ADD(NOW(), INTERVAL 15 DAY), 1)"
        );
        $stmtE->execute([$razao, $nome, $cnpj ?: null, $email, $telefone, $telefone, $cidade, $uf, $tipoConta]);
        $empresaId = (int) $db->lastInsertId();

        // Criar usuário admin
        $senhaHash = $viaGoogle
            ? password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT)
            : password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        // Conta via Google já vem com o e-mail confirmado pelo próprio Google;
        // conta por senha precisa confirmar o e-mail (token válido por 24h).
        $tokenVerificacao = $viaGoogle ? null : bin2hex(random_bytes(32));
        $stmtU = $db->prepare(
            "INSERT INTO usuarios (empresa_id, nome, email, senha, google_id, perfil, ativo, email_verificado, token_verificacao, token_verificacao_expira)
             VALUES (?, ?, ?, ?, ?, 'admin', 1, ?, ?, ?)"
        );
        $stmtU->execute([
            $empresaId, $admNome, $email, $senhaHash, $googleId ?: null,
            $viaGoogle ? 1 : 0,
            $tokenVerificacao,
            $tokenVerificacao ? date('Y-m-d H:i:s', strtotime('+24 hours')) : null,
        ]);

        // Backbone de status NATIVO (bloqueado=1) — esqueleto travado, igual para toda empresa.
        // Só a cor pode ser alterada; nome/tipo/ordem são fixos e não podem ser excluídos.
        // [codigo, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor]
        $statusNativos = [
            ['orcamento',        'Orçamento',        '#0d6efd', '#ffffff', 1, 'aberta',       0, 0],
            ['em_analise',       'Em análise',       '#6f42c1', '#ffffff', 2, 'em_andamento', 1, 0],
            ['aguardando_pecas', 'Aguardando peças', '#fd7e14', '#ffffff', 3, 'aguardando',   1, 0],
            ['pronto',           'Prontos',          '#198754', '#ffffff', 4, 'concluida',    1, 0],
            ['fechado',          'Fechado',          '#20c997', '#ffffff', 5, 'entregue',     0, 0],
            ['sem_conserto',     'Sem Conserto',     '#dc3545', '#ffffff', 7, 'cancelada',    1, 0],
        ];
        $stmtS = $db->prepare(
            "INSERT INTO os_status (empresa_id, codigo, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor, bloqueado)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1)"
        );
        foreach ($statusNativos as $s) $stmtS->execute([$empresaId, ...$s]);

        // Um status de trabalho útil (editável/removível) — conveniência para oficinas
        $db->prepare("INSERT INTO os_status (empresa_id, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor, bloqueado)
                      VALUES (?, 'Em Reparo', '#0dcaf0', '#ffffff', 8, 'em_andamento', 1, 0, 0)")
           ->execute([$empresaId]);

        // Acessório padrão (etiqueta "sem acessórios" — protegida e exclusiva)
        $db->prepare("INSERT IGNORE INTO equip_acessorios (empresa_id, nome) VALUES (?, 'sem acessórios')")
           ->execute([$empresaId]);

        // Estágios CRM
        $estagios = [
            ['Primeiro Contato', '#0d6efd', 1, 'aberto'],
            ['Orçamento Enviado', '#ffc107', 2, 'aberto'],
            ['Em Negociação',    '#fd7e14', 3, 'aberto'],
            ['Ganho',            '#198754', 4, 'ganho'],
            ['Perdido',          '#dc3545', 5, 'perdido'],
        ];
        $stmtCRM = $db->prepare("INSERT INTO crm_estagios (empresa_id, nome, cor, ordem, tipo) VALUES (?, ?, ?, ?, ?)");
        foreach ($estagios as $e) $stmtCRM->execute([$empresaId, ...$e]);

        // Categorias equipamento
        $cats = [
            ['Celular/Smartphone','bi-phone'],['Tablet','bi-tablet'],['Notebook','bi-laptop'],
            ['Desktop/PC','bi-pc-display'],['Televisão','bi-tv'],['Monitor','bi-display'],
            ['Geladeira/Freezer','bi-snow'],['Máquina de Lavar','bi-water'],
            ['Micro-ondas','bi-box2'],['Ar Condicionado','bi-wind'],
            ['Impressora','bi-printer'],['Videogame','bi-joystick'],['Outro','bi-tools'],
        ];
        $stmtCat = $db->prepare("INSERT INTO categorias_equipamento (empresa_id, nome, icone) VALUES (?, ?, ?)");
        foreach ($cats as $c) $stmtCat->execute([$empresaId, $c[0], $c[1]]);

        // Conta caixa
        $db->prepare("INSERT INTO fin_contas (empresa_id, nome, tipo) VALUES (?, 'Caixa', 'caixa')")
           ->execute([$empresaId]);

        // Categoria financeira padrão: Serviços (receita) — usada nos lançamentos de fechamento de OS
        $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'receita', 'Serviços', '#198754')")
           ->execute([$empresaId]);

        // Textos padrão (baseados no modelo da Eletroli) — a empresa deve revisar/adaptar (ver aviso na tela de configurações)
        $textoEntradaPadrao = <<<'HTML'
<p><b>Política de Devolução:</b> A devolução do equipamento, reparado ou não, está condicionada à comprovação da titularidade através da apresentação da Ordem de Serviço. Aceitamos tanto a versão impressa quanto o arquivo digital (PDF) original emitido pela nossa assistência. Na ausência de ambos, o titular deverá apresentar documento oficial com foto. A retirada por terceiros, na falta da Ordem de Serviço (física ou digital), exige documento de identificação do portador e autorização expressa do titular.</p>
HTML;

        $textoGarantiaPadrao = <<<'HTML'
<h3><b>TERMOS DE GARANTIA E VISITA TÉCNICA</b></h3><p><b>1. Garantia:</b> Cobertura exclusiva para os serviços executados e peças substituídas. A garantia não cobre danos decorrentes de mau uso, oscilações elétricas, agentes da natureza, transporte inadequado ou intervenções de terceiros.</p><p><b>2. Taxa de Visita (R$ 150,00):</b> Caso o técnico seja acionado em garantia e o problema seja constatado como externo ao serviço realizado, será cobrada uma taxa de <b>R$ 150,00</b> por visita. Exemplos de situações passíveis de cobrança:</p><ul><li><p>Cabos de força ou sinal desconectados/defeituosos;</p></li><li><p>Defeitos no aparelho ou sinal da prestadora de TV;</p></li><li><p>Falhas no controle remoto (incluindo falta de pilhas).</p></li></ul><p><b>3. Retirada:</b> A devolução do equipamento requer a apresentação desta Ordem de Serviço (física ou digital). Em caso de extravio, a retirada por terceiros exige documento de identificação e autorização por escrito do titular.</p>
HTML;

        // Configurações
        $configs = [
            ['os_prefixo','OS'],['os_digitos','6'],['garantia_padrao_dias','90'],
            ['prazo_retirada_dias','30'],['comissao_tecnico_percentual','20'],
            ['texto_entrada_equipamento',$textoEntradaPadrao],
            ['texto_garantia',$textoGarantiaPadrao],
        ];
        $stmtCfg = $db->prepare("INSERT INTO configuracoes (empresa_id, chave, valor) VALUES (?, ?, ?)");
        foreach ($configs as $c) $stmtCfg->execute([$empresaId, $c[0], $c[1]]);

        // Buscar o usuário recém-criado e fazer login automático
        $stmtLogin = $db->prepare(
            "SELECT u.*, e.nome_fantasia AS empresa_nome FROM usuarios u
             JOIN empresas e ON e.id = u.empresa_id
             WHERE u.empresa_id = ? AND u.email = ? LIMIT 1"
        );
        $stmtLogin->execute([$empresaId, $email]);
        $novoUsuario = $stmtLogin->fetch();

        if ($novoUsuario) {
            \App\Core\Auth::login($novoUsuario, []);
        }

        // Conceder 10 créditos de boas-vindas no Marketplace
        $db->prepare(
            "INSERT INTO marketplace_creditos (empresa_id, saldo_creditos)
             VALUES (?, 10)
             ON DUPLICATE KEY UPDATE saldo_creditos = saldo_creditos + 10"
        )->execute([$empresaId]);

        $db->prepare(
            "INSERT INTO marketplace_historico_creditos (empresa_id, tipo, quantidade, justificativa)
             VALUES (?, 'compra', 10, 'Bônus de boas-vindas — 10 créditos gratuitos')"
        )->execute([$empresaId]);

        // Mensagem de boas-vindas via WhatsApp (best-effort — nunca quebra o cadastro)
        if (!empty($telefone)) {
            try {
                \App\Services\WhatsAppService::boasVindas($telefone, $nome);
            } catch (\Throwable $e) {
                // silencioso: falha no WhatsApp não impede o cadastro
            }
        }

        // E-mail de boas-vindas (best-effort — nunca quebra o cadastro)
        try {
            \App\Services\EmailService::boasVindas($email, $nome);
        } catch (\Throwable $e) {
            // silencioso: falha no e-mail não impede o cadastro
        }

        // E-mail de confirmação (só pra quem se cadastrou com senha — Google já confirma)
        if ($tokenVerificacao) {
            try {
                $base = rtrim((require BASE_PATH . '/config/app.php')['url'], '/');
                $link = $base . '/verificar-email/' . $tokenVerificacao;
                \App\Services\EmailService::confirmarEmail($email, $admNome, $link);
            } catch (\Throwable $e) {
                // silencioso: falha no e-mail não impede o cadastro
            }
        }

        // Redireciona para onboarding em vez do login
        $this->redirect(url('/setup'));
    }
}
