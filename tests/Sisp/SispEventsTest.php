<?php

declare(strict_types=1);

use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Events\PaymentPending;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake();
});

it('dispatches PaymentFailed for invalid or failed callbacks', function (): void {
    $t = Transaction::factory()->create([
        'merchant_ref' => 'MR-E1',
        'merchant_session' => 'MS-E1',
        'amount' => 10,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 10,
        'merchantRef' => 'MR-E1',
        'merchantSession' => 'MS-E1',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]), 'failed');

    Sisp::handlePaymentCallback($payload);

    Event::assertDispatched(PaymentFailed::class);
});

it('dispatches PaymentPending for pending status', function (): void {
    $t = Transaction::factory()->create([
        'merchant_ref' => 'MR-E2',
        'merchant_session' => 'MS-E2',
        'amount' => 10,
        'currency' => '132',
        'status' => 'pending',
    ]);

    // Build a custom payload with unknown message type to force pending
    $data = [
        'messageType' => 'Z',
        'merchantRespCP' => '01',
        'merchantRespTid' => 'T123',
        'merchantRespMerchantRef' => 'MR-E2',
        'merchantRespMerchantSession' => 'MS-E2',
        'merchantRespPurchaseAmount' => 10,
        'merchantRespMessageID' => 'MSG-1',
        'merchantRespPan' => '****-****',
        'merchantResp' => '00',
        'merchantRespTimeStamp' => '2024-01-01 00:00:00',
        'merchantRespReferenceNumber' => 'REF',
        'merchantRespEntityCode' => '10010',
        'merchantRespClientReceipt' => 'REC',
        'merchantRespAdditionalErrorMessage' => '',
        'reloadCode' => '',
        'fingerPrintVersion' => '1',
        'posID' => config('sisp.posID'),
        'currency' => '132',
        'transactionCode' => '1',
    ];

    $payload = Akira\Sisp\ValueObjects\CallbackPayload::from($data);
    $fp = resolve(PaymentResponseFingerPrintAction::class)->handle($payload);
    $data['resultFingerPrint'] = $fp;
    $payload = Akira\Sisp\ValueObjects\CallbackPayload::from($data);

    Sisp::handlePaymentCallback($payload);

    Event::assertDispatched(PaymentPending::class);
});
