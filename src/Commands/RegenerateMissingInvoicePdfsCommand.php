<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Models\Invoice;
use Illuminate\Console\Command;
use Throwable;

final class RegenerateMissingInvoicePdfsCommand extends Command
{
    protected $signature = 'sisp:regenerate-pdfs
                            {--limit= : Limit the number of invoices to process}';

    protected $description = 'Regenerate PDFs for paid invoices that are missing PDF files';

    public function handle(GenerateInvoicePdfAction $generatePdf): int
    {
        $this->info('🔍 Searching for paid invoices without PDFs...');

        $query = Invoice::query()
            ->with(['transaction.items'])
            ->where('status', InvoiceStatus::paid->value)
            ->whereNull('pdf_path');

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->info('✅ No invoices found that need PDF regeneration.');

            return self::SUCCESS;
        }

        $this->info("📄 Found {$invoices->count()} invoices without PDFs.");

        $progressBar = $this->output->createProgressBar($invoices->count());
        $progressBar->start();

        $successCount = 0;
        $errorCount = 0;

        foreach ($invoices as $invoice) {
            try {
                $generatePdf->handle($invoice);
                $successCount++;
            } catch (Throwable $e) {
                $errorCount++;
                $this->newLine();
                $this->error("❌ Failed to generate PDF for invoice #{$invoice->invoice_number}: {$e->getMessage()}");
            }

            $progressBar->advance();
        }

        $progressBar->finish();
        $this->newLine(2);

        $this->info("✅ Successfully generated {$successCount} PDFs");

        if ($errorCount > 0) {
            $this->warn("⚠️  Failed to generate {$errorCount} PDFs");
        }

        return self::SUCCESS;
    }
}
