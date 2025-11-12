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
        private ValidatePaymentResponseFingerprintAction $validateFingerprint,
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
            'messageType' => $messageType,
            'clearingPeriod' => '01',
            'transactionID' => 'FAKE'.uniqid(),
            'merchantReference' => $merchantRef,
            'merchantSession' => $merchantSession,
            'amount' => $amount,
            'messageID' => 'MSG-'.uniqid(),
            'pan' => '****-****-****-1234',
            'merchantResponse' => '00',
            'timeStamp' => $timestamp,
            'reference' => uniqid(),
            'entity' => '10010',
            'clientReceipt' => 'RECEIPT-'.uniqid(),
            'additionalErrorMessage' => $status === 'failed' ? 'Sandbox transaction failed' : '',
            'reloadCode' => '',
            'fingerPrintVersion' => '1',
            'posID' => Sisp::getPosId(),
            'currency' => $currency,
            'transactionCode' => $transactionCode,
        ];

        $payload['fingerPrint'] = $this->validateFingerprint->computeFingerprint($payload);

        return CallbackPayload::from($payload);
    }
}
