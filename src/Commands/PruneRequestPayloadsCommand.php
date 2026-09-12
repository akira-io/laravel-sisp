<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;

#[Signature('sisp:prune-request-payloads
                            {--older-than= : Minimum transaction age in days}
                            {--limit= : Maximum transactions to prune}')]
#[Description('Remove the 3-D Secure purchase request blob from old terminal SISP transactions')]
final class PruneRequestPayloadsCommand extends Command
{
    private const array TERMINAL_STATUSES = [
        TransactionStatus::completed->value,
        TransactionStatus::failed->value,
        TransactionStatus::cancelled->value,
        TransactionStatus::refunded->value,
    ];

    public function handle(Repository $config): int
    {
        $days = (int) ($this->option('older-than') ?: $config->get('sisp.prune_request_payloads_after_days', 90));
        $limit = (int) ($this->option('limit') ?: 100);

        $pruned = 0;

        Transaction::query()
            ->whereIn('status', self::TERMINAL_STATUSES)
            ->where('created_at', '<=', now()->subDays($days))
            ->oldest()
            ->limit($limit)
            ->get()
            ->each(function (Transaction $transaction) use (&$pruned): void {
                if ($this->prune($transaction)) {
                    $pruned++;
                }
            });

        if ($pruned === 0) {
            $this->info('No SISP request payloads needed pruning.');

            return self::SUCCESS;
        }

        $this->info("Pruned the purchase request payload from {$pruned} SISP transactions.");

        return self::SUCCESS;
    }

    private function prune(Transaction $transaction): bool
    {
        $payload = $transaction->payload;

        if (! array_key_exists('purchaseRequest', $payload)) {
            return false;
        }

        unset($payload['purchaseRequest']);

        TransactionLogContext::run(
            'prune',
            fn (): bool => $transaction->update(['payload' => $payload])
        );

        return true;
    }
}
