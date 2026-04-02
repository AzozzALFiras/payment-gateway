<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\NeonPay;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * NeonPay Webhook Handler.
 *
 * Verifies MyFatoorah-Signature header using HMAC-SHA256.
 *
 * @link https://neonpay.com
 */
class NeonPayWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $invoiceStatus = (string) Arr::get($payload, 'InvoiceStatus', Arr::get($payload, 'status', ''));

        $status = match (strtolower($invoiceStatus)) {
            'paid', 'completed' => 'paid',
            'failed'            => 'failed',
            'pending'           => 'pending',
            'refunded'          => 'refunded',
            default             => $invoiceStatus,
        };

        $amount = (float) Arr::get($payload, 'InvoiceValue', Arr::get($payload, 'amount', 0));

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         'payment.' . strtolower($invoiceStatus),
            transactionId: (string) Arr::get($payload, 'InvoiceId', Arr::get($payload, 'payment_token', '')),
            status:        $status,
            amount:        $amount,
            currency:      (string) Arr::get($payload, 'currency', 'SAR'),
            orderId:       (string) Arr::get($payload, 'CustomerReference', Arr::get($payload, 'order_id', '')),
            message:       (string) Arr::get($payload, 'InvoiceReference', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $secret = $this->config->get('webhook_secret');
        if (empty($secret)) {
            return true;
        }

        $signatureHeader = SignatureVerifier::extractSignature($headers, [
            'MyFatoorah-Signature', 'myfatoorah-signature',
            'X-NeonPay-Signature', 'x-neonpay-signature',
        ]);

        if ($signatureHeader === '') {
            return false;
        }

        $payloadString = json_encode($payload, JSON_THROW_ON_ERROR);

        return SignatureVerifier::verifyHmacSha256($payloadString, $signatureHeader, (string) $secret);
    }
}
