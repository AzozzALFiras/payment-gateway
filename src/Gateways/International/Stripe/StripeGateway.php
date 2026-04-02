<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Stripe;

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
 * Stripe Payment Gateway Driver.
 *
 * Sub-modules:
 *  - StripeCheckout — Checkout sessions
 *  - StripeCharge   — Direct charges
 *  - StripeCustomer — Customer management
 *  - StripeRefund   — Refunds
 *  - StripeWebhook  — Webhook handling
 *
 * @link https://docs.stripe.com/api
 */
class StripeGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const API_BASE = 'https://api.stripe.com/v1';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected StripeCheckout $checkout;
    protected StripeCharge $charge;
    protected StripeCustomer $customer;
    protected StripeRefund $refundModule;
    protected StripeWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $secretKey = (string) $config->require('secret_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Authorization' => "Bearer {$secretKey}",
                'Content-Type'  => 'application/x-www-form-urlencoded',
                'Accept'        => 'application/json',
            ]
        );

        $this->checkout     = new StripeCheckout($this->http, self::API_BASE);
        $this->charge       = new StripeCharge($this->http, self::API_BASE);
        $this->customer     = new StripeCustomer($this->http, self::API_BASE);
        $this->refundModule = new StripeRefund($this->http, self::API_BASE);
        $this->webhook      = new StripeWebhook($config);
    }

    public function getName(): string { return 'Stripe'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $lineItems = [];

        if (! empty($request->items)) {
            foreach ($request->items as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency'     => strtolower($request->currency),
                        'unit_amount'  => (int) (($item['price'] ?? 0) * 100),
                        'product_data' => ['name' => $item['name'] ?? $request->description],
                    ],
                    'quantity' => $item['quantity'] ?? 1,
                ];
            }
        } else {
            $lineItems[] = [
                'price_data' => [
                    'currency'     => strtolower($request->currency),
                    'unit_amount'  => (int) ($request->amount * 100),
                    'product_data' => ['name' => $request->description ?: 'Payment'],
                ],
                'quantity' => 1,
            ];
        }

        $payload = [
            'mode'                => 'payment',
            'line_items'          => $lineItems,
            'success_url'         => $request->returnUrl ?: $request->callbackUrl,
            'cancel_url'          => $request->cancelUrl ?: $request->callbackUrl,
            'client_reference_id' => $request->orderId ?: ('ORD-' . uniqid()),
        ];

        if ($request->customer !== null) {
            $payload['customer_email'] = $request->customer->email;
        }

        if (! empty($request->metadata)) {
            $payload['metadata'] = $request->metadata;
        }

        $response = $this->checkout->create($payload);
        $sessionUrl = (string) Arr::get($response, 'url', '');
        $sessionId = (string) Arr::get($response, 'id', '');

        return new PaymentResponse(
            success:       ! empty($sessionUrl),
            transactionId: $sessionId,
            status:        (string) Arr::get($response, 'payment_status', Arr::get($response, 'status', 'open')),
            message:       '',
            amount:        $request->amount,
            currency:      strtoupper($request->currency),
            paymentUrl:    $sessionUrl,
            sessionId:     $sessionId !== '' ? $sessionId : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->checkout->retrieve($paymentId);
        $status = (string) Arr::get($response, 'payment_status', Arr::get($response, 'status', ''));
        $amountTotal = (int) Arr::get($response, 'amount_total', 0);

        return new PaymentResponse(
            success:       $status === 'paid',
            transactionId: (string) Arr::get($response, 'id', $paymentId),
            status:        $status,
            amount:        $amountTotal / 100,
            currency:      strtoupper((string) Arr::get($response, 'currency', '')),
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $payload = ['amount' => (int) ($request->amount * 100)];

        // Stripe needs either charge or payment_intent
        if (str_starts_with($request->transactionId, 'ch_')) {
            $payload['charge'] = $request->transactionId;
        } else {
            $payload['payment_intent'] = $request->transactionId;
        }

        if ($request->reason) {
            $payload['reason'] = match (strtolower($request->reason)) {
                'duplicate'            => 'duplicate',
                'fraudulent', 'fraud'  => 'fraudulent',
                default                => 'requested_by_customer',
            };
        }

        $response = $this->refundModule->create($payload);
        $refundStatus = (string) Arr::get($response, 'status', '');

        return new RefundResponse(
            success:       $refundStatus === 'succeeded',
            refundId:      (string) Arr::get($response, 'id', ''),
            transactionId: $request->transactionId,
            status:        $refundStatus,
            message:       (string) Arr::get($response, 'failure_reason', ''),
            amount:        ((int) Arr::get($response, 'amount', 0)) / 100,
            currency:      strtoupper((string) Arr::get($response, 'currency', '')),
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

    // ── Sub-module Access ──

    public function checkouts(): StripeCheckout { return $this->checkout; }
    public function charges(): StripeCharge { return $this->charge; }
    public function customers(): StripeCustomer { return $this->customer; }
    public function refunds(): StripeRefund { return $this->refundModule; }

    /**
     * Create a PaymentIntent (server-side payment).
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function createPaymentIntent(array $params): array
    {
        return $this->http->post(self::API_BASE . '/payment_intents', $params);
    }

    /**
     * Retrieve a PaymentIntent.
     *
     * @return array<string, mixed>
     */
    public function retrievePaymentIntent(string $intentId): array
    {
        return $this->http->get(self::API_BASE . "/payment_intents/{$intentId}");
    }
}
