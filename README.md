# Laravel SISP - SISP Cabo Verde Payment Integration

[![Latest Version on Packagist](https://img.shields.io/packagist/v/akira/laravel-sisp.svg)](https://packagist.org/packages/akira/laravel-sisp)
[![Tests](https://github.com/akira-io/laravel-sisp/workflows/tests/badge.svg)](https://github.com/akira-io/laravel-sisp/actions?query=workflow%3Atests)
[![PHPStan Level](https://img.shields.io/badge/phpstan-level%209-brightgreen.svg)](https://phpstan.org)
[![License](https://img.shields.io/github/license/akira-io/laravel-sisp.svg)](LICENSE.md)

A robust Laravel 12 package for integrating SISP Cabo Verde (Vinti4 / Pagamento24) payment gateway into your Laravel applications.

## Features

✨ **Payment Integration**
- Secure payment initiation with automatic fingerprint generation
- Support for multiple transaction types
- Real-time transaction status tracking
- Webhook callback handling with signature validation

🔐 **Security**
- SHA512 fingerprint generation and validation
- Strict payload validation
- Support for 3D Secure transactions
- Automatic fingerprint verification on callbacks

🎨 **Frontend Support**
- **Blade templates** - out of the box
- **Inertia.js** (Vue/React) - optional support
- Auto-submit payment forms
- Configurable rendering

🧪 **Local Testing**
- Built-in sandbox mode for development
- Fake SISP gateway simulation
- No credentials required for testing

📊 **Events & Monitoring**
- Event-based architecture for extensibility
- `PaymentCompleted`, `PaymentFailed`, `PaymentPending` events
- Transaction persistence with full history

🏗️ **Clean Architecture**
- Action Pattern for business logic
- Value Objects for data encapsulation
- Single Responsibility Principle
- Type-safe with PHP 8.4 strict types

📄 **PDF Invoice Generation**
- Automatic invoice generation after successful payment
- Built-in PDF support with professional templates
- Customer data storage in transactions
- Download link in payment response

## Installation

### 1. Install via Composer

```bash
composer require akira/laravel-sisp
```

### 2. Publish & Install

```bash
php artisan laravel-sisp:install
```

Or manually:

```bash
php artisan vendor:publish --tag="sisp-config"
php artisan migrate
```

## Configuration

### Environment Variables

Set the following variables in your `.env` file:

```env
# SISP Gateway Configuration
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_pos_authorization_code
SISP_MERCHANT_ID=your_merchant_id

# Optional Configuration
SISP_CURRENCY=132
SISP_LANGUAGE_MESSAGES=en
SISP_IS_3D_SEC=1
SISP_DEFAULT_TRANSACTION_CODE=1

# Invoice Configuration
SISP_INVOICE_DISK=public
SISP_INVOICE_TEMPLATE=modern
SISP_COMPANY_ADDRESS=Your company address
SISP_COMPANY_CODE=Your VAT code
SISP_COMPANY_COUNTRY=Country
SISP_COMPANY_PHONE=+XXX XXXX XXXX
SISP_COMPANY_EMAIL=contact@company.com
SISP_COMPANY_WEBSITE=https://company.com

# Rendering Configuration
SISP_USE_BLADE=true
SISP_USE_INERTIA=false

# Sandbox/Development Mode
SISP_SANDBOX=false
```

### Configuration File

Edit `config/sisp.php` to customize behavior:

```php
return [
    'url' => env('SISP_URL'),
    'posID' => env('SISP_POS_ID'),
    'posAutCode' => env('SISP_POS_AUT_CODE'),
    'sandbox' => env('SISP_SANDBOX', false),

    // Blade/Template Configuration
    'use_blade' => [
        'enabled' => env('SISP_USE_BLADE', true),
        'payment_form' => 'sisp::payment-form',
        'payment_response' => 'sisp::payment-response',
    ],

    // Inertia.js Configuration (Vue/React)
    'use_inertia' => [
        'enabled' => env('SISP_USE_INERTIA', false),
        'payment_form_component' => env('SISP_INERTIA_PAYMENT_COMPONENT', 'Sisp/PaymentForm'),
        'payment_response_component' => env('SISP_INERTIA_CALLBACK_COMPONENT', 'Sisp/PaymentResponse'),
    ],
];
```

## Usage

### 1. Basic Payment Initiation

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;

public function checkout(Request $request)
{
    $paymentData = PaymentRequestData::from([
        'amount' => 50.00,
    ]);

    $paymentRequest = Sisp::buildRequestPayload($paymentData);
    return redirect()->route('sisp.payment')
        ->with($paymentRequest->toArray());
}
```

### 2. Handle Payment Events

Subscribe to payment events in your `EventServiceProvider`:

```php
protected $listen = [
    \Akira\Sisp\Events\PaymentCompleted::class => [
        App\Listeners\HandlePaymentCompleted::class,
    ],
    \Akira\Sisp\Events\PaymentFailed::class => [
        App\Listeners\HandlePaymentFailed::class,
    ],
];
```

### 3. Event Listener Example

```php
namespace App\Listeners;

use Akira\Sisp\Events\PaymentCompleted;

class HandlePaymentCompleted
{
    public function handle(PaymentCompleted $event): void
    {
        $transaction = $event->transaction;

        Order::find($transaction->merchant_ref)
            ->markAsPaid();
    }
}
```

## Routes

| Method | Path | Name | Description |
|--------|------|------|-------------|
| POST | `/sisp/payment` | `sisp.payment` | Initiate payment |
| POST | `/sisp/callback` | `sisp.callback` | Payment callback |
| GET | `/sisp/fake-gateway` | `sisp.sandbox` | Sandbox (dev) |

## Sandbox Mode

For local development:

```env
SISP_SANDBOX=true
```

The package will automatically simulate SISP responses.

## Events

### PaymentCompleted
Dispatched when payment succeeds.

### PaymentFailed
Dispatched when payment fails.

### PaymentPending
Dispatched when payment is pending.

## Requirements

- PHP 8.4+
- Laravel 11 or 12
- Composer
- Node.js with `npm` (for Puppeteer, required for PDF invoice generation)

## Peer Dependencies

This package uses `laravel-pdf-invoices` which requires Puppeteer for PDF generation. Install it with:

```bash
npm install puppeteer
```

This is required if you want to generate PDF invoices.

## Testing

```bash
composer test
```

## Official References

- [SISP](https://www.sisp.cv/)
- [Vinti4](https://www.vinti4.cv/)

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

## Contributing

Contributions are welcome! Please ensure tests pass:

```bash
composer test
```

---

**Built with ❤️ by [Akira](https://akira-io.com)**