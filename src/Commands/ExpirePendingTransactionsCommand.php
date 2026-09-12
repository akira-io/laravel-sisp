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
            ->whereNull('message_type')
            ->where('created_at', '<=', now()->subDays($days))
            ->oldest()
            ->limit($limit)
            ->get();

        if ($transactions->isEmpty()) {
            $this->info('No pending SISP transactions are old enough to expire.');

            return self::SUCCESS;
        }

        foreach ($transactions as $transaction) {
            $cancel->handle($transaction, 'expired');
        }

        $this->info("Expired {$transactions->count()} pending SISP transactions older than {$days} days.");

        return self::SUCCESS;
    }
}
