# Installation

## Requirements

- **PHP** 8.4 or higher
- **Laravel** 11 or higher
- **Composer**

## Step 1: Install via Composer

```bash
composer require akira/laravel-sisp
```

## Step 2: Install Package Assets

Run the installation command which publishes config and runs migrations:

```bash
php artisan laravel-sisp:install
```

Or manually publish and migrate:

```bash
# Publish configuration
php artisan vendor:publish --tag="sisp-config"

# Run migrations
php artisan migrate
```

## Step 3: Configure Environment

Add these variables to your `.env` file:

```env
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_pos_authorization_code
SISP_MERCHANT_ID=your_merchant_id
```

## Step 4: Verify Installation

Test the installation by checking the routes are registered:

```bash
php artisan route:list | grep sisp
```

You should see three routes:
- `POST /sisp/payment` (sisp.payment)
- `POST /sisp/callback` (sisp.callback)
- `GET /sisp/fake-gateway` (sisp.sandbox)

## Step 5: Configure Rendering (Optional)

Choose your rendering engine:

### For Blade (Default)

```env
SISP_USE_BLADE=true
SISP_USE_INERTIA=false
```

### For Inertia.js

```env
SISP_USE_INERTIA=true
SISP_USE_BLADE=false
```

Then install Inertia components:

```bash
npm install @inertiajs/react @inertiajs/vue3
```

## What's Installed?

- ✅ Service provider registered
- ✅ Config published to `config/sisp.php`
- ✅ Migration created for `sisp_transactions` table
- ✅ Routes registered with prefix `/sisp`
- ✅ Blade views published (optional)

## Database Tables Created

The installation creates these tables:
- `sisp_transactions` - Payment transactions
- `sisp_transaction_items` - Transaction line items
- `sisp_invoices` - Generated invoices
- `sisp_request_metadata` - Security metadata (IP, device, geolocation)
- `sisp_rate_limits` - Rate limiting data
- `sisp_blacklist` - Blocked identifiers

See [Security & Fraud Detection](./security-and-fraud-detection.md) for details on security tables.

## Next Steps

1. [Quick Start Guide](./quick-start.md) - Get your first payment working
2. [Configuration Guide](./configuration.md) - Customize settings
3. [Payment Flow](./payment-flow.md) - Understand the complete flow
4. [Architecture](./architecture.md) - Learn package structure
5. [Security Setup](./security-and-fraud-detection.md) - Enable fraud detection

## Troubleshooting

### Routes not showing?

```bash
php artisan optimize:clear
php artisan route:cache
```

### Migration errors?

Ensure your database connection is configured in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_user
DB_PASSWORD=your_password
```

Then run:
```bash
php artisan migrate --force
```

### Config not loading?

```bash
php artisan config:clear
php artisan config:cache
```

### stevebauman/location package required?

The package uses [stevebauman/location](https://github.com/stevebauman/location) for geolocation. If you get errors about missing location functions, ensure it's installed:

```bash
composer require stevebauman/location
```

## Related Packages

The SISP package integrates with:
- **[Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices)** - Generate professional PDF invoices after payments
- **[stevebauman/location](https://github.com/stevebauman/location)** - IP geolocation for fraud detection

## Need Help?

- [Troubleshooting Guide](./troubleshooting/common-issues.md)
- [FAQ](./troubleshooting/faq.md)
- [Configuration Reference](./configuration.md)
- [Security & Fraud Detection](./security-and-fraud-detection.md)