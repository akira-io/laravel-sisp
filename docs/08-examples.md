# Examples

Common patterns and use cases for the SISP payment package.

## Listen to Payment Events

Handle payment success, failure, and other transaction events.

### Payment Completed

```php
use Akira\Sisp\Events\PaymentCompleted;
use Illuminate\Support\Facades\Event;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $transaction = $event->transaction;
    $payload = $event->payload;

    // Send confirmation email
    Mail::to($transaction->customer_email)
        ->send(new PaymentConfirmationMail($transaction));

    // Update order status
    Order::where('transaction_id', $transaction->id)
        ->update(['status' => 'paid']);

    // Log successful payment
    Log::info('Payment completed', [
        'transaction_id' => $transaction->id,
        'amount' => $transaction->formatted_amount,
    ]);
});
```

### Payment Failed

```php
use Akira\Sisp\Events\PaymentFailed;

Event::listen(PaymentFailed::class, function (PaymentFailed $event) {
    $transaction = $event->transaction;

    // Notify customer
    Mail::to($transaction->customer_email)
        ->send(new PaymentFailedMail($transaction));

    // Mark order as failed
    Order::where('transaction_id', $transaction->id)
        ->update(['status' => 'failed']);

    // Log failure
    Log::warning('Payment failed', [
        'transaction_id' => $transaction->id,
        'reason' => $transaction->merchant_response,
    ]);
});
```

### Transaction Cancelled

```php
use Akira\Sisp\Events\TransactionCancelled;

Event::listen(TransactionCancelled::class, function (TransactionCancelled $event) {
    $transaction = $event->transaction;
    $reason = $event->reason;

    // Restore inventory
    foreach ($transaction->items as $item) {
        Product::where('id', $item->product_id)
            ->increment('stock', $item->quantity);
    }

    Log::info('Transaction cancelled', [
        'transaction_id' => $transaction->id,
        'reason' => $reason,
    ]);
});
```

### Transaction Refunded

```php
use Akira\Sisp\Events\TransactionRefunded;

Event::listen(TransactionRefunded::class, function (TransactionRefunded $event) {
    $transaction = $event->transaction;
    $refundAmount = $event->refundAmount;
    $reason = $event->reason;

    // Process refund in your system
    Refund::create([
        'transaction_id' => $transaction->id,
        'amount' => $refundAmount,
        'reason' => $reason,
        'processed_at' => now(),
    ]);

    // Notify customer
    Mail::to($transaction->customer_email)
        ->send(new RefundMail($transaction, $refundAmount));
});
```

## Custom Payment Form

Create a branded payment form in your application.

### Blade Template

```blade
<form action="{{ route('sisp.payment') }}" method="POST">
    @csrf

    <div class="form-group">
        <label>Amount (ECV)</label>
        <input
            type="number"
            name="amount"
            placeholder="0.00"
            step="0.01"
            required
        >
    </div>

    <div class="form-group">
        <label>Product Name</label>
        <input
            type="text"
            name="items[0][product_name]"
            placeholder="Product name"
            required
        >
    </div>

    <div class="form-group">
        <label>Quantity</label>
        <input
            type="number"
            name="items[0][quantity]"
            value="1"
            required
        >
    </div>

    <div class="form-group">
        <label>Unit Price</label>
        <input
            type="number"
            name="items[0][unit_price]"
            placeholder="0.00"
            step="0.01"
            required
        >
    </div>

    <div class="form-group">
        <label>Total Price</label>
        <input
            type="number"
            name="items[0][total_price]"
            placeholder="0.00"
            step="0.01"
            required
        >
    </div>

    <div class="form-group">
        <label>Email (Optional)</label>
        <input
            type="email"
            name="customer_email"
            placeholder="customer@example.com"
        >
    </div>

    <button type="submit">Pay Now</button>
</form>
```

## Multiple Line Items

Include multiple products in a single payment.

```blade
<form action="{{ route('sisp.payment') }}" method="POST">
    @csrf

    <input type="hidden" name="amount" value="150.00">

    <!-- Item 1 -->
    <input type="hidden" name="items[0][product_name]" value="Widget">
    <input type="hidden" name="items[0][quantity]" value="2">
    <input type="hidden" name="items[0][unit_price]" value="50.00">
    <input type="hidden" name="items[0][total_price]" value="100.00">

    <!-- Item 2 -->
    <input type="hidden" name="items[1][product_name]" value="Gadget">
    <input type="hidden" name="items[1][quantity]" value="1">
    <input type="hidden" name="items[1][unit_price]" value="50.00">
    <input type="hidden" name="items[1][total_price]" value="50.00">

    <button type="submit">Pay 150.00 ECV</button>
</form>
```

## Programmatic Payment Creation

Create and process payments programmatically without a form.

```php
use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;
use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Http\Request;

$action = app(CreateAndStorePaymentTransactionAction::class);

// Create a fake request object with payment data
$request = new Request([
    'amount' => 100.00,
    'items' => [
        [
            'product_name' => 'Service',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
        ]
    ],
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
]);

// Create transaction
$transaction = $action->handle(
    PaymentRequestData::from($request->all()),
    $request
);

echo "Transaction created: " . $transaction->id;
```

## Query Transactions for Reporting

Generate reports and analytics from payment data.

```php
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Enums\TransactionStatus;
use Carbon\Carbon;

// Total revenue this month
$revenue = Transaction::where('status', TransactionStatus::completed)
    ->whereBetween('created_at', [
        Carbon::now()->startOfMonth(),
        Carbon::now()->endOfMonth(),
    ])
    ->sum('amount'); // in cents

echo 'Revenue: ' . ($revenue / 100) . ' ECV';

// Failed transactions
$failed = Transaction::where('status', TransactionStatus::failed)
    ->count();

// Average transaction amount
$average = Transaction::where('status', TransactionStatus::completed)
    ->avg('amount') / 100;

// Transactions by customer
$byCustomer = Transaction::where('customer_email', 'user@example.com')
    ->paginate(20);

// Transactions with high risk score
$risky = Transaction::with('metadata')
    ->whereHas('metadata', fn ($q) => $q->where('risk_score', '>=', 70))
    ->get();

// Export to CSV
$transactions = Transaction::where('status', TransactionStatus::completed)
    ->with(['items', 'invoice'])
    ->get();

return response()->stream(
    fn () => $transactions->each(fn ($t) => echo $t->toJson()),
    200,
    ['Content-Type' => 'application/json']
);
```

## Access Invoice Data in Event

Get invoice information when payment completes.

```php
use Akira\Sisp\Events\PaymentCompleted;

Event::listen(PaymentCompleted::class, function (PaymentCompleted $event) {
    $transaction = $event->transaction;
    $invoice = $transaction->invoice;

    if ($invoice) {
        echo "Invoice Number: " . $invoice->invoice_number;
        echo "PDF Path: " . $invoice->pdf_path;
        echo "Status: " . $invoice->status->value;

        // Attach PDF to email
        Mail::to($transaction->customer_email)
            ->send(
                new InvoiceMail($transaction, $invoice->pdf_path)
            );
    }
});
```

## Custom Rate Limiting

Implement custom rate limit logic for specific scenarios.

```php
use Akira\Sisp\Actions\CheckRateLimitAction;

$action = app(CheckRateLimitAction::class);

try {
    // Check custom rate limit: max 10 payments per user per day
    $action->handle(
        limitType: 'user',
        identifier: auth()->user()->email,
        context: 'daily_limit',
        limit: 10,
        windowSeconds: 86400  // 24 hours
    );

    // If we get here, limit not exceeded
    // Process payment...

} catch (\Akira\Sisp\Exceptions\RateLimitExceededException $e) {
    return response()->json([
        'error' => 'Too many payment attempts today. Please try again tomorrow.',
    ], 429);
}
```

## Block Suspicious Activity

Add IPs or emails to blacklist when suspicious activity is detected.

```php
use Akira\Sisp\Actions\CheckBlacklistAction;
use Akira\Sisp\Models\RequestMetadata;

$action = app(CheckBlacklistAction::class);

// Monitor failed transactions
$failedCount = Transaction::where('customer_email', $email)
    ->where('status', TransactionStatus::failed)
    ->where('created_at', '>=', now()->subHour())
    ->count();

if ($failedCount >= 3) {
    // Block this email
    $action->add(
        type: 'email',
        value: $email,
        severity: 'high',
        reason: 'Multiple failed transactions',
        expiresInMinutes: 60
    );
}

// Monitor high-risk IPs
$riskMetadata = RequestMetadata::where('risk_score', '>=', 80)
    ->where('created_at', '>=', now()->subHour())
    ->get();

foreach ($riskMetadata as $metadata) {
    $action->add(
        type: 'ip',
        value: $metadata->ip_address,
        severity: 'medium',
        reason: 'High fraud risk score',
        expiresInMinutes: 1440  // 24 hours
    );
}
```

## Cancel Payment

Cancel a pending or failed transaction.

```php
use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Models\Transaction;

$transaction = Transaction::find($id);

$action = app(CancelTransactionAction::class);

try {
    $action->handle(
        transaction: $transaction,
        reason: 'user_cancelled'
    );

    return response()->json([
        'success' => true,
        'message' => 'Transaction cancelled successfully',
    ]);

} catch (LogicException $e) {
    return response()->json([
        'success' => false,
        'error' => $e->getMessage(),
    ], 400);
}
```

## Refund Payment

Refund a completed transaction.

```php
use Akira\Sisp\Actions\RefundTransactionAction;
use Akira\Sisp\Models\Transaction;

$transaction = Transaction::find($id);
$refundAmount = 50.00;

$action = app(RefundTransactionAction::class);

try {
    $action->handle(
        transaction: $transaction,
        refundAmount: $refundAmount,
        reason: 'customer_request'
    );

    return response()->json([
        'success' => true,
        'message' => "Refunded {$refundAmount} ECV",
    ]);

} catch (LogicException $e) {
    return response()->json([
        'success' => false,
        'error' => $e->getMessage(),
    ], 400);
}
```

## Monitor Transaction Metadata

Analyze payment risk and security metrics.

```php
use Akira\Sisp\Models\RequestMetadata;

// VPN/Proxy detection
$vpnUsers = RequestMetadata::where(function ($q) {
    $q->where('is_vpn', true)
        ->orWhere('is_proxy', true);
})->count();

// Mobile payments
$mobilePayments = RequestMetadata::where('is_mobile', true)->count();

// High-risk transactions
$highRisk = RequestMetadata::where('risk_score', '>=', 70)
    ->orderByDesc('risk_score')
    ->get();

foreach ($highRisk as $metadata) {
    echo "Risk: {$metadata->risk_score} - {$metadata->risk_reason}";
    echo "IP: {$metadata->ip_address}";
    echo "Country: {$metadata->country_name}";
}

// Geographic analysis
$byCountry = RequestMetadata::selectRaw('country_name, COUNT(*) as count')
    ->groupBy('country_name')
    ->orderByDesc('count')
    ->get();
```

## Next Steps

- Check [API Reference](#) for detailed method documentation
- Join the community on [GitHub](https://github.com/akira-projects/laravel-sisp)