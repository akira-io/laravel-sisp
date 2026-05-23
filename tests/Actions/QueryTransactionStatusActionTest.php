<?php

declare(strict_types=1);

use Akira\Sisp\Actions\QueryTransactionStatusAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('queries the sandbox transaction status endpoint with basic authentication', function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        'https://comerciante.teste.sisp.cv/pos/transaction-status' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-STATUS-1',
    ]);

    $response = resolve(QueryTransactionStatusAction::class)->handle($transaction);

    expect($response->result)->toBeTrue()
        ->and($response->transactionSuccess)->toBeTrue()
        ->and($response->transactionStatusDescription)->toBe('C-SUCESSO');

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://comerciante.teste.sisp.cv/pos/transaction-status'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('portal:secret'))
        && $request['posID'] === 'TEST_POS_001'
        && $request['posAuthCode'] === 'TEST_POS_AUT_CODE'
        && $request['merchantRef'] === 'MR-STATUS-1');
});

it('returns an unsuccessful response when SISP returns a non-success HTTP status', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        '*' => Http::response(['msg' => 'Forbidden'], 403),
    ]);

    $response = resolve(QueryTransactionStatusAction::class)->handle('MR-STATUS-2');

    expect($response->result)->toBeFalse()
        ->and($response->transactionSuccess)->toBeFalse()
        ->and($response->msg)->toContain('HTTP 403');
});
