<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Transaction;

final readonly class InvoiceNumberGeneratorAction
{
    public function __construct(private LoadConfig $config) {}

    public function handle(Transaction $transaction): string
    {
        $format = $this->config->getInvoiceNumberFormat();

        return match ($format) {
            'sequential' => $this->generateSequential($transaction),
            'date-based' => $this->generateDateBased($transaction),
            default => $this->generateDefault($transaction),
        };
    }

    private function generateSequential(Transaction $transaction): string
    {
        $prefix = $this->config->getInvoiceNumberPrefix();
        $sequence = str_pad((string)$transaction->id, 6, '0', STR_PAD_LEFT);

        return "{$prefix}{$sequence}";
    }

    private function generateDateBased(Transaction $transaction): string
    {
        $prefix = $this->config->getInvoiceNumberPrefix();
        $year = $transaction->created_at->format('Y');
        $month = $transaction->created_at->format('m');
        $sequence = str_pad((string)$transaction->id, 6, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}{$month}-{$sequence}";
    }

    private function generateDefault(Transaction $transaction): string
    {
        return $this->generateDateBased($transaction);
    }
}
