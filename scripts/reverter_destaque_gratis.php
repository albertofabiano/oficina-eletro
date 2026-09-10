<?php
/**
 * Reverte o "destaque" concedido de graça pelo botão "Ativar destaque grátis" (removido —
 * ver EmpresaController::ativarDestaqueGratis(), excluído; era a estratégia "isca grátis" do
 * Diretório, revertida a pedido do usuário: destaque volta a valer só pra quem pagou de
 * verdade). Deixa intocado quem tem uma assinatura PAGA de verdade (webhook da InfinitePay,
 * ver PagamentoController::webhook()) ou destaque concedido manualmente pelo Master
 * (MasterController::toggleDestaque()) — os dois SEMPRE gravam `diretorio_destaque_ate` com
 * uma data real de expiração. Só a ativação gratuita gravava `diretorio_destaque_ate=NULL`
 * (nunca vence) — é exatamente esse padrão (`diretorio_destaque != 'none' AND
 * diretorio_destaque_ate IS NULL`) que identifica quem pegou o destaque de graça.
 *
 * Reversível de propósito (não apaga nada, só zera 2 colunas) — o resumo final imprime o
 * UPDATE inverso pra reativar, caso precise.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra quantas/quais seriam afetadas).
 * Pra gravar:
 *   php scripts/reverter_destaque_gratis.php --aplicar
 */

define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/app/Helpers/functions.php';
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

$aplicar = in_array('--aplicar', $argv, true);
$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$stmt = $db->query(
    "SELECT id, nome_fantasia, cidade, uf, diretorio_destaque
       FROM empresas
      WHERE diretorio_destaque <> 'none'
        AND diretorio_destaque_ate IS NULL
      ORDER BY id"
);
$afetadas = $stmt->fetchAll();

if (!$afetadas) {
    echo "Nenhuma empresa com destaque gratuito ativo encontrada.\n";
    exit(0);
}

echo "Empresas com destaque GRATUITO (serão revertidas):\n";
foreach ($afetadas as $e) {
    echo sprintf(
        "  #%d — %s (%s/%s) — destaque atual: %s\n",
        $e['id'], $e['nome_fantasia'] ?: '(sem nome)', $e['cidade'] ?: '?', $e['uf'] ?: '?', $e['diretorio_destaque']
    );
}
echo str_repeat('-', 78) . "\n";
echo count($afetadas) . " empresa(s) seriam revertidas.\n\n";

if ($aplicar) {
    $ids = array_column($afetadas, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db->prepare(
        "UPDATE empresas SET diretorio_destaque='none', diretorio_destaque_ate=NULL
          WHERE id IN ($placeholders)"
    )->execute($ids);
    echo "Aplicado — destaque gratuito revertido pra " . count($ids) . " empresa(s).\n";
} else {
    echo "Simulação — nada foi gravado. Rode com --aplicar pra aplicar de verdade.\n";
}

echo "\nPra desfazer manualmente (reativar como estava antes, caso precise):\n";
foreach ($afetadas as $e) {
    echo "  UPDATE empresas SET diretorio_destaque='{$e['diretorio_destaque']}', diretorio_destaque_ate=NULL WHERE id={$e['id']};\n";
}
