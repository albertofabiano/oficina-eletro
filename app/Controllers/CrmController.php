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
