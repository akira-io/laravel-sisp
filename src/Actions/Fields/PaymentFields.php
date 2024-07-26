<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Fields;

class PaymentFields
{
    private string $transactionCode;

    private string $posID;

    private mixed $currency;

    private string $is3DSec;

    private string $urlMerchantResponse;

    private string $languageMessages;

    private string $fingerprintVersion;

    private string $posAutCode;

    private string $merchantId;

    private string $entityCode;

    private string $referenceNumber;

    private float|int $amount = 0;

    public function __construct()
    {
        $this->transactionCode = config('sisp.transactionCode');
        $this->posID = config('sisp.posID');
        $this->currency = config('sisp.currency');
        $this->is3DSec = config('sisp.is3DSec');
        $this->urlMerchantResponse = config('sisp.urlMerchantResponse');
        $this->languageMessages = config('sisp.languageMessages');
        $this->fingerprintVersion = config('sisp.fingerPrintVersion');
        $this->posAutCode = config('sisp.posAutCode');
        $this->merchantId = config('sisp.merchantId');
        $this->entityCode = '';
        $this->referenceNumber = '';
    }

    public static function make(): self
    {

        return app(self::class);
    }

    public function withAmount(float|int $amount): self
    {

        $this->amount = $amount;

        return $this;
    }

    public function getTransactionCode(): string
    {

        return $this->transactionCode;
    }

    public function getPosID(): string
    {

        return $this->posID;
    }

    public function getMerchantRef(): string
    {
        return trim('R'.now()->format('YmdHis'));
    }

    public function getMerchantSession(): string
    {

        return trim('S'.now()->format('YmdHis'));
    }

    public function getAmount()
    {

        return $this->amount;
    }

    public function parsedAmount(): int
    {
        return (int) ((float) $this->amount * 1000);

    }

    public function getCurrency(): mixed
    {

        return $this->currency;
    }

    public function getIs3DSec(): string
    {

        return $this->is3DSec;
    }

    public function getUrlMerchantResponse(): string
    {

        return $this->urlMerchantResponse;
    }

    public function getLanguageMessages(): string
    {

        return $this->languageMessages;
    }

    public function getTimeStamp(): string
    {

        return (string) now();
    }

    public function getFingerprintVersion(): string
    {

        return $this->fingerprintVersion;
    }

    public function getPosAutCode(): string
    {

        return $this->posAutCode;
    }

    public function getMerchantId(): string
    {

        return $this->merchantId;
    }

    public function getEntityCode(): string
    {

        return $this->entityCode;
    }

    public function getReferenceNumber(): string
    {

        return $this->referenceNumber;
    }

    public function toArray(): array
    {

        return [
            'transactionCode' => $this->getTransactionCode(),
            'posID' => $this->getPosID(),
            'merchantRef' => $this->getMerchantRef(),
            'merchantSession' => $this->getMerchantSession(),
            'amount' => $this->getAmount(),
            'currency' => $this->getCurrency(),
            'is3DSec' => $this->getIs3DSec(),
            'urlMerchantResponse' => $this->getUrlMerchantResponse(),
            'languageMessages' => $this->getLanguageMessages(),
            'timeStamp' => $this->getTimeStamp(),
            'fingerPrintVersion' => $this->getFingerprintVersion(),
            'posAutCode' => $this->getPosAutCode(),
            'merchantId' => $this->getMerchantId(),
            'entityCode' => $this->getEntityCode(),
            'referenceNumber' => $this->getReferenceNumber(),
        ];
    }
}
