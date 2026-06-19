<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('retries payment and renders form', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-R',
        'merchant_session' => 'MS-R',
        'amount' => 30.0,
        'currency' => '132',
    ]);

    $response = $this->post(signedRetryUrl($t));
    $response->assertStatus(200);
});

it('opens signed retry links with get requests without mutating transactions', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-GET',
        'merchant_session' => 'MS-GET-OLD',
        'amount' => 30.0,
        'currency' => '132',
        'transaction_id' => 'OLD-TID',
        'message_type' => '13',
        'merchant_response' => 'old failure',
        'response_code' => '13',
        'fingerprint' => 'old-fingerprint',
    ]);

    $this->get(signedRetryUrl($t))
        ->assertStatus(200);

    $t->refresh();

    expect($t->merchant_session)->toBe('MS-GET-OLD')
        ->and($t->status->value)->toBe('failed')
        ->and($t->transaction_id)->toBe('OLD-TID')
        ->and($t->message_type)->toBe('13')
        ->and($t->merchant_response)->toBe('old failure')
        ->and($t->response_code)->toBe('13')
        ->and($t->fingerprint)->toBe('old-fingerprint');
});

it('keeps the same SISP identifiers on retry so the gateway sees the same transaction', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-R2',
        'merchant_session' => 'MS-OLD',
        'amount' => 50.0,
        'currency' => '132',
        'transaction_id' => 'OLD-TID',
        'message_type' => '13',
        'merchant_response' => 'old failure',
        'response_code' => '13',
        'fingerprint' => 'old-fingerprint',
    ]);

    $this->post(signedRetryUrl($t))
        ->assertStatus(200);

    $t->refresh();

    expect($t->merchant_ref)->toBe('MR-R2')
        ->and($t->merchant_session)->toBe('MS-OLD')
        ->and($t->status->value)->toBe('failed')
        ->and($t->transaction_id)->toBe('OLD-TID')
        ->and($t->message_type)->toBe('13')
        ->and($t->merchant_response)->toBe('old failure')
        ->and($t->response_code)->toBe('13')
        ->and($t->fingerprint)->toBe('old-fingerprint');
});

it('does not reset completed transactions through signed retry links', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'completed',
        'merchant_ref' => 'MR-COMPLETED',
        'merchant_session' => 'MS-COMPLETED',
        'amount' => 50.0,
        'currency' => '132',
        'transaction_id' => 'PAID-TID',
        'message_type' => '8',
        'merchant_response' => 'paid',
        'response_code' => '00',
        'fingerprint' => 'paid-fingerprint',
    ]);

    $this->postJson(signedRetryUrl($t))
        ->assertJsonValidationErrors(['transaction']);

    $t->refresh();

    expect($t->status->value)->toBe('completed')
        ->and($t->merchant_session)->toBe('MS-COMPLETED')
        ->and($t->transaction_id)->toBe('PAID-TID')
        ->and($t->message_type)->toBe('8')
        ->and($t->merchant_response)->toBe('paid')
        ->and($t->response_code)->toBe('00')
        ->and($t->fingerprint)->toBe('paid-fingerprint');
});

it('returns validation error without triggering after callback when transaction does not exist', function (): void {
    $this->postJson(signedRetryUrl(999999))
        ->assertJsonValidationErrors(['transaction']);
});

it('rejects unsigned retry requests before resolving transactions', function (): void {
    $t = Transaction::factory()->failed()->create();

    $this->post(route('sisp.retry-payment', ['transaction' => $t->id]))
        ->assertForbidden();
});

it('rejects expired retry links before resolving transactions', function (): void {
    $t = Transaction::factory()->failed()->create();

    $this->post(URL::temporarySignedRoute('sisp.retry-payment', now()->subMinute(), ['transaction' => $t->id]))
        ->assertForbidden();
});

it('rejects retry when 3DS is enabled and required customer data is missing', function (): void {
    config([
        'sisp.allow_retry' => true,
        'sisp.is_3dsec' => '1',
    ]);

    $t = Transaction::factory()->failed()->create([
        'customer_email' => null,
        'customer_country' => null,
        'customer_city' => null,
        'customer_address' => null,
        'customer_postal_code' => null,
    ]);

    $this->from('/sisp/callback?ref='.$t->merchant_ref)
        ->postJson(signedRetryUrl($t))
        ->assertJsonValidationErrors(['transaction']);
});

function signedRetryUrl(Transaction|int $transaction): string
{
    return URL::temporarySignedRoute(
        'sisp.retry-payment',
        now()->addMinutes(30),
        ['transaction' => $transaction instanceof Transaction ? $transaction->id : $transaction],
    );
}
