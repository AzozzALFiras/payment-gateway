<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Fatora;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRecurring;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\RefundRequest;
use AzozzALFiras\PaymentGateway\DTOs\RefundResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Fatora Payment Gateway Driver.
 *
 * Sub-modules:
 *  - FatoraCheckout  — Checkout + Verify
 *  - FatoraRecurring — Card tokenization / recurring
 *  - FatoraWebhook   — Webhook handling
 *
 * @link https://fatora.io/docs
 */
class FatoraGateway implements GatewayInterface, SupportsRefund, SupportsRecurring, SupportsWebhook
{
    private const API_BASE_LIVE = 'https://api.fatora.io';
    private const API_BASE_TEST = 'https://api-test.fatora.io';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected FatoraCheckout $checkout;
    protected FatoraRecurring $recurringModule;
    protected FatoraWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $apiKey = (string) $config->require('api_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: ['api_key' => $apiKey, 'Accept' => 'application/json']
        );

        $baseUrl = $this->getBaseUrl();
        $this->checkout        = new FatoraCheckout($this->http, $baseUrl);
        $this->recurringModule = new FatoraRecurring($this->http, $baseUrl);
        $this->webhook         = new FatoraWebhook($config);
    }

    public function getName(): string { return 'Fatora'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }
    public function getBaseUrl(): string { return $this->isTestMode() ? self::API_BASE_TEST : self::API_BASE_LIVE; }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'amount'      => $request->amount,
            'currency'    => $request->currency,
            'order_id'    => $request->orderId ?: ('ORD-' . uniqid()),
            'client'      => [],
            'success_url' => $request->returnUrl ?: $request->callbackUrl,
            'failure_url' => $request->cancelUrl ?: $request->callbackUrl,
            'note'        => $request->description,
        ];

        if ($request->customer !== null) {
            $payload['client'] = [
                'name'  => $request->customer->name,
                'phone' => $request->customer->phone,
                'email' => $request->customer->email,
            ];
        }

        if (! empty($request->items)) {
            $payload['items'] = array_map(fn(array $item) => [
                'name' => $item['name'] ?? '', 'amount' => $item['price'] ?? 0, 'quantity' => $item['quantity'] ?? 1,
            ], $request->items);
        }

        if ($request->recurringToken !== null) {
            $payload['token'] = $request->recurringToken;
            $payload['save_token'] = true;
        }

        if (! empty($request->metadata['save_token'])) {
            $payload['save_token'] = true;
        }

        $payload = array_merge($payload, Arr::only($request->metadata, ['lang', 'save_token', 'trigger_transaction']));

        $response = $this->checkout->create($payload);
        $checkoutUrl = (string) Arr::get($response, 'result.checkout_url', '');
        $isSuccess = (bool) Arr::get($response, 'status', false);

        return new PaymentResponse(
            success:        $isSuccess && $checkoutUrl !== '',
            transactionId:  (string) Arr::get($response, 'result.order_id', $payload['order_id']),
            status:         $isSuccess ? 'checkout_created' : 'failed',
            message:        (string) Arr::get($response, 'message', ''),
            amount:         $request->amount,
            currency:       $request->currency,
            paymentUrl:     $checkoutUrl,
            recurringToken: Arr::get($response, 'result.token') ? (string) $response['result']['token'] : null,
            rawResponse:    $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->checkout->verify($paymentId);
        $isSuccess = (bool) Arr::get($response, 'status', false);
        $result = (array) Arr::get($response, 'result', []);

        return new PaymentResponse(
            success:       $isSuccess,
            transactionId: (string) Arr::get($result, 'order_id', $paymentId),
            status:        (string) Arr::get($result, 'payment_status', ''),
            message:       (string) Arr::get($response, 'message', ''),
            amount:        (float) Arr::get($result, 'amount', 0),
            currency:      (string) Arr::get($result, 'currency', ''),
            rawResponse:   $response,
        );
    }

    public function recurring(PaymentRequest $request): PaymentResponse
    {
        if ($request->recurringToken === null) {
            throw new \InvalidArgumentException('Card token (recurringToken) is required for recurring payments');
        }

        $payload = [
            'token'    => $request->recurringToken,
            'amount'   => $request->amount,
            'currency' => $request->currency,
            'order_id' => $request->orderId ?: ('REC-' . uniqid()),
            'note'     => $request->description,
        ];

        if ($request->customer !== null) {
            $payload['client'] = ['name' => $request->customer->name, 'phone' => $request->customer->phone, 'email' => $request->customer->email];
        }

        $response = $this->recurringModule->charge($payload);
        $isSuccess = (bool) Arr::get($response, 'status', false);

        return new PaymentResponse(
            success:       $isSuccess,
            transactionId: (string) Arr::get($response, 'result.order_id', $payload['order_id']),
            status:        $isSuccess ? 'charged' : 'failed',
            message:       (string) Arr::get($response, 'message', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $response = $this->http->post($this->getBaseUrl() . '/v1/payments/refund', [
            'order_id' => $request->transactionId, 'amount' => $request->amount, 'reason' => $request->reason ?: 'Refund',
        ]);
        $isSuccess = (bool) Arr::get($response, 'status', false);

        return new RefundResponse(
            success:       $isSuccess,
            refundId:      (string) Arr::get($response, 'result.refund_id', ''),
            transactionId: $request->transactionId,
            status:        $isSuccess ? 'refunded' : 'failed',
            message:       (string) Arr::get($response, 'message', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            rawResponse:   $response,
        );
    }

    public function partialRefund(RefundRequest $request): RefundResponse { return $this->refund($request); }

    public function handleWebhook(array $payload, array $headers = []): WebhookPayload { return $this->webhook->handle($payload, $headers); }
    public function verifyWebhook(array $payload, array $headers = []): bool { return $this->webhook->verify($payload, $headers); }

    // ── Sub-module Access ──

    public function checkouts(): FatoraCheckout { return $this->checkout; }
    public function recurringPayments(): FatoraRecurring { return $this->recurringModule; }
}
