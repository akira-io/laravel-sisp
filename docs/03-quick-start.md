# Quick Start Guide

## Available Routes

The package registers these routes automatically:

- `POST /sisp/payment` - Submit payment
- `GET|POST /sisp/callback` - SISP callback handler
- `POST /sisp/retry-payment` - Retry a failed payment
- `GET /sisp/cancel` - Cancel transaction
- `POST /sisp/refund/{transaction}` - Refund transaction
- `GET|POST /sisp/sandbox` - Sandbox testing
- `GET /sisp/countries` - List countries (ISO codes + flags)

## Payment Form

Create a form that POSTs to `POST /sisp/payment`:

```blade
<form action="{{ route('sisp.payment') }}" method="POST">
    @csrf

    <input type="number" name="amount" required>

    <input type="text" name="items[0][product_name]" required>
    <input type="number" name="items[0][quantity]" required>
    <input type="number" name="items[0][unit_price]" required>
    <input type="number" name="items[0][total_price]" required>

    <input type="email" name="customer_email">

    <button type="submit">Pay</button>
</form>
```

## Required Fields

- `amount` - Total amount (numeric, min 1)
- `items` - Array of items (at least 1)
- `items[*][product_name]` - Item name
- `items[*][quantity]` - Quantity (integer)
- `items[*][unit_price]` - Unit price
- `items[*][total_price]` - Total price

## Optional Fields

- `customer_name`
- `customer_email`
- `customer_phone`
- `customer_country`
- `customer_city`
- `customer_address`
- `customer_postal_code`
- `locale` - Customer's language preference (pt, en) - defaults to 'pt'
- `items[*][product_id]`
- `items[*][description]`
- `items[*][metadata]`

If `SISP_IS_3D_SEC=1`, these fields become required:
- `customer_email`
- `customer_country`
- `customer_city`
- `customer_address`
- `customer_postal_code`

## What Happens

1. User submits form to `/sisp/payment`
2. Package validates data
3. The payment pipeline runs: blacklist check → rate limits → request building → transaction persistence → metadata capture (see [Payment Flow](./04-payment-flow.md))
4. Renders SISP payment form
5. User redirected to SISP gateway (resolved by the active driver: production or sandbox)
6. After payment, redirected to `/sisp/callback`
7. The callback pipeline validates the fingerprint and the transaction details
8. Transaction status updated (approved/failed) and events dispatched

## Programmatic Payments (Builder)

Instead of the HTTP form, you can build a payment request in code with the fluent builder:

```php
use Akira\Sisp\Facades\Sisp;

$paymentRequest = Sisp::payment()
    ->amount(1500.0)
    ->currency('132')
    ->customerEmail('buyer@example.cv')
    ->locale('pt')
    ->build(); // returns a signed PaymentRequest value object
```

## Test in Sandbox

With `SISP_SANDBOX=true`, payments are tested without hitting real SISP.

Visit the form, submit, and you'll see the fake gateway.

## Check Transaction

```bash
php artisan tinker
>>> use Akira\Sisp\Models\Transaction;
>>> Transaction::latest()->first();
```

## Countries List

```bash
GET /sisp/countries
```

Returns a JSON list of countries with alpha-2 code, numeric code, name, and flag URL.

## Next Steps

- [Payment Flow](./04-payment-flow.md) - Complete flow diagram
- [Transaction Management](./05-transaction-management.md) - Advanced features
- [Invoice Generation](./06-invoice-generation.md) - Auto-generate PDFs

**Previous:** [Configuration](02-configuration.md) | **Next:** [Payment Flow](04-payment-flow.md)
