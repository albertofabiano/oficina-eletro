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
            [$whereData, $paramsData] = match ($view) {
                'semana'          => ["a.data_inicio BETWEEN ? AND ?", [$inicioSemana->format('Y-m-d 00:00:00'), $fimSemana->format('Y-m-d 23:59:59')]],
                'dia', 'tecnicos' => ["a.data_inicio BETWEEN ? AND ?", [$dataRef->format('Y-m-d 00:00:00'), $dataRef->format('Y-m-d 23:59:59')]],
                default           => ["MONTH(a.data_inicio) = ? AND YEAR(a.data_inicio) = ?", [$mes, $ano]],
            };

            $where  = "a.empresa_id = ? AND $whereData";
            $params = array_merge([$eid], $paramsData);

            if (count($tiposAtivos) < count($tiposTodos)) {
                $where   .= " AND a.tipo IN (" . implode(',', array_fill(0, count($tiposAtivos), '?')) . ")";
                $params   = array_merge($params, $tiposAtivos);
            }
            if ($statusAtivo !== null) {
                $where   .= " AND a.status = ?";
                $params[] = $statusAtivo;
            }
            if ($usuarioFiltro > 0) {
                $where   .= " AND a.usuario_id = ?";
                $params[] = $usuarioFiltro;
            }

            $stmt = $db->prepare(
                "SELECT a.*, c.nome AS cliente_nome, u.nome AS usuario_nome
                 FROM agenda a
                 LEFT JOIN clientes c ON c.id = a.cliente_id
                 LEFT JOIN usuarios u ON u.id = a.usuario_id
                 WHERE $where
                 ORDER BY a.data_inicio"
            );
            $stmt->execute($params);
            $eventos = $stmt->fetchAll();
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
        if (!csrf_verify()) { $this->flash('error', 'Token inválido.'); $this->redirectBack(); }

        $eid       = $this->empresaId();
        $eventoId  = (int)$this->post('evento_id', 0);

        if ($eventoId) {
            // Edição
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
                $eventoId,
                $eid,
            ]);
            $this->flash('success', 'Evento atualizado!');
        } else {
            // Criação
            DB::pdo()->prepare(
                "INSERT INTO agenda (empresa_id, titulo, descricao, tipo, cliente_id, os_id, usuario_id, data_inicio, data_fim, dia_todo, cor, status)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendado')"
            )->execute([
                $eid,
                $this->post('titulo'),
                $this->post('descricao'),
                $this->post('tipo', 'outro'),
                $this->post('cliente_id') ?: null,
                $this->post('os_id') ?: null,
                $this->post('usuario_id') ?: $this->usuarioId(),
                $this->post('data_inicio'),
                $this->post('data_fim') ?: null,
                $this->post('dia_todo', 0),
                $this->post('cor', '#0d6efd'),
            ]);
            $this->flash('success', 'Evento agendado!');
        }

        $this->redirect(url('/agenda'));
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
        DB::pdo()->prepare("DELETE FROM agenda WHERE id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->empresaId()]);
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
}
