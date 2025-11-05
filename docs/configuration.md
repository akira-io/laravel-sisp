# Configuration Reference

The package configuration is located in `config/sisp.php`. All settings can be overridden via environment variables.

## SISP Credentials

These are required for production. Get them from SISP:

```env
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_pos_authorization_code
SISP_MERCHANT_ID=your_merchant_id
```

**Description:**
- `SISP_URL` - SISP payment gateway URL (endpoint for payment processing)
- `SISP_POS_ID` - Point of Sale (POS) Terminal ID provided by SISP
- `SISP_POS_AUT_CODE` - POS Authorization Code (used for authentication)
- `SISP_MERCHANT_ID` - Your merchant identifier in SISP system

## Payment Settings

```env
# Currency code (ISO 4217)
SISP_CURRENCY=132

# Language for response messages
SISP_LANGUAGE_MESSAGES=en

# Enable 3D Secure
SISP_IS_3D_SEC=1

# Transaction type
SISP_DEFAULT_TRANSACTION_CODE=1

# Fingerprint version for request signing
SISP_FINGERPRINT_VERSION=1

# Redirect URL after payment completion
SISP_REDIRECT_URL=/

# Payment form theme (white, dark)
SISP_THEME=white
```

**Description:**
- `SISP_CURRENCY` - Currency code (132 = Cape Verde Escudo/CVE). See [ISO 4217](https://en.wikipedia.org/wiki/ISO_4217)
- `SISP_LANGUAGE_MESSAGES` - Response language (en = English, pt = Portuguese)
- `SISP_IS_3D_SEC` - Enable 3D Secure authentication (1 = enabled, 0 = disabled)
- `SISP_DEFAULT_TRANSACTION_CODE` - Transaction type code (1 = standard payment, consult SISP for other codes)
- `SISP_FINGERPRINT_VERSION` - Fingerprint algorithm version used for request signing (typically 1)
- `SISP_REDIRECT_URL` - Where to redirect user after payment completes (default: home page)
- `SISP_THEME` - Payment form theme (white = light theme, dark = dark theme)

## Rendering Configuration

### Blade (Default)

```env
SISP_USE_BLADE=true
```

### Inertia.js

```env
SISP_USE_INERTIA=true
SISP_INERTIA_PAYMENT_COMPONENT=Sisp/PaymentForm
SISP_INERTIA_CALLBACK_COMPONENT=Sisp/PaymentResponse
```

## Sandbox Mode

For local development without real SISP credentials:

```env
SISP_SANDBOX=true
```

The package will simulate all SISP responses. Perfect for:
- ✅ Local development
- ✅ Testing payment flows
- ✅ CI/CD pipelines
- ✅ Learning the integration

## Generator Configuration

These generate unique values for each payment. Customize in `config/sisp.php`:

```php
'generators' => [
    'merchantSession' => Akira\Sisp\Actions\Generators\MerchantSessionGeneratorAction::class,
    'merchantReference' => Akira\Sisp\Actions\Generators\MerchantReferenceGeneratorAction::class,
    'timeStamp' => Akira\Sisp\Actions\Generators\TimeStampGeneratorAction::class,
],
```

## Database Configuration

```php
'table_name' => 'sisp_transactions',
```

The migration creates a table with:
- Transaction metadata
- Payment status
- Full SISP response payload
- Fingerprint verification

## Full Configuration File

```php
<?php

declare(strict_types=1);

return [
    // SISP Gateway URL
    'url' => env('SISP_URL'),

    // POS Terminal ID from SISP
    'posID' => env('SISP_POS_ID'),

    // POS Authorization Code from SISP
    'posAutCode' => env('SISP_POS_AUT_CODE'),

    // Merchant ID from SISP
    'merchantId' => env('SISP_MERCHANT_ID'),

    // Currency (132 = CVE)
    'currency' => env('SISP_CURRENCY', '132'),

    // Language for messages (en, pt)
    'languageMessages' => env('SISP_LANGUAGE_MESSAGES', 'en'),

    // Fingerprint algorithm version
    'fingerPrintVersion' => env('SISP_FINGERPRINT_VERSION', '1'),

    // Merchant response URL
    'urlMerchantResponse' => config('app.url').'/sisp/callback',

    // 3D Secure enabled
    'is3DSec' => env('SISP_IS_3D_SEC', '1'),

    // Default transaction code
    'transactionCode' => env('SISP_DEFAULT_TRANSACTION_CODE', '1'),

    // Database table name
    'table_name' => 'sisp_transactions',

    // Redirect after payment
    'redirect_url' => env('SISP_REDIRECT_URL', '/'),

    // Theme (white, dark)
    'theme' => env('SISP_THEME', 'white'),

    // Value generators
    'generators' => [
        'merchantSession' => Akira\Sisp\Actions\Generators\MerchantSessionGeneratorAction::class,
        'merchantReference' => Akira\Sisp\Actions\Generators\MerchantReferenceGeneratorAction::class,
        'timeStamp' => Akira\Sisp\Actions\Generators\TimeStampGeneratorAction::class,
    ],

    // Sandbox mode
    'sandbox' => env('SISP_SANDBOX', false),

    // Blade rendering
    'use_blade' => [
        'enabled' => env('SISP_USE_BLADE', true),
        'payment_form' => 'sisp::payment-form',
        'payment_response' => 'sisp::payment-response',
    ],

    // Inertia rendering (Vue/React)
    'use_inertia' => [
        'enabled' => env('SISP_USE_INERTIA', false),
        'payment_form_component' => env('SISP_INERTIA_PAYMENT_COMPONENT', 'Sisp/PaymentForm'),
        'payment_response_component' => env('SISP_INERTIA_CALLBACK_COMPONENT', 'Sisp/PaymentResponse'),
    ],
];
```

## Custom Generators

Create your own generator by implementing the `Generator` contract:

```php
<?php

namespace App\Generators;

use Akira\Sisp\Contracts\Generator;

final class CustomMerchantReferenceGenerator implements Generator
{
    public function __invoke(): string
    {
        return 'ORDER-' . time() . '-' . rand(1000, 9999);
    }
}
```

Register in `config/sisp.php`:

```php
'generators' => [
    'merchantReference' => App\Generators\CustomMerchantReferenceGenerator::class,
    // ...
],
```

## Environment Checklist

- [ ] Database configured
- [ ] `SISP_POS_ID` set
- [ ] `SISP_POS_AUT_CODE` set
- [ ] `SISP_MERCHANT_ID` set
- [ ] Migrations run (`php artisan migrate`)
- [ ] Routes registered (check with `php artisan route:list`)
- [ ] Rendering configured (Blade or Inertia)
- [ ] Testing (use `SISP_SANDBOX=true`)

## Related Configuration

### Rate Limiting Configuration

The package includes built-in rate limiting to prevent payment abuse:

```env
SISP_RATE_LIMIT_PER_IP_LIMIT=100
SISP_RATE_LIMIT_PER_IP_WINDOW=3600
```

See [Rate Limiting Guide](./rate-limiting.md) for complete details on configuring rate limits.

### Security Configuration

For fraud detection and security features:

```env
SISP_BLACKLIST_ENABLED=true
SISP_REQUEST_METADATA_ENABLED=true
```

See [Security & Fraud Detection](./security-and-fraud-detection.md) for comprehensive security configuration.

### Invoice Generation

After successful payments, invoices can be generated automatically:

```env
SISP_INVOICE_NUMBER_FORMAT=date-based
SISP_INVOICE_NUMBER_PREFIX=INV
```

Integrates with [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) for PDF generation.

## Next Steps

- [Quick Start Guide](./quick-start.md) - Get started in 5 minutes
- [Payment Flow](./payment-flow.md) - Understand complete payment process
- [Security & Fraud Detection](./security-and-fraud-detection.md) - Enable fraud protection
- [Rate Limiting](./rate-limiting.md) - Prevent payment abuse
- [API Reference](./api-reference.md) - Complete API documentation

## See Also

- [Architecture & Design Patterns](./architecture.md) - System design overview
- [Events & Monitoring](./events-and-monitoring.md) - Event handling and monitoring
- [E-Commerce Transactions](./e-commerce-transactions.md) - Full transaction example