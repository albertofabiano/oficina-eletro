<?php
// Lembrete mensal por e-mail pra suporte@fixaos.com.br: entrar no painel da Locaweb (Servidores
// Cloud -> Cópias de segurança) e conferir/apagar backups antigos do vps68451, antes que a cota
// contratada encha de novo (já aconteceu em 31/08/2026 — ver CLAUDE.md "Backup Locaweb do
// vps68451: sem retenção automática, sem API"). A Locaweb não oferece retenção automática nem
// endpoint de API pra esse produto de backup (confirmado no developer portal deles — busca por
// "backup" não retorna nenhum endpoint), então a limpeza é sempre manual pelo painel web; este
// script só GARANTE que ninguém esqueça de checar, não faz a limpeza sozinho.
//
// Rodar via cron real, 1x/mês, ex. (todo dia 1 às 9h):
//   0 9 1 * * php /var/www/fixaos/scripts/lembrete_backup_locaweb.php >> /var/www/fixaos/storage/logs/lembrete_backup_cron.log 2>&1

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Services\EmailService;

$appConfig = require BASE_PATH . '/config/app.php';
date_default_timezone_set($appConfig['timezone']);

$destino = 'suporte@fixaos.com.br';
$assunto = 'Lembrete mensal: verificar espaço de backup do vps68451 (Locaweb)';
$html = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:480px;margin:0 auto">'
    . '<div style="background:#1e3a5f;padding:20px;text-align:center;border-radius:10px 10px 0 0">'
    . '<span style="color:#fff;font-size:22px;font-weight:900">Fixa<span style="color:#f97316">OS</span></span></div>'
    . '<div style="border:1px solid #e2e8f0;border-top:none;padding:24px;border-radius:0 0 10px 10px">'
    . '<p style="color:#0f172a;margin:0 0 12px;font-weight:700">Lembrete mensal de backup</p>'
    . '<p style="color:#374151;line-height:1.6;margin:0 0 8px">Entre no painel da Locaweb (Servidores Cloud → Cópias de segurança → vps68451) e confira o espaço usado dos GB contratados pro backup.</p>'
    . '<p style="color:#374151;line-height:1.6;margin:0 0 8px">Se estiver perto do limite, apague os backups mais antigos na aba "Cópias De Segurança" (marque as linhas mais antigas e exclua). A Locaweb não tem retenção automática nem API pra esse produto — a limpeza é sempre manual.</p>'
    . '<p style="color:#94a3b8;font-size:12px;margin:16px 0 0">Contexto: em 31/08/2026 o backup diário falhou porque a cota (10GB na época) ficou 96,7% cheia com 19 backups acumulados sem limpeza.</p>'
    . '</div></div>';

$ok = EmailService::send($destino, 'Suporte FixaOS', $assunto, $html);

printf("[%s] lembrete de backup Locaweb enviado=%s\n", date('Y-m-d H:i:s'), $ok ? 'sim' : 'nao');
