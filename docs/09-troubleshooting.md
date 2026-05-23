# Troubleshooting

Common issues and solutions.

## Installation Issues

### Routes not showing

Clear the route cache and regenerate:

```bash
php artisan optimize:clear
php artisan route:cache
```

Verify routes are registered:

```bash
php artisan route:list | grep sisp
```

You should see these routes:

- `POST /sisp/payment`
- `GET|POST /sisp/callback`
- `POST /sisp/retry-payment`
- `GET /sisp/cancel`
- `POST /sisp/refund/{transaction}`
- `GET|POST /sisp/sandbox`
- `GET /sisp/countries`

### Migration errors

Ensure your database connection is configured in `.env`:

```bash
php artisan migrate --force
```

If migrations already ran:

```bash
php artisan migrate:status
```

### Config not loading

Clear config cache:

```bash
php artisan config:clear
php artisan config:cache
```

Verify the config file exists:

```bash
ls -la config/sisp.php
```

### Missing dependencies

Install required package:

```bash
composer require stevebauman/location
```

## Configuration Issues

### SISP_URL not set

Error: `Call to a member function on null`

Solution: Add to `.env`:

```env
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_authorization_code
SISP_MERCHANT_ID=your_merchant_id
```

Then clear cache:

```bash
php artisan config:clear
```

### Sandbox not working

Error: Route not found or sandbox disabled

Solution: Verify sandbox is enabled in `.env`:

```env
SISP_SANDBOX=true
```

The sandbox route is `/sisp/sandbox`. Access it directly in browser to test.

## Testing & CI

### Paratest hangs or interactive prompts block CI

When running `sisp:install` in tests/CI without a TTY, drive prompts via config flags (only read during unit tests):

```php
config()->set('sisp.tests.publish_config', true);
config()->set('sisp.tests.publish_migrations', true);
config()->set('sisp.tests.run_migrations', true);
config()->set('sisp.tests.fake_migrate', true); // skip real migrate
config()->set('sisp.tests.publish_inertia', false); // avoid vendor:publish in CI
config()->set('sisp.tests.publish_blade', false);
```

Then call with no interaction:

```php
Artisan::call('sisp:install', ['--no-interaction' => true]);
```

### Exact 100% coverage checks fail at 99.x%

Use the exact-coverage flag and ensure environment-dependent branches are excluded or driven via the test toggles above.

```bash
vendor/bin/pest --parallel --coverage --compact --exactly=100
```

### Invoice company info missing

Error: Invoices generated but company name/address blank

Solution: Add company configuration to `.env`:

```env
SISP_COMPANY_NAME="Your Company"
SISP_COMPANY_ADDRESS="Street Address"
SISP_COMPANY_CODE="VAT123456"
SISP_COMPANY_EMAIL="billing@company.com"
SISP_COMPANY_COUNTRY="CV"
SISP_COMPANY_PHONE="+238 1234567"
```

Publish config if needed:

```bash
php artisan vendor:publish --tag=sisp-config --force
```

### Paid invoices missing PDFs

If paid invoices lack `pdf_path`, run:

```bash
php artisan sisp:doctor
php artisan sisp:regenerate-pdfs --limit=50
```

`sisp:doctor` reports storage/config issues and sample invoices.  
`sisp:regenerate-pdfs` generates PDFs only for paid invoices without PDFs.

## Payment Issues

### Payment form not rendering

Error: Blank page or 500 error when posting to `/sisp/payment`

Check logs:

```bash
tail -f storage/logs/laravel.log
```

### 3D Secure enabled but customer fields missing

Error: `MissingThreeDSecureDataException`

Solution: When `SISP_IS_3D_SEC=1`, include these fields in the request:
- `customer_email`
- `customer_country` (ISO alpha-2)
- `customer_city`
- `customer_address`
- `customer_postal_code`

Verify form inputs:

- `amount` must be numeric and >= 0.01
- `items` must be array with at least 1 item
- Each item needs: `product_name`, `quantity`, `unit_price`, `total_price`

Example valid form:

```html

<form method='POST' action='/sisp/payment'>
 @csrf
 <input type='hidden' name='amount' value='100.00'>
 <input type='hidden' name='items[0][product_name]' value='Test'>
 <input type='hidden' name='items[0][quantity]' value='1'>
 <input type='hidden' name='items[0][unit_price]' value='100.00'>
 <input type='hidden' name='items[0][total_price]' value='100.00'>
 <button type='submit'>Pay</button>
</form>
```

### Rate limit exceeded

Error: HTTP 429 Too Many Requests

This means you've exceeded the configured rate limit. Check your config:

```env
SISP_RATE_LIMITING_ENABLED=true
SISP_RATE_LIMIT_PER_IP_LIMIT=100
SISP_RATE_LIMIT_PER_IP_WINDOW=3600
```

To disable rate limiting temporarily:

```env
SISP_RATE_LIMITING_ENABLED=false
```

To increase the limit:

```env
SISP_RATE_LIMIT_PER_IP_LIMIT=1000
```

Check current rate limits:

```php
php artisan tinker
>>> use Akira\Sisp\Models\RateLimit;
>>> RateLimit::where('limit_type', 'ip')->first();
```

### IP is blacklisted

Error: HTTP 403 Forbidden - "This ip is blacklisted"

Your IP is on the blacklist. Remove it:

```php
php artisan tinker
>>> use Akira\Sisp\Actions\CheckBlacklistAction;
>>> $action = app(CheckBlacklistAction::class);
>>> $action->remove('ip', '192.168.1.1');
```

Or query the blacklist:

```php
>>> use Akira\Sisp\Models\Blacklist;
>>> Blacklist::active()->get();
```

## Callback Issues

### Callback not being called

SISP gateway not reaching your callback URL.

Verify:

1. Your domain is accessible from SISP servers
2. Callback URL is correct in your code
3. SISP has your callback URL configured
4. No SSL certificate issues
5. Firewall isn't blocking SISP's IP addresses

Test callback manually:

```bash
curl -X POST http://localhost:8000/sisp/callback \
  -H "Content-Type: application/json" \
  -d '{"merchantRespMerchantRef":"test","merchantRespMerchantSession":"test","messageType":"success","merchantResp":"Approved"}'
```

### Callback signature validation fails

Error: Invalid fingerprint or signature

This means SISP's response was tampered with or your credentials are wrong.

Verify SISP credentials:

```env
SISP_POS_ID=correct_id
SISP_POS_AUT_CODE=correct_code
SISP_MERCHANT_ID=correct_id
```

Check logs for exact error:

```bash
tail -f storage/logs/laravel.log | grep -i signature
```

### Transaction not found in callback

Error: "No transaction found" when SISP calls callback

The transaction may not have been created. Verify:

```php
php artisan tinker
>>> use Akira\Sisp\Models\Transaction;
>>> Transaction::where('merchant_ref', 'ref-value')->first();
```

If not found, the payment form creation failed. Check payment logs:

```bash
grep -i "error" storage/logs/laravel.log | tail -20
```

## Database Issues

### Table not created

Error: "Table 'sisp_transactions' doesn't exist"

Run migrations:

```bash
php artisan migrate
```

If already run but table missing:

```bash
php artisan migrate:refresh --step=1
```

Check migration status:

```bash
php artisan migrate:status
```

### Encrypted fields not decrypting

Error: Garbled data when accessing `customer_email` or `customer_phone`

Verify your `APP_KEY` is set correctly:

```bash
php artisan key:generate
```

Check `.env`:

```env
APP_KEY=base64:your_key_here
```

If you changed the key, old encrypted data won't decrypt. This is expected - new transactions will encrypt/decrypt
correctly.

## Performance Issues

### Slow payment processing

Monitor query performance:

```php
use Illuminate\Support\Facades\DB;

DB::listen(function ($query) {
    if ($query->time > 1000) {  // > 1 second
        Log::warning('Slow query', [
            'query' => $query->sql,
            'time' => $query->time,
        ]);
    }
});
```

Common bottlenecks:

1. **Geolocation lookups**: Cached for 24 hours by default. Disable if not needed:

```env
SISP_COLLECT_METADATA=false
```

2. **Rate limit checks**: Use Redis cache for faster lookups. Configure in `config/cache.php`.

3. **Invoice generation**: Deferred to avoid blocking. Check queue worker:

```bash
php artisan queue:work
```

### High memory usage

Invoice PDF generation uses significant memory.

Optimize:

1. Increase PHP memory limit:

```bash
php -d memory_limit=512M artisan queue:work
```

2. Process invoices in background jobs instead of synchronously.

3. Delete old PDF files periodically:

```bash
php artisan sisp:cleanup-invoices --days=90
```

## Testing Issues

### Sandbox payment not working

Verify sandbox is enabled:

```env
SISP_SANDBOX=true
```

Access the sandbox gateway:

```
http://localhost:8000/sisp/sandbox
```

Fill in test details and submit. Should redirect to `/sisp/callback`.

### Test payment created but callback not processed

Check if the callback was received:

```php
php artisan tinker
>>> use Akira\Sisp\Models\Transaction;
>>> Transaction::latest()->first();
```

If status is still `pending`, callback wasn't processed. Check:

1. Callback route is accessible
2. No middleware blocking the callback
3. No exceptions thrown in CallbackController (check logs)

If the customer reached SISP but no callback arrived after about 5 minutes, query the SISP POS transaction-status API:

```bash
php artisan sisp:transaction-status <merchantRef>
```

Use `--transaction=<id> --update` only when you want to update the local transaction from a successful status API response. For accounting reconciliation, compare against the daily VBVT file generated by SISP.

## Debugging Tips

### Enable query logging

See all database queries:

```php
use Illuminate\Support\Facades\DB;

DB::enableQueryLog();

// ... your code ...

dd(DB::getQueryLog());
```

### Monitor events

See which events are dispatched:

```php
use Illuminate\Support\Facades\Event;

Event::listen('*', function ($event) {
    if (str_contains($event, 'Sisp')) {
        Log::info('Event dispatched: ' . $event);
    }
});
```

### Check rate limit cache

See cached rate limits:

```bash
php artisan tinker
>>> use Illuminate\Support\Facades\Cache;
>>> Cache::get('rate_limit:ip:192.168.1.1:payment')
```

### View transaction payload

See raw SISP response:

```php
php artisan tinker
>>> use Akira\Sisp\Models\Transaction;
>>> $transaction = Transaction::find('id');
>>> dd($transaction->payload);
```

## Getting Help

Check these resources:

1. **Package Repository**: https://github.com/kidiatoliny/laravel-sisp
2. **Issues**: Report bugs on GitHub issues
3. **Documentation**: Review the full documentation
4. **Logs**: Always check `storage/logs/laravel.log`
5. **Database**: Query models directly with `php artisan tinker`

## Next Steps

- Review [API Reference](./11-api-reference.md) for detailed documentation
- Check [Examples](./08-examples.md) for code samples

**Previous:** [Examples](08-examples.md) | **Next:** [FAQ](10-faq.md)
