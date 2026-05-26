<?php

declare(strict_types=1);

use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('reports healthy storage and invoices on public disk', function (): void {
    Storage::fake('public');
    config()->set('sisp.invoice.disk', 'public');
    config()->set('sisp.invoice.path', 'billing/pdfs');
    config()->set('filesystems.disks.public.driver', 'local');
    config()->set('filesystems.disks.public.root', storage_path('app/public'));

    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
    ]);

    Invoice::query()->create([
        'transaction_id' => $transaction->id,
        'invoice_number' => 'INV-1001',
        'invoice_date' => now(),
        'status' => InvoiceStatus::paid->value,
        'customer_name' => 'Test Customer',
        'pdf_path' => 'invoices/test.pdf',
    ]);

    $code = Artisan::call('sisp:doctor');
    $output = Artisan::output();

    expect($code)->toBe(0)
        ->and($output)->toContain('Configuration Check:')
        ->and($output)->toContain('Disk: ')
        ->and($output)->toContain('Path: ')
        ->and($output)->toContain('billing/pdfs')
        ->and($output)->toContain('Driver: ')
        ->and($output)->toContain('Root: ')
        ->and($output)->toContain('Storage disk is accessible')
        ->and($output)->toContain('Invoice directory exists or was created')
        ->and($output)->toContain('Can write to invoice directory')
        ->and($output)->toContain('All paid invoices have PDFs');
});

it('reports storage errors and missing invoices on s3 disk', function (): void {
    config()->set('sisp.invoice.disk', 's3');
    config()->set('sisp.invoice.path', 'invoices');
    config()->set('filesystems.disks.s3.bucket', 'test-bucket');

    Storage::shouldReceive('disk')
        ->times(3)
        ->andThrow(new RuntimeException('boom'));

    $transaction = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
    ]);

    Invoice::query()->create([
        'transaction_id' => $transaction->id,
        'invoice_number' => 'INV-2001',
        'invoice_date' => now(),
        'status' => InvoiceStatus::paid->value,
        'customer_name' => 'Missing Pdf',
    ]);

    $code = Artisan::call('sisp:doctor');
    $output = Artisan::output();

    expect($code)->toBe(0)
        ->and($output)->toContain('S3 Bucket: ')
        ->and($output)->toContain('Error accessing storage:')
        ->and($output)->toContain('Cannot create invoice directory:')
        ->and($output)->toContain('Cannot write to invoice directory:')
        ->and($output)->toContain('paid invoices are missing PDFs')
        ->and($output)->toContain('Sample invoice without PDF:')
        ->and($output)->toContain('Transaction Status:');
});
