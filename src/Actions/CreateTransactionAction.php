<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Exceptions\DuplicatePaymentIdentifierException;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\UniqueConstraintViolation;
use Akira\Sisp\ValueObjects\TransactionData;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

final readonly class CreateTransactionAction
{
    public function __construct(private CreateTransactionAttemptAction $createAttempt) {}

    public function handle(TransactionData $data): Transaction
    {
        try {
            return DB::transaction(function () use ($data): Transaction {
                $transaction = Transaction::query()->create([
                    'merchant_ref' => $data->merchantRef,
                    'merchant_session' => $data->merchantSession,
                    'amount' => $data->amount,
                    'currency' => $data->currency,
                    'status' => 'pending',
                    'transaction_code' => $data->transactionCode,
                    'payload' => $data->payload,
                    'locale' => $data->locale,
                ]);

                $this->createAttempt->createFromTransaction($transaction);

                return $transaction;
            }, attempts: 3);
        } catch (QueryException $exception) {
            throw_if(UniqueConstraintViolation::causedBy($exception), DuplicatePaymentIdentifierException::class, $exception);

            throw $exception;
        }
    }
}
