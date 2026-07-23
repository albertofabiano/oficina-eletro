<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class MarketplaceCategoriasController extends Controller
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
        $cats = $this->listar();
        $this->view('marketplace.categorias', [
            'titulo'     => 'Categorias do Marketplace',
            'categorias' => $cats,
        ]);
    }

    public function salvar(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $nome = trim($this->post('nome', ''));
        $icone= trim($this->post('icone', 'bi-tag'));
        $cor  = trim($this->post('cor', '#0d6efd'));
        $id   = (int)$this->post('id', 0);

        if (!$nome) { $this->json(['error' => 'Nome obrigatório'], 422); }

        if ($id) {
            $this->db->prepare(
                "UPDATE marketplace_categorias SET nome=?,icone=?,cor=? WHERE id=? AND empresa_id=?"
            )->execute([$nome, $icone, $cor, $id, $this->eid]);
        } else {
            $maxOrdem = (int)$this->db->query("SELECT COALESCE(MAX(ordem),0)+1 FROM marketplace_categorias WHERE empresa_id={$this->eid}")->fetchColumn();
            $this->db->prepare(
                "INSERT IGNORE INTO marketplace_categorias (empresa_id,nome,icone,cor,ordem) VALUES (?,?,?,?,?)"
            )->execute([$this->eid, $nome, $icone, $cor, $maxOrdem]);
        }

        $this->json(['success' => true, 'categorias' => $this->listar()]);
    }

    public function excluir(string $id): void
    {
        $this->db->prepare(
            "DELETE FROM marketplace_categorias WHERE id=? AND empresa_id=?"
        )->execute([(int)$id, $this->eid]);
        $this->json(['success' => true, 'categorias' => $this->listar()]);
    }

    public function toggleAtivo(string $id): void
    {
        $this->db->prepare(
            "UPDATE marketplace_categorias SET ativo = NOT ativo WHERE id=? AND empresa_id=?"
        )->execute([(int)$id, $this->eid]);
        $this->json(['success' => true, 'categorias' => $this->listar()]);
    }

    private function listar(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM marketplace_categorias WHERE empresa_id=? ORDER BY ordem, nome"
        );
        $stmt->execute([$this->eid]);
        return $stmt->fetchAll();
    }
}
