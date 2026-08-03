<?php
/**
 * Envia o aviso de atualização do FixaOS (redesenho da etapa Equipamento, fotos
 * do estado de entrada e modo escuro) via WhatsApp da PLATAFORMA pra todas as
 * empresas ativas com WhatsApp cadastrado.
 *
 * Uso:
 *   php tools/aviso_atualizacao.php --dry-run   (só lista quem receberia, não envia nada)
 *   php tools/aviso_atualizacao.php --enviar    (envia de verdade, com 5s pra cancelar)
 */

define('BASE_PATH', dirname(__DIR__));

spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Core\DB;
use App\Services\WhatsAppService;

$modo = $argv[1] ?? '';
if (!in_array($modo, ['--dry-run', '--enviar'], true)) {
    fwrite(STDERR, "Uso:\n  php tools/aviso_atualizacao.php --dry-run   (só lista, não envia)\n  php tools/aviso_atualizacao.php --enviar    (envia de verdade)\n");
    exit(1);
}

$mensagem = <<<'MSG'
Olá! 👋 Passando pra avisar que o FixaOS acabou de passar por algumas atualizações:

📋 Tela de criação e edição de Ordem de Serviço mais rápida — tipo, marca, estado de entrada e acessórios do equipamento ficaram mais simples de preencher.

📸 Agora dá pra tirar até 4 fotos do estado de entrada do equipamento (inclusive pelo celular) e elas ficam anexadas direto na OS.

🌙 E se você prefere trabalhar no modo escuro, é só clicar no ícone de sol/lua no topo da tela — o sistema lembra da sua escolha.

Se notar qualquer coisa estranha ou que não esteja funcionando como antes, é só responder esta mensagem ou chamar no suporte — corrigimos rapidinho. 🙌
MSG;

$pdo = DB::pdo();
$stmt = $pdo->query(
    "SELECT id, nome_fantasia, whatsapp FROM empresas
     WHERE ativo = 1 AND whatsapp IS NOT NULL AND TRIM(whatsapp) != ''
     ORDER BY id"
);
$empresas = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== " . count($empresas) . " empresa(s) ativa(s) com WhatsApp cadastrado ===\n\n";

if ($modo === '--dry-run') {
    foreach ($empresas as $e) {
        echo "  #{$e['id']} — {$e['nome_fantasia']} — {$e['whatsapp']}\n";
    }
    echo "\nNenhuma mensagem foi enviada (--dry-run). Rode com --enviar pra mandar de verdade.\n";
    exit(0);
}

echo "Enviando de verdade em 5 segundos... (Ctrl+C pra cancelar)\n";
sleep(5);

$sucesso = 0;
$falha   = 0;
foreach ($empresas as $e) {
    $ok = WhatsAppService::enviarTextoPlataforma($e['whatsapp'], $mensagem);
    echo ($ok ? "[OK]     " : "[FALHOU] ") . "#{$e['id']} — {$e['nome_fantasia']} — {$e['whatsapp']}\n";
    $ok ? $sucesso++ : $falha++;
    sleep(2); // evita rajada -- reduz risco de bloqueio/flood na API
}

echo "\n=== Concluído: {$sucesso} enviada(s), {$falha} falhou(aram) ===\n";
