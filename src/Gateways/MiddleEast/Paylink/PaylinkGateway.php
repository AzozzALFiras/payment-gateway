<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Paylink;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsInvoice;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceRequest;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Paylink Payment Gateway Driver.
 *
 * Supports:
 *  - Invoice-based payment flow (Create → Pay → Verify)
 *  - Invoice CRUD with Cancel & Digital Product Delivery
 *  - Reconciliation API (Transactions & Settlements)
 *  - Webhook handling
 *
 * @link https://developer.paylink.sa
 */
class PaylinkGateway implements GatewayInterface, SupportsInvoice, SupportsWebhook
{
    private const API_BASE_LIVE = 'https://restapi.paylink.sa';
    private const API_BASE_TEST = 'https://restpilot.paylink.sa';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected PaylinkAuth $auth;
    protected PaylinkInvoice $invoiceManager;
    protected PaylinkReconcile $reconcile;
    protected PaylinkWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ]
        );

        $baseUrl = $this->getBaseUrl();

        $this->auth = new PaylinkAuth(
            http:         $this->http,
            baseUrl:      $baseUrl,
            apiId:        (string) $config->require('api_id'),
            secretKey:    (string) $config->require('secret_key'),
            persistToken: (bool) $config->get('persist_token', true),
        );

        $this->invoiceManager = new PaylinkInvoice($this->http, $this->auth, $baseUrl);
        $this->reconcile      = new PaylinkReconcile($this->http, $this->auth, $baseUrl);
        $this->webhook        = new PaylinkWebhook($config);
    }

    // ──────────────────────────────────────────
    //  GatewayInterface
    // ──────────────────────────────────────────

    public function getName(): string
    {
        return 'Paylink';
    }

    public function isTestMode(): bool
    {
        return $this->config->isTestMode();
    }

    /**
     * Get the API base URL.
     */
    public function getBaseUrl(): string
    {
        return $this->isTestMode() ? self::API_BASE_TEST : self::API_BASE_LIVE;
    }

    /**
     * Initiate a payment by creating an invoice.
     *
     * Paylink uses an invoice-based flow:
     *  1. Create invoice → returns payment URL
     *  2. Redirect customer to payment URL
     *  3. Customer pays on Paylink's hosted page
     *  4. Customer is redirected back to callbackUrl
     */
    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $invoiceRequest = new InvoiceRequest(
            amount:      $request->amount,
            currency:    $request->currency,
            orderId:     $request->orderId,
            description: $request->description,
            callbackUrl: $request->callbackUrl,
            customer:    $request->customer,
            items:       $request->items,
            metadata:    $request->metadata,
        );

        $invoice = $this->invoiceManager->create($invoiceRequest);

        return new PaymentResponse(
            success:       $invoice->success,
            transactionId: $invoice->transactionNo,
            status:        $invoice->status,
            message:       $invoice->message,
            amount:        $invoice->amount,
            currency:      $request->currency,
            paymentUrl:    $invoice->paymentUrl,
            rawResponse:   $invoice->rawResponse,
        );
    }

    /**
     * Check payment status by transaction number.
     */
    public function status(string $paymentId): PaymentResponse
    {
        $invoice = $this->invoiceManager->get($paymentId);

        return new PaymentResponse(
            success:       $invoice->success,
            transactionId: $invoice->transactionNo,
            status:        $invoice->status,
            message:       $invoice->message,
            amount:        $invoice->amount,
            currency:      $invoice->currency,
            paymentUrl:    $invoice->paymentUrl,
            rawResponse:   $invoice->rawResponse,
        );
    }

    // ──────────────────────────────────────────
    //  SupportsInvoice
    // ──────────────────────────────────────────

    public function createInvoice(InvoiceRequest $request): InvoiceResponse
    {
        return $this->invoiceManager->create($request);
    }

    public function getInvoice(string $invoiceId): InvoiceResponse
    {
        return $this->invoiceManager->get($invoiceId);
    }

    /**
     * Cancel an invoice by transaction number.
     */
    public function cancelInvoice(string $transactionNo): InvoiceResponse
    {
        return $this->invoiceManager->cancel($transactionNo);
    }

    /**
     * Send digital product information to the payer.
     *
     * @param string $transactionNo
     * @param string $productInfo
     * @return array<string, mixed>
     */
    public function sendDigitalProduct(string $transactionNo, string $productInfo): array
    {
        return $this->invoiceManager->sendDigitalProduct($transactionNo, $productInfo);
    }

    // ──────────────────────────────────────────
    //  Reconciliation
    // ──────────────────────────────────────────

    /**
     * Access the reconciliation sub-module.
     */
    public function reconcile(): PaylinkReconcile
    {
        return $this->reconcile;
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
}
