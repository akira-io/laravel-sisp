# Invoice Generation

Automatically generate and manage PDF invoices for completed payments.

## Auto-Generation

Invoices are automatically generated in two scenarios:

### 1. After Payment Submission

When a form is submitted to `POST /sisp/payment`, an invoice is created with status `pending`:

```php
// Happens automatically
$invoice = $transaction->invoice;
echo $invoice->status;        // 'pending'
echo $invoice->pdf_path;      // 'invoices/uuid.pdf'
```

The PDF is deferred and generated after the transaction is stored.

### 2. Configure Invoice Generation

Invoice generation requires company information in your `.env`:

```env
SISP_COMPANY_NAME="Your Company"
SISP_COMPANY_ADDRESS="Street Address, City"
SISP_COMPANY_CODE="VAT123456"
SISP_COMPANY_EMAIL="billing@company.com"
SISP_COMPANY_COUNTRY="CV"
SISP_COMPANY_PHONE="+238 XXXXXXX"
SISP_COMPANY_WEBSITE="https://yourcompany.com"
```

These are used on every invoice PDF.

## Invoice Attributes

```php
$invoice->invoice_number;     // Generated number (e.g., INV-20250101-001)
$invoice->invoice_date;       // Date invoice was created
$invoice->due_date;           // Due date (7 days from creation by default)
$invoice->status;             // pending/issued/paid/overdue/cancelled
$invoice->customer_name;      // Customer name from transaction
$invoice->customer_email;     // Customer email from transaction
$invoice->customer_city;      // Customer city from transaction
$invoice->customer_address;   // Customer address from transaction
$invoice->customer_country;   // Customer country from transaction
$invoice->pdf_path;           // Path to generated PDF file
$invoice->pdf_url;            // Publicly accessible URL to PDF (handles S3 temporary URLs)
$invoice->notes;              // Optional invoice notes
$invoice->metadata;           // Additional metadata (array)
```

## Access Invoice

```php
$transaction = Transaction::find($id);
$invoice = $transaction->invoice;

if ($invoice) {
    echo $invoice->invoice_number;
    echo $invoice->status->value;      // 'pending' (string)
}
```

## Invoice Statuses

```php
use Akira\Sisp\Enums\InvoiceStatus;

match ($invoice->status) {
    InvoiceStatus::pending => /* Not yet issued */,
    InvoiceStatus::issued => /* Sent to customer */,
    InvoiceStatus::paid => /* Payment confirmed */,
    InvoiceStatus::overdue => /* Past due date */,
    InvoiceStatus::cancelled => /* Cancelled */,
};
```

## Invoice Number Format

Invoice numbers are generated based on configuration:

```env
SISP_INVOICE_NUMBER_FORMAT=date-based
SISP_INVOICE_NUMBER_PREFIX=INV
```

Result: `INV-20250101-001`, `INV-20250101-002`, etc.

## PDF Storage

PDFs are stored in `storage/app/public/invoices/` with UUID filenames:

```php
echo $invoice->pdf_path;  // 'invoices/550e8400-e29b-41d4-a716-446655440000.pdf'

// Access full path
$fullPath = storage_path('app/public/' . $invoice->pdf_path);
```

## PDF Templates

Configure the invoice template:

```env
SISP_INVOICE_TEMPLATE=modern
```

Available templates:
- `modern` - Professional modern design with company branding
- `minimal` - Clean, simple invoice layout

## Query Invoices

```php
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Enums\InvoiceStatus;

// All invoices
$invoices = Invoice::all();

// By status
$pending = Invoice::where('status', InvoiceStatus::pending)->get();
$paid = Invoice::where('status', InvoiceStatus::paid)->get();

// By customer
$invoices = Invoice::where('customer_email', 'user@example.com')->get();

// With transaction
$invoices = Invoice::with('transaction')->get();

// Paginated
$invoices = Invoice::paginate(15);
```

## Invoice Items

Access line items from invoice:

```php
$transaction = $invoice->transaction;

foreach ($transaction->items as $item) {
    echo $item->product_name;
    echo $item->quantity;
    echo $item->unit_price;
    echo $item->total_price;
}
```

## Accessing Invoice PDFs

Use the `pdf_url` accessor to get a publicly accessible URL to the PDF. This automatically handles both local and S3 storage:

```php
$invoice = $transaction->invoice;

// Get the PDF URL (local storage)
if ($invoice->pdf_url) {
    echo $invoice->pdf_url;  // '/storage/invoices/550e8400-e29b-41d4-a716-446655440000.pdf'
}

// Get the PDF URL (S3 storage) - returns temporary signed URL
if ($invoice->pdf_url) {
    echo $invoice->pdf_url;  // 'https://s3.region.amazonaws.com/bucket/invoices/uuid.pdf?...'
}
```

### PDF Storage Configuration

Configure where invoices are stored:

```env
# Local storage (default)
SISP_INVOICE_DISK=public

# Or S3 storage
SISP_INVOICE_DISK=s3
```

### S3 Temporary URL Expiration

When using S3, PDFs are served via temporary signed URLs that expire after a configured time:

```env
# Set expiration time in hours (default: 24 hours)
SISP_INVOICE_TEMPORARY_URL_EXPIRATION_HOURS=24
```

The expiration time is configured per application, not per URL. URLs are generated fresh each time `pdf_url` is accessed.

### Using in Frontend

In your Inertia/React components, use the `pdf_url` directly:

```jsx
{invoice && invoice.pdf_url && (
  <a href={invoice.pdf_url} target="_blank" download={`${invoice.invoice_number}.pdf`}>
    Download Invoice
  </a>
)}
```

Or in Blade templates:

```blade
@if($invoice && $invoice->pdf_url)
    <a href="{{ $invoice->pdf_url }}" target="_blank" download="{{ $invoice->invoice_number }}.pdf">
        Download Invoice
    </a>
@endif
```

## Customize Invoice Data

Invoices copy customer data from transaction at creation time. To override invoice customer data:

```php
$invoice->update([
    'customer_name' => 'Different Name',
    'customer_email' => 'other@example.com',
    'notes' => 'Special order instructions',
]);
```

## Next Steps

- [Security](./07-security.md) - Rate limiting and security features