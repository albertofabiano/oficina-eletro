<?php

namespace App\Controllers\Indica;

use App\Core\Controller;
use App\Core\DB;
use PDO;

/** sitemap.xml próprio do Fixa Indica — isolado do SitemapController do FixaOS principal. */
class SitemapController extends Controller
{
    public function xml(): void
    {
        $db = DB::pdo();
        $urls = [];

        $urls[] = ['loc' => indica_url('/'), 'priority' => '1.0'];
        $urls[] = ['loc' => indica_url('/guias'), 'priority' => '0.7'];

        $categorias = $db->query("SELECT slug FROM indica_categorias WHERE ativa = 1")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($categorias as $c) {
            $urls[] = ['loc' => indica_url('/categoria/' . $c['slug']), 'priority' => '0.8'];
        }

        $produtos = $db->query(
            "SELECT slug, atualizado_em FROM indica_produtos WHERE ativo = 1"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($produtos as $p) {
            $urls[] = [
                'loc'     => indica_url('/produto/' . $p['slug']),
                'lastmod' => substr($p['atualizado_em'], 0, 10),
                'priority' => '0.6',
            ];
        }

        $guias = $db->query(
            "SELECT slug, atualizado_em FROM indica_guias WHERE status = 'publicado'"
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($guias as $g) {
            $urls[] = [
                'loc'     => indica_url('/guias/' . $g['slug']),
                'lastmod' => substr($g['atualizado_em'], 0, 10),
                'priority' => '0.6',
            ];
        }

        header('Content-Type: application/xml; charset=UTF-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo "  <url>\n";
            echo "    <loc>" . e($u['loc']) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                echo "    <lastmod>" . e($u['lastmod']) . "</lastmod>\n";
            }
            echo "    <priority>" . e($u['priority']) . "</priority>\n";
            echo "  </url>\n";
        }
        echo '</urlset>';
        exit;
    }
}
