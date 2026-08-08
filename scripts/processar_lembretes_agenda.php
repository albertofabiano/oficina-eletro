<?php
// Processa a fila de lembretes de agenda vencidos (agenda_lembretes_fila) — envia os
// pendentes com disparar_em <= agora, registra sucesso/falha (com retentativa) e nunca envia
// pra evento cancelado/excluído. Ver App\Services\Lembretes\AgendaLembreteService.
//
// Este é o caminho RECOMENDADO em produção: rodar via cron real, ex. a cada minuto:
//   * * * * * php /var/www/fixaos/scripts/processar_lembretes_agenda.php >> /var/www/fixaos/storage/logs/lembretes_cron.log 2>&1
//
// Sem cron configurado, a fila ainda é processada (throttled, 1x/min) só de o app receber
// tráfego — ver AgendaLembreteService::processarFilaThrottled(), chamado de
// NotificacaoController::index()/api() — mas isso depende de alguém ter o FixaOS aberto.
//
// Uso: php scripts/processar_lembretes_agenda.php [--lote=100]

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Helpers/rrule.php';

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$lote = 100;
foreach ($argv as $arg) {
    if (str_starts_with($arg, '--lote=')) $lote = max(1, (int) substr($arg, 7));
}

$resultado = (new App\Services\Lembretes\AgendaLembreteService())->processarFila($lote);

printf(
    "[%s] processados=%d enviados=%d falhas=%d cancelados=%d\n",
    date('Y-m-d H:i:s'), $resultado['processados'], $resultado['enviados'], $resultado['falhas'], $resultado['cancelados']
);
