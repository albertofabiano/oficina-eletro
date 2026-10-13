<?php
/**
 * Complementa scripts/seed_carteira_fixa_teste.php: adiciona entradas AVULSAS de freelance
 * (categoria "Outras receitas", não a categoria "Salário" fixa) nos últimos meses — diversifica
 * a renda da conta de teste além do salário fixo mensal já seedado.
 *
 * Cada lançamento passa por FormaPagamentoService::resolverParcelas() igual os outros seeds
 * (sempre 1 parcela aqui, forma=Pix — pagamento de freelance recebido via Pix é o caso comum no
 * Brasil); não reimplementa a matemática de data, só monta o INSERT com o resultado.
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o resumo do que seria criado).
 * Pra gravar:
 *   php scripts/seed_carteira_fixa_freelance.php --usuario=ID --aplicar
 *   php scripts/seed_carteira_fixa_freelance.php --email=fulano@exemplo.com --aplicar
 *   php scripts/seed_carteira_fixa_freelance.php --documento=12345678901 --aplicar
 *
 * Opções: as mesmas de seed_carteira_fixa_teste.php (--usuario=/--email=/--documento=/--perfil=),
 * mais --meses=N (padrão 6, mesma janela do seed base) e --aplicar.
 *
 * Idempotente: se já existe algum lançamento "Freelance — %" neste perfil, o script avisa e não
 * cria de novo (evita duplicar renda ao rodar a mesma rotina duas vezes).
 *
 * Pra desfazer (ajuste pelos ids impressos no resumo final):
 *   DELETE FROM financeiro_pessoal_lancamentos WHERE id BETWEEN {MIN_ID} AND {MAX_ID};
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Services\Fixa\PerfilService;
use App\Services\Fixa\FormaPagamentoService as FP;

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
$meses        = max(1, min(24, (int) $argOpt('meses', 6)));

echo ($aplicar ? "MODO APLICAR — vai gravar de verdade no banco.\n" : "MODO SIMULAÇÃO — nada será gravado (rode com --aplicar pra gravar de verdade).\n");
echo str_repeat('-', 78) . "\n";

$db = DB::pdo();

// ---------------------------------------------------------------------------------------
// Resolve usuário/perfil — mesmo bloco dos outros seeds da Carteira Fixa
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

if ($perfil['tipo'] !== 'pf') {
    fwrite(STDERR, "Este script só sabe gerar dados pra perfil tipo \"pf\" — perfil #{$perfilId} é \"{$perfil['tipo']}\".\n");
    exit(1);
}

$categorias = PerfilService::categoriasDoPerfil($db, $perfilId, 'pf');
if (!isset($categorias['outras_receitas'])) {
    fwrite(STDERR, "Categoria \"outras_receitas\" não existe neste perfil — rode seed_carteira_fixa_teste.php --aplicar primeiro.\n");
    exit(1);
}

$contas = PerfilService::contasDoPerfil($db, $perfilId);
if (!$contas) { fwrite(STDERR, "Perfil #{$perfilId} não tem nenhuma conta cadastrada.\n"); exit(1); }
$contaPadrao = null;
foreach ($contas as $c) { if ((int) $c['padrao'] === 1) { $contaPadrao = $c; break; } }
if (!$contaPadrao) $contaPadrao = $contas[0];
$contaId = (int) $contaPadrao['id'];
echo "Conta: #{$contaId} — {$contaPadrao['nome']}\n";

$stForma = $db->prepare("SELECT id, tipo, dia_fechamento, dia_vencimento, prazo_dias FROM financeiro_pessoal_formas_pagamento WHERE perfil_id = ? AND nome = 'Pix' LIMIT 1");
$stForma->execute([$perfilId]);
$formaPix = $stForma->fetch();
if (!$formaPix) { fwrite(STDERR, "Forma de pagamento \"Pix\" não existe neste perfil — rode seed_carteira_fixa_teste.php --aplicar primeiro.\n"); exit(1); }
$formaPix['id'] = (int) $formaPix['id'];
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Idempotência: já existe alguma entrada "Freelance — %" neste perfil? Não cria de novo.
// ---------------------------------------------------------------------------------------
$stExiste = $db->prepare("SELECT COUNT(*) FROM financeiro_pessoal_lancamentos WHERE perfil_id = ? AND descricao LIKE 'Freelance — %'");
$stExiste->execute([$perfilId]);
if ((int) $stExiste->fetchColumn() > 0) {
    echo "Já existem lançamentos \"Freelance — ...\" neste perfil — nada a fazer (rodar de novo não duplica).\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Monta o plano — 1 a 3 bicos de freelance por mês, últimos $meses meses (mesma janela do
// seed base), valores variando bastante (bico pequeno a projeto maior).
// ---------------------------------------------------------------------------------------
$hoje = new DateTime(date('Y-m-d'));
$mesesRange = [];
for ($i = $meses - 1; $i >= 0; $i--) {
    $mesesRange[] = (clone $hoje)->modify("first day of -{$i} month");
}

$pool = [
    'Design de logo', 'Consultoria de TI', 'Edição de vídeo', 'Aula particular',
    'Tradução de documento', 'Criação de site institucional', 'Social media (pacote mensal)',
    'Revisão de texto', 'Fotografia de evento', 'Manutenção de planilha',
];
$rand = fn(array $a) => $a[array_rand($a)];
$money = fn(float $min, float $max) => round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);

$plano = [];
foreach ($mesesRange as $inicioMes) {
    $ano = (int) $inicioMes->format('Y');
    $mesNum = (int) $inicioMes->format('n');
    $diaMax = (int) $inicioMes->format('t');
    foreach (range(1, mt_rand(1, 3)) as $_) {
        $dia = mt_rand(1, $diaMax);
        $plano[] = [
            'descricao' => 'Freelance — ' . $rand($pool),
            'valor' => $money(250, 1800),
            'dataEvento' => sprintf('%04d-%02d-%02d', $ano, $mesNum, $dia),
        ];
    }
}

$linhas = [];
$total = 0.0;
foreach ($plano as $item) {
    $p = FP::resolverParcelas($formaPix, $item['dataEvento'], $item['valor'], 1)[0];
    $jaPassou = $p['data'] <= $hoje->format('Y-m-d');
    $linhas[] = [
        'descricao'  => $item['descricao'],
        'valor'      => round($p['valor'], 2),
        'dataHora'   => $p['data'] . ' ' . sprintf('%02d:%02d:00', mt_rand(8, 21), mt_rand(0, 59)),
        'vencimento' => $jaPassou ? null : $p['data'],
        'pagoEm'     => $jaPassou ? $p['data'] : null,
    ];
    $total += $p['valor'];
}
usort($linhas, fn($a, $b) => $a['dataHora'] <=> $b['dataHora']);

echo count($linhas) . " entrada(s) de freelance preparada(s) para {$meses} mês(es), de " . $mesesRange[0]->format('m/Y') . " até " . end($mesesRange)->format('m/Y') . ".\n";
echo "Total: R$ " . number_format($total, 2, ',', '.') . "   Média mensal: R$ " . number_format($total / $meses, 2, ',', '.') . "\n";
echo ($aplicar ? "Lançamentos a gravar:\n" : "[simulação] Lançamentos que seriam gravados:\n");
foreach ($linhas as $l) {
    $status = $l['pagoEm'] ? 'pago' : 'pendente';
    echo "  " . substr($l['dataHora'], 0, 10) . " · {$l['descricao']} · R$ " . number_format($l['valor'], 2, ',', '.') . " · {$status}\n";
}
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Rode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Grava de verdade — mesma sequência dos outros seeds: INSERT, compra_id = próprio id (sempre
// 1 parcela aqui, mas mantém a mesma convenção já usada no resto do módulo), incrementa usos
// da forma, registra o aprendizado por beneficiário.
// ---------------------------------------------------------------------------------------
$insert = $db->prepare(
    "INSERT INTO financeiro_pessoal_lancamentos
        (usuario_id, perfil_id, conta_id, forma_pagamento_id, tipo, categoria, descricao, valor,
         data_hora, vencimento, pago_em, origem, compra_id, hora_informada)
     VALUES (?, ?, ?, ?, 'receita', 'outras_receitas', ?, ?, ?, ?, ?, 'manual', NULL, 0)"
);

$ids = [];
$usos = 0;
foreach ($linhas as $l) {
    $insert->execute([$uid, $perfilId, $contaId, $formaPix['id'], $l['descricao'], $l['valor'], $l['dataHora'], $l['vencimento'], $l['pagoEm']]);
    $novoId = (int) $db->lastInsertId();
    $db->prepare("UPDATE financeiro_pessoal_lancamentos SET compra_id = ? WHERE id = ?")->execute([$novoId, $novoId]);
    $ids[] = $novoId;
    $usos++;
    financeiro_pessoal_aprender_upsert($db, $uid, $perfilId, $l['descricao'], 'outras_receitas', $contaId, $formaPix['id']);
}
$db->prepare("UPDATE financeiro_pessoal_formas_pagamento SET usos = usos + ? WHERE id = ?")->execute([$usos, $formaPix['id']]);

$minId = min($ids);
$maxId = max($ids);
echo count($ids) . " lançamento(s) gravado(s) com sucesso (ids {$minId}–{$maxId}).\n";
echo "Pra desfazer: DELETE FROM financeiro_pessoal_lancamentos WHERE id BETWEEN {$minId} AND {$maxId};\n";
