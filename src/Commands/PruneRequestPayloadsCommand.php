<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands;

use Akira\Sisp\Commands\Concerns\ValidatesIntegerOptions;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

#[Signature('sisp:prune-request-payloads
                            {--older-than= : Minimum transaction age in days}
                            {--limit= : Maximum transactions to prune}')]
#[Description('Remove the 3-D Secure purchase request blob from old terminal SISP transactions')]
final class PruneRequestPayloadsCommand extends Command
{
    use ValidatesIntegerOptions;

    private const array TERMINAL_STATUSES = [
        TransactionStatus::completed->value,
        TransactionStatus::failed->value,
        TransactionStatus::cancelled->value,
        TransactionStatus::refunded->value,
    ];

    public function handle(Repository $config): int
    {
        if ($this->rejectsIntegerOption('older-than', 0, 'The --older-than option must be a whole number of days, zero or more.')) {
            return self::FAILURE;
        }

        if ($this->rejectsIntegerOption('limit', 1, 'The --limit option must be a whole number of at least 1.')) {
            return self::FAILURE;
        }

        $olderThan = $this->option('older-than');
        $days = $olderThan !== null
            ? (int) $olderThan
            : (int) $config->get('sisp.prune_request_payloads_after_days', 90);

        $limit = (int) ($this->option('limit') ?: 100);
        $pruned = 0;
        $processed = 0;

        $candidates = Transaction::query()
            ->whereIn('status', self::TERMINAL_STATUSES)
            ->where('created_at', '<=', now()->subDays($days))
            ->whereNull('request_payload_pruned_at')
            ->lazyById();

        foreach ($candidates as $transaction) {
            $outcome = $this->prune($transaction);

            if ($outcome === null) {
                continue;
            }

            if ($outcome) {
                $pruned++;
            }

            $processed++;

            if ($processed >= $limit) {
                break;
            }
        }

        if ($pruned === 0) {
            $this->info('No SISP request payloads needed pruning.');

            return self::SUCCESS;
        }

        $this->info("Pruned the purchase request payload from {$pruned} SISP transactions.");

        return self::SUCCESS;
    }

    private function prune(Transaction $transaction): ?bool
    {
        return DB::transaction(function () use ($transaction): ?bool {
            $locked = $transaction->newQuery()
                ->whereKey($transaction->getKey())
                ->whereNull('request_payload_pruned_at')
                ->lockForUpdate()
                ->first();

            if (! $locked instanceof Transaction) {
                return false;
            }

            /** @var array<string, mixed>|string|null $payload */
            $payload = $locked->payload;

            if (is_string($payload)) {
                Log::warning('Skipped pruning an undecryptable SISP transaction payload.', [
                    'transaction_id' => $locked->id,
                ]);

                return null;
            }

            if ($payload === null || ! array_key_exists('purchaseRequest', $payload)) {
                TransactionLogContext::run(
                    'prune',
                    fn (): bool => $locked->update(['request_payload_pruned_at' => now()])
                );

                return false;
            }

            unset($payload['purchaseRequest']);

            TransactionLogContext::run(
                'prune',
                fn (): bool => $locked->update([
                    'payload' => $payload,
                    'request_payload_pruned_at' => now(),
                ])
            );

            return true;
        });
    }
}
