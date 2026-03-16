<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Payzah;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Payzah Webhook Handler.
 */
class PayzahWebhook
{
    public function __construct(
        protected GatewayConfig $config,
        protected string $privateKey
    ) {}

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         (string) Arr::get($payload, 'event', 'payment'),
            transactionId: (string) Arr::get($payload, 'payment_id', ''),
            status:        (string) Arr::get($payload, 'status', ''),
            amount:        (float) Arr::get($payload, 'amount', 0),
            currency:      (string) Arr::get($payload, 'currency', 'KWD'),
            orderId:       (string) Arr::get($payload, 'order_id', ''),
            message:       (string) Arr::get($payload, 'message', ''),
            rawPayload:    $payload,
        );
    }

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function verify(array $payload, array $headers = []): bool
    {
        $receivedKey = (string) Arr::get($payload, 'private_key', '');

        if ($receivedKey !== '' && $receivedKey !== $this->privateKey) {
            return false;
        }

        return true;
    }
}
