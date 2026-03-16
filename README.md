# 💳 Payment Gateway PHP Library

[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Composer](https://img.shields.io/badge/Composer-azozzalfiras%2Fpayment--gateway-orange.svg)](https://packagist.org/packages/azozzalfiras/payment-gateway)

A **unified**, **extensible** PHP Composer package for integrating with major MENA-region payment gateways. Built with clean architecture, full API coverage, and zero external dependencies.

## ✨ Supported Gateways

| Gateway | Features | Region |
|---------|----------|--------|
| **MyFatoorah** | Payments, Sessions, Invoices, Customers, Webhooks | Kuwait, Saudi Arabia, UAE, Bahrain, Qatar, Oman, Egypt, Jordan |
| **Paylink** | Invoices, Digital Products, Reconciliation, Webhooks | Saudi Arabia |
| **EdfaPay** | Checkout, Embedded S2S, Apple Pay, Recurring, Auth/Capture, Refunds, Webhooks | Saudi Arabia, MENA |

## 📦 Installation

```bash
composer require azozzalfiras/payment-gateway
```

**Requirements:** PHP 8.1+, ext-curl, ext-json, ext-mbstring

## 🚀 Quick Start

### MyFatoorah

```php
use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\Customer;

// Specify country to auto-route to the correct API endpoint
$gateway = PaymentGateway::myfatoorah([
    'api_key'  => 'YOUR_API_KEY',
    'country'  => 'SAU',       // KWT, SAU, ARE, QAT, BHR, OMN, JOR, EGY
    'testMode' => true,
]);

// Also accepts ISO alpha-2: 'SA', 'AE', 'KW', 'QA', 'BH', 'OM', 'JO', 'EG'

$response = $gateway->purchase(new PaymentRequest(
    amount:      100.00,
    currency:    'SAR',       // or use $gateway->getDefaultCurrency()
    orderId:     'ORDER-001',
    description: 'Premium Subscription',
    callbackUrl: 'https://yoursite.com/callback',
    customer:    new Customer(name: 'Ahmed', email: 'ahmed@example.com'),
));

if ($response->requiresRedirect()) {
    header("Location: {$response->getRedirectUrl()}");
}
```

> **Multi-Country Support:** Each MyFatoorah country has its own API token key. Use the `country` config to auto-route requests to the correct regional endpoint (e.g., `api-sa.myfatoorah.com` for Saudi Arabia).

### Paylink

```php
$gateway = PaymentGateway::paylink([
    'api_id'     => 'YOUR_API_ID',
    'secret_key' => 'YOUR_SECRET_KEY',
    'testMode'   => true,
]);

$invoice = $gateway->createInvoice(new InvoiceRequest(
    amount:      250.00,
    currency:    'SAR',
    orderId:     'INV-001',
    callbackUrl: 'https://yoursite.com/callback',
    customer:    new Customer(name: 'Mohammed', email: 'mohammed@example.com'),
    items:       [['name' => 'Web Design', 'price' => 250.00, 'quantity' => 1]],
));

// Redirect to: $invoice->paymentUrl
```

### EdfaPay

```php
$gateway = PaymentGateway::edfapay([
    'client_key' => 'YOUR_CLIENT_KEY',
    'password'   => 'YOUR_SECRET_PASSWORD',
    'testMode'   => true,
]);

// Hosted Checkout
$response = $gateway->purchase(new PaymentRequest(
    amount:      150.00,
    currency:    'SAR',
    orderId:     'ORD-001',
    description: 'Online Order',
    callbackUrl: 'https://yoursite.com/callback',
    customer:    new Customer(email: 'khalid@example.com'),
));

// Embedded S2S (Direct Card Payment)
$result = $gateway->embedded()->sale([
    'order_id'       => 'ORD-002',
    'order_amount'   => 75.50,
    'order_currency' => 'SAR',
    'card_number'    => '5123456789012346',
    'card_exp_month' => '12',
    'card_exp_year'  => '2030',
    'card_cvv2'      => '123',
    'payer_email'    => 'khalid@example.com',
    'payer_ip'       => '127.0.0.1',
    'term_url_3ds'   => 'https://yoursite.com/3ds/callback',
]);
```

## 🏗️ Architecture

```
src/
├── PaymentGateway.php          ← Factory (entry point)
├── Contracts/                  ← Interfaces (GatewayInterface, SupportsRefund, etc.)
├── Config/                     ← Immutable configuration
├── DTOs/                       ← Data Transfer Objects
├── Exceptions/                 ← Typed exception hierarchy
├── Http/                       ← Zero-dependency cURL client
├── Support/                    ← Arr & Currency helpers
└── Gateways/
    ├── MyFatoorah/             ← 5 classes (Gateway, Session, Invoice, Customer, Webhook)
    ├── Paylink/                ← 5 classes (Gateway, Auth, Invoice, Reconcile, Webhook)
    └── EdfaPay/                ← 6 classes (Gateway, Checkout, Embedded, ApplePay, Hash, Webhook)
```

## 🔌 Feature Matrix

### MyFatoorah (V3 API)

| Feature | Method |
|---------|--------|
| Create Payment | `$gateway->purchase($request)` |
| Payment Status | `$gateway->status($paymentId)` |
| Capture/Release | `$gateway->updatePayment($id, 'Capture')` |
| Create Session | `$gateway->sessions()->create($amount)` |
| Get Invoice | `$gateway->getInvoice($id)` |
| Customer Details | `$gateway->customers()->getDetails($ref)` |
| Webhook | `$gateway->handleWebhook($payload)` |

### Paylink

| Feature | Method |
|---------|--------|
| Create Invoice | `$gateway->createInvoice($request)` |
| Get Invoice | `$gateway->getInvoice($transactionNo)` |
| Cancel Invoice | `$gateway->cancelInvoice($transNo)` |
| Digital Products | `$gateway->sendDigitalProduct($transNo, $info)` |
| Transactions by Date | `$gateway->reconcile()->getByDateRange(...)` |
| Settlements | `$gateway->reconcile()->getSettlements(...)` |
| Webhook | `$gateway->handleWebhook($payload)` |

### EdfaPay

| Feature | Method |
|---------|--------|
| Hosted Checkout | `$gateway->purchase($request)` |
| S2S Card Sale | `$gateway->embedded()->sale($params)` |
| Authorize | `$gateway->embedded()->authorize($params)` |
| Capture | `$gateway->embedded()->capture($transId)` |
| Full Refund | `$gateway->refund($request)` |
| Partial Refund | `$gateway->partialRefund($request)` |
| Recurring | `$gateway->recurring($request)` |
| Apple Pay | `$gateway->applePay()->sale(...)` |
| Transaction Status | `$gateway->embedded()->status($transId, $orderId)` |
| Credit Void | `$gateway->embedded()->creditVoid($transId)` |
| Extra Amount | `$gateway->embedded()->extraAmount($transId, $amount)` |
| Webhook | `$gateway->handleWebhook($payload)` |

## 🔐 Webhook Handling

```php
// In your webhook endpoint controller
$payload = json_decode(file_get_contents('php://input'), true);
$headers = getallheaders();

$webhook = $gateway->handleWebhook($payload, $headers);

if ($webhook->isValid) {
    // Process the event
    echo "Transaction: {$webhook->transactionId}\n";
    echo "Status: {$webhook->status}\n";
    echo "Amount: {$webhook->amount} {$webhook->currency}\n";
}
```

## 🏭 Factory Pattern

```php
// Generic factory
$gateway = PaymentGateway::create('myfatoorah', ['api_key' => '...']);
$gateway = PaymentGateway::create('paylink', ['api_id' => '...', 'secret_key' => '...']);
$gateway = PaymentGateway::create('edfapay', ['client_key' => '...', 'password' => '...']);

// Typed factory (IDE-friendly, full autocomplete)
$gateway = PaymentGateway::myfatoorah([...]);
$gateway = PaymentGateway::paylink([...]);
$gateway = PaymentGateway::edfapay([...]);

// List available drivers
PaymentGateway::getAvailableDrivers(); // ['myfatoorah', 'paylink', 'edfapay']
```

## 📁 Examples

See the [`examples/`](examples/) directory for complete working examples:

- [`myfatoorah_payment.php`](examples/myfatoorah_payment.php) — Payment + Session flow
- [`paylink_invoice.php`](examples/paylink_invoice.php) — Invoice lifecycle + Reconciliation
- [`edfapay_checkout.php`](examples/edfapay_checkout.php) — Checkout, S2S, Auth/Capture, Recurring, Apple Pay

## 🧪 Testing

```bash
composer install
composer test        # Run PHPUnit tests
composer analyse     # Run PHPStan analysis
```

## 📄 License

MIT License — see [LICENSE](LICENSE) file.

## 👨‍💻 Author

**AzozzALFiras** — [GitHub](https://github.com/AzozzALFiras)
# payment-gateway
