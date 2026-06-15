<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $attemptsTable = config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');

        $this->ensureNoDuplicateTransactionIdentifiers($transactionsTable);

        Schema::create($attemptsTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained($transactionsTable)
                ->cascadeOnDelete();
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

        $this->backfillCurrentAttempts($transactionsTable, $attemptsTable);
        $this->addMerchantReferenceUniqueIndex($transactionsTable);
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

    private function ensureNoDuplicateTransactionIdentifiers(string $transactionsTable): void
    {
        $duplicateMerchantRef = DB::table($transactionsTable)
            ->select('merchant_ref')
            ->whereNotNull('merchant_ref')
            ->groupBy('merchant_ref')
            ->havingRaw('COUNT(*) > 1')
            ->value('merchant_ref');

        throw_if($duplicateMerchantRef !== null, RuntimeException::class, "Cannot add SISP merchant_ref uniqueness; duplicate merchant_ref [{$duplicateMerchantRef}] already exists.");

        $duplicateMerchantSession = DB::table($transactionsTable)
            ->select('merchant_session')
            ->whereNotNull('merchant_session')
            ->groupBy('merchant_session')
            ->havingRaw('COUNT(*) > 1')
            ->value('merchant_session');

        throw_if($duplicateMerchantSession !== null, RuntimeException::class, "Cannot backfill SISP attempts; duplicate merchant_session [{$duplicateMerchantSession}] already exists.");
    }

    private function backfillCurrentAttempts(string $transactionsTable, string $attemptsTable): void
    {
        DB::table($transactionsTable)
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
            ->orderBy('id')
            ->chunkById(500, function ($transactions) use ($attemptsTable): void {
                $now = now();
                $records = [];

                foreach ($transactions as $transaction) {
                    $records[] = [
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
                        'submitted_at' => $transaction->created_at,
                        'created_at' => $transaction->created_at ?? $now,
                        'updated_at' => $transaction->updated_at ?? $now,
                    ];
                }

                if ($records !== []) {
                    DB::table($attemptsTable)->insert($records);
                }
            });
    }

    private function addMerchantReferenceUniqueIndex(string $transactionsTable): void
    {
        try {
            Schema::table($transactionsTable, function (Blueprint $table): void {
                $table->unique('merchant_ref');
            });
        } catch (QueryException $exception) {
            throw_unless(str_contains($exception->getMessage(), 'already exists'), $exception);
        }
    }
};
