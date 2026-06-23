<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class OsStatusController extends Controller
{
    public function index(): void
    {
        $eid  = $this->empresaId();
        $stmt = DB::pdo()->prepare(
            "SELECT s.*, COUNT(os.id) AS total_os
             FROM os_status s
             LEFT JOIN ordens_servico os ON os.status_id = s.id AND os.empresa_id = s.empresa_id
             WHERE s.empresa_id = ?
             GROUP BY s.id
             ORDER BY s.ordem"
        );
        $stmt->execute([$eid]);

        $this->view('os_status.index', [
            'titulo' => 'Status de OS',
            'lista'  => $stmt->fetchAll(),
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $id   = (int) $this->post('id');
        $nome = trim($this->post('nome', ''));
        $cor  = $this->post('cor', '#6c757d');
        $tipo = $this->post('tipo', 'aberta');

        if (!$nome) { $this->flash('error', 'Nome obrigatório.'); $this->redirectBack(); }

        // Ordem: último + 1 se novo
        if ($id) {
            $db->prepare(
                "UPDATE os_status SET nome=?, cor=?, tipo=? WHERE id=? AND empresa_id=?"
            )->execute([$nome, $cor, $tipo, $id, $eid]);
            $this->flash('success', 'Status atualizado!');
        } else {
            $stmtOrdem = $db->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM os_status WHERE empresa_id=?");
            $stmtOrdem->execute([$eid]);
            $ordem = (int) $stmtOrdem->fetchColumn();

            $db->prepare(
                "INSERT INTO os_status (empresa_id, nome, cor, ordem, tipo) VALUES (?,?,?,?,?)"
            )->execute([$eid, $nome, $cor, $ordem, $tipo]);
            $this->flash('success', 'Status criado!');
        }

        $this->redirect(url('/os/status'));
    }

    public function reordenar(): void
    {
        $ids = $this->post('ids', []);
        if (!is_array($ids)) { $this->json(['error' => 'inválido'], 400); }

        $eid  = $this->empresaId();
        $stmt = DB::pdo()->prepare(
            "UPDATE os_status SET ordem=? WHERE id=? AND empresa_id=?"
        );
        foreach ($ids as $ordem => $id) {
            $stmt->execute([$ordem + 1, (int) $id, $eid]);
        }
        $this->json(['success' => true]);
    }

    public function excluir(string $id): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Bloquear exclusão se houver OS vinculadas
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM ordens_servico WHERE status_id=? AND empresa_id=?"
        );
        $stmt->execute([(int) $id, $eid]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->flash('error', 'Não é possível excluir: existem OS vinculadas a este status.');
            $this->redirect(url('/os/status'));
        }

        $db->prepare("DELETE FROM os_status WHERE id=? AND empresa_id=?")
           ->execute([(int) $id, $eid]);

        $this->flash('success', 'Status removido.');
        $this->redirect(url('/os/status'));
    }
}
