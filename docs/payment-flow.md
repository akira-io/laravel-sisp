# Payment Flow - Complete Overview

This document describes the complete payment flow from request initiation to callback processing.

## High-Level Flow

```
User submits payment form
    ↓
Validate request data
    ↓
Create transaction record
    ↓
Store transaction items
    ↓
Render payment form
    ↓
User redirects to SISP gateway
    ↓
User completes payment on SISP
    ↓
SISP sends callback to your server
    ↓
Validate callback signature
    ↓
Update transaction status
    ↓
Dispatch event
    ↓
Your listeners handle completion
```

## Step-by-Step Breakdown

### Step 1: Request Validation

User submits payment request:

```http
POST /payment HTTP/1.1
Content-Type: application/json

{
    "amount": 815.00,
    "items": [
        {
            "product_id": "PROD_001",
            "product_name": "Laptop",
            "quantity": 1,
            "unit_price": 815.00,
            "total_price": 815.00,
            "description": "High-end laptop",
            "metadata": {"color": "silver"}
        }
    ]
}
```

**What Happens:**
1. Request reaches `PaymentController::__invoke()`
2. Form request `StorePaymentRequest` validates the payload
3. Validation rules ensure:
   - `amount` is required and numeric (min 0.01)
   - `items` is required array with at least 1 element
   - Each item has required fields: `product_name`, `quantity`, `unit_price`, `total_price`
   - Optional fields: `product_id`, `description`, `metadata`

**Validation Rules:**

```php
'amount' => ['required', 'numeric', 'min:0.01'],
'items' => ['required', 'array', 'min:1'],
'items.*.product_id' => ['sometimes', 'string', 'max:255'],
'items.*.product_name' => ['required', 'string', 'max:255'],
'items.*.quantity' => ['required', 'integer', 'min:1'],
'items.*.unit_price' => ['required', 'numeric', 'min:0'],
'items.*.total_price' => ['required', 'numeric', 'min:0'],
'items.*.description' => ['sometimes', 'string'],
'items.*.metadata' => ['sometimes', 'array'],
```

**Response on Validation Error:**

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/json

{
    "message": "The given data was invalid.",
    "errors": {
        "items": ["The items field is required"],
        "items.0.quantity": ["The quantity must be at least 1"]
    }
}
```

### Step 2: Prepare Payment Request

Controller calls `PreparePaymentAction`:

```php
$requestData = PaymentRequestData::from($request->validated());
$paymentRequest = $this->preparePayment->handle($requestData);
```

**What Happens:**

`PreparePaymentAction` orchestrates several actions:

1. **Generate Merchant Reference** (if not provided)
   ```php
   $merchantRef = MerchantReferenceGeneratorAction::handle()
   // Returns: "R20251027121530" (timestamp-based)
   ```

2. **Generate Merchant Session** (if not provided)
   ```php
   $merchantSession = MerchantSessionGeneratorAction::handle()
   // Returns: "S20251027121530" (timestamp-based)
   ```

3. **Generate Timestamp**
   ```php
   $timestamp = TimeStampGeneratorAction::handle()
   // Returns: "2025-10-27 12:15:30"
   ```

4. **Determine Endpoint** (Sandbox vs Production)
   ```php
   $endpoint = DeterminePaymentEndpointAction::handle()
   // Returns SISP URL or sandbox URL based on config
   ```

5. **Build Request Payload**
   ```php
   $payload = BuildRequestPayloadAction::handle()
   // Creates payload with all SISP fields
   ```

6. **Generate Fingerprint**
   ```php
   $fingerprint = GenerateFingerprintAction::handle()
   // SHA512 hash of fields in specific order
   ```

**Result: PaymentRequest Value Object**

```php
class PaymentRequest
{
    public string $url; // SISP gateway URL
    public string $method; // POST
    public array $fields; // Form fields
    public string $merchant_ref; // e.g., "R20251027121530"
    public string $merchant_session; // e.g., "S20251027121530"
    public string $fingerprint; // SHA512 hash
    public array $payload; // Original request data
}
```

### Step 3: Create Transaction Record

Controller wraps in `DB::transaction()`:

```php
$transaction = DB::transaction(function () use ($paymentRequest, $request) {
    $transaction = $this->storeTransaction->handle($paymentRequest);
    // ...
    return $transaction;
});
```

**What Happens:**

`StorePaymentTransactionAction::handle()` creates a `Transaction` model:

```php
Transaction::create([
    'merchant_ref' => 'R20251027121530',
    'merchant_session' => 'S20251027121530',
    'amount' => 815.00,
    'currency' => '132',
    'status' => 'pending',
    'transaction_code' => '1',
    'payload' => $paymentRequest->payload, // Original data
    'fingerprint' => 'abc123def456...',
    'created_at' => now(),
    'updated_at' => now(),
]);
```

**Database Result:**

```
id: 1
merchant_ref: R20251027121530
merchant_session: S20251027121530
amount: 815.00
currency: 132
status: pending
transaction_id: (null - filled on callback)
message_type: (null - filled on callback)
response_code: (null - filled on callback)
merchant_response: (null - filled on callback)
payload: {"amount": 815.00, "items": [...]}
fingerprint: abc123def456...
created_at: 2025-10-27 12:15:30
updated_at: 2025-10-27 12:15:30
```

### Step 4: Store Transaction Items

Still inside transaction:

```php
$items = $request->input('items');
$itemsData = TransactionItemData::collection($items);
$this->storeItems->handle($transaction, ...$itemsData);
```

**What Happens:**

`StoreTransactionItemsAction` optimizes with bulk insert:

```php
// Maps items and converts to cents
$records = [
    [
        'transaction_id' => 1,
        'product_id' => 'PROD_001',
        'product_name' => 'Laptop',
        'quantity' => 1,
        'unit_price_cents' => 81500, // 815.00 * 100
        'total_price_cents' => 81500,
        'description' => 'High-end laptop',
        'metadata' => '{"color": "silver"}',
        'created_at' => now(),
        'updated_at' => now(),
    ]
];

// Single INSERT query with all records
DB::table('sisp_transaction_items')->insert($records);
```

**Database Result:**

```
id: 1
transaction_id: 1
product_id: PROD_001
product_name: Laptop
quantity: 1
unit_price_cents: 81500 (displayed as 815.00)
total_price_cents: 81500 (displayed as 815.00)
description: High-end laptop
metadata: {"color": "silver"}
created_at: 2025-10-27 12:15:30
updated_at: 2025-10-27 12:15:30
```

### Step 5: Render Payment Form

After all data is saved:

```php
if ($this->loadConfig->shouldUseInertia()) {
    return $this->renderForm->renderInertia(
        $paymentRequest,
        $this->loadConfig->getPaymentFormComponent()
    );
}

return $this->renderForm->renderBlade($paymentRequest);
```

**What Happens:**

`RenderPaymentFormAction` returns HTML with:
- Payment form with hidden fields
- JavaScript to auto-submit to SISP
- All SISP required fields

**Response:**

```html
<form id="payment-form" action="https://mc.vinti4net.cv/..." method="POST">
    <input type="hidden" name="posID" value="your_pos_id">
    <input type="hidden" name="posAutCode" value="your_pos_aut_code">
    <input type="hidden" name="merchantID" value="your_merchant_id">
    <input type="hidden" name="transactionType" value="1">
    <input type="hidden" name="transactionID" value="">
    <input type="hidden" name="amount" value="815.00">
    <input type="hidden" name="currency" value="132">
    <input type="hidden" name="merchantSessionID" value="S20251027121530">
    <input type="hidden" name="merchantReferenceID" value="R20251027121530">
    <input type="hidden" name="timeStamp" value="2025-10-27 12:15:30">
    <input type="hidden" name="language" value="EN">
    <input type="hidden" name="fingerprint" value="abc123def456...">
    <input type="hidden" name="urlMerchantResponse" value="https://yourapp.com/sisp/callback">
</form>

<script>
    document.getElementById('payment-form').submit();
</script>
```

User is automatically redirected to SISP gateway.

### Step 6: User Completes Payment on SISP

User:
1. Enters payment details on SISP gateway
2. Confirms payment
3. SISP processes transaction
4. SISP redirects to your `urlMerchantResponse` with callback

### Step 7: Callback Received

SISP sends POST to `/sisp/callback`:

```http
POST /sisp/callback HTTP/1.1
Content-Type: application/x-www-form-urlencoded

merchantRespMerchantRef=R20251027121530&
merchantRespMerchantSessionID=S20251027121530&
merchantRespTransactionID=SISP_TID_12345&
merchantRespMessageType=8&
merchantResp=C&
merchantRespCP=&
messageDigest=abc123def456...
```

**Mapping:**
- `messageType: 8` = Purchase success
- `merchantResp: C` = Completed
- `messageDigest` = SHA512 fingerprint to validate

### Step 8: Validate Callback Signature

Controller calls `ValidateFingerprintAction`:

```php
$signature = $this->validateFingerprint->handle($payload);
```

**What Happens:**

1. Extract fields from callback in specific order
2. Generate SHA512 hash
3. Compare with provided fingerprint
4. Raise exception if mismatch

```php
// Pseudo code
$fieldOrder = [
    'merchantID',
    'posID',
    'posAutCode',
    'merchantSessionID',
    'merchantReferenceID',
    'timeStamp',
    'messageType',
    'merchantResp',
    'CP',
];

$concat = implode($fields[$order]);
$computed = hash('sha512', $concat);

if ($computed !== $callbackFingerprint) {
    throw InvalidPaymentResponseException;
}
```

### Step 9: Update Transaction Status

`HandleCallbackAction` updates the transaction:

```php
$callbackPayload = CallbackPayload::from($payload);
$this->handleCallback->handle($transaction, $callbackPayload);
```

**What Happens:**

1. Map callback fields to transaction columns:
   ```php
   'merchant_ref' => $payload['merchantRespMerchantRef'],
   'merchant_session' => $payload['merchantRespMerchantSessionID'],
   'transaction_id' => $payload['merchantRespTransactionID'],
   'message_type' => $payload['merchantRespMessageType'],
   'merchant_response' => $payload['merchantResp'],
   'response_code' => $payload['merchantRespCP'],
   ```

2. Determine status from `messageType`:
   - `messageType: 8` = Success → status: `completed`
   - `messageType: 6 or 9` = Cancelled → status: `cancelled`
   - Other = Handle accordingly

3. Merge payloads:
   ```php
   $merged = array_merge(
       $transaction->payload ?? [],
       $callbackPayload->toArray()
   );
   ```

4. Save transaction:
   ```php
   $transaction->update([
       'transaction_id' => $callbackPayload->transaction_id,
       'message_type' => $callbackPayload->message_type,
       'merchant_response' => $callbackPayload->merchant_response,
       'response_code' => $callbackPayload->response_code,
       'status' => TransactionStatus::completed,
       'payload' => $merged,
   ]);
   ```

**Database Result:**

```
id: 1
merchant_ref: R20251027121530
merchant_session: S20251027121530
amount: 815.00
currency: 132
status: completed (UPDATED)
transaction_id: SISP_TID_12345 (UPDATED)
message_type: 8 (UPDATED)
response_code: (empty)
merchant_response: C (UPDATED)
payload: {original + callback data} (UPDATED)
fingerprint: abc123def456...
created_at: 2025-10-27 12:15:30
updated_at: 2025-10-27 12:15:35 (UPDATED)
```

### Step 10: Dispatch Event

Based on transaction status:

```php
match ($transaction->status) {
    TransactionStatus::completed => Event::dispatch(new PaymentCompleted($transaction)),
    TransactionStatus::failed => Event::dispatch(new PaymentFailed($transaction)),
    TransactionStatus::pending => Event::dispatch(new PaymentPending($transaction)),
    TransactionStatus::cancelled => Event::dispatch(new TransactionCancelled($transaction)),
    TransactionStatus::refunded => Event::dispatch(new TransactionRefunded($transaction)),
    default => null,
};
```

### Step 11: Your Listeners Handle Completion

Define listeners in `EventServiceProvider`:

```php
protected $listen = [
    PaymentCompleted::class => [
        GenerateInvoiceOnPaymentCompleted::class,
        MarkOrderAsPaidListener::class,
        SendConfirmationEmailListener::class,
    ],
];
```

Example listener:

```php
class MarkOrderAsPaidListener
{
    public function handle(PaymentCompleted $event): void
    {
        $orderId = $event->transaction->merchant_ref;

        Order::find($orderId)
            ->update(['status' => 'paid', 'transaction_id' => $event->transaction->id]);
    }
}
```

## Complete State Machine

```
Initial State: pending

pending
├─ (Payment succeeds) → completed
│  └─ Dispatch PaymentCompleted event
├─ (Payment fails) → failed
│  └─ Dispatch PaymentFailed event
├─ (User cancels) → cancelled
│  └─ Dispatch TransactionCancelled event
├─ (Pending response) → pending
│  └─ Dispatch PaymentPending event
└─ (Admin refunds) → refunded
   └─ Dispatch TransactionRefunded event
```

## Error Scenarios

### Validation Error

```
User submits invalid data
    ↓
StorePaymentRequest validation fails
    ↓
422 Unprocessable Entity response
    ↓
No database record created
    ↓
User sees validation errors
```

### Transaction Creation Error

```
ValidPaymentRequest received
    ↓
DB::transaction started
    ↓
Transaction creation succeeds
    ↓
Item insertion fails
    ↓
Entire transaction rolled back
    ↓
No transaction or items in database
    ↓
Exception thrown to user
```

### Callback Signature Invalid

```
SISP sends callback
    ↓
ValidateFingerprintAction fails
    ↓
InvalidPaymentResponseException thrown
    ↓
400 Bad Request response
    ↓
Transaction status NOT updated
    ↓
Logged for investigation
```

### Duplicate Callback

```
SISP sends same callback twice
    ↓
First callback processed (status updated to completed)
    ↓
Event dispatched
    ↓
Second callback received
    ↓
ValidateFingerprintAction passes
    ↓
HandleCallbackAction updates status to completed again
    ↓
Status already completed, no change
    ↓
Event dispatched again (listener should be idempotent)
```

## Database Transactions

All operations within `DB::transaction()` are atomic:

```php
$transaction = DB::transaction(function () use ($paymentRequest, $request) {
    // Savepoint 1
    $transaction = $this->storeTransaction->handle($paymentRequest);

    // Savepoint 2
    $items = $request->input('items');
    $itemsData = TransactionItemData::collection($items);
    $this->storeItems->handle($transaction, ...$itemsData);

    // All or nothing
    return $transaction;
});
```

If any operation fails:
- Both transaction and items are rolled back
- Database remains clean
- No partial data

## Performance Timeline

```
Request arrives: T+0ms
    ↓
Validation: T+5ms
    ↓
Generate references: T+10ms
    ↓
Build payload: T+15ms
    ↓
Generate fingerprint: T+20ms
    ↓
Create transaction: T+50ms
    ↓
Insert 100 items (bulk): T+70ms
    ↓
Render form: T+85ms
    ↓
Response sent: T+90ms
```

For comparison, without bulk insert:
- 100 items individually: T+250ms (3x slower)

## Callback Processing Timeline

```
Callback arrives: T+0ms
    ↓
Validate signature: T+5ms
    ↓
Map fields: T+10ms
    ↓
Update transaction: T+30ms
    ↓
Dispatch events: T+35ms
    ↓
Event listeners execute: T+100ms (depends on listeners)
    ↓
Response sent: T+110ms
```

## Data Flow Diagram

```
User Input
    ↓
Validation Layer (StorePaymentRequest)
    ↓
Value Objects (PaymentRequestData)
    ↓
Action Layer (PreparePaymentAction, StorePaymentTransactionAction, etc)
    ↓
Database (Transactions, TransactionItems)
    ↓
Response (Form or Inertia)
    ↓
SISP Gateway
    ↓
Callback
    ↓
Validation (ValidateFingerprintAction)
    ↓
Action Layer (HandleCallbackAction)
    ↓
Database (Update Transaction)
    ↓
Events (PaymentCompleted, etc)
    ↓
User Listeners (GenerateInvoice, MarkOrderAsPaid, etc)
```

## Post-Payment Invoice Generation

After a successful payment callback, you can automatically generate PDF invoices using [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices). This typically happens in a PaymentCompleted event listener.

See [Customer Data & Invoices](./customer-data-and-invoices.md) for complete information on collecting customer data and generating invoices.

## See Also

- [Customer Data & Invoices](./customer-data-and-invoices.md) - Collect customer info and generate invoices
- [Architecture & Design Patterns](./architecture.md) - Technical architecture
- [E-Commerce Transactions](./e-commerce-transactions.md) - Complete example
- [Events & Monitoring](./events-and-monitoring.md) - Listen to events
- [Rate Limiting](./rate-limiting.md) - Prevent payment request abuse
- [API Reference](./api-reference.md) - Complete API documentation
- [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) - Generate professional PDF invoices
