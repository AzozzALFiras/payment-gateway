<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\MiddleEast\Tap;

use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\PaymentResponse;
use AzozzALFiras\PaymentGateway\Http\HttpClient;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Tap Charges Sub-module.
 *
 * Handles charge creation, retrieval, update, and listing.
 *
 * @link https://developers.tap.company/reference/charges
 */
class TapCharge
{
    public function __construct(
        protected HttpClient $http,
        protected string $baseUrl
    ) {}

    /**
     * Create a Charge.
     *
     * @link https://developers.tap.company/reference/create-a-charge
     */
    public function create(PaymentRequest $request): PaymentResponse
    {
        $payload = [
            'amount'   => $request->amount,
            'currency' => $request->currency,
            'redirect' => [
                'url' => $request->callbackUrl ?: $request->returnUrl,
            ],
            'post' => [
                'url' => $request->callbackUrl,
            ],
        ];

        if ($request->customer !== null) {
            $payload['customer'] = [
                'first_name' => $request->customer->name,
                'email'      => $request->customer->email,
                'phone'      => [
                    'number'       => $request->customer->phone,
                    'country_code' => $request->customer->country ?: '965',
                ],
            ];
        }

        if ($request->orderId !== '') {
            $payload['reference'] = ['order' => $request->orderId];
        }

        if ($request->description !== '') {
            $payload['description'] = $request->description;
        }

        $payload['source'] = ['id' => $request->recurringToken ?? 'src_all'];

        if (! empty($request->metadata)) {
            $payload['metadata'] = $request->metadata;
        }

        $response = $this->http->post($this->baseUrl . '/charges', $payload);
        $status = (string) Arr::get($response, 'status', '');

        return new PaymentResponse(
            success:       in_array($status, ['CAPTURED', 'AUTHORIZED', 'INITIATED'], true),
            transactionId: (string) Arr::get($response, 'id', ''),
            status:        $status,
            message:       (string) Arr::get($response, 'response.message', ''),
            amount:        (float) Arr::get($response, 'amount', $request->amount),
            currency:      (string) Arr::get($response, 'currency', $request->currency),
            paymentUrl:    (string) Arr::get($response, 'transaction.url', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Retrieve a Charge.
     *
     * @link https://developers.tap.company/reference/retrieve-a-charges
     */
    public function retrieve(string $chargeId): PaymentResponse
    {
        $response = $this->http->get($this->baseUrl . "/charges/{$chargeId}");
        $status = (string) Arr::get($response, 'status', '');

        return new PaymentResponse(
            success:       $status === 'CAPTURED',
            transactionId: (string) Arr::get($response, 'id', $chargeId),
            status:        $status,
            message:       (string) Arr::get($response, 'response.message', ''),
            amount:        (float) Arr::get($response, 'amount', 0),
            currency:      (string) Arr::get($response, 'currency', ''),
            rawResponse:   $response,
        );
    }

    /**
     * Update a Charge.
     *
     * @param string               $chargeId
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function update(string $chargeId, array $data): array
    {
        return $this->http->put($this->baseUrl . "/charges/{$chargeId}", $data);
    }

    /**
     * List all Charges.
     *
     * @param array<string, mixed> $filters
     * @return array<string, mixed>
     */
    public function list(array $filters = []): array
    {
        return $this->http->post($this->baseUrl . '/charges/list', $filters);
    }
}
