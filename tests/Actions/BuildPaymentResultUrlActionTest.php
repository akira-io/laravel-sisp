<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildPaymentResultUrlAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

it('builds a signed result page link for the transaction reference', function (): void {
    $transaction = Transaction::factory()->create(['merchant_ref' => 'MR-RESULT-URL']);

    $url = resolve(BuildPaymentResultUrlAction::class)->handle($transaction);

    expect(URL::hasValidSignature(Request::create($url)))->toBeTrue()
        ->and($url)->toStartWith(route('sisp.callback'))
        ->and($url)->toContain('ref=MR-RESULT-URL');
});

it('builds a result page link that expires after thirty minutes', function (): void {
    $transaction = Transaction::factory()->create();
    $url = resolve(BuildPaymentResultUrlAction::class)->handle($transaction);

    $this->travel(29)->minutes();
    $stillValid = URL::hasValidSignature(Request::create($url));

    $this->travel(2)->minutes();

    expect($stillValid)->toBeTrue()
        ->and(URL::hasValidSignature(Request::create($url)))->toBeFalse();
});
