<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Str;

final readonly class BuildSandboxPayloadAction
{
    public function __construct(
        private PaymentResponseFingerPrintAction $generateFingerprint,
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
            'failed' => ErrorMessageType::issuerError->value,
            default => 'P',
        };

        $payload = [
            'messageType' => $messageType,
            'merchantRespCP' => '01',
            'merchantRespTid' => 'FAKE'.Str::random(8),
            'merchantRespMerchantRef' => $merchantRef,
            'merchantRespMerchantSession' => $merchantSession,
            'merchantRespPurchaseAmount' => $amount,
            'merchantRespMessageID' => 'MSG-'.Str::random(8),
            'merchantRespPan' => '****-****-****-1234',
            'merchantResp' => '00',
            'merchantRespTimeStamp' => $timestamp,
            'merchantRespReferenceNumber' => Str::random(12),
            'merchantRespEntityCode' => '10010',
            'merchantRespClientReceipt' => 'RECEIPT-'.Str::random(8),
            'merchantRespAdditionalErrorMessage' => $status === 'failed' ? 'Sandbox transaction failed' : '',
            'merchantRespReloadCode' => '',
            'fingerPrintVersion' => '1',
            'posID' => Sisp::getPosId(),
            'currency' => $currency,
            'transactionCode' => $transactionCode,
        ];

        $callbackPayload = CallbackPayload::from($payload);

        $fingerprint = $this->generateFingerprint->handle($callbackPayload);

        $payload['resultFingerPrint'] = $fingerprint;

        return CallbackPayload::from($payload);
    }
}
