<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Security;

/**
 * Centralized signature verification utility.
 *
 * All webhook and callback signature validations go through this class
 * to ensure consistent, timing-safe comparison across all gateways.
 */
final class SignatureVerifier
{
    /**
     * Verify an HMAC-SHA256 signature.
     *
     * @param string $payload   The raw payload string
     * @param string $signature The received signature
     * @param string $secret    The shared secret key
     */
    public static function verifyHmacSha256(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $computed = hash_hmac('sha256', $payload, $secret);

        return hash_equals($computed, $signature);
    }

    /**
     * Verify an HMAC-SHA512 signature.
     */
    public static function verifyHmacSha512(string $payload, string $signature, string $secret): bool
    {
        if ($signature === '' || $secret === '') {
            return false;
        }

        $computed = hash_hmac('sha512', $payload, $secret);

        return hash_equals($computed, $signature);
    }

    /**
     * Verify an MD5-based hash (used by EdfaPay).
     */
    public static function verifyMd5(string $computedHash, string $receivedHash): bool
    {
        if ($computedHash === '' || $receivedHash === '') {
            return false;
        }

        return hash_equals(strtolower($computedHash), strtolower($receivedHash));
    }

    /**
     * Verify a bearer token (used by Tamara webhooks).
     */
    public static function verifyBearerToken(string $authHeader, string $expectedToken): bool
    {
        if ($authHeader === '' || $expectedToken === '') {
            return false;
        }

        $token = str_replace('Bearer ', '', $authHeader);

        return hash_equals($expectedToken, $token);
    }

    /**
     * Extract a signature from common header locations.
     *
     * @param array<string, string> $headers
     * @param array<int, string>    $headerNames  Header names to check (case-insensitive)
     */
    public static function extractSignature(array $headers, array $headerNames): string
    {
        foreach ($headerNames as $name) {
            $value = $headers[$name] ?? $headers[strtolower($name)] ?? $headers[strtoupper($name)] ?? '';

            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }
}
