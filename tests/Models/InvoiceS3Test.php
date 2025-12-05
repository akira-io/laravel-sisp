<?php

declare(strict_types=1);

use Akira\PdfInvoices\Contracts\PdfGeneratorContract;
use Akira\PdfInvoices\DTO\InvoiceData as DtoInvoiceData;
use Akira\Sisp\Actions\GenerateInvoiceAction;
use Akira\Sisp\Actions\GenerateInvoicePdfAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\Filesystem\Cloud;
use Illuminate\Support\Facades\Storage;

it('generates s3 temporaryUrl for invoice pdf', function (): void {
    // Bind a simple PDF generator implementation
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

    // Register a minimal s3 driver with temporaryUrl support
    Storage::extend('s3', fn (): Cloud => new class implements Cloud
    {
        private array $data = [];

        public function url($path)
        {
            return 'https://fake-s3/'.$path;
        }

        public function temporaryUrl(string $path, $expiration, array $options = []): string
        {
            return 'https://fake-s3/'.$path.'?exp='.$expiration->getTimestamp();
        }

        public function get($path)
        {
            return $this->data[$path] ?? '';
        }

        public function put($path, $contents, $options = [])
        {
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
    });

    config()->set('sisp.invoice.disk', 's3');

    $t = Transaction::factory()->create(['locale' => 'pt']);
    $invoice = resolve(GenerateInvoiceAction::class)->handle($t);
    resolve(GenerateInvoicePdfAction::class)->handle($invoice);
    $invoice->refresh();

    expect($invoice->pdf_url)->toStartWith('https://fake-s3/')
        ->and($invoice->pdf_url)->toContain('?exp=');
});
