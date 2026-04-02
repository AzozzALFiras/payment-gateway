# Webhook Handling

> **Back to [README](../README.md)**

All 18 gateways support webhook processing through a unified interface:

```php
use AzozzALFiras\PaymentGateway\Enums\PaymentStatus;

// In your webhook endpoint controller (e.g. POST /webhook/tap)
$payload = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();

// 1. Verify signature + parse payload
$webhook = $gateway->handleWebhook($payload, $headers);

// 2. Check validity
if (!$webhook->isValid) {
    http_response_code(401);
    exit('Invalid signature');
}

// 3. Use unified PaymentStatus enum
$status = PaymentStatus::normalize($webhook->status);

echo "Transaction: {$webhook->transactionId}\n";
echo "Order: {$webhook->orderId}\n";
echo "Amount: {$webhook->amount} {$webhook->currency}\n";
echo "Status: {$status->value}\n";
echo "Successful: " . ($status->isSuccessful() ? 'yes' : 'no') . "\n";
echo "Final: " . ($status->isFinal() ? 'yes' : 'no') . "\n";
```

## Webhook Verification by Gateway

| Gateway | Verification Method |
|---------|-------------------|
| MyFatoorah | Bearer token |
| Paylink | Bearer token |
| EdfaPay | MD5 hash |
| Tap | HMAC-SHA256 |
| ClickPay | HMAC-SHA256 |
| Tamara | Bearer token |
| Thawani | HMAC-SHA256 |
| Fatora | HMAC-SHA256 |
| Payzaty | HMAC-SHA256 |
| Payzah | HMAC-SHA256 |
| Stripe | HMAC-SHA256 (Stripe-Signature header) |
| PayPal | CRC32 + transmission signature |
| NeonPay | HMAC-SHA256 |
| AsiaPay | HMAC-SHA256 |
| ZainCash | JWT HS256 verification |
| Mollie | Fetch-back (no signature, API verification) |
| Redsys | 3DES + HMAC-SHA256 |
| GoCardless | HMAC-SHA256 (Webhook-Signature header) |
