<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\RequestMetadata;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

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
