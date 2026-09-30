<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\Marketing\Dashboard;
use App\Services\Marketing\Dates;
use App\Services\Marketing\GoogleAdsPlatform;
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
        $conta = $sync->contaAtivaOuDemo($eid);

        // Conta recém-criada (ou nunca coletada): sincroniza na hora, senão o painel abriria
        // vazio até o cron rodar (scripts/marketing_sincronizar.php, ver Etapa 2).
        if (empty($conta['last_synced_at'])) {
            $sync->syncAccount($conta, PlatformFactory::make($db, $conta));
            $conta = $sync->contaAtivaOuDemo($eid); // refaz a leitura pra pegar o last_synced_at novo
            $this->rodarOtimizacao($db, $eid, (int) $conta['id']);
        }

        $faltamSegundos = SyncService::secondsUntilNextSync($conta['last_synced_at'], new \DateTimeImmutable('now'));
        $pendentes = $this->contarPendentes($db, $eid);

        $days = Dashboard::parsePeriod($this->get('dias'), 7);
        $today = Dates::todayInSaoPaulo();
        $ranges = Dashboard::periodRanges($days, $today);

        // Filtra pela conta ATIVA (não só empresa_id) — sem isso, campanha de uma conta antiga
        // (demo desativada, ou reconexão com outro Customer ID) ainda gravada em mkt_campaigns
        // aparecia misturada com a campanha real de verdade (bug real, achado em produção
        // conectando a primeira conta real do Google Ads).
        $stmt = $db->prepare('SELECT id, external_id, name, status, daily_budget_cents FROM mkt_campaigns WHERE empresa_id = ? AND ad_account_id = ?');
        $stmt->execute([$eid, $conta['id']]);
        $campaigns = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $stmt = $db->prepare(
            'SELECT i.campaign_id, i.`date`, i.spend_cents, i.impressions, i.clicks, i.leads
             FROM mkt_daily_insights i JOIN mkt_campaigns c ON c.id = i.campaign_id
             WHERE i.empresa_id = ? AND c.ad_account_id = ? AND i.`date` BETWEEN ? AND ?'
        );
        $stmt->execute([$eid, $conta['id'], $ranges['previous']['from'], $ranges['current']['to']]);
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
        $conta = $sync->contaAtivaOuDemo($eid);
        $now = new \DateTimeImmutable('now');
        $faltam = SyncService::secondsUntilNextSync($conta['last_synced_at'], $now);
        if ($faltam > 0) { $this->json(['sucesso' => false, 'erro' => "Aguarde {$faltam}s antes de sincronizar de novo.", 'aguardar_segundos' => $faltam]); }

        try {
            $resumo = $sync->syncAccount($conta, PlatformFactory::make($db, $conta), $now);
            $this->rodarOtimizacao($db, $eid, (int) $conta['id'], $now);
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

    /**
     * Tela onde a própria empresa vincula o Customer ID real da conta dela no Google Ads —
     * pré-requisito: já ter enviado/aceito o convite de vínculo com a conta Gerenciadora do
     * FixaOS (fora daqui, dentro do próprio Google Ads da empresa). Sem isso, `conectarGoogleAds()`
     * abaixo falha com uma mensagem clara em vez de gravar um Customer ID que não funciona.
     */
    public function contaGoogleAds(): void
    {
        $eid = $this->empresaId();
        $db = DB::pdo();

        $stmt = $db->prepare('SELECT marketing_habilitado FROM empresas WHERE id = ?');
        $stmt->execute([$eid]);
        if (!$stmt->fetchColumn()) {
            $this->view('marketing.desabilitado', ['titulo' => 'Marketing']);
            return;
        }

        $stmt = $db->prepare(
            "SELECT * FROM mkt_ad_accounts WHERE empresa_id = ? AND platform = 'google_ads'
             ORDER BY (status = 'active') DESC, id DESC LIMIT 1"
        );
        $stmt->execute([$eid]);
        $conta = $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;

        $this->view('marketing.conta_google_ads', [
            'titulo' => 'Marketing — Conectar conta do Google Ads',
            'conta'  => $conta,
        ]);
    }

    /** Só dígitos — aceita o Customer ID como o Google Ads mostra na UI (ex. "123-456-7890"),
     *  com espaço, traço ou colado. Pura/testável sem rede nem banco. */
    public static function normalizarCustomerId(string $raw): string
    {
        return preg_replace('/\D+/', '', $raw) ?? '';
    }

    public function conectarGoogleAds(): void
    {
        if (!csrf_verify()) { $this->backWithInput('Sessão expirada. Tente de novo.'); return; }

        $eid = $this->empresaId();
        $db = DB::pdo();

        $stmt = $db->prepare('SELECT marketing_habilitado FROM empresas WHERE id = ?');
        $stmt->execute([$eid]);
        if (!$stmt->fetchColumn()) { $this->backWithInput('Marketing não está habilitado pra sua empresa.'); return; }

        $customerId = self::normalizarCustomerId((string) $this->post('customer_id', ''));
        if (strlen($customerId) !== 10) {
            $this->backWithInput('Customer ID inválido — o Google Ads usa 10 dígitos (ex.: 123-456-7890).');
            return;
        }

        try {
            $platform = PlatformFactory::make($db, ['platform' => 'google_ads']);
        } catch (\Throwable $e) {
            $this->backWithInput('Google Ads ainda não está conectado no sistema (fale com o suporte da FixaOS): ' . $e->getMessage());
            return;
        }

        try {
            /** @var GoogleAdsPlatform $platform garantido pelo match() de PlatformFactory::make() */
            $dados = $platform->resolverConta($customerId);
        } catch (\Throwable $eResolver) {
            // Ainda não vinculado de verdade (1ª vez, ou convite anterior ainda não aceito) —
            // manda o convite de vínculo automaticamente em vez de só rejeitar, poupando a
            // empresa de precisar navegar o Google Ads sozinha pra iniciar isso. Reenviar pra
            // quem já está pendente é inofensivo (o Google não duplica o convite).
            try {
                $platform->enviarConviteVinculo($customerId);
                $_SESSION['_old'] = ['customer_id' => $customerId];
                $this->flash('success', 'Convite de vínculo enviado! Abra seu Google Ads (ou confira seu e-mail) e aceite o convite da FixaOS — depois, volte aqui e clique em "Conectar" de novo.');
                $this->redirectBack();
                return;
            } catch (\Throwable $eConvite) {
                // Convite também falhou — geralmente sinal de Customer ID genuinamente errado
                // (não existe), não só "ainda não vinculado". A mensagem de resolverConta() já
                // orienta a conferir o número, é a mais útil das duas pra mostrar aqui.
                $this->backWithInput($eResolver->getMessage());
                return;
            }
        }

        $db->prepare(
            "INSERT INTO mkt_ad_accounts (empresa_id, platform, external_id, name, currency, status)
             VALUES (?, 'google_ads', ?, ?, ?, 'active')
             ON DUPLICATE KEY UPDATE name = VALUES(name), currency = VALUES(currency), status = 'active', last_sync_error = NULL"
        )->execute([$eid, $dados['external_id'], $dados['name'], $dados['currency']]);

        $stmt = $db->prepare("SELECT id FROM mkt_ad_accounts WHERE empresa_id = ? AND platform = 'google_ads' AND external_id = ?");
        $stmt->execute([$eid, $dados['external_id']]);
        $contaId = (int) $stmt->fetchColumn();

        // Só 1 conta ativa por empresa de cada vez — desliga qualquer outra (a demo fictícia,
        // ou uma conexão anterior com outro Customer ID) pra não misturar campanha fictícia
        // com campanha real no mesmo painel (ambas só filtram por empresa_id, ver painel()).
        $db->prepare("UPDATE mkt_ad_accounts SET status = 'disconnected' WHERE empresa_id = ? AND id != ?")
           ->execute([$eid, $contaId]);

        $this->flash('success', 'Conta do Google Ads conectada: "' . $dados['name'] . '". Os dados reais já aparecem no painel de Marketing.');
        $this->redirect(url('/marketing/conta-google-ads'));
    }

    public function desconectarGoogleAds(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/marketing/conta-google-ads')); return; }

        $eid = $this->empresaId();
        $db = DB::pdo();

        $db->prepare("UPDATE mkt_ad_accounts SET status = 'disconnected' WHERE empresa_id = ? AND platform = 'google_ads'")
           ->execute([$eid]);
        // Reativa a conta de demonstração pra o painel não ficar com dado congelado de antes
        // do desconectar — sem isso, contaAtivaOuDemo() devolveria a demo do jeito que
        // garantirContaDemo() a achar (inclusive 'disconnected'), sem o cron nunca mais
        // atualizar ela por não bater no filtro status='active' de syncAllAccounts().
        $db->prepare("UPDATE mkt_ad_accounts SET status = 'active' WHERE empresa_id = ? AND platform = 'fake'")
           ->execute([$eid]);

        $this->flash('success', 'Conta do Google Ads desconectada. O painel volta a usar a conta de demonstração até você conectar outra.');
        $this->redirect(url('/marketing/conta-google-ads'));
    }

    // ── Controle manual de campanha (painel simplificado) ───────────────────────────────────
    // Diferente da fila de sugestão/aprovação (QueueService) — aqui é o USUÁRIO clicando
    // "pausar"/"salvar orçamento" de propósito, então sempre executa de verdade na hora, nunca
    // respeita MarketingConfig::isDryRun() (decisão do dono do produto: ação manual não é
    // sugestão do robô, não faz sentido simular).

    public function atualizarStatusCampanha(int $campaignId): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $status = (string) $this->post('status', '');
        if (!in_array($status, ['active', 'paused'], true)) {
            $this->json(['sucesso' => false, 'erro' => 'Status inválido.']);
        }

        $db = DB::pdo();
        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $this->empresaId());
        if ($alvo === null) {
            $this->json(['sucesso' => false, 'erro' => 'Campanha não encontrada (ou a conta que ela pertence não está mais ativa).']);
        }

        try {
            $platform = PlatformFactory::make($db, $alvo);
            $platform->setCampaignStatus($alvo['account_external_id'], $alvo['campaign_external_id'], $status);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao atualizar na plataforma: ' . $e->getMessage()]);
        }

        $db->prepare('UPDATE mkt_campaigns SET status = ? WHERE id = ?')->execute([$status, $campaignId]);
        $this->auditarAcaoManual($db, (int) $alvo['empresa_id'], 'campaign_status_manual', 'mkt_campaigns', $campaignId, ['status' => $status]);

        $this->json(['sucesso' => true, 'status' => $status]);
    }

    public function atualizarOrcamentoCampanha(int $campaignId): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $cents = (int) round(moeda_float((string) $this->post('valor', '0')) * 100);
        if ($cents <= 0) {
            $this->json(['sucesso' => false, 'erro' => 'Orçamento precisa ser maior que zero.']);
        }

        $db = DB::pdo();
        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $this->empresaId());
        if ($alvo === null) {
            $this->json(['sucesso' => false, 'erro' => 'Campanha não encontrada (ou a conta que ela pertence não está mais ativa).']);
        }

        try {
            $platform = PlatformFactory::make($db, $alvo);
            $platform->setDailyBudget($alvo['account_external_id'], $alvo['campaign_external_id'], $cents);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao atualizar orçamento na plataforma: ' . $e->getMessage()]);
        }

        $db->prepare('UPDATE mkt_campaigns SET daily_budget_cents = ? WHERE id = ?')->execute([$cents, $campaignId]);
        $this->auditarAcaoManual($db, (int) $alvo['empresa_id'], 'campaign_budget_manual', 'mkt_campaigns', $campaignId, ['daily_budget_cents' => $cents]);

        $this->json(['sucesso' => true, 'daily_budget_cents' => $cents]);
    }

    /**
     * Anúncios de uma campanha — busca AO VIVO na API (não sincronizado/guardado localmente
     * como campanha/insight diário; essa tela é acessada ocasionalmente, não faz parte do
     * ciclo de coleta de rotina). Mesma janela de período do painel (7/14/30 dias).
     */
    public function anuncios(int $campaignId): void
    {
        $eid = $this->empresaId();
        $db = DB::pdo();

        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $eid);
        if ($alvo === null) {
            $this->flash('error', 'Campanha não encontrada.');
            $this->redirect(url('/marketing'));
            return;
        }

        $days = Dashboard::parsePeriod($this->get('dias'), 7);
        $today = Dates::todayInSaoPaulo();
        $from = Dates::addDays($today, -($days - 1));

        $erro = null;
        $anunciosLista = [];
        try {
            $platform = PlatformFactory::make($db, $alvo);
            if ($platform instanceof GoogleAdsPlatform) {
                $anunciosLista = $platform->listAds($alvo['account_external_id'], $alvo['campaign_external_id'], $from, $today);
            }
            // FakeAdPlatform (conta de demonstração) não tem anúncio nenhum pra listar — a
            // tela mostra "nenhum anúncio" nesse caso, sem erro (não é uma falha de verdade).
        } catch (\Throwable $e) {
            $erro = 'Não foi possível carregar os anúncios: ' . $e->getMessage();
        }

        $this->view('marketing.anuncios', [
            'titulo'       => 'Marketing — Anúncios',
            'campanhaId'   => $campaignId,
            'campanhaNome' => $alvo['campaign_name'],
            'anuncios'     => $anunciosLista,
            'dias'         => $days,
            'erro'         => $erro,
        ]);
    }

    public function atualizarStatusAnuncio(int $campaignId): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $status = (string) $this->post('status', '');
        $resourceName = (string) $this->post('resource_name', '');
        if (!in_array($status, ['active', 'paused'], true) || $resourceName === '') {
            $this->json(['sucesso' => false, 'erro' => 'Dados inválidos.']);
        }

        $db = DB::pdo();
        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $this->empresaId());
        if ($alvo === null) {
            $this->json(['sucesso' => false, 'erro' => 'Campanha não encontrada (ou a conta que ela pertence não está mais ativa).']);
        }

        // Confere que o resource_name é mesmo da conta resolvida (nunca de outra conta/empresa) —
        // não impede mexer num anúncio de outra campanha DA MESMA conta (o Google Ads não separa
        // isso por URL), só barra cruzar pra uma conta que não é a desta empresa.
        $prefixoEsperado = "customers/{$alvo['account_external_id']}/adGroupAds/";
        if (!str_starts_with($resourceName, $prefixoEsperado)) {
            $this->json(['sucesso' => false, 'erro' => 'Anúncio não pertence a esta conta.']);
        }

        try {
            $platform = PlatformFactory::make($db, $alvo);
            if (!($platform instanceof GoogleAdsPlatform)) {
                $this->json(['sucesso' => false, 'erro' => 'Essa conta de demonstração não tem anúncio de verdade pra pausar.']);
            }
            $platform->setAdStatus($alvo['account_external_id'], $resourceName, $status);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao atualizar o anúncio na plataforma: ' . $e->getMessage()]);
        }

        $this->auditarAcaoManual($db, (int) $alvo['empresa_id'], 'ad_status_manual', 'mkt_campaigns', $campaignId, ['resource_name' => $resourceName, 'status' => $status]);
        $this->json(['sucesso' => true, 'status' => $status]);
    }

    /**
     * Palavras-chave (positivas) e negativas de uma campanha — busca AO VIVO na API, mesmo
     * padrão de anuncios(). Também busca os grupos de anúncio da campanha (`listAdGroups()`) só
     * pra resolver o formulário de adicionar palavra-chave positiva: com 1 grupo só, a view
     * pré-seleciona ele sozinha; com mais de 1, mostra um <select> — o conceito "grupo de
     * anúncios" nunca é exposto como algo pra entender, só um detalhe de onde a palavra entra.
     */
    public function palavrasChave(int $campaignId): void
    {
        $eid = $this->empresaId();
        $db = DB::pdo();

        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $eid);
        if ($alvo === null) {
            $this->flash('error', 'Campanha não encontrada.');
            $this->redirect(url('/marketing'));
            return;
        }

        $days = Dashboard::parsePeriod($this->get('dias'), 30);
        $today = Dates::todayInSaoPaulo();
        $from = Dates::addDays($today, -($days - 1));

        $erro = null;
        $palavras = [];
        $negativas = [];
        $gruposAnuncio = [];
        try {
            $platform = PlatformFactory::make($db, $alvo);
            if ($platform instanceof GoogleAdsPlatform) {
                $palavras = $platform->listKeywords($alvo['account_external_id'], $alvo['campaign_external_id'], $from, $today);
                $negativas = $platform->listNegativeKeywords($alvo['account_external_id'], $alvo['campaign_external_id']);
                $gruposAnuncio = $platform->listAdGroups($alvo['account_external_id'], $alvo['campaign_external_id']);
            }
            // FakeAdPlatform (conta de demonstração) não tem palavra-chave nenhuma pra listar —
            // a tela mostra listas vazias nesse caso, sem erro (não é uma falha de verdade).
        } catch (\Throwable $e) {
            $erro = 'Não foi possível carregar as palavras-chave: ' . $e->getMessage();
        }

        $this->view('marketing.palavras_chave', [
            'titulo'        => 'Marketing — Palavras-chave',
            'campanhaId'    => $campaignId,
            'campanhaNome'  => $alvo['campaign_name'],
            'palavras'      => $palavras,
            'negativas'     => $negativas,
            'gruposAnuncio' => $gruposAnuncio,
            'dias'          => $days,
            'erro'          => $erro,
        ]);
    }

    public function adicionarPalavrasChave(int $campaignId): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $palavras = GoogleAdsPlatform::normalizarListaPalavras((string) $this->post('texto', ''));
        $matchType = (string) $this->post('match_type', 'PHRASE');
        $adGroupResourceName = (string) $this->post('ad_group_resource_name', '');
        if (!$palavras) {
            $this->json(['sucesso' => false, 'erro' => 'Digite ao menos uma palavra-chave (1 por linha).']);
        }
        if ($adGroupResourceName === '') {
            $this->json(['sucesso' => false, 'erro' => 'Grupo de anúncios não informado.']);
        }

        $db = DB::pdo();
        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $this->empresaId());
        if ($alvo === null) {
            $this->json(['sucesso' => false, 'erro' => 'Campanha não encontrada (ou a conta que ela pertence não está mais ativa).']);
        }

        // Confere que o grupo de anúncios é mesmo desta conta — mesma cautela já usada em
        // atualizarStatusAnuncio() pro anúncio, nunca cruza pra conta de outra empresa.
        $prefixoEsperado = "customers/{$alvo['account_external_id']}/adGroups/";
        if (!str_starts_with($adGroupResourceName, $prefixoEsperado)) {
            $this->json(['sucesso' => false, 'erro' => 'Grupo de anúncios não pertence a esta conta.']);
        }

        $criadas = 0;
        try {
            $platform = PlatformFactory::make($db, $alvo);
            if (!($platform instanceof GoogleAdsPlatform)) {
                $this->json(['sucesso' => false, 'erro' => 'Essa conta de demonstração não tem como cadastrar palavra-chave de verdade.']);
            }
            $criadas = $platform->addKeywords($alvo['account_external_id'], $adGroupResourceName, $palavras, $matchType);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao cadastrar palavra-chave na plataforma: ' . $e->getMessage()]);
        }

        $this->auditarAcaoManual($db, (int) $alvo['empresa_id'], 'keyword_add_manual', 'mkt_campaigns', $campaignId, ['palavras' => $palavras, 'match_type' => $matchType]);
        $this->json(['sucesso' => true, 'criadas' => $criadas]);
    }

    public function adicionarPalavrasNegativas(int $campaignId): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $palavras = GoogleAdsPlatform::normalizarListaPalavras((string) $this->post('texto', ''));
        $matchType = (string) $this->post('match_type', 'BROAD');
        if (!$palavras) {
            $this->json(['sucesso' => false, 'erro' => 'Digite ao menos uma palavra-chave negativa (1 por linha).']);
        }

        $db = DB::pdo();
        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $this->empresaId());
        if ($alvo === null) {
            $this->json(['sucesso' => false, 'erro' => 'Campanha não encontrada (ou a conta que ela pertence não está mais ativa).']);
        }

        $criadas = 0;
        try {
            $platform = PlatformFactory::make($db, $alvo);
            if (!($platform instanceof GoogleAdsPlatform)) {
                $this->json(['sucesso' => false, 'erro' => 'Essa conta de demonstração não tem como cadastrar palavra-chave negativa de verdade.']);
            }
            $criadas = $platform->addNegativeKeywords($alvo['account_external_id'], $alvo['campaign_external_id'], $palavras, $matchType);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao cadastrar palavra-chave negativa na plataforma: ' . $e->getMessage()]);
        }

        $this->auditarAcaoManual($db, (int) $alvo['empresa_id'], 'negative_keyword_add_manual', 'mkt_campaigns', $campaignId, ['palavras' => $palavras, 'match_type' => $matchType]);
        $this->json(['sucesso' => true, 'criadas' => $criadas]);
    }

    public function removerPalavraChave(int $campaignId): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $resourceName = (string) $this->post('resource_name', '');
        if ($resourceName === '') {
            $this->json(['sucesso' => false, 'erro' => 'Dados inválidos.']);
        }

        $db = DB::pdo();
        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $this->empresaId());
        if ($alvo === null) {
            $this->json(['sucesso' => false, 'erro' => 'Campanha não encontrada (ou a conta que ela pertence não está mais ativa).']);
        }

        $prefixoEsperado = "customers/{$alvo['account_external_id']}/adGroupCriteria/";
        if (!str_starts_with($resourceName, $prefixoEsperado)) {
            $this->json(['sucesso' => false, 'erro' => 'Palavra-chave não pertence a esta conta.']);
        }

        try {
            $platform = PlatformFactory::make($db, $alvo);
            if (!($platform instanceof GoogleAdsPlatform)) {
                $this->json(['sucesso' => false, 'erro' => 'Essa conta de demonstração não tem palavra-chave de verdade pra remover.']);
            }
            $platform->removeKeyword($alvo['account_external_id'], $resourceName);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao remover a palavra-chave na plataforma: ' . $e->getMessage()]);
        }

        $this->auditarAcaoManual($db, (int) $alvo['empresa_id'], 'keyword_remove_manual', 'mkt_campaigns', $campaignId, ['resource_name' => $resourceName]);
        $this->json(['sucesso' => true]);
    }

    public function removerPalavraNegativa(int $campaignId): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }

        $resourceName = (string) $this->post('resource_name', '');
        if ($resourceName === '') {
            $this->json(['sucesso' => false, 'erro' => 'Dados inválidos.']);
        }

        $db = DB::pdo();
        $alvo = $this->carregarCampanhaAtiva($db, $campaignId, $this->empresaId());
        if ($alvo === null) {
            $this->json(['sucesso' => false, 'erro' => 'Campanha não encontrada (ou a conta que ela pertence não está mais ativa).']);
        }

        $prefixoEsperado = "customers/{$alvo['account_external_id']}/campaignCriteria/";
        if (!str_starts_with($resourceName, $prefixoEsperado)) {
            $this->json(['sucesso' => false, 'erro' => 'Palavra-chave negativa não pertence a esta conta.']);
        }

        try {
            $platform = PlatformFactory::make($db, $alvo);
            if (!($platform instanceof GoogleAdsPlatform)) {
                $this->json(['sucesso' => false, 'erro' => 'Essa conta de demonstração não tem palavra-chave negativa de verdade pra remover.']);
            }
            $platform->removeNegativeKeyword($alvo['account_external_id'], $resourceName);
        } catch (\Throwable $e) {
            $this->json(['sucesso' => false, 'erro' => 'Falha ao remover a palavra-chave negativa na plataforma: ' . $e->getMessage()]);
        }

        $this->auditarAcaoManual($db, (int) $alvo['empresa_id'], 'negative_keyword_remove_manual', 'mkt_campaigns', $campaignId, ['resource_name' => $resourceName]);
        $this->json(['sucesso' => true]);
    }

    /**
     * Resolve uma campanha + a conta de anúncio dela, só se pertencer à empresa da sessão E a
     * conta ainda estiver ativa (`status='active'`) — sem o segundo filtro, um campaign_id de
     * uma conta já desconectada (reconexão com outro Customer ID, ou a demo desativada) deixaria
     * mandar comando pra API errada. Mesmo formato de linha que QueueService::carregarAlvo()
     * usa, de propósito — os dois lugares que chamam PlatformFactory::make()/setCampaignStatus()/
     * setDailyBudget() esperam as mesmas chaves.
     */
    private function carregarCampanhaAtiva(\PDO $db, int $campaignId, int $empresaId): ?array
    {
        $stmt = $db->prepare(
            "SELECT c.external_id AS campaign_external_id, c.name AS campaign_name, a.id AS ad_account_id,
                    a.platform, a.external_id AS account_external_id, a.empresa_id
             FROM mkt_campaigns c JOIN mkt_ad_accounts a ON a.id = c.ad_account_id
             WHERE c.id = ? AND c.empresa_id = ? AND a.status = 'active'"
        );
        $stmt->execute([$campaignId, $empresaId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }

    private function auditarAcaoManual(\PDO $db, int $empresaId, string $action, string $entityType, int $entityId, array $details): void
    {
        try {
            $stmt = $db->prepare(
                'INSERT INTO mkt_audit_log (empresa_id, usuario_id, action, entity_type, entity_id, details) VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$empresaId, $this->usuarioId(), $action, $entityType, $entityId, json_encode($details, JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e) {
            // mkt_audit_log só existe a partir da migration 069 — mesma cautela de rodarOtimizacao():
            // a ação principal (já executada na plataforma e gravada localmente) não pode
            // falhar por causa de um log auxiliar.
            error_log('[marketing] auditarAcaoManual falhou (empresa ' . $empresaId . '): ' . $e->getMessage());
        }
    }

    /** Roda depois de toda sincronização (Etapa 3): gera sugestões novas e já tenta executar
     *  qualquer pedido que porventura já esteja aprovado (ex.: aprovado pelo botão mas o clique
     *  de execução falhou por algum motivo transitório). */
    private function rodarOtimizacao(\PDO $db, int $empresaId, int $adAccountId, ?\DateTimeImmutable $now = null): void
    {
        try {
            $queue = QueueService::make();
            $queue->gerarSugestoes($empresaId, $adAccountId, $now);
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
