<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;
use App\Models\Usuario;
use App\Services\EmailService;
use App\Services\WhatsAppService;

class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->view('auth.login', [], 'auth');
    }

    public function login(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/login')); }

        $login = trim($this->post('login', ''));
        $senha = $this->post('senha', '');

        $model = new Usuario();

        // Login com "@" é e-mail. Caso contrário, o texto vira dígitos e pode ser
        // CNPJ/CPF (da empresa) e/ou celular (do usuário) — um número de 11 dígitos
        // é ambíguo entre CPF e celular, então junta os dois como candidatos e
        // deixa a senha decidir qual é o certo (também resolve telefone repetido
        // entre usuários, já que ele não é único no sistema).
        $candidatos = [];
        if (str_contains($login, '@')) {
            $u = $model->findByEmailGlobal(mb_strtolower($login));
            if ($u) $candidatos[] = $u;
        } else {
            $digitos = only_numbers($login);
            if (in_array(strlen($digitos), [11, 14], true)) {
                $u = $model->findTitularByDocumento($digitos);
                if ($u) $candidatos[] = $u;
            }
            if (in_array(strlen($digitos), [10, 11], true)) {
                $candidatos = array_merge($candidatos, $model->findByTelefone($digitos));
            }
        }

        $usuario = null;
        foreach ($candidatos as $c) {
            if (password_verify($senha, $c['senha'])) { $usuario = $c; break; }
        }

        if (!$usuario) {
            $_SESSION['_old'] = ['login' => $login];
            $this->flash('error', 'Credenciais incorretas.');
            $this->redirect(url('/login'));
        }

        $permissoes = $model->permissoes($usuario['id']);
        Auth::login($usuario, $permissoes);
        $model->updateUltimoLogin($usuario['id']);

        $this->redirect(url('/dashboard'));
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect(url('/login'));
    }

    // ── Sai do modo demonstração e vai direto pro cadastro real ──────────
    public function sairParaCadastro(): void
    {
        Auth::logout();
        $this->redirect(url('/cadastrar'));
    }

    // ── Modo demonstração: entra no painel sem cadastro ──────────────────
    public function demo(): void
    {
        $model   = new Usuario();
        $usuario = $model->findByEmailGlobal('demo@fixaos.com.br');
        if (!$usuario) {
            $this->flash('error', 'A demonstração está indisponível no momento. Tente novamente em instantes.');
            $this->redirect(url('/login'));
        }
        $permissoes = $model->permissoes($usuario['id']);
        Auth::login($usuario, $permissoes);
        $_SESSION['demo_mode'] = true;
        $this->redirect(url('/dashboard'));
    }

    // ── Recuperação de senha ─────────────────────────────────────────────
    public function esqueciSenha(): void
    {
        $this->view('auth.esqueci_senha', [], 'auth');
    }

    public function enviarReset(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/esqueci-senha')); }

        $email = trim($this->post('email', ''));
        $canal = $this->post('canal', 'email') === 'whatsapp' ? 'whatsapp' : 'email';
        $db = DB::pdo();
        $st = $db->prepare("SELECT id, nome, email, telefone FROM usuarios WHERE email = ? AND ativo = 1 LIMIT 1");
        $st->execute([$email]);
        $u = $st->fetch();

        // Só envia se existir — mas a resposta é sempre genérica (não revela se o e-mail está
        // cadastrado, nem se tem WhatsApp) e igual pros dois canais, por segurança.
        if ($u) {
            $token = bin2hex(random_bytes(32));
            $db->prepare("UPDATE usuarios SET token_reset = ?, token_reset_expira = DATE_ADD(NOW(), INTERVAL 1 HOUR) WHERE id = ?")
               ->execute([$token, $u['id']]);
            $base = rtrim((require BASE_PATH . '/config/app.php')['url'], '/');
            $link = $base . '/redefinir-senha/' . $token;

            if ($canal === 'whatsapp' && !empty($u['telefone'])) {
                // Sai do número da PLATAFORMA (mesma instância do bot de suporte), não do
                // WhatsApp da empresa — é uma mensagem de segurança da conta do usuário, não uma
                // comunicação da assistência com o próprio cliente dela, e assim funciona mesmo
                // pra quem ainda não conectou o WhatsApp da empresa (não é pré-requisito nenhum).
                WhatsAppService::enviarTextoPlataforma(only_numbers($u['telefone']), $this->whatsappResetTexto($u['nome'], $link));
            } else {
                EmailService::send($u['email'], $u['nome'], 'Redefinir sua senha — FixaOS', $this->emailResetHtml($u['nome'], $link));
            }
        }

        $mensagem = $canal === 'whatsapp'
            ? 'Se este e-mail estiver cadastrado e tiver WhatsApp registrado, enviamos o link de redefinição por lá.'
            : 'Se este e-mail estiver cadastrado, enviamos um link para redefinir a senha. Verifique sua caixa de entrada (e o spam).';
        $this->flash('success', $mensagem);
        $this->redirect(url('/esqueci-senha'));
    }

    private function whatsappResetTexto(string $nome, string $link): string
    {
        return "Olá, {$nome}! 👋\n\n"
            . "Recebemos um pedido para redefinir a senha da sua conta no FixaOS.\n\n"
            . "Toque no link abaixo para criar uma nova senha (expira em 1 hora):\n{$link}\n\n"
            . "Se você não pediu isso, ignore esta mensagem — sua senha continua a mesma.";
    }

    public function resetForm(string $token): void
    {
        $st = DB::pdo()->prepare("SELECT id FROM usuarios WHERE token_reset = ? AND token_reset_expira >= NOW() LIMIT 1");
        $st->execute([$token]);
        if (!$st->fetch()) {
            $this->flash('error', 'Link inválido ou expirado. Solicite um novo.');
            $this->redirect(url('/esqueci-senha'));
        }
        $this->view('auth.reset_senha', ['token' => $token], 'auth');
    }

    public function resetar(string $token): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/redefinir-senha/' . $token)); }

        $db = DB::pdo();
        $st = $db->prepare("SELECT id FROM usuarios WHERE token_reset = ? AND token_reset_expira >= NOW() LIMIT 1");
        $st->execute([$token]);
        $u = $st->fetch();
        if (!$u) {
            $this->flash('error', 'Link inválido ou expirado. Solicite um novo.');
            $this->redirect(url('/esqueci-senha'));
        }

        $senha = $this->post('senha', '');
        $conf  = $this->post('senha_confirma', '');
        if (strlen($senha) < 6) { $this->flash('error', 'A senha deve ter ao menos 6 caracteres.'); $this->redirect(url('/redefinir-senha/' . $token)); }
        if ($senha !== $conf)   { $this->flash('error', 'As senhas não conferem.'); $this->redirect(url('/redefinir-senha/' . $token)); }

        $hash = password_hash($senha, PASSWORD_DEFAULT);
        $db->prepare("UPDATE usuarios SET senha = ?, token_reset = NULL, token_reset_expira = NULL WHERE id = ?")->execute([$hash, $u['id']]);

        $this->flash('success', 'Senha redefinida com sucesso! Faça login com a nova senha.');
        $this->redirect(url('/login'));
    }

    // ── Confirmação de e-mail ─────────────────────────────────────────────
    public function verificarEmail(string $token): void
    {
        $db = DB::pdo();
        $st = $db->prepare("SELECT id FROM usuarios WHERE token_verificacao = ? AND token_verificacao_expira >= NOW() LIMIT 1");
        $st->execute([$token]);
        $u = $st->fetch();

        if (!$u) {
            $this->flash('error', 'Link de confirmação inválido ou expirado. Peça um novo em "Reenviar confirmação" no seu painel.');
            $this->redirect(url(Auth::check() ? '/dashboard' : '/login'));
        }

        $db->prepare("UPDATE usuarios SET email_verificado = 1, token_verificacao = NULL, token_verificacao_expira = NULL WHERE id = ?")
           ->execute([$u['id']]);

        if (Auth::check() && (int) Auth::id() === (int) $u['id']) {
            $_SESSION['usuario']['email_verificado'] = 1;
        }

        $this->flash('success', 'E-mail confirmado! ✅');
        $this->redirect(url(Auth::check() ? '/dashboard' : '/login'));
    }

    public function reenviarVerificacao(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $usuario = Auth::user();
        if (!$usuario || (int) ($usuario['email_verificado'] ?? 1) === 1) {
            $this->redirect(url('/dashboard'));
        }

        $db = DB::pdo();
        $token = bin2hex(random_bytes(32));
        $db->prepare("UPDATE usuarios SET token_verificacao = ?, token_verificacao_expira = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?")
           ->execute([$token, $usuario['id']]);

        $base = rtrim((require BASE_PATH . '/config/app.php')['url'], '/');
        $link = $base . '/verificar-email/' . $token;
        EmailService::confirmarEmail($usuario['email'], $usuario['nome'], $link);

        $this->flash('success', 'Enviamos um novo link de confirmação para ' . $usuario['email'] . '.');
        $this->redirectBack();
    }

    private function emailResetHtml(string $nome, string $link): string
    {
        $n = htmlspecialchars($nome);
        $l = htmlspecialchars($link);
        return '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto">'
            . '<div style="background:#1e3a5f;padding:20px;text-align:center;border-radius:10px 10px 0 0">'
            . '<span style="color:#fff;font-size:22px;font-weight:900">Fixa<span style="color:#f97316">OS</span></span></div>'
            . '<div style="border:1px solid #e2e8f0;border-top:none;padding:24px;border-radius:0 0 10px 10px">'
            . '<p style="color:#0f172a;margin:0 0 12px">Olá, ' . $n . '!</p>'
            . '<p style="color:#374151;line-height:1.6;margin:0 0 8px">Recebemos um pedido para redefinir a senha da sua conta no FixaOS. Clique no botão abaixo para criar uma nova senha:</p>'
            . '<p style="text-align:center;margin:26px 0"><a href="' . $l . '" style="background:#4f46e5;color:#fff;text-decoration:none;padding:12px 28px;border-radius:8px;font-weight:700;display:inline-block">Redefinir minha senha</a></p>'
            . '<p style="color:#64748b;font-size:13px;line-height:1.6;margin:0 0 8px">O link expira em 1 hora. Se você não fez esse pedido, ignore este e-mail — sua senha continua a mesma.</p>'
            . '<p style="color:#94a3b8;font-size:12px;word-break:break-all;margin:0">Ou copie e cole no navegador: ' . $l . '</p>'
            . '</div></div>';
    }
}
