<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class ProdutoAuxController extends Controller
{
    // Catálogo de "Acessórios que acompanham" (equip_acessorios) tem teto por empresa -- ao
    // ultrapassar, o(s) item(ns) mais antigo(s) (menor id) são apagados sozinhos, sem exigir
    // que o usuário abra "gerenciar lista" pra limpar manualmente. Só se aplica a esse tipo;
    // os outros catálogos geridos por este controller (estados/tipos/marcas/etc.) continuam
    // sem limite nenhum.
    private const LIMITE_EQUIP_ACESSORIOS = 12;

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

    /**
     * Acha, entre os itens já cadastrados da empresa, um cujo nome normalizado (minúsculo,
     * sem acento) bata com $nome — ou null se nenhum bater. Usado em salvar() em vez de confiar
     * só em INSERT IGNORE: essas tabelas (equip_tipos/equip_acessorios/equip_marcas etc.) não
     * têm migration versionada no repo, então não dá pra garantir que exista uma UNIQUE KEY de
     * verdade, muito menos que ela ignore maiúsculas/acento — "CABO DE FORÇA" e "cabo de forca"
     * já viraram duas linhas reais assim. Comparação sempre em PHP (remover_acentos()), nunca
     * dependendo da collation da coluna.
     */
    private function acharPorNomeNormalizado(string $tabela, int $eid, string $nome, ?int $ignorarId = null): ?array
    {
        $alvo = mb_strtolower(remover_acentos(trim($nome)), 'UTF-8');
        $stmt = DB::pdo()->prepare("SELECT id, nome FROM `{$tabela}` WHERE empresa_id = ?");
        $stmt->execute([$eid]);
        foreach ($stmt->fetchAll() as $row) {
            if ($ignorarId !== null && (int) $row['id'] === $ignorarId) continue;
            if (mb_strtolower(remover_acentos(trim($row['nome'])), 'UTF-8') === $alvo) return $row;
        }
        return null;
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
            // Renomear pra um nome que já existe (ignorando caixa/acento) em OUTRO item da
            // mesma empresa criaria uma duplicata nova — bloqueia em vez de deixar passar.
            $colisao = $this->acharPorNomeNormalizado($tabela, $eid, $nome, $id);
            if ($colisao) { $this->json(['error' => 'Já existe "' . $colisao['nome'] . '" cadastrado.'], 422); }
            $db->prepare("UPDATE `{$tabela}` SET nome=? WHERE id=? AND empresa_id=?")
               ->execute([$nome, $id, $eid]);
        } else {
            // Mesmo nome já cadastrado (ignorando caixa/acento) -- reaproveita o id existente
            // em vez de inserir de novo, não importa o que a constraint do banco garanta.
            $existente = $this->acharPorNomeNormalizado($tabela, $eid, $nome);
            if ($existente) {
                $id = (int) $existente['id'];
            } else {
                $db->prepare("INSERT INTO `{$tabela}` (empresa_id, nome) VALUES (?,?)")
                   ->execute([$eid, $nome]);
                $id = (int) $db->lastInsertId();
                if ($tabela === 'equip_acessorios') $this->limitarCatalogoAcessorios($eid);
            }
        }

        // Retorna lista atualizada
        $stmt = $db->prepare("SELECT * FROM `{$tabela}` WHERE empresa_id = ? ORDER BY nome");
        $stmt->execute([$eid]);

        $this->json(['success' => true, 'id' => $id, 'nome' => $nome, 'lista' => $stmt->fetchAll()]);
    }

    /** Apaga o(s) item(ns) mais antigo(s) (menor id) do catálogo de acessórios da empresa,
     *  só o suficiente pra voltar ao teto de self::LIMITE_EQUIP_ACESSORIOS -- chamado só depois
     *  de um INSERT novo de verdade (renomear um já existente não aumenta a contagem). */
    private function limitarCatalogoAcessorios(int $eid): void
    {
        $db = DB::pdo();
        $st = $db->prepare("SELECT COUNT(*) FROM equip_acessorios WHERE empresa_id = ?");
        $st->execute([$eid]);
        $excesso = (int) $st->fetchColumn() - self::LIMITE_EQUIP_ACESSORIOS;
        if ($excesso <= 0) return;

        $db->prepare(
            "DELETE FROM equip_acessorios WHERE empresa_id = ? ORDER BY id ASC LIMIT " . $excesso
        )->execute([$eid]);
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
