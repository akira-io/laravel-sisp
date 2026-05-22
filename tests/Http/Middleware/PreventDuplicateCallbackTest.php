<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;

it('redirects duplicate callback requests after validation', function (): void {
    config()->set('sisp.sandbox', true);

    Transaction::factory()->create([
        'merchant_ref' => 'MR-CB-DUP',
        'merchant_session' => 'MS-CB-DUP',
        'transaction_id' => 'T-EXISTS',
    ]);

    config()->set('sisp.redirect_url', '/home');

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 15,
        'merchantRef' => 'MR-CB-DUP',
        'merchantSession' => 'MS-CB-DUP',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect('/home');
});
