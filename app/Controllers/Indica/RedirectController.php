<?php

namespace App\Controllers\Indica;

use App\Core\Controller;
use App\Core\DB;

/**
 * /ir/{slug} — ponto único de saída pro Mercado Livre. Grava o clique (sem dado pessoal, LGPD —
 * ver migration 105) e faz 302 pro link_afiliado. rel="nofollow sponsored noopener" e
 * target="_blank" ficam no <a> que aponta pra cá (é atributo de HTML, não de resposta HTTP).
 */
class RedirectController extends Controller
{
    public function ir(string $slug): void
    {
        $db = DB::pdo();
        $stmt = $db->prepare("SELECT id, link_afiliado, link_ml FROM indica_produtos WHERE slug = ? AND ativo = 1 LIMIT 1");
        $stmt->execute([$slug]);
        $produto = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$produto) {
            $this->redirect(indica_url('/'));
            return;
        }

        $destino = $produto['link_afiliado'] ?: $produto['link_ml'];
        if (!$destino) {
            $this->redirect(indica_url('/produto/' . $slug));
            return;
        }

        if (!requisicao_de_robo()) {
            $origem = (string) $this->get('origem', 'produto');
            $ua = (string) ($_SERVER['HTTP_USER_AGENT'] ?? '');
            $uaResumido = $this->resumirUserAgent($ua);

            $ins = $db->prepare(
                "INSERT INTO indica_cliques (produto_id, origem, user_agent_resumido) VALUES (?, ?, ?)"
            );
            $ins->execute([(int) $produto['id'], substr($origem, 0, 40), $uaResumido]);
        }

        $this->redirect($destino);
    }

    /** "mobile Chrome" / "desktop Safari" — nunca a string completa (fingerprintável). */
    private function resumirUserAgent(string $ua): string
    {
        $tipo = preg_match('/Mobile|Android|iPhone/i', $ua) ? 'mobile' : 'desktop';
        $navegador = 'outro';
        if (preg_match('/Edg\//i', $ua)) $navegador = 'Edge';
        elseif (preg_match('/Chrome\//i', $ua)) $navegador = 'Chrome';
        elseif (preg_match('/Firefox\//i', $ua)) $navegador = 'Firefox';
        elseif (preg_match('/Safari\//i', $ua)) $navegador = 'Safari';
        return substr($tipo . ' ' . $navegador, 0, 60);
    }
}
