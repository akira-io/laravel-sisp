<?php

declare(strict_types=1);

namespace Akira\Sisp\Support;

use Akira\Sisp\Enums\InvoiceStatus;
use Akira\Sisp\Models\Invoice;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class Diagnostics
{
    /**
     * @return array{disk: string, path: string, driver: ?string, root: ?string, bucket: ?string}
     */
    public function configuration(): array
    {
        $disk = (string) config('sisp.invoice.disk', 'public');

        return [
            'disk' => $disk,
            'path' => $this->invoiceStoragePath(),
            'driver' => config("filesystems.disks.{$disk}.driver"),
            'root' => $disk === 'public' ? config('filesystems.disks.public.root') : null,
            'bucket' => $disk === 's3' ? config('filesystems.disks.s3.bucket') : null,
        ];
    }

    /**
     * @return array{accessible: bool, directory_writable: bool, error: ?string}
     */
    public function storage(): array
    {
        $disk = (string) config('sisp.invoice.disk', 'public');
        $path = $this->invoiceStoragePath();

        try {
            $accessible = Storage::disk($disk)->exists('');
            Storage::disk($disk)->makeDirectory($path);
            $testFile = $path.'/sisp-doctor-test.txt';
            Storage::disk($disk)->put($testFile, 'test');
            Storage::disk($disk)->delete($testFile);

            return ['accessible' => $accessible, 'directory_writable' => true, 'error' => null];
        } catch (Throwable $e) {
            return ['accessible' => false, 'directory_writable' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * @return array{total: int, paid: int, with_pdf: int, paid_without_pdf: int}
     */
    public function invoices(): array
    {
        return [
            'total' => Invoice::query()->count(),
            'paid' => Invoice::query()->where('status', InvoiceStatus::paid->value)->count(),
            'with_pdf' => Invoice::query()->whereNotNull('pdf_path')->count(),
            'paid_without_pdf' => Invoice::query()
                ->where('status', InvoiceStatus::paid->value)
                ->whereNull('pdf_path')
                ->count(),
        ];
    }

    /**
     * @return array{configuration: array<string, mixed>, storage: array<string, mixed>, invoices: array<string, mixed>}
     */
    public function all(): array
    {
        return [
            'configuration' => $this->configuration(),
            'storage' => $this->storage(),
            'invoices' => $this->invoices(),
        ];
    }

    private function invoiceStoragePath(): string
    {
        return mb_trim((string) config('sisp.invoice.path', 'invoices'), '/') ?: 'invoices';
    }
}
