<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\Financeiro;
use App\Services\Lembretes\AgendaLembreteService;

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

    /** Cor da etiqueta na Agenda pra um lançamento sincronizado — mesma paleta já usada em
     *  financeiro/categorias.php pra distinguir receita (verde) de despesa (vermelho), sem
     *  escolha manual: o tipo do lançamento já diz a cor. */
    private function corAgendaPorTipo(string $tipo): string
    {
        return $tipo === 'despesa' ? '#dc2626' : '#16a34a';
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
            'mostrar_agenda'  => $this->post('mostrar_agenda') ? 1 : 0,
        ];

        if ($data['status'] === 'pago') {
            $data['data_pagamento'] = date('Y-m-d');
        }

        $novoId = $this->model->insert($data);
        $this->sincronizarAgenda($novoId, $this->empresaId());
        $this->flash('success', 'Lançamento registrado!');
        $this->redirect(url('/financeiro'));
    }

    /**
     * Financeiro → Agenda (sentido inverso do "molde financeiro" que a Agenda já tinha — ver
     * CLAUDE.md "Agenda gera lançamento no Financeiro (opcional)" e a seção de complemento
     * logo abaixo). Uma conta lançada direto no Financeiro, ainda PENDENTE (a pagar/receber no
     * futuro), ganha automaticamente um evento na Agenda na data de vencimento, pra aparecer
     * como lembrete visual no calendário — sem precisar recriar a conta manualmente lá.
     *
     * Só considera lançamento manual (sem os_id) — receita de venda/OS já nasce vinculada a um
     * documento próprio (a OS, o PDV) e, na prática, quase sempre já nasce paga na hora; não faz
     * sentido lotar a Agenda com um evento por parcela de cartão, por exemplo.
     *
     * Idempotente e nos dois sentidos:
     *   - Sem agenda_id ainda + pendente → cria o evento, grava o vínculo de volta.
     *   - Já tem agenda_id + continua pendente → atualiza data/valor/descrição no evento (o
     *     usuário editou o lançamento, o lembrete acompanha).
     *   - Já tem agenda_id + virou pago → marca o evento como concluído (fecha o lembrete).
     *
     * `mostrar_agenda` (migration 048) dá controle por lançamento: desligado nunca cria/mantém
     * evento (remove se já existia). Ligado, a etiqueta do evento sai verde/vermelha conforme
     * receita/despesa (`corAgendaPorTipo()`, sem escolha manual) gravada em `agenda.cor` — a
     * mesma coluna que já dá cor personalizada a qualquer evento (ver `agenda_evento_cor()`),
     * então nenhuma view da Agenda precisou de mudança pra exibir a cor.
     *
     * `observacoes` do lançamento vira `agenda.descricao` — o texto some do modal do evento se
     * não for copiado, já que a Agenda não sabe nada sobre `fin_lancamentos.observacoes`.
     *
     * Lembrete interno reaproveita `AgendaLembreteService`, o mesmo motor que qualquer evento da
     * Agenda já usa (ver CLAUDE.md "Lembretes de agenda") — nada de mecanismo novo. Como o
     * lembrete pro "técnico" exige `usuario_id` preenchido na linha do evento
     * (`AgendaLembreteService::agendarOcorrencia()`), o evento nasce com `usuario_id` = quem
     * criou o lançamento (`fin_lancamentos.usuario_id`, já gravado por `salvar()`) e
     * `lembrete_tecnico_offsets = '1440'` (1 dia antes) — só na criação; editar depois só chama
     * `reagendar()` de novo pra acompanhar uma mudança de vencimento, sem sobrescrever um
     * lembrete que o usuário tenha customizado direto na Agenda. Virou pago → cancela qualquer
     * lembrete ainda pendente (`cancelarPendentes()`), pra não notificar sobre uma conta que já
     * foi paga antes da data do lembrete chegar.
     */
    private function sincronizarAgenda(int $lancamentoId, int $eid): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT * FROM fin_lancamentos WHERE id = ? AND empresa_id = ? AND os_id IS NULL");
        $stmt->execute([$lancamentoId, $eid]);
        $lanc = $stmt->fetch();
        if (!$lanc) return;

        $lembretes = new AgendaLembreteService();

        // "Mostrar na Agenda" desligado pra este lançamento: nunca cria evento novo, e se já
        // tinha um (ligado antes, desligado agora), remove — `fin_lancamentos.agenda_id`
        // (ON DELETE SET NULL) se limpa sozinho ao apagar a linha de `agenda`.
        if (empty($lanc['mostrar_agenda'])) {
            if (!empty($lanc['agenda_id'])) {
                $db->prepare("DELETE FROM agenda WHERE id = ? AND empresa_id = ?")
                   ->execute([$lanc['agenda_id'], $eid]);
            }
            return;
        }

        if (!empty($lanc['agenda_id'])) {
            if ($lanc['status'] === 'pago') {
                $db->prepare("UPDATE agenda SET status = 'concluido' WHERE id = ? AND empresa_id = ? AND status <> 'cancelado'")
                   ->execute([$lanc['agenda_id'], $eid]);
                $lembretes->cancelarPendentes((int) $lanc['agenda_id'], $eid);
            } elseif ($lanc['status'] === 'pendente') {
                // status = 'agendado' de volta: cobre o caso de reabrir um lançamento que já
                // tinha sido marcado como pago (evento virou 'concluido') e voltou pra pendente
                // editando o Status no modal — sem isso o evento ficava preso em "Concluído" na
                // Agenda mesmo com a conta voltando a ser uma cobrança em aberto.
                $db->prepare(
                    "UPDATE agenda SET titulo = ?, descricao = ?, data_inicio = ?, data_fim = ?, fin_tipo = ?,
                     fin_valor = ?, fin_categoria_id = ?, fin_conta_id = ?, cliente_id = ?, cor = ?, status = 'agendado'
                     WHERE id = ? AND empresa_id = ? AND status <> 'cancelado'"
                )->execute([
                    $lanc['descricao'], $lanc['observacoes'],
                    $lanc['data_vencimento'] . ' 09:00:00', $lanc['data_vencimento'] . ' 09:00:00',
                    $lanc['tipo'], $lanc['valor'], $lanc['categoria_id'], $lanc['conta_id'], $lanc['cliente_id'],
                    $this->corAgendaPorTipo($lanc['tipo']),
                    $lanc['agenda_id'], $eid,
                ]);
                $lembretes->reagendar((int) $lanc['agenda_id'], $eid);
            }
            return;
        }

        if ($lanc['status'] !== 'pendente' || empty($lanc['data_vencimento'])) return;

        $db->prepare(
            "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, usuario_id, data_inicio, data_fim,
             dia_todo, status, fin_tipo, fin_valor, fin_categoria_id, fin_conta_id, cor, lembrete_tecnico_offsets)
             VALUES (?, ?, ?, 'financeiro', ?, ?, ?, ?, 1, 'agendado', ?, ?, ?, ?, ?, '1440')"
        )->execute([
            $eid, $lanc['descricao'], $lanc['observacoes'], $lanc['cliente_id'], $lanc['usuario_id'],
            $lanc['data_vencimento'] . ' 09:00:00', $lanc['data_vencimento'] . ' 09:00:00',
            $lanc['tipo'], $lanc['valor'], $lanc['categoria_id'], $lanc['conta_id'],
            $this->corAgendaPorTipo($lanc['tipo']),
        ]);
        $novoAgendaId = (int) $db->lastInsertId();
        $db->prepare("UPDATE fin_lancamentos SET agenda_id = ? WHERE id = ?")
           ->execute([$novoAgendaId, $lancamentoId]);
        $lembretes->reagendar($novoAgendaId, $eid);
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
            'mostrar_agenda'  => $this->post('mostrar_agenda') ? 1 : 0,
        ];

        $set    = implode(', ', array_map(fn($k) => "`$k` = ?", array_keys($data)));
        $values = array_values($data);
        $values[] = (int)$id;
        $values[] = $eid;

        \App\Core\DB::pdo()->prepare(
            "UPDATE fin_lancamentos SET {$set} WHERE id = ? AND empresa_id = ?"
        )->execute($values);

        $this->sincronizarAgenda((int) $id, $eid);
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

        $this->sincronizarAgenda((int) $id, $eid);
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
        $this->sincronizarAgenda((int) $id, $this->empresaId());
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
        $eid = $this->empresaId();

        // Achado revisando o sistema: excluir o lançamento nunca limpava o evento que
        // sincronizarAgenda() tinha criado na Agenda — ficava órfão pra sempre (com o lembrete
        // ainda armado), já que só o lado Agenda→lançamento tem ON DELETE SET NULL, não o
        // inverso. `agenda_lembretes_fila.agenda_id` tem ON DELETE CASCADE, então apagar a linha
        // de `agenda` já cancela qualquer lembrete pendente sozinho, sem precisar chamar
        // cancelarPendentes() explicitamente.
        $stmt = \App\Core\DB::pdo()->prepare("SELECT agenda_id FROM fin_lancamentos WHERE id = ? AND empresa_id = ?");
        $stmt->execute([(int) $id, $eid]);
        $agendaId = $stmt->fetchColumn();

        $this->model->delete((int)$id);

        if ($agendaId) {
            \App\Core\DB::pdo()->prepare("DELETE FROM agenda WHERE id = ? AND empresa_id = ?")
                ->execute([(int) $agendaId, $eid]);
        }

        $this->flash('success', 'Lançamento removido.');
        $this->redirect(url('/financeiro'));
    }
}
