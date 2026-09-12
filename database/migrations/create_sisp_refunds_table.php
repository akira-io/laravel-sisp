<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\SispAmount;
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

        Schema::create($refundsTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained($transactionsTable)
                ->cascadeOnDelete();
            $table->decimal('amount', 13, 2);
            $table->bigInteger('amount_cents');
            $table->string('reason')->nullable();
            $table->longText('request')->nullable();
            $table->timestamps();

            $table->index('transaction_id');
        });

        $this->copyLegacyRefunds($refundsTable);
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sisp.tables.refunds', 'sisp_refunds'));
    }

    private function copyLegacyRefunds(string $refundsTable): void
    {
        $copied = 0;
        $unreadable = [];

        Transaction::query()
            ->orderBy('id')
            ->chunkById(100, function (Collection $transactions) use (&$copied, &$unreadable, $refundsTable): void {
                foreach ($transactions as $transaction) {
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

                    foreach ($refunds as $refund) {
                        if (! is_array($refund)) {
                            continue;
                        }

                        $amount = (float) ($refund['amount'] ?? 0);

                        DB::table($refundsTable)->insert([
                            'transaction_id' => $transaction->getKey(),
                            'amount' => $amount,
                            'amount_cents' => SispAmount::toCents($amount),
                            'reason' => $refund['reason'] ?? null,
                            'request' => json_encode($refund['request'] ?? [], JSON_THROW_ON_ERROR),
                            'created_at' => $transaction->getAttribute('refunded_at') ?? now(),
                            'updated_at' => now(),
                        ]);

                        $copied++;
                    }
                }
            });

        if ($unreadable !== []) {
            Log::warning('SISP refund history could not be read for some transactions.', [
                'transaction_ids' => $unreadable,
                'copied' => $copied,
            ]);
        }
    }
};
