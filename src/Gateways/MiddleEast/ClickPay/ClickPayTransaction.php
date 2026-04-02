<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\ClickPay;

use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * ClickPay Transaction Sub-module.
 *
 * Handles Sale, Auth, Capture, Void via the unified payment/request endpoint.
 */
class ClickPayTransaction
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl,
        protected int $profileId
    ) {}

    /**
     * Create a hosted payment page (sale).
     *
     * @param array<string, mixed> $params
     */
    public function sale(array $params): PaymentResponse
    {
        $params['profile_id'] = $this->profileId;
        $params['tran_type']  = $params['tran_type'] ?? 'sale';
        $params['tran_class'] = $params['tran_class'] ?? 'ecom';

        $response = $this->http->post($this->baseUrl . '/payment/request', $params);

        return new PaymentResponse(
            success:       ! empty(Arr::get($response, 'redirect_url')) || ! empty(Arr::get($response, 'tran_ref')),
            transactionId: (string) Arr::get($response, 'tran_ref', ''),
            status:        (string) Arr::get($response, 'payment_result.response_status', 'pending'),
            message:       (string) Arr::get($response, 'payment_result.response_message', ''),
            amount:        (float) Arr::get($response, 'cart_amount', 0),
            currency:      (string) Arr::get($response, 'cart_currency', ''),
            paymentUrl:    (string) Arr::get($response, 'redirect_url', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Create an authorization hold.
     *
     * @param array<string, mixed> $params
     */
    public function authorize(array $params): PaymentResponse
    {
        $params['tran_type'] = 'auth';
        return $this->sale($params);
    }

    /**
     * Capture a previously authorized transaction.
     */
    public function capture(string $transactionRef, float $amount, string $currency = 'SAR', string $description = 'Capture'): PaymentResponse
    {
        return $this->sale([
            'tran_type'        => 'capture',
            'tran_ref'         => $transactionRef,
            'cart_id'          => 'CAPTURE-' . uniqid(),
            'cart_amount'      => $amount,
            'cart_currency'    => $currency,
            'cart_description' => $description,
        ]);
    }

    /**
     * Void a transaction.
     */
    public function void(string $transactionRef, float $amount, string $currency = 'SAR', string $description = 'Void'): PaymentResponse
    {
        return $this->sale([
            'tran_type'        => 'void',
            'tran_ref'         => $transactionRef,
            'cart_id'          => 'VOID-' . uniqid(),
            'cart_amount'      => $amount,
            'cart_currency'    => $currency,
            'cart_description' => $description,
        ]);
    }

    /**
     * Query transaction status.
     *
     * @return array<string, mixed>
     */
    public function query(string $transactionRef): array
    {
        return $this->http->post($this->baseUrl . '/payment/query', [
            'profile_id' => $this->profileId,
            'tran_ref'   => $transactionRef,
        ]);
    }
}
