# Quick Start Guide

Get your first SISP payment working in 5 minutes.

## 1. Install Package

```bash
composer require akira/laravel-sisp
php artisan laravel-sisp:install
```

## 2. Configure Credentials

Edit `.env`:

```env
SISP_POS_ID=YOUR_POS_ID
SISP_POS_AUT_CODE=YOUR_POS_AUTH_CODE
SISP_MERCHANT_ID=YOUR_MERCHANT_ID
```

Or use sandbox for testing:

```env
SISP_SANDBOX=true
```

## 3. Create a Payment Route

In `routes/web.php`:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;

Route::post('/checkout', function (Request $request) {
    $paymentData = PaymentRequestData::from([
        'amount' => 50.00,
    ]);

    $paymentRequest = Sisp::buildRequestPayload($paymentData);

    return redirect()->route('sisp.payment')
        ->with($paymentRequest->toArray());
});
```

## 4. Create Payment Button

In your checkout page (Blade or Inertia):

```blade
<form method="POST" action="/checkout">
    @csrf
    <button type="submit" class="btn btn-primary">
        Pay with SISP
    </button>
</form>
```

## 5. Handle Payment Response

Listen to the payment event:

```php
// app/Listeners/HandlePaymentCompleted.php

namespace App\Listeners;

use Akira\Sisp\Events\PaymentCompleted;

class HandlePaymentCompleted
{
    public function handle(PaymentCompleted $event): void
    {
        $transaction = $event->transaction;

        // Update your order
        Order::find($transaction->merchant_ref)
            ->update(['status' => 'paid']);
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    \Akira\Sisp\Events\PaymentCompleted::class => [
        App\Listeners\HandlePaymentCompleted::class,
    ],
];
```

## 6. Test with Sandbox

Enable sandbox mode to test without credentials:

```env
SISP_SANDBOX=true
```

The package will simulate SISP responses automatically.

## What Happens?

1. User clicks "Pay with SISP"
2. System creates a transaction in the database
3. User is redirected to payment form
4. Form auto-submits to SISP (or sandbox)
5. SISP processes payment (or fake gateway)
6. Callback is sent to `/sisp/callback`
7. Event is dispatched (PaymentCompleted/Failed)
8. Your listener updates the order

## That's It! 🎉

You now have:
- ✅ Payment initiation
- ✅ Transaction tracking
- ✅ Event-based payment handling
- ✅ Sandbox testing

## Next Steps

- [Learn about Payment Flow](./payment-flow.md) - Understand complete payment process
- [Customer Data & Invoices](./customer-data-and-invoices.md) - Collect customer info and generate invoices
- [Configuration Guide](./configuration.md) - Customize all settings
- [Events & Monitoring](./events-and-monitoring.md) - Handle payment events
- [Security & Fraud Detection](./security-and-fraud-detection.md) - Enable fraud protection
- [Rate Limiting](./rate-limiting.md) - Prevent payment abuse

## Common Questions

**Q: How do I test payments locally?**
A: Set `SISP_SANDBOX=true` in your `.env` file.

**Q: Where are transactions stored?**
A: In the `sisp_transactions` table, accessible via the `Transaction` model.

**Q: Can I generate PDF invoices after payment?**
A: Yes! Use [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) package in your PaymentCompleted listener.

**Q: How do I handle failed payments?**
A: Listen to the `PaymentFailed` event in your EventServiceProvider. See [Events & Monitoring](./events-and-monitoring.md).

**Q: What about security and fraud detection?**
A: The package includes rate limiting, blacklisting, geolocation detection, and device fingerprinting. See [Security & Fraud Detection](./security-and-fraud-detection.md).

**Q: How many requests can one IP make?**
A: Default is 100 per hour. Configure in `.env` using `SISP_RATE_LIMIT_PER_IP_LIMIT`. See [Rate Limiting Guide](./rate-limiting.md).