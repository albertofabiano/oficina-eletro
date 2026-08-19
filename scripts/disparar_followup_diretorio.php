<?php
// Acompanhamento pós-publicação do diretório grátis — alguns dias depois da empresa publicar o
// perfil (empresas.diretorio_publicado_em, marcado por EmpresaController::salvarPerfilPublico()),
// envia um convite formal pro sistema completo (EmailService::diretorioFollowUp()). Só empresas
// tipo_conta='diretorio' (quem assina o sistema completo já vê o card de upsell dentro do
// próprio painel, não precisa desse e-mail) e só 1x por empresa — nunca reenvia.
//
// Rodar via cron real, 1x/dia, ex.:
//   0 10 * * * php /var/www/fixaos/scripts/disparar_followup_diretorio.php >> /var/www/fixaos/storage/logs/followup_cron.log 2>&1
//
// Ver config/prospeccao_email.php (followup_dias) e CLAUDE.md "Acompanhamento pós-cadastro no
// diretório".

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Core\DB;
use App\Services\EmailService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$emailCfg = require BASE_PATH . '/config/prospeccao_email.php';
$dias     = (int) ($emailCfg['followup_dias'] ?? 5);

$db = DB::pdo();
$stmt = $db->prepare(
    "SELECT id, razao_social AS contato_nome, nome_fantasia AS empresa_nome, email AS contato_email
     FROM empresas
     WHERE tipo_conta = 'diretorio'
       AND ativo = 1
       AND diretorio_publicado_em IS NOT NULL
       AND diretorio_publicado_em <= DATE_SUB(NOW(), INTERVAL ? DAY)
       AND diretorio_followup_enviado_em IS NULL
       AND email IS NOT NULL AND email <> ''"
);
$stmt->execute([$dias]);
$empresas = $stmt->fetchAll();

$enviados = 0;
foreach ($empresas as $e) {
    $ok = EmailService::diretorioFollowUp($e['contato_email'], (string) $e['contato_nome'], (string) $e['empresa_nome']);
    if ($ok) {
        $db->prepare("UPDATE empresas SET diretorio_followup_enviado_em = NOW() WHERE id = ?")->execute([$e['id']]);
        $enviados++;
    }
}

printf(
    "[%s] elegiveis=%d enviados=%d (followup_dias=%d)\n",
    date('Y-m-d H:i:s'), count($empresas), $enviados, $dias
);
