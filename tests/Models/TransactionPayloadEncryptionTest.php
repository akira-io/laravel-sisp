<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

it('encrypts and decrypts payload array transparently', function (): void {
    $payload = ['foo' => 'bar', 'n' => 1];

    $t = Transaction::query()->create([
        'merchant_ref' => 'MR-PL-1',
        'merchant_session' => 'MS-PL-1',
        'amount' => 10.0,
        'currency' => '132',
        'status' => 'pending',
        'transaction_code' => '1',
        'payload' => $payload,
        'locale' => 'pt',
    ]);

    $raw = \DB::table($t->getTable())->where('id', $t->id)->value('payload');
    expect(is_string($raw))->toBeTrue()
        ->and($raw)->not->toContain('foo')
        ->and($raw)->not->toContain('bar');

    $fresh = Transaction::query()->findOrFail($t->id);
    expect($fresh->payload)->toBe($payload);
});

