<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('retries the transaction the link was signed for, not the one named in the request body', function (): void {
    $own = Transaction::factory()->create(['status' => 'failed', 'merchant_ref' => 'MR-OWN', 'merchant_session' => 'MS-OWN']);
    $victim = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-VICTIM',
        'merchant_session' => 'MS-VICTIM',
        'customer_email' => 'victim@example.com',
    ]);

    $response = $this->post(signedRetryLink($own), ['transaction' => $victim->id]);

    $response->assertOk();
    expect($response->getContent())->not->toContain('victim@example.com')
        ->and($own->refresh()->status->value)->toBe('pending')
        ->and($victim->refresh()->status->value)->toBe('failed')
        ->and($victim->attempts()->count())->toBe(0);
});

it('ignores a transaction named in a JSON body on a signed retry link opened with GET', function (): void {
    $own = Transaction::factory()->create(['status' => 'failed', 'merchant_ref' => 'MR-OWN-GET', 'merchant_session' => 'MS-OWN-GET']);
    $victim = Transaction::factory()->create([
        'status' => 'failed',
        'merchant_ref' => 'MR-VICTIM-GET',
        'merchant_session' => 'MS-VICTIM-GET',
        'customer_email' => 'victim-get@example.com',
    ]);

    $response = $this->json('GET', signedRetryLink($own), ['transaction' => $victim->id]);

    $response->assertOk();
    expect($response->getContent())->not->toContain('victim-get@example.com');
});

it('cancels the transaction the link was signed for, not the one named in the request body', function (): void {
    $own = Transaction::factory()->create(['status' => 'pending', 'merchant_ref' => 'MR-CANCEL-OWN', 'merchant_session' => 'MS-CANCEL-OWN']);
    $victim = Transaction::factory()->create(['status' => 'pending', 'merchant_ref' => 'MR-CANCEL-VICTIM', 'merchant_session' => 'MS-CANCEL-VICTIM']);

    $this->json('GET', URL::signedRoute('sisp.cancel', ['merchantRef' => 'MR-CANCEL-OWN']), ['merchantRef' => 'MR-CANCEL-VICTIM'])
        ->assertRedirect();

    expect($own->refresh()->status->value)->toBe('cancelled')
        ->and($victim->refresh()->status->value)->toBe('pending');
});

it('does not resolve a cancellation target from the request body when the signed query names none', function (): void {
    $victim = Transaction::factory()->create(['status' => 'pending', 'merchant_ref' => 'MR-BODY-ONLY', 'merchant_session' => 'MS-BODY-ONLY']);

    $this->withHeader('referer', '/checkout')
        ->json('GET', URL::signedRoute('sisp.cancel'), ['merchantRef' => 'MR-BODY-ONLY', 'transaction_id' => 'TXN-X'])
        ->assertRedirect('/checkout');

    expect($victim->refresh()->status->value)->toBe('pending');
});

function signedRetryLink(Transaction $transaction): string
{
    return URL::temporarySignedRoute('sisp.retry-payment', now()->addMinutes(30), ['transaction' => $transaction->id]);
}

it('cancels the transaction signed by transaction_id, not the one named by merchantRef in the body', function (): void {
    $own = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-TXN-OWN',
        'merchant_session' => 'MS-TXN-OWN',
        'transaction_id' => 'TXN-SIGNED-001',
    ]);
    $victim = Transaction::factory()->create(['status' => 'pending', 'merchant_ref' => 'MR-TXN-VICTIM', 'merchant_session' => 'MS-TXN-VICTIM']);

    $this->json('GET', URL::signedRoute('sisp.cancel', ['transaction_id' => 'TXN-SIGNED-001']), ['merchantRef' => 'MR-TXN-VICTIM'])
        ->assertRedirect();

    expect($own->refresh()->status->value)->toBe('cancelled')
        ->and($victim->refresh()->status->value)->toBe('pending');
});
