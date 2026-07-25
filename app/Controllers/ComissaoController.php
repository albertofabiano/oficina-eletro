<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Comissao;
use App\Models\Usuario;

class ComissaoController extends Controller
{
    private Comissao $model;

    public function __construct()
    {
        $this->model = new Comissao();
    }

    /** Percentual padrão da empresa (usado quando o técnico não tem um % próprio configurado). */
    private function percentualPadrao(int $eid): float
    {
        $st = DB::pdo()->prepare("SELECT valor FROM configuracoes WHERE empresa_id = ? AND chave = 'comissao_tecnico_percentual'");
        $st->execute([$eid]);
        $v = $st->fetchColumn();
        return $v !== false && $v !== '' ? (float) $v : 20.0;
    }

    public function index(): void
    {
        $eid = $this->empresaId();
        $filtros = [
            'tecnico_id'  => $this->get('tecnico_id'),
            'pago'        => $this->get('pago', ''),
            'data_inicio' => $this->get('data_inicio'),
            'data_fim'    => $this->get('data_fim'),
        ];

        $st = DB::pdo()->prepare("SELECT id, nome FROM usuarios WHERE empresa_id = ? AND (perfil = 'tecnico' OR atende_os = 1) AND ativo = 1 ORDER BY nome");
        $st->execute([$eid]);

        $this->view('comissoes.index', [
            'titulo'    => 'Comissões de Técnicos',
            'comissoes' => $this->model->listar($filtros),
            'totais'    => $this->model->totais($filtros),
            'tecnicos'  => $st->fetchAll(),
            'filtros'   => $filtros,
        ], $this->layoutAtual());
    }

    public function criar(): void
    {
        $eid = $this->empresaId();
        $st = DB::pdo()->prepare("SELECT id, nome, comissao_percentual FROM usuarios WHERE empresa_id = ? AND (perfil = 'tecnico' OR atende_os = 1) AND ativo = 1 ORDER BY nome");
        $st->execute([$eid]);

        $this->view('comissoes.form', [
            'titulo'            => 'Nova Comissão',
            'tecnicos'          => $st->fetchAll(),
            'percentualPadrao'  => $this->percentualPadrao($eid),
        ], $this->layoutAtual());
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid       = $this->empresaId();
        $tecnicoId = (int) $this->post('tecnico_id');
        $osId      = (int) $this->post('os_id') ?: null;
        $valorBase = moeda_float($this->post('valor_base', '0'));
        $percentual = moeda_float($this->post('percentual', '0'));

        if (!$tecnicoId) { $this->flash('error', 'Selecione o técnico.'); $this->redirectBack(); }
        if ($valorBase <= 0) { $this->flash('error', 'Informe o valor do serviço.'); $this->redirectBack(); }
        if ($percentual <= 0 || $percentual > 100) { $this->flash('error', 'Percentual de comissão inválido.'); $this->redirectBack(); }

        $db = DB::pdo();
        $stT = $db->prepare("SELECT id FROM usuarios WHERE id = ? AND empresa_id = ?");
        $stT->execute([$tecnicoId, $eid]);
        if (!$stT->fetchColumn()) { $this->flash('error', 'Técnico inválido.'); $this->redirectBack(); }

        if ($osId) {
            $stO = $db->prepare("SELECT id FROM ordens_servico WHERE id = ? AND empresa_id = ?");
            $stO->execute([$osId, $eid]);
            if (!$stO->fetchColumn()) { $this->flash('error', 'OS inválida.'); $this->redirectBack(); }
        }

        $valorComissao = round($valorBase * $percentual / 100, 2);

        $db->prepare(
            "INSERT INTO fin_comissoes (empresa_id, tecnico_id, os_id, percentual, valor_base, valor_comissao, pago)
             VALUES (?, ?, ?, ?, ?, ?, 0)"
        )->execute([$eid, $tecnicoId, $osId, $percentual, $valorBase, $valorComissao]);

        $this->flash('success', 'Comissão registrada!');
        $this->redirect(url('/comissoes'));
    }

    /** Busca OS onde o técnico atuou (responsável da OS ou fez algum serviço nela), pra popular o autocomplete. */
    public function buscarOs(): void
    {
        $eid       = $this->empresaId();
        $tecnicoId = (int) $this->get('tecnico_id');
        $q         = trim($this->get('q', ''));
        if (!$tecnicoId) { $this->json(['os' => []]); return; }

        $sql = "SELECT DISTINCT os.id, os.numero, c.nome AS cliente_nome, os.valor_total
                FROM ordens_servico os
                JOIN clientes c ON c.id = os.cliente_id
                LEFT JOIN os_servicos oss ON oss.os_id = os.id AND oss.tecnico_id = ?
                WHERE os.empresa_id = ? AND (os.tecnico_id = ? OR oss.id IS NOT NULL)";
        $params = [$tecnicoId, $eid, $tecnicoId];

        if ($q) {
            $sql .= " AND (os.numero LIKE ? OR c.nome LIKE ?)";
            $b = "%{$q}%";
            $params[] = $b;
            $params[] = $b;
        }
        $sql .= " ORDER BY os.criado_em DESC LIMIT 20";

        $st = DB::pdo()->prepare($sql);
        $st->execute($params);
        $this->json(['os' => $st->fetchAll()]);
    }

    /** Soma os serviços (os_servicos.valor_total) que o técnico realizou numa OS específica. */
    public function valorServicos(): void
    {
        $eid       = $this->empresaId();
        $tecnicoId = (int) $this->get('tecnico_id');
        $osId      = (int) $this->get('os_id');
        if (!$tecnicoId || !$osId) { $this->json(['valor' => 0]); return; }

        $st = DB::pdo()->prepare(
            "SELECT COALESCE(SUM(valor_total), 0) FROM os_servicos WHERE os_id = ? AND tecnico_id = ? AND empresa_id = ?"
        );
        $st->execute([$osId, $tecnicoId, $eid]);
        $this->json(['valor' => (float) $st->fetchColumn()]);
    }

    /** Percentual configurado do técnico (ou o padrão da empresa, se ele não tiver um próprio). */
    public function percentualTecnico(string $id): void
    {
        $eid = $this->empresaId();
        $st = DB::pdo()->prepare("SELECT comissao_percentual FROM usuarios WHERE id = ? AND empresa_id = ?");
        $st->execute([(int) $id, $eid]);
        $v = $st->fetchColumn();
        $this->json(['percentual' => $v !== false && $v !== null ? (float) $v : $this->percentualPadrao($eid)]);
    }

    /** Marca a comissão como paga e lança a despesa correspondente no Financeiro. */
    public function pagar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectPreservandoPainel(url('/comissoes')); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $st = $db->prepare(
            "SELECT c.*, u.nome AS tecnico_nome, os.numero AS os_numero
             FROM fin_comissoes c
             JOIN usuarios u ON u.id = c.tecnico_id
             LEFT JOIN ordens_servico os ON os.id = c.os_id
             WHERE c.id = ? AND c.empresa_id = ?"
        );
        $st->execute([(int) $id, $eid]);
        $com = $st->fetch();
        if (!$com) { $this->flash('error', 'Comissão não encontrada.'); $this->redirectPreservandoPainel(url('/comissoes')); }
        if ((int) $com['pago'] === 1) { $this->flash('error', 'Essa comissão já está paga.'); $this->redirectPreservandoPainel(url('/comissoes')); }

        $db->prepare("UPDATE fin_comissoes SET pago = 1, data_pagamento = CURDATE() WHERE id = ? AND empresa_id = ?")
           ->execute([(int) $id, $eid]);

        // Lança a despesa correspondente no Financeiro, pra bater com o caixa real.
        $stConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY id LIMIT 1");
        $stConta->execute([$eid]);
        $contaId = $stConta->fetchColumn() ?: null;

        $stCat = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id = ? AND tipo = 'despesa' AND nome = 'Comissões' LIMIT 1");
        $stCat->execute([$eid]);
        $catId = $stCat->fetchColumn();
        if (!$catId) {
            $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'despesa', 'Comissões', '#f97316')")->execute([$eid]);
            $catId = (int) $db->lastInsertId();
        }

        $descricao = 'Comissão — ' . $com['tecnico_nome'] . ($com['os_numero'] ? ' (OS ' . $com['os_numero'] . ')' : '');
        $db->prepare(
            "INSERT INTO fin_lancamentos
             (empresa_id, conta_id, categoria_id, os_id, usuario_id, tipo, descricao, valor, data_vencimento, data_pagamento, status)
             VALUES (?, ?, ?, ?, ?, 'despesa', ?, ?, CURDATE(), CURDATE(), 'pago')"
        )->execute([
            $eid, $contaId, $catId, $com['os_id'] ?: null, $com['tecnico_id'],
            $descricao, $com['valor_comissao'],
        ]);

        log_acao('comissao', 'pagar', (int) $id, $descricao . ' — ' . money((float) $com['valor_comissao']));
        $this->flash('success', 'Comissão marcada como paga e lançada no Financeiro!');
        $this->redirectPreservandoPainel(url('/comissoes'));
    }

    public function excluir(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectPreservandoPainel(url('/comissoes')); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $st = $db->prepare("SELECT pago FROM fin_comissoes WHERE id = ? AND empresa_id = ?");
        $st->execute([(int) $id, $eid]);
        $pago = $st->fetchColumn();
        if ($pago === false) { $this->flash('error', 'Comissão não encontrada.'); $this->redirectPreservandoPainel(url('/comissoes')); }
        if ((int) $pago === 1) { $this->flash('error', 'Não é possível excluir uma comissão já paga (fica registrada no Financeiro).'); $this->redirectPreservandoPainel(url('/comissoes')); }

        $db->prepare("DELETE FROM fin_comissoes WHERE id = ? AND empresa_id = ?")->execute([(int) $id, $eid]);
        $this->flash('success', 'Comissão excluída.');
        $this->redirectPreservandoPainel(url('/comissoes'));
    }
}
