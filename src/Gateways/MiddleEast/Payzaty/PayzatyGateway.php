<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Payzaty;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Payzaty Payment Gateway Driver (Saudi Arabia).
 *
 * Sub-modules:
 *  - PayzatyCheckout — Checkout page creation & status
 *  - PayzatyWebhook  — Webhook handling
 *
 * @link https://payzaty.com/developer/reference
 */
class PayzatyGateway implements GatewayInterface, SupportsWebhook
{
    private const API_BASE_LIVE = 'https://api.payzaty.com';
    private const API_BASE_TEST = 'https://api.sandbox.payzaty.com';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected PayzatyCheckout $checkout;
    protected PayzatyWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $accountNo = (string) $config->require('account_no');
        $secretKey = (string) $config->require('secret_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'X-AccountNo'  => $accountNo,
                'X-SecretKey'  => $secretKey,
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
                'X-Language'   => (string) $config->get('language', 'en'),
            ]
        );

        $baseUrl = $this->getBaseUrl();
        $this->checkout = new PayzatyCheckout($this->http, $baseUrl);
        $this->webhook  = new PayzatyWebhook($config);
    }

    public function getName(): string { return 'Payzaty'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }
    public function getBaseUrl(): string { return $this->isTestMode() ? self::API_BASE_TEST : self::API_BASE_LIVE; }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'amount'       => $request->amount,
            'currency'     => $request->currency ?: 'SAR',
            'description'  => $request->description ?: 'Payment',
            'order_id'     => $request->orderId ?: ('ORD-' . uniqid()),
            'callback_url' => $request->callbackUrl,
            'success_url'  => $request->returnUrl ?: $request->callbackUrl,
            'error_url'    => $request->cancelUrl ?: $request->callbackUrl,
        ];

        if ($request->customer !== null) {
            $payload['customer'] = ['name' => $request->customer->name, 'email' => $request->customer->email, 'mobile' => $request->customer->phone];
        }

        $payload = array_merge($payload, $request->metadata);
        $response = $this->checkout->create($payload);

        $sessionUrl = (string) Arr::get($response, 'payment_url', Arr::get($response, 'session_url', ''));
        $sessionId = (string) Arr::get($response, 'session_id', Arr::get($response, 'payment_id', ''));

        return new PaymentResponse(
            success:       ! empty($sessionUrl) || ! empty($sessionId),
            transactionId: $sessionId,
            status:        (! empty($sessionUrl) || ! empty($sessionId)) ? 'checkout_created' : 'failed',
            message:       (string) Arr::get($response, 'message', ''),
            amount:        $request->amount,
            currency:      $request->currency ?: 'SAR',
            paymentUrl:    $sessionUrl,
            sessionId:     $sessionId !== '' ? $sessionId : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->checkout->status($paymentId);
        $status = (string) Arr::get($response, 'status', Arr::get($response, 'payment_status', ''));

        return new PaymentResponse(
            success:       strtolower($status) === 'paid' || strtolower($status) === 'success',
            transactionId: (string) Arr::get($response, 'payment_id', $paymentId),
            status:        $status,
            message:       (string) Arr::get($response, 'message', ''),
            amount:        (float) Arr::get($response, 'amount', 0),
            currency:      (string) Arr::get($response, 'currency', 'SAR'),
            rawResponse:   $response,
        );
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookPayload { return $this->webhook->handle($payload, $headers); }
    public function verifyWebhook(array $payload, array $headers = []): bool { return $this->webhook->verify($payload, $headers); }

    public function checkouts(): PayzatyCheckout { return $this->checkout; }
}
