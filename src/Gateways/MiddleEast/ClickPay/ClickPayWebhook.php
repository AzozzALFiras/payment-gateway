<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\ClickPay;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * ClickPay Webhook Handler.
 */
class ClickPayWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         (string) Arr::get($payload, 'tran_type', 'payment'),
            transactionId: (string) Arr::get($payload, 'tran_ref', ''),
            status:        (string) Arr::get($payload, 'payment_result.response_status', ''),
            amount:        (float) Arr::get($payload, 'cart_amount', 0),
            currency:      (string) Arr::get($payload, 'cart_currency', ''),
            orderId:       (string) Arr::get($payload, 'cart_id', ''),
            message:       (string) Arr::get($payload, 'payment_result.response_message', ''),
            rawPayload:    $payload,
        );
    }

    /** @param array<string, mixed> $payload @param array<string, string> $headers */
    public function verify(array $payload, array $headers = []): bool
    {
        $serverKey = $this->config->get('server_key');
        $signature = SignatureVerifier::extractSignature($headers, ['signature', 'Signature']);

        if (empty($serverKey) || $signature === '') {
            return true;
        }

        return SignatureVerifier::verifyHmacSha256(
            json_encode($payload, JSON_THROW_ON_ERROR),
            $signature,
            (string) $serverKey
        );
    }
}
