<?php
/*
 * Testes de App\Services\Marketing\QueueService — geração de sugestões sem duplicar (nem
 * repetir uma rejeitada há menos de 7 dias), aprovar/rejeitar (só transiciona de 'pending',
 * nunca decide duas vezes o mesmo pedido, isolado por empresa), e o executor (simulação vs.
 * execução de verdade, sucesso e falha, payload inválido falha mesmo em dry_run). SQL portável
 * (sem upsert MySQL-específico como em SyncService) — roda de ponta a ponta contra SQLite em
 * memória, reproduzindo a trava de unicidade "só 1 pendente por campanha+ação" da migration 069
 * via coluna gerada equivalente (IF()->CASE WHEN, sintaxe SQLite). Rodar com:
 *   php tests/marketing_queue_test.php
 */

define('BASE_PATH', dirname(__DIR__));
require BASE_PATH . '/app/Services/Marketing/Money.php';
require BASE_PATH . '/app/Services/Marketing/Dates.php';
require BASE_PATH . '/app/Services/Marketing/Dashboard.php';
require BASE_PATH . '/app/Services/Marketing/Rules.php';
require BASE_PATH . '/app/Services/Marketing/ActionPayload.php';
require BASE_PATH . '/app/Services/Marketing/AdPlatformInterface.php';
require BASE_PATH . '/app/Services/Marketing/QueueService.php';

use App\Services\Marketing\AdPlatformInterface;
use App\Services\Marketing\QueueService;

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

class PlataformaDeTeste implements AdPlatformInterface
{
    public array $chamadas = [];
    public bool $falhar = false;

    public function id(): string { return 'teste'; }
    public function listCampaigns(string $accountExternalId): array { return []; }
    public function getDailyInsights(string $accountExternalId, string $from, string $to): array { return []; }

    public function setCampaignStatus(string $accountExternalId, string $campaignExternalId, string $status): void
    {
        if ($this->falhar) throw new \RuntimeException('Falha simulada ao chamar a API da plataforma.');
        $this->chamadas[] = ['metodo' => 'setCampaignStatus', 'conta' => $accountExternalId, 'campanha' => $campaignExternalId, 'status' => $status];
    }

    public function setDailyBudget(string $accountExternalId, string $campaignExternalId, int $dailyBudgetCents): void
    {
        if ($this->falhar) throw new \RuntimeException('Falha simulada ao chamar a API da plataforma.');
        $this->chamadas[] = ['metodo' => 'setDailyBudget', 'conta' => $accountExternalId, 'campanha' => $campaignExternalId, 'orcamento' => $dailyBudgetCents];
    }
}

function montarBanco(): PDO
{
    $db = new PDO('sqlite::memory:');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->exec('CREATE TABLE empresas (id INTEGER PRIMARY KEY)');
    $db->exec('CREATE TABLE mkt_ad_accounts (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, platform TEXT, external_id TEXT)');
    $db->exec('CREATE TABLE mkt_campaigns (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, ad_account_id INTEGER, external_id TEXT, name TEXT, status TEXT, daily_budget_cents INTEGER)');
    $db->exec('CREATE TABLE mkt_daily_insights (campaign_id INTEGER, date TEXT, empresa_id INTEGER, spend_cents INTEGER, impressions INTEGER, clicks INTEGER, leads INTEGER)');
    $db->exec("CREATE TABLE mkt_action_requests (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        empresa_id INTEGER NOT NULL,
        campaign_id INTEGER NOT NULL,
        action_type TEXT NOT NULL,
        payload TEXT NULL,
        reason TEXT NOT NULL,
        source TEXT NOT NULL DEFAULT 'rule',
        rule_id TEXT NULL,
        status TEXT NOT NULL DEFAULT 'pending',
        requested_by INTEGER NULL,
        requested_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
        decided_by INTEGER NULL,
        decided_at TEXT NULL,
        executed_at TEXT NULL,
        dry_run INTEGER NULL,
        error TEXT NULL,
        pending_key TEXT GENERATED ALWAYS AS (CASE WHEN status = 'pending' THEN campaign_id || ':' || action_type ELSE NULL END) STORED,
        UNIQUE (pending_key)
    )");
    $db->exec('CREATE TABLE mkt_audit_log (id INTEGER PRIMARY KEY AUTOINCREMENT, empresa_id INTEGER, usuario_id INTEGER, action TEXT, entity_type TEXT, entity_id INTEGER, details TEXT, created_at TEXT DEFAULT CURRENT_TIMESTAMP)');
    return $db;
}

// ────────────────────────────────────────────────────────────────────────────────────────────
// gerarSugestoes(): dispara pause-no-leads, nunca duplica, respeita cooldown de rejeição
// ────────────────────────────────────────────────────────────────────────────────────────────
$db = montarBanco();
$db->exec('INSERT INTO empresas (id) VALUES (1)');
$db->exec("INSERT INTO mkt_ad_accounts (id, empresa_id, platform, external_id) VALUES (1, 1, 'fake', 'act_1')");
$db->exec("INSERT INTO mkt_campaigns (id, empresa_id, ad_account_id, external_id, name, status, daily_budget_cents) VALUES (1, 1, 1, 'c1', 'Campanha sem lead', 'active', 4000)");

$agora = new \DateTimeImmutable('2026-09-29 08:00:00');
// janela de sugestão = 7 dias terminando ontem (2026-09-22 a 2026-09-28)
$ins = $db->prepare('INSERT INTO mkt_daily_insights (campaign_id, date, empresa_id, spend_cents, impressions, clicks, leads) VALUES (1, ?, 1, ?, 100, 5, 0)');
foreach (['2026-09-22','2026-09-23','2026-09-24','2026-09-25','2026-09-26','2026-09-27','2026-09-28'] as $dia) {
    $ins->execute([$dia, 900]); // 7 x 900 = 6300 centavos, >= piso de 5000 -> dispara pause-no-leads
}

$queue = new QueueService($db);
$n1 = $queue->gerarSugestoes(1, $agora);
assert_igual(1, $n1, 'gerarSugestoes: 1ª chamada gera a sugestão pause-no-leads');

$pendente = $db->query("SELECT * FROM mkt_action_requests WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC);
assert_verdadeiro($pendente !== false, 'gerarSugestoes: a linha pending existe de verdade no banco');
assert_igual('pause-no-leads', $pendente['rule_id'] ?? null, 'gerarSugestoes: rule_id gravado certo');
assert_igual('rule', $pendente['source'] ?? null, "gerarSugestoes: source='rule' (veio do cron/regra, não de clique do usuário)");

$n2 = $queue->gerarSugestoes(1, $agora);
assert_igual(0, $n2, 'gerarSugestoes: 2ª chamada não duplica (já existe uma pending pra essa campanha+ação)');

$totalPendentes = (int) $db->query("SELECT COUNT(*) FROM mkt_action_requests WHERE status = 'pending'")->fetchColumn();
assert_igual(1, $totalPendentes, 'gerarSugestoes: continua só 1 linha pending no banco depois da 2ª chamada');

// ── trava de unicidade no BANCO (defesa em dupla camada, além do filtro em código) ──────────
$db2 = montarBanco();
$db2->exec('INSERT INTO empresas (id) VALUES (1)');
$db2->exec("INSERT INTO mkt_ad_accounts (id, empresa_id, platform, external_id) VALUES (1, 1, 'fake', 'act_1')");
$db2->exec("INSERT INTO mkt_campaigns (id, empresa_id, ad_account_id, external_id, name, status, daily_budget_cents) VALUES (1, 1, 1, 'c1', 'X', 'active', 4000)");
$db2->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, reason, status) VALUES (1, 1, 'pause_campaign', 'motivo', 'pending')");
try {
    $db2->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, reason, status) VALUES (1, 1, 'pause_campaign', 'motivo 2', 'pending')");
    $falhas++; $total++; echo "FALHA trava de unicidade no banco deveria ter lançado\n";
} catch (\PDOException $e) {
    $total++;
    if ($e->getCode() === '23000') echo "  OK  trava de unicidade no banco: 2º pending da mesma campanha+ação é rejeitado (SQLSTATE 23000)\n";
    else { $falhas++; echo "FALHA código de erro inesperado: {$e->getCode()}\n"; }
}

// ── rejeitar bloqueia por 7 dias, mas libera depois ─────────────────────────────────────────
$db3 = montarBanco();
$db3->exec('INSERT INTO empresas (id) VALUES (1)');
$db3->exec("INSERT INTO mkt_ad_accounts (id, empresa_id, platform, external_id) VALUES (1, 1, 'fake', 'act_1')");
$db3->exec("INSERT INTO mkt_campaigns (id, empresa_id, ad_account_id, external_id, name, status, daily_budget_cents) VALUES (1, 1, 1, 'c1', 'Y', 'active', 4000)");
$ins3 = $db3->prepare('INSERT INTO mkt_daily_insights (campaign_id, date, empresa_id, spend_cents, impressions, clicks, leads) VALUES (1, ?, 1, 900, 100, 5, 0)');
foreach (['2026-09-22','2026-09-23','2026-09-24','2026-09-25','2026-09-26','2026-09-27','2026-09-28'] as $dia) $ins3->execute([$dia]);

$queue3 = new QueueService($db3);
$queue3->gerarSugestoes(1, $agora);
$id3 = (int) $db3->query("SELECT id FROM mkt_action_requests WHERE status = 'pending'")->fetchColumn();
$queue3->rejeitar($id3, 1, 99, $agora);

$n3 = $queue3->gerarSugestoes(1, $agora);
assert_igual(0, $n3, 'gerarSugestoes: rejeitada há pouco (mesmo $now) continua bloqueada');

// simula que a rejeição aconteceu há 8 dias (cooldown de 7 já expirou)
$db3->exec("UPDATE mkt_action_requests SET decided_at = '2026-09-20 08:00:00' WHERE id = {$id3}");
$n4 = $queue3->gerarSugestoes(1, $agora);
assert_igual(1, $n4, 'gerarSugestoes: rejeição de 8 dias atrás já expirou o cooldown de 7 dias, sugere de novo');

// ────────────────────────────────────────────────────────────────────────────────────────────
// aprovar() / rejeitar(): só transiciona de pending, isolado por empresa, nunca decide 2x
// ────────────────────────────────────────────────────────────────────────────────────────────
$db4 = montarBanco();
$db4->exec('INSERT INTO empresas (id) VALUES (1), (2)');
$db4->exec("INSERT INTO mkt_ad_accounts (id, empresa_id, platform, external_id) VALUES (1, 1, 'fake', 'act_1')");
$db4->exec("INSERT INTO mkt_campaigns (id, empresa_id, ad_account_id, external_id, name, status, daily_budget_cents) VALUES (1, 1, 1, 'c1', 'Z', 'active', 4000)");
$db4->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, reason, status) VALUES (1, 1, 'pause_campaign', 'motivo', 'pending')");
$idPend = (int) $db4->lastInsertId();

$queue4 = new QueueService($db4);
$rAprovar = $queue4->aprovar($idPend, 1, 42, $agora);
assert_igual(['ok' => true], $rAprovar, 'aprovar: pedido pending vira approved com sucesso');

$linha = $db4->query("SELECT status, decided_by FROM mkt_action_requests WHERE id = {$idPend}")->fetch(PDO::FETCH_ASSOC);
assert_igual('approved', $linha['status'], 'aprovar: status gravado como approved');
assert_igual(42, (int) $linha['decided_by'], 'aprovar: decided_by grava quem aprovou');

$rDeNovo = $queue4->aprovar($idPend, 1, 42, $agora);
assert_igual(false, $rDeNovo['ok'], 'aprovar: decidir 2 vezes o mesmo pedido falha (já não está mais pending)');

$rOutraEmpresa = $queue4->aprovar($idPend, 2, 42, $agora);
assert_igual(false, $rOutraEmpresa['ok'], 'aprovar: pedido de OUTRA empresa nunca é achado (isolamento por empresa_id)');

$rInexistente = $queue4->aprovar(9999, 1, 42, $agora);
assert_igual('Pedido não encontrado.', $rInexistente['erro'] ?? null, 'aprovar: id inexistente devolve erro claro');

$db4->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, reason, status) VALUES (1, 1, 'resume_campaign', 'motivo', 'pending')");
$idPend2 = (int) $db4->lastInsertId();
$queue4->rejeitar($idPend2, 1, 42, $agora);
$linha2 = $db4->query("SELECT status FROM mkt_action_requests WHERE id = {$idPend2}")->fetch(PDO::FETCH_ASSOC);
assert_igual('rejected', $linha2['status'], 'rejeitar: status gravado como rejected');

// ────────────────────────────────────────────────────────────────────────────────────────────
// executarAprovados(): dry_run nunca chama a plataforma; execução de verdade chama e aplica
// ────────────────────────────────────────────────────────────────────────────────────────────
function montarBancoExecucao(): PDO
{
    $db = montarBanco();
    $db->exec('INSERT INTO empresas (id) VALUES (1)');
    $db->exec("INSERT INTO mkt_ad_accounts (id, empresa_id, platform, external_id) VALUES (1, 1, 'fake', 'act_xyz')");
    $db->exec("INSERT INTO mkt_campaigns (id, empresa_id, ad_account_id, external_id, name, status, daily_budget_cents) VALUES (1, 1, 1, 'camp_ext_1', 'Campanha 1', 'active', 4000)");
    return $db;
}

// dry_run=true: marca executed, dry_run=1, NUNCA chama a plataforma, NUNCA muda mkt_campaigns
$db5 = montarBancoExecucao();
$db5->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, payload, reason, status) VALUES (1, 1, 'pause_campaign', '{}', 'motivo', 'approved')");
$plat5 = new PlataformaDeTeste();
$res5 = (new QueueService($db5))->executarAprovados(fn($alvo) => $plat5, true, null, null, $agora);
assert_igual(1, count($res5), 'executarAprovados (dry_run): processa 1 pedido approved');
assert_igual('executed', $res5[0]['status'], 'executarAprovados (dry_run): marca executed mesmo sem chamar a plataforma');
assert_igual(true, $res5[0]['dry_run'], 'executarAprovados (dry_run): dry_run=true no resultado');
assert_igual(0, count($plat5->chamadas), 'executarAprovados (dry_run): NUNCA chama a plataforma de verdade');
$campanha5 = $db5->query('SELECT status FROM mkt_campaigns WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
assert_igual('active', $campanha5['status'], 'executarAprovados (dry_run): mkt_campaigns.status NÃO muda em simulação');

// dry_run=false, pause_campaign: chama a plataforma de verdade e aplica na campanha local
$db6 = montarBancoExecucao();
$db6->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, payload, reason, status) VALUES (1, 1, 'pause_campaign', '{}', 'motivo', 'approved')");
$plat6 = new PlataformaDeTeste();
$res6 = (new QueueService($db6))->executarAprovados(fn($alvo) => $plat6, false, null, null, $agora);
assert_igual('executed', $res6[0]['status'], 'executarAprovados (real): pause_campaign executado com sucesso');
assert_igual(false, $res6[0]['dry_run'], 'executarAprovados (real): dry_run=false no resultado');
assert_igual(1, count($plat6->chamadas), 'executarAprovados (real): chamou a plataforma exatamente 1 vez');
assert_igual(['metodo' => 'setCampaignStatus', 'conta' => 'act_xyz', 'campanha' => 'camp_ext_1', 'status' => 'paused'], $plat6->chamadas[0], 'executarAprovados (real): chamou setCampaignStatus com os external_id certos');
$campanha6 = $db6->query('SELECT status FROM mkt_campaigns WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
assert_igual('paused', $campanha6['status'], 'executarAprovados (real): mkt_campaigns.status foi atualizado de verdade');

// dry_run=false, update_daily_budget: chama setDailyBudget e atualiza o orçamento local
$db7 = montarBancoExecucao();
$db7->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, payload, reason, status)
            VALUES (1, 1, 'update_daily_budget', '{\"daily_budget_cents\":3200,\"previous_daily_budget_cents\":4000}', 'motivo', 'approved')");
$plat7 = new PlataformaDeTeste();
$res7 = (new QueueService($db7))->executarAprovados(fn($alvo) => $plat7, false, null, null, $agora);
assert_igual('executed', $res7[0]['status'], 'executarAprovados (real): update_daily_budget executado com sucesso');
assert_igual(['metodo' => 'setDailyBudget', 'conta' => 'act_xyz', 'campanha' => 'camp_ext_1', 'orcamento' => 3200], $plat7->chamadas[0], 'executarAprovados (real): chamou setDailyBudget com o valor novo certo');
$campanha7 = $db7->query('SELECT daily_budget_cents FROM mkt_campaigns WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
assert_igual(3200, (int) $campanha7['daily_budget_cents'], 'executarAprovados (real): mkt_campaigns.daily_budget_cents atualizado de verdade');

// dry_run=false, plataforma falha: marca failed, com o erro gravado, e NUNCA aplica na campanha local
$db8 = montarBancoExecucao();
$db8->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, payload, reason, status) VALUES (1, 1, 'pause_campaign', '{}', 'motivo', 'approved')");
$plat8 = new PlataformaDeTeste();
$plat8->falhar = true;
$res8 = (new QueueService($db8))->executarAprovados(fn($alvo) => $plat8, false, null, null, $agora);
assert_igual('failed', $res8[0]['status'], 'executarAprovados (falha da plataforma): marca failed');
assert_verdadeiro(str_contains($res8[0]['error'], 'Falha simulada'), 'executarAprovados (falha da plataforma): guarda a mensagem de erro');
$campanha8 = $db8->query('SELECT status FROM mkt_campaigns WHERE id = 1')->fetch(PDO::FETCH_ASSOC);
assert_igual('active', $campanha8['status'], 'executarAprovados (falha da plataforma): mkt_campaigns NÃO muda quando a chamada falha');
$linhaFalha8 = $db8->query("SELECT status, error FROM mkt_action_requests WHERE id = 1")->fetch(PDO::FETCH_ASSOC);
assert_igual('failed', $linhaFalha8['status'], 'executarAprovados (falha da plataforma): status no banco também é failed');

// payload inválido falha MESMO em dry_run (nunca chega a "executado" com dado ruim)
$db9 = montarBancoExecucao();
$db9->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, payload, reason, status)
            VALUES (1, 1, 'update_daily_budget', '{\"daily_budget_cents\":0}', 'motivo', 'approved')");
$plat9 = new PlataformaDeTeste();
$res9 = (new QueueService($db9))->executarAprovados(fn($alvo) => $plat9, true, null, null, $agora);
assert_igual('failed', $res9[0]['status'], 'executarAprovados (payload inválido): falha mesmo em modo simulação');
assert_igual(0, count($plat9->chamadas), 'executarAprovados (payload inválido): nem chega a tentar chamar a plataforma');

// filtro por requestId/empresaId: só processa o pedido pedido, nunca o de outra empresa
$db10 = montarBanco();
$db10->exec('INSERT INTO empresas (id) VALUES (1), (2)');
$db10->exec("INSERT INTO mkt_ad_accounts (id, empresa_id, platform, external_id) VALUES (1, 1, 'fake', 'a1'), (2, 2, 'fake', 'a2')");
$db10->exec("INSERT INTO mkt_campaigns (id, empresa_id, ad_account_id, external_id, name, status, daily_budget_cents) VALUES (1, 1, 1, 'ce1', 'C1', 'active', 4000), (2, 2, 2, 'ce2', 'C2', 'active', 4000)");
$db10->exec("INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, payload, reason, status) VALUES (1, 1, 'pause_campaign', '{}', 'm', 'approved'), (2, 2, 'pause_campaign', '{}', 'm', 'approved')");
$plat10 = new PlataformaDeTeste();
$res10 = (new QueueService($db10))->executarAprovados(fn($alvo) => $plat10, true, 1, null, $agora);
assert_igual(1, count($res10), 'executarAprovados (filtro empresaId): só processa os pedidos daquela empresa');

echo "\n{$total} testes, " . ($total - $falhas) . " OK, {$falhas} falha(s)\n";
exit($falhas > 0 ? 1 : 0);
