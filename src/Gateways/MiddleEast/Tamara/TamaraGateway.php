<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tamara;

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
 * Tamara BNPL Gateway Driver.
 *
 * Sub-modules:
 *  - TamaraCheckout — Checkout session creation
 *  - TamaraOrder    — Order management (auth, capture, cancel, refund)
 *  - TamaraWebhook  — Webhook handling
 *
 * @link https://docs.tamara.co
 */
class TamaraGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const API_BASE_LIVE = 'https://api.tamara.co';
    private const API_BASE_TEST = 'https://api-sandbox.tamara.co';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected TamaraCheckout $checkout;
    protected TamaraOrder $order;
    protected TamaraWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $apiToken = (string) $config->require('api_token');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Authorization' => "Bearer {$apiToken}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ]
        );

        $baseUrl = $this->getBaseUrl();
        $this->checkout = new TamaraCheckout($this->http, $baseUrl);
        $this->order    = new TamaraOrder($this->http, $baseUrl);
        $this->webhook  = new TamaraWebhook($config);
    }

    public function getName(): string { return 'Tamara'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function getBaseUrl(): string
    {
        return $this->isTestMode() ? self::API_BASE_TEST : self::API_BASE_LIVE;
    }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'order_reference_id' => $request->orderId ?: ('ORD-' . uniqid()),
            'total_amount'       => ['amount' => $request->amount, 'currency' => $request->currency],
            'description'        => $request->description,
            'country_code'       => $request->customer?->country ?: 'SA',
            'payment_type'       => $request->metadata['payment_type'] ?? 'PAY_BY_INSTALMENTS',
            'locale'             => $request->metadata['locale'] ?? 'en_US',
            'merchant_url'       => [
                'success'      => $request->returnUrl ?: $request->callbackUrl,
                'failure'      => $request->cancelUrl ?: $request->callbackUrl,
                'cancel'       => $request->cancelUrl ?: $request->callbackUrl,
                'notification' => $request->callbackUrl,
            ],
        ];

        if ($request->customer !== null) {
            $payload['consumer'] = [
                'first_name'   => explode(' ', $request->customer->name)[0] ?? '',
                'last_name'    => explode(' ', $request->customer->name)[1] ?? '',
                'phone_number' => $request->customer->phone,
                'email'        => $request->customer->email,
            ];
        }

        if (! empty($request->items)) {
            $payload['items'] = array_map(fn(array $item) => [
                'reference_id' => $item['id'] ?? uniqid(),
                'name'         => $item['name'] ?? '',
                'quantity'     => $item['quantity'] ?? 1,
                'total_amount' => [
                    'amount'   => ($item['price'] ?? 0) * ($item['quantity'] ?? 1),
                    'currency' => $request->currency,
                ],
            ], $request->items);
        }

        $payload['shipping_amount'] = ['amount' => $request->metadata['shipping_amount'] ?? 0, 'currency' => $request->currency];
        $payload['tax_amount']      = ['amount' => $request->metadata['tax_amount'] ?? 0, 'currency' => $request->currency];

        $response = $this->checkout->create($payload);
        $checkoutUrl = (string) Arr::get($response, 'checkout_url', '');

        return new PaymentResponse(
            success:       ! empty($checkoutUrl),
            transactionId: (string) Arr::get($response, 'order_id', ''),
            status:        ! empty($checkoutUrl) ? 'checkout_created' : 'failed',
            message:       (string) Arr::get($response, 'message', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            paymentUrl:    $checkoutUrl,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->order->getDetails($paymentId);

        return new PaymentResponse(
            success:       true,
            transactionId: (string) Arr::get($response, 'order_id', $paymentId),
            status:        (string) Arr::get($response, 'status', ''),
            amount:        (float) Arr::get($response, 'total_amount.amount', 0),
            currency:      (string) Arr::get($response, 'total_amount.currency', ''),
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $response = $this->order->refund($request->transactionId, $request->amount, $request->currency, $request->reason ?: 'Refund');

        return new RefundResponse(
            success:       ! empty(Arr::get($response, 'refund_id')),
            refundId:      (string) Arr::get($response, 'refund_id', ''),
            transactionId: $request->transactionId,
            status:        ! empty(Arr::get($response, 'refund_id')) ? 'refunded' : 'failed',
            message:       (string) Arr::get($response, 'message', ''),
            amount:        $request->amount,
            currency:      $request->currency,
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

    public function checkouts(): TamaraCheckout { return $this->checkout; }
    public function orders(): TamaraOrder { return $this->order; }
}
