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
            $table->string('attempt_session')->nullable();
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

            $table->unique(['transaction_id', 'attempt_number']);
            $table->unique('attempt_session');
            $table->index(['merchant_ref', 'merchant_session']);
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

    }

    private function backfillCurrentAttempts(string $transactionsTable, string $attemptsTable): void
    {
        $usedAttemptSessions = [];

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
            ->chunkById(500, function ($transactions) use ($attemptsTable, &$usedAttemptSessions): void {
                $now = now();
                $records = [];

                foreach ($transactions as $transaction) {
                    $attemptSession = $this->uniqueLegacyAttemptSession(
                        (string) $transaction->merchant_session,
                        (int) $transaction->id,
                        $usedAttemptSessions,
                    );
                    $hasDuplicateAttemptSession = $attemptSession !== (string) $transaction->merchant_session;

                    $records[] = [
                        'transaction_id' => $transaction->id,
                        'attempt_number' => 1,
                        'merchant_ref' => $transaction->merchant_ref,
                        'merchant_session' => $transaction->merchant_session,
                        'attempt_session' => $attemptSession,
                        'status' => $transaction->status,
                        'gateway_transaction_id' => $transaction->transaction_id,
                        'message_type' => $transaction->message_type,
                        'response_code' => $transaction->response_code,
                        'merchant_response' => $transaction->merchant_response,
                        'fingerprint' => $transaction->fingerprint,
                        'payload' => $transaction->payload,
                        'failure_reason' => $hasDuplicateAttemptSession ? 'Legacy duplicate local attempt_session; original SISP merchant_session remains on the attempt.' : null,
                        'submitted_at' => $transaction->created_at,
                        'superseded_at' => $hasDuplicateAttemptSession ? ($transaction->updated_at ?? $now) : null,
                        'created_at' => $transaction->created_at ?? $now,
                        'updated_at' => $transaction->updated_at ?? $now,
                    ];

                    if ($hasDuplicateAttemptSession) {
                        $records[] = [
                            'transaction_id' => $transaction->id,
                            'attempt_number' => 2,
                            'merchant_ref' => $transaction->merchant_ref,
                            'merchant_session' => $transaction->merchant_session,
                            'attempt_session' => $this->uniqueLegacyAttemptSession(
                                (string) $transaction->merchant_session,
                                $transaction->id.'-active',
                                $usedAttemptSessions,
                            ),
                            'status' => 'pending',
                            'payload' => $transaction->payload,
                            'submitted_at' => null,
                            'created_at' => $transaction->updated_at ?? $now,
                            'updated_at' => $transaction->updated_at ?? $now,
                        ];
                    }
                }

                if ($records !== []) {
                    DB::table($attemptsTable)->insert($records);
                }
            });
    }

    /**
     * @param  array<string, true>  $usedAttemptSessions
     */
    private function uniqueLegacyAttemptSession(string $merchantSession, int|string $transactionId, array &$usedAttemptSessions): string
    {
        if ($merchantSession !== '' && ! isset($usedAttemptSessions[$merchantSession])) {
            $usedAttemptSessions[$merchantSession] = true;

            return $merchantSession;
        }

        $base = $merchantSession !== '' ? $merchantSession : 'legacy-empty-session';
        $candidate = $this->legacyIdentifier($base, $transactionId);
        $counter = 1;

        while (isset($usedAttemptSessions[$candidate])) {
            $candidate = $this->legacyIdentifier($base, $transactionId.'-'.$counter);
            $counter++;
        }

        $usedAttemptSessions[$candidate] = true;

        return $candidate;
    }

    private function legacyIdentifier(string $value, int|string $suffix): string
    {
        $suffix = '-legacy-'.$suffix;

        return mb_substr($value, 0, 255 - mb_strlen($suffix)).$suffix;
    }

    private function addMerchantReferenceUniqueIndex(string $transactionsTable): void
    {
        try {
            Schema::table($transactionsTable, function (Blueprint $table): void {
                $table->unique('merchant_ref');
            });
        } catch (QueryException $exception) {
            throw_unless($this->indexAlreadyExists($exception), $exception);
        }
    }

    private function indexAlreadyExists(QueryException $exception): bool
    {
        $sqlState = (string) ($exception->errorInfo[0] ?? '');
        $driverCode = (int) ($exception->errorInfo[1] ?? 0);
        $message = mb_strtolower($exception->getMessage());

        return $sqlState === '42P07'
            || ($sqlState === '42000' && $driverCode === 1061)
            || ($sqlState === '42000' && str_contains($message, 'duplicate key name'))
            || str_contains($message, 'already exists');
    }
};
