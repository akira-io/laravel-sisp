<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Actions\FingerPrint\PaymentErrorResponseFingerPrintAction;
use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Enums\SuccessMessageType;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Str;
use LogicException;

final readonly class BuildSandboxPayloadAction
{
    public function __construct(
        private PaymentResponseFingerPrintAction $generateFingerprint,
        private PaymentErrorResponseFingerPrintAction $generateErrorFingerprint,
        private SispCredentialsResolver $resolver,
    ) {}

    public function handle(PaymentRequestData $data, string $status = 'success'): CallbackPayload
    {
        $credentials = $this->resolver->resolve();

        throw_unless($credentials->sandbox, LogicException::class, 'Sandbox payloads can only be generated when SISP sandbox mode is enabled.');

        $merchantRef = $data->merchantRef ?? Sisp::getMerchantReference();
        $merchantSession = $data->merchantSession ?? Sisp::getMerchantSession();
        $amount = $data->amount;
        $timestamp = $data->timeStamp ?? Sisp::getTimeStamp();
        $currency = $data->currency ?? $credentials->currency;
        $transactionCode = $data->transactionCode ?? Sisp::getDefaultTransactionCode();

        $messageType = match ($status) {
            'success' => SuccessMessageType::purchase->value,
            'failed' => CallbackPayload::ERROR_MESSAGE_TYPE,
            default => 'P',
        };

        $successType = SuccessMessageType::tryFrom($messageType);
        $merchantResp = $successType instanceof SuccessMessageType
            ? $successType->expectedMerchantResponses()[0]
            : '00';

        $payload = [
            'messageType' => $messageType,
            'merchantRespCP' => '01',
            'merchantRespTid' => 'FAKE'.Str::random(8),
            'merchantRespMerchantRef' => $merchantRef,
            'merchantRespMerchantSession' => $merchantSession,
            'merchantRespPurchaseAmount' => $amount,
            'merchantRespMessageID' => 'MSG-'.Str::random(8),
            'merchantRespPan' => '****-****-****-1234',
            'merchantResp' => $merchantResp,
            'merchantRespTimeStamp' => $timestamp,
            'merchantRespReferenceNumber' => Str::random(12),
            'merchantRespEntityCode' => '10010',
            'merchantRespClientReceipt' => 'RECEIPT-'.Str::random(8),
            'merchantRespAdditionalErrorMessage' => $status === 'failed' ? 'Sandbox transaction failed' : '',
            'merchantRespReloadCode' => '',
            'fingerPrintVersion' => '1',
            'posID' => $credentials->posId,
            'currency' => $currency,
            'transactionCode' => $transactionCode,
        ];

        $callbackPayload = CallbackPayload::from($payload);

        $fingerprint = $callbackPayload->isError()
            ? $this->generateErrorFingerprint->handle($callbackPayload)
            : $this->generateFingerprint->handle($callbackPayload);

        $payload['resultFingerPrint'] = $fingerprint;

        return CallbackPayload::from($payload);
    }
}
