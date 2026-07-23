<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class ProdutoAuxController extends Controller
{
    private function tabela(string $tipo): string
    {
        return match($tipo) {
            'estados'         => 'produto_estados',
            'tipos'           => 'produto_tipos',
            'marcas'          => 'produto_marcas',
            'categorias'      => 'categorias_produto',
            'equip_tipos'     => 'equip_tipos',
            'equip_marcas'    => 'equip_marcas',
            'equip_acessorios'=> 'equip_acessorios',
            'unidades'        => 'produto_unidades',
            default           => throw new \InvalidArgumentException("Tipo invalido: $tipo"),
        };
    }

    public function listar(string $tipo): void
    {
        $tabela = $this->tabela($tipo);
        $stmt   = DB::pdo()->prepare("SELECT * FROM `{$tabela}` WHERE empresa_id = ? ORDER BY nome");
        $stmt->execute([$this->empresaId()]);
        $this->json($stmt->fetchAll());
    }

    public function salvar(string $tipo): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token invalido'], 403); }

        $tabela = $this->tabela($tipo);
        $eid    = $this->empresaId();
        $id     = (int) $this->post('id');
        $nome   = trim($this->post('nome', ''));

        if (!$nome) { $this->json(['error' => 'Nome obrigatorio'], 422); }

        $db = DB::pdo();
        if ($id) {
            $db->prepare("UPDATE `{$tabela}` SET nome=? WHERE id=? AND empresa_id=?")
               ->execute([$nome, $id, $eid]);
        } else {
            // INSERT IGNORE para não duplicar
            $db->prepare("INSERT IGNORE INTO `{$tabela}` (empresa_id, nome) VALUES (?,?)")
               ->execute([$eid, $nome]);
            $id = (int) $db->lastInsertId();
            // Se INSERT IGNORE ignorou (duplicado), busca o existente
            if (!$id) {
                $stmt = $db->prepare("SELECT id FROM `{$tabela}` WHERE empresa_id=? AND nome=?");
                $stmt->execute([$eid, $nome]);
                $id = (int) $stmt->fetchColumn();
            }
        }

        // Retorna lista atualizada
        $stmt = $db->prepare("SELECT * FROM `{$tabela}` WHERE empresa_id = ? ORDER BY nome");
        $stmt->execute([$eid]);

        $this->json(['success' => true, 'id' => $id, 'nome' => $nome, 'lista' => $stmt->fetchAll()]);
    }

    public function excluir(string $tipo, string $id): void
    {
        $tabela = $this->tabela($tipo);
        $eid    = $this->empresaId();

        // A etiqueta "sem acessórios" é protegida — não pode ser excluída do banco.
        if ($tabela === 'equip_acessorios') {
            $stmtN = DB::pdo()->prepare("SELECT nome FROM `{$tabela}` WHERE id=? AND empresa_id=?");
            $stmtN->execute([(int)$id, $eid]);
            if (mb_strtolower(trim((string) $stmtN->fetchColumn())) === 'sem acessórios') {
                $this->json(['error' => 'A etiqueta "sem acessórios" não pode ser excluída.'], 403);
            }
        }

        DB::pdo()->prepare("DELETE FROM `{$tabela}` WHERE id=? AND empresa_id=?")
                 ->execute([(int)$id, $eid]);

        $stmt = DB::pdo()->prepare("SELECT * FROM `{$tabela}` WHERE empresa_id = ? ORDER BY nome");
        $stmt->execute([$this->empresaId()]);
        $this->json(['success' => true, 'lista' => $stmt->fetchAll()]);
    }

    // ── Acessórios padrão por tipo de equipamento ──────────────────────

    public function acessoriosPadrao(string $equipTipo): void
    {
        $eid  = $this->empresaId();
        $tipo = urldecode($equipTipo);
        $stmt = DB::pdo()->prepare(
            "SELECT acessorios_ids FROM equip_acessorios_padrao
             WHERE empresa_id = ? AND equip_tipo = ?"
        );
        $stmt->execute([$eid, $tipo]);
        $row = $stmt->fetch();
        $ids = $row ? json_decode($row['acessorios_ids'], true) : [];
        $this->json(['ids' => $ids]);
    }

    public function salvarAcessoriosPadrao(): void
    {
        if (!csrf_verify()) { $this->json(['error' => 'Token invalido'], 403); }

        $eid       = $this->empresaId();
        $equipTipo = trim($this->post('equip_tipo', ''));
        $ids       = $this->post('acessorios_ids', []);

        if (!$equipTipo) { $this->json(['error' => 'Tipo de equipamento obrigatorio'], 422); }

        DB::pdo()->prepare(
            "INSERT INTO equip_acessorios_padrao (empresa_id, equip_tipo, acessorios_ids)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE acessorios_ids = VALUES(acessorios_ids)"
        )->execute([$eid, $equipTipo, json_encode($ids)]);

        $this->json(['success' => true]);
    }
}
