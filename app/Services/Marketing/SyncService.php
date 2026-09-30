<?php

namespace App\Services\Marketing;

use App\Core\DB;
use PDO;

/**
 * Coleta (sincronização) de uma conta de anúncio — porta fiel de
 * ads-platform/src/lib/sync/sync-account.ts, com o "store" embutido direto em SQL (mesmo
 * padrão de DB::pdo() direto já usado no resto do FixaOS pra controller sem Model dedicado).
 *
 * Regras inegociáveis aplicadas aqui: recoleta sempre os últimos 7 dias (a atribuição da
 * Meta muda depois); 1ª coleta de uma conta importa 60 dias (o suficiente pro painel de 30
 * dias + o período de comparação); falha numa conta nunca interrompe as outras.
 */
class SyncService
{
    public const RECOLLECT_DAYS = 7;
    public const BACKFILL_DAYS = 60;

    /** Etapa 2: intervalo mínimo entre dois cliques em "Sincronizar agora" da mesma conta —
     *  não é sobre limite de API (o cron já roda sozinho de tempos em tempos), é só pra
     *  ninguém martelar o botão sem perceber que a sincronização anterior ainda não terminou
     *  de refletir na tela. */
    public const SYNC_COOLDOWN_SECONDS = 60;

    public function __construct(private readonly PDO $db)
    {
        // Produção sempre chama make(), que injeta a conexão do FixaOS; testes injetam
        // um PDO próprio (SQLite em memória) — mesma técnica já usada no resto do projeto.
    }

    public static function make(): self
    {
        return new self(DB::pdo());
    }

    /** Janela de coleta: 7 dias recentes normalmente, 60 dias na primeira coleta da conta. */
    public static function collectionRange(string $today, ?string $lastSyncedAt): array
    {
        $days = $lastSyncedAt ? self::RECOLLECT_DAYS : self::BACKFILL_DAYS;
        return ['from' => Dates::addDays($today, -($days - 1)), 'to' => $today];
    }

    /**
     * Quantos segundos faltam até "Sincronizar agora" poder rodar de novo pra esta conta —
     * 0 já libera. `$lastSyncedAt` é o valor cru de `mkt_ad_accounts.last_synced_at`
     * ("AAAA-MM-DD HH:MM:SS", sem fuso explícito — grava e lê sempre no fuso local do PHP,
     * já fixado em America/Sao_Paulo pelo bootstrap da aplicação).
     */
    public static function secondsUntilNextSync(?string $lastSyncedAt, \DateTimeImmutable $now): int
    {
        if ($lastSyncedAt === null || $lastSyncedAt === '') return 0;
        $last = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $lastSyncedAt, $now->getTimezone());
        if ($last === false) return 0; // valor não reconhecido — nunca bloqueia por causa disso
        $decorridos = $now->getTimestamp() - $last->getTimestamp();
        $faltam = self::SYNC_COOLDOWN_SECONDS - $decorridos;
        return $faltam > 0 ? $faltam : 0;
    }

    /**
     * Sincroniza UMA conta: lista campanhas (upsert), busca métricas diárias (upsert),
     * marca last_synced_at. Lança exceção em caso de erro — quem chama decide o que fazer
     * (syncAllAccounts grava o erro e segue pras outras contas).
     */
    public function syncAccount(array $account, AdPlatformInterface $platform, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now');
        $today = Dates::todayInSaoPaulo($now);
        $range = self::collectionRange($today, $account['last_synced_at']);

        $campaigns = $platform->listCampaigns($account['external_id']);
        $campaignIds = $this->upsertCampaigns($account, $campaigns, $now);

        $insights = $platform->getDailyInsights($account['external_id'], $range['from'], $range['to']);
        $rows = [];
        foreach ($insights as $row) {
            $campaignId = $campaignIds[$row['campaign_external_id']] ?? null;
            if ($campaignId === null) continue; // campanha desconhecida (ex.: criada e já excluída entre listCampaigns e aqui)
            $rows[] = $row + ['campaign_id' => $campaignId];
        }
        $this->upsertInsights($account, $rows, $now);

        $summary = ['from' => $range['from'], 'to' => $range['to'], 'campaigns' => count($campaigns), 'insight_rows' => count($rows)];
        $this->markSynced((int) $account['id'], $now);
        $this->audit((int) $account['empresa_id'], 'sync', 'mkt_ad_accounts', (int) $account['id'], $summary);

        return $summary;
    }

    /** Sincroniza toda conta ATIVA de empresa com o módulo habilitado. Uma falha não trava as outras. */
    public function syncAllAccounts(callable $platformFor, ?int $empresaId = null, ?\DateTimeImmutable $now = null): array
    {
        $sql = "SELECT a.* FROM mkt_ad_accounts a
                JOIN empresas e ON e.id = a.empresa_id
                WHERE a.status = 'active' AND e.marketing_habilitado = 1";
        $params = [];
        if ($empresaId !== null) { $sql .= ' AND a.empresa_id = ?'; $params[] = $empresaId; }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($accounts as $account) {
            try {
                $platform = $platformFor($account);
                $summary = $this->syncAccount($account, $platform, $now);
                $results[] = ['account_id' => $account['id'], 'ok' => true, 'summary' => $summary];
            } catch (\Throwable $e) {
                $this->markFailed((int) $account['id'], $e->getMessage());
                $this->audit((int) $account['empresa_id'], 'sync_failed', 'mkt_ad_accounts', (int) $account['id'], ['error' => $e->getMessage()]);
                $results[] = ['account_id' => $account['id'], 'ok' => false, 'error' => $e->getMessage()];
            }
        }
        return $results;
    }

    /**
     * Conta que o painel/"Sincronizar agora" devem usar: a conta REAL do Google Ads
     * conectada pela empresa (ver MarketingController::conectarGoogleAds()), se existir e
     * estiver ativa; senão, a conta de demonstração (fake), criando-a se for a primeira vez.
     * Ponto único de decisão — evita o painel e o botão de sincronizar escolherem contas
     * diferentes por engano.
     */
    public function contaAtivaOuDemo(int $empresaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM mkt_ad_accounts WHERE empresa_id = ? AND platform = 'google_ads' AND status = 'active'
             ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([$empresaId]);
        $real = $stmt->fetch(PDO::FETCH_ASSOC);
        return $real ?: $this->garantirContaDemo($empresaId);
    }

    /** Cria (se ainda não existir) a conta de demonstração (plataforma fake) da empresa. */
    public function garantirContaDemo(int $empresaId): array
    {
        $externalId = "demo_{$empresaId}";
        $stmt = $this->db->prepare("SELECT * FROM mkt_ad_accounts WHERE empresa_id = ? AND platform = 'fake' AND external_id = ?");
        $stmt->execute([$empresaId, $externalId]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($existing) return $existing;

        $ins = $this->db->prepare(
            "INSERT INTO mkt_ad_accounts (empresa_id, platform, external_id, name, currency, status)
             VALUES (?, 'fake', ?, 'Conta de demonstração', 'BRL', 'active')"
        );
        $ins->execute([$empresaId, $externalId]);
        $id = (int) $this->db->lastInsertId();

        $stmt = $this->db->prepare('SELECT * FROM mkt_ad_accounts WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /** @return array<string,int> external_id da campanha => id interno */
    private function upsertCampaigns(array $account, array $campaigns, \DateTimeImmutable $now): array
    {
        $syncedAt = $now->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "INSERT INTO mkt_campaigns (empresa_id, ad_account_id, external_id, name, status, daily_budget_cents, synced_at)
             VALUES (?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name), status = VALUES(status),
               daily_budget_cents = VALUES(daily_budget_cents), synced_at = VALUES(synced_at)"
        );
        foreach ($campaigns as $c) {
            $stmt->execute([
                $account['empresa_id'], $account['id'], $c['external_id'], $c['name'],
                $c['status'], $c['daily_budget_cents'], $syncedAt,
            ]);
        }

        $ids = [];
        if ($campaigns) {
            $placeholders = implode(',', array_fill(0, count($campaigns), '?'));
            $sel = $this->db->prepare("SELECT id, external_id FROM mkt_campaigns WHERE ad_account_id = ? AND external_id IN ({$placeholders})");
            $sel->execute([$account['id'], ...array_column($campaigns, 'external_id')]);
            foreach ($sel->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $ids[$row['external_id']] = (int) $row['id'];
            }
        }
        return $ids;
    }

    private function upsertInsights(array $account, array $rows, \DateTimeImmutable $now): void
    {
        if (!$rows) return;
        $collectedAt = $now->format('Y-m-d H:i:s');
        $stmt = $this->db->prepare(
            "INSERT INTO mkt_daily_insights (campaign_id, `date`, empresa_id, spend_cents, impressions, clicks, leads, collected_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE spend_cents = VALUES(spend_cents), impressions = VALUES(impressions),
               clicks = VALUES(clicks), leads = VALUES(leads), collected_at = VALUES(collected_at)"
        );
        foreach ($rows as $row) {
            $stmt->execute([
                $row['campaign_id'], $row['date'], $account['empresa_id'],
                $row['spend_cents'], $row['impressions'], $row['clicks'], $row['leads'], $collectedAt,
            ]);
        }
    }

    private function markSynced(int $accountId, \DateTimeImmutable $now): void
    {
        $stmt = $this->db->prepare("UPDATE mkt_ad_accounts SET last_synced_at = ?, last_sync_error = NULL WHERE id = ?");
        $stmt->execute([$now->format('Y-m-d H:i:s'), $accountId]);
    }

    private function markFailed(int $accountId, string $error): void
    {
        // Nunca grava token/URL — describeMetaError() (Etapa 5) já garante isso antes de chegar aqui.
        $stmt = $this->db->prepare('UPDATE mkt_ad_accounts SET last_sync_error = ? WHERE id = ?');
        $stmt->execute([substr($error, 0, 500), $accountId]);
    }

    private function audit(int $empresaId, string $action, string $entityType, int $entityId, array $details): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO mkt_audit_log (empresa_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?)'
        );
        try {
            $stmt->execute([$empresaId, $action, $entityType, $entityId, json_encode($details, JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable) {
            // mkt_audit_log só chega na Etapa 3 (fila de aprovação) — antes disso a tabela
            // pode não existir ainda; auditoria de coleta não pode travar a coleta em si.
        }
    }
}
