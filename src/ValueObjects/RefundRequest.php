<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

final readonly class RefundRequest
{
    public function __construct(
        public string $posID,
        public string $merchantRef,
        public string $merchantSession,
        public float $amount,
        public string $currency,
        public string $timeStamp,
        public string $fingerprintVersion,
        public string $transactionCode,
        public string $fingerprint,
        public string $reversal,
        public string $clearingPeriod,
        public string $transactionID,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function from(array $data): self
    {
        return new self(
            posID: (string) $data['posID'],
            merchantRef: (string) $data['merchantRef'],
            merchantSession: (string) $data['merchantSession'],
            amount: (float) $data['amount'],
            currency: (string) $data['currency'],
            timeStamp: (string) $data['timeStamp'],
            fingerprintVersion: (string) $data['fingerprintversion'],
            transactionCode: (string) $data['transactionCode'],
            fingerprint: (string) $data['fingerprint'],
            reversal: (string) $data['reversal'],
            clearingPeriod: (string) $data['clearingPeriod'],
            transactionID: (string) $data['transactionID'],
        );
    }

    /**
     * @return array<string, float|string>
     */
    public function toArray(): array
    {
        return [
            'posID' => $this->posID,
            'merchantRef' => $this->merchantRef,
            'merchantSession' => $this->merchantSession,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'timeStamp' => $this->timeStamp,
            'fingerprintversion' => $this->fingerprintVersion,
            'transactionCode' => $this->transactionCode,
            'fingerprint' => $this->fingerprint,
            'reversal' => $this->reversal,
            'clearingPeriod' => $this->clearingPeriod,
            'transactionID' => $this->transactionID,
        ];
    }
}
