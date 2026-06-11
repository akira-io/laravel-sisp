<?php

declare(strict_types=1);

namespace Akira\Sisp\Builders;

use Akira\Sisp\Actions\PreparePaymentAction;
use Akira\Sisp\Enums\TransactionCode;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use LogicException;

final class PaymentBuilder
{
    private ?float $amount = null;

    private ?string $merchantRef = null;

    private ?string $merchantSession = null;

    private ?string $timeStamp = null;

    private ?string $currency = null;

    private ?string $transactionCode = null;

    private ?string $token = null;

    private ?string $entityCode = null;

    private ?string $referenceNumber = null;

    private ?string $locale = null;

    private ?string $customerEmail = null;

    private ?string $customerCountry = null;

    private ?string $customerCity = null;

    private ?string $customerAddress = null;

    private ?string $customerPostalCode = null;

    private ?string $customerPhone = null;

    public function __construct(private readonly PreparePaymentAction $preparePayment) {}

    public function amount(float $amount): self
    {
        $this->amount = $amount;

        return $this;
    }

    public function merchantRef(string $merchantRef): self
    {
        $this->merchantRef = $merchantRef;

        return $this;
    }

    public function merchantSession(string $merchantSession): self
    {
        $this->merchantSession = $merchantSession;

        return $this;
    }

    public function timeStamp(string $timeStamp): self
    {
        $this->timeStamp = $timeStamp;

        return $this;
    }

    public function currency(string $currency): self
    {
        $this->currency = $currency;

        return $this;
    }

    public function transactionCode(TransactionCode|string $transactionCode): self
    {
        $this->transactionCode = $transactionCode instanceof TransactionCode
            ? $transactionCode->value
            : $transactionCode;

        return $this;
    }

    public function token(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    public function entityCode(string $entityCode): self
    {
        $this->entityCode = $entityCode;

        return $this;
    }

    public function referenceNumber(string $referenceNumber): self
    {
        $this->referenceNumber = $referenceNumber;

        return $this;
    }

    public function locale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function customerEmail(string $email): self
    {
        $this->customerEmail = $email;

        return $this;
    }

    public function customerCountry(string $country): self
    {
        $this->customerCountry = $country;

        return $this;
    }

    public function customerCity(string $city): self
    {
        $this->customerCity = $city;

        return $this;
    }

    public function customerAddress(string $address): self
    {
        $this->customerAddress = $address;

        return $this;
    }

    public function customerPostalCode(string $postalCode): self
    {
        $this->customerPostalCode = $postalCode;

        return $this;
    }

    public function customerPhone(string $phone): self
    {
        $this->customerPhone = $phone;

        return $this;
    }

    public function toData(): PaymentRequestData
    {
        throw_if($this->amount === null || $this->amount <= 0, LogicException::class, 'A payment amount greater than zero is required.');

        return new PaymentRequestData(
            amount: $this->amount,
            merchantRef: $this->merchantRef,
            merchantSession: $this->merchantSession,
            timeStamp: $this->timeStamp,
            currency: $this->currency,
            transactionCode: $this->transactionCode,
            token: $this->token,
            entityCode: $this->entityCode,
            referenceNumber: $this->referenceNumber,
            locale: $this->locale,
            customerEmail: $this->customerEmail,
            customerCountry: $this->customerCountry,
            customerCity: $this->customerCity,
            customerAddress: $this->customerAddress,
            customerPostalCode: $this->customerPostalCode,
            customerPhone: $this->customerPhone,
        );
    }

    public function build(): PaymentRequest
    {
        return $this->preparePayment->handle($this->toData());
    }
}
