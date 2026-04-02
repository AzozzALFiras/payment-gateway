# Payment Gateway PHP Library

[![PHP Version](https://img.shields.io/badge/PHP-%5E8.1-blue.svg)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Composer](https://img.shields.io/badge/Composer-azozzalfiras%2Fpayment--gateway-orange.svg)](https://packagist.org/packages/azozzalfiras/payment-gateway)

A **unified**, **extensible** PHP Composer package for integrating with **18 payment gateways** (Middle East + Iraq + Europe + International). Built with clean architecture, full API coverage, zero external dependencies, and enterprise-grade security.

---

## Table of Contents

- [Supported Gateways](#supported-gateways)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Factory Pattern](#factory-pattern)
- [Feature Matrix](#feature-matrix)
- [Documentation](#documentation)
- [Testing](#testing)
- [Contributing](#contributing)
- [License](#license)

---

<a id="supported-gateways"></a>

## Supported Gateways

<table>
  <tr>
    <td align="center"><img src="assets/logos/myfatoorah.png" width="80"><br><b>MyFatoorah</b></td>
    <td align="center"><img src="assets/logos/paylink.png" width="80"><br><b>Paylink</b></td>
    <td align="center"><img src="assets/logos/edfapay.png" width="80"><br><b>EdfaPay</b></td>
    <td align="center"><img src="assets/logos/tap.png" width="80"><br><b>Tap</b></td>
    <td align="center"><img src="assets/logos/clickpay.png" width="80"><br><b>ClickPay</b></td>
  </tr>
  <tr>
    <td align="center"><img src="assets/logos/tamara.png" width="80"><br><b>Tamara</b></td>
    <td align="center"><img src="assets/logos/thawani.png" width="80"><br><b>Thawani</b></td>
    <td align="center"><img src="assets/logos/fatorah.png" width="80"><br><b>Fatora</b></td>
    <td align="center"><img src="assets/logos/payzaty.png" width="80"><br><b>Payzaty</b></td>
    <td align="center"><img src="assets/logos/payzah.png" width="80"><br><b>Payzah</b></td>
  </tr>
  <tr>
    <td align="center"><img src="assets/logos/stripe.png" width="80"><br><b>Stripe</b></td>
    <td align="center"><img src="assets/logos/paypal.png" width="80"><br><b>PayPal</b></td>
    <td align="center"><img src="assets/logos/neonpay.png" width="80"><br><b>NeonPay</b></td>
    <td align="center"><img src="assets/logos/asiapay.png" width="80"><br><b>AsiaPay</b></td>
    <td align="center"><img src="assets/logos/zaincash.png" width="80"><br><b>ZainCash</b></td>
  </tr>
  <tr>
    <td align="center"><img src="assets/logos/mollie.png" width="80"><br><b>Mollie</b></td>
    <td align="center"><img src="assets/logos/redsys.png" width="80"><br><b>Redsys</b></td>
    <td align="center" colspan="3"><img src="assets/logos/gocardless.png" width="80"><br><b>GoCardless</b></td>
  </tr>
</table>

| Gateway | Region | Classes | Key Features | Authentication |
|---------|--------|:-------:|-------------|----------------|
| **MyFatoorah** | Middle East | 5 | Payments, Sessions, Invoices, Customers, Webhooks | API Key (Bearer) |
| **Paylink** | Middle East | 5 | Invoices, Digital Products, Reconciliation, Webhooks | API ID + Secret Key |
| **EdfaPay** | Middle East | 6 | Checkout, Embedded S2S, Apple Pay, Recurring, Refunds | Client Key + Password |
| **Tap** | Middle East | 7 | Charges, Authorize/Void, Refunds, Invoices, Customers, Tokens | Secret Key (Bearer) |
| **ClickPay** | Middle East | 3 | Hosted Page, Auth/Capture/Void/Refund, Tokenization | Server Key |
| **Tamara** | Middle East | 4 | BNPL Checkout, Order Authorize/Capture/Cancel, Refunds | API Token (Bearer) |
| **Thawani** | Middle East | 4 | Checkout Sessions, Payment Intents, Customer Management | Secret + Publishable Key |
| **Fatora** | Middle East | 4 | Checkout, Verify, Refunds, Recurring (Card Tokenization) | API Key |
| **Payzaty** | Middle East | 3 | Secure Checkout, Mada/VISA/MC/Apple Pay/STC Pay | Account No + Secret Key |
| **Payzah** | Middle East | 3 | Hosted Checkout, K-Net/VISA/MC/Apple Pay/Amex | Private Key |
| **NeonPay** | Middle East | 3 | Payments, Refunds, Webhooks (EBIK Key Auth) | EBIK Key (Header) |
| **AsiaPay** | Middle East | 4 | Orders, Refunds, JWT Auth, Webhooks | App Key + Secret (JWT) |
| **ZainCash** | Middle East | 3 | Transactions, JWT HS256 Auth, Webhooks | Merchant ID + Secret (JWT) |
| **Stripe** | International | 6 | Checkout Sessions, Charges, Customers, Refunds, Webhooks | Secret Key (Bearer) |
| **PayPal** | International | 6 | Orders, Captures, Authorizations, Refunds, OAuth 2.0 | Client ID + Secret (OAuth) |
| **Mollie** | Europe | 5 | Payments, Refunds, Customers, iDEAL, SEPA, Klarna | API Key (Bearer) |
| **Redsys** | Europe | 3 | Payments, Refunds, 3DES + HMAC-SHA256 Signing | Merchant Key (3DES) |
| **GoCardless** | Europe | 5 | Payments, Mandates, Refunds, Direct Debit, Webhooks | Access Token (Bearer) |

> **79 PHP classes** across 18 gateways organized in 3 regions: `International/`, `MiddleEast/`, `Europe/`

---

<a id="installation"></a>

## Installation

```bash
composer require azozzalfiras/payment-gateway
```

| Requirement | Version |
|-------------|---------|
| PHP | >= 8.1 |
| ext-curl | Required |
| ext-json | Required |
| ext-mbstring | Required |

---

<a id="quick-start"></a>

## Quick Start

```php
use AzozzALFiras\PaymentGateway\PaymentGateway;
use AzozzALFiras\PaymentGateway\Enums\Gateway;
use AzozzALFiras\PaymentGateway\Enums\PaymentStatus;
use AzozzALFiras\PaymentGateway\DTOs\PaymentRequest;
use AzozzALFiras\PaymentGateway\DTOs\Customer;

// Create gateway from enum
$gateway = PaymentGateway::create(Gateway::TAP->value, [
    'secret_key' => 'sk_test_xxx',
    'testMode'   => true,
]);

// Or use typed static factory
$gateway = PaymentGateway::stripe(['secret_key' => 'sk_test_xxx', 'testMode' => true]);

// Purchase
$response = $gateway->purchase(new PaymentRequest(
    amount:      100.00,
    currency:    'SAR',
    orderId:     'ORDER-001',
    description: 'Premium Plan',
    callbackUrl: 'https://yoursite.com/callback',
    customer:    new Customer(name: 'Ahmed', email: 'ahmed@example.com'),
));

// Redirect customer to payment page
header("Location: {$response->paymentUrl}");

// Check payment status
$status = $gateway->status($response->transactionId);

// Gateway metadata from enum
echo Gateway::TAP->label();           // "Tap Payments"
echo Gateway::TAP->region();          // "MiddleEast"
echo Gateway::TAP->supportsRefund();  // true
echo Gateway::TAP->countries();       // ['KWT', 'SAU', 'ARE', ...]
```

---

<a id="factory-pattern"></a>

## Factory Pattern

```php
use AzozzALFiras\PaymentGateway\PaymentGateway;

// 1. Generic factory — accepts any driver name string
$gateway = PaymentGateway::create('tap', ['secret_key' => '...']);

// 2. Typed static factory — full IDE autocomplete per gateway
$gateway = PaymentGateway::myfatoorah([...]);
$gateway = PaymentGateway::paylink([...]);
$gateway = PaymentGateway::edfapay([...]);
$gateway = PaymentGateway::tap([...]);
$gateway = PaymentGateway::clickpay([...]);
$gateway = PaymentGateway::tamara([...]);
$gateway = PaymentGateway::thawani([...]);
$gateway = PaymentGateway::fatora([...]);
$gateway = PaymentGateway::payzaty([...]);
$gateway = PaymentGateway::payzah([...]);
$gateway = PaymentGateway::stripe([...]);
$gateway = PaymentGateway::paypal([...]);
$gateway = PaymentGateway::neonpay([...]);
$gateway = PaymentGateway::asiapay([...]);
$gateway = PaymentGateway::zaincash([...]);
$gateway = PaymentGateway::mollie([...]);
$gateway = PaymentGateway::redsys([...]);
$gateway = PaymentGateway::gocardless([...]);

// List all 18 available driver names
PaymentGateway::getAvailableDrivers();
```

---

<a id="feature-matrix"></a>

## Feature Matrix

| Feature | <img src="assets/logos/myfatoorah.png" width="20"> | <img src="assets/logos/paylink.png" width="20"> | <img src="assets/logos/edfapay.png" width="20"> | <img src="assets/logos/tap.png" width="20"> | <img src="assets/logos/clickpay.png" width="20"> | <img src="assets/logos/tamara.png" width="20"> | <img src="assets/logos/thawani.png" width="20"> | <img src="assets/logos/fatorah.png" width="20"> | <img src="assets/logos/payzaty.png" width="20"> | <img src="assets/logos/payzah.png" width="20"> | <img src="assets/logos/stripe.png" width="20"> | <img src="assets/logos/paypal.png" width="20"> | <img src="assets/logos/neonpay.png" width="20"> | <img src="assets/logos/asiapay.png" width="20"> | <img src="assets/logos/zaincash.png" width="20"> | <img src="assets/logos/mollie.png" width="20"> | <img src="assets/logos/redsys.png" width="20"> | <img src="assets/logos/gocardless.png" width="20"> |
|---------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| Purchase | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Status | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |
| Refund | ✅ | — | ✅ | ✅ | ✅ | ✅ | — | ✅ | — | — | ✅ | ✅ | ✅ | ✅ | — | ✅ | ✅ | ✅ |
| Recurring | — | — | ✅ | ✅ | ✅ | — | ✅ | ✅ | — | — | ✅ | — | — | — | — | — | — | — |
| Auth/Capture | ✅ | — | ✅ | ✅ | ✅ | ✅ | — | — | — | — | ✅ | ✅ | — | — | — | — | — | — |
| Customer CRUD | ✅ | — | — | ✅ | — | — | ✅ | — | — | — | ✅ | — | — | — | — | ✅ | — | — |
| Direct Debit | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | — | ✅ |
| Webhook | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ | ✅ |

---

<a id="documentation"></a>

## Documentation

| Document | Description |
|----------|-------------|
| **[Gateway Reference](docs/GATEWAYS.md)** | Configuration, code examples, and sub-modules for all 18 gateways |
| **[Architecture](docs/ARCHITECTURE.md)** | Project structure, directory tree, and design principles |
| **[Security](docs/SECURITY.md)** | Signature verification, input sanitization, and best practices |
| **[Webhooks](docs/WEBHOOKS.md)** | Unified webhook handling across all gateways |
| **[Database Integration](docs/DATABASE.md)** | SQL schemas, models, and full payment flow examples |
| **[Contributing](docs/CONTRIBUTING.md)** | How to add gateways, code style, and PR guidelines |

---

<a id="testing"></a>

## Testing

```bash
composer install
composer test       # PHPUnit
composer analyse    # PHPStan Level 5
```

**Current test results:**
- PHPUnit: 135 tests, 281 assertions — all passing
- PHP Lint: all files — zero syntax errors
- PHPStan Level 5: 0 errors

---

<a id="contributing"></a>

## Contributing

We welcome pull requests! Whether you're adding a new payment gateway, fixing bugs, or improving documentation.

See **[CONTRIBUTING.md](docs/CONTRIBUTING.md)** for detailed guidelines on:
- How to add a new gateway (file structure, contracts, registration)
- Code style (PHP 8.1+, PSR-12, strict types)
- Testing requirements
- PR process

---

<a id="license"></a>

## License

MIT License — see [LICENSE](LICENSE) file.

---

## Author

**AzozzALFiras** — [GitHub](https://github.com/AzozzALFiras)
