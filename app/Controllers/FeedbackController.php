<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Core\Auth;
use App\Services\EmailService;

class FeedbackController extends Controller
{
    /** E-mail que recebe os feedbacks do sistema. */
    private const SUPORTE = 'suporte@fixaos.com.br';

    /** Recebe crítica / elogio / sugestão do usuário logado (modal do sistema). */
    public function enviar(): void
    {
        $back = $_SERVER['HTTP_REFERER'] ?? url('/');
        if (!csrf_verify()) { $this->flash('error', 'Sessão expirada. Tente novamente.'); $this->redirect($back); }

        $tipo = $this->post('tipo', 'sugestao');
        if (!in_array($tipo, ['critica', 'elogio', 'sugestao'], true)) $tipo = 'sugestao';

        $msg = trim($this->post('mensagem', ''));
        if ($msg === '') { $this->flash('error', 'Escreva sua mensagem antes de enviar.'); $this->redirect($back); }

        $msg    = mb_substr($msg, 0, 2000);
        $pagina = mb_substr(trim($this->post('pagina', '')), 0, 255) ?: null;
        $eid    = $this->empresaId();
        $uid    = Auth::user()['id'] ?? null;

        DB::pdo()->prepare("INSERT INTO feedbacks (empresa_id, usuario_id, tipo, mensagem, pagina) VALUES (?,?,?,?,?)")
            ->execute([$eid, $uid, $tipo, $msg, $pagina]);

        // Notifica o suporte por e-mail — best-effort (o feedback já ficou salvo no painel).
        try {
            $this->notificarSuporte($tipo, $msg, $pagina, $eid, $uid);
        } catch (\Throwable $ex) {
            error_log('[feedback] falha ao enviar e-mail ao suporte: ' . $ex->getMessage());
        }

        $this->flash('success', 'Feedback enviado! Obrigado por ajudar a melhorar o FixaOS. 💚');
        $this->redirect($back);
    }

    /** Monta e envia o e-mail do feedback para o suporte. */
    private function notificarSuporte(string $tipo, string $msg, ?string $pagina, int $eid, $uid): void
    {
        $db = DB::pdo();

        $u = $db->prepare("SELECT nome, email FROM usuarios WHERE id = ?");
        $u->execute([$uid]);
        $usr = $u->fetch() ?: [];

        $e = $db->prepare("SELECT COALESCE(NULLIF(nome_fantasia, ''), razao_social) AS nome FROM empresas WHERE id = ?");
        $e->execute([$eid]);
        $empNome = (string) ($e->fetchColumn() ?: ('Empresa #' . $eid));

        $labels  = ['critica' => '🔴 Crítica', 'elogio' => '💚 Elogio', 'sugestao' => '💡 Sugestão'];
        $rot     = $labels[$tipo] ?? ucfirst($tipo);
        $assunto = "[Feedback FixaOS] {$rot} — {$empNome}";

        $html = '<div style="font-family:Arial,sans-serif;max-width:560px;color:#0f172a">'
              . '<h2 style="margin:0 0 10px;font-size:18px">' . $rot . '</h2>'
              . '<div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px 14px;white-space:pre-wrap;font-size:15px;line-height:1.5">' . e($msg) . '</div>'
              . '<table style="margin-top:14px;font-size:13px;color:#475569">'
              . '<tr><td style="padding:2px 10px 2px 0"><b>Empresa</b></td><td>' . e($empNome) . ' (#' . $eid . ')</td></tr>'
              . '<tr><td style="padding:2px 10px 2px 0"><b>Usuário</b></td><td>' . e($usr['nome'] ?? '—') . ' &lt;' . e($usr['email'] ?? '—') . '&gt;</td></tr>'
              . '<tr><td style="padding:2px 10px 2px 0"><b>Página</b></td><td>' . e($pagina ?: '—') . '</td></tr>'
              . '<tr><td style="padding:2px 10px 2px 0"><b>Quando</b></td><td>' . date('d/m/Y H:i') . '</td></tr>'
              . '</table>'
              . '<p style="margin-top:14px"><a href="' . url('/master/feedbacks') . '" style="color:#0d9488">Ver no painel de feedbacks →</a></p>'
              . '</div>';

        EmailService::send(self::SUPORTE, 'Suporte FixaOS', $assunto, $html);
    }
}
