<?php

namespace App\Services;

/**
 * Envio de e-mail via SMTP (cliente próprio, sem dependências externas).
 * Suporta SSL (porta 465) e STARTTLS (porta 587), AUTH LOGIN.
 * Best-effort: qualquer falha é silenciosa e NUNCA quebra o fluxo que chamou.
 */
class EmailService
{
    /** Quando true, registra a conversa SMTP em self::$log (uso: diagnóstico via tools/test-email.php). */
    public static bool $debug = false;
    /** @var string[] Log da última conversa SMTP quando $debug está ligado. */
    public static array $log = [];

    private static function cfg(): array
    {
        $f = BASE_PATH . '/config/email.php';
        return is_file($f) ? (require $f) : ['enabled' => false];
    }

    /**
     * Envia um e-mail HTML. Retorna true em sucesso.
     * $anexos: lista de ['filename'=>, 'mime'=>, 'data'=> (binário)].
     * $fromEmail/$fromName: sobrescreve o remetente padrão de config/email.php — usado quando um
     * fluxo específico precisa de um remetente diferente do genérico (ex.: convitePropeccao()
     * envia como suporte@fixaos.com.br, não o from_email padrão do sistema).
     */
    public static function send(string $toEmail, string $toName, string $assunto, string $html, array $anexos = [], ?string $fromEmail = null, ?string $fromName = null): bool
    {
        // Modo demonstração nunca envia comunicação real (evita spam pela conta demo).
        if (!empty($_SESSION['demo_mode'])) return false;

        $cfg = self::cfg();
        if (empty($cfg['enabled']) || empty($cfg['host'])) return false;
        if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) return false;

        $host   = $cfg['host'];
        $port   = (int) ($cfg['port'] ?? 465);
        $secure = $cfg['secure'] ?? 'ssl';
        $user   = $cfg['username'] ?? '';
        $pass   = $cfg['password'] ?? '';
        $fromE  = $fromEmail ?? ($cfg['from_email'] ?? $user);
        $fromN  = $fromName ?? ($cfg['from_name'] ?? 'FixaOS');

        if (self::$debug) self::$log = [];
        $logLine = function (string $s): void { if (self::$debug) self::$log[] = rtrim($s); };

        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) { $logLine("CONEXÃO FALHOU em {$remote}: [{$errno}] {$errstr}"); return false; }
        stream_set_timeout($fp, 15);

        $read = function () use ($fp, $logLine): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') break; // fim da resposta multiline
            }
            $logLine('S: ' . $data);
            return $data;
        };
        $cmd = function (string $c) use ($fp, $read, $logLine): string {
            // não loga o conteúdo de credenciais em base64
            $logLine('C: ' . (preg_match('~^[A-Za-z0-9+/=]{8,}$~', $c) ? '****** (base64)' : $c));
            fwrite($fp, $c . "\r\n");
            return $read();
        };

        $ok = true;
        try {
            $read();                                   // 220 banner
            $ehlo = $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'fixaos.com.br'));

            if ($secure === 'tls') {
                $cmd('STARTTLS');
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) { fclose($fp); return false; }
                $cmd('EHLO ' . ($_SERVER['SERVER_NAME'] ?? 'fixaos.com.br'));
            }

            if ($user !== '') {
                $cmd('AUTH LOGIN');
                $r = $cmd(base64_encode($user));
                $r = $cmd(base64_encode($pass));
                if (strncmp($r, '235', 3) !== 0) { $ok = false; }
            }

            if ($ok) {
                $cmd('MAIL FROM:<' . $fromE . '>');
                $cmd('RCPT TO:<' . $toEmail . '>');
                $cmd('DATA');

                $headers  = 'From: ' . self::mime($fromN) . ' <' . $fromE . ">\r\n";
                $headers .= 'To: ' . self::mime($toName) . ' <' . $toEmail . ">\r\n";
                $headers .= 'Subject: ' . self::mime($assunto) . "\r\n";
                $headers .= "MIME-Version: 1.0\r\n";
                $headers .= 'Date: ' . date('r') . "\r\n";

                if (!empty($anexos)) {
                    $boundary = 'fixaos_' . bin2hex(random_bytes(12));
                    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
                    $corpo  = "--{$boundary}\r\n";
                    $corpo .= "Content-Type: text/html; charset=UTF-8\r\n\r\n";
                    $corpo .= $html . "\r\n";
                    foreach ($anexos as $att) {
                        $fn    = self::mime($att['filename'] ?? 'anexo');
                        $mimeA = $att['mime'] ?? 'application/octet-stream';
                        $corpo .= "--{$boundary}\r\n";
                        $corpo .= "Content-Type: {$mimeA}; name=\"{$fn}\"\r\n";
                        $corpo .= "Content-Transfer-Encoding: base64\r\n";
                        $corpo .= "Content-Disposition: attachment; filename=\"{$fn}\"\r\n\r\n";
                        $corpo .= chunk_split(base64_encode($att['data'] ?? '')) . "\r\n";
                    }
                    $corpo .= "--{$boundary}--";
                    $body = $corpo;
                } else {
                    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                    $body = $html;
                }

                $body = preg_replace('/^\./m', '..', $body);   // dot-stuffing
                $r = $cmd($headers . "\r\n" . $body . "\r\n.");
                if (strncmp($r, '250', 3) !== 0) $ok = false;
            }
            $cmd('QUIT');
        } catch (\Throwable $e) {
            $ok = false;
        }
        if (is_resource($fp)) fclose($fp);
        return $ok;
    }

    private static function mime(string $s): string
    {
        return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
    }

    /**
     * Registro fotográfico do estado de entrada do equipamento.
     * As fotos vão apenas como ANEXO — não são armazenadas no servidor.
     * $ctx: empresa, cliente, equipamento, os, data.
     */
    public static function fotosEntrada(string $toEmail, string $toName, array $ctx, array $anexos): bool
    {
        $emp    = htmlspecialchars($ctx['empresa'] ?? 'FixaOS', ENT_QUOTES, 'UTF-8');
        $cli    = htmlspecialchars($ctx['cliente'] ?? '—', ENT_QUOTES, 'UTF-8');
        $equip  = htmlspecialchars($ctx['equipamento'] ?? '—', ENT_QUOTES, 'UTF-8');
        $os     = htmlspecialchars((string)($ctx['os'] ?? ''), ENT_QUOTES, 'UTF-8');
        $quando = htmlspecialchars($ctx['data'] ?? date('d/m/Y H:i'), ENT_QUOTES, 'UTF-8');
        $qtd    = count($anexos);
        $linhaOs = $os !== '' ? "<tr><td style='padding:4px 0;color:#64748b'>OS</td><td style='padding:4px 0;text-align:right;font-weight:600'>#{$os}</td></tr>" : '';

        $html = <<<HTML
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:28px 12px"><tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06)">
      <tr><td style="background:#1e3a5f;padding:22px 28px;color:#fff">
        <span style="font-size:22px;font-weight:900">Fixa<span style="color:#f97316">OS</span></span>
        <div style="font-size:13px;color:#cbd5e1;margin-top:2px">📸 Registro fotográfico de entrada</div>
      </td></tr>
      <tr><td style="padding:26px 28px">
        <p style="margin:0 0 14px;font-size:15px;line-height:1.6;color:#334155">
          Segue o <strong>registro do estado do equipamento na entrada</strong>, com <strong>{$qtd} foto(s)</strong> em anexo.
          Guarde este e-mail — ele comprova o estado do aparelho no momento do recebimento.
        </p>
        <table width="100%" style="font-size:14px;color:#0f172a;border-top:1px solid #e2e8f0;margin-top:8px">
          <tr><td style="padding:4px 0;color:#64748b">Empresa</td><td style="padding:4px 0;text-align:right;font-weight:600">{$emp}</td></tr>
          <tr><td style="padding:4px 0;color:#64748b">Cliente</td><td style="padding:4px 0;text-align:right;font-weight:600">{$cli}</td></tr>
          <tr><td style="padding:4px 0;color:#64748b">Equipamento</td><td style="padding:4px 0;text-align:right;font-weight:600">{$equip}</td></tr>
          {$linhaOs}
          <tr><td style="padding:4px 0;color:#64748b">Data/hora</td><td style="padding:4px 0;text-align:right;font-weight:600">{$quando}</td></tr>
        </table>
      </td></tr>
      <tr><td style="padding:18px 28px;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px">
        © FixaOS — as fotos não ficam armazenadas no sistema, apenas neste e-mail.
      </td></tr>
    </table>
  </td></tr></table>
</body></html>
HTML;

        $assunto = 'Estado de entrada — ' . ($ctx['equipamento'] ?? 'Equipamento')
                 . ($os !== '' ? " (OS #{$os})" : '') . ' — ' . ($ctx['cliente'] ?? '');
        return self::send($toEmail, $toName, $assunto, $html, $anexos);
    }

    /** Link de confirmação de e-mail, enviado no cadastro (e no reenvio manual). */
    public static function confirmarEmail(string $email, string $nome, string $link): bool
    {
        $n = htmlspecialchars(explode(' ', trim($nome))[0] ?: 'amigo(a)', ENT_QUOTES, 'UTF-8');
        $l = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');

        $html = <<<HTML
<!DOCTYPE html><html lang="pt-BR"><head><meta charset="UTF-8"></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:28px 12px"><tr><td align="center">
    <table width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.06)">
      <tr><td style="background:#1e3a5f;padding:22px 28px;color:#fff">
        <span style="font-size:22px;font-weight:900">Fixa<span style="color:#f97316">OS</span></span>
      </td></tr>
      <tr><td style="padding:26px 28px">
        <h2 style="margin:0 0 12px;font-size:18px;color:#0f172a">Confirme seu e-mail, {$n}</h2>
        <p style="margin:0 0 20px;font-size:15px;line-height:1.6;color:#334155">
          Falta pouco! Clique no botão abaixo pra confirmar que este e-mail é seu.
          O link vale por 24 horas.
        </p>
        <p style="text-align:center;margin:0 0 20px">
          <a href="{$l}" style="display:inline-block;background:#2563eb;color:#fff;text-decoration:none;
             font-weight:700;font-size:15px;padding:13px 28px;border-radius:8px">Confirmar e-mail</a>
        </p>
        <p style="margin:0;font-size:12.5px;color:#94a3b8;line-height:1.5">
          Se o botão não funcionar, copie e cole este link no navegador:<br>
          <span style="word-break:break-all">{$l}</span>
        </p>
      </td></tr>
      <tr><td style="padding:18px 28px;border-top:1px solid #e2e8f0;text-align:center;color:#94a3b8;font-size:12px">
        Não foi você quem se cadastrou? Pode ignorar este e-mail.
      </td></tr>
    </table>
  </td></tr></table>
</body></html>
HTML;

        return self::send($email, $nome, 'Confirme seu e-mail — FixaOS', $html);
    }

    /** E-mail de boas-vindas para uma assistência recém-cadastrada. */
    public static function boasVindas(string $email, string $nomeEmpresa): bool
    {
        $cfg = require BASE_PATH . '/config/app.php';
        $painel = rtrim($cfg['url'], '/') . '/dashboard';
        $nome = htmlspecialchars(trim($nomeEmpresa) ?: 'amigo(a)', ENT_QUOTES, 'UTF-8');

        $html = self::template($nome, $painel);
        return self::send($email, $nomeEmpresa, "Bem-vindo(a) ao FixaOS, {$nome}! 🎉", $html);
    }

    /** Agradecimento + guia detalhado de edição, enviado a quem reivindica o perfil no diretório. */
    public static function perfilReivindicado(string $email, string $nome, string $empresaNome): bool
    {
        $cfg   = require BASE_PATH . '/config/app.php';
        $login = rtrim($cfg['url'], '/') . '/login';
        $n     = htmlspecialchars(explode(' ', trim($nome))[0] ?: 'amigo(a)', ENT_QUOTES, 'UTF-8');
        $emp   = htmlspecialchars(trim($empresaNome) ?: 'sua empresa', ENT_QUOTES, 'UTF-8');
        $html  = self::templateReivindicado($n, $emp, $login);
        return self::send($email, $nome, "Bem-vindo ao FixaOS — o perfil da {$emp} agora é seu! 🎉", $html);
    }

    private static function templateReivindicado(string $nome, string $emp, string $login): string
    {
        $item = function (string $ic, string $titulo, string $desc): string {
            return '<tr>
                <td valign="top" style="width:30px;font-size:18px;padding:7px 8px 7px 0">' . $ic . '</td>
                <td style="padding:7px 0;font-size:14px;line-height:1.55;color:#475569">
                  <strong style="color:#0f172a">' . $titulo . '</strong><br>' . $desc . '
                </td></tr>';
        };
        $edicao =
            $item('📸', 'Fotos', 'Uma foto principal + galeria de até 4 fotos da sua assistência (fachada, bancada, equipe).') .
            $item('📝', 'Descrição', 'Apresente sua história, especialidades e o que faz sua assistência ser diferente.') .
            $item('🔧', 'Serviços', 'O que você conserta — celular, TV, notebook, geladeira, ar-condicionado…') .
            $item('📍', 'Endereço', 'Aparece no mapa e na busca por CEP, cidade e raio de distância.') .
            $item('📱', 'Contato', 'Telefone, WhatsApp e e-mail públicos, com botão de contato direto.') .
            $item('🌐', 'Redes sociais', 'Facebook, Instagram, YouTube e TikTok.');
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <tr><td style="background:#1e3a5f;padding:26px 32px;text-align:center">
          <span style="font-size:26px;font-weight:900;color:#fff;letter-spacing:-.5px">Fixa<span style="color:#f97316">OS</span></span>
        </td></tr>
        <tr><td style="padding:34px 32px 6px">
          <h1 style="margin:0 0 8px;font-size:22px;color:#0f172a">Olá, {$nome}! 🎉</h1>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#475569">
            Você acaba de <strong>reivindicar o perfil da {$emp}</strong> no diretório do FixaOS. A partir de agora, essa
            página é <strong>sua para gerenciar</strong> — e é de graça. Obrigado por confiar na gente! 💙
          </p>

          <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:14px 18px;margin:0 0 22px">
            <p style="margin:0;font-size:14px;line-height:1.6;color:#1e3a5f">
              <strong>Como acessar:</strong> entre em <a href="{$login}" style="color:#2563eb;font-weight:bold">{$login}</a>
              com o seu e-mail e a senha que você criou. Você cai direto em <strong>“Minha Empresa na Web”</strong>.
            </p>
          </div>

          <p style="margin:0 0 6px;font-size:16px;font-weight:bold;color:#0f172a">O que você pode editar no seu perfil:</p>
          <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 22px">{$edicao}</table>

          <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:14px 18px;margin:0 0 24px">
            <p style="margin:0 0 6px;font-size:14px;font-weight:bold;color:#166534">Por que vale a pena manter atualizado:</p>
            <p style="margin:0;font-size:13.5px;line-height:1.6;color:#166534">
              ✔ Sua assistência fica <strong>encontrável no Google</strong> (SEO otimizado)<br>
              ✔ Clientes te acham por <strong>proximidade</strong> (busca por CEP e raio)<br>
              ✔ Você recebe <strong>avaliações verificadas</strong> de clientes reais
            </p>
          </div>

          <p style="margin:0 0 22px;font-size:14px;line-height:1.7;color:#475569">
            E tem mais chegando: o <strong>sistema completo de gestão</strong> (ordens de serviço, financeiro, estoque, PDV,
            WhatsApp…) está nos ajustes finais. <strong>Você será um dos primeiros convidados</strong> a testar — a gente
            te avisa por aqui. 😉
          </p>

          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 26px"><tr><td style="border-radius:12px;background:#f97316">
            <a href="{$login}" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:700;color:#fff;text-decoration:none;border-radius:12px">▶ Gerenciar meu perfil</a>
          </td></tr></table>

          <p style="margin:0;font-size:14px;color:#475569">Qualquer dúvida, é só responder este e-mail.<br>Com carinho,<br><strong>Equipe FixaOS</strong> 💙</p>
        </td></tr>
        <tr><td style="padding:22px 32px;border-top:1px solid #e2e8f0;text-align:center">
          <p style="margin:0;font-size:12px;color:#94a3b8">© FixaOS — Gestão para assistências técnicas · fixaos.com.br</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
    }

    /** Convite frio pro diretório grátis + apresentação do sistema completo, disparado de
     *  /master/prospeccao (ver MasterController::prospeccaoDisparar()). */
    public static function convitePropeccao(string $email, string $razaoSocial, string $municipio, string $uf, string $unsubLink): bool
    {
        $nomeExib = htmlspecialchars($razaoSocial, ENT_QUOTES, 'UTF-8');
        $local    = htmlspecialchars(trim($municipio . ($uf ? "/{$uf}" : '')), ENT_QUOTES, 'UTF-8');
        $cfg      = require BASE_PATH . '/config/app.php';
        $baseUrl      = rtrim($cfg['url'], '/');
        $diretorioUrl = $baseUrl . '/diretorio/cadastrar';
        $demoUrl      = $baseUrl . '/demo';
        $unsub    = htmlspecialchars($unsubLink, ENT_QUOTES, 'UTF-8');

        $beneficio = function (string $emoji, string $titulo, string $desc): string {
            return '<tr><td valign="top" style="width:34px;padding:8px 8px 8px 0;font-size:19px;line-height:1">' . $emoji . '</td>'
                 . '<td style="padding:8px 0;font-size:13.5px;line-height:1.55;color:#475569;border-bottom:1px solid #f1f5f9">'
                 . '<strong style="color:#0f172a">' . $titulo . '</strong><br>' . $desc . '</td></tr>';
        };
        $beneficios =
            $beneficio('🧾', 'Ordens de Serviço', 'Do orçamento ao fechamento, com status, laudo técnico e histórico completo por cliente.') .
            $beneficio('💰', 'Financeiro', 'Fluxo de caixa, contas a pagar/receber e taxa de cartão calculada sozinha.') .
            $beneficio('📦', 'Estoque e PDV', 'Controle de peças, venda balcão e comprovante em segundos.') .
            $beneficio('💬', 'WhatsApp automático', 'Cliente recebe aviso de status, orçamento e link de acompanhamento sem você digitar nada.');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <tr><td style="background:#1e3a5f;padding:26px 32px;text-align:center">
          <span style="font-size:24px;font-weight:900;color:#fff;letter-spacing:-.5px">Fixa<span style="color:#f97316">OS</span></span>
        </td></tr>

        <!-- Bloco 1: diretório grátis -->
        <tr><td style="padding:32px 32px 8px">
          <h1 style="margin:0 0 12px;font-size:19px;color:#0f172a">Olá, {$nomeExib}!</h1>
          <p style="margin:0 0 16px;font-size:14.5px;line-height:1.7;color:#475569">
            Encontramos o cadastro da sua empresa nos dados públicos de CNPJ e queremos convidar
            vocês pra aparecer <strong>gratuitamente</strong> no diretório de assistências técnicas
            do FixaOS — a página onde clientes de {$local} buscam assistência técnica perto deles.
          </p>
          <p style="margin:0 0 20px;font-size:14.5px;line-height:1.7;color:#475569">
            Cadastro leva 2 minutos, não pede cartão e o perfil já sai com telefone, WhatsApp,
            endereço e avaliações de clientes — sem custo nenhum.
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 8px"><tr><td style="border-radius:12px;background:#f97316">
            <a href="{$diretorioUrl}" style="display:inline-block;padding:13px 28px;font-size:15px;font-weight:700;color:#fff;text-decoration:none;border-radius:12px">Cadastrar grátis no diretório</a>
          </td></tr></table>
        </td></tr>

        <!-- Divisor -->
        <tr><td style="padding:24px 32px 0">
          <div style="border-top:1px dashed #e2e8f0"></div>
        </td></tr>

        <!-- Bloco 2: sistema completo -->
        <tr><td style="padding:22px 32px 8px">
          <p style="margin:0 0 4px;font-size:13px;font-weight:700;color:#f97316;text-transform:uppercase;letter-spacing:.04em">De quebra, aproveite pra conhecer</p>
          <h2 style="margin:0 0 12px;font-size:17px;color:#0f172a">O FixaOS também organiza o dia a dia da sua assistência</h2>
          <p style="margin:0 0 16px;font-size:14.5px;line-height:1.7;color:#475569">
            Além do perfil grátis no diretório, o FixaOS é um sistema completo pra quem conserta —
            pensado pra tirar o controle da cabeça (ou do caderno) e organizar tudo num só lugar:
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 20px">{$beneficios}</table>
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 24px"><tr><td style="border-radius:12px;border:2px solid #1e3a5f">
            <a href="{$demoUrl}" style="display:inline-block;padding:12px 26px;font-size:14.5px;font-weight:700;color:#1e3a5f;text-decoration:none;border-radius:10px">▶ Ver demonstração ao vivo, sem cadastro</a>
          </td></tr></table>
          <p style="margin:0;font-size:13.5px;color:#475569">Qualquer dúvida, é só responder este e-mail.<br>Equipe FixaOS</p>
        </td></tr>

        <tr><td style="padding:18px 32px;border-top:1px solid #e2e8f0;text-align:center">
          <p style="margin:0 0 4px;font-size:11.5px;color:#94a3b8">© FixaOS — Gestão para assistências técnicas · fixaos.com.br</p>
          <p style="margin:0;font-size:11.5px;color:#94a3b8">Não quer mais receber este convite? <a href="{$unsub}" style="color:#94a3b8">Cancelar</a></p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;

        // Remetente é suporte@fixaos.com.br (não o from_email padrão do sistema) — é convite frio
        // pra fora, faz mais sentido vir de "suporte" do que do endereço genérico de contato.
        return self::send($email, $razaoSocial, "Sua assistência técnica em {$local} pode aparecer grátis no FixaOS", $html, [], 'suporte@fixaos.com.br', 'FixaOS');
    }

    /**
     * Acompanhamento enviado alguns dias depois da empresa publicar o perfil grátis no diretório
     * (disparado por scripts/disparar_followup_diretorio.php) — convite pro sistema completo,
     * num registro formal/corporativo, deliberadamente sem emoji nem elementos decorativos (a
     * pedido do usuário: "sem ícones de florzinha nem coração, algo sério e muito profissional").
     */
    public static function diretorioFollowUp(string $email, string $nomeContato, string $nomeEmpresa): bool
    {
        $primeiroNome = htmlspecialchars(explode(' ', trim($nomeContato))[0] ?: 'responsável', ENT_QUOTES, 'UTF-8');
        $emp          = htmlspecialchars(trim($nomeEmpresa) ?: 'sua empresa', ENT_QUOTES, 'UTF-8');
        $cfg          = require BASE_PATH . '/config/app.php';
        $baseUrl      = rtrim($cfg['url'], '/');
        $demoUrl      = $baseUrl . '/demo';
        $planosUrl    = $baseUrl . '/planos';

        $item = function (string $titulo, string $desc): string {
            return '<tr><td style="padding:9px 0;border-bottom:1px solid #e6e9ee">'
                 . '<p style="margin:0 0 2px;font-size:13.5px;font-weight:700;color:#0f172a">' . $titulo . '</p>'
                 . '<p style="margin:0;font-size:13px;line-height:1.55;color:#5a6578">' . $desc . '</p>'
                 . '</td></tr>';
        };
        $itens =
            $item('Ordens de serviço', 'Controle completo do orçamento ao fechamento, com histórico por cliente e por equipamento.') .
            $item('Financeiro', 'Fluxo de caixa, contas a pagar e a receber, com apuração automática de taxas de cartão.') .
            $item('Estoque e ponto de venda', 'Controle de peças e produtos integrado às vendas realizadas no balcão.') .
            $item('Comunicação com o cliente', 'Envio automático de atualizações de status e orçamento por WhatsApp.');

        $html = <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#eef1f5;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eef1f5;padding:36px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:580px;background:#ffffff;border:1px solid #d8dde4">

        <tr><td style="padding:28px 36px;border-bottom:2px solid #1e3a5f">
          <span style="font-size:20px;font-weight:800;color:#1e3a5f;letter-spacing:-.3px">FixaOS</span>
        </td></tr>

        <tr><td style="padding:34px 36px 6px">
          <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#1f2937">Prezado(a) {$primeiroNome},</p>

          <p style="margin:0 0 16px;font-size:14px;line-height:1.75;color:#374151">
            O perfil de {$emp} está publicado no diretório público de assistências técnicas do
            FixaOS. A partir deste cadastro, sua empresa passa a ser localizável por clientes que
            realizam buscas por assistência técnica na sua região.
          </p>
          <p style="margin:0 0 24px;font-size:14px;line-height:1.75;color:#374151">
            O diretório é um dos módulos do sistema. Gostaríamos de apresentar os demais recursos
            disponíveis para a gestão operacional e financeira de uma assistência técnica:
          </p>

          <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;margin:0 0 28px">{$itens}</table>

          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 12px">
            <tr>
              <td style="background:#1e3a5f;padding:1px">
                <a href="{$demoUrl}" style="display:inline-block;padding:11px 24px;font-size:13.5px;font-weight:700;color:#ffffff;text-decoration:none">Acessar demonstração</a>
              </td>
              <td style="width:12px"></td>
              <td style="border:1px solid #1e3a5f;padding:1px">
                <a href="{$planosUrl}" style="display:inline-block;padding:11px 24px;font-size:13.5px;font-weight:700;color:#1e3a5f;text-decoration:none">Consultar planos</a>
              </td>
            </tr>
          </table>

          <p style="margin:26px 0 0;font-size:13.5px;line-height:1.7;color:#374151">
            Em caso de dúvidas, esta mensagem pode ser respondida diretamente.
          </p>
          <p style="margin:18px 0 0;font-size:13.5px;line-height:1.6;color:#374151">
            Atenciosamente,<br>Equipe FixaOS
          </p>
        </td></tr>

        <tr><td style="padding:20px 36px;border-top:1px solid #e2e8f0">
          <p style="margin:0;font-size:11px;color:#8592a3">FixaOS — Sistema de gestão para assistências técnicas · fixaos.com.br</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body></html>
HTML;

        return self::send($email, $nomeContato, "O sistema FixaOS além do diretório", $html, [], 'suporte@fixaos.com.br', 'FixaOS');
    }

    private static function template(string $nome, string $painel): string
    {
        return <<<HTML
<!DOCTYPE html>
<html lang="pt-BR"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif">
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:32px 12px">
    <tr><td align="center">
      <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:560px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(0,0,0,.06)">
        <tr><td style="background:#1e3a5f;padding:28px 32px;text-align:center">
          <span style="font-size:26px;font-weight:900;color:#fff;letter-spacing:-.5px">Fixa<span style="color:#f97316">OS</span></span>
        </td></tr>
        <tr><td style="padding:36px 32px 8px">
          <h1 style="margin:0 0 8px;font-size:22px;color:#0f172a">Olá, {$nome}! 🎉</h1>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#475569">
            Seja muito bem-vindo(a) à família <strong>FixaOS</strong>. Você acaba de dar um passo e tanto pra organizar
            sua assistência técnica de verdade.
          </p>
          <p style="margin:0 0 16px;font-size:15px;line-height:1.7;color:#475569">
            Aqui você encontra tudo o que precisa — <strong>ordens de serviço, clientes, estoque, financeiro</strong> e
            até uma página pública pra seus clientes te acharem no Google. E o melhor: você aprende a usar em minutos.
          </p>
          <p style="margin:0 0 24px;font-size:15px;line-height:1.7;color:#475569">
            Seu <strong>teste grátis de 15 dias</strong> já está rolando. Aproveite sem pressa. 😉
          </p>
          <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 28px"><tr><td style="border-radius:12px;background:#f97316">
            <a href="{$painel}" style="display:inline-block;padding:14px 32px;font-size:16px;font-weight:700;color:#fff;text-decoration:none;border-radius:12px">▶ Acessar meu painel</a>
          </td></tr></table>
          <p style="margin:0 0 6px;font-size:14px;line-height:1.7;color:#475569">
            Precisa de uma mãozinha? É só responder este e-mail — estamos aqui pra você.
          </p>
          <p style="margin:0;font-size:14px;color:#475569">Com carinho,<br><strong>Equipe FixaOS</strong> 💙</p>
        </td></tr>
        <tr><td style="padding:24px 32px;border-top:1px solid #e2e8f0;text-align:center">
          <p style="margin:0;font-size:12px;color:#94a3b8">© FixaOS — Gestão para assistências técnicas</p>
        </td></tr>
      </table>
    </td></tr>
  </table>
</body></html>
HTML;
    }
}
