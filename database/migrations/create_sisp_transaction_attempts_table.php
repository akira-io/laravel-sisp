<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $attemptsTable = config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');

        $this->guardAgainstExistingDuplicates($transactionsTable);

        Schema::create($attemptsTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained($transactionsTable)
                ->onDelete('cascade');
            $table->unsignedInteger('attempt_number');
            $table->string('merchant_ref');
            $table->string('merchant_session');
            $table->string('status')->default('pending');
            $table->string('gateway_transaction_id')->nullable();
            $table->string('message_type')->nullable();
            $table->string('response_code')->nullable();
            $table->text('merchant_response')->nullable();
            $table->text('fingerprint')->nullable();
            $table->longText('payload')->nullable();
            $table->longText('callback_payload')->nullable();
            $table->string('failure_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('callback_received_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->unique('merchant_session');
            $table->unique(['merchant_ref', 'merchant_session']);
            $table->unique(['transaction_id', 'attempt_number']);
            $table->index(['transaction_id', 'status']);
            $table->index('gateway_transaction_id');
        });

        $this->backfillAttempts($transactionsTable, $attemptsTable);

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->unique('merchant_ref');
        });
    }

    public function down(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $attemptsTable = config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');

        Schema::dropIfExists($attemptsTable);

        Schema::table($transactionsTable, function (Blueprint $table): void {
            $table->dropUnique($table->getTable().'_merchant_ref_unique');
        });
    }

    private function guardAgainstExistingDuplicates(string $transactionsTable): void
    {
        throw_if($this->hasDuplicates($transactionsTable, ['merchant_ref']), RuntimeException::class, 'Cannot add SISP uniqueness constraints because duplicate merchant_ref values already exist.');

        throw_if($this->hasDuplicates($transactionsTable, ['merchant_session']), RuntimeException::class, 'Cannot backfill SISP attempts because duplicate merchant_session values already exist.');
    }

    /**
     * @param  list<string>  $columns
     */
    private function hasDuplicates(string $table, array $columns): bool
    {
        $query = DB::table($table)
            ->select($columns)
            ->whereNotNull($columns[0])
            ->groupBy($columns);

        foreach ($columns as $column) {
            $query->where($column, '!=', '');
        }

        return $query->havingRaw('COUNT(*) > 1')->exists();
    }

    private function backfillAttempts(string $transactionsTable, string $attemptsTable): void
    {
        DB::table($transactionsTable)
            ->orderBy('id')
            ->select([
                'id',
                'merchant_ref',
                'merchant_session',
                'status',
                'transaction_id',
                'message_type',
                'response_code',
                'merchant_response',
                'fingerprint',
                'payload',
                'created_at',
                'updated_at',
            ])
            ->chunkById(100, function ($transactions) use ($attemptsTable): void {
                foreach ($transactions as $transaction) {
                    DB::table($attemptsTable)->insert([
                        'transaction_id' => $transaction->id,
                        'attempt_number' => 1,
                        'merchant_ref' => $transaction->merchant_ref,
                        'merchant_session' => $transaction->merchant_session,
                        'status' => $transaction->status,
                        'gateway_transaction_id' => $transaction->transaction_id,
                        'message_type' => $transaction->message_type,
                        'response_code' => $transaction->response_code,
                        'merchant_response' => $transaction->merchant_response,
                        'fingerprint' => $transaction->fingerprint,
                        'payload' => $transaction->payload,
                        'callback_payload' => null,
                        'failure_reason' => null,
                        'submitted_at' => $transaction->created_at,
                        'callback_received_at' => $transaction->transaction_id !== null ? $transaction->updated_at : null,
                        'superseded_at' => null,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ]);
                }
            });
    }
};
