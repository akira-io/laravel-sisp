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