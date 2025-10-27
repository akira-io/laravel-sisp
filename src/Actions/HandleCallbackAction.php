<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Transaction;

final readonly class HandleCallbackAction
{
    public function __construct(
        private ValidateFingerprintAction $validateFingerprint,
    ) {}

    public function handle(array $payload): Transaction
    {
        $transaction = $this->findOrCreateTransaction($payload);

        $transaction->update([
            'transaction_id' => $payload['transactionID'] ?? null,
            'message_type' => $payload['messageType'] ?? null,
            'merchant_response' => $payload['merchantResponse'] ?? null,
            'response_code' => $payload['responseCode'] ?? null,
            'fingerprint' => $payload['fingerprint'] ?? null,
            'payload' => $payload,
            'status' => $this->mapStatus($payload['messageType'] ?? null),
        ]);

        $this->dispatchEvent($transaction, $payload);

        return $transaction;
    }

    private function findOrCreateTransaction(array $payload): Transaction
    {
        return Transaction::where('merchant_ref', $payload['merchantRef'] ?? null)
            ->where('merchant_session', $payload['merchantSession'] ?? null)
            ->firstOrFail();
    }

    private function mapStatus(?string $messageType): string
    {
        return match ($messageType) {
            SuccessMessageType::purchase->value, 
            SuccessMessageType::servicePayment->value, 
            SuccessMessageType::phoneRecharge->value,
            SuccessMessageType::enrollmentRequest->value, 
            SuccessMessageType::tokenPayment->value,
            SuccessMessageType::tokenCancel->value => 'completed',
            ErrorMessageType::transactionError->value => 'failed',
            default => 'pending',
        };
    }

    private function dispatchEvent(Transaction $transaction, array $payload): void
    {
        match ($transaction->status) {
            'completed' => PaymentCompleted::dispatch($transaction, $payload),
            'failed' => PaymentFailed::dispatch($transaction, $payload),
            'pending' => PaymentPending::dispatch($transaction, $payload),
            default => null,
        };
    }
}