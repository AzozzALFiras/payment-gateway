<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\Mollie;

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
 * Mollie Payment Gateway Driver.
 *
 * Sub-modules:
 *  - MolliePayment  — Payments API (create, get, list, cancel)
 *  - MollieRefund   — Refunds API
 *  - MollieCustomer — Customers API
 *  - MollieWebhook  — Webhook handling
 *
 * Covers: Germany, France, Netherlands, Belgium, Austria, Portugal, Spain, UK.
 *
 * @link https://docs.mollie.com/reference/overview
 */
class MollieGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const API_BASE = 'https://api.mollie.com/v2';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected MolliePayment $paymentModule;
    protected MollieRefund $refundModule;
    protected MollieCustomer $customerModule;
    protected MollieWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $apiKey = (string) $config->require('api_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
                'Accept'        => 'application/json',
            ]
        );

        $this->paymentModule  = new MolliePayment($this->http, self::API_BASE);
        $this->refundModule   = new MollieRefund($this->http, self::API_BASE);
        $this->customerModule = new MollieCustomer($this->http, self::API_BASE);
        $this->webhook        = new MollieWebhook($config, $this->paymentModule);
    }

    public function getName(): string { return 'Mollie'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'amount' => [
                'currency' => strtoupper($request->currency),
                'value'    => number_format($request->amount, 2, '.', ''),
            ],
            'description' => $request->description ?: 'Payment',
            'redirectUrl'  => $request->returnUrl ?: $request->callbackUrl,
        ];

        if ($request->callbackUrl !== '') {
            $payload['webhookUrl'] = $request->callbackUrl;
        }

        if ($request->cancelUrl !== '') {
            $payload['cancelUrl'] = $request->cancelUrl;
        }

        $locale = $this->config->get('locale');
        if (! empty($locale)) {
            $payload['locale'] = $locale;
        }

        $method = $this->config->get('method');
        if (! empty($method)) {
            $payload['method'] = $method;
        }

        if (! empty($request->metadata)) {
            $payload['metadata'] = $request->metadata;
        }

        if ($request->orderId !== '') {
            $payload['metadata']['order_id'] = $request->orderId;
        }

        $response = $this->paymentModule->create($payload);
        $paymentId = (string) Arr::get($response, 'id', '');
        $checkoutUrl = (string) Arr::get($response, '_links.checkout.href', '');
        $status = (string) Arr::get($response, 'status', '');

        return new PaymentResponse(
            success:       ! empty($checkoutUrl),
            transactionId: $paymentId,
            status:        $status,
            message:       '',
            amount:        $request->amount,
            currency:      strtoupper($request->currency),
            paymentUrl:    $checkoutUrl,
            sessionId:     $paymentId !== '' ? $paymentId : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->paymentModule->retrieve($paymentId);
        $status = (string) Arr::get($response, 'status', '');
        $amountData = (array) Arr::get($response, 'amount', []);

        return new PaymentResponse(
            success:       $status === 'paid',
            transactionId: (string) Arr::get($response, 'id', $paymentId),
            status:        $status,
            amount:        (float) ($amountData['value'] ?? 0),
            currency:      strtoupper((string) ($amountData['currency'] ?? '')),
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $payload = [
            'amount' => [
                'currency' => strtoupper($request->currency),
                'value'    => number_format($request->amount, 2, '.', ''),
            ],
        ];

        if ($request->reason !== '') {
            $payload['description'] = $request->reason;
        }

        $response = $this->refundModule->create($request->transactionId, $payload);
        $refundStatus = (string) Arr::get($response, 'status', '');
        $amountData = (array) Arr::get($response, 'amount', []);

        return new RefundResponse(
            success:       in_array($refundStatus, ['pending', 'processing', 'refunded'], true),
            refundId:      (string) Arr::get($response, 'id', ''),
            transactionId: $request->transactionId,
            status:        $refundStatus,
            message:       (string) Arr::get($response, 'description', ''),
            amount:        (float) ($amountData['value'] ?? 0),
            currency:      strtoupper((string) ($amountData['currency'] ?? '')),
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

    public function payments(): MolliePayment { return $this->paymentModule; }
    public function refunds(): MollieRefund { return $this->refundModule; }
    public function customers(): MollieCustomer { return $this->customerModule; }
}
