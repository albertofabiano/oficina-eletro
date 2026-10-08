<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\Fixa\AssinaturaService;
use App\Services\InfinitePayService;

/**
 * Cadastro PRÓPRIO e simples do Carteira Fixa standalone (financeiro pessoal vendido à parte,
 * pra quem nunca foi cliente de assistência técnica) — decisão já tomada com o usuário:
 * "cadastro próprio e simples" em vez de reaproveitar o fluxo de cadastro de empresa completo.
 * Mesmo padrão de DiretorioController::cadastrarSalvar()/cadastroRapidoSalvar() (empresa
 * "casca" criada por baixo + usuário + login automático, tudo numa transação só).
 *
 * O cadastro em si NUNCA pede forma de pagamento — são sempre os `teste_dias` da config (hoje
 * 7), nunca mais que isso, em nenhum fluxo (ver AssinaturaService::criarTeste()). Pagar de
 * verdade é uma ação separada e explícita (assinar()/upgrade()), só depois do teste já ter
 * começado — reaproveita o MESMO motor de checkout InfinitePay do plano completo
 * (InfinitePayService + tabela `cobrancas`, ramificado por `tipo='fixa'` em
 * PagamentoController::webhook()), nunca cartão/débito recorrente de verdade — a InfinitePay
 * não oferece isso hoje, então é sempre link de checkout avulso por ciclo, igual o resto do
 * sistema.
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
        $googleId  = trim((string) $this->post('google_id', ''));
        $viaGoogle = $googleId !== '';

        $cfg = AssinaturaService::config();
        $planosValidos = array_column($cfg['planos'], null, 'codigo');
        $ciclosValidos = $cfg['ciclos'];

        // Preserva o vínculo Google (não só nome/e-mail/plano) se alguma validação falhar —
        // senão a pessoa precisaria clicar em "Continuar com Google" de novo.
        $manterContexto = function () use ($nome, $email, $plano, $ciclo, $viaGoogle, $googleId) {
            if ($viaGoogle) { $_SESSION['google_signup'] = ['google_id' => $googleId, 'email' => $email, 'nome' => $nome]; }
            $_SESSION['carteira_fixa_cadastro_rascunho'] = compact('nome', 'email', 'plano', 'ciclo');
        };

        if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->flash('error', 'Informe seu nome e um e-mail válido.');
            $manterContexto(); $this->redirect($back);
        }
        if (!$viaGoogle) {
            if (strlen($senha) < 6) { $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.'); $manterContexto(); $this->redirect($back); }
            if ($senha !== $confirm) { $this->flash('error', 'As senhas não conferem.'); $manterContexto(); $this->redirect($back); }
        } elseif (strlen($senha) < 6) {
            $senha = bin2hex(random_bytes(16)); // conta criada via Google nunca usa senha própria — hash forte só pra preencher a coluna
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

            $db->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, google_id, perfil, ativo) VALUES (?, ?, ?, ?, ?, 'admin', 1)")
                ->execute([$empresaId, mb_substr($nome, 0, 100), mb_substr($email, 0, 100), $senhaHash, ($viaGoogle ? $googleId : null)]);
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

    /** Confirma que o teste já começou e mostra os ciclos de pagamento (ver assinar()) — a
     *  pessoa pode usar o produto normalmente durante o teste sem escolher nada aqui ainda. */
    public function formaPagamento(): void
    {
        $db = DB::pdo();
        $assinatura = AssinaturaService::doUsuario($db, Auth::id());
        // Sem assinatura standalone nenhuma (ex.: conta de empresa com Fixa liberado de graça
        // pelo plano — nunca tem linha em fixa_assinaturas) não há nada pra pagar aqui.
        if (!$assinatura) { $this->redirect(url('/financeiro-pessoal')); }

        $cfg = AssinaturaService::config();
        $plano = self::planoFixa((string) $assinatura['plano']);

        $this->view('fixa_cadastro.forma_pagamento', [
            'titulo'      => 'Carteira Fixa — forma de pagamento',
            'noindex'     => true,
            'assinatura'  => $assinatura,
            'plano'       => $plano,
            'ciclos'      => $cfg['ciclos'],
            'infinitePayAtivo' => InfinitePayService::ativo(),
        ], 'landing');
    }

    /**
     * Gera a cobrança da assinatura (teste virando pago, ou renovação de um ciclo já ativo) e
     * manda pro checkout da InfinitePay — mesmo padrão de PagamentoController::assinar(), só que
     * pro plano/ciclo que o usuário já escolheu no cadastro (não há seletor de PLANO aqui, só de
     * CICLO — trocar de plano é upgrade(), ação separada).
     */
    public function assinar(string $ciclo): void
    {
        $db = DB::pdo();
        $assinatura = AssinaturaService::doUsuario($db, Auth::id());
        if (!$assinatura) { $this->redirect(url('/carteira-fixa/forma-pagamento')); }

        if (!isset(AssinaturaService::config()['ciclos'][$ciclo])) {
            $this->flash('error', 'Ciclo inválido.');
            $this->redirect(url('/carteira-fixa/forma-pagamento'));
        }
        if (!InfinitePayService::ativo()) {
            $this->flash('error', 'O pagamento online ainda não está ativo. Fale com o suporte para ativar sua assinatura. 🙂');
            $this->redirect(url('/carteira-fixa/forma-pagamento'));
        }

        $link = self::gerarLinkPagamento($db, $this->empresaId(), $assinatura, $ciclo);
        if (!$link) {
            $this->flash('error', 'Não foi possível gerar o pagamento agora. Tente novamente em instantes.');
            $this->redirect(url('/carteira-fixa/forma-pagamento'));
        }

        header('Location: ' . $link);
        exit;
    }

    /**
     * Monta a cobrança + link de checkout da InfinitePay pra uma assinatura do Carteira Fixa
     * standalone — extraído de assinar() pra ser reaproveitado também fora de contexto HTTP
     * (sem sessão), pelo cron de aviso de vencimento (scripts/avisar_teste_fixa_terminando.php),
     * mesmo padrão de PagamentoController::gerarLinkAssinatura() pro plano completo. `$empresaId`
     * vem explícito (não de Auth::empresaId(), que não existe fora de uma sessão) — é a empresa
     * "casca" criada junto do usuário no cadastro (ver cadastrarSalvar()).
     */
    public static function gerarLinkPagamento(\PDO $db, int $empresaId, array $assinatura, string $ciclo): ?string
    {
        $cfg = AssinaturaService::config();
        $ck  = $cfg['ciclos'][$ciclo] ?? null;
        $plano = self::planoFixa((string) $assinatura['plano']);
        if (!$ck || !$plano || !InfinitePayService::ativo()) return null;

        $valor = plano_preco_ciclo((int) $plano['preco_mensal'], $ck);

        // confirmarPagamento() sempre lê o CICLO gravado na própria linha de fixa_assinaturas
        // pra saber quantos dias estender (não recebe ciclo como parâmetro) — se o ciclo pedido
        // aqui for diferente do que já estava na linha, ela precisa refletir isso ANTES de gerar
        // a cobrança, senão a confirmação (quando chegar) estenderia pelo ciclo antigo errado.
        $db->prepare("UPDATE fixa_assinaturas SET ciclo = ?, valor_centavos = ? WHERE id = ?")
            ->execute([$ciclo, $valor, $assinatura['id']]);

        $orderNsu = 'fxf-' . $assinatura['id'] . '-' . time();
        $db->prepare("INSERT INTO cobrancas (empresa_id, tipo, plano, ciclo, dias, valor, order_nsu, status) VALUES (?, 'fixa', ?, ?, ?, ?, ?, 'pendente')")
            ->execute([$empresaId, 'fixa_' . $assinatura['id'], $ciclo, (int) $ck['dias'], $valor, $orderNsu]);
        $cobId = (int) $db->lastInsertId();

        $items = [['description' => 'Carteira Fixa — ' . $plano['nome'] . ' (' . $ck['nome'] . ')', 'quantity' => 1, 'price' => $valor]];
        $link = InfinitePayService::criarLink(
            $orderNsu, $items,
            url('/pagamento/retorno?c=' . $cobId),
            url('/webhook/infinitepay'),
            self::clienteDoUsuario($db, (int) $assinatura['usuario_id'])
        );

        if (!$link) {
            $db->prepare("UPDATE cobrancas SET status='cancelado' WHERE id=?")->execute([$cobId]);
            return null;
        }

        $db->prepare("UPDATE cobrancas SET link_url=? WHERE id=?")->execute([$link, $cobId]);
        return $link;
    }

    /**
     * Upgrade Individual → Diretório no meio do período — cobra só a diferença proporcional
     * (AssinaturaService::valorUpgrade()), nunca o valor cheio do plano novo. Sem diferença a
     * cobrar (já no plano Diretório, ou crédito acumulado cobre tudo), aplica o upgrade na hora
     * sem gerar cobrança nenhuma.
     */
    public function upgrade(): void
    {
        $db = DB::pdo();
        $assinatura = AssinaturaService::doUsuario($db, Auth::id());
        if (!$assinatura) { $this->redirect(url('/carteira-fixa/forma-pagamento')); }
        if ($assinatura['plano'] === 'fixa_diretorio') {
            $this->flash('success', 'Você já está no Carteira Fixa + Diretório.');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        $valor = AssinaturaService::valorUpgrade($assinatura, 'fixa_diretorio');
        if ($valor <= 0) {
            // Crédito acumulado já cobre a diferença inteira — sem cobrança nenhuma.
            AssinaturaService::confirmarUpgrade($db, (int) $assinatura['id'], 'fixa_diretorio');
            $this->flash('success', 'Upgrade pro Carteira Fixa + Diretório aplicado — seu crédito acumulado cobriu a diferença inteira. 🎉');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        if (!InfinitePayService::ativo()) {
            $this->flash('error', 'O pagamento online ainda não está ativo. Fale com o suporte. 🙂');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        $orderNsu = 'fxu-' . $assinatura['id'] . '-' . time();
        $db->prepare("INSERT INTO cobrancas (empresa_id, tipo, plano, ciclo, valor, order_nsu, status) VALUES (?, 'fixa', ?, ?, ?, ?, 'pendente')")
            ->execute([$this->empresaId(), 'fixa_upgrade_' . $assinatura['id'], $assinatura['ciclo'], $valor, $orderNsu]);
        $cobId = (int) $db->lastInsertId();

        $items = [['description' => 'Carteira Fixa — upgrade pra + Diretório (diferença proporcional)', 'quantity' => 1, 'price' => $valor]];
        $link = InfinitePayService::criarLink(
            $orderNsu, $items,
            url('/pagamento/retorno?c=' . $cobId),
            url('/webhook/infinitepay'),
            self::clienteDoUsuario($db, Auth::id())
        );

        if (!$link) {
            $db->prepare("UPDATE cobrancas SET status='cancelado' WHERE id=?")->execute([$cobId]);
            $this->flash('error', 'Não foi possível gerar o pagamento agora. Tente novamente em instantes.');
            $this->redirect(url('/financeiro-pessoal/configuracoes'));
        }

        $db->prepare("UPDATE cobrancas SET link_url=? WHERE id=?")->execute([$link, $cobId]);
        header('Location: ' . $link);
        exit;
    }

    private static function planoFixa(string $codigo): ?array
    {
        foreach (AssinaturaService::config()['planos'] as $p) if ($p['codigo'] === $codigo) return $p;
        return null;
    }

    /** Dados de contato do usuário pro checkout da InfinitePay — a empresa "casca" do Fixa
     *  standalone não tem contato próprio que valha a pena usar (ver cadastrarSalvar()), o
     *  contato real é sempre o do usuário/pessoa física. */
    private static function clienteDoUsuario(\PDO $db, int $usuarioId): array
    {
        $st = $db->prepare("SELECT nome, email, telefone FROM usuarios WHERE id = ?");
        $st->execute([$usuarioId]);
        $u = $st->fetch() ?: [];
        return array_filter([
            'name'         => $u['nome'] ?? null,
            'email'        => $u['email'] ?? null,
            'phone_number' => telefone_internacional((string) ($u['telefone'] ?? '')),
        ]);
    }
}
