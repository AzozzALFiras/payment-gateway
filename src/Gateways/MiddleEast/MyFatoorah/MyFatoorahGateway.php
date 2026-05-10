<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\MyFatoorah;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\Contracts\SupportsInvoice;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\RefundRequest;
use AzozzALFiras\PaymentGateway\DTOs\RefundResponse;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceRequest;
use AzozzALFiras\PaymentGateway\DTOs\InvoiceResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * MyFatoorah Payment Gateway Driver (V3 API).
 *
 * Supports:
 *  - Create Payment (Hosted / Embedded)
 *  - Get Payment Details
 *  - Update Payment (Capture / Release)
 *  - Session Management (Create / Get)
 *  - Invoice Lookup
 *  - Customer Details
 *  - Webhook Handling
 *  - Multi-Country: KWT, SAU, ARE, QAT, BHR, OMN, JOR, EGY
 *
 * @link https://docs.myfatoorah.com
 */
class MyFatoorahGateway implements GatewayInterface, SupportsRefund, SupportsWebhook, SupportsInvoice
{
    private const API_BASE_TEST = 'https://apitest.myfatoorah.com';

    /**
     * Country-specific API base URLs for production.
     *
     * Each country where MyFatoorah operates has its own dedicated API endpoint.
     * The test/sandbox environment uses a single shared URL for all countries.
     *
     * @var array<string, array{api: string, portal: string, name: string, currency: string}>
     *
     * @link https://docs.myfatoorah.com/docs/api-key#api--portal-urls
     */
    private const COUNTRIES = [
        'KWT' => [
            'api'      => 'https://api.myfatoorah.com',
            'portal'   => 'https://portal.myfatoorah.com',
            'name'     => 'Kuwait',
            'currency' => 'KWD',
        ],
        'SAU' => [
            'api'      => 'https://api-sa.myfatoorah.com',
            'portal'   => 'https://sa.myfatoorah.com',
            'name'     => 'Saudi Arabia',
            'currency' => 'SAR',
        ],
        'ARE' => [
            'api'      => 'https://api-ae.myfatoorah.com',
            'portal'   => 'https://ae.myfatoorah.com',
            'name'     => 'United Arab Emirates',
            'currency' => 'AED',
        ],
        'QAT' => [
            'api'      => 'https://api-qa.myfatoorah.com',
            'portal'   => 'https://qa.myfatoorah.com',
            'name'     => 'Qatar',
            'currency' => 'QAR',
        ],
        'BHR' => [
            'api'      => 'https://api.myfatoorah.com',
            'portal'   => 'https://portal.myfatoorah.com',
            'name'     => 'Bahrain',
            'currency' => 'BHD',
        ],
        'OMN' => [
            'api'      => 'https://api.myfatoorah.com',
            'portal'   => 'https://portal.myfatoorah.com',
            'name'     => 'Oman',
            'currency' => 'OMR',
        ],
        'JOR' => [
            'api'      => 'https://api.myfatoorah.com',
            'portal'   => 'https://portal.myfatoorah.com',
            'name'     => 'Jordan',
            'currency' => 'JOD',
        ],
        'EGY' => [
            'api'      => 'https://api-eg.myfatoorah.com',
            'portal'   => 'https://eg.myfatoorah.com',
            'name'     => 'Egypt',
            'currency' => 'EGP',
        ],
    ];

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected MyFatoorahSession $session;
    protected MyFatoorahInvoice $invoice;
    protected MyFatoorahCustomer $customer;
    protected MyFatoorahWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;

        $apiKey = (string) $config->require('api_key');

        // Validate country if specified
        $country = $this->resolveCountryCode();
        if ($country !== null && ! isset(self::COUNTRIES[$country])) {
            throw new \InvalidArgumentException(
                "Unsupported MyFatoorah country: '{$country}'. " .
                'Supported countries: ' . implode(', ', array_keys(self::COUNTRIES))
            );
        }

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Authorization' => "Bearer {$apiKey}",
                'Accept'        => 'application/json',
            ]
        );

        $this->session  = new MyFatoorahSession($this->http, $this);
        $this->invoice  = new MyFatoorahInvoice($this->http, $this);
        $this->customer = new MyFatoorahCustomer($this->http, $this);
        $this->webhook  = new MyFatoorahWebhook($config);
    }

    // ──────────────────────────────────────────
    //  GatewayInterface
    // ──────────────────────────────────────────

    public function getName(): string
    {
        return 'MyFatoorah';
    }

    public function isTestMode(): bool
    {
        return $this->config->isTestMode();
    }

    /**
     * Get the API base URL for the current environment and country.
     *
     * In test mode, all countries use the shared sandbox endpoint.
     * In live mode, the URL is resolved based on the configured country.
     */
    public function getBaseUrl(): string
    {
        if ($this->isTestMode()) {
            return self::API_BASE_TEST;
        }

        $country = $this->resolveCountryCode();

        if ($country !== null && isset(self::COUNTRIES[$country])) {
            return self::COUNTRIES[$country]['api'];
        }

        // Default to Kuwait (main endpoint) if no country specified
        return self::COUNTRIES['KWT']['api'];
    }

    /**
     * Get the portal URL for the current country.
     */
    public function getPortalUrl(): string
    {
        if ($this->isTestMode()) {
            return 'https://demo.myfatoorah.com';
        }

        $country = $this->resolveCountryCode();

        if ($country !== null && isset(self::COUNTRIES[$country])) {
            return self::COUNTRIES[$country]['portal'];
        }

        return self::COUNTRIES['KWT']['portal'];
    }

    /**
     * Get the configured country code.
     */
    public function getCountry(): string
    {
        return $this->resolveCountryCode() ?? 'KWT';
    }

    /**
     * Get the default currency for the configured country.
     */
    public function getDefaultCurrency(): string
    {
        $country = $this->resolveCountryCode();

        if ($country !== null && isset(self::COUNTRIES[$country])) {
            return self::COUNTRIES[$country]['currency'];
        }

        return 'KWD';
    }

    /**
     * Get the country display name.
     */
    public function getCountryName(): string
    {
        $country = $this->resolveCountryCode();

        if ($country !== null && isset(self::COUNTRIES[$country])) {
            return self::COUNTRIES[$country]['name'];
        }

        return 'Kuwait';
    }

    /**
     * Get all supported countries with their configuration.
     *
     * @return array<string, array{api: string, portal: string, name: string, currency: string}>
     */
    public static function getSupportedCountries(): array
    {
        return self::COUNTRIES;
    }

    /**
     * Resolve the country code from config (supports ISO 3166-1 alpha-2 and alpha-3).
     */
    private function resolveCountryCode(): ?string
    {
        $country = $this->config->get('country');

        if ($country === null || $country === '') {
            return null;
        }

        $country = strtoupper(trim((string) $country));

        // Already a valid alpha-3 code
        if (isset(self::COUNTRIES[$country])) {
            return $country;
        }

        // Map alpha-2 → alpha-3
        $alpha2Map = [
            'KW' => 'KWT',
            'SA' => 'SAU',
            'AE' => 'ARE',
            'QA' => 'QAT',
            'BH' => 'BHR',
            'OM' => 'OMN',
            'JO' => 'JOR',
            'EG' => 'EGY',
        ];

        return $alpha2Map[$country] ?? $country;
    }

    // ──────────────────────────────────────────
    //  Payments
    // ──────────────────────────────────────────

    /**
     * Create a new payment via the v2 SendPayment endpoint.
     *
     * @link https://docs.myfatoorah.com/reference/sendpayment
     */
    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'NotificationOption' => 'LNK',
            'InvoiceValue'       => $request->amount,
            'DisplayCurrencyIso' => $request->currency,
        ];

        if ($request->orderId !== '') {
            $payload['CustomerReference'] = $request->orderId;
        }

        if ($request->callbackUrl !== '') {
            $payload['CallBackUrl'] = $request->callbackUrl;
        }

        if ($request->returnUrl !== '') {
            $payload['ErrorUrl'] = $request->returnUrl;
        } elseif ($request->callbackUrl !== '') {
            $payload['ErrorUrl'] = $request->callbackUrl;
        }

        if ($request->customer !== null) {
            $payload['CustomerName']   = $request->customer->name;
            $payload['CustomerEmail']  = $request->customer->email;
            $payload['CustomerMobile'] = $request->customer->phone;
        }

        if (! empty($request->items)) {
            $payload['InvoiceItems'] = array_map(fn(array $item) => [
                'ItemName'  => $item['name'] ?? '',
                'Quantity'  => $item['quantity'] ?? 1,
                'UnitPrice' => $item['price'] ?? 0,
            ], $request->items);
        }

        // Merge any gateway-specific metadata
        $payload = array_merge($payload, $request->metadata);

        $response = $this->http->post(
            $this->getBaseUrl() . '/v2/SendPayment',
            $payload
        );

        $isSuccess = (bool) Arr::get($response, 'IsSuccess', false);
        $data = Arr::get($response, 'Data', []);

        return new PaymentResponse(
            success:       $isSuccess,
            transactionId: (string) Arr::get($data, 'InvoiceId', ''),
            status:        $isSuccess ? 'created' : 'failed',
            message:       (string) Arr::get($response, 'Message', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            paymentUrl:    (string) Arr::get($data, 'InvoiceURL', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Get payment status by InvoiceId via the v2 GetPaymentStatus endpoint.
     *
     * @link https://docs.myfatoorah.com/reference/get-payment-status
     */
    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->http->post(
            $this->getBaseUrl() . '/v2/GetPaymentStatus',
            [
                'Key'     => $paymentId,
                'KeyType' => 'InvoiceId',
            ]
        );

        $isSuccess = (bool) Arr::get($response, 'IsSuccess', false);
        $data = (array) Arr::get($response, 'Data', []);
        $transactions = (array) Arr::get($data, 'InvoiceTransactions', []);
        $latest = $transactions[0] ?? [];

        return new PaymentResponse(
            success:       $isSuccess,
            transactionId: (string) Arr::get($latest, 'TransactionId', (string) Arr::get($data, 'InvoiceId', $paymentId)),
            status:        (string) Arr::get($data, 'InvoiceStatus', ''),
            message:       (string) Arr::get($response, 'Message', ''),
            amount:        (float) Arr::get($data, 'InvoiceValue', 0),
            currency:      (string) Arr::get($data, 'DisplayCurrencyIso', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Update a payment — not supported on MyFatoorah v2.
     *
     * v2 SendPayment auto-captures. There is no Capture/Release action.
     * Use refund() for refunds.
     */
    public function updatePayment(string $paymentId, string $action, ?float $amount = null, array $extra = []): PaymentResponse
    {
        throw new \BadMethodCallException(
            'MyFatoorah v2 does not support payment updates (Capture/Release). ' .
            'Payments are auto-captured on SendPayment; use refund() for refunds.'
        );
    }

    // ──────────────────────────────────────────
    //  Refunds (via /v2/MakeRefund)
    // ──────────────────────────────────────────

    public function refund(RefundRequest $request): RefundResponse
    {
        $payload = array_merge([
            'Key'                     => $request->transactionId,
            'KeyType'                 => 'InvoiceId',
            'Amount'                  => $request->amount,
            'Comment'                 => $request->reason,
            'RefundChargeOnCustomer'  => false,
            'ServiceChargeOnCustomer' => false,
        ], $request->metadata);

        $response = $this->http->post(
            $this->getBaseUrl() . '/v2/MakeRefund',
            $payload
        );

        $isSuccess = (bool) Arr::get($response, 'IsSuccess', false);
        $data = (array) Arr::get($response, 'Data', []);

        return new RefundResponse(
            success:       $isSuccess,
            refundId:      (string) Arr::get($data, 'RefundReference', (string) Arr::get($data, 'RefundId', '')),
            transactionId: $request->transactionId,
            status:        $isSuccess ? 'refunded' : 'failed',
            message:       (string) Arr::get($response, 'Message', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            rawResponse:   $response,
        );
    }

    public function partialRefund(RefundRequest $request): RefundResponse
    {
        return $this->refund($request);
    }

    // ──────────────────────────────────────────
    //  Invoice Management
    // ──────────────────────────────────────────

    public function createInvoice(InvoiceRequest $request): InvoiceResponse
    {
        return $this->invoice->create($request);
    }

    public function getInvoice(string $invoiceId): InvoiceResponse
    {
        return $this->invoice->getByInvoiceId($invoiceId);
    }

    /**
     * Get invoice by external identifier.
     */
    public function getInvoiceByExternalId(string $externalId): InvoiceResponse
    {
        return $this->invoice->getByExternalId($externalId);
    }

    // ──────────────────────────────────────────
    //  Sessions
    // ──────────────────────────────────────────

    /**
     * Access the session management sub-module.
     */
    public function sessions(): MyFatoorahSession
    {
        return $this->session;
    }

    // ──────────────────────────────────────────
    //  Customers
    // ──────────────────────────────────────────

    /**
     * Access the customer sub-module.
     */
    public function customers(): MyFatoorahCustomer
    {
        return $this->customer;
    }

    // ──────────────────────────────────────────
    //  Webhooks
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
