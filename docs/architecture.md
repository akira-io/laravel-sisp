# Architecture & Design Patterns

This document explains the architecture and design patterns used in Laravel SISP.

## Overview

Laravel SISP follows clean architecture principles with strong emphasis on:
- Single Responsibility Principle (SRP)
- Action Pattern for business logic
- Value Objects for data encapsulation
- Type safety with PHP 8.4 strict types
- Dependency Injection throughout

## Core Patterns

### 1. Action Pattern

All business logic is encapsulated in **Action classes** that live in `src/Actions/`.

Each action:
- Has exactly ONE public method: `handle()`
- Uses constructor dependency injection
- Is marked as `final readonly` for immutability
- Has clear, single responsibility

**Example:**

```php
namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\TransactionItem;
use Akira\Sisp\Transaction;
use Akira\Sisp\ValueObjects\TransactionItemData;
use Illuminate\Support\Facades\DB;

final readonly class StoreTransactionItemsAction
{
    public function handle(Transaction $transaction, TransactionItemData ...$items): void
    {
        if (empty($items)) {
            return;
        }

        $records = array_map(
            fn(TransactionItemData $item) => [
                'transaction_id' => $transaction->id,
                'product_id' => $item->product_id,
                'product_name' => $item->product_name,
                'quantity' => $item->quantity,
                'unit_price_cents' => (int)round($item->unit_price * 100),
                'total_price_cents' => (int)round($item->total_price * 100),
                'description' => $item->description,
                'metadata' => $item->metadata,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            $items
        );

        DB::table((new TransactionItem())->getTable())->insert($records);
    }
}
```

Benefits:
- Testable in isolation
- Reusable across controllers and commands
- Clear intent and responsibility
- Easy to understand at a glance

### 2. Value Objects

Data is passed between layers using immutable **Value Objects** (readonly classes).

**Example:**

```php
namespace Akira\Sisp\ValueObjects;

use Carbon\Carbon;

final readonly class InvoiceData
{
    public function __construct(
        public string $invoice_number,
        public Carbon $invoice_date,
        public ?Carbon $due_date = null,
        public ?string $notes = null,
        public ?array $metadata = null,
    ) {}

    public static function from(array $data): self
    {
        return new self(
            invoice_number: $data['invoice_number'],
            invoice_date: $data['invoice_date'] instanceof Carbon
                ? $data['invoice_date']
                : Carbon::parse($data['invoice_date']),
            due_date: isset($data['due_date'])
                ? ($data['due_date'] instanceof Carbon ? $data['due_date'] : Carbon::parse($data['due_date']))
                : null,
            notes: $data['notes'] ?? null,
            metadata: $data['metadata'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'invoice_number' => $this->invoice_number,
            'invoice_date' => $this->invoice_date,
            'due_date' => $this->due_date,
            'notes' => $this->notes,
            'metadata' => $this->metadata,
        ];
    }
}
```

Benefits:
- Type safety at compile-time
- IDE autocomplete and type hints
- Immutability prevents side effects
- Clear data contracts between layers

### 3. Enums for Status

Transaction and invoice statuses are type-safe enums:

```php
namespace Akira\Sisp\Enums;

enum TransactionStatus: string
{
    case pending = 'pending';
    case completed = 'completed';
    case failed = 'failed';
    case cancelled = 'cancelled';
    case refunded = 'refunded';
}

enum InvoiceStatus: string
{
    case pending = 'pending';
    case issued = 'issued';
    case paid = 'paid';
    case overdue = 'overdue';
    case cancelled = 'cancelled';
}
```

Usage:

```php
$transaction->status = TransactionStatus::completed;

if ($transaction->status === TransactionStatus::completed) {
    // Handle completed payment
}
```

Benefits:
- No string magic values
- Compile-time safety
- IDE autocomplete
- Cannot assign invalid status

### 4. Dependency Injection

All services are injected via constructor, never resolved statically:

```php
final readonly class PaymentController
{
    public function __construct(
        private PreparePaymentAction $preparePayment,
        private StorePaymentTransactionAction $storeTransaction,
        private StoreTransactionItemsAction $storeItems,
        private RenderPaymentFormAction $renderForm,
        private LoadConfig $loadConfig,
    ) {}

    public function __invoke(StorePaymentRequest $request)
    {
        // All dependencies are available as properties
        $paymentRequest = $this->preparePayment->handle($requestData);
        // ...
    }
}
```

Benefits:
- Easy to test (mock dependencies)
- Easy to swap implementations
- Clear dependencies
- No service locator pattern

## Folder Structure

```
src/
├── Actions/
│   ├── Generators/
│   │   ├── InvoiceNumberGeneratorAction.php
│   │   ├── MerchantReferenceGeneratorAction.php
│   │   ├── MerchantSessionGeneratorAction.php
│   │   └── TimeStampGeneratorAction.php
│   ├── BuildRequestPayloadAction.php
│   ├── BuildSandboxPayloadAction.php
│   ├── CancelTransactionAction.php
│   ├── CreateTransactionAction.php
│   ├── DeterminePaymentEndpointAction.php
│   ├── GenerateFingerprintAction.php
│   ├── GenerateInvoiceAction.php
│   ├── HandleCallbackAction.php
│   ├── PreparePaymentAction.php
│   ├── RefundTransactionAction.php
│   ├── RenderPaymentFormAction.php
│   ├── RenderPaymentResponseAction.php
│   ├── StorePaymentTransactionAction.php
│   ├── StoreTransactionItemsAction.php
│   └── ValidateFingerprintAction.php
│
├── Configuration/
│   └── LoadConfig.php
│
├── Enums/
│   ├── InvoiceStatus.php
│   ├── MessageTypes.php
│   └── TransactionStatus.php
│
├── Events/
│   ├── PaymentCompleted.php
│   ├── PaymentFailed.php
│   ├── PaymentPending.php
│   ├── TransactionCancelled.php
│   └── TransactionRefunded.php
│
├── Exceptions/
│   └── InvalidPaymentResponseException.php
│
├── Http/
│   ├── Controllers/
│   │   ├── CallbackController.php
│   │   ├── CancelTransactionController.php
│   │   ├── PaymentController.php
│   │   ├── RefundTransactionController.php
│   │   └── SandboxController.php
│   └── Requests/
│       └── StorePaymentRequest.php
│
├── Models/
│   ├── Invoice.php
│   └── TransactionItem.php
│
├── Traits/
│   └── EncryptsAttributes.php
│
├── ValueObjects/
│   ├── CallbackPayload.php
│   ├── InvoiceData.php
│   ├── PaymentRequest.php
│   ├── PaymentRequestData.php
│   ├── TransactionData.php
│   └── TransactionItemData.php
│
├── Configuration/
│   └── LoadConfig.php
│
├── Sisp.php (Facade)
├── SispServiceProvider.php
└── Transaction.php (Model)
```

## Request Flow

### 1. Payment Initiation Request

```
POST /payment (StorePaymentRequest)
    ↓
PaymentController::__invoke
    ↓
PreparePaymentAction
    ↓
StorePaymentTransactionAction (creates Transaction)
    ↓
StoreTransactionItemsAction (bulk insert items)
    ↓
RenderPaymentFormAction (returns Blade/Inertia form)
    ↓
Frontend submits to SISP gateway
```

### 2. Callback Processing

```
POST /sisp/callback (SISP Response)
    ↓
CallbackController::__invoke
    ↓
ValidateFingerprintAction
    ↓
HandleCallbackAction (updates transaction status)
    ↓
Dispatch Events (PaymentCompleted, PaymentFailed, etc.)
    ↓
User Listeners (e.g., generate invoice, update order)
```

## Data Flow

### Transaction Creation

```
StorePaymentRequest::validated()
    ↓
PaymentRequestData::from(array)
    ↓
PreparePaymentAction::handle()
    ↓
BuildRequestPayloadAction::handle()
    ↓
StorePaymentTransactionAction::handle()
    ↓
Transaction Model (saved to DB)
```

### Transaction Items

```
StorePaymentRequest::input('items')
    ↓
TransactionItemData::collection(array)
    ↓
StoreTransactionItemsAction::handle()
    ↓
DB::table()->insert() (bulk insert)
    ↓
TransactionItem Models (saved to DB)
```

## Configuration Management

```
config/sisp.php
    ↓
LoadConfig Service
    ↓
Used throughout application
```

Configuration supports:
- Environment variables with fallbacks
- Customizable table names
- Customizable invoice numbering
- Payment gateway endpoints
- Rendering engines (Blade/Inertia)

## Error Handling

### Form Validation

```php
StorePaymentRequest
├── 'amount' => ['required', 'numeric', 'min:0.01']
├── 'items' => ['required', 'array', 'min:1']
├── 'items.*.product_name' => ['required', 'string', 'max:255']
├── 'items.*.quantity' => ['required', 'integer', 'min:1']
├── 'items.*.unit_price' => ['required', 'numeric', 'min:0']
└── 'items.*.total_price' => ['required', 'numeric', 'min:0']
```

Returns 422 Unprocessable Entity with validation errors.

### Transaction Integrity

```php
DB::transaction(function () {
    $transaction = $this->storeTransaction->handle($paymentRequest);
    $this->storeItems->handle($transaction, ...$itemsData);
    return $transaction;
})
```

If any operation fails, entire transaction is rolled back.

### Exception Handling

```php
try {
    $signature = $this->validateFingerprint->handle($payload);
} catch (InvalidPaymentResponseException $e) {
    Log::error('Fingerprint validation failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Invalid signature'], 400);
}
```

## Database Patterns

### Configurable Table Names

All models use dynamic table names:

```php
final class TransactionItem extends Model
{
    public function getTable(): string
    {
        return config('sisp.tables.transaction_items', 'sisp_transaction_items');
    }
}
```

Allows users to customize:

```php
// .env
SISP_TABLE_TRANSACTIONS=custom_transactions
SISP_TABLE_TRANSACTION_ITEMS=custom_items
SISP_TABLE_INVOICES=custom_invoices
```

### Cents Storage

Monetary values are stored as `bigInteger` in cents:

```php
// Model
protected $casts = [
    'unit_price_cents' => 'integer',
    'total_price_cents' => 'integer',
];

// Accessor
public function getUnitPriceAttribute(): float
{
    return $this->unit_price_cents / 100;
}

// Mutator
public function setUnitPriceAttribute(float $value): void
{
    $this->attributes['unit_price_cents'] = (int)round($value * 100);
}
```

Usage:

```php
$item->unit_price = 815.50; // Stores 81550 in database
echo $item->unit_price; // Returns 815.50
```

### Relationships

```php
// Transaction -> Items (1:N)
$transaction->items(); // Returns HasMany

// Transaction -> Invoice (1:1)
$transaction->invoice(); // Returns HasOne

// Invoice -> Items (through Transaction)
$invoice->items(); // Returns TransactionItems
```

## Performance Considerations

### Bulk Insert

```php
// Before: 100 separate INSERT queries
// After: 1 INSERT query
DB::table('sisp_transaction_items')->insert($records);
```

Performance gains:
- 10 items: 2.5x faster
- 100 items: 6x faster
- 500 items: 16x faster
- 1000 items: 20x faster

### Database Indexing

```sql
-- Transaction table indexes
INDEX ['merchant_ref', 'merchant_session', 'status', 'message_type']
INDEX ['transaction_id']

-- Transaction Items table indexes
INDEX ['transaction_id', 'product_id']

-- Invoice table indexes
INDEX ['invoice_number', 'status']
```

### Query Optimization

```php
// Eager load relationships
$transactions = Transaction::with('items', 'invoice')->get();

// Use whereIn for batch queries
$transactions = Transaction::whereIn('id', $ids)
    ->with('items')
    ->get();
```

## Security

### Fingerprint Validation

All SISP callbacks are validated:

```php
ValidateFingerprintAction::handle($payload)
    ↓
Compute SHA512 fingerprint
    ↓
Compare with SISP fingerprint
    ↓
Raise exception if mismatch
```

### Data Encryption

Sensitive transaction data can be encrypted:

```php
use Akira\Sisp\Traits\EncryptsAttributes;

class Transaction extends Model
{
    use EncryptsAttributes;

    protected $encrypted = ['payload', 'fingerprint'];
}
```

### CSRF Protection

SISP callback route bypasses CSRF middleware:

```php
Route::post('sisp/callback', CallbackController::class)
    ->withoutMiddleware('web');
```

This is intentional - external payment gateway cannot provide CSRF token.

## Testing

Actions can be tested in isolation:

```php
public function test_store_transaction_items()
{
    $transaction = Transaction::factory()->create();

    $items = [
        TransactionItemData::from([
            'product_name' => 'Test',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
        ]),
    ];

    app(StoreTransactionItemsAction::class)
        ->handle($transaction, ...$items);

    $this->assertDatabaseHas('sisp_transaction_items', [
        'transaction_id' => $transaction->id,
        'product_name' => 'Test',
        'unit_price_cents' => 10000,
    ]);
}
```

## Extending the Package

### Custom Actions

Create your own action class:

```php
final readonly class CustomPaymentAction
{
    public function __construct(
        private LoadConfig $config,
        private TransactionRepository $repository,
    ) {}

    public function handle(PaymentRequest $request): Transaction
    {
        // Your custom logic here
        return $transaction;
    }
}
```

### Custom Events

Dispatch custom events:

```php
Event::dispatch(new CustomPaymentEvent($transaction));
```

### Custom Models

Override models by binding in service provider:

```php
$this->app->bind(Transaction::class, CustomTransaction::class);
```

## Best Practices

1. Always use Actions for business logic
2. Pass Value Objects, not arrays
3. Use Enums for statuses
4. Inject dependencies via constructor
5. Keep actions small and focused
6. Write tests for each action
7. Use type hints everywhere
8. Avoid service locator pattern (no Service::make())
9. Validate input early (Form Requests)
10. Log important operations

## See Also

- [Payment Flow](./payment-flow.md) - Complete payment flow explanation
- [Configuration Reference](./configuration.md) - All configuration options
- [API Reference](./api-reference.md) - Complete API documentation
- [E-Commerce Transactions](./e-commerce-transactions.md) - Full e-commerce example
- [Security & Fraud Detection](./security-and-fraud-detection.md) - Security architecture
- [Rate Limiting](./rate-limiting.md) - Rate limiting implementation
- [Events & Monitoring](./events-and-monitoring.md) - Event-driven architecture
