# Transaction Creation Pattern

Guide to the CreateAndStorePaymentTransactionAction pattern for atomic transaction handling.

## Overview

The `CreateAndStorePaymentTransactionAction` encapsulates the creation and storage of payment transactions with items in
a single atomic database transaction.

## Problem It Solves

### Without the Pattern

```php
<?php

$transaction = DB::transaction(function () use ($paymentRequest, $request) {
    $transaction = $this->storeTransaction->handle($paymentRequest);

    $itemsData = TransactionItemData::collection($request->array('items'));

    $this->storeItems->handle($transaction, ...$itemsData);

    return $transaction;
});
```

Issues:

- Boilerplate database transaction code in controller
- Manual collection of items from request
- Callback closure is hard to test
- Logic is mixed with controller concerns

### With the Pattern

```php
<?php

$transaction = $this->createTransaction->handle($paymentRequest, $request);
```

Benefits:

- Clean, single method call
- Transaction logic isolated
- Easy to test independently
- Controller focuses on flow, not implementation

## Implementation

### CreateAndStorePaymentTransactionAction

**Location:** `src/Actions/CreateAndStorePaymentTransactionAction.php`

```php
<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;use Akira\Sisp\ValueObjects\PaymentRequest;use Illuminate\Http\Request;use Illuminate\Support\Facades\DB;use Throwable;

final readonly class CreateAndStorePaymentTransactionAction
{
    public function __construct(
        private StorePaymentTransactionAction $storeTransaction,
        private StoreTransactionItemsAction $storeItems,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PaymentRequest $paymentRequest, Request $request): Transaction
    {
        return DB::transaction(function () use ($paymentRequest, $request) {
            $transaction = $this->storeTransaction->handle($paymentRequest);

            $itemsData = $this->getItemsData($request);

            $this->storeItems->handle($transaction, ...$itemsData);

            return $transaction;
        });
    }

    private function getItemsData(Request $request): array
    {
        return \Akira\Sisp\ValueObjects\TransactionItemData::collection(
            $request->array('items')
        );
    }
}
```

## Usage in Controllers

### PaymentController

**Location:** `src/Http/Controllers/PaymentController.php`

```php
<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\Actions\PreparePaymentAction;

final readonly class PaymentController
{
    public function __construct(
        private PreparePaymentAction $preparePayment,
        private CreateAndStorePaymentTransactionAction $createTransaction,
    ) {}

    public function __invoke(StorePaymentRequest $request)
    {
        $requestData = PaymentRequestData::from($request->validated());
        $paymentRequest = $this->preparePayment->handle($requestData);

        // Single action call handles all transaction logic atomically
        $transaction = $this->createTransaction->handle($paymentRequest, $request);

        return $transaction;
    }
}
```

## Key Features

### 1. Atomic Transaction Guarantee

All operations complete or rollback together:

```php
DB::transaction(function () {
    // If anything fails here, entire transaction rolls back
    $this->storeTransaction->handle($paymentRequest);
    $this->storeItems->handle($transaction, ...$itemsData);
});
```

### 2. Automatic Items Collection

Extracts items from request and converts to ValueObjects:

```php
private function getItemsData(Request $request): array
{
    return TransactionItemData::collection($request->array('items'));
}
```

Request format expected:

```json
{
    "items": [
        {
            "product_id": "abc123",
            "product_name": "Widget",
            "quantity": 2,
            "unit_price": 50.00,
            "total_price": 100.00
        }
    ]
}
```

### 3. Type Safety

- `PaymentRequest` value object ensures validated data
- `Request` object provides type hints
- Returns `Transaction` model instance

### 4. Error Propagation

Throws exceptions naturally:

```php
/**
 * @throws Throwable
 */
public function handle(PaymentRequest $paymentRequest, Request $request): Transaction
{
    // Any exception thrown here will rollback the transaction
}
```

Use in controller:

```php
try {
    $transaction = $this->createTransaction->handle($paymentRequest, $request);
} catch (Throwable $e) {
    return response()->json(['error' => $e->getMessage()], 400);
}
```

## Testing

### Unit Test Example

```php
<?php

namespace Tests\Feature;

use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;use Akira\Sisp\Actions\StorePaymentTransactionAction;use Akira\Sisp\Actions\StoreTransactionItemsAction;use Akira\Sisp\Models\Transaction;use Akira\Sisp\ValueObjects\PaymentRequest;use Illuminate\Foundation\Testing\RefreshDatabase;use Tests\TestCase;

final class CreateAndStorePaymentTransactionActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_transaction_with_items(): void
    {
        $action = app(CreateAndStorePaymentTransactionAction::class);

        $paymentRequest = new PaymentRequest(
            merchantRef: 'REF001',
            merchantSession: 'SESSION001',
            amount: 100.00,
            currency: '132',
            // ... other fields
        );

        $request = $this->createMockRequest([
            'items' => [
                [
                    'product_id' => 'prod1',
                    'product_name' => 'Widget',
                    'quantity' => 2,
                    'unit_price' => 50.00,
                    'total_price' => 100.00,
                ],
            ],
        ]);

        $transaction = $action->handle($paymentRequest, $request);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertCount(1, $transaction->items);
        $this->assertEquals('prod1', $transaction->items[0]->product_id);
    }

    public function test_rolls_back_on_error(): void
    {
        $storeTransaction = $this->mock(StorePaymentTransactionAction::class);
        $storeTransaction->shouldReceive('handle')->andThrow(new \Exception('Database error'));

        $storeItems = $this->mock(StoreTransactionItemsAction::class);

        $action = new CreateAndStorePaymentTransactionAction($storeTransaction, $storeItems);

        $this->expectException(\Exception::class);

        $action->handle($paymentRequest, $request);
    }

    private function createMockRequest(array $data): \Illuminate\Http\Request
    {
        $request = $this->mock(\Illuminate\Http\Request::class);
        $request->shouldReceive('array')->with('items')->andReturn($data['items']);
        return $request;
    }
}
```

## Best Practices

### 1. Use for Multi-step Operations

Use this pattern when multiple steps must succeed together:

```php
// Good: All related operations in one transaction
$transaction = $this->createTransaction->handle($paymentRequest, $request);

// Bad: Separate transactions could leave inconsistent state
$transaction = $this->storeTransaction->handle($paymentRequest);
$this->storeItems->handle($transaction, ...$itemsData); // What if this fails?
```

### 2. Keep Item Collection Logic Encapsulated

Don't expose request-to-ValueObject conversion:

```php
// Good: Hidden in action
private function getItemsData(Request $request): array { }

// Bad: In controller
$itemsData = TransactionItemData::collection($request->array('items'));
```

### 3. Use Value Objects for Data Transfer

Ensures validated data:

```php
public function handle(PaymentRequest $paymentRequest, Request $request): Transaction
{
    // paymentRequest is guaranteed to be valid
    // request items are automatically converted to ValueObjects
}
```

### 4. Handle Exceptions in Controllers

Let the action throw, catch in controller:

```php
try {
    $transaction = $this->createTransaction->handle($paymentRequest, $request);
} catch (Throwable $e) {
    Log::error('Transaction creation failed', ['error' => $e->getMessage()]);
    return response()->json(['error' => 'Failed to create transaction'], 500);
}
```

## Related Patterns

### Compared to Service Class

**Service (Anti-pattern for this package):**

```php
class PaymentService {
    public function createTransaction() { }
    public function storeItems() { }
    public function updateInventory() { }
}
```

**Action (Preferred):**

```php
class CreateAndStorePaymentTransactionAction {
    public function handle() { }
}

class UpdateInventoryAction {
    public function handle() { }
}
```

### Compared to Repository Pattern

**Repository:**

```php
$transaction = $this->transactionRepository->create($data);
$this->transactionRepository->addItems($transaction, $items);
```

**Action:**

```php
$transaction = $this->createTransaction->handle($paymentRequest, $request);
```

## See Also

- [Architecture & Design Patterns](./architecture.md) - Action pattern and design principles
- [E-Commerce Transactions](./e-commerce-transactions.md) - Complete transaction examples
- [API Reference](./api-reference.md) - Complete API documentation
- [Middleware & Security](./middleware-and-security.md) - Security and validation
- [Payment Flow](./payment-flow.md) - Payment flow overview
- [Configuration Reference](./configuration.md) - Configuration options
