<?php
/**
 * Fixa Fase 1 (PF/PJ) — migra os dados do Financeiro Pessoal pro modelo de perfis/contas.
 * Pedido original: "Para cada usuário: criar perfil 'Pessoal' (pf) e conta 'Carteira'; ligar
 * todos os lançamentos e eventos existentes a eles."
 *
 * ATENÇÃO: faça backup do banco ANTES de rodar com --aplicar.
 *   mysqldump -u fixaos -p fixaos > backup_antes_fixa_fase1_$(date +%Y%m%d_%H%M).sql
 *
 * Roda em modo SIMULAÇÃO por padrão (mostra o que faria, sem gravar — cada usuário roda numa
 * transação própria, desfeita no final se não for --aplicar). Pra gravar de verdade:
 *   php scripts/migrar_fixa_perfis.php --aplicar
 *
 * Opções:
 *   --usuario=ID   migra só esse usuário (padrão: todos que já usam o módulo)
 *
 * Reversível: desfazer = apagar as linhas de `financeiro_pessoal_perfis` criadas aqui — o resto
 * desfaz sozinho via FK (`financeiro_pessoal_contas` é ON DELETE CASCADE do perfil;
 * `lancamentos.perfil_id`/`conta_id` e `eventos.perfil_id` são ON DELETE SET NULL, voltam a
 * ficar como estavam antes desta migração). O resumo final imprime os ids criados e o DELETE
 * pronto.
 *
 * O que faz, por usuário que já tem alguma linha em categorias/lançamentos/eventos:
 *   1. Cria o perfil "Pessoal" (tipo pf) — só se o usuário ainda não tiver NENHUM perfil.
 *   2. Cria a conta "Carteira" (tipo dinheiro, saldo_inicial 0, data_saldo_inicial = hoje) nesse
 *      perfil — só se ainda não tiver NENHUMA conta.
 *   3. Aponta toda categoria/lançamento/evento do usuário SEM perfil_id pra esse perfil; todo
 *      lançamento sem conta_id pra essa conta.
 *   4. Cria como categoria de verdade qualquer `categoria` usada em lançamento que ainda não
 *      tem linha correspondente em financeiro_pessoal_categorias — com nome acentuado quando a
 *      chave bate uma palavra conhecida (saude->Saúde, alimentacao->Alimentação,
 *      salario->Salário etc.), senão título simples a partir da chave.
 *   5. Lançamento com pago_em AINDA NÃO GRAVADO (campo existe desde a migration 081, mas pode
 *      estar vazio em lançamento antigo): data_hora até agora -> pago_em = data(data_hora);
 *      data_hora no futuro -> fica em aberto (pago_em continua nulo). NUNCA sobrescreve um
 *      pago_em que o usuário já tenha preenchido manualmente pela tela.
 *   6. "Recebido de empréstimo" (qualquer grafia/acento) é só LISTADO pra revisão — nada é
 *      alterado nesses lançamentos.
 *   7. Qualquer `descricao`/`categoria`/`categorias.nome` contendo "cccccc" ou "fffff" (dado de
 *      teste citado em conversa anterior) é só LISTADO — decisão de manter ou apagar é sua.
 *
 * Confere, por usuário: soma(valor) + contagem de linhas de financeiro_pessoal_lancamentos
 * ANTES de qualquer UPDATE == DEPOIS (este script nunca toca na coluna `valor` nem insere/
 * remove lançamento nenhum — só vincula perfil_id/conta_id e preenche pago_em que estava NULL).
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
if (!$aplicar) {
    echo "Lembrete: faça backup do banco antes de rodar com --aplicar:\n";
    echo "  mysqldump -u fixaos -p fixaos > backup_antes_fixa_fase1_" . date('Ymd_Hi') . ".sql\n";
}
echo str_repeat('-', 78) . "\n";

// Mesma lista de chaves/nomes acentuados já usada como semente padrão (migration 078/
// FinanceiroPessoalController::CATEGORIAS_PADRAO), mais algumas variações comuns de grafia sem
// acento que podem ter ficado gravadas em `lancamentos.categoria` por digitação direta ou
// import — não lidas do const privado do controller (é privado), replicado aqui de propósito,
// mesmo princípio já usado por reconciliar_categorias_financeiro_pessoal.php.
$NOMES_CONHECIDOS = [
    'alimentacao' => 'Alimentação',
    'transporte'  => 'Transporte',
    'lazer'       => 'Lazer',
    'compras'     => 'Compras',
    'moradia'     => 'Moradia',
    'saude'       => 'Saúde',
    'outros'      => 'Outros',
    'educacao'    => 'Educação',
    'assinaturas' => 'Assinaturas',
    'salario'     => 'Salário',
    'outras_receitas' => 'Outras receitas',
];

function nomeCategoriaCorrigido(string $chave, array $nomesConhecidos): string
{
    $k = strtolower(remover_acentos(trim($chave)));
    if (isset($nomesConhecidos[$k])) return $nomesConhecidos[$k];
    return financeiro_pessoal_categoria_humanizar($chave);
}

if ($usuarioArg !== null) {
    $usuarios = [(int) $usuarioArg];
} else {
    $usuarios = $db->query(
        "SELECT DISTINCT usuario_id FROM (
            SELECT usuario_id FROM financeiro_pessoal_categorias
            UNION SELECT usuario_id FROM financeiro_pessoal_lancamentos
            UNION SELECT usuario_id FROM financeiro_pessoal_eventos
         ) t ORDER BY usuario_id"
    )->fetchAll(PDO::FETCH_COLUMN);
}

if (!$usuarios) {
    echo "Nenhum usuário com dado no Financeiro Pessoal ainda.\n";
    exit(0);
}

$perfisCriados = [];
$achadosRecebidoEmprestimo = [];
$achadosDadoTeste = [];
$divergencias = [];
$totalCategoriasResgatadas = 0;
$totalPagoEmPreenchido = 0;

foreach ($usuarios as $usuarioId) {
    $usuarioId = (int) $usuarioId;
    $db->beginTransaction();

    $nomeSt = $db->prepare("SELECT nome FROM usuarios WHERE id = ?");
    $nomeSt->execute([$usuarioId]);
    $nomeUsuario = $nomeSt->fetchColumn() ?: ('id ' . $usuarioId);

    // ── Verificação ANTES (soma + contagem de lançamentos) ─────────────────────────────────
    $antesSt = $db->prepare("SELECT COUNT(*) qtd, COALESCE(SUM(valor), 0) soma FROM financeiro_pessoal_lancamentos WHERE usuario_id = ?");
    $antesSt->execute([$usuarioId]);
    $antes = $antesSt->fetch();

    // ── 1. Perfil "Pessoal" ─────────────────────────────────────────────────────────────────
    $perfSt = $db->prepare("SELECT id FROM financeiro_pessoal_perfis WHERE usuario_id = ? ORDER BY id LIMIT 1");
    $perfSt->execute([$usuarioId]);
    $perfilId = $perfSt->fetchColumn();

    $perfilCriadoAgora = false;
    if (!$perfilId) {
        $db->prepare(
            "INSERT INTO financeiro_pessoal_perfis (usuario_id, tipo, nome, cor, ordem, arquivado)
             VALUES (?, 'pf', 'Pessoal', '#8C7CFF', 0, 0)"
        )->execute([$usuarioId]);
        $perfilId = (int) $db->lastInsertId();
        $perfilCriadoAgora = true;
        $perfisCriados[] = $perfilId;
    }
    $perfilId = (int) $perfilId;

    // ── 2. Conta "Carteira" ─────────────────────────────────────────────────────────────────
    $contaSt = $db->prepare("SELECT id FROM financeiro_pessoal_contas WHERE perfil_id = ? ORDER BY id LIMIT 1");
    $contaSt->execute([$perfilId]);
    $contaId = $contaSt->fetchColumn();

    $contaCriadaAgora = false;
    if (!$contaId) {
        $db->prepare(
            "INSERT INTO financeiro_pessoal_contas (usuario_id, perfil_id, nome, tipo, saldo_inicial, data_saldo_inicial, cor, arquivada)
             VALUES (?, ?, 'Carteira', 'dinheiro', 0.00, CURDATE(), '#3CC9C0', 0)"
        )->execute([$usuarioId, $perfilId]);
        $contaId = (int) $db->lastInsertId();
        $contaCriadaAgora = true;
    }
    $contaId = (int) $contaId;

    // ── 3a. Backfill de categorias sem perfil ──────────────────────────────────────────────
    $db->prepare("UPDATE financeiro_pessoal_categorias SET perfil_id = ? WHERE usuario_id = ? AND perfil_id IS NULL")
        ->execute([$perfilId, $usuarioId]);

    // ── 4. Resgata categoria usada em lançamento mas sem linha correspondente ─────────────
    $chavesSt = $db->prepare("SELECT chave FROM financeiro_pessoal_categorias WHERE perfil_id = ?");
    $chavesSt->execute([$perfilId]);
    $chavesConhecidas = $chavesSt->fetchAll(PDO::FETCH_COLUMN);

    $usadasSt = $db->prepare("SELECT DISTINCT categoria FROM financeiro_pessoal_lancamentos WHERE usuario_id = ?");
    $usadasSt->execute([$usuarioId]);
    $categoriasUsadas = array_filter($usadasSt->fetchAll(PDO::FETCH_COLUMN), fn($c) => trim((string) $c) !== '');
    $orfas = array_values(array_diff($categoriasUsadas, $chavesConhecidas));

    $categoriasResgatadas = [];
    if ($orfas) {
        $posSt = $db->prepare("SELECT COALESCE(MAX(posicao), -1) + 1 FROM financeiro_pessoal_categorias WHERE perfil_id = ?");
        $posSt->execute([$perfilId]);
        $pos = (int) $posSt->fetchColumn();

        $ins = $db->prepare(
            "INSERT INTO financeiro_pessoal_categorias (usuario_id, perfil_id, chave, nome, tipo, cor, posicao)
             VALUES (?, ?, ?, ?, 'despesa', '#7A6A88', ?)"
        );
        foreach ($orfas as $chaveOrfa) {
            $nome = nomeCategoriaCorrigido($chaveOrfa, $NOMES_CONHECIDOS);
            $ins->execute([$usuarioId, $perfilId, $chaveOrfa, $nome, $pos]);
            $categoriasResgatadas[] = "{$chaveOrfa} -> \"{$nome}\"";
            $pos++;
        }
        $totalCategoriasResgatadas += count($categoriasResgatadas);
    }

    // ── 3b. Backfill de lançamentos sem perfil/conta ───────────────────────────────────────
    $db->prepare("UPDATE financeiro_pessoal_lancamentos SET perfil_id = ?, conta_id = ? WHERE usuario_id = ? AND perfil_id IS NULL")
        ->execute([$perfilId, $contaId, $usuarioId]);

    // ── 3c. Backfill de eventos sem perfil ──────────────────────────────────────────────────
    $db->prepare("UPDATE financeiro_pessoal_eventos SET perfil_id = ? WHERE usuario_id = ? AND perfil_id IS NULL")
        ->execute([$perfilId, $usuarioId]);

    // ── 5. pago_em: lançamento até hoje vira "pago" na própria data; futuro fica em aberto ──
    $pagoSt = $db->prepare(
        "UPDATE financeiro_pessoal_lancamentos
         SET pago_em = DATE(data_hora)
         WHERE usuario_id = ? AND pago_em IS NULL AND data_hora <= NOW()"
    );
    $pagoSt->execute([$usuarioId]);
    $qtdPagoEm = $pagoSt->rowCount();
    $totalPagoEmPreenchido += $qtdPagoEm;

    // ── 6. "Recebido de empréstimo" — só lista, não mexe ───────────────────────────────────
    $recSt = $db->prepare(
        "SELECT id, descricao, categoria, valor, data_hora FROM financeiro_pessoal_lancamentos
         WHERE usuario_id = ? AND (
            LOWER(descricao) LIKE '%recebido de emprestimo%' OR LOWER(descricao) LIKE '%recebido de empréstimo%'
            OR LOWER(categoria) LIKE '%recebido_de_emprestimo%' OR LOWER(categoria) LIKE '%recebido de emprestimo%'
         )"
    );
    $recSt->execute([$usuarioId]);
    $achadosRec = $recSt->fetchAll();
    foreach ($achadosRec as $a) {
        $achadosRecebidoEmprestimo[] = "usuário {$usuarioId} ({$nomeUsuario}) — lançamento #{$a['id']}: \"{$a['descricao']}\" (categoria: {$a['categoria']}, R$ {$a['valor']}, {$a['data_hora']})";
    }

    // ── 7. "cccccc"/"fffff" — dado de teste, só lista ──────────────────────────────────────
    $testeLancSt = $db->prepare(
        "SELECT id, descricao, categoria FROM financeiro_pessoal_lancamentos
         WHERE usuario_id = ? AND (LOWER(descricao) LIKE '%cccccc%' OR LOWER(descricao) LIKE '%fffff%' OR LOWER(categoria) LIKE '%fffff%')"
    );
    $testeLancSt->execute([$usuarioId]);
    foreach ($testeLancSt->fetchAll() as $a) {
        $achadosDadoTeste[] = "usuário {$usuarioId} ({$nomeUsuario}) — lançamento #{$a['id']}: \"{$a['descricao']}\" (categoria: {$a['categoria']})";
    }
    $testeCatSt = $db->prepare(
        "SELECT id, chave, nome FROM financeiro_pessoal_categorias
         WHERE usuario_id = ? AND (LOWER(chave) LIKE '%cccccc%' OR LOWER(chave) LIKE '%fffff%' OR LOWER(nome) LIKE '%cccccc%' OR LOWER(nome) LIKE '%fffff%')"
    );
    $testeCatSt->execute([$usuarioId]);
    foreach ($testeCatSt->fetchAll() as $a) {
        $achadosDadoTeste[] = "usuário {$usuarioId} ({$nomeUsuario}) — categoria #{$a['id']}: chave=\"{$a['chave']}\" nome=\"{$a['nome']}\"";
    }

    // ── Verificação DEPOIS ──────────────────────────────────────────────────────────────────
    $depoisSt = $db->prepare("SELECT COUNT(*) qtd, COALESCE(SUM(valor), 0) soma FROM financeiro_pessoal_lancamentos WHERE usuario_id = ?");
    $depoisSt->execute([$usuarioId]);
    $depois = $depoisSt->fetch();

    $bateu = ((int) $antes['qtd'] === (int) $depois['qtd']) && (abs((float) $antes['soma'] - (float) $depois['soma']) < 0.005);
    if (!$bateu) {
        $divergencias[] = "usuário {$usuarioId} ({$nomeUsuario}): antes qtd={$antes['qtd']} soma={$antes['soma']} / depois qtd={$depois['qtd']} soma={$depois['soma']}";
    }

    printf(
        "Usuário %d (%s): perfil %s (id %d), conta %s (id %d), %d categoria(s) resgatada(s), %d lançamento(s) com pago_em preenchido. Soma: R$ %s -> R$ %s (%s)\n",
        $usuarioId,
        $nomeUsuario,
        $perfilCriadoAgora ? 'CRIADO' : 'já existia',
        $perfilId,
        $contaCriadaAgora ? 'CRIADA' : 'já existia',
        $contaId,
        count($categoriasResgatadas),
        $qtdPagoEm,
        number_format((float) $antes['soma'], 2, ',', '.'),
        number_format((float) $depois['soma'], 2, ',', '.'),
        $bateu ? 'bateu' : 'DIVERGIU!!'
    );

    if ($aplicar) {
        $db->commit();
    } else {
        $db->rollBack();
    }
}

echo str_repeat('-', 78) . "\n";
echo ($aplicar ? "Gravado! " : "Simulado — ")
    . count($usuarios) . " usuário(s) processado(s), "
    . count($perfisCriados) . " perfil(is) 'Pessoal' criado(s), "
    . "{$totalCategoriasResgatadas} categoria(s) resgatada(s), "
    . "{$totalPagoEmPreenchido} lançamento(s) com pago_em preenchido.\n";

if ($divergencias) {
    echo "\n⚠️  DIVERGÊNCIA NA SOMA — investigue antes de confiar no resultado:\n";
    foreach ($divergencias as $d) { echo "  - {$d}\n"; }
} else {
    echo "Soma de lançamentos por usuário bateu igual antes e depois, em todos os " . count($usuarios) . " usuários.\n";
}

if ($achadosRecebidoEmprestimo) {
    echo "\n⚠️  \"Recebido de empréstimo\" encontrado (NÃO alterado — revise manualmente se isso deveria ser receita de verdade):\n";
    foreach ($achadosRecebidoEmprestimo as $a) { echo "  - {$a}\n"; }
} else {
    echo "\nNenhum lançamento \"recebido de empréstimo\" encontrado.\n";
}

if ($achadosDadoTeste) {
    echo "\n⚠️  Possível dado de teste encontrado (\"cccccc\"/\"fffff\" — NÃO alterado, decida se mantém ou apaga):\n";
    foreach ($achadosDadoTeste as $a) { echo "  - {$a}\n"; }
} else {
    echo "\nNenhum dado de teste (\"cccccc\"/\"fffff\") encontrado.\n";
}

if ($aplicar && $perfisCriados) {
    echo "\nPra desfazer (apaga os perfis criados agora — contas/vínculos somem/voltam a NULL sozinhos via FK):\n";
    echo "  DELETE FROM financeiro_pessoal_perfis WHERE id IN (" . implode(',', $perfisCriados) . ");\n";
}
