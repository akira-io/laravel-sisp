<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

#[Signature('sisp:reconcile-pending
                            {--older-than= : Minimum pending age in minutes}
                            {--limit= : Maximum transactions to reconcile}
                            {--force : Run even when reconciliation is disabled in config}')]
#[Description('Reconcile old pending SISP transactions using the POS transaction-status API')]
final class ReconcilePendingTransactionsCommand extends Command
{
    public function handle(Repository $config, ReconcileTransactionStatusAction $reconcile): int
    {
        if (! (bool) $config->get('sisp.transaction_status.reconciliation_enabled', false) && ! $this->option('force')) {
            $this->warn('SISP transaction reconciliation is disabled.');

            return self::SUCCESS;
        }

        $olderThan = (int) ($this->option('older-than') ?: $config->get('sisp.transaction_status.reconcile_after_minutes', 5));
        $limit = (int) ($this->option('limit') ?: $config->get('sisp.transaction_status.reconcile_limit', 50));

        $query = Transaction::query()
            ->where('status', TransactionStatus::pending->value)
            ->where('message_type')
            ->where('created_at', '<=', now()->subMinutes($olderThan))
            ->oldest();

        $query->limit($limit);

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
