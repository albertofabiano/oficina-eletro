<?php
// Disparo diário automático do convite de prospecção (ver MasterController::prospeccaoDisparar()
// e CLAUDE.md "Disparo de e-mail de prospecção") — sem filtro de cidade/UF, misturando estados
// diferentes por round-robin (App\Services\Prospeccao\DisparoService::dispararMisturandoUf()),
// respeitando o limite diário de config/prospeccao_email.php (a mesma cota do botão manual
// "Disparar agora" — os dois somam no mesmo contador, nunca ultrapassa).
//
// Rodar via cron real, 1x/dia, ex.:
//   0 9 * * * php /var/www/fixaos/scripts/disparar_prospeccao_diario.php >> /var/www/fixaos/storage/logs/prospeccao_cron.log 2>&1
//
// Sem cron configurado, o disparo automático simplesmente não acontece — diferente do poller de
// lembretes/notificações, não há fallback throttled embutido no tráfego do app (ver CLAUDE.md,
// "Deliberadamente manual, não em cron" — decisão revista: agora é automático, mas só via cron
// real, exatamente pra manter volume/ritmo previsível, um disparo por dia, não a cada request).

use App\Services\Prospeccao\DisparoService;

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$emailCfg     = require BASE_PATH . '/config/prospeccao_email.php';
$limiteDiario = (int) ($emailCfg['limite_diario'] ?? 20);
$restante     = max(0, $limiteDiario - DisparoService::enviadosHoje());

$enviados = $restante > 0 ? DisparoService::dispararMisturandoUf($restante) : 0;

printf(
    "[%s] limite_diario=%d restante_antes=%d enviados=%d\n",
    date('Y-m-d H:i:s'), $limiteDiario, $restante, $enviados
);
