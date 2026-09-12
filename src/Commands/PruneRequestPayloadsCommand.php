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
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

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

    private const string CURSOR_CACHE_KEY = 'sisp:prune-request-payloads:cursor';

    public function handle(Repository $config): int
    {
        $olderThan = $this->option('older-than');
        $days = $olderThan !== null
            ? (int) $olderThan
            : (int) $config->get('sisp.prune_request_payloads_after_days', 90);

        if ($days < 0) {
            $this->error('The --older-than option cannot be negative.');

            return self::FAILURE;
        }

        $limit = (int) ($this->option('limit') ?: 100);

        $cursor = (int) Cache::get(self::CURSOR_CACHE_KEY, 0);
        $lastId = $cursor;
        $pruned = 0;

        Transaction::query()
            ->whereIn('status', self::TERMINAL_STATUSES)
            ->where('created_at', '<=', now()->subDays($days))
            ->where('id', '>', $cursor)
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->each(function (Transaction $transaction) use (&$pruned, &$lastId): void {
                if ($this->prune($transaction)) {
                    $pruned++;
                }

                $lastId = max($lastId, (int) $transaction->id);
            });

        Cache::forever(self::CURSOR_CACHE_KEY, $lastId);

        if ($pruned === 0) {
            $this->info('No SISP request payloads needed pruning.');

            return self::SUCCESS;
        }

        $this->info("Pruned the purchase request payload from {$pruned} SISP transactions.");

        return self::SUCCESS;
    }

    private function prune(Transaction $transaction): bool
    {
        /** @var array<string, mixed>|string $payload */
        $payload = $transaction->payload;

        if (! is_array($payload)) {
            Log::warning('Skipped pruning an undecryptable SISP transaction payload.', [
                'transaction_id' => $transaction->id,
            ]);

            return false;
        }

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
