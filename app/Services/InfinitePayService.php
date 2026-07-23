<?php

namespace App\Services;

/**
 * InfinitePay — Checkout API (link de pagamento + confirmação).
 * Docs: POST https://api.checkout.infinitepay.io/links  → { "url": "..." }
 *       POST .../payment_check  (confirma um pagamento)
 * Auth: campo `handle` (InfiniteTag sem o $) no corpo — não há apikey.
 */
class InfinitePayService
{
    public static function config(): array
    {
        return require BASE_PATH . '/config/infinitepay.php';
    }

    public static function ativo(): bool
    {
        $c = self::config();
        return !empty($c['ativo']) && !empty($c['handle']);
    }

    /**
     * Cria um link de pagamento. Retorna a URL do checkout ou null em falha.
     * @param array $items  cada item: ['description'=>..., 'quantity'=>1, 'price'=>centavos]
     */
    public static function criarLink(string $orderNsu, array $items, string $redirectUrl, string $webhookUrl, array $customer = []): ?string
    {
        $c = self::config();
        if (empty($c['handle'])) return null;

        $payload = [
            'handle'       => $c['handle'],
            'order_nsu'    => $orderNsu,
            'items'        => $items,
            'redirect_url' => $redirectUrl,
            'webhook_url'  => $webhookUrl,
        ];
        if ($customer) $payload['customer'] = $customer;

        $resp = self::post('/links', $payload);
        return $resp['url'] ?? null;
    }

    /** Confirma um pagamento junto à InfinitePay (defesa contra webhook forjado). */
    public static function verificarPagamento(string $orderNsu, string $transactionNsu, string $slug): array
    {
        $c = self::config();
        return self::post('/payment_check', [
            'handle'          => $c['handle'],
            'order_nsu'       => $orderNsu,
            'transaction_nsu' => $transactionNsu,
            'slug'            => $slug,
        ]) ?? [];
    }

    private static function post(string $path, array $body): ?array
    {
        $c  = self::config();
        $ch = curl_init(rtrim($c['api_url'], '/') . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 15,
        ]);
        $res  = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code >= 200 && $code < 300) {
            $j = json_decode((string) $res, true);
            return is_array($j) ? $j : [];
        }
        return null;
    }
}
