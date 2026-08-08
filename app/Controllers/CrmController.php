<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class CrmController extends Controller
{
    public function pipeline(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmtE = $db->prepare("SELECT * FROM crm_estagios WHERE empresa_id = ? ORDER BY ordem");
        $stmtE->execute([$eid]);
        $estagios = $stmtE->fetchAll();

        $oportunidades = [];
        foreach ($estagios as $e) {
            $stmt = $db->prepare(
                "SELECT op.*, c.nome AS cliente_nome FROM crm_oportunidades op
                 LEFT JOIN clientes c ON c.id = op.cliente_id
                 WHERE op.estagio_id = ? AND op.empresa_id = ? ORDER BY op.valor_estimado DESC"
            );
            $stmt->execute([$e['id'], $eid]);
            $oportunidades[$e['id']] = $stmt->fetchAll();
        }

        $this->view('crm.pipeline', [
            'titulo'        => 'Pipeline CRM',
            'estagios'      => $estagios,
            'oportunidades' => $oportunidades,
        ]);
    }

    /** Cria uma oportunidade nova, associada a um cliente já cadastrado. */
    public function criarOportunidade(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid       = $this->empresaId();
        $db        = DB::pdo();
        $clienteId = (int) $this->post('cliente_id', 0);
        $titulo    = trim($this->post('titulo', ''));

        if (!$clienteId || !$titulo) {
            $this->flash('error', 'Selecione um cliente e informe um título.');
            $this->redirectBack();
        }

        $chkC = $db->prepare("SELECT 1 FROM clientes WHERE id = ? AND empresa_id = ?");
        $chkC->execute([$clienteId, $eid]);
        if (!$chkC->fetchColumn()) { $this->flash('error', 'Cliente inválido.'); $this->redirectBack(); }

        $estagioId = (int) $this->post('estagio_id', 0);
        if (!$estagioId) {
            $stmtP = $db->prepare("SELECT id FROM crm_estagios WHERE empresa_id = ? ORDER BY ordem LIMIT 1");
            $stmtP->execute([$eid]);
            $estagioId = (int) $stmtP->fetchColumn();
        }
        $chkE = $db->prepare("SELECT 1 FROM crm_estagios WHERE id = ? AND empresa_id = ?");
        $chkE->execute([$estagioId, $eid]);
        if (!$estagioId || !$chkE->fetchColumn()) {
            $this->flash('error', 'Nenhum estágio de CRM cadastrado para esta empresa.');
            $this->redirectBack();
        }

        $valor     = moeda_float($this->post('valor_estimado', 0));
        $prob      = max(0, min(100, (int) $this->post('probabilidade', 50)));
        $dataPrev  = $this->post('data_fechamento_prevista') ?: null;
        $descricao = trim($this->post('descricao', ''));

        $db->prepare(
            "INSERT INTO crm_oportunidades
                (empresa_id, cliente_id, estagio_id, responsavel_id, titulo, valor_estimado, probabilidade, data_fechamento_prevista, descricao)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $eid, $clienteId, $estagioId, $this->usuarioId(), $titulo,
            $valor ?: null, $prob, $dataPrev, $descricao ?: null,
        ]);

        $this->flash('success', 'Oportunidade criada!');
        $this->redirect(url('/crm'));
    }

    /** Atualiza os dados de uma oportunidade (título, valor, estágio, etc.) — não troca o cliente. */
    public function atualizarOportunidade(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();
        $chk = $db->prepare("SELECT 1 FROM crm_oportunidades WHERE id = ? AND empresa_id = ?");
        $chk->execute([(int) $id, $eid]);
        if (!$chk->fetchColumn()) { $this->flash('error', 'Oportunidade não encontrada.'); $this->redirectBack(); }

        $titulo = trim($this->post('titulo', ''));
        if (!$titulo) { $this->flash('error', 'Informe um título.'); $this->redirectBack(); }

        $estagioId = (int) $this->post('estagio_id', 0);
        $chkE = $db->prepare("SELECT 1 FROM crm_estagios WHERE id = ? AND empresa_id = ?");
        $chkE->execute([$estagioId, $eid]);
        if (!$estagioId || !$chkE->fetchColumn()) { $this->flash('error', 'Estágio inválido.'); $this->redirectBack(); }

        $valor     = moeda_float($this->post('valor_estimado', 0));
        $prob      = max(0, min(100, (int) $this->post('probabilidade', 50)));
        $dataPrev  = $this->post('data_fechamento_prevista') ?: null;
        $descricao = trim($this->post('descricao', ''));
        $motivo    = trim($this->post('motivo_perda', ''));

        $db->prepare(
            "UPDATE crm_oportunidades
             SET titulo=?, estagio_id=?, valor_estimado=?, probabilidade=?, data_fechamento_prevista=?, descricao=?, motivo_perda=?
             WHERE id=? AND empresa_id=?"
        )->execute([$titulo, $estagioId, $valor ?: null, $prob, $dataPrev, $descricao ?: null, $motivo ?: null, (int) $id, $eid]);

        $this->flash('success', 'Oportunidade atualizada!');
        $this->redirect(url('/crm'));
    }

    /** Move o card entre colunas (drag-and-drop) — via AJAX, devolve JSON. */
    public function moverOportunidade(string $id): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido — recarregue a página.'], 400); }

        $eid       = $this->empresaId();
        $db        = DB::pdo();
        $estagioId = (int) $this->post('estagio_id', 0);

        $chkE = $db->prepare("SELECT 1 FROM crm_estagios WHERE id = ? AND empresa_id = ?");
        $chkE->execute([$estagioId, $eid]);
        if (!$estagioId || !$chkE->fetchColumn()) { $this->json(['ok' => false, 'erro' => 'Estágio inválido.'], 400); }

        $motivo = trim((string) $this->post('motivo_perda', ''));

        $stmt = $db->prepare("UPDATE crm_oportunidades SET estagio_id = ?, motivo_perda = ? WHERE id = ? AND empresa_id = ?");
        $ok   = $stmt->execute([$estagioId, $motivo ?: null, (int) $id, $eid]);

        $this->json(['ok' => (bool) $ok]);
    }

    public function excluirOportunidade(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        DB::pdo()->prepare("DELETE FROM crm_oportunidades WHERE id = ? AND empresa_id = ?")
            ->execute([(int) $id, $this->empresaId()]);

        $this->flash('success', 'Oportunidade excluída.');
        $this->redirect(url('/crm'));
    }

    public function registrarContato(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        DB::pdo()->prepare(
            "INSERT INTO crm_contatos (empresa_id, cliente_id, oportunidade_id, usuario_id, tipo, direcao, assunto, descricao, proximo_contato)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $eid,
            $this->post('cliente_id'),
            $this->post('oportunidade_id') ?: null,
            $this->usuarioId(),
            $this->post('tipo', 'ligacao'),
            $this->post('direcao', 'saida'),
            $this->post('assunto'),
            $this->post('descricao'),
            $this->post('proximo_contato') ?: null,
        ]);

        $this->flash('success', 'Contato registrado!');
        $this->redirectBack();
    }
}
