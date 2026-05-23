<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Actions\QueryTransactionStatusAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Console\Command;

final class TransactionStatusCommand extends Command
{
    protected $signature = 'sisp:transaction-status
                            {merchantRef? : Merchant reference to query}
                            {--transaction= : Local transaction ID to query}
                            {--update : Update the local transaction status from the SISP response}';

    protected $description = 'Query the SISP POS transaction-status API for a merchant reference';

    public function handle(QueryTransactionStatusAction $queryTransactionStatus): int
    {
        $transaction = $this->resolveTransaction();
        $merchantRef = $transaction?->getAttribute('merchant_ref') ?? $this->argument('merchantRef');

        if (! is_string($merchantRef) || $merchantRef === '') {
            $this->error('Provide a merchantRef argument or --transaction option.');

            return self::FAILURE;
        }

        $response = $queryTransactionStatus->handle($transaction ?? $merchantRef);

        $this->line('Result: '.($response->result ? 'success' : 'failed'));
        $this->line('Payment: '.$response->paymentStatus()->value);
        $this->line("Description: {$response->transactionStatusDescription}");
        $this->line("Message: {$response->message}");

        if ($transaction instanceof Transaction && $this->option('update') && $response->result) {
            $transaction->update([
                'status' => $response->paymentStatus()->value,
                'merchant_response' => $response->transactionStatusDescription ?: $response->message,
            ]);

            $this->info('Local transaction updated.');
        }

        return self::SUCCESS;
    }

    private function resolveTransaction(): ?Transaction
    {
        $transactionId = $this->option('transaction');

        if (! is_string($transactionId) || $transactionId === '') {
            return null;
        }

        return Transaction::query()->findOrFail($transactionId);
    }
}
