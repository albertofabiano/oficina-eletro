<?php

namespace App\Services\Marketing;

/**
 * Valida o payload (json) de um mkt_action_requests ANTES de usar — porta fiel de
 * ads-platform/src/lib/optimization/payload.ts. Chamado pelo executor mesmo em modo
 * simulação (regra inegociável: um payload inválido tem que aparecer como falha mesmo em
 * DRY_RUN, não só quando for de verdade pra plataforma).
 */
class ActionPayload
{
    /** @return array{type:string, to_cents?:int, from_cents?:int} */
    public static function parse(string $actionType, ?array $payload): array
    {
        return match ($actionType) {
            'pause_campaign', 'resume_campaign' => ['type' => $actionType],
            'update_daily_budget' => self::parseOrcamento($payload),
            default => throw new \InvalidArgumentException("action_type desconhecido: \"{$actionType}\"."),
        };
    }

    /** Formato pronto pra gravar em mkt_action_requests.payload — {} pras ações sem dado extra. */
    public static function toStored(string $actionType, array $extra = []): array
    {
        return $actionType === 'update_daily_budget' ? $extra : [];
    }

    private static function parseOrcamento(?array $payload): array
    {
        $toCents = $payload['daily_budget_cents'] ?? null;
        if (!is_int($toCents) || $toCents <= 0) {
            throw new \InvalidArgumentException('payload inválido: daily_budget_cents precisa ser um inteiro positivo.');
        }
        $fromCents = $payload['previous_daily_budget_cents'] ?? $toCents;
        if (!is_int($fromCents) || $fromCents <= 0) {
            throw new \InvalidArgumentException('payload inválido: previous_daily_budget_cents precisa ser um inteiro positivo.');
        }
        return ['type' => 'update_daily_budget', 'to_cents' => $toCents, 'from_cents' => $fromCents];
    }
}
