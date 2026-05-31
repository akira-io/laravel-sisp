<?php

declare(strict_types=1);

use Akira\Sisp\Configuration\EnvSispCredentialsResolver;
use Akira\Sisp\Contracts\SispCredentialsResolver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Sisp;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\TransactionData;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('scoped sisp uses credentials and restores resolver', function (): void {
    config()->set('sisp.merchant_ref', 'CFG-REF');
    config()->set('sisp.merchant_session', 'CFG-SESSION');
    config()->set('sisp.transaction_code', '7');

    $credentials = SispCredentials::from([
        'pos_id' => 'SCOPED_POS',
        'pos_aut_code' => 'SCOPED_AUT',
        'currency' => '978',
        'merchant_id' => 'MID',
        'url' => 'https://scoped.example.com',
        'language_messages' => 'EN',
        'fingerprint_version' => '1',
        'is_3d_sec' => '0',
        'sandbox' => true,
        'url_merchant_response' => 'https://scoped.example.com/callback',
    ]);

    $scoped = resolve(Sisp::class)->forCredentials($credentials);

    $before = app()->make(SispCredentialsResolver::class);

    $request = $scoped->buildRequestPayload(PaymentRequestData::from([
        'amount' => 10.5,
        'merchantRef' => 'MR-SCOPED',
        'merchantSession' => 'MS-SCOPED',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '978',
        'transactionCode' => '7',
    ]));

    $after = app()->make(SispCredentialsResolver::class);

    expect($before)->toBeInstanceOf(EnvSispCredentialsResolver::class)
        ->and(spl_object_id($before))->toBe(spl_object_id($after));

    $payload = $request->toArray();

    expect($payload['posID'])->toBe('SCOPED_POS')
        ->and($payload['currency'])->toBe('978')
        ->and($payload['languageMessages'])->toBe('EN')
        ->and($payload['urlMerchantResponse'])->toBe('https://scoped.example.com/callback');

    expect($scoped->getMerchantReference())->toBe('CFG-REF')
        ->and($scoped->getMerchantSession())->toBe('CFG-SESSION')
        ->and($scoped->getTimeStamp())->toBeString()
        ->and($scoped->getTimeStamp())->not->toBe('')
        ->and($scoped->getDefaultTransactionCode())->toBe('7')
        ->and($scoped->getCurrency())->toBe('978')
        ->and($scoped->getPosId())->toBe('SCOPED_POS')
        ->and($scoped->getPosAutCode())->toBe('SCOPED_AUT')
        ->and($scoped->getIs3Dsec())->toBe('0')
        ->and($scoped->getLanguageMessages())->toBe('EN')
        ->and($scoped->getFingerprintVersion())->toBe('1')
        ->and($scoped->getUri())->toBe('https://scoped.example.com');
});

it('scoped sisp handles sandbox callbacks and transactions', function (): void {
    $credentials = SispCredentials::from([
        'pos_id' => config('sisp.posID'),
        'pos_aut_code' => config('sisp.posAutCode'),
        'currency' => '132',
        'merchant_id' => 'MID2',
        'url' => 'https://scoped.example.com',
        'language_messages' => 'EN',
        'fingerprint_version' => '1',
        'is_3d_sec' => '0',
        'sandbox' => true,
        'url_merchant_response' => null,
    ]);

    $scoped = resolve(Sisp::class)->forCredentials($credentials);

    $stored = $scoped->storeTransaction(TransactionData::from([
        'merchantRef' => 'MR-STORE',
        'merchantSession' => 'MS-STORE',
        'amount' => 15.0,
        'currency' => '132',
        'transactionCode' => '1',
        'locale' => 'pt',
        'payload' => ['foo' => 'bar'],
    ]));

    $all = $scoped->getTransactions()->get();
    expect($all->contains(fn (Transaction $t): bool => $t->id === $stored->id))->toBeTrue();

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CB-SC',
        'merchant_session' => 'MS-CB-SC',
        'amount' => 20.0,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $data = PaymentRequestData::from([
        'amount' => 20.0,
        'merchantRef' => 'MR-CB-SC',
        'merchantSession' => 'MS-CB-SC',
        'timeStamp' => '2024-01-03 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]);

    $payload = $scoped->generateSandboxPayload($data, 'success');

    expect($scoped->validateCallback($payload))->toBeTrue()
        ->and($scoped->getUrlMerchantResponse())->toBe(route('sisp.callback'));

    $updated = $scoped->handlePaymentCallback($payload);

    expect($updated->status->value)->toBe('completed')
        ->and($updated->id)->toBe($transaction->id);
});

it('scoped sisp queries and reconciles transaction status with explicit credentials', function (): void {
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

    $credentials = SispCredentials::from([
        'pos_id' => 'SCOPED_STATUS_POS',
        'pos_aut_code' => 'SCOPED_STATUS_AUT',
        'currency' => '132',
        'merchant_id' => 'MID3',
        'url' => 'https://scoped.example.com',
        'language_messages' => 'EN',
        'fingerprint_version' => '1',
        'is_3d_sec' => '0',
        'sandbox' => true,
        'url_merchant_response' => null,
    ]);

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-SCOPED-STATUS',
        'status' => 'pending',
    ]);

    $scoped = resolve(Sisp::class)->forCredentials($credentials);

    $response = $scoped->queryTransactionStatus($transaction);
    $updated = $scoped->reconcileTransactionStatus($transaction);

    expect($response->paymentStatus()->value)->toBe('completed')
        ->and($updated->status->value)->toBe('completed');

    Http::assertSent(fn (Request $request): bool => $request['posID'] === 'SCOPED_STATUS_POS'
        && $request['posAuthCode'] === 'SCOPED_STATUS_AUT'
        && $request['merchantRef'] === 'MR-SCOPED-STATUS');
});
