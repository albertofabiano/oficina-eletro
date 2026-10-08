<?php
/*
 * Testes da cobrança/monetização standalone do Fixa (financeiro pessoal vendido à parte da
 * assistência técnica) e do controle de custo do scanner de contas por IA — Etapas 2/3/4 do
 * pedido de cobrança. Mesmo runner mínimo do projeto (sem PHPUnit/Composer, ver CLAUDE.md).
 *
 * Cobre: preços por período (config/planos_fixa.php), ciclo teste→ativa→inadimplente→
 * bloqueada, crédito proporcional no upgrade, limite de leituras do scanner, escolha
 * Haiku/Sonnet, e a regra de nunca passar de 7 dias grátis em nenhum fluxo.
 *
 * `App\Services\Fixa\AssinaturaService::confirmarPagamento()`/`cancelarComCredito()` usam
 * sintaxe só-MySQL (`CURDATE()`, `DATE_ADD(x, INTERVAL ? DAY)`, `GREATEST()`) que o SQLite não
 * consegue nem PARSEAR (não é falta de função — `INTERVAL ? DAY` não é uma expressão válida na
 * gramática do SQLite), então não dá pra rodar essas duas de ponta a ponta sem um MySQL de
 * verdade — mesma limitação de "não há banco de teste no projeto" já documentada em CLAUDE.md
 * (idêntica à já aceita em tests/marketing_sync_test.php pro mesmo motivo). `criarTeste()` e
 * `registrarTentativaFalha()` não têm esse problema (INSERT/UPDATE parametrizados simples, só
 * precisando de um `NOW()` registrado como função SQLite) e rodam de ponta a ponta de verdade.
 *
 * Rodar com: php tests/fixa_cobranca_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Helpers/functions.php';
require BASE_PATH . '/app/Services/Fixa/AssinaturaService.php';
require BASE_PATH . '/app/Services/VisionService.php';

use App\Services\Fixa\AssinaturaService;

$falhas = 0; $total = 0;
function assert_igual($esperado, $obtido, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($esperado === $obtido) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n      esperado: " . var_export($esperado, true) . "\n      obtido:   " . var_export($obtido, true) . "\n";
}
function assert_verdadeiro(bool $cond, string $descricao): void
{
    global $falhas, $total;
    $total++;
    if ($cond) { echo "  OK  $descricao\n"; return; }
    $falhas++;
    echo "FALHA $descricao\n";
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "== Preços por período (config/planos_fixa.php, via plano_preco_ciclo()) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $cfg = AssinaturaService::config();
    $individual = $cfg['planos'][0];
    $diretorio  = $cfg['planos'][1];
    assert_igual('fixa_individual', $individual['codigo'], 'config[0] é o Individual');
    assert_igual('fixa_diretorio', $diretorio['codigo'], 'config[1] é o Diretório');

    assert_igual(990,  plano_preco_ciclo($individual['preco_mensal'], $cfg['ciclos']['mensal']),     'Individual mensal R$9,90');
    assert_igual(2822, plano_preco_ciclo($individual['preco_mensal'], $cfg['ciclos']['trimestral']), 'Individual trimestral R$28,22 (5% off)');
    assert_igual(5643, plano_preco_ciclo($individual['preco_mensal'], $cfg['ciclos']['semestral']),  'Individual semestral R$56,43 (5% off)');
    assert_igual(11286, plano_preco_ciclo($individual['preco_mensal'], $cfg['ciclos']['anual']),     'Individual anual R$112,86 (5% off)');

    assert_igual(1990,  plano_preco_ciclo($diretorio['preco_mensal'], $cfg['ciclos']['mensal']),     'Diretório mensal R$19,90');
    assert_igual(5672,  plano_preco_ciclo($diretorio['preco_mensal'], $cfg['ciclos']['trimestral']), 'Diretório trimestral R$56,72 (5% off)');
    assert_igual(11343, plano_preco_ciclo($diretorio['preco_mensal'], $cfg['ciclos']['semestral']),  'Diretório semestral R$113,43 (5% off)');
    assert_igual(22686, plano_preco_ciclo($diretorio['preco_mensal'], $cfg['ciclos']['anual']),      'Diretório anual R$226,86 (5% off)');

    // Diretório sempre inclui tudo do Individual + diretório — checagem por CÓDIGO, não preço
    // (o próprio arquivo de config documenta esse cuidado).
    assert_igual(false, $individual['inclui_diretorio'], 'Individual não inclui diretório');
    assert_igual(true,  $diretorio['inclui_diretorio'],  'Diretório inclui diretório completo');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Ciclo teste → ativa → inadimplente → bloqueada (statusEfetivo/acessoCompleto) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $base = ['status' => 'teste', 'teste_fim' => null, 'bloqueada_em' => null, 'cancelada_em' => null];

    // NUNCA `$base + [...]` aqui — o operador `+` de array do PHP mantém o valor do lado
    // ESQUERDO quando a chave já existe nos dois (`$base` já tem 'teste_fim' => null), então
    // isso silenciosamente ignoraria qualquer override; `[...] + $base` (override primeiro)
    // é o jeito certo de "começar do $base, mas sobrescrever este campo".
    $testeDentroDoPrazo = ['teste_fim' => date('Y-m-d H:i:s', strtotime('+3 days'))] + $base;
    assert_igual('teste', AssinaturaService::statusEfetivo($testeDentroDoPrazo), 'teste com teste_fim no futuro continua "teste"');
    assert_igual(true, AssinaturaService::acessoCompleto($testeDentroDoPrazo), 'teste dentro do prazo tem acesso completo');

    // Pedido explícito: nunca confiar cegamente no status gravado se o teste já passou do prazo
    // e o cron ainda não rodou — statusEfetivo() recalcula pra 'inadimplente' na hora.
    $testeVencidoSemCron = ['teste_fim' => date('Y-m-d H:i:s', strtotime('-1 day'))] + $base;
    assert_igual('inadimplente', AssinaturaService::statusEfetivo($testeVencidoSemCron), 'teste_fim no passado recalcula pra "inadimplente" mesmo sem o cron já ter rodado');
    assert_igual(true, AssinaturaService::acessoCompleto($testeVencidoSemCron), 'inadimplente (grace period) ainda tem acesso completo — bloqueio só no dia 7');

    $ativa = ['status' => 'ativa', 'teste_fim' => null, 'bloqueada_em' => null, 'cancelada_em' => null];
    assert_igual('ativa', AssinaturaService::statusEfetivo($ativa), 'ativa continua ativa');
    assert_igual(true, AssinaturaService::acessoCompleto($ativa), 'ativa tem acesso completo');

    $inadimplente = ['status' => 'inadimplente', 'teste_fim' => null, 'bloqueada_em' => null, 'cancelada_em' => null];
    assert_igual(true, AssinaturaService::acessoCompleto($inadimplente), 'inadimplente (retentativa em andamento) ainda tem acesso completo');

    $bloqueadaRecente = ['status' => 'bloqueada', 'teste_fim' => null, 'bloqueada_em' => date('Y-m-d H:i:s', strtotime('-2 days')), 'cancelada_em' => null];
    assert_igual(false, AssinaturaService::acessoCompleto($bloqueadaRecente), 'bloqueada nunca tem acesso completo (só exportação)');
    assert_igual(true, AssinaturaService::somenteExportacao($bloqueadaRecente), 'bloqueada há 2 dias ainda dentro dos 30 dias de retenção — só exportação');
    assert_igual(false, AssinaturaService::elegivelParaPurga($bloqueadaRecente), 'bloqueada há 2 dias NÃO é elegível pra apagar ainda');

    $bloqueadaAntiga = ['status' => 'bloqueada', 'teste_fim' => null, 'bloqueada_em' => date('Y-m-d H:i:s', strtotime('-31 days')), 'cancelada_em' => null];
    assert_igual(false, AssinaturaService::somenteExportacao($bloqueadaAntiga), 'bloqueada há 31 dias já passou da retenção — não é mais "só exportação"');
    assert_igual(true, AssinaturaService::elegivelParaPurga($bloqueadaAntiga), 'bloqueada há 31 dias já é elegível pra apagar (nunca automático, só o CHECK)');

    // registrarTentativaFalha() de verdade, contra SQLite — só precisa de NOW() registrada,
    // o resto do UPDATE é parametrizado simples (sem DATE_ADD/CURDATE/GREATEST).
    $pdo = new PDO('sqlite::memory:');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'));
    $pdo->exec("CREATE TABLE fixa_assinaturas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER, plano TEXT, ciclo TEXT,
        status TEXT, teste_inicio TEXT, teste_fim TEXT, data_inicio TEXT, data_fim TEXT,
        valor_centavos INTEGER, credito_centavos INTEGER DEFAULT 0,
        indicado_por_usuario_id INTEGER, tentativas_falhas INTEGER DEFAULT 0,
        ultima_tentativa_em TEXT, bloqueada_em TEXT, cancelada_em TEXT
    )");
    $pdo->exec("INSERT INTO fixa_assinaturas (id, usuario_id, status, credito_centavos, tentativas_falhas) VALUES (1, 7, 'ativa', 0, 0)");

    AssinaturaService::registrarTentativaFalha($pdo, 1, 1);
    $a1 = $pdo->query("SELECT * FROM fixa_assinaturas WHERE id=1")->fetch();
    assert_igual('inadimplente', $a1['status'], 'dia 1 de falha: vira "inadimplente" (não bloqueia ainda)');
    assert_igual(1, (int) $a1['tentativas_falhas'], 'dia 1: conta 1 tentativa');
    assert_igual(null, $a1['bloqueada_em'], 'dia 1: ainda não marca bloqueada_em');

    AssinaturaService::registrarTentativaFalha($pdo, 1, 3);
    $a3 = $pdo->query("SELECT * FROM fixa_assinaturas WHERE id=1")->fetch();
    assert_igual('inadimplente', $a3['status'], 'dia 3 de falha: continua "inadimplente"');
    assert_igual(2, (int) $a3['tentativas_falhas'], 'dia 3: acumula 2 tentativas');

    AssinaturaService::registrarTentativaFalha($pdo, 1, 5);
    $a5 = $pdo->query("SELECT * FROM fixa_assinaturas WHERE id=1")->fetch();
    assert_igual('inadimplente', $a5['status'], 'dia 5 de falha: continua "inadimplente"');

    AssinaturaService::registrarTentativaFalha($pdo, 1, 7);
    $a7 = $pdo->query("SELECT * FROM fixa_assinaturas WHERE id=1")->fetch();
    assert_igual('bloqueada', $a7['status'], 'dia 7 de falha: bloqueia (7 dias de inadimplência, pedido explícito)');
    assert_verdadeiro(!empty($a7['bloqueada_em']), 'dia 7: marca bloqueada_em');
    assert_igual(false, AssinaturaService::acessoCompleto($a7), 'depois de bloqueada, acesso completo cai de vez');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Crédito proporcional no upgrade (Individual → Diretório) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    // Ciclo mensal (30 dias), 15 dias restantes, diferença mensal 1990-990=1000 centavos.
    // Proporcional = 1000 * (15/30) = 500. Sem crédito acumulado: cobra os 500 cheios.
    $assinaturaMetadeMes = [
        'plano' => 'fixa_individual', 'ciclo' => 'mensal', 'credito_centavos' => 0,
        'data_fim' => date('Y-m-d', strtotime('+15 days')),
    ];
    $valor = AssinaturaService::valorUpgrade($assinaturaMetadeMes);
    assert_verdadeiro($valor > 0 && $valor <= 600, 'upgrade na metade do mês cobra algo entre 1 centavo e R$6 (proporcional aos ~15 dias restantes), obtido=' . $valor);

    // Crédito acumulado (indicação, por ex.) abate da diferença ANTES de cobrar.
    $assinaturaComCredito = $assinaturaMetadeMes;
    $assinaturaComCredito['credito_centavos'] = 100000; // crédito bem maior que qualquer proporcional possível
    assert_igual(0, AssinaturaService::valorUpgrade($assinaturaComCredito), 'crédito acumulado suficiente zera o valor a cobrar no upgrade');

    // Sem dias restantes (data_fim já passou) -> nada a cobrar (não tem "resto" de período pra upgradar).
    $assinaturaVencida = $assinaturaMetadeMes;
    $assinaturaVencida['data_fim'] = date('Y-m-d', strtotime('-2 days'));
    assert_igual(0, AssinaturaService::valorUpgrade($assinaturaVencida), 'ciclo já vencido (0 dias restantes) não cobra upgrade nenhum');

    // "Upgrade" pro mesmo plano ou pra um mais barato nunca cobra nada (diferença <= 0).
    assert_igual(0, AssinaturaService::valorUpgrade($assinaturaMetadeMes, 'fixa_individual'), 'upgrade pro MESMO plano não cobra nada (diferença zero)');

    // Plano desconhecido nunca estoura erro, só não cobra nada (defensivo).
    assert_igual(0, AssinaturaService::valorUpgrade(['plano' => 'inexistente', 'ciclo' => 'mensal', 'credito_centavos' => 0, 'data_fim' => date('Y-m-d', strtotime('+10 days'))]), 'plano desconhecido não cobra nada (defensivo, sem erro)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Nunca passar de 7 dias grátis, em NENHUM fluxo (criarTeste) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $pdo2 = new PDO('sqlite::memory:');
    $pdo2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo2->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo2->exec("CREATE TABLE fixa_assinaturas (
        id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER, plano TEXT, ciclo TEXT,
        status TEXT, teste_inicio TEXT, teste_fim TEXT, data_inicio TEXT, data_fim TEXT,
        valor_centavos INTEGER, credito_centavos INTEGER DEFAULT 0,
        indicado_por_usuario_id INTEGER, tentativas_falhas INTEGER DEFAULT 0,
        ultima_tentativa_em TEXT, bloqueada_em TEXT, cancelada_em TEXT
    )");

    $diasEntre = function (string $a, string $b): float {
        return (strtotime($b) - strtotime($a)) / 86400;
    };

    // Varia plano/ciclo/indicação — NENHUMA combinação pode estender o teste além de 7 dias.
    // É exatamente o "NUNCA mais que 7 dias em nenhum fluxo" do pedido original.
    $cenarios = [
        [1, 'fixa_individual', 'mensal', null],
        [2, 'fixa_diretorio', 'anual', null],       // plano antecipado (anual) não estende o teste
        [3, 'fixa_individual', 'semestral', 55],    // indicação não estende o teste
        [4, 'fixa_diretorio', 'trimestral', null],
    ];
    foreach ($cenarios as [$uid, $plano, $ciclo, $indicadoPor]) {
        $a = AssinaturaService::criarTeste($pdo2, $uid, $plano, $ciclo, $indicadoPor);
        $dias = $diasEntre($a['teste_inicio'], $a['teste_fim']);
        assert_igual(7.0, round($dias, 2), "teste de {$plano}/{$ciclo}" . ($indicadoPor ? ' com indicação' : '') . " dura exatos 7 dias, obtido={$dias}");
        assert_igual('teste', $a['status'], "status nasce como 'teste'");
    }

    // valor_centavos gravado já reflete o preço do CICLO escolhido (ex.: anual com desconto),
    // mesmo sendo um teste grátis — é o valor que vai ser cobrado no dia 8.
    $cfg = AssinaturaService::config();
    $precoDiretorioAnual = plano_preco_ciclo(1990, $cfg['ciclos']['anual']);
    $assinaturaAnual = AssinaturaService::doUsuario($pdo2, 2);
    assert_igual($precoDiretorioAnual, (int) $assinaturaAnual['valor_centavos'], 'valor_centavos do teste já reflete o preço do ciclo anual escolhido (22686)');

    // Plano inexistente nunca cria teste nenhum.
    $lancouExcecao = false;
    try { AssinaturaService::criarTeste($pdo2, 999, 'plano_que_nao_existe'); } catch (\InvalidArgumentException $e) { $lancouExcecao = true; }
    assert_verdadeiro($lancouExcecao, 'plano desconhecido nunca cria um teste (lança exceção em vez de criar algo errado)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Limite de leituras do scanner (mesma regra de fixa_scanner_verificar()) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
// fixa_scanner_verificar() chama \App\Core\DB::pdo() direto (sem jeito de injetar uma conexão
// de teste — DB::connection() sempre tenta abrir o MySQL real de config/database.php, que nem
// existe neste sandbox). Por isso a mesma REGRA é replicada aqui contra SQLite (a contagem) +
// os MESMOS arquivos de config reais do projeto (os limites não são inventados no teste).
{
    $pdo3 = new PDO('sqlite::memory:');
    $pdo3->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo3->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo3->exec("CREATE TABLE fixa_scanner_leituras (id INTEGER PRIMARY KEY AUTOINCREMENT, usuario_id INTEGER, referencia_mes TEXT)");

    function fixa_scanner_verificar_replica(PDO $db, int $usuarioId, array $empresa): array
    {
        $ref = date('Y-m');
        $st = $db->prepare("SELECT COUNT(*) FROM fixa_scanner_leituras WHERE usuario_id=? AND referencia_mes=?");
        $st->execute([$usuarioId, $ref]);
        $usado = (int) $st->fetchColumn();

        $viaPlanoEmpresa = !empty($empresa['reivindicada']) && in_array($empresa['plano_atual'] ?? '', ['autonomo', 'oficina', 'empresa'], true);
        if ($viaPlanoEmpresa) {
            $planosCfg = require BASE_PATH . '/config/planos.php';
            $limite = 0;
            foreach ($planosCfg['planos'] as $p) { if ($p['codigo'] === $empresa['plano_atual']) { $limite = (int) ($p['scan_fixa_conta_mes'] ?? 0); break; } }
        } else {
            $cfgFixa = require BASE_PATH . '/config/planos_fixa.php';
            $limite = (int) ($cfgFixa['scanner_leituras_mes'] ?? 100);
        }
        if ($limite <= 0) return ['liberado' => true, 'usado' => $usado, 'limite' => $limite];
        return ['liberado' => $usado < $limite, 'usado' => $usado, 'limite' => $limite];
    }

    $registrarLeitura = function (PDO $db, int $uid, int $qtd) {
        $ref = date('Y-m');
        for ($i = 0; $i < $qtd; $i++) {
            $db->prepare("INSERT INTO fixa_scanner_leituras (usuario_id, referencia_mes) VALUES (?, ?)")->execute([$uid, $ref]);
        }
    };

    // Standalone (sem plano de empresa) — limite 100/mês, config/planos_fixa.php.
    $semEmpresa = [];
    $registrarLeitura($pdo3, 10, 99);
    $r99 = fixa_scanner_verificar_replica($pdo3, 10, $semEmpresa);
    assert_igual(true, $r99['liberado'], '99/100 leituras no mês: ainda liberado');
    assert_igual(100, $r99['limite'], 'limite standalone é 100 (config/planos_fixa.php)');

    $registrarLeitura($pdo3, 10, 1); // completa 100
    $r100 = fixa_scanner_verificar_replica($pdo3, 10, $semEmpresa);
    assert_igual(false, $r100['liberado'], '100/100 leituras: limite atingido, bloqueia (avisa e permite lançamento manual)');

    // Empresa com plano Autônomo (scan_fixa_conta_mes=40, config/planos.php) — limite PRÓPRIO,
    // diferente do standalone.
    $empresaAutonomo = ['reivindicada' => 1, 'plano_atual' => 'autonomo'];
    $registrarLeitura($pdo3, 20, 40);
    $rAutonomo = fixa_scanner_verificar_replica($pdo3, 20, $empresaAutonomo);
    assert_igual(40, $rAutonomo['limite'], 'empresa Autônomo usa o limite do PRÓPRIO plano (40), não o standalone (100)');
    assert_igual(false, $rAutonomo['liberado'], '40/40 leituras do plano Autônomo: limite atingido');

    // Top Empresa (scan_fixa_conta_mes=0) — 0 = ilimitado, nunca bloqueia.
    $empresaTop = ['reivindicada' => 1, 'plano_atual' => 'empresa'];
    $registrarLeitura($pdo3, 30, 500);
    $rTop = fixa_scanner_verificar_replica($pdo3, 30, $empresaTop);
    assert_igual(true, $rTop['liberado'], 'plano Top Empresa (scan_fixa_conta_mes=0) nunca bloqueia, mesmo com 500 leituras no mês');

    // Empresa não reivindicada ou com plano básico/sem plano cai no limite STANDALONE (100) —
    // mesmo critério de financeiro_pessoal_liberado(), que só libera de graça pra quem tem
    // autonomo/oficina/empresa reivindicado.
    $empresaBasico = ['reivindicada' => 1, 'plano_atual' => 'basico'];
    $rBasico = fixa_scanner_verificar_replica($pdo3, 40, $empresaBasico);
    assert_igual(100, $rBasico['limite'], 'empresa no plano Básico (sem Fixa de graça) cai no limite standalone (100)');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Escolha Haiku/Sonnet (VisionService::contaPrecisaEscalonar(), via Reflection) ==\n";
// ────────────────────────────────────────────────────────────────────────────────────────────
{
    $metodo = new ReflectionMethod(\App\Services\VisionService::class, 'contaPrecisaEscalonar');
    $metodo->setAccessible(true);
    $escalona = fn(array $r) => $metodo->invoke(null, $r);

    $confiancaAltaAlta = ['valor' => 'alta', 'vencimento' => 'alta', 'codigo' => 'alta'];

    $resultadoCompleto = ['valor' => 187.40, 'vencimento' => '2026-11-10', 'confianca' => $confiancaAltaAlta];
    assert_igual(false, $escalona($resultadoCompleto), 'Haiku leu valor+vencimento com confiança alta: NÃO escalona pro Sonnet (fica mais barato)');

    $semValor = ['valor' => 0.0, 'vencimento' => '2026-11-10', 'confianca' => $confiancaAltaAlta];
    assert_igual(true, $escalona($semValor), 'faltou o VALOR: escalona pro Sonnet');

    $semVencimento = ['valor' => 100.0, 'vencimento' => '', 'confianca' => $confiancaAltaAlta];
    assert_igual(true, $escalona($semVencimento), 'faltou o VENCIMENTO: escalona pro Sonnet');

    $confiancaBaixaNoCodigo = ['valor' => 100.0, 'vencimento' => '2026-11-10', 'confianca' => ['valor' => 'alta', 'vencimento' => 'alta', 'codigo' => 'baixa']];
    assert_igual(true, $escalona($confiancaBaixaNoCodigo), 'confiança BAIXA no código de barras/Pix: escalona pro Sonnet (resposta inconsistente)');

    $confiancaBaixaNoValor = ['valor' => 100.0, 'vencimento' => '2026-11-10', 'confianca' => ['valor' => 'baixa', 'vencimento' => 'alta', 'codigo' => 'alta']];
    assert_igual(true, $escalona($confiancaBaixaNoValor), 'confiança BAIXA no valor: escalona pro Sonnet');
}

// ────────────────────────────────────────────────────────────────────────────────────────────
echo "\n== Resultado ==\n";
echo "Total: $total | Falhas: $falhas\n";
exit($falhas > 0 ? 1 : 0);
