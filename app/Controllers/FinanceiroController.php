<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Financeiro;

class FinanceiroController extends Controller
{
    private Financeiro $model;

    public function __construct() { $this->model = new Financeiro(); }

    public function buscar(): void
    {
        $q    = trim($this->get('q', ''));
        $eid  = $this->empresaId();
        $db   = \App\Core\DB::pdo();

        if (strlen($q) < 2) { $this->json([]); return; }

        $like = "%{$q}%";
        $inner = $this->model->sqlUnificadoPublico();

        $stmt = $db->prepare(
            "SELECT u.* FROM ({$inner}) u
             WHERE u.empresa_id = ?
               AND (u.descricao LIKE ? OR u.categoria_nome LIKE ?
                    OR CAST(u.valor AS CHAR) LIKE ? OR u.cliente_nome LIKE ?)
             ORDER BY u.data_vencimento DESC
             LIMIT 50"
        );
        $stmt->execute([$eid, $like, $like, $like, $like]);
        $this->json($stmt->fetchAll());
    }

    /** Checagem de duplicata antes de salvar (ver JS em fluxo_caixa.php, no submit do
     *  #formLancamento) — mesma tipo+descrição+valor+vencimento lançado nas últimas 6h é quase
     *  certamente o usuário clicando "Salvar" de novo achando que não tinha ido (foi exatamente
     *  o que gerou um lançamento duplicado de verdade, ver CLAUDE.md). Não bloqueia o salvar em
     *  si (o endpoint só avisa) — o técnico decide se quer confirmar mesmo assim. */
    public function verificarDuplicata(): void
    {
        $eid = $this->empresaId();
        $tipo = (string) $this->get('tipo', '');
        $descricao = trim((string) $this->get('descricao', ''));
        $valor = (float) str_replace(['.', ','], ['', '.'], (string) $this->get('valor', '0'));
        $dataVencimento = (string) $this->get('data_vencimento', '');
        $ignorarId = (int) $this->get('ignorar_id', 0); // edição de um lançamento não deveria acusar duplicata dele mesmo

        if ($descricao === '' || $valor <= 0 || $dataVencimento === '') {
            $this->json(['duplicado' => false]);
            return;
        }

        $stmt = DB::pdo()->prepare(
            "SELECT id, criado_em FROM fin_lancamentos
             WHERE empresa_id = ? AND tipo = ? AND descricao = ? AND valor = ? AND data_vencimento = ?
               AND id != ? AND criado_em >= DATE_SUB(NOW(), INTERVAL 6 HOUR)
             ORDER BY criado_em DESC LIMIT 1"
        );
        $stmt->execute([$eid, $tipo, $descricao, $valor, $dataVencimento, $ignorarId]);
        $achado = $stmt->fetch();

        $this->json([
            'duplicado' => (bool) $achado,
            'criadoEm'  => $achado['criado_em'] ?? null,
        ]);
    }

    public function index(): void
    {
        $page    = (int) $this->get('page', 1);
        $inicio  = $this->get('data_inicio', date('Y-m-01'));
        $fim     = $this->get('data_fim', date('Y-m-d'));
        $filtros = [
            'tipo'        => $this->get('tipo'),
            'status'      => $this->get('status'),
            'fonte'       => $this->get('fonte'),
            'categoria'   => $this->get('categoria'),
            'data_inicio' => $inicio,
            'data_fim'    => $fim,
        ];

        $this->view('financeiro.fluxo_caixa', [
            'titulo'      => 'Fluxo de Caixa',
            'paginator'   => $this->model->listarUnificado($page, 25, $filtros),
            'totaisFiltrados' => $this->model->totaisFiltrados($filtros),
            'taxasCartaoPeriodo' => $this->model->taxasCartaoNoPeriodo($inicio, $fim),
            'resumo'      => $this->model->saldoUnificado($inicio, $fim),
            'porFonte'    => $this->model->receitaPorFonte($inicio, $fim),
            'fluxo'       => $this->model->fluxoCaixa($inicio, $fim),
            'osPendentes' => $this->model->osPendentes(),
            'filtros'     => $filtros,
            'contas'      => $this->model->contas(),
            'categorias'  => $this->model->categorias(),
            'vencendo'    => $this->model->vencendoHoje(),
            'editando'    => null,
            'financeiroInicio' => $this->model->getInicio(),
        ]);
    }

    // ─── Data de início do financeiro (corte): ignora movimento anterior ──
    public function salvarInicio(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/financeiro')); }
        $eid  = $this->empresaId();
        $data = trim($this->post('financeiro_inicio', ''));

        if ($data === '') {
            DB::pdo()->prepare("UPDATE empresas SET financeiro_inicio = NULL WHERE id = ?")->execute([$eid]);
            $this->flash('success', 'Corte removido — o financeiro voltou a contar todo o histórico.');
        } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $data)) {
            DB::pdo()->prepare("UPDATE empresas SET financeiro_inicio = ? WHERE id = ?")->execute([$data, $eid]);
            $this->flash('success', 'Pronto! O financeiro passa a contar a partir de ' . date('d/m/Y', strtotime($data)) . '. O histórico anterior sumiu do fluxo, mas as OS continuam intactas.');
        } else {
            $this->flash('error', 'Data inválida.');
        }
        $this->redirect(url('/financeiro'));
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $valor = (float) str_replace(['.', ','], ['', '.'], $this->post('valor', 0));
        $data  = [
            'conta_id'        => (int) $this->post('conta_id') ?: null,
            'categoria_id'    => $this->post('categoria_id') ?: null,
            'cliente_id'      => $this->post('cliente_id') ?: null,
            'tipo'            => $this->post('tipo', 'receita'),
            'descricao'       => trim($this->post('descricao')),
            'valor'           => $valor,
            'data_vencimento' => $this->post('data_vencimento'),
            'status'          => $this->post('status', 'pendente'),
            'forma_pagamento' => $this->post('forma_pagamento') ?: null,
            'observacoes'     => $this->post('observacoes'),
            'usuario_id'      => $this->usuarioId(),
        ];

        if ($data['status'] === 'pago') {
            $data['data_pagamento'] = date('Y-m-d');
        }

        $this->model->insert($data);
        $this->flash('success', 'Lançamento registrado!');
        $this->redirect(url('/financeiro'));
    }

    public function editar(string $id): void
    {
        $eid  = $this->empresaId();
        $stmt = \App\Core\DB::pdo()->prepare(
            "SELECT * FROM fin_lancamentos WHERE id = ? AND empresa_id = ?"
        );
        $stmt->execute([(int)$id, $eid]);
        $lancamento = $stmt->fetch();

        if (!$lancamento) {
            $this->flash('error', 'Lançamento não encontrado.');
            $this->redirect(url('/financeiro'));
        }

        $inicio = date('Y-m-01');
        $fim    = date('Y-m-d');

        $this->view('financeiro.fluxo_caixa', [
            'titulo'      => 'Editar Lançamento',
            'paginator'   => $this->model->listarUnificado(1, 25, ['data_inicio' => $inicio, 'data_fim' => $fim]),
            'totaisFiltrados' => $this->model->totaisFiltrados(['data_inicio' => $inicio, 'data_fim' => $fim]),
            'resumo'      => $this->model->saldoUnificado($inicio, $fim),
            'porFonte'    => $this->model->receitaPorFonte($inicio, $fim),
            'fluxo'       => $this->model->fluxoCaixa($inicio, $fim),
            'osPendentes' => $this->model->osPendentes(),
            'filtros'     => [],
            'contas'      => $this->model->contas(),
            'categorias'  => $this->model->categorias(),
            'vencendo'    => $this->model->vencendoHoje(),
            'editando'    => $lancamento,
        ]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid   = $this->empresaId();
        $valor = (float) str_replace(['.', ','], ['', '.'], $this->post('valor', 0));
        $status = $this->post('status', 'pendente');
        $data  = [
            'categoria_id'    => $this->post('categoria_id') ?: null,
            'tipo'            => $this->post('tipo', 'receita'),
            'descricao'       => trim($this->post('descricao')),
            'valor'           => $valor,
            'data_vencimento' => $this->post('data_vencimento'),
            'status'          => $status,
            'forma_pagamento' => $this->post('forma_pagamento') ?: null,
            'observacoes'     => $this->post('observacoes'),
            'data_pagamento'  => $status === 'pago'
                ? ($this->post('data_pagamento') ?: date('Y-m-d'))
                : null,
        ];

        $set    = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = (int)$id;
        $values[] = $eid;

        \App\Core\DB::pdo()->prepare(
            "UPDATE fin_lancamentos SET {$set} WHERE id = ? AND empresa_id = ?"
        )->execute($values);

        $this->flash('success', 'Lançamento atualizado!');
        $this->redirect(url('/financeiro'));
    }

    public function liquidar(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $forma = $this->post('forma_pagamento', 'dinheiro');
        $eid   = $this->empresaId();

        \App\Core\DB::pdo()->prepare(
            "UPDATE fin_lancamentos
             SET status = 'pago', data_pagamento = CURDATE(), forma_pagamento = ?
             WHERE id = ? AND empresa_id = ? AND status = 'pendente'"
        )->execute([$forma, (int)$id, $eid]);

        $this->json(['success' => true]);
    }

    public function pagar(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $this->model->update((int)$id, [
            'status'          => 'pago',
            'data_pagamento'  => date('Y-m-d'),
            'forma_pagamento' => $this->post('forma_pagamento', 'dinheiro'),
        ]);
        $this->json(['success' => true]);
    }

    public function pagarOs(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $eid   = $this->empresaId();
        $db    = \App\Core\DB::pdo();
        $forma = $this->post('forma_pagamento', 'dinheiro');
        // "PIX (maquininha)" é só uma opção de UI pra sinalizar que esse pix passou pela
        // maquininha (tem taxa) — normalizado pra 'pix' antes de qualquer gravação, o banco não
        // tem essa forma como valor válido de forma_pagamento.
        $comTaxaPix = $forma === 'pix_maquininha';
        if ($comTaxaPix) $forma = 'pix';

        $stmt = $db->prepare(
            "SELECT os.*, c.nome AS cliente_nome, eq.marca AS equip_marca, eq.modelo AS equip_modelo
             FROM ordens_servico os
             JOIN clientes c ON c.id = os.cliente_id
             LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
             WHERE os.id = ? AND os.empresa_id = ?"
        );
        $stmt->execute([(int)$id, $eid]);
        $os = $stmt->fetch();
        if (!$os) { $this->json(['error' => 'OS não encontrada'], 404); }

        $db->prepare(
            "UPDATE ordens_servico
             SET situacao_pagamento='pago', valor_pago=valor_total, forma_pagamento_fechamento=?
             WHERE id=? AND empresa_id=?"
        )->execute([$forma, (int)$id, $eid]);

        // Taxa de cartão: mesma lógica de OrdemServicoController::fechar() (que este atalho
        // nunca teve — era o único caminho de pagamento de OS sem ela, causa real de uma OS paga
        // no débito não gerar a despesa "Taxa cartão", ver CLAUDE.md). O modal "Receber OS" não
        // tem seletor de parcelas nem "repassar taxa ao cliente", então é sempre 1x sem repasse.
        $ehMaquininha = in_array($forma, ['cartao_credito', 'cartao_debito'], true) || $comTaxaPix;
        $taxa         = $ehMaquininha ? taxa_cartao_configurada($eid, $forma, 1) : 0.0;
        $valorCobrado = (float) $os['valor_total'];
        $taxaValor    = ($ehMaquininha && $taxa > 0 && $valorCobrado > 0) ? round($valorCobrado * $taxa / 100, 2) : 0.0;

        $stmtConta = $db->prepare(
            "SELECT id FROM fin_contas WHERE empresa_id=? AND ativo=1 ORDER BY id LIMIT 1"
        );
        $stmtConta->execute([$eid]);
        $contaId = $stmtConta->fetchColumn();

        if ($contaId) {
            $catStmtServ = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='receita' AND nome='Serviços' LIMIT 1");
            $catStmtServ->execute([$eid]);
            $catServico = $catStmtServ->fetchColumn();
            if (!$catServico) {
                $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'receita', 'Serviços', '#198754')")->execute([$eid]);
                $catServico = (int) $db->lastInsertId();
            }

            $descricaoBase = 'OS ' . $os['numero'] . ' — ' . implode(' — ', array_filter([
                trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? '')),
                $os['cliente_nome'],
            ]));

            $db->prepare(
                "INSERT INTO fin_lancamentos
                 (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao, valor,
                  data_vencimento, data_pagamento, status, forma_pagamento)
                 VALUES (?,?,?,?,?,?,'receita',?,?,CURDATE(),CURDATE(),'pago',?)"
            )->execute([
                $eid, $contaId, $catServico, (int)$id, $os['cliente_id'], $this->usuarioId(),
                $descricaoBase, $valorCobrado, $forma,
            ]);

            $db->prepare(
                "INSERT INTO os_pagamentos
                 (empresa_id, os_id, forma_pagamento, valor, parcelas, taxa_percentual, taxa_valor, valor_cobrado)
                 VALUES (?, ?, ?, ?, 1, ?, ?, ?)"
            )->execute([$eid, (int)$id, $forma, $valorCobrado, $taxa, $taxaValor, $valorCobrado]);

            // Despesa: taxa do cartão — mesmo padrão de OrdemServicoController::fechar().
            if ($taxaValor > 0) {
                $catStmtTaxa = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='despesa' AND nome='Taxas de cartão' LIMIT 1");
                $catStmtTaxa->execute([$eid]);
                $catTaxa = $catStmtTaxa->fetchColumn();
                if (!$catTaxa) {
                    $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'despesa', 'Taxas de cartão', '#dc3545')")->execute([$eid]);
                    $catTaxa = (int) $db->lastInsertId();
                }
                if ($forma === 'pix') {
                    $descTaxa = 'Taxa pix (maquininha) — OS ' . $os['numero'] . ' (' . number_format($taxa, 2, ',', '.') . '%)';
                } else {
                    $qualCart = $forma === 'cartao_debito' ? 'débito' : '1x';
                    $descTaxa = 'Taxa cartão — OS ' . $os['numero'] . ' (' . $qualCart . ' · ' . number_format($taxa, 2, ',', '.') . '%)';
                }
                $db->prepare(
                    "INSERT INTO fin_lancamentos
                     (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao,
                      valor, data_vencimento, data_pagamento, status, forma_pagamento)
                     VALUES (?, ?, ?, ?, ?, ?, 'despesa', ?, ?, CURDATE(), CURDATE(), 'pago', ?)"
                )->execute([
                    $eid, $contaId, $catTaxa, (int)$id, $os['cliente_id'], $this->usuarioId(),
                    $descTaxa, $taxaValor, $forma,
                ]);
            }
        }

        $this->json(['success' => true]);
    }

    public function excluir(string $id): void
    {
        $this->model->delete((int)$id);
        $this->flash('success', 'Lançamento removido.');
        $this->redirect(url('/financeiro'));
    }
}
