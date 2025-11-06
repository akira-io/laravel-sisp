<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Models\Transaction;

final readonly class HandleCallbackAction
{
    public function __construct(
        private ValidateFingerprintAction $validateFingerprint,
    ) {}

    public function handle(array $payload): Transaction
    {
        $transaction = $this->findOrCreateTransaction($payload);

        $mergedPayload = array_merge(
            $transaction->payload ?? [],
            $payload
        );

        $transaction->update([
            'transaction_id' => $payload['merchantRespTid'] ?? null,
            'message_type' => $payload['messageType'] ?? null,
            'merchant_response' => $payload['merchantResp'] ?? null,
            'response_code' => $payload['merchantRespCP'] ?? null,
            'fingerprint' => $payload['resultFingerPrint'] ?? null,
            'payload' => $mergedPayload,
            'status' => $this->mapStatus($payload['messageType'] ?? null)->value,
        ]);

        $this->dispatchEvent($transaction, $payload);

        return $transaction;
    }

    private function findOrCreateTransaction(array $payload): Transaction
    {
        return Transaction::where('merchant_ref', $payload['merchantRespMerchantRef'] ?? null)
            ->where('merchant_session', $payload['merchantRespMerchantSession'] ?? null)
            ->firstOrFail();
    }

    private function mapStatus(?string $messageType): TransactionStatus
    {
        return match ($messageType) {
            SuccessMessageType::purchase->value,
            SuccessMessageType::servicePayment->value,
            SuccessMessageType::phoneRecharge->value,
            SuccessMessageType::enrollmentRequest->value,
            SuccessMessageType::tokenPayment->value,
            SuccessMessageType::tokenCancel->value => TransactionStatus::completed,
            ErrorMessageType::transactionError->value => TransactionStatus::failed,
            default => TransactionStatus::pending,
        };
    }

    private function dispatchEvent(Transaction $transaction, array $payload): void
    {
        match ($transaction->status) {
            TransactionStatus::completed => PaymentCompleted::dispatch($transaction, $payload),
            TransactionStatus::failed => PaymentFailed::dispatch($transaction, $payload),
            TransactionStatus::pending => PaymentPending::dispatch($transaction, $payload),
            default => null,
        };
    }
}
