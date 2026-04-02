<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tamara;

use AzozzALFiras\PaymentGateway\Http\HttpClient;

/**
 * Tamara Order Management Sub-module.
 *
 * Handles post-checkout order operations.
 */
class TamaraOrder
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /** @return array<string, mixed> */
    public function getDetails(string $orderId): array
    {
        return $this->http->get($this->baseUrl . "/orders/{$orderId}");
    }

    /** @return array<string, mixed> */
    public function authorise(string $orderId): array
    {
        return $this->http->post($this->baseUrl . "/orders/{$orderId}/authorise");
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    public function capture(string $orderId, float $amount, string $currency = 'SAR', array $items = []): array
    {
        $payload = [
            'total_amount'  => ['amount' => $amount, 'currency' => $currency],
            'shipping_info' => ['shipped_at' => date('c'), 'shipping_company' => $items['shipping_company'] ?? ''],
        ];

        if (! empty($items)) {
            $payload['items'] = $items;
        }

        return $this->http->post($this->baseUrl . "/orders/{$orderId}/capture", $payload);
    }

    /**
     * @param array<string, mixed> $items
     * @return array<string, mixed>
     */
    public function cancel(string $orderId, float $amount, string $currency = 'SAR', array $items = []): array
    {
        $payload = ['total_amount' => ['amount' => $amount, 'currency' => $currency]];

        if (! empty($items)) {
            $payload['items'] = $items;
        }

        return $this->http->post($this->baseUrl . "/orders/{$orderId}/cancel", $payload);
    }

    /**
     * @return array<string, mixed>
     */
    public function refund(string $orderId, float $amount, string $currency = 'SAR', string $comment = 'Refund'): array
    {
        return $this->http->post($this->baseUrl . "/orders/{$orderId}/refund", [
            'total_amount' => ['amount' => $amount, 'currency' => $currency],
            'comment'      => $comment,
        ]);
    }
}
