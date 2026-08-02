<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Cliente;

class AgendaController extends Controller
{
    public function index(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $mes  = (int) $this->get('mes', date('m'));
        $ano  = (int) $this->get('ano', date('Y'));

        $stmt = $db->prepare(
            "SELECT a.*, c.nome AS cliente_nome, u.nome AS usuario_nome
             FROM agenda a
             LEFT JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.empresa_id = ? AND MONTH(a.data_inicio) = ? AND YEAR(a.data_inicio) = ?
             ORDER BY a.data_inicio"
        );
        $stmt->execute([$eid, $mes, $ano]);
        $eventos = $stmt->fetchAll();

        $stmtU = $db->prepare("SELECT id, nome FROM usuarios WHERE empresa_id = ? AND ativo = 1 ORDER BY nome");
        $stmtU->execute([$eid]);

        $this->view('agenda.index', [
            'titulo'   => 'Agenda',
            'eventos'  => $eventos,
            'mes'      => $mes,
            'ano'      => $ano,
            'usuarios' => $stmtU->fetchAll(),
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid       = $this->empresaId();
        $eventoId  = (int)$this->post('evento_id', 0);

        if ($eventoId) {
            // Edição
            DB::pdo()->prepare(
                "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?
                 WHERE id=? AND empresa_id=?"
            )->execute([
                $this->post('titulo'),
                $this->post('descricao'),
                $this->post('tipo', 'outro'),
                $this->post('usuario_id') ?: $this->usuarioId(),
                $this->post('data_inicio'),
                $this->post('data_fim') ?: null,
                $this->post('cor', '#0d6efd'),
                $eventoId,
                $eid,
            ]);
            $this->flash('success', 'Evento atualizado!');
        } else {
            // Criação
            DB::pdo()->prepare(
                "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado')"
            )->execute([
                $eid,
                $this->post('titulo'),
                $this->post('descricao'),
                $this->post('tipo', 'outro'),
                $this->post('cliente_id') ?: null,
                $this->post('os_id') ?: null,
                $this->post('usuario_id') ?: $this->usuarioId(),
                $this->post('data_inicio'),
                $this->post('data_fim') ?: null,
                $this->post('dia_todo', 0),
                $this->post('cor', '#0d6efd'),
            ]);
            $this->flash('success', 'Evento agendado!');
        }

        $this->redirect(url('/agenda'));
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        DB::pdo()->prepare(
            "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?
             WHERE id=? AND empresa_id=?"
        )->execute([
            $this->post('titulo'),
            $this->post('descricao'),
            $this->post('tipo', 'outro'),
            $this->post('usuario_id') ?: $this->usuarioId(),
            $this->post('data_inicio'),
            $this->post('data_fim') ?: null,
            $this->post('cor', '#0d6efd'),
            (int)$id,
            $eid,
        ]);

        $this->flash('success', 'Evento atualizado!');
        $this->redirect(url('/agenda'));
    }

    public function excluir(string $id): void
    {
        DB::pdo()->prepare("DELETE FROM agenda WHERE id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->empresaId()]);
        $this->flash('success', 'Evento removido.');
        $this->redirect(url('/agenda'));
    }

    public function eventos(): void
    {
        $eid  = $this->empresaId();
        $ini  = $this->get('start', date('Y-m-01'));
        $fim  = $this->get('end',   date('Y-m-t'));
        $stmt = DB::pdo()->prepare(
            "SELECT a.id, a.titulo AS title, a.data_inicio AS start, a.data_fim AS end,
             a.cor AS color, a.dia_todo AS allDay, a.status, c.nome AS cliente_nome
             FROM agenda a LEFT JOIN clientes c ON c.id = a.cliente_id
             WHERE a.empresa_id = ? AND a.data_inicio BETWEEN ? AND ?
             ORDER BY a.data_inicio"
        );
        $stmt->execute([$eid, $ini, $fim]);
        $this->json($stmt->fetchAll());
    }
}
