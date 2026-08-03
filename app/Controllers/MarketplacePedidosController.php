<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;

class MarketplacePedidosController extends Controller
{
    // Listagem pública de pedidos
    public function index(): void
    {
        $db     = DB::pdo();
        $busca  = trim($_GET['busca'] ?? '');
        $tipo   = trim($_GET['tipo'] ?? '');
        $pag    = max(1, (int)($_GET['pag'] ?? 1));
        $limit  = 12;
        $offset = ($pag - 1) * $limit;

        $where  = ["p.status = 'aberto'"];
        $params = [];

        if ($busca) {
            $where[]  = "(p.titulo LIKE ? OR p.descricao LIKE ? OR p.marca LIKE ? OR p.modelo LIKE ?)";
            $params[] = "%$busca%"; $params[] = "%$busca%";
            $params[] = "%$busca%"; $params[] = "%$busca%";
        }
        if ($tipo) { $where[] = "p.tipo LIKE ?"; $params[] = "%$tipo%"; }

        $whereStr = implode(' AND ', $where);

        $stmtCount = $db->prepare("SELECT COUNT(*) FROM marketplace_pedidos p WHERE $whereStr");
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();

        $stmt = $db->prepare("
            SELECT p.*, e.nome_fantasia AS empresa_nome, e.cidade, e.uf,
                   (SELECT COUNT(*) FROM marketplace_pedido_respostas r WHERE r.pedido_id = p.id) AS total_respostas
            FROM marketplace_pedidos p
            JOIN empresas e ON e.id = p.empresa_id
            WHERE $whereStr
            ORDER BY p.urgencia DESC, p.criado_em DESC
            LIMIT $limit OFFSET $offset
        ");
        $stmt->execute($params);
        $pedidos = $stmt->fetchAll();

        $totalPags = ceil($total / $limit);

        $cfg = require BASE_PATH . '/config/app.php';
        $baseUrl = rtrim($cfg['url'], '/');

        $this->view('marketplace.pedidos_publico', compact('pedidos','busca','tipo','total','pag','totalPags','baseUrl'), 'landing');
    }

    // Ver pedido individual
    public function ver(string $id): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("
            SELECT p.*, e.nome_fantasia AS empresa_nome, e.cidade, e.uf, e.logo AS empresa_logo
            FROM marketplace_pedidos p
            JOIN empresas e ON e.id = p.empresa_id
            WHERE p.id = ?
        ");
        $stmt->execute([$id]);
        $pedido = $stmt->fetch();

        if (!$pedido) { http_response_code(404); echo 'Pedido não encontrado'; exit; }

        $stmtR = $db->prepare("
            SELECT r.*, e.nome_fantasia AS empresa_nome, e.cidade, e.uf
            FROM marketplace_pedido_respostas r
            JOIN empresas e ON e.id = r.empresa_id
            WHERE r.pedido_id = ?
            ORDER BY r.criado_em ASC
        ");
        $stmtR->execute([$id]);
        $respostas = $stmtR->fetchAll();

        $cfg = require BASE_PATH . '/config/app.php';
        $baseUrl = rtrim($cfg['url'], '/');

        $this->view('marketplace.pedido_ver', compact('pedido','respostas','baseUrl'), 'landing');
    }

    // Meus pedidos (autenticado)
    public function meus(): void
    {
        $db  = DB::pdo();
        $eid = $this->empresaId();

        $stmt = $db->prepare("
            SELECT p.*,
                   (SELECT COUNT(*) FROM marketplace_pedido_respostas r WHERE r.pedido_id = p.id) AS total_respostas
            FROM marketplace_pedidos p
            WHERE p.empresa_id = ?
            ORDER BY p.criado_em DESC
        ");
        $stmt->execute([$eid]);
        $pedidos = $stmt->fetchAll();

        // Respostas recebidas nos meus pedidos
        $stmtR = $db->prepare("
            SELECT r.*, p.titulo AS pedido_titulo, p.id AS pedido_id, e.nome_fantasia AS empresa_nome
            FROM marketplace_pedido_respostas r
            JOIN marketplace_pedidos p ON p.id = r.pedido_id
            JOIN empresas e ON e.id = r.empresa_id
            WHERE p.empresa_id = ?
            ORDER BY r.criado_em DESC
            LIMIT 20
        ");
        $stmtR->execute([$eid]);
        $respostas = $stmtR->fetchAll();

        $forcarTemaClaro = true;
        $this->view('marketplace.meus_pedidos', compact('pedidos','respostas','forcarTemaClaro'), 'main');
    }

    // Criar pedido
    public function criar(): void
    {
        if (!csrf_verify()) { $this->flash('error','Token inválido.'); $this->redirect(url('/marketplace/pedidos')); }

        $db  = DB::pdo();
        $eid = $this->empresaId();

        $titulo = trim($this->post('titulo', ''));
        if (!$titulo) { $this->flash('error','Informe o título do pedido.'); $this->redirect(url('/marketplace/pedidos')); }

        $db->prepare("INSERT INTO marketplace_pedidos (empresa_id, titulo, descricao, tipo, marca, modelo, urgencia, contato_whatsapp) VALUES (?,?,?,?,?,?,?,?)")
           ->execute([
               $eid,
               $titulo,
               trim($this->post('descricao', '')),
               trim($this->post('tipo', '')),
               trim($this->post('marca', '')),
               trim($this->post('modelo', '')),
               $this->post('urgencia', 'normal'),
               only_numbers($this->post('contato_whatsapp', '')),
           ]);

        $this->flash('success', 'Pedido publicado! A comunidade será notificada.');
        $this->redirect(url('/marketplace/pedidos'));
    }

    // Responder pedido
    public function responder(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error','Token inválido.'); $this->redirect(url('/pecas/pedidos/'.$id)); }

        $db  = DB::pdo();
        $eid = $this->empresaId();

        $msg = trim($this->post('mensagem', ''));
        if (!$msg) { $this->flash('error','Escreva uma mensagem.'); $this->redirect(url('/pecas/pedidos/'.$id)); }

        // Não responder o próprio pedido
        $proprio = $db->prepare("SELECT empresa_id FROM marketplace_pedidos WHERE id = ?");
        $proprio->execute([$id]);
        if ((int)$proprio->fetchColumn() === $eid) {
            $this->flash('error','Você não pode responder seu próprio pedido.');
            $this->redirect(url('/pecas/pedidos/'.$id));
        }

        $db->prepare("INSERT INTO marketplace_pedido_respostas (pedido_id, empresa_id, mensagem, whatsapp) VALUES (?,?,?,?)")
           ->execute([$id, $eid, $msg, only_numbers($this->post('whatsapp', ''))]);

        $this->flash('success', 'Resposta enviada! O solicitante verá sua oferta.');
        $this->redirect(url('/pecas/pedidos/'.$id));
    }

    // Marcar como atendido
    public function atender(string $id): void
    {
        DB::pdo()->prepare("UPDATE marketplace_pedidos SET status='atendido' WHERE id=? AND empresa_id=?")
                 ->execute([$id, $this->empresaId()]);
        $this->flash('success', 'Pedido marcado como atendido!');
        $this->redirect(url('/marketplace/pedidos'));
    }

    // Cancelar pedido
    public function cancelar(string $id): void
    {
        DB::pdo()->prepare("UPDATE marketplace_pedidos SET status='cancelado' WHERE id=? AND empresa_id=?")
                 ->execute([$id, $this->empresaId()]);
        $this->flash('success', 'Pedido cancelado.');
        $this->redirect(url('/marketplace/pedidos'));
    }
}
