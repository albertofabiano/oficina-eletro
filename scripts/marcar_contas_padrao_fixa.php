<?php
/*
 * Backfill da migration 090 (financeiro_pessoal_contas.padrao) — marca, em cada perfil já
 * existente em produção, qual conta é a "padrão" (criada automaticamente junto com o perfil,
 * nunca deve poder ser arquivada — ver PerfilService::criarPerfilPessoalPadrao()/criarPerfil()
 * e FixaContasController::arquivar()).
 *
 * Nenhuma conta de produção tem essa coluna preenchida ainda (a coluna nasce com DEFAULT 0 pra
 * toda linha já existente) — sem marcar pelo menos uma por perfil, NENHUMA conta antiga fica
 * protegida contra arquivamento, mesmo as que foram criadas junto com o perfil.
 *
 * Heurística: a conta de MENOR id por perfil é a auto-criada — os dois pontos que criam conta
 * junto com o perfil (PerfilService::criarPerfilPessoalPadrao()/criarPerfil(), e o backfill
 * histórico scripts/migrar_fixa_perfis.php que reimplementa o mesmo INSERT) sempre inserem essa
 * conta na MESMA transação/request logo depois do INSERT do perfil — não existe caminho no
 * sistema pra uma conta criada manualmente pelo usuário ("+ Nova conta") entrar ANTES dela.
 * Perfil sem nenhuma conta (não deveria existir, mas defensivo) é só ignorado.
 *
 * Modo simulação por padrão (só mostra o que faria); --aplicar grava de verdade.
 * Rodar com: php scripts/marcar_contas_padrao_fixa.php [--aplicar]
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});

use App\Core\DB;

$aplicar = in_array('--aplicar', $argv, true);
$db = DB::pdo();

$stmt = $db->query(
    "SELECT c.id, c.perfil_id, c.nome, p.nome AS perfil_nome
     FROM financeiro_pessoal_contas c
     JOIN financeiro_pessoal_perfis p ON p.id = c.perfil_id
     WHERE c.id = (SELECT MIN(c2.id) FROM financeiro_pessoal_contas c2 WHERE c2.perfil_id = c.perfil_id)
       AND c.padrao = 0"
);
$aMarcar = $stmt->fetchAll();

echo $aplicar ? "Aplicando...\n" : "Simulação (rode com --aplicar pra gravar de verdade)\n";
echo "Contas a marcar como padrão: " . count($aMarcar) . "\n";

foreach ($aMarcar as $c) {
    echo "  conta #{$c['id']} \"{$c['nome']}\" — perfil #{$c['perfil_id']} \"{$c['perfil_nome']}\"\n";
    if ($aplicar) {
        $db->prepare("UPDATE financeiro_pessoal_contas SET padrao = 1 WHERE id = ?")->execute([$c['id']]);
    }
}

if ($aplicar) {
    echo "\nPronto. Pra desfazer:\n";
    $ids = array_column($aMarcar, 'id');
    if ($ids) {
        echo "  UPDATE financeiro_pessoal_contas SET padrao = 0 WHERE id IN (" . implode(',', $ids) . ");\n";
    }
}
