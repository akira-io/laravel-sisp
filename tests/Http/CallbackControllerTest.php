<?php

declare(strict_types=1);

use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Sisp as SispManager;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\SispCredentials;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
});

function callback_controller_payload(Transaction $transaction, array $overrides = []): array
{
    return Sisp::generateSandboxPayload(PaymentRequestData::from(array_merge([
        'amount' => $transaction->amount,
        'merchantRef' => $transaction->merchant_ref,
        'merchantSession' => $transaction->merchant_session,
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => $transaction->currency,
        'transactionCode' => $transaction->transaction_code ?? '1',
    ], $overrides)))->toArray();
}

it('redirects when user cancelled flag present', function (): void {
    config()->set('sisp.redirect_url', '/home');
    $this->post(route('sisp.callback'), ['UserCancelled' => true])
        ->assertRedirect('/home');
});

it('renders response for existing transaction via GET', function (): void {
    $t = Transaction::factory()->create([
        'merchant_ref' => 'MR-G1',
        'merchant_session' => 'MS-G1',
        'amount' => 10,
        'currency' => '132',
        'status' => 'pending',
        'locale' => 'pt',
    ]);

    $this->get(route('sisp.callback', ['ref' => 'MR-G1']))
        ->assertOk();
});

it('handles POST callback and redirects to GET with ref', function (): void {
    $t = Transaction::factory()->create([
        'merchant_ref' => 'MR-G2',
        'merchant_session' => 'MS-G2',
        'amount' => 20,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-G2',
        'merchantSession' => 'MS-G2',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $this->post(route('sisp.callback'), $payload->toArray())
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-G2']));

    $t->refresh();

    expect($t->status->value)->toBe('completed')
        ->and($t->transaction_id)->toBe($payload->transactionID)
        ->and($t->message_type)->toBe($payload->messageType)
        ->and($t->merchant_response)->toBe($payload->merchantResponse)
        ->and($t->response_code)->toBe($payload->merchantRespCp)
        ->and($t->fingerprint)->toBe($payload->fingerprint);
});

it('records callbacks with invalid fingerprints as failed and redirects to response page', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-G3',
        'merchant_session' => 'MS-G3',
        'amount' => 20,
        'currency' => '132',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-G3',
        'merchantSession' => 'MS-G3',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]))->toArray();
    $payload['resultFingerPrint'] = 'invalid-fingerprint';

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-G3']));

    $transaction->refresh();

    expect($transaction->status->value)->toBe('failed')
        ->and($transaction->merchant_response)->toBe('invalid_callback_fingerprint')
        ->and($transaction->fingerprint)->toBe('invalid-fingerprint');
});

it('redirects invalid callbacks with unknown transaction keys', function (): void {
    config()->set('sisp.redirect_url', '/home');

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-UNKNOWN',
        'merchantSession' => 'MS-UNKNOWN',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]))->toArray();
    $payload['resultFingerPrint'] = 'invalid-fingerprint';

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect('/home');
});

it('skips duplicate lookup when callback payload has no transaction keys', function (): void {
    config()->set('sisp.redirect_url', '/home');
    config()->set('sisp.merchant_reference', '');
    config()->set('sisp.merchant_session', '');

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]))->toArray();
    unset($payload['merchantRespMerchantRef'], $payload['merchantRespMerchantSession']);

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect('/home');
});

it('records signed amount mismatches as failed without completing the transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-AMOUNT-MISMATCH',
        'merchant_session' => 'MS-AMOUNT-MISMATCH',
        'amount' => 20,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), callback_controller_payload($transaction, ['amount' => 25]))
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-AMOUNT-MISMATCH']));

    $transaction->refresh();

    expect($transaction->status->value)->toBe('failed')
        ->and($transaction->merchant_response)->toBe('callback_details_mismatch');
});

it('records signed currency mismatches as failed without completing the transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-CURRENCY-MISMATCH',
        'merchant_session' => 'MS-CURRENCY-MISMATCH',
        'amount' => 20,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), callback_controller_payload($transaction, ['currency' => '978']))
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-CURRENCY-MISMATCH']));

    $transaction->refresh();

    expect($transaction->status->value)->toBe('failed')
        ->and($transaction->merchant_response)->toBe('callback_details_mismatch');
});

it('records signed pos id mismatches as failed without completing the transaction', function (): void {
    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-POS-MISMATCH',
        'merchant_session' => 'MS-POS-MISMATCH',
        'amount' => 20,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $scoped = resolve(SispManager::class)->forCredentials(SispCredentials::from([
        'pos_id' => 'OTHER_POS',
        'pos_aut_code' => config('sisp.posAutCode'),
        'currency' => '132',
        'merchant_id' => config('sisp.merchantID'),
        'url' => config('sisp.endpoint'),
        'language_messages' => config('sisp.languageMessages'),
        'fingerprint_version' => config('sisp.fingerPrintVersion'),
        'is_3d_sec' => config('sisp.is3DSec'),
        'sandbox' => true,
        'url_merchant_response' => config('sisp.url_merchant_response'),
    ]));

    $payload = $scoped->generateSandboxPayload(PaymentRequestData::from([
        'amount' => 20,
        'merchantRef' => 'MR-POS-MISMATCH',
        'merchantSession' => 'MS-POS-MISMATCH',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]))->toArray();

    $this->post(route('sisp.callback'), $payload)
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-POS-MISMATCH']));

    $transaction->refresh();

    expect($transaction->status->value)->toBe('failed')
        ->and($transaction->merchant_response)->toBe('callback_details_mismatch');
});

it('reconciles zero transaction codes without falling back to config default', function (): void {
    config()->set('sisp.transaction_code', '1');

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-ZERO-CODE',
        'merchant_session' => 'MS-ZERO-CODE',
        'amount' => 20,
        'currency' => '132',
        'transaction_code' => '0',
        'status' => 'pending',
    ]);

    $this->post(route('sisp.callback'), callback_controller_payload($transaction))
        ->assertRedirect(route('sisp.callback', ['ref' => 'MR-ZERO-CODE']));

    expect($transaction->refresh()->status->value)->toBe('completed');
});
