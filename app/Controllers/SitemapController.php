<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class SitemapController extends Controller
{
    /**
     * Sitemap XML dinâmico — inclui páginas estáticas + todos os perfis
     * públicos do diretório (/assistencias/{slug}) + anúncios públicos do
     * marketplace (/pecas/{slug}) + categorias e tópicos do Fórum
     * (/forum/categoria/{id}, /forum/topico/{id}). Serve pro Google descobrir e indexar
     * as milhares de páginas de assistências, peças e tópicos de fórum.
     */
    public function xml(): void
    {
        $base = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'fixaos.com.br');

        // Páginas estáticas (mais importantes primeiro)
        // /encontrar fica fora de propósito — é só um 301 pra /assistencias (já listado
        // abaixo); submeter uma URL que redireciona no sitemap é prática desaconselhada
        // (gera aviso de "página com redirecionamento" no Search Console).
        $estaticas = [
            ['/',             '1.0', 'daily'],
            ['/assistencias', '0.9', 'daily'],
            ['/pecas',        '0.8', 'daily'],
            ['/forum',        '0.8', 'daily'],
            ['/privacidade',  '0.3', 'yearly'],
            ['/termos',       '0.3', 'yearly'],
        ];

        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($estaticas as [$loc, $prio, $freq]) {
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($base . $loc, ENT_XML1) . '</loc>' . "\n";
            echo '    <changefreq>' . $freq . '</changefreq>' . "\n";
            echo '    <priority>' . $prio . '</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        // Perfis públicos do diretório — reivindicada entra sempre que tem nome (um humano já
        // verificou aquele perfil); não reivindicada só entra se o NOME também sinalizar que é
        // do ramo de assistência técnica/conserto (mesmo critério de noindex usado em
        // DiretorioController::empresa() — ver empresa_nome_indica_servico(), filtra o ruído da
        // base de CNPJ importada: empresas de outro ramo dentro da mesma CNAE do setor).
        // Reivindicadas ganham priority um pouco maior (perfil mais completo).
        // REGEXP com \b (fronteira de palavra) em vez de LIKE '%palavra%' — mesma exigência de
        // "palavra inteira" do lado PHP (empresa_nome_indica_servico()), senão "tech" bateria
        // dentro de "Technog"/"Btechstore" aqui mas não lá, sitemap e noindex ficando
        // inconsistentes entre si (achado testando contra a amostra real de produção).
        $db = DB::pdo();
        // (es|s)? antes da borda final: aceita plural (celular/celulares, phone/phones) —
        // mesma tolerância de empresa_nome_indica_servico().
        $regexPalavras = '\\b(' . implode('|', empresa_palavras_servico()) . ')(es|s)?\\b';

        $sql = "SELECT e.slug, e.atualizado_em, e.reivindicada FROM empresas e
                WHERE e.ativo = 1 AND e.listagem_publica = 1
                  AND e.slug IS NOT NULL AND e.slug <> ''
                  AND COALESCE(e.nome_fantasia,'') <> ''
                  AND (e.reivindicada = 1 OR e.nome_fantasia REGEXP ?)
                ORDER BY e.id";
        $stmt = $db->prepare($sql);
        $stmt->execute([$regexPalavras]);
        while ($e = $stmt->fetch()) {
            $lastmod = !empty($e['atualizado_em']) ? substr($e['atualizado_em'], 0, 10) : null;
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($base . '/assistencias/' . $e['slug'], ENT_XML1) . '</loc>' . "\n";
            if ($lastmod) echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>' . (!empty($e['reivindicada']) ? '0.7' : '0.5') . '</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        // Páginas de cidade (/assistencias/{uf}/{cidade-slug}) — só as que passam do mesmo
        // mínimo de empresas usado por DiretorioController::cidade() pra decidir se indexa
        // (abaixo disso a URL nem existe de verdade — redireciona pra busca geral filtrada).
        $stmtCid = $db->query(
            "SELECT uf, cidade, COUNT(*) AS total FROM empresas
              WHERE ativo = 1 AND listagem_publica = 1 AND slug IS NOT NULL AND slug <> ''
                AND uf IS NOT NULL AND uf <> '' AND cidade IS NOT NULL AND cidade <> ''
              GROUP BY uf, cidade
              HAVING total >= " . DiretorioController::MIN_EMPRESAS_PAGINA_CIDADE
        );
        while ($c = $stmtCid->fetch()) {
            $loc = $base . '/assistencias/' . strtolower($c['uf']) . '/' . slugify($c['cidade']);
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($loc, ENT_XML1) . '</loc>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>0.7</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        // Anúncios ativos do marketplace público (/pecas/{slug})
        $stmtP = $db->query(
            "SELECT slug, updated_at FROM marketplace_anuncios
             WHERE status = 'ativo' AND slug IS NOT NULL AND slug <> ''
             ORDER BY id"
        );
        while ($p = $stmtP->fetch()) {
            $lastmod = !empty($p['updated_at']) ? substr($p['updated_at'], 0, 10) : null;
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($base . '/pecas/' . $p['slug'], ENT_XML1) . '</loc>' . "\n";
            if ($lastmod) echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>0.6</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        // Categorias do Fórum (/forum/categoria/{id})
        $stmtFc = $db->query("SELECT id FROM forum_categorias WHERE ativo = 1 ORDER BY ordem");
        while ($fc = $stmtFc->fetch()) {
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($base . '/forum/categoria/' . $fc['id'], ENT_XML1) . '</loc>' . "\n";
            echo '    <changefreq>daily</changefreq>' . "\n";
            echo '    <priority>0.6</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        // Tópicos do Fórum (/forum/topico/{id}) — conteúdo real (defeitos, soluções), o mesmo
        // que já rendia "501 tópicos" numa categoria só sem nenhum estar no sitemap antes disso.
        // Sem filtro de status: forum_topicos não tem soft-delete (excluir() é DELETE de
        // verdade), só depende da categoria estar ativa (join já garante isso).
        $stmtFt = $db->query(
            "SELECT ft.id, ft.atualizado_em FROM forum_topicos ft
             JOIN forum_categorias fc ON fc.id = ft.forum_categoria_id AND fc.ativo = 1
             ORDER BY ft.id"
        );
        while ($ft = $stmtFt->fetch()) {
            $lastmod = !empty($ft['atualizado_em']) ? substr($ft['atualizado_em'], 0, 10) : null;
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($base . '/forum/topico/' . $ft['id'], ENT_XML1) . '</loc>' . "\n";
            if ($lastmod) echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>0.5</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        echo '</urlset>' . "\n";
    }
}
