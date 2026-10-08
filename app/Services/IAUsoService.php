<?php

namespace App\Services;

use App\Core\DB;

/**
 * Custo estimado + registro de uso de QUALQUER chamada à Anthropic (não só o scanner do Fixa —
 * reaproveitável por etiqueta de equipamento, placa, bot de suporte etc.) — Etapa 4 do pedido.
 * Preço por modelo em config/ia_precos.php, nunca fixo aqui; custo calculado a partir do
 * `usage` REAL da resposta, nunca um valor chutado "por leitura".
 */
class IAUsoService
{
    public static function precos(): array
    {
        return require BASE_PATH . '/config/ia_precos.php';
    }

    /** Custo em CENTAVOS (com casas decimais — uma leitura custa frações de centavo, arredondar
     *  pro inteiro faria a maioria das leituras de Haiku aparecer como R$0,00). Modelo sem
     *  preço cadastrado: 0 (nunca inventa um valor). */
    public static function custoCentavos(string $modelo, int $tokensEntrada, int $tokensSaida): float
    {
        $cfg = self::precos();
        $preco = $cfg['modelos'][$modelo] ?? null;
        if (!$preco) return 0.0;

        $usd = ($tokensEntrada / 1_000_000 * (float) $preco['input_usd_mtok'])
             + ($tokensSaida   / 1_000_000 * (float) $preco['output_usd_mtok']);

        return round($usd * (float) ($cfg['dolar_brl'] ?? 5.5) * 100, 4);
    }

    /**
     * Registra uma chamada no log — nunca lança exceção pra quem chama (um log falho não pode
     * derrubar a ação principal, mesmo princípio já usado em todo o resto do projeto).
     * $usage = o array ['input_tokens'=>N,'output_tokens'=>N] devolvido por
     * IAService::perguntar() (campo 'usage' da resposta da Anthropic).
     */
    public static function registrar(?int $usuarioId, ?int $empresaId, string $modelo, string $contexto, array $usage): void
    {
        $tokensEntrada = (int) ($usage['input_tokens'] ?? 0);
        $tokensSaida   = (int) ($usage['output_tokens'] ?? 0);
        $custo = self::custoCentavos($modelo, $tokensEntrada, $tokensSaida);

        try {
            DB::pdo()->prepare(
                "INSERT INTO ia_uso_log (usuario_id, empresa_id, modelo, contexto, tokens_entrada, tokens_saida, custo_centavos)
                 VALUES (?, ?, ?, ?, ?, ?, ?)"
            )->execute([$usuarioId ?: null, $empresaId ?: null, $modelo, $contexto, $tokensEntrada, $tokensSaida, $custo]);
        } catch (\Throwable $e) {
            error_log('IAUsoService::registrar — ' . $e->getMessage());
        }
    }

    /** Resumo do mês (por padrão o mês corrente) pro painel master — total geral + agrupado
     *  por modelo e por usuário. */
    public static function resumoMes(?string $referenciaMes = null): array
    {
        $ref = $referenciaMes ?: date('Y-m');
        $inicio = $ref . '-01 00:00:00';
        $fim = date('Y-m-t 23:59:59', strtotime($inicio));
        $db = DB::pdo();

        $totalSt = $db->prepare(
            "SELECT COUNT(*) qtd, COALESCE(SUM(tokens_entrada),0) te, COALESCE(SUM(tokens_saida),0) ts, COALESCE(SUM(custo_centavos),0) custo
             FROM ia_uso_log WHERE criado_em BETWEEN ? AND ?"
        );
        $totalSt->execute([$inicio, $fim]);
        $total = $totalSt->fetch();

        $porModeloSt = $db->prepare(
            "SELECT modelo, COUNT(*) qtd, COALESCE(SUM(tokens_entrada),0) te, COALESCE(SUM(tokens_saida),0) ts, COALESCE(SUM(custo_centavos),0) custo
             FROM ia_uso_log WHERE criado_em BETWEEN ? AND ? GROUP BY modelo ORDER BY custo DESC"
        );
        $porModeloSt->execute([$inicio, $fim]);

        $porUsuarioSt = $db->prepare(
            "SELECT l.usuario_id, u.nome, COUNT(*) qtd, COALESCE(SUM(l.custo_centavos),0) custo
             FROM ia_uso_log l LEFT JOIN usuarios u ON u.id = l.usuario_id
             WHERE l.criado_em BETWEEN ? AND ? AND l.usuario_id IS NOT NULL
             GROUP BY l.usuario_id, u.nome ORDER BY custo DESC LIMIT 50"
        );
        $porUsuarioSt->execute([$inicio, $fim]);

        return [
            'referencia_mes' => $ref,
            'total'          => $total,
            'por_modelo'     => $porModeloSt->fetchAll(),
            'por_usuario'    => $porUsuarioSt->fetchAll(),
        ];
    }
}
