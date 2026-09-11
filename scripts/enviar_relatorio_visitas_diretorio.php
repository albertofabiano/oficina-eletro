<?php
// Relatório SEMANAL de visitas ao perfil do Diretório — dispara pra empresa `tipo_conta='completo'`
// ou com destaque pago ativo (ver App\Services\RelatorioVisitasDiretorioService, doc completa lá
// e em CLAUDE.md "Relatório mensal de visitas do Diretório" pra histórico da versão original,
// mensal, antes de virar semanal). Recorrência garantida pelo cron (o script não checa a data
// sozinho, confia no agendamento) + dedup por semana via empresas_email_log, então rodar de
// novo dentro da mesma semana não reenvia.
//
// Rodar via cron real, uma vez por semana (ex.: toda segunda-feira às 8h):
//   0 8 * * 1 php /var/www/fixaos/scripts/enviar_relatorio_visitas_diretorio.php >> /var/www/fixaos/storage/logs/relatorio_visitas_diretorio_cron.log 2>&1

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Services\RelatorioVisitasDiretorioService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$resultado = RelatorioVisitasDiretorioService::dispararTodos();

printf(
    "[%s] relatório de visitas do Diretório (%s): %d elegíveis, %d enviados, %d falhas\n",
    date('Y-m-d H:i:s'),
    $resultado['periodo'],
    $resultado['total'],
    $resultado['enviados'],
    $resultado['falhas']
);
