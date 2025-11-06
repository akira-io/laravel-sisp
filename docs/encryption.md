# Encryption

The SISP package automatically encrypts sensitive transaction data using Laravel's encryption system.

## Encrypted Attributes

By default, the following attributes are encrypted in the `transactions` table:

- `merchant_ref` - Merchant transaction reference
- `merchant_session` - Merchant session identifier
- `fingerprint` - Request/response digital signature
- `payload` - Complete transaction payload
- `transaction_id` - SISP transaction identifier

## How It Works

The `EncryptsAttributes` trait automatically encrypts data when you set an attribute and decrypts it when you retrieve
it. This is transparent to your application code.

### Example Usage

```php
use Akira\Sisp\Models\Transaction;

// Automatically encrypted
$transaction = Transaction::create([
    'merchant_ref' => 'REF-12345',
    'merchant_session' => 'SESSION-ABC',
    'fingerprint' => 'hash-value',
    'payload' => ['key' => 'value'],
    'transaction_id' => 'TXN-67890',
]);

// Automatically decrypted when retrieved
echo $transaction->merchant_ref; // REF-12345
echo $transaction->transaction_id; // TXN-67890
```

## Customizing Encrypted Attributes

To customize which attributes should be encrypted, override the `encryptable()` method in your Transaction model:

```php
protected function encryptable(): array
{
    return [
        'merchant_ref',
        'merchant_session',
        'fingerprint',
        'payload',
        'transaction_id',
        'custom_field', // Add custom fields
    ];
}
```

To encrypt **all** attributes (except non-string values), return an empty array:

```php
protected function encryptable(): array
{
    return [];
}
```

## Database Storage

Encrypted values are stored as encrypted strings in the database. They automatically decrypt when accessed through the
model:

```php
$transaction = Transaction::find($id);
$transaction->merchant_ref; // Returns decrypted value
```

## Querying Encrypted Data

Since encrypted values are stored as encrypted strings, you cannot directly query them. The encryption process is
transparent to reads, but queries must account for encryption:

```php
// This won't work (merchant_ref is encrypted in the database)
Transaction::where('merchant_ref', 'REF-12345')->first();

// Instead, retrieve and filter in application code
$transactions = Transaction::all()
    ->filter(fn($t) => $t->merchant_ref === 'REF-12345')
    ->first();
```

For better performance with frequently-queried fields, consider using a hashed index alongside encryption:

```php
// In migration
$table->string('merchant_ref_hash')->nullable()->index();
```

Then use the hash for queries:

```php
$hash = hash('sha256', 'REF-12345');
Transaction::where('merchant_ref_hash', $hash)->first();
```

## Requirements

Ensure your `.env` file has a valid `APP_KEY`:

```
APP_KEY=base64:...
```

If you don't have an encryption key, generate one:

```bash
php artisan key:generate
```

## Security Considerations

- Encrypted data is only encrypted at rest in the database
- Data is transmitted unencrypted during the payment flow to/from SISP
- Always use HTTPS for all payment-related endpoints
- Never log or expose unencrypted sensitive data
- Ensure your `APP_KEY` is secure and backed up safely

## Integration with Security Features

Encryption works alongside other security features:

- **Fingerprint Validation** - Validates request/response integrity (see [Architecture](./architecture.md))
- **Rate Limiting** - Prevents abuse through frequency limits (see [Rate Limiting](./rate-limiting.md))
- **Request Metadata** - Captures geolocation and device data (
  see [Security & Fraud Detection](./security-and-fraud-detection.md))
- **Blacklisting** - Blocks known malicious identifiers (
  see [Security & Fraud Detection](./security-and-fraud-detection.md))

## See Also

- [Security & Fraud Detection](./security-and-fraud-detection.md) - Comprehensive security guide
- [Architecture & Design Patterns](./architecture.md) - System architecture overview
- [Rate Limiting](./rate-limiting.md) - Rate limiting and abuse prevention
- [Configuration Reference](./configuration.md) - Configuration options including encryption settings