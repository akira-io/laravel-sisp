<?php

declare(strict_types=1);

use Akira\Sisp\Actions\GenerateFingerprintAction;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\TransactionData;
use Illuminate\Support\Facades\Http;

it('builds request payload via facade with deterministic fingerprint', function (): void {
    $data = PaymentRequestData::from([
        'amount' => 10.5,
        'merchantRef' => 'MR-X',
        'merchantSession' => 'MS-Y',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    $req = Sisp::buildRequestPayload($data);
    $arr = $req->toArray();

    $expectedFingerprint = resolve(GenerateFingerprintAction::class)->handle([
        'timeStamp' => '2024-01-01 00:00:00',
        'amount' => 10.5,
        'merchantRef' => 'MR-X',
        'merchantSession' => 'MS-Y',
        'posID' => config('sisp.posID'),
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    expect($arr['fingerprint'])->toBe($expectedFingerprint)
        ->and($arr['merchantRef'])->toBe('MR-X')
        ->and($arr['merchantSession'])->toBe('MS-Y');
});

it('generates sandbox payload and validates callback', function (): void {
    config()->set('sisp.sandbox', true);

    $data = PaymentRequestData::from([
        'amount' => 50.0,
        'merchantRef' => 'MR-CB',
        'merchantSession' => 'MS-CB',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    $payload = Sisp::generateSandboxPayload($data, 'success');

    expect($payload->fingerprint)->not->toBe('')
        ->and(Sisp::validateCallback($payload))->toBeTrue();
});

it('stores a transaction and lists it via facade', function (): void {
    $t = Sisp::storeTransaction(TransactionData::from([
        'merchantRef' => 'MR-S',
        'merchantSession' => 'MS-S',
        'amount' => 99.0,
        'currency' => '132',
        'transactionCode' => '1',
        'locale' => 'pt',
        'payload' => ['foo' => 'bar'],
    ]));

    expect($t)->toBeInstanceOf(Transaction::class)
        ->and($t->exists)->toBeTrue();

    $all = Sisp::getTransactions()->get();
    expect($all->contains(fn ($tr): bool => $tr->id === $t->id))->toBeTrue();
});

it('handles payment callback and updates status', function (): void {
    config()->set('sisp.sandbox', true);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CB2',
        'merchant_session' => 'MS-CB2',
        'amount' => 20.0,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $data = PaymentRequestData::from([
        'amount' => 20.0,
        'merchantRef' => 'MR-CB2',
        'merchantSession' => 'MS-CB2',
        'timeStamp' => '2024-01-03 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    $payload = Sisp::generateSandboxPayload($data, 'success');

    $updated = Sisp::handlePaymentCallback($payload);

    expect($updated->status->value)->toBe('completed');
});

it('queries transaction status through the facade', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        '*' => Http::response([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ]),
    ]);

    $response = Sisp::queryTransactionStatus('MR-FACADE-STATUS');

    expect($response->result)->toBeTrue()
        ->and($response->transactionSuccess)->toBeTrue()
        ->and($response->paymentStatus()->value)->toBe('completed');
});

it('does not expose failed status API requests as payment failures through the facade', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fake([
        '*' => Http::response(['msg' => 'Forbidden'], 403),
    ]);

    $response = Sisp::queryTransactionStatus('MR-FACADE-FAILED-QUERY');

    expect($response->result)->toBeFalse()
        ->and($response->paymentStatus()->value)->toBe('pending')
        ->and($response->message)->toContain('HTTP 403');
});

it('reconciles completed and failed payments through the facade', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');

    Http::fakeSequence()
        ->push([
            'result' => true,
            'transactionSuccess' => true,
            'transactionStatusDescription' => 'C-SUCESSO',
            'msg' => 'Approved',
        ])
        ->push([
            'result' => true,
            'transactionSuccess' => false,
            'transactionStatusDescription' => 'E-ERRO',
            'msg' => 'Declined',
        ]);

    $completed = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_response' => null,
    ]);

    $failed = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_response' => null,
    ]);

    expect(Sisp::reconcileTransactionStatus($completed)->status->value)->toBe('completed')
        ->and(Sisp::reconcileTransactionStatus($failed)->status->value)->toBe('failed');
});
