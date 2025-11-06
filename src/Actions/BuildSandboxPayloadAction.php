<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Enums\SuccessMessageType;
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
            'success' => SuccessMessageType::purchase->value,
            'failed' => ErrorMessageType::transactionError->value,
            'pending' => 'P',
            default => 'P',
        };

        $payload = [
            'merchantRespCP' => '0',
            'merchantRespTid' => (int) ('FAKE'.uniqid()),
            'merchantRespMerchantRef' => $merchantRef,
            'merchantRespMerchantSession' => $merchantSession,
            'merchantRespMessageID' => 'MSG-'.uniqid(),
            'merchantRespPan' => '****-****-****-1234',
            'merchantResp' => '0',
            'merchantRespTimeStamp' => $timestamp,
            'merchantRespEntityCode' => Sisp::getPosId(),
            'merchantRespReferenceNumber' => uniqid(),
            'merchantRespClientReceipt' => 'RECEIPT-'.uniqid(),
            'merchantRespAdditionalErrorMessage' => $status === 'failed' ? 'Sandbox transaction failed' : '',
            'merchantRespReloadCode' => '',
            'messageType' => $messageType,
        ];

        $payload['resultFingerPrint'] = $this->validateFingerprint->computeFingerprint($payload);

        return CallbackPayload::from($payload);
    }
}
