<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Actions\QueryTransactionStatusAction;
use Akira\Sisp\Actions\ReconcileTransactionStatusAction;
use Akira\Sisp\Commands\Concerns\ValidatesIntegerOptions;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use LogicException;
use RuntimeException;
use Throwable;

#[Signature('sisp:expire-pending
                            {--older-than= : Minimum pending age in days}
                            {--limit= : Maximum transactions to expire}')]
#[Description('Cancel pending SISP transactions that never received a callback')]
final class ExpirePendingTransactionsCommand extends Command
{
    use ValidatesIntegerOptions;

    public function handle(
        Repository $config,
        CancelTransactionAction $cancel,
        QueryTransactionStatusAction $queryTransactionStatus,
        ReconcileTransactionStatusAction $reconcile,
    ): int {
        if ($this->rejectsIntegerOption('older-than', 1, 'The --older-than option must be a whole number of days, at least 1.')) {
            return self::FAILURE;
        }

        if ($this->rejectsIntegerOption('limit', 1, 'The --limit option must be a whole number of at least 1.')) {
            return self::FAILURE;
        }

        $olderThan = $this->option('older-than');
        $days = $olderThan !== null ? (int) $olderThan : (int) $config->get('sisp.expire_pending_after_days', 30);

        if ($days < 1) {
            $this->error('The sisp.expire_pending_after_days setting must be at least 1 day.');

            return self::FAILURE;
        }

        $limit = (int) ($this->option('limit') ?: 100);

        $transactions = Transaction::query()
            ->where('status', TransactionStatus::pending->value)
            ->where(fn (Builder $query): Builder => $query->whereNull('message_type')->orWhere('message_type', ''))
            ->where('created_at', '<=', now()->subDays($days))
            ->oldest()
            ->limit($limit)
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No pending SISP transactions are old enough to expire.');

            return self::SUCCESS;
        }

        $expired = 0;
        $skipped = 0;
        $settled = 0;

        $reconciliationEnabled = (bool) $config->get('sisp.transaction_status.reconciliation_enabled', false);

        foreach ($transactions as $transaction) {
            if ($reconciliationEnabled && ! $this->isStillPendingAtSisp($transaction, $queryTransactionStatus, $reconcile)) {
                $settled++;

                continue;
            }

            try {
                $cancel->handle($transaction, 'expired');
                $expired++;
            } catch (LogicException $exception) {
                $skipped++;

                Log::warning('Skipped expiring a SISP transaction that changed state before it could be cancelled.', [
                    'transaction_id' => $transaction->id,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $this->info("Expired {$expired} pending SISP transactions older than {$days} days.");

        if ($skipped > 0) {
            $this->info("Skipped {$skipped} transactions that could not be cancelled.");
        }

        if ($settled > 0) {
            $this->info("Left {$settled} transactions that SISP settled or could not be asked about.");
        }

        return self::SUCCESS;
    }

    private function isStillPendingAtSisp(
        Transaction $transaction,
        QueryTransactionStatusAction $queryTransactionStatus,
        ReconcileTransactionStatusAction $reconcile,
    ): bool {
        try {
            $response = $queryTransactionStatus->handle($transaction);

            throw_unless($response->answered, RuntimeException::class, $response->message);

            return $reconcile->applyResponse($transaction, $response)->status === TransactionStatus::pending;
        } catch (Throwable $exception) {
            Log::warning('Skipped expiring a SISP transaction whose status could not be queried.', [
                'transaction_id' => $transaction->id,
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
