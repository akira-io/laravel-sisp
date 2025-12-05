<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('redirects when GET callback has no ref', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $this->get(route('sisp.callback'))
        ->assertRedirect('/home');
});

it('redirects when transaction not found by ref', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $this->get(route('sisp.callback', ['ref' => 'UNKNOWN']))
        ->assertRedirect('/home');
});

