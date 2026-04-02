<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\GoCardless;

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
 * GoCardless Payment Gateway Driver.
 *
 * UK's leading Direct Debit payment platform.
 *
 * Sub-modules:
 *  - GoCardlessPayment — Payments API
 *  - GoCardlessMandate — Direct Debit mandates
 *  - GoCardlessRefund  — Refunds API
 *  - GoCardlessWebhook — Webhook handling
 *
 * @link https://developer.gocardless.com/api-reference/
 */
class GoCardlessGateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    private const LIVE_URL    = 'https://api.gocardless.com';
    private const SANDBOX_URL = 'https://api-sandbox.gocardless.com';

    protected GatewayConfig $config;
    protected HttpClient $http;
    protected GoCardlessPayment $paymentModule;
    protected GoCardlessMandate $mandateModule;
    protected GoCardlessRefund $refundModule;
    protected GoCardlessWebhook $webhook;

    public function __construct(GatewayConfig $config)
    {
        $this->config = $config;
        $baseUrl = $config->isTestMode() ? self::SANDBOX_URL : self::LIVE_URL;
        $accessToken = (string) $config->require('access_token');

        $this->http = new HttpClient(
            timeout: $config->getTimeout(),
            defaultHeaders: [
                'Authorization'    => "Bearer {$accessToken}",
                'GoCardless-Version' => '2015-07-06',
                'Content-Type'     => 'application/json',
                'Accept'           => 'application/json',
            ]
        );

        $this->paymentModule = new GoCardlessPayment($this->http, $baseUrl);
        $this->mandateModule = new GoCardlessMandate($this->http, $baseUrl);
        $this->refundModule  = new GoCardlessRefund($this->http, $baseUrl);
        $this->webhook       = new GoCardlessWebhook($config);
    }

    public function getName(): string { return 'GoCardless'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }

    public function purchase(PaymentRequest $request): PaymentResponse
    {
        $mandateId = $request->metadata['mandate_id']
            ?? (string) $this->config->get('mandate_id', '');

        if ($mandateId === '') {
            return new PaymentResponse(
                success: false,
                status: 'failed',
                message: 'GoCardless requires a mandate_id in metadata or config',
                rawResponse: [],
            );
        }

        $amountInPence = (int) round($request->amount * 100);

        $payload = [
            'amount'   => $amountInPence,
            'currency' => strtoupper($request->currency),
            'links'    => ['mandate' => $mandateId],
        ];

        if ($request->description !== '') {
            $payload['description'] = $request->description;
        }

        if ($request->orderId !== '') {
            $payload['metadata'] = ['order_id' => $request->orderId];
        }

        $chargeDate = $request->metadata['charge_date'] ?? null;
        if ($chargeDate !== null) {
            $payload['charge_date'] = $chargeDate;
        }

        $response = $this->paymentModule->create($payload);
        $payment = (array) Arr::get($response, 'payments', $response);
        $paymentId = (string) Arr::get($payment, 'id', '');
        $status = (string) Arr::get($payment, 'status', '');

        return new PaymentResponse(
            success:       ! empty($paymentId),
            transactionId: $paymentId,
            status:        $status,
            message:       '',
            amount:        $request->amount,
            currency:      strtoupper($request->currency),
            rawResponse:   $response,
        );
    }

    public function status(string $paymentId): PaymentResponse
    {
        $response = $this->paymentModule->retrieve($paymentId);
        $payment = (array) Arr::get($response, 'payments', $response);
        $status = (string) Arr::get($payment, 'status', '');
        $amountInPence = (int) Arr::get($payment, 'amount', 0);

        return new PaymentResponse(
            success:       in_array($status, ['confirmed', 'paid_out'], true),
            transactionId: (string) Arr::get($payment, 'id', $paymentId),
            status:        $status,
            amount:        $amountInPence / 100,
            currency:      strtoupper((string) Arr::get($payment, 'currency', '')),
            rawResponse:   $response,
        );
    }

    public function refund(RefundRequest $request): RefundResponse
    {
        $amountInPence = (int) round($request->amount * 100);

        $payload = [
            'amount' => $amountInPence,
            'links'  => ['payment' => $request->transactionId],
        ];

        if ($request->reason !== '') {
            $payload['metadata'] = ['reason' => $request->reason];
        }

        $response = $this->refundModule->create($payload);
        $refund = (array) Arr::get($response, 'refunds', $response);

        return new RefundResponse(
            success:       ! empty(Arr::get($refund, 'id')),
            refundId:      (string) Arr::get($refund, 'id', ''),
            transactionId: $request->transactionId,
            status:        'created',
            message:       '',
            amount:        ((int) Arr::get($refund, 'amount', 0)) / 100,
            currency:      strtoupper((string) Arr::get($refund, 'currency', '')),
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

    public function payments(): GoCardlessPayment { return $this->paymentModule; }
    public function mandates(): GoCardlessMandate { return $this->mandateModule; }
    public function refunds(): GoCardlessRefund { return $this->refundModule; }
}
