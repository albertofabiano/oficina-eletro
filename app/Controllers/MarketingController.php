<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Marketing\Dashboard;
use App\Services\Marketing\Dates;
use App\Services\Marketing\MarketingConfig;
use App\Services\Marketing\PlatformFactory;
use App\Services\Marketing\QueueService;
use App\Services\Marketing\SyncService;

/**
 * Painel do módulo Marketing (Etapa 1: fundação + conta de demonstração; Etapa 2: coleta
 * agendada via cron + "Sincronizar agora" manual com cooldown; Etapa 3: regras de sugestão +
 * fila de aprovação + executor). Só dono/admin acessa (Auth::MATRIZ, módulo 'marketing') —
 * empresa sem `marketing_habilitado=1` não vê nada e nenhuma chamada externa é feita.
 */
class MarketingController extends Controller
{
    public function painel(): void
    {
        $eid = $this->empresaId();
        $db = DB::pdo();

        $stmt = $db->prepare('SELECT marketing_habilitado FROM empresas WHERE id = ?');
        $stmt->execute([$eid]);
        $habilitado = (bool) $stmt->fetchColumn();

        if (!$habilitado) {
            $this->view('marketing.desabilitado', ['titulo' => 'Marketing']);
            return;
        }

        $sync = SyncService::make();
        $conta = $sync->garantirContaDemo($eid);

        // Conta recém-criada (ou nunca coletada): sincroniza na hora, senão o painel abriria
        // vazio até o cron rodar (scripts/marketing_sincronizar.php, ver Etapa 2). Passa pelo
        // mesmo PlatformFactory do botão manual — hoje sempre resolve pra FakeAdPlatform
        // (toda conta existente é 'fake'), mas já fica pronto pra quando existir conta real.
        if (empty($conta['last_synced_at'])) {
            $sync->syncAccount($conta, PlatformFactory::make($db, $conta));
            $conta = $sync->garantirContaDemo($eid); // refaz a leitura pra pegar o last_synced_at novo
            $this->rodarOtimizacao($db, $eid);
        }

        $faltamSegundos = SyncService::secondsUntilNextSync($conta['last_synced_at'], new \DateTimeImmutable('now'));
        $pendentes = $this->contarPendentes($db, $eid);

        $days = Dashboard::parsePeriod($this->get('dias'), 7);
        $today = Dates::todayInSaoPaulo();
        $ranges = Dashboard::periodRanges($days, $today);

        $stmt = $db->prepare('SELECT id, external_id, name, status, daily_budget_cents FROM mkt_campaigns WHERE empresa_id = ?');
        $stmt->execute([$eid]);
        $campaigns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare(
            'SELECT campaign_id, `date`, spend_cents, impressions, clicks, leads
             FROM mkt_daily_insights WHERE empresa_id = ? AND `date` BETWEEN ? AND ?'
        );
        $stmt->execute([$eid, $ranges['previous']['from'], $ranges['current']['to']]);
        $insights = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        // PDO devolve INT como string em alguns drivers/configs — normaliza antes das contas.
        foreach ($insights as &$row) {
            $row['campaign_id'] = (int) $row['campaign_id'];
            $row['spend_cents'] = (int) $row['spend_cents'];
            $row['impressions'] = (int) $row['impressions'];
            $row['clicks'] = (int) $row['clicks'];
            $row['leads'] = (int) $row['leads'];
        }
        unset($row);
        foreach ($campaigns as &$c) {
            $c['id'] = (int) $c['id'];
            $c['daily_budget_cents'] = $c['daily_budget_cents'] !== null ? (int) $c['daily_budget_cents'] : null;
        }
        unset($c);

        $painel = Dashboard::load($campaigns, $insights, $days, $today);

        $this->view('marketing.painel', [
            'titulo'         => 'Marketing',
            'painel'         => $painel,
            'dias'           => $days,
            'conta'          => $conta,
            'faltamSegundos' => $faltamSegundos,
            'pendentes'      => $pendentes,
        ]);
    }

    /**
     * "Sincronizar agora" (AJAX) — respeita o mesmo cooldown de 60s calculado em painel(),
     * agora conferido de novo no servidor (nunca confia só no botão desabilitado no HTML).
     */
    public function sincronizar(): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $eid = $this->empresaId();
        $db = DB::pdo();

        $stmt = $db->prepare('SELECT marketing_habilitado FROM empresas WHERE id = ?');
        $stmt->execute([$eid]);
        if (!$stmt->fetchColumn()) { $this->json(['sucesso' => false, 'erro' => 'Marketing não está habilitado pra sua empresa.']); }

        $sync = SyncService::make();
        $conta = $sync->garantirContaDemo($eid);
        $now = new \DateTimeImmutable('now');
        $faltam = SyncService::secondsUntilNextSync($conta['last_synced_at'], $now);
        if ($faltam > 0) { $this->json(['sucesso' => false, 'erro' => "Aguarde {$faltam}s antes de sincronizar de novo.", 'aguardar_segundos' => $faltam]); }

        try {
            $resumo = $sync->syncAccount($conta, PlatformFactory::make($db, $conta), $now);
            $this->rodarOtimizacao($db, $eid, $now);
            $this->json(['sucesso' => true, 'resumo' => $resumo]);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao sincronizar: ' . $e->getMessage()]);
        }
    }

    /** Lista de sugestões pendentes de decisão + histórico recente já decidido/executado. */
    public function aprovacoes(): void
    {
        $eid = $this->empresaId();
        $db = DB::pdo();

        $stmt = $db->prepare(
            "SELECT r.*, c.name AS campaign_name
             FROM mkt_action_requests r JOIN mkt_campaigns c ON c.id = r.campaign_id
             WHERE r.empresa_id = ? AND r.status = 'pending'
             ORDER BY r.requested_at ASC"
        );
        $stmt->execute([$eid]);
        $pendentes = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare(
            "SELECT r.*, c.name AS campaign_name, u.nome AS decidido_por_nome
             FROM mkt_action_requests r
             JOIN mkt_campaigns c ON c.id = r.campaign_id
             LEFT JOIN usuarios u ON u.id = r.decided_by
             WHERE r.empresa_id = ? AND r.status <> 'pending'
             ORDER BY COALESCE(r.executed_at, r.decided_at) DESC LIMIT 30"
        );
        $stmt->execute([$eid]);
        $historico = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $this->view('marketing.aprovacoes', [
            'titulo'    => 'Marketing — Aprovações',
            'pendentes' => $pendentes,
            'historico' => $historico,
            'dryRun'    => MarketingConfig::isDryRun(),
        ]);
    }

    public function aprovar(int $id): void
    {
        $this->decidirEExecutar($id, 'aprovar');
    }

    public function rejeitar(int $id): void
    {
        $this->decidirEExecutar($id, 'rejeitar');
    }

    private function decidirEExecutar(int $id, string $decisao): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $eid = $this->empresaId();
        $usuarioId = \App\Core\Auth::id();
        $db = DB::pdo();
        $queue = QueueService::make();

        $resultado = $decisao === 'aprovar' ? $queue->aprovar($id, $eid, $usuarioId) : $queue->rejeitar($id, $eid, $usuarioId);
        if (!$resultado['ok']) {
            $this->json(['sucesso' => false, 'erro' => $resultado['erro']]);
        }

        if ($decisao === 'rejeitar') {
            $this->json(['sucesso' => true, 'mensagem' => 'Sugestão rejeitada. Ela não será sugerida de novo nos próximos 7 dias.']);
        }

        // "Aprovar" já executa na hora (além do cron) — spec seção 9: "Botão sugerido: executar
        // logo após a aprovação". DRY_RUN decide se isso de fato toca a plataforma ou só registra.
        $dryRun = MarketingConfig::isDryRun();
        $execucao = $queue->executarAprovados(fn(array $alvo) => PlatformFactory::make($db, $alvo), $dryRun, $eid, $id);
        $resultadoExecucao = $execucao[0] ?? null;

        if ($resultadoExecucao === null) {
            $this->json(['sucesso' => true, 'mensagem' => 'Aprovado.']);
        } elseif ($resultadoExecucao['status'] === 'failed') {
            $this->json(['sucesso' => true, 'mensagem' => 'Aprovado, mas a execução falhou. Veja o motivo no histórico.', 'erro_execucao' => $resultadoExecucao['error']]);
        } elseif ($dryRun) {
            $this->json(['sucesso' => true, 'mensagem' => 'Aprovado e registrado em modo simulação — nada foi enviado à plataforma.']);
        } else {
            $this->json(['sucesso' => true, 'mensagem' => 'Aprovado e aplicado na plataforma.']);
        }
    }

    /** Roda depois de toda sincronização (Etapa 3): gera sugestões novas e já tenta executar
     *  qualquer pedido que porventura já esteja aprovado (ex.: aprovado pelo botão mas o clique
     *  de execução falhou por algum motivo transitório). */
    private function rodarOtimizacao(\PDO $db, int $empresaId, ?\DateTimeImmutable $now = null): void
    {
        try {
            $queue = QueueService::make();
            $queue->gerarSugestoes($empresaId, $now);
            $queue->executarAprovados(fn(array $alvo) => PlatformFactory::make($db, $alvo), MarketingConfig::isDryRun(), $empresaId, null, $now);
        } catch (\Throwable $e) {
            // mkt_action_requests/mkt_audit_log só existem a partir da migration 069 — num
            // deploy em estágios (código já subiu, migration ainda não rodou), a sincronização
            // não pode quebrar por causa disso; a otimização só volta a rodar na próxima coleta.
            error_log('[marketing] rodarOtimizacao falhou (empresa ' . $empresaId . '): ' . $e->getMessage());
        }
    }

    private function contarPendentes(\PDO $db, int $empresaId): int
    {
        try {
            $stmt = $db->prepare("SELECT COUNT(*) FROM mkt_action_requests WHERE empresa_id = ? AND status = 'pending'");
            $stmt->execute([$empresaId]);
            return (int) $stmt->fetchColumn();
        } catch (\Throwable) {
            return 0; // mesma cautela de rodarOtimizacao() — tabela pode não existir ainda
        }
    }
}
