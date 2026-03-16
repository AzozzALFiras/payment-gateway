<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MyFatoorah;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Webhook Handler.
 *
 * Parses and validates incoming webhook notifications from MyFatoorah.
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
     * Verify the webhook signature.
     *
     * MyFatoorah webhooks include a signature header that should be validated
     * against the webhook secret key.
     *
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     * @return bool
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $webhookSecret = $this->config->get('webhook_secret');

        // If no webhook secret is configured, skip verification
        if (empty($webhookSecret)) {
            return true;
        }

        $signature = $headers['X-MyFatoorah-Signature']
            ?? $headers['x-myfatoorah-signature']
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
