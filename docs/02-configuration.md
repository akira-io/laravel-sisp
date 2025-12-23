# Configuration Guide

All configuration is done through the `config/sisp.php` file or environment variables in `.env`.

## Required Configuration

### SISP Credentials

```env
SISP_URL=https://mc.vinti4net.cv/Client_VbV_v2/biz_vbv_clientdata.jsp
SISP_POS_ID=your_pos_id
SISP_POS_AUT_CODE=your_authorization_code
SISP_MERCHANT_ID=your_merchant_id
```

These credentials are provided by SISP when you register as a merchant.

### Currency and Language

```env
SISP_CURRENCY=132              # 132 = CVE (Cape Verde Escudo)
SISP_LANGUAGE_MESSAGES=en      # en or pt
```

The locale for each transaction is automatically stored in the `locale` field of the transaction record. This allows you to track and filter transactions by customer language preference.

## Optional Configuration

### 3D Secure

```env
SISP_IS_3D_SEC=1               # 1 = enabled, 0 = disabled
SISP_TRANSACTION_CODE=1        # Transaction type
```

### Sandbox Mode

```env
SISP_SANDBOX=true              # true for testing, false for production
```

When enabled, uses the fake gateway instead of real SISP server for testing.

## Invoice Configuration

### Company Information

```env
SISP_COMPANY_NAME="Your Company"
SISP_COMPANY_ADDRESS="Street Address, City"
SISP_COMPANY_CODE="VAT123456"
SISP_COMPANY_EMAIL="billing@company.com"
SISP_COMPANY_COUNTRY="CV"
SISP_COMPANY_PHONE="+238 XXXXXXX"
SISP_COMPANY_WEBSITE="https://company.com"
```

### Invoice Settings

```env
SISP_INVOICE_TEMPLATE=modern       # modern, minimal, or branded
SISP_INVOICE_NUMBER_FORMAT=date-based
SISP_INVOICE_NUMBER_PREFIX=INV
SISP_INVOICE_DISK=public           # Storage disk for PDFs
```

## Rendering Engine Configuration

### Blade Views (Default)

```env
SISP_USE_BLADE=true
SISP_USE_INERTIA=false
```

Renders payment forms using Blade templates.

### Inertia.js

```env
SISP_USE_INERTIA=true
SISP_USE_BLADE=false
SISP_INERTIA_PAYMENT_COMPONENT=sisp/payment-form
SISP_INERTIA_CALLBACK_COMPONENT=sisp/payment-response
```

For React or Vue 3 frontends. Choose ONE framework and install it:

```bash
npm install @inertiajs/react    # For React
# or
npm install @inertiajs/vue3     # For Vue 3
```

## Rate Limiting Configuration

```env
SISP_RATE_LIMITING_ENABLED=true

# Per IP limiting
SISP_RATE_LIMIT_PER_IP=true
SISP_RATE_LIMIT_PER_IP_LIMIT=100
SISP_RATE_LIMIT_PER_IP_WINDOW=3600

# Per merchant limiting
SISP_RATE_LIMIT_PER_MERCHANT=true
SISP_RATE_LIMIT_PER_MERCHANT_LIMIT=500
SISP_RATE_LIMIT_PER_MERCHANT_WINDOW=3600

# Per user limiting
SISP_RATE_LIMIT_PER_USER=true
SISP_RATE_LIMIT_PER_USER_LIMIT=50
SISP_RATE_LIMIT_PER_USER_WINDOW=3600
```

Window is in seconds. Limit is number of requests per window.

## Security Configuration

```env
SISP_COLLECT_METADATA=true         # Collect IP, device, geolocation
SISP_DETECT_VPN=true               # Detect VPN usage
SISP_DETECT_PROXY=true             # Detect proxy usage
SISP_CALCULATE_RISK_SCORE=true     # Calculate fraud risk score
SISP_BLOCK_VPN_PROXY=true          # Block VPN/proxy requests
SISP_REQUIRE_WHITELIST=false       # Require IP whitelist
```

### Amount Limits

```env
SISP_MAX_AMOUNT_PER_DAY=50000      # Max amount per day (in cents)
SISP_MAX_AMOUNT_PER_MONTH=200000   # Max amount per month (in cents)
```

### Geolocation

```env
SISP_GEOLOCATION_PROVIDER=maxmind  # maxmind or ip-api
MAXMIND_KEY=your_maxmind_key
IP_API_KEY=your_ip_api_key
SISP_GEOLOCATION_CACHE_TTL=1440    # Cache in minutes
```

## Database Tables

You can customize table names if needed:

```env
SISP_TABLE_TRANSACTIONS=sisp_transactions
SISP_TABLE_TRANSACTION_ITEMS=sisp_transaction_items
SISP_TABLE_INVOICES=sisp_invoices
SISP_TABLE_REQUEST_METADATA=sisp_request_metadata
SISP_TABLE_RATE_LIMITS=sisp_rate_limits
SISP_TABLE_BLACKLIST=sisp_blacklist
```

## Configuration via Code

You can also configure programmatically in a service provider:

```php
config([
    'sisp.url' => env('SISP_URL'),
    'sisp.posID' => env('SISP_POS_ID'),
    'sisp.posAutCode' => env('SISP_POS_AUT_CODE'),
    'sisp.merchantId' => env('SISP_MERCHANT_ID'),
]);
```

## Environment Variables Reference

```env
# SISP Credentials (Required)
SISP_URL=
SISP_POS_ID=
SISP_POS_AUT_CODE=
SISP_MERCHANT_ID=

# Currency & Language
SISP_CURRENCY=132
SISP_LANGUAGE_MESSAGES=en

# Transaction Settings
SISP_IS_3D_SEC=1
SISP_TRANSACTION_CODE=1

# Sandbox Mode
SISP_SANDBOX=true

# Invoice Configuration
SISP_COMPANY_NAME=
SISP_COMPANY_ADDRESS=
SISP_COMPANY_CODE=
SISP_COMPANY_EMAIL=
SISP_COMPANY_COUNTRY=CV
SISP_COMPANY_PHONE=
SISP_COMPANY_WEBSITE=
SISP_INVOICE_TEMPLATE=modern
SISP_INVOICE_NUMBER_FORMAT=date-based
SISP_INVOICE_NUMBER_PREFIX=INV
SISP_INVOICE_DISK=public

# Rendering Engine
SISP_USE_BLADE=true
SISP_USE_INERTIA=false
SISP_INERTIA_PAYMENT_COMPONENT=sisp/payment-form
SISP_INERTIA_CALLBACK_COMPONENT=sisp/payment-response

# Rate Limiting
SISP_RATE_LIMITING_ENABLED=true
SISP_RATE_LIMIT_PER_IP=true
SISP_RATE_LIMIT_PER_IP_LIMIT=100
SISP_RATE_LIMIT_PER_IP_WINDOW=3600
SISP_RATE_LIMIT_PER_MERCHANT=true
SISP_RATE_LIMIT_PER_MERCHANT_LIMIT=500
SISP_RATE_LIMIT_PER_MERCHANT_WINDOW=3600
SISP_RATE_LIMIT_PER_USER=true
SISP_RATE_LIMIT_PER_USER_LIMIT=50
SISP_RATE_LIMIT_PER_USER_WINDOW=3600

# Security
SISP_COLLECT_METADATA=true
SISP_DETECT_VPN=true
SISP_DETECT_PROXY=true
SISP_CALCULATE_RISK_SCORE=true
SISP_BLOCK_VPN_PROXY=true
SISP_REQUIRE_WHITELIST=false
SISP_MAX_AMOUNT_PER_DAY=
SISP_MAX_AMOUNT_PER_MONTH=

# Geolocation
SISP_GEOLOCATION_PROVIDER=maxmind
MAXMIND_KEY=
IP_API_KEY=
SISP_GEOLOCATION_CACHE_TTL=1440
```

## Next Steps

- Read [Quick Start Guide](./03-quick-start.md)
- Learn [Payment Flow](./04-payment-flow.md)

**Previous:** [Installation](01-installation.md) | **Next:** [Quick Start Guide](03-quick-start.md)