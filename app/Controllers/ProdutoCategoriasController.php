<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\DB;

class ProdutoCategoriasController extends Controller
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
            "SELECT c.id, c.nome, c.pai_id, p.nome AS pai_nome,
                    (SELECT COUNT(*) FROM produtos pr WHERE pr.categoria_id = c.id) AS total_produtos
             FROM categorias_produto c
             LEFT JOIN categorias_produto p ON p.id = c.pai_id
             WHERE c.empresa_id = ?
             ORDER BY COALESCE(p.nome, c.nome), c.nome"
        );
        $st->execute([$this->eid]);

        $this->view('produtos.categorias', [
            'titulo'     => 'Categorias de Produtos',
            'categorias' => $st->fetchAll(),
        ]);
    }

    public function salvar(): void
    {
        $this->guard();
        $nome = trim((string) $this->post('nome', ''));
        $pai  = $this->post('pai_id') ? (int) $this->post('pai_id') : null;
        if ($nome === '') { $this->flash('error', 'Informe o nome da categoria.'); $this->redirect(url('/produtos/categorias')); }

        $this->db->prepare("INSERT INTO categorias_produto (empresa_id, nome, pai_id) VALUES (?, ?, ?)")
                 ->execute([$this->eid, $nome, $pai]);
        $this->flash('success', 'Categoria criada!');
        $this->redirect(url('/produtos/categorias'));
    }

    public function atualizar(string $id): void
    {
        $this->guard();
        $nome = trim((string) $this->post('nome', ''));
        $pai  = $this->post('pai_id') ? (int) $this->post('pai_id') : null;
        if ($nome === '') { $this->flash('error', 'Informe o nome da categoria.'); $this->redirect(url('/produtos/categorias')); }
        if ($pai === (int) $id) $pai = null; // não pode ser pai de si mesma

        $this->db->prepare("UPDATE categorias_produto SET nome = ?, pai_id = ? WHERE id = ? AND empresa_id = ?")
                 ->execute([$nome, $pai, (int) $id, $this->eid]);
        $this->flash('success', 'Categoria atualizada!');
        $this->redirect(url('/produtos/categorias'));
    }

    public function excluir(string $id): void
    {
        $this->guard();
        // Desvincula produtos e subcategorias antes de remover (não deixa referência órfã)
        $this->db->prepare("UPDATE produtos SET categoria_id = NULL WHERE categoria_id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->eid]);
        $this->db->prepare("UPDATE categorias_produto SET pai_id = NULL WHERE pai_id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->eid]);
        $this->db->prepare("DELETE FROM categorias_produto WHERE id = ? AND empresa_id = ?")
                 ->execute([(int) $id, $this->eid]);
        $this->flash('success', 'Categoria excluída.');
        $this->redirect(url('/produtos/categorias'));
    }

    /** csrf + permissão de editar estoque. */
    private function guard(): void
    {
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Tente novamente.'); $this->redirect(url('/produtos/categorias')); }
        if (!Auth::can('estoque', 'editar')) { $this->flash('error', 'Você não tem permissão para gerenciar categorias.'); $this->redirect(url('/produtos/categorias')); }
    }
}
