<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Models\Invoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class DoctorCommand extends Command
{
    protected $signature = 'sisp:doctor';

    protected $description = 'Diagnose issues with invoice PDF generation';

    public function handle(): int
    {
        $this->info('🔍 Diagnosing Invoice PDF Issues...');
        $this->newLine();

        // Check configuration
        $this->checkConfiguration();
        $this->newLine();

        // Check storage
        $this->checkStorage();
        $this->newLine();

        // Check invoices
        $this->checkInvoices();
        $this->newLine();

        return self::SUCCESS;
    }

    private function checkConfiguration(): void
    {
        $this->info('📋 Configuration Check:');

        $disk = config('sisp.invoice.disk', 'public');
        $this->line("  Disk: <info>{$disk}</info>");

        $path = config('sisp.invoice.path', 'invoices');
        $this->line("  Path: <info>{$path}</info>");

        $driver = config("filesystems.disks.{$disk}.driver");
        $this->line("  Driver: <info>{$driver}</info>");

        if ($disk === 'public') {
            $root = config('filesystems.disks.public.root');
            $this->line("  Root: <info>{$root}</info>");
        }

        if ($disk === 's3') {
            $bucket = config('filesystems.disks.s3.bucket');
            $this->line("  S3 Bucket: <info>{$bucket}</info>");
        }
    }

    private function checkStorage(): void
    {
        $this->info('💾 Storage Check:');

        $disk = config('sisp.invoice.disk', 'public');

        try {
            $exists = Storage::disk($disk)->exists('');
            $this->line($exists
                ? '  ✅ Storage disk is accessible'
                : '  ❌ Storage disk is not accessible');
        } catch (Throwable $e) {
            $this->error("  ❌ Error accessing storage: {$e->getMessage()}");
        }

        // Check if directory is writable
        $path = config('sisp.invoice.path', 'invoices');

        try {
            Storage::disk($disk)->makeDirectory($path);
            $this->line('  ✅ Invoice directory exists or was created');
        } catch (Throwable $e) {
            $this->error("  ❌ Cannot create invoice directory: {$e->getMessage()}");
        }

        // Try to write a test file
        try {
            $testFile = $path.'/test.txt';
            Storage::disk($disk)->put($testFile, 'test');
            Storage::disk($disk)->delete($testFile);
            $this->line('  ✅ Can write to invoice directory');
        } catch (Throwable $e) {
            $this->error("  ❌ Cannot write to invoice directory: {$e->getMessage()}");
        }
    }

    private function checkInvoices(): void
    {
        $this->info('📄 Invoice Status:');

        $totalInvoices = Invoice::query()->count();
        $this->line("  Total invoices: <info>{$totalInvoices}</info>");

        $paidInvoices = Invoice::query()->where('status', InvoiceStatus::paid->value)->count();
        $this->line("  Paid invoices: <info>{$paidInvoices}</info>");

        $invoicesWithPdf = Invoice::query()->whereNotNull('pdf_path')->count();
        $this->line("  Invoices with PDF: <info>{$invoicesWithPdf}</info>");

        $paidWithoutPdf = Invoice::query()->where('status', InvoiceStatus::paid->value)
            ->whereNull('pdf_path')
            ->count();

        if ($paidWithoutPdf > 0) {
            $this->warn("  ⚠️  {$paidWithoutPdf} paid invoices are missing PDFs");

            // Show sample
            $sample = Invoice::query()->where('status', InvoiceStatus::paid->value)
                ->whereNull('pdf_path')
                ->with('transaction')
                ->first();

            if ($sample) {
                $this->newLine();
                $this->line('  Sample invoice without PDF:');
                $this->line("    Invoice: <info>#{$sample->invoice_number}</info>");
                $this->line("    Customer: <info>{$sample->customer_name}</info>");
                $this->line("    Status: <info>{$sample->status->value}</info>");
                $this->line("    Transaction Status: <info>{$sample->transaction?->status->value}</info>");
                $this->line("    Created: <info>{$sample->created_at}</info>");
            }

            $this->newLine();
            $this->line('  💡 Run <info>php artisan sisp:regenerate-pdfs</info> to generate missing PDFs');
        } else {
            $this->line('  ✅ All paid invoices have PDFs');
        }
    }
}
