<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Security;

/**
 * Input sanitization and validation for payment data.
 *
 * Ensures all data sent to payment gateways is clean and safe,
 * preventing XSS, injection attacks, and malformed data.
 */
final class PayloadSanitizer
{
    /**
     * Sanitize a string value — remove HTML/JS and trim.
     */
    public static function string(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return htmlspecialchars(
            strip_tags(trim((string) $value)),
            ENT_QUOTES | ENT_HTML5,
            'UTF-8'
        );
    }

    /**
     * Sanitize an email address.
     */
    public static function email(string $email): string
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        return $email !== false ? $email : '';
    }

    /**
     * Sanitize a phone number — digits, +, spaces only.
     */
    public static function phone(string $phone): string
    {
        return preg_replace('/[^\d+\s\-]/', '', trim($phone)) ?? '';
    }

    /**
     * Sanitize a monetary amount.
     */
    public static function amount(mixed $amount): float
    {
        $value = filter_var($amount, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        $floatVal = $value !== false ? (float) $value : 0.0;

        return max(0.0, round($floatVal, 3));
    }

    /**
     * Sanitize a URL.
     */
    public static function url(string $url): string
    {
        $url = filter_var(trim($url), FILTER_SANITIZE_URL);

        if ($url === false) {
            return '';
        }

        // Only allow HTTPS URLs in production
        return (str_starts_with($url, 'https://') || str_starts_with($url, 'http://'))
            ? $url
            : '';
    }

    /**
     * Sanitize an array of key-value strings.
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public static function metadata(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            $cleanKey = self::string($key);

            if ($cleanKey === '') {
                continue;
            }

            $clean[$cleanKey] = is_array($value)
                ? self::metadata($value)
                : self::string($value);
        }

        return $clean;
    }

    /**
     * Validate and sanitize an IP address.
     */
    public static function ip(string $ip): string
    {
        $ip = trim($ip);

        return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '';
    }

    /**
     * Mask a card number for safe logging (show first 6 and last 4).
     */
    public static function maskCardNumber(string $cardNumber): string
    {
        $clean = preg_replace('/\D/', '', $cardNumber) ?? '';

        if (strlen($clean) < 10) {
            return str_repeat('*', strlen($clean));
        }

        return substr($clean, 0, 6) . str_repeat('*', strlen($clean) - 10) . substr($clean, -4);
    }
}
