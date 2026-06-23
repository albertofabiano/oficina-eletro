<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Models\Financeiro;

class FinanceiroController extends Controller
{
    private Financeiro $model;

    public function __construct() { $this->model = new Financeiro(); }

    public function index(): void
    {
        $page    = (int) $this->get('page', 1);
        $inicio  = $this->get('data_inicio', date('Y-m-01'));
        $fim     = $this->get('data_fim', date('Y-m-t'));
        $filtros = [
            'tipo'        => $this->get('tipo'),
            'status'      => $this->get('status'),
            'fonte'       => $this->get('fonte'),
            'data_inicio' => $inicio,
            'data_fim'    => $fim,
        ];

        $this->view('financeiro.index', [
            'titulo'       => 'Financeiro',
            'paginator'    => $this->model->listarUnificado($page, 25, $filtros),
            'resumo'       => $this->model->saldoUnificado($inicio, $fim),
            'porFonte'     => $this->model->receitaPorFonte($inicio, $fim),
            'fluxo'        => $this->model->fluxoCaixa($inicio, $fim),
            'osPendentes'  => $this->model->osPendentes(),
            'filtros'      => $filtros,
            'contas'       => $this->model->contas(),
            'categorias'   => $this->model->categorias(),
            'vencendo'     => $this->model->vencendoHoje(),
        ]);
    }

    public function pagarOs(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $eid  = $this->empresaId();
        $db   = \App\Core\DB::pdo();
        $forma = $this->post('forma_pagamento', 'dinheiro');

        $stmt = $db->prepare(
            "SELECT os.*, c.nome AS cliente_nome FROM ordens_servico os
             JOIN clientes c ON c.id = os.cliente_id
             WHERE os.id = ? AND os.empresa_id = ?"
        );
        $stmt->execute([(int)$id, $eid]);
        $os = $stmt->fetch();
        if (!$os) { $this->json(['error' => 'OS não encontrada'], 404); }

        // Atualizar situação de pagamento na OS
        $db->prepare("UPDATE ordens_servico SET situacao_pagamento='pago', valor_pago=valor_total, forma_pagamento_fechamento=? WHERE id=? AND empresa_id=?")
           ->execute([$forma, (int)$id, $eid]);

        // Criar lançamento financeiro
        $stmtConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id=? AND ativo=1 ORDER BY id LIMIT 1");
        $stmtConta->execute([$eid]);
        $contaId = $stmtConta->fetchColumn();

        if ($contaId) {
            $db->prepare(
                "INSERT INTO fin_lancamentos (empresa_id, conta_id, os_id, cliente_id, usuario_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento)
                 VALUES (?,?,?,?,?,'receita',?,?,CURDATE(),CURDATE(),'pago',?)"
            )->execute([
                $eid, $contaId, (int)$id, $os['cliente_id'], $this->usuarioId(),
                '' . $os['numero'] . ' — ' . $os['cliente_nome'],
                $os['valor_total'], $forma,
            ]);
        }

        $this->json(['success' => true]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $valor = (float) str_replace(',', '.', $this->post('valor', 0));
        $data = [
            'conta_id'       => (int) $this->post('conta_id'),
            'categoria_id'   => $this->post('categoria_id') ?: null,
            'cliente_id'     => $this->post('cliente_id') ?: null,
            'tipo'           => $this->post('tipo', 'receita'),
            'descricao'      => trim($this->post('descricao')),
            'valor'          => $valor,
            'data_vencimento'=> $this->post('data_vencimento'),
            'status'         => $this->post('status', 'pendente'),
            'forma_pagamento'=> $this->post('forma_pagamento') ?: null,
            'observacoes'    => $this->post('observacoes'),
            'usuario_id'     => $this->usuarioId(),
        ];

        if ($data['status'] === 'pago') {
            $data['data_pagamento'] = date('Y-m-d');
        }

        $this->model->insert($data);
        $this->flash('success', 'Lançamento registrado!');
        $this->redirect(url('/financeiro'));
    }

    public function pagar(string $id): void
    {
        $this->model->update((int) $id, [
            'status'         => 'pago',
            'data_pagamento' => date('Y-m-d'),
            'forma_pagamento'=> $this->post('forma_pagamento', 'dinheiro'),
        ]);
        $this->json(['success' => true]);
    }

    public function excluir(string $id): void
    {
        $this->model->delete((int) $id);
        $this->flash('success', 'Lançamento removido.');
        $this->redirect(url('/financeiro'));
    }
}
