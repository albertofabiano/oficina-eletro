<?php
/**
 * Complementa scripts/seed_carteira_fixa_teste.php: liga a Agenda de verdade pra uma conta de
 * teste já populada — Contas Recorrentes (o "agendamento" real do módulo,
 * app/Services/Fixa/RecorrenteService.php) + os alertas de vencimento (bell/som,
 * financeiro_pessoal_eventos) pros lançamentos pendentes que o seed base já criou.
 *
 * Duas frentes:
 *
 * 1) Cadastra Contas Recorrentes de verdade (Aluguel, Conta de luz, Internet, Celular, uma
 *    assinatura de streaming, Salário) usando as MESMAS formas de pagamento que
 *    seed_carteira_fixa_teste.php já criou pro perfil (Pix/Débito/Boleto/Cartão de crédito) —
 *    não reimplementa nada, usa RecorrenteService::criar() + ::gerarPendentes(), o mesmo código
 *    que o controller real usa. `data_inicio` é sempre o dia 1 do mês SEGUINTE ao mês atual —
 *    de propósito, pra nunca colidir com os lançamentos "avulsos" que o seed base já gravou pro
 *    mês corrente (ex.: "Celular — Vivo Controle" de outubro já existe como lançamento comum;
 *    a recorrência só assume o controle a partir de novembro em diante). Rodar o script de novo
 *    não duplica — cada recorrência é buscada por descrição antes de criar.
 *
 * 2) Alertas: lançamentos pendentes que o seed base inseriu direto via SQL (sem passar pelo
 *    controller) nunca ganharam o evento-lembrete em `financeiro_pessoal_eventos` que
 *    FinanceiroPessoalController::sincronizarEventoDoLancamento() cria normalmente a cada
 *    save/edição — sem isso, o sino/som "chegou no horário" nunca dispara pra eles (a grade do
 *    Calendário já mostra esses lançamentos certo, isso é lido direto de `_lancamentos`; só o
 *    alerta sonoro/toast depende de `_eventos`). Este script fecha essa lacuna: pra cada
 *    lançamento pendente (vencimento setado, pago_em nulo) do perfil que ainda não tem evento
 *    vinculado, grava um, reproduzindo exatamente o mesmo formato de título que o controller
 *    gera.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o resumo do que seria criado).
 * Pra gravar:
 *   php scripts/seed_carteira_fixa_agenda.php --usuario=ID --aplicar
 *   php scripts/seed_carteira_fixa_agenda.php --email=fulano@exemplo.com --aplicar
 *   php scripts/seed_carteira_fixa_agenda.php --documento=12345678901 --aplicar
 *
 * Opções: as mesmas de seed_carteira_fixa_teste.php (--usuario=/--email=/--documento=/--perfil=),
 * mais --aplicar. Exige que o perfil já tenha passado por seed_carteira_fixa_teste.php antes (ou
 * já tenha as formas de pagamento Pix/Débito/Boleto/Cartão de crédito cadastradas manualmente).
 *
 * Pra desfazer as Contas Recorrentes criadas:
 *   DELETE FROM financeiro_pessoal_recorrentes WHERE id IN ({IDS impressos no resumo});
 * (os lançamentos/eventos que elas já tiverem gerado ficam órfãos — recorrente_id vira NULL via
 * ON DELETE SET NULL — e podem ser apagados à parte se quiser, pelos ids também impressos.)
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Services\Fixa\PerfilService;
use App\Services\Fixa\RecorrenteService;

$aplicar = in_array('--aplicar', $argv, true);
$argOpt = function (string $nome, $default) use ($argv) {
    foreach ($argv as $a) {
        if (str_starts_with($a, "--{$nome}=")) return substr($a, strlen($nome) + 3);
    }
    return $default;
};
$usuarioArg   = $argOpt('usuario', null);
$emailArg     = $argOpt('email', null);
$documentoArg = $argOpt('documento', null);
$perfilArg    = $argOpt('perfil', null);

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$db = DB::pdo();

// ---------------------------------------------------------------------------------------
// Resolve usuário/perfil — mesmo bloco de seed_carteira_fixa_teste.php
// ---------------------------------------------------------------------------------------
if ($usuarioArg === null && $emailArg === null && $documentoArg === null) {
    fwrite(STDERR, "Informe --usuario=ID, --email=E ou --documento=CPF do dono da conta de teste.\n");
    exit(1);
}

$perfilResolvidoPorDocumento = null;
if ($documentoArg !== null) {
    $digitos = preg_replace('/\D/', '', $documentoArg);
    if ($digitos === '') { fwrite(STDERR, "--documento= veio vazio depois de tirar a pontuação.\n"); exit(1); }
    $st = $db->prepare(
        "SELECT id, usuario_id, tipo, nome, documento, arquivado
         FROM financeiro_pessoal_perfis
         WHERE REPLACE(REPLACE(REPLACE(documento, '.', ''), '-', ''), '/', '') = ?"
    );
    $st->execute([$digitos]);
    $achados = $st->fetchAll();
    if (!$achados) { fwrite(STDERR, "Nenhum perfil da Carteira Fixa com o documento '{$documentoArg}'.\n"); exit(1); }
    if (count($achados) > 1) {
        fwrite(STDERR, "Mais de um perfil com esse documento — use --usuario=ID ou --perfil=ID:\n");
        foreach ($achados as $p) fwrite(STDERR, "  perfil #{$p['id']} (usuario_id={$p['usuario_id']}) — {$p['nome']}\n");
        exit(1);
    }
    $perfilResolvidoPorDocumento = $achados[0];
    $usuarioArg = (string) $perfilResolvidoPorDocumento['usuario_id'];
}

if ($emailArg !== null) {
    $st = $db->prepare("SELECT id, nome, email FROM usuarios WHERE email = ? LIMIT 1");
    $st->execute([$emailArg]);
    $usuario = $st->fetch();
    if (!$usuario) { fwrite(STDERR, "Nenhum usuário com e-mail '{$emailArg}'.\n"); exit(1); }
} else {
    $st = $db->prepare("SELECT id, nome, email FROM usuarios WHERE id = ? LIMIT 1");
    $st->execute([(int) $usuarioArg]);
    $usuario = $st->fetch();
    if (!$usuario) { fwrite(STDERR, "Usuário #{$usuarioArg} não encontrado.\n"); exit(1); }
}
$uid = (int) $usuario['id'];
echo "Usuário: #{$uid} — {$usuario['nome']} ({$usuario['email']})\n";

if ($perfilResolvidoPorDocumento !== null) {
    if (!empty($perfilResolvidoPorDocumento['arquivado'])) { fwrite(STDERR, "Perfil #{$perfilResolvidoPorDocumento['id']} está arquivado.\n"); exit(1); }
    $perfil = $perfilResolvidoPorDocumento;
} elseif ($perfilArg !== null) {
    $perfil = PerfilService::pertenceAoUsuario($db, (int) $perfilArg, $uid);
    if (!$perfil) { fwrite(STDERR, "Perfil #{$perfilArg} não existe ou não pertence a este usuário.\n"); exit(1); }
} else {
    $perfis = PerfilService::perfisDoUsuario($db, $uid, false);
    if (!$perfis) { fwrite(STDERR, "Usuário ainda não tem nenhum perfil na Carteira Fixa.\n"); exit(1); }
    if (count($perfis) > 1) {
        fwrite(STDERR, "Mais de um perfil — escolha com --perfil=ID:\n");
        foreach ($perfis as $p) fwrite(STDERR, "  #{$p['id']} — {$p['nome']} ({$p['tipo']})\n");
        exit(1);
    }
    $perfil = $perfis[0];
}
$perfilId = (int) $perfil['id'];
echo "Perfil: #{$perfilId} — {$perfil['nome']} (tipo={$perfil['tipo']})\n";

// ---------------------------------------------------------------------------------------
// Categorias — precisam já existir (seed_carteira_fixa_teste.php já semeia o PADRAO_PF)
// ---------------------------------------------------------------------------------------
$categorias = PerfilService::categoriasDoPerfil($db, $perfilId, $perfil['tipo']);
foreach (['moradia', 'assinaturas', 'salario'] as $chave) {
    if (!isset($categorias[$chave])) {
        fwrite(STDERR, "Categoria \"{$chave}\" não existe neste perfil — rode seed_carteira_fixa_teste.php --aplicar primeiro.\n");
        exit(1);
    }
}

// ---------------------------------------------------------------------------------------
// Formas de pagamento — precisam já existir (seed_carteira_fixa_teste.php cria as 4)
// ---------------------------------------------------------------------------------------
$stFormas = $db->prepare("SELECT id, nome FROM financeiro_pessoal_formas_pagamento WHERE perfil_id = ? AND arquivada = 0");
$stFormas->execute([$perfilId]);
$formasPorNome = [];
foreach ($stFormas->fetchAll() as $f) { $formasPorNome[$f['nome']] = (int) $f['id']; }
foreach (['Pix', 'Débito', 'Boleto', 'Cartão de crédito'] as $nomeNecessario) {
    if (!isset($formasPorNome[$nomeNecessario])) {
        fwrite(STDERR, "Forma de pagamento \"{$nomeNecessario}\" não existe neste perfil — rode seed_carteira_fixa_teste.php --aplicar primeiro, ou cadastre-a manualmente em Configurações.\n");
        exit(1);
    }
}

$contas = PerfilService::contasDoPerfil($db, $perfilId);
if (!$contas) { fwrite(STDERR, "Perfil #{$perfilId} não tem nenhuma conta cadastrada.\n"); exit(1); }
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Contas Recorrentes — começam no dia 1 do mês SEGUINTE (nunca colide com o mês corrente, já
// coberto por lançamentos avulsos do seed base).
// ---------------------------------------------------------------------------------------
$dataInicio = date('Y-m-01', strtotime('first day of next month'));

$moldes = [
    ['descricao' => 'Aluguel',                 'categoria' => 'moradia',     'tipo' => 'despesa', 'valor' => 1850.00, 'dia' => 5,  'forma' => 'Boleto'],
    ['descricao' => 'Conta de luz — Enel',      'categoria' => 'moradia',     'tipo' => 'despesa', 'valor' => 195.00,  'dia' => 10, 'forma' => 'Boleto'],
    ['descricao' => 'Internet — Vivo Fibra',    'categoria' => 'assinaturas','tipo' => 'despesa', 'valor' => 109.90,  'dia' => 15, 'forma' => 'Débito'],
    ['descricao' => 'Celular — Vivo Controle',  'categoria' => 'assinaturas','tipo' => 'despesa', 'valor' => 69.90,   'dia' => 20, 'forma' => 'Débito'],
    ['descricao' => 'Netflix',                  'categoria' => 'assinaturas','tipo' => 'despesa', 'valor' => 39.90,   'dia' => 8,  'forma' => 'Cartão de crédito'],
    ['descricao' => 'Salário mensal',           'categoria' => 'salario',     'tipo' => 'receita', 'valor' => 5000.00, 'dia' => 5,  'forma' => 'Pix'],
];

$stExiste = $db->prepare("SELECT id FROM financeiro_pessoal_recorrentes WHERE perfil_id = ? AND descricao = ? LIMIT 1");

$recorrentesCriados = [];
$recorrentesJaExistiam = [];
foreach ($moldes as $m) {
    $stExiste->execute([$perfilId, $m['descricao']]);
    if ($stExiste->fetchColumn()) {
        $recorrentesJaExistiam[] = $m['descricao'];
        continue;
    }

    if ($aplicar) {
        $id = RecorrenteService::criar($db, $uid, $perfilId, [
            'conta_id' => null,
            'forma_pagamento_id' => $formasPorNome[$m['forma']],
            'tipo' => $m['tipo'],
            'categoria' => $m['categoria'],
            'descricao' => $m['descricao'],
            'notas' => null,
            'valor' => $m['valor'],
            'dia_vencimento' => $m['dia'],
            'data_inicio' => $dataInicio,
            'data_fim' => null,
        ]);
        $recorrentesCriados[] = "#{$id} {$m['descricao']} (dia {$m['dia']}, {$m['forma']}, a partir de {$dataInicio})";
    } else {
        $recorrentesCriados[] = "{$m['descricao']} (dia {$m['dia']}, {$m['forma']}, a partir de {$dataInicio})";
    }
}

echo ($aplicar ? "Contas recorrentes criadas:\n" : "[simulação] Contas recorrentes que seriam criadas:\n");
foreach ($recorrentesCriados as $linha) { echo "  - {$linha}\n"; }
if ($recorrentesJaExistiam) {
    echo "Já existiam (puladas, sem duplicar): " . implode(', ', $recorrentesJaExistiam) . "\n";
}
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Materializa já o mês seguinte (lançamento + evento) pras recorrências recém-criadas, em vez
// de esperar o usuário abrir o módulo — mesma chamada que o controller real faz depois de
// cadastrar uma recorrência nova.
// ---------------------------------------------------------------------------------------
if ($aplicar && $recorrentesCriados) {
    $gerados = RecorrenteService::gerarPendentes($db, $uid, $perfilId);
    echo "{$gerados} lançamento(s) + evento(s) de agenda já materializados pro próximo mês.\n";
    echo str_repeat('-', 78) . "\n";
}

// ---------------------------------------------------------------------------------------
// Backfill de alertas: lançamentos pendentes (vencimento setado, ainda não pago) que o seed
// base inseriu direto via SQL nunca ganharam o evento-lembrete em `financeiro_pessoal_eventos`
// — sem ele, o sino/som "chegou no horário" não dispara pra eles (a grade do Calendário já
// mostra certo, é lido direto de `_lancamentos`; só o alerta sonoro depende de `_eventos`).
// ---------------------------------------------------------------------------------------
$stPendentes = $db->prepare(
    "SELECT l.id, l.descricao, l.valor, l.tipo, l.vencimento
     FROM financeiro_pessoal_lancamentos l
     LEFT JOIN financeiro_pessoal_eventos e ON e.lancamento_id = l.id
     WHERE l.usuario_id = ? AND l.perfil_id = ? AND l.vencimento IS NOT NULL AND l.pago_em IS NULL
       AND e.id IS NULL"
);
$stPendentes->execute([$uid, $perfilId]);
$pendentesSemEvento = $stPendentes->fetchAll();

echo ($aplicar ? "Alertas de agenda gerados pra lançamentos pendentes já existentes:\n" : "[simulação] Alertas de agenda que seriam gerados pra lançamentos pendentes já existentes:\n");
if (!$pendentesSemEvento) {
    echo "  (nenhum lançamento pendente sem alerta — nada a fazer aqui)\n";
} else {
    $insertEvento = $db->prepare(
        "INSERT INTO financeiro_pessoal_eventos (usuario_id, perfil_id, lancamento_id, titulo, data_hora) VALUES (?, ?, ?, ?, ?)"
    );
    foreach ($pendentesSemEvento as $l) {
        $tipoLabel = $l['tipo'] === 'receita' ? 'recebimento' : 'pagamento';
        $titulo = mb_substr(
            $l['descricao'] . ' — ' . $tipoLabel . ' de R$ ' . number_format((float) $l['valor'], 2, ',', '.') . ' vence hoje',
            0, 150
        );
        if ($aplicar) {
            $insertEvento->execute([$uid, $perfilId, $l['id'], $titulo, $l['vencimento'] . ' 08:00:00']);
        }
        echo "  - " . substr($l['vencimento'], 0, 10) . " · {$l['descricao']} · R$ " . number_format((float) $l['valor'], 2, ',', '.') . "\n";
    }
}
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Rode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Resumo final — estado real dos alertas depois da gravação
// ---------------------------------------------------------------------------------------
$stVencidos = $db->prepare(
    "SELECT COUNT(*) FROM financeiro_pessoal_lancamentos
     WHERE usuario_id = ? AND perfil_id = ? AND pago_em IS NULL AND vencimento IS NOT NULL AND vencimento < CURDATE()"
);
$stVencidos->execute([$uid, $perfilId]);
$vencidos = (int) $stVencidos->fetchColumn();

$stProximos = $db->prepare(
    "SELECT COUNT(*) FROM financeiro_pessoal_lancamentos
     WHERE usuario_id = ? AND perfil_id = ? AND pago_em IS NULL AND vencimento IS NOT NULL
       AND vencimento BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
);
$stProximos->execute([$uid, $perfilId]);
$proximos30d = (int) $stProximos->fetchColumn();

echo "Pronto. Agenda agora reflete:\n";
echo "  - {$vencidos} conta(s) vencida(s) (vão disparar o alerta 'vencido' no próximo acesso)\n";
echo "  - {$proximos30d} conta(s) a vencer nos próximos 30 dias\n";
echo "  - " . count($recorrentesCriados) . " conta(s) recorrente(s) agendada(s) pra se renovar sozinha(s) todo mês a partir de {$dataInicio}\n";
