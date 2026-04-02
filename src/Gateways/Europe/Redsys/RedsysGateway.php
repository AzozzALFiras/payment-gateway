<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\Redsys;

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
 * Redsys Payment Gateway Driver.
 *
 * Spain's leading payment processor used by most Spanish banks.
 *
 * Sub-modules:
 *  - RedsysTransaction — Payment initiation and REST ops
 *  - RedsysWebhook     — Notification handling with 3DES + HMAC-SHA256
 *
 * @link https://redsys.es
 */
class RedsysGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const LIVE_URL    = 'https://sis.redsys.es/sis';
    private const SANDBOX_URL = 'https://sis-t.redsys.es:25443/sis';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected RedsysTransaction $transaction;
    protected RedsysWebhook $webhook;
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

        $this->transaction = new RedsysTransaction($this->http, $this->baseUrl);
        $this->webhook     = new RedsysWebhook($config);
    }

    public function getName(): string { return 'Redsys'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $merchantCode = (string) $this->config->require('merchant_code');
        $terminal = (string) $this->config->get('terminal', '1');
        $merchantKey = (string) $this->config->require('merchant_key');

        $amountInCents = (int) round($request->amount * 100);
        $orderId = $request->orderId ?: $this->generateOrderId();
        $currencyCode = $this->currencyToCode($request->currency);

        $merchantParams = [
            'Ds_Merchant_Amount'             => (string) $amountInCents,
            'Ds_Merchant_Order'              => $orderId,
            'Ds_Merchant_MerchantCode'       => $merchantCode,
            'Ds_Merchant_Currency'           => $currencyCode,
            'Ds_Merchant_TransactionType'    => '0', // Authorization
            'Ds_Merchant_Terminal'           => $terminal,
            'Ds_Merchant_MerchantURL'        => $request->callbackUrl,
            'Ds_Merchant_UrlOK'              => $request->returnUrl ?: $request->callbackUrl,
            'Ds_Merchant_UrlKO'              => $request->cancelUrl ?: $request->callbackUrl,
            'Ds_Merchant_ProductDescription' => $request->description ?: 'Payment',
        ];

        $encodedParams = base64_encode(json_encode($merchantParams, JSON_THROW_ON_ERROR));

        // Diversify key with 3DES
        $key = base64_decode($merchantKey);
        $diversifiedKey = $this->encrypt3Des($orderId, $key);

        // HMAC-SHA256 signature
        $signature = base64_encode(hash_hmac('sha256', $encodedParams, $diversifiedKey, true));

        // Build the redirect URL for the payment form
        $redirectUrl = $this->baseUrl . '/realizarPago';
        $formParams = [
            'Ds_SignatureVersion'    => 'HMAC_SHA256_V1',
            'Ds_MerchantParameters'  => $encodedParams,
            'Ds_Signature'           => $signature,
        ];

        $paymentUrl = $redirectUrl . '?' . http_build_query($formParams);

        return new PaymentResponse(
            success:       true,
            transactionId: $orderId,
            status:        'pending',
            message:       '',
            amount:        $request->amount,
            currency:      strtoupper($request->currency),
            paymentUrl:    $paymentUrl,
            sessionId:     $orderId,
            rawResponse:   $formParams,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        // Redsys doesn't have a status query API; status comes via webhook
        return new PaymentResponse(
            success:       false,
            transactionId: $paymentId,
            status:        'unknown',
            message:       'Redsys requires webhook notifications for status updates',
            rawResponse:   [],
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $merchantCode = (string) $this->config->require('merchant_code');
        $terminal = (string) $this->config->get('terminal', '1');
        $merchantKey = (string) $this->config->require('merchant_key');

        $amountInCents = (int) round($request->amount * 100);
        $currencyCode = $this->currencyToCode($request->currency);

        $merchantParams = [
            'Ds_Merchant_Amount'          => (string) $amountInCents,
            'Ds_Merchant_Order'           => $request->transactionId,
            'Ds_Merchant_MerchantCode'    => $merchantCode,
            'Ds_Merchant_Currency'        => $currencyCode,
            'Ds_Merchant_TransactionType' => '3', // Refund
            'Ds_Merchant_Terminal'        => $terminal,
        ];

        $encodedParams = base64_encode(json_encode($merchantParams, JSON_THROW_ON_ERROR));

        $key = base64_decode($merchantKey);
        $diversifiedKey = $this->encrypt3Des($request->transactionId, $key);
        $signature = base64_encode(hash_hmac('sha256', $encodedParams, $diversifiedKey, true));

        $payload = [
            'Ds_SignatureVersion'   => 'HMAC_SHA256_V1',
            'Ds_MerchantParameters' => $encodedParams,
            'Ds_Signature'          => $signature,
        ];

        $response = $this->transaction->restRequest($payload);
        $responseCode = (int) Arr::get($response, 'Ds_Response', Arr::get($response, 'errorCode', 9999));

        return new RefundResponse(
            success:       $responseCode >= 0 && $responseCode <= 99,
            refundId:      (string) Arr::get($response, 'Ds_AuthorisationCode', ''),
            transactionId: $request->transactionId,
            status:        ($responseCode >= 0 && $responseCode <= 99) ? 'refunded' : 'failed',
            message:       'Response code: ' . $responseCode,
            amount:        $request->amount,
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

    public function transactions(): RedsysTransaction { return $this->transaction; }

    protected function encrypt3Des(string $data, string $key): string
    {
        $padLength = 8 - (strlen($data) % 8);
        $data .= str_repeat("\0", $padLength);

        $result = openssl_encrypt($data, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, "\0\0\0\0\0\0\0\0");

        return $result !== false ? $result : '';
    }

    protected function generateOrderId(): string
    {
        return date('ymd') . str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
    }

    protected function currencyToCode(string $currency): string
    {
        return match (strtoupper($currency)) {
            'EUR' => '978',
            'USD' => '840',
            'GBP' => '826',
            default => '978',
        };
    }
}
