<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class TecnicoController extends Controller
{
    public function index(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmt = $db->prepare(
            "SELECT u.*,
             COUNT(DISTINCT os.id)                                    AS total_os,
             COUNT(DISTINCT CASE WHEN s.tipo IN ('concluida','entregue') THEN os.id END) AS os_concluidas,
             COALESCE(SUM(CASE WHEN s.tipo IN ('concluida','entregue') THEN os.valor_total END),0) AS total_faturado,
             MAX(os.criado_em)                                        AS ultima_os
             FROM usuarios u
             LEFT JOIN ordens_servico os ON os.tecnico_id = u.id AND os.empresa_id = u.empresa_id
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE u.empresa_id = ? AND u.perfil = 'tecnico'
             GROUP BY u.id
             ORDER BY u.nome"
        );
        $stmt->execute([$eid]);

        $this->view('tecnicos.index', [
            'titulo'   => 'Técnicos',
            'tecnicos' => $stmt->fetchAll(),
        ], $this->layoutAtual());
    }

    public function criar(): void
    {
        $this->view('tecnicos.form', [
            'titulo'  => 'Novo Técnico',
            'tecnico' => [],
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid   = $this->empresaId();
        $nome  = trim($this->post('nome', ''));
        $email = trim($this->post('email', ''));
        $tel   = trim($this->post('telefone', ''));

        if (!$nome)  { $this->flash('error', 'Nome é obrigatório.'); $this->redirectBack(); }
        if (!$email) { $this->flash('error', 'E-mail é obrigatório.'); $this->redirectBack(); }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->flash('error', 'E-mail inválido.'); $this->redirectBack(); }
        if (!$tel)   { $this->flash('error', 'Telefone é obrigatório.'); $this->redirectBack(); }

        $db = DB::pdo();
        $stmtCheck = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND empresa_id = ?");
        $stmtCheck->execute([$email, $eid]);
        if ((int) $stmtCheck->fetchColumn() > 0) {
            $this->flash('error', 'Este e-mail já está cadastrado.');
            $this->redirectBack();
        }

        // Gera senha automática (técnico não faz login — sem senha exposta)
        $senhaAuto = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 12]);

        $db->prepare(
            "INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, telefone, ativo, atende_os)
             VALUES (?, ?, ?, ?, 'tecnico', ?, ?, ?)"
        )->execute([
            $eid,
            $nome,
            $email,
            $senhaAuto,
            $tel,
            (int) $this->post('ativo', 1),
            $this->post('atende_os') ? 1 : 0,
        ]);

        $this->flash('success', 'Técnico cadastrado com sucesso!');
        $this->redirect(url('/tecnicos'));
    }

    public function ver(string $id): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmt = $db->prepare("SELECT * FROM usuarios WHERE id = ? AND empresa_id = ? AND perfil = 'tecnico'");
        $stmt->execute([(int) $id, $eid]);
        $tecnico = $stmt->fetch();
        if (!$tecnico) { $this->flash('error', 'Técnico não encontrado.'); $this->redirect(url('/tecnicos')); }

        // OS do técnico
        $stmtOS = $db->prepare(
            "SELECT os.*, s.nome AS status_nome, s.cor AS status_cor, s.tipo AS status_tipo,
             c.nome AS cliente_nome, eq.tipo AS equip_tipo, eq.marca AS equip_marca, eq.modelo AS equip_modelo
             FROM ordens_servico os
             LEFT JOIN os_status s ON s.id = os.status_id
             LEFT JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             WHERE os.tecnico_id = ? AND os.empresa_id = ?
             ORDER BY os.criado_em DESC LIMIT 30"
        );
        $stmtOS->execute([(int) $id, $eid]);

        // Métricas
        $stmtMet = $db->prepare(
            "SELECT
             COUNT(*) AS total_os,
             SUM(CASE WHEN s.tipo IN ('concluida','entregue') THEN 1 ELSE 0 END) AS concluidas,
             SUM(CASE WHEN s.tipo IN ('aberta','em_andamento','aguardando') THEN 1 ELSE 0 END) AS em_aberto,
             COALESCE(SUM(CASE WHEN s.tipo IN ('concluida','entregue') THEN os.valor_total END),0) AS faturado,
             COALESCE(AVG(CASE WHEN s.tipo IN ('concluida','entregue') THEN os.valor_total END),0) AS ticket_medio
             FROM ordens_servico os
             LEFT JOIN os_status s ON s.id = os.status_id
             WHERE os.tecnico_id = ? AND os.empresa_id = ?"
        );
        $stmtMet->execute([(int) $id, $eid]);

        // Top serviços do técnico
        $stmtSvc = $db->prepare(
            "SELECT oss.descricao, COUNT(*) AS vezes, SUM(oss.valor_total) AS receita
             FROM os_servicos oss
             JOIN ordens_servico os ON os.id = oss.os_id
             WHERE oss.tecnico_id = ? AND oss.empresa_id = ?
             GROUP BY oss.descricao ORDER BY vezes DESC LIMIT 5"
        );
        $stmtSvc->execute([(int) $id, $eid]);

        $this->view('tecnicos.show', [
            'titulo'   => 'Técnico: ' . $tecnico['nome'],
            'tecnico'  => $tecnico,
            'ordens'   => $stmtOS->fetchAll(),
            'metricas' => $stmtMet->fetch() ?: [],
            'top'      => $stmtSvc->fetchAll(),
        ]);
    }

    public function editar(string $id): void
    {
        $eid  = $this->empresaId();
        $stmt = DB::pdo()->prepare("SELECT * FROM usuarios WHERE id = ? AND empresa_id = ? AND perfil = 'tecnico'");
        $stmt->execute([(int) $id, $eid]);
        $tecnico = $stmt->fetch();
        if (!$tecnico) { $this->flash('error', 'Técnico não encontrado.'); $this->redirect(url('/tecnicos')); }

        $this->view('tecnicos.form', [
            'titulo'  => 'Editar Técnico',
            'tecnico' => $tecnico,
        ]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid  = $this->empresaId();
        $db   = DB::pdo();
        $nome = trim($this->post('nome', ''));
        $tel  = trim($this->post('telefone', ''));

        if (!$nome) { $this->flash('error', 'Nome é obrigatório.'); $this->redirectBack(); }
        if (!$tel)  { $this->flash('error', 'Telefone é obrigatório.'); $this->redirectBack(); }

        $data = [
            'nome'      => $nome,
            'telefone'  => $tel,
            'ativo'     => (int) $this->post('ativo', 1),
            'atende_os' => $this->post('atende_os') ? 1 : 0,
        ];

        $senha = $this->post('senha', '');
        if ($senha) {
            if (strlen($senha) < 6) {
                $this->flash('error', 'A senha deve ter pelo menos 6 caracteres.');
                $this->redirectBack();
            }
            $data['senha'] = password_hash($senha, PASSWORD_BCRYPT, ['cost' => 12]);
        }

        $set = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($data)));
        $db->prepare("UPDATE usuarios SET $set WHERE id = ? AND empresa_id = ? AND perfil = 'tecnico'")
           ->execute([...array_values($data), (int) $id, $eid]);

        $this->flash('success', 'Técnico atualizado!');
        $this->redirect(url('/tecnicos/' . $id));
    }

    public function excluir(string $id): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Verificar se tem OS vinculadas
        $stmt = $db->prepare("SELECT COUNT(*) FROM ordens_servico WHERE tecnico_id = ? AND empresa_id = ?");
        $stmt->execute([(int) $id, $eid]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->flash('error', 'Não é possível excluir: técnico possui OS vinculadas.');
            $this->redirectPreservandoPainel(url('/tecnicos'));
        }

        $db->prepare("DELETE FROM usuarios WHERE id = ? AND empresa_id = ? AND perfil = 'tecnico'")
           ->execute([(int) $id, $eid]);

        $this->flash('success', 'Técnico removido.');
        $this->redirectPreservandoPainel(url('/tecnicos'));
    }

    /** Autopreenche nome/telefone quando o e-mail digitado já pertence a alguém da empresa. */
    public function apiBuscarPorEmail(): void
    {
        $email = trim($this->get('email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->json(['encontrado' => false]); return; }

        $st = DB::pdo()->prepare("SELECT nome, telefone FROM usuarios WHERE empresa_id = ? AND email = ? LIMIT 1");
        $st->execute([$this->empresaId(), $email]);
        $u = $st->fetch();

        if (!$u) { $this->json(['encontrado' => false]); return; }
        $this->json(['encontrado' => true, 'nome' => $u['nome'], 'telefone' => $u['telefone']]);
    }

    // ── API inline (CRUD de técnico dentro do formulário de OS) ───────────
    public function apiListar(): void
    {
        $st = DB::pdo()->prepare("SELECT id, nome, email, telefone FROM usuarios WHERE empresa_id = ? AND perfil = 'tecnico' AND ativo = 1 ORDER BY nome");
        $st->execute([$this->empresaId()]);
        $this->json(['tecnicos' => $st->fetchAll()]);
    }

    public function apiSalvar(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Sessão expirada. Recarregue a página.'], 403); return; }
        $eid   = $this->empresaId();
        $db    = DB::pdo();
        $nome  = trim($this->post('nome', ''));
        $email = trim($this->post('email', ''));
        $tel   = trim($this->post('telefone', ''));
        if ($nome === '')  { $this->json(['error' => 'Informe o nome do técnico.'], 422); return; }
        if ($email === '') { $this->json(['error' => 'Informe o e-mail do técnico.'], 422); return; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->json(['error' => 'E-mail inválido.'], 422); return; }
        if ($tel === '')   { $this->json(['error' => 'Informe o telefone do técnico.'], 422); return; }

        $c = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ?");
        $c->execute([$email]);
        if ((int) $c->fetchColumn() > 0) { $this->json(['error' => 'Este e-mail já está em uso.'], 422); return; }

        $senha = password_hash(bin2hex(random_bytes(16)), PASSWORD_BCRYPT, ['cost' => 12]);
        $db->prepare("INSERT INTO usuarios (empresa_id, nome, email, senha, perfil, telefone, ativo) VALUES (?,?,?,?,'tecnico',?,1)")
           ->execute([$eid, $nome, $email, $senha, $tel]);

        $this->json(['ok' => true, 'tecnico' => ['id' => (int) $db->lastInsertId(), 'nome' => $nome, 'email' => $email, 'telefone' => $tel]]);
    }

    public function apiAtualizar(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Sessão expirada. Recarregue a página.'], 403); return; }
        $eid   = $this->empresaId();
        $db    = DB::pdo();
        $nome  = trim($this->post('nome', ''));
        $email = trim($this->post('email', ''));
        $tel   = trim($this->post('telefone', ''));
        if ($nome === '')  { $this->json(['error' => 'Informe o nome.'], 422); return; }
        if ($email === '') { $this->json(['error' => 'Informe o e-mail do técnico.'], 422); return; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $this->json(['error' => 'E-mail inválido.'], 422); return; }
        if ($tel === '')   { $this->json(['error' => 'Informe o telefone do técnico.'], 422); return; }

        $c = $db->prepare("SELECT COUNT(*) FROM usuarios WHERE email = ? AND id != ?");
        $c->execute([$email, (int) $id]);
        if ((int) $c->fetchColumn() > 0) { $this->json(['error' => 'Este e-mail já está em uso.'], 422); return; }

        $db->prepare("UPDATE usuarios SET nome = ?, email = ?, telefone = ? WHERE id = ? AND empresa_id = ? AND perfil = 'tecnico'")
           ->execute([$nome, $email, $tel, (int) $id, $eid]);
        $this->json(['ok' => true]);
    }

    public function apiExcluir(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Sessão expirada. Recarregue a página.'], 403); return; }
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $c = $db->prepare("SELECT COUNT(*) FROM ordens_servico WHERE tecnico_id = ? AND empresa_id = ?");
        $c->execute([(int) $id, $eid]);
        if ((int) $c->fetchColumn() > 0) {
            // Tem histórico de OS: desativa (some da lista, mas preserva as OS antigas)
            $db->prepare("UPDATE usuarios SET ativo = 0 WHERE id = ? AND empresa_id = ? AND perfil = 'tecnico'")->execute([(int) $id, $eid]);
            $this->json(['ok' => true, 'modo' => 'desativado']);
        } else {
            $db->prepare("DELETE FROM usuarios WHERE id = ? AND empresa_id = ? AND perfil = 'tecnico'")->execute([(int) $id, $eid]);
            $this->json(['ok' => true, 'modo' => 'excluido']);
        }
    }
}
