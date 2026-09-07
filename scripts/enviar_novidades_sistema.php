<?php
/**
 * Dispara EmailService::novidadesSistema() pra toda empresa JÁ CADASTRADA no sistema — inclui
 * tipo_conta='completo' (sistema completo) e tipo_conta='diretorio' que de fato criaram conta
 * (reivindicada=1). NÃO inclui as ~28 mil fichas importadas de CNPJ que nunca passaram por
 * cadastro nenhum (reivindicada=0, sem usuário/login) — pra elas "novidades no seu sistema"
 * seria falso (não têm sistema nenhum); esse público usa os e-mails de prospecção/reivindicação
 * já existentes (EmailService::convitePropeccao()/conviteReivindicarDiretorio()), não este.
 *
 * Lógica real de contagem/disparo mora em App\Services\NovidadesSistemaService (mesma classe
 * usada pela tela /master/novidades-sistema) — este script é só a interface de linha de comando.
 *
 * Dedup via `empresas_email_log` (campanha fixa em NovidadesSistemaService::CAMPANHA) —
 * reexecutar o script não reenvia pra quem já recebeu, então é seguro rodar de novo se cair no
 * meio (rede, VPS reiniciado etc.).
 *
 * Por padrão roda em modo SIMULAÇÃO (não manda e-mail nenhum, só mostra quantos/quais seriam
 * afetados). Pra mandar de verdade:
 *   php scripts/enviar_novidades_sistema.php --aplicar
 *   php scripts/enviar_novidades_sistema.php --aplicar --limite=50   (testar num lote pequeno antes)
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Services\NovidadesSistemaService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$aplicar = in_array('--aplicar', $argv, true);
$limite  = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--limite=(\d+)$/', $arg, $m)) { $limite = (int) $m[1]; }
}

echo ($aplicar ? "MODO APLICAR — vai enviar e-mail de verdade.\n" : "MODO SIMULAÇÃO — nenhum e-mail será enviado (rode com --aplicar pra enviar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$total = NovidadesSistemaService::contarElegiveis();
echo "Empresas elegíveis (cadastradas, ainda não receberam esta campanha): {$total}\n";
echo "Já receberam esta campanha antes: " . NovidadesSistemaService::contarJaEnviados() . "\n";

if (!$aplicar) {
    echo "\nAmostra dos primeiros 10:\n";
    foreach (NovidadesSistemaService::elegiveis(10) as $e) {
        echo "  #{$e['id']} — {$e['nome_contato']} <{$e['email']}>\n";
    }
    echo "\nRode com --aplicar pra enviar de verdade (use --limite=N pra testar num lote pequeno primeiro).\n";
    exit(0);
}

if ($total === 0) { echo "Nada a fazer.\n"; exit(0); }

$r = NovidadesSistemaService::dispararTodos($limite);

printf(
    "\n[%s] elegiveis=%d enviados=%d falhas=%d (campanha=%s)\n",
    date('Y-m-d H:i:s'), $r['total'], $r['enviados'], $r['falhas'], NovidadesSistemaService::CAMPANHA
);
