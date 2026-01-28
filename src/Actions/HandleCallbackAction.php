<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\Transaction\FindOrCreateTransactionAction;
use Akira\Sisp\Actions\Transaction\UpdateTransactionAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Exceptions\InvalidSignatureException;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;

final readonly class HandleCallbackAction
{
    public function __construct(
        private FindOrCreateTransactionAction $findOrCreateTransaction,
        private UpdateTransactionAction $updateTransaction,
    ) {}

    public function handle(CallbackPayload $payload): Transaction
    {
        if (! Sisp::validateCallback($payload)) {
            throw new InvalidSignatureException;
        }

        $transaction = $this->findOrCreateTransaction->handle($payload);

        $this->updateTransaction->handle($transaction, $payload);

        $this->dispatchEvent($transaction, $payload);

        return $transaction;
    }

    private function dispatchEvent(Transaction $transaction, CallbackPayload $payload): void
    {
        match ($transaction->status) {
            TransactionStatus::completed => event(new PaymentCompleted($transaction, $payload)),
            TransactionStatus::failed => event(new PaymentFailed($transaction, $payload)),
            TransactionStatus::pending => event(new PaymentPending($transaction, $payload)),
            default => null, // @codeCoverageIgnore
        };
    }
}
