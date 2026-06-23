<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class LandingController extends Controller
{
    public function index(): void
    {
        $this->view('landing.index', [], 'landing');
    }

    public function cadastro(): void
    {
        $this->view('landing.cadastro', [], 'landing');
    }

    public function registrar(): void
    {
        if (!csrf_verify()) {
            $this->flash('error', 'Token inválido.');
            $this->redirect(url('/cadastrar'));
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

        // Validações
        if (!$nome || !$email || !$senha || !$admNome) {
            $this->flash('error', 'Preencha todos os campos obrigatórios.');
            $this->redirect(url('/cadastrar'));
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'E-mail inválido.');
            $this->redirect(url('/cadastrar'));
        }
        if (strlen($senha) < 6) {
            $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.');
            $this->redirect(url('/cadastrar'));
        }
        if ($senha !== $confirm) {
            $this->flash('error', 'As senhas não conferem.');
            $this->redirect(url('/cadastrar'));
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
        $stmtE = $db->prepare(
            "INSERT INTO empresas (razao_social, nome_fantasia, cnpj, email, telefone, cidade, uf, plano, trial_ate, ativo)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'profissional', DATE_ADD(NOW(), INTERVAL 30 DAY), 1)"
        );
        $stmtE->execute([$razao, $nome, $cnpj ?: null, $email, $telefone, $cidade, $uf]);
        $empresaId = (int) $db->lastInsertId();

        // Criar usuário admin
        $stmtU = $db->prepare(
            "INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, ativo)
             VALUES (?, ?, ?, ?, 'admin', 1)"
        );
        $stmtU->execute([$empresaId, $admNome, $email, password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12])]);

        // Status OS padrão
        $statusPadrao = [
            ['Aguardando Diagnóstico', '#6c757d', 1, 'aberta'],
            ['Em Diagnóstico',         '#0dcaf0', 2, 'em_andamento'],
            ['Aguardando Aprovação',   '#ffc107', 3, 'aguardando'],
            ['Aguardando Peças',       '#fd7e14', 4, 'aguardando'],
            ['Em Reparo',              '#0d6efd', 5, 'em_andamento'],
            ['Pronto para Retirada',   '#198754', 6, 'concluida'],
            ['Entregue',               '#20c997', 7, 'entregue'],
            ['Cancelado',              '#dc3545', 8, 'cancelada'],
        ];
        $stmtS = $db->prepare("INSERT INTO os_status (empresa_id, nome, cor, ordem, tipo) VALUES (?, ?, ?, ?, ?)");
        foreach ($statusPadrao as $s) $stmtS->execute([$empresaId, ...$s]);

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

        // Configurações
        $configs = [
            ['os_prefixo','OS'],['os_digitos','6'],['garantia_padrao_dias','90'],
            ['prazo_retirada_dias','30'],['comissao_tecnico_percentual','20'],
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

        // Redireciona para onboarding em vez do login
        $this->redirect(url('/setup'));
    }
}
