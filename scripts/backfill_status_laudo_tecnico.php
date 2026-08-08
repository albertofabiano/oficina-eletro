<?php
// Backfill: cria o novo status nativo "Laudo Técnico" pra toda empresa que já existe
// (empresas novas já ganham ele no cadastro — ver LandingController::processarCadastro).
//
// Entra sempre imediatamente ANTES do status "Fechado" de cada empresa — mas como o
// status nativo pode ter sido reordenado por drag-and-drop (Config → Status de OS), a
// posição de "Fechado" varia por empresa. Por isso resolve a ordem individualmente
// (prefere codigo='fechado'; sem isso, cai no 1º tipo='entregue' por ordem — mesmo
// fallback usado em OrdemServicoController::fechar()), empurra tudo dali pra frente
// +1, e insere o novo status na vaga aberta.
//
// Idempotente: pula qualquer empresa que já tenha 'laudo_tecnico'. Pode rodar mais de
// uma vez sem duplicar.
//
// Rodar de dentro de /var/www/fixaos: php scripts/backfill_status_laudo_tecnico.php

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Core\DB;

$db = DB::pdo();

// Só empresas reais do sistema (têm OS de verdade) — exclui os milhares de perfis do
// Diretório de Assistências (tipo_conta='diretorio', nunca passaram pelo onboarding que
// cria os_status) e empresas desativadas.
$empresas = $db->query("SELECT id, nome_fantasia FROM empresas WHERE ativo = 1 AND tipo_conta = 'completo'")->fetchAll();

$criados = 0;
$pulados = 0;
$semAlvo = 0;

foreach ($empresas as $emp) {
    $eid = (int) $emp['id'];

    $jaTem = $db->prepare("SELECT COUNT(*) FROM os_status WHERE empresa_id = ? AND codigo = 'laudo_tecnico'");
    $jaTem->execute([$eid]);
    if ((int) $jaTem->fetchColumn() > 0) {
        $pulados++;
        continue;
    }

    $stmtAlvo = $db->prepare(
        "SELECT ordem FROM os_status WHERE empresa_id = ? AND (codigo = 'fechado' OR tipo = 'entregue')
         ORDER BY (codigo = 'fechado') DESC, ordem ASC LIMIT 1"
    );
    $stmtAlvo->execute([$eid]);
    $ordemAlvo = $stmtAlvo->fetchColumn();

    if ($ordemAlvo === false) {
        echo "[SEM ALVO] empresa {$eid} ({$emp['nome_fantasia']}) não tem status 'Fechado'/tipo entregue — pulando.\n";
        $semAlvo++;
        continue;
    }
    $ordemAlvo = (int) $ordemAlvo;

    $db->beginTransaction();
    try {
        $db->prepare("UPDATE os_status SET ordem = ordem + 1 WHERE empresa_id = ? AND ordem >= ?")
           ->execute([$eid, $ordemAlvo]);

        $db->prepare(
            "INSERT INTO os_status (empresa_id, codigo, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor, bloqueado)
             VALUES (?, 'laudo_tecnico', 'Laudo Técnico', '#0891b2', '#ffffff', ?, 'em_andamento', 1, 0, 1)"
        )->execute([$eid, $ordemAlvo]);

        $db->commit();
        $criados++;
    } catch (\Throwable $e) {
        $db->rollBack();
        echo "[ERRO] empresa {$eid} ({$emp['nome_fantasia']}): " . $e->getMessage() . "\n";
    }
}

echo "\nConcluído. Criados: {$criados} | Já existiam: {$pulados} | Sem status-alvo: {$semAlvo}\n";
