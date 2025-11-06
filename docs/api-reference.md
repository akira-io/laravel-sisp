# API Reference

Complete reference for all classes, methods, and configurations.

## Actions

### GenerateInvoiceAction

Generates invoices with configurable numbering.

```php
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\ValueObjects\InvoiceData;

$action = app(GenerateInvoiceAction::class);

$invoiceData = InvoiceData::from([
    'invoice_number' => '', // Auto-generated
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'notes' => 'Thank you for your purchase',
    'metadata' => ['order_id' => 123],
]);

$invoice = $action->handle($transaction, $invoiceData);
```

**Signature:**

```php
public function handle(Transaction $transaction, InvoiceData $invoiceData): Invoice
```

**Returns:** `Akira\Sisp\Models\Invoice`

**Throws:** `InvalidArgumentException` if invoice_number is invalid

---

### StoreTransactionItemsAction

Stores multiple transaction items with bulk insert optimization.

```php
use Akira\Sisp\Actions\StoreTransactionItemsAction;
use Akira\Sisp\ValueObjects\TransactionItemData;

$action = app(StoreTransactionItemsAction::class);

$items = [
    TransactionItemData::from([
        'product_name' => 'Laptop',
        'quantity' => 1,
        'unit_price' => 815.00,
        'total_price' => 815.00,
    ]),
];

$action->handle($transaction, ...$items);
```

**Signature:**

```php
public function handle(Transaction $transaction, TransactionItemData ...$items): void
```

**Returns:** void

**Performance:** O(1) query for any number of items (bulk insert)

---

### PreparePaymentAction

Prepares a payment request with all required SISP fields.

```php
use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

$action = app(PreparePaymentAction::class);

$requestData = PaymentRequestData::from([
    'amount' => 815.00,
]);

$paymentRequest = $action->handle($requestData);
```

**Signature:**

```php
public function handle(PaymentRequestData $data): PaymentRequest
```

**Returns:** `Akira\Sisp\ValueObjects\PaymentRequest`

---

### ValidateFingerprintAction

Validates SISP callback fingerprint (SHA512).

```php
use Akira\Sisp\Actions\ValidateFingerprintAction;

$action = app(ValidateFingerprintAction::class);

$signature = $action->handle($callbackPayload);
```

**Signature:**

```php
public function handle(array $payload): string
```

**Returns:** `string` (computed fingerprint)

**Throws:** `InvalidPaymentResponseException` if signature doesn't match

---

### HandleCallbackAction

Processes SISP callback and updates transaction status.

```php
use Akira\Sisp\Actions\HandleCallbackAction;
use Akira\Sisp\ValueObjects\CallbackPayload;

$action = app(HandleCallbackAction::class);

$callbackPayload = CallbackPayload::from($payload);

$action->handle($transaction, $callbackPayload);
```

**Signature:**

```php
public function handle(Transaction $transaction, CallbackPayload $callbackPayload): void
```

**Returns:** void

**Side Effects:** Updates transaction, dispatches events

---

## Generators

### InvoiceNumberGeneratorAction

Generates invoice numbers with configurable format.

```php
use Akira\Sisp\Actions\Generators\InvoiceNumberGeneratorAction;

$action = app(InvoiceNumberGeneratorAction::class);

$invoiceNumber = $action->handle($transaction);
```

**Signature:**

```php
public function handle(Transaction $transaction): string
```

**Returns:** `string` (e.g., "INV-202510-000001")

**Formats:**

- `sequential`: INV000001, INV000002, etc.
- `date-based`: INV-202510-000001, INV-202510-000002, etc.

---

## Value Objects

### PaymentRequestData

Immutable data structure for payment requests.

```php
use Akira\Sisp\ValueObjects\PaymentRequestData;

$data = PaymentRequestData::from([
    'amount' => 815.00,
    'currency' => '132',
    'merchant_ref' => 'ORD-123',
    'merchant_session' => 'SESS-ABC',
    'transaction_code' => '1',
]);

echo $data->amount; // 815.00
echo $data->currency; // '132'
```

**Properties:**

- `amount: float` - Transaction amount (required)
- `currency: string` - ISO 4217 code (default: '132')
- `merchant_ref: string` - Merchant reference (auto-generated if null)
- `merchant_session: string` - Session ID (auto-generated if null)
- `transaction_code: string` - Transaction type (default: '1')

---

### TransactionItemData

Immutable data structure for transaction items.

```php
use Akira\Sisp\ValueObjects\TransactionItemData;

$item = TransactionItemData::from([
    'product_id' => 'SKU_001',
    'product_name' => 'Laptop',
    'quantity' => 1,
    'unit_price' => 815.00,
    'total_price' => 815.00,
    'description' => 'High-end laptop',
    'metadata' => ['color' => 'silver'],
]);

// Create collection
$items = TransactionItemData::collection([
    ['product_name' => 'Item 1', ...],
    ['product_name' => 'Item 2', ...],
]);
```

**Properties:**

- `product_id: string` - Product SKU (optional)
- `product_name: string` - Product name (required)
- `quantity: int` - Quantity (required, min: 1)
- `unit_price: float` - Unit price (required)
- `total_price: float` - Total price (required)
- `description: string` - Item description (optional)
- `metadata: array` - Additional data (optional)

**Methods:**

- `from(array $data): self` - Create from array
- `collection(array $items): array` - Create collection

---

### InvoiceData

Immutable data structure for invoices.

```php
use Akira\Sisp\ValueObjects\InvoiceData;

$invoiceData = InvoiceData::from([
    'invoice_number' => 'INV-202510-000001',
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'notes' => 'Thank you',
    'metadata' => ['order_id' => 123],
]);
```

**Properties:**

- `invoice_number: string` - Invoice number (required)
- `invoice_date: Carbon` - Invoice date (required)
- `due_date: Carbon` - Due date (optional)
- `notes: string` - Notes (optional)
- `metadata: array` - Additional data (optional)

**Methods:**

- `from(array $data): self` - Create from array
- `toArray(): array` - Convert to array

---

### PaymentRequest

Immutable payment request with SISP fields.

```php
use Akira\Sisp\ValueObjects\PaymentRequest;

$paymentRequest = new PaymentRequest(
    url: 'https://mc.vinti4net.cv/...',
    method: 'POST',
    fields: [...],
    merchant_ref: 'R20251027121530',
    merchant_session: 'S20251027121530',
    fingerprint: 'abc123def456...',
    payload: [...],
);

echo $paymentRequest->url;
```

**Properties:**

- `url: string` - SISP gateway URL
- `method: string` - HTTP method (POST)
- `fields: array` - Form fields
- `merchant_ref: string` - Merchant reference
- `merchant_session: string` - Session ID
- `fingerprint: string` - SHA512 fingerprint
- `payload: array` - Original request data

---

### CallbackPayload

Immutable SISP callback data.

```php
use Akira\Sisp\ValueObjects\CallbackPayload;

$callbackPayload = CallbackPayload::from($payload);

echo $callbackPayload->transaction_id;
echo $callbackPayload->message_type;
echo $callbackPayload->merchant_response;
```

**Properties:**

- `merchant_ref: string` - Merchant reference
- `merchant_session: string` - Session ID
- `transaction_id: string` - SISP transaction ID
- `message_type: string` - Message type (8 = success)
- `merchant_response: string` - Response code
- `response_code: string` - Additional code (optional)

---

## Models

### Transaction

```php
use Akira\Sisp\Models\Transaction;

$transaction = Transaction::find(1);

echo $transaction->merchant_ref;
echo $transaction->amount;
echo $transaction->status; // TransactionStatus enum
echo $transaction->payload; // JSON array
echo $transaction->created_at; // Carbon instance

// Relations
$items = $transaction->items(); // HasMany
$invoice = $transaction->invoice(); // HasOne
```

**Properties:**

- `id: int` - Primary key
- `merchant_ref: string` - Merchant reference
- `merchant_session: string` - Session ID
- `amount: float` - Transaction amount
- `currency: string` - Currency code
- `status: TransactionStatus` - Transaction status (enum)
- `transaction_code: string` - Transaction type
- `transaction_id: string` - SISP transaction ID
- `message_type: string` - Message type
- `response_code: string` - Response code
- `merchant_response: string` - Response status
- `fingerprint: string` - SHA512 fingerprint
- `payload: array` - Request/callback data
- `cancelled_at: datetime` - Cancellation time
- `refunded_at: datetime` - Refund time
- `created_at: datetime` - Creation time
- `updated_at: datetime` - Update time

**Methods:**

- `items(): HasMany` - Get transaction items
- `invoice(): HasOne` - Get associated invoice
- `getTable(): string` - Get table name (configurable)

**Scopes:**

```php
Transaction::completed()->get(); // status = completed
Transaction::failed()->get(); // status = failed
Transaction::pending()->get(); // status = pending
Transaction::cancelled()->get(); // status = cancelled
```

---

### TransactionItem

```php
use Akira\Sisp\Models\TransactionItem;

$item = TransactionItem::find(1);

echo $item->product_name;
echo $item->quantity;
echo $item->unit_price; // 815.50 (auto-converted from cents)
echo $item->total_price; // 815.50 (auto-converted from cents)
echo $item->metadata; // Array (auto-cast from JSON)

// Relation
$transaction = $item->transaction(); // BelongsTo
```

**Properties:**

- `id: int` - Primary key
- `transaction_id: int` - Foreign key
- `product_id: string` - Product SKU
- `product_name: string` - Product name
- `quantity: int` - Quantity
- `unit_price: float` - Unit price (accessor converts from cents)
- `unit_price_cents: int` - Unit price in cents (storage)
- `total_price: float` - Total price (accessor converts from cents)
- `total_price_cents: int` - Total price in cents (storage)
- `description: string` - Description
- `metadata: array` - Additional data (auto-cast from JSON)
- `created_at: datetime` - Creation time
- `updated_at: datetime` - Update time

**Methods:**

- `transaction(): BelongsTo` - Get parent transaction
- `getTable(): string` - Get table name (configurable)

---

### Invoice

```php
use Akira\Sisp\Models\Invoice;

$invoice = Invoice::find(1);

echo $invoice->invoice_number;
echo $invoice->invoice_date;
echo $invoice->status; // InvoiceStatus enum

// Relation
$transaction = $invoice->transaction(); // BelongsTo
$items = $invoice->items(); // HasMany (through transaction)
```

**Properties:**

- `id: int` - Primary key
- `transaction_id: int` - Foreign key (unique)
- `invoice_number: string` - Invoice number (unique)
- `invoice_date: date` - Invoice date
- `due_date: date` - Due date
- `status: InvoiceStatus` - Invoice status (enum)
- `notes: string` - Notes
- `pdf_path: string` - PDF file path
- `metadata: array` - Additional data (auto-cast from JSON)
- `created_at: datetime` - Creation time
- `updated_at: datetime` - Update time

**Methods:**

- `transaction(): BelongsTo` - Get transaction
- `items(): HasMany` - Get transaction items
- `getTable(): string` - Get table name (configurable)

**Scopes:**

```php
Invoice::pending()->get(); // status = pending
Invoice::issued()->get(); // status = issued
Invoice::paid()->get(); // status = paid
Invoice::overdue()->get(); // status = overdue
```

---

## Enums

### TransactionStatus

```php
use Akira\Sisp\Enums\TransactionStatus;

TransactionStatus::pending;    // "pending"
TransactionStatus::completed;  // "completed"
TransactionStatus::failed;     // "failed"
TransactionStatus::cancelled;  // "cancelled"
TransactionStatus::refunded;   // "refunded"

// Usage
if ($transaction->status === TransactionStatus::completed) {
    // Handle completion
}

$transaction->status = TransactionStatus::refunded;
```

---

### InvoiceStatus

```php
use Akira\Sisp\Enums\InvoiceStatus;

InvoiceStatus::pending;   // "pending"
InvoiceStatus::issued;    // "issued"
InvoiceStatus::paid;      // "paid"
InvoiceStatus::overdue;   // "overdue"
InvoiceStatus::cancelled; // "cancelled"
```

---

## Events

### PaymentCompleted

Dispatched when payment succeeds.

```php
use Akira\Sisp\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $transaction = $event->transaction;
    // Handle successful payment
});
```

**Properties:**

- `transaction: Transaction` - The completed transaction

---

### PaymentFailed

Dispatched when payment fails.

```php
use Akira\Sisp\Events\PaymentFailed;

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $transaction = $event->transaction;
    // Handle failed payment
});
```

---

### PaymentPending

Dispatched when payment is pending.

```php
use Akira\Sisp\Events\PaymentPending;

Event::listen(PaymentPending::class, function (PaymentPending $event) {
    $transaction = $event->transaction;
    // Handle pending payment
});
```

---

### TransactionCancelled

Dispatched when transaction is cancelled.

```php
use Akira\Sisp\Events\TransactionCancelled;

Event::listen(TransactionCancelled::class, function (TransactionCancelled $event) {
    $transaction = $event->transaction;
    // Handle cancellation
});
```

---

### TransactionRefunded

Dispatched when transaction is refunded.

```php
use Akira\Sisp\Events\TransactionRefunded;

Event::listen(TransactionRefunded::class, function (TransactionRefunded $event) {
    $transaction = $event->transaction;
    // Handle refund
});
```

---

## Configuration

### File: config/sisp.php

```php
return [
    'url' => env('SISP_URL'),
    'posID' => env('SISP_POS_ID'),
    'posAutCode' => env('SISP_POS_AUT_CODE'),
    'currency' => env('SISP_CURRENCY', '132'),
    'language_messages' => env('SISP_LANGUAGE_MESSAGES', 'EN'),
    'fingerprint_version' => env('SISP_FINGERPRINT_VERSION', '1'),
    'url_merchant_response' => env('SISP_URL_MERCHANT_RESPONSE'),
    'is_3dsec' => env('SISP_IS_3D_SEC', '0'),
    'transaction_code' => env('SISP_DEFAULT_TRANSACTION_CODE', '1'),
    'merchantId' => env('SISP_MERCHANT_ID'),
    'sandbox' => env('SISP_SANDBOX', false),

    'tables' => [
        'transactions' => env('SISP_TABLE_TRANSACTIONS', 'sisp_transactions'),
        'transaction_items' => env('SISP_TABLE_TRANSACTION_ITEMS', 'sisp_transaction_items'),
        'invoices' => env('SISP_TABLE_INVOICES', 'sisp_invoices'),
    ],

    'use_blade' => [
        'enabled' => env('SISP_USE_BLADE', true),
        'payment_form' => 'sisp::payment-form',
        'payment_response' => 'sisp::payment-response',
    ],

    'use_inertia' => [
        'enabled' => env('SISP_USE_INERTIA', false),
        'payment_form_component' => env('SISP_INERTIA_PAYMENT_COMPONENT', 'Sisp/PaymentForm'),
        'payment_response_component' => env('SISP_INERTIA_CALLBACK_COMPONENT', 'Sisp/PaymentResponse'),
    ],

    'invoice' => [
        'number_format' => env('SISP_INVOICE_NUMBER_FORMAT', 'date-based'),
        'prefix' => env('SISP_INVOICE_NUMBER_PREFIX', 'INV'),
    ],
];
```

---

## Additional Resources

### Rate Limiting API

For controlling payment frequency and preventing abuse:

```php
use Akira\Sisp\Actions\CheckRateLimitAction;

$action = app(CheckRateLimitAction::class);
$action->handle($request);
```

See [Rate Limiting Guide](./rate-limiting.md) for detailed rate limiting documentation.

### Security Actions

For fraud detection and security:

```php
use Akira\Sisp\Actions\StoreRequestMetadataAction;

$action = app(StoreRequestMetadataAction::class);
$action->handle($transaction, $request);
```

See [Security & Fraud Detection](./security-and-fraud-detection.md) for security features.

### PDF Invoice Generation

Invoices can be generated automatically after payment:

```php
use Akira\Sisp\Actions\GenerateInvoicePdfAction;

$action = app(GenerateInvoicePdfAction::class);
$action->handle($invoice);
```

Requires [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) package.

## See Also

- [Architecture & Design Patterns](./architecture.md) - System architecture and design patterns
- [Payment Flow](./payment-flow.md) - Complete payment flow explanation
- [Configuration Reference](./configuration.md) - All configuration options
- [E-Commerce Transactions](./e-commerce-transactions.md) - Complete transaction example
- [Security & Fraud Detection](./security-and-fraud-detection.md) - Fraud detection features
- [Rate Limiting](./rate-limiting.md) - Rate limiting system
- [Events & Monitoring](./events-and-monitoring.md) - Event-driven architecture
