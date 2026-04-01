<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\PayPal;

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
 * PayPal Payment Gateway Driver.
 *
 * Sub-modules:
 *  - PayPalAuth    — OAuth 2.0 client credentials
 *  - PayPalOrder   — Orders API (create, capture, authorize)
 *  - PayPalPayment — Payments API (captures, authorizations)
 *  - PayPalRefund  — Refunds
 *  - PayPalWebhook — Webhook handling
 *
 * @link https://developer.paypal.com/docs/api/overview/
 */
class PayPalGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const LIVE_URL    = 'https://api-m.paypal.com';
    private const SANDBOX_URL = 'https://api-m.sandbox.paypal.com';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected PayPalAuth $auth;
    protected PayPalOrder $order;
    protected PayPalPayment $payment;
    protected PayPalRefund $refundModule;
    protected PayPalWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $baseUrl = $config->isTestMode() ? self::SANDBOX_URL : self::LIVE_URL;

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ]
        );

        $this->auth         = new PayPalAuth(
            $this->http,
            $baseUrl,
            (string) $config->require('client_id'),
            (string) $config->require('client_secret')
        );
        $this->order        = new PayPalOrder($this->http, $baseUrl, $this->auth);
        $this->payment      = new PayPalPayment($this->http, $baseUrl, $this->auth);
        $this->refundModule = new PayPalRefund($this->http, $baseUrl, $this->auth);
        $this->webhook      = new PayPalWebhook($config);
    }

    public function getName(): string { return 'PayPal'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $purchaseUnit = [
            'amount' => [
                'currency_code' => strtoupper($request->currency),
                'value'         => number_format($request->amount, 2, '.', ''),
            ],
        ];

        if ($request->orderId !== '') {
            $purchaseUnit['invoice_id'] = $request->orderId;
        }

        if ($request->description !== '') {
            $purchaseUnit['description'] = $request->description;
        }

        if (! empty($request->items)) {
            $itemTotal = 0.0;
            $lineItems = [];
            foreach ($request->items as $item) {
                $price = (float) ($item['price'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 1);
                $itemTotal += $price * $qty;
                $lineItems[] = [
                    'name'        => $item['name'] ?? $request->description,
                    'unit_amount' => [
                        'currency_code' => strtoupper($request->currency),
                        'value'         => number_format($price, 2, '.', ''),
                    ],
                    'quantity' => (string) $qty,
                ];
            }
            $purchaseUnit['items'] = $lineItems;
            $purchaseUnit['amount']['breakdown'] = [
                'item_total' => [
                    'currency_code' => strtoupper($request->currency),
                    'value'         => number_format($itemTotal, 2, '.', ''),
                ],
            ];
        }

        $payload = [
            'intent'         => 'CAPTURE',
            'purchase_units' => [$purchaseUnit],
            'application_context' => [
                'return_url' => $request->returnUrl ?: $request->callbackUrl,
                'cancel_url' => $request->cancelUrl ?: $request->callbackUrl,
            ],
        ];

        if ($request->customer !== null && $request->customer->email !== '') {
            $payload['payer'] = [
                'email_address' => $request->customer->email,
            ];
            if ($request->customer->name !== '') {
                $payload['payer']['name'] = [
                    'given_name' => $request->customer->name,
                ];
            }
        }

        if (! empty($request->metadata)) {
            $purchaseUnit['custom_id'] = json_encode($request->metadata);
            $payload['purchase_units'] = [$purchaseUnit];
        }

        $response = $this->order->create($payload);
        $orderId = (string) Arr::get($response, 'id', '');
        $status = (string) Arr::get($response, 'status', '');

        // Extract approval link
        $approvalUrl = '';
        $links = (array) Arr::get($response, 'links', []);
        foreach ($links as $link) {
            if (($link['rel'] ?? '') === 'approve') {
                $approvalUrl = (string) ($link['href'] ?? '');
                break;
            }
        }

        return new PaymentResponse(
            success:       ! empty($approvalUrl),
            transactionId: $orderId,
            status:        $status,
            message:       '',
            amount:        $request->amount,
            currency:      strtoupper($request->currency),
            paymentUrl:    $approvalUrl,
            sessionId:     $orderId !== '' ? $orderId : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->order->retrieve($paymentId);
        $status = (string) Arr::get($response, 'status', '');

        $amount = 0.0;
        $currency = '';
        $purchaseUnits = (array) Arr::get($response, 'purchase_units', []);
        if (! empty($purchaseUnits)) {
            $amountData = (array) Arr::get($purchaseUnits[0], 'amount', []);
            $amount = (float) ($amountData['value'] ?? 0);
            $currency = strtoupper((string) ($amountData['currency_code'] ?? ''));
        }

        return new PaymentResponse(
            success:       in_array($status, ['COMPLETED', 'APPROVED'], true),
            transactionId: (string) Arr::get($response, 'id', $paymentId),
            status:        $status,
            amount:        $amount,
            currency:      $currency,
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $payload = [];

        if ($request->amount > 0) {
            $payload['amount'] = [
                'value'         => number_format($request->amount, 2, '.', ''),
                'currency_code' => strtoupper($request->currency),
            ];
        }

        if ($request->reason !== '') {
            $payload['note_to_payer'] = $request->reason;
        }

        $response = $this->refundModule->create($request->transactionId, $payload);
        $refundStatus = (string) Arr::get($response, 'status', '');

        $refundAmount = 0.0;
        $refundCurrency = '';
        $amountData = (array) Arr::get($response, 'amount', []);
        if (! empty($amountData)) {
            $refundAmount = (float) ($amountData['value'] ?? 0);
            $refundCurrency = strtoupper((string) ($amountData['currency_code'] ?? ''));
        }

        return new RefundResponse(
            success:       $refundStatus === 'COMPLETED',
            refundId:      (string) Arr::get($response, 'id', ''),
            transactionId: $request->transactionId,
            status:        $refundStatus,
            message:       (string) Arr::get($response, 'status_details.reason', ''),
            amount:        $refundAmount,
            currency:      $refundCurrency,
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

    public function orders(): PayPalOrder { return $this->order; }
    public function payments(): PayPalPayment { return $this->payment; }
    public function refunds(): PayPalRefund { return $this->refundModule; }

    /**
     * Capture an approved order (after customer approves on PayPal).
     *
     * @return array<string, mixed>
     */
    public function captureOrder(string $orderId): array
    {
        return $this->order->capture($orderId);
    }
}
