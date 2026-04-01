<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\NeonPay;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\RefundRequest;
use AzozzALFiras\PaymentGateway\DTOs\RefundResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * NeonPay Payment Gateway Driver.
 *
 * Sub-modules:
 *  - NeonPayPayment — Payments (create, retrieve, list, refund)
 *  - NeonPayWebhook — Webhook handling
 *
 * Authentication: X-EBIK-KEY header.
 *
 * @link https://neonpay.com
 */
class NeonPayGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const LIVE_URL    = 'https://neonpay.com';
    private const SANDBOX_URL = 'http://neon-pay.test';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected NeonPayPayment $paymentModule;
    protected NeonPayWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $baseUrl = $config->isTestMode() ? self::SANDBOX_URL : self::LIVE_URL;

        $ebikKey = (string) $config->require('ebik_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'X-EBIK-KEY'   => $ebikKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ]
        );

        $this->paymentModule = new NeonPayPayment($this->http, $baseUrl);
        $this->webhook       = new NeonPayWebhook($config);
    }

    public function getName(): string { return 'NeonPay'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'amount'   => $request->amount,
            'currency' => strtoupper($request->currency),
        ];

        if ($request->orderId !== '') {
            $payload['order_id'] = $request->orderId;
        }

        if ($request->callbackUrl !== '') {
            $payload['callback_url'] = $request->callbackUrl;
        }

        if ($request->returnUrl !== '') {
            $payload['success_url'] = $request->returnUrl;
        }

        if ($request->cancelUrl !== '') {
            $payload['error_url'] = $request->cancelUrl;
        }

        if (! empty($request->metadata)) {
            $payload['metadata'] = $request->metadata;
        }

        $response = $this->paymentModule->create($payload);
        $data = (array) Arr::get($response, 'data', $response);

        $paymentToken = (string) Arr::get($data, 'payment_token', '');
        $paymentUrl = (string) Arr::get($data, 'payment_url', '');

        return new PaymentResponse(
            success:       ! empty($paymentUrl),
            transactionId: $paymentToken,
            status:        (string) Arr::get($data, 'status', 'pending'),
            message:       '',
            amount:        (float) Arr::get($data, 'amount', $request->amount),
            currency:      strtoupper((string) Arr::get($data, 'currency', $request->currency)),
            paymentUrl:    $paymentUrl,
            sessionId:     $paymentToken !== '' ? $paymentToken : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->paymentModule->retrieve($paymentId);
        $data = (array) Arr::get($response, 'data', $response);
        $status = (string) Arr::get($data, 'status', '');

        return new PaymentResponse(
            success:       $status === 'completed',
            transactionId: (string) Arr::get($data, 'payment_token', $paymentId),
            status:        $status,
            amount:        (float) Arr::get($data, 'amount', 0),
            currency:      strtoupper((string) Arr::get($data, 'currency', '')),
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $response = $this->paymentModule->refund($request->transactionId);

        $message = (string) Arr::get($response, 'message', '');
        $success = str_contains(strtolower($message), 'success') || str_contains(strtolower($message), 'refunded');

        return new RefundResponse(
            success:       $success,
            refundId:      (string) Arr::get($response, 'payment_token', ''),
            transactionId: $request->transactionId,
            status:        $success ? 'refunded' : 'failed',
            message:       $message,
            amount:        $request->amount,
            currency:      strtoupper($request->currency),
            rawResponse:   $response,
        );
    }

    public function partialRefund(RefundRequest $request): RefundResponse
    {
        return $this->refund($request);
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookPayload
    {
        return $this->webhook->handle($payload, $headers);
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        return $this->webhook->verify($payload, $headers);
    }

    // -- Sub-module Access --

    public function payments(): NeonPayPayment { return $this->paymentModule; }

    /**
     * Validate EBIK key by calling GET /me.
     *
     * @return array<string, mixed>
     */
    public function validateKey(): array
    {
        return $this->http->get(
            ($this->config->isTestMode() ? self::SANDBOX_URL : self::LIVE_URL) . '/api/v1/me'
        );
    }

    /**
     * Health check — GET /health.
     *
     * @return array<string, mixed>
     */
    public function healthCheck(): array
    {
        return $this->http->get(
            ($this->config->isTestMode() ? self::SANDBOX_URL : self::LIVE_URL) . '/api/v1/health'
        );
    }
}
