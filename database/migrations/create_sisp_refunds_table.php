<?php

declare(strict_types=1);

use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\LegacyPayload;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $refundsTable = config('sisp.tables.refunds', 'sisp_refunds');

        if (! Schema::hasTable($refundsTable)) {
            Schema::create($refundsTable, function (Blueprint $table) use ($transactionsTable): void {
                $table->id();
                $table->foreignId('transaction_id')
                    ->constrained($transactionsTable)
                    ->cascadeOnDelete();
                $table->bigInteger('amount_thousandths');
                $table->decimal('amount', 13, 3);
                $table->string('reason')->nullable();
                $table->longText('request')->nullable();
                $table->timestamps();
            });
        }

        $this->copyLegacyRefunds();
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sisp.tables.refunds', 'sisp_refunds'));
    }

    private function copyLegacyRefunds(): void
    {
        $refundsTable = config('sisp.tables.refunds', 'sisp_refunds');
        $copied = 0;
        $undecodable = [];
        $leftBehind = [];
        $malformed = [];

        Transaction::query()
            ->orderBy('id')
            ->chunkById(100, function (Collection $transactions) use ($refundsTable, &$copied, &$undecodable, &$leftBehind, &$malformed): void {
                $alreadyMigrated = DB::table($refundsTable)
                    ->whereIn('transaction_id', $transactions->modelKeys())
                    ->pluck('transaction_id')
                    ->map(fn (mixed $id): int => (int) $id)
                    ->all();

                foreach ($transactions as $transaction) {
                    $id = (int) $transaction->getKey();

                    if (in_array($id, $alreadyMigrated, true)) {
                        continue;
                    }

                    $stored = $transaction->getAttribute('payload');

                    if ($stored === null) {
                        continue;
                    }

                    $payload = LegacyPayload::decode($stored);

                    if ($payload === null) {
                        if (str_contains((string) $transaction->getRawOriginal('payload'), 'refunds')) {
                            $leftBehind[] = $id;
                        } else {
                            $undecodable[] = $id;
                        }

                        continue;
                    }

                    $refunds = LegacyPayload::refunds($payload);

                    if ($refunds === null) {
                        $leftBehind[] = $id;

                        continue;
                    }

                    $entries = array_values(array_filter($refunds, is_array(...)));

                    if (count($entries) !== count($refunds)) {
                        $malformed[$id] = count($refunds) - count($entries);
                    }

                    if ($entries === []) {
                        continue;
                    }

                    $copied += DB::transaction(
                        fn (): int => $this->insertRefunds($transaction, $entries)
                    );
                }
            });

        $context = [
            'copied' => $copied,
            'undecodable_transaction_ids' => $undecodable,
            'refunds_left_behind_transaction_ids' => $leftBehind,
            'malformed_entries' => $malformed,
        ];

        Log::info('SISP refund history copied into the refunds table.', $context);

        if ($leftBehind !== []) {
            Log::error('SISP refund history exists but could not be copied for some transactions.', $context);
        }

        if ($undecodable !== [] || $malformed !== []) {
            Log::warning('SISP refund history could not be read for some transactions.', $context);
        }
    }

    /**
     * @param  array<int, array<array-key, mixed>>  $entries
     */
    private function insertRefunds(Transaction $transaction, array $entries): int
    {
        $createdAt = $transaction->getAttribute('refunded_at') ?? now();

        foreach ($entries as $entry) {
            $amount = $entry['amount'] ?? 0;
            $reason = $entry['reason'] ?? null;
            $request = $entry['request'] ?? [];

            $refund = new Refund();
            $refund->setAttribute('transaction_id', $transaction->getKey());
            $refund->setAttribute('amount', is_numeric($amount) ? $amount : 0);
            $refund->setAttribute('reason', is_string($reason) ? $reason : null);
            $refund->setAttribute('request', is_array($request) ? $request : []);
            $refund->setAttribute('created_at', $createdAt);
            $refund->setAttribute('updated_at', now());
            $refund->save();
        }

        return count($entries);
    }
};
