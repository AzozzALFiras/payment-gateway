<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\Europe\Redsys;

use AzozzALFiras\PaymentGateway\Config\GatewayConfig;
use AzozzALFiras\PaymentGateway\DTOs\WebhookPayload;
use AzozzALFiras\PaymentGateway\Support\Arr;

/**
 * Redsys Webhook/Notification Handler.
 *
 * Verifies Ds_Signature using HMAC-SHA256 with merchant key.
 *
 * @link https://redsys.es
 */
class RedsysWebhook
{
    public function __construct(protected GatewayConfig $config) {}

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function handle(array $payload, array $headers = []): WebhookPayload
    {
        $merchantParams = (string) Arr::get($payload, 'Ds_MerchantParameters', '');
        $decoded = $this->decodeMerchantParameters($merchantParams);

        $responseCode = (int) Arr::get($decoded, 'Ds_Response', 9999);
        $status = match (true) {
            $responseCode >= 0 && $responseCode <= 99 => 'paid',
            $responseCode === 900                      => 'refunded',
            $responseCode === 400                      => 'cancelled',
            $responseCode === 101                      => 'expired',
            default                                    => 'failed',
        };

        $amount = (float) Arr::get($decoded, 'Ds_Amount', 0) / 100;

        return new WebhookPayload(
            isValid:       $this->verify($payload, $headers),
            event:         'payment.' . $status,
            transactionId: (string) Arr::get($decoded, 'Ds_AuthorisationCode', ''),
            status:        $status,
            amount:        $amount,
            currency:      $this->currencyFromCode((string) Arr::get($decoded, 'Ds_Currency', '978')),
            orderId:       (string) Arr::get($decoded, 'Ds_Order', ''),
            message:       'Response code: ' . $responseCode,
            rawPayload:    $decoded,
        );
    }

    /**
     * @param array<string, mixed>  $payload
     * @param array<string, string> $headers
     */
    public function verify(array $payload, array $headers = []): bool
    {
        $merchantKey = $this->config->get('merchant_key');
        if (empty($merchantKey)) {
            return true;
        }

        $merchantParams = (string) Arr::get($payload, 'Ds_MerchantParameters', '');
        $signature = (string) Arr::get($payload, 'Ds_Signature', '');

        if ($merchantParams === '' || $signature === '') {
            return false;
        }

        $decoded = $this->decodeMerchantParameters($merchantParams);
        $order = (string) Arr::get($decoded, 'Ds_Order', '');

        // Diversify the key using 3DES with the order number
        $key = base64_decode((string) $merchantKey);
        $diversifiedKey = $this->encrypt3Des($order, $key);

        // Calculate HMAC-SHA256
        $expectedSig = base64_encode(hash_hmac('sha256', $merchantParams, $diversifiedKey, true));

        // Redsys uses URL-safe base64
        $expectedSigSafe = strtr($expectedSig, '+/', '-_');

        return hash_equals($expectedSigSafe, $signature) || hash_equals($expectedSig, $signature);
    }

    /**
     * Decode Base64-encoded merchant parameters.
     *
     * @return array<string, mixed>
     */
    public function decodeMerchantParameters(string $encoded): array
    {
        $decoded = base64_decode(strtr($encoded, '-_', '+/'));

        return (array) json_decode($decoded ?: '{}', true);
    }

    /**
     * 3DES encryption for key diversification.
     */
    protected function encrypt3Des(string $data, string $key): string
    {
        // Pad data to 8-byte blocks
        $padLength = 8 - (strlen($data) % 8);
        $data .= str_repeat("\0", $padLength);

        $result = openssl_encrypt($data, 'des-ede3-cbc', $key, OPENSSL_RAW_DATA | OPENSSL_ZERO_PADDING, "\0\0\0\0\0\0\0\0");

        return $result !== false ? $result : '';
    }

    protected function currencyFromCode(string $code): string
    {
        return match ($code) {
            '978' => 'EUR',
            '840' => 'USD',
            '826' => 'GBP',
            default => $code,
        };
    }
}
