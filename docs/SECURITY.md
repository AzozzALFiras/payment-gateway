# Security Architecture

> **Back to [README](../README.md)**

## Signature Verification

All webhook signatures use the centralized `SignatureVerifier` class, which provides **timing-safe comparison** via `hash_equals()` to prevent timing attacks:

```php
use AzozzALFiras\PaymentGateway\Security\SignatureVerifier;

// HMAC-SHA256 — used by Tap, ClickPay, Thawani, Fatora, Payzaty, GoCardless, NeonPay
SignatureVerifier::verifyHmacSha256($payload, $signature, $secret);

// HMAC-SHA512
SignatureVerifier::verifyHmacSha512($payload, $signature, $secret);

// Bearer Token — used by Tamara
SignatureVerifier::verifyBearerToken($authHeader, $expectedToken);

// MD5 Hash — used by EdfaPay
SignatureVerifier::verifyMd5($computed, $received);

// Extract signature from headers (case-insensitive)
SignatureVerifier::extractSignature($headers, ['Tap-Signature', 'tap-signature']);
```

## Input Sanitization

```php
use AzozzALFiras\PaymentGateway\Security\PayloadSanitizer;

PayloadSanitizer::string($input);          // Strip HTML/JS, trim
PayloadSanitizer::email($input);           // Sanitized email
PayloadSanitizer::phone($input);           // Digits, +, spaces only
PayloadSanitizer::url($input);             // HTTPS validation
PayloadSanitizer::amount($input);          // Positive float, max 3 decimals
PayloadSanitizer::ip($input);              // Valid IPv4/IPv6 only
PayloadSanitizer::maskCardNumber($card);   // 512345****2346
PayloadSanitizer::metadata($array);        // Recursive XSS clean
```

## Gateway-Specific Signing

| Gateway | Method |
|---------|--------|
| **Redsys** | 3DES key diversification + HMAC-SHA256 |
| **ZainCash** | JWT HS256 (manual implementation, no external library) |
| **AsiaPay** | JWT token + HMAC-SHA256 payload signing |
| **PayPal** | OAuth 2.0 + CRC32-based webhook verification |
| **Mollie** | No signature — webhook sends only payment ID, you fetch status via API |

## Best Practices

1. **Never log raw card numbers** — use `PayloadSanitizer::maskCardNumber()`
2. **Always verify webhooks** — call `$gateway->verifyWebhook()` before processing
3. **Use HTTPS** for all callback/return URLs
4. **Store API keys** in environment variables, never in source code
5. **Rotate keys** immediately if any key may have been compromised
6. **IP Whitelist** gateway webhook IPs when the gateway supports it
