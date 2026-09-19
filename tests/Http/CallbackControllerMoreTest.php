<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('redirects when GET callback has no ref', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $this->get(route('sisp.callback'))
        ->assertRedirect('/home');
});

it('redirects when transaction not found by ref', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $this->get(URL::signedRoute('sisp.callback', ['ref' => 'UNKNOWN']))
        ->assertRedirect('/home');
});

it('does not render the result page for an unsigned reference', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $transaction = Transaction::factory()->create(['status' => 'failed']);

    $this->get(route('sisp.callback', ['ref' => $transaction->merchant_ref]))
        ->assertRedirect('/home');
});

it('does not render the result page once the signed link has expired', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $transaction = Transaction::factory()->create(['status' => 'failed']);
    $url = URL::temporarySignedRoute('sisp.callback', now()->addMinutes(30), ['ref' => $transaction->merchant_ref]);

    $this->travel(31)->minutes();

    $this->get($url)->assertRedirect('/home');
});

it('does not render the result page when the signed reference was swapped', function (): void {
    config()->set('sisp.redirect_url', '/home');
    Transaction::factory()->create(['merchant_ref' => 'MR-VICTIM', 'status' => 'failed']);
    $signed = URL::signedRoute('sisp.callback', ['ref' => 'MR-OWN']);

    $this->get(str_replace('MR-OWN', 'MR-VICTIM', $signed))->assertRedirect('/home');
});
