<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class ProdutoAuxController extends Controller
{
    private function tabela(string $tipo): string
    {
        return match($tipo) {
            'estados' => 'produto_estados',
            'tipos'   => 'produto_tipos',
            'marcas'  => 'produto_marcas',
            default   => throw new \InvalidArgumentException("Tipo inválido: $tipo"),
        };
    }

    public function listar(string $tipo): void
    {
        $tabela = $this->tabela($tipo);
        $stmt   = DB::pdo()->prepare("SELECT * FROM {$tabela} WHERE empresa_id = ? ORDER BY nome");
        $stmt->execute([$this->empresaId()]);
        $this->json($stmt->fetchAll());
    }

    public function salvar(string $tipo): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token inválido'], 403); }

        $tabela = $this->tabela($tipo);
        $eid    = $this->empresaId();
        $id     = (int) $this->post('id');
        $nome   = trim($this->post('nome', ''));

        if (!$nome) { $this->json(['error' => 'Nome obrigatório'], 422); }

        $db = DB::pdo();
        if ($id) {
            $db->prepare("UPDATE {$tabela} SET nome=? WHERE id=? AND empresa_id=?")
               ->execute([$nome, $id, $eid]);
        } else {
            $db->prepare("INSERT INTO {$tabela} (empresa_id, nome) VALUES (?,?)")
               ->execute([$eid, $nome]);
            $id = (int) $db->lastInsertId();
        }

        $this->json(['success' => true, 'id' => $id, 'nome' => $nome]);
    }

    public function excluir(string $tipo, string $id): void
    {
        $tabela = $this->tabela($tipo);
        DB::pdo()->prepare("DELETE FROM {$tabela} WHERE id=? AND empresa_id=?")
                 ->execute([(int)$id, $this->empresaId()]);
        $this->json(['success' => true]);
    }
}
