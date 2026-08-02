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
        ], $this->layoutAtual());
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $id       = (int) $this->post('id');
        $nome     = trim($this->post('nome', ''));
        $cor      = $this->post('cor', '#6c757d');
        $corFonte = $this->post('cor_fonte', '#ffffff');
        $tipo     = $this->post('tipo', 'aberta');
        $permiteFechar = $this->post('permite_fechar') ? 1 : 0;
        $semValor      = $this->post('sem_valor') ? 1 : 0;

        // Status nativos (bloqueado=1): nome/tipo/ordem são fixos (protegem o esqueleto),
        // mas cor + os 2 comportamentos (permite_fechar, sem_valor) podem ser ajustados por empresa.
        if ($id) {
            $stmtTipo = $db->prepare("SELECT bloqueado FROM os_status WHERE id=? AND empresa_id=?");
            $stmtTipo->execute([$id, $eid]);
            $statusAtual = $stmtTipo->fetch();
            if ($statusAtual && (int) $statusAtual['bloqueado'] === 1) {
                $db->prepare("UPDATE os_status SET cor=?, cor_fonte=?, permite_fechar=?, sem_valor=? WHERE id=? AND empresa_id=?")
                   ->execute([$cor, $corFonte, $permiteFechar, $semValor, $id, $eid]);
                $this->flash('success', 'Status atualizado.');
                $this->redirectPreservandoPainel(url('/os/status'));
            }
        }

        if (!$nome) { $this->flash('error', 'Nome obrigatório.'); $this->redirectBack(); }

        if ($id) {
            $db->prepare(
                "UPDATE os_status SET nome=?, cor=?, cor_fonte=?, tipo=?, permite_fechar=?, sem_valor=? WHERE id=? AND empresa_id=?"
            )->execute([$nome, $cor, $corFonte, $tipo, $permiteFechar, $semValor, $id, $eid]);
            $this->flash('success', 'Status atualizado!');
        } else {
            $stmtOrdem = $db->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM os_status WHERE empresa_id=?");
            $stmtOrdem->execute([$eid]);
            $ordem = (int) $stmtOrdem->fetchColumn();

            $db->prepare(
                "INSERT INTO os_status (empresa_id, nome, cor, cor_fonte, ordem, tipo, permite_fechar, sem_valor) VALUES (?,?,?,?,?,?,?,?)"
            )->execute([$eid, $nome, $cor, $corFonte, $ordem, $tipo, $permiteFechar, $semValor]);
            $this->flash('success', 'Status criado!');
        }

        $this->redirectPreservandoPainel(url('/os/status'));
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

        // Status nativos (bloqueado=1) não podem ser excluídos — são o esqueleto do sistema.
        $stmtTipo = $db->prepare("SELECT bloqueado FROM os_status WHERE id=? AND empresa_id=?");
        $stmtTipo->execute([(int)$id, $eid]);
        $statusAtual = $stmtTipo->fetch();
        if ($statusAtual && (int) $statusAtual['bloqueado'] === 1) {
            $this->flash('error', 'Este é um status nativo do sistema e não pode ser excluído (só a cor pode ser alterada).');
            $this->redirectPreservandoPainel(url('/os/status'));
        }

        // Bloquear exclusão se houver OS vinculadas
        $stmt = $db->prepare(
            "SELECT COUNT(*) FROM ordens_servico WHERE status_id=? AND empresa_id=?"
        );
        $stmt->execute([(int) $id, $eid]);
        if ((int) $stmt->fetchColumn() > 0) {
            $this->flash('error', 'Não é possível excluir: existem OS vinculadas a este status.');
            $this->redirectPreservandoPainel(url('/os/status'));
        }

        $db->prepare("DELETE FROM os_status WHERE id=? AND empresa_id=?")
           ->execute([(int) $id, $eid]);

        $this->flash('success', 'Status removido.');
        $this->redirectPreservandoPainel(url('/os/status'));
    }
}
