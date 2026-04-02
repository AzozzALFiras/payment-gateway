<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\AsiaPay;

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
 * AsiaPay Iraq Payment Gateway Driver.
 *
 * Sub-modules:
 *  - AsiaPayAuth  — JWT token authentication
 *  - AsiaPayOrder — Pre-order, query, refund
 *  - AsiaPayWebhook — Webhook handling
 *
 * @link https://www.asiapay.iq/integration
 */
class AsiaPayGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const LIVE_URL    = 'https://api.asiapay.iq/apiaccess';
    private const SANDBOX_URL = 'https://apitest.asiapay.iq:5443/apiaccess';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected AsiaPayAuth $auth;
    protected AsiaPayOrder $order;
    protected AsiaPayWebhook $webhook;

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

        $this->auth = new AsiaPayAuth(
            $this->http,
            $baseUrl,
            (string) $config->require('app_key'),
            (string) $config->require('app_secret'),
            (string) $config->require('private_key')
        );

        $this->order   = new AsiaPayOrder($this->http, $baseUrl, $this->auth);
        $this->webhook = new AsiaPayWebhook($config);
    }

    public function getName(): string { return 'AsiaPay'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'appid'            => (string) $this->config->require('app_id'),
            'business_type'    => 'BuyGoods',
            'merch_code'       => (string) $this->config->require('merch_code'),
            'merch_order_id'   => $request->orderId ?: ('ORD-' . uniqid()),
            'redirect_url'     => $request->returnUrl ?: $request->callbackUrl,
            'notify_url'       => $request->callbackUrl,
            'timeout_express'  => '30m',
            'title'            => $request->description ?: 'Payment',
            'total_amount'     => number_format($request->amount, 3, '.', ''),
            'trade_type'       => 'Checkout',
            'trans_currency'   => strtoupper($request->currency),
            'sign_type'        => 'JWTSecret',
        ];

        if (! empty($request->items)) {
            $payload['wallet_reference_data'] = ['items' => $request->items];
        }

        $response = $this->order->preOrder($payload);
        $bizContent = (array) Arr::get($response, 'biz_content', []);
        $redirectUrl = (string) Arr::get($bizContent, 'redirect_url', '');
        $result = (string) Arr::get($response, 'result', '');

        return new PaymentResponse(
            success:       $result === 'SUCCESS' && ! empty($redirectUrl),
            transactionId: (string) Arr::get($bizContent, 'merch_order_id', $payload['merch_order_id']),
            status:        $result === 'SUCCESS' ? 'pending' : 'failed',
            message:       (string) Arr::get($response, 'msg', ''),
            amount:        $request->amount,
            currency:      strtoupper($request->currency),
            paymentUrl:    $redirectUrl,
            sessionId:     Arr::get($bizContent, 'prepay_id') ? (string) Arr::get($bizContent, 'prepay_id') : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $payload = [
            'appid'          => (string) $this->config->require('app_id'),
            'merch_code'     => (string) $this->config->require('merch_code'),
            'merch_order_id' => $paymentId,
        ];

        $response = $this->order->queryOrder($payload);
        $bizContent = (array) Arr::get($response, 'biz_content', []);
        $orderStatus = (string) Arr::get($bizContent, 'order_status', '');

        return new PaymentResponse(
            success:       $orderStatus === 'PAY_SUCCESS',
            transactionId: (string) Arr::get($bizContent, 'trans_id', $paymentId),
            status:        $orderStatus,
            amount:        (float) Arr::get($bizContent, 'total_amount', 0),
            currency:      (string) Arr::get($bizContent, 'trans_currency', 'IQD'),
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $payload = [
            'appid'             => (string) $this->config->require('app_id'),
            'merch_code'        => (string) $this->config->require('merch_code'),
            'merch_order_id'    => $request->transactionId,
            'refund_request_no' => 'REF-' . uniqid(),
            'refund_reason'     => $request->reason ?: 'Refund requested',
        ];

        $response = $this->order->refund($payload);
        $bizContent = (array) Arr::get($response, 'biz_content', []);
        $refundStatus = (string) Arr::get($bizContent, 'refund_status', '');

        return new RefundResponse(
            success:       $refundStatus === 'REFUND_SUCCESS',
            refundId:      (string) Arr::get($bizContent, 'refund_request_no', $payload['refund_request_no']),
            transactionId: $request->transactionId,
            status:        $refundStatus,
            message:       (string) Arr::get($response, 'msg', ''),
            amount:        (float) Arr::get($bizContent, 'refund_amount', $request->amount),
            currency:      strtoupper($request->currency),
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

    public function orders(): AsiaPayOrder { return $this->order; }
}
