<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\EdfaPay;

use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\DTOs\RefundRequest;
use AzozzALFiras\PaymentGateway\DTOs\RefundResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * EdfaPay Hosted Checkout Integration.
 *
 * Checkout flow: Customer is redirected to EdfaPay's hosted checkout page.
 *
 * Supported operations:
 *  - Initiate (create checkout session)
 *  - Status
 *  - Recurring
 *  - Full Refund
 *  - Partial Refund
 *
 * @link https://edfapay-payment-gateway-api.readme.io/reference/hosted-checkout-integration
 */
class EdfaPayCheckout
{
    public function __construct(
        private readonly HttpClient $http,
        private readonly string $baseUrl,
        private readonly string $clientKey,
        private readonly string $password,
    ) {
    }

    /**
     * Initiate a checkout payment session.
     *
     * @param float                $amount
     * @param string               $currency
     * @param string               $orderId
     * @param string               $orderDescription
     * @param string               $payerEmail
     * @param string               $successUrl
     * @param string               $failUrl
     * @param array<string, mixed> $extra
     * @return PaymentResponse
     */
    public function initiate(
        float $amount,
        string $currency,
        string $orderId,
        string $orderDescription,
        string $payerEmail,
        string $successUrl,
        string $failUrl,
        array $extra = []
    ): PaymentResponse {
        $payload = array_merge([
            'action'            => 'SALE',
            'client_key'        => $this->clientKey,
            'order_id'          => $orderId,
            'order_amount'      => number_format($amount, 2, '.', ''),
            'order_currency'    => $currency,
            'order_description' => $orderDescription,
            'payer_email'       => $payerEmail,
            'success_url'       => $successUrl,
            'fail_url'          => $failUrl,
        ], $extra);

        $response = $this->http->postForm($this->baseUrl . '/payment/initiate', $payload);

        $redirectUrl = (string) Arr::get($response, 'redirect_url', '');
        $result = (string) Arr::get($response, 'result', '');

        return new PaymentResponse(
            success:     strtoupper($result) === 'SUCCESS' || ! empty($redirectUrl),
            transactionId: (string) Arr::get($response, 'trans_id', $orderId),
            status:      $result ?: 'initiated',
            message:     (string) Arr::get($response, 'decline_reason', ''),
            amount:      $amount,
            currency:    $currency,
            paymentUrl:  $redirectUrl,
            rawResponse: $response,
        );
    }

    /**
     * Get the status of a checkout transaction.
     *
     * @param string               $transactionId
     * @param string               $orderId
     * @param string               $payerEmail
     * @param string               $cardNumber  Full card number for hash
     * @param array<string, mixed> $extra
     * @return PaymentResponse
     */
    public function status(
        string $transactionId,
        string $orderId,
        string $payerEmail,
        string $cardNumber = '',
        array $extra = []
    ): PaymentResponse {
        $payload = array_merge([
            'action'     => 'GET_TRANS_STATUS',
            'client_key' => $this->clientKey,
            'trans_id'   => $transactionId,
            'order_id'   => $orderId,
        ], $extra);

        if ($cardNumber !== '' && $payerEmail !== '') {
            $payload['hash'] = EdfaPayHash::generate($payerEmail, $cardNumber, $this->password);
        }

        $response = $this->http->postForm($this->baseUrl . '/payment/post', $payload);
        $result = (string) Arr::get($response, 'result', '');

        return new PaymentResponse(
            success:       strtoupper($result) === 'SUCCESS',
            transactionId: (string) Arr::get($response, 'trans_id', $transactionId),
            status:        (string) Arr::get($response, 'status', $result),
            message:       (string) Arr::get($response, 'decline_reason', ''),
            amount:        (float) Arr::get($response, 'order_amount', 0),
            currency:      (string) Arr::get($response, 'order_currency', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Process a recurring payment via checkout.
     *
     * @param string               $recurringToken
     * @param float                $amount
     * @param string               $currency
     * @param string               $orderId
     * @param string               $orderDescription
     * @param array<string, mixed> $extra
     * @return PaymentResponse
     */
    public function recurring(
        string $recurringToken,
        float $amount,
        string $currency,
        string $orderId,
        string $orderDescription,
        array $extra = []
    ): PaymentResponse {
        $payload = array_merge([
            'action'               => 'RECURRING_SALE',
            'client_key'           => $this->clientKey,
            'order_id'             => $orderId,
            'order_amount'         => number_format($amount, 2, '.', ''),
            'order_currency'       => $currency,
            'order_description'    => $orderDescription,
            'recurring_first_trans_id' => $recurringToken,
        ], $extra);

        $response = $this->http->postForm($this->baseUrl . '/payment/post', $payload);
        $result = (string) Arr::get($response, 'result', '');

        return new PaymentResponse(
            success:       strtoupper($result) === 'SUCCESS',
            transactionId: (string) Arr::get($response, 'trans_id', ''),
            status:        $result,
            message:       (string) Arr::get($response, 'decline_reason', ''),
            amount:        $amount,
            currency:      $currency,
            rawResponse:   $response,
        );
    }

    /**
     * Process a full refund.
     */
    public function fullRefund(RefundRequest $request): RefundResponse
    {
        return $this->processRefund($request, 'FULL');
    }

    /**
     * Process a partial refund.
     */
    public function partialRefund(RefundRequest $request): RefundResponse
    {
        return $this->processRefund($request, 'PARTIAL');
    }

    /**
     * Process a refund (full or partial).
     */
    private function processRefund(RefundRequest $request, string $type): RefundResponse
    {
        $payload = array_merge([
            'action'     => $type === 'FULL' ? 'CREDITVOID' : 'CREDITVOID',
            'client_key' => $this->clientKey,
            'trans_id'   => $request->transactionId,
            'amount'     => number_format($request->amount, 2, '.', ''),
        ], $request->metadata);

        $response = $this->http->postForm($this->baseUrl . '/payment/post', $payload);
        $result = (string) Arr::get($response, 'result', '');

        return new RefundResponse(
            success:       strtoupper($result) === 'SUCCESS',
            refundId:      (string) Arr::get($response, 'trans_id', ''),
            transactionId: $request->transactionId,
            status:        $result,
            message:       (string) Arr::get($response, 'decline_reason', ''),
            amount:        $request->amount,
            currency:      $request->currency,
            rawResponse:   $response,
        );
    }
}
