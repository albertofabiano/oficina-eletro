<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Auth;
use App\Core\DB;
use App\Models\OrdemServico;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Usuario;
use App\Services\ImageService;

class OrdemServicoController extends Controller
{
    private OrdemServico $model;

    public function __construct()
    {
        $this->model = new OrdemServico();
    }

    /**
     * Valida se um id de "técnico responsável" postado é mesmo alguém que a empresa considera
     * técnico — perfil='tecnico' OU atende_os=1 (mesmo critério de Usuario::tecnicos(), a lista
     * que alimenta o <select> do formulário) — e está ativo. Sem essa checagem, qualquer usuário
     * (ex.: um recepcionista/admin sem atende_os) podia acabar salvo como tecnico_id da OS —
     * o <select> do form já filtra isso, mas nada impedia um POST direto, ou dados antigos de
     * quando o usuário ainda tinha atende_os=1 e depois foi desmarcado.
     */
    private function validarTecnicoId(int $eid, mixed $tecnicoIdPost): ?int
    {
        $tecnicoId = (int) $tecnicoIdPost;
        if (!$tecnicoId) return null;
        $stmt = DB::pdo()->prepare(
            "SELECT id FROM usuarios WHERE id = ? AND empresa_id = ? AND ativo = 1 AND (perfil = 'tecnico' OR atende_os = 1) LIMIT 1"
        );
        $stmt->execute([$tecnicoId, $eid]);
        return $stmt->fetchColumn() ? $tecnicoId : null;
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

    /**
     * Grava as fotos do estado de entrada (data URLs jpeg/png/webp) em disco e
     * registra na tabela os_fotos. Usado tanto na criação quanto na edição da OS.
     */
    private function persistirFotosEntrada(int $eid, int $osId, array $fotos): int
    {
        $dir = BASE_PATH . '/storage/uploads/os_fotos/' . $eid;
        if (!is_dir($dir)) @mkdir($dir, 0775, true);

        $db     = DB::pdo();
        $salvas = 0;
        foreach ($fotos as $durl) {
            if (!is_string($durl) || !preg_match('~^data:image/(jpe?g|png|webp);base64,~', $durl, $m)) continue;
            $bin = base64_decode(substr($durl, strpos($durl, ',') + 1), true);
            if ($bin === false || strlen($bin) < 100 || strlen($bin) > 4_000_000) continue;

            $nome = 'entrada_' . $osId . '_' . time() . '_' . bin2hex(random_bytes(3)) . '.webp';
            if (!ImageService::binarioParaWebp($bin, $dir . '/' . $nome, 85, 1600)) continue;

            $db->prepare("INSERT INTO os_fotos (empresa_id, os_id, arquivo) VALUES (?, ?, ?)")
               ->execute([$eid, $osId, 'os_fotos/' . $eid . '/' . $nome]);
            $salvas++;
        }
        return $salvas;
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

        $stmtDias = $db->prepare("SELECT valor FROM configuracoes WHERE empresa_id = ? AND chave = 'dias_previsao_padrao'");
        $stmtDias->execute([$eid]);
        $diasPrevisaoValor = $stmtDias->fetchColumn();
        $diasPrevisaoPadrao = ($diasPrevisaoValor === false || $diasPrevisaoValor === '') ? 5 : (int) $diasPrevisaoValor;

        $this->view('os.form', [
            'titulo'         => 'Nova OS',
            'os'             => [],
            'statusList'     => $this->statusList($eid),
            'tecnicos'       => (new Usuario())->tecnicos(),
            'numero'         => $this->model->proximoNumero(),
            'status_inicial' => $primeiroStatus,
            'categorias'     => $stmtCat->fetchAll(),
            'diasPrevisaoPadrao' => $diasPrevisaoPadrao,
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
        if (!trim($this->post('equip_marca', ''))) {
            $this->flash('error', 'Informe a marca do equipamento.');
            $this->redirect(url('/os/nova'));
        }
        if (!trim($this->post('equip_modelo', ''))) {
            $this->flash('error', 'Informe o modelo do equipamento.');
            $this->redirect(url('/os/nova'));
        }
        if (!trim($this->post('acessorios', ''))) {
            $this->flash('error', 'Informe os acessórios que vieram com o equipamento (ou marque "Sem acessórios").');
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
                "INSERT INTO equipamentos (empresa_id, cliente_id, categoria_id, tipo, marca, modelo, numero_serie, imei, cor, voltagem, especificacao, estado_entrada, observacoes, descricao_defeito_cliente, acessorios, senha_desbloqueio, tipo_armazenamento, memoria_ram, placa_video, placa_mae, processador)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
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
                $this->post('especificacao') ?: null,
                $this->post('estado_entrada', 'regular'),
                $this->post('estado_observacoes') ?: null,
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
            'tecnico_id'      => $this->validarTecnicoId($eid, $this->post('tecnico_id')),
            'recepcionista_id'=> $this->usuarioId(),
            'prioridade'      => $this->post('prioridade', 'normal'),
            'tipo_servico'    => $this->post('tipo_servico', 'conserto'),
            'defeito_relatado'=> $this->post('defeito_relatado'),
            'data_previsao'   => $this->normalizarPrevisao($this->post('data_previsao', '')),
            'garantia_dias'   => (int) $this->post('garantia_dias', 90),
            'observacoes_internas' => $this->post('observacoes_internas'),
            'observacoes_cliente'  => $this->post('observacoes_cliente'),
        ];

        $osId = $this->model->insert($data);
        $this->model->registrarHistorico($osId, null, $statusId, 'OS criada.');

        // Fotos do estado de entrada (comprimidas/webp no navegador) — gravadas em disco agora que a OS já existe.
        // Vêm num campo oculto com JSON (o form é um POST normal, não um fetch com corpo JSON).
        $fotosEntrada = json_decode((string) $this->post('fotos_entrada', '[]'), true);
        if (is_array($fotosEntrada) && $fotosEntrada) {
            $this->persistirFotosEntrada($eid, $osId, array_slice($fotosEntrada, 0, 6));
        }

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
     * Recebe um "rascunho" de OS criado offline (sem número, sem cliente/equipamento
     * confirmados) e transforma numa OS de verdade — chamado automaticamente pelo
     * navegador assim que a internet volta. Cliente é casado por telefone (cria um
     * cadastro básico se não achar); número da OS só existe a partir daqui, nunca offline,
     * pra não ter risco de duplicar número entre dispositivos.
     */
    public function sincronizarRascunho(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Sessão expirada. Recarregue a página e tente sincronizar de novo.'], 403); return; }

        $db  = DB::pdo();
        $eid = $this->empresaId();

        $clienteNome = trim($this->post('cliente_nome', ''));
        $clienteTel  = trim($this->post('cliente_telefone', ''));
        $equipTipo   = trim($this->post('equip_tipo', ''));
        $defeito     = trim($this->post('defeito_relatado', ''));

        if ($clienteNome === '') { $this->json(['error' => 'Informe o nome do cliente.'], 422); return; }
        if ($clienteTel === '')  { $this->json(['error' => 'Informe o telefone do cliente.'], 422); return; }
        if ($equipTipo === '')   { $this->json(['error' => 'Informe o tipo do equipamento.'], 422); return; }
        if ($defeito === '')     { $this->json(['error' => 'Informe o defeito relatado.'], 422); return; }

        $cpfCnpj = only_numbers(trim($this->post('cliente_cpf_cnpj', '')));
        if ($cpfCnpj !== '' && !documento_valido($cpfCnpj)) { $this->json(['error' => 'CPF/CNPJ do cliente inválido.'], 422); return; }

        // Casa com um cliente já cadastrado pelo telefone/whatsapp (ignorando formatação).
        $telLimpo = preg_replace('/\D/', '', $clienteTel);
        $clienteId = 0;
        if ($telLimpo !== '') {
            $stmtC = $db->prepare(
                "SELECT id FROM clientes WHERE empresa_id = ? AND (
                    REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telefone,'(',''),')',''),'-',''),' ',''),'+','') = ?
                    OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(whatsapp,'(',''),')',''),'-',''),' ',''),'+','') = ?
                 ) LIMIT 1"
            );
            $stmtC->execute([$eid, $telLimpo, $telLimpo]);
            $clienteId = (int) $stmtC->fetchColumn();
        }

        if (!$clienteId) {
            $primeiroNome = trim(explode(' ', $clienteNome)[0]);
            $stmtIns = $db->prepare(
                "INSERT INTO clientes (empresa_id, tipo, nome, cpf_cnpj, telefone, whatsapp, contato, cep, logradouro, numero, complemento, bairro, cidade, uf, origem, status)
                 VALUES (?, 'pf', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'balcao', 'ativo')"
            );
            $stmtIns->execute([
                $eid,
                $clienteNome,
                $cpfCnpj ?: null,
                $clienteTel,
                $clienteTel,
                $primeiroNome,
                only_numbers(trim($this->post('cliente_cep', ''))) ?: null,
                trim($this->post('cliente_logradouro', '')) ?: null,
                trim($this->post('cliente_numero', '')) ?: null,
                trim($this->post('cliente_complemento', '')) ?: null,
                trim($this->post('cliente_bairro', '')) ?: null,
                trim($this->post('cliente_cidade', '')) ?: null,
                strtoupper(trim($this->post('cliente_uf', ''))) ?: null,
            ]);
            $clienteId = (int) $db->lastInsertId();
        }

        $this->garantirTipoEquip($eid, $equipTipo);
        $this->garantirMarcaEquip($eid, $this->post('equip_marca', ''));

        $stmtEq = $db->prepare(
            "INSERT INTO equipamentos (empresa_id, cliente_id, tipo, marca, modelo, descricao_defeito_cliente, acessorios)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtEq->execute([
            $eid, $clienteId, $equipTipo, $this->post('equip_marca', ''), $this->post('equip_modelo', ''),
            $defeito, trim($this->post('acessorios', '')) ?: null,
        ]);
        $equipId = (int) $db->lastInsertId();

        $stmtS = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? ORDER BY ordem LIMIT 1");
        $stmtS->execute([$eid]);
        $statusId = (int) $stmtS->fetchColumn();
        if (!$statusId) { $this->json(['error' => 'Nenhum status de OS configurado nessa empresa.'], 422); return; }

        $obsInternas = trim($this->post('observacoes_internas', ''));
        $criadoOfflineEm = trim($this->post('criado_offline_em', ''));
        if ($criadoOfflineEm !== '') {
            $obsInternas = trim("[Criado offline em {$criadoOfflineEm}, sincronizado automaticamente ao reconectar]\n" . $obsInternas);
        }

        $numero = $this->model->proximoNumero();
        $data = [
            'numero'               => $numero,
            'cliente_id'           => $clienteId,
            'equipamento_id'       => $equipId,
            'status_id'            => $statusId,
            'tecnico_id'           => null,
            'recepcionista_id'     => $this->usuarioId(),
            'prioridade'           => in_array($this->post('prioridade'), ['baixa', 'normal', 'alta', 'urgente'], true) ? $this->post('prioridade') : 'normal',
            'tipo_servico'         => 'conserto',
            'defeito_relatado'     => $defeito,
            'garantia_dias'        => 90,
            'observacoes_internas' => $obsInternas ?: null,
        ];

        $osId = $this->model->insert($data);
        $this->model->registrarHistorico($osId, null, $statusId, 'OS criada (rascunho offline sincronizado).');

        $token = bin2hex(random_bytes(16));
        $db->prepare("UPDATE ordens_servico SET token_publico = ? WHERE id = ?")->execute([$token, $osId]);

        log_acao('os', 'criar', $osId, 'OS ' . $numero . ' (sincronizada offline)');
        os_checar_limite($eid);

        $this->json(['success' => true, 'id' => $osId, 'numero' => $numero]);
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

        // Eventos de agenda vinculados a esta OS (ver os/show.php, card "Agenda").
        $stmtAg = DB::pdo()->prepare(
            "SELECT a.id, a.titulo, a.data_inicio, a.data_fim, a.tipo, a.status, u.nome AS usuario_nome
             FROM agenda a LEFT JOIN usuarios u ON u.id = a.usuario_id
             WHERE a.os_id = ? AND a.empresa_id = ?
             ORDER BY a.data_inicio DESC"
        );
        $stmtAg->execute([(int) $id, $eid]);

        $stmtFotos = DB::pdo()->prepare("SELECT id, arquivo FROM os_fotos WHERE os_id = ? AND empresa_id = ? ORDER BY id ASC");
        $stmtFotos->execute([(int) $id, $eid]);

        $stmtAdiant = DB::pdo()->prepare("SELECT * FROM os_adiantamentos WHERE os_id = ? AND empresa_id = ? ORDER BY criado_em ASC");
        $stmtAdiant->execute([(int) $id, $eid]);

        $this->view('os.show', [
            'titulo'         => 'OS: ' . $os['numero'],
            'os'             => $os,
            'statusList'     => $this->statusList($eid),
            'tecnicos'       => (new Usuario())->tecnicos(),
            'taxasCartao'    => $taxasCartao,
            'taxaCartaoOS'   => $taxaCartaoOS,
            'eventosAgenda'  => $stmtAg->fetchAll(),
            'fotosEntrada'   => $stmtFotos->fetchAll(),
            'adiantamentos'  => $stmtAdiant->fetchAll(),
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

        $stmtFotos = $db->prepare("SELECT id, arquivo FROM os_fotos WHERE os_id = ? AND empresa_id = ? ORDER BY id ASC");
        $stmtFotos->execute([(int) $id, $eid]);

        $this->view('os.form', [
            'titulo'     => 'Editar OS: ' . $os['numero'],
            'os'         => $os,
            'statusList' => $this->statusList($eid),
            'tecnicos'   => (new Usuario())->tecnicos(),
            'categorias' => $stmtCat->fetchAll(),
            'status_inicial' => null,
            'fotosExistentes' => $stmtFotos->fetchAll(),
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
            'tecnico_id'      => $this->validarTecnicoId($this->empresaId(), $this->post('tecnico_id')),
            'prioridade'      => $this->post('prioridade', 'normal'),
            'tipo_servico'    => $this->post('tipo_servico', 'conserto'),
            'defeito_relatado'=> $this->post('defeito_relatado'),
            'data_previsao'   => $this->normalizarPrevisao($this->post('data_previsao', '')),
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
            if (!trim($this->post('equip_marca', ''))) {
                $this->flash('error', 'Informe a marca do equipamento.');
                $this->redirectBack();
            }
            if (!trim($this->post('equip_modelo', ''))) {
                $this->flash('error', 'Informe o modelo do equipamento.');
                $this->redirectBack();
            }
            if (!trim($this->post('acessorios', ''))) {
                $this->flash('error', 'Informe os acessórios que vieram com o equipamento (ou marque "Sem acessórios").');
                $this->redirectBack();
            }
            $this->garantirTipoEquip($this->empresaId(), $this->post('equip_tipo', ''));
            $this->garantirMarcaEquip($this->empresaId(), $this->post('equip_marca', ''));
            $db->prepare(
                "UPDATE equipamentos
                    SET tipo=?, marca=?, modelo=?, numero_serie=?, imei=?, cor=?, voltagem=?, especificacao=?, estado_entrada=?, observacoes=?, acessorios=?, senha_desbloqueio=?,
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
                $this->post('especificacao') ?: null,
                $this->post('estado_entrada') ?: 'regular',
                $this->post('estado_observacoes') ?: null,
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

        // Fotos do estado de entrada adicionadas nesta edição (comprimidas/webp no navegador)
        $fotosEntrada = json_decode((string) $this->post('fotos_entrada', '[]'), true);
        if (is_array($fotosEntrada) && $fotosEntrada) {
            $this->persistirFotosEntrada($this->empresaId(), (int) $id, array_slice($fotosEntrada, 0, 6));
        }

        // Registrar histórico se status mudou
        if ($novoStatusId !== (int) $os['status_id']) {
            $this->model->registrarHistorico((int) $id, $os['status_id'], $novoStatusId, 'Atualizado via edição.');
            $this->talvezFecharAutomaticoSemCobranca((int) $id, $this->empresaId(), $novoStatusId);
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

        // Sem mudança real de estado: não grava transição no histórico (evita
        // "X → X" no mesmo minuto quando o modal é salvo sem trocar o status
        // selecionado, ou num reenvio duplicado do formulário).
        if ($novoStatusId === (int) $os['status_id']) {
            $this->json(['success' => true, 'sem_alteracao' => true]);
        }

        $update = ['status_id' => $novoStatusId];

        $db = DB::pdo();
        $stmt = $db->prepare("SELECT tipo, sem_valor FROM os_status WHERE id = ?");
        $stmt->execute([$novoStatusId]);
        $stRow      = $stmt->fetch();
        $statusTipo = $stRow['tipo'] ?? '';


        if ($statusTipo === 'concluida') $update['data_conclusao'] = date('Y-m-d H:i:s');
        if ($statusTipo === 'entregue')  $update['data_entrega']   = date('Y-m-d H:i:s');

        // Voltando de um estado fechado/cancelado pra um ativo (ex.: reabrir manualmente pelo
        // dropdown de status, não pelo botão "Reabrir OS") — limpa os marcadores de fechamento,
        // senão a OS fica "fechada sem cobrança" fantasma (menu de impressão, financeiro etc.
        // continuam achando que ela ainda está encerrada).
        if (!in_array($statusTipo, ['concluida', 'entregue', 'cancelada'], true)) {
            $update['data_conclusao']      = null;
            $update['data_entrega']        = null;
            $update['fechada_sem_receita'] = 0;
            $update['garantia_dias']       = null;
            $update['garantia_ate']        = null;
        }

        $this->model->update((int) $id, $update);
        $this->model->registrarHistorico((int) $id, $os['status_id'], $novoStatusId, $descricao);
        $this->talvezFecharAutomaticoSemCobranca((int) $id, $this->empresaId(), $novoStatusId);

        log_acao('os', 'status', (int) $id, 'OS ' . ($os['numero'] ?? $id) . ' — ' . $descricao);
        $this->json(['success' => true]);
    }

    /**
     * Se o status pra onde a OS acabou de ir (id $statusAtualId) tem `fecha_sem_cobranca=1`
     * (configurado em Config → Status de OS), fecha a OS na hora sem cobrança — mesmo destino/
     * campos do fechamento manual "Sem Conserto/Recusado" que fechar() já faz (ver o bloco
     * $ehSemConserto lá), só que sem passar pelo modal nem esperar confirmação.
     *
     * Importante: registra o histórico como uma transição SAINDO do status flagado (tipo
     * cancelada) — é exatamente o formato que nomeStatusSemConserto() já sabe ler pra recuperar
     * o nome original ("Sem Conserto"/"Descartado"/etc.) depois que a OS já está em "Fechado",
     * o mesmo formato que o fluxo manual (mudar status → abrir Fechar OS) sempre produziu. Por
     * isso quem chama este método precisa ter ATUALIZADO status_id pro status flagado e gravado
     * esse histórico ANTES de chamar aqui — senão o comprovante "Sem Conserto" não acha o nome.
     */
    private function talvezFecharAutomaticoSemCobranca(int $osId, int $eid, int $statusAtualId): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT tipo, nome, fecha_sem_cobranca FROM os_status WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$statusAtualId, $eid]);
        $status = $stmt->fetch();
        if (!$status || !$status['fecha_sem_cobranca'] || $status['tipo'] !== 'cancelada') return;

        // Mesma busca de "status Fechado" que fechar() usa — prefere o nativo 'fechado'
        // (codigo, imune a reordenação/exclusão), senão o 1o 'entregue' por ordem.
        $stmtStatus = $db->prepare(
            "SELECT id FROM os_status WHERE empresa_id = ? AND (codigo = 'fechado' OR tipo = 'entregue')
             ORDER BY (codigo = 'fechado') DESC, ordem LIMIT 1"
        );
        $stmtStatus->execute([$eid]);
        $statusFechado = (int) $stmtStatus->fetchColumn();
        if (!$statusFechado) {
            $stmtFb = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? AND tipo = 'concluida' ORDER BY ordem LIMIT 1");
            $stmtFb->execute([$eid]);
            $statusFechado = (int) $stmtFb->fetchColumn();
        }
        if (!$statusFechado || $statusFechado === $statusAtualId) return;

        $os = $this->model->find($osId);
        if (!$os) return;

        $this->model->update($osId, [
            'status_id'                  => $statusFechado,
            'data_conclusao'             => $os['data_conclusao'] ?: date('Y-m-d H:i:s'),
            // Sem Conserto/Recusado não tem garantia (nada foi consertado/entregue).
            'garantia_dias'              => null,
            'garantia_ate'               => null,
            'situacao_pagamento'         => 'pendente',
            // Não zera um adiantamento genuíno já recebido antes (ver fechar()) — estornar é
            // decisão de fora do sistema, não um zerar automático aqui.
            'valor_pago'                 => (float) ($os['valor_pago'] ?? 0),
            'forma_pagamento_fechamento' => null,
            'fechada_sem_receita'        => 1,
        ]);
        $this->model->registrarHistorico($osId, $statusAtualId, $statusFechado,
            'Fechada automaticamente sem cobrança — status "' . $status['nome'] . '" configurado pra fechar sozinho.');
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

        // Todo serviço lançado numa OS (digitado avulso ou vindo do catálogo) passa a existir
        // no catálogo — quem digitou algo novo não precisa ir em /servicos cadastrar de novo.
        $this->garantirServicoNoCatalogo($eid, (string) $this->post('descricao'), $val);

        $totais = $this->model->calcularTotal((int) $id);
        $this->model->update((int) $id, ['valor_total' => $totais['total']]);
        $this->flash('success', $servicoId ? 'Serviço atualizado!' : 'Serviço adicionado!');
        $this->redirect(url("/os/{$id}"));
    }

    /**
     * Garante que a descrição usada num serviço de OS exista no catálogo (servicos_catalogo) —
     * quem digita algo avulso na OS não precisa cadastrar de novo em /servicos. Casa por
     * descrição (case-insensitive), sem tocar no valor_padrao de um item já cadastrado (o
     * catálogo é só sugestão; um valor pontual numa OS não deve sobrescrever o padrão da
     * empresa). Reativa se já existia mas tinha sido excluído (ativo=0).
     */
    private function garantirServicoNoCatalogo(int $eid, string $descricao, float $valor): void
    {
        // servicos_catalogo.descricao é VARCHAR(150); os_servicos.descricao é VARCHAR(255) e
        // sem limite no campo da OS — trunca pra não estourar a coluna do catálogo.
        $descricao = mb_substr(trim($descricao), 0, 150);
        if ($descricao === '') return;

        $db = DB::pdo();
        $existente = $db->prepare(
            "SELECT id, ativo FROM servicos_catalogo WHERE empresa_id = ? AND LOWER(descricao) = LOWER(?) LIMIT 1"
        );
        $existente->execute([$eid, $descricao]);
        $row = $existente->fetch();

        if ($row) {
            if (!$row['ativo']) {
                $db->prepare("UPDATE servicos_catalogo SET ativo = 1 WHERE id = ?")->execute([$row['id']]);
            }
            return;
        }

        $db->prepare("INSERT INTO servicos_catalogo (empresa_id, descricao, valor_padrao) VALUES (?, ?, ?)")
           ->execute([$eid, $descricao, $valor]);
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

    /**
     * Registra um adiantamento (sinal) recebido do cliente antes do fechamento — pedido do
     * usuário: peça cara, o dono pede parte do valor adiantado pro cliente. Vira receita de
     * verdade na hora (mesmas regras de forma de pagamento/taxa de cartão/repasse do
     * fechamento — ver fechar()), sem esperar a OS fechar; soma em ordens_servico.valor_pago,
     * que o card "Financeiro" da tela da OS já usa pra mostrar Pago/Saldo.
     */
    public function adicionarAdiantamento(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $os  = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        if (($os['status_tipo'] ?? '') === 'entregue') {
            $this->flash('error', 'Esta OS já está fechada — adiantamento só faz sentido antes do fechamento.');
            $this->redirect(url('/os/' . $id));
        }

        $formasOk = ['dinheiro', 'pix', 'pix_maquininha', 'cartao_credito', 'cartao_debito', 'transferencia', 'boleto'];
        $forma    = (string) $this->post('forma_pagamento', '');
        if (!in_array($forma, $formasOk, true)) {
            $this->flash('error', 'Forma de pagamento inválida.');
            $this->redirect(url('/os/' . $id));
        }
        $valor = moeda_float($this->post('valor', 0));
        if ($valor <= 0) {
            $this->flash('error', 'Informe um valor de adiantamento maior que zero.');
            $this->redirect(url('/os/' . $id));
        }

        // "PIX (maquininha)" é só UI pra sinalizar taxa — mesma normalização de fechar()/PdvController.
        $comTaxaPix = $forma === 'pix_maquininha';
        if ($comTaxaPix) $forma = 'pix';
        $parcelas = $forma === 'cartao_credito' ? max(1, min(24, (int) $this->post('parcelas', 1))) : 1;
        // Taxa nunca vem do formulário — só da config da empresa (Config → Cartões).
        $taxa = (in_array($forma, ['cartao_credito', 'cartao_debito'], true) || $comTaxaPix)
              ? taxa_cartao_configurada($eid, $forma, $parcelas) : 0.0;

        $repassar     = $this->post('repassar') === '1';
        $taxaAplica   = $taxa > 0 && $valor > 0;
        $valorCobrado = ($taxaAplica && $repassar) ? round($valor / (1 - $taxa / 100), 2) : $valor;
        $taxaValor    = $taxaAplica ? round($valorCobrado * $taxa / 100, 2) : 0.0;

        $db = DB::pdo();

        $stmtConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY id LIMIT 1");
        $stmtConta->execute([$eid]);
        $contaId = $stmtConta->fetchColumn() ?: null;

        $catStmt = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='receita' AND nome='Serviços' LIMIT 1");
        $catStmt->execute([$eid]);
        $catServico = $catStmt->fetchColumn();
        if (!$catServico) {
            $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'receita', 'Serviços', '#198754')")->execute([$eid]);
            $catServico = (int) $db->lastInsertId();
        }

        $equipDesc = trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''));
        $descricao = 'Adiantamento — OS ' . $os['numero'] . ' — ' . implode(' — ', array_filter([$equipDesc, $os['cliente_nome'] ?? '']));
        $hoje = date('Y-m-d');

        $db->prepare(
            "INSERT INTO fin_lancamentos
             (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao,
              valor, data_vencimento, data_pagamento, status, forma_pagamento)
             VALUES (?, ?, ?, ?, ?, ?, 'receita', ?, ?, ?, ?, 'pago', ?)"
        )->execute([
            $eid, $contaId, $catServico, (int) $id, $os['cliente_id'], $this->usuarioId(),
            $descricao, $valorCobrado, $hoje, $hoje, $forma,
        ]);
        $lancamentoReceitaId = (int) $db->lastInsertId();

        $lancamentoTaxaId = null;
        if ($taxaValor > 0) {
            $catStmtTx = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='despesa' AND nome='Taxas de cartão' LIMIT 1");
            $catStmtTx->execute([$eid]);
            $catTaxa = $catStmtTx->fetchColumn();
            if (!$catTaxa) {
                $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'despesa', 'Taxas de cartão', '#dc3545')")->execute([$eid]);
                $catTaxa = (int) $db->lastInsertId();
            }
            $descTaxa = $forma === 'pix'
                ? 'Taxa pix (maquininha) — Adiantamento OS ' . $os['numero'] . ' (' . number_format($taxa, 2, ',', '.') . '%)'
                : 'Taxa cartão — Adiantamento OS ' . $os['numero'] . ' (' . ($forma === 'cartao_debito' ? 'débito' : $parcelas . 'x') . ' · ' . number_format($taxa, 2, ',', '.') . '%)';
            $db->prepare(
                "INSERT INTO fin_lancamentos
                 (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao,
                  valor, data_vencimento, data_pagamento, status, forma_pagamento)
                 VALUES (?, ?, ?, ?, ?, ?, 'despesa', ?, ?, CURDATE(), CURDATE(), 'pago', ?)"
            )->execute([
                $eid, $contaId, $catTaxa, (int) $id, $os['cliente_id'], $this->usuarioId(),
                $descTaxa, $taxaValor, $forma,
            ]);
            $lancamentoTaxaId = (int) $db->lastInsertId();
        }

        $db->prepare(
            "INSERT INTO os_adiantamentos
             (empresa_id, os_id, usuario_id, forma_pagamento, parcelas, valor, taxa_percentual, taxa_valor, valor_cobrado, fin_lancamento_id, fin_lancamento_taxa_id)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        )->execute([
            $eid, (int) $id, $this->usuarioId(), $forma, $parcelas, $valor, $taxa, $taxaValor, $valorCobrado,
            $lancamentoReceitaId, $lancamentoTaxaId,
        ]);

        // valor_pago/situação comparam contra o VALOR do serviço (sem a taxa repassada, que é
        // só compensação da maquininha, não pagamento pelo reparo em si) — mesmo critério de
        // fechar(), que soma $valorPago (bruto) e não valor_cobrado.
        $novoValorPago = (float) ($os['valor_pago'] ?? 0) + $valor;
        $novaSituacao  = ($novoValorPago >= (float) $os['valor_total'] && (float) $os['valor_total'] > 0) ? 'pago' : 'parcial';
        $this->model->update((int) $id, ['valor_pago' => $novoValorPago, 'situacao_pagamento' => $novaSituacao]);

        log_acao('os', 'adiantamento', (int) $id, 'Adiantamento OS ' . $os['numero'] . ' — ' . money($valorCobrado));
        $this->flash('success', 'Adiantamento de ' . money($valorCobrado) . ' registrado!');
        $this->redirect(url('/os/' . $id));
    }

    /** Remove um adiantamento e estorna a receita (e a despesa da taxa, se houver) que ele gerou. */
    public function excluirAdiantamento(string $id, string $adiantamentoId): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmt = $db->prepare("SELECT * FROM os_adiantamentos WHERE id = ? AND os_id = ? AND empresa_id = ?");
        $stmt->execute([(int) $adiantamentoId, (int) $id, $eid]);
        $ad = $stmt->fetch();
        if (!$ad) { $this->flash('error', 'Adiantamento não encontrado.'); $this->redirect(url('/os/' . $id)); }

        if ($ad['fin_lancamento_id']) {
            $db->prepare("DELETE FROM fin_lancamentos WHERE id = ? AND empresa_id = ?")->execute([(int) $ad['fin_lancamento_id'], $eid]);
        }
        if ($ad['fin_lancamento_taxa_id']) {
            $db->prepare("DELETE FROM fin_lancamentos WHERE id = ? AND empresa_id = ?")->execute([(int) $ad['fin_lancamento_taxa_id'], $eid]);
        }
        $db->prepare("DELETE FROM os_adiantamentos WHERE id = ? AND empresa_id = ?")->execute([(int) $adiantamentoId, $eid]);

        $os = $this->model->find((int) $id);
        if ($os) {
            $novoValorPago = max(0, (float) ($os['valor_pago'] ?? 0) - (float) $ad['valor']);
            $novaSituacao  = $novoValorPago <= 0
                ? 'pendente'
                : (($novoValorPago >= (float) $os['valor_total'] && (float) $os['valor_total'] > 0) ? 'pago' : 'parcial');
            $this->model->update((int) $id, ['valor_pago' => $novoValorPago, 'situacao_pagamento' => $novaSituacao]);
        }

        log_acao('os', 'adiantamento_excluido', (int) $id, 'Adiantamento removido — OS ' . ($os['numero'] ?? $id));
        $this->flash('success', 'Adiantamento removido.');
        $this->redirect(url('/os/' . $id));
    }

    /** Busca um adiantamento específico da OS, já validando empresa — usado por imprimir/enviar. */
    private function buscarAdiantamento(int $osId, int $adiantamentoId): ?array
    {
        $stmt = DB::pdo()->prepare("SELECT * FROM os_adiantamentos WHERE id = ? AND os_id = ? AND empresa_id = ?");
        $stmt->execute([$adiantamentoId, $osId, $this->empresaId()]);
        return $stmt->fetch() ?: null;
    }

    /** Recibo de um adiantamento específico — prova de que o cliente adiantou aquele valor. */
    public function imprimirAdiantamento(string $id, string $adiantamentoId): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $adiantamento = $this->buscarAdiantamento((int) $id, (int) $adiantamentoId);
        if (!$adiantamento) { $this->flash('error', 'Adiantamento não encontrado.'); $this->redirect(url('/os/' . $id)); }

        $this->saidaImpressao(
            $this->renderView('os.print_adiantamento', ['os' => $os, 'adiantamento' => $adiantamento], 'print_adiantamento'),
            'recibo-adiantamento-os-' . $os['numero']
        );
    }

    /** Gera o PDF do recibo de um adiantamento e envia ao WhatsApp do cliente pela Evolution. */
    public function enviarAdiantamentoWhatsapp(string $id, string $adiantamentoId): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 400); }

        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->json(['error' => 'OS não encontrada'], 404); }

        $adiantamento = $this->buscarAdiantamento((int) $id, (int) $adiantamentoId);
        if (!$adiantamento) { $this->json(['error' => 'Adiantamento não encontrado'], 404); }

        $eid  = $this->empresaId();
        $html = $this->renderView('os.print_adiantamento', ['os' => $os, 'adiantamento' => $adiantamento], 'print_adiantamento');
        $pdf  = \App\Services\PdfService::fromHtml($html);
        if ($pdf === null) { $this->json(['success' => false, 'error' => 'Falha ao gerar o PDF.']); }

        $whats = only_numbers(($os['cliente_whats'] ?? '') ?: ($os['cliente_tel'] ?? ''));
        if (!$whats) { $this->json(['success' => false, 'error' => 'Cliente sem WhatsApp/telefone cadastrado.']); }

        if (\App\Services\WhatsAppService::statusEmpresa($eid) !== 'open') {
            $this->json(['success' => false, 'error' => 'O WhatsApp da sua empresa não está conectado. Conecte em Configurações → WhatsApp para enviar do seu número.']);
        }

        $fileName = preg_replace('/[^A-Za-z0-9\-]/', '-', 'recibo-adiantamento-os-' . $os['numero']) . '.pdf';
        $caption  = "Comprovante de adiantamento — OS {$os['numero']}\n"
                  . "Valor recebido: " . money($adiantamento['valor_cobrado']);

        $ok = \App\Services\WhatsAppService::enviarDocumento($eid, $whats, base64_encode($pdf), $fileName, $caption);
        $this->json($ok ? ['success' => true] : ['success' => false, 'error' => 'Falha no envio pelo WhatsApp.']);
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

    /** Edita a garantia (dias) direto na tela da OS — recalcula a validade se a OS já foi entregue/fechada. */
    public function atualizarGarantia(string $id): void
    {
        if (!csrf_verify()) { $this->json(['erro' => 'Sessão expirada.'], 403); }
        $eid = $this->empresaId();
        $os  = $this->model->find((int) $id);
        if (!$os) { $this->json(['erro' => 'OS não encontrada.'], 404); }

        $dias = max(0, min(3650, (int) $this->post('garantia_dias', 0)));
        $dados = ['garantia_dias' => $dias];
        // Base é data_entrega (gravada só quando o status vira "entregue" de verdade, o
        // fechamento real da OS) — nunca data_conclusao, que pode já estar preenchida bem antes
        // disso (status só virou "Concluída", reparo pronto mas ainda não retirado/pago), o que
        // faria a garantia contar de um momento que não é o fechamento.
        if (!empty($os['data_entrega'])) {
            $dados['garantia_ate'] = date('Y-m-d', strtotime($os['data_entrega'] . " +{$dias} days"));
        }
        $this->model->update((int) $id, $dados);
        $this->json(['ok' => true, 'dias' => $dias, 'ate' => $dados['garantia_ate'] ?? null]);
    }

    /** Edição rápida da previsão de entrega direto na tela de detalhe (mesmo padrão da garantia). */
    public function atualizarPrevisao(string $id): void
    {
        if (!csrf_verify()) { $this->json(['erro' => 'Sessão expirada.'], 403); }
        $os = $this->model->find((int) $id);
        if (!$os) { $this->json(['erro' => 'OS não encontrada.'], 404); }

        $data = $this->normalizarPrevisao($this->post('data_previsao', ''));
        $this->model->update((int) $id, ['data_previsao' => $data]);
        $this->json(['ok' => true, 'data_previsao' => $data]);
    }

    /**
     * Previsão de entrega vem dos formulários como campo <input type="date"> (só data,
     * sem hora) — grava sempre às 18h daquele dia, pro cálculo de "atrasada" (que compara
     * com a hora atual) fazer sentido em vez de virar atrasada logo à meia-noite.
     */
    private function normalizarPrevisao(string $valor): ?string
    {
        return $valor !== '' ? date('Y-m-d', strtotime($valor)) . ' 18:00:00' : null;
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

        // Fotos do estado de entrada (salvas no servidor em vez de mandadas por WhatsApp)
        $fts = $db->prepare("SELECT arquivo FROM os_fotos WHERE os_id = ? AND empresa_id = ? ORDER BY id ASC");
        $fts->execute([$os['id'], $os['empresa_id']]);
        $fotosEntrada = $fts->fetchAll();

        // Avaliação verificada atrelada a esta OS (existe? / pode avaliar?)
        $av = $db->prepare("SELECT nota, comentario, resposta, criado_em FROM diretorio_avaliacoes WHERE os_id = ? LIMIT 1");
        $av->execute([$os['id']]);
        $avaliacaoOs = $av->fetch() ?: null;
        $podeAvaliar = in_array($os['status_tipo'] ?? '', ['entregue', 'concluida'], true) && !empty($os['empresa_listada']);

        // Título/descrição/imagem própria da página — sem isso, o link compartilhado no
        // WhatsApp (og:title/og:description) caía no padrão genérico do layout (o pitch de
        // venda do FixaOS, "Teste grátis 7 dias, sem cartão"), confundindo o CLIENTE final que
        // só quer ver o andamento do reparo dele, não sendo convidado a assinar o sistema.
        $equipamento = trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? '')) ?: ($os['equip_tipo'] ?? 'equipamento');
        $tituloFull  = "Acompanhamento da OS {$os['numero']} — {$os['empresa_nome']}";
        $metaDesc    = "Status atual: {$os['status_nome']}. Acompanhe o reparo do seu {$equipamento} na {$os['empresa_nome']}.";

        // Deliberadamente sem og:image de logo aqui — o card de preview do WhatsApp cortava/
        // "zoomava" logos com proporção alongada de um jeito que não dá pra controlar (o
        // WhatsApp decide o tamanho do card, só a proporção é nossa). Sem og:image, o layout
        // (landing.php) cai no ícone genérico do FixaOS — a logo da empresa continua aparecendo
        // normalmente dentro da própria página de acompanhamento, só não vai mais no link preview.
        $this->view('os.acompanhar', compact('os','historico','servicos','pecas','fotosEntrada','avaliacaoOs','podeAvaliar','tituloFull','metaDesc'), 'landing');
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

    public function imprimirLaudo(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }
        if (empty($os['laudo_tecnico'])) { $this->flash('error', 'Esta OS não possui laudo técnico preenchido.'); $this->redirect(url('/os/' . $os['id'])); }

        $this->saidaImpressao($this->renderView('os.print', ['os' => $os], 'print_laudo'), 'laudo-os-' . $os['numero']);
    }

    /**
     * Gera um rascunho de laudo técnico com IA, a partir dos dados já cadastrados na OS
     * (equipamento, defeito relatado/constatado) + o que o técnico já tiver anotado no editor
     * (usado como pista/contexto extra, não obrigatório). O dono sempre revisa antes de salvar —
     * isso só preenche o campo, não substitui o "Salvar".
     */
    public function gerarLaudoIA(string $id): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido — recarregue a página.'], 400); }
        if (!\App\Services\IAService::ativo()) { $this->json(['ok' => false, 'erro' => 'A geração por IA não está disponível no momento.']); }

        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->json(['ok' => false, 'erro' => 'OS não encontrada.'], 404); }

        $dica = trim(mb_substr((string) $this->post('dica', ''), 0, 1500));

        $equip = trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''));
        $contexto = "Equipamento: " . ($equip ?: 'não informado') . "\n"
            . "Tipo de equipamento: " . ($os['equip_tipo'] ?? 'não informado') . "\n"
            . "Nº de série/IMEI: " . (($os['numero_serie'] ?? '') ?: ($os['imei'] ?? '') ?: 'não informado') . "\n"
            . "Defeito relatado pelo cliente: " . (($os['defeito_relatado'] ?? '') ?: 'não informado') . "\n"
            . (!empty($os['defeito_constatado']) ? "Defeito já constatado pelo técnico: {$os['defeito_constatado']}\n" : '')
            . ($dica !== '' ? "Anotações do técnico (usar como base principal): {$dica}\n" : '');

        $system = "Você é um técnico sênior de assistência técnica de eletrônicos (celular, TV, notebook, "
            . "eletrodoméstico, videogame), redigindo o LAUDO TÉCNICO oficial de uma Ordem de Serviço — o texto "
            . "que vai constar no documento entregue ao cliente.\n\n"
            . "Com base nas informações fornecidas, escreva o laudo técnico cobrindo: diagnóstico/causa provável "
            . "do defeito, e o que foi (ou seria) necessário para o reparo.\n\n"
            . "Regras:\n"
            . "- 100% em português do Brasil — nenhuma palavra em outro idioma.\n"
            . "- Linguagem clara e simples, fácil de entender pra qualquer cliente, mesmo sem conhecimento técnico. "
            . "Evite termos técnicos difíceis; se precisar usar um, explique em poucas palavras.\n"
            . "- Máximo de 500 caracteres no total. Seja direto, sem saudação, sem assinatura, sem repetir os "
            . "dados de cabeçalho (marca/modelo/OS), sem inventar peça, valor ou prazo específico que não foi informado.\n"
            . "- Se as informações forem escassas, seja tecnicamente plausível e genérico, nunca invente fatos "
            . "específicos (ex.: não afirme qual componente exato falhou se isso não foi informado).\n"
            . "- Responda em texto simples (sem markdown, sem HTML). Pode usar 1 ou 2 parágrafos curtos.";

        $r = \App\Services\IAService::perguntar([['role' => 'user', 'content' => $contexto]], $system, 250);
        if (empty($r['ok'])) {
            $this->json(['ok' => false, 'erro' => 'Não foi possível gerar o laudo agora. Tente novamente em instantes.']);
        }

        // Garante o limite de 500 caracteres mesmo que a IA passe um pouco do combinado —
        // corta numa palavra inteira em vez de partir no meio.
        $texto = trim((string) $r['texto']);
        if (mb_strlen($texto) > 500) {
            $texto = mb_substr($texto, 0, 500);
            $texto = mb_substr($texto, 0, mb_strrpos($texto, ' ') ?: 500) . '…';
        }

        $paragrafos = preg_split('/\n\s*\n/', $texto) ?: [];
        $htmlParas  = array_map(
            fn ($p) => '<div>' . nl2br(htmlspecialchars(trim($p), ENT_QUOTES, 'UTF-8')) . '</div>',
            array_filter(array_map('trim', $paragrafos), fn ($p) => $p !== '')
        );

        $this->json(['ok' => true, 'html' => implode('<div><br></div>', $htmlParas)]);
    }

    /**
     * Corretor ortográfico/gramatical próprio (via IA) — usado no "Recado ao cliente" e reutilizável
     * em qualquer campo de texto simples da OS. Só sugere: devolve o texto corrigido pro front-end
     * mostrar, e o usuário decide se aplica ("Usar esta versão") ou descarta.
     */
    public function corrigirTexto(string $id): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido — recarregue a página.'], 400); }
        if (!\App\Services\IAService::ativo()) { $this->json(['ok' => false, 'erro' => 'O corretor não está disponível no momento.']); }

        $os = $this->model->find((int) $id);
        if (!$os) { $this->json(['ok' => false, 'erro' => 'OS não encontrada.'], 404); }

        $texto = trim(mb_substr((string) $this->post('texto', ''), 0, 2000));
        if ($texto === '') { $this->json(['ok' => false, 'erro' => 'Nada pra corrigir.']); }

        $system = "Você corrige ortografia, acentuação, concordância e pontuação de textos em português do "
            . "Brasil. Devolva APENAS o texto corrigido — mesmo sentido, mesmo tom, mesmo tamanho aproximado. "
            . "Não reescreva o estilo, não adicione nem remova informação, não acrescente saudação nem comentário. "
            . "Se o texto já estiver correto, devolva ele exatamente igual, sem alterar nada.";

        $r = \App\Services\IAService::perguntar([['role' => 'user', 'content' => $texto]], $system, 400);
        if (empty($r['ok'])) {
            $this->json(['ok' => false, 'erro' => 'Não foi possível corrigir agora. Tente novamente.']);
        }

        $corrigido = trim((string) $r['texto']);
        $this->json(['ok' => true, 'texto' => $corrigido, 'mudou' => $corrigido !== $texto]);
    }

    /**
     * Depois de fechada, o status vira "Fechado" — busca no histórico qual era o status cancelada
     * de origem (Sem Conserto, Recusado, etc.) pra manter o texto do documento/mensagem condizente
     * com o motivo real, em vez de mostrar "Fechado". Usado tanto na impressão quanto no envio por
     * WhatsApp do mesmo documento.
     */
    private function nomeStatusSemConserto(array $os): string
    {
        if (($os['status_tipo'] ?? '') === 'cancelada') return $os['status_nome'] ?? 'Sem Conserto';

        $stmtHist = DB::pdo()->prepare(
            "SELECT sa.nome FROM os_historico h
             JOIN os_status sa ON sa.id = h.status_anterior_id
             WHERE h.os_id = ? AND h.empresa_id = ? AND h.status_novo_id = ? AND sa.tipo = 'cancelada'
             ORDER BY h.criado_em DESC LIMIT 1"
        );
        $stmtHist->execute([(int) $os['id'], $this->empresaId(), (int) $os['status_id']]);
        return $stmtHist->fetchColumn() ?: ($os['status_nome'] ?? 'Sem Conserto');
    }

    /** Documento de devolução sem conserto — só faz sentido quando o status atual é do tipo cancelada. */
    public function imprimirSemConserto(string $id): void
    {
        $os = $this->model->findCompleto((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $ehCancelada = ($os['status_tipo'] ?? '') === 'cancelada';
        if (!$ehCancelada && empty($os['fechada_sem_receita'])) {
            $this->flash('error', 'Este documento só está disponível quando a OS está (ou foi fechada) em um status "Sem Conserto"/"Recusado".');
            $this->redirect(url('/os/' . $os['id']));
        }

        $os['status_nome'] = $this->nomeStatusSemConserto($os);

        $this->saidaImpressao($this->renderView('os.print', ['os' => $os], 'print_sem_conserto'), 'sem-conserto-os-' . $os['numero']);
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
            'abertura'     => ['os.print',            'print_wa_entrada', 'Comprovante de entrada'],
            'orcamento'    => ['os.print',            'print_orcamento',  'Orçamento'],
            'fechamento'   => ['os.print_fechamento', 'print_fechamento', 'Comprovante'],
            'garantia'     => ['os.print_garantia',   'print_garantia',   'Comprovante de garantia'],
            'laudo'        => ['os.print',            'print_laudo',      'Laudo técnico'],
            'sem-conserto' => ['os.print',            'print_sem_conserto', 'Comprovante sem cobrança'],
        ];
        if (!isset($map[$tipo])) { $this->json(['error' => 'Tipo inválido'], 400); }
        [$view, $layout, $rotulo] = $map[$tipo];

        if ($tipo === 'sem-conserto') {
            $os['status_nome'] = $this->nomeStatusSemConserto($os);
            $rotulo = $os['status_nome'];
        }

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

    /**
     * Salva no servidor as fotos do estado de entrada do equipamento (em vez de mandar cada
     * foto como mensagem de WhatsApp) e avisa o cliente com o link de acompanhamento, onde as
     * fotos já aparecem. Reduz de até 10+ mensagens de imagem pra 1 mensagem de texto só.
     */
    public function salvarFotosEntrada(string $id): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido. Recarregue a página.'], 403); }

        $eid = $this->empresaId();
        $os  = $this->model->findCompleto((int) $id);
        if (!$os) { $this->json(['ok' => false, 'erro' => 'OS não encontrada.'], 404); }

        $fotos = $this->post('fotos', []);
        if (!is_array($fotos) || !$fotos) { $this->json(['ok' => false, 'erro' => 'Nenhuma foto recebida.'], 400); }
        $fotos = array_slice($fotos, 0, 10);

        $salvas = $this->persistirFotosEntrada($eid, (int) $id, $fotos);
        if ($salvas === 0) { $this->json(['ok' => false, 'erro' => 'Não consegui salvar as fotos. Tente de novo.'], 400); }

        // Avisa o cliente com o link de acompanhamento (onde as fotos aparecem) — 1 mensagem só.
        if (!empty($os['token_publico'])) {
            $whats = only_numbers(($os['cliente_whats'] ?? '') ?: ($os['cliente_tel'] ?? ''));
            if ($whats && \App\Services\WhatsAppService::statusEmpresa($eid) === 'open') {
                $appCfg = require BASE_PATH . '/config/app.php';
                $link   = rtrim($appCfg['url'], '/') . '/os/acompanhar/' . $os['token_publico'];
                $nome   = ($os['cliente_contato'] ?? '') ?: explode(' ', trim($os['cliente_nome'] ?? ''))[0];
                $plural = $salvas > 1 ? 'fotos' : 'foto';
                $msg    = "Olá {$nome}! Registramos {$salvas} {$plural} do estado de entrada do seu equipamento. "
                        . "Acompanhe a OS {$os['numero']} (com as fotos) aqui:\n{$link}";
                \App\Services\WhatsAppService::enviarTexto($eid, $whats, $msg);
            }
        }

        $this->json(['ok' => true, 'salvas' => $salvas]);
    }

    /** Exclui uma foto do estado de entrada já salva (arquivo + registro), usada na edição da OS. */
    public function excluirFotoEntrada(string $id, string $fotoId): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido. Recarregue a página.'], 403); }
        if (!\App\Core\Auth::isAdmin()) { $this->json(['ok' => false, 'erro' => 'Só o administrador pode excluir fotos.'], 403); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmt = $db->prepare("SELECT id, arquivo FROM os_fotos WHERE id = ? AND os_id = ? AND empresa_id = ?");
        $stmt->execute([(int) $fotoId, (int) $id, $eid]);
        $foto = $stmt->fetch();
        if (!$foto) { $this->json(['ok' => false, 'erro' => 'Foto não encontrada.'], 404); }

        $db->prepare("DELETE FROM os_fotos WHERE id = ?")->execute([(int) $foto['id']]);

        $caminho = BASE_PATH . '/storage/uploads/' . $foto['arquivo'];
        if (is_file($caminho)) @unlink($caminho);

        $this->json(['ok' => true]);
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

    /** Salva as anotações internas (nunca aparece pro cliente, nem no WhatsApp nem no PDF). */
    public function salvarObservacoesInternas(string $id): void
    {
        if (!csrf_verify()) { $this->json(['success' => false, 'error' => 'Token inválido'], 400); }
        $obs = trim((string) $this->post('observacoes_internas', ''));
        DB::pdo()->prepare("UPDATE ordens_servico SET observacoes_internas = ? WHERE id = ? AND empresa_id = ?")
            ->execute([$obs !== '' ? $obs : null, (int) $id, $this->empresaId()]);
        $this->json(['success' => true]);
    }

    /** Salva o laudo técnico (aparece na impressão de orçamento). */
    public function salvarLaudo(string $id): void
    {
        if (!csrf_verify()) { $this->json(['success' => false, 'error' => 'Token inválido'], 400); }
        $laudo = $this->sanitizarLaudoHtml((string) $this->post('laudo_tecnico', ''));
        DB::pdo()->prepare("UPDATE ordens_servico SET laudo_tecnico = ? WHERE id = ? AND empresa_id = ?")
            ->execute([$laudo !== '' ? $laudo : null, (int) $id, $this->empresaId()]);
        $this->json(['success' => true, 'html' => $laudo]);
    }

    /**
     * Sanitiza o HTML do laudo técnico (vem de um editor WYSIWYG contenteditable com
     * negrito/itálico/sublinhado/listas/cor): mantém só tags de formatação básica, sem
     * atributos — exceto "style" em <span>, e mesmo assim só a propriedade color com
     * valor hex/rgb válido.
     */
    private function sanitizarLaudoHtml(string $html): string
    {
        $html = trim($html);
        if ($html === '') { return ''; }

        $html = strip_tags($html, '<b><strong><i><em><u><span><font><br><div><p><ul><ol><li>');

        // Normaliza <font color="..."> pro mesmo formato de <span style="color:...">
        // (browsers antigos/execCommand sem styleWithCSS geram <font> em vez de span+style).
        $html = preg_replace_callback('/<font([^>]*)>/i', function ($m) {
            if (preg_match('/color\s*=\s*"?(#[0-9a-fA-F]{3,8})"?/i', $m[1], $cm)) {
                return '<span style="color:' . $cm[1] . '">';
            }
            return '<span>';
        }, $html);
        $html = str_ireplace('</font>', '</span>', $html);

        $html = preg_replace_callback('/<span([^>]*)>/i', function ($m) {
            if (preg_match('/style\s*=\s*"([^"]*)"/i', $m[1], $sm)
                && preg_match('/color\s*:\s*(#[0-9a-fA-F]{3,8}|rgb\([\d,\s]+\))/i', $sm[1], $cm)) {
                return '<span style="color:' . $cm[1] . '">';
            }
            return '<span>';
        }, $html);

        $html = preg_replace('/<(b|strong|i|em|u|br|div|p|ul|ol|li)\s[^>]*>/i', '<$1>', $html);

        return trim($html);
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
        // O campo laudo_tecnico some do formulário no fechamento Sem Conserto/Recusado (já foi
        // preenchido antes, na etapa de Laudo Técnico) — nesse caso $laudoPost vem null e mantemos
        // o laudo que já existia na OS, em vez de apagar por ausência do campo.
        $laudoPost     = $this->post('laudo_tecnico');
        $laudo         = $laudoPost !== null ? $this->sanitizarLaudoHtml((string) $laudoPost) : null;
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
        $formasOsOk = ['dinheiro', 'pix', 'pix_maquininha', 'cartao_credito', 'cartao_debito', 'transferencia', 'boleto'];
        $pagamentosRaw = json_decode((string) $this->post('pagamentos', '[]'), true);
        $pagamentos = [];
        if (is_array($pagamentosRaw)) {
            foreach ($pagamentosRaw as $p) {
                $f = $p['forma'] ?? '';
                if (!in_array($f, $formasOsOk, true)) continue;
                $v = moeda_float($p['valor'] ?? 0);
                if ($v <= 0) continue;
                $parc = max(1, min(24, (int) ($p['parcelas'] ?? 1)));
                $parcTaxa = $f === 'cartao_credito' ? $parc : 1;
                // "PIX (maquininha)" é só uma opção de UI pra sinalizar que ESSE pix passou pela
                // maquininha (tem taxa) — normalizado pra 'pix' antes de gravar, forma_pagamento
                // no banco não distingue canal. Pix "direto" (sem essa marcação) nunca tem taxa,
                // mesmo que a empresa tenha configurado uma — só o pix da maquininha aplica.
                $comTaxaPix = $f === 'pix_maquininha';
                if ($comTaxaPix) $f = 'pix';
                // Taxa NUNCA vem do formulário — só da config da empresa (Config → Cartões).
                $tx   = (in_array($f, ['cartao_credito', 'cartao_debito'], true) || $comTaxaPix)
                      ? taxa_cartao_configurada($eid, $f, $parcTaxa) : 0.0;
                $pagamentos[] = ['forma' => $f, 'valor' => round($v, 2), 'parcelas' => $parcTaxa, 'taxa' => $tx];
            }
        }
        $ehSplit = (bool) $pagamentos;

        $cartaoRepassar = $this->post('cartao_repassar') === '1';
        if (!$pagamentos) {
            $parcelasFb   = max(1, (int) $this->post('cartao_parcelas', 1));
            $comTaxaPixFb = $formaPagto === 'pix_maquininha';
            $formaFb      = $comTaxaPixFb ? 'pix' : $formaPagto;
            $pagamentos[] = [
                'forma'    => $formaFb,
                // Só o que foi de fato recebido — nunca o total da OS (isso criava um "pendente"
                // fantasma no Financeiro pra OS fechada sem receber nada, ou recebendo só parte).
                'valor'    => $valorPago,
                'parcelas' => $parcelasFb,
                // Taxa NUNCA vem do formulário — só da config da empresa (Config → Cartões).
                'taxa'     => (in_array($formaFb, ['cartao_credito', 'cartao_debito'], true) || $comTaxaPixFb)
                             ? taxa_cartao_configurada($eid, $formaFb, $parcelasFb) : 0.0,
            ];
        }

        $linhasCalc = [];
        foreach ($pagamentos as $p) {
            $ehMaquininhaL = in_array($p['forma'], ['cartao_credito', 'cartao_debito', 'pix'], true);
            $taxaAplicaL = $ehMaquininhaL && $p['taxa'] > 0 && $p['valor'] > 0;
            $cobradoL    = ($taxaAplicaL && $cartaoRepassar) ? round($p['valor'] / (1 - $p['taxa'] / 100), 2) : $p['valor'];
            $taxaValorL  = $taxaAplicaL ? round($cobradoL * $p['taxa'] / 100, 2) : 0.0;
            $linhasCalc[] = $p + ['valor_cobrado' => $cobradoL, 'taxa_valor' => $taxaValorL];
        }
        if ($ehSplit) {
            $valorPago  = array_sum(array_column($pagamentos, 'valor'));
            $formaPagto = count($pagamentos) > 1 ? 'misto' : $pagamentos[0]['forma'];
        }

        $dataConclusao = date('Y-m-d H:i:s');
        $garantiaAte   = date('Y-m-d', strtotime("+{$garantiaDias} days"));

        // Um adiantamento pode já ter sido recebido antes do fechamento (ver
        // adicionarAdiantamento()) — o que entra aqui em $valorPago é só o que está sendo
        // recebido AGORA, no fechamento; pra decidir "pago"/"parcial" e pra valor_pago final,
        // soma com o que a OS já tinha antes, nunca sobrescreve.
        $valorPagoAcumulado = (float) ($os['valor_pago'] ?? 0) + $valorPago;

        $update = [
            'status_id'          => $statusFechado,
            'data_conclusao'     => $os['data_conclusao'] ?: $dataConclusao,
            // Sem Conserto/Recusado não tem garantia (nada foi consertado/entregue) — some o botão "Abrir Garantia".
            'garantia_dias'      => $ehSemConserto ? null : $garantiaDias,
            'garantia_ate'       => $ehSemConserto ? null : $garantiaAte,
            'solucao_aplicada'   => $solucao ?: null,
            'laudo_tecnico'      => $laudoPost !== null ? ($laudo ?: null) : $os['laudo_tecnico'],
            'observacoes_cliente'=> $obsCliente ?: $os['observacoes_cliente'],
            'desconto_valor'     => $descontoValor > 0 ? $descontoValor : null,
            'desconto_percentual'=> $descontoTipo === 'percentual' ? $descontoRaw : null,
            'valor_total'        => $totalFinal,
            'situacao_pagamento' => $ehSemConserto
                ? 'pendente'
                : ($valorPagoAcumulado >= $totalFinal && $totalFinal > 0 ? 'pago' : ($valorPagoAcumulado > 0 ? 'parcial' : $os['situacao_pagamento'])),
            // Marca que este fechamento não gerou receita (Sem Conserto/Recusado) — a view usa isso
            // pra mostrar "Sem Débito" em vermelho em vez de "Pago", mesmo que o valor_total exista
            // (fica só de referência, caso o cliente volte com o mesmo orçamento).
            'fechada_sem_receita'=> $ehSemConserto ? 1 : 0,
            // Só o modal Sem Conserto/Recusado pergunta isso (rádio "Devolvido"/"Descartado") —
            // em qualquer outro fechamento o campo não vem no POST, então fica no default (0).
            'equipamento_descartado' => $ehSemConserto ? ($this->post('equipamento_descartado') ? 1 : 0) : 0,
        ];

        // Só carimba entrega quando fecha de verdade (entregue) — não no "Sem Conserto".
        if (!$ehSemConserto) {
            $update['data_entrega'] = $dataConclusao;
        }

        if ($ehSemConserto) {
            // A recusa em si não gera cobrança nova, mas não apaga um adiantamento genuíno que
            // já tinha sido recebido antes (isso já virou receita lançada na hora — estornar é
            // decisão da assistência, fora do sistema, não um zerar automático aqui).
            $update['valor_pago']                 = (float) ($os['valor_pago'] ?? 0);
            $update['forma_pagamento_fechamento'] = null;
        } elseif ($valorPago > 0) {
            $update['valor_pago']                 = $valorPagoAcumulado;
            $update['forma_pagamento_fechamento'] = $formaPagto;
        }

        $this->model->update((int) $id, $update);
        $this->model->registrarHistorico((int) $id, $os['status_id'], $statusFechado,
            $ehSemConserto
                ? 'OS fechada como "' . ($cur['nome'] ?? 'Sem Conserto') . '" — sem receita.'
                : 'OS fechada. Garantia até ' . date('d/m/Y', strtotime($garantiaAte)) . '.'
        );

        // Lançar no financeiro ao fechar OS — nunca para "Sem Conserto" (sem receita) e nunca
        // sem ter recebido nada de fato: o Financeiro só reflete dinheiro que entrou de verdade,
        // não uma promessa de pagamento (nada de lançamento "pendente" fantasma).
        if (!$ehSemConserto && $totalFinal > 0 && $valorPago > 0) {
            $stmtConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY id LIMIT 1");
            $stmtConta->execute([$eid]);
            $contaId = $stmtConta->fetchColumn() ?: null;

            // Verificar se o FECHAMENTO desta OS já lançou (não checar fin_lancamentos: um
            // adiantamento recebido antes do fechamento — ver adicionarAdiantamento() — já grava
            // sua própria receita lá, e não pode ser confundido com "o fechamento já rodou".
            // os_pagamentos só é gravado por este bloco, então é o guard certo de idempotência.
            $jaLancado = $db->prepare("SELECT COUNT(*) FROM os_pagamentos WHERE os_id = ? AND empresa_id = ?");
            $jaLancado->execute([(int)$id, $eid]);

            if (!$jaLancado->fetchColumn()) {
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
                $descricaoBase = 'OS ' . $os['numero'] . ' — ' . implode(' — ', array_filter([$equipDesc, $info['cliente_nome'] ?? '']));

                // Crédito parcelado no modo "mês a mês" (Config → Cartões): a adquirente repassa 1
                // parcela por mês, então lançamos 1 receita por parcela na data prevista de cada uma
                // (pendente até chegar o dia). Débito, à vista e outras formas: sempre no mesmo dia,
                // porque não há parcela pra espalhar.
                $modoReceb = modo_recebimento_cartao($eid);
                $hoje      = date('Y-m-d');
                $dataBase  = date('Y-m-d', strtotime($dataConclusao));

                // Categoria "Serviços" (receita) — categoriza automaticamente o lançamento gerado pelo fechamento da OS
                $catStmtServ = $db->prepare("SELECT id FROM fin_categorias WHERE empresa_id=? AND tipo='receita' AND nome='Serviços' LIMIT 1");
                $catStmtServ->execute([$eid]);
                $catServico = $catStmtServ->fetchColumn();
                if (!$catServico) {
                    $db->prepare("INSERT INTO fin_categorias (empresa_id, tipo, nome, cor) VALUES (?, 'receita', 'Serviços', '#198754')")->execute([$eid]);
                    $catServico = (int) $db->lastInsertId();
                }

                $insReceita = $db->prepare(
                    "INSERT INTO fin_lancamentos
                     (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao,
                      valor, data_vencimento, data_pagamento, status, forma_pagamento)
                     VALUES (?, ?, ?, ?, ?, ?, 'receita', ?, ?, ?, ?, ?, ?)"
                );

                foreach ($linhasCalc as $l) {
                    $nParc = ($l['forma'] === 'cartao_credito' && $l['parcelas'] > 1 && $modoReceb === 'mes_a_mes')
                           ? $l['parcelas'] : 1;

                    if ($nParc === 1) {
                        $insReceita->execute([
                            $eid, $contaId, $catServico, (int) $id, $os['cliente_id'], $this->usuarioId(),
                            $descricaoBase, $l['valor_cobrado'], $hoje, $hoje, 'pago', $l['forma'],
                        ]);
                        continue;
                    }

                    $base = round($l['valor_cobrado'] / $nParc, 2);
                    $soma = 0.0;
                    for ($i = 1; $i <= $nParc; $i++) {
                        $valorParc = $i < $nParc ? $base : round($l['valor_cobrado'] - $soma, 2);
                        $soma     += $valorParc;
                        $dataVenc  = date('Y-m-d', strtotime($dataBase . ' +' . (($i - 1) * 30) . ' days'));
                        $jaPago    = $dataVenc <= $hoje;
                        $insReceita->execute([
                            $eid, $contaId, $catServico, (int) $id, $os['cliente_id'], $this->usuarioId(),
                            $descricaoBase . " (parcela {$i}/{$nParc})", $valorParc, $dataVenc,
                            $jaPago ? $dataVenc : null, $jaPago ? 'pago' : 'pendente', $l['forma'],
                        ]);
                    }
                }

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
                        if ($l['forma'] === 'pix') {
                            $descTaxa = 'Taxa pix (maquininha) — OS ' . $os['numero'] . ' (' . number_format($l['taxa'], 2, ',', '.') . '%)';
                        } else {
                            $qualCart = $l['forma'] === 'cartao_debito' ? 'débito' : $l['parcelas'] . 'x';
                            $descTaxa = 'Taxa cartão — OS ' . $os['numero'] . ' (' . $qualCart . ' · ' . number_format($l['taxa'], 2, ',', '.') . '%)';
                        }
                        // A taxa é sempre lançada como despesa paga na hora do fechamento, mesmo quando
                        // a receita da parcela é espalhada mês a mês — o custo da maquininha é conhecido
                        // e cobrado de uma vez, independente de quando cada parcela cair na conta.
                        $db->prepare(
                            "INSERT INTO fin_lancamentos
                             (empresa_id, conta_id, categoria_id, os_id, cliente_id, usuario_id, tipo, descricao,
                              valor, data_vencimento, data_pagamento, status, forma_pagamento)
                             VALUES (?, ?, ?, ?, ?, ?, 'despesa', ?, ?, CURDATE(), CURDATE(), 'pago', ?)"
                        )->execute([
                            $eid, $contaId, $catTaxa, (int)$id, $os['cliente_id'], $this->usuarioId(),
                            $descTaxa, $l['taxa_valor'], $l['forma'],
                        ]);
                    }
                }
            }
        }

        log_acao('os', $ehSemConserto ? 'fechar_sem_conserto' : 'fechar', (int) $id, 'OS ' . ($os['numero'] ?? $id) . ' — ' . money((float) $totalFinal));

        if ($ehSemConserto) {
            $this->flash('success', 'OS fechada como "' . ($cur['nome'] ?? 'Sem Conserto') . '" — sem cobrança.');
            $this->redirect(url('/os/' . $id . '/imprimir/sem-conserto'));
        }

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

    /** Busca geral de OS por número/cliente/equipamento (qualquer status) — usado pelo campo
     *  de vincular OS no formulário de evento da Agenda. */
    public function buscarAjax(): void
    {
        $eid = $this->empresaId();
        $q   = trim($this->get('q', ''));
        $db  = DB::pdo();

        $sql = "SELECT os.id, os.numero, os.tipo_servico, os.tecnico_id,
                       c.id AS cliente_id, c.nome AS cliente_nome,
                       t.nome AS tecnico_nome,
                       COALESCE(eq.tipo,'') AS equip_tipo,
                       COALESCE(eq.marca,'') AS equip_marca,
                       COALESCE(eq.modelo,'') AS equip_modelo,
                       s.nome AS status_nome
                FROM ordens_servico os
                JOIN os_status s   ON s.id  = os.status_id
                JOIN clientes c    ON c.id  = os.cliente_id
                LEFT JOIN usuarios t      ON t.id  = os.tecnico_id
                LEFT JOIN equipamentos eq ON eq.id = os.equipamento_id
                WHERE os.empresa_id = ?";
        $params = [$eid];

        if ($q) {
            $sql .= " AND (os.numero LIKE ? OR c.nome LIKE ? OR eq.marca LIKE ? OR eq.modelo LIKE ?)";
            $b = "%{$q}%";
            array_push($params, $b, $b, $b, $b);
        }

        $sql .= " ORDER BY os.criado_em DESC LIMIT 20";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $resultado = array_map(function ($os) {
            $equip = trim($os['equip_tipo'] . ' ' . $os['equip_marca'] . ' ' . $os['equip_modelo']);
            $os['equipamento']     = $equip ?: null;
            $os['titulo_sugerido'] = 'OS ' . $os['numero'] . ($equip ? " - $equip" : '');
            return $os;
        }, $stmt->fetchAll());

        $this->json($resultado);
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

        // Se garantia_ate não foi gravada, calcular a partir da data de ENTREGA (o fechamento de
        // verdade, quando o status vira "entregue") — nunca de data_conclusao (pode ter sido
        // gravada antes, quando o status só virou "Concluída") nem da data de entrada/criação.
        if (!$garantiaAte && $garantiaDias > 0) {
            $dataBase = $os['data_entrega'] ?? null;
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
        $tecnicoId    = $this->validarTecnicoId($eid, $this->post('tecnico_id'))
                      ?? $this->validarTecnicoId($eid, $os['tecnico_id'] ?? null);
        $garantiaDias = (int) ($os['garantia_dias'] ?? 90);
        $numero       = $this->model->proximoNumero();

        // Campos editados do equipamento no passo 3
        $estadoEntrada = $this->post('estado_entrada', $os['estado_entrada'] ?? 'regular');
        $acessorios    = trim((string) $this->post('acessorios', ''));
        $obsCliente    = $this->post('observacoes_cliente', '');
        $obsInternas   = $this->post('observacoes_internas', '');

        // O passo 3 (revisão do equipamento) já bloqueia isso no front, mas nunca confiar só no
        // JS: sem nenhum acessório selecionado (nem o "Sem acessórios" explícito) não pode virar
        // OS de garantia — some sem ninguém perceber e depois é a palavra do técnico contra a do
        // cliente sobre o que veio junto.
        if ($acessorios === '') {
            $this->flash('error', 'Selecione ao menos um acessório (ou "Sem acessórios") antes de criar a OS de garantia.');
            $this->redirect(url('/os'));
        }

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

        $token = bin2hex(random_bytes(16));
        $db->prepare("UPDATE ordens_servico SET token_publico = ? WHERE id = ?")->execute([$token, $novaOsId]);

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

    /**
     * Duplica uma OS: cria uma OS nova para o mesmo cliente/equipamento (reaproveitado,
     * não clonado), com o mesmo defeito relatado como ponto de partida. NÃO copia valores,
     * serviços, peças, histórico ou laudo — começa como uma OS nova de verdade, no primeiro
     * status configurado, pra o usuário revisar/ajustar antes de seguir.
     */
    public function duplicar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirect(url('/os')); }

        $os = $this->model->find((int) $id);
        if (!$os) { $this->flash('error', 'OS não encontrada.'); $this->redirect(url('/os')); }

        $eid = $this->empresaId();
        $db  = DB::pdo();

        $stmtS = $db->prepare("SELECT id FROM os_status WHERE empresa_id = ? ORDER BY ordem LIMIT 1");
        $stmtS->execute([$eid]);
        $statusId = (int) $stmtS->fetchColumn();
        if (!$statusId) { $this->flash('error', 'Nenhum status de OS configurado nessa empresa.'); $this->redirect(url("/os/{$id}")); }

        $numero = $this->model->proximoNumero();
        $data = [
            'numero'          => $numero,
            'cliente_id'      => $os['cliente_id'],
            'equipamento_id'  => $os['equipamento_id'],
            'status_id'       => $statusId,
            'tecnico_id'      => $os['tecnico_id'],
            'recepcionista_id'=> $this->usuarioId(),
            'prioridade'      => $os['prioridade'],
            'tipo_servico'    => 'conserto',
            'defeito_relatado'=> $os['defeito_relatado'],
            'garantia_dias'   => $os['garantia_dias'] ?: 90,
        ];
        $osId = $this->model->insert($data);
        $this->model->registrarHistorico($osId, null, $statusId, 'OS criada a partir da duplicação da OS ' . $os['numero'] . '.');

        $token = bin2hex(random_bytes(16));
        $db->prepare("UPDATE ordens_servico SET token_publico = ? WHERE id = ?")->execute([$token, $osId]);

        log_acao('os', 'criar', $osId, 'OS ' . $numero . ' (duplicada da OS ' . $os['numero'] . ')');
        $avisoLimite = os_checar_limite($eid);
        if ($avisoLimite) $this->flash('warning', $avisoLimite);
        $this->flash('success', "OS: {$numero} criada a partir da OS {$os['numero']}. Revise os dados antes de prosseguir.");
        $this->redirect(url("/os/{$osId}/editar"));
    }
}
