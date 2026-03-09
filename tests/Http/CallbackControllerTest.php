<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\DB;

it('redirects when user cancelled flag present', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $this->post(route('sisp.callback'), ['UserCancelled' => true])
        ->assertRedirect('/home');
});

it('renders response for existing transaction via GET', function (): void {
    $t = Transaction::factory()->create([
        'merchant_ref' => 'MR-G1',
        'merchant_session' => 'MS-G1',
        'amount' => 10,
        'currency' => '132',
        'status' => 'pending',
        'locale' => 'pt',
    ]);

    $this->get(route('sisp.callback', ['ref' => 'MR-G1']))
        ->assertOk();
});

it('handles POST callback and redirects to GET with ref', function (): void {
    $t = Transaction::factory()->create([
        'merchant_ref' => 'MR-G2',
        'merchant_session' => 'MS-G2',
        'amount' => 20,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-G2',
        'merchantSession' => 'MS-G2',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-G2']));
});

it('rejects invalid callback payload before transaction lookups', function (): void {
    config()->set('sisp.redirect_url', '/home');

    Transaction::factory()->create([
        'merchant_ref' => 'MR-G3',
        'merchant_session' => 'MS-G3',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-G3',
        'merchantSession' => 'MS-G3',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]))->toArray();
    $payload['resultFingerPrint'] = 'invalid-fingerprint';

    DB::flushQueryLog();
    DB::enableQueryLog();

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect('/home');

    $queries = collect(DB::getQueryLog())->pluck('query');

    expect($queries->filter(
        fn (string $query): bool => str_contains(mb_strtolower($query), 'from "transactions"')
    ))->toHaveCount(0);
});

it('skips duplicate lookup when callback payload has no transaction keys', function (): void {
    config()->set('sisp.redirect_url', '/home');
    config()->set('sisp.merchant_reference', '');
    config()->set('sisp.merchant_session', '');

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect('/home');
});
