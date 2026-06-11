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
$transaction->amount;              // Decimal amount in CVE
$transaction->amount_cents;        // Canonical integer amount in cents
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

`amount` remains a decimal CVE value for backward compatibility. `amount_cents` is the canonical integer storage value used to avoid money precision drift before the public API is stabilized.

### Breaking Change Note

Before `1.0.0`, applications should treat `amount_cents` as the stable storage representation and `amount` as the compatibility accessor. A future major release may expose only integer minor-unit amounts in public APIs. Migration path: read and write decimal CVE through `amount` while also persisting `amount_cents`, then switch integrations that need exact arithmetic to `amount_cents`.

After upgrading to the version that introduces `amount_cents`, publish or run the package migrations so existing rows are backfilled from `amount`.

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

## Transaction Change History

Every transaction update is recorded in `sisp_transaction_logs`.

```php
$logs = $transaction->logs()->latest()->get();

foreach ($logs as $log) {
    echo $log->source;
    dump($log->changed_attributes);
    dump($log->old_values);
    dump($log->new_values);
}
```

Each log stores:

- `transaction_id` - Transaction that changed
- `source` - Flow that changed the transaction, such as `callback`, `refund`, `cancel`, `retry`, `reconciliation`, `customer-data`, or `model`
- `changed_attributes` - Changed column names
- `old_values` - Values before the update
- `new_values` - Values after the update
- `created_at` - When the change was recorded

Timestamp-only updates are ignored. Encrypted payload values are stored in decrypted array form so the history remains inspectable.

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

## Status Reconciliation

Use SISP status reconciliation when a transaction stays `pending` because the automatic callback was not received after the SISP timeout window.

### Manual Query

Check the status in SISP without changing the local transaction:

```bash
php artisan sisp:transaction-status R20260523235959
```

The command prints:

- `Result` - whether the SISP status API request itself succeeded
- `Payment` - local interpretation: `completed`, `failed`, or `pending`
- `Description` - SISP status description such as `C-SUCESSO` or `E-ERRO`
- `Message` - SISP diagnostic message

### Manual Update

Update a local transaction only after a successful status API response:

```bash
php artisan sisp:transaction-status --transaction=123 --update
```

The update rules are:

- `result=false`: no local update
- `result=true` and `transactionSuccess=true`: status becomes `completed`
- `result=true` and `transactionSuccess=false`: status becomes `failed`

### Public Status API

Use the `Sisp` facade to query or reconcile a specific transaction from application code:

```php
use Akira\Sisp\Facades\Sisp;

$response = Sisp::queryTransactionStatus($transaction);
$response = Sisp::queryTransactionStatus($transaction->merchant_ref);

$updatedTransaction = Sisp::reconcileTransactionStatus($transaction);
```

`queryTransactionStatus()` returns `TransactionStatusResponse` and never writes to the database. `reconcileTransactionStatus()` returns a `Transaction` and only updates pending transactions when the SISP status API returns `result=true`.

Since v2 the status query is routed through the active gateway driver. You can also call it on a specific driver directly:

```php
$response = Sisp::driver('production')->queryTransactionStatus($transaction);
```

For multi-merchant flows, scope the call with explicit credentials:

```php
$sisp = Sisp::forCredentials($credentials);

$response = $sisp->queryTransactionStatus($transaction);
$updatedTransaction = $sisp->reconcileTransactionStatus($transaction);
```

Use the command for manual operations or scheduled monitoring. Use the facade API when the application already has a specific transaction and needs an immediate check.

### Automatic Scheduled Reconciliation

Enable the feature:

```env
SISP_TRANSACTION_RECONCILIATION_ENABLED=true
SISP_TRANSACTION_RECONCILE_AFTER_MINUTES=5
SISP_TRANSACTION_RECONCILE_LIMIT=50
```

Schedule the command in your application:

```php
$schedule->command('sisp:reconcile-pending')->everyFiveMinutes();
```

The command reconciles only old indeterminate transactions:

- status is `pending`
- `message_type` is `null`
- creation time is older than the configured threshold

## Refund Transaction

Refund a completed transaction with the fluent builder (v2):

```php
use Akira\Sisp\Facades\Sisp;

try {
    // Full refund
    $transaction = Sisp::refund($transaction)
        ->full()
        ->reason('customer_request')
        ->process();

    // Partial refund
    $transaction = Sisp::refund($transaction)
        ->amount(500.00)
        ->reason('partial_return')
        ->process();

    echo "Refunded " . $transaction->formatted_amount;
} catch (LogicException $e) {
    echo "Cannot refund: " . $e->getMessage();
}
```

The underlying action remains available when you prefer direct invocation:

```php
use Akira\Sisp\Actions\RefundTransactionAction;

$transaction = app(RefundTransactionAction::class)->handle(
    transaction: $transaction,
    refundAmount: 500.00,
    reason: 'customer_request'
);
```

### Refund Rules

Can be refunded:
- `completed` - Total reversal or partial refund

Cannot be refunded:
- `pending` - Waiting for payment
- `failed` - Payment failed
- `cancelled` - Transaction cancelled
- `refunded` - Already refunded

### Refund Amount Rules

- Must be greater than 0
- Must not exceed the refundable balance known by the package
- Total reversal uses `transactionCode = 4`
- Partial refund uses `transactionCode = 8`
- Refund history lookup uses `transactionCode = 9` and `amount = 0`
- Refund and history requests use `reversal = R`
- Refund operations use the dedicated refund FingerPrint with version `2`
- Successful full refunds preserve the original transaction amount and change status to `refunded`
- Successful partial refunds preserve the transaction as `completed` until the known refunded balance reaches the original amount
- For refunds on a different day, SISP may require enough daily purchase liquidity to cover the refunded amount
- For DCC transactions, refund in the original transaction currency

The original SISP callback must provide `merchantRespCP` and `merchantRespTid`; these are used as `clearingPeriod` and `transactionID` in refund requests.

Build a refund history request without changing local transaction status:

```php
use Akira\Sisp\Actions\BuildRefundRequestAction;

$request = app(BuildRefundRequestAction::class)->history($transaction);
$payload = $request->toArray();
```

Sandbox certification should validate SISP test cases 29-31 for total reversal, 32-34 for partial refund, and 35 for refund history. Confirm final accounting in the daily VBVT reconciliation file.

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
