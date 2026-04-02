<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\ZainCash;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * ZainCash Payment Gateway Driver.
 *
 * Sub-modules:
 *  - ZainCashTransaction — Init and get transactions
 *  - ZainCashWebhook     — Callback handling
 *
 * Authentication: JWT (HS256) with merchant secret.
 * Currency: IQD only (minimum 250 IQD).
 *
 * @link https://docs.zaincash.iq
 */
class ZainCashGateway implements GatewayInterface, SupportsWebhook
{
    private const LIVE_URL    = 'https://api.zaincash.iq';
    private const SANDBOX_URL = 'https://test.zaincash.iq';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected ZainCashTransaction $transaction;
    protected ZainCashWebhook $webhook;
    protected string $baseUrl;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $this->baseUrl = $config->isTestMode() ? self::SANDBOX_URL : self::LIVE_URL;

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Content-Type' => 'application/x-www-form-urlencoded',
                'Accept'       => 'application/json',
            ]
        );

        $this->transaction = new ZainCashTransaction($this->http, $this->baseUrl);
        $this->webhook     = new ZainCashWebhook($config);
    }

    public function getName(): string { return 'ZainCash'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $secret = (string) $this->config->require('secret');
        $msisdn = (string) $this->config->require('msisdn');
        $merchantId = (string) $this->config->require('merchant_id');

        $now = time();
        $jwtPayload = [
            'amount'      => $request->amount,
            'serviceType' => $this->config->get('service_type', 'Other'),
            'msisdn'      => $msisdn,
            'orderId'     => $request->orderId ?: ('ORD-' . uniqid()),
            'redirectUrl'  => $request->returnUrl ?: $request->callbackUrl,
            'iat'         => $now,
            'exp'         => $now + 3600,
        ];

        $token = $this->encodeJwt($jwtPayload, $secret);

        $response = $this->transaction->init([
            'token'       => $token,
            'merchantId'  => $merchantId,
            'lang'        => $this->config->get('language', 'en'),
        ]);

        $transactionId = (string) Arr::get($response, 'id', '');
        $paymentUrl = '';

        if ($transactionId !== '') {
            $paymentUrl = $this->baseUrl . '/transaction/pay?id=' . $transactionId;
        }

        return new PaymentResponse(
            success:       ! empty($transactionId),
            transactionId: $transactionId,
            status:        ! empty($transactionId) ? 'pending' : 'failed',
            message:       (string) Arr::get($response, 'msg', ''),
            amount:        $request->amount,
            currency:      'IQD',
            paymentUrl:    $paymentUrl,
            sessionId:     $transactionId !== '' ? $transactionId : null,
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $secret = (string) $this->config->require('secret');
        $msisdn = (string) $this->config->require('msisdn');
        $merchantId = (string) $this->config->require('merchant_id');

        $now = time();
        $jwtPayload = [
            'id'     => $paymentId,
            'msisdn' => $msisdn,
            'iat'    => $now,
            'exp'    => $now + 3600,
        ];

        $token = $this->encodeJwt($jwtPayload, $secret);

        $response = $this->transaction->get([
            'token'      => $token,
            'merchantId' => $merchantId,
            'id'         => $paymentId,
        ]);

        $status = strtolower((string) Arr::get($response, 'status', ''));

        return new PaymentResponse(
            success:       $status === 'completed' || $status === 'success',
            transactionId: (string) Arr::get($response, 'id', $paymentId),
            status:        $status,
            amount:        (float) Arr::get($response, 'amount', 0),
            currency:      'IQD',
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

    public function transactions(): ZainCashTransaction { return $this->transaction; }

    /**
     * Encode payload as JWT (HS256).
     *
     * @param array<string, mixed> $payload
     */
    protected function encodeJwt(array $payload, string $secret): string
    {
        $header = $this->base64UrlEncode(json_encode(['alg' => 'HS256', 'typ' => 'JWT'], JSON_THROW_ON_ERROR));
        $body = $this->base64UrlEncode(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = $this->base64UrlEncode(hash_hmac('sha256', $header . '.' . $body, $secret, true));

        return $header . '.' . $body . '.' . $signature;
    }

    protected function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
