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
    /**
     * Nº de perguntas/mês (por empresa) que ainda usam o modelo forte (Sonnet). Acima
     * disso, cai pro Haiku sem avisar — cobre a maioria absoluta do uso normal (dono
     * fazendo umas dezenas de perguntas por mês) e só reduz custo em quem usa o Mentor
     * como chat o dia inteiro. Nunca aparece pro usuário: sem aviso, sem erro, sem
     * mudança visível de comportamento — só o modelo por trás muda.
     */
    private const LIMITE_SONNET_MES = 50;

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

        $eid = $this->empresaId();
        $this->registrarUso($eid);

        // Modelo mais forte que o padrão do bot de suporte (haiku) — o Mentor precisa de
        // raciocínio melhor pra diagnóstico técnico e orientação de negócio de verdade.
        // Passado esse volume no mês, cai pro Haiku em silêncio (ver LIMITE_SONNET_MES).
        $modelo = ($eid && mentor_uso_mes($eid) > self::LIMITE_SONNET_MES)
                ? 'claude-haiku-4-5-20251001' : 'claude-sonnet-5';

        $r = IAService::perguntar($mensagens, $this->systemPrompt(), 900, $modelo);

        // Nunca mostra erro pro dono — se o modelo escolhido falhar (instabilidade, rate
        // limit etc.), tenta uma vez com o outro modelo antes de desistir.
        $modeloAlternativo = $modelo === 'claude-sonnet-5' ? 'claude-haiku-4-5-20251001' : 'claude-sonnet-5';
        if (empty($r['ok'])) {
            $r = IAService::perguntar($mensagens, $this->systemPrompt(), 900, $modeloAlternativo);
        }

        $resposta = !empty($r['ok']) ? trim((string) $r['texto']) : $this->respostaGenerica();
        $this->json(['ok' => true, 'resposta' => $resposta]);
    }

    /**
     * Se as duas tentativas de IA falharem, o chat nunca deve parecer quebrado — devolve
     * uma resposta genérica, no tom do Mentor, pedindo mais contexto (o que também dá
     * uma segunda chance real de resposta na próxima mensagem).
     */
    private function respostaGenerica(): string
    {
        $opcoes = [
            'Consigo te ajudar com isso! Me conta um pouco mais — marca/modelo do aparelho, ou o que já foi tentado — que te dou um caminho mais certeiro. 🔧',
            'Boa pergunta! Pra te dar uma resposta mais precisa, me passa mais detalhes (equipamento, situação, contexto) e a gente continua daqui.',
            'Entendi a ideia! Me dá um pouco mais de contexto sobre isso que eu aprofundo a resposta com você.',
        ];
        return $opcoes[array_rand($opcoes)];
    }

    /** Loga a pergunta (best-effort) — só pra contar uso mensal, nunca trava o chat. */
    private function registrarUso(int $empresaId): void
    {
        if (!$empresaId) return;
        try {
            DB::pdo()->prepare("INSERT INTO mentor_perguntas (empresa_id) VALUES (?)")->execute([$empresaId]);
        } catch (\Throwable $e) { /* best-effort */ }
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

        return "Você é o MENTOR do FixaOS: o parceiro de bancada mais experiente do mercado — referência em assistência "
             . "técnica de eletrônicos (celular, TV, notebook, eletrodoméstico, videogame). Ajuda donos, principalmente "
             . "quem está começando, a TOCAR O NEGÓCIO E O REPARO, não só a usar o sistema.\n\n"
             . "Você orienta sobre:\n"
             . "- Diagnóstico e reparo: defeitos comuns por categoria/marca/modelo, causa raiz provável, ordem de investigação, "
             . "erros clássicos a evitar, quando vale reparar x quando indicar troca.\n"
             . "- Peças e componentes: como avaliar se uma placa/peça/acessório à venda é boa compra — testada x sem garantia, "
             . "compatibilidade real com o modelo, sinais de item recondicionado vendido como novo, como comparar preço entre "
             . "fornecedores sem cair em oferta boa demais pra ser verdade. Pra COMPRAR ou VENDER peça de verdade, sempre "
             . "aponte primeiro o Marketplace de Peças do próprio FixaOS (menu Marketplace) — é o canal real e íntegro à "
             . "plataforma; você não tem acesso a estoque/preço ao vivo de fornecedor nenhum, então nunca invente nome de "
             . "loja, site ou preço específico de fornecedor externo como se fosse informação atual.\n"
             . "- Precificação de reparos, garantia (na prática e o que a lei costuma exigir), o que escrever na OS pra se "
             . "proteger, controle de caixa e separar o dinheiro do negócio, atendimento e comunicação com o cliente, "
             . "organização e primeiros passos — e também como fazer isso dentro do FixaOS.\n\n"
             . "COMO RESPONDER:\n"
             . "- Português do Brasil, tom de colega de bancada que já rodou: direto, prático e encorajador. Sem textão, sem juridiquês.\n"
             . "- Respostas curtas e acionáveis; use bullets quando ajudar; no máximo 1 emoji.\n"
             . "- Quando o que ele quer dá pra fazer no FixaOS, aponte o caminho (use a BASE DE CONHECIMENTO abaixo).\n"
             . "- Precificação: NUNCA crave um valor único como 'o certo'; ensine a montar o preço (custo da peça + mão de obra + margem) "
             . "e dê faixas/exemplos, deixando claro que varia por região e aparelho.\n"
             . "- Garantia e temas legais: oriente com prudência e sugira confirmar com contador/Procon quando for jurídico; não afirme como certeza jurídica.\n"
             . "- Seu conhecimento tem data de corte e você NÃO navega na internet ao vivo: nunca afirme preço, disponibilidade "
             . "de estoque ou lançamento recente como se fosse informação de agora. Quando o dono precisar do dado mais atual "
             . "possível (preço de peça, câmbio de componente importado, novidade de mercado), diga isso com transparência e "
             . "oriente onde ele confirma na hora — Marketplace/Fórum do FixaOS, grupos de técnicos, ou o fornecedor direto.\n"
             . "- NÃO invente recursos nem preços do FixaOS. Se for bug/erro do sistema ou cobrança/conta, diga pra falar com o suporte pelo WhatsApp.\n"
             . ($ctx ? "- {$ctx}\n" : '')
             . "\nBASE DE CONHECIMENTO (FixaOS):\n" . $kb;
    }
}
