# Contributing

We welcome contributions! Whether it's adding a new payment gateway, fixing bugs, improving documentation, or suggesting features.

## How to Contribute

1. **Fork** the repository
2. **Create a branch** from `main`: `git checkout -b feature/my-new-gateway`
3. **Make your changes** following the guidelines below
4. **Run tests**: `composer test`
5. **Submit a Pull Request** with a clear description

## Adding a New Gateway

Each gateway follows a consistent multi-file pattern:

### 1. Choose the Regional Folder

| Region | Path | When to use |
|--------|------|-------------|
| `International/` | `src/Gateways/International/` | Gateways available in 10+ countries worldwide |
| `MiddleEast/` | `src/Gateways/MiddleEast/` | MENA region gateways (including Iraq) |
| `Europe/` | `src/Gateways/Europe/` | European gateways |

### 2. Create Gateway Files

```
src/Gateways/{Region}/{GatewayName}/
├── {GatewayName}Gateway.php     # Main class (implements GatewayInterface)
├── {GatewayName}Payment.php     # Payment sub-module (optional)
├── {GatewayName}Refund.php      # Refund sub-module (optional)
└── {GatewayName}Webhook.php     # Webhook handler
```

### 3. Implement Contracts

```php
<?php

declare(strict_types=1);

namespace AzozzALFiras\PaymentGateway\Gateways\{Region}\{GatewayName};

use AzozzALFiras\PaymentGateway\Contracts\GatewayInterface;
use AzozzALFiras\PaymentGateway\Contracts\SupportsRefund;    // If applicable
use AzozzALFiras\PaymentGateway\Contracts\SupportsWebhook;   // If applicable

class {GatewayName}Gateway implements GatewayInterface, SupportsRefund, SupportsWebhook
{
    public function getName(): string { return '{GatewayName}'; }
    public function isTestMode(): bool { return $this->config->isTestMode(); }
    public function purchase(PaymentRequest $request): PaymentResponse { ... }
    public function status(string $transactionId): PaymentResponse { ... }
    // ...
}
```

### 4. Register the Gateway

- Add a case to `src/Enums/Gateway.php` (all metadata methods: `label()`, `countries()`, `currencies()`, `supportsRefund()`, `region()`)
- Add a `use` statement + driver entry + typed factory method to `src/PaymentGateway.php`
- Add gateway docs to `docs/GATEWAYS.md`
- Add a test file under `tests/Unit/Gateways/{Region}/`

### 5. Write Tests

Every gateway needs at minimum:

```php
public function testInstantiation(): void { ... }
public function testGetName(): void { ... }
public function testIsTestMode(): void { ... }
public function testCreateViaFactory(): void { ... }
public function testMissingRequiredConfigThrows(): void { ... }
public function testSubModuleAccessors(): void { ... }
```

## Code Style

- **PHP 8.1+** with `declare(strict_types=1)`
- **PSR-12** coding standard
- **PSR-4** autoloading
- Use the shared `HttpClient` for all HTTP requests (no external HTTP libraries)
- Use `GatewayConfig::require()` for mandatory config keys
- Use `SignatureVerifier` for webhook signature validation
- Use DTOs (`PaymentRequest`, `PaymentResponse`, etc.) for all data structures

## Running Tests

```bash
composer install
composer test              # PHPUnit
composer analyse           # PHPStan Level 5
find src/ -name '*.php' -exec php -l {} \;  # Lint
```

## Pull Request Guidelines

- Keep PRs focused — one gateway or one fix per PR
- Include tests for new code
- Update documentation if adding features
- Ensure all tests pass before submitting
- Write clear commit messages

## Reporting Issues

Found a bug? Have a suggestion? [Open an issue](https://github.com/AzozzALFiras/payment-gateway/issues) with:
- Gateway name (if applicable)
- PHP version
- Steps to reproduce
- Expected vs actual behavior
