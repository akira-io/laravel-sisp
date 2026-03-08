# Installation Guide

## Requirements

- PHP 8.3 or higher
- Laravel 12 or higher
- Composer
- Database (PostgreSQL or MySQL)

## Installation Steps

### Step 1: Install via Composer

```bash
composer require akira/laravel-sisp
```

### Step 2: Run Installation Command

```bash
php artisan laravel-sisp:install
```

This command will:
- Publish configuration file
- Run database migrations
- Create required database tables
- Register service provider

#### Non‑interactive in CI (optional)

When running in CI/tests without a TTY, you can drive the install prompts via config flags. These flags are only read when the app is running unit tests.

```php
// In your test bootstrap or a specific test before calling the command:
config()->set('sisp.tests.publish_config', true);
config()->set('sisp.tests.publish_migrations', true);
config()->set('sisp.tests.run_migrations', true);
config()->set('sisp.tests.fake_migrate', true); // don’t re-run real migrations in tests

// Disable publish steps to keep paratest stable
config()->set('sisp.tests.publish_inertia', false);
config()->set('sisp.tests.publish_blade', false);
```

Then call:

```php
Artisan::call('sisp:install', ['--no-interaction' => true]);
```

### Step 3: Verify Installation

Confirm the package is installed by checking the routes:

```bash
php artisan route:list | grep sisp
```

You should see these routes:
- `POST /sisp/payment` - Submit payment form
- `POST /sisp/callback` - Receive SISP callbacks
- `GET /sisp/fake-gateway` - Sandbox testing endpoint

### Step 4: Configure Environment

Add these variables to your `.env` file:

```env
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_authorization_code
SISP_MERCHANT_ID=your_merchant_id
SISP_SANDBOX=true
```

You'll receive these credentials from SISP when you register as a merchant.

### Step 5: Configure Invoice Generation (Optional)

If you want automatic PDF invoice generation after payments:

```env
SISP_COMPANY_NAME="Your Company Name"
SISP_COMPANY_ADDRESS="Your Address"
SISP_COMPANY_CODE="Your VAT/Tax ID"
SISP_COMPANY_EMAIL="billing@yourcompany.com"
SISP_COMPANY_COUNTRY="CV"
SISP_COMPANY_PHONE="+238 XXXXXXX"
SISP_COMPANY_WEBSITE="https://yourcompany.com"
SISP_INVOICE_TEMPLATE="modern"
```

### Step 6: Choose Rendering Engine

By default, Blade views are enabled. Choose your rendering engine:

**For Blade (default):**
```env
SISP_USE_BLADE=true
SISP_USE_INERTIA=false
```

**For Inertia.js:**
```env
SISP_USE_INERTIA=true
SISP_USE_BLADE=false
```

Then install Inertia components. Choose ONE:

**For React:**
```bash
npm install @inertiajs/react
```

**For Vue 3:**
```bash
npm install @inertiajs/vue3
```

## Verify Installation

Run the following to verify everything is working:

```bash
php artisan laravel-sisp:verify
```

## Database Tables Created

The installation creates these tables:

- `sisp_transactions` - Payment transactions
- `sisp_transaction_items` - Line items for transactions
- `sisp_invoices` - Generated PDF invoices
- `sisp_request_metadata` - Security and fraud detection data
- `sisp_rate_limits` - Rate limiting tracking
- `sisp_blacklist` - Blocked IPs/identifiers

## What's Installed?

- Service provider registered in `config/app.php`
- Configuration file published to `config/sisp.php`
- Database migrations created
- Routes registered with `/sisp` prefix
- Blade views published (if using Blade)

## Troubleshooting

### Routes not showing?

```bash
php artisan optimize:clear
php artisan route:cache
```

### Migration errors?

Ensure your database connection is configured:

```bash
php artisan migrate --force
```

### Config not loading?

```bash
php artisan config:clear
php artisan config:cache
```

### Missing dependencies?

The package requires `stevebauman/location` for geolocation. If you get errors:

```bash
composer require stevebauman/location
```

## Next Steps

1. Read [Configuration Guide](./02-configuration.md)
2. Try [Quick Start Guide](./03-quick-start.md)
3. Understand [Payment Flow](./04-payment-flow.md)

**Next:** [Configuration](02-configuration.md)
