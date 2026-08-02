<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class FinanceiroCategoriasController extends Controller
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
        $this->view('financeiro.categorias', [
            'titulo'     => 'Categorias Financeiras',
            'categorias' => $this->listar(),
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $nome = trim($this->post('nome', ''));
        $tipo = $this->post('tipo', 'receita');
        $cor  = $this->post('cor', '#6c757d');

        if (!$nome) { $this->json(['error' => 'Nome obrigatório'], 422); }

        $this->db->prepare(
            "INSERT INTO fin_categorias (empresa_id, tipo, nome, cor, status) VALUES (?, ?, ?, ?, 'ativo')"
        )->execute([$this->eid, $tipo, $nome, $cor]);

        $this->json(['success' => true, 'categorias' => $this->listar()]);
    }

    public function atualizar(string $id): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $nome   = trim($this->post('nome', ''));
        $tipo   = $this->post('tipo', 'receita');
        $cor    = $this->post('cor', '#6c757d');
        $status = $this->post('status', 'ativo');

        if (!$nome) { $this->json(['error' => 'Nome obrigatório'], 422); }

        $this->db->prepare(
            "UPDATE fin_categorias SET nome=?, tipo=?, cor=?, status=? WHERE id=? AND empresa_id=?"
        )->execute([$nome, $tipo, $cor, $status, (int)$id, $this->eid]);

        $this->json(['success' => true, 'categorias' => $this->listar()]);
    }

    public function excluir(string $id): void
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) FROM fin_lancamentos WHERE categoria_id = ? AND empresa_id = ?"
        );
        $stmt->execute([(int)$id, $this->eid]);

        if ($stmt->fetchColumn() > 0) {
            $this->db->prepare(
                "UPDATE fin_categorias SET status = 'inativo' WHERE id = ? AND empresa_id = ?"
            )->execute([(int)$id, $this->eid]);

            $this->json([
                'success' => true,
                'aviso'   => 'Categoria possui lançamentos e foi desativada em vez de excluída.',
                'categorias' => $this->listar(),
            ]);
            return;
        }

        $this->db->prepare(
            "DELETE FROM fin_categorias WHERE id = ? AND empresa_id = ?"
        )->execute([(int)$id, $this->eid]);

        $this->json(['success' => true, 'categorias' => $this->listar()]);
    }

    private function listar(): array
    {
        $stmt = $this->db->prepare(
            "SELECT id, nome, tipo, status, cor
             FROM fin_categorias
             WHERE empresa_id = ?
             ORDER BY tipo, nome"
        );
        $stmt->execute([$this->eid]);
        return $stmt->fetchAll();
    }
}
