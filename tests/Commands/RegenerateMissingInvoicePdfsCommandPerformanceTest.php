<?php

declare(strict_types=1);

namespace Akira\Sisp\Tests\Commands;

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Models\TransactionItem;
use Akira\Sisp\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class RegenerateMissingInvoicePdfsCommandPerformanceTest extends TestCase
{
    public function test_it_optimizes_queries_for_pdf_regeneration(): void
    {
        // Mock dependencies
        Storage::fake('public');
        $this->mock(PdfGeneratorContract::class)
            ->shouldReceive('generate')
            ->andReturn('dummy content');

        // Create data
        $count = 5;
        for ($i = 0; $i < $count; $i++) {
            $transaction = Transaction::factory()->create(['locale' => 'pt-CV']);
            TransactionItem::factory()->count(2)->forTransaction($transaction)->create();

            Invoice::create([
                'transaction_id' => $transaction->id,
                'invoice_number' => "INV-$i",
                'invoice_date' => now(),
                'due_date' => now()->addDays(30),
                'status' => InvoiceStatus::paid,
                'customer_name' => 'Test User',
                'customer_email' => 'test@example.com',
                'pdf_path' => null, // Needs regeneration
            ]);
        }

        // Ensure we have 5 invoices
        $this->assertEquals(5, Invoice::count());

        // Count queries
        DB::enableQueryLog();

        $this->artisan('sisp:regenerate-pdfs')
            ->assertExitCode(0);

        $queries = DB::getQueryLog();
        $queryCount = count($queries);

        // Expected queries (Unoptimized):
        // 1 (select invoices)
        // + 5 (select transaction for each invoice)
        // + 5 (select items for each transaction)
        // = 11 queries
        // Assuming some might be cached or optimized by Laravel internals, but definitely > 3

        // Optimized:
        // 1 (invoices)
        // 1 (transactions)
        // 1 (items)
        // = 3 queries (plus maybe some internal ones, but should be constant)

        // Expected: 1 (invoices) + 5 (transactions) + 5 (items) + 5 (updates) = 16
        // Optimized: 1 (invoices) + 1 (transactions) + 1 (items) + 5 (updates) = 8

        // Check if PDF generation succeeded
        $invoice = Invoice::first();
        $this->assertNotNull($invoice->pdf_path, "Invoice PDF path is null. Generation failed.");

        // Assert we have reduced queries
        $this->assertLessThan(10, $queryCount, "Expected optimized queries (< 10), got {$queryCount}");
    }
}
