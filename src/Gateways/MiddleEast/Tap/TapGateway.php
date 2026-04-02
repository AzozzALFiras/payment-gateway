<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tap;

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
 * Tap Payments Gateway Driver.
 *
 * Sub-modules:
 *  - TapCharge     — Charge CRUD operations
 *  - TapAuthorize  — Authorization & Void
 *  - TapCustomer   — Customer management
 *  - TapInvoice    — Invoice management
 *  - TapToken      — Card tokenization
 *  - TapWebhook    — Webhook handling
 *
 * @link https://developers.tap.company
 */
class TapGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const API_BASE = 'https://api.tap.company/v2';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected TapCharge $charge;
    protected TapAuthorize $authorize;
    protected TapCustomer $customer;
    protected TapInvoice $invoice;
    protected TapToken $token;
    protected TapWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;

        $secretKey = (string) $config->require('secret_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Authorization' => "Bearer {$secretKey}",
                'Accept'        => 'application/json',
                'Content-Type'  => 'application/json',
            ]
        );

        $this->charge    = new TapCharge($this->http, self::API_BASE);
        $this->authorize = new TapAuthorize($this->http, self::API_BASE);
        $this->customer  = new TapCustomer($this->http, self::API_BASE);
        $this->invoice   = new TapInvoice($this->http, self::API_BASE);
        $this->token     = new TapToken($this->http, self::API_BASE);
        $this->webhook   = new TapWebhook($config);
    }

    // ── GatewayInterface ──

    public function getName(): string { return 'Tap'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        return $this->charge->create($request);
    }

    public function status(string $paymentId): PaymentResponse
    {
        return $this->charge->retrieve($paymentId);
    }

    // ── SupportsRefund ──

    public function refund(RefundRequest $request): RefundResponse
    {
        $payload = [
            'charge_id' => $request->transactionId,
            'amount'    => $request->amount,
            'currency'  => $request->currency,
            'reason'    => $request->reason,
        ];

        $response = $this->http->post(self::API_BASE . '/refunds', array_merge($payload, $request->metadata));
        $status = (string) Arr::get($response, 'status', '');

        return new RefundResponse(
            success:       $status === 'REFUNDED',
            refundId:      (string) Arr::get($response, 'id', ''),
            transactionId: $request->transactionId,
            status:        $status,
            message:       (string) Arr::get($response, 'response.message', ''),
            amount:        (float) Arr::get($response, 'amount', $request->amount),
            currency:      (string) Arr::get($response, 'currency', $request->currency),
            rawResponse:   $response,
        );
    }

    public function partialRefund(RefundRequest $request): RefundResponse
    {
        return $this->refund($request);
    }

    // ── SupportsWebhook ──

    public function handleWebhook(array $payload, array $headers = []): WebhookPayload
    {
        return $this->webhook->handle($payload, $headers);
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        return $this->webhook->verify($payload, $headers);
    }

    // ── Sub-module Access ──

    public function charges(): TapCharge { return $this->charge; }
    public function authorizations(): TapAuthorize { return $this->authorize; }
    public function customers(): TapCustomer { return $this->customer; }
    public function invoices(): TapInvoice { return $this->invoice; }
    public function tokens(): TapToken { return $this->token; }
}
