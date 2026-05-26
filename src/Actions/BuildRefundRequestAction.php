<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\FingerPrint\RefundFingerPrintAction;
use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\RefundRequest;
use LogicException;

final readonly class BuildRefundRequestAction
{
    public const string TOTAL_REVERSAL = '4';

    public const string PARTIAL_REFUND = '8';

    public const string REFUND_HISTORY = '9';

    public function __construct(
        private RefundFingerPrintAction $fingerPrint,
        private SispCredentialsResolver $credentialsResolver,
        private LoadConfig $config,
    ) {}

    public function total(Transaction $transaction): RefundRequest
    {
        return $this->handle($transaction, (float) $transaction->amount, self::TOTAL_REVERSAL);
    }

    public function partial(Transaction $transaction, float $amount): RefundRequest
    {
        return $this->handle($transaction, $amount, self::PARTIAL_REFUND);
    }

    public function history(Transaction $transaction): RefundRequest
    {
        return $this->handle($transaction, 0.0, self::REFUND_HISTORY);
    }

    public function handle(Transaction $transaction, float $amount, string $transactionCode): RefundRequest
    {
        $credentials = $this->credentialsResolver->resolve();
        $clearingPeriod = $this->requiredTransactionString($transaction, 'response_code', 'clearingPeriod');
        $transactionID = $this->requiredTransactionString($transaction, 'transaction_id', 'transactionID');

        $payload = [
            'posID' => $credentials->posId,
            'merchantRef' => $this->requiredTransactionString($transaction, 'merchant_ref', 'merchantRef'),
            'merchantSession' => $this->requiredTransactionString($transaction, 'merchant_session', 'merchantSession'),
            'amount' => $amount,
            'currency' => $this->requiredTransactionString($transaction, 'currency', 'currency'),
            'timeStamp' => $this->config->getTimeStamp(),
            'fingerprintversion' => '2',
            'transactionCode' => $transactionCode,
            'reversal' => 'R',
            'clearingPeriod' => $clearingPeriod,
            'transactionID' => $transactionID,
        ];

        $payload['fingerprint'] = $this->fingerPrint->handle($payload);

        return RefundRequest::from($payload);
    }

    private function requiredTransactionString(Transaction $transaction, string $attribute, string $field): string
    {
        $value = $transaction->getAttribute($attribute);
        $value = is_scalar($value) ? mb_trim((string) $value) : '';

        throw_if($value === '', LogicException::class, "SISP refund requires original {$field}.");

        return $value;
    }
}
