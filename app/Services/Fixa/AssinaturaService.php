<?php

namespace App\Services\Fixa;

/**
 * Assinatura STANDALONE do Fixa (financeiro pessoal vendido à parte, sem empresa de assistência
 * técnica por trás) — cobre a MÁQUINA DE ESTADOS e o cálculo de crédito/acesso. A cobrança real
 * é feita via InfinitePay, mesmo mecanismo de link de checkout avulso já usado pro plano
 * completo (ver FixaCadastroController::assinar()/upgrade(), PagamentoController::webhook()) —
 * nunca cartão/débito recorrente de verdade (a InfinitePay não oferece isso hoje). Por isso o
 * bloqueio é por DIAS vencidos (statusEfetivo()), não por "tentativas de cobrança falhada" —
 * não existe tentativa nenhuma nesse modelo, só "venceu e ninguém pagou o link ainda".
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
     * Status EFETIVO agora — nunca confia cegamente no campo `status` gravado se o prazo já
     * passou e ninguém rodou o cron ainda (mesmo princípio já usado em
     * fixa_status_lancamento(): calculado a partir da data real, não só do que está salvo).
     *
     * Modelo de link manual (sem cartão/débito recorrente de verdade): não existe "tentativa de
     * cobrança falhou" — o que existe é "venceu e ninguém pagou ainda". Por isso o bloqueio é
     * por DIAS vencidos, não por contagem de tentativas (ver histórico de
     * registrarTentativaFalha(), removido):
     *   teste_fim/data_fim no futuro  → status gravado (teste/ativa) vale como está
     *   já venceu, dentro da carência (config/app.php['carencia_dias'], mesma do plano
     *   completo)                     → 'inadimplente' (computado)
     *   já venceu, além da carência    → 'bloqueada' (computado)
     * 'teste' e 'ativa' são os dois únicos status com um "vencimento" (teste_fim/data_fim) que
     * justifique recalcular — 'inadimplente'/'bloqueada'/'cancelada' já são estados finais/
     * persistidos (cancelada sempre por ação explícita, via cancelarPeloToken()/
     * cancelarComCredito()).
     */
    public static function statusEfetivo(array $assinatura): string
    {
        $status = $assinatura['status'];
        $vencimento = $status === 'teste' ? ($assinatura['teste_fim'] ?? null)
                    : ($status === 'ativa' ? ($assinatura['data_fim'] ?? null) : null);
        if ($vencimento === null) return $status;

        $diasVencido = (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($vencimento)))) / 86400;
        if ($diasVencido <= 0) return $status; // ainda não venceu (vence hoje inclusive)

        $carenciaDias = (int) ((require BASE_PATH . '/config/app.php')['carencia_dias'] ?? 0);
        return $diasVencido > $carenciaDias ? 'bloqueada' : 'inadimplente';
    }

    /** Acesso completo (criar/editar lançamento) — só teste ou ativa dentro do prazo. Vencido
     *  (mesmo em carência) já cai em "lançamentos travados, só exportação" — pedido explícito:
     *  "fim do teste sem pagamento: lançamentos travados e só exportação liberada". */
    public static function acessoCompleto(array $assinatura): bool
    {
        return in_array(self::statusEfetivo($assinatura), ['teste', 'ativa'], true);
    }

    /**
     * Inadimplente (vencido, ainda em carência), bloqueada (vencido além da carência) ou
     * cancelada — só lê/exporta, nunca cria/edita. Depois dos 30 dias de retenção (mesmo marco
     * de elegivelParaPurga()) deixa de garantir nem exportação — comportamento original mantido
     * nesta reescrita: a promessa de acesso (mesmo que só-leitura) é só durante a retenção.
     */
    public static function somenteExportacao(array $assinatura): bool
    {
        if (!in_array(self::statusEfetivo($assinatura), ['inadimplente', 'bloqueada', 'cancelada'], true)) return false;
        $marco = self::marcoRetencao($assinatura);
        return $marco === null || strtotime($marco) >= strtotime('-30 days');
    }

    /**
     * Marco de "virou ruim" pra contar os 30 dias de retenção — cancelamento explícito usa
     * cancelada_em (evento real); vencimento orgânico (nunca gravado por nenhum cron, é sempre
     * computado) usa o MESMO campo que statusEfetivo() usou pra decidir o vencimento daquele
     * status específico (teste_fim só se o status gravado é 'teste', data_fim só se 'ativa') —
     * nunca um `??` cego entre os dois: uma assinatura que já foi teste e depois ficou ativa
     * carrega teste_fim antigo (do trial) PRA SEMPRE na linha, então priorizar ele por acaso
     * contaria a retenção a partir da data errada (o trial antigo, não o ciclo pago que
     * realmente venceu).
     */
    private static function marcoRetencao(array $assinatura): ?string
    {
        return match ($assinatura['status']) {
            'cancelada' => $assinatura['cancelada_em'] ?? null,
            'teste'     => $assinatura['teste_fim'] ?? null,
            'ativa'     => $assinatura['data_fim'] ?? null,
            default     => $assinatura['bloqueada_em'] ?? null, // já persistido como bloqueada/inadimplente por algum caminho legado
        };
    }

    /** Elegível pra apagar de vez (passou dos 30 dias de retenção) — nunca chamado automaticamente. */
    public static function elegivelParaPurga(array $assinatura): bool
    {
        if (!in_array(self::statusEfetivo($assinatura), ['bloqueada', 'cancelada'], true)) return false;
        $marco = self::marcoRetencao($assinatura);
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
     * Confirma o pagamento de um UPGRADE (Individual → Diretório) no meio do ciclo — troca o
     * `plano` sem mexer em `data_fim` (upgrade não estende o ciclo, só muda o que ele inclui a
     * partir de agora) e zera `credito_centavos`: o crédito acumulado já foi abatido do valor
     * cobrado no momento de calcular a diferença (ver AssinaturaService::valorUpgrade(),
     * chamado por quem gera a cobrança), então continuar com saldo aqui duplicaria o desconto
     * na cobrança seguinte.
     */
    public static function confirmarUpgrade(\PDO $db, int $assinaturaId, string $novoPlano): void
    {
        $db->prepare("UPDATE fixa_assinaturas SET plano = ?, credito_centavos = 0 WHERE id = ?")
            ->execute([$novoPlano, $assinaturaId]);
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
     * Data (YYYY-MM-DD) do vencimento que importa pro aviso dessa assinatura agora — teste_fim
     * enquanto 'teste', data_fim enquanto 'ativa' (mesmo campo que statusEfetivo() usa pra
     * recalcular o status); null se não há vencimento (já inadimplente/bloqueada/cancelada —
     * esses não recebem mais aviso de "vai vencer", já venceu).
     */
    public static function vencimentoParaAviso(array $assinatura): ?string
    {
        $vencimento = $assinatura['status'] === 'teste' ? ($assinatura['teste_fim'] ?? null)
                    : ($assinatura['status'] === 'ativa' ? ($assinatura['data_fim'] ?? null) : null);
        return $vencimento ? date('Y-m-d', strtotime($vencimento)) : null;
    }

    /**
     * Mesma cadência do aviso do plano completo (scripts/avisar_vencimento_licenca.php): 3 dias
     * antes do vencimento (teste_fim ou data_fim, conforme o status atual) e no dia do
     * vencimento. $tipo: '3_dias_antes' ou 'vencimento'. Quem chama (o cron) decide quando
     * checar; esta função só diz SE um aviso faz sentido agora. Dedup de verdade é no banco
     * (fixa_assinatura_avisos, UNIQUE assinatura_id+tipo+referencia) — seguro chamar todo dia.
     */
    public static function precisaAviso(array $assinatura, string $tipo): bool
    {
        $vencimento = self::vencimentoParaAviso($assinatura);
        if ($vencimento === null) return false;
        $hoje = date('Y-m-d');
        return match ($tipo) {
            'vencimento'   => $vencimento === $hoje,
            '3_dias_antes' => $vencimento === date('Y-m-d', strtotime('+3 days')),
            default        => false,
        };
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
