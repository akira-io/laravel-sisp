# Customer Data Integration Guide

## Overview

Customer information must be sent to the SISP payment endpoint so that it can be stored in transactions and used for invoice generation.

## Required Integration

When NosFerry makes a POST request to `/sisp/payment`, it must include the following customer fields:

### Request Format

```json
{
  "amount": 100.00,
  "customer_name": "João Silva",
  "customer_email": "joao@example.com",
  "customer_phone": "+238 9999999",
  "customer_country": "CV",
  "items": [
    {
      "product_name": "Product Name",
      "quantity": 1,
      "unit_price": 100.00,
      "total_price": 100.00
    }
  ]
}
```

### Field Specifications

| Field | Type | Required | Max Length | Notes |
|-------|------|----------|-----------|-------|
| `customer_name` | string | optional | 255 | Full name of customer |
| `customer_email` | string | optional | 255 | Valid email format |
| `customer_phone` | string | optional | 20 | Phone number |
| `customer_country` | string | optional | 2 | ISO 3166-1 alpha-2 code (e.g., "CV", "PT", "US") |

## Data Flow

```
NosFerry Frontend
    ↓
    Collects customer information from user
    ↓
POST /sisp/payment
    ↓
StorePaymentRequest (validates fields)
    ↓
PaymentController
    ↓
CreateAndStorePaymentTransactionAction
    ├→ CustomerData::from($request->all())
    ├→ StoreCustomerDataAction (updates transaction)
    └→ GenerateInvoiceAction (uses customer data)
    ↓
sisp_transactions table
    (customer_name, customer_email, customer_phone, customer_country columns)
    ↓
PDF Invoice Generation
    (populated with customer information)
```

## Validation Rules

The fields are validated according to these rules:

```php
'customer_name' => ['sometimes', 'string', 'max:255'],
'customer_email' => ['sometimes', 'email', 'max:255'],
'customer_phone' => ['sometimes', 'string', 'max:20'],
'customer_country' => ['sometimes', 'string', 'max:2'],
```

- All fields are optional (`sometimes`)
- Email must be a valid email format
- Phone and country have length restrictions

## Important Notes

1. **Field Names Matter**: Use `customer_` prefix exactly as shown
2. **No Data Transformation**: Fields are stored as-is in the database
3. **Invoice Generation**: Customer data is used to populate invoice PDFs
4. **Database Storage**: Data is persisted in `sisp_transactions` table for future reference

## Example cURL Request

```bash
curl -X POST http://localhost/sisp/payment \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100.00,
    "customer_name": "João Silva",
    "customer_email": "joao@example.com",
    "customer_phone": "+238 9999999",
    "customer_country": "CV",
    "items": [
      {
        "product_name": "Passagem Aérea",
        "quantity": 1,
        "unit_price": 100.00,
        "total_price": 100.00
      }
    ]
  }'
```

## Verification

After successful payment, you can verify customer data was stored by checking the `sisp_transactions` table:

```sql
SELECT customer_name, customer_email, customer_phone, customer_country
FROM sisp_transactions
WHERE merchant_ref = 'your-merchant-ref';
```