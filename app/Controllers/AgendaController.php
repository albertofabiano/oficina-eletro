<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Enums\StatusEvento;
use App\Enums\TipoEvento;
use App\Models\Cliente;

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

        if (empty($tiposAtivos)) {
            // Nenhum tipo ativo: não há o que buscar (evita IN () inválido no SQL).
            $eventos = [];
        } else {
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

            // 1) Eventos "normais" no período: não-recorrentes e exceções materializadas
            //    (ocorrência editada isoladamente) que não foram excluídas. Mestres de série
            //    nunca aparecem direto aqui — só via expansão da regra, abaixo, senão a
            //    primeira ocorrência apareceria duplicada.
            $where = "a.empresa_id = ? AND $whereData AND a.rrule IS NULL "
                   . "AND (a.recorrencia_excluida = 0 OR a.recorrencia_excluida IS NULL)" . $filtrosSql;
            $stmt = $db->prepare(
                "SELECT a.*, c.nome AS cliente_nome, u.nome AS usuario_nome, os.numero AS os_numero
                 FROM agenda a
                 LEFT JOIN clientes c ON c.id = a.cliente_id
                 LEFT JOIN usuarios u ON u.id = a.usuario_id
                 LEFT JOIN ordens_servico os ON os.id = a.os_id
                 WHERE $where
                 ORDER BY a.data_inicio"
            );
            $stmt->execute(array_merge([$eid], $paramsData, $paramsFiltros));
            $eventos = $stmt->fetchAll();

            // 2) Mestres de série que batem no filtro de tipo/status/técnico — sem filtro de
            //    data (uma série antiga ainda pode gerar ocorrências dentro da janela atual).
            $stmtM = $db->prepare(
                "SELECT a.*, c.nome AS cliente_nome, u.nome AS usuario_nome, os.numero AS os_numero
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
                // 3) TODAS as exceções desses mestres, sem filtro nenhum — só pra saber quais
                //    datas já têm linha própria (editada OU excluída) e não devem ser geradas
                //    de novo pela expansão da regra.
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
        }

        $stmtU = $db->prepare("SELECT id, nome FROM usuarios WHERE empresa_id = ? AND ativo = 1 ORDER BY nome");
        $stmtU->execute([$eid]);

        $this->view('agenda.index', [
            'titulo'         => 'Agenda',
            'eventos'        => $eventos,
            'mes'            => $mes,
            'ano'            => $ano,
            'dataRef'        => $dataRef->format('Y-m-d'),
            'inicioSemana'   => $inicioSemana->format('Y-m-d'),
            'fimSemana'      => $fimSemana->format('Y-m-d'),
            'view'           => $view,
            'usuarios'       => $stmtU->fetchAll(),
            'tiposAtivos'    => $tiposAtivos,
            'statusAtivo'    => $statusAtivo,
            'usuarioFiltro'  => $usuarioFiltro,
            'temFiltroAtivo' => $temFiltroAtivo,
        ]);
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

        $campos = [
            'titulo'      => $titulo,
            'descricao'   => (string) $this->post('descricao'),
            'tipo'        => (string) $this->post('tipo', 'outro'),
            'usuario_id'  => (int) ($this->post('usuario_id') ?: $this->usuarioId()),
            'data_inicio' => $dataInicio,
            'data_fim'    => $this->post('data_fim') ?: null,
            'cor'         => (string) $this->post('cor', '#0d6efd'),
            'cliente_id'  => $this->post('cliente_id') ?: null,
            'os_id'       => $this->post('os_id') ?: null,
        ];

        if ($recId && $recData) {
            // Editando uma ocorrência de uma série existente (virtual ou já materializada) —
            // "Somente este evento" / "Este e os seguintes" / "Toda a série".
            match ($escopo) {
                'serie'     => $this->editarSerie($recId, $eid, $campos),
                'seguintes' => $this->editarSeguintes($recId, $recData, $eid, $campos),
                default     => $this->editarOcorrenciaUnica($recId, $recData, $eid, $campos),
            };
            $this->flash('success', 'Evento atualizado!');
        } elseif ($eventoId) {
            DB::pdo()->prepare(
                "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?, cliente_id=?, os_id=?
                 WHERE id=? AND empresa_id=?"
            )->execute([
                $campos['titulo'], $campos['descricao'], $campos['tipo'], $campos['usuario_id'],
                $campos['data_inicio'], $campos['data_fim'], $campos['cor'], $campos['cliente_id'], $campos['os_id'],
                $eventoId, $eid,
            ]);
            $this->flash('success', 'Evento atualizado!');
        } else {
            $rrule = agenda_rrule_montar($this->post(), $campos['data_inicio']);
            DB::pdo()->prepare(
                "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, status, rrule)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado', ?)"
            )->execute([
                $eid, $campos['titulo'], $campos['descricao'], $campos['tipo'],
                $campos['cliente_id'], $campos['os_id'], $campos['usuario_id'],
                $campos['data_inicio'], $campos['data_fim'], $this->post('dia_todo', 0), $campos['cor'], $rrule,
            ]);
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

    /** "Toda a série": atualiza os campos-padrão do mestre (título/tipo/técnico/cor/descrição)
     *  e o horário de data_inicio/data_fim — mantém a DATA do mestre (e portanto o padrão de
     *  recorrência) intacta, senão BYMONTHDAY/BYDAY ficariam desalinhados da regra. */
    private function editarSerie(int $masterId, int $eid, array $campos): void
    {
        $master = $this->buscarEvento($masterId, $eid);
        if (!$master) return;

        $duracao = !empty($master['data_fim']) ? (strtotime($master['data_fim']) - strtotime($master['data_inicio'])) : null;

        $dataMaster = substr($master['data_inicio'], 0, 10);
        $horaNova   = trim(substr((string) $campos['data_inicio'], 10)) ?: substr($master['data_inicio'], 10);
        $novoInicio = $dataMaster . $horaNova;
        $novoFim    = $duracao !== null ? date('Y-m-d H:i:s', strtotime($novoInicio) + $duracao) : null;

        DB::pdo()->prepare(
            "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?, cliente_id=?, os_id=?
             WHERE id=? AND empresa_id=?"
        )->execute([
            $campos['titulo'], $campos['descricao'], $campos['tipo'], $campos['usuario_id'],
            $novoInicio, $novoFim, $campos['cor'], $campos['cliente_id'], $campos['os_id'], $masterId, $eid,
        ]);
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

        if ($excecaoId) {
            $db->prepare(
                "UPDATE agenda SET titulo=?, descricao=?, tipo=?, usuario_id=?, data_inicio=?, data_fim=?, cor=?, cliente_id=?, os_id=?, recorrencia_excluida=0
                 WHERE id=? AND empresa_id=?"
            )->execute([
                $campos['titulo'], $campos['descricao'], $campos['tipo'], $campos['usuario_id'],
                $campos['data_inicio'], $campos['data_fim'], $campos['cor'], $campos['cliente_id'], $campos['os_id'],
                $excecaoId, $eid,
            ]);
            return;
        }

        $master = $this->buscarEvento($masterId, $eid);
        if (!$master) return;

        $db->prepare(
            "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, status, recorrencia_id, recorrencia_data_original, recorrencia_excluida)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado', ?, ?, 0)"
        )->execute([
            $eid, $campos['titulo'], $campos['descricao'], $campos['tipo'],
            $campos['cliente_id'], $campos['os_id'], $campos['usuario_id'],
            $campos['data_inicio'], $campos['data_fim'], $master['dia_todo'], $campos['cor'],
            $masterId, $dataOriginal,
        ]);
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
            "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, status, rrule)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado', ?)"
        )->execute([
            $eid, $campos['titulo'], $campos['descricao'], $campos['tipo'],
            $campos['cliente_id'], $campos['os_id'], $campos['usuario_id'],
            $campos['data_inicio'], $campos['data_fim'], $master['dia_todo'], $campos['cor'],
            $this->rruleParaContinuacao($master['rrule']),
        ]);
        $novoMasterId = (int) $db->lastInsertId();

        $db->prepare(
            "UPDATE agenda SET recorrencia_id = ? WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original > ?"
        )->execute([$novoMasterId, $eid, $masterId, $dataOriginal]);

        // A exceção da própria data editada (se existir) some — foi substituída pelo DTSTART
        // do novo mestre.
        $db->prepare(
            "DELETE FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original = ?"
        )->execute([$eid, $masterId, $dataOriginal]);
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

        if ($excecaoId) {
            $db->prepare("UPDATE agenda SET recorrencia_excluida = 1 WHERE id = ? AND empresa_id = ?")
               ->execute([$excecaoId, $eid]);
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

        $db->prepare(
            "DELETE FROM agenda WHERE empresa_id = ? AND recorrencia_id = ? AND recorrencia_data_original >= ?"
        )->execute([$eid, $masterId, $dataOriginal]);
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
