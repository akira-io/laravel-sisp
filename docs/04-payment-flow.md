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
    ├─ Validate Signature
    ├─ Find Transaction
    ├─ Update Status
    ├─ Store Callback Response
    ├─ Generate Invoice (if configured)
    ├─ Dispatch Event
    |
    v
Response View
    |
    └─ Show Result
```

## Step 1: Payment Form Submission

User submits payment form with:
- `amount` - Total payment amount
- `items[]` - Array of line items
- `customer_email` - Optional customer email
- Other customer details (optional)

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
- Risk score calculation

## Step 4: Transaction Creation

Package creates transaction with:
- Status: `pending`
- Reference ID (unique)
- Amount in cents
- Items array
- Customer information

Stored in `sisp_transactions` table.

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

1. **Signature Verification**
   - Validates SISP signature
   - Prevents tampering

2. **Transaction Lookup**
   - Finds transaction by reference

3. **Status Update**
   - Sets status: `completed`, `failed`, or `pending`
   - Stores SISP response data

4. **Invoice Generation** (if enabled)
   - Generates PDF invoice
   - Stores path in database

5. **Event Dispatch**
   - `PaymentCompleted` - Payment successful
   - `PaymentFailed` - Payment rejected
   - `PaymentPending` - Still processing

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
Fired when transaction is refunded via `POST /sisp/refund`:
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
- amount (in cents)
- status (pending/completed/failed)
- merchant_ref (unique reference)
- merchant_session
- customer_email
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

If error occurs:

1. **Validation Error** - Returns 422 with validation messages
2. **Rate Limit** - Returns 429 (Too Many Requests)
3. **Blacklist** - Returns 403 (Forbidden)
4. **Callback Error** - Returns error response
5. **Invoice Error** - Logs but doesn't fail payment

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