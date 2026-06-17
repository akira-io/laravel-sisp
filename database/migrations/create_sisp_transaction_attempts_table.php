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
        $referencesTable = config('sisp.tables.transaction_references', 'sisp_transaction_references');

        $this->createTransactionReferencesTable($referencesTable, $transactionsTable);
        $this->backfillTransactionReferences($transactionsTable, $referencesTable);

        if (Schema::hasTable($attemptsTable)) {
            return;
        }

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
    }

    public function down(): void
    {
        $attemptsTable = config('sisp.tables.transaction_attempts', 'sisp_transaction_attempts');
        $referencesTable = config('sisp.tables.transaction_references', 'sisp_transaction_references');

        Schema::dropIfExists($attemptsTable);
        Schema::dropIfExists($referencesTable);
    }

    private function createTransactionReferencesTable(string $referencesTable, string $transactionsTable): void
    {
        if (Schema::hasTable($referencesTable)) {
            return;
        }

        Schema::create($referencesTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->string('merchant_ref')->unique();
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained($transactionsTable)
                ->nullOnDelete();
            $table->timestamps();

            $table->index('transaction_id');
        });
    }

    private function backfillTransactionReferences(string $transactionsTable, string $referencesTable): void
    {
        DB::table($transactionsTable)
            ->selectRaw('merchant_ref, MIN(id) as transaction_id, MIN(created_at) as created_at, MAX(updated_at) as updated_at')
            ->whereNotNull('merchant_ref')
            ->where('merchant_ref', '!=', '')
            ->groupBy('merchant_ref')
            ->orderBy('merchant_ref')
            ->cursor()
            ->each(function (object $reference) use ($referencesTable): void {
                DB::table($referencesTable)->insertOrIgnore([
                    'merchant_ref' => $reference->merchant_ref,
                    'transaction_id' => $reference->transaction_id,
                    'created_at' => $reference->created_at ?? now(),
                    'updated_at' => $reference->updated_at ?? now(),
                ]);
            });
    }

    private function backfillAttempts(string $transactionsTable, string $attemptsTable): void
    {
        $usedMerchantSessions = [];

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
            ->chunkById(100, function ($transactions) use ($attemptsTable, &$usedMerchantSessions): void {
                foreach ($transactions as $transaction) {
                    $merchantSession = $this->uniqueLegacyMerchantSession(
                        (string) $transaction->merchant_session,
                        (int) $transaction->id,
                        $usedMerchantSessions,
                    );
                    $hasDuplicateSession = $merchantSession !== (string) $transaction->merchant_session;

                    DB::table($attemptsTable)->insert([
                        'transaction_id' => $transaction->id,
                        'attempt_number' => 1,
                        'merchant_ref' => $transaction->merchant_ref,
                        'merchant_session' => $merchantSession,
                        'status' => $transaction->status,
                        'gateway_transaction_id' => $transaction->transaction_id,
                        'message_type' => $transaction->message_type,
                        'response_code' => $transaction->response_code,
                        'merchant_response' => $transaction->merchant_response,
                        'fingerprint' => $transaction->fingerprint,
                        'payload' => $transaction->payload,
                        'callback_payload' => null,
                        'failure_reason' => $hasDuplicateSession ? 'Legacy duplicate merchant_session; original value remains on the transaction.' : null,
                        'submitted_at' => $transaction->created_at,
                        'callback_received_at' => $transaction->transaction_id !== null ? $transaction->updated_at : null,
                        'superseded_at' => $hasDuplicateSession ? ($transaction->updated_at ?? now()) : null,
                        'created_at' => $transaction->created_at,
                        'updated_at' => $transaction->updated_at,
                    ]);
                }
            });
    }

    /**
     * @param  array<string, true>  $usedMerchantSessions
     */
    private function uniqueLegacyMerchantSession(string $merchantSession, int $transactionId, array &$usedMerchantSessions): string
    {
        if ($merchantSession !== '' && ! isset($usedMerchantSessions[$merchantSession])) {
            $usedMerchantSessions[$merchantSession] = true;

            return $merchantSession;
        }

        $base = $merchantSession !== '' ? $merchantSession : 'legacy-empty-session';
        $candidate = $this->legacyIdentifier($base, $transactionId);
        $counter = 1;

        while (isset($usedMerchantSessions[$candidate])) {
            $candidate = $this->legacyIdentifier($base, $transactionId.'-'.$counter);
            $counter++;
        }

        $usedMerchantSessions[$candidate] = true;

        return $candidate;
    }

    private function legacyIdentifier(string $value, int|string $suffix): string
    {
        $suffix = '-legacy-'.$suffix;

        return mb_substr($value, 0, 255 - mb_strlen($suffix)).$suffix;
    }
};
