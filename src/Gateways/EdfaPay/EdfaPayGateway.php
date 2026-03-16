<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\EdfaPay;

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

/**
 * EdfaPay Payment Gateway Driver.
 *
 * Supports two integration modes:
 *  1. Checkout (Hosted) — Customer is redirected to EdfaPay's hosted page
 *  2. Embedded S2S — Direct card processing via server-to-server calls
 *
 * Additional features:
 *  - Apple Pay integration
 *  - Tamara BNPL support
 *  - Recurring payments
 *  - Full/Partial refunds
 *  - Authorize → Capture workflow
 *  - Hash-based authentication
 *  - Webhook handling with validation
 *
 * @link https://docs.edfapay.com
 */
class EdfaPayGateway implements GatewayInterface, SupportsRefund, SupportsRecurring, SupportsWebhook
{
    private const API_BASE_LIVE = 'https://api.edfapay.com';
    private const API_BASE_TEST = 'https://api.sandbox.edfapay.com';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected EdfaPayCheckout $checkout;
    protected EdfaPayEmbedded $embedded;
    protected EdfaPayApplePay $applePay;
    protected EdfaPayWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;

        $clientKey = (string) $config->require('client_key');
        $password  = (string) $config->require('password');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Accept' => 'application/json',
            ]
        );

        $baseUrl = $this->getBaseUrl();

        $this->checkout = new EdfaPayCheckout($this->http, $baseUrl, $clientKey, $password);
        $this->embedded = new EdfaPayEmbedded($this->http, $baseUrl, $clientKey, $password);
        $this->applePay = new EdfaPayApplePay($this->http, $baseUrl, $clientKey);
        $this->webhook  = new EdfaPayWebhook($config);
    }

    // ──────────────────────────────────────────
    //  GatewayInterface
    // ──────────────────────────────────────────

    public function getName(): string
    {
        return 'EdfaPay';
    }

    public function isTestMode(): bool
    {
        return $this->config->isTestMode();
    }

    /**
     * Get the API base URL for the current environment.
     */
    public function getBaseUrl(): string
    {
        return $this->isTestMode() ? self::API_BASE_TEST : self::API_BASE_LIVE;
    }

    /**
     * Initiate a payment via the hosted checkout flow (default).
     *
     * For embedded/S2S card payments, use the embedded() sub-module directly.
     */
    public function purchase(PaymentRequest $request): PaymentResponse
    {
        return $this->checkout->initiate(
            amount:           $request->amount,
            currency:         $request->currency,
            orderId:          $request->orderId ?: ('ORD-' . uniqid()),
            orderDescription: $request->description,
            payerEmail:       $request->customer?->email ?? '',
            successUrl:       $request->returnUrl ?: $request->callbackUrl,
            failUrl:          $request->cancelUrl ?: $request->callbackUrl,
            extra:            $request->metadata,
        );
    }

    /**
     * Get transaction status.
     */
    public function status(string $paymentId): PaymentResponse
    {
        return $this->checkout->status(
            transactionId: $paymentId,
            orderId:       '',
            payerEmail:    '',
        );
    }

    // ──────────────────────────────────────────
    //  SupportsRefund
    // ──────────────────────────────────────────

    public function refund(RefundRequest $request): RefundResponse
    {
        return $this->checkout->fullRefund($request);
    }

    public function partialRefund(RefundRequest $request): RefundResponse
    {
        return $this->checkout->partialRefund($request);
    }

    // ──────────────────────────────────────────
    //  SupportsRecurring
    // ──────────────────────────────────────────

    public function recurring(PaymentRequest $request): PaymentResponse
    {
        if ($request->recurringToken === null) {
            throw new \InvalidArgumentException('Recurring token (first transaction ID) is required');
        }

        return $this->checkout->recurring(
            recurringToken:   $request->recurringToken,
            amount:           $request->amount,
            currency:         $request->currency,
            orderId:          $request->orderId ?: ('ORD-' . uniqid()),
            orderDescription: $request->description,
        );
    }

    // ──────────────────────────────────────────
    //  SupportsWebhook
    // ──────────────────────────────────────────

    public function handleWebhook(array $payload, array $headers = []): WebhookPayload
    {
        return $this->webhook->handle($payload, $headers);
    }

    public function verifyWebhook(array $payload, array $headers = []): bool
    {
        return $this->webhook->verify($payload, $headers);
    }

    // ──────────────────────────────────────────
    //  Sub-module Access
    // ──────────────────────────────────────────

    /**
     * Access the hosted checkout sub-module.
     */
    public function checkout(): EdfaPayCheckout
    {
        return $this->checkout;
    }

    /**
     * Access the embedded S2S sub-module for direct card processing.
     */
    public function embedded(): EdfaPayEmbedded
    {
        return $this->embedded;
    }

    /**
     * Access the Apple Pay sub-module.
     */
    public function applePay(): EdfaPayApplePay
    {
        return $this->applePay;
    }
}
