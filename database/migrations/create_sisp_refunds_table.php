<?php

declare(strict_types=1);

use Akira\Sisp\Models\Refund;
use Akira\Sisp\Models\Transaction;
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
        $unreadable = [];

        Transaction::query()
            ->orderBy('id')
            ->chunkById(100, function (Collection $transactions) use ($refundsTable, &$copied, &$unreadable): void {
                $alreadyMigrated = DB::table($refundsTable)
                    ->whereIn('transaction_id', $transactions->modelKeys())
                    ->pluck('transaction_id')
                    ->all();

                foreach ($transactions as $transaction) {
                    if (in_array($transaction->getKey(), $alreadyMigrated, true)) {
                        continue;
                    }

                    $payload = $transaction->getAttribute('payload');

                    if (! is_array($payload)) {
                        if ($payload !== null) {
                            $unreadable[] = $transaction->getKey();
                        }

                        continue;
                    }

                    $refunds = $payload['refunds'] ?? [];

                    if (! is_array($refunds)) {
                        $unreadable[] = $transaction->getKey();

                        continue;
                    }

                    $entries = array_values(array_filter($refunds, is_array(...)));

                    if ($entries === []) {
                        continue;
                    }

                    $copied += DB::transaction(
                        fn (): int => $this->insertRefunds($transaction, $entries)
                    );
                }
            });

        Log::info('SISP refund history copied into the refunds table.', [
            'copied' => $copied,
            'unreadable_transaction_ids' => $unreadable,
        ]);

        if ($unreadable !== []) {
            Log::warning('SISP refund history could not be read for some transactions.', [
                'transaction_ids' => $unreadable,
                'copied' => $copied,
            ]);
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
