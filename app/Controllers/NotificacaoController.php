<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\NotificacaoService;

class NotificacaoController extends Controller
{
    /** Roda gerarTodas() no MÁXIMO 1x a cada 5 min por empresa (marker por arquivo em /tmp). */
    private static function gerarThrottled(int $eid): void
    {
        $marker = sys_get_temp_dir() . '/fixaos_notif_' . $eid;
        if (!is_file($marker) || (time() - filemtime($marker)) > 300) {
            @touch($marker);
            (new NotificacaoService($eid))->gerarTodas();
        }
    }

    public function index(): void
    {
        $eid  = $this->empresaId();

        // Gerar notificações automáticas (throttled — no máx. 1x/5min por empresa)
        self::gerarThrottled($eid);

        $notificacoes = NotificacaoService::buscar($eid, 50);
        $this->view('notificacoes.index', ['titulo' => 'Notificações', 'notificacoes' => $notificacoes]);
    }

    public function marcarLida(string $id): void
    {
        NotificacaoService::marcarLida((int)$id, $this->empresaId());
        $link = $_GET['redirect'] ?? url('/notificacoes');
        $this->redirect($link);
    }

    public function marcarTodas(): void
    {
        NotificacaoService::marcarTodasLidas($this->empresaId());
        $this->json(['ok' => true]);
    }

    public function excluir(string $id): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido.'], 403); }
        NotificacaoService::excluir((int) $id, $this->empresaId());
        $this->json(['ok' => true]);
    }

    public function limparTodas(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Token inválido.'], 403); }
        NotificacaoService::limparTodas($this->empresaId());
        $this->json(['ok' => true]);
    }

    public function api(): void
    {
        $eid = $this->empresaId();
        // Polling (chamado periodicamente): libera a trava da sessão imediatamente
        // pra não enfileirar nem segurar a navegação do usuário (evita 504 em cascata).
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }

        // Gerar automaticamente, mas NO MÁXIMO 1x a cada 5 min por empresa.
        // (Antes rodava a CADA poll de ~10s → varria milhares de OSs e sufocava o MySQL → load 50, site fora.)
        self::gerarThrottled($eid);

        $total = NotificacaoService::contar($eid);
        $lista = NotificacaoService::buscar($eid, 8);

        $this->json(['total' => $total, 'lista' => $lista]);
    }

    /** Status do chat interno pro sino de chat no topo (contador + itens recentes + id p/ som). */
    public function chatStatus(): void
    {
        $eid = $this->empresaId();
        $uid = $this->usuarioId();
        if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); } // polling só-leitura: solta a trava

        $db = DB::pdo();
        $st = $db->prepare("SELECT chat_visto_id FROM usuarios WHERE id = ?");
        $st->execute([$uid]);
        $visto = (int) ($st->fetchColumn() ?: 0);

        $st = $db->prepare("SELECT COUNT(*) FROM os_mensagens WHERE empresa_id = ? AND id > ? AND usuario_id <> ?");
        $st->execute([$eid, $visto, $uid]);
        $naoLidas = (int) $st->fetchColumn();

        $st = $db->prepare("SELECT COALESCE(MAX(id),0) FROM os_mensagens WHERE empresa_id = ?");
        $st->execute([$eid]);
        $ultimo = (int) $st->fetchColumn();

        $st = $db->prepare(
            "SELECT m.os_id, o.numero, u.nome AS usuario_nome, m.mensagem,
                    DATE_FORMAT(m.criado_em, '%d/%m %H:%i') AS quando
             FROM os_mensagens m
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             LEFT JOIN ordens_servico o ON o.id = m.os_id
             WHERE m.empresa_id = ? AND m.id > ? AND m.usuario_id <> ?
             ORDER BY m.id DESC LIMIT 12"
        );
        $st->execute([$eid, $visto, $uid]);
        $this->json(['nao_lidas' => $naoLidas, 'ultimo' => $ultimo, 'itens' => $st->fetchAll()]);
    }

    /** Marca todo o chat como lido pro usuário (zera o contador do sino de chat). */
    public function chatLido(): void
    {
        $eid = $this->empresaId();
        $db  = DB::pdo();
        $st  = $db->prepare("SELECT COALESCE(MAX(id),0) FROM os_mensagens WHERE empresa_id = ?");
        $st->execute([$eid]);
        $max = (int) $st->fetchColumn();
        $db->prepare("UPDATE usuarios SET chat_visto_id = ? WHERE id = ?")->execute([$max, $this->usuarioId()]);
        $this->json(['ok' => true]);
    }
}
