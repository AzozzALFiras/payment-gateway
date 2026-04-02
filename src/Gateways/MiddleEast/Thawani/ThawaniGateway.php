<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Thawani;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Thawani Gateway Driver (Oman).
 *
 * Sub-modules:
 *  - ThawaniSession  — Checkout sessions
 *  - ThawaniCustomer — Customer management
 *  - ThawaniWebhook  — Webhook handling
 *
 * @link https://docs.thawani.om
 */
class ThawaniGateway implements GatewayInterface, SupportsWebhook
{
    private const API_BASE_LIVE = 'https://checkout.thawani.om/api/v1';
    private const API_BASE_TEST = 'https://uatcheckout.thawani.om/api/v1';
    private const CHECKOUT_LIVE = 'https://checkout.thawani.om/pay/';
    private const CHECKOUT_TEST = 'https://uatcheckout.thawani.om/pay/';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected ThawaniSession $session;
    protected ThawaniCustomer $customer;
    protected ThawaniWebhook $webhook;
    protected string $publishableKey;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $secretKey = (string) $config->require('secret_key');
        $this->publishableKey = (string) $config->require('publishable_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Thawani-Api-Key' => $secretKey,
                'Accept'          => 'application/json',
                'Content-Type'    => 'application/json',
            ]
        );

        $baseUrl = $this->getBaseUrl();
        $checkoutBase = $this->getCheckoutBaseUrl();
        $this->session  = new ThawaniSession($this->http, $baseUrl, $checkoutBase, $this->publishableKey);
        $this->customer = new ThawaniCustomer($this->http, $baseUrl);
        $this->webhook  = new ThawaniWebhook($config);
    }

    public function getName(): string { return 'Thawani'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }
    public function getBaseUrl(): string { return $this->isTestMode() ? self::API_BASE_TEST : self::API_BASE_LIVE; }
    public function getCheckoutBaseUrl(): string { return $this->isTestMode() ? self::CHECKOUT_TEST : self::CHECKOUT_LIVE; }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $amountInBaisa = $request->metadata['amount_in_baisa'] ?? (int) ($request->amount * 1000);

        $payload = [
            'client_reference_id' => $request->orderId ?: ('ORD-' . uniqid()),
            'mode'                => 'payment',
            'products'            => [],
            'success_url'         => $request->returnUrl ?: $request->callbackUrl,
            'cancel_url'          => $request->cancelUrl ?: $request->callbackUrl,
        ];

        if (! empty($request->items)) {
            $payload['products'] = array_map(fn(array $item) => [
                'name'        => $item['name'] ?? $request->description,
                'quantity'    => (int) ($item['quantity'] ?? 1),
                'unit_amount' => (int) (($item['price'] ?? 0) * 1000),
            ], $request->items);
        } else {
            $payload['products'] = [['name' => $request->description ?: 'Payment', 'quantity' => 1, 'unit_amount' => $amountInBaisa]];
        }

        if ($request->recurringToken !== null) {
            $payload['payment_method_id'] = $request->recurringToken;
        }

        $response = $this->session->create($payload);
        $data = (array) Arr::get($response, 'data', $response);
        $sessionId = (string) Arr::get($data, 'session_id', '');
        $isSuccess = (bool) Arr::get($response, 'success', false);
        $paymentUrl = $sessionId !== '' ? $this->session->getCheckoutUrl($sessionId) : '';

        return new PaymentResponse(
            success:       $isSuccess && $sessionId !== '',
            transactionId: $sessionId,
            status:        $isSuccess ? 'created' : 'failed',
            message:       (string) Arr::get($response, 'description', ''),
            amount:        $request->amount,
            currency:      'OMR',
            paymentUrl:    $paymentUrl,
            sessionId:     $sessionId !== '' ? $sessionId : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->session->retrieve($paymentId);
        $data = (array) Arr::get($response, 'data', $response);
        $status = (string) Arr::get($data, 'payment_status', '');

        return new PaymentResponse(
            success:       $status === 'paid',
            transactionId: (string) Arr::get($data, 'session_id', $paymentId),
            status:        $status,
            message:       (string) Arr::get($response, 'description', ''),
            amount:        (float) Arr::get($data, 'total_amount', 0) / 1000,
            currency:      'OMR',
            sessionId:     (string) Arr::get($data, 'session_id', ''),
            rawResponse:   $response,
        );
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

    public function sessions(): ThawaniSession { return $this->session; }
    public function customers(): ThawaniCustomer { return $this->customer; }

    /**
     * Create a payment intent (charge a saved card).
     *
     * @return array<string, mixed>
     */
    public function createPaymentIntent(int $amountInBaisa, string $clientReferenceId, string $paymentMethodId, string $returnUrl): array
    {
        return $this->http->post($this->getBaseUrl() . '/payment_intents', [
            'amount'              => $amountInBaisa,
            'client_reference_id' => $clientReferenceId,
            'payment_method_id'   => $paymentMethodId,
            'return_url'          => $returnUrl,
        ]);
    }
}
