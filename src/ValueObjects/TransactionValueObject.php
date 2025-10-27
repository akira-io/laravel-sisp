<?php

declare(strict_types=1);

namespace Akira\Sisp\ValueObjects;

use Akira\Sisp\Facades\Sisp;
use Illuminate\Http\Request;

final readonly class TransactionValueObject
{
    private function __construct(
        private string $merchantRespMerchantRef = '',
        private string $merchantRespMerchantSession = '',
        private float $merchantRespPurchaseAmount = 0.0,
        private array $details = [],
    ) {}

    /**
     * Create a new instance from an array.
     */
    public static function fromArray(array $data): self
    {

        //        'transactionCode' => Sisp::getDefaultTransactionCode(),
        //            'posID' => Sisp::getPosID(),
        //            'merchantRef' => Sisp::getMerchantReference(),
        //            'merchantSession' => Sisp::getMerchantSession(),
        //            'amount' => $this->amount,
        //            'currency' => Sisp::getCurrency(),
        //            'is3DSec' => Sisp::getIs3DSec(),
        //            'urlMerchantResponse' => Sisp::getUrlMerchantResponse(),
        //            'languageMessages' => Sisp::getLanguageMessages(),

        return new self(
            merchantRespMerchantRef    : data_get($data, 'merchantRef'),
            merchantRespMerchantSession: data_get($data, 'merchantSession'),
            merchantRespPurchaseAmount : (float) data_get($data, 'amount'),
            details                    : [
                'currency' => data_get($data, 'currency', Sisp::getCurrency()),
            ]
        );
    }

    final public static function fromRequest(Request $request): self
    {

        return new self(
            merchantRespMerchantRef    : $request->get('merchantRef'),
            merchantRespMerchantSession: $request->get('merchantSession'),
            merchantRespPurchaseAmount : (float) $request->get('amount', 0),
            details                    : $request->all()
        );
    }

    public function getMerchantRespMerchantRef(): string
    {

        return $this->merchantRespMerchantRef;
    }

    public function getMerchantRespMerchantSession(): string
    {

        return $this->merchantRespMerchantSession;
    }

    public function getMerchantRespPurchaseAmount(): float
    {

        return $this->merchantRespPurchaseAmount;
    }

    public function getDetails(): array
    {

        return $this->details;
    }
}
