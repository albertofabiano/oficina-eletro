<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;

class SitemapController extends Controller
{
    /**
     * Sitemap XML dinâmico — inclui páginas estáticas + todos os perfis
     * públicos do diretório (/assistencias/{slug}). Serve pro Google
     * descobrir e indexar as milhares de páginas de assistências.
     */
    public function xml(): void
    {
        $base = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'fixaos.com.br');

        // Páginas estáticas (mais importantes primeiro)
        $estaticas = [
            ['/',             '1.0', 'daily'],
            ['/encontrar',    '0.9', 'daily'],
            ['/assistencias', '0.9', 'daily'],
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

        // Perfis públicos do diretório — SOMENTE fichas com valor (reivindicadas e com
        // conteúdo real). As fichas semeadas/vazias ficam FORA do sitemap e recebem
        // noindex na página, pra não sinalizar "conteúdo raso/automático" ao Google.
        $db  = DB::pdo();
        $sql = "SELECT e.slug, e.atualizado_em FROM empresas e
                WHERE e.ativo = 1 AND e.listagem_publica = 1 AND e.reivindicada = 1
                  AND e.slug IS NOT NULL AND e.slug <> ''
                  AND (
                        COALESCE(e.descricao_publica,'') <> ''
                     OR COALESCE(e.especialidades,'')   <> ''
                     OR EXISTS (SELECT 1 FROM empresa_fotos f WHERE f.empresa_id = e.id)
                     OR EXISTS (SELECT 1 FROM diretorio_avaliacoes a WHERE a.empresa_id = e.id AND a.aprovado = 1 AND a.situacao = 'publicada')
                  )
                ORDER BY e.id";
        $stmt = $db->query($sql);
        while ($e = $stmt->fetch()) {
            $lastmod = !empty($e['atualizado_em']) ? substr($e['atualizado_em'], 0, 10) : null;
            echo '  <url>' . "\n";
            echo '    <loc>' . htmlspecialchars($base . '/assistencias/' . $e['slug'], ENT_XML1) . '</loc>' . "\n";
            if ($lastmod) echo '    <lastmod>' . $lastmod . '</lastmod>' . "\n";
            echo '    <changefreq>weekly</changefreq>' . "\n";
            echo '    <priority>0.7</priority>' . "\n";
            echo '  </url>' . "\n";
        }

        echo '</urlset>' . "\n";
    }
}
