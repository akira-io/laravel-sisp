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

## Drive sisp:install in Tests (No TTY)

Use config toggles to control the interactive prompts only when running unit tests:

```php
config()->set('sisp.tests.publish_config', true);
config()->set('sisp.tests.publish_migrations', true);
config()->set('sisp.tests.run_migrations', true);
config()->set('sisp.tests.fake_migrate', true); // don’t execute real migrate again

// Avoid vendor:publish for UI assets during CI
config()->set('sisp.tests.publish_inertia', false);
config()->set('sisp.tests.publish_blade', false);

Artisan::call('sisp:install', ['--no-interaction' => true]);
```

## Populate Country Select

Use the countries helper to render a dropdown with flags and numeric codes.

```php
use Akira\Sisp\Facades\Sisp;

$countries = Sisp::countries();

foreach ($countries as $country) {
    echo "{$country['name']} ({$country['alpha2']})";
}
```

## Countries API Endpoint

```http
GET /sisp/countries
```

Returns cached country data as JSON.

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

    <div class="form-group">
        <label>Language</label>
        <select name="locale">
            <option value="pt">Português</option>
            <option value="en">English</option>
        </select>
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

Build a signed payment request with the fluent builder:

```php
use Akira\Sisp\Facades\Sisp;

$paymentRequest = Sisp::payment()
    ->amount(100.00)
    ->currency('132')
    ->customerEmail('customer@example.com')
    ->locale('pt')
    ->build(); // signed PaymentRequest value object
```

Or run the full payment pipeline (blacklist, rate limits, request building, persistence, metadata) exactly as the HTTP flow does:

```php
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Akira\Sisp\Pipelines\Payment\ProcessPaymentPipeline;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Http\Request;

$request = Request::create('/sisp/payment', 'POST', [
    'amount' => 100.00,
    'items' => [
        [
            'product_name' => 'Service',
            'quantity' => 1,
            'unit_price' => 100.00,
            'total_price' => 100.00,
        ],
    ],
    'customer_email' => 'customer@example.com',
    'customer_name' => 'John Doe',
    'locale' => 'pt',
]);

$context = app(ProcessPaymentPipeline::class)->run(new PaymentContext(
    data: PaymentRequestData::from($request->all()),
    request: $request,
));

$transaction = $context->transaction();
$paymentRequest = $context->paymentRequest();

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

// Transactions by locale
$portugueseTransactions = Transaction::where('locale', 'pt')
    ->where('status', TransactionStatus::completed)
    ->count();

$englishTransactions = Transaction::where('locale', 'en')
    ->where('status', TransactionStatus::completed)
    ->count();

// Transactions with application-assigned risk score
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

// Monitor application-assigned high-risk IPs
$riskMetadata = RequestMetadata::where('risk_score', '>=', 80)
    ->where('created_at', '>=', now()->subHour())
    ->get();

foreach ($riskMetadata as $metadata) {
    $action->add(
        type: 'ip',
        value: $metadata->ip_address,
        severity: 'medium',
        reason: 'Application-assigned fraud risk score',
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
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;

$transaction = Transaction::find($id);
$refundAmount = $transaction->amount;

try {
    Sisp::refund($transaction)
        ->full()
        ->reason('customer_request')
        ->process();

    return response()->json([
        'success' => true,
        'message' => "Refunded the full {$refundAmount} ECV transaction amount",
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

// Application-assigned VPN and proxy flags
$vpnUsers = RequestMetadata::where(function ($q) {
    $q->where('is_vpn', true)
        ->orWhere('is_proxy', true);
})->count();

// Mobile payments
$mobilePayments = RequestMetadata::where('is_mobile', true)->count();

// Application-assigned high-risk transactions
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

## Multi-Merchant Operations

### Basic Multi-Merchant Usage

Process payments for different merchants in the same request:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\PaymentRequestData;

// Merchant A
$merchantA = SispCredentials::from([
    'pos_id' => 'MERCHANT_A_POS',
    'pos_aut_code' => 'secret_a',
    'currency' => '132',
    'merchant_id' => 'MERCHANT_A',
    'url' => 'https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp',
]);

$requestA = Sisp::forCredentials($merchantA)
    ->buildRequestPayload(PaymentRequestData::from([
        'amount' => 100.00,
        'merchantRef' => 'ORDER-A-001',
    ]));

// Merchant B
$merchantB = SispCredentials::from([
    'pos_id' => 'MERCHANT_B_POS',
    'pos_aut_code' => 'secret_b',
    'currency' => '132',
    'merchant_id' => 'MERCHANT_B',
    'url' => 'https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp',
]);

$requestB = Sisp::forCredentials($merchantB)
    ->buildRequestPayload(PaymentRequestData::from([
        'amount' => 200.00,
        'merchantRef' => 'ORDER-B-002',
    ]));
```

### SaaS Platform Implementation

Create a controller for multi-tenant payment processing:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use App\Models\Tenant;

class TenantPaymentController extends Controller
{
    public function create(Request $request)
    {
        $tenant = Tenant::findOrFail($request->tenant_id);

        // Load tenant-specific credentials
        $credentials = SispCredentials::from([
            'pos_id' => $tenant->sisp_pos_id,
            'pos_aut_code' => decrypt($tenant->sisp_pos_aut_code),
            'currency' => $tenant->currency,
            'merchant_id' => $tenant->sisp_merchant_id,
            'url' => $tenant->sisp_url,
            'sandbox' => $tenant->is_sandbox,
        ]);

        // Create payment with tenant credentials
        $paymentRequest = Sisp::forCredentials($credentials)
            ->buildRequestPayload(
                PaymentRequestData::from([
                    'amount' => $request->amount,
                    'merchantRef' => $tenant->generateReference(),
                ])
            );

        return view('payment', ['request' => $paymentRequest]);
    }
}
```

### Custom Resolver for Database Credentials

Automatically resolve credentials from the database:

```php
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\ValueObjects\SispCredentials;

class TenantCredentialsResolver implements SispCredentialsResolver
{
    public function __construct(private int $tenantId) {}

    public function resolve(): SispCredentials
    {
        $tenant = Tenant::findOrFail($this->tenantId);

        return SispCredentials::from([
            'pos_id' => $tenant->sisp_pos_id,
            'pos_aut_code' => decrypt($tenant->sisp_pos_aut_code),
            'currency' => $tenant->currency,
            'merchant_id' => $tenant->sisp_merchant_id,
            'url' => $tenant->sisp_url,
            'language_messages' => $tenant->default_language,
            'fingerprint_version' => '1',
            'is_3d_sec' => $tenant->enable_3d_secure ? '1' : '0',
            'sandbox' => $tenant->is_sandbox,
            'url_merchant_response' => route('tenant.sisp.callback', $tenant),
        ]);
    }
}
```

Register in `AppServiceProvider`:

```php
use Akira\Sisp\Contracts\SispCredentialsResolver;
use App\Resolvers\TenantCredentialsResolver;

public function register(): void
{
    $this->app->bind(
        SispCredentialsResolver::class,
        function ($app) {
            // Get tenant from authenticated user
            $tenant = auth()->user()->tenant;

            return new TenantCredentialsResolver($tenant->id);
        }
    );
}
```

With this setup, all Sisp operations automatically use the authenticated tenant's credentials:

```php
// No need to specify credentials - automatically resolved
$request = Sisp::buildRequestPayload(
    PaymentRequestData::from(['amount' => 100.00])
);
```

### Testing Multi-Merchant Scenarios

Test different merchant configurations:

```php
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('processes payments for different merchants', function () {
    $merchantA = SispCredentials::from([
        'pos_id' => 'TEST_A',
        'pos_aut_code' => 'secret_a',
        'currency' => '132',
        'merchant_id' => 'MERCHANT_A',
        'url' => 'https://gateway.test',
    ]);

    $merchantB = SispCredentials::from([
        'pos_id' => 'TEST_B',
        'pos_aut_code' => 'secret_b',
        'currency' => '132',
        'merchant_id' => 'MERCHANT_B',
        'url' => 'https://gateway.test',
    ]);

    $requestA = Sisp::forCredentials($merchantA)
        ->buildRequestPayload(PaymentRequestData::from(['amount' => 100]));

    $requestB = Sisp::forCredentials($merchantB)
        ->buildRequestPayload(PaymentRequestData::from(['amount' => 200]));

    expect($requestA->toArray()['posID'])->toBe('TEST_A')
        ->and($requestB->toArray()['posID'])->toBe('TEST_B');
});
```

## Custom Payment Pipe (v2)

Add your own stage to the payment pipeline.

```php
namespace App\Sisp\Pipes;

use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Closure;
use Illuminate\Support\Facades\Log;

final readonly class LogPaymentAttempt implements PaymentPipe
{
    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        Log::info('SISP payment attempt', [
            'amount' => $context->data->amount,
            'ip' => $context->request->ip(),
        ]);

        return $next($context);
    }
}
```

Register it in `config/sisp.php`:

```php
'pipelines' => [
    'payment' => [
        Akira\Sisp\Pipelines\Payment\Pipes\EnsureIpIsNotBlacklisted::class,
        Akira\Sisp\Pipelines\Payment\Pipes\EnforceRateLimits::class,
        App\Sisp\Pipes\LogPaymentAttempt::class,
        Akira\Sisp\Pipelines\Payment\Pipes\BuildPaymentRequest::class,
        Akira\Sisp\Pipelines\Payment\Pipes\PersistTransaction::class,
        Akira\Sisp\Pipelines\Payment\Pipes\CaptureRequestMetadata::class,
    ],
],
```

## Custom Gateway Driver (v2)

Register an additional gateway driver and select it by config.

```php
namespace App\Sisp;

use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;

final readonly class StagingDriver implements SispDriver
{
    public function name(): string
    {
        return 'staging';
    }

    public function paymentEndpoint(): string
    {
        return 'https://staging.gateway.example.com/pay';
    }

    public function queryTransactionStatus(Transaction|string $transaction): TransactionStatusResponse
    {
        // Delegate to your staging status API...
    }
}
```

```php
// In a service provider:
use Akira\Sisp\Drivers\SispManager;

resolve(SispManager::class)->extend('staging', fn () => new \App\Sisp\StagingDriver());
```

```env
SISP_DRIVER=staging
```

## Next Steps

- Check [API Reference](./11-api-reference.md) for detailed method documentation
- Join the community on [GitHub](https://github.com/akira-io/laravel-sisp)

**Previous:** [Security](07-security.md) | **Next:** [Troubleshooting](09-troubleshooting.md)
