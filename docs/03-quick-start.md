# Quick Start Guide

## Available Routes

The package registers these routes automatically:

- `POST /sisp/payment` - Submit payment
- `GET|POST /sisp/callback` - SISP callback handler
- `POST /sisp/cancel` - Cancel transaction
- `POST /sisp/refund` - Refund transaction
- `GET|POST /sisp/sandbox` - Sandbox testing

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

- `amount` - Total amount (numeric, min 0.01)
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
- `items[*][product_id]`
- `items[*][description]`
- `items[*][metadata]`

## What Happens

1. User submits form to `/sisp/payment`
2. Package validates data
3. Creates transaction in database
4. Renders SISP payment form
5. User redirected to SISP gateway
6. After payment, redirected to `/sisp/callback`
7. Package validates and stores response
8. Transaction status updated (approved/failed)

## Test in Sandbox

With `SISP_SANDBOX=true`, payments are tested without hitting real SISP.

Visit the form, submit, and you'll see the fake gateway.

## Check Transaction

```bash
php artisan tinker
>>> use Akira\Sisp\Models\Transaction;
>>> Transaction::latest()->first();
```

## Next Steps

- [Payment Flow](./04-payment-flow.md) - Complete flow diagram
- [Transaction Management](./05-transaction-management.md) - Advanced features
- [Invoice Generation](./06-invoice-generation.md) - Auto-generate PDFs