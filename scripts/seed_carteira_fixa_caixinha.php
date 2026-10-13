<?php
/**
 * Complementa scripts/seed_carteira_fixa_teste.php/seed_carteira_fixa_agenda.php: cria uma
 * caixinha "Férias" (meta R$10.000, data-alvo 31/dez deste ano) com alguns meses de depósito já
 * feitos — pra conta de teste mostrar o módulo Caixinhas com progresso real, não vazio.
 *
 * Reaproveita App\Services\Fixa\CaixinhaService (criação da caixinha é um INSERT simples, igual
 * CaixinhasController::salvar(); os depósitos usam CaixinhaService::depositar()/
 * marcarTransferido() de verdade, não reimplementa nada).
 *
 * Por padrão roda em modo SIMULAÇÃO (não grava nada, só mostra o resumo do que seria criado).
 * Pra gravar:
 *   php scripts/seed_carteira_fixa_caixinha.php --usuario=ID --aplicar
 *   php scripts/seed_carteira_fixa_caixinha.php --email=fulano@exemplo.com --aplicar
 *   php scripts/seed_carteira_fixa_caixinha.php --documento=12345678901 --aplicar
 *
 * Opções: as mesmas de seed_carteira_fixa_teste.php (--usuario=/--email=/--documento=/--perfil=),
 * mais --aplicar. Idempotente: se o perfil já tem uma caixinha chamada "Férias", o script avisa
 * e não cria/deposita de novo (rodar duas vezes não duplica).
 *
 * Pra desfazer:
 *   DELETE FROM caixinhas WHERE id = {ID impresso no resumo};
 * (caixinha_movimentos vinculados somem sozinhos via ON DELETE CASCADE.)
 */

define('BASE_PATH', dirname(__DIR__));
spl_autoload_register(function (string $class) {
    $path = BASE_PATH . '/' . str_replace(['App\\', '\\'], ['app/', '/'], $class) . '.php';
    if (file_exists($path)) require $path;
});
require BASE_PATH . '/app/Helpers/functions.php';

use App\Core\DB;
use App\Services\Fixa\PerfilService;
use App\Services\Fixa\CaixinhaService;

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
// Resolve usuário/perfil — mesmo bloco de seed_carteira_fixa_teste.php/seed_carteira_fixa_agenda.php
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

$contas = PerfilService::contasDoPerfil($db, $perfilId);
if (!$contas) { fwrite(STDERR, "Perfil #{$perfilId} não tem nenhuma conta cadastrada.\n"); exit(1); }
$contaPadrao = null;
foreach ($contas as $c) { if ((int) $c['padrao'] === 1) { $contaPadrao = $c; break; } }
if (!$contaPadrao) $contaPadrao = $contas[0];
$contaId = (int) $contaPadrao['id'];
echo "Conta: #{$contaId} — {$contaPadrao['nome']}\n";
echo str_repeat('-', 78) . "\n";

// ---------------------------------------------------------------------------------------
// Idempotência: se já existe uma caixinha "Férias" neste perfil, não cria/deposita de novo.
// ---------------------------------------------------------------------------------------
$stExiste = $db->prepare("SELECT id FROM caixinhas WHERE perfil_id = ? AND nome = 'Férias' LIMIT 1");
$stExiste->execute([$perfilId]);
if ($stExiste->fetchColumn()) {
    echo "Já existe uma caixinha \"Férias\" neste perfil — nada a fazer (rodar de novo não duplica).\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Plano de depósitos — um por mês, nos últimos 6 meses (mesma janela do seed base de
// lançamentos), valores variando entre R$700 e R$1.400, acumulando progresso real rumo aos
// R$10.000 sem necessariamente bater a meta (faz sentido: "fim de ano" ainda não chegou).
// Todos os depósitos exceto o mais recente já foram "transferidos pro banco" (selo "Falta
// transferir" só aparece no último, como esperado de uma conta em uso de verdade).
// ---------------------------------------------------------------------------------------
$hoje = new DateTime(date('Y-m-d'));
$anoAtual = (int) $hoje->format('Y');
$dataMeta = sprintf('%04d-12-31', $anoAtual);

$money = fn(float $min, float $max) => round($min + mt_rand() / mt_getrandmax() * ($max - $min), 2);

$meses = [];
for ($i = 5; $i >= 0; $i--) {
    $meses[] = (clone $hoje)->modify("first day of -{$i} month");
}

$deposito = [];
$total = 0.0;
foreach ($meses as $m) {
    $ano = (int) $m->format('Y');
    $mesNum = (int) $m->format('n');
    $diaMax = (int) $m->format('t');
    $data = sprintf('%04d-%02d-%02d', $ano, $mesNum, min(5, $diaMax)); // logo depois do salário, dia 5
    $valor = $money(700, 1400);
    $deposito[] = ['data' => $data, 'valor' => $valor];
    $total += $valor;
}

$metaReais = 10000.00;
echo "Caixinha a criar: \"Férias\" — meta R$ " . number_format($metaReais, 2, ',', '.') . ", até " . date('d/m/Y', strtotime($dataMeta)) . "\n";
echo ($aplicar ? "Depósitos a gravar:\n" : "[simulação] Depósitos que seriam gravados:\n");
foreach ($deposito as $i => $d) {
    $status = $i === count($deposito) - 1 ? 'pendente de transferir' : 'já transferido';
    echo "  - " . $d['data'] . " · R$ " . number_format($d['valor'], 2, ',', '.') . " · {$status}\n";
}
echo "Total acumulado: R$ " . number_format($total, 2, ',', '.') . " (" . number_format($total / $metaReais * 100, 1, ',', '.') . "% da meta)\n";
echo str_repeat('-', 78) . "\n";

if (!$aplicar) {
    echo "Rode com --aplicar pra gravar de verdade.\n";
    exit(0);
}

// ---------------------------------------------------------------------------------------
// Grava de verdade
// ---------------------------------------------------------------------------------------
$metaCentavos = (int) round($metaReais * 100);
$db->prepare(
    "INSERT INTO caixinhas (usuario_id, perfil_id, nome, cor, icone, meta_centavos, data_meta, arquivada)
     VALUES (?, ?, 'Férias', '#FF8A3D', 'piggy-bank-fill', ?, ?, 0)"
)->execute([$uid, $perfilId, $metaCentavos, $dataMeta]);
$caixinhaId = (int) $db->lastInsertId();
echo "Caixinha \"Férias\" criada (#{$caixinhaId}).\n";

foreach ($deposito as $i => $d) {
    $valorCentavos = (int) round($d['valor'] * 100);
    $movId = CaixinhaService::depositar($db, $caixinhaId, $uid, $perfilId, $valorCentavos, $d['data'], $contaId, 'Depósito mensal pra Férias');
    $ultimo = $i === count($deposito) - 1;
    if (!$ultimo) {
        CaixinhaService::marcarTransferido($db, $movId, $uid, $perfilId);
    }
}

$saldoFinal = CaixinhaService::saldoCaixinha($db, $caixinhaId);
echo count($deposito) . " depósito(s) gravado(s) com sucesso.\n";
echo "Saldo da caixinha: R$ " . number_format($saldoFinal / 100, 2, ',', '.') . "\n";
echo "Pra desfazer: DELETE FROM caixinhas WHERE id = {$caixinhaId};\n";
