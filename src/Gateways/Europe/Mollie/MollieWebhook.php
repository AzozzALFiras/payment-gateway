<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\Mollie;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Mollie Webhook Handler.
 *
 * Mollie webhooks send only { id: "tr_xxx" }. Status is fetched via API.
 * Verification is IP-based (Mollie sends from known IPs).
 *
 * @link https://docs.mollie.com/reference/webhooks
 */
class MollieWebhook
{
    public function __construct(
        protected GatewayConfig $config,
        protected MolliePayment $paymentModule
    ) {}

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $paymentId = (string) Arr::get($payload, 'id', '');

        if ($paymentId === '') {
            return new WebhookPayload(
                isValid: false,
                event: 'unknown',
                rawPayload: $payload,
            );
        }

        // Mollie requires fetching the payment to get the status
        $payment = $this->paymentModule->retrieve($paymentId);
        $status = (string) Arr::get($payment, 'status', '');

        $normalizedStatus = match ($status) {
            'paid'       => 'paid',
            'open'       => 'pending',
            'pending'    => 'pending',
            'authorized' => 'authorized',
            'canceled'   => 'cancelled',
            'expired'    => 'expired',
            'failed'     => 'failed',
            default      => $status,
        };

        $amountData = (array) Arr::get($payment, 'amount', []);

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         'payment.' . $normalizedStatus,
            transactionId: $paymentId,
            status:        $normalizedStatus,
            amount:        (float) ($amountData['value'] ?? 0),
            currency:      strtoupper((string) ($amountData['currency'] ?? '')),
            orderId:       (string) Arr::get($payment, 'metadata.order_id', ''),
            message:       (string) Arr::get($payment, 'description', ''),
            rawPayload:    $payment,
        );
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function verify(array $payload, array $headers = []): bool
    {
        // Mollie uses IP-based verification rather than signatures
        // In production, verify the request comes from Mollie's IP range
        return ! empty(Arr::get($payload, 'id', ''));
    }
}
