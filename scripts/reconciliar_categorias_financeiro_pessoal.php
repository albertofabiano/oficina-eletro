<?php
/**
 * Reconcilia as categorias do Financeiro Pessoal pra TODO usuário que já usa o módulo —
 * pedido do usuário vendo a tela real: lançamentos mostrando a `categoria` crua
 * ("alimentacao", "recebido_de_emprestimo", "ffffff") em vez do nome de verdade, enquanto a
 * tela de Categorias só listava "Transporte".
 *
 * Causa raiz (corrigida em FinanceiroPessoalController::categoriasDoUsuario()): o método só
 * semeava as 7 categorias padrão quando o usuário tinha ZERO categorias — quem já tinha
 * qualquer categoria avulsa (ex.: 1 só) nunca ganhava o resto, deixando `categoria` de
 * lançamento sem nenhuma linha correspondente em `financeiro_pessoal_categorias` pra sempre.
 * O fix no controller já evita isso daqui pra frente; este script é o BACKFILL retroativo
 * pra quem já ficou nesse estado antes do fix — faz duas coisas, por usuário já envolvido com
 * o módulo (tem linha em `financeiro_pessoal_categorias` OU `financeiro_pessoal_lancamentos`):
 *
 *   1. Chama `FinanceiroPessoalController::categoriasDoUsuario()` de verdade (mesma lógica do
 *      controller, não duplicada aqui) — tapa os padrões que faltam.
 *   2. Varre os lançamentos do usuário atrás de qualquer `categoria` que ainda assim não bate
 *      com NENHUMA linha (nem ativa, nem excluída) — sobra de categoria criada fora do fluxo
 *      normal (ex.: a extinta criação inline direto no card do lançamento, já removida do
 *      sistema) — e cria uma linha "resgatada" pra ela, com nome humanizado a partir da
 *      chave (ex. "ffffff" -> "Ffffff", "recebido_de_emprestimo" -> "Recebido De Emprestimo")
 *      e cor neutra. Fica disponível no CRUD de Categorias pra renomear/colorir de verdade.
 *
 * Roda em modo SIMULAÇÃO por padrão (mostra o que faria, sem gravar — cada usuário roda numa
 * transação própria, desfeita no final se não for --aplicar). Pra gravar de verdade:
 *   php scripts/reconciliar_categorias_financeiro_pessoal.php --aplicar
 *
 * Opções:
 *   --usuario=ID   reconcilia só esse usuário (padrão: todos que já usam o módulo)
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

$aplicar = in_array('--aplicar', $argv, true);

$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$usuarioArg = $argOpt('usuario', null);

$db = App\Core\DB::pdo();

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

if ($usuarioArg !== null) {
    $usuarios = [(int) $usuarioArg];
} else {
    $usuarios = $db->query(
        "SELECT DISTINCT usuario_id FROM (
            SELECT usuario_id FROM financeiro_pessoal_categorias
            UNION
            SELECT usuario_id FROM financeiro_pessoal_lancamentos
         ) t ORDER BY usuario_id"
    )->fetchAll(PDO::FETCH_COLUMN);
}

if (!$usuarios) {
    echo "Nenhum usuário com dado no Financeiro Pessoal ainda.\n";
    exit(0);
}

$totalPadraoCriadas = 0;
$totalResgatadasCriadas = 0;
$usuariosAfetados = 0;

foreach ($usuarios as $usuarioId) {
    $usuarioId = (int) $usuarioId;
    $db->beginTransaction();

    $nomeSt = $db->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $nomeSt->execute([$usuarioId]);
    $nomeUsuario = $nomeSt->fetchColumn() ?: ('id ' . $usuarioId);

    // 1) Tapa os padrões que faltam — mesma lógica do controller, não duplicada aqui.
    $antesSt = $db->prepare("SELECT chave FROM financeiro_pessoal_categorias WHERE usuario_id = ?");
    $antesSt->execute([$usuarioId]);
    $chavesAntes = $antesSt->fetchAll(PDO::FETCH_COLUMN);

    App\Controllers\FinanceiroPessoalController::categoriasDoUsuario($db, $usuarioId);

    $depoisSt = $db->prepare("SELECT chave FROM financeiro_pessoal_categorias WHERE usuario_id = ?");
    $depoisSt->execute([$usuarioId]);
    $chavesConhecidas = $depoisSt->fetchAll(PDO::FETCH_COLUMN);

    $padraoCriadas = array_values(array_diff($chavesConhecidas, $chavesAntes));

    // 2) Resgata categoria de lançamento que ainda não bate com nenhuma chave conhecida.
    $usadasSt = $db->prepare("SELECT DISTINCT categoria FROM financeiro_pessoal_lancamentos WHERE usuario_id = ?");
    $usadasSt->execute([$usuarioId]);
    $categoriasUsadas = array_filter($usadasSt->fetchAll(PDO::FETCH_COLUMN), fn($c) => trim((string) $c) !== '');

    $orfas = array_values(array_diff($categoriasUsadas, $chavesConhecidas));

    $resgatadas = [];
    if ($orfas) {
        $posSt = $db->prepare("SELECT COALESCE(MAX(posicao), -1) + 1 FROM financeiro_pessoal_categorias WHERE usuario_id = ?");
        $posSt->execute([$usuarioId]);
        $pos = (int) $posSt->fetchColumn();

        $ins = $db->prepare(
            "INSERT INTO financeiro_pessoal_categorias (usuario_id, chave, nome, cor, posicao)
             VALUES (?, ?, ?, ?, ?)"
        );
        foreach ($orfas as $chaveOrfa) {
            $nome = financeiro_pessoal_categoria_humanizar($chaveOrfa);
            $ins->execute([$usuarioId, $chaveOrfa, $nome, '#7A6A88', $pos]);
            $resgatadas[] = "{$chaveOrfa} -> \"{$nome}\"";
            $pos++;
        }
    }

    if ($padraoCriadas || $resgatadas) {
        $usuariosAfetados++;
        printf("Usuário %d (%s):\n", $usuarioId, $nomeUsuario);
        if ($padraoCriadas) {
            printf("  padrão(ões) completado(s): %s\n", implode(', ', $padraoCriadas));
            $totalPadraoCriadas += count($padraoCriadas);
        }
        if ($resgatadas) {
            printf("  categoria(s) resgatada(s): %s\n", implode(', ', $resgatadas));
            $totalResgatadasCriadas += count($resgatadas);
        }
    }

    if ($aplicar) {
        $db->commit();
    } else {
        $db->rollBack();
    }
}

echo str_repeat('-', 78) . "\n";
echo ($aplicar ? "Gravado! " : "Simulado — ")
    . "{$usuariosAfetados} usuário(s) afetado(s), "
    . "{$totalPadraoCriadas} categoria(s) padrão completada(s), "
    . "{$totalResgatadasCriadas} categoria(s) resgatada(s) a partir de lançamento órfão.\n";
