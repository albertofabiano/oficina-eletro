<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\IAService;
use App\Services\WhatsAppService;

/**
 * Bot de suporte por WhatsApp: recebe mensagem (webhook da Evolution) → IA com a
 * Base de Conhecimento + contexto do assinante → responde e escala pra humano quando preciso.
 */
class BotController extends Controller
{
    /** Webhook público da Evolution (evento messages.upsert). */
    public function webhook(): void
    {
        header('Content-Type: application/json');
        $ok = function () { http_response_code(200); echo json_encode(['success' => true]); };

        try {
            $data = json_decode((string) file_get_contents('php://input'), true) ?: [];
            $msg  = $data['data'] ?? $data;
            $key  = $msg['key'] ?? [];

            if (!empty($key['fromMe'])) { $ok(); return; }                 // mensagem do próprio bot
            $jid = $key['remoteJid'] ?? '';
            if ($jid === '' || str_contains($jid, '@g.us')) { $ok(); return; } // ignora grupos
            $numero = preg_replace('/\D/', '', explode('@', $jid)[0]);
            if ($numero === '') { $ok(); return; }

            $texto = $msg['message']['conversation']
                  ?? $msg['message']['extendedTextMessage']['text']
                  ?? '';
            $texto = trim((string) $texto);
            if ($texto === '') { $ok(); return; }

            if (!IAService::ativo()) { $ok(); return; }                    // bot desligado

            $pushName = mb_substr((string) ($msg['pushName'] ?? ''), 0, 80);
            $db = DB::pdo();

            // conversa (cria/atualiza)
            $db->prepare("INSERT INTO ia_conversas (numero, push_name) VALUES (?,?)
                          ON DUPLICATE KEY UPDATE push_name=VALUES(push_name)")
               ->execute([$numero, $pushName]);
            $status = $db->query("SELECT status FROM ia_conversas WHERE numero=" . $db->quote($numero))->fetchColumn();

            $this->salvarMsg($numero, 'user', $texto);

            // Já em atendimento humano → não responde (deixa a pessoa cuidar).
            if ($status === 'humano') { $ok(); return; }

            $empresaId = $this->identificarEmpresa($numero);
            if ($empresaId) $db->prepare("UPDATE ia_conversas SET empresa_id=? WHERE numero=?")->execute([$empresaId, $numero]);

            $resp = $this->gerarResposta($numero, $texto, $empresaId, $pushName);

            $this->salvarMsg($numero, 'assistant', $resp['texto']);

            if (!empty($resp['handoff'])) {
                $db->prepare("UPDATE ia_conversas SET status='humano' WHERE numero=?")->execute([$numero]);
                $this->notificarHandoff($numero, $pushName, $texto);
            }

            WhatsAppService::enviarTextoPlataforma($numero, $resp['texto']);
        } catch (\Throwable $e) { /* nunca quebra o webhook */ }

        $ok();
    }

    /**
     * CÉREBRO (testável): monta contexto + KB + histórico e chama a IA.
     * Retorna ['texto'=>string, 'handoff'=>bool].
     */
    public function gerarResposta(string $numero, string $texto, ?int $empresaId, string $pushName = ''): array
    {
        $system = $this->systemPrompt($empresaId, $pushName);

        // histórico (últimas 10 mensagens, em ordem)
        $st = DB::pdo()->prepare("SELECT role, conteudo FROM (SELECT role, conteudo, criado_em FROM ia_mensagens WHERE numero=? ORDER BY id DESC LIMIT 10) t ORDER BY criado_em ASC");
        $st->execute([$numero]);
        $mensagens = [];
        foreach ($st->fetchAll() as $m) $mensagens[] = ['role' => $m['role'], 'content' => $m['conteudo']];
        // garante que a última é a pergunta atual
        if (!$mensagens || end($mensagens)['content'] !== $texto) $mensagens[] = ['role' => 'user', 'content' => $texto];

        $r = IAService::perguntar($mensagens, $system, 700);
        if (!$r['ok']) {
            return ['texto' => 'Tive um probleminha técnico agora 😅. Já vou chamar um atendente pra te ajudar!', 'handoff' => true];
        }
        $out     = $r['texto'];
        $handoff = str_contains($out, '[HUMANO]');
        $out     = trim(str_replace('[HUMANO]', '', $out));
        if ($handoff && $out === '') $out = 'Vou chamar um atendente pra te ajudar com isso. Um instante! 🙏';
        return ['texto' => $out, 'handoff' => $handoff];
    }

    private function systemPrompt(?int $empresaId, string $pushName): string
    {
        $kb = '';
        foreach (DB::pdo()->query("SELECT titulo, conteudo FROM kb_artigos WHERE ativo=1 ORDER BY categoria, ordem") as $a) {
            $kb .= "## {$a['titulo']}\n{$a['conteudo']}\n\n";
        }

        $ctx = "O cliente não foi identificado pelo número (não dê informações específicas da conta dele; ofereça ajuda geral e, se ele quiser dados da conta, peça que fale pelo número cadastrado ou acesse Configurações no sistema).";
        if ($empresaId) {
            $db = DB::pdo();
            $e  = $db->prepare("SELECT nome_fantasia, razao_social, plano_atual, trial_ate, licenca_ate FROM empresas WHERE id=?");
            $e->execute([$empresaId]); $emp = $e->fetch() ?: [];
            $nome = $emp['nome_fantasia'] ?: ($emp['razao_social'] ?: 'sua empresa');
            $plano = $emp['plano_atual'] ? ('plano ' . $emp['plano_atual']) : (!empty($emp['trial_ate']) && $emp['trial_ate'] >= date('Y-m-d') ? 'em período de teste' : 'sem plano ativo');
            $osMes = (int) $db->query("SELECT COUNT(*) FROM ordens_servico WHERE empresa_id=$empresaId AND criado_em >= DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn();
            $ctx = "O cliente é da empresa \"{$nome}\" ({$plano}), abriu {$osMes} OS este mês. Pode usar esses dados para ajudar.";
        }

        return "Você é o assistente de suporte do FixaOS, um sistema de gestão para assistências técnicas. "
             . "Responda em português do Brasil, de forma amigável, CURTA e direta (é WhatsApp — evite textão). Use no máximo 1 emoji.\n\n"
             . "REGRAS:\n"
             . "- Responda APENAS sobre o FixaOS, usando a BASE DE CONHECIMENTO abaixo. NÃO invente funcionalidades nem preços.\n"
             . "- Nunca prometa reembolso, desconto, mudança de plano/conta ou prazos. Nunca peça senha nem dados de cartão.\n"
             . "- Se a pergunta estiver fora do seu alcance (bug/erro do sistema, cobrança, cancelamento, mudança de conta, cliente irritado) OU você não tiver certeza da resposta, ENCAMINHE para um humano: inclua a tag [HUMANO] na sua resposta e diga que vai chamar um atendente.\n"
             . "- {$ctx}\n\n"
             . "BASE DE CONHECIMENTO:\n" . $kb;
    }

    private function salvarMsg(string $numero, string $role, string $conteudo): void
    {
        try { DB::pdo()->prepare("INSERT INTO ia_mensagens (numero, role, conteudo) VALUES (?,?,?)")->execute([$numero, $role, mb_substr($conteudo, 0, 4000)]); }
        catch (\Throwable $e) {}
    }

    private function identificarEmpresa(string $numero): ?int
    {
        try {
            $fim = substr($numero, -8); // últimos 8 dígitos (evita divergência de DDI/DDD)
            $db  = DB::pdo();
            // usuários pelo telefone
            $q = $db->prepare("SELECT empresa_id FROM usuarios WHERE ativo=1 AND telefone IS NOT NULL AND REPLACE(REPLACE(REPLACE(REPLACE(telefone,'(',''),')',''),'-',''),' ','') LIKE ? LIMIT 1");
            $q->execute(['%' . $fim]);
            if ($e = $q->fetchColumn()) return (int) $e;
            // empresa pelo whatsapp/telefone
            $q2 = $db->prepare("SELECT id FROM empresas WHERE ativo=1 AND (REPLACE(REPLACE(REPLACE(REPLACE(whatsapp,'(',''),')',''),'-',''),' ','') LIKE ? OR REPLACE(REPLACE(REPLACE(REPLACE(telefone,'(',''),')',''),'-',''),' ','') LIKE ?) LIMIT 1");
            $q2->execute(['%' . $fim, '%' . $fim]);
            if ($e = $q2->fetchColumn()) return (int) $e;
        } catch (\Throwable $e) {}
        return null;
    }

    private function notificarHandoff(string $numero, string $pushName, string $texto): void
    {
        try {
            \App\Services\EmailService::send(
                'suporte@fixaos.com.br', 'FixaOS',
                'Suporte: atendimento humano solicitado — ' . ($pushName ?: $numero),
                "<p>O bot escalou uma conversa no WhatsApp para <b>atendimento humano</b>:</p>
                 <ul><li><b>Número:</b> {$numero}</li><li><b>Nome:</b> " . htmlspecialchars($pushName) . "</li>
                 <li><b>Última mensagem:</b> " . htmlspecialchars($texto) . "</li></ul>
                 <p>Responda por lá. Enquanto o status estiver 'humano', o bot não responde esse número.</p>"
            );
        } catch (\Throwable $e) {}
    }
}
