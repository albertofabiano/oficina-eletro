<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Core\DB;
use App\Services\IAService;

/**
 * Mentor IA dentro do painel: ajuda o dono (sobretudo o iniciante) a TOCAR O NEGÓCIO —
 * precificação, garantia, o que escrever na OS, caixa, atendimento — reaproveitando a
 * Base de Conhecimento (kb_artigos) e o IAService já configurados (mesma chave do bot).
 */
class MentorController extends Controller
{
    /** Recebe a pergunta (+ histórico do cliente) e devolve a resposta do mentor. */
    public function perguntar(): void
    {
        if (!csrf_verify()) { $this->json(['ok' => false, 'erro' => 'Sessão expirada — recarregue a página.'], 400); }

        $pergunta = mb_substr(trim((string) $this->post('pergunta', '')), 0, 1000);
        if ($pergunta === '') { $this->json(['ok' => false, 'erro' => 'Escreva sua dúvida.']); }

        if (!IAService::ativo()) {
            $this->json(['ok' => false, 'erro' => 'O mentor está fora do ar no momento. Fale com o suporte pelo WhatsApp.']);
        }

        // histórico curto vindo do cliente (sem tabela nova nesta 1ª versão)
        $mensagens = [];
        $hist = json_decode((string) $this->post('historico', '[]'), true);
        if (is_array($hist)) {
            foreach (array_slice($hist, -8) as $m) {
                $role = (($m['role'] ?? '') === 'assistant') ? 'assistant' : 'user';
                $c    = mb_substr(trim((string) ($m['content'] ?? '')), 0, 2000);
                if ($c !== '') $mensagens[] = ['role' => $role, 'content' => $c];
            }
        }
        if (!$mensagens || end($mensagens)['content'] !== $pergunta) {
            $mensagens[] = ['role' => 'user', 'content' => $pergunta];
        }

        $r = IAService::perguntar($mensagens, $this->systemPrompt(), 700);
        if (empty($r['ok'])) {
            $this->json(['ok' => false, 'erro' => 'Tive um probleminha pra pensar agora 😅. Tenta de novo em instantes.']);
        }
        $this->json(['ok' => true, 'resposta' => trim((string) $r['texto'])]);
    }

    /** Persona do mentor + contexto da empresa + Base de Conhecimento. */
    private function systemPrompt(): string
    {
        $kb = '';
        foreach (DB::pdo()->query("SELECT titulo, conteudo FROM kb_artigos WHERE ativo=1 ORDER BY categoria, ordem") as $a) {
            $kb .= "## {$a['titulo']}\n{$a['conteudo']}\n\n";
        }

        $ctx = '';
        $eid = $this->empresaId();
        if ($eid) {
            $db = DB::pdo();
            $e  = $db->prepare("SELECT nome_fantasia, razao_social FROM empresas WHERE id=?");
            $e->execute([$eid]); $emp = $e->fetch() ?: [];
            $nome  = ($emp['nome_fantasia'] ?? '') ?: (($emp['razao_social'] ?? '') ?: 'a loja dele');
            $osMes = (int) $db->query("SELECT COUNT(*) FROM ordens_servico WHERE empresa_id=" . (int) $eid . " AND criado_em >= DATE_FORMAT(CURDATE(),'%Y-%m-01')")->fetchColumn();
            $novo  = $osMes < 15 ? ' Ele parece estar começando agora — seja especialmente didático, paciente e encorajador.' : '';
            $ctx   = "Você fala com o dono de \"{$nome}\", que abriu {$osMes} OS este mês.{$novo}";
        }

        return "Você é o MENTOR do FixaOS: um parceiro experiente de bancada que ajuda donos de assistência técnica — "
             . "principalmente quem está começando — a TOCAR O NEGÓCIO, não só a usar o sistema.\n\n"
             . "Você orienta sobre: precificação de reparos, garantia (na prática e o que a lei costuma exigir), o que escrever "
             . "na OS pra se proteger, controle de caixa e separar o dinheiro do negócio, atendimento e comunicação com o cliente, "
             . "organização e primeiros passos — e também como fazer isso dentro do FixaOS.\n\n"
             . "COMO RESPONDER:\n"
             . "- Português do Brasil, tom de colega de bancada que já rodou: direto, prático e encorajador. Sem textão, sem juridiquês.\n"
             . "- Respostas curtas e acionáveis; use bullets quando ajudar; no máximo 1 emoji.\n"
             . "- Quando o que ele quer dá pra fazer no FixaOS, aponte o caminho (use a BASE DE CONHECIMENTO abaixo).\n"
             . "- Precificação: NUNCA crave um valor único como 'o certo'; ensine a montar o preço (custo da peça + mão de obra + margem) "
             . "e dê faixas/exemplos, deixando claro que varia por região e aparelho.\n"
             . "- Garantia e temas legais: oriente com prudência e sugira confirmar com contador/Procon quando for jurídico; não afirme como certeza jurídica.\n"
             . "- NÃO invente recursos nem preços do FixaOS. Se for bug/erro do sistema ou cobrança/conta, diga pra falar com o suporte pelo WhatsApp.\n"
             . ($ctx ? "- {$ctx}\n" : '')
             . "\nBASE DE CONHECIMENTO (FixaOS):\n" . $kb;
    }
}
