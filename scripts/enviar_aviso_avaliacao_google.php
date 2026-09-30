<?php
/**
 * Dispara EmailService::avisoAvaliacaoGoogle() — aviso pontual da funcionalidade "Pedir
 * avaliação no Google" (botão na tela da OS) — pro mesmo público de
 * scripts/enviar_novidades_sistema.php: toda empresa JÁ CADASTRADA (reivindicada=1), inclui
 * tipo_conta='completo' usando o sistema de verdade OU em trial testando, e tipo_conta='diretorio'
 * que reivindicou o perfil. NÃO inclui fichas de CNPJ importadas sem cadastro (reivindicada=0).
 *
 * Lógica real de contagem/disparo mora em App\Services\AvisoAvaliacaoGoogleService — este
 * script é só a interface de linha de comando. Campanha PRÓPRIA
 * (AvisoAvaliacaoGoogleService::CAMPANHA), separada da campanha de novidades em lote — não
 * compete nem se soma com ela em `empresas_email_log`.
 *
 * Dedup via `empresas_email_log` — reexecutar o script não reenvia pra quem já recebeu, seguro
 * rodar de novo se cair no meio (rede, VPS reiniciado etc.).
 *
 * Por padrão roda em modo SIMULAÇÃO (não manda e-mail nenhum, só mostra quantos/quais seriam
 * afetados). Pra mandar de verdade:
 *   php scripts/enviar_aviso_avaliacao_google.php --aplicar
 *   php scripts/enviar_aviso_avaliacao_google.php --aplicar --limite=50   (testar num lote pequeno antes)
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Services\AvisoAvaliacaoGoogleService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$aplicar = in_array('--aplicar', $argv, true);
$limite  = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--limite=(\d+)$/', $arg, $m)) { $limite = (int) $m[1]; }
}

echo ($aplicar ? "MODO APLICAR — vai enviar e-mail de verdade.\n" : "MODO SIMULAÇÃO — nenhum e-mail será enviado (rode com --aplicar pra enviar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$total = AvisoAvaliacaoGoogleService::contarElegiveis();
echo "Empresas elegíveis (cadastradas, ainda não receberam esta campanha): {$total}\n";
echo "Já receberam esta campanha antes: " . AvisoAvaliacaoGoogleService::contarJaEnviados() . "\n";

if (!$aplicar) {
    echo "\nAmostra dos primeiros 10:\n";
    foreach (AvisoAvaliacaoGoogleService::elegiveis(10) as $e) {
        echo "  #{$e['id']} — {$e['nome_contato']} <{$e['email']}>\n";
    }
    echo "\nRode com --aplicar pra enviar de verdade (use --limite=N pra testar num lote pequeno primeiro).\n";
    exit(0);
}

if ($total === 0) { echo "Nada a fazer.\n"; exit(0); }

$r = AvisoAvaliacaoGoogleService::dispararTodos($limite);

printf(
    "\n[%s] elegiveis=%d enviados=%d falhas=%d (campanha=%s)\n",
    date('Y-m-d H:i:s'), $r['total'], $r['enviados'], $r['falhas'], AvisoAvaliacaoGoogleService::CAMPANHA
);
