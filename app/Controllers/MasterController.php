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
              (SELECT COUNT(*) FROM empresas WHERE ativo=1)              AS total_empresas,
              (SELECT COUNT(*) FROM usuarios WHERE ativo=1)              AS total_usuarios,
              (SELECT COUNT(*) FROM ordens_servico)                       AS total_os,
              (SELECT COUNT(*) FROM clientes)                             AS total_clientes,
              (SELECT COUNT(*) FROM ordens_servico WHERE DATE(criado_em)=CURDATE()) AS os_hoje,
              (SELECT COUNT(*) FROM empresas WHERE trial_ate >= CURDATE() AND ativo=1) AS em_trial
        ")->fetch();

        $empresas = $db->query("
            SELECT e.*,
              (SELECT COUNT(*) FROM usuarios u WHERE u.empresa_id = e.id AND u.ativo=1) AS qtd_usuarios,
              (SELECT COUNT(*) FROM ordens_servico os WHERE os.empresa_id = e.id) AS qtd_os,
              (SELECT COUNT(*) FROM clientes c WHERE c.empresa_id = e.id) AS qtd_clientes,
              (SELECT MAX(os.criado_em) FROM ordens_servico os WHERE os.empresa_id = e.id) AS ultima_os
            FROM empresas e
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
        $where   = $busca ? "WHERE e.razao_social LIKE ? OR e.email LIKE ? OR e.nome_fantasia LIKE ?" : "";
        $params  = $busca ? ["%$busca%","%$busca%","%$busca%"] : [];

        $stmt = $db->prepare("
            SELECT e.*,
              (SELECT COUNT(*) FROM usuarios u WHERE u.empresa_id=e.id) AS qtd_usuarios,
              (SELECT COUNT(*) FROM ordens_servico os WHERE os.empresa_id=e.id) AS qtd_os
            FROM empresas e $where ORDER BY e.criado_em DESC
        ");
        $stmt->execute($params);

        $this->view('master.empresas', [
            'titulo'   => 'Empresas',
            'empresas' => $stmt->fetchAll(),
            'busca'    => $busca,
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
}
