<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\ClickPay;

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
 * ClickPay Payment Gateway Driver.
 *
 * Sub-modules:
 *  - ClickPayTransaction — Sale/Auth/Capture/Void/Query
 *  - ClickPayWebhook     — Webhook handling
 *
 * @link https://support.clickpay.com.sa
 */
class ClickPayGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const REGIONS = [
        'SAU'    => 'https://secure.clickpay.com.sa',
        'ARE'    => 'https://secure.clickpay.com.sa',
        'EGY'    => 'https://secure-egypt.clickpay.com.sa',
        'OMN'    => 'https://secure-oman.clickpay.com.sa',
        'JOR'    => 'https://secure-jordan.clickpay.com.sa',
        'global' => 'https://secure-global.clickpay.com.sa',
    ];

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected ClickPayTransaction $transaction;
    protected ClickPayWebhook $webhook;
    protected int $profileId;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;

        $serverKey = (string) $config->require('server_key');
        $this->profileId = (int) $config->require('profile_id');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Authorization' => $serverKey,
                'Content-Type'  => 'application/json',
            ]
        );

        $baseUrl = $this->getBaseUrl();
        $this->transaction = new ClickPayTransaction($this->http, $baseUrl, $this->profileId);
        $this->webhook     = new ClickPayWebhook($config);
    }

    public function getName(): string { return 'ClickPay'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function getBaseUrl(): string
    {
        $region = strtoupper((string) $this->config->get('region', 'SAU'));
        return self::REGIONS[$region] ?? self::REGIONS['SAU'];
    }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $params = [
            'cart_id'          => $request->orderId ?: ('CART-' . uniqid()),
            'cart_amount'      => $request->amount,
            'cart_currency'    => $request->currency,
            'cart_description' => $request->description ?: 'Payment',
            'callback'         => $request->callbackUrl,
            'return'           => $request->returnUrl ?: $request->callbackUrl,
        ];

        if ($request->customer !== null) {
            $params['customer_details'] = [
                'name'    => $request->customer->name,
                'email'   => $request->customer->email,
                'phone'   => $request->customer->phone,
                'street1' => $request->customer->address,
                'city'    => $request->customer->city,
                'country' => $request->customer->country ?: 'SA',
            ];
        }

        if ($request->recurringToken !== null) {
            $params['token']      = $request->recurringToken;
            $params['tran_class'] = 'recurring';
        }

        $params = array_merge($params, $request->metadata);

        return $this->transaction->sale($params);
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->transaction->query($paymentId);
        $result = (string) Arr::get($response, 'payment_result.response_status', '');

        return new PaymentResponse(
            success:       strtoupper($result) === 'A',
            transactionId: (string) Arr::get($response, 'tran_ref', $paymentId),
            status:        $result,
            message:       (string) Arr::get($response, 'payment_result.response_message', ''),
            amount:        (float) Arr::get($response, 'cart_amount', 0),
            currency:      (string) Arr::get($response, 'cart_currency', ''),
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $result = $this->transaction->sale([
            'tran_type'        => 'refund',
            'tran_ref'         => $request->transactionId,
            'cart_id'          => 'REFUND-' . uniqid(),
            'cart_amount'      => $request->amount,
            'cart_currency'    => $request->currency,
            'cart_description' => $request->reason ?: 'Refund',
        ]);

        return new RefundResponse(
            success:       $result->success,
            refundId:      $result->transactionId,
            transactionId: $request->transactionId,
            status:        $result->status,
            message:       $result->message,
            amount:        $request->amount,
            currency:      $request->currency,
            rawResponse:   $result->rawResponse,
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

    public function transactions(): ClickPayTransaction { return $this->transaction; }
}
