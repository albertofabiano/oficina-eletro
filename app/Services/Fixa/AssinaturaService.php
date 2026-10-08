<?php

namespace App\Services\Fixa;

/**
 * Assinatura STANDALONE do Fixa (financeiro pessoal vendido à parte, sem empresa de assistência
 * técnica por trás) — Etapas 2/3 do pedido. Cobre a MÁQUINA DE ESTADOS e o cálculo de crédito/
 * acesso; a EXECUÇÃO real de cobrança (cartão/Pix recorrente) fica fora daqui de propósito —
 * depende da escolha de gateway, ainda em aberto (ver conversa). `registrarTentativaFalha()`/
 * `confirmarPagamento()` são os pontos de entrada que o código de cobrança (quando existir) vai
 * chamar; por ora só existem pra dar suporte aos testes da máquina de estados.
 *
 * Nunca usado pra empresa que já tem Fixa de graça por um plano pago do FixaOS — ver
 * financeiro_pessoal_liberado() em app/Helpers/functions.php, que checa os dois caminhos.
 */
class AssinaturaService
{
    public static function config(): array
    {
        return require BASE_PATH . '/config/planos_fixa.php';
    }

    private static function plano(string $codigo): ?array
    {
        foreach (self::config()['planos'] as $p) if ($p['codigo'] === $codigo) return $p;
        return null;
    }

    /** Assinatura Fixa ATUAL do usuário (a mais recente que não está cancelada), ou null. */
    public static function doUsuario(\PDO $db, int $usuarioId): ?array
    {
        $st = $db->prepare(
            "SELECT * FROM fixa_assinaturas WHERE usuario_id = ? AND status <> 'cancelada'
             ORDER BY id DESC LIMIT 1"
        );
        $st->execute([$usuarioId]);
        $a = $st->fetch();
        return $a ?: null;
    }

    /**
     * Status EFETIVO agora — nunca confia cegamente no campo `status` gravado se o teste já
     * passou do prazo e ninguém rodou o cron ainda (mesmo princípio já usado em
     * fixa_status_lancamento(): calculado a partir da data real, não só do que está salvo).
     */
    public static function statusEfetivo(array $assinatura): string
    {
        if ($assinatura['status'] === 'teste') {
            $fim = $assinatura['teste_fim'] ?? null;
            if ($fim && strtotime($fim) < time()) return 'inadimplente'; // teste venceu, ainda não cobrou
        }
        return $assinatura['status'];
    }

    /** Acesso completo (criar/editar lançamento) — teste dentro do prazo, ativa, ou inadimplente
     *  (grace period antes do bloqueio no dia 7, pedido explícito da Etapa 3). */
    public static function acessoCompleto(array $assinatura): bool
    {
        return in_array(self::statusEfetivo($assinatura), ['teste', 'ativa', 'inadimplente'], true);
    }

    /** Bloqueada/cancelada ainda dentro dos 30 dias de retenção — só exportação, nunca apagar antes disso. */
    public static function somenteExportacao(array $assinatura): bool
    {
        $status = self::statusEfetivo($assinatura);
        if (!in_array($status, ['bloqueada', 'cancelada'], true)) return false;
        $marco = $assinatura['bloqueada_em'] ?? $assinatura['cancelada_em'] ?? null;
        return $marco === null || strtotime($marco) >= strtotime('-30 days');
    }

    /** Elegível pra apagar de vez (passou dos 30 dias de retenção) — nunca chamado automaticamente. */
    public static function elegivelParaPurga(array $assinatura): bool
    {
        $status = self::statusEfetivo($assinatura);
        if (!in_array($status, ['bloqueada', 'cancelada'], true)) return false;
        $marco = $assinatura['bloqueada_em'] ?? $assinatura['cancelada_em'] ?? null;
        return $marco !== null && strtotime($marco) < strtotime('-30 days');
    }

    /**
     * Cria o teste grátis — SEMPRE `teste_dias` da config (hoje 7), nunca mais, em nenhuma
     * circunstância: não existe parâmetro pra estender, de propósito — cupom/indicação/plano
     * antecipado nunca passam por aqui com um valor diferente (pedido explícito: "NUNCA mais
     * que 7 dias em nenhum fluxo").
     */
    public static function criarTeste(\PDO $db, int $usuarioId, string $plano, string $ciclo = 'mensal', ?int $indicadoPorUsuarioId = null): array
    {
        $cfg = self::config();
        $p = self::plano($plano);
        if (!$p) throw new \InvalidArgumentException("Plano Fixa desconhecido: {$plano}");
        $ck = $cfg['ciclos'][$ciclo] ?? $cfg['ciclos']['mensal'];

        $dias = (int) $cfg['teste_dias'];
        $inicio = date('Y-m-d H:i:s');
        $fim = date('Y-m-d H:i:s', strtotime("+{$dias} days"));
        $valor = \plano_preco_ciclo((int) $p['preco_mensal'], $ck);
        // Token opaco pro link de cancelamento em 1 clique do aviso do dia 5 — nunca o id da
        // assinatura cru na URL (mesmo cuidado dos links de descadastro de e-mail do projeto).
        $cancelarToken = bin2hex(random_bytes(20));

        $db->prepare(
            "INSERT INTO fixa_assinaturas
                (usuario_id, plano, ciclo, status, teste_inicio, teste_fim, valor_centavos, indicado_por_usuario_id, cancelar_token)
             VALUES (?, ?, ?, 'teste', ?, ?, ?, ?, ?)"
        )->execute([$usuarioId, $plano, $ciclo, $inicio, $fim, $valor, $indicadoPorUsuarioId, $cancelarToken]);

        return self::doUsuario($db, $usuarioId);
    }

    /**
     * Confirma um pagamento — teste vira ativa, ou renova o ciclo de quem já era ativa/
     * inadimplente. Zera tentativas de falha. Dispara o desconto de indicação na PRÓXIMA
     * mensalidade de quem indicou (não na deste pagamento), se for a 1ª cobrança do indicado.
     */
    public static function confirmarPagamento(\PDO $db, int $assinaturaId): void
    {
        $st = $db->prepare("SELECT * FROM fixa_assinaturas WHERE id = ?");
        $st->execute([$assinaturaId]);
        $a = $st->fetch();
        if (!$a) return;

        $primeiraCobranca = $a['status'] === 'teste';
        $cfg = self::config();
        $ck = $cfg['ciclos'][$a['ciclo']] ?? $cfg['ciclos']['mensal'];
        $dias = (int) $ck['dias'];

        $db->prepare(
            "UPDATE fixa_assinaturas SET status='ativa', tentativas_falhas=0,
                data_inicio = COALESCE(data_inicio, CURDATE()),
                data_fim = DATE_ADD(GREATEST(CURDATE(), COALESCE(data_fim, CURDATE())), INTERVAL ? DAY)
             WHERE id = ?"
        )->execute([$dias, $assinaturaId]);

        if ($primeiraCobranca && $a['indicado_por_usuario_id']) {
            self::creditarIndicacao($db, (int) $a['indicado_por_usuario_id']);
        }
    }

    /** Crédito de indicação (R$5 por padrão, config) — some automaticamente na próxima cobrança
     *  de quem indicou, via o campo credito_centavos (mesmo saldo usado pelo upgrade). */
    public static function creditarIndicacao(\PDO $db, int $usuarioIndicadorId): void
    {
        $valor = (int) self::config()['indicacao_desconto_centavos'];
        $db->prepare(
            "UPDATE fixa_assinaturas SET credito_centavos = credito_centavos + ?
             WHERE usuario_id = ? AND status <> 'cancelada' ORDER BY id DESC LIMIT 1"
        )->execute([$valor, $usuarioIndicadorId]);
    }

    /**
     * Falha de cobrança — some com o estado. Assinante que ainda estava 'ativa' vira
     * 'inadimplente' na 1ª falha; depois disso só conta tentativas. Bloqueia sozinho na 7ª
     * tentativa-dia (dia 7 da inadimplência, pedido explícito) — quem chama (o cron de
     * cobrança) decide QUANDO chamar isso (dias 1/3/5/7), esta função só aplica a transição.
     */
    public static function registrarTentativaFalha(\PDO $db, int $assinaturaId, int $diaDaFalha): void
    {
        $st = $db->prepare("SELECT * FROM fixa_assinaturas WHERE id = ?");
        $st->execute([$assinaturaId]);
        $a = $st->fetch();
        if (!$a) return;

        $novoStatus = $diaDaFalha >= 7 ? 'bloqueada' : 'inadimplente';
        $bloqueadaEm = $novoStatus === 'bloqueada' ? date('Y-m-d H:i:s') : null;

        $db->prepare(
            "UPDATE fixa_assinaturas SET status = ?, tentativas_falhas = tentativas_falhas + 1,
                ultima_tentativa_em = NOW(), bloqueada_em = COALESCE(bloqueada_em, ?)
             WHERE id = ?"
        )->execute([$novoStatus, $bloqueadaEm, $assinaturaId]);
    }

    /**
     * Cancela com crédito proporcional do que já foi pago e ainda não foi "usado" — chamado
     * quando a empresa do usuário passa a ter Fixa de graça por um plano pago do FixaOS
     * (pedido explícito da Etapa 2: "cancelar a cobrança do Fixa e creditar o proporcional").
     */
    public static function cancelarComCredito(\PDO $db, int $assinaturaId): int
    {
        $st = $db->prepare("SELECT * FROM fixa_assinaturas WHERE id = ?");
        $st->execute([$assinaturaId]);
        $a = $st->fetch();
        if (!$a || $a['status'] === 'cancelada') return 0;

        $credito = self::creditoProporcional($a);

        $db->prepare(
            "UPDATE fixa_assinaturas SET status='cancelada', cancelada_em=NOW(),
                credito_centavos = credito_centavos + ?
             WHERE id = ?"
        )->execute([$credito, $assinaturaId]);

        return $credito;
    }

    /**
     * Upgrade Individual → Diretório no meio do período — cobra só a diferença proporcional
     * (dias restantes do ciclo atual × diferença de preço mensal), e o crédito acumulado
     * (indicação, cancelamento anterior) abate dessa diferença antes de qualquer cobrança nova.
     * Retorna o valor em centavos que ainda precisa ser cobrado (0 ou negativo = nada a cobrar,
     * sobra vira crédito).
     */
    public static function valorUpgrade(array $assinaturaAtual, string $novoPlano = 'fixa_diretorio'): int
    {
        $pAtual = self::plano($assinaturaAtual['plano']);
        $pNovo  = self::plano($novoPlano);
        if (!$pAtual || !$pNovo) return 0;

        $diferencaMensal = (int) $pNovo['preco_mensal'] - (int) $pAtual['preco_mensal'];
        if ($diferencaMensal <= 0) return 0;

        $diasRestantes = self::diasRestantes($assinaturaAtual);
        $diasCiclo = (int) (self::config()['ciclos'][$assinaturaAtual['ciclo']]['dias'] ?? 30);
        $proporcional = (int) round($diferencaMensal * ($diasRestantes / max(1, $diasCiclo)));

        return max(0, $proporcional - (int) $assinaturaAtual['credito_centavos']);
    }

    /** Quanto do valor já pago ainda "resta" — base pro crédito de cancelamento/upgrade. */
    private static function creditoProporcional(array $assinatura): int
    {
        $diasRestantes = self::diasRestantes($assinatura);
        $diasCiclo = (int) (self::config()['ciclos'][$assinatura['ciclo']]['dias'] ?? 30);
        if ($diasCiclo <= 0) return 0;
        return (int) round((int) $assinatura['valor_centavos'] * ($diasRestantes / $diasCiclo));
    }

    private static function diasRestantes(array $assinatura): int
    {
        if (empty($assinatura['data_fim'])) return 0;
        $dias = (int) ceil((strtotime($assinatura['data_fim']) - time()) / 86400);
        return max(0, $dias);
    }

    /**
     * Dia 5 do teste (faltam 2 dias ou menos pro teste_fim, pedido explícito da Etapa 3) — quem
     * chama (o cron de aviso) decide quando checar; esta função só diz SE um aviso faz sentido
     * pra essa assinatura agora. O dedup de verdade (nunca mandar duas vezes) é no banco
     * (fixa_assinatura_avisos, UNIQUE em assinatura_id+tipo), não aqui — então é seguro chamar
     * isso todo dia do dia 5 ao 7 sem reenviar.
     */
    public static function precisaAvisoTesteAcabando(array $assinatura): bool
    {
        if ($assinatura['status'] !== 'teste') return false;
        if (empty($assinatura['teste_fim'])) return false;
        $horas = (strtotime($assinatura['teste_fim']) - time()) / 3600;
        return $horas > 0 && $horas <= 48;
    }

    /** Assinatura pelo token de cancelamento (link do aviso do dia 5) — nunca pelo id cru. */
    public static function porToken(\PDO $db, string $token): ?array
    {
        if ($token === '') return null;
        $st = $db->prepare("SELECT * FROM fixa_assinaturas WHERE cancelar_token = ?");
        $st->execute([$token]);
        $a = $st->fetch();
        return $a ?: null;
    }

    /**
     * Cancela pelo link de 1 clique do e-mail — SEM crédito (diferente de cancelarComCredito():
     * durante o teste nada foi cobrado ainda, não há "proporcional já pago" pra devolver).
     * Idempotente: cancelar de novo um token já cancelado/inexistente simplesmente não faz nada,
     * pra um clique duplicado no e-mail (ou um scanner de segurança pré-carregando o link) não
     * lançar erro nenhum pra quem recebeu o e-mail.
     */
    public static function cancelarPeloToken(\PDO $db, string $token): bool
    {
        $a = self::porToken($db, $token);
        if (!$a || $a['status'] === 'cancelada') return false;

        $db->prepare("UPDATE fixa_assinaturas SET status='cancelada', cancelada_em=NOW() WHERE id = ?")
            ->execute([$a['id']]);
        return true;
    }
}
