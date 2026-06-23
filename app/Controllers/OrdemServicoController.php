<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Models\OrdemServico;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Usuario;

class OrdemServicoController extends Controller
{
    private OrdemServico $model;

    public function __construct()
    {
        $this->model = new OrdemServico();
    }

    public function index(): void
    {
        $page    = (int) $this->get('page', 1);
        $filtros = [
            'busca'       => $this->get('busca', ''),
            'status_id'   => $this->get('status_id'),
            'tecnico_id'  => $this->get('tecnico_id'),
            'data_inicio' => $this->get('data_inicio'),
            'data_fim'    => $this->get('data_fim'),
            'em_garantia' => $this->get('em_garantia'),
        ];

        $db = DB::pdo();
        $eid = $this->empresaId();
        $statusList = $db->prepare("SELECT * FROM os_status WHERE empresa_id = ? ORDER BY ordem")->execute([$eid])
            ? ($db->prepare("SELECT * FROM os_status WHERE empresa_id = ? ORDER BY ordem") ?: null) : null;
        $stmtS = $db->prepare("SELECT * FROM os_status WHERE empresa_id = ? ORDER BY ordem");
        $stmtS->execute([$eid]);

        $this->view('os.index', [
            'titulo'    => 'Ordens de Serviço',
            'paginator' => $this->model->listar($page, 20, $filtros),
            'filtros'   => $filtros,
            'statusList'=> $stmtS->fetchAll(),
            'tecnicos'  => (new Usuario())->tecnicos(),
            'totais'       => $this->model->totaisPorStatus(),
            'totalGarantia'=> $this->model->totalEmGarantia(),
        ]);
    }

    public function criar(): void
    {
        $db  = DB::pdo();
        $eid = $this->empresaId();

        $stmtS = $db->prepare("SELECT * FROM os_status WHERE empresa_id = ? ORDER BY ordem LIMIT 1");
        $stmtS->execute([$eid]);
        $primeiroStatus = $stmtS->fetch();

        $stmtCat = $db->prepare("SELECT * FROM categorias_equipamento WHERE empresa_id = ? ORDER BY nome");
        $stmtCat->execute([$eid]);

        $this->view('os.form', [
            'titulo'         => 'Nova OS',
            'os'             => [],
            'statusList'     => $this->statusList($eid),
            'tecnicos'       => (new Usuario())->tecnicos(),
            'numero'         => $this->model->proximoNumero(),
            'status_inicial' => $primeiroStatus,
            'categorias'     => $stmtCat->fetchAll(),
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $db  = DB::pdo();
        $eid = $this->empresaId();

        // Validações obrigatórias
        $clienteId = (int) $this->post('cliente_id');
        if (!$clienteId) {
            $this->flash('error', 'Selecione um cliente da lista de sugestões antes de salvar a OS.');
            $this->redirect(url('/os/nova'));
        }

        // Verificar se o cliente pertence a esta empresa
        $stmtC = $db->prepare("SELECT id FROM clientes WHERE id = ? AND empresa_id = ? LIMIT 1");
        $stmtC->execute([$clienteId, $eid]);
        if (!$stmtC->fetchColumn()) {
            $this->flash('error', 'Cliente inválido ou não encontrado.');
            $this->redirect(url('/os/nova'));
        }

        if (!trim($this->post('equip_tipo', ''))) {
            $this->flash('error', 'Informe o tipo do equipamento.');
            $this->redirect(url('/os/nova'));
        }

        if (!trim($this->post('defeito_relatado', ''))) {
            $this->flash('error', 'Informe o defeito relatado pelo cliente.');
            $this->redirect(url('/os/nova'));
        }

        // Criar/encontrar equipamento
        $equipId = (int) $this->post('equipamento_id');
        if (!$equipId) {
            $stmtEq = $db->prepare(
                "INSERT INTO equipamentos (empresa_id, cliente_id, categoria_id, tipo, marca, modelo, numero_serie, cor, voltagem, estado_entrada, descricao_defeito_cliente, acessorios, senha_desbloqueio)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtEq->execute([
                $eid,
                $clienteId,
                $this->post('categoria_id') ?: null,
                $this->post('equip_tipo'),
                $this->post('equip_marca'),
                $this->post('equip_modelo'),
                $this->post('numero_serie'),
                $this->post('equip_cor'),
                $this->post('voltagem') ?: null,
                $this->post('estado_entrada', 'regular'),
                $this->post('defeito_relatado'),
                $this->post('acessorios'),
                $this->post('senha_desbloqueio'),
            ]);
            $equipId = (int) $db->lastInsertId();
        }

        $numero = $this->model->proximoNumero();
        $statusId = (int) $this->post('status_id');

        $data = [
            'numero'          => $numero,
            'cliente_id'      => $clienteId,
            'equipamento_id'  => $equipId,
            'status_id'       => $statusId,
            'tecnico_id'      => $this->post('tecnico_id') ?: null,
            'recepcionista_id'=> $this->usuarioId(),
            'prioridade'      => $this->post('prioridade', 'normal'),
            'tipo_servico'    => $this->post('tipo_servico', 'conserto'),
            'defeito_relatado'=> $this->post('defeito_relatado'),
            'data_previsao'   => $this->post('data_previsao') ?: null,
            'garantia_dias'   => (int) $this->post('garantia_dias', 90),
            'observacoes_internas' => $this->post('observacoes_internas'),
            'observacoes_cliente'  => $this->post('observacoes_cliente'),
        ];

        $osId = $this->model->insert($data);
        $this->model->registrarHistorico($osId, null, $statusId, 'OS criada.');

        $this->flash('success', "OS: {$numero} aberta com sucesso!");
        $this->redirect(url("/os/{$osId}"));
    }

    public function ver(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $eid = $this->empresaId();
        $this->view('os.show', [
            'titulo'     => 'OS: ' . $os['numero'],
            'os'         => $os,
            'statusList' => $this->statusList($eid),
            'tecnicos'   => (new Usuario())->tecnicos(),
        ]);
    }

    public function editar(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmtCat = $db->prepare("SELECT * FROM categorias_equipamento WHERE empresa_id = ? ORDER BY nome");
        $stmtCat->execute([$eid]);

        $this->view('os.form', [
            'titulo'     => 'Editar OS: ' . $os['numero'],
            'os'         => $os,
            'statusList' => $this->statusList($eid),
            'tecnicos'   => (new Usuario())->tecnicos(),
            'categorias' => $stmtCat->fetchAll(),
            'status_inicial' => null,
        ]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $os = $this->model->find((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $novoStatusId = (int) $this->post('status_id');
        $db = DB::pdo();

        // Datas automáticas por tipo de status
        $stmtTipo = $db->prepare("SELECT tipo FROM os_status WHERE id = ?");
        $stmtTipo->execute([$novoStatusId]);
        $statusTipo = $stmtTipo->fetchColumn();

        $data = [
            'status_id'       => $novoStatusId,
            'tecnico_id'      => $this->post('tecnico_id') ?: null,
            'prioridade'      => $this->post('prioridade', 'normal'),
            'tipo_servico'    => $this->post('tipo_servico', 'conserto'),
            'defeito_relatado'=> $this->post('defeito_relatado'),
            'defeito_constatado' => $this->post('defeito_constatado') ?: null,
            'laudo_tecnico'   => $this->post('laudo_tecnico') ?: null,
            'solucao_aplicada'=> $this->post('solucao_aplicada') ?: null,
            'data_previsao'   => $this->post('data_previsao') ?: null,
            'garantia_dias'   => (int) $this->post('garantia_dias', 90),
            'observacoes_internas' => $this->post('observacoes_internas') ?: null,
            'observacoes_cliente'  => $this->post('observacoes_cliente') ?: null,
        ];

        if ($statusTipo === 'concluida' && !$os['data_conclusao']) {
            $data['data_conclusao'] = date('Y-m-d H:i:s');
        }
        if ($statusTipo === 'entregue' && !$os['data_entrega']) {
            $data['data_entrega'] = date('Y-m-d H:i:s');
        }

        $this->model->update((int) $id, $data);

        // Registrar histórico se status mudou
        if ($novoStatusId !== (int) $os['status_id']) {
            $this->model->registrarHistorico((int) $id, $os['status_id'], $novoStatusId, 'Atualizado via edição.');
        }

        $this->flash('success', 'OS atualizada com sucesso!');
        $this->redirect(url("/os/{$id}"));
    }

    public function atualizarStatus(string $id): void
    {
        $os = $this->model->find((int) $id);
        if (!$os) { $this->json(['error' => 'OS não encontrada'], 404); }

        $novoStatusId = (int) $this->post('status_id');
        $descricao    = $this->post('descricao', '');

        $update = ['status_id' => $novoStatusId];

        $db = DB::pdo();
        $stmt = $db->prepare("SELECT tipo FROM os_status WHERE id = ?");
        $stmt->execute([$novoStatusId]);
        $statusTipo = $stmt->fetchColumn();

        if ($statusTipo === 'concluida') $update['data_conclusao'] = date('Y-m-d H:i:s');
        if ($statusTipo === 'entregue')  $update['data_entrega']   = date('Y-m-d H:i:s');

        $this->model->update((int) $id, $update);
        $this->model->registrarHistorico((int) $id, $os['status_id'], $novoStatusId, $descricao);

        $this->json(['success' => true]);
    }

    public function adicionarServico(string $id): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();
        $qtd = (float) str_replace(',', '.', $this->post('quantidade', 1));
        $val = (float) str_replace(',', '.', $this->post('valor_unitario', 0));
        $servicoId = (int) $this->post('servico_id');

        if ($servicoId) {
            // Editar existente
            $db->prepare(
                "UPDATE os_servicos SET descricao=?, quantidade=?, valor_unitario=?, valor_total=?, tecnico_id=?
                 WHERE id=? AND os_id=? AND empresa_id=?"
            )->execute([
                $this->post('descricao'), $qtd, $val, $qtd * $val,
                $this->post('tecnico_id') ?: null,
                $servicoId, (int) $id, $eid,
            ]);
        } else {
            // Criar novo
            $db->prepare(
                "INSERT INTO os_servicos (empresa_id, os_id, descricao, quantidade, valor_unitario, valor_total, tecnico_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([
                $eid, (int) $id,
                $this->post('descricao'),
                $qtd, $val, $qtd * $val,
                $this->post('tecnico_id') ?: null,
            ]);
        }

        $totais = $this->model->calcularTotal((int) $id);
        $this->model->update((int) $id, ['valor_total' => $totais['total']]);
        $this->flash('success', $servicoId ? 'Serviço atualizado!' : 'Serviço adicionado!');
        $this->redirect(url("/os/{$id}"));
    }

    public function adicionarPeca(string $id): void
    {
        $eid     = $this->empresaId();
        $db      = DB::pdo();
        $prodId  = $this->post('produto_id') ?: null;
        $qtd     = (float) str_replace(',', '.', $this->post('quantidade', 1));
        $valUnit = (float) str_replace(',', '.', $this->post('valor_unitario', 0));
        $pecaId  = (int) $this->post('peca_id');

        if ($pecaId) {
            $db->prepare(
                "UPDATE os_pecas SET descricao=?, quantidade=?, valor_unitario=?, valor_total=?
                 WHERE id=? AND os_id=? AND empresa_id=?"
            )->execute([
                $this->post('descricao'), $qtd, $valUnit, $qtd * $valUnit,
                $pecaId, (int) $id, $eid,
            ]);
        } else {
            $db->prepare(
                "INSERT INTO os_pecas (empresa_id, os_id, produto_id, descricao, quantidade, valor_unitario, valor_total)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([$eid, (int) $id, $prodId, $this->post('descricao'), $qtd, $valUnit, $qtd * $valUnit]);

            if ($prodId) {
                (new Produto())->movimentar((int) $prodId, 'saida', $qtd, $valUnit, '' . $id, (int) $id);
            }
        }

        $totais = $this->model->calcularTotal((int) $id);
        $this->model->update((int) $id, ['valor_total' => $totais['total']]);
        $this->flash('success', $pecaId ? 'Peça atualizada!' : 'Peça adicionada!');
        $this->redirect(url("/os/{$id}"));
    }

    public function removerServico(string $osId, string $itemId): void
    {
        $eid = $this->empresaId();
        DB::pdo()->prepare("DELETE FROM os_servicos WHERE id = ? AND os_id = ? AND empresa_id = ?")
                 ->execute([(int) $itemId, (int) $osId, $eid]);
        $totais = $this->model->calcularTotal((int) $osId);
        $this->model->update((int) $osId, ['valor_total' => $totais['total']]);
        $this->flash('success', 'Serviço removido.');
        $this->redirect(url("/os/{$osId}"));
    }

    public function removerPeca(string $osId, string $itemId): void
    {
        $eid = $this->empresaId();
        DB::pdo()->prepare("DELETE FROM os_pecas WHERE id = ? AND os_id = ? AND empresa_id = ?")
                 ->execute([(int) $itemId, (int) $osId, $eid]);
        $totais = $this->model->calcularTotal((int) $osId);
        $this->model->update((int) $osId, ['valor_total' => $totais['total']]);
        $this->flash('success', 'Peça removida.');
        $this->redirect(url("/os/{$osId}"));
    }

    public function imprimir(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }
        $this->view('os.print', ['os' => $os], 'print');
    }

    public function imprimirGarantia(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        // Buscar OS original se for garantia
        $osOrigem = null;
        if (!empty($os['os_origem_id'])) {
            $osOrigem = $this->model->findCompleto((int) $os['os_origem_id']);
        }

        $this->view('os.print_garantia', ['os' => $os, 'osOrigem' => $osOrigem], 'print_garantia');
    }

    public function imprimirFechamento(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }
        $this->view('os.print_fechamento', ['os' => $os], 'print');
    }

    public function fechar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();
        $os  = $this->model->find((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        // Buscar status "concluida" da empresa
        $stmtStatus = $db->prepare(
            "SELECT id FROM os_status WHERE empresa_id = ? AND tipo = 'concluida' ORDER BY ordem LIMIT 1"
        );
        $stmtStatus->execute([$eid]);
        $statusConcluido = $stmtStatus->fetchColumn();

        if (!$statusConcluido) {
            $this->flash('error', 'Nenhum status do tipo "Concluída" cadastrado. Crie um em Config → Status de OS.');
            $this->redirect(url('/os/' . $id));
        }

        $garantiaDias  = (int) $this->post('garantia_dias', $os['garantia_dias'] ?? 90);
        $solucao       = $this->post('solucao_aplicada', '');
        $laudo         = $this->post('laudo_tecnico', '');
        $obsCliente    = $this->post('observacoes_cliente', '');
        $formaPagto    = $this->post('forma_pagamento', '');
        $valorPago     = (float) str_replace(',', '.', $this->post('valor_pago', 0));
        $dataConclusao = date('Y-m-d H:i:s');
        $garantiaAte   = date('Y-m-d', strtotime("+{$garantiaDias} days"));

        $update = [
            'status_id'          => $statusConcluido,
            'data_conclusao'     => $dataConclusao,
            'garantia_dias'      => $garantiaDias,
            'garantia_ate'       => $garantiaAte,
            'solucao_aplicada'   => $solucao ?: null,
            'laudo_tecnico'      => $laudo ?: null,
            'observacoes_cliente'=> $obsCliente ?: $os['observacoes_cliente'],
            'situacao_pagamento' => $valorPago >= $os['valor_total'] && $os['valor_total'] > 0 ? 'pago' : ($valorPago > 0 ? 'parcial' : $os['situacao_pagamento']),
        ];

        if ($valorPago > 0) {
            $update['valor_pago']       = $valorPago;
            $update['forma_pagamento_fechamento'] = $formaPagto;
        }

        $this->model->update((int) $id, $update);
        $this->model->registrarHistorico((int) $id, $os['status_id'], $statusConcluido,
            'OS fechada. Garantia até ' . date('d/m/Y', strtotime($garantiaAte)) . '.'
        );

        // Lançar no financeiro se recebeu pagamento
        if ($valorPago > 0 && $formaPagto) {
            $stmtConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY id LIMIT 1");
            $stmtConta->execute([$eid]);
            $contaId = $stmtConta->fetchColumn();

            if ($contaId) {
                $db->prepare(
                    "INSERT INTO fin_lancamentos (empresa_id, conta_id, os_id, cliente_id, usuario_id, tipo, descricao, valor, data_vencimento, data_pagamento, status, forma_pagamento)
                     VALUES (?, ?, ?, ?, ?, 'receita', ?, ?, CURDATE(), CURDATE(), 'pago', ?)"
                )->execute([
                    $eid, $contaId, (int) $id, $os['cliente_id'], $this->usuarioId(),
                    '' . $os['numero'] . ' — ' . ($os['cliente_nome'] ?? ''),
                    $valorPago, $formaPagto,
                ]);
            }
        }

        $this->flash('success', 'OS fechada com sucesso! Garantia até ' . date('d/m/Y', strtotime($garantiaAte)) . '.');
        $this->redirect(url('/os/' . $id . '/imprimir/fechamento'));
    }

    public function buscarEmGarantia(): void
    {
        $eid  = $this->empresaId();
        $q    = trim($this->get('q', ''));
        $db   = DB::pdo();

        $sql = "SELECT os.id, os.numero, os.garantia_ate, os.garantia_dias,
                       os.data_conclusao, os.valor_total,
                       c.nome AS cliente_nome, c.telefone AS cliente_tel,
                       eq.tipo AS equip_tipo, eq.marca AS equip_marca, eq.modelo AS equip_modelo,
                       DATEDIFF(os.garantia_ate, CURDATE()) AS dias_restantes
                FROM ordens_servico os
                JOIN os_status s  ON s.id  = os.status_id
                JOIN clientes c   ON c.id  = os.cliente_id
                JOIN equipamentos eq ON eq.id = os.equipamento_id
                WHERE os.empresa_id = ?
                  AND s.tipo IN ('concluida','entregue')
                  AND os.garantia_ate >= CURDATE()
                  AND os.tipo_servico != 'garantia'";

        $params = [$eid];

        if ($q) {
            $sql .= " AND (os.numero LIKE ? OR c.nome LIKE ? OR eq.marca LIKE ? OR eq.modelo LIKE ? OR eq.numero_serie LIKE ?)";
            $b = "%{$q}%";
            array_push($params, $b, $b, $b, $b, $b);
        }

        $sql .= " ORDER BY os.garantia_ate ASC LIMIT 20";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $this->json($stmt->fetchAll());
    }

    public function abrirGarantia(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();
        $os  = $this->model->findCompleto((int) $id);

        if (!$os) { $this->flash('error', 'OS original não encontrada.'); $this->redirect(url('/os')); }

        // Verificar se está dentro do prazo de garantia
        $garantiaAte = $os['garantia_ate'] ?? null;
        if (!$garantiaAte) {
            $this->flash('error', 'Esta OS não possui garantia registrada.');
            $this->redirect(url('/os/' . $id));
        }

        if (strtotime($garantiaAte) < strtotime('today')) {
            $dias = (int) ceil((strtotime('today') - strtotime($garantiaAte)) / 86400);
            $this->flash('error', "Garantia expirada há {$dias} dia(s). Venceu em " . date('d/m/Y', strtotime($garantiaAte)) . '.');
            $this->redirect(url('/os/' . $id));
        }

        // Buscar status de garantia da empresa (ou criar se não existir)
        $stmtStatus = $db->prepare(
            "SELECT id FROM os_status WHERE empresa_id = ? AND LOWER(nome) LIKE '%garantia%' ORDER BY ordem LIMIT 1"
        );
        $stmtStatus->execute([$eid]);
        $statusInicial = (int) $stmtStatus->fetchColumn();

        if (!$statusInicial) {
            $stmtOrdem = $db->prepare("SELECT COALESCE(MAX(ordem),0)+1 FROM os_status WHERE empresa_id=?");
            $stmtOrdem->execute([$eid]);
            $db->prepare(
                "INSERT INTO os_status (empresa_id, nome, cor, ordem, tipo) VALUES (?, 'Em Garantia', '#dc3545', ?, 'em_andamento')"
            )->execute([$eid, (int) $stmtOrdem->fetchColumn()]);
            $statusInicial = (int) $db->lastInsertId();
        }

        $motivo       = trim($this->post('motivo_retorno', ''));
        $tecnicoId    = $this->post('tecnico_id') ?: $os['tecnico_id'] ?: null;
        $garantiaDias = (int) ($os['garantia_dias'] ?? 90);
        $numero       = $this->model->proximoNumero();

        // Campos editados do equipamento no passo 3
        $estadoEntrada = $this->post('estado_entrada', $os['estado_entrada'] ?? 'regular');
        $acessorios    = $this->post('acessorios', $os['acessorios'] ?? '');
        $obsCliente    = $this->post('observacoes_cliente', '');
        $obsInternas   = $this->post('observacoes_internas', '');

        // Atualizar o equipamento com os dados revisados
        $equipId = $os['equipamento_id'];
        if ($equipId && ($acessorios !== ($os['acessorios'] ?? '') || $estadoEntrada !== ($os['estado_entrada'] ?? ''))) {
            $db->prepare(
                "UPDATE equipamentos SET estado_entrada=?, acessorios=? WHERE id=? AND empresa_id=?"
            )->execute([$estadoEntrada, $acessorios, $equipId, $eid]);
        }

        // Criar nova OS de garantia
        $novaOsId = $this->model->insert([
            'numero'              => $numero,
            'cliente_id'          => $os['cliente_id'],
            'equipamento_id'      => $equipId,
            'status_id'           => $statusInicial,
            'tecnico_id'          => $tecnicoId,
            'recepcionista_id'    => $this->usuarioId(),
            'prioridade'          => 'alta',
            'tipo_servico'        => 'garantia',
            'defeito_relatado'    => $motivo ?: 'Retorno em garantia da OS #' . $os['numero'],
            'os_origem_id'        => (int) $id,
            'motivo_retorno'      => $motivo,
            'garantia_dias'       => $garantiaDias,
            'data_previsao'       => date('Y-m-d\TH:i', strtotime('+5 days')),
            'valor_total'         => 0.00,
            'situacao_pagamento'  => 'pago',
            'observacoes_cliente' => $obsCliente ?: null,
            'observacoes_internas'=> $obsInternas ?: 'Garantia da OS ' . $os['numero'],
        ]);

        $this->model->registrarHistorico($novaOsId, null, $statusInicial,
            'Garantia — retorno da ' . $os['numero']
        );

        $db->prepare(
            "INSERT INTO os_historico (empresa_id, os_id, usuario_id, descricao)
             VALUES (?, ?, ?, ?)"
        )->execute([
            $eid, (int) $id, $this->usuarioId(),
            "Retorno em garantia registrado → nova OS #{$numero}"
        ]);

        // Redireciona para impressão da OS de garantia
        $this->redirect(url("/os/{$novaOsId}/imprimir/garantia"));
    }

    private function statusList(int $eid): array
    {
        $stmt = DB::pdo()->prepare("SELECT * FROM os_status WHERE empresa_id = ? ORDER BY ordem");
        $stmt->execute([$eid]);
        return $stmt->fetchAll();
    }
}
