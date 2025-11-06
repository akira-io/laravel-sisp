# Frequently Asked Questions

## General

### What is SISP?

SISP (Sistema Integrado de Serviços de Pagamentos) is Cape Verde's integrated payment services system. This package integrates Laravel applications with SISP's payment gateway.

### What countries does SISP support?

SISP is primarily designed for Cape Verde. Check with SISP directly for coverage in other regions.

### Do I need a merchant account?

Yes, you must register as a merchant with SISP to get credentials (POS ID, authorization code, merchant ID).

### How do I get SISP credentials?

Contact SISP directly. They will provide:
- SISP_URL
- SISP_POS_ID
- SISP_POS_AUT_CODE
- SISP_MERCHANT_ID

### Can I test payments before going live?

Yes, enable sandbox mode in your `.env`:

```env
SISP_SANDBOX=true
```

This routes payments through a fake gateway for testing.

## Setup & Installation

### Do I need to create custom routes?

No, the package registers all routes automatically:
- `POST /sisp/payment` - Payment submission
- `GET|POST /sisp/callback` - SISP callback
- `POST /sisp/cancel` - Cancel transaction
- `POST /sisp/refund` - Refund transaction

### Can I customize the routes?

Currently, routes are fixed. You can create wrapper routes in your application that call the package routes.

### Do I need to create a payment form?

No form is required. The package renders its own form. You just need a form that POSTs to `/sisp/payment`.

You can use a simple HTML form or build your own payment page that submits to the endpoint.

### How do I handle the payment response?

The package automatically handles callbacks from SISP and dispatches events:
- `PaymentCompleted` - Payment successful
- `PaymentFailed` - Payment rejected
- `PaymentPending` - Still processing

Listen to these events in your application to handle responses.

## Payments

### What currencies are supported?

Only ECV (Cape Verde Escudo) is supported currently.

### What's the minimum payment amount?

The minimum is 0.01 ECV. Amounts are stored in cents in the database.

### Can I charge fees?

Yes, add a fee line item to your payment:

```html
<input type="hidden" name="items[1][product_name]" value="Processing Fee">
<input type="hidden" name="items[1][quantity]" value="1">
<input type="hidden" name="items[1][unit_price]" value="5.00">
<input type="hidden" name="items[1][total_price]" value="5.00">
```

### Can I accept partial payments?

Not directly through SISP. The customer must pay the full amount or nothing.

You can create multiple transactions for partial payments and track them separately.

### How long is a transaction valid?

Transactions submitted to SISP remain pending until:
1. Customer completes payment (moves to completed/failed)
2. You manually cancel it
3. Default: 24 hours (check with SISP)

### What if the customer closes the payment window?

The transaction remains in `pending` status. You can:
1. Send a reminder email with a payment link
2. Cancel it and create a new one
3. Let it auto-expire

## Transactions

### Can I refund a payment?

Yes, refund completed transactions using `RefundTransactionAction`. Partial and full refunds are supported.

### Can I cancel a pending payment?

Yes, cancel using `CancelTransactionAction`. Works for `pending` or `failed` transactions.

### Can I modify a transaction?

No, transactions are immutable once created. Create a new transaction instead.

### How do I track order status?

Listen to payment events and update your order status:

```php
Event::listen(PaymentCompleted::class, function ($event) {
    Order::where('transaction_id', $event->transaction->id)
        ->update(['status' => 'paid']);
});
```

### Can I add custom data to transactions?

Yes, transactions include metadata support. You can store custom data in the `payload` field.

## Invoices

### Are invoices required?

No, invoices are optional. Configure company details to enable auto-generation:

```env
SISP_COMPANY_NAME="Your Company"
SISP_COMPANY_ADDRESS="..."
```

### Can I customize invoice templates?

Yes, choose between `modern` and `minimal` templates:

```env
SISP_INVOICE_TEMPLATE=modern
```

### Where are PDF files stored?

PDFs are stored in `storage/app/public/invoices/`. Make sure this directory is writable.

### Can I attach invoices to emails?

Yes, listen to `PaymentCompleted` event and send the PDF:

```php
Event::listen(PaymentCompleted::class, function ($event) {
    $invoice = $event->transaction->invoice;
    Mail::to($event->transaction->customer_email)
        ->send(new InvoiceMail($invoice->pdf_path));
});
```

### Can I change invoice due date?

Yes, update the invoice after creation:

```php
$invoice->update(['due_date' => now()->addDays(30)]);
```

## Security

### How is data encrypted?

Sensitive fields (`customer_email`, `customer_phone`) are encrypted using Laravel's encryption.

Your `APP_KEY` is used for encryption. Never change it after storing data, or encrypted data won't decrypt.

### How do I prevent fraud?

The package provides several tools:

1. **Rate limiting** - Limit requests per IP/merchant/user
2. **Blacklist** - Block suspicious IPs/emails
3. **VPN/Proxy detection** - Identify risky connections
4. **Risk scoring** - Calculate fraud likelihood
5. **Metadata collection** - Analyze device fingerprints, geolocation

### What data is collected?

Request metadata includes:
- IP address, user agent, browser, OS
- Device type and fingerprint
- Geolocation (country, city, coordinates)
- VPN/Proxy detection
- Risk score calculation

Disable collection if not needed:

```env
SISP_COLLECT_METADATA=false
```

### Can I block VPN/Proxy users?

Yes:

```env
SISP_DETECT_VPN=true
SISP_BLOCK_VPN_PROXY=true
```

When enabled, VPN/proxy requests return HTTP 403.

### How are callbacks verified?

SISP signs every callback with a cryptographic signature. The package:
1. Validates the signature on every callback
2. Rejects unsigned/tampered requests
3. Prevents status spoofing

### Can I rate limit per customer?

Yes, check by email:

```php
$action->handle(
    limitType: 'user',
    identifier: $email,
);
```

## Performance & Scaling

### Does the package support caching?

Yes, geolocation lookups are cached for 24 hours by default.

Configure cache in `config/cache.php` to use Redis for production.

### Can I queue invoice generation?

Yes, invoice generation is deferred. Set up a queue worker:

```bash
php artisan queue:work
```

### What's the maximum transaction size?

No limit enforced by the package. SISP may have limits - check with them.

### Can I handle high volume?

Yes, the package is optimized for high-volume payments:

1. Database indexes on transaction lookup fields
2. Deferred processing for non-blocking operations
3. Caching for geolocation and rate limits
4. Queue support for background jobs

For peak load, ensure:
- Adequate database connections
- Queue worker running
- Cache backend configured (Redis recommended)
- PHP memory limit sufficient for PDF generation

## Inertia & Frontend

### Can I use Inertia.js?

Yes, enable Inertia mode:

```env
SISP_USE_INERTIA=true
SISP_USE_BLADE=false
```

Then install Inertia for React or Vue:

```bash
npm install @inertiajs/react
```

### Can I use a custom payment component?

Yes, specify your component:

```env
SISP_INERTIA_PAYMENT_COMPONENT=my-payment-form
SISP_INERTIA_CALLBACK_COMPONENT=my-payment-response
```

### Can I customize the payment form styling?

With Blade, modify the published views in `resources/views/vendor/sisp/`.

With Inertia, create your own component and pass it in configuration.

## Database

### Can I use PostgreSQL?

Yes, the package supports both MySQL and PostgreSQL.

### Can I customize table names?

Yes, configure in `.env`:

```env
SISP_TABLE_TRANSACTIONS=my_transactions
SISP_TABLE_INVOICES=my_invoices
```

### How do I backup transaction data?

Use Laravel's backup tools or database backups. Include these tables:
- `sisp_transactions`
- `sisp_transaction_items`
- `sisp_invoices`
- `sisp_request_metadata`

### Can I export transactions?

Yes, query and export manually:

```php
$transactions = Transaction::where('status', 'completed')
    ->with('items', 'invoice')
    ->get();

return Excel::download(
    new TransactionsExport($transactions),
    'transactions.xlsx'
);
```

## Support & Licensing

### Is this package free?

Check the LICENSE file in the repository for terms.

### How do I report a bug?

Open an issue on GitHub: https://github.com/akira-projects/laravel-sisp/issues

### Can I contribute?

Yes, pull requests are welcome. Follow the contribution guidelines in the repository.

### Where's the source code?

Available on GitHub: https://github.com/akira-projects/laravel-sisp

### Can I use this in production?

Yes, when you have valid SISP credentials. Always test in sandbox mode first.

### What Laravel versions are supported?

Laravel 12+

### What PHP versions are supported?

PHP 8.4+

## Still Have Questions?

- Check the [Troubleshooting](./09-troubleshooting.md) guide
- Review [Examples](./08-examples.md) for code samples
- Read the full [Documentation](./README.md)