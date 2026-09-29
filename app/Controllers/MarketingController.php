<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Marketing\Dashboard;
use App\Services\Marketing\Dates;
use App\Services\Marketing\PlatformFactory;
use App\Services\Marketing\SyncService;

/**
 * Painel do módulo Marketing (Etapa 1: fundação + conta de demonstração; Etapa 2: coleta
 * agendada via cron + "Sincronizar agora" manual com cooldown). Só dono/admin acessa
 * (Auth::MATRIZ, módulo 'marketing') — empresa sem `marketing_habilitado=1` não vê nada e
 * nenhuma chamada externa é feita.
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
        }

        $faltamSegundos = SyncService::secondsUntilNextSync($conta['last_synced_at'], new \DateTimeImmutable('now'));

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
            $this->json(['sucesso' => true, 'resumo' => $resumo]);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao sincronizar: ' . $e->getMessage()]);
        }
    }
}
