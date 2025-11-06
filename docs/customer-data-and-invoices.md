# Customer Data & Invoice Generation

Complete guide to collecting customer information during payment and generating professional PDF invoices.

## Overview

The Laravel SISP package supports capturing customer data during the payment request and automatically generating PDF invoices after successful payment. Customer information is stored in both the transaction and invoice records for audit and reference purposes.

## Supported Customer Fields

The following customer fields can be collected during payment:

| Field | Type | Required | Max Length | Description |
|-------|------|----------|-----------|-------------|
| `customer_name` | string | No | 255 | Customer's full name |
| `customer_email` | email | No | 255 | Customer's email address |
| `customer_phone` | string | No | 20 | Customer's phone number |
| `customer_country` | string | No | 2 | ISO 3166-1 alpha-2 country code (e.g., CV, PT, US) |
| `customer_city` | string | No | 255 | Customer's city |
| `customer_address` | string | No | 255 | Customer's street address |

## Collecting Customer Data

### From API Requests (JSON)

Send customer information along with payment data:

```json
POST /payment
Content-Type: application/json

{
    "amount": 815.50,
    "items": [
        {
            "product_id": "PROD_001",
            "product_name": "Premium Widget",
            "quantity": 1,
            "unit_price": 815.50,
            "total_price": 815.50
        }
    ],
    "customer_name": "João Silva",
    "customer_email": "joao@example.com",
    "customer_phone": "+238-9876-5432",
    "customer_country": "CV",
    "customer_city": "Praia",
    "customer_address": "Rua Principal 123"
}
```

### From HTML Forms (Blade)

Render form fields for customer data:

```blade
<form action="{{ route('payment.store') }}" method="POST">
    @csrf

    <!-- Customer Information Section -->
    <fieldset>
        <legend>Customer Information</legend>

        <div>
            <label for="customer_name">Full Name</label>
            <input
                type="text"
                id="customer_name"
                name="customer_name"
                placeholder="João Silva"
                value="{{ old('customer_name') }}"
                class="form-control"
            >
            @error('customer_name')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="customer_email">Email Address</label>
            <input
                type="email"
                id="customer_email"
                name="customer_email"
                placeholder="joao@example.com"
                value="{{ old('customer_email') }}"
                class="form-control"
            >
            @error('customer_email')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="customer_phone">Phone Number</label>
            <input
                type="tel"
                id="customer_phone"
                name="customer_phone"
                placeholder="+238-9876-5432"
                value="{{ old('customer_phone') }}"
                class="form-control"
            >
            @error('customer_phone')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="customer_country">Country</label>
            <select
                id="customer_country"
                name="customer_country"
                class="form-control"
            >
                <option value="">Select Country</option>
                <option value="CV" {{ old('customer_country') === 'CV' ? 'selected' : '' }}>Cape Verde</option>
                <option value="PT" {{ old('customer_country') === 'PT' ? 'selected' : '' }}>Portugal</option>
                <option value="US" {{ old('customer_country') === 'US' ? 'selected' : '' }}>United States</option>
                <option value="BR" {{ old('customer_country') === 'BR' ? 'selected' : '' }}>Brazil</option>
            </select>
            @error('customer_country')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="customer_city">City</label>
            <input
                type="text"
                id="customer_city"
                name="customer_city"
                placeholder="Praia"
                value="{{ old('customer_city') }}"
                class="form-control"
            >
            @error('customer_city')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>

        <div>
            <label for="customer_address">Street Address</label>
            <input
                type="text"
                id="customer_address"
                name="customer_address"
                placeholder="Rua Principal 123"
                value="{{ old('customer_address') }}"
                class="form-control"
            >
            @error('customer_address')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>
    </fieldset>

    <!-- Items Section (existing) -->
    <!-- ... -->

    <button type="submit" class="btn btn-primary">Proceed to Payment</button>
</form>
```

### From React/Vue with Inertia

Collect customer data in your frontend component:

```jsx
import { useForm } from '@inertiajs/react'
import { useState } from 'react'

export default function Checkout({ cartItems, total }) {
    const { data, setData, post, processing, errors } = useForm({
        amount: total,
        items: cartItems.map(item => ({
            product_id: item.id,
            product_name: item.name,
            quantity: item.quantity,
            unit_price: item.price,
            total_price: item.totalPrice,
            description: item.description,
        })),
        customer_name: '',
        customer_email: '',
        customer_phone: '',
        customer_country: 'CV',
        customer_city: '',
        customer_address: '',
    })

    const handleChange = (e) => {
        const { name, value } = e.target
        setData(name, value)
    }

    const handleSubmit = (e) => {
        e.preventDefault()
        post(route('payment.store'))
    }

    return (
        <form onSubmit={handleSubmit} className="space-y-6">
            {/* Cart Items Section */}
            <div>
                <h2 className="text-lg font-semibold">Order Items</h2>
                <table className="w-full">
                    <thead>
                        <tr className="border-b">
                            <th className="text-left">Product</th>
                            <th className="text-center">Quantity</th>
                            <th className="text-right">Price</th>
                            <th className="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {cartItems.map(item => (
                            <tr key={item.id} className="border-b">
                                <td>{item.name}</td>
                                <td className="text-center">{item.quantity}</td>
                                <td className="text-right">${item.price.toFixed(2)}</td>
                                <td className="text-right font-semibold">${item.totalPrice.toFixed(2)}</td>
                            </tr>
                        ))}
                    </tbody>
                    <tfoot>
                        <tr className="text-lg font-semibold">
                            <td colSpan="3" className="text-right">Total:</td>
                            <td className="text-right">${total.toFixed(2)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            {/* Customer Information Section */}
            <fieldset className="border-t pt-6">
                <legend className="text-lg font-semibold mb-4">Customer Information</legend>

                <div className="grid grid-cols-2 gap-4">
                    <div>
                        <label className="block text-sm font-medium mb-1">Full Name</label>
                        <input
                            type="text"
                            name="customer_name"
                            value={data.customer_name}
                            onChange={handleChange}
                            placeholder="João Silva"
                            className={`w-full px-3 py-2 border rounded ${errors.customer_name ? 'border-red-500' : 'border-gray-300'}`}
                        />
                        {errors.customer_name && (
                            <p className="text-red-500 text-sm mt-1">{errors.customer_name}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Email Address</label>
                        <input
                            type="email"
                            name="customer_email"
                            value={data.customer_email}
                            onChange={handleChange}
                            placeholder="joao@example.com"
                            className={`w-full px-3 py-2 border rounded ${errors.customer_email ? 'border-red-500' : 'border-gray-300'}`}
                        />
                        {errors.customer_email && (
                            <p className="text-red-500 text-sm mt-1">{errors.customer_email}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Phone Number</label>
                        <input
                            type="tel"
                            name="customer_phone"
                            value={data.customer_phone}
                            onChange={handleChange}
                            placeholder="+238-9876-5432"
                            className={`w-full px-3 py-2 border rounded ${errors.customer_phone ? 'border-red-500' : 'border-gray-300'}`}
                        />
                        {errors.customer_phone && (
                            <p className="text-red-500 text-sm mt-1">{errors.customer_phone}</p>
                        )}
                    </div>

                    <div>
                        <label className="block text-sm font-medium mb-1">Country</label>
                        <select
                            name="customer_country"
                            value={data.customer_country}
                            onChange={handleChange}
                            className={`w-full px-3 py-2 border rounded ${errors.customer_country ? 'border-red-500' : 'border-gray-300'}`}
                        >
                            <option value="CV">Cape Verde</option>
                            <option value="PT">Portugal</option>
                            <option value="US">United States</option>
                            <option value="BR">Brazil</option>
                        </select>
                        {errors.customer_country && (
                            <p className="text-red-500 text-sm mt-1">{errors.customer_country}</p>
                        )}
                    </div>

                    <div className="col-span-2">
                        <label className="block text-sm font-medium mb-1">City</label>
                        <input
                            type="text"
                            name="customer_city"
                            value={data.customer_city}
                            onChange={handleChange}
                            placeholder="Praia"
                            className={`w-full px-3 py-2 border rounded ${errors.customer_city ? 'border-red-500' : 'border-gray-300'}`}
                        />
                        {errors.customer_city && (
                            <p className="text-red-500 text-sm mt-1">{errors.customer_city}</p>
                        )}
                    </div>

                    <div className="col-span-2">
                        <label className="block text-sm font-medium mb-1">Street Address</label>
                        <input
                            type="text"
                            name="customer_address"
                            value={data.customer_address}
                            onChange={handleChange}
                            placeholder="Rua Principal 123"
                            className={`w-full px-3 py-2 border rounded ${errors.customer_address ? 'border-red-500' : 'border-gray-300'}`}
                        />
                        {errors.customer_address && (
                            <p className="text-red-500 text-sm mt-1">{errors.customer_address}</p>
                        )}
                    </div>
                </div>
            </fieldset>

            <button
                type="submit"
                disabled={processing}
                className="w-full px-6 py-3 bg-blue-600 text-white rounded font-semibold hover:bg-blue-700 disabled:opacity-50"
            >
                {processing ? 'Processing...' : 'Proceed to Payment'}
            </button>
        </form>
    )
}
```

## Data Flow: Storing Customer Information

### Step 1: Request Validation

Customer data is validated in `StorePaymentRequest`:

```php
'customer_name' => ['sometimes', 'string', 'max:255'],
'customer_email' => ['sometimes', 'email', 'max:255'],
'customer_phone' => ['sometimes', 'string', 'max:20'],
'customer_country' => ['sometimes', 'string', 'max:2'],
'customer_city' => ['sometimes', 'string', 'max:255'],
'customer_address' => ['sometimes', 'string', 'max:255'],
```

All customer fields are optional (`sometimes`), but if provided, they must match their validation rules.

### Step 2: Store in Transaction

Customer data is stored in the `sisp_transactions` table:

```php
// Transaction record includes:
- customer_name
- customer_email
- customer_phone
- customer_country
- customer_city
- customer_address
- customer_postal_code (if applicable)
```

### Step 3: Denormalize to Invoice

When an invoice is generated, customer data is copied from the transaction to the invoice for independent reference:

```php
// Invoice record includes the same customer fields
- customer_name
- customer_email
- customer_phone
- customer_country
- customer_city
- customer_address
```

This denormalization ensures invoices remain complete even if transaction records are modified.

## Generating Invoices with Customer Data

### Automatic Invoice Generation (Event Listener)

Create a listener that generates invoices automatically after successful payment:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\ValueObjects\InvoiceData;

final readonly class GenerateInvoiceOnPaymentCompleted
{
    public function __construct(
        private GenerateInvoiceAction $generateInvoice,
        private GenerateInvoicePdfAction $generatePdf,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $transaction = $event->transaction;

        // Generate invoice record
        $invoiceData = InvoiceData::from([
            'invoice_number' => '', // Auto-generated
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'notes' => 'Thank you for your purchase!',
            'metadata' => [
                'order_id' => $transaction->merchant_ref,
                'payment_method' => 'SISP',
            ],
        ]);

        $invoice = $this->generateInvoice->handle($transaction, $invoiceData);

        // Generate PDF file
        $pdfPath = $this->generatePdf->handle($invoice);

        // Optionally: Send email with PDF
        // Mail::send(new InvoiceMail($invoice, $pdfPath));
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    PaymentCompleted::class => [
        GenerateInvoiceOnPaymentCompleted::class,
    ],
];
```

### Manual Invoice Generation

Generate invoices on-demand:

```php
<?php

use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\InvoiceData;

// Get completed transaction
$transaction = Transaction::where('id', 123)
    ->where('status', 'completed')
    ->firstOrFail();

// Create invoice action
$generateInvoice = app(GenerateInvoiceAction::class);

// Generate invoice with customer data
$invoiceData = InvoiceData::from([
    'invoice_number' => '', // Auto-generated
    'invoice_date' => now(),
    'due_date' => now()->addDays(30),
    'notes' => 'Thank you for your purchase!',
]);

$invoice = $generateInvoice->handle($transaction, $invoiceData);

// Generate PDF
$generatePdf = app(GenerateInvoicePdfAction::class);
$pdfPath = $generatePdf->handle($invoice);

// Access customer data from invoice
echo $invoice->customer_name;     // João Silva
echo $invoice->customer_email;    // joao@example.com
echo $invoice->customer_address;  // Rua Principal 123
echo $invoice->customer_city;     // Praia
echo $invoice->customer_country;  // CV
```

## Invoice Data Structure

### Accessing Customer Data from Invoices

```php
<?php

use Akira\Sisp\Models\Invoice;

$invoice = Invoice::findOrFail(1);

// Customer Information
$invoice->customer_name;    // string
$invoice->customer_email;   // string
$invoice->customer_phone;   // string|null
$invoice->customer_address; // string|null
$invoice->customer_city;    // string|null
$invoice->customer_country; // string|null (ISO 3166-1 alpha-2)

// Invoice Details
$invoice->invoice_number;   // string (INV-202511-000001)
$invoice->invoice_date;     // Carbon
$invoice->due_date;         // Carbon
$invoice->status;           // string (pending|issued|paid|overdue|cancelled)
$invoice->pdf_path;         // string (path to PDF file)

// Relationships
$invoice->transaction;      // Transaction model
$invoice->transaction->items; // Collection of TransactionItems
```

## Invoice PDF Customization

The invoice PDF uses the [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) package. Customer data is automatically included in the PDF:

### Minimal Template

```blade
<!-- Customer information section -->
<div class="customer">
    <strong>{{ $invoice->customer_name }}</strong><br>
    {{ $invoice->customer_address }}<br>
    {{ $invoice->customer_city }}, {{ $invoice->customer_country }}<br>
    {{ $invoice->customer_email }}<br>
    {{ $invoice->customer_phone }}
</div>
```

### Custom Attributes

Add custom attributes to invoices that appear in PDFs:

```php
// When creating invoice
$invoice = Invoice::create([
    'transaction_id' => $transaction->id,
    'invoice_number' => 'INV-2025-001',
    'customer_name' => 'João Silva',
    'customer_email' => 'joao@example.com',
    'metadata' => [
        'company_name' => 'Tech Solutions Ltd',
        'tax_id' => '123456789',
        'bank_account' => 'PT50 AIVB 0000 0000 0000 0000',
    ],
]);
```

Access in PDF template:

```blade
@if($invoice->metadata)
    @if($invoice->metadata['company_name'] ?? false)
        <p>Company: {{ $invoice->metadata['company_name'] }}</p>
    @endif
    @if($invoice->metadata['tax_id'] ?? false)
        <p>Tax ID: {{ $invoice->metadata['tax_id'] }}</p>
    @endif
@endif
```

## Email Invoices to Customers

Send PDF invoices via email:

```php
<?php

declare(strict_types=1);

namespace App\Listeners;

use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Mail\InvoiceMail;
use Akira\Sisp\ValueObjects\InvoiceData;
use Illuminate\Support\Facades\Mail;

final readonly class EmailInvoiceOnPaymentCompleted
{
    public function __construct(
        private GenerateInvoiceAction $generateInvoice,
        private GenerateInvoicePdfAction $generatePdf,
    ) {}

    public function handle(PaymentCompleted $event): void
    {
        $transaction = $event->transaction;

        // Generate invoice
        $invoiceData = InvoiceData::from([
            'invoice_number' => '',
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
        ]);

        $invoice = $this->generateInvoice->handle($transaction, $invoiceData);
        $pdfPath = $this->generatePdf->handle($invoice);

        // Send email with PDF attachment
        if ($transaction->customer_email) {
            Mail::send(new InvoiceMail($invoice, $pdfPath));
        }
    }
}
```

Create the mailable:

```php
<?php

declare(strict_types=1);

namespace App\Mail;

use Akira\Sisp\Models\Invoice;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

final class InvoiceMail extends Mailable
{
    public function __construct(
        public Invoice $invoice,
        public string $pdfPath,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Invoice ' . $this->invoice->invoice_number,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'mails.invoice',
            with: [
                'invoice' => $this->invoice,
            ],
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as('invoice_' . $this->invoice->invoice_number . '.pdf'),
        ];
    }
}
```

Create the email template:

```blade
<!-- resources/views/mails/invoice.blade.php -->

<h1>Invoice {{ $invoice->invoice_number }}</h1>

<p>Dear {{ $invoice->customer_name }},</p>

<p>Thank you for your purchase! Your invoice is attached below.</p>

<table>
    <tr>
        <td><strong>Invoice Number:</strong></td>
        <td>{{ $invoice->invoice_number }}</td>
    </tr>
    <tr>
        <td><strong>Invoice Date:</strong></td>
        <td>{{ $invoice->invoice_date->format('Y-m-d') }}</td>
    </tr>
    <tr>
        <td><strong>Due Date:</strong></td>
        <td>{{ $invoice->due_date->format('Y-m-d') }}</td>
    </tr>
</table>

<p>If you have any questions, please contact us.</p>

<p>Best regards,<br>Our Team</p>
```

## Common Patterns

### Pre-fill Customer Data from Authenticated User

```php
<?php

use Illuminate\Support\Facades\Auth;

public function checkout()
{
    $user = Auth::user();

    return inertia('Checkout', [
        'cartItems' => $this->getCartItems(),
        'defaultCustomer' => [
            'customer_name' => $user->name,
            'customer_email' => $user->email,
            'customer_phone' => $user->phone,
            'customer_country' => $user->country,
            'customer_city' => $user->city,
            'customer_address' => $user->address,
        ],
    ]);
}
```

### Retrieve Customer from Database

```php
<?php

use App\Models\Customer;

$customerId = request('customer_id');
$customer = Customer::findOrFail($customerId);

$paymentData = [
    'amount' => $total,
    'items' => $items,
    'customer_name' => $customer->full_name,
    'customer_email' => $customer->email,
    'customer_phone' => $customer->phone,
    'customer_country' => $customer->country_code,
    'customer_city' => $customer->city,
    'customer_address' => $customer->address,
];
```

### Query Invoices by Customer

```php
<?php

use Akira\Sisp\Models\Invoice;

// Get all invoices for a customer
$invoices = Invoice::where('customer_email', 'joao@example.com')
    ->orderByDesc('invoice_date')
    ->get();

// Get invoices by customer name (partial match)
$invoices = Invoice::where('customer_name', 'like', '%João%')
    ->orderByDesc('invoice_date')
    ->get();

// Get invoices by country
$invoices = Invoice::where('customer_country', 'CV')
    ->orderByDesc('invoice_date')
    ->get();

// Get unpaid invoices for a customer
$unpaidInvoices = Invoice::where('customer_email', 'joao@example.com')
    ->whereIn('status', ['pending', 'issued', 'overdue'])
    ->get();
```

## See Also

- [Payment Flow](./payment-flow.md) - Complete payment process overview
- [E-Commerce Transactions](./e-commerce-transactions.md) - Transaction patterns with items
- [Events & Monitoring](./events-and-monitoring.md) - Event-driven architecture
- [Configuration Reference](./configuration.md) - Invoice configuration options
- [API Reference](./api-reference.md) - Complete API documentation
- [Laravel PDF Invoices](https://packages.akira-io.com/packages/laravel-pdf-invoices) - Invoice generation library