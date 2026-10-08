<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\Fixa\AssinaturaService;

/**
 * Cadastro PRÓPRIO e simples do Carteira Fixa standalone (financeiro pessoal vendido à parte,
 * pra quem nunca foi cliente de assistência técnica) — decisão já tomada com o usuário:
 * "cadastro próprio e simples" em vez de reaproveitar o fluxo de cadastro de empresa completo.
 * Mesmo padrão de DiretorioController::cadastrarSalvar()/cadastroRapidoSalvar() (empresa
 * "casca" criada por baixo + usuário + login automático, tudo numa transação só).
 *
 * IMPORTANTE — cobrança real ainda não liga aqui: o teste de 7 dias começa normalmente
 * (AssinaturaService::criarTeste()), mas não há coleta de cartão/Pix Automático nenhuma — isso
 * depende da escolha do gateway de pagamento, que está PAUSADA (ver conversa). formaPagamento()
 * é só um placeholder explicando isso, pronto pra virar o formulário de verdade quando o
 * gateway for decidido.
 */
class FixaCadastroController extends Controller
{
    public function cadastrarForm(): void
    {
        if (Auth::check()) { $this->redirect(url('/financeiro-pessoal')); }

        $cfg = AssinaturaService::config();
        $this->view('fixa_cadastro.cadastrar', [
            'titulo'   => 'Carteira Fixa — cadastro',
            'noindex'  => false,
            'planos'   => $cfg['planos'],
            'ciclos'   => $cfg['ciclos'],
            'testeDias' => (int) $cfg['teste_dias'],
        ], 'landing');
    }

    public function cadastrarSalvar(): void
    {
        if (Auth::check()) { $this->redirect(url('/financeiro-pessoal')); }
        $back = url('/carteira-fixa/cadastrar');

        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        // Honeypot anti-bot (mesmo campo/padrão já usado nos cadastros do Diretório).
        if (trim((string) $this->post('website', '')) !== '') { $this->redirect(url('/')); }

        $nome    = trim((string) $this->post('nome', ''));
        $email   = trim((string) $this->post('email', ''));
        $senha   = (string) $this->post('senha', '');
        $confirm = (string) $this->post('senha_confirm', '');
        $plano   = (string) $this->post('plano', '');
        $ciclo   = (string) $this->post('ciclo', 'mensal');

        $cfg = AssinaturaService::config();
        $planosValidos = array_column($cfg['planos'], null, 'codigo');
        $ciclosValidos = $cfg['ciclos'];

        $manterContexto = function () use ($nome, $email, $plano, $ciclo) {
            $_SESSION['carteira_fixa_cadastro_rascunho'] = compact('nome', 'email', 'plano', 'ciclo');
        };

        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Informe seu nome e um e-mail válido.');
            $manterContexto(); $this->redirect($back);
        }
        if (strlen($senha) < 6) {
            $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.');
            $manterContexto(); $this->redirect($back);
        }
        if ($senha !== $confirm) {
            $this->flash('error', 'As senhas não conferem.');
            $manterContexto(); $this->redirect($back);
        }
        if (!isset($planosValidos[$plano])) {
            $this->flash('error', 'Escolha um dos planos do Carteira Fixa.');
            $manterContexto(); $this->redirect($back);
        }
        if (!isset($ciclosValidos[$ciclo])) { $ciclo = 'mensal'; }

        $db = DB::pdo();
        $chk = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $chk->execute([$email]);
        if ((int) $chk->fetchColumn() > 0) {
            $this->flash('error', 'Já existe uma conta com esse e-mail. Faça login pra acessar.');
            $this->redirect(url('/login'));
        }

        $senhaHash = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);

        $db->beginTransaction();
        try {
            // Empresa "casca" — nunca aparece no Diretório (listagem_publica=0, sem slug/
            // nome_fantasia) nem é tratada como assistência técnica de verdade (tipo_conta=
            // 'fixa', ver Auth::soFixa()/AuthMiddleware). razao_social guarda o nome da PESSOA
            // que se cadastrou, mesmo padrão já usado pra conta tipo_conta='diretorio'.
            $db->prepare(
                "INSERT INTO empresas (razao_social, email, tipo_conta, reivindicada, listagem_publica, ativo)
                 VALUES (?, ?, 'fixa', 0, 0, 1)"
            )->execute([mb_substr($nome, 0, 150), mb_substr($email, 0, 100)]);
            $empresaId = (int) $db->lastInsertId();

            $db->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, ativo) VALUES (?, ?, ?, ?, 'admin', 1)")
                ->execute([$empresaId, mb_substr($nome, 0, 100), mb_substr($email, 0, 100), $senhaHash]);
            $usuarioId = (int) $db->lastInsertId();

            AssinaturaService::criarTeste($db, $usuarioId, $plano, $ciclo);

            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            error_log('FixaCadastroController::cadastrarSalvar — ' . $e->getMessage());
            $this->flash('error', 'Não foi possível concluir agora. Tente novamente em instantes.');
            $manterContexto(); $this->redirect($back);
        }

        try {
            \App\Services\EmailService::send(
                'suporte@fixaos.com.br', 'FixaOS',
                'Nova conta Carteira Fixa standalone — ' . htmlspecialchars($nome),
                '<p>Alguém se cadastrou direto pro Carteira Fixa (sem passar por assistência técnica):</p>'
                . '<ul><li><b>Nome:</b> ' . htmlspecialchars($nome) . '</li>'
                . '<li><b>E-mail:</b> ' . htmlspecialchars($email) . '</li>'
                . '<li><b>Plano:</b> ' . htmlspecialchars($planosValidos[$plano]['nome']) . ' (' . htmlspecialchars($ciclo) . ')</li></ul>'
            );
        } catch (\Throwable $e) { /* best-effort, não derruba o cadastro */ }

        $stmtLogin = $db->prepare("SELECT * FROM usuarios WHERE id = ?");
        $stmtLogin->execute([$usuarioId]);
        if ($novo = $stmtLogin->fetch()) { Auth::login($novo, []); }

        unset($_SESSION['carteira_fixa_cadastro_rascunho']);
        $this->flash('success', 'Conta criada! Seus ' . (int) $cfg['teste_dias'] . ' dias grátis começaram agora.');
        $this->redirect(Auth::check() ? url('/carteira-fixa/forma-pagamento') : url('/login'));
    }

    /**
     * Placeholder — fica pronto pra virar a coleta de cartão/Pix Automático de verdade assim
     * que o gateway for escolhido (ver nota no topo da classe). Por ora só confirma que o teste
     * já começou e deixa seguir pro produto.
     */
    public function formaPagamento(): void
    {
        $db = DB::pdo();
        $assinatura = AssinaturaService::doUsuario($db, Auth::id());

        $this->view('fixa_cadastro.forma_pagamento', [
            'titulo'     => 'Carteira Fixa — forma de pagamento',
            'noindex'    => true,
            'assinatura' => $assinatura,
        ], 'landing');
    }
}
