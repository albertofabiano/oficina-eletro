<?php
/**
 * Dispara EmailService::novidadesSistema() pra toda empresa JÁ CADASTRADA no sistema — inclui
 * tipo_conta='completo' (sistema completo) e tipo_conta='diretorio' que de fato criaram conta
 * (reivindicada=1). NÃO inclui as ~28 mil fichas importadas de CNPJ que nunca passaram por
 * cadastro nenhum (reivindicada=0, sem usuário/login) — pra elas "novidades no seu sistema"
 * seria falso (não têm sistema nenhum); esse público usa os e-mails de prospecção/reivindicação
 * já existentes (EmailService::convitePropeccao()/conviteReivindicarDiretorio()), não este.
 *
 * Dedup via `empresas_email_log` (campanha fixa abaixo) — reexecutar o script não reenvia pra
 * quem já recebeu, então é seguro rodar de novo se cair no meio (rede, VPS reiniciado etc.).
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

use App\Core\DB;
use App\Services\EmailService;

const CAMPANHA = 'novidades_2026_09';

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$aplicar = in_array('--aplicar', $argv, true);
$limite  = 0;
foreach ($argv as $arg) {
    if (preg_match('/^--limite=(\d+)$/', $arg, $m)) { $limite = (int) $m[1]; }
}

echo ($aplicar ? "MODO APLICAR — vai enviar e-mail de verdade.\n" : "MODO SIMULAÇÃO — nenhum e-mail será enviado (rode com --aplicar pra enviar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$db = DB::pdo();
$sql =
    "SELECT e.id, e.email,
            COALESCE(
              (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id AND u.perfil = 'admin' ORDER BY u.id LIMIT 1),
              (SELECT u.nome FROM usuarios u WHERE u.empresa_id = e.id ORDER BY u.id LIMIT 1),
              e.razao_social, e.nome_fantasia
            ) AS nome_contato
     FROM empresas e
     WHERE e.ativo = 1
       AND e.reivindicada = 1
       AND e.email IS NOT NULL AND e.email <> ''
       AND e.id NOT IN (SELECT empresa_id FROM empresas_email_log WHERE campanha = ?)
     ORDER BY e.id";
if ($limite > 0) $sql .= " LIMIT {$limite}";

$stmt = $db->prepare($sql);
$stmt->execute([CAMPANHA]);
$empresas = $stmt->fetchAll();

$total = count($empresas);
echo "Empresas elegíveis (cadastradas, ainda não receberam esta campanha): {$total}\n";

if (!$aplicar) {
    echo "\nAmostra dos primeiros 10:\n";
    foreach (array_slice($empresas, 0, 10) as $e) {
        echo "  #{$e['id']} — {$e['nome_contato']} <{$e['email']}>\n";
    }
    echo "\nRode com --aplicar pra enviar de verdade (use --limite=N pra testar num lote pequeno primeiro).\n";
    exit(0);
}

if ($total === 0) { echo "Nada a fazer.\n"; exit(0); }

$enviados = 0;
$falhas   = 0;
foreach ($empresas as $e) {
    $ok = EmailService::novidadesSistema((string) $e['email'], (string) $e['nome_contato']);
    if ($ok) {
        $db->prepare("INSERT IGNORE INTO empresas_email_log (empresa_id, campanha) VALUES (?, ?)")
           ->execute([$e['id'], CAMPANHA]);
        $enviados++;
    } else {
        $falhas++;
    }
}

printf(
    "\n[%s] elegiveis=%d enviados=%d falhas=%d (campanha=%s)\n",
    date('Y-m-d H:i:s'), $total, $enviados, $falhas, CAMPANHA
);
