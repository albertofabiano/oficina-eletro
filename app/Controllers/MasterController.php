<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class MasterController extends Controller
{
    // ── Auth ────────────────────────────────────────────────────────────
    public function loginForm(): void
    {
        require BASE_PATH . '/app/Views/master/login.php';
        exit;
    }

    public function login(): void
    {
        $email = trim($this->post('email', ''));
        $senha = $this->post('senha', '');

        $stmt = DB::pdo()->prepare("SELECT * FROM master_admins WHERE email = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($senha, $admin['senha'])) {
            $this->flash('error', 'Credenciais inválidas.');
            $this->redirect(url('/master/login'));
        }

        session_regenerate_id(true);
        $_SESSION['master_id']   = $admin['id'];
        $_SESSION['master_nome'] = $admin['nome'];
        $_SESSION['master_email']= $admin['email'];

        DB::pdo()->prepare("UPDATE master_admins SET ultimo_login=NOW() WHERE id=?")->execute([$admin['id']]);

        $this->redirect(url('/master'));
    }

    public function logout(): void
    {
        unset($_SESSION['master_id'], $_SESSION['master_nome'], $_SESSION['master_email']);
        $this->redirect(url('/master/login'));
    }

    // ── Dashboard ────────────────────────────────────────────────────────
    public function dashboard(): void
    {
        $db = DB::pdo();

        $metricas = $db->query("
            SELECT
              (SELECT COUNT(*) FROM empresas WHERE ativo=1 AND reivindicada=1)  AS total_empresas,
              (SELECT COUNT(*) FROM usuarios WHERE ativo=1)              AS total_usuarios,
              (SELECT COUNT(*) FROM ordens_servico)                       AS total_os,
              (SELECT COUNT(*) FROM clientes)                             AS total_clientes,
              (SELECT COUNT(*) FROM ordens_servico WHERE DATE(criado_em)=CURDATE()) AS os_hoje,
              (SELECT COUNT(*) FROM empresas WHERE trial_ate >= CURDATE() AND ativo=1 AND reivindicada=1) AS em_trial
        ")->fetch();

        $empresas = $db->query("
            SELECT e.*,
              (SELECT COUNT(*) FROM usuarios u WHERE u.empresa_id = e.id AND u.ativo=1) AS qtd_usuarios,
              (SELECT COUNT(*) FROM ordens_servico os WHERE os.empresa_id = e.id) AS qtd_os,
              (SELECT COUNT(*) FROM clientes c WHERE c.empresa_id = e.id) AS qtd_clientes,
              (SELECT MAX(os.criado_em) FROM ordens_servico os WHERE os.empresa_id = e.id) AS ultima_os
            FROM empresas e
            WHERE e.reivindicada=1
            ORDER BY e.criado_em DESC
        ")->fetchAll();

        $osPorDia = $db->query("
            SELECT DATE(criado_em) AS dia, COUNT(*) AS total
            FROM ordens_servico
            WHERE criado_em >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            GROUP BY DATE(criado_em) ORDER BY dia
        ")->fetchAll();

        $this->view('master.dashboard', [
            'titulo'   => 'Painel Master',
            'metricas' => $metricas,
            'empresas' => $empresas,
            'osPorDia' => $osPorDia,
        ], 'master');
    }

    // ── Empresas ─────────────────────────────────────────────────────────
    public function empresas(): void
    {
        $db      = DB::pdo();
        $busca   = $this->get('busca', '');
        $where   = "WHERE e.reivindicada=1" . ($busca ? " AND (e.razao_social LIKE ? OR e.email LIKE ? OR e.nome_fantasia LIKE ?)" : "");
        $params  = $busca ? ["%$busca%","%$busca%","%$busca%"] : [];

        $stmt = $db->prepare("
            SELECT e.*,
              (SELECT COUNT(*) FROM usuarios u WHERE u.empresa_id=e.id) AS qtd_usuarios,
              (SELECT COUNT(*) FROM ordens_servico os WHERE os.empresa_id=e.id) AS qtd_os,
              (SELECT COALESCE(c.paid_amount, c.valor) FROM cobrancas c
                WHERE c.empresa_id=e.id AND c.status='pago' AND (c.tipo IS NULL OR c.tipo <> 'credito')
                ORDER BY c.pago_em DESC LIMIT 1) AS ultimo_valor_pago
            FROM empresas e $where ORDER BY e.criado_em DESC
        ");
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $planosCfg = require BASE_PATH . '/config/planos.php';
        $nomesPlano = array_column($planosCfg['planos'], 'nome', 'codigo');

        $hoje   = date('Y-m-d');
        $resumo = [
            'total'          => count($rows),
            'ativas'         => 0,
            'pagas'          => 0,
            'sem_plano'      => 0,
            'trial_expirado' => 0,
            'valor_total'    => 0.0,
            'por_plano'      => [],
        ];
        foreach ($rows as $r) {
            if (!empty($r['ativo'])) $resumo['ativas']++;

            $pago = !empty($r['plano_atual']) && !empty($r['licenca_ate']) && $r['licenca_ate'] >= $hoje;
            if ($pago) {
                $resumo['pagas']++;
                $nome = $nomesPlano[$r['plano_atual']] ?? $r['plano_atual'];
                $resumo['por_plano'][$nome] = ($resumo['por_plano'][$nome] ?? 0) + 1;
            } else {
                $resumo['sem_plano']++;
                if (!empty($r['trial_ate']) && $r['trial_ate'] < $hoje) $resumo['trial_expirado']++;
            }
            if (!empty($r['ultimo_valor_pago'])) $resumo['valor_total'] += (float) $r['ultimo_valor_pago'];
        }

        $this->view('master.empresas', [
            'titulo'   => 'Empresas',
            'empresas' => $rows,
            'busca'    => $busca,
            'resumo'   => $resumo,
        ], 'master');
    }

    public function verEmpresa(string $id): void
    {
        $db   = DB::pdo();
        $stmt = $db->prepare("SELECT * FROM empresas WHERE id=?");
        $stmt->execute([(int)$id]);
        $empresa = $stmt->fetch();
        if (!$empresa) { $this->flash('error', 'Empresa não encontrada.'); $this->redirect(url('/master/empresas')); }

        $usuarios = $db->prepare("SELECT * FROM usuarios WHERE empresa_id=? ORDER BY nome");
        $usuarios->execute([(int)$id]);

        $osRecentes = $db->prepare("
            SELECT os.*, c.nome AS cliente_nome, s.nome AS status_nome, s.cor AS status_cor, s.tipo AS status_tipo
            FROM ordens_servico os
            LEFT JOIN clientes c ON c.id=os.cliente_id
            LEFT JOIN os_status s ON s.id=os.status_id
            WHERE os.empresa_id=? ORDER BY os.criado_em DESC LIMIT 10
        ");
        $osRecentes->execute([(int)$id]);

        $configs = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id=?");
        $configs->execute([(int)$id]);
        $cfgArr = [];
        foreach ($configs->fetchAll() as $r) $cfgArr[$r['chave']] = $r['valor'];

        $this->view('master.empresa_ver', [
            'titulo'   => 'Empresa: ' . $empresa['nome_fantasia'],
            'empresa'  => $empresa,
            'usuarios' => $usuarios->fetchAll(),
            'osRecentes'=> $osRecentes->fetchAll(),
            'configs'  => $cfgArr,
        ], 'master');
    }

    public function toggleEmpresa(string $id): void
    {
        $db   = DB::pdo();
        $stmt = $db->prepare("SELECT ativo FROM empresas WHERE id=?");
        $stmt->execute([(int)$id]);
        $atual = (int) $stmt->fetchColumn();
        $db->prepare("UPDATE empresas SET ativo=? WHERE id=?")->execute([$atual ? 0 : 1, (int)$id]);
        $this->flash('success', 'Status da empresa atualizado.');
        $this->redirect(url('/master/empresas/' . $id));
    }

    /**
     * Exclui DEFINITIVAMENTE uma empresa e todos os seus dados (cascata via FK).
     * Exige confirmação digitando o nome fantasia exato para evitar exclusão acidental.
     */
    public function excluirEmpresa(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/empresas/' . $id)); }

        $db   = DB::pdo();
        $stmt = $db->prepare("SELECT nome_fantasia FROM empresas WHERE id=?");
        $stmt->execute([(int)$id]);
        $nome = $stmt->fetchColumn();

        if ($nome === false) {
            $this->flash('error', 'Empresa não encontrada.');
            $this->redirect(url('/master/empresas'));
        }

        // Confirmação: precisa digitar o nome fantasia exato
        if (trim((string)$this->post('confirma')) !== trim((string)$nome)) {
            $this->flash('error', 'Confirmação inválida: digite o nome exato da empresa para excluir.');
            $this->redirect(url('/master/empresas/' . $id));
        }

        try {
            // Várias tabelas cascateiam de empresas independentemente, mas têm entre si uma
            // FK sem ON DELETE CASCADE (fin_lancamentos.conta_id -> fin_contas,
            // crm_oportunidades.estagio_id -> crm_estagios, ordens_servico.equipamento_id ->
            // equipamentos) — se o MySQL cascatear a tabela "pai" antes da "filha", o DELETE
            // trava com erro de FK. Por isso apagamos essas "filhas" manualmente primeiro.
            $db->prepare("DELETE FROM fin_lancamentos WHERE empresa_id=?")->execute([(int)$id]);
            $db->prepare("DELETE FROM crm_oportunidades WHERE empresa_id=?")->execute([(int)$id]);
            $db->prepare("DELETE FROM ordens_servico WHERE empresa_id=?")->execute([(int)$id]);
            $db->prepare("DELETE FROM empresas WHERE id=?")->execute([(int)$id]);
        } catch (\Throwable $e) {
            $this->flash('error', 'Não foi possível excluir a empresa: ' . $e->getMessage());
            $this->redirect(url('/master/empresas/' . $id));
        }

        $this->flash('success', 'Empresa "' . $nome . '" e todos os seus dados foram excluídos.');
        $this->redirect(url('/master/empresas'));
    }

    /**
     * Suporte: Master Admin troca a senha de um usuário direto, sem precisar do e-mail de
     * "esqueci minha senha" — útil quando o cliente não recebe o e-mail, não lembra o
     * endereço cadastrado, ou simplesmente prefere resolver por telefone/WhatsApp.
     */
    public function alterarSenhaUsuario(string $id): void
    {
        $db   = DB::pdo();
        $stmt = $db->prepare("SELECT id, empresa_id, nome FROM usuarios WHERE id = ?");
        $stmt->execute([(int) $id]);
        $usuario = $stmt->fetch();
        if (!$usuario) { $this->flash('error', 'Usuário não encontrado.'); $this->redirect(url('/master/empresas')); }

        $voltar = url('/master/empresas/' . $usuario['empresa_id']);
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($voltar); }

        $senha = (string) $this->post('senha', '');
        if (strlen($senha) < 6) {
            $this->flash('error', 'Senha mínima: 6 caracteres.');
            $this->redirect($voltar);
        }

        $db->prepare("UPDATE usuarios SET senha = ? WHERE id = ?")
           ->execute([password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]), (int) $id]);

        $this->flash('success', 'Senha de ' . $usuario['nome'] . ' atualizada com sucesso.');
        $this->redirect($voltar);
    }

    public function salvarEmpresa(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        DB::pdo()->prepare(
            "UPDATE empresas SET nome_fantasia=?, razao_social=?, email=?, telefone=?, plano=?, trial_ate=?, max_usuarios=?, ativo=? WHERE id=?"
        )->execute([
            $this->post('nome_fantasia'),
            $this->post('razao_social'),
            $this->post('email'),
            $this->post('telefone'),
            $this->post('plano', 'basico'),
            $this->post('trial_ate') ?: null,
            (int) $this->post('max_usuarios', 3),
            (int) $this->post('ativo', 1),
            (int) $id,
        ]);

        $this->flash('success', 'Empresa atualizada!');
        $this->redirect(url('/master/empresas/' . $id));
    }

    /**
     * Ativa/desativa o DESTAQUE da empresa no diretório (fluxo InfinitePay).
     * Ativar = diretorio_destaque='basico' por 31 dias (renova a cada pagamento).
     * Usar após confirmar o pagamento da assinatura na InfinitePay.
     */
    public function toggleDestaque(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT diretorio_destaque, diretorio_destaque_ate FROM empresas WHERE id=?");
        $stmt->execute([(int) $id]);
        $e = $stmt->fetch();
        if (!$e) { $this->flash('error', 'Empresa não encontrada.'); $this->redirect(url('/master/empresas')); }

        $ativo = ($e['diretorio_destaque'] ?? 'none') !== 'none'
                 && (empty($e['diretorio_destaque_ate']) || $e['diretorio_destaque_ate'] >= date('Y-m-d'));

        if ($ativo) {
            $db->prepare("UPDATE empresas SET diretorio_destaque='none', diretorio_destaque_ate=NULL WHERE id=?")->execute([(int) $id]);
            $this->flash('success', 'Destaque desativado.');
        } else {
            $ate = date('Y-m-d', strtotime('+31 days'));
            $db->prepare("UPDATE empresas SET diretorio_destaque='basico', diretorio_destaque_ate=? WHERE id=?")->execute([$ate, (int) $id]);
            $this->flash('success', 'Destaque ativado até ' . date('d/m/Y', strtotime($ate)) . '! A empresa já aparece no topo do diretório.');
        }
        $this->redirect(url('/master/empresas/' . $id));
    }

    /**
     * Liga/desliga o módulo Marketing (tráfego pago) pra UMA empresa — mesmo padrão de
     * toggleDestaque(), substitui rodar scripts/marketing_habilitar_empresa.php na mão pra
     * habilitar empresa por empresa depois que o piloto (tvservice/Eletroli/Timetec) deixou de
     * ser a única audiência. O script continua existindo pra habilitar várias de uma vez por
     * nome (útil pra redes com várias unidades), mas ligar uma só agora tem uma tela.
     */
    public function toggleMarketing(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT marketing_habilitado FROM empresas WHERE id=?");
        $stmt->execute([(int) $id]);
        $atual = $stmt->fetchColumn();
        if ($atual === false) { $this->flash('error', 'Empresa não encontrada.'); $this->redirect(url('/master/empresas')); }

        $novo = $atual ? 0 : 1;
        $db->prepare("UPDATE empresas SET marketing_habilitado=? WHERE id=?")->execute([$novo, (int) $id]);
        $this->flash('success', $novo ? 'Módulo Marketing habilitado — o link já aparece pro admin dessa empresa.' : 'Módulo Marketing desabilitado.');
        $this->redirect(url('/master/empresas/' . $id));
    }

    // ── WhatsApp: página de conexão (QR ao vivo) ─────────────────────────
    public function whatsapp(): void
    {
        $state = \App\Services\WhatsAppService::status();
        $qr    = $state === 'open' ? null : \App\Services\WhatsAppService::qrDataUri();
        require BASE_PATH . '/app/Views/master/whatsapp.php';
        exit;
    }

    // ── Marketplace: gerenciar créditos ──────────────────────────────────

    public function marketplaceCreditos(): void
    {
        $busca   = $this->get('busca', '');
        $model   = new \App\Models\Marketplace();
        $saldos  = $model->saldosTodas($busca);

        $this->view('master.marketplace_creditos', [
            'titulo' => 'Marketplace — Créditos das Empresas',
            'saldos' => $saldos,
            'busca'  => $busca,
        ], 'master');
    }

    public function adicionarCreditos(string $empresaId): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $quantidade   = (int) $this->post('quantidade', 0);
        $justificativa = trim($this->post('justificativa', 'Créditos adicionados pelo admin master'));

        if ($quantidade < 1 || $quantidade > 9999) {
            $this->flash('error', 'Quantidade inválida (1–9999).');
            $this->redirect(url('/master/marketplace/creditos'));
        }

        // Verificar se empresa existe
        $stmt = DB::pdo()->prepare("SELECT nome_fantasia FROM empresas WHERE id = ?");
        $stmt->execute([(int) $empresaId]);
        $empresa = $stmt->fetchColumn();

        if (!$empresa) {
            $this->flash('error', 'Empresa não encontrada.');
            $this->redirect(url('/master/marketplace/creditos'));
        }

        $model = new \App\Models\Marketplace();
        $model->adicionarCreditos((int) $empresaId, $quantidade, $justificativa, null);

        $this->flash('success', "{$quantidade} crédito(s) adicionado(s) para {$empresa}.");
        $this->redirect(url('/master/marketplace/creditos'));
    }

    // ── Marketing: conexão OAuth da conta Gerenciadora do Google Ads ────────
    // Credencial GLOBAL (scope='global' em mkt_credentials) — uma só, compartilhada por toda
    // empresa cliente do módulo Marketing (modelo agência, ver PlatformFactory::googleAds()).
    // Nunca por empresa: por isso vive aqui no Master, não em MarketingController (que é
    // por-empresa). O redirect_uri é fixo e precisa bater com o cadastrado no Google Cloud
    // Console (Credenciais → ID do cliente OAuth → URIs de redirecionamento autorizados).

    private function marketingCfg(): array
    {
        $arquivo = BASE_PATH . '/config/marketing.php';
        return is_file($arquivo) ? require $arquivo : [];
    }

    /** Fixo de propósito (não derivado de config/app.php) — precisa bater exatamente com a
     *  URI cadastrada no Google Cloud Console, independente de qual `url` está configurada
     *  neste ambiente (evita quebrar se config/app.local.php ainda não estiver correto). */
    private function marketingGoogleRedirectUri(): string
    {
        return 'https://fixaos.com.br/marketing/conectar/google/callback';
    }

    public function marketingGoogleAds(): void
    {
        $db = DB::pdo();
        $stmt = $db->query(
            "SELECT created_at, updated_at FROM mkt_credentials
             WHERE scope='global' AND platform='google_ads' ORDER BY id DESC LIMIT 1"
        );
        $cred = $stmt->fetch();

        $cfg = $this->marketingCfg();
        $this->view('master.marketing_google_ads', [
            'titulo'         => 'Marketing — Google Ads',
            'conectado'      => (bool) $cred,
            'cred'           => $cred ?: null,
            'configOk'       => !empty($cfg['google_ads']['client_id']) && !empty($cfg['google_ads']['client_secret']),
            'loginCustomerId'=> $cfg['google_ads']['login_customer_id'] ?? '',
        ], 'master');
    }

    public function marketingConectarGoogle(): void
    {
        $cfg = $this->marketingCfg();
        $googleCfg = $cfg['google_ads'] ?? [];
        if (empty($googleCfg['client_id']) || empty($googleCfg['client_secret'])) {
            $this->flash('error', 'Configure client_id/client_secret do Google Ads em config/marketing.php antes de conectar.');
            $this->redirect(url('/master/marketing/google-ads'));
        }

        $state = bin2hex(random_bytes(16));
        $_SESSION['mkt_google_oauth_state'] = $state;

        // access_type=offline + prompt=consent: sem os dois, o Google só devolve
        // refresh_token na PRIMEIRA autorização de uma conta — reconectar depois (ex.: pra
        // trocar de conta Google) sairia sem token nenhum, silenciosamente.
        $params = http_build_query([
            'client_id'     => $googleCfg['client_id'],
            'redirect_uri'  => $this->marketingGoogleRedirectUri(),
            'response_type' => 'code',
            'scope'         => 'https://www.googleapis.com/auth/adwords',
            'access_type'   => 'offline',
            'prompt'        => 'consent',
            'state'         => $state,
        ]);

        header('Location: https://accounts.google.com/o/oauth2/v2/auth?' . $params);
        exit;
    }

    public function marketingConectarGoogleCallback(): void
    {
        $state = $_GET['state'] ?? '';
        $code  = $_GET['code']  ?? '';
        $error = $_GET['error'] ?? '';

        if ($error || !$code) {
            $this->flash('error', 'Conexão com o Google Ads cancelada ou recusada (' . e($error ?: 'sem código') . ').');
            $this->redirect(url('/master/marketing/google-ads'));
        }
        if (!hash_equals($_SESSION['mkt_google_oauth_state'] ?? '', $state)) {
            $this->flash('error', 'Estado inválido — tente conectar de novo.');
            $this->redirect(url('/master/marketing/google-ads'));
        }
        unset($_SESSION['mkt_google_oauth_state']);

        $cfg = $this->marketingCfg();
        $googleCfg = $cfg['google_ads'] ?? [];

        $ch = curl_init('https://oauth2.googleapis.com/token');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_POSTFIELDS     => http_build_query([
                'code'          => $code,
                'client_id'     => $googleCfg['client_id'] ?? '',
                'client_secret' => $googleCfg['client_secret'] ?? '',
                'redirect_uri'  => $this->marketingGoogleRedirectUri(),
                'grant_type'    => 'authorization_code',
            ]),
        ]);
        $body     = curl_exec($ch);
        $erroCurl = curl_errno($ch) !== 0 ? curl_error($ch) : null;
        curl_close($ch);

        $json = is_string($body) ? json_decode($body, true) : null;

        if ($erroCurl !== null || !is_array($json)) {
            $this->flash('error', 'Falha de rede ao trocar o código pelo token do Google.');
            $this->redirect(url('/master/marketing/google-ads'));
        }
        if (empty($json['refresh_token'])) {
            // Acontece quando o Google já tinha uma autorização anterior desta mesma conta
            // sem revogar — mesmo com prompt=consent, alguns fluxos ainda pulam o
            // refresh_token se o app já constava como autorizado. Orienta a resolver na mão.
            $this->flash('error', 'O Google não devolveu um refresh_token. Revogue o acesso do FixaOS em myaccount.google.com/permissions e tente conectar de novo.');
            $this->redirect(url('/master/marketing/google-ads'));
        }

        $cipher = new \App\Services\Marketing\CredentialCipher($cfg);
        $enc    = $cipher->encrypt((string) $json['refresh_token']);

        $db = DB::pdo();
        // Só 1 linha faz sentido pra scope='global' — substitui a anterior, se houver.
        $db->prepare("DELETE FROM mkt_credentials WHERE scope='global' AND platform='google_ads'")->execute();
        $db->prepare(
            "INSERT INTO mkt_credentials (scope, empresa_id, platform, token_ciphertext, token_nonce, key_version)
             VALUES ('global', NULL, 'google_ads', ?, ?, ?)"
        )->execute([$enc['ciphertext'], $enc['nonce'], $enc['key_version']]);

        $this->flash('success', 'Google Ads conectado com sucesso! A conta Gerenciadora já está pronta pro módulo Marketing usar.');
        $this->redirect(url('/master/marketing/google-ads'));
    }

    public function marketingGoogleAdsDesconectar(): void
    {
        if (!csrf_verify()) {
            $this->flash('error', 'Token inválido.');
            $this->redirect(url('/master/marketing/google-ads'));
        }
        DB::pdo()->prepare("DELETE FROM mkt_credentials WHERE scope='global' AND platform='google_ads'")->execute();
        $this->flash('success', 'Google Ads desconectado.');
        $this->redirect(url('/master/marketing/google-ads'));
    }

    // ── Blocos AdSense ───────────────────────────────────────────────────
    public function adsense(): void
    {
        $db  = DB::pdo();
        $marketplace = $db->query("SELECT * FROM master_adsense_blocos WHERE local='marketplace' ORDER BY posicao")->fetchAll();
        $forum       = $db->query("SELECT * FROM master_adsense_blocos WHERE local='forum' ORDER BY posicao")->fetchAll();
        $diretorio   = $db->query("SELECT * FROM master_adsense_blocos WHERE local='diretorio' ORDER BY posicao")->fetchAll();
        require BASE_PATH . '/app/Views/master/adsense.php';
        exit;
    }

    public function salvarAdsense(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }
        $db    = DB::pdo();
        $pos   = (int)$this->post('posicao');
        $local = in_array($this->post('local'), ['marketplace','forum','diretorio']) ? $this->post('local') : 'marketplace';
        $max   = in_array($local, ['forum','diretorio']) ? 5 : 3;
        if ($pos < 1 || $pos > $max) { $this->json(['error' => 'Posição inválida'], 422); }

        $db->prepare(
            "UPDATE master_adsense_blocos SET titulo=?, codigo=?, ativo=? WHERE local=? AND posicao=?"
        )->execute([
            trim($this->post('titulo', '')),
            trim($this->post('codigo', '')),
            $this->post('ativo') ? 1 : 0,
            $local, $pos,
        ]);

        $this->json(['success' => true]);
    }

    // ── Usuários globais ─────────────────────────────────────────────────
    public function usuarios(): void
    {
        $busca = $this->get('busca', '');
        $where = $busca ? "AND (u.nome LIKE ? OR u.email LIKE ?)" : "";
        $p     = $busca ? ["%$busca%","%$busca%"] : [];

        $stmt = DB::pdo()->prepare("
            SELECT u.*, e.nome_fantasia AS empresa_nome
            FROM usuarios u JOIN empresas e ON e.id=u.empresa_id
            WHERE 1=1 $where ORDER BY u.criado_em DESC LIMIT 200
        ");
        $stmt->execute($p);

        $this->view('master.usuarios', [
            'titulo'   => 'Usuários',
            'usuarios' => $stmt->fetchAll(),
            'busca'    => $busca,
        ], 'master');
    }

    // ── Admins master ────────────────────────────────────────────────────
    public function admins(): void
    {
        $admins = DB::pdo()->query("SELECT * FROM master_admins ORDER BY criado_em")->fetchAll();
        $this->view('master.admins', [
            'titulo'  => 'Admins Master',
            'admins'  => $admins,
        ], 'master');
    }

    public function salvarAdmin(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $id    = (int) $this->post('id');
        $nome  = trim($this->post('nome'));
        $email = trim($this->post('email'));
        $senha = $this->post('senha');
        $db    = DB::pdo();

        if ($id) {
            $data = ['nome' => $nome, 'ativo' => (int)$this->post('ativo',1)];
            if ($senha) $data['senha'] = password_hash($senha, PASSWORD_BCRYPT, ['cost'=>12]);
            $set = implode(',', array_map(fn($c) => "`$c`=?", array_keys($data)));
            $db->prepare("UPDATE master_admins SET $set WHERE id=?")->execute([...array_values($data), $id]);
        } else {
            if (!$senha) { $this->flash('error', 'Senha obrigatória.'); $this->redirectBack(); }
            $db->prepare("INSERT INTO master_admins (nome,email,senha) VALUES(?,?,?)")
               ->execute([$nome, $email, password_hash($senha, PASSWORD_BCRYPT, ['cost'=>12])]);
        }

        $this->flash('success', 'Admin salvo!');
        $this->redirect(url('/master/admins'));
    }

    public function excluirAdmin(string $id): void
    {
        if ((int)$id === (int)$_SESSION['master_id']) {
            $this->flash('error', 'Não pode excluir o próprio admin.');
            $this->redirect(url('/master/admins'));
        }
        DB::pdo()->prepare("DELETE FROM master_admins WHERE id=?")->execute([(int)$id]);
        $this->flash('success', 'Admin removido.');
        $this->redirect(url('/master/admins'));
    }

    // ── Anúncios do Diretório ───────────────────────────────────────────
    public function anunciosDiretorio(): void
    {
        $db = DB::pdo();

        $assinaturas = $db->query("
            SELECT a.*, p.nome AS plano_nome, p.tipo AS plano_tipo, e.nome_fantasia AS empresa_nome
            FROM diretorio_assinaturas a
            JOIN diretorio_planos p ON p.id = a.plano_id
            JOIN empresas e ON e.id = a.empresa_id
            ORDER BY a.criado_em DESC
        ")->fetchAll();

        $banners = $db->query("
            SELECT b.*, e.nome_fantasia AS empresa_nome
            FROM diretorio_banners b
            JOIN empresas e ON e.id = b.empresa_id
            ORDER BY b.aprovado ASC, b.criado_em DESC
        ")->fetchAll();

        $planos = $db->query("SELECT * FROM diretorio_planos ORDER BY tipo, preco")->fetchAll();

        $kpis = [
            'pendentes'        => (int)$db->query("SELECT COUNT(*) FROM diretorio_assinaturas WHERE status='pendente'")->fetchColumn(),
            'ativos'           => (int)$db->query("SELECT COUNT(*) FROM diretorio_assinaturas WHERE status='ativo'")->fetchColumn(),
            'receita'          => (float)$db->query("SELECT COALESCE(SUM(valor_pago),0) FROM diretorio_assinaturas WHERE status='ativo'")->fetchColumn(),
            'banners_pendentes'=> (int)$db->query("SELECT COUNT(*) FROM diretorio_banners WHERE aprovado=0")->fetchColumn(),
        ];

        $this->view('master.anuncios_diretorio', compact('assinaturas','banners','planos','kpis'), 'master');
    }

    public function ativarAssinatura(int $id): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT a.*, p.duracao_dias, p.tipo AS plano_tipo FROM diretorio_assinaturas a JOIN diretorio_planos p ON p.id=a.plano_id WHERE a.id=?");
        $stmt->execute([$id]);
        $a = $stmt->fetch();
        if (!$a) { $this->flash('error','Assinatura não encontrada.'); $this->redirect(url('/master/diretorio')); }

        $inicio = date('Y-m-d');
        $fim    = date('Y-m-d', strtotime("+{$a['duracao_dias']} days"));

        $db->prepare("UPDATE diretorio_assinaturas SET status='ativo', data_inicio=?, data_fim=? WHERE id=?")->execute([$inicio, $fim, $id]);

        // Ativar destaque na empresa
        if ($a['plano_tipo'] === 'destaque') {
            $tipo = $a['valor_pago'] > 60 ? 'premium' : 'basico';
            $db->prepare("UPDATE empresas SET diretorio_destaque=?, diretorio_destaque_ate=? WHERE id=?")->execute([$tipo, $fim, $a['empresa_id']]);
        }

        $this->flash('success','Assinatura ativada!');
        $this->redirect(url('/master/diretorio'));
    }

    public function cancelarAssinatura(int $id): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT * FROM diretorio_assinaturas WHERE id=?");
        $stmt->execute([$id]);
        $a = $stmt->fetch();
        if ($a) {
            $db->prepare("UPDATE diretorio_assinaturas SET status='cancelado' WHERE id=?")->execute([$id]);
            $db->prepare("UPDATE empresas SET diretorio_destaque='none', diretorio_destaque_ate=NULL WHERE id=?")->execute([$a['empresa_id']]);
        }
        $this->flash('success','Assinatura cancelada.');
        $this->redirect(url('/master/diretorio'));
    }

    public function aprovarBanner(int $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_banners SET aprovado=1 WHERE id=?")->execute([$id]);
        $this->flash('success','Banner aprovado e publicado!');
        $this->redirect(url('/master/diretorio'));
    }

    public function reprovarBanner(int $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_banners SET aprovado=0, imagem=NULL WHERE id=?")->execute([$id]);
        $this->flash('success','Banner reprovado.');
        $this->redirect(url('/master/diretorio'));
    }

    public function togglePlano(int $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_planos SET ativo = 1 - ativo WHERE id=?")->execute([$id]);
        $this->redirect(url('/master/diretorio'));
    }

    public function salvarPlano(): void
    {
        if (!csrf_verify()) { $this->flash('error','Token inválido.'); $this->redirect(url('/master/diretorio')); }
        $db = DB::pdo();
        $id       = (int)$this->post('id', 0);
        $nome     = trim($this->post('nome', ''));
        $tipo     = in_array($this->post('tipo'),['destaque','banner']) ? $this->post('tipo') : 'destaque';
        $descricao= trim($this->post('descricao', ''));
        $preco    = (float)str_replace(',','.',str_replace('.','',$this->post('preco','0')));
        $duracao  = (int)$this->post('duracao_dias', 30);
        // Whitelist (ver diretorio_banner_posicoes()) — nunca grava um slug inventado via POST direto.
        $posicaoPost = trim((string) $this->post('posicao_banner', ''));
        $posicao  = ($tipo === 'banner' && array_key_exists($posicaoPost, diretorio_banner_posicoes())) ? $posicaoPost : null;
        $beneficios = trim($this->post('beneficios', ''));
        $ativo    = (int)$this->post('ativo', 1);

        if ($id > 0) {
            $db->prepare("UPDATE diretorio_planos SET nome=?,tipo=?,descricao=?,preco=?,duracao_dias=?,posicao_banner=?,beneficios=?,ativo=? WHERE id=?")
               ->execute([$nome,$tipo,$descricao,$preco,$duracao,$posicao,$beneficios,$ativo,$id]);
            $this->flash('success','Plano atualizado!');
        } else {
            $db->prepare("INSERT INTO diretorio_planos (nome,tipo,descricao,preco,duracao_dias,posicao_banner,beneficios,ativo) VALUES (?,?,?,?,?,?,?,?)")
               ->execute([$nome,$tipo,$descricao,$preco,$duracao,$posicao,$beneficios,$ativo]);
            $this->flash('success','Plano criado!');
        }
        $this->redirect(url('/master/diretorio'));
    }

    public function excluirPlano(int $id): void
    {
        $db = DB::pdo();
        $assinaturas = $db->prepare("SELECT COUNT(*) FROM diretorio_assinaturas WHERE plano_id=?");
        $assinaturas->execute([$id]);
        if ($assinaturas->fetchColumn() > 0) {
            $this->flash('error','Não é possível excluir um plano com assinaturas ativas. Desative-o.');
            $this->redirect(url('/master/diretorio'));
        }
        $db->prepare("DELETE FROM diretorio_planos WHERE id=?")->execute([$id]);
        $this->flash('success','Plano excluído.');
        $this->redirect(url('/master/diretorio'));
    }

    // ── Moderação de Avaliações ─────────────────────────────────────────
    public function avaliacoes(): void
    {
        $db     = DB::pdo();
        $filtro = $_GET['filtro'] ?? 'pendentes';

        $where = match($filtro) {
            'aprovadas'   => "a.aprovado = 1 AND a.situacao = 'publicada'",
            'reprovadas'  => 'a.aprovado = 2',
            'contestadas' => "a.situacao = 'contestada'",
            default       => 'a.aprovado = 0',
        };

        $stmt = $db->prepare("
            SELECT a.*, e.nome_fantasia AS empresa_nome, e.slug AS empresa_slug
            FROM diretorio_avaliacoes a
            JOIN empresas e ON e.id = a.empresa_id
            WHERE $where
            ORDER BY a.criado_em DESC
        ");
        $stmt->execute();
        $avaliacoes = $stmt->fetchAll();

        $pendentes   = (int)$db->query("SELECT COUNT(*) FROM diretorio_avaliacoes WHERE aprovado = 0")->fetchColumn();
        $aprovadas   = (int)$db->query("SELECT COUNT(*) FROM diretorio_avaliacoes WHERE aprovado = 1 AND situacao = 'publicada'")->fetchColumn();
        $reprovadas  = (int)$db->query("SELECT COUNT(*) FROM diretorio_avaliacoes WHERE aprovado = 2")->fetchColumn();
        $contestadas = (int)$db->query("SELECT COUNT(*) FROM diretorio_avaliacoes WHERE situacao = 'contestada'")->fetchColumn();

        $this->view('master.avaliacoes', compact('avaliacoes','filtro','pendentes','aprovadas','reprovadas','contestadas'), 'master');
    }

    /** Nega a contestação: mantém a avaliação pública. */
    public function manterAvaliacao(int $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_avaliacoes SET situacao='publicada', contestacao_motivo=NULL WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Contestação negada — avaliação mantida no ar.');
        $this->redirect(url('/master/avaliacoes?filtro=contestadas'));
    }

    /** Aceita a contestação: oculta a avaliação do público. */
    public function removerAvaliacao(int $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_avaliacoes SET situacao='oculta' WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Contestação aceita — avaliação removida do público.');
        $this->redirect(url('/master/avaliacoes?filtro=contestadas'));
    }

    // ── Feedbacks do sistema (crítica / elogio / sugestão) ───────────────
    public function feedbacks(): void
    {
        $db     = DB::pdo();
        $valid  = ['novo', 'lido', 'arquivado'];
        $filtro = in_array($_GET['filtro'] ?? 'novo', $valid, true) ? $_GET['filtro'] : 'novo';

        $stmt = $db->prepare("
            SELECT f.*, e.nome_fantasia AS empresa_nome, u.nome AS usuario_nome
            FROM feedbacks f
            LEFT JOIN empresas e ON e.id = f.empresa_id
            LEFT JOIN usuarios u ON u.id = f.usuario_id
            WHERE f.status = ?
            ORDER BY f.criado_em DESC
        ");
        $stmt->execute([$filtro]);
        $feedbacks = $stmt->fetchAll();

        $novos      = (int)$db->query("SELECT COUNT(*) FROM feedbacks WHERE status='novo'")->fetchColumn();
        $lidos      = (int)$db->query("SELECT COUNT(*) FROM feedbacks WHERE status='lido'")->fetchColumn();
        $arquivados = (int)$db->query("SELECT COUNT(*) FROM feedbacks WHERE status='arquivado'")->fetchColumn();

        $this->view('master.feedbacks', compact('feedbacks', 'filtro', 'novos', 'lidos', 'arquivados'), 'master');
    }

    public function marcarFeedback(int $id): void
    {
        $status = $_POST['status'] ?? 'lido';
        if (!in_array($status, ['novo', 'lido', 'arquivado'], true)) $status = 'lido';
        DB::pdo()->prepare("UPDATE feedbacks SET status = ? WHERE id = ?")->execute([$status, $id]);
        $this->flash('success', 'Feedback atualizado.');
        $this->redirect(url('/master/feedbacks?filtro=' . $status));
    }

    public function aprovarAvaliacao(int $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_avaliacoes SET aprovado = 1 WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Avaliação aprovada e publicada.');
        $this->redirect(url('/master/avaliacoes'));
    }

    public function reprovarAvaliacao(int $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_avaliacoes SET aprovado = 2 WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Avaliação reprovada.');
        $this->redirect(url('/master/avaliacoes'));
    }

    public function excluirAvaliacao(int $id): void
    {
        DB::pdo()->prepare("DELETE FROM diretorio_avaliacoes WHERE id = ?")->execute([$id]);
        $this->flash('success', 'Avaliação excluída permanentemente.');
        $this->redirect(url('/master/avaliacoes'));
    }

    // ── Reivindicações de perfil do diretório ────────────────────────────
    public function reivindicacoes(): void
    {
        $db = DB::pdo();
        $db->exec("CREATE TABLE IF NOT EXISTS diretorio_reivindicacoes (
          id INT AUTO_INCREMENT PRIMARY KEY, empresa_id INT NOT NULL,
          nome VARCHAR(100) NOT NULL, email VARCHAR(100) NOT NULL, whatsapp VARCHAR(20) NULL,
          senha_hash VARCHAR(255) NOT NULL,
          status ENUM('pendente','aprovada','rejeitada') NOT NULL DEFAULT 'pendente',
          criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP, processado_em TIMESTAMP NULL,
          INDEX(empresa_id), INDEX(status)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $pendentes = $db->query("SELECT r.*, e.nome_fantasia, e.slug, e.cidade, e.uf FROM diretorio_reivindicacoes r JOIN empresas e ON e.id=r.empresa_id WHERE r.status='pendente' ORDER BY r.criado_em DESC")->fetchAll();
        $historico = $db->query("SELECT r.*, e.nome_fantasia FROM diretorio_reivindicacoes r JOIN empresas e ON e.id=r.empresa_id WHERE r.status<>'pendente' ORDER BY r.processado_em DESC LIMIT 40")->fetchAll();
        $this->view('master.reivindicacoes', ['titulo'=>'Reivindicações','pendentes'=>$pendentes,'historico'=>$historico], 'master');
    }

    public function aprovarReivindicacao(string $id): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT * FROM diretorio_reivindicacoes WHERE id=? AND status='pendente'");
        $stmt->execute([(int)$id]);
        $rec = $stmt->fetch();
        if (!$rec) { $this->flash('error','Pedido não encontrado.'); $this->redirect(url('/master/reivindicacoes')); }

        $chk = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email=?");
        $chk->execute([$rec['email']]);
        if ((int)$chk->fetchColumn() > 0) {
            $this->flash('error','Já existe um usuário com esse e-mail. Aprove manualmente/verifique.');
            $this->redirect(url('/master/reivindicacoes'));
        }

        $db->beginTransaction();
        $db->prepare("INSERT INTO usuarios (empresa_id,nome,email,senha,perfil,ativo) VALUES (?,?,?,?, 'admin', 1)")
           ->execute([(int)$rec['empresa_id'], $rec['nome'], $rec['email'], $rec['senha_hash']]);
        $db->prepare("UPDATE empresas SET reivindicada=1, trial_ate=DATE_ADD(CURDATE(), INTERVAL 7 DAY), plano='profissional',
                        email=COALESCE(NULLIF(email,''),?), whatsapp_publico=COALESCE(NULLIF(whatsapp_publico,''),?)
                      WHERE id=?")
           ->execute([$rec['email'], $rec['whatsapp'], (int)$rec['empresa_id']]);
        $db->prepare("UPDATE diretorio_reivindicacoes SET status='aprovada', processado_em=NOW() WHERE id=?")->execute([(int)$id]);
        $db->commit();

        $this->flash('success','Reivindicação aprovada! A empresa virou cliente e já pode fazer login.');
        $this->redirect(url('/master/reivindicacoes'));
    }

    public function rejeitarReivindicacao(string $id): void
    {
        DB::pdo()->prepare("UPDATE diretorio_reivindicacoes SET status='rejeitada', processado_em=NOW() WHERE id=? AND status='pendente'")->execute([(int)$id]);
        $this->flash('success','Pedido rejeitado.');
        $this->redirect(url('/master/reivindicacoes'));
    }

    // ── Leads / lista de espera (CRM de early adopters) ──────────────────
    public function leads(): void
    {
        $db = DB::pdo();
        $db->exec("CREATE TABLE IF NOT EXISTS lista_espera (
          id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(150) NOT NULL,
          origem VARCHAR(40) NOT NULL DEFAULT 'landing', convidado TINYINT NOT NULL DEFAULT 0,
          convidado_em TIMESTAMP NULL, criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          UNIQUE KEY uq_email (email)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $filtro = $this->get('origem', '');
        $where  = in_array($filtro, ['landing','reivindicacao','forum','diretorio'], true) ? "WHERE le.origem = ?" : "";
        $params = $where ? [$filtro] : [];

        $stmt = $db->prepare("
            SELECT le.*,
                   r.nome AS reiv_nome, r.whatsapp AS reiv_whats,
                   e.nome_fantasia AS empresa_nome, e.slug AS empresa_slug
            FROM lista_espera le
            LEFT JOIN diretorio_reivindicacoes r ON r.email = le.email
            LEFT JOIN empresas e ON e.id = r.empresa_id
            $where
            GROUP BY le.id
            ORDER BY le.convidado ASC, le.criado_em DESC
        ");
        $stmt->execute($params);

        $kpis = $db->query("
            SELECT
              COUNT(*) AS total,
              COALESCE(SUM(origem='landing'),0)        AS landing,
              COALESCE(SUM(origem='reivindicacao'),0)  AS reivindicacao,
              COALESCE(SUM(origem='diretorio'),0)      AS diretorio,
              COALESCE(SUM(origem='forum'),0)          AS forum,
              COALESCE(SUM(convidado=1),0)             AS convidados,
              COALESCE(SUM(convidado=0),0)             AS pendentes,
              COALESCE(SUM(criado_em >= DATE_SUB(NOW(), INTERVAL 7 DAY)),0) AS ultimos7
            FROM lista_espera
        ")->fetch();

        $this->view('master.leads', [
            'titulo' => 'Leads',
            'leads'  => $stmt->fetchAll(),
            'kpis'   => $kpis,
            'filtro' => $where ? $filtro : '',
        ], 'master');
    }

    public function convidarLead(string $id): void
    {
        $db  = DB::pdo();
        $st  = $db->prepare("SELECT convidado FROM lista_espera WHERE id=?");
        $st->execute([(int)$id]);
        $atual = $st->fetch();
        if ($atual === false) { $this->flash('error','Lead não encontrado.'); $this->redirect(url('/master/leads')); }

        $novo = ((int)$atual['convidado']) ? 0 : 1;
        $db->prepare("UPDATE lista_espera SET convidado=?, convidado_em=? WHERE id=?")
           ->execute([$novo, $novo ? date('Y-m-d H:i:s') : null, (int)$id]);
        $this->flash('success', $novo ? 'Lead marcado como convidado.' : 'Marcação de convite removida.');
        $this->redirect(url('/master/leads' . ($this->get('origem') ? '?origem=' . urlencode($this->get('origem')) : '')));
    }

    // ── Prospecção / CNPJs de dados abertos (leads frios pra convidar) ────
    public function prospeccao(): void
    {
        $db = DB::pdo();

        $status    = $this->get('status', '');
        $cnae      = $this->get('cnae', '');
        $uf        = $this->get('uf', '');
        $municipio = trim($this->get('municipio', ''));

        $where  = [];
        $params = [];
        if (in_array($status, ['novo', 'contatado', 'convertido', 'descartado'], true)) { $where[] = 'status = ?'; $params[] = $status; }
        if ($cnae !== '') { $where[] = 'cnae = ?'; $params[] = $cnae; }
        if ($uf !== '')   { $where[] = 'uf = ?'; $params[] = strtoupper($uf); }
        if ($municipio !== '') { $where[] = 'municipio LIKE ?'; $params[] = "%{$municipio}%"; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $db->prepare("
            SELECT * FROM leads_prospeccao
            $whereSql
            ORDER BY status = 'novo' DESC, criado_em DESC
            LIMIT 500
        ");
        $stmt->execute($params);

        $kpis = $db->query("
            SELECT
              COUNT(*) AS total,
              COALESCE(SUM(status='novo'),0)        AS novos,
              COALESCE(SUM(status='contatado'),0)   AS contatados,
              COALESCE(SUM(status='convertido'),0)  AS convertidos,
              COALESCE(SUM(status='descartado'),0)  AS descartados,
              COALESCE(SUM(email_convite_enviado_em IS NOT NULL),0) AS convites_enviados,
              COALESCE(SUM(email_aberto_em IS NOT NULL),0)          AS convites_abertos
            FROM leads_prospeccao
        ")->fetch();

        $cnaes = $db->query("SELECT cnae, COUNT(*) AS total FROM leads_prospeccao GROUP BY cnae ORDER BY total DESC")->fetchAll();

        // Disparo de e-mail: quantos já saíram hoje (pro limite diário) e quantos leads do
        // filtro atual são elegíveis (têm e-mail e nunca receberam o convite).
        $emailCfg = require BASE_PATH . '/config/prospeccao_email.php';
        $enviadosHoje = (int) $db->query(
            "SELECT COUNT(*) FROM leads_prospeccao WHERE email_convite_enviado_em >= CURDATE()"
        )->fetchColumn();
        $whereElegivel = $where;
        $whereElegivel[] = "email IS NOT NULL AND email <> ''";
        $whereElegivel[] = "email_convite_enviado_em IS NULL";
        $stmtEleg = $db->prepare("SELECT COUNT(*) FROM leads_prospeccao WHERE " . implode(' AND ', $whereElegivel));
        $stmtEleg->execute($params);
        $elegiveisNoFiltro = (int) $stmtEleg->fetchColumn();

        $this->view('master.prospeccao', [
            'titulo'  => 'Prospecção',
            'leads'   => $stmt->fetchAll(),
            'kpis'    => $kpis,
            'cnaes'   => $cnaes,
            'filtros' => ['status' => $status, 'cnae' => $cnae, 'uf' => $uf, 'municipio' => $municipio],
            'limiteDiario'      => \App\Services\Prospeccao\DisparoService::limiteDiarioAtual($emailCfg),
            'enviadosHoje'      => $enviadosHoje,
            'elegiveisNoFiltro' => $elegiveisNoFiltro,
        ], 'master');
    }

    /**
     * Dispara o convite por e-mail (EmailService::convitePropeccao()) pros leads do filtro
     * atual que têm e-mail e nunca receberam o convite — respeitando o limite diário de
     * config/prospeccao_email.php (soma o que já saiu hoje, nunca ultrapassa). Ver CLAUDE.md
     * "Disparo de e-mail de prospecção" pro racional do limite.
     */
    public function prospeccaoDisparar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/prospeccao')); }

        $emailCfg = require BASE_PATH . '/config/prospeccao_email.php';
        $limiteDiario = \App\Services\Prospeccao\DisparoService::limiteDiarioAtual($emailCfg);
        $restante = max(0, $limiteDiario - \App\Services\Prospeccao\DisparoService::enviadosHoje());

        $qs = $_GET;
        $redirecionar = fn() => $this->redirect(url('/master/prospeccao') . ($qs ? '?' . http_build_query($qs) : ''));

        if ($restante <= 0) {
            $this->flash('warning', "Limite diário de {$limiteDiario} e-mails já foi atingido hoje (inclui o que a rotina automática já mandou). Volte amanhã.");
            $redirecionar();
        }

        // Mesmos filtros da listagem — dispara só pro que está sendo visto na tela.
        $status    = $this->get('status', '');
        $cnae      = $this->get('cnae', '');
        $uf        = $this->get('uf', '');
        $municipio = trim($this->get('municipio', ''));

        $where  = [];
        $params = [];
        if (in_array($status, ['novo', 'contatado', 'convertido', 'descartado'], true)) { $where[] = 'status = ?'; $params[] = $status; }
        if ($cnae !== '') { $where[] = 'cnae = ?'; $params[] = $cnae; }
        if ($uf !== '')   { $where[] = 'uf = ?'; $params[] = strtoupper($uf); }
        if ($municipio !== '') { $where[] = 'municipio LIKE ?'; $params[] = "%{$municipio}%"; }

        $enviados = \App\Services\Prospeccao\DisparoService::dispararFiltrado($where, $params, $restante);

        if ($enviados > 0) {
            $this->flash('success', "{$enviados} convite(s) enviado(s). Restam " . ($restante - $enviados) . " no limite de hoje.");
        } else {
            $this->flash('warning', 'Nenhum e-mail foi enviado — confira se há lead elegível nesse filtro (já enviados, sem e-mail, ou filtro vazio) e a configuração de SMTP em Configurações → E-mail.');
        }
        $redirecionar();
    }

    /** Descadastro público (link no rodapé do e-mail de prospecção) — sem MasterMiddleware de propósito. */
    public function prospeccaoDescadastrar(string $token): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT id, razao_social FROM leads_prospeccao WHERE email_unsub_token = ?");
        $stmt->execute([$token]);
        $lead = $stmt->fetch();

        if ($lead) {
            $db->prepare("UPDATE leads_prospeccao SET status = 'descartado' WHERE id = ?")->execute([$lead['id']]);
        }

        $this->view('master.prospeccao_descadastrado', ['titulo' => 'Descadastro', 'encontrado' => (bool) $lead, 'noindex' => true], 'landing');
    }

    /**
     * Pixel de 1x1 embutido no convite de prospecção (ver EmailService::convitePropeccao() e
     * CLAUDE.md "Rastreamento de abertura do e-mail") — mesmo token do link de descadastro.
     * Sempre devolve a imagem, casando o token ou não, pra nunca dar erro visível num e-mail.
     */
    public function prospeccaoPixel(string $token): void
    {
        $db = DB::pdo();
        $db->prepare(
            "UPDATE leads_prospeccao SET email_aberto_em = NOW()
             WHERE email_unsub_token = ? AND email_aberto_em IS NULL"
        )->execute([$token]);

        // PNG transparente 1x1, o menor válido possível.
        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($png));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $png;
    }

    public function prospeccaoStatus(string $id): void
    {
        $novo = $this->post('status', '');
        if (!in_array($novo, ['novo', 'contatado', 'convertido', 'descartado'], true)) {
            $this->flash('error', 'Status inválido.');
            $this->redirect(url('/master/prospeccao'));
        }

        $db = DB::pdo();
        $db->prepare("UPDATE leads_prospeccao SET status = ?, contatado_em = IF(? = 'novo', NULL, COALESCE(contatado_em, NOW())) WHERE id = ?")
           ->execute([$novo, $novo, (int) $id]);

        $this->flash('success', 'Status atualizado.');
        $qs = $_GET;
        unset($qs['id']);
        $this->redirect(url('/master/prospeccao') . ($qs ? '?' . http_build_query($qs) : ''));
    }

    // ── E-mails do Diretório: convite "reivindique seu perfil" ────────────
    // Separado da Prospecção acima de propósito — outro público (empresa que já tem ficha
    // publicada, não lead frio sem cadastro), outra tabela (diretorio_leads_email, extraída de
    // `empresas` por scripts/extrair_emails_diretorio.php), outro limite diário. Mesmo conceito
    // de disparo/pixel/descadastro, só que via App\Services\Prospeccao\DisparoDiretorioService.
    public function diretorioEmails(): void
    {
        $db = DB::pdo();

        $uf     = $this->get('uf', '');
        $cidade = trim($this->get('cidade', ''));
        $busca  = trim($this->get('busca', ''));

        $where  = [];
        $params = [];
        if ($uf !== '')     { $where[] = 'uf = ?'; $params[] = strtoupper($uf); }
        if ($cidade !== '') { $where[] = 'cidade LIKE ?'; $params[] = "%{$cidade}%"; }
        if ($busca !== '')  { $where[] = '(nome_fantasia LIKE ? OR email LIKE ?)'; $params[] = "%{$busca}%"; $params[] = "%{$busca}%"; }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $db->prepare("
            SELECT * FROM diretorio_leads_email
            $whereSql
            ORDER BY email_convite_enviado_em IS NOT NULL, criado_em DESC
            LIMIT 500
        ");
        $stmt->execute($params);

        $kpis = $db->query("
            SELECT
              COUNT(*) AS total,
              COALESCE(SUM(reivindicada = 1),0)                       AS reivindicadas,
              COALESCE(SUM(descadastrado_em IS NOT NULL),0)           AS descadastrados,
              COALESCE(SUM(email_convite_enviado_em IS NOT NULL),0)   AS convites_enviados,
              COALESCE(SUM(email_aberto_em IS NOT NULL),0)            AS convites_abertos
            FROM diretorio_leads_email
        ")->fetch();

        $emailCfg = require BASE_PATH . '/config/diretorio_leads_email.php';
        $enviadosHoje = \App\Services\Prospeccao\DisparoDiretorioService::enviadosHoje();

        $whereElegivel = $where;
        $whereElegivel[] = "email IS NOT NULL AND email <> ''";
        $whereElegivel[] = "email_convite_enviado_em IS NULL";
        $whereElegivel[] = "descadastrado_em IS NULL";
        $whereElegivel[] = "reivindicada = 0";
        $stmtEleg = $db->prepare("SELECT COUNT(*) FROM diretorio_leads_email WHERE " . implode(' AND ', $whereElegivel));
        $stmtEleg->execute($params);
        $elegiveisNoFiltro = (int) $stmtEleg->fetchColumn();

        $this->view('master.diretorio_emails', [
            'titulo'  => 'E-mails do Diretório',
            'leads'   => $stmt->fetchAll(),
            'kpis'    => $kpis,
            'filtros' => ['uf' => $uf, 'cidade' => $cidade, 'busca' => $busca],
            'limiteDiario'      => \App\Services\Prospeccao\DisparoDiretorioService::limiteDiarioAtual($emailCfg),
            'enviadosHoje'      => $enviadosHoje,
            'elegiveisNoFiltro' => $elegiveisNoFiltro,
        ], 'master');
    }

    /** Dispara o convite (EmailService::conviteReivindicarDiretorio()) pros elegíveis do filtro
     *  atual, respeitando o limite diário de config/diretorio_leads_email.php. */
    public function diretorioEmailsDisparar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/diretorio-emails')); }

        $emailCfg = require BASE_PATH . '/config/diretorio_leads_email.php';
        $limiteDiario = \App\Services\Prospeccao\DisparoDiretorioService::limiteDiarioAtual($emailCfg);
        $restante = max(0, $limiteDiario - \App\Services\Prospeccao\DisparoDiretorioService::enviadosHoje());

        $qs = $_GET;
        $redirecionar = fn() => $this->redirect(url('/master/diretorio-emails') . ($qs ? '?' . http_build_query($qs) : ''));

        if ($restante <= 0) {
            $this->flash('warning', "Limite diário de {$limiteDiario} e-mails já foi atingido hoje. Volte amanhã.");
            $redirecionar();
        }

        $uf     = $this->get('uf', '');
        $cidade = trim($this->get('cidade', ''));
        $busca  = trim($this->get('busca', ''));

        $where  = [];
        $params = [];
        if ($uf !== '')     { $where[] = 'uf = ?'; $params[] = strtoupper($uf); }
        if ($cidade !== '') { $where[] = 'cidade LIKE ?'; $params[] = "%{$cidade}%"; }
        if ($busca !== '')  { $where[] = '(nome_fantasia LIKE ? OR email LIKE ?)'; $params[] = "%{$busca}%"; $params[] = "%{$busca}%"; }

        $enviados = \App\Services\Prospeccao\DisparoDiretorioService::dispararFiltrado($where, $params, $restante);

        if ($enviados > 0) {
            $this->flash('success', "{$enviados} convite(s) enviado(s). Restam " . ($restante - $enviados) . " no limite de hoje.");
        } else {
            $this->flash('warning', 'Nenhum e-mail foi enviado — confira se há empresa elegível nesse filtro (já enviado, sem e-mail, já reivindicada, descadastrada, ou filtro vazio) e a configuração de SMTP em Configurações → E-mail.');
        }
        $redirecionar();
    }

    /** Tela de disparo de "novidades do sistema" (e-mail + WhatsApp) pra base de clientes já
     *  cadastrados (reivindicada=1 — completo ou diretório de verdade, nunca lead sem conta).
     *  Lista COMPLETA nos dois canais (não só uma amostra) — o Master seleciona manualmente
     *  quem recebe (checkbox por empresa + "selecionar todas"), em vez do sistema decidir
     *  sozinho quem está "elegível"; mesmo assim nunca duplica envio pra quem já recebeu esta
     *  rodada (dedup continua valendo mesmo numa seleção manual). */
    public function novidadesSistema(): void
    {
        // listaWhatsapp()/contarJaEnviadosWhatsapp() dependem de `empresas_whatsapp_log`
        // (migration 070) — se ainda não foi aplicada no ambiente, cai pro canal WhatsApp
        // vazio em vez de derrubar a tela inteira com 500 (o canal e-mail, que não depende
        // dessa tabela, continua funcionando normalmente).
        try {
            $listaWhatsapp      = \App\Services\NovidadesSistemaService::listaWhatsapp();
            $jaEnviadosWhatsapp = \App\Services\NovidadesSistemaService::contarJaEnviadosWhatsapp();
            $erroWhatsapp       = null;
        } catch (\Throwable $ex) {
            $listaWhatsapp      = [];
            $jaEnviadosWhatsapp = 0;
            $erroWhatsapp       = 'Canal WhatsApp indisponível — provavelmente falta aplicar a migration 070_empresas_whatsapp_log.sql.';
        }

        $this->view('master.novidades_sistema', [
            'titulo'             => 'Novidades do Sistema',
            'empresasBase'       => \App\Services\NovidadesSistemaService::contarEmpresasBase(),
            'listaEmail'         => \App\Services\NovidadesSistemaService::listaEmail(),
            'listaWhatsapp'      => $listaWhatsapp,
            'jaEnviadosEmail'    => \App\Services\NovidadesSistemaService::contarJaEnviados(),
            'jaEnviadosWhatsapp' => $jaEnviadosWhatsapp,
            'erroWhatsapp'       => $erroWhatsapp,
            'previewWhatsapp'    => \App\Services\WhatsAppService::previewNovidadesSistema('Você'),
        ], 'master');
    }

    /** HTML cru (sem o layout do Master) pra abrir dentro do <iframe> da prévia de e-mail —
     *  o mesmo conteúdo que EmailService::novidadesSistema() mandaria, sem enviar nada. */
    public function novidadesSistemaPreviewEmail(): void
    {
        header('Content-Type: text/html; charset=UTF-8');
        echo \App\Services\EmailService::previewNovidadesSistema('Você');
        exit;
    }

    public function novidadesSistemaDisparar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/novidades-sistema')); }

        $ids = array_map('intval', (array) $this->post('ids', []));
        $r   = \App\Services\NovidadesSistemaService::dispararEmailSelecionados($ids);

        if ($r['enviados'] > 0) {
            $this->flash('success', "{$r['enviados']} e-mail(s) enviado(s) de {$r['total']} selecionado(s) elegível(is)." . ($r['falhas'] > 0 ? " {$r['falhas']} falha(s)." : ''));
        } else {
            $this->flash('warning', 'Nenhum e-mail foi enviado — confira a seleção (quem não tem e-mail ou já recebeu é ignorado) e a configuração de SMTP em Configurações → E-mail.');
        }
        $this->redirect(url('/master/novidades-sistema'));
    }

    public function novidadesSistemaDispararWhatsapp(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/novidades-sistema')); }

        $ids = array_map('intval', (array) $this->post('ids', []));
        $r   = \App\Services\NovidadesSistemaService::dispararWhatsappSelecionados($ids);

        if ($r['enviados'] > 0) {
            $this->flash('success', "{$r['enviados']} mensagem(ns) de WhatsApp enviada(s) de {$r['total']} selecionado(s) elegível(is)." . ($r['falhas'] > 0 ? " {$r['falhas']} falha(s)." : ''));
        } else {
            $this->flash('warning', 'Nenhuma mensagem foi enviada — confira a seleção (quem não tem telefone ou já recebeu é ignorado) e se o WhatsApp da plataforma está conectado.');
        }
        $this->redirect(url('/master/novidades-sistema'));
    }

    /** Descadastro público (link no rodapé do convite) — sem MasterMiddleware de propósito. */
    public function diretorioEmailsDescadastrar(string $token): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT id, nome_fantasia FROM diretorio_leads_email WHERE email_unsub_token = ?");
        $stmt->execute([$token]);
        $lead = $stmt->fetch();

        if ($lead) {
            $db->prepare("UPDATE diretorio_leads_email SET descadastrado_em = NOW() WHERE id = ?")->execute([$lead['id']]);
        }

        $this->view('master.diretorio_leads_descadastrado', ['titulo' => 'Descadastro', 'encontrado' => (bool) $lead, 'noindex' => true], 'landing');
    }

    /** Pixel de 1x1 embutido no convite (mesmo token do descadastro). Sempre devolve a imagem,
     *  casando o token ou não, pra nunca dar erro visível num e-mail. */
    public function diretorioEmailsPixel(string $token): void
    {
        $db = DB::pdo();
        $db->prepare(
            "UPDATE diretorio_leads_email SET email_aberto_em = NOW()
             WHERE email_unsub_token = ? AND email_aberto_em IS NULL"
        )->execute([$token]);

        $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=');

        header('Content-Type: image/png');
        header('Content-Length: ' . strlen($png));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $png;
    }

    /** Tela de disparo de convite via WhatsApp pro Diretório — duas frentes (reivindicar
     *  empresa já listada / cadastrar empresa nova), mesmo limite diário compartilhado
     *  (ver config/diretorio_whatsapp.php). */
    public function diretorioWhatsapp(): void
    {
        $db = DB::pdo();

        $uf     = $this->get('uf', '');
        $cidade = trim($this->get('cidade', ''));
        $busca  = trim($this->get('busca', ''));

        $whereReiv = ["ativo = 1", "listagem_publica = 1", "reivindicada = 0",
            "slug IS NOT NULL AND slug <> ''", "(COALESCE(whatsapp_publico,'') <> '' OR COALESCE(telefone,'') <> '')"];
        $paramsReiv = [];
        if ($uf !== '')     { $whereReiv[] = 'uf = ?'; $paramsReiv[] = strtoupper($uf); }
        if ($cidade !== '') { $whereReiv[] = 'cidade LIKE ?'; $paramsReiv[] = "%{$cidade}%"; }
        if ($busca !== '')  { $whereReiv[] = 'nome_fantasia LIKE ?'; $paramsReiv[] = "%{$busca}%"; }

        $whereCad = ["status <> 'descartado'", "COALESCE(telefone,'') <> ''"];
        $paramsCad = [];
        if ($uf !== '')     { $whereCad[] = 'uf = ?'; $paramsCad[] = strtoupper($uf); }
        if ($cidade !== '') { $whereCad[] = 'municipio LIKE ?'; $paramsCad[] = "%{$cidade}%"; }
        if ($busca !== '')  { $whereCad[] = '(nome_fantasia LIKE ? OR razao_social LIKE ?)'; $paramsCad[] = "%{$busca}%"; $paramsCad[] = "%{$busca}%"; }

        $stmtEligReiv = $db->prepare("SELECT COUNT(*) FROM empresas WHERE " . implode(' AND ', array_merge($whereReiv, ["whatsapp_convite_enviado_em IS NULL"])));
        $stmtEligReiv->execute($paramsReiv);
        $elegiveisReiv = (int) $stmtEligReiv->fetchColumn();

        $stmtEligCad = $db->prepare("SELECT COUNT(*) FROM leads_prospeccao WHERE " . implode(' AND ', array_merge($whereCad, ["whatsapp_convite_enviado_em IS NULL"])));
        $stmtEligCad->execute($paramsCad);
        $elegiveisCad = (int) $stmtEligCad->fetchColumn();

        $whatsCfg = require BASE_PATH . '/config/diretorio_whatsapp.php';
        $limiteDiario = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::limiteDiarioAtual($whatsCfg);
        $enviadosHoje = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::enviadosHoje();

        $totalReivEnviados = (int) $db->query("SELECT COUNT(*) FROM empresas WHERE whatsapp_convite_enviado_em IS NOT NULL")->fetchColumn();
        $totalCadEnviados  = (int) $db->query("SELECT COUNT(*) FROM leads_prospeccao WHERE whatsapp_convite_enviado_em IS NOT NULL")->fetchColumn();
        $totalManualEnviados = (int) $db->query("SELECT COUNT(*) FROM diretorio_convites_manuais WHERE enviado_em IS NOT NULL")->fetchColumn();
        $pendentesManual = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::contarPendentesManual();
        $listaManual     = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::listarPendentesManual();

        $this->view('master.diretorio_whatsapp', [
            'titulo'         => 'WhatsApp do Diretório',
            'filtros'        => ['uf' => $uf, 'cidade' => $cidade, 'busca' => $busca],
            'elegiveisReiv'  => $elegiveisReiv,
            'elegiveisCad'   => $elegiveisCad,
            'limiteDiario'   => $limiteDiario,
            'enviadosHoje'   => $enviadosHoje,
            'restanteHoje'   => max(0, $limiteDiario - $enviadosHoje),
            'totalReivEnviados'   => $totalReivEnviados,
            'totalCadEnviados'    => $totalCadEnviados,
            'totalManualEnviados' => $totalManualEnviados,
            'pendentesManual'     => $pendentesManual,
            'listaManual'         => $listaManual,
        ], 'master');
    }

    public function diretorioWhatsappDispararReivindicar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/diretorio-whatsapp')); }

        $qs = $_GET;
        $redirecionar = fn() => $this->redirect(url('/master/diretorio-whatsapp') . ($qs ? '?' . http_build_query($qs) : ''));

        $whatsCfg = require BASE_PATH . '/config/diretorio_whatsapp.php';
        $limiteDiario = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::limiteDiarioAtual($whatsCfg);
        $restante = max(0, $limiteDiario - \App\Services\Prospeccao\DisparoWhatsappDiretorioService::enviadosHoje());
        if ($restante <= 0) {
            $this->flash('warning', "Limite diário de {$limiteDiario} mensagens (somando os três tipos de convite) já foi atingido hoje. Volte amanhã.");
            $redirecionar();
        }

        $uf     = $this->get('uf', '');
        $cidade = trim($this->get('cidade', ''));
        $busca  = trim($this->get('busca', ''));
        $where  = [];
        $params = [];
        if ($uf !== '')     { $where[] = 'uf = ?'; $params[] = strtoupper($uf); }
        if ($cidade !== '') { $where[] = 'cidade LIKE ?'; $params[] = "%{$cidade}%"; }
        if ($busca !== '')  { $where[] = 'nome_fantasia LIKE ?'; $params[] = "%{$busca}%"; }

        $enviados = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::dispararReivindicar($where, $params, $restante);

        if ($enviados > 0) {
            $this->flash('success', "{$enviados} convite(s) de WhatsApp enviado(s). Restam " . ($restante - $enviados) . " no limite de hoje.");
        } else {
            $this->flash('warning', 'Nenhuma mensagem foi enviada — confira se há empresa elegível nesse filtro e se o WhatsApp da plataforma está conectado.');
        }
        $redirecionar();
    }

    public function diretorioWhatsappDispararCadastrar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/diretorio-whatsapp')); }

        $qs = $_GET;
        $redirecionar = fn() => $this->redirect(url('/master/diretorio-whatsapp') . ($qs ? '?' . http_build_query($qs) : ''));

        $whatsCfg = require BASE_PATH . '/config/diretorio_whatsapp.php';
        $limiteDiario = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::limiteDiarioAtual($whatsCfg);
        $restante = max(0, $limiteDiario - \App\Services\Prospeccao\DisparoWhatsappDiretorioService::enviadosHoje());
        if ($restante <= 0) {
            $this->flash('warning', "Limite diário de {$limiteDiario} mensagens (somando os três tipos de convite) já foi atingido hoje. Volte amanhã.");
            $redirecionar();
        }

        $uf     = $this->get('uf', '');
        $cidade = trim($this->get('cidade', ''));
        $busca  = trim($this->get('busca', ''));
        $where  = [];
        $params = [];
        if ($uf !== '')     { $where[] = 'uf = ?'; $params[] = strtoupper($uf); }
        if ($cidade !== '') { $where[] = 'municipio LIKE ?'; $params[] = "%{$cidade}%"; }
        if ($busca !== '')  { $where[] = '(nome_fantasia LIKE ? OR razao_social LIKE ?)'; $params[] = "%{$busca}%"; $params[] = "%{$busca}%"; }

        $enviados = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::dispararCadastrar($where, $params, $restante);

        if ($enviados > 0) {
            $this->flash('success', "{$enviados} convite(s) de WhatsApp enviado(s). Restam " . ($restante - $enviados) . " no limite de hoje.");
        } else {
            $this->flash('warning', 'Nenhuma mensagem foi enviada — confira se há lead elegível nesse filtro e se o WhatsApp da plataforma está conectado.');
        }
        $redirecionar();
    }

    /** Recebe o texto colado (uma empresa por linha) e grava o que for válido na lista
     *  manual — terceira fonte de convite, curada pelo próprio Master pra fugir do risco de
     *  número morto/errado que a base de CNPJ pode ter. */
    public function diretorioWhatsappAdicionarManual(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/diretorio-whatsapp')); }

        $texto = (string) $this->post('lista', '');
        $r = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::adicionarManual($texto);

        $msg = "{$r['adicionados']} empresa(s) adicionada(s) à lista.";
        if ($r['duplicados'] > 0) {
            $msg .= " {$r['duplicados']} já estava(m) na lista (ignorada(s)).";
        }
        if (!empty($r['invalidos'])) {
            $msg .= ' ' . count($r['invalidos']) . ' linha(s) não reconhecida(s) — confira o formato '
                  . '"Nome da empresa; WhatsApp" (e-mail é opcional, mas precisa ser o último campo): '
                  . implode(' | ', array_slice($r['invalidos'], 0, 5));
        }
        $this->flash($r['adicionados'] > 0 ? 'success' : 'warning', $msg);
        $this->redirect(url('/master/diretorio-whatsapp'));
    }

    public function diretorioWhatsappDispararManual(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/diretorio-whatsapp')); }

        $whatsCfg = require BASE_PATH . '/config/diretorio_whatsapp.php';
        $limiteDiario = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::limiteDiarioAtual($whatsCfg);
        $restante = max(0, $limiteDiario - \App\Services\Prospeccao\DisparoWhatsappDiretorioService::enviadosHoje());
        if ($restante <= 0) {
            $this->flash('warning', "Limite diário de {$limiteDiario} mensagens (somando os três tipos de convite) já foi atingido hoje. Volte amanhã.");
            $this->redirect(url('/master/diretorio-whatsapp'));
        }

        $enviados = \App\Services\Prospeccao\DisparoWhatsappDiretorioService::dispararManual($restante);

        if ($enviados > 0) {
            $this->flash('success', "{$enviados} convite(s) enviado(s) da lista manual (WhatsApp e/ou e-mail, quando cadastrado). Restam " . ($restante - $enviados) . " no limite de hoje.");
        } else {
            $this->flash('warning', 'Nenhuma mensagem foi enviada — confira se há empresa pendente na lista e se o WhatsApp da plataforma está conectado.');
        }
        $this->redirect(url('/master/diretorio-whatsapp'));
    }

    public function diretorioWhatsappExcluirManual($id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/diretorio-whatsapp')); }
        \App\Services\Prospeccao\DisparoWhatsappDiretorioService::excluirManual((int) $id);
        $this->flash('success', 'Removida da lista.');
        $this->redirect(url('/master/diretorio-whatsapp'));
    }

    // ── Base de Conhecimento (fonte do bot de suporte + central de ajuda) ──
    public function kb(): void
    {
        $arts = DB::pdo()->query("SELECT * FROM kb_artigos ORDER BY categoria, ordem, titulo")->fetchAll();
        $this->view('master.kb', ['titulo' => 'Base de Conhecimento', 'artigos' => $arts], 'master');
    }

    public function kbSalvar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/kb')); }
        $db    = DB::pdo();
        $tit   = trim($this->post('titulo', ''));
        $cat   = trim($this->post('categoria', '')) ?: 'Geral';
        $pc    = trim($this->post('palavras_chave', ''));
        $con   = trim($this->post('conteudo', ''));
        $ativo = $this->post('ativo') ? 1 : 0;
        if ($tit === '' || $con === '') { $this->flash('error', 'Título e conteúdo são obrigatórios.'); $this->redirect(url('/master/kb')); }

        if ($id === 'novo') {
            $slug = trim(substr(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT', $tit))), 0, 70), '-') ?: ('art-' . time());
            $ck = $db->prepare("SELECT COUNT(*) FROM kb_artigos WHERE slug=?"); $ck->execute([$slug]);
            if ((int) $ck->fetchColumn()) $slug .= '-' . substr((string) time(), -4);
            $db->prepare("INSERT INTO kb_artigos (slug,categoria,titulo,palavras_chave,conteudo,ativo) VALUES (?,?,?,?,?,?)")
               ->execute([$slug, $cat, $tit, $pc, $con, $ativo]);
        } else {
            $db->prepare("UPDATE kb_artigos SET categoria=?,titulo=?,palavras_chave=?,conteudo=?,ativo=? WHERE id=?")
               ->execute([$cat, $tit, $pc, $con, $ativo, (int) $id]);
        }
        $this->flash('success', 'Artigo salvo.');
        $this->redirect(url('/master/kb'));
    }

    public function kbExcluir(string $id): void
    {
        DB::pdo()->prepare("DELETE FROM kb_artigos WHERE id=?")->execute([(int) $id]);
        $this->flash('success', 'Artigo removido.');
        $this->redirect(url('/master/kb'));
    }

    // ── IA / Bot de Suporte — chave da API e teste de conexão ──
    public function iaConfig(): void
    {
        $this->view('master.ia', [
            'titulo'    => 'IA — Bot de Suporte',
            'apiKeySet' => \App\Services\IAService::apiKey() !== '',
            'modelo'    => \App\Services\IAService::modelo(),
            'ativo'     => \App\Services\IAService::ativo(),
        ], 'master');
    }

    public function iaSalvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/ia')); }
        $db  = DB::pdo();
        $set = function (string $ch, string $v) use ($db) {
            $db->prepare("INSERT INTO sistema_config (chave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)")->execute([$ch, $v]);
        };
        // só troca a chave se o campo veio preenchido (não apaga por engano ao salvar outras opções)
        $key = trim($this->post('api_key', ''));
        if ($key !== '' && strpos($key, '*') === false) $set('ia_api_key', $key);
        $set('ia_modelo', trim($this->post('modelo', '')) ?: 'claude-haiku-4-5-20251001');
        $set('ia_ativo', $this->post('ativo') ? '1' : '0');
        $this->flash('success', 'Configuração de IA salva.');
        $this->redirect(url('/master/ia'));
    }

    public function iaTestar(): void
    {
        $this->json(\App\Services\IAService::testar());
    }

    // ─────────── Consulta de IMEI ───────────
    public function imeiConfig(): void
    {
        $this->view('master.imei', [
            'titulo'    => 'Consulta de IMEI',
            'apiKeySet' => \App\Services\IMEIService::apiKey() !== '',
            'apiUrl'    => \App\Services\IMEIService::apiUrl(),
            'limiteMes' => \App\Services\IMEIService::limiteMes(),
            'flagAtivo' => \App\Services\IMEIService::flagAtivo(),
            'ativo'     => \App\Services\IMEIService::ativo(),
        ], 'master');
    }

    public function imeiSalvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/master/imei')); }
        $db  = DB::pdo();
        $set = function (string $ch, string $v) use ($db) {
            $db->prepare("INSERT INTO sistema_config (chave,valor) VALUES (?,?) ON DUPLICATE KEY UPDATE valor=VALUES(valor)")->execute([$ch, $v]);
        };
        $key = trim($this->post('api_key', ''));
        if ($key !== '' && strpos($key, '*') === false) $set('imei_api_key', $key);
        $set('imei_api_url', trim($this->post('api_url', '')));
        $set('imei_limite_mes', (string) max(0, (int) $this->post('limite_mes', 100)));
        $set('imei_ativo', $this->post('ativo') ? '1' : '0');
        $this->flash('success', 'Configuração de IMEI salva.');
        $this->redirect(url('/master/imei'));
    }

    public function imeiTestar(): void
    {
        $this->json(\App\Services\IMEIService::testar());
    }

    public function interesseNf(): void
    {
        $db = DB::pdo();
        $lista = $db->query("SELECT e.id, e.nome_fantasia, e.cidade, e.uf, e.plano_atual, ni.criado_em
                             FROM nf_interesse ni JOIN empresas e ON e.id = ni.empresa_id
                             ORDER BY ni.criado_em DESC")->fetchAll();
        $porCidade = $db->query("SELECT COALESCE(NULLIF(e.cidade,''),'(sem cidade)') AS cidade, e.uf, COUNT(*) AS qtd
                             FROM nf_interesse ni JOIN empresas e ON e.id = ni.empresa_id
                             GROUP BY e.cidade, e.uf ORDER BY qtd DESC")->fetchAll();
        $this->view('master.interesse_nf', ['titulo' => 'Interesse em Nota Fiscal', 'lista' => $lista, 'porCidade' => $porCidade], 'master');
    }

    /**
     * Mapa com a distribuição geográfica das empresas do FixaOS — Diretório (tipo_conta=
     * 'diretorio', toda ficha listada, reivindicada ou não — maioria importada de CNPJ,
     * raramente com endereço completo/geocodificado) e Sistema completo (tipo_conta='completo'
     * E reivindicada=1, quem de fato paga/testa o sistema — ver o `AND (tipo_conta='diretorio'
     * OR reivindicada=1)` na query abaixo, e o motivo dele no comentário logo ali), agregado
     * por CIDADE em vez de empresa individual — a maioria das ~28 mil fichas do Diretório não
     * tem latitude/longitude própria (nunca passaram pelo formulário de endereço), só cidade/UF
     * vindos da importação de CNPJ.
     *
     * A coordenada de cada cidade vem de `municipios_brasil` (migration 073 — referência
     * estática do IBGE, 5.571 municípios, nunca muda), casada em PHP (não em SQL — ver
     * comentário na query sobre o JOIN com TRIM()/COLLATE que travou a página em produção)
     * contra `empresas.cidade`/`uf` normalizados via `remover_acentos()` + minúsculo, pra
     * "Sao Paulo"/"São Paulo"/"são paulo" (variação de digitação comum na base de CNPJ) caírem
     * todos na mesma cidade.
     */
    public function mapaClientes(): void
    {
        $db = DB::pdo();

        // Agrega por cidade/UF CRUS primeiro, sem join nenhum — rápido mesmo com dezenas de
        // milhares de empresas (GROUP BY direto em colunas indexadas). A versão anterior fazia
        // o match contra municipios_brasil dentro do próprio SQL, com TRIM()/COLLATE nos dois
        // lados do JOIN — qualquer função envolvendo a coluna impede o uso de índice, virando
        // uma varredura cruzada pesada (empresas × municípios) que travou a página em produção
        // (achado real, reportado pelo usuário). Resolver a cidade contra o catálogo de
        // municípios em PHP, com o catálogo inteiro (5.571 linhas, pouco) carregado uma vez só
        // numa tabela hash, é muito mais barato — o lado caro (empresas) nunca é escaneado mais
        // de uma vez.
        // `tipo_conta='completo'` sozinho NÃO distingue cliente de verdade de ficha importada:
        // a coluna tem DEFAULT 'completo', e o import original de CNPJ pro Diretório (~28 mil
        // fichas, ver backfill_status_laudo_tecnico.php) marcou `reivindicada=0` certinho, mas
        // esqueceu de marcar `tipo_conta='diretorio'` — ficou no default. Sem o `reivindicada=1`
        // abaixo, "Sistema completo" contaria essas dezenas de milhares de fichas nunca
        // reivindicadas como se fossem clientes pagantes/trial de verdade (bug real, visto em
        // produção: 17.964 "Sistema completo" contra as 68 "Empresas ativas" do Dashboard, que
        // já usa esse mesmo filtro). Diretório não exige reivindicada=1 de propósito — o
        // usuário quer ver o alcance geográfico de TODA ficha listada, reivindicada ou não.
        $rows = $db->query(
            "SELECT cidade, uf, tipo_conta, COUNT(*) AS total
               FROM empresas
              WHERE ativo = 1 AND COALESCE(cidade,'') <> '' AND COALESCE(uf,'') <> ''
                AND (tipo_conta = 'diretorio' OR reivindicada = 1)
              GROUP BY cidade, uf, tipo_conta"
        )->fetchAll();

        // Terceiro grupo, SUBCONJUNTO de "completo": quem de fato paga um plano, não só criou
        // conta (trial incluso). Mesmo critério já usado em perfil_diretorio_completo() —
        // licenca_ate >= hoje; trial sozinho (trial_ate) não conta, só licença paga de verdade.
        $rowsPagantes = $db->query(
            "SELECT cidade, uf, COUNT(*) AS total
               FROM empresas
              WHERE ativo = 1 AND COALESCE(cidade,'') <> '' AND COALESCE(uf,'') <> ''
                AND tipo_conta = 'completo' AND reivindicada = 1 AND licenca_ate >= CURDATE()
              GROUP BY cidade, uf"
        )->fetchAll();

        // Catálogo de municípios indexado por "nome normalizado|UF" — mesma normalização
        // (remover_acentos + minúsculo) já usada em empresa_nome_indica_servico(), pra
        // "Sao Paulo"/"São Paulo"/"são paulo" caírem no mesmo município.
        $municipios = [];
        foreach ($db->query("SELECT nome, uf, latitude, longitude FROM municipios_brasil")->fetchAll() as $m) {
            $chave = remover_acentos(mb_strtolower(trim($m['nome']))) . '|' . $m['uf'];
            $municipios[$chave] = $m;
        }

        // Agrupado por (grupo, município) — necessário porque mais de uma grafia crua de
        // empresas (ex.: "Sao Paulo" e "São Paulo") pode resolver pro MESMO município, e as
        // duas precisam somar no mesmo ponto do mapa, não virar dois pontos sobrepostos.
        $agregado = ['diretorio' => [], 'completo' => [], 'pagante' => []];
        $semCoordenada = 0;
        foreach ($rows as $r) {
            $chave = remover_acentos(mb_strtolower(trim($r['cidade']))) . '|' . strtoupper(trim($r['uf']));
            $m = $municipios[$chave] ?? null;
            if (!$m) {
                $semCoordenada += (int) $r['total'];
                continue;
            }
            $tipo = $r['tipo_conta'] === 'diretorio' ? 'diretorio' : 'completo';
            if (!isset($agregado[$tipo][$chave])) {
                $agregado[$tipo][$chave] = [
                    'cidade' => $m['nome'],
                    'uf'     => $m['uf'],
                    'lat'    => (float) $m['latitude'],
                    'lng'    => (float) $m['longitude'],
                    'total'  => 0,
                ];
            }
            $agregado[$tipo][$chave]['total'] += (int) $r['total'];
        }
        // "Pagante" nunca soma em semCoordenada — já é subconjunto de "completo", contado ali.
        foreach ($rowsPagantes as $r) {
            $chave = remover_acentos(mb_strtolower(trim($r['cidade']))) . '|' . strtoupper(trim($r['uf']));
            $m = $municipios[$chave] ?? null;
            if (!$m) {
                continue;
            }
            if (!isset($agregado['pagante'][$chave])) {
                $agregado['pagante'][$chave] = [
                    'cidade' => $m['nome'],
                    'uf'     => $m['uf'],
                    'lat'    => (float) $m['latitude'],
                    'lng'    => (float) $m['longitude'],
                    'total'  => 0,
                ];
            }
            $agregado['pagante'][$chave]['total'] += (int) $r['total'];
        }

        $porTipo = [
            'diretorio' => array_values($agregado['diretorio']),
            'completo'  => array_values($agregado['completo']),
            'pagante'   => array_values($agregado['pagante']),
        ];

        $totalDiretorio = array_sum(array_column($porTipo['diretorio'], 'total'));
        $totalCompleto  = array_sum(array_column($porTipo['completo'], 'total'));
        $totalPagante   = array_sum(array_column($porTipo['pagante'], 'total'));

        $this->view('master.mapa_clientes', [
            'titulo'          => 'Mapa de Clientes',
            'pontosDiretorio' => $porTipo['diretorio'],
            'pontosCompleto'  => $porTipo['completo'],
            'pontosPagantes'  => $porTipo['pagante'],
            'totalDiretorio'  => $totalDiretorio,
            'totalCompleto'   => $totalCompleto,
            'totalPagantes'   => $totalPagante,
            'semCoordenada'   => $semCoordenada,
        ], 'master');
    }

    /**
     * Custo de uso de IA (Etapa 4 do pedido de cobrança do Fixa) — mês a mês, por modelo e por
     * usuário, a partir de `ia_uso_log` (App\Services\IAUsoService::registrar(), chamado por
     * IAService::perguntar()/VisionService::lerConta() sempre que a Anthropic responde com
     * `usage` real). Não é exclusivo do scanner de contas do Fixa — qualquer chamada logada
     * (etiqueta de equipamento, placa, bot de suporte) aparece aqui também, por módulo
     * (`contexto`), já que o objetivo da tela é custo de IA do sistema inteiro, não só do Fixa.
     */
    public function custoIA(): void
    {
        $mes = (string) $this->get('mes', date('Y-m'));
        if (!preg_match('/^\d{4}-\d{2}$/', $mes)) { $mes = date('Y-m'); }

        $resumo = \App\Services\IAUsoService::resumoMes($mes);

        $this->view('master.custo_ia', [
            'titulo' => 'Custo de IA',
            'mes'    => $mes,
            'resumo' => $resumo,
        ], 'master');
    }
}
