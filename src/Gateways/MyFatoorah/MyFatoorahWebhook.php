<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MyFatoorah;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Webhook Handler.
 *
 * Uses centralized SignatureVerifier for HMAC-SHA256 verification.
 *
 * @link https://docs.myfatoorah.com/docs/webhook
 */
class MyFatoorahWebhook
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

        $data = (array) Arr::get($payload, 'Data', $payload);

        return new WebhookPayload(
            isValid:       $isValid,
            event:         (string) Arr::get($payload, 'Event', 'payment'),
            transactionId: (string) Arr::get($data, 'PaymentId', ''),
            status:        (string) Arr::get($data, 'TransactionStatus', ''),
            amount:        (float) Arr::get($data, 'InvoiceValue', 0),
            currency:      (string) Arr::get($data, 'DisplayCurrencyIso', ''),
            orderId:       (string) Arr::get($data, 'ExternalIdentifier', ''),
            message:       (string) Arr::get($payload, 'Message', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * Verify the webhook signature using HMAC-SHA256.
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

        $signature = SignatureVerifier::extractSignature($headers, ['X-MyFatoorah-Signature', 'x-myfatoorah-signature']);

        if ($signature === '') {
            return false;
        }

        $payloadString = json_encode($payload, JSON_THROW_ON_ERROR);

        return SignatureVerifier::verifyHmacSha256($payloadString, $signature, (string) $webhookSecret);
    }
}
