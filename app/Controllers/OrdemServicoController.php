<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
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

    /** Garante que o tipo de equipamento exista na lista da empresa (sem duplicar, case-insensitive). */
    private function garantirTipoEquip(int $eid, string $nome): void
    {
        $nome = trim($nome);
        if ($nome === '' || mb_strtoupper($nome, 'UTF-8') === 'SELECIONE O TIPO') return;
        $db = DB::pdo();
        $st = $db->prepare("SELECT id FROM equip_tipos WHERE empresa_id = ? AND LOWER(nome) = LOWER(?) LIMIT 1");
        $st->execute([$eid, $nome]);
        if ($st->fetchColumn()) return;
        $db->prepare("INSERT INTO equip_tipos (empresa_id, nome) VALUES (?, ?)")->execute([$eid, $nome]);
    }

    /** Garante que a marca de equipamento exista na lista da empresa (sem duplicar, case-insensitive). */
    private function garantirMarcaEquip(int $eid, string $nome): void
    {
        $nome = trim($nome);
        if ($nome === '' || mb_strtoupper($nome, 'UTF-8') === 'SELECIONE MARCA') return;
        $db = DB::pdo();
        $st = $db->prepare("SELECT id FROM equip_marcas WHERE empresa_id = ? AND LOWER(nome) = LOWER(?) LIMIT 1");
        $st->execute([$eid, $nome]);
        if ($st->fetchColumn()) return;
        $db->prepare("INSERT INTO equip_marcas (empresa_id, nome) VALUES (?, ?)")->execute([$eid, $nome]);
    }

    public function index(): void
    {
        $page    = (int) $this->get('page', 1);
        $filtros = [
            'busca'       => $this->get('busca', ''),
            'status_id'   => $this->get('status_id'),
            'tecnico_id'  => $this->get('tecnico_id'),
            'prioridade'  => $this->get('prioridade', ''),
            'data_inicio' => $this->get('data_inicio'),
            'data_fim'    => $this->get('data_fim'),
            'em_garantia' => $this->get('em_garantia'),
            'fechadas'    => $this->get('fechadas'),
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
            'totalFechadas'=> $this->model->totalFechadas(),
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

        // Limite de OS/mês do plano (dormente se cobrança off).
        $msgLim = limite_plano_atingido($eid, 'os_mes', os_uso_mes($eid));
        if ($msgLim) { $this->flash('error', $msgLim . ' 👉 Veja os planos em Configurações → Planos.'); $this->redirect(url('/os/nova')); }

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

        // tipo/marca lidos/digitados entram na lista da empresa pra reaproveitar (sem repetir)
        $this->garantirTipoEquip($this->empresaId(), $this->post('equip_tipo', ''));
        $this->garantirMarcaEquip($this->empresaId(), $this->post('equip_marca', ''));

        if (!trim($this->post('defeito_relatado', ''))) {
            $this->flash('error', 'Informe o defeito relatado pelo cliente.');
            $this->redirect(url('/os/nova'));
        }

        // Criar/encontrar equipamento
        $equipId = (int) $this->post('equipamento_id');
        if (!$equipId) {
            $stmtEq = $db->prepare(
                "INSERT INTO equipamentos (empresa_id, cliente_id, categoria_id, tipo, marca, modelo, numero_serie, imei, cor, voltagem, estado_entrada, descricao_defeito_cliente, acessorios, senha_desbloqueio, tipo_armazenamento, memoria_ram, placa_video, placa_mae, processador)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmtEq->execute([
                $eid,
                $clienteId,
                $this->post('categoria_id') ?: null,
                $this->post('equip_tipo'),
                $this->post('equip_marca'),
                $this->post('equip_modelo'),
                $this->post('numero_serie'),
                $this->post('imei'),
                $this->post('equip_cor'),
                $this->post('voltagem') ?: null,
                $this->post('estado_entrada', 'regular'),
                $this->post('defeito_relatado'),
                $this->post('acessorios'),
                $this->post('senha_desbloqueio'),
                $this->post('tipo_armazenamento') ?: null,
                $this->post('memoria_ram') ?: null,
                $this->post('placa_video') ?: null,
                $this->post('placa_mae') ?: null,
                $this->post('processador') ?: null,
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

        // Gerar token público único
        $token = bin2hex(random_bytes(16));
        $db->prepare("UPDATE ordens_servico SET token_publico = ? WHERE id = ?")->execute([$token, $osId]);

        log_acao('os', 'criar', $osId, 'OS ' . $numero);
        $avisoLimite = os_checar_limite($this->empresaId());   // limite/crédito do plano (dormente se cobrança off)
        if ($avisoLimite) $this->flash('warning', $avisoLimite);
        $this->flash('success', "OS: {$numero} aberta com sucesso!");
        $this->redirect(url("/os/{$osId}"));
    }

    /**
     * Envia por e-mail (empresa + cliente) as fotos do estado de entrada do equipamento.
     * As fotos NÃO são gravadas no servidor — só transitam em memória para virar anexo.
     */
    public function fotosEntrada(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 400); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $clienteId = (int) $this->post('cliente_id');
        $equip     = trim((string) $this->post('equipamento', ''));
        $numero    = trim((string) $this->post('numero', ''));
        $fotos     = $this->post('fotos', []);

        if (!is_array($fotos) || !$fotos) { $this->json(['error' => 'Nenhuma foto recebida'], 400); }
        $fotos = array_slice($fotos, 0, 6); // limite de segurança

        // Decodifica os data URLs em anexos binários (memória apenas)
        $anexos = [];
        foreach ($fotos as $i => $durl) {
            if (!is_string($durl) || !preg_match('~^data:image/(jpe?g|png|webp);base64,~', $durl)) continue;
            $bin = base64_decode(substr($durl, strpos($durl, ',') + 1), true);
            if ($bin === false || strlen($bin) < 100 || strlen($bin) > 4_000_000) continue;
            $anexos[] = ['filename' => 'estado-entrada-' . ($i + 1) . '.jpg', 'mime' => 'image/jpeg', 'data' => $bin];
        }
        if (!$anexos) { $this->json(['error' => 'Fotos inválidas'], 400); }

        // Destinatários: empresa + cliente
        $stmtE = $db->prepare("SELECT nome_fantasia, email FROM empresas WHERE id = ?");
        $stmtE->execute([$eid]);
        $empresa = $stmtE->fetch() ?: [];

        $destinatarios = [];
        if (!empty($empresa['email']) && filter_var($empresa['email'], FILTER_VALIDATE_EMAIL)) {
            $destinatarios[] = ['email' => $empresa['email'], 'nome' => $empresa['nome_fantasia'] ?? 'Empresa'];
        }

        $clienteNome = '';
        if ($clienteId) {
            $stmtC = $db->prepare("SELECT nome, email FROM clientes WHERE id = ? AND empresa_id = ?");
            $stmtC->execute([$clienteId, $eid]);
            $cli = $stmtC->fetch() ?: [];
            $clienteNome = $cli['nome'] ?? '';
            if (!empty($cli['email']) && filter_var($cli['email'], FILTER_VALIDATE_EMAIL)) {
                $destinatarios[] = ['email' => $cli['email'], 'nome' => $cli['nome'] ?? 'Cliente'];
            }
        }

        if (!$destinatarios) {
            $this->json(['success' => false, 'error' => 'Nenhum e-mail de destino válido (empresa e cliente sem e-mail cadastrado).']);
        }

        $ctx = [
            'empresa'     => ($empresa['nome_fantasia'] ?? '') ?: 'sua assistência técnica',
            'cliente'     => $clienteNome,
            'equipamento' => $equip ?: 'Equipamento',
            'os'          => $numero,
            'data'        => date('d/m/Y H:i'),
        ];

        $enviados = [];
        foreach ($destinatarios as $d) {
            try {
                if (\App\Services\EmailService::fotosEntrada($d['email'], $d['nome'], $ctx, $anexos)) {
                    $enviados[] = $d['email'];
                }
            } catch (\Throwable $e) { /* best-effort */ }
        }

        // Descarta os binários explicitamente (nada persistido)
        unset($anexos, $fotos);

        if ($enviados) {
            $this->json(['success' => true, 'enviados' => $enviados]);
        } else {
            $this->json(['success' => false, 'error' => 'Não foi possível enviar o e-mail agora.']);
        }
    }

    public function ver(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $eid = $this->empresaId();
        $txStmt = DB::pdo()->prepare("SELECT valor FROM configuracoes WHERE empresa_id=? AND chave='taxas_cartao'");
        $txStmt->execute([$eid]);
        $taxasCartao = $txStmt->fetchColumn() ?: '';

        // Taxa de cartão (maquininha) realmente lançada p/ esta OS + receita — p/ o Resumo Financeiro
        $finStmt = DB::pdo()->prepare(
            "SELECT
                COALESCE(SUM(CASE WHEN l.tipo='receita' THEN l.valor END),0) AS receita,
                COALESCE(SUM(CASE WHEN l.tipo='despesa' AND c.nome='Taxas de cartão' THEN l.valor END),0) AS taxa,
                MAX(CASE WHEN l.tipo='despesa' AND c.nome='Taxas de cartão' THEN l.descricao END) AS taxa_desc
             FROM fin_lancamentos l
             LEFT JOIN fin_categorias c ON c.id = l.categoria_id
             WHERE l.os_id = ? AND l.empresa_id = ?"
        );
        $finStmt->execute([(int) $id, $eid]);
        $taxaCartaoOS = $finStmt->fetch(\PDO::FETCH_ASSOC) ?: ['receita' => 0, 'taxa' => 0, 'taxa_desc' => null];

        $this->view('os.show', [
            'titulo'       => 'OS: ' . $os['numero'],
            'os'           => $os,
            'statusList'   => $this->statusList($eid),
            'tecnicos'     => (new Usuario())->tecnicos(),
            'taxasCartao'  => $taxasCartao,
            'taxaCartaoOS' => $taxaCartaoOS,
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

        // Atualizar dados do equipamento (guarda: só se o tipo vier preenchido — evita zerar)
        if (!empty($os['equipamento_id']) && trim($this->post('equip_tipo', '')) !== '') {
            $this->garantirTipoEquip($this->empresaId(), $this->post('equip_tipo', ''));
            $this->garantirMarcaEquip($this->empresaId(), $this->post('equip_marca', ''));
            $db->prepare(
                "UPDATE equipamentos
                    SET tipo=?, marca=?, modelo=?, numero_serie=?, imei=?, cor=?, voltagem=?, estado_entrada=?, acessorios=?, senha_desbloqueio=?,
                        tipo_armazenamento=?, memoria_ram=?, placa_video=?, placa_mae=?, processador=?
                  WHERE id=? AND empresa_id=?"
            )->execute([
                $this->post('equip_tipo'),
                $this->post('equip_marca'),
                $this->post('equip_modelo'),
                $this->post('numero_serie'),
                $this->post('imei'),
                $this->post('equip_cor'),
                $this->post('voltagem') ?: null,
                $this->post('estado_entrada') ?: 'regular',
                $this->post('acessorios'),
                $this->post('senha_desbloqueio'),
                $this->post('tipo_armazenamento') ?: null,
                $this->post('memoria_ram') ?: null,
                $this->post('placa_video') ?: null,
                $this->post('placa_mae') ?: null,
                $this->post('processador') ?: null,
                (int) $os['equipamento_id'],
                $this->empresaId(),
            ]);
        }

        // Registrar histórico se status mudou
        if ($novoStatusId !== (int) $os['status_id']) {
            $this->model->registrarHistorico((int) $id, $os['status_id'], $novoStatusId, 'Atualizado via edição.');
        }

        log_acao('os', 'editar', (int) $id, 'OS ' . ($os['numero'] ?? $id));
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
        $stmt = $db->prepare("SELECT tipo, sem_valor FROM os_status WHERE id = ?");
        $stmt->execute([$novoStatusId]);
        $stRow      = $stmt->fetch();
        $statusTipo = $stRow['tipo'] ?? '';


        if ($statusTipo === 'concluida') $update['data_conclusao'] = date('Y-m-d H:i:s');
        if ($statusTipo === 'entregue')  $update['data_entrega']   = date('Y-m-d H:i:s');

        $this->model->update((int) $id, $update);
        $this->model->registrarHistorico((int) $id, $os['status_id'], $novoStatusId, $descricao);

        log_acao('os', 'status', (int) $id, 'OS ' . ($os['numero'] ?? $id) . ' — ' . $descricao);
        $this->json(['success' => true]);
    }

    /** A OS está num status marcado "não permite valor" (ex.: Orçamento, Em Diagnóstico)? */
    private function statusSemValor(int $osId): bool
    {
        return false; // opção "não permite valor (pré-orçamento)" removida — nunca bloqueia serviço/peça
    }

    public function adicionarServico(string $id): void
    {
        // Regra de negócio: status pré-orçamento (ex.: Orçamento, Em Diagnóstico) não pode ter valor.
        if ($this->statusSemValor((int) $id)) {
            $this->flash('error', 'Este status ainda não permite valor (o orçamento não foi concluído). Mude para um status como Aprovado / Em Reparo antes de adicionar serviços ou peças.');
            $this->redirect(url("/os/{$id}"));
        }
        $eid = $this->empresaId();
        $db  = DB::pdo();
        $qtd = (float) str_replace(',', '.', $this->post('quantidade', 1));
        $val = moeda_float($this->post('valor_unitario', 0));
        $servicoId = (int) $this->post('servico_id');

        if ($servicoId) {
            // Editar existente
            $db->prepare(
                "UPDATE os_servicos SET descricao=?, quantidade=?, valor_unitario=?, valor_total=?, tecnico_id=?
                 WHERE id=? AND os_id=? AND empresa_id=?"
            )->execute([
                $this->post('descricao'), $qtd, $val, $qtd * $val,
                $this->post('tecnico_id') ?: $this->usuarioId(),
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
                $this->post('tecnico_id') ?: $this->usuarioId(),
            ]);
        }

        $totais = $this->model->calcularTotal((int) $id);
        $this->model->update((int) $id, ['valor_total' => $totais['total']]);
        $this->flash('success', $servicoId ? 'Serviço atualizado!' : 'Serviço adicionado!');
        $this->redirect(url("/os/{$id}"));
    }

    public function adicionarPeca(string $id): void
    {
        // Regra de negócio: status pré-orçamento (ex.: Orçamento, Em Diagnóstico) não pode ter valor.
        if ($this->statusSemValor((int) $id)) {
            $this->flash('error', 'Este status ainda não permite valor (o orçamento não foi concluído). Mude para um status como Aprovado / Em Reparo antes de adicionar serviços ou peças.');
            $this->redirect(url("/os/{$id}"));
        }
        $eid     = $this->empresaId();
        $db      = DB::pdo();
        $prodId  = $this->post('produto_id') ?: null;
        $qtd     = (float) str_replace(',', '.', $this->post('quantidade', 1));
        $valUnit = moeda_float($this->post('valor_unitario', 0));
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

    /** Chat interno da equipe, amarrado à OS — lista mensagens (JSON, usado no polling). */
    public function listarMensagens(string $id): void
    {
        $eid = $this->empresaId();
        $uid = $this->usuarioId();
        // Polling frequente: solta a trava de sessão pra não segurar outras requisições.
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
        $db = DB::pdo();

        // Todas as mensagens da OS (render completo — necessário p/ refletir edições/exclusões).
        $stmt = $db->prepare(
            "SELECT m.id, m.mensagem, m.usuario_id, u.nome AS usuario_nome, u.perfil,
                    DATE_FORMAT(m.criado_em, '%d/%m %H:%i') AS quando,
                    (m.editado_em IS NOT NULL) AS editada
             FROM os_mensagens m
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             WHERE m.os_id = ? AND m.empresa_id = ?
             ORDER BY m.id ASC"
        );
        $stmt->execute([(int) $id, $eid]);
        $msgs = $stmt->fetchAll();

        // Read receipt: até que id OUTROS usuários já leram nesta OS.
        $st = $db->prepare("SELECT COALESCE(MAX(ultimo_lido_id),0) FROM os_mensagens_lidas WHERE os_id = ? AND usuario_id <> ?");
        $st->execute([(int) $id, $uid]);
        $lidoOutros = (int) $st->fetchColumn();

        // Marca todas as mensagens desta OS como lidas por MIM (quem está vendo).
        $maxId = 0;
        foreach ($msgs as $m) { $maxId = max($maxId, (int) $m['id']); }
        if ($maxId > 0) {
            $db->prepare(
                "INSERT INTO os_mensagens_lidas (os_id, usuario_id, ultimo_lido_id, atualizado_em)
                 VALUES (?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE ultimo_lido_id = GREATEST(ultimo_lido_id, VALUES(ultimo_lido_id)), atualizado_em = NOW()"
            )->execute([(int) $id, $uid, $maxId]);
        }

        $this->json(['mensagens' => $msgs, 'eu' => $uid, 'lido_outros' => $lidoOutros]);
    }

    /** Chat interno — edita a própria mensagem. */
    public function editarMensagem(string $id, string $msgId): void
    {
        if (!csrf_verify()) { $this->json(['erro' => 'Sessão expirada.'], 403); }
        $eid = $this->empresaId();
        $uid = $this->usuarioId();
        $txt = trim($this->post('mensagem', ''));
        if ($txt === '') { $this->json(['erro' => 'Mensagem vazia.'], 422); }
        $txt = mb_substr($txt, 0, 2000);

        $db = DB::pdo();
        $st = $db->prepare("SELECT usuario_id FROM os_mensagens WHERE id = ? AND os_id = ? AND empresa_id = ?");
        $st->execute([(int) $msgId, (int) $id, $eid]);
        $dono = $st->fetchColumn();
        if ($dono === false) { $this->json(['erro' => 'Mensagem não encontrada.'], 404); }
        if ((int) $dono !== $uid) { $this->json(['erro' => 'Você só pode editar suas próprias mensagens.'], 403); }

        $db->prepare("UPDATE os_mensagens SET mensagem = ?, editado_em = NOW() WHERE id = ?")
           ->execute([$txt, (int) $msgId]);
        $this->json(['ok' => true]);
    }

    /** Chat interno — apaga uma mensagem (autor, ou admin da empresa). */
    public function excluirMensagem(string $id, string $msgId): void
    {
        if (!csrf_verify()) { $this->json(['erro' => 'Sessão expirada.'], 403); }
        $eid = $this->empresaId();
        $uid = $this->usuarioId();

        $db = DB::pdo();
        $st = $db->prepare("SELECT usuario_id FROM os_mensagens WHERE id = ? AND os_id = ? AND empresa_id = ?");
        $st->execute([(int) $msgId, (int) $id, $eid]);
        $dono = $st->fetchColumn();
        if ($dono === false) { $this->json(['erro' => 'Mensagem não encontrada.'], 404); }
        if ((int) $dono !== $uid && !Auth::isAdmin()) { $this->json(['erro' => 'Sem permissão para apagar esta mensagem.'], 403); }

        $db->prepare("DELETE FROM os_mensagens WHERE id = ?")->execute([(int) $msgId]);
        $this->json(['ok' => true]);
    }

    /** Chat interno — envia uma mensagem na OS + notifica a equipe. */
    public function enviarMensagem(string $id): void
    {
        if (!csrf_verify()) { $this->json(['erro' => 'Sessão expirada.'], 403); }
        $eid = $this->empresaId();
        // Chat desativado para a empresa? bloqueia o envio.
        $cfg = DB::pdo()->prepare("SELECT valor FROM configuracoes WHERE empresa_id = ? AND chave = 'chat_habilitado' LIMIT 1");
        $cfg->execute([$eid]);
        $vc = $cfg->fetchColumn();
        if ($vc !== false && $vc !== null && (int) $vc === 0) { $this->json(['erro' => 'O chat está desativado.'], 403); }
        $os  = $this->model->find((int) $id);
        if (!$os) { $this->json(['erro' => 'OS não encontrada.'], 404); }

        $txt = trim($this->post('mensagem', ''));
        if ($txt === '') { $this->json(['erro' => 'Mensagem vazia.'], 422); }
        $txt = mb_substr($txt, 0, 2000);

        DB::pdo()->prepare("INSERT INTO os_mensagens (empresa_id, os_id, usuario_id, mensagem) VALUES (?,?,?,?)")
            ->execute([$eid, (int) $id, $this->usuarioId(), $txt]);
        // O aviso à equipe é feito pelo sino de chat dedicado no topo (NotificacaoController@chatStatus).

        $this->json(['ok' => true]);
    }

    /** Edita a garantia (dias) direto na tela da OS — recalcula a validade se a OS já foi concluída. */
    public function atualizarGarantia(string $id): void
    {
        if (!csrf_verify()) { $this->json(['erro' => 'Sessão expirada.'], 403); }
        $eid = $this->empresaId();
        $os  = $this->model->find((int) $id);
        if (!$os) { $this->json(['erro' => 'OS não encontrada.'], 404); }

        $dias = max(0, min(3650, (int) $this->post('garantia_dias', 0)));
        $dados = ['garantia_dias' => $dias];
        if (!empty($os['data_conclusao'])) {
            $dados['garantia_ate'] = date('Y-m-d', strtotime($os['data_conclusao'] . " +{$dias} days"));
        }
        $this->model->update((int) $id, $dados);
        $this->json(['ok' => true, 'dias' => $dias, 'ate' => $dados['garantia_ate'] ?? null]);
    }

    public function acompanhar(string $token): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("
            SELECT os.*,
                   c.nome AS cliente_nome, c.telefone AS cliente_tel, c.whatsapp AS cliente_whats,
                   e.tipo AS equip_tipo, e.marca AS equip_marca, e.modelo AS equip_modelo,
                   e.numero_serie, e.cor AS equip_cor, e.estado_entrada,
                   s.nome AS status_nome, s.cor AS status_cor, s.tipo AS status_tipo,
                   t.nome AS tecnico_nome,
                   emp.nome_fantasia AS empresa_nome, emp.telefone AS empresa_tel,
                   emp.whatsapp AS empresa_whats, emp.logo AS empresa_logo,
                   emp.slug AS empresa_slug, emp.listagem_publica AS empresa_listada,
                   emp.logradouro, emp.numero AS emp_numero, emp.bairro, emp.cidade, emp.uf
            FROM ordens_servico os
            JOIN clientes c ON c.id = os.cliente_id
            JOIN equipamentos e ON e.id = os.equipamento_id
            JOIN os_status s ON s.id = os.status_id
            JOIN empresas emp ON emp.id = os.empresa_id
            LEFT JOIN usuarios t ON t.id = os.tecnico_id
            WHERE os.token_publico = ?
        ");
        $stmt->execute([$token]);
        $os = $stmt->fetch();

        if (!$os) {
            http_response_code(404);
            echo '<!DOCTYPE html><html><body style="font-family:sans-serif;text-align:center;padding:4rem"><h2>OS não encontrada</h2><p>Link inválido ou expirado.</p></body></html>';
            exit;
        }

        // Histórico
        $hist = $db->prepare("SELECT h.*, u.nome AS usuario_nome FROM os_historico h LEFT JOIN usuarios u ON u.id = h.usuario_id WHERE h.os_id = ? ORDER BY h.criado_em ASC");
        $hist->execute([$os['id']]);
        $historico = $hist->fetchAll();

        // Serviços
        $svcs = $db->prepare("SELECT * FROM os_servicos WHERE os_id = ? AND empresa_id = ?");
        $svcs->execute([$os['id'], $os['empresa_id']]);
        $servicos = $svcs->fetchAll();

        // Peças
        $pcs = $db->prepare("SELECT * FROM os_pecas WHERE os_id = ? AND empresa_id = ?");
        $pcs->execute([$os['id'], $os['empresa_id']]);
        $pecas = $pcs->fetchAll();

        // Avaliação verificada atrelada a esta OS (existe? / pode avaliar?)
        $av = $db->prepare("SELECT nota, comentario, resposta, criado_em FROM diretorio_avaliacoes WHERE os_id = ? LIMIT 1");
        $av->execute([$os['id']]);
        $avaliacaoOs = $av->fetch() ?: null;
        $podeAvaliar = in_array($os['status_tipo'] ?? '', ['entregue', 'concluida'], true) && !empty($os['empresa_listada']);

        $this->view('os.acompanhar', compact('os','historico','servicos','pecas','avaliacaoOs','podeAvaliar'), 'landing');
    }

    /** Avaliação VERIFICADA: sai da página pública da OS (token) e vira crítica no diretório. */
    public function avaliarOs(string $token): void
    {
        $db   = DB::pdo();
        $back = url('/os/acompanhar/' . $token) . '#avaliar';

        if (!csrf_verify()) {
            $this->flash('error', 'Sessão expirada. Recarregue a página e tente novamente.');
            $this->redirect($back);
        }

        $stmt = $db->prepare("
            SELECT os.id, os.empresa_id, c.nome AS cliente_nome, s.tipo AS status_tipo, emp.listagem_publica
            FROM ordens_servico os
            JOIN clientes c ON c.id = os.cliente_id
            JOIN os_status s ON s.id = os.status_id
            JOIN empresas emp ON emp.id = os.empresa_id
            WHERE os.token_publico = ? LIMIT 1
        ");
        $stmt->execute([$token]);
        $os = $stmt->fetch();

        if (!$os) { http_response_code(404); echo 'OS não encontrada.'; return; }

        if (empty($os['listagem_publica'])) {
            $this->flash('error', 'Esta empresa não está no diretório público.');
            $this->redirect($back);
        }
        if (!in_array($os['status_tipo'], ['entregue', 'concluida'], true)) {
            $this->flash('error', 'A avaliação fica disponível quando o serviço é concluído.');
            $this->redirect($back);
        }

        // Uma avaliação por OS
        $ja = $db->prepare("SELECT id FROM diretorio_avaliacoes WHERE os_id = ? LIMIT 1");
        $ja->execute([$os['id']]);
        if ($ja->fetch()) {
            $this->flash('error', 'Esta OS já foi avaliada. Obrigado!');
            $this->redirect($back);
        }

        $nota   = (int) $this->post('nota', 0);
        $coment = trim($this->post('comentario', ''));
        if ($nota < 1 || $nota > 5) {
            $this->flash('error', 'Selecione uma nota de 1 a 5 estrelas.');
            $this->redirect($back);
        }

        // Verificada => publicada automaticamente (aprovado=1, verificada=1)
        $db->prepare("INSERT INTO diretorio_avaliacoes (empresa_id, os_id, nome, nota, comentario, aprovado, verificada, situacao)
                      VALUES (?,?,?,?,?,1,1,'publicada')")
           ->execute([$os['empresa_id'], $os['id'], $os['cliente_nome'], $nota, $coment ?: null]);

        $this->flash('success', 'Avaliação enviada! Obrigado por avaliar o atendimento. 💚');
        $this->redirect($back);
    }

    public function imprimir(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $eid    = $this->empresaId();
        $db     = DB::pdo();
        $stmtCfg = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ? AND chave IN ('texto_entrada_equipamento','texto_garantia')");
        $stmtCfg->execute([$eid]);
        $configs = [];
        foreach ($stmtCfg->fetchAll() as $r) $configs[$r['chave']] = $r['valor'];

        $this->saidaImpressao($this->renderView('os.print', ['os' => $os, 'configs' => $configs], 'print'), 'os-' . $os['numero']);
    }

    /** Etiqueta interna da OS (via da empresa + etiquetas de recortar) — impressão avulsa. */
    public function imprimirEtiqueta(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }
        $this->saidaImpressao($this->renderView('os.print', ['os' => $os], 'print_etiqueta'), 'etiqueta-os-' . $os['numero']);
    }

    public function imprimirOrcamento(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }
        $this->saidaImpressao($this->renderView('os.print', ['os' => $os, 'configs' => []], 'print_orcamento'), 'orcamento-os-' . $os['numero']);
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

        $this->saidaImpressao($this->renderView('os.print_garantia', ['os' => $os, 'osOrigem' => $osOrigem], 'print_garantia'), 'garantia-os-' . $os['numero']);
    }

    public function imprimirFechamento(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $stmt = DB::pdo()->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ? AND chave = 'texto_garantia'");
        $stmt->execute([$this->empresaId()]);
        $configs = [];
        foreach ($stmt->fetchAll() as $r) $configs[$r['chave']] = $r['valor'];

        $this->saidaImpressao($this->renderView('os.print_fechamento', ['os' => $os, 'configs' => $configs], 'print_fechamento'), 'comprovante-os-' . $os['numero']);
    }

    /** Saída da impressão: HTML normal, ou PDF (Dompdf) se ?pdf=1. */
    private function saidaImpressao(string $html, string $baseFilename): void
    {
        if ($this->get('pdf')) {
            $pdf = \App\Services\PdfService::fromHtml($html);
            if ($pdf !== null) {
                $fn = preg_replace('/[^A-Za-z0-9\-]/', '-', $baseFilename) . '.pdf';
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $fn . '"');
                header('Content-Length: ' . strlen($pdf));
                echo $pdf;
                exit;
            }
        }
        echo $html;
        exit;
    }

    /** Gera o PDF de uma impressão e envia ao WhatsApp do cliente pela Evolution. */
    public function enviarPdfWhatsapp(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 400); }

        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->json(['error' => 'OS não encontrada'], 404); }

        $tipo = $this->post('tipo', 'orcamento');
        $eid  = $this->empresaId();
        $db   = DB::pdo();

        $stmtCfg = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ?");
        $stmtCfg->execute([$eid]);
        $configs = [];
        foreach ($stmtCfg->fetchAll() as $r) $configs[$r['chave']] = $r['valor'];

        // tipo => [view, layout, rótulo]
        $map = [
            'abertura'   => ['os.print',            'print_wa_entrada', 'Comprovante de entrada'],
            'orcamento'  => ['os.print',            'print_orcamento',  'Orçamento'],
            'fechamento' => ['os.print_fechamento', 'print_fechamento', 'Comprovante'],
            'garantia'   => ['os.print_garantia',   'print_garantia',   'Comprovante de garantia'],
        ];
        if (!isset($map[$tipo])) { $this->json(['error' => 'Tipo inválido'], 400); }
        [$view, $layout, $rotulo] = $map[$tipo];

        $dados = ['os' => $os, 'configs' => $configs];
        if ($tipo === 'garantia') {
            $dados['osOrigem'] = !empty($os['os_origem_id']) ? $this->model->findCompleto((int) $os['os_origem_id']) : null;
        }
        $html = $this->renderView($view, $dados, $layout);
        $pdf  = \App\Services\PdfService::fromHtml($html);
        if ($pdf === null) { $this->json(['success' => false, 'error' => 'Falha ao gerar o PDF.']); }

        $whats = only_numbers(($os['cliente_whats'] ?? '') ?: ($os['cliente_tel'] ?? ''));
        if (!$whats) { $this->json(['success' => false, 'error' => 'Cliente sem WhatsApp/telefone cadastrado.']); }

        if (\App\Services\WhatsAppService::statusEmpresa($eid) !== 'open') {
            $this->json(['success' => false, 'error' => 'O WhatsApp da sua empresa não está conectado. Conecte em Configurações → WhatsApp para enviar do seu número.']);
        }

        $fileName = preg_replace('/[^A-Za-z0-9\-]/', '-', strtolower($rotulo) . '-os-' . $os['numero']) . '.pdf';
        $recado   = trim((string) ($os['recado_cliente'] ?? ''));
        $caption  = ($recado !== '' ? $recado . "\n\n" : '')
                  . "{$rotulo} — OS {$os['numero']}";

        $ok = \App\Services\WhatsAppService::enviarDocumento($eid, $whats, base64_encode($pdf), $fileName, $caption);
        $this->json($ok ? ['success' => true] : ['success' => false, 'error' => 'Falha no envio pelo WhatsApp.']);
    }

    /** Envia o LINK de acompanhamento como mensagem de texto via API (Evolution), do número da empresa. */
    public function enviarLinkWhatsapp(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 400); }

        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->json(['error' => 'OS não encontrada'], 404); }
        if (empty($os['token_publico'])) { $this->json(['success' => false, 'error' => 'Esta OS não tem link de acompanhamento.']); }

        $eid   = $this->empresaId();
        $whats = only_numbers(($os['cliente_whats'] ?? '') ?: ($os['cliente_tel'] ?? ''));
        if (!$whats) { $this->json(['success' => false, 'error' => 'Cliente sem WhatsApp/telefone cadastrado.']); }

        if (\App\Services\WhatsAppService::statusEmpresa($eid) !== 'open') {
            $this->json(['success' => false, 'error' => 'O WhatsApp da sua empresa não está conectado. Conecte em Configurações → WhatsApp para enviar do seu número.']);
        }

        $appCfg = require BASE_PATH . '/config/app.php';
        $link   = rtrim($appCfg['url'], '/') . '/os/acompanhar/' . $os['token_publico'];
        $nome   = ($os['cliente_contato'] ?? '') ?: explode(' ', trim($os['cliente_nome'] ?? ''))[0];
        $recado = trim((string) ($os['recado_cliente'] ?? ''));
        $msg    = ($recado !== '' ? $recado . "\n\n" : '')
                . "Olá {$nome}! Acompanhe o andamento da sua OS {$os['numero']} aqui:\n"
                . $link;

        $ok = \App\Services\WhatsAppService::enviarTexto($eid, $whats, $msg);
        $this->json($ok ? ['success' => true] : ['success' => false, 'error' => 'Falha no envio pelo WhatsApp.']);
    }

    /** Salva o recado ao cliente (vai junto nos envios de PDF/link). */
    public function salvarRecado(string $id): void
    {
        if (!csrf_verify()) { $this->json(['success' => false, 'error' => 'Token inválido'], 400); }
        $recado = mb_substr(trim((string) $this->post('recado', '')), 0, 600);
        DB::pdo()->prepare("UPDATE ordens_servico SET recado_cliente = ? WHERE id = ? AND empresa_id = ?")
            ->execute([$recado !== '' ? $recado : null, (int) $id, $this->empresaId()]);
        $this->json(['success' => true]);
    }

    /** Salva e envia o recado como mensagem de texto no WhatsApp do cliente. */
    public function enviarRecadoWhatsapp(string $id): void
    {
        if (!csrf_verify()) { $this->json(['success' => false, 'error' => 'Token inválido'], 400); }
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->json(['success' => false, 'error' => 'OS não encontrada'], 404); }

        $eid    = $this->empresaId();
        $recado = mb_substr(trim((string) $this->post('recado', '')), 0, 600);

        // persiste o recado (também acompanha os PDFs enviados)
        DB::pdo()->prepare("UPDATE ordens_servico SET recado_cliente = ? WHERE id = ? AND empresa_id = ?")
            ->execute([$recado !== '' ? $recado : null, (int) $id, $eid]);

        $whats = only_numbers(($os['cliente_whats'] ?? '') ?: ($os['cliente_tel'] ?? ''));
        if (!$whats) { $this->json(['success' => false, 'error' => 'Cliente sem WhatsApp/telefone cadastrado.']); }
        if (\App\Services\WhatsAppService::statusEmpresa($eid) !== 'open') {
            $this->json(['success' => false, 'error' => 'O WhatsApp da sua empresa não está conectado. Conecte em Configurações → WhatsApp.']);
        }

        $appCfg = require BASE_PATH . '/config/app.php';
        $link   = !empty($os['token_publico'])
                ? rtrim($appCfg['url'], '/') . '/os/acompanhar/' . $os['token_publico']
                : '';
        if ($recado === '' && $link === '') {
            $this->json(['success' => false, 'error' => 'Escreva uma mensagem (esta OS não tem link de acompanhamento).']);
        }

        // Mensagem + link JUNTOS, num envio só.
        $nome = ($os['cliente_contato'] ?? '') ?: explode(' ', trim($os['cliente_nome'] ?? ''))[0];
        $msg  = "Olá {$nome}!";
        if ($recado !== '') $msg .= "\n\n{$recado}";
        if ($link !== '')   $msg .= "\n\nAcompanhe o andamento da sua OS {$os['numero']} aqui:\n{$link}";

        $ok = \App\Services\WhatsAppService::enviarTexto($eid, $whats, $msg);
        $this->json($ok ? ['success' => true] : ['success' => false, 'error' => 'Falha no envio pelo WhatsApp.']);
    }

    public function fechar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();
        $os  = $this->model->find((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        // É um fechamento "Sem Conserto/Recusado"? (status atual é do tipo cancelada)
        // Isso só controla se lança receita no financeiro — a OS sempre vai para "Fechado" (pedido do dono).
        $stmtCur = $db->prepare("SELECT tipo, nome FROM os_status WHERE id = ? AND empresa_id = ?");
        $stmtCur->execute([(int) $os['status_id'], $eid]);
        $cur           = $stmtCur->fetch();
        $ehSemConserto = $cur && $cur['tipo'] === 'cancelada';

        // Fechar OS = sempre leva ao status "Fechado" (tipo entregue), tenha ou não valor: badge vira
        // "Fechado" e a tela passa a mostrar "Reabrir OS" no lugar de "Fechar OS" (ver $jaEntregue na view).
        // Prefere o nativo "Fechado" (codigo, imune a reordenacao/custom); senao 1o 'entregue' por ordem.
        $stmtStatus = $db->prepare(
            "SELECT id FROM os_status WHERE empresa_id = ? AND (codigo = 'fechado' OR tipo = 'entregue')
             ORDER BY (codigo = 'fechado') DESC, ordem LIMIT 1"
        );
        $stmtStatus->execute([$eid]);
        $statusFechado = (int) $stmtStatus->fetchColumn();

        // Fallback: se (por algum motivo) não houver status 'entregue', usa o 'concluida'
        if (!$statusFechado) {
            $stmtFb = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? AND tipo = 'concluida' ORDER BY ordem LIMIT 1");
            $stmtFb->execute([$eid]);
            $statusFechado = (int) $stmtFb->fetchColumn();
        }

        if (!$statusFechado) {
            $this->flash('error', 'Nenhum status do tipo "Fechado/Entregue" cadastrado. Crie um em Config → Status de OS.');
            $this->redirect(url('/os/' . $id));
        }

        $garantiaDias  = (int) $this->post('garantia_dias', $os['garantia_dias'] ?? 90);
        $solucao       = $this->post('solucao_aplicada', '');
        $laudo         = $this->post('laudo_tecnico', '');
        $obsCliente    = $this->post('observacoes_cliente', '');
        $formaPagto    = $this->post('forma_pagamento', '');
        $valorPago     = moeda_float($this->post('valor_pago', 0));

        // Desconto
        $descontoRaw  = moeda_float($this->post('desconto_valor', 0));
        $descontoTipo = $this->post('desconto_tipo', 'valor');
        $descontoValor = $descontoTipo === 'percentual'
            ? round($os['valor_total'] * $descontoRaw / 100, 2)
            : $descontoRaw;
        $descontoValor = min($descontoValor, $os['valor_total']);
        $totalFinal    = max(0, $os['valor_total'] - $descontoValor);

        // Pagamento dividido (múltiplas formas) — mesma UX do PDV
        $formasOsOk = ['dinheiro', 'pix', 'cartao_credito', 'cartao_debito', 'transferencia', 'boleto'];
        $pagamentosRaw = json_decode((string) $this->post('pagamentos', '[]'), true);
        $pagamentos = [];
        if (is_array($pagamentosRaw)) {
            foreach ($pagamentosRaw as $p) {
                $f = $p['forma'] ?? '';
                if (!in_array($f, $formasOsOk, true)) continue;
                $v = (float) str_replace(',', '.', (string) ($p['valor'] ?? 0));
                if ($v <= 0) continue;
                $parc = max(1, min(24, (int) ($p['parcelas'] ?? 1)));
                $tx   = max(0.0, min(100.0, (float) str_replace(',', '.', (string) ($p['taxa'] ?? 0))));
                $pagamentos[] = ['forma' => $f, 'valor' => round($v, 2), 'parcelas' => $f === 'cartao_credito' ? $parc : 1, 'taxa' => $tx];
            }
        }
        $ehSplit = (bool) $pagamentos;

        $cartaoRepassar = $this->post('cartao_repassar') === '1';
        if (!$pagamentos) {
            $pagamentos[] = [
                'forma'    => $formaPagto,
                'valor'    => $totalFinal,
                'parcelas' => max(1, (int) $this->post('cartao_parcelas', 1)),
                'taxa'     => max(0.0, min(100.0, (float) str_replace(',', '.', (string) $this->post('cartao_taxa', 0)))),
            ];
        }

        $linhasCalc   = [];
        $valorCobrado = 0.0;
        $taxaValor    = 0.0;
        foreach ($pagamentos as $p) {
            $ehCartaoL   = in_array($p['forma'], ['cartao_credito', 'cartao_debito'], true);
            $taxaAplicaL = $ehCartaoL && $p['taxa'] > 0 && $p['valor'] > 0;
            $cobradoL    = ($taxaAplicaL && $cartaoRepassar) ? round($p['valor'] / (1 - $p['taxa'] / 100), 2) : $p['valor'];
            $taxaValorL  = $taxaAplicaL ? round($cobradoL * $p['taxa'] / 100, 2) : 0.0;
            $valorCobrado += $cobradoL;
            $taxaValor    += $taxaValorL;
            $linhasCalc[]  = $p + ['valor_cobrado' => $cobradoL, 'taxa_valor' => $taxaValorL];
        }
        if ($ehSplit) {
            $valorPago  = array_sum(array_column($pagamentos, 'valor'));
            $formaPagto = count($pagamentos) > 1 ? 'misto' : $pagamentos[0]['forma'];
        }

        $dataConclusao = date('Y-m-d H:i:s');
        $garantiaAte   = date('Y-m-d', strtotime("+{$garantiaDias} days"));

        $update = [
            'status_id'          => $statusFechado,
            'data_conclusao'     => $os['data_conclusao'] ?: $dataConclusao,
            // Sem Conserto/Recusado não tem garantia (nada foi consertado/entregue) — some o botão "Abrir Garantia".
            'garantia_dias'      => $ehSemConserto ? null : $garantiaDias,
            'garantia_ate'       => $ehSemConserto ? null : $garantiaAte,
            'solucao_aplicada'   => $solucao ?: null,
            'laudo_tecnico'      => $laudo ?: null,
            'observacoes_cliente'=> $obsCliente ?: $os['observacoes_cliente'],
            'desconto_valor'     => $descontoValor > 0 ? $descontoValor : null,
            'desconto_percentual'=> $descontoTipo === 'percentual' ? $descontoRaw : null,
            'valor_total'        => $totalFinal,
            'situacao_pagamento' => $ehSemConserto
                ? 'pendente'
                : ($valorPago >= $totalFinal && $totalFinal > 0 ? 'pago' : ($valorPago > 0 ? 'parcial' : $os['situacao_pagamento'])),
            // Marca que este fechamento não gerou receita (Sem Conserto/Recusado) — a view usa isso
            // pra mostrar "Sem Débito" em vermelho em vez de "Pago", mesmo que o valor_total exista
            // (fica só de referência, caso o cliente volte com o mesmo orçamento).
            'fechada_sem_receita'=> $ehSemConserto ? 1 : 0,
        ];

        // Só carimba entrega quando fecha de verdade (entregue) — não no "Sem Conserto".
        if (!$ehSemConserto) {
            $update['data_entrega'] = $dataConclusao;
        }

        if ($ehSemConserto) {
            // Nada foi pago de fato — zera qualquer valor/forma de pagamento vindo do formulário.
            $update['valor_pago']                 = 0;
            $update['forma_pagamento_fechamento'] = null;
        } elseif ($valorPago > 0) {
            $update['valor_pago']                 = $valorPago;
            $update['forma_pagamento_fechamento'] = $formaPagto;
        }

        $this->model->update((int) $id, $update);
        $this->model->registrarHistorico((int) $id, $os['status_id'], $statusFechado,
            $ehSemConserto
                ? 'OS fechada como "' . ($cur['nome'] ?? 'Sem Conserto') . '" — sem receita.'
                : 'OS fechada. Garantia até ' . date('d/m/Y', strtotime($garantiaAte)) . '.'
        );

        // Lançar no financeiro ao fechar OS — nunca para "Sem Conserto" (sem receita)
        if (!$ehSemConserto && $totalFinal > 0) {
            $stmtConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY id LIMIT 1");
            $stmtConta->execute([$eid]);
            $contaId = $stmtConta->fetchColumn() ?: null;

            // Verificar se já existe lançamento dessa OS para não duplicar
            $jaLancado = $db->prepare("SELECT COUNT(*) FROM fin_lancamentos WHERE os_id = ? AND empresa_id = ? AND tipo = 'receita'");
            $jaLancado->execute([(int)$id, $eid]);

            if (!$jaLancado->fetchColumn()) {
                $statusFin  = $valorPago >= $totalFinal ? 'pago' : 'pendente';
                $dtPagto    = $valorPago > 0 ? date('Y-m-d') : null;

                $stmtInfo = $db->prepare(
                    "SELECT c.nome AS cliente_nome, eq.marca, eq.modelo
                     FROM ordens_servico os
                     JOIN clientes c ON c.id = os.cliente_id
                     LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
                     WHERE os.id = ? AND os.empresa_id = ?"
                );
                $stmtInfo->execute([(int) $id, $eid]);
                $info      = $stmtInfo->fetch() ?: [];
                $equipDesc = trim(($info['marca'] ?? '') . ' ' . ($info['modelo'] ?? ''));
                $descricao = 'OS ' . $os['numero'] . ' — ' . implode(' — ', array_filter([$equipDesc, $info['cliente_nome'] ?? '']));

                $db->prepare(
                    "INSERT INTO fin_lancamentos
                     (empresa_id, conta_id, os_id, cliente_id, usuario_id, tipo, descricao,
                      valor, data_vencimento, data_pagamento, status, forma_pagamento)
                     VALUES (?, ?, ?, ?, ?, 'receita', ?, ?, CURDATE(), ?, ?, ?)"
                )->execute([
                    $eid, $contaId, (int)$id, $os['cliente_id'], $this->usuarioId(),
                    $descricao, $valorCobrado, $dtPagto, $statusFin,
                    $formaPagto ?: null,
                ]);

                foreach ($linhasCalc as $l) {
                    $db->prepare(
                        "INSERT INTO os_pagamentos
                         (empresa_id, os_id, forma_pagamento, valor, parcelas, taxa_percentual, taxa_valor, valor_cobrado)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
                    )->execute([
                        $eid, (int) $id, $l['forma'], $l['valor'], $l['parcelas'], $l['taxa'], $l['taxa_valor'], $l['valor_cobrado'],
                    ]);
                }

                // Despesa: taxa do cartão — uma linha por forma de cartão com taxa (a empresa vê quanto pagou; líquido correto no caixa)
                $linhasComTaxa = array_filter($linhasCalc, function ($l) { return $l['taxa_valor'] > 0; });
                if ($linhasComTaxa) {
                    $catStmt = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='despesa' AND nome='Taxas de cartão' LIMIT 1");
                    $catStmt->execute([$eid]);
                    $catTaxa = $catStmt->fetchColumn();
                    if (!$catTaxa) {
                        $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'despesa', 'Taxas de cartão', '#dc3545')")->execute([$eid]);
                        $catTaxa = (int) $db->lastInsertId();
                    }
                    foreach ($linhasComTaxa as $l) {
                        $qualCart = $l['forma'] === 'cartao_debito' ? 'débito' : $l['parcelas'] . 'x';
                        $descTaxa = 'Taxa cartão — OS ' . $os['numero'] . ' (' . $qualCart . ' · ' . number_format($l['taxa'], 2, ',', '.') . '%)';
                        $db->prepare(
                            "INSERT INTO fin_lancamentos
                             (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao,
                              valor, data_vencimento, data_pagamento, status, forma_pagamento)
                             VALUES (?, ?, ?, ?, ?, ?, 'despesa', ?, ?, CURDATE(), ?, ?, ?)"
                        )->execute([
                            $eid, $contaId, $catTaxa, (int)$id, $os['cliente_id'], $this->usuarioId(),
                            $descTaxa, $l['taxa_valor'], $dtPagto, $statusFin, $l['forma'],
                        ]);
                    }
                }
            }
        }

        log_acao('os', $ehSemConserto ? 'fechar_sem_conserto' : 'fechar', (int) $id, 'OS ' . ($os['numero'] ?? $id) . ' — ' . money((float) $totalFinal));
        $this->flash('success', 'OS fechada com sucesso! Garantia até ' . date('d/m/Y', strtotime($garantiaAte)) . '.');
        $this->redirect(url('/os/' . $id . '/imprimir/fechamento'));
    }

    public function reabrir(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();
        $os  = $this->model->find((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        // Buscar o status anterior ao fechamento, pelo histórico
        $stmtHist = $db->prepare(
            "SELECT status_anterior_id FROM os_historico
             WHERE os_id = ? AND empresa_id = ? AND status_novo_id = ?
             ORDER BY criado_em DESC LIMIT 1"
        );
        $stmtHist->execute([(int) $id, $eid, $os['status_id']]);
        $statusAnterior = $stmtHist->fetchColumn();

        // #4: o status anterior pode ter sido excluido depois — valida antes de usar
        if ($statusAnterior) {
            $chk = $db->prepare("SELECT 1 FROM os_status WHERE id = ? AND empresa_id = ?");
            $chk->execute([(int) $statusAnterior, $eid]);
            if (!$chk->fetchColumn()) $statusAnterior = null;
        }

        if (!$statusAnterior) {
            $stmtFallback = $db->prepare(
                "SELECT id FROM os_status WHERE empresa_id = ? AND tipo = 'em_andamento' ORDER BY ordem LIMIT 1"
            );
            $stmtFallback->execute([$eid]);
            $statusAnterior = $stmtFallback->fetchColumn();
        }

        if (!$statusAnterior) {
            $stmtFallback2 = $db->prepare(
                "SELECT id FROM os_status WHERE empresa_id = ? AND tipo = 'aberta' ORDER BY ordem LIMIT 1"
            );
            $stmtFallback2->execute([$eid]);
            $statusAnterior = $stmtFallback2->fetchColumn();
        }

        if (!$statusAnterior) {
            $this->flash('error', 'Não foi possível determinar um status para reabrir. Verifique os status cadastrados.');
            $this->redirect(url('/os/' . $id));
        }

        $this->model->update((int) $id, [
            'status_id'                  => (int) $statusAnterior,
            'data_conclusao'             => null,
            'data_entrega'               => null,
            'situacao_pagamento'         => 'pendente',
            'valor_pago'                 => 0,
            'forma_pagamento_fechamento' => null,
            'fechada_sem_receita'        => 0,
            // A OS não está mais entregue — a garantia só volta a valer quando fechar de novo.
            'garantia_dias'              => null,
            'garantia_ate'               => null,
        ]);

        // Estorno do caixa: a OS voltou a ser "em andamento" — pode não dar certo e o dinheiro
        // ser devolvido, então a receita (e a taxa de cartão) sai do fluxo. Se a OS for
        // refechada, o fechar() relança com o valor ATUAL, pois a trava anti-duplicação
        // (jaLancado) volta a zero ao removermos os lançamentos desta OS.
        $estorno = $db->prepare("DELETE FROM fin_lancamentos WHERE os_id = ? AND empresa_id = ?");
        $estorno->execute([(int) $id, $eid]);
        $estornados = $estorno->rowCount();

        $this->model->registrarHistorico((int) $id, $os['status_id'], (int) $statusAnterior,
            $estornados > 0 ? 'OS reaberta — receita estornada do caixa.' : 'OS reaberta.');

        log_acao('os', 'reabrir', (int) $id, 'OS ' . ($os['numero'] ?? $id));
        $this->flash('success', $estornados > 0
            ? 'OS reaberta. A receita saiu do caixa — volta ao refechar a OS.'
            : 'OS reaberta com sucesso!');
        $this->redirect(url('/os/' . $id));
    }

    public function buscarFechadas(): void
    {
        $eid = $this->empresaId();
        $q   = trim($this->get('q', ''));
        $db  = DB::pdo();

        $sql = "SELECT os.id, os.numero, os.data_conclusao, os.data_entrega, os.valor_total,
                       c.nome AS cliente_nome,
                       COALESCE(eq.tipo,'') AS equip_tipo,
                       COALESCE(eq.marca,'') AS equip_marca,
                       COALESCE(eq.modelo,'') AS equip_modelo,
                       s.nome AS status_nome
                FROM ordens_servico os
                JOIN os_status s   ON s.id  = os.status_id
                JOIN clientes c    ON c.id  = os.cliente_id
                LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
                WHERE os.empresa_id = ?
                  AND s.tipo IN ('concluida','entregue')";

        $params = [$eid];

        if ($q) {
            $b    = "%{$q}%";
            $bNum = "%" . preg_replace('/\D/', '', $q) . "%";
            $bN   = strlen($bNum) > 2 ? $bNum : $b;
            $sql .= " AND (
              os.numero  LIKE ?
              OR c.nome  LIKE ?
              OR c.telefone LIKE ?
              OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.telefone,'(',''),')',''),'-',''),' ',''),'+','') LIKE ?
              OR eq.marca  LIKE ?
              OR eq.modelo LIKE ?
            )";
            array_push($params, $b, $b, $b, $bN, $b, $b);
        }

        $sql .= " ORDER BY COALESCE(os.data_entrega, os.data_conclusao) DESC LIMIT 50";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $this->json($stmt->fetchAll());
    }

    public function buscarEmGarantia(): void
    {
        $eid  = $this->empresaId();
        $q    = trim($this->get('q', ''));
        $db   = DB::pdo();

        // Garantia só conta a partir do fechamento (data_conclusao) — nunca da criação da OS.
        $dataGarantiaCalc = "DATE_ADD(os.data_conclusao, INTERVAL os.garantia_dias DAY)";
        $sql = "SELECT os.id, os.numero,
                       COALESCE(os.garantia_ate, {$dataGarantiaCalc}) AS garantia_ate,
                       os.garantia_dias, os.data_conclusao, os.valor_total,
                       c.nome AS cliente_nome, c.telefone AS cliente_tel,
                       COALESCE(eq.tipo,'') AS equip_tipo,
                       COALESCE(eq.marca,'') AS equip_marca,
                       COALESCE(eq.modelo,'') AS equip_modelo,
                       DATEDIFF(COALESCE(os.garantia_ate, {$dataGarantiaCalc}), CURDATE()) AS dias_restantes
                FROM ordens_servico os
                JOIN os_status s   ON s.id  = os.status_id
                JOIN clientes c    ON c.id  = os.cliente_id
                LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
                WHERE os.empresa_id = ?
                  AND os.garantia_dias > 0
                  AND COALESCE(os.garantia_ate, {$dataGarantiaCalc}) >= CURDATE()
                  AND s.tipo NOT IN ('cancelada')";

        $params = [$eid];

        if ($q) {
            $b    = "%{$q}%";
            $bNum = "%" . preg_replace('/\D/', '', $q) . "%";
            $bN   = strlen($bNum) > 2 ? $bNum : $b;
            $sql .= " AND (
              os.numero  LIKE ?
              OR c.nome  LIKE ?
              OR c.telefone LIKE ?
              OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(c.telefone,'(',''),')',''),'-',''),' ',''),'+','') LIKE ?
              OR c.cpf_cnpj LIKE ?
              OR REPLACE(REPLACE(REPLACE(REPLACE(c.cpf_cnpj,'.',''),'-',''),'/',''),' ','') LIKE ?
              OR eq.marca  LIKE ?
              OR eq.modelo LIKE ?
              OR eq.numero_serie LIKE ?
            )";
            array_push($params, $b, $b, $b, $bN, $b, $bN, $b, $b, $b);
        }

        $sql .= " ORDER BY os.garantia_ate ASC LIMIT 50";

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

        // Calcular prazo de garantia (pode ser null em OS importadas)
        $garantiaDias = (int)($os['garantia_dias'] ?? 90);
        $garantiaAte  = $os['garantia_ate'] ?? null;

        // Se garantia_ate não foi gravada, calcular a partir da data de conclusão — nunca da
        // data de entrada/criação, pois a garantia só conta a partir do fechamento da OS.
        if (!$garantiaAte && $garantiaDias > 0) {
            $dataBase = $os['data_conclusao'] ?? null;
            if ($dataBase) {
                $garantiaAte = date('Y-m-d', strtotime($dataBase . " +{$garantiaDias} days"));
                // Salvar no banco para futuras consultas
                $db->prepare("UPDATE ordens_servico SET garantia_ate=? WHERE id=? AND empresa_id=?")
                   ->execute([$garantiaAte, (int)$id, $eid]);
            }
        }

        if (!$garantiaAte) {
            $this->flash('error', 'Esta OS não possui garantia registrada. Configure os dias de garantia.');
            $this->redirect(url('/os/' . $id));
        }

        if (strtotime($garantiaAte) < strtotime('today')) {
            $dias = (int) ceil((strtotime('today') - strtotime($garantiaAte)) / 86400);
            $this->flash('error', "Garantia expirada há {$dias} dia(s). Venceu em " . date('d/m/Y', strtotime($garantiaAte)) . '.');
            $this->redirect(url('/os/' . $id));
        }

        // Retorno em garantia entra no fluxo normal (a garantia é identificada por tipo_servico + selo).
        // Começa no 1º status de trabalho ("Em análise"); fallback: 1º status por ordem.
        $stmtStatus = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? AND tipo = 'em_andamento' ORDER BY ordem LIMIT 1");
        $stmtStatus->execute([$eid]);
        $statusInicial = (int) $stmtStatus->fetchColumn();
        if (!$statusInicial) {
            $s2 = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? ORDER BY ordem LIMIT 1");
            $s2->execute([$eid]);
            $statusInicial = (int) $s2->fetchColumn();
        }
        if (!$statusInicial) {
            $this->flash('error', 'Nenhum status de OS configurado.');
            $this->redirect(url('/os/' . $id));
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

        log_acao('os', 'abrir_garantia', $novaOsId, 'Garantia — retorno da OS ' . $os['numero']);

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

    /** Finaliza uma OS de retorno em garantia (sem cobrança — coberto pela garantia). */
    public function finalizarGarantia(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();
        $os  = $this->model->find((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }
        if (empty($os['os_origem_id'])) {
            $this->flash('error', 'Esta OS não é um retorno em garantia.');
            $this->redirect(url('/os/' . $id));
        }

        // Garantia finalizada = aparelho já devolvido ao cliente → status "Fechado" (entregue).
        $stmtStatus = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? AND (codigo = 'fechado' OR tipo = 'entregue') ORDER BY (codigo = 'fechado') DESC, ordem LIMIT 1");
        $stmtStatus->execute([$eid]);
        $statusFechado = (int) $stmtStatus->fetchColumn();
        if (!$statusFechado) { // fallback: sem "entregue", usa "Pronto" (concluida)
            $fb = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? AND (codigo = 'pronto' OR tipo = 'concluida') ORDER BY (codigo = 'pronto') DESC, ordem LIMIT 1");
            $fb->execute([$eid]);
            $statusFechado = (int) $fb->fetchColumn();
        }
        if (!$statusFechado) {
            $this->flash('error', 'Nenhum status "Fechado/Entregue" cadastrado.');
            $this->redirect(url('/os/' . $id));
        }

        $garantiaDias = (int) $this->post('garantia_dias', $os['garantia_dias'] ?? 90);
        $solucao      = trim($this->post('solucao_aplicada', ''));
        $garantiaAte  = date('Y-m-d', strtotime("+{$garantiaDias} days"));

        $this->model->update((int) $id, [
            'status_id'          => $statusFechado,
            'data_conclusao'     => date('Y-m-d H:i:s'),
            'data_entrega'       => date('Y-m-d H:i:s'),
            'garantia_finalizada'=> 1,
            'garantia_dias'      => $garantiaDias,
            'garantia_ate'       => $garantiaAte,
            'solucao_aplicada'   => $solucao ?: ($os['solucao_aplicada'] ?? null),
            'valor_total'        => 0.00,
            'situacao_pagamento' => 'pago',
        ]);
        $this->model->registrarHistorico((int) $id, $os['status_id'], $statusFechado,
            'Garantia finalizada (sem cobrança). Nova garantia até ' . date('d/m/Y', strtotime($garantiaAte)) . '.'
        );

        log_acao('os', 'finalizar_garantia', (int) $id, 'OS ' . ($os['numero'] ?? $id));
        $this->flash('success', 'Garantia finalizada! Sem cobrança — coberto pela garantia. 🛡️');
        $this->redirect(url('/os/' . $id . '/imprimir/fechamento'));
    }

    private function statusList(int $eid): array
    {
        $stmt = DB::pdo()->prepare("SELECT * FROM os_status WHERE empresa_id = ? ORDER BY ordem");
        $stmt->execute([$eid]);
        return $stmt->fetchAll();
    }

    /**
     * Exclui uma OS PERMANENTEMENTE — SÓ ADMIN. Ação irreversível, registrada no log de ações.
     * Remove a OS + tabelas-filhas + o equipamento se ele for exclusivo desta OS.
     */
    public function excluir(string $id): void
    {
        $back = url('/os');
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect($back); }
        if (!\App\Core\Auth::isAdmin()) {
            $this->flash('error', 'Apenas o administrador pode excluir uma OS.');
            $this->redirect($back);
        }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Reautenticação por senha — segurança extra numa ação IRREVERSÍVEL.
        $senha = (string) $this->post('senha', '');
        $uStmt = $db->prepare("SELECT senha FROM usuarios WHERE id = ?");
        $uStmt->execute([\App\Core\Auth::id()]);
        $hash = (string) $uStmt->fetchColumn();
        if ($senha === '' || $hash === '' || !password_verify($senha, $hash)) {
            $this->flash('error', 'Senha incorreta — a OS NÃO foi excluída.');
            $this->redirect($back);
        }

        $st = $db->prepare(
            "SELECT o.id, o.numero, o.equipamento_id, o.valor_total, cl.nome AS cliente
             FROM ordens_servico o LEFT JOIN clientes cl ON cl.id = o.cliente_id
             WHERE o.id = ? AND o.empresa_id = ?"
        );
        $st->execute([(int) $id, $eid]);
        $os = $st->fetch();
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect($back); }

        $detalhe = 'OS ' . $os['numero'] . ' — ' . ($os['cliente'] ?? 'sem cliente') . ' — ' . money((float) $os['valor_total']);
        $filhos  = ['os_mensagens_lidas','os_historico','os_servicos','os_pecas','fin_comissoes','fin_lancamentos','movimentos_estoque','agenda','os_mensagens','crm_contatos','diretorio_avaliacoes'];

        $db->beginTransaction();
        try {
            foreach ($filhos as $t) {
                try { $db->prepare("DELETE FROM `$t` WHERE os_id = ?")->execute([(int) $id]); }
                catch (\Throwable $e) { /* tabela pode não ter os_id — ignora */ }
            }
            $db->prepare("DELETE FROM ordens_servico WHERE id = ? AND empresa_id = ?")->execute([(int) $id, $eid]);

            $eq = (int) ($os['equipamento_id'] ?? 0);
            if ($eq) {
                $usa = (int) $db->query("SELECT COUNT(*) FROM ordens_servico WHERE equipamento_id = $eq")->fetchColumn();
                if ($usa === 0) $db->prepare("DELETE FROM equipamentos WHERE id = ? AND empresa_id = ?")->execute([$eq, $eid]);
            }
            $db->commit();
        } catch (\Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $this->flash('error', 'Não foi possível excluir a OS. Tente novamente.');
            $this->redirect($back);
        }

        log_acao('os', 'excluir', (int) $id, $detalhe);
        $this->flash('success', 'OS ' . $os['numero'] . ' excluída permanentemente.');
        $this->redirect($back);
    }
}
