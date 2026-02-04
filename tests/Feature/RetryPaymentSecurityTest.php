<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

it('forbids retry requests with missing signature', function () {
    $transaction = Transaction::factory()->failed()->create();

    $url = route('sisp.retry-payment', ['transaction' => $transaction->id]);

    $response = $this->post($url);

    $response->assertStatus(403);
});

it('forbids retry requests with invalid signature', function () {
    $transaction = Transaction::factory()->failed()->create();

    $url = URL::signedRoute('sisp.retry-payment', ['transaction' => $transaction->id]);

    // Tamper with the signature
    $url .= 'invalid';

    $response = $this->post($url);

    $response->assertStatus(403);
});

it('forbids retry requests when tampering with transaction id', function () {
    $transaction = Transaction::factory()->failed()->create();
    $otherTransaction = Transaction::factory()->failed()->create();

    // Generate valid signed URL for $transaction
    $url = URL::signedRoute('sisp.retry-payment', ['transaction' => $transaction->id]);

    // Replace the transaction ID in the URL with another one
    // The URL structure is .../sisp/retry-payment/{id}?signature=...
    $tamperedUrl = str_replace(
        "/sisp/retry-payment/{$transaction->id}",
        "/sisp/retry-payment/{$otherTransaction->id}",
        $url
    );

    $response = $this->post($tamperedUrl);

    $response->assertStatus(403);
});

it('allows valid signed retry requests', function () {
    $transaction = Transaction::factory()->failed()->create();

    $url = URL::signedRoute('sisp.retry-payment', ['transaction' => $transaction->id]);

    $response = $this->post($url);

    $response->assertOk();
});
