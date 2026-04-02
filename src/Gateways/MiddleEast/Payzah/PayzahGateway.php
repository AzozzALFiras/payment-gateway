<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Payzah;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Payzah Payment Gateway Driver (Kuwait).
 *
 * Sub-modules:
 *  - PayzahPayment — Payment init & status
 *  - PayzahWebhook — Webhook handling
 *
 * @link https://payzah.com
 */
class PayzahGateway implements GatewayInterface, SupportsWebhook
{
    private const API_BASE_LIVE = 'https://api.payzah.com';
    private const API_BASE_TEST = 'https://test-api.payzah.com';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected PayzahPayment $payment;
    protected PayzahWebhook $webhook;
    protected string $privateKey;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $this->privateKey = (string) $config->require('private_key');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: ['Accept' => 'application/json', 'Content-Type' => 'application/json']
        );

        $baseUrl = $this->getBaseUrl();
        $this->payment = new PayzahPayment($this->http, $baseUrl, $this->privateKey);
        $this->webhook = new PayzahWebhook($config, $this->privateKey);
    }

    public function getName(): string { return 'Payzah'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }
    public function getBaseUrl(): string { return $this->isTestMode() ? self::API_BASE_TEST : self::API_BASE_LIVE; }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'amount'       => $request->amount,
            'currency'     => $request->currency ?: 'KWD',
            'order_id'     => $request->orderId ?: ('ORD-' . uniqid()),
            'description'  => $request->description ?: 'Payment',
            'success_url'  => $request->returnUrl ?: $request->callbackUrl,
            'error_url'    => $request->cancelUrl ?: $request->callbackUrl,
            'callback_url' => $request->callbackUrl,
        ];

        if ($request->customer !== null) {
            $payload['customer_name']  = $request->customer->name;
            $payload['customer_email'] = $request->customer->email;
            $payload['customer_phone'] = $request->customer->phone;
        }

        if (! empty($request->metadata['payment_method'])) {
            $payload['payment_method'] = $request->metadata['payment_method'];
        }

        if (! empty($request->metadata['vendors'])) {
            $payload['vendors'] = $request->metadata['vendors'];
        }

        $payload = array_merge($payload, Arr::only($request->metadata, ['lang', 'custom_field']));

        $response = $this->payment->init($payload);
        $paymentUrl = (string) Arr::get($response, 'payment_url', Arr::get($response, 'redirect_url', ''));
        $paymentId = (string) Arr::get($response, 'payment_id', Arr::get($response, 'transaction_id', ''));

        return new PaymentResponse(
            success:       ! empty($paymentUrl),
            transactionId: $paymentId,
            status:        ! empty($paymentUrl) ? 'initiated' : 'failed',
            message:       (string) Arr::get($response, 'message', ''),
            amount:        $request->amount,
            currency:      $request->currency ?: 'KWD',
            paymentUrl:    $paymentUrl,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->payment->status($paymentId);
        $status = (string) Arr::get($response, 'status', Arr::get($response, 'payment_status', ''));

        return new PaymentResponse(
            success:       strtolower($status) === 'paid' || strtolower($status) === 'success',
            transactionId: (string) Arr::get($response, 'payment_id', $paymentId),
            status:        $status,
            message:       (string) Arr::get($response, 'message', ''),
            amount:        (float) Arr::get($response, 'amount', 0),
            currency:      (string) Arr::get($response, 'currency', 'KWD'),
            rawResponse:   $response,
        );
    }

    public function handleWebhook(array $payload, array $headers = []): WebhookPayload { return $this->webhook->handle($payload, $headers); }
    public function verifyWebhook(array $payload, array $headers = []): bool { return $this->webhook->verify($payload, $headers); }

    public function payments(): PayzahPayment { return $this->payment; }
}
