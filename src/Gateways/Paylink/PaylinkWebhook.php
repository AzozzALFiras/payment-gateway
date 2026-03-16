<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Paylink;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Paylink Webhook Handler.
 *
 * Parses and validates incoming webhook notifications from Paylink.
 */
class PaylinkWebhook
{
    public function __construct(
        private readonly GatewayConfig $config,
    ) {
    }

    /**
     * Parse an incoming webhook payload.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     * @return WebhookPayload
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $isValid = $this->verify($payload, $headers);

        return new WebhookPayload(
            isValid:       $isValid,
            event:         (string) Arr::get($payload, 'event', 'payment'),
            transactionId: (string) Arr::get($payload, 'transactionNo', ''),
            status:        (string) Arr::get($payload, 'orderStatus', ''),
            amount:        (float) Arr::get($payload, 'amount', 0),
            currency:      (string) Arr::get($payload, 'currency', 'SAR'),
            orderId:       (string) Arr::get($payload, 'orderNumber', ''),
            message:       (string) Arr::get($payload, 'message', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * Verify the webhook payload.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     * @return bool
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $webhookSecret = $this->config->get('webhook_secret');

        if (empty($webhookSecret)) {
            return true;
        }

        $signature = $headers['X-Paylink-Signature']
            ?? $headers['x-paylink-signature']
            ?? '';

        if ($signature === '') {
            return false;
        }

        $computedSignature = hash_hmac(
            'sha256',
            json_encode($payload, JSON_THROW_ON_ERROR),
            (string) $webhookSecret
        );

        return hash_equals($computedSignature, $signature);
    }
}
