# Payment Flow

Complete overview of the payment process from start to finish.

## Flow Diagram

```
User Form
    |
    v
POST /sisp/payment
    |
    ├─ Validate Request
    ├─ Check Blacklist
    ├─ Check Rate Limit
    ├─ Store Request Metadata
    ├─ Create Transaction (pending)
    ├─ Render SISP Payment Form
    |
    v
SISP Gateway (Real or Sandbox)
    |
    ├─ User enters payment details
    ├─ Payment processing
    |
    v
GET|POST /sisp/callback
    |
    ├─ Validate Fingerprint
    ├─ Require Merchant Ref and Session
    ├─ Check Duplicate Callback
    ├─ Find or Create Transaction
    ├─ Reconcile Callback Details
    ├─ Parse Error Response (if failed)
    ├─ Update Status
    ├─ Store Callback Response
    ├─ Store Callback Metadata
    ├─ Generate Invoice (if configured)
    ├─ Dispatch Event
    |
    v
Response View
    |
    ├─ Show Transaction Result
    ├─ Display Structured Error (if failed)
    ├─ Show Retry Option (if configured)
    |
    └─ Show Result
```

## Step 1: Payment Form Submission

User submits payment form with:
- `amount` - Total payment amount
- `items[]` - Array of line items
- `customer_email` - Optional customer email
- `locale` - Optional customer language (pt, en) - defaults to 'pt'
- Other customer details (optional)

## Step 1.5: Duplicate Transaction Protection

`ProtectPaymentRoute` middleware prevents replaying a payment request:
- Checks for existing transactions with same `merchantRef` + `merchantSession`
- Blocks reprocessing when status is `completed`, `failed`, or `pending`
- Redirects to `/` with an error message if a duplicate is detected

## Step 2: Request Validation

`PaymentController` validates using `StorePaymentRequest`:
- `amount` must be numeric, minimum 0.01
- `items` must be array with at least 1 item
- Each item must have: `product_name`, `quantity`, `unit_price`, `total_price`

## Step 3: Security Checks

Before creating transaction:

### Blacklist Check
- Verifies IP is not blacklisted
- Can block by IP, email, or other identifiers

### Rate Limiting
- Checks limits per IP
- Checks limits per merchant
- Checks limits per user
- Returns 429 if exceeded

### Metadata Collection
- Captures IP address
- Device information
- Geolocation data (if enabled)
- Risk score fields for application-level integrations

The package does not perform VPN detection, proxy detection, risk scoring, or
whitelist enforcement by itself. Use custom middleware or blacklist entries for
those controls.

## Step 4: Transaction Creation

Package creates transaction with:
- Status: `pending`
- Reference ID (unique)
- Amount in cents
- Items array
- Customer information

Stored in `sisp_transactions` table.

### 4.1 3D Secure Purchase Request (when enabled)

If `SISP_IS_3D_SEC=1`, the package builds a `purchaseRequest` payload using:
- `customer_email`
- `customer_country` (alpha-2)
- `customer_city`
- `customer_address`
- `customer_postal_code`
- `customer_phone` (optional)

If any required field is missing, `MissingThreeDSecureDataException` is thrown.

## Step 5: Payment Form Rendering

Package renders:
- SISP payment form with merchant credentials
- Transaction data and security tokens
- Callback URL for return

## Step 6: Redirect to SISP

Form submits to SISP gateway:

**In Sandbox Mode:**
- Routes to `/sisp/sandbox` (local fake gateway)

**In Production:**
- Routes to real SISP: `https://mc.vinti4net.cv/...`

## Step 7: Payment Processing

User completes payment at SISP gateway:
- Enters card details
- Confirms payment
- SISP processes transaction

## Step 8: SISP Callback

After payment, SISP POSTs to `/sisp/callback` with:
- Transaction reference
- Payment status
- SISP transaction ID
- Signature for verification

## Step 9: Callback Validation & Processing

`CallbackController` with `HandleCallbackAction`:

### 9.1 Fingerprint Validation
`ValidatePaymentResponseFingerprintAction` validates:
- SHA512 hash of callback fields with pos auth code
- Verifies data integrity from SISP
- Prevents callback tampering or spoofing
- Fields included: messageType, amount, reference, timestamp, and 13+ others

Invalid POST callbacks are redirected to `config('sisp.redirect_url', '/')` before any transaction lookup.

### 9.2 Required Callback Keys
After the fingerprint passes, the callback must include:

- `merchantRespMerchantRef`
- `merchantRespMerchantSession`

If either value is missing, the request is redirected to `config('sisp.redirect_url', '/')`.

### 9.3 Duplicate Prevention
Duplicate callback detection happens inside `CallbackController` after signature validation.

The controller:
- Looks up the transaction by merchant reference and merchant session
- Treats it as already processed when the local `transaction_id` is already set
- Redirects to `config('sisp.redirect_url', '/')` with an `info` flash message

This keeps invalid unsigned traffic from triggering database lookups.

### 9.4 Transaction Lookup and Reconciliation
`HandleCallbackAction` finds or creates the transaction, then reconciles the signed callback against the stored transaction.

The callback must match:

- Merchant reference
- Merchant session
- Amount
- Currency
- Transaction code
- POS ID

If any value does not match, the transaction is marked `failed` with `merchant_response` set to `callback_details_mismatch`.

### 9.5 Error Response Parsing
`GetPaymentErrorResponseAction` transforms error codes into structured responses:
- Maps error code (e.g., "6") to human-readable label
- Categorizes error: card, funds, security, validation, system, issuer
- Suggests action: contact-issuer, use-different-card, retry, etc.
- Provides translated messages for EN and PT

### 9.6 Status Update
- Sets status: `completed`, `failed`, or `pending`
- Stores SISP response data
- Records error code and response details

### 9.7 Callback Metadata
The controller stores request metadata for the signed callback before updating invoice status.

### 9.8 Invoice Status Update
The controller updates the linked invoice status after the transaction is updated.

### 9.9 Invoice Generation (if enabled)
- Generates PDF invoice
- Stores path in database

### 9.10 Event Dispatch
- `PaymentCompleted` - Payment successful
- `PaymentFailed` - Payment rejected
- `PaymentPending` - Still processing

### 9.11 Response Rendering
The POST callback redirects to:

```php
route('sisp.callback', ['ref' => $transaction->merchant_ref])
```

The GET callback renders the payment response for the `ref` query parameter. Missing or unknown references redirect to `config('sisp.redirect_url', '/')`.

## Step 10: Timeout Reconciliation

In normal operation, SISP resolves the transaction through the automatic callback. If no callback is received after the SISP timeout window, the transaction can remain indeterminate:

- status is `pending`
- `message_type` is `null`
- the transaction is older than the configured reconciliation threshold

Enable scheduled reconciliation when you want the package to monitor those incomplete transactions:

```env
SISP_TRANSACTION_RECONCILIATION_ENABLED=true
SISP_TRANSACTION_RECONCILE_AFTER_MINUTES=5
SISP_TRANSACTION_RECONCILE_LIMIT=50
```

Then schedule:

```php
$schedule->command('sisp:reconcile-pending')->everyFiveMinutes();
```

The scheduled command uses SISP's POS transaction-status API. It does not mark a transaction failed when the API request itself fails. It only updates the local status when SISP returns `result=true`.

## Events Dispatched

### PaymentCompleted
Fired when transaction status becomes `completed`:
```php
PaymentCompleted::dispatch($transaction, $payload);
```

### PaymentFailed
Fired when transaction status becomes `failed`:
```php
PaymentFailed::dispatch($transaction, $payload);
```

### PaymentPending
Fired when transaction status remains `pending`:
```php
PaymentPending::dispatch($transaction, $payload);
```

### TransactionCancelled
Fired when transaction is cancelled via `POST /sisp/cancel`:
```php
TransactionCancelled::dispatch($transaction, $reason);
```

### TransactionRefunded
Fired when transaction is refunded via `POST /sisp/refund/{transaction}`:
```php
TransactionRefunded::dispatch($transaction, $refundAmount, $reason);
```

## Transaction Statuses

- **pending** - Form submitted, awaiting SISP response
- **completed** - Payment successful
- **failed** - Payment rejected
- **cancelled** - Transaction cancelled by user or merchant
- **refunded** - Payment refunded to customer

## Database Records Created

### sisp_transactions
- transaction_id (UUID)
- amount (decimal CVE, compatibility)
- amount_cents (integer cents, canonical storage)
- status (pending/completed/failed)
- merchant_ref (unique reference)
- merchant_session
- customer_email
- locale (customer language preference)
- customer data

### sisp_transaction_items
- Each line item from the payment
- Linked to transaction
- Preserves item details

### sisp_request_metadata
- IP address
- Device information
- Geolocation data
- Risk score
- VPN/Proxy detection

### sisp_invoices (if enabled)
- PDF invoice path
- Linked to transaction
- Generated after completion

## Error Handling

### Request Validation Errors
- Returns 422 with validation messages
- Specific validation rules for amount, items, customer data

### Security Errors
- **Rate Limit** - Returns 429 (Too Many Requests)
- **Blacklist** - Returns 403 (Forbidden) for blacklisted IP/email
- **Duplicate Callback** - Redirects to `config('sisp.redirect_url', '/')` with an `info` flash message

### Callback/Payment Errors
Payment failures return structured error information:

```php
[
    'code' => 'card_declined',           // Error code from SISP (e.g., "6")
    'label' => 'Card Declined',          // Human-readable label (translated)
    'category' => 'card',                 // Category: card|funds|security|validation|system|issuer
    'categoryLabel' => 'Card Issue',      // Category label (translated)
    'action' => 'use-different-card',    // Suggested action for user
    'actionLabel' => 'Try Another Card', // Action label (translated)
]
```

These error responses are shown to user with retry option if configured.

### Fingerprint Validation Errors
- Redirects to `config('sisp.redirect_url', '/')` if fingerprint validation fails
- Indicates potential tampering or replay attack
- Callback is not processed and no transaction lookup is performed by the controller

### Invoice Generation Errors
- Logs error but doesn't fail payment
- Transaction still marked as completed/failed
- User can still see payment result

## Flow Summary

```
Submit Form
    ↓
Validate & Security Checks
    ↓
Create Transaction (pending)
    ↓
Render & Submit SISP Form
    ↓
User Pays at Gateway
    ↓
SISP Callback
    ↓
Validate Signature
    ↓
Update Status
    ↓
Generate Invoice
    ↓
Dispatch Event
    ↓
Show Result
```

## Next Steps

- [Transaction Management](./05-transaction-management.md) - Work with transactions
- [Invoice Generation](./06-invoice-generation.md) - Auto-generate invoices
- [Security](./07-security.md) - Rate limiting details

**Previous:** [Quick Start Guide](03-quick-start.md) | **Next:** [Transaction Management](05-transaction-management.md)
