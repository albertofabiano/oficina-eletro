<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Marketing\Dashboard;
use App\Services\Marketing\Dates;
use App\Services\Marketing\FakeAdPlatform;
use App\Services\Marketing\SyncService;

/**
 * Painel do módulo Marketing (Etapa 1 da especificação — fundação + conta de demonstração).
 * Só dono/admin acessa (Auth::MATRIZ, módulo 'marketing') — empresa sem
 * `marketing_habilitado=1` não vê nada e nenhuma chamada externa é feita.
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

        // Conta de demonstração recém-criada (ou nunca coletada): sincroniza na hora, senão
        // o painel abriria vazio até o cron das 6h rodar — mesmo espírito do botão
        // "Sincronizar agora" que a Etapa 2 vai expor de verdade, com cooldown de 60s.
        if (empty($conta['last_synced_at'])) {
            $sync->syncAccount($conta, new FakeAdPlatform());
        }

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
            'titulo'    => 'Marketing',
            'painel'    => $painel,
            'dias'      => $days,
            'conta'     => $conta,
        ]);
    }
}
