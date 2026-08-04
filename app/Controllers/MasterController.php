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
            // A FK ordens_servico.equipamento_id -> equipamentos NÃO é cascade,
            // então apagamos as OS primeiro (elas cascateiam serviços/peças/histórico).
            // Depois o DELETE da empresa cascateia o resto (clientes, equipamentos, status...).
            $db->prepare("DELETE FROM ordens_servico WHERE empresa_id=?")->execute([(int)$id]);
            $db->prepare("DELETE FROM empresas WHERE id=?")->execute([(int)$id]);
        } catch (\Throwable $e) {
            $this->flash('error', 'Não foi possível excluir a empresa: ' . $e->getMessage());
            $this->redirect(url('/master/empresas/' . $id));
        }

        $this->flash('success', 'Empresa "' . $nome . '" e todos os seus dados foram excluídos.');
        $this->redirect(url('/master/empresas'));
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
        $posicao  = $tipo === 'banner' ? (int)$this->post('posicao_banner', 0) : null;
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

        $status = $this->get('status', '');
        $cnae   = $this->get('cnae', '');
        $uf     = $this->get('uf', '');

        $where  = [];
        $params = [];
        if (in_array($status, ['novo', 'contatado', 'convertido', 'descartado'], true)) { $where[] = 'status = ?'; $params[] = $status; }
        if ($cnae !== '') { $where[] = 'cnae = ?'; $params[] = $cnae; }
        if ($uf !== '')   { $where[] = 'uf = ?'; $params[] = strtoupper($uf); }
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
              COALESCE(SUM(status='descartado'),0)  AS descartados
            FROM leads_prospeccao
        ")->fetch();

        $cnaes = $db->query("SELECT cnae, COUNT(*) AS total FROM leads_prospeccao GROUP BY cnae ORDER BY total DESC")->fetchAll();

        $this->view('master.prospeccao', [
            'titulo'  => 'Prospecção',
            'leads'   => $stmt->fetchAll(),
            'kpis'    => $kpis,
            'cnaes'   => $cnaes,
            'filtros' => ['status' => $status, 'cnae' => $cnae, 'uf' => $uf],
        ], 'master');
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
}
