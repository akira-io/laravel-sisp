<?php

declare(strict_types=1);

use Akira\Sisp\Actions\FingerPrint\PaymentResponseFingerPrintAction;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\DB;

it('does not capture callback metadata when metadata collection is disabled', function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.security.collect_metadata', false);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-NO-METADATA',
        'merchant_session' => 'MS-NO-METADATA',
        'amount' => 20,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-NO-METADATA',
        'merchantSession' => 'MS-NO-METADATA',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-NO-METADATA']));

    expect($transaction->refresh()->status->value)->toBe('completed')
        ->and(RequestMetadata::query()->count())->toBe(0);
});

it('masks the card number in the stored callback metadata', function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.security.collect_metadata', true);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-PAN-METADATA',
        'merchant_session' => 'MS-PAN-METADATA',
        'amount' => 20,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-PAN-METADATA',
        'merchantSession' => 'MS-PAN-METADATA',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]))->toArray();
    $payload['merchantRespPan'] = '4111111111111111';
    $payload['resultFingerPrint'] = resolve(PaymentResponseFingerPrintAction::class)->handle(CallbackPayload::from($payload));

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-PAN-METADATA']));

    $stored = RequestMetadata::query()->where('transaction_id', $transaction->id)->sole();
    $raw = (string) DB::table($stored->getTable())->where('id', $stored->id)->value('custom_metadata');

    expect($transaction->refresh()->status->value)->toBe('completed')
        ->and($stored->custom_metadata['payload']['merchantRespPan'])->toBe('************1111')
        ->and($raw)->not->toContain('4111111111111111');
});
