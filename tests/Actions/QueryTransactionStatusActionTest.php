<?php

declare(strict_types=1);

use Akira\Sisp\Actions\QueryTransactionStatusAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('queries the SISP test transaction-status endpoint with basic auth', function (): void {
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
        ->and($response->transactionStatusDescription)->toBe('C-SUCESSO')
        ->and($response->paymentStatus())->toBe(TransactionStatus::completed);

    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://comerciante.teste.sisp.cv/pos/transaction-status'
        && $request->hasHeader('Authorization', 'Basic '.base64_encode('portal:secret'))
        && $request['posID'] === 'TEST_POS_001'
        && $request['posAuthCode'] === 'TEST_POS_AUT_CODE'
        && $request['merchantRef'] === 'MR-STATUS-1');
});

it('maps a successful API call with failed payment to failed local status', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => false,
            'transactionStatusDescription' => 'E-ERRO',
            'msg' => 'Declined',
        ]),
    ]);

    $response = resolve(QueryTransactionStatusAction::class)->handle('MR-STATUS-2');

    expect($response->result)->toBeTrue()
        ->and($response->transactionSuccess)->toBeFalse()
        ->and($response->paymentStatus())->toBe(TransactionStatus::failed);
});

it('does not treat a failed API query as a definitive payment failure', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        '*' => Http::response(['msg' => 'Forbidden'], 403),
    ]);

    $response = resolve(QueryTransactionStatusAction::class)->handle('MR-STATUS-3');

    expect($response->result)->toBeFalse()
        ->and($response->paymentStatus())->toBe(TransactionStatus::pending)
        ->and($response->message)->toContain('HTTP 403');
});
