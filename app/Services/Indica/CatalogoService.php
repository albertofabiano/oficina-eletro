<?php

namespace App\Services\Indica;

use App\Core\DB;
use PDO;

/**
 * Fixa Indica não tem Model dedicado (mesmo padrão "nem todo domínio tem Model" já usado no
 * resto do projeto, ex. AgendaController) — as queries de listagem/detalhe do catálogo ficam
 * aqui, direto com DB::pdo(), reaproveitadas pelos controllers públicos e (Fase 2) pelo painel
 * admin.
 */
class CatalogoService
{
    public static function categoriasAtivas(): array
    {
        $db = DB::pdo();
        return $db->query(
            "SELECT * FROM indica_categorias WHERE ativa = 1 ORDER BY ordem ASC, nome ASC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function categoriaPorSlug(string $slug): ?array
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT * FROM indica_categorias WHERE slug = ? AND ativa = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Produtos ativos de uma categoria, mais recentes primeiro. */
    public static function produtosPorCategoria(int $categoriaId, int $limite = 60): array
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM indica_produtos WHERE categoria_id = ? AND ativo = 1
             ORDER BY destaque_home DESC, atualizado_em DESC LIMIT " . max(1, (int) $limite)
        );
        $stmt->execute([$categoriaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function produtoPorSlug(string $slug): ?array
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug
             FROM indica_produtos p
             LEFT JOIN indica_categorias c ON c.id = p.categoria_id
             WHERE p.slug = ? AND p.ativo = 1 LIMIT 1"
        );
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Produtos "relacionados" — mesma categoria do produto atual, excluindo ele mesmo. */
    public static function produtosRelacionados(int $produtoId, ?int $categoriaId, int $limite = 6): array
    {
        if (!$categoriaId) return [];
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM indica_produtos WHERE categoria_id = ? AND ativo = 1 AND id != ?
             ORDER BY destaque_home DESC, atualizado_em DESC LIMIT " . max(1, (int) $limite)
        );
        $stmt->execute([$categoriaId, $produtoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function produtosDestaque(int $limite = 12): array
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM indica_produtos WHERE ativo = 1 AND destaque_home = 1
             ORDER BY atualizado_em DESC LIMIT " . max(1, (int) $limite)
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /** Fallback pra home: se não tiver produto em destaque suficiente, completa com os mais recentes. */
    public static function produtosRecentes(int $limite = 12, array $excetoIds = []): array
    {
        $db = DB::pdo();
        $sql = "SELECT * FROM indica_produtos WHERE ativo = 1";
        $params = [];
        if ($excetoIds) {
            $placeholders = implode(',', array_fill(0, count($excetoIds), '?'));
            $sql .= " AND id NOT IN ($placeholders)";
            $params = $excetoIds;
        }
        $sql .= " ORDER BY atualizado_em DESC LIMIT " . max(1, (int) $limite);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function guiasPublicados(int $limite = 30): array
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM indica_guias WHERE status = 'publicado'
             ORDER BY publicado_em DESC LIMIT " . max(1, (int) $limite)
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function guiaPorSlug(string $slug): ?array
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT * FROM indica_guias WHERE slug = ? AND status = 'publicado' LIMIT 1");
        $stmt->execute([$slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /** Blocos do guia, em ordem — bloco tipo 'produto' já vem com o produto resolvido junto. */
    public static function blocosDoGuia(int $guiaId): array
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM indica_guia_blocos WHERE guia_id = ? ORDER BY ordem ASC"
        );
        $stmt->execute([$guiaId]);
        $blocos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($blocos as &$bloco) {
            $bloco['conteudo'] = json_decode($bloco['conteudo'], true) ?? [];
            if ($bloco['tipo'] === 'produto' && !empty($bloco['conteudo']['produto_id'])) {
                $stmtP = $db->prepare("SELECT * FROM indica_produtos WHERE id = ? AND ativo = 1 LIMIT 1");
                $stmtP->execute([(int) $bloco['conteudo']['produto_id']]);
                $bloco['produto'] = $stmtP->fetch(PDO::FETCH_ASSOC) ?: null;
            }
        }
        unset($bloco);

        return $blocos;
    }

    /** Busca simples por nome/especificação — usada em /busca. */
    public static function buscarProdutos(string $termo, int $limite = 40): array
    {
        $termo = trim($termo);
        if ($termo === '') return [];
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM indica_produtos
             WHERE ativo = 1 AND (nome LIKE ? OR especificacao_curta LIKE ? OR descricao LIKE ?)
             ORDER BY destaque_home DESC, atualizado_em DESC LIMIT " . max(1, (int) $limite)
        );
        $like = '%' . $termo . '%';
        $stmt->execute([$like, $like, $like]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Banners da sidebar (hoje só posição 'sidebar_produto') — lista estática ordenada, sem
     * rotação (ver comentário na migration 108: isso fica pra quando entrar algo tipo AdSense).
     */
    public static function bannersAtivos(string $posicao = 'sidebar_produto'): array
    {
        $db = DB::pdo();
        $stmt = $db->prepare(
            "SELECT * FROM indica_banners WHERE posicao = ? AND ativo = 1 ORDER BY ordem ASC, id ASC"
        );
        $stmt->execute([$posicao]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Contagens reais do catálogo (produtos/categorias/guias ativos) — usadas na seção "Quem
     * indica" da home como prova social, em vez de número inventado: cresce (ou cai) sozinho
     * conforme o admin cadastra, nunca fica desatualizado.
     */
    public static function contagens(): array
    {
        $db = DB::pdo();
        return [
            'produtos'   => (int) $db->query("SELECT COUNT(*) FROM indica_produtos WHERE ativo = 1")->fetchColumn(),
            'categorias' => (int) $db->query("SELECT COUNT(*) FROM indica_categorias WHERE ativa = 1")->fetchColumn(),
            'guias'      => (int) $db->query("SELECT COUNT(*) FROM indica_guias WHERE status = 'publicado'")->fetchColumn(),
        ];
    }

    /** Quais lojas aparecem num conjunto de produtos já carregado — decide se vale mostrar o filtro de loja. */
    public static function lojasPresentes(array $produtos): array
    {
        $lojas = [];
        foreach ($produtos as $p) {
            $lojas[$p['loja'] ?? 'mercado_livre'] = true;
        }
        return array_keys($lojas);
    }

    public static function config(string $chave, ?string $default = null): ?string
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT valor FROM indica_config WHERE chave = ? LIMIT 1");
        $stmt->execute([$chave]);
        $val = $stmt->fetchColumn();
        return $val !== false ? $val : $default;
    }
}
