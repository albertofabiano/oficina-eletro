<?php

/**
 * Teste de envio de e-mail (SMTP) via linha de comando.
 *
 * Uso:
 *   php tools/test-email.php destino@exemplo.com
 *
 * Mostra a conversa completa com o servidor SMTP para facilitar o diagnóstico.
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Services\EmailService;

$dest = $argv[1] ?? '';
if (!filter_var($dest, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Uso: php tools/test-email.php destino@exemplo.com\n");
    exit(1);
}

$cfg = require BASE_PATH . '/config/email.php';

echo "=== Configuração atual (config/email.php) ===\n";
echo "  enabled    : " . ($cfg['enabled'] ? 'true' : 'false') . "\n";
echo "  host:port  : {$cfg['host']}:{$cfg['port']} ({$cfg['secure']})\n";
echo "  username   : " . ($cfg['username'] !== '' ? $cfg['username'] : '(VAZIO!)') . "\n";
echo "  password   : " . ($cfg['password'] !== '' ? str_repeat('*', 8) . ' (preenchida)' : '(VAZIA!)') . "\n";
echo "  from       : {$cfg['from_name']} <{$cfg['from_email']}>\n\n";

if (empty($cfg['enabled'])) {
    echo "[AVISO] 'enabled' está false — o envio real está desativado.\n";
    echo "        Preencha username/password e mude 'enabled' para true em config/email.php.\n\n";
}

$html = '<h2>Teste FixaOS ✅</h2><p>Se você está lendo isto, o SMTP está funcionando. '
      . 'Enviado em ' . date('d/m/Y H:i:s') . '.</p>';

EmailService::$debug = true;
$inicio = microtime(true);
$ok = EmailService::send($dest, 'Teste FixaOS', 'Teste de e-mail — FixaOS', $html);
$totalMs = (int) round((microtime(true) - $inicio) * 1000);

echo "=== Conversa SMTP (cada linha com o tempo decorrido desde o início) ===\n";
foreach (EmailService::$log as $line) {
    echo "  " . str_replace(["\r", "\n"], ['', ' '], $line) . "\n";
}
echo "\n  Tempo total da conversa SMTP: {$totalMs}ms.\n";
echo "  Isso mede só até o servidor ACEITAR o e-mail (resposta 250) — não a entrega de\n";
echo "  verdade na caixa de entrada, que já sai da mão do FixaOS e passa a depender do\n";
echo "  provedor de SMTP e das políticas anti-spam de quem recebe (Gmail, etc.). Se esse\n";
echo "  tempo aqui já for alto (vários segundos), o problema é a conexão com o provedor.\n";
echo "  Se for rápido mas o e-mail demora minutos pra chegar na caixa, é entrega/greylisting\n";
echo "  do lado de fora — vale checar o painel de logs do provedor de SMTP (Brevo etc.).\n";

echo "\n=== Resultado ===\n";
if ($ok) {
    echo "  ✅ E-mail aceito pelo servidor. Confira a caixa de entrada (e o spam) de {$dest}.\n";
    exit(0);
}

echo "  ❌ Falhou. Causas mais comuns:\n";
echo "     - username/password incorretos (use a SMTP Key do Brevo, não a senha do site)\n";
echo "     - remetente '{$cfg['from_email']}' não verificado no provedor\n";
echo "     - 'enabled' ainda está false\n";
echo "     - porta 587 bloqueada na rede/servidor\n";
exit(2);
