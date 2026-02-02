<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

it('exits when no paid invoices are missing PDFs', function (): void {
    $code = Artisan::call('sisp:regenerate-pdfs');
    $output = Artisan::output();

    expect($code)->toBe(0)
        ->and($output)->toContain('No invoices found that need PDF regeneration.');
});

it('regenerates missing PDFs with limits and reports failures', function (): void {
    app()->instance(PdfGeneratorContract::class, new class implements PdfGeneratorContract
    {
        public function generate(DtoInvoiceData $invoice, string $template = 'modern'): string
        {
            return '%PDF%';
        }

        public function save(DtoInvoiceData $invoice, string $template = 'modern', ?string $path = null): string
        {
            return '%PDF-SAVED%';
        }
    });

    $driver = new class implements Cloud
    {
        private array $data = [];

        private int $puts = 0;

        public function url($path)
        {
            return 'https://fake/'.$path;
        }

        public function temporaryUrl(string $path, $expiration, array $options = []): string
        {
            return 'https://fake/'.$path.'?exp='.$expiration->getTimestamp();
        }

        public function get($path)
        {
            return $this->data[$path] ?? '';
        }

        public function put($path, $contents, $options = [])
        {
            $this->puts++;
            throw_if($this->puts === 2, RuntimeException::class, 'boom');
            $this->data[$path] = (string) $contents;

            return true;
        }

        public function exists($path)
        {
            return array_key_exists($path, $this->data);
        }

        public function missing($path): bool
        {
            return ! $this->exists($path);
        }

        public function download($path, $name = null, array $headers = []): string
        {
            return '';
        }

        public function path($path)
        {
            return $path;
        }

        public function size($path)
        {
            return mb_strlen($this->data[$path] ?? '');
        }

        public function lastModified($path)
        {
            return time();
        }

        public function copy($from, $to)
        {
            $this->data[$to] = $this->data[$from] ?? '';

            return true;
        }

        public function move($from, $to)
        {
            $this->copy($from, $to);
            unset($this->data[$from]);

            return true;
        }

        public function delete($paths)
        {
            foreach ((array) $paths as $p) {
                unset($this->data[$p]);
            }

            return true;
        }

        public function readStream($path)
        {
            return fopen('data://text/plain,'.($this->data[$path] ?? ''), 'r');
        }

        public function writeStream($path, $resource, array $options = [])
        {
            $this->data[$path] = stream_get_contents($resource) ?: '';

            return true;
        }

        public function putFile($path, $file = null, $options = [])
        {
            return $path;
        }

        public function putFileAs($path, $file, $name = null, $options = [])
        {
            return $path.'/'.($name ?? 'file');
        }

        public function prepend($path, $data, $separator = PHP_EOL)
        {
            $this->data[$path] = ($this->data[$path] ?? '').$separator.$data;

            return true;
        }

        public function append($path, $data, $separator = PHP_EOL)
        {
            return $this->prepend($path, $data, $separator);
        }

        public function files($directory = null, $recursive = false)
        {
            return array_keys($this->data);
        }

        public function allFiles($directory = null)
        {
            return $this->files($directory, true);
        }

        public function directories($directory = null, $recursive = false)
        {
            return [];
        }

        public function allDirectories($directory = null)
        {
            return [];
        }

        public function makeDirectory($path)
        {
            return true;
        }

        public function deleteDirectory($path)
        {
            return true;
        }

        public function getVisibility($path)
        {
            return 'public';
        }

        public function setVisibility($path, $visibility)
        {
            return true;
        }
    };

    Storage::extend('throwing', fn (): Cloud => $driver);
    config()->set('sisp.invoice.disk', 'throwing');
    config()->set('filesystems.disks.throwing', [
        'driver' => 'throwing',
    ]);

    $transactionOne = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
    ]);
    $transactionTwo = Transaction::factory()->create([
        'status' => TransactionStatus::completed->value,
    ]);

    $invoiceOne = Invoice::query()->create([
        'transaction_id' => $transactionOne->id,
        'invoice_number' => 'INV-3001',
        'invoice_date' => now(),
        'due_date' => now()->addDay(),
        'status' => InvoiceStatus::paid->value,
        'customer_name' => 'First Customer',
    ]);
    $invoiceTwo = Invoice::query()->create([
        'transaction_id' => $transactionTwo->id,
        'invoice_number' => 'INV-3002',
        'invoice_date' => now(),
        'due_date' => now()->addDays(2),
        'status' => InvoiceStatus::paid->value,
        'customer_name' => 'Second Customer',
    ]);

    $code = Artisan::call('sisp:regenerate-pdfs', ['--limit' => 2]);
    $output = Artisan::output();

    expect($code)->toBe(0)
        ->and($output)->toContain('Found 2 invoices without PDFs.')
        ->and($output)->toContain('Failed to generate PDF for invoice #')
        ->and($output)->toContain('Successfully generated 1 PDFs')
        ->and($output)->toContain('Failed to generate 1 PDFs');
});
