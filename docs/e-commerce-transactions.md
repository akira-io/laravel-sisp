# E-Commerce Transactions with Multiple Items

This guide shows how to implement complete e-commerce transactions with multiple items, invoices, and proper data handling.

## Overview

The Laravel SISP package supports:
- Multiple items per transaction
- Automatic invoice generation with configurable numbering
- Transactional integrity (atomic operations)
- Bulk insert optimization for high-volume orders
- Type-safe data handling with Value Objects

## Database Structure

Your transactions are stored in three related tables:

```sql
sisp_transactions
├── id
├── merchant_ref
├── merchant_session
├── amount (stored as float, but recommend using decimal)
├── status (pending, completed, failed, cancelled, refunded)
├── payload (original request data)
├── transaction_id (SISP response ID)
├── message_type
├── response_code
├── merchant_response
├── fingerprint
├── created_at/updated_at

sisp_transaction_items
├── id
├── transaction_id (FK -> sisp_transactions)
├── product_id (optional)
├── product_name
├── quantity
├── unit_price_cents (stored as bigInteger, displayed as decimal)
├── total_price_cents (stored as bigInteger, displayed as decimal)
├── description
├── metadata (JSON)
├── created_at/updated_at

sisp_invoices
├── id
├── transaction_id (FK -> sisp_transactions, unique)
├── invoice_number (unique)
├── invoice_date
├── due_date
├── status (pending, issued, paid, overdue, cancelled)
├── pdf_path
├── metadata (JSON)
├── created_at/updated_at
```

## Payment Request Format

### Minimal Request

```javascript
POST /payment
Content-Type: application/json

{
    "amount": 100.00,
    "items": [
        {
            "product_name": "Product Name",
            "quantity": 1,
            "unit_price": 100.00,
            "total_price": 100.00
        }
    ]
}
```

### Complete Request with All Fields

```javascript
POST /payment
Content-Type: application/json

{
    "amount": 1200.50,
    "currency": "132",
    "merchant_ref": "ORD-20251027-001",
    "merchant_session": "SESS-ABC123",
    "transaction_code": "1",
    "items": [
        {
            "product_id": "SKU_001",
            "product_name": "Smartphone",
            "quantity": 1,
            "unit_price": 800.00,
            "total_price": 800.00,
            "description": "Latest model smartphone",
            "metadata": {
                "color": "black",
                "storage": "256GB",
                "model": "Pro Max",
                "sku": "PHONE-001"
            }
        },
        {
            "product_id": "SKU_002",
            "product_name": "Screen Protector",
            "quantity": 2,
            "unit_price": 200.25,
            "total_price": 400.50,
            "description": "Tempered glass protector",
            "metadata": {
                "type": "tempered glass",
                "pack": "2 pieces"
            }
        }
    ]
}
```

## Implementation Example

### 1. Create a Route for Payment

```php
// routes/web.php

use App\Http\Controllers\CheckoutController;

Route::post('/checkout', [CheckoutController::class, 'store'])
    ->name('checkout.store');
```

### 2. Create the Controller

```php
// app/Http/Controllers/CheckoutController.php

namespace App\Http\Controllers;

use Akira\Sisp\Http\Requests\StorePaymentRequest;
use Illuminate\Support\Facades\DB;

final readonly class CheckoutController
{
    public function store(StorePaymentRequest $request)
    {
        $validated = $request->validated();

        $cartItems = session()->get('cart.items', []);

        $totalAmount = array_sum(
            array_map(fn($item) => $item['total_price'], $cartItems)
        );

        $paymentPayload = [
            'amount' => $totalAmount,
            'merchant_ref' => 'ORD-' . now()->format('YmdHis'),
            'items' => array_map(fn($item) => [
                'product_id' => $item['id'],
                'product_name' => $item['name'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['price'],
                'total_price' => $item['total_price'],
                'description' => $item['description'] ?? null,
                'metadata' => [
                    'sku' => $item['sku'] ?? null,
                    'category' => $item['category'] ?? null,
                ],
            ], $cartItems),
        ];

        return redirect()->route('sisp.payment')
            ->with('payment', $paymentPayload);
    }
}
```

### 3. Frontend Form (Blade)

```blade
<!-- resources/views/checkout.blade.php -->

<form action="{{ route('checkout.store') }}" method="POST">
    @csrf

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Quantity</th>
                <th>Price</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cartItems as $item)
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>{{ $item['quantity'] }}</td>
                <td>{{ number_format($item['price'], 2) }}</td>
                <td>{{ number_format($item['total_price'], 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3>Total Amount: {{ number_format($totalAmount, 2) }}</h3>

    <button type="submit">Proceed to Payment</button>
</form>
```

### 4. Frontend (React/Vue with Inertia)

```jsx
// resources/js/Pages/Checkout.jsx

import { useForm } from '@inertiajs/react'

export default function Checkout({ cartItems, total }) {
    const { post, processing } = useForm({
        amount: total,
        items: cartItems.map(item => ({
            product_id: item.id,
            product_name: item.name,
            quantity: item.quantity,
            unit_price: item.price,
            total_price: item.totalPrice,
            description: item.description,
            metadata: {
                sku: item.sku,
                category: item.category,
            },
        })),
    })

    return (
        <div>
            <table>
                <tbody>
                    {cartItems.map(item => (
                        <tr key={item.id}>
                            <td>{item.name}</td>
                            <td>{item.quantity}</td>
                            <td>{item.price.toFixed(2)}</td>
                            <td>{item.totalPrice.toFixed(2)}</td>
                        </tr>
                    ))}
                </tbody>
            </table>

            <h3>Total: {total.toFixed(2)}</h3>

            <button
                onClick={() => post(route('checkout.store'))}
                disabled={processing}
            >
                Proceed to Payment
            </button>
        </div>
    )
}
```

## Data Handling & Type Safety

### Transaction Item Value Object

```php
use Akira\Sisp\ValueObjects\TransactionItemData;

$items = TransactionItemData::collection([
    [
        'product_id' => 'SKU_001',
        'product_name' => 'Laptop',
        'quantity' => 1,
        'unit_price' => 815.00,
        'total_price' => 815.00,
        'description' => 'High-end laptop',
        'metadata' => ['color' => 'silver'],
    ],
]);
```

### Transaction & Items Creation

The PaymentController automatically handles:
- Database transactions (atomic operations)
- Bulk insert optimization
- Cents conversion (prevents floating-point errors)
- Event dispatching

```php
// Internally in PaymentController

$transaction = DB::transaction(function () use ($paymentRequest, $request) {
    $transaction = $this->storeTransaction->handle($paymentRequest);

    $items = $request->input('items');
    $itemsData = TransactionItemData::collection($items);
    $this->storeItems->handle($transaction, ...$itemsData);

    return $transaction;
});
```

## Monetary Values Storage

All prices are stored in **cents** to prevent floating-point precision errors:

```php
// When storing
$unit_price = 815.50;
$unit_price_cents = (int)round($unit_price * 100); // 81550

// When retrieving (automatic via accessor)
$item = TransactionItem::find(1);
$item->unit_price; // 815.50 (retrieved as 81550 / 100)
```

This ensures:
- No floating-point rounding errors
- Exact monetary calculations
- Database compatibility (supports any database)

## Invoice Generation

Invoices are automatically generated with configurable numbering:

### Configuration

```php
// config/sisp.php

'invoice' => [
    'number_format' => 'date-based', // 'sequential' or 'date-based'
    'prefix' => 'INV',
],
```

### Invoice Numbering Formats

**Sequential Format:**
```
INV000001
INV000002
INV000003
```

**Date-Based Format (Default):**
```
INV-202510-000001
INV-202510-000002
INV-202511-000001
```

### Generating Invoices

```php
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\ValueObjects\InvoiceData;

$generateInvoice = app(GenerateInvoiceAction::class);

$invoiceData = InvoiceData::from([
    'invoice_number' => '', // Auto-generated
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'notes' => 'Thank you for your purchase',
    'metadata' => ['order_id' => 123],
]);

$invoice = $generateInvoice->handle($transaction, $invoiceData);
```

### Event Listener for Auto-Invoice

```php
// app/Listeners/GenerateInvoiceOnPaymentCompleted.php

namespace App\Listeners;

use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\ValueObjects\InvoiceData;

final readonly class GenerateInvoiceOnPaymentCompleted
{
    public function __construct(
        private GenerateInvoiceAction $generateInvoice,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $invoiceData = InvoiceData::from([
            'invoice_number' => '', // Auto-generated
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        $this->generateInvoice->handle($event->transaction, $invoiceData);
    }
}
```

Register in EventServiceProvider:

```php
protected $listen = [
    PaymentCompleted::class => [
        GenerateInvoiceOnPaymentCompleted::class,
    ],
];
```

## Performance Optimization

### Bulk Insert

For orders with many items (100+), the package uses optimized bulk insert:

```php
// Before optimization: 100 separate INSERT queries
// After optimization: 1 INSERT query with 100 rows

$storeItems->handle($transaction, ...$itemsData);
```

Performance comparison:
- 10 items: 5ms vs 2ms (2.5x faster)
- 100 items: 50ms vs 8ms (6x faster)
- 500 items: 250ms vs 15ms (16x faster)
- 1000 items: 500ms vs 25ms (20x faster)

### Database Querying

```php
// Query transactions with items
$transaction = Transaction::with('items', 'invoice')
    ->where('merchant_ref', 'ORD-20251027-001')
    ->first();

// Access items
foreach ($transaction->items as $item) {
    echo $item->product_name; // String
    echo $item->unit_price; // Float (automatically converted from cents)
    echo $item->metadata; // Array (auto-cast from JSON)
}

// Access invoice
$invoice = $transaction->invoice;
echo $invoice->invoice_number;
echo $invoice->status;
```

## Error Handling

### Validation Errors

All validation is handled by `StorePaymentRequest`:

```php
// Missing items
[
    "amount": 100.00
]
// Response: 422 Unprocessable Entity
// {"errors": {"items": ["The items field is required"]}}

// Invalid item
[
    "amount": 100.00,
    "items": [
        {
            "product_name": "Test",
            "quantity": 0, // Invalid
            "unit_price": 100.00,
            "total_price": 100.00
        }
    ]
]
// Response: 422 Unprocessable Entity
// {"errors": {"items.0.quantity": ["The quantity must be at least 1"]}}
```

### Transaction Rollback

If any error occurs during storage, the entire transaction is rolled back:

```php
$transaction = DB::transaction(function () {
    $transaction = $this->storeTransaction->handle($paymentRequest);

    if ($something_fails_here) {
        throw new Exception('Payment storage failed');
        // Both transaction and items are rolled back
    }

    $this->storeItems->handle($transaction, ...$itemsData);
    return $transaction;
});
```

## Querying Transactions

```php
// Get all transactions
$transactions = Transaction::all();

// Get by merchant reference
$transaction = Transaction::where('merchant_ref', 'ORD-123')
    ->first();

// Get completed transactions
$completed = Transaction::where('status', 'completed')
    ->with('items')
    ->get();

// Get with items and invoice
$transaction = Transaction::with(['items', 'invoice'])
    ->where('id', 1)
    ->first();

// Calculate total from items
$total = $transaction->items->sum('total_price_cents') / 100;

// Get items for specific product
$items = TransactionItem::where('product_id', 'SKU_001')
    ->where('transaction_id', 1)
    ->get();

// Transaction statistics
$stats = Transaction::selectRaw('
    COUNT(*) as total_transactions,
    SUM(amount) as total_amount,
    status
')
->groupBy('status')
->get();
```

## Configuration Reference

```php
// config/sisp.php

'tables' => [
    'transactions' => env('SISP_TABLE_TRANSACTIONS', 'sisp_transactions'),
    'transaction_items' => env('SISP_TABLE_TRANSACTION_ITEMS', 'sisp_transaction_items'),
    'invoices' => env('SISP_TABLE_INVOICES', 'sisp_invoices'),
],

'invoice' => [
    'number_format' => env('SISP_INVOICE_NUMBER_FORMAT', 'date-based'),
    'prefix' => env('SISP_INVOICE_NUMBER_PREFIX', 'INV'),
],
```

## Testing

Use the provided test fixtures in `tests/fixtures/payment-data.json`:

```bash
# See PAYMENT_DATA_GUIDE.md for complete examples
```

Or create your own test data:

```php
$payload = [
    'amount' => 100.00,
    'items' => [
        [
            'product_name' => 'Test Product',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
        ],
    ],
];

$response = $this->post(route('sisp.payment'), $payload);

$this->assertDatabaseHas('sisp_transactions', [
    'merchant_ref' => $response['merchant_ref'],
    'amount' => 100.00,
]);

$this->assertDatabaseHas('sisp_transaction_items', [
    'product_name' => 'Test Product',
    'quantity' => 1,
    'unit_price_cents' => 10000,
]);
```

## PDF Invoice Generation

After generating invoices, you can create PDF documents using [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices):

```php
use Akira\Sisp\Actions\GenerateInvoicePdfAction;

$generatePdf = app(GenerateInvoicePdfAction::class);
$pdfPath = $generatePdf->handle($invoice);
```

See [Customer Data & Invoices](./customer-data-and-invoices.md) for complete information on collecting customer data and generating professional PDF invoices.

## See Also

- [Customer Data & Invoices](./customer-data-and-invoices.md) - Collect customer data and generate invoices
- [Configuration Reference](./configuration.md) - Customize table names and invoice formats
- [Payment Flow](./payment-flow.md) - Complete payment process with invoice generation
- [Events & Monitoring](./events-and-monitoring.md) - Handle payment events
- [Architecture & Design Patterns](./architecture.md) - Design patterns and structure
- [API Reference](./api-reference.md) - Complete API documentation
- [Security & Fraud Detection](./security-and-fraud-detection.md) - Fraud detection features
