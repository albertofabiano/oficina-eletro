<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;

class ServicosCatalogoController extends Controller
{
    private \PDO $db;
    private int $eid;

    public function __construct()
    {
        $this->db  = DB::pdo();
        $this->eid = $this->empresaId();
    }

    public function index(): void
    {
        $st = $this->db->prepare(
            "SELECT * FROM servicos_catalogo WHERE empresa_id = ? AND ativo = 1 ORDER BY descricao"
        );
        $st->execute([$this->eid]);

        $this->view('servicos.index', [
            'titulo'   => 'Serviços Cadastrados',
            'servicos' => $st->fetchAll(),
        ]);
    }

    public function salvar(): void
    {
        $this->guard();
        $descricao = trim((string) $this->post('descricao', ''));
        $valor     = moeda_float($this->post('valor_padrao', 0));
        if ($descricao === '') { $this->flash('error', 'Informe a descrição do serviço.'); $this->redirect(url('/servicos')); }

        $this->db->prepare("INSERT INTO servicos_catalogo (empresa_id, descricao, valor_padrao) VALUES (?, ?, ?)")
                 ->execute([$this->eid, $descricao, $valor]);
        $this->flash('success', 'Serviço cadastrado!');
        $this->redirect(url('/servicos'));
    }

    public function atualizar(string $id): void
    {
        $this->guard();
        $descricao = trim((string) $this->post('descricao', ''));
        $valor     = moeda_float($this->post('valor_padrao', 0));
        if ($descricao === '') { $this->flash('error', 'Informe a descrição do serviço.'); $this->redirect(url('/servicos')); }

        $this->db->prepare("UPDATE servicos_catalogo SET descricao = ?, valor_padrao = ? WHERE id = ? AND empresa_id = ?")
                 ->execute([$descricao, $valor, (int) $id, $this->eid]);
        $this->flash('success', 'Serviço atualizado!');
        $this->redirect(url('/servicos'));
    }

    public function excluir(string $id): void
    {
        $this->guard();
        // Soft delete — descrição livre em os_servicos não tem FK aqui, então não há órfão
        // a resolver; mantemos a linha por histórico em vez de excluir de verdade.
        $this->db->prepare("UPDATE servicos_catalogo SET ativo = 0 WHERE id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->eid]);
        $this->flash('success', 'Serviço removido do catálogo.');
        $this->redirect(url('/servicos'));
    }

    /** Exclusão em lote (seleção de várias linhas na tela) — mesmo soft delete de excluir(). */
    public function excluirLote(): void
    {
        if (!csrf_verify()) { $this->json(['sucesso' => false, 'erro' => 'Sessão expirada. Recarregue a página.']); }
        if (!Auth::can('estoque', 'editar')) { $this->json(['sucesso' => false, 'erro' => 'Você não tem permissão para gerenciar o catálogo de serviços.']); }

        $raw = json_decode(file_get_contents('php://input'), true);
        $ids = array_values(array_filter(array_map('intval', $raw['ids'] ?? []), fn($id) => $id > 0));
        if (!$ids) { $this->json(['sucesso' => false, 'erro' => 'Nenhum serviço selecionado.']); }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare(
            "UPDATE servicos_catalogo SET ativo = 0 WHERE empresa_id = ? AND id IN ({$placeholders})"
        );
        $stmt->execute([$this->eid, ...$ids]);

        $this->json(['sucesso' => true, 'removidos' => $stmt->rowCount()]);
    }

    /** Autocomplete usado no modal "Novo Serviço" da OS. */
    public function buscarAjax(): void
    {
        $termo = (string) $this->get('q', '');
        $st = $this->db->prepare(
            "SELECT id, descricao, valor_padrao FROM servicos_catalogo
             WHERE empresa_id = ? AND ativo = 1 AND descricao LIKE ? ORDER BY descricao LIMIT 20"
        );
        $st->execute([$this->eid, "%{$termo}%"]);
        $this->json($st->fetchAll());
    }

    /** csrf + permissão de editar estoque/catálogo. */
    private function guard(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Tente novamente.'); $this->redirect(url('/servicos')); }
        if (!Auth::can('estoque', 'editar')) { $this->flash('error', 'Você não tem permissão para gerenciar o catálogo de serviços.'); $this->redirect(url('/servicos')); }
    }
}
