<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as PdfInvoiceData;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

final class RealSispFlowUser implements Authenticatable
{
    public function __construct(
        public int $id,
        public string $email,
    ) {}

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): ?string
    {
        return null;
    }

    public function setRememberToken($value): void {}

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    public function can(string $ability, mixed $arguments = []): bool
    {
        return Gate::forUser($this)->check($ability, $arguments);
    }
}

beforeEach(function (): void {
    config([
        'sisp.sandbox' => true,
        'sisp.rate_limiting.enabled' => false,
        'sisp.invoice.company_name' => 'Akira Test',
        'sisp.invoice.company_address' => 'Rua Teste',
        'sisp.invoice.company_code' => 'NIF-123',
        'sisp.invoice.company_email' => 'billing@example.test',
        'sisp.invoice.company_country' => 'CV',
        'sisp.invoice.company_phone' => '+238 000 0000',
        'sisp.invoice.company_website' => 'https://example.test',
    ]);

    Storage::fake('public');
    app()->instance(PdfGeneratorContract::class, new class implements PdfGeneratorContract
    {
        public function generate(PdfInvoiceData $invoice, string $template = 'modern'): string
        {
            return '%PDF-INTEGRATION%';
        }

        public function save(PdfInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
        {
            return '%PDF-SAVED%';
        }
    });
});

it('runs a successful payment from payment request through signed callback and invoice rendering', function (): void {
    $this->post(route('sisp.payment'), real_sisp_flow_payment_payload(amount: 150.0))
        ->assertOk()
        ->assertSee('sisp-payment-form');

    defer()->invoke();

    $transaction = Transaction::query()->with(['items', 'invoice'])->sole();

    expect($transaction->items)->toHaveCount(1)
        ->and($transaction->customer_email)->toBe('buyer@example.test')
        ->and($transaction->invoice)->toBeInstanceOf(Invoice::class)
        ->and($transaction->invoice->status)->toBe(InvoiceStatus::pending);

    $payload = real_sisp_flow_callback_payload($transaction, 'success');

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => $transaction->merchant_ref]));

    $transaction->refresh();
    $invoice = $transaction->invoice()->firstOrFail();

    expect($transaction->status->value)->toBe('completed')
        ->and($transaction->transaction_id)->toBe($payload->transactionID)
        ->and($invoice->status)->toBe(InvoiceStatus::paid)
        ->and($invoice->pdf_path)->not->toBeNull();

    Storage::disk('public')->assertExists($invoice->pdf_path);

    $this->get(route('sisp.callback', ['ref' => $transaction->merchant_ref]))
        ->assertOk()
        ->assertSee($transaction->merchant_ref);
});

it('runs a failed payment flow and exposes signed retry without paying the invoice', function (): void {
    $this->post(route('sisp.payment'), real_sisp_flow_payment_payload(amount: 75.0))
        ->assertOk();

    defer()->invoke();

    $transaction = Transaction::query()->with('invoice')->sole();

    $this->post(route('sisp.callback'), real_sisp_flow_callback_payload($transaction, 'failed')->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => $transaction->merchant_ref]));

    $transaction->refresh();
    $invoice = $transaction->invoice()->firstOrFail();

    expect($transaction->status->value)->toBe('failed')
        ->and($invoice->status)->toBe(InvoiceStatus::cancelled)
        ->and($invoice->pdf_path)->toBeNull();

    $this->get(route('sisp.callback', ['ref' => $transaction->merchant_ref]))
        ->assertOk()
        ->assertSee('/sisp/retry-payment', false);
});

it('runs retry through the signed public route with the same SISP identifiers', function (): void {
    $transaction = Transaction::factory()->create([
        'amount' => 123.0,
        'currency' => '132',
        'status' => 'failed',
        'merchant_ref' => 'retry-reference',
        'merchant_session' => 'old-session',
        'transaction_code' => '1',
        'locale' => 'pt',
        'customer_email' => 'buyer@example.test',
        'customer_country' => 'CV',
        'customer_city' => 'Praia',
        'customer_address' => 'Rua Teste',
        'customer_postal_code' => '0000',
    ]);

    $this->post(URL::temporarySignedRoute('sisp.retry-payment', now()->addMinutes(30), ['transaction' => $transaction->id]))
        ->assertOk()
        ->assertSee('name="timeStamp"', false);

    $transaction->refresh();

    expect($transaction->merchant_ref)->toBe('retry-reference')
        ->and($transaction->merchant_session)->toBe('old-session');
});

it('runs authorized full refund through the public route', function (): void {
    Gate::define('refund', fn (RealSispFlowUser $user, Transaction $transaction): bool => true);

    $transaction = Transaction::factory()->create([
        'status' => 'completed',
        'amount' => 90.0,
        'transaction_id' => 'PAID-TID',
        'response_code' => '5',
        'customer_email' => 'buyer@example.test',
        'transaction_id' => 'TX-REFUND-1',
        'response_code' => '001',
    ]);

    $this->actingAs(new RealSispFlowUser(1, 'agent@example.test'))
        ->post(route('sisp.refund', $transaction), ['amount' => 90.0, 'reason' => 'integration'])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($transaction->refresh()->status->value)->toBe('refunded')
        ->and($transaction->merchant_response)->toBe('integration::90');
});

function real_sisp_flow_payment_payload(float $amount): array
{
    return [
        'amount' => $amount,
        'items' => [[
            'product_name' => 'Integration Ticket',
            'quantity' => 1,
            'unit_price' => $amount,
            'total_price' => $amount,
        ]],
        'customer_name' => 'Buyer Test',
        'customer_email' => 'buyer@example.test',
        'customer_phone' => '+238 000 0000',
        'customer_country' => 'CV',
        'customer_city' => 'Praia',
        'customer_address' => 'Rua Teste',
        'customer_postal_code' => '0000',
        'locale' => 'pt',
    ];
}

function real_sisp_flow_callback_payload(Transaction $transaction, string $status): Akira\Sisp\ValueObjects\CallbackPayload
{
    return Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => $transaction->amount,
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
        'timeStamp' => '2026-05-26 10:00:00',
        'currency' => $transaction->currency,
        'transactionCode' => $transaction->transaction_code ?? '1',
    ]), $status);
}
