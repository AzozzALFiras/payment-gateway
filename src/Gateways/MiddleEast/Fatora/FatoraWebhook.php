<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Fatora;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Fatora Webhook Handler.
 */
class FatoraWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         (string) Arr::get($payload, 'event', 'payment'),
            transactionId: (string) Arr::get($payload, 'order_id', ''),
            status:        (string) Arr::get($payload, 'payment_status', ''),
            amount:        (float) Arr::get($payload, 'amount', 0),
            currency:      (string) Arr::get($payload, 'currency', ''),
            orderId:       (string) Arr::get($payload, 'order_id', ''),
            message:       (string) Arr::get($payload, 'message', ''),
            rawPayload:    $payload,
        );
    }

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function verify(array $payload, array $headers = []): bool
    {
        $secret = $this->config->get('webhook_secret');
        if (empty($secret)) {
            return true;
        }

        $signature = SignatureVerifier::extractSignature($headers, ['X-Fatora-Signature', 'x-fatora-signature']);
        if ($signature === '') {
            return false;
        }

        return SignatureVerifier::verifyHmacSha256(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $signature,
            (string) $secret
        );
    }
}
