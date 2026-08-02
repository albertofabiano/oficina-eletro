<?php
/**
 * Alerta de disponibilidade — envia e-mail ao dono quando o site fica fora do ar/lento.
 * Uso: php tools/alerta_uptime.php <http_code> <segundos>   (ou 'TESTE' pra testar)
 */
define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Services\EmailService;

$code  = $argv[1] ?? '?';
$secs  = $argv[2] ?? '?';
$dest  = 'afmbl2112@gmail.com';
$teste = ($code === 'TESTE');
$quando = date('H:i:s \d\o d/m/Y');

if ($teste) {
    $assunto = '✅ [TESTE] Monitor do FixaOS ativo';
    $html = "<h2>Monitor de disponibilidade ativado ✅</h2>"
          . "<p>Este é um <b>teste</b>. A partir de agora, se o <b>fixaos.com.br</b> ficar fora do ar ou lento, "
          . "você recebe um alerta neste e-mail — não precisa mais descobrir por cliente reclamando.</p>"
          . "<p style='color:#888'>Pode ignorar este e-mail de teste.</p>";
} else {
    $assunto = "⚠️ FixaOS instável (HTTP {$code}, {$secs}s)";
    $html = "<h2 style='color:#c0392b'>⚠️ Alerta — FixaOS instável</h2>"
          . "<p>O site <b>fixaos.com.br</b> respondeu <b>HTTP {$code}</b> em <b>{$secs}s</b> às {$quando}.</p>"
          . "<p>Pode ser um engasgo passageiro (atualize a página), mas vale checar o servidor se repetir.</p>";
}

$ok = EmailService::send($dest, $assunto, strip_tags($html), $html);
echo ($ok ? 'alerta enviado' : 'FALHA ao enviar alerta') . " (code={$code}, {$secs}s)\n";
