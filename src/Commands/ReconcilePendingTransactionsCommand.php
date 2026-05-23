<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Console\Command;

final class ReconcilePendingTransactionsCommand extends Command
{
    protected $signature = 'sisp:reconcile-pending
                            {--older-than= : Minimum pending age in minutes}
                            {--limit= : Maximum transactions to reconcile}';

    protected $description = 'Reconcile pending SISP transactions using the transaction status API';

    public function handle(LoadConfig $config, ReconcileTransactionStatusAction $reconcile): int
    {
        if (! $config->isTransactionStatusReconciliationEnabled()) {
            $this->warn('SISP transaction status reconciliation is disabled.');

            return self::SUCCESS;
        }

        $olderThan = (int) ($this->option('older-than') ?: $config->getTransactionStatusIndeterminateAfterMinutes());

        $query = Transaction::query()
            ->where('status', TransactionStatus::pending->value)
            ->whereNull('message_type')
            ->where('created_at', '<=', now()->subMinutes($olderThan))
            ->oldest();

        if ($limit = $this->option('limit')) {
            $query->limit((int) $limit);
        }

        $transactions = $query->get();

        if ($transactions->isEmpty()) {
            $this->info('No pending SISP transactions require reconciliation.');

            return self::SUCCESS;
        }

        $reconciled = 0;

        foreach ($transactions as $transaction) {
            $before = $transaction->status;
            $updated = $reconcile->handle($transaction);

            if ($updated->status !== $before) {
                $reconciled++;
            }
        }

        $this->info("Reconciled {$reconciled} of {$transactions->count()} pending SISP transactions.");

        return self::SUCCESS;
    }
}
