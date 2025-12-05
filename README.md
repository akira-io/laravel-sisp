# Laravel SISP Documentation

Welcome to **Laravel SISP** - a robust Laravel 12+ package for integrating SISP Cabo Verde payment gateway with
comprehensive transaction management, invoice generation, and fraud detection.

## Quick Start

```bash
composer require akira/laravel-sisp
php artisan laravel-sisp:install
```

Configure your `.env`:

```env
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_authorization_code
SISP_MERCHANT_ID=your_merchant_id
```

## Documentation Index

### Getting Started

- [Installation](./01-installation.md) - Install and configure the package
- [Configuration](./02-configuration.md) - Configure SISP credentials and options
- [Quick Start Guide](./03-quick-start.md) - Create your first payment in 5 minutes

### Core Concepts

- [Payment Flow](./04-payment-flow.md) - Complete payment process overview
- [Transaction Management](./05-transaction-management.md) - Create and manage transactions
- [Invoice Generation](./06-invoice-generation.md) - Auto-generate PDF invoices after payments

### Features & Security

- [Security](./07-security.md) - Rate limiting, metadata collection, fraud detection

### Learning & Reference

- [Examples](./08-examples.md) - Real-world integration examples and code samples
- [API Reference](./11-api-reference.md) - Complete API methods and classes
- [FAQ](./10-faq.md) - Frequently asked questions
- [Troubleshooting](./09-troubleshooting.md) - Common issues and solutions

## Key Features

- Payment form rendering (Blade or Inertia.js)
- Automatic PDF invoice generation
- Multi-item transaction support
- Comprehensive rate limiting
- Security metadata collection
- Complete transaction audit trail
- Webhook signature verification
- Type-safe DTOs and builders

## System Requirements

- PHP 8.4 or higher
- Laravel 12 or higher
- PostgreSQL or MySQL database
- Node.js for frontend assets (if using Inertia)

## What's Included

After installation, you get:

- Service provider and service container bindings
- Database migrations for transactions, invoices, and security tables
- Payment routes and webhook handling
- Blade views or Inertia.js components for payment forms
- Invoice generation via Laravel PDF Invoices package
- Rate limiting middleware
- Security metadata collection

## Package Structure

```
laravel-sisp/
├── src/
│   ├── Actions/              # Business logic
│   ├── Controllers/          # HTTP controllers
│   ├── DTO/                  # Data transfer objects
│   ├── Models/               # Eloquent models
│   ├── Facades/              # Facade classes
│   ├── Middleware/           # HTTP middleware
│   └── Providers/            # Service providers
├── database/
│   ├── migrations/           # Database migrations
│   └── factories/            # Model factories
├── resources/
│   ├── views/                # Blade templates
│   └── components/           # Vue/React components
├── config/
│   └── sisp.php             # Package configuration
└── docs/                     # This documentation
```

## Next Steps

1. Start with [Installation](./01-installation.md)
2. Follow [Configuration](./02-configuration.md) for your setup
3. Try the [Quick Start Guide](./03-quick-start.md)
4. Read [Payment Flow](./04-payment-flow.md) to understand the process

## Support

For issues or questions:

- Check [Troubleshooting](./09-troubleshooting.md)
- Review [Examples](./08-examples.md)
- Read [API Reference](./11-api-reference.md)
- Visit [FAQ](./10-faq.md) for common questions

## License

MIT License. See LICENSE file for details.

## Testing & Coverage

- Run full test suite with code coverage at exactly 100%:

  - `vendor/bin/pest --parallel --coverage --compact --exactly=100`

- Enforce 100% type coverage:

  - `vendor/bin/pest --type-coverage --min=100`

### Driving sisp:install in tests (no TTY, no mocks)

In tests, interactive prompts for `sisp:install` are controlled by config flags under `sisp.tests.*`. These flags are only read when `app()->runningUnitTests()` is true. In normal usage the command remains fully interactive.

Available toggles (bool):

- `sisp.tests.publish_config` / `sisp.tests.force_config`
- `sisp.tests.publish_migrations` / `sisp.tests.force_migrations`
- `sisp.tests.publish_inertia` / `sisp.tests.force_inertia`
- `sisp.tests.publish_blade` / `sisp.tests.force_blade`
- `sisp.tests.run_migrations` – whether to run migrations step
- `sisp.tests.fake_migrate` – short-circuit actual `migrate` call in tests (defaults to true)
- `sisp.tests.give_star` – whether to show the “give a star” note

Example (Pest test):

```php
config()->set('sisp.tests.publish_config', true);
config()->set('sisp.tests.publish_migrations', true);
config()->set('sisp.tests.run_migrations', true);
config()->set('sisp.tests.fake_migrate', true); // don’t run real migrations again
config()->set('sisp.tests.publish_inertia', false); // avoid vendor:publish in CI
config()->set('sisp.tests.publish_blade', false);
```

This keeps tests stable and fast in parallel CI runs while allowing full branch coverage without using mocks.
