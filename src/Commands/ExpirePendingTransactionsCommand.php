<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use LogicException;

#[Signature('sisp:expire-pending
                            {--older-than= : Minimum pending age in days}
                            {--limit= : Maximum transactions to expire}')]
#[Description('Cancel pending SISP transactions that never received a callback')]
final class ExpirePendingTransactionsCommand extends Command
{
    public function handle(Repository $config, CancelTransactionAction $cancel): int
    {
        $olderThan = $this->option('older-than');
        $days = $olderThan !== null ? (int) $olderThan : (int) $config->get('sisp.expire_pending_after_days', 30);

        if ($days < 1) {
            $this->error('The --older-than option must be at least 1 day.');

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

        foreach ($transactions as $transaction) {
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

        return self::SUCCESS;
    }
}
