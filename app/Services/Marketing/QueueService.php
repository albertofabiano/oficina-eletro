<?php

namespace App\Services\Marketing;

use App\Core\DB;
use PDO;

/**
 * Fila de aprovação e executor — porta fiel de ads-platform/src/lib/queue/{generate,execute,store}.ts,
 * com o "store" embutido direto em SQL (mesmo padrão de SyncService). É a ÚNICA parte do
 * sistema autorizada a chamar os métodos de ESCRITA de um AdPlatformInterface
 * (setCampaignStatus/setDailyBudget) — nenhum outro código deve fazer isso.
 *
 * Três operações, cada uma numa transação própria travando a linha antes de decidir (mesma
 * disciplina já usada pra evitar corrida de duplo-clique documentada em CLAUDE.md — "Bug: Taxa
 * cartão duplicada"/"Adiantamento de OS"): gerar sugestões nunca duplica uma já pendente nem
 * repete uma rejeitada há menos de 7 dias; aprovar/rejeitar só transiciona de 'pending'; o
 * executor só roda pedido 'approved', nunca dois processos executam o mesmo duas vezes.
 */
class QueueService
{
    public const SUGGESTION_WINDOW_DAYS = 7;
    public const REJECTION_COOLDOWN_DAYS = 7;

    public function __construct(private readonly PDO $db)
    {
    }

    public static function make(): self
    {
        return new self(DB::pdo());
    }

    // ── Geração de sugestões (Rules::suggest() sobre os últimos 7 dias completos) ───────────

    /**
     * @param int $adAccountId conta ATIVA de anúncio da empresa (ver SyncService::
     *   contaAtivaOuDemo()) — nunca aceita "toda campanha da empresa" sem esse filtro: uma
     *   empresa pode ter campanhas de uma conta antiga (demo desativada, ou reconexão com
     *   outro Customer ID) ainda gravadas em mkt_campaigns, e sugerir ação sobre elas seria
     *   sobre uma conta que não está mais em uso.
     * @return int quantas sugestões novas foram de fato inseridas (0 se nada casou ou tudo já estava bloqueado)
     */
    public function gerarSugestoes(int $empresaId, int $adAccountId, ?\DateTimeImmutable $now = null): int
    {
        $now ??= new \DateTimeImmutable('now');
        $today = Dates::todayInSaoPaulo($now);
        $to = Dates::addDays($today, -1);
        $from = Dates::addDays($to, -(self::SUGGESTION_WINDOW_DAYS - 1));

        $campaigns = $this->campanhasDaEmpresa($empresaId, $adAccountId);
        $insights = $this->insightsNoPeriodo($empresaId, $adAccountId, $from, $to);

        $totaisConta = Dashboard::deriveMetrics(Dashboard::sumInsights($insights));
        $rows = Dashboard::campaignRows($campaigns, $insights);
        $sugestoes = Rules::suggest($rows, $totaisConta['cost_per_lead_cents'], self::SUGGESTION_WINDOW_DAYS);
        if (!$sugestoes) return 0;

        $bloqueadas = $this->chavesBloqueadas($empresaId, $now);
        $novas = array_values(array_filter($sugestoes, fn($s) => !in_array("{$s['campaign_id']}:{$s['action_type']}", $bloqueadas, true)));
        if (!$novas) return 0;

        $ins = $this->db->prepare(
            "INSERT INTO mkt_action_requests (empresa_id, campaign_id, action_type, payload, reason, source, rule_id, status)
             VALUES (?, ?, ?, ?, ?, 'rule', ?, 'pending')"
        );
        $inseridas = 0;
        foreach ($novas as $s) {
            try {
                $ins->execute([$empresaId, $s['campaign_id'], $s['action_type'], json_encode($s['payload'], JSON_UNESCAPED_UNICODE), $s['reason'], $s['rule_id']]);
                $inseridas++;
                $this->audit($empresaId, null, 'suggestion_created', 'mkt_action_requests', (int) $this->db->lastInsertId(), ['rule_id' => $s['rule_id'], 'campaign_id' => $s['campaign_id']]);
            } catch (\PDOException $e) {
                // Corrida rara (dois processos gerando a mesma sugestão ao mesmo tempo) colide
                // na unique key de pendente (ver migration 069) — não é erro de verdade, só
                // significa que já existe; qualquer outro erro de banco continua subindo.
                if ($e->getCode() !== '23000') throw $e;
            }
        }
        return $inseridas;
    }

    /** @return string[] "campaign_id:action_type" que não podem receber sugestão nova agora. */
    private function chavesBloqueadas(int $empresaId, \DateTimeImmutable $now): array
    {
        $desde = $now->modify('-' . self::REJECTION_COOLDOWN_DAYS . ' days')->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "SELECT campaign_id, action_type FROM mkt_action_requests
             WHERE empresa_id = ? AND (status = 'pending' OR (status = 'rejected' AND decided_at >= ?))"
        );
        $stmt->execute([$empresaId, $desde]);
        return array_map(fn($r) => "{$r['campaign_id']}:{$r['action_type']}", $stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    private function campanhasDaEmpresa(int $empresaId, int $adAccountId): array
    {
        $stmt = $this->db->prepare('SELECT id, external_id, name, status, daily_budget_cents FROM mkt_campaigns WHERE empresa_id = ? AND ad_account_id = ?');
        $stmt->execute([$empresaId, $adAccountId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$c) {
            $c['id'] = (int) $c['id'];
            $c['daily_budget_cents'] = $c['daily_budget_cents'] !== null ? (int) $c['daily_budget_cents'] : null;
        }
        return $rows;
    }

    /** Junta com mkt_campaigns pra filtrar por conta — mkt_daily_insights não guarda
     *  ad_account_id direto, só campaign_id (que já pertence a uma conta certa). */
    private function insightsNoPeriodo(int $empresaId, int $adAccountId, string $from, string $to): array
    {
        $stmt = $this->db->prepare(
            "SELECT i.campaign_id, i.`date`, i.spend_cents, i.impressions, i.clicks, i.leads
             FROM mkt_daily_insights i JOIN mkt_campaigns c ON c.id = i.campaign_id
             WHERE i.empresa_id = ? AND c.ad_account_id = ? AND i.`date` BETWEEN ? AND ?"
        );
        $stmt->execute([$empresaId, $adAccountId, $from, $to]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['campaign_id'] = (int) $r['campaign_id'];
            $r['spend_cents'] = (int) $r['spend_cents'];
            $r['impressions'] = (int) $r['impressions'];
            $r['clicks'] = (int) $r['clicks'];
            $r['leads'] = (int) $r['leads'];
        }
        return $rows;
    }

    // ── Aprovar / rejeitar ───────────────────────────────────────────────────────────────────

    /** @return array{ok:bool, erro?:string} */
    public function aprovar(int $requestId, int $empresaId, int $usuarioId, ?\DateTimeImmutable $now = null): array
    {
        return $this->decidir($requestId, $empresaId, $usuarioId, 'approved', $now);
    }

    /** @return array{ok:bool, erro?:string} */
    public function rejeitar(int $requestId, int $empresaId, int $usuarioId, ?\DateTimeImmutable $now = null): array
    {
        return $this->decidir($requestId, $empresaId, $usuarioId, 'rejected', $now);
    }

    private function decidir(int $requestId, int $empresaId, int $usuarioId, string $novoStatus, ?\DateTimeImmutable $now): array
    {
        $now ??= new \DateTimeImmutable('now');
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("SELECT * FROM mkt_action_requests WHERE id = ? AND empresa_id = ?" . $this->forUpdate());
        $stmt->execute([$requestId, $empresaId]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido) {
            $this->db->rollBack();
            return ['ok' => false, 'erro' => 'Pedido não encontrado.'];
        }
        if ($pedido['status'] !== 'pending') {
            $this->db->rollBack();
            return ['ok' => false, 'erro' => 'Este pedido já foi decidido antes — só dá pra decidir um pedido pendente uma vez.'];
        }

        $upd = $this->db->prepare("UPDATE mkt_action_requests SET status = ?, decided_by = ?, decided_at = ? WHERE id = ?");
        $upd->execute([$novoStatus, $usuarioId, $now->format('Y-m-d H:i:s'), $requestId]);
        $this->audit($empresaId, $usuarioId, $novoStatus === 'approved' ? 'suggestion_approved' : 'suggestion_rejected', 'mkt_action_requests', $requestId, []);
        $this->db->commit();
        return ['ok' => true];
    }

    // ── Executor — a ÚNICA parte que chama setCampaignStatus/setDailyBudget de verdade ──────

    /**
     * @param callable $platformFor recebe o "alvo" (ver carregarAlvo()) e devolve um AdPlatformInterface
     * @return array<int, array{request_id:int, status:string, dry_run?:bool, error?:string}>
     */
    public function executarAprovados(callable $platformFor, bool $dryRun, ?int $empresaId = null, ?int $requestId = null, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now');
        $sql = "SELECT id FROM mkt_action_requests WHERE status = 'approved'";
        $params = [];
        if ($empresaId !== null) { $sql .= ' AND empresa_id = ?'; $params[] = $empresaId; }
        if ($requestId !== null) { $sql .= ' AND id = ?'; $params[] = $requestId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $resultados = [];
        foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $resultados[] = $this->executarUm((int) $id, $platformFor, $dryRun, $now);
        }
        return $resultados;
    }

    private function executarUm(int $id, callable $platformFor, bool $dryRun, \DateTimeImmutable $now): array
    {
        $this->db->beginTransaction();
        $stmt = $this->db->prepare("SELECT * FROM mkt_action_requests WHERE id = ?" . $this->forUpdate());
        $stmt->execute([$id]);
        $pedido = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$pedido || $pedido['status'] !== 'approved') {
            $this->db->rollBack();
            return ['request_id' => $id, 'status' => 'skipped'];
        }

        try {
            $payloadArr = $pedido['payload'] !== null ? json_decode($pedido['payload'], true) : null;
            $acao = ActionPayload::parse($pedido['action_type'], $payloadArr); // valida mesmo em dry_run

            if (!$dryRun) {
                $alvo = $this->carregarAlvo((int) $pedido['campaign_id']);
                if ($alvo === null) throw new \RuntimeException('Campanha não encontrada.');
                $this->aplicarNaPlataforma($platformFor($alvo), $alvo, $acao);
                $this->aplicarNaCampanhaLocal((int) $pedido['campaign_id'], $acao);
            }

            $upd = $this->db->prepare("UPDATE mkt_action_requests SET status = 'executed', executed_at = ?, dry_run = ?, error = NULL WHERE id = ?");
            $upd->execute([$now->format('Y-m-d H:i:s'), $dryRun ? 1 : 0, $id]);
            $this->audit((int) $pedido['empresa_id'], null, 'suggestion_executed', 'mkt_action_requests', $id, ['dry_run' => $dryRun]);
            $this->db->commit();
            return ['request_id' => $id, 'status' => 'executed', 'dry_run' => $dryRun];
        } catch (\Throwable $e) {
            $mensagem = substr($e->getMessage(), 0, 500);
            $upd = $this->db->prepare("UPDATE mkt_action_requests SET status = 'failed', executed_at = ?, error = ? WHERE id = ?");
            $upd->execute([$now->format('Y-m-d H:i:s'), $mensagem, $id]);
            $this->audit((int) $pedido['empresa_id'], null, 'suggestion_failed', 'mkt_action_requests', $id, ['error' => $mensagem]);
            $this->db->commit();
            return ['request_id' => $id, 'status' => 'failed', 'error' => $mensagem];
        }
    }

    /** @return array{campaign_external_id:string, ad_account_id:int, platform:string, account_external_id:string, empresa_id:int}|null */
    private function carregarAlvo(int $campaignId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT c.external_id AS campaign_external_id, a.id AS ad_account_id, a.platform,
                    a.external_id AS account_external_id, a.empresa_id
             FROM mkt_campaigns c JOIN mkt_ad_accounts a ON a.id = c.ad_account_id
             WHERE c.id = ?"
        );
        $stmt->execute([$campaignId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    private function aplicarNaPlataforma(AdPlatformInterface $platform, array $alvo, array $acao): void
    {
        match ($acao['type']) {
            'pause_campaign'      => $platform->setCampaignStatus($alvo['account_external_id'], $alvo['campaign_external_id'], 'paused'),
            'resume_campaign'     => $platform->setCampaignStatus($alvo['account_external_id'], $alvo['campaign_external_id'], 'active'),
            'update_daily_budget' => $platform->setDailyBudget($alvo['account_external_id'], $alvo['campaign_external_id'], $acao['to_cents']),
        };
    }

    /** Espelha na campanha local só depois que a mudança realmente chegou na plataforma. */
    private function aplicarNaCampanhaLocal(int $campaignId, array $acao): void
    {
        $campos = match ($acao['type']) {
            'pause_campaign'      => ['status' => 'paused'],
            'resume_campaign'     => ['status' => 'active'],
            'update_daily_budget' => ['daily_budget_cents' => $acao['to_cents']],
        };
        $sets = []; $params = [];
        foreach ($campos as $coluna => $valor) { $sets[] = "{$coluna} = ?"; $params[] = $valor; }
        $params[] = $campaignId;
        $this->db->prepare('UPDATE mkt_campaigns SET ' . implode(', ', $sets) . ' WHERE id = ?')->execute($params);
    }

    /**
     * SQLite (usado só em teste) não entende `FOR UPDATE` — em MySQL/InnoDB (produção) essa
     * cláusula é o que garante que duas aprovações/execuções concorrentes do mesmo pedido
     * nunca duplicam o efeito, travando a linha até o commit.
     */
    private function forUpdate(): string
    {
        return $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? '' : ' FOR UPDATE';
    }

    private function audit(int $empresaId, ?int $usuarioId, string $action, string $entityType, int $entityId, array $details): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO mkt_audit_log (empresa_id, usuario_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$empresaId, $usuarioId, $action, $entityType, $entityId, json_encode($details, JSON_UNESCAPED_UNICODE)]);
    }
}
