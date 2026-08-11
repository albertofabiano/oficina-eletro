<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Enums\StatusEvento;
use App\Enums\TipoEvento;
use App\Models\Cliente;
use App\Services\Lembretes\AgendaLembreteService;

class AgendaController extends Controller
{
    public function index(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();

        // Visão do calendário (?view=mes|semana|dia|tecnicos) — sobrevive ao refresh e é
        // compartilhável por link.
        $viewsValidas = ['mes', 'semana', 'dia', 'tecnicos'];
        $view = (string) $this->get('view', 'mes');
        if (!in_array($view, $viewsValidas, true)) $view = 'mes';

        // Data de referência única da tela (?data=Y-m-d) — é o que sobrevive ao trocar de
        // visão. Mês deriva mes/ano dela pra manter a navegação por mês já existente;
        // Semana/Dia/Técnicos usam ela direto (Semana usa o domingo da semana que a contém).
        $dataParam = (string) $this->get('data', '');
        $dataRef   = \DateTime::createFromFormat('Y-m-d', $dataParam) ?: false;
        if (!$dataRef || $dataRef->format('Y-m-d') !== $dataParam) $dataRef = new \DateTime('today');

        $mes = (int) $dataRef->format('n');
        $ano = (int) $dataRef->format('Y');

        $inicioSemana = (clone $dataRef)->modify('-' . $dataRef->format('w') . ' days');
        $fimSemana    = (clone $inicioSemana)->modify('+6 days');

        // Filtros (chips de tipo, dropdown de técnico, dropdown de status) — vivem na
        // URL pra sobreviver ao refresh e serem compartilháveis, e se aplicam à mesma
        // consulta usada por todas as visões (fonte única de dados).
        $tiposTodos  = array_map(fn (TipoEvento $t) => $t->value, TipoEvento::cases());
        $tipoParam   = $this->get('tipo', null);
        $tiposAtivos = $tipoParam === null
            ? $tiposTodos
            : array_values(array_intersect($tiposTodos, array_filter(explode(',', (string) $tipoParam), fn ($v) => $v !== '')));

        $statusTodos = array_map(fn (StatusEvento $s) => $s->value, StatusEvento::cases());
        $statusParam = (string) $this->get('status', '');
        $statusAtivo = in_array($statusParam, $statusTodos, true) ? $statusParam : null;

        $usuarioFiltro  = (int) $this->get('usuario_id', 0);
        $temFiltroAtivo = count($tiposAtivos) !== count($tiposTodos) || $statusAtivo !== null || $usuarioFiltro > 0;

        [$whereData, $paramsData, $janelaDe, $janelaAte] = match ($view) {
            'semana' => [
                "a.data_inicio BETWEEN ? AND ?",
                [$inicioSemana->format('Y-m-d 00:00:00'), $fimSemana->format('Y-m-d 23:59:59')],
                $inicioSemana->format('Y-m-d'), $fimSemana->format('Y-m-d'),
            ],
            'dia', 'tecnicos' => [
                "a.data_inicio BETWEEN ? AND ?",
                [$dataRef->format('Y-m-d 00:00:00'), $dataRef->format('Y-m-d 23:59:59')],
                $dataRef->format('Y-m-d'), $dataRef->format('Y-m-d'),
            ],
            default => [
                "MONTH(a.data_inicio) = ? AND YEAR(a.data_inicio) = ?", [$mes, $ano],
                sprintf('%04d-%02d-01', $ano, $mes), date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano)),
            ],
        };

        $eventos = $this->carregarEventosDaJanela($eid, $whereData, $paramsData, $janelaDe, $janelaAte, $tiposAtivos, $tiposTodos, $statusAtivo, $usuarioFiltro);

        // Indicadores: sempre no escopo do MÊS de $dataRef, independente da visão atual —
        // reaproveita os mesmos filtros ativos. "Eventos no mês" já é o próprio count();
        // "OS agendadas"/"valor a receber" usam a mesma definição (tipo=ordem_servico), pra não
        // inventar uma dimensão de filtro nova só pro card 4 — ver _rodape_agenda.php.
        $janelaMesDe  = sprintf('%04d-%02d-01', $ano, $mes);
        $janelaMesAte = date('Y-m-t', mktime(0, 0, 0, $mes, 1, $ano));
        $eventosDoMes = ($janelaDe === $janelaMesDe && $janelaAte === $janelaMesAte)
            ? $eventos // visão Mês já É o mês inteiro — não repete a consulta
            : $this->carregarEventosDaJanela($eid, "MONTH(a.data_inicio) = ? AND YEAR(a.data_inicio) = ?", [$mes, $ano], $janelaMesDe, $janelaMesAte, $tiposAtivos, $tiposTodos, $statusAtivo, $usuarioFiltro);

        $indicadores = $this->calcularIndicadores($eid, $eventosDoMes);

        // "Próximos 7 dias": sempre a partir de HOJE de verdade (não de $dataRef) e sempre sem
        // os filtros da grade — é um resumo geral de "o que vem por aí", não a mesma visão
        // filtrada do calendário acima (senão filtrar por tipo escondia o resto sem avisar).
        $hojeStr  = date('Y-m-d');
        $prox7De  = $hojeStr;
        $prox7Ate = date('Y-m-d', strtotime($hojeStr . ' +6 days'));
        $proximos7Dias = $this->carregarEventosDaJanela(
            $eid, "a.data_inicio BETWEEN ? AND ?", [$prox7De . ' 00:00:00', $prox7Ate . ' 23:59:59'],
            $prox7De, $prox7Ate, $tiposTodos, $tiposTodos, null, 0
        );

        // Performance: mais de 50 itens (recorrência daily/etc. pode gerar bastante) — pagina
        // em memória em vez de despejar tudo de uma vez (ver ?prox_pagina= na view).
        $prox7Total = count($proximos7Dias);
        $prox7PorPagina = 50;
        $prox7Pagina = max(1, (int) $this->get('prox_pagina', 1));
        $proximos7Dias = array_slice($proximos7Dias, ($prox7Pagina - 1) * $prox7PorPagina, $prox7PorPagina);

        $stmtU = $db->prepare("SELECT id, nome FROM usuarios WHERE empresa_id = ? AND ativo = 1 ORDER BY nome");
        $stmtU->execute([$eid]);
        $usuarios = $stmtU->fetchAll();

        // Pro molde "Gerar lançamento no Financeiro" (evento tipo=financeiro) — mesmas
        // categorias/contas de Configurações → Financeiro, sem duplicar cadastro.
        $stmtFinCat = $db->prepare("SELECT id, tipo, nome FROM fin_categorias WHERE empresa_id = ? ORDER BY tipo, nome");
        $stmtFinCat->execute([$eid]);
        $finCategorias = $stmtFinCat->fetchAll();

        $stmtFinConta = $db->prepare("SELECT id, nome FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY nome");
        $stmtFinConta->execute([$eid]);
        $finContas = $stmtFinConta->fetchAll();

        $this->view('agenda.index', [
            'titulo'         => 'Agenda',
            'eventos'        => $eventos,
            'mes'            => $mes,
            'ano'            => $ano,
            'dataRef'        => $dataRef->format('Y-m-d'),
            'inicioSemana'   => $inicioSemana->format('Y-m-d'),
            'fimSemana'      => $fimSemana->format('Y-m-d'),
            'view'           => $view,
            'usuarios'       => $usuarios,
            'tiposAtivos'    => $tiposAtivos,
            'statusAtivo'    => $statusAtivo,
            'usuarioFiltro'  => $usuarioFiltro,
            'temFiltroAtivo' => $temFiltroAtivo,
            'indicadores'    => $indicadores,
            'proximos7Dias'  => $proximos7Dias,
            'prox7Total'     => $prox7Total,
            'prox7Pagina'    => $prox7Pagina,
            'prox7PorPagina' => $prox7PorPagina,
            'painelHoje'     => $this->painelHoje($eid, $usuarios),
            'finCategorias'  => $finCategorias,
            'finContas'      => $finContas,
        ]);
    }

    /**
     * Busca eventos numa janela (SQL: $whereData/$paramsData; PHP: $janelaDe/$janelaAte pra
     * expansão de recorrência) já com os filtros de tipo/status/técnico aplicados, mestres de
     * série expandidos em ocorrências virtuais e exceções resolvidas — a mesma fonte de dados
     * usada pelas 4 visões, reaproveitada aqui pros indicadores e pra lista Próximos 7 dias.
     */
    private function carregarEventosDaJanela(int $eid, string $whereData, array $paramsData, string $janelaDe, string $janelaAte, array $tiposAtivos, array $tiposTodos, ?string $statusAtivo, int $usuarioFiltro): array
    {
        if (empty($tiposAtivos)) return [];

        $db = DB::pdo();
        $filtrosSql = '';
        $paramsFiltros = [];
        if (count($tiposAtivos) < count($tiposTodos)) {
            $filtrosSql .= " AND a.tipo IN (" . implode(',', array_fill(0, count($tiposAtivos), '?')) . ")";
            $paramsFiltros = array_merge($paramsFiltros, $tiposAtivos);
        }
        if ($statusAtivo !== null) {
            $filtrosSql .= " AND a.status = ?";
            $paramsFiltros[] = $statusAtivo;
        }
        if ($usuarioFiltro > 0) {
            $filtrosSql .= " AND a.usuario_id = ?";
            $paramsFiltros[] = $usuarioFiltro;
        }

        $where = "a.empresa_id = ? AND $whereData AND a.rrule IS NULL "
               . "AND (a.recorrencia_excluida = 0 OR a.recorrencia_excluida IS NULL)" . $filtrosSql;
        $stmt = $db->prepare(
            "SELECT a.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone,
                    CONCAT_WS(', ', NULLIF(CONCAT_WS(', ', c.logradouro, c.numero), ''), c.bairro, c.cidade, c.uf) AS cliente_endereco,
                    u.nome AS usuario_nome, os.numero AS os_numero
             FROM agenda a
             LEFT JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             LEFT JOIN ordens_servico os ON os.id = a.os_id
             WHERE $where
             ORDER BY a.data_inicio"
        );
        $stmt->execute(array_merge([$eid], $paramsData, $paramsFiltros));
        $eventos = $stmt->fetchAll();

        $stmtM = $db->prepare(
            "SELECT a.*, c.nome AS cliente_nome, c.telefone AS cliente_telefone,
                    CONCAT_WS(', ', NULLIF(CONCAT_WS(', ', c.logradouro, c.numero), ''), c.bairro, c.cidade, c.uf) AS cliente_endereco,
                    u.nome AS usuario_nome, os.numero AS os_numero
             FROM agenda a
             LEFT JOIN clientes c ON c.id = a.cliente_id
             LEFT JOIN usuarios u ON u.id = a.usuario_id
             LEFT JOIN ordens_servico os ON os.id = a.os_id
             WHERE a.empresa_id = ? AND a.rrule IS NOT NULL$filtrosSql"
        );
        $stmtM->execute(array_merge([$eid], $paramsFiltros));
        $mestres = $stmtM->fetchAll();

        foreach ($eventos as &$ev) $ev['recorrente'] = !empty($ev['recorrencia_id']);
        unset($ev);

        if ($mestres) {
            $idsM = array_column($mestres, 'id');
            $ph = implode(',', array_fill(0, count($idsM), '?'));
            $stmtExc = $db->prepare(
                "SELECT recorrencia_id, recorrencia_data_original FROM agenda
                 WHERE empresa_id = ? AND recorrencia_id IN ($ph)"
            );
            $stmtExc->execute(array_merge([$eid], $idsM));
            $datasComExcecao = [];
            foreach ($stmtExc->fetchAll() as $r) {
                $datasComExcecao[$r['recorrencia_id'] . ':' . $r['recorrencia_data_original']] = true;
            }

            $textoPorMestre = [];
            foreach ($mestres as $m) {
                $textoPorMestre[$m['id']] = agenda_rrule_descricao($m['rrule'], $m['data_inicio']);
            }
            foreach ($eventos as &$ev) {
                if ($ev['recorrente']) $ev['_rrule_texto'] = $textoPorMestre[$ev['recorrencia_id']] ?? null;
            }
            unset($ev);

            foreach ($mestres as $m) {
                $duracaoSeg = !empty($m['data_fim']) ? (strtotime($m['data_fim']) - strtotime($m['data_inicio'])) : null;
                $ocorrencias = agenda_rrule_expandir($m['data_inicio'], $m['rrule'], $janelaDe, $janelaAte);
                foreach ($ocorrencias as $dtOcorrencia) {
                    $dataChave = substr($dtOcorrencia, 0, 10);
                    if (isset($datasComExcecao[$m['id'] . ':' . $dataChave])) continue;

                    $virtual = $m;
                    $virtual['data_inicio']              = $dtOcorrencia;
                    $virtual['data_fim']                 = $duracaoSeg !== null ? date('Y-m-d H:i:s', strtotime($dtOcorrencia) + $duracaoSeg) : null;
                    $virtual['recorrencia_id']            = $m['id'];
                    $virtual['recorrencia_data_original'] = $dataChave;
                    $virtual['evento_virtual']            = true;
                    $virtual['recorrente']                = true;
                    $virtual['_rrule_texto']               = $textoPorMestre[$m['id']];
                    $eventos[] = $virtual;
                }
            }

            usort($eventos, fn ($a, $b) => strcmp($a['data_inicio'], $b['data_inicio']));
        }

        return $eventos;
    }

    /** Os 4 indicadores do rodapé — sempre a partir do MESMO conjunto de eventos do mês
     *  (já filtrado), pra ficarem consistentes entre si. "Valor a receber" soma o pendente das
     *  OS vinculadas aos eventos do mês (sem duplicar OS que aparece em mais de um evento). */
    private function calcularIndicadores(int $eid, array $eventosDoMes): array
    {
        $emAtraso = 0;
        $osAgendadas = 0;
        $osIds = [];
        foreach ($eventosDoMes as $ev) {
            if (($ev['status'] ?? '') === 'atrasado') $emAtraso++;
            if (($ev['tipo'] ?? '') === 'ordem_servico') $osAgendadas++;
            if (!empty($ev['os_id'])) $osIds[(int) $ev['os_id']] = true;
        }

        $valorPendente = 0.0;
        if ($osIds) {
            $ids = array_keys($osIds);
            $ph = implode(',', array_fill(0, count($ids), '?'));
            $stmt = DB::pdo()->prepare(
                "SELECT COALESCE(SUM(valor_total - valor_pago), 0) FROM ordens_servico
                 WHERE empresa_id = ? AND id IN ($ph) AND situacao_pagamento != 'pago'"
            );
            $stmt->execute(array_merge([$eid], $ids));
            $valorPendente = (float) $stmt->fetchColumn();
        }

        return [
            'eventosNoMes'  => count($eventosDoMes),
            'osAgendadas'   => $osAgendadas,
            'emAtraso'      => $emAtraso,
            'valorPendente' => $valorPendente,
        ];
    }

    /** Painel "Hoje" — resumo operacional pro gestor ver ao abrir a Agenda, sem precisar
     *  navegar pra nenhuma visão específica. Mistura dois mundos: contagens de OS por status
     *  (equipamento aguardando atendimento, orçamento aguardando aprovação, atrasado — mesma
     *  definição de "atrasada" que NotificacaoService::verificarOsAtrasadas() usa, pra não
     *  divergir do que já vira notificação) e a agenda de HOJE (entregas previstas, ocupação
     *  por técnico — reaproveita a jornada configurada em config/eventos_agenda.php, mesma
     *  referência da barra de ocupação da visão Técnicos). */
    private function painelHoje(int $eid, array $usuarios): array
    {
        $stmtOs = DB::pdo()->prepare(
            "SELECT
                SUM(CASE WHEN s.tipo = 'aberta' THEN 1 ELSE 0 END) AS aguardando_atendimento,
                SUM(CASE WHEN s.tipo = 'aguardando' THEN 1 ELSE 0 END) AS aguardando_aprovacao,
                SUM(CASE WHEN os.data_previsao IS NOT NULL AND os.data_previsao < CURDATE()
                         AND s.tipo NOT IN ('entregue','cancelada','concluida') THEN 1 ELSE 0 END) AS atrasados
             FROM ordens_servico os
             JOIN os_status s ON s.id = os.status_id
             WHERE os.empresa_id = ?"
        );
        $stmtOs->execute([$eid]);
        $os = $stmtOs->fetch() ?: [];

        $hojeStr = date('Y-m-d');
        $tiposTodos = array_map(fn (TipoEvento $t) => $t->value, TipoEvento::cases());
        $eventosHoje = $this->carregarEventosDaJanela(
            $eid, "a.data_inicio BETWEEN ? AND ?", [$hojeStr . ' 00:00:00', $hojeStr . ' 23:59:59'],
            $hojeStr, $hojeStr, $tiposTodos, $tiposTodos, null, 0
        );

        $entregasHoje = 0;
        $horasPorUsuario = [];
        foreach ($eventosHoje as $ev) {
            if (($ev['tipo'] ?? '') === TipoEvento::Entrega->value) $entregasHoje++;
            $uid = (int) ($ev['usuario_id'] ?? 0);
            if ($uid <= 0) continue;
            $horasPorUsuario[$uid] = ($horasPorUsuario[$uid] ?? 0) + agenda_evento_duracao_horas($ev);
        }

        $cfgAgenda    = require BASE_PATH . '/config/eventos_agenda.php';
        $jornadaHoras = max(0, (float) $cfgAgenda['jornada']['hora_fim'] - (float) $cfgAgenda['jornada']['hora_inicio']);

        $horasLivres = 0.0;
        $tecnicos = [];
        foreach ($usuarios as $u) {
            $uid = (int) $u['id'];
            $horasAgendadas = $horasPorUsuario[$uid] ?? 0.0;
            $pct = $jornadaHoras > 0 ? (int) round($horasAgendadas / $jornadaHoras * 100) : 0;
            $tecnicos[] = [
                'nome'           => $u['nome'],
                'pct'            => $pct,
                'horasAgendadas' => $horasAgendadas,
                'jornadaHoras'   => $jornadaHoras,
            ];
            $horasLivres += max(0, $jornadaHoras - $horasAgendadas);
        }

        return [
            'aguardandoAtendimento' => (int) ($os['aguardando_atendimento'] ?? 0),
            'entregasHoje'          => $entregasHoje,
            'orcamentosAguardando'  => (int) ($os['aguardando_aprovacao'] ?? 0),
            'servicosAtrasados'     => (int) ($os['atrasados'] ?? 0),
            'horasLivres'           => (int) floor($horasLivres),
            'tecnicos'              => $tecnicos,
        ];
    }

    public function salvar(): void
    {
        $ajax = (bool) $this->post('_ajax', false);

        if (!csrf_verify()) {
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => 'Token inválido. Atualize a página.'], 419); }
            $this->flash('error', 'Token inválido.'); $this->redirectBack();
        }

        $eid      = $this->empresaId();
        $eventoId = (int) $this->post('evento_id', 0);
        $recId    = (int) $this->post('recorrencia_id', 0);
        $recData  = (string) $this->post('recorrencia_data_original', '');
        $escopo   = (string) $this->post('escopo_recorrencia', 'unico');
        $redirectTo = (string) $this->post('redirect_to', '');

        $titulo     = trim((string) $this->post('titulo', ''));
        $dataInicio = trim((string) $this->post('data_inicio', ''));

        // Impede salvar sem título e sem data (o "required" do HTML é só a primeira barreira;
        // aqui é o que realmente vale, inclusive pro caminho AJAX da criação rápida).
        if ($titulo === '' || $dataInicio === '') {
            $erro = 'Preencha o título e a data de início.';
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => $erro], 422); }
            $this->flash('error', $erro);
            $this->redirectBack();
        }

        // Lembretes: internos (técnico) são um CSV de offsets em minutos ("0,15,60,1440" = na
        // hora/15min/1h/1dia antes); cliente é um único lembrete opcional (canal+offset+
        // mensagem). Config vive na própria linha de agenda, igual cliente_id/os_id — ver
        // AgendaLembreteService::reagendar(), chamado no fim deste método.
        $lembreteClienteAtivo = (bool) $this->post('lembrete_cliente_ativo', false);
        // Não usar "implode(...) ?: null": um único offset "0" ("na hora") é string falsy em
        // PHP e viraria null indevidamente — checa o array, não a string resultante.
        $offsetsTecnico = array_map('intval', (array) $this->post('lembrete_tecnico', []));
        // Cor personalizada é opcional (checkbox "Cor personalizada" desativa o campo, que some
        // do POST) — sem escolha nenhuma grava NULL, não um hex padrão: agenda_evento_cor()
        // (_evento.php) usa NULL/vazio como "sem override, usa a cor do Tipo". Valida o formato
        // pra não guardar lixo se o campo for adulterado fora do <input type=color>.
        $corPost = trim((string) $this->post('cor', ''));
        // Molde de lançamento no Financeiro (evento tipo=financeiro) — opcional, igual cor: o
        // checkbox "Gerar lançamento" desativa os campos, que somem do POST. Sem fin_tipo E
        // fin_valor os dois, o molde inteiro vira NULL (ver AgendaController::marcarPago()).
        $finTipoPost  = (string) $this->post('fin_tipo', '');
        $finValorPost = trim((string) $this->post('fin_valor', ''));
        $finAtivo     = in_array($finTipoPost, ['receita', 'despesa'], true) && $finValorPost !== '';
        $campos = [
            'titulo'      => $titulo,
            'descricao'   => (string) $this->post('descricao'),
            'tipo'        => (string) $this->post('tipo', 'outro'),
            'usuario_id'  => (int) ($this->post('usuario_id') ?: $this->usuarioId()),
            'data_inicio' => $dataInicio,
            'data_fim'    => $this->post('data_fim') ?: null,
            'cor'         => preg_match('/^#[0-9a-fA-F]{6}$/', $corPost) ? $corPost : null,
            'alerta_sonoro' => (bool) $this->post('alerta_sonoro', false) ? 1 : 0,
            'fin_tipo'         => $finAtivo ? $finTipoPost : null,
            'fin_valor'        => $finAtivo ? (float) str_replace(['.', ','], ['', '.'], $finValorPost) : null,
            'fin_categoria_id' => $finAtivo ? ((int) $this->post('fin_categoria_id') ?: null) : null,
            'fin_conta_id'     => $finAtivo ? ((int) $this->post('fin_conta_id') ?: null) : null,
            'cliente_id'  => $this->post('cliente_id') ?: null,
            'os_id'       => $this->post('os_id') ?: null,
            'lembrete_tecnico_offsets'   => $offsetsTecnico ? implode(',', $offsetsTecnico) : null,
            'lembrete_cliente_ativo'     => $lembreteClienteAtivo ? 1 : 0,
            'lembrete_cliente_canal'     => $lembreteClienteAtivo ? ((string) $this->post('lembrete_cliente_canal', 'whatsapp') ?: 'whatsapp') : null,
            'lembrete_cliente_offset'    => $lembreteClienteAtivo ? (int) $this->post('lembrete_cliente_offset', 60) : null,
            'lembrete_cliente_mensagem'  => $lembreteClienteAtivo ? (string) $this->post('lembrete_cliente_mensagem', '') : null,
        ];

        if ($recId && $recData) {
            // Editando uma ocorrência de uma série existente (virtual ou já materializada) —
            // "Somente este evento" / "Este e os seguintes" / "Toda a série".
            // "mover_serie" só vem de agendaCommitMover() (arrastar/redimensionar/mover por
            // teclado) — distingue "arrastei a série pra outro dia" (quero deslocar o padrão de
            // recorrência) de um save comum do formulário com escopo "série" (só troca detalhes
            // como título/cor; não deve reancorar a série na data da ocorrência que foi aberta
            // pra edição). Ver editarSerie().
            $moverSerie = (bool) $this->post('mover_serie', false);
            match ($escopo) {
                'serie'     => $this->editarSerie($recId, $eid, $campos, $moverSerie),
                'seguintes' => $this->editarSeguintes($recId, $recData, $eid, $campos),
                default     => $this->editarOcorrenciaUnica($recId, $recData, $eid, $campos),
            };
            $this->flash('success', 'Evento atualizado!');
        } elseif ($eventoId) {
            // Este branch só recebe eventos genuinamente avulsos (série/exceção passa por
            // $recId+$recData acima) — então é aqui, e só aqui, que "Repetir" pode LIGAR pela
            // primeira vez (evento avulso virando série) ou permanecer desligado (repetir=1
            // não veio no POST -> agenda_rrule_montar() devolve null -> rrule fica NULL, igual
            // já era). Faltava isso: o UPDATE nunca gravava rrule, só o INSERT de evento novo.
            $rrule = agenda_rrule_montar($this->post(), $campos['data_inicio']);
            DB::pdo()->prepare(
                "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?, alerta_sonoro=?,
                    fin_tipo=?, fin_valor=?, fin_categoria_id=?, fin_conta_id=?, cliente_id=?, os_id=?, rrule=?,
                    lembrete_tecnico_offsets=?, lembrete_cliente_ativo=?, lembrete_cliente_canal=?, lembrete_cliente_offset=?, lembrete_cliente_mensagem=?
                 WHERE id=? AND empresa_id=?"
            )->execute([
                $campos['titulo'], $campos['descricao'], $campos['tipo'], $campos['usuario_id'],
                $campos['data_inicio'], $campos['data_fim'], $campos['cor'], $campos['alerta_sonoro'],
                $campos['fin_tipo'], $campos['fin_valor'], $campos['fin_categoria_id'], $campos['fin_conta_id'],
                $campos['cliente_id'], $campos['os_id'], $rrule,
                $campos['lembrete_tecnico_offsets'], $campos['lembrete_cliente_ativo'], $campos['lembrete_cliente_canal'],
                $campos['lembrete_cliente_offset'], $campos['lembrete_cliente_mensagem'],
                $eventoId, $eid,
            ]);
            (new AgendaLembreteService())->reagendar($eventoId, $eid);
            $this->flash('success', $rrule ? 'Evento atualizado e agora é uma série!' : 'Evento atualizado!');
        } else {
            $rrule = agenda_rrule_montar($this->post(), $campos['data_inicio']);
            $db = DB::pdo();
            $db->prepare(
                "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, alerta_sonoro,
                    fin_tipo, fin_valor, fin_categoria_id, fin_conta_id, status, rrule,
                    lembrete_tecnico_offsets, lembrete_cliente_ativo, lembrete_cliente_canal, lembrete_cliente_offset, lembrete_cliente_mensagem)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?, ?, ?, ?, ?)"
            )->execute([
                $eid, $campos['titulo'], $campos['descricao'], $campos['tipo'],
                $campos['cliente_id'], $campos['os_id'], $campos['usuario_id'],
                $campos['data_inicio'], $campos['data_fim'], $this->post('dia_todo', 0), $campos['cor'], $campos['alerta_sonoro'],
                $campos['fin_tipo'], $campos['fin_valor'], $campos['fin_categoria_id'], $campos['fin_conta_id'], $rrule,
                $campos['lembrete_tecnico_offsets'], $campos['lembrete_cliente_ativo'], $campos['lembrete_cliente_canal'],
                $campos['lembrete_cliente_offset'], $campos['lembrete_cliente_mensagem'],
            ]);
            (new AgendaLembreteService())->reagendar((int) $db->lastInsertId(), $eid);
            $this->flash('success', $rrule ? 'Série de eventos criada!' : 'Evento agendado!');
        }

        if ($ajax) { $this->json(['sucesso' => true]); }
        $this->redirect($redirectTo !== '' ? $redirectTo : url('/agenda'));
    }

    /** Aviso (não bloqueante) de conflito de horário do técnico — GET /api/agenda/conflito.
     *  Considera eventos normais/exceções e ocorrências de série nesse dia (expande a regra só
     *  pro dia do candidato, é barato). "Conflito" = qualquer evento do mesmo técnico cujo
     *  intervalo cruza o do candidato, exceto o próprio evento sendo editado. */
    public function conflito(): void
    {
        $eid       = $this->empresaId();
        $usuarioId = (int) $this->get('usuario_id', 0);
        $dataInicio = (string) $this->get('data_inicio', '');
        $dataFim    = (string) $this->get('data_fim', '') ?: null;
        $excluirId  = (int) $this->get('excluir_id', 0);

        if (!$usuarioId || !$dataInicio) { $this->json(['conflito' => false]); }

        $ini = strtotime($dataInicio);
        $fim = $dataFim ? strtotime($dataFim) : $ini + 3600;
        if ($fim <= $ini) $fim = $ini + 1800;

        $dia = date('Y-m-d', $ini);
        $db  = DB::pdo();

        $candidatos = [];

        $stmt = $db->prepare(
            "SELECT id, titulo, data_inicio, data_fim FROM agenda
             WHERE empresa_id = ? AND usuario_id = ? AND rrule IS NULL
               AND (recorrencia_excluida = 0 OR recorrencia_excluida IS NULL)
               AND DATE(data_inicio) = ?"
        );
        $stmt->execute([$eid, $usuarioId, $dia]);
        foreach ($stmt->fetchAll() as $ev) $candidatos[] = $ev;

        $stmtM = $db->prepare(
            "SELECT id, titulo, rrule, data_inicio, data_fim FROM agenda
             WHERE empresa_id = ? AND usuario_id = ? AND rrule IS NOT NULL"
        );
        $stmtM->execute([$eid, $usuarioId]);
        foreach ($stmtM->fetchAll() as $m) {
            $duracaoSeg = !empty($m['data_fim']) ? (strtotime($m['data_fim']) - strtotime($m['data_inicio'])) : null;
            foreach (agenda_rrule_expandir($m['data_inicio'], $m['rrule'], $dia, $dia) as $dt) {
                $candidatos[] = [
                    'id' => $m['id'], 'titulo' => $m['titulo'], 'data_inicio' => $dt,
                    'data_fim' => $duracaoSeg !== null ? date('Y-m-d H:i:s', strtotime($dt) + $duracaoSeg) : null,
                ];
            }
        }

        foreach ($candidatos as $ev) {
            if ($excluirId && (int) $ev['id'] === $excluirId) continue;
            $evIni = strtotime($ev['data_inicio']);
            $evFim = !empty($ev['data_fim']) ? strtotime($ev['data_fim']) : $evIni + 3600;
            if ($evFim <= $evIni) $evFim = $evIni + 1800;

            if ($evIni < $fim && $evFim > $ini) {
                $this->json([
                    'conflito' => true,
                    'evento'   => ['titulo' => $ev['titulo'], 'hora' => date('H:i', $evIni) . '–' . date('H:i', $evFim)],
                ]);
            }
        }

        $this->json(['conflito' => false]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid = $this->empresaId();
        DB::pdo()->prepare(
            "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?
             WHERE id=? AND empresa_id=?"
        )->execute([
            $this->post('titulo'),
            $this->post('descricao'),
            $this->post('tipo', 'outro'),
            $this->post('usuario_id') ?: $this->usuarioId(),
            $this->post('data_inicio'),
            $this->post('data_fim') ?: null,
            $this->post('cor', '#0d6efd'),
            (int)$id,
            $eid,
        ]);

        $this->flash('success', 'Evento atualizado!');
        $this->redirect(url('/agenda'));
    }

    public function excluir(string $id): void
    {
        $eid     = $this->empresaId();
        $recData = (string) $this->post('recorrencia_data_original', '');
        $escopo  = (string) $this->post('escopo_recorrencia', 'unico');

        if ($recData) {
            match ($escopo) {
                'serie'     => DB::pdo()->prepare("DELETE FROM agenda WHERE id = ? AND empresa_id = ?")->execute([(int) $id, $eid]),
                'seguintes' => $this->excluirSeguintes((int) $id, $recData, $eid),
                default     => $this->excluirOcorrenciaUnica((int) $id, $recData, $eid),
            };
        } else {
            DB::pdo()->prepare("DELETE FROM agenda WHERE id = ? AND empresa_id = ?")
                     ->execute([(int) $id, $eid]);
        }

        $this->flash('success', 'Evento removido.');
        $this->redirect(url('/agenda'));
    }

    /** Ação rápida de status (Concluir/Cancelar na lista "Próximos 7 dias") — troca só o
     *  status. Ocorrência de série sempre vira exceção "somente este evento": concluir/
     *  cancelar é uma coisa por ocorrência, não faz sentido pedir escopo aqui. */
    public function mudarStatus(string $id): void
    {
        $ajax = (bool) $this->post('_ajax', false);

        if (!csrf_verify()) {
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => 'Token inválido. Atualize a página.'], 419); }
            $this->flash('error', 'Token inválido.'); $this->redirectBack();
        }

        $eid = $this->empresaId();
        $novoStatus = (string) $this->post('status', '');
        $statusValidos = array_map(fn (StatusEvento $s) => $s->value, StatusEvento::cases());
        if (!in_array($novoStatus, $statusValidos, true)) {
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => 'Status inválido.'], 422); }
            $this->flash('error', 'Status inválido.'); $this->redirectBack();
        }

        $recData = (string) $this->post('recorrencia_data_original', '');

        if ($recData) {
            $dataInicio = (string) $this->post('data_inicio', '');
            $dataFim    = $this->post('data_fim') ?: null;
            $this->mudarStatusOcorrenciaUnica((int) $id, $recData, $eid, $novoStatus, $dataInicio, $dataFim);
        } else {
            DB::pdo()->prepare("UPDATE agenda SET status = ? WHERE id = ? AND empresa_id = ?")
                     ->execute([$novoStatus, (int) $id, $eid]);
            // Nunca dispara lembrete de evento cancelado — inclusive quando o cancelamento
            // acontece DEPOIS de já ter enfileirado (ver requisito no CLAUDE.md).
            $lembretes = new AgendaLembreteService();
            if ($novoStatus === 'cancelado') {
                $lembretes->cancelarPendentes((int) $id, $eid, null);
            } else {
                $lembretes->reagendar((int) $id, $eid);
            }
        }

        if ($ajax) { $this->json(['sucesso' => true]); }
        $this->flash('success', 'Status atualizado!');
        $this->redirect(url('/agenda'));
    }

    /** "Marcar como pago/recebido" — só existe pra evento tipo=financeiro com o molde
     *  preenchido (fin_tipo/fin_valor, ver salvar()). Cria o lançamento de verdade no
     *  Fluxo de Caixa e marca o evento como concluído — sempre com 1 clique de confirmação,
     *  nunca automático (ver CLAUDE.md, "Agenda gera lançamento no Financeiro"). Reaproveita
     *  mudarStatusOcorrenciaUnica() pra resolver/materializar a ocorrência (mesma lógica de
     *  "somente este evento" já usada por Concluir/Cancelar), então "marcar como pago" numa
     *  série sempre vira uma exceção — não faz sentido pagar a série inteira de uma vez. */
    public function marcarPago(string $id): void
    {
        $ajax = (bool) $this->post('_ajax', false);

        if (!csrf_verify()) {
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => 'Token inválido. Atualize a página.'], 419); }
            $this->flash('error', 'Token inválido.'); $this->redirectBack();
        }

        $eid     = $this->empresaId();
        $recData = (string) $this->post('recorrencia_data_original', '');
        $db      = DB::pdo();

        if ($recData) {
            $dataInicio = (string) $this->post('data_inicio', '');
            $dataFim    = $this->post('data_fim') ?: null;
            $this->mudarStatusOcorrenciaUnica((int) $id, $recData, $eid, 'concluido', $dataInicio, $dataFim);

            $stmt = $db->prepare(
                "SELECT * FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original = ?"
            );
            $stmt->execute([$eid, (int) $id, $recData]);
            $evento = $stmt->fetch() ?: null;
        } else {
            $db->prepare("UPDATE agenda SET status = 'concluido' WHERE id = ? AND empresa_id = ?")
               ->execute([(int) $id, $eid]);
            (new AgendaLembreteService())->reagendar((int) $id, $eid);
            $evento = $this->buscarEvento((int) $id, $eid);
        }

        if (!$evento) {
            $erro = 'Evento não encontrado.';
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => $erro], 404); }
            $this->flash('error', $erro); $this->redirect(url('/agenda'));
        }

        if (empty($evento['fin_tipo']) || $evento['fin_valor'] === null) {
            $erro = 'Este evento não tem um lançamento configurado — edite o evento e preencha "Gerar lançamento no Financeiro".';
            if ($ajax) { $this->json(['sucesso' => false, 'erro' => $erro], 422); }
            $this->flash('error', $erro); $this->redirect(url('/agenda'));
        }

        // Idempotente: clicar duas vezes (ex.: duplo clique, ou "Desfazer" reaplicado sem
        // querer) não duplica o lançamento — cada linha de agenda só gera um.
        $stmtDup = $db->prepare("SELECT id FROM fin_lancamentos WHERE agenda_id = ? AND empresa_id = ?");
        $stmtDup->execute([(int) $evento['id'], $eid]);
        if (!$stmtDup->fetchColumn()) {
            $contaId = $evento['fin_conta_id'];
            if (!$contaId) {
                $stmtConta = $db->prepare("SELECT id FROM fin_contas WHERE empresa_id = ? AND ativo = 1 ORDER BY id LIMIT 1");
                $stmtConta->execute([$eid]);
                $contaId = $stmtConta->fetchColumn() ?: null;
            }
            $hoje = date('Y-m-d');
            $db->prepare(
                "INSERT INTO fin_lancamentos
                 (empresa_id, conta_id, categoria_id, agenda_id, os_id, cliente_id, tipo, descricao,
                  valor, data_vencimento, data_pagamento, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pago')"
            )->execute([
                $eid, $contaId, $evento['fin_categoria_id'], (int) $evento['id'], $evento['os_id'], $evento['cliente_id'],
                $evento['fin_tipo'], $evento['titulo'], $evento['fin_valor'], $hoje, $hoje,
            ]);
        }

        if ($ajax) { $this->json(['sucesso' => true]); }
        $this->flash('success', $evento['fin_tipo'] === 'receita' ? 'Recebimento lançado!' : 'Pagamento lançado!');
        $this->redirect(url('/agenda'));
    }

    /**
     * Manda pro WhatsApp do técnico/motorista responsável (usuarios.telefone) os dados do
     * atendimento: cliente, endereço, aparelho e o PDF da OS (mesmo orçamento que vai pro
     * cliente) — pra ele ter tudo em mãos antes de sair pra visita/coleta/entrega. Só faz
     * sentido pra evento com técnico E OS vinculados (por isso o botão só aparece nesse caso,
     * ver _proximos7dias.php); não escreve nada na tabela agenda, então não precisa resolver
     * ocorrência de série — usa direto os campos já resolvidos que o JS manda (o evento que o
     * usuário está vendo já é a ocorrência certa, com data/hora efetivas).
     */
    public function enviarInfoTecnico(string $id): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Token inválido. Atualize a página.'], 419); }

        $eid       = $this->empresaId();
        $osId      = (int) $this->post('os_id', 0);
        $usuarioId = (int) $this->post('usuario_id', 0);
        if (!$osId || !$usuarioId) {
            $this->json(['sucesso' => false, 'erro' => 'Este evento precisa ter um técnico e uma OS vinculados.'], 400);
        }

        $db = DB::pdo();
        $stmtU = $db->prepare("SELECT nome, telefone FROM usuarios WHERE id = ? AND empresa_id = ?");
        $stmtU->execute([$usuarioId, $eid]);
        $tecnico = $stmtU->fetch();
        if (!$tecnico) { $this->json(['sucesso' => false, 'erro' => 'Técnico não encontrado.'], 404); }

        $telTecnico = only_numbers((string) ($tecnico['telefone'] ?? ''));
        if (!$telTecnico) {
            $this->json(['sucesso' => false, 'erro' => 'Este técnico não tem telefone/WhatsApp cadastrado (Usuários → editar).'], 400);
        }

        if (\App\Services\WhatsAppService::statusEmpresa($eid) !== 'open') {
            $this->json(['sucesso' => false, 'erro' => 'O WhatsApp da sua empresa não está conectado. Conecte em Configurações → WhatsApp da Empresa.'], 400);
        }

        $os = (new \App\Models\OrdemServico())->findCompleto($osId);
        if (!$os) { $this->json(['sucesso' => false, 'erro' => 'OS não encontrada.'], 404); }

        $dataEvento = (string) $this->post('data_inicio', '');
        $titulo     = trim((string) $this->post('titulo', ''));

        $endereco = implode(', ', array_filter([
            trim(($os['cli_logradouro'] ?? '') . ' ' . ($os['cli_numero'] ?? '')),
            $os['cli_bairro'] ?? '',
            trim(($os['cli_cidade'] ?? '') . (!empty($os['cli_uf']) ? '/' . $os['cli_uf'] : '')),
        ]));

        $linhas = ["*Atendimento — OS {$os['numero']}*"];
        if ($titulo !== '') $linhas[] = $titulo;
        if ($dataEvento !== '') $linhas[] = '🗓️ ' . date('d/m/Y \à\s H:i', strtotime($dataEvento));
        $linhas[] = '';
        $linhas[] = '*Cliente:* ' . ($os['cliente_nome'] ?? '—');
        $telCliente = ($os['cliente_tel'] ?? '') ?: ($os['cliente_whats'] ?? '');
        if ($telCliente) $linhas[] = '*Telefone:* ' . $telCliente;
        if ($endereco !== '') $linhas[] = '*Endereço:* ' . $endereco;
        $linhas[] = '';
        $equip = trim(($os['equip_marca'] ?? '') . ' ' . ($os['equip_modelo'] ?? ''));
        $linhas[] = '*Aparelho:* ' . ($equip !== '' ? $equip : ($os['equip_tipo'] ?? '—'));
        if (!empty($os['defeito_relatado'])) $linhas[] = '*Defeito relatado:* ' . $os['defeito_relatado'];

        $okTexto = \App\Services\WhatsAppService::enviarTexto($eid, $telTecnico, implode("\n", $linhas));

        $stmtCfg = $db->prepare("SELECT chave, valor FROM configuracoes WHERE empresa_id = ?");
        $stmtCfg->execute([$eid]);
        $configs = [];
        foreach ($stmtCfg->fetchAll() as $r) $configs[$r['chave']] = $r['valor'];

        $okPdf = false;
        $html  = $this->renderView('os.print', ['os' => $os, 'configs' => $configs], 'print_orcamento');
        $pdf   = \App\Services\PdfService::fromHtml($html);
        if ($pdf !== null) {
            $fileName = 'os-' . preg_replace('/[^A-Za-z0-9\-]/', '-', $os['numero']) . '.pdf';
            $okPdf = \App\Services\WhatsAppService::enviarDocumento($eid, $telTecnico, base64_encode($pdf), $fileName, 'OS ' . $os['numero']);
        }

        if (!$okTexto && !$okPdf) {
            $this->json(['sucesso' => false, 'erro' => 'Falha no envio pelo WhatsApp.'], 500);
        }
        $this->json(['sucesso' => true]);
    }

    private function mudarStatusOcorrenciaUnica(int $masterId, string $dataOriginal, int $eid, string $novoStatus, string $dataInicio, ?string $dataFim): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT id FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original = ?"
        );
        $stmt->execute([$eid, $masterId, $dataOriginal]);
        $excecaoId = $stmt->fetchColumn();

        $lembretes = new AgendaLembreteService();
        $lembretes->cancelarPendentes($masterId, $eid, $dataOriginal);

        if ($excecaoId) {
            $db->prepare("UPDATE agenda SET status = ?, recorrencia_excluida = 0 WHERE id = ? AND empresa_id = ?")
               ->execute([$novoStatus, $excecaoId, $eid]);
            if ($novoStatus !== 'cancelado') $lembretes->reagendar((int) $excecaoId, $eid);
            return;
        }

        $master = $this->buscarEvento($masterId, $eid);
        if (!$master) return;

        $db->prepare(
            "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, alerta_sonoro,
                fin_tipo, fin_valor, fin_categoria_id, fin_conta_id, status, recorrencia_id, recorrencia_data_original, recorrencia_excluida,
                lembrete_tecnico_offsets, lembrete_cliente_ativo, lembrete_cliente_canal, lembrete_cliente_offset, lembrete_cliente_mensagem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, ?)"
        )->execute([
            $eid, $master['titulo'], $master['descricao'], $master['tipo'],
            $master['cliente_id'], $master['os_id'], $master['usuario_id'],
            $dataInicio ?: $master['data_inicio'], $dataFim, $master['dia_todo'], $master['cor'], $master['alerta_sonoro'],
            $master['fin_tipo'], $master['fin_valor'], $master['fin_categoria_id'], $master['fin_conta_id'], $novoStatus,
            $masterId, $dataOriginal,
            $master['lembrete_tecnico_offsets'], $master['lembrete_cliente_ativo'], $master['lembrete_cliente_canal'],
            $master['lembrete_cliente_offset'], $master['lembrete_cliente_mensagem'],
        ]);
        if ($novoStatus !== 'cancelado') $lembretes->reagendar((int) $db->lastInsertId(), $eid);
    }

    public function eventos(): void
    {
        $eid  = $this->empresaId();
        $ini  = $this->get('start', date('Y-m-01'));
        $fim  = $this->get('end',   date('Y-m-t'));
        $stmt = DB::pdo()->prepare(
            "SELECT a.id, a.titulo AS title, a.data_inicio AS start, a.data_fim AS end,
             a.cor AS color, a.dia_todo AS allDay, a.status, c.nome AS cliente_nome
             FROM agenda a LEFT JOIN clientes c ON c.id = a.cliente_id
             WHERE a.empresa_id = ? AND a.data_inicio BETWEEN ? AND ?
             ORDER BY a.data_inicio"
        );
        $stmt->execute([$eid, $ini, $fim]);
        $this->json($stmt->fetchAll());
    }

    private function buscarEvento(int $id, int $eid): ?array
    {
        $stmt = DB::pdo()->prepare("SELECT * FROM agenda WHERE id = ? AND empresa_id = ?");
        $stmt->execute([$id, $eid]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /** "Toda a série": atualiza os campos-padrão do mestre (título/tipo/técnico/cor/descrição).
     *  Pra data/hora, dois comportamentos possíveis, escolhidos por $moverSerie:
     *  - false (save comum do formulário): mantém a DATA do mestre, só troca a hora — editar
     *    uma ocorrência qualquer da série (ex.: a de dia 19) e salvar como "toda a série" não
     *    pode reancorar a recorrência na data dessa ocorrência aberta, só ajustar horário.
     *  - true (arrastar/redimensionar/mover por teclado, ver "mover_serie" em salvar()): o
     *    usuário moveu a ocorrência pra outro dia com intenção explícita de deslocar a série
     *    inteira — desloca data_inicio por completo. DAILY/WEEKLY/MONTHLY(dia)/YEARLY se
     *    realinham sozinhos (agenda_rrule_expandir() deriva o padrão de data_inicio a cada
     *    expansão); só "mensal por posição" (BYDAY tipo "2MO") guarda a posição como texto
     *    fixo na rrule e precisa ser recalculada pra não continuar caindo no dia antigo. */
    private function editarSerie(int $masterId, int $eid, array $campos, bool $moverSerie = false): void
    {
        $master = $this->buscarEvento($masterId, $eid);
        if (!$master) return;

        $duracao = !empty($master['data_fim']) ? (strtotime($master['data_fim']) - strtotime($master['data_inicio'])) : null;

        // strtotime() aceita tanto "Y-m-d H:i:s" (vem do arrastar/soltar, ver
        // agendaFormatarDatetime() em index.php) quanto "Y-m-d\TH:i" (vem do
        // <input type=datetime-local> do formulário).
        $tsNovo = strtotime((string) $campos['data_inicio']);

        if ($moverSerie && $tsNovo) {
            $novoInicio = date('Y-m-d H:i:s', $tsNovo);
        } else {
            $dataMaster = substr($master['data_inicio'], 0, 10);
            $horaNova   = $tsNovo ? date('H:i:s', $tsNovo) : substr($master['data_inicio'], 11);
            $novoInicio = $dataMaster . ' ' . $horaNova;
        }
        $novoFim = $duracao !== null ? date('Y-m-d H:i:s', strtotime($novoInicio) + $duracao) : null;

        $rruleNovo = $master['rrule'];
        if ($moverSerie && $tsNovo && !empty($master['rrule'])) {
            $regra = agenda_rrule_parse((string) $master['rrule']);
            if (!empty($regra['BYDAY']) && preg_match('/^-?\d+(MO|TU|WE|TH|FR|SA|SU)$/', $regra['BYDAY'])) {
                $regra['BYDAY'] = agenda_rrule_byday_da_data(new DateTime($novoInicio, new DateTimeZone(AGENDA_RRULE_TZ)));
                $partes = [];
                foreach ($regra as $chave => $valor) { $partes[] = "$chave=$valor"; }
                $rruleNovo = implode(';', $partes);
            }
        }

        DB::pdo()->prepare(
            "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?, alerta_sonoro=?,
                fin_tipo=?, fin_valor=?, fin_categoria_id=?, fin_conta_id=?, cliente_id=?, os_id=?, rrule=?,
                lembrete_tecnico_offsets=?, lembrete_cliente_ativo=?, lembrete_cliente_canal=?, lembrete_cliente_offset=?, lembrete_cliente_mensagem=?
             WHERE id=? AND empresa_id=?"
        )->execute([
            $campos['titulo'], $campos['descricao'], $campos['tipo'], $campos['usuario_id'],
            $novoInicio, $novoFim, $campos['cor'], $campos['alerta_sonoro'],
            $campos['fin_tipo'], $campos['fin_valor'], $campos['fin_categoria_id'], $campos['fin_conta_id'],
            $campos['cliente_id'], $campos['os_id'], $rruleNovo,
            $campos['lembrete_tecnico_offsets'], $campos['lembrete_cliente_ativo'], $campos['lembrete_cliente_canal'],
            $campos['lembrete_cliente_offset'], $campos['lembrete_cliente_mensagem'], $masterId, $eid,
        ]);
        (new AgendaLembreteService())->reagendar($masterId, $eid);
    }

    /** "Somente este evento": materializa (ou atualiza) uma linha de exceção pra essa data,
     *  sem mexer na série. */
    private function editarOcorrenciaUnica(int $masterId, string $dataOriginal, int $eid, array $campos): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT id FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original = ?"
        );
        $stmt->execute([$eid, $masterId, $dataOriginal]);
        $excecaoId = $stmt->fetchColumn();

        // A ocorrência materializada passa a ter sua própria linha de fila (agenda_id = dela
        // mesma); o disparo que a série tinha agendado pra essa data (agenda_id = masterId,
        // ocorrencia_data = $dataOriginal) fica órfão e precisa ser cancelado, senão dispara
        // duas vezes.
        $lembretes = new AgendaLembreteService();

        if ($excecaoId) {
            $db->prepare(
                "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?, alerta_sonoro=?,
                    fin_tipo=?, fin_valor=?, fin_categoria_id=?, fin_conta_id=?, cliente_id=?, os_id=?, recorrencia_excluida=0,
                    lembrete_tecnico_offsets=?, lembrete_cliente_ativo=?, lembrete_cliente_canal=?, lembrete_cliente_offset=?, lembrete_cliente_mensagem=?
                 WHERE id=? AND empresa_id=?"
            )->execute([
                $campos['titulo'], $campos['descricao'], $campos['tipo'], $campos['usuario_id'],
                $campos['data_inicio'], $campos['data_fim'], $campos['cor'], $campos['alerta_sonoro'],
                $campos['fin_tipo'], $campos['fin_valor'], $campos['fin_categoria_id'], $campos['fin_conta_id'],
                $campos['cliente_id'], $campos['os_id'],
                $campos['lembrete_tecnico_offsets'], $campos['lembrete_cliente_ativo'], $campos['lembrete_cliente_canal'],
                $campos['lembrete_cliente_offset'], $campos['lembrete_cliente_mensagem'],
                $excecaoId, $eid,
            ]);
            $lembretes->cancelarPendentes($masterId, $eid, $dataOriginal);
            $lembretes->reagendar((int) $excecaoId, $eid);
            return;
        }

        $master = $this->buscarEvento($masterId, $eid);
        if (!$master) return;

        $db->prepare(
            "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, alerta_sonoro,
                fin_tipo, fin_valor, fin_categoria_id, fin_conta_id, status, recorrencia_id, recorrencia_data_original, recorrencia_excluida,
                lembrete_tecnico_offsets, lembrete_cliente_ativo, lembrete_cliente_canal, lembrete_cliente_offset, lembrete_cliente_mensagem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?, 0, ?, ?, ?, ?, ?)"
        )->execute([
            $eid, $campos['titulo'], $campos['descricao'], $campos['tipo'],
            $campos['cliente_id'], $campos['os_id'], $campos['usuario_id'],
            $campos['data_inicio'], $campos['data_fim'], $master['dia_todo'], $campos['cor'], $campos['alerta_sonoro'],
            $campos['fin_tipo'], $campos['fin_valor'], $campos['fin_categoria_id'], $campos['fin_conta_id'],
            $masterId, $dataOriginal,
            $campos['lembrete_tecnico_offsets'], $campos['lembrete_cliente_ativo'], $campos['lembrete_cliente_canal'],
            $campos['lembrete_cliente_offset'], $campos['lembrete_cliente_mensagem'],
        ]);
        $lembretes->cancelarPendentes($masterId, $eid, $dataOriginal);
        $lembretes->reagendar((int) $db->lastInsertId(), $eid);
    }

    /** "Este e os seguintes": encerra a série antiga na véspera dessa ocorrência e cria uma
     *  nova série (mesma frequência/intervalo/posição) a partir dos campos editados; exceções
     *  futuras migram pra série nova. */
    private function editarSeguintes(int $masterId, string $dataOriginal, int $eid, array $campos): void
    {
        $db = DB::pdo();
        $master = $this->buscarEvento($masterId, $eid);
        if (!$master || empty($master['rrule'])) return;

        $db->prepare("UPDATE agenda SET rrule = ? WHERE id = ? AND empresa_id = ?")
           ->execute([$this->truncarRruleAntesDe($master['rrule'], $dataOriginal), $masterId, $eid]);

        $db->prepare(
            "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, alerta_sonoro,
                fin_tipo, fin_valor, fin_categoria_id, fin_conta_id, status, rrule,
                lembrete_tecnico_offsets, lembrete_cliente_ativo, lembrete_cliente_canal, lembrete_cliente_offset, lembrete_cliente_mensagem)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?, ?, ?, ?, ?)"
        )->execute([
            $eid, $campos['titulo'], $campos['descricao'], $campos['tipo'],
            $campos['cliente_id'], $campos['os_id'], $campos['usuario_id'],
            $campos['data_inicio'], $campos['data_fim'], $master['dia_todo'], $campos['cor'], $campos['alerta_sonoro'],
            $campos['fin_tipo'], $campos['fin_valor'], $campos['fin_categoria_id'], $campos['fin_conta_id'],
            $this->rruleParaContinuacao($master['rrule']),
            $campos['lembrete_tecnico_offsets'], $campos['lembrete_cliente_ativo'], $campos['lembrete_cliente_canal'],
            $campos['lembrete_cliente_offset'], $campos['lembrete_cliente_mensagem'],
        ]);
        $novoMasterId = (int) $db->lastInsertId();

        $db->prepare(
            "UPDATE agenda SET recorrencia_id = ? WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original > ?"
        )->execute([$novoMasterId, $eid, $masterId, $dataOriginal]);

        // A exceção da própria data editada (se existir) some — foi substituída pelo DTSTART
        // do novo mestre. Cascade de FK já limpa a fila de lembretes dela.
        $db->prepare(
            "DELETE FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original = ?"
        )->execute([$eid, $masterId, $dataOriginal]);

        $lembretes = new AgendaLembreteService();
        $lembretes->reagendar($masterId, $eid);    // série antiga, agora truncada: encolhe a janela de lembretes
        $lembretes->reagendar($novoMasterId, $eid); // série nova: agenda a janela dela
    }

    /** "Somente este evento" (excluir): marca uma linha de exceção como excluída (equivalente
     *  a um EXDATE) — a ocorrência simplesmente para de ser gerada, sem mexer na série. */
    private function excluirOcorrenciaUnica(int $masterId, string $dataOriginal, int $eid): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT id FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original = ?"
        );
        $stmt->execute([$eid, $masterId, $dataOriginal]);
        $excecaoId = $stmt->fetchColumn();

        // Nunca dispara lembrete de ocorrência excluída — cancela tanto o disparo que a série
        // tinha agendado pra essa data quanto (se já existia) o da própria exceção.
        $lembretes = new AgendaLembreteService();
        $lembretes->cancelarPendentes($masterId, $eid, $dataOriginal);

        if ($excecaoId) {
            $db->prepare("UPDATE agenda SET recorrencia_excluida = 1 WHERE id = ? AND empresa_id = ?")
               ->execute([$excecaoId, $eid]);
            $lembretes->cancelarPendentes((int) $excecaoId, $eid, null);
            return;
        }

        $master = $this->buscarEvento($masterId, $eid);
        if (!$master) return;

        $db->prepare(
            "INSERT INTO agenda (empresa_id, titulo, tipo, usuario_id, data_inicio, dia_todo, cor, status, recorrencia_id, recorrencia_data_original, recorrencia_excluida)
             VALUES (?, ?, ?, ?, ?, ?, ?, 'cancelado', ?, ?, 1)"
        )->execute([
            $eid, $master['titulo'], $master['tipo'], $master['usuario_id'],
            $dataOriginal . ' 00:00:00', $master['dia_todo'], $master['cor'],
            $masterId, $dataOriginal,
        ]);
    }

    /** "Este e os seguintes" (excluir): a série simplesmente termina na véspera dessa
     *  ocorrência; exceções futuras ficam órfãs do propósito e são removidas junto. */
    private function excluirSeguintes(int $masterId, string $dataOriginal, int $eid): void
    {
        $db = DB::pdo();
        $master = $this->buscarEvento($masterId, $eid);
        if (!$master || empty($master['rrule'])) return;

        $db->prepare("UPDATE agenda SET rrule = ? WHERE id = ? AND empresa_id = ?")
           ->execute([$this->truncarRruleAntesDe($master['rrule'], $dataOriginal), $masterId, $eid]);

        // Exceções futuras cascade-deletam sua própria fila de lembretes (FK); a fatia futura
        // da série em si é encolhida por reagendar() logo abaixo (regenera só até o novo UNTIL).
        $db->prepare(
            "DELETE FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original >= ?"
        )->execute([$eid, $masterId, $dataOriginal]);

        (new AgendaLembreteService())->reagendar($masterId, $eid);
    }

    /** Regra truncada com UNTIL na véspera de $dataOriginal (mantém FREQ/INTERVAL/BYDAY,
     *  descarta COUNT/UNTIL antigos — a série "acaba aqui" independente de como terminava). */
    private function truncarRruleAntesDe(string $rruleAtual, string $dataOriginal): string
    {
        $diaAntes = date('Ymd', strtotime($dataOriginal . ' -1 day'));
        $regra = agenda_rrule_parse($rruleAtual);
        $partes = ['FREQ=' . ($regra['FREQ'] ?? 'DAILY')];
        if (!empty($regra['INTERVAL'])) $partes[] = "INTERVAL={$regra['INTERVAL']}";
        if (!empty($regra['BYDAY']))    $partes[] = "BYDAY={$regra['BYDAY']}";
        $partes[] = "UNTIL=$diaAntes";
        return implode(';', $partes);
    }

    /** Regra da série nova ("este e os seguintes"): mesma frequência/intervalo/posição; COUNT
     *  não é levado adiante (contava a partir do início antigo, não faz mais sentido numa
     *  série que recomeça noutra data) — UNTIL, se houver, continua valendo. */
    private function rruleParaContinuacao(string $rruleAntiga): string
    {
        $regra = agenda_rrule_parse($rruleAntiga);
        $partes = ['FREQ=' . ($regra['FREQ'] ?? 'DAILY')];
        if (!empty($regra['INTERVAL'])) $partes[] = "INTERVAL={$regra['INTERVAL']}";
        if (!empty($regra['BYDAY']))    $partes[] = "BYDAY={$regra['BYDAY']}";
        if (!empty($regra['UNTIL']))    $partes[] = "UNTIL={$regra['UNTIL']}";
        return implode(';', $partes);
    }
}
