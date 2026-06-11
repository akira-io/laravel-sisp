# Roadmap

This roadmap outlines future enhancements and improvements for Laravel SISP based on the current architecture and
extension points.

## Delivered in v2

- Laravel 13 and PHP 8.5 baseline, with native framework attributes throughout (`#[Fillable]`, `#[UseFactory]`, `#[Scope]`, `#[Signature]`, `#[Description]`, `#[Bind]`, `#[Singleton]`)
- Driver pattern: `SispManager` with `production` and `sandbox` drivers behind the `SispDriver` contract, extensible via `SispManager::extend()`
- Builder pattern: fluent `Sisp::payment()` and `Sisp::refund()` builders
- Pipeline pattern: configurable payment and callback pipelines with single-purpose pipes (custom action hooks at key lifecycle points)
- Contract-driven internals (`CallbackFingerprintValidator`, `SispCredentialsResolver`, `SispDriver`) for replaceable service implementations

## Transaction Features

**Enhanced Transaction Management**

- Full-amount SISP refund compliance helpers
- Split payments across multiple cards
- Recurring payment scheduling
- Payment plan installment tracking
- Automatic retry logic for failed transactions

**Transaction Queries**

- Advanced transaction filtering and search
- Export transactions to CSV/Excel
- Transaction analytics dashboard data
- Custom reporting queries

## Invoice System

**Invoice Customization**

The current invoice system uses Laravel PDF Invoices with basic customization. Extension points exist for:

- Multiple invoice template layouts
- Custom PDF generators
- Invoice branding and theming
- Multi-language invoice generation
- Invoice email automation with attachments
- Invoice versioning and amendments

**Tax Management**

- Multiple tax rate support
- Tax exemption handling
- Tax jurisdiction detection
- Automatic tax calculation based on location

## Security Enhancements

**Advanced Fraud Detection**

The existing security metadata collection and risk scoring can be extended with:

- Machine learning-based fraud detection
- Behavioral analysis patterns
- Transaction velocity checks
- Card fingerprinting
- 3D Secure v2 protocol support

**Compliance Features**

- PCI DSS compliance helpers
- GDPR data export and deletion
- Payment data encryption at rest
- Audit log export capabilities

## Developer Experience

**Testing Tools**

- Sandbox mode improvements
- Payment flow simulation helpers
- Webhook testing utilities
- Transaction factory builders

**Integration Utilities**

- Pre-built payment form components for Livewire
- React and Vue component libraries
- Webhook signature verification middleware
- Payment status polling utilities

**Events and Webhooks**

The package currently dispatches events for payment lifecycle. Future extensions include:

- Outbound webhook delivery to external services
- Webhook retry logic with exponential backoff
- Webhook payload signing
- Custom event listener registration

## Internationalization

- Multi-currency support beyond CVE
- Automatic currency conversion
- Localized payment form rendering
- Regional payment method preferences
- Translation file expansion

## Performance Optimizations

- Transaction query result caching
- Lazy loading optimization for relationships
- Background job processing for invoice generation
- Database index optimization recommendations

## API Enhancements

**Public API Layer**

- RESTful API for transaction management
- API authentication and rate limiting
- Transaction status webhooks
- API documentation generation

**Admin Interface**

- Optional admin panel for transaction monitoring
- Payment dispute management
- Customer blacklist management
- Rate limit configuration UI

## Extensibility

**Plugin System**

- Custom action hooks at key lifecycle points
- Replaceable service implementations
- Custom metadata collectors
- Payment flow modifiers

**Third-Party Integrations**

- Accounting software integration (QuickBooks, Xero)
- CRM synchronization (Salesforce, HubSpot)
- Analytics platform integration (Google Analytics, Mixpanel)
- Customer support tools (Intercom, Zendesk)

## Documentation

- Video tutorial series
- Integration example repositories
- Migration guides from other payment packages
- Architecture decision records
- Performance tuning guides

**Next:** [Installation](01-installation.md)
