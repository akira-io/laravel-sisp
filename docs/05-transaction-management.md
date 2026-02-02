# Transaction Management

Work with transactions after they are created.

## Access Transactions

```php
use Akira\Sisp\Models\Transaction;

// Find transaction
$transaction = Transaction::find($id);

// Get latest transaction
$transaction = Transaction::latest()->first();

// Filter by status
$completed = Transaction::where('status', 'completed')->get();
$failed = Transaction::where('status', 'failed')->get();
$pending = Transaction::where('status', 'pending')->get();
```

## Transaction Attributes

Access transaction data:

```php
$transaction->merchant_ref;        // Unique reference ID
$transaction->amount;              // Amount in cents
$transaction->status;              // pending/completed/failed/cancelled/refunded
$transaction->customer_email;      // Customer email
$transaction->customer_name;       // Customer name
$transaction->customer_phone;      // Customer phone
$transaction->customer_country;    // Customer country
$transaction->customer_city;       // Customer city
$transaction->customer_address;    // Customer address
$transaction->locale;              // Transaction locale (pt, en, etc.)
$transaction->transaction_id;      // SISP transaction ID
$transaction->response_code;       // SISP response code
$transaction->merchant_response;   // SISP response message
$transaction->cancelled_at;        // Cancel timestamp
$transaction->refunded_at;         // Refund timestamp
$transaction->created_at;          // Created timestamp
$transaction->updated_at;          // Updated timestamp
```

## Formatted Amount

Get formatted amount in CVE:

```php
echo $transaction->formatted_amount;  // "1000,00 ECV"
```

## Transaction Items

Access line items:

```php
// Get all items
$items = $transaction->items;

// Iterate items
foreach ($transaction->items as $item) {
    echo $item->product_name;      // Item name
    echo $item->quantity;          // Quantity
    echo $item->unit_price;        // Unit price (automatic conversion from cents)
    echo $item->total_price;       // Total price (automatic conversion from cents)
    echo $item->description;       // Item description
    echo $item->metadata;          // Item metadata (array)
}
```

## Invoice

Access generated invoice:

```php
$invoice = $transaction->invoice;

if ($invoice) {
    echo $invoice->pdf_path;       // Path to PDF file
    echo $invoice->status;         // pending/issued/paid/overdue/cancelled
}
```

## Cancel Transaction

Cancel a pending or failed transaction:

```php
use Akira\Sisp\Actions\CancelTransactionAction;

$action = app(CancelTransactionAction::class);

try {
    $transaction = $action->handle(
        transaction: $transaction,
        reason: 'user_cancelled'  // Optional reason
    );
    echo "Transaction cancelled";
} catch (LogicException $e) {
    echo "Cannot cancel: " . $e->getMessage();
}
```

### Cancellation Rules

Can be cancelled:
- `pending` - Awaiting SISP response
- `failed` - Payment failed

Cannot be cancelled:
- `completed` - Payment successful
- `cancelled` - Already cancelled

### Cancel via Route

GET `/sisp/cancel`:

```php
// From your application
GET /sisp/cancel?transaction_id=uuid-here&reason=user_cancelled
```

Dispatches `TransactionCancelled` event.

## Refund Transaction

Refund a completed or partially refunded transaction:

```php
use Akira\Sisp\Actions\RefundTransactionAction;

$action = app(RefundTransactionAction::class);

try {
    $transaction = $action->handle(
        transaction: $transaction,
        refundAmount: 500.00,      // Amount to refund
        reason: 'customer_request' // Optional reason
    );
    echo "Refunded " . $transaction->formatted_amount;
} catch (LogicException $e) {
    echo "Cannot refund: " . $e->getMessage();
}
```

### Refund Rules

Can be refunded:
- `completed` - Full or partial refund
- `partially_refunded` - Additional refunds

Cannot be refunded:
- `pending` - Waiting for payment
- `failed` - Payment failed
- `cancelled` - Transaction cancelled
- `refunded` - Already fully refunded

### Refund Amount Rules

- Must be greater than 0
- Cannot exceed transaction amount
- Full refund changes status to `refunded`
- Partial refund changes status to `partially_refunded`

### Refund via Route

POST to `/sisp/refund/{transaction}`:

```php
POST /sisp/refund/{transaction}
{
    "amount": 500.00
}
```

Dispatches `TransactionRefunded` event.

The refund route middleware is configurable via `config/sisp.php`:

```php
'middleware' => [
    'refund' => ['web', 'auth'],
],
```

## Check Transaction Status

```php
$transaction->status->value;  // 'completed' (string)

// Check if completed
if ($transaction->status === \Akira\Sisp\Enums\TransactionStatus::completed) {
    // Handle completed payment
}

// Check if failed
if ($transaction->status === \Akira\Sisp\Enums\TransactionStatus::failed) {
    // Handle failed payment
}

// Check status value
match ($transaction->status->value) {
    'pending' => /* Handle pending */,
    'completed' => /* Handle completed */,
    'failed' => /* Handle failed */,
    'cancelled' => /* Handle cancelled */,
    'refunded' => /* Handle refunded */,
};
```

## Query Transactions

```php
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Enums\TransactionStatus;

// By customer
$transactions = Transaction::where('customer_email', 'user@example.com')->get();

// By locale
$portugueseTransactions = Transaction::where('locale', 'pt')->get();
$englishTransactions = Transaction::where('locale', 'en')->get();

// By status
$completed = Transaction::where('status', TransactionStatus::completed)->get();

// Date range
$recent = Transaction::whereBetween('created_at', [$start, $end])->get();

// With items
$transactions = Transaction::with('items')->get();

// With invoice
$transactions = Transaction::with('invoice')->get();

// Paginated
$transactions = Transaction::paginate(15);
```

## Decrypt Encrypted Fields

Some fields are encrypted (email, phone, address). Access them normally - decryption is automatic:

```php
echo $transaction->customer_email;  // Automatically decrypted
echo $transaction->customer_phone;  // Automatically decrypted
```

## Events

Listen to transaction events:

```php
use Akira\Sisp\Events\TransactionCancelled;
use Akira\Sisp\Events\TransactionRefunded;
use Illuminate\Support\Facades\Event;

Event::listen(TransactionCancelled::class, function (TransactionCancelled $event) {
    // Handle cancellation
    $transaction = $event->transaction;
    $reason = $event->reason;
});

Event::listen(TransactionRefunded::class, function (TransactionRefunded $event) {
    // Handle refund
    $transaction = $event->transaction;
    $refundAmount = $event->refundAmount;
    $reason = $event->reason;
});
```

## Next Steps

- [Invoice Generation](./06-invoice-generation.md) - Work with invoices
- [Security](./07-security.md) - Rate limiting and security

**Previous:** [Payment Flow](04-payment-flow.md) | **Next:** [Invoice Generation](06-invoice-generation.md)
