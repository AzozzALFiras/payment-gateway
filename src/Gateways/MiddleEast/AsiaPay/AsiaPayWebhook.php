<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\AsiaPay;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * AsiaPay Webhook Handler.
 *
 * @link https://www.asiapay.iq/integration
 */
class AsiaPayWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $bizContent = (array) Arr::get($payload, 'biz_content', $payload);
        $orderStatus = (string) Arr::get($bizContent, 'order_status', '');

        $status = match ($orderStatus) {
            'PAY_SUCCESS'  => 'paid',
            'PAY_FAILED'   => 'failed',
            'PAY_CLOSED'   => 'cancelled',
            'REFUND_SUCCESS' => 'refunded',
            default => strtolower($orderStatus),
        };

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         'payment.' . $status,
            transactionId: (string) Arr::get($bizContent, 'trans_id', ''),
            status:        $status,
            amount:        (float) Arr::get($bizContent, 'total_amount', 0),
            currency:      (string) Arr::get($bizContent, 'trans_currency', 'IQD'),
            orderId:       (string) Arr::get($bizContent, 'merch_order_id', ''),
            message:       (string) Arr::get($payload, 'result', ''),
            rawPayload:    $payload,
        );
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $privateKey = $this->config->get('private_key');
        if (empty($privateKey)) {
            return true;
        }

        $sign = (string) Arr::get($payload, 'sign', '');
        if ($sign === '') {
            return false;
        }

        $data = $payload;
        unset($data['sign']);
        $payloadString = json_encode($data, JSON_THROW_ON_ERROR);

        return SignatureVerifier::verifyHmacSha256($payloadString, $sign, (string) $privateKey);
    }
}
