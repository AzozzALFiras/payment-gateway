<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tamara;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Tamara Webhook Handler.
 */
class TamaraWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         (string) Arr::get($payload, 'event_type', ''),
            transactionId: (string) Arr::get($payload, 'order_id', ''),
            status:        (string) Arr::get($payload, 'order_status', ''),
            amount:        (float) Arr::get($payload, 'total_amount.amount', 0),
            currency:      (string) Arr::get($payload, 'total_amount.currency', ''),
            orderId:       (string) Arr::get($payload, 'order_reference_id', ''),
            message:       (string) Arr::get($payload, 'message', ''),
            rawPayload:    $payload,
        );
    }

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function verify(array $payload, array $headers = []): bool
    {
        $token = $this->config->get('notification_token');
        if (empty($token)) {
            return true;
        }

        $authHeader = SignatureVerifier::extractSignature($headers, ['Authorization', 'authorization']);
        if ($authHeader === '') {
            return false;
        }

        return SignatureVerifier::verifyBearerToken($authHeader, (string) $token);
    }
}
