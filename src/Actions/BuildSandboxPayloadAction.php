<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequestData;

final readonly class BuildSandboxPayloadAction
{
    public function __construct(
        private ValidateFingerprintAction $validateFingerprint,
    ) {}

    public function handle(PaymentRequestData $data, string $status = 'success'): CallbackPayload
    {
        $merchantRef = $data->merchantRef ?? Sisp::getMerchantReference();
        $merchantSession = $data->merchantSession ?? Sisp::getMerchantSession();
        $amount = $data->amount;
        $timestamp = $data->timeStamp ?? Sisp::getTimeStamp();
        $currency = $data->currency ?? Sisp::getCurrency();
        $transactionCode = $data->transactionCode ?? Sisp::getDefaultTransactionCode();

        $messageType = match ($status) {
            'success' => '8',
            'failed' => '10',
            'pending' => 'P',
            default => 'P',
        };

        $payload = [
            'posID' => Sisp::getPosId(),
            'merchantRef' => $merchantRef,
            'merchantSession' => $merchantSession,
            'amount' => $amount,
            'currency' => $currency,
            'timeStamp' => $timestamp,
            'transactionCode' => $transactionCode,
            'transactionID' => 'FAKE-' . uniqid(),
            'messageType' => $messageType,
            'merchantResponse' => '0',
            'responseCode' => '0',
        ];

        $payload['fingerprint'] = $this->validateFingerprint->computeFingerprint($payload);

        return CallbackPayload::from($payload);
    }
}