<?php

declare(strict_types=1);

use Akira\Sisp\Actions\BuildRefundRequestAction;
use Akira\Sisp\Contracts\TransactionCredentialsResolver;
use Akira\Sisp\Exceptions\UnknownMerchantCredentialsException;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\TransactionData;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

function tenantCredentials(): SispCredentials
{
    return SispCredentials::from([
        'pos_id' => 'TENANT_POS',
        'pos_aut_code' => 'TENANT_SECRET',
        'currency' => '132',
        'merchant_id' => 'TENANT',
        'url' => 'https://tenant.example.com/gateway',
        'sandbox' => true,
    ]);
}

/**
 * The application's resolver: it knows the tenant's credentials by posID.
 */
function bindTenantCredentialsResolver(): void
{
    app()->instance(TransactionCredentialsResolver::class, new class implements TransactionCredentialsResolver
    {
        public function resolveFor(Transaction $transaction): SispCredentials
        {
            return match ($transaction->posId()) {
                'TENANT_POS' => tenantCredentials(),
                default => SispCredentials::fromConfig(resolve(Akira\Sisp\Configuration\LoadConfig::class)),
            };
        }
    });
}

function tenantTransaction(string $ref = 'MR-TENANT'): Transaction
{
    $request = Sisp::forCredentials(tenantCredentials())->buildRequestPayload(PaymentRequestData::from([
        'amount' => 50.0,
        'merchantRef' => $ref,
        'merchantSession' => "S-{$ref}",
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    return Sisp::storeTransaction(TransactionData::from([
        'merchantRef' => $request->merchantRef,
        'merchantSession' => $request->merchantSession,
        'amount' => 50.0,
        'currency' => '132',
        'transactionCode' => '1',
        'locale' => 'pt',
        'payload' => $request->toArray(),
    ]));
}

it('stores the posID a transaction was built for', function (): void {
    $transaction = tenantTransaction();

    expect($transaction->refresh()->pos_id)->toBe('TENANT_POS')
        ->and($transaction->posId())->toBe('TENANT_POS');
});

it('reads the posID out of the stored payload for rows created before the column', function (): void {
    $transaction = Transaction::factory()->create(['payload' => ['posID' => 'OLD_POS']]);
    $transaction->forceFill(['pos_id' => null])->save();

    expect($transaction->refresh()->posId())->toBe('OLD_POS');
});

it('accepts the callback of a payment built with another merchant\'s credentials', function (): void {
    bindTenantCredentialsResolver();
    $transaction = tenantTransaction('MR-TENANT-CB');

    $payload = Sisp::forCredentials(tenantCredentials())->generateSandboxPayload(PaymentRequestData::from([
        'amount' => 50.0,
        'merchantRef' => 'MR-TENANT-CB',
        'merchantSession' => 'S-MR-TENANT-CB',
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]), 'success');

    expect(Sisp::validateCallback($payload))->toBeFalse();

    $this->post(route('sisp.callback'), $payload->toArray())->assertRedirect();

    expect($transaction->refresh()->status->value)->toBe('completed');
});

it('rejects the callback of a merchant the application has no credentials for', function (): void {
    Log::spy();
    app()->instance(TransactionCredentialsResolver::class, new class implements TransactionCredentialsResolver
    {
        public function resolveFor(Transaction $transaction): SispCredentials
        {
            throw new UnknownMerchantCredentialsException((string) $transaction->posId());
        }
    });
    $transaction = tenantTransaction('MR-UNKNOWN-CB');

    $payload = Sisp::forCredentials(tenantCredentials())->generateSandboxPayload(PaymentRequestData::from([
        'amount' => 50.0,
        'merchantRef' => 'MR-UNKNOWN-CB',
        'merchantSession' => 'S-MR-UNKNOWN-CB',
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]), 'success');

    $this->post(route('sisp.callback'), $payload->toArray())->assertRedirect();

    expect($transaction->refresh()->status->value)->toBe('pending');
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => str_contains($message, 'no credentials are known') && $context['pos_id'] === 'TENANT_POS')->once();
});

it('queries the status of a transaction with the credentials it was built for', function (): void {
    bindTenantCredentialsResolver();
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'transactionStatusDescription' => 'C', 'msg' => 'ok'])]);

    $tenant = tenantTransaction('MR-TENANT-STATUS');
    $own = Transaction::factory()->create(['merchant_ref' => 'MR-OWN-STATUS', 'status' => 'pending']);

    Sisp::queryTransactionStatus($tenant);
    Sisp::queryTransactionStatus($own);

    Http::assertSent(fn (Request $request): bool => $request['merchantRef'] === 'MR-TENANT-STATUS' && $request['posID'] === 'TENANT_POS' && $request['posAuthCode'] === 'TENANT_SECRET');
    Http::assertSent(fn (Request $request): bool => $request['merchantRef'] === 'MR-OWN-STATUS' && $request['posID'] === 'TEST_POS_001' && $request['posAuthCode'] === 'TEST_POS_AUT_CODE');
});

it('does not keep a scoped merchant on the cached driver for the next status query', function (): void {
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'transactionStatusDescription' => 'C', 'msg' => 'ok'])]);

    Sisp::forCredentials(tenantCredentials())->queryTransactionStatus('MR-SCOPED-FIRST');
    Sisp::queryTransactionStatus('MR-DEFAULT-SECOND');

    expect(Sisp::driver()->paymentEndpoint())->toBe('https://test.sisp.example.com');
    Http::assertSent(fn (Request $request): bool => $request['merchantRef'] === 'MR-SCOPED-FIRST' && $request['posID'] === 'TENANT_POS');
    Http::assertSent(fn (Request $request): bool => $request['merchantRef'] === 'MR-DEFAULT-SECOND' && $request['posID'] === 'TEST_POS_001');
});

it('signs the refund request with the credentials the transaction was built for', function (): void {
    bindTenantCredentialsResolver();
    $transaction = tenantTransaction('MR-TENANT-REFUND');
    $transaction->update(['status' => 'completed', 'transaction_id' => 'TID-1', 'response_code' => '5']);

    $recorded = Sisp::refund($transaction->refresh())->amount(20.0)->record();

    $request = $recorded->refunds()->sole()->request;
    $expected = Sisp::forCredentials(tenantCredentials())->buildRequestPayload(PaymentRequestData::from(['amount' => 1]));

    expect($request['posID'])->toBe('TENANT_POS')
        ->and($expected->posID)->toBe('TENANT_POS')
        ->and($request['fingerprint'])->toBe(
            resolve(Akira\Sisp\Configuration\CredentialScope::class)->run(
                tenantCredentials(),
                fn (): string => resolve(BuildRefundRequestAction::class)->partial($transaction, 20.0)->fingerprint,
            ),
        );
});

it('falls back to the active credentials for another posID by default, with a warning', function (): void {
    Log::spy();
    $transaction = Transaction::factory()->create(['pos_id' => 'SOMEONE_ELSE']);

    $credentials = resolve(TransactionCredentialsResolver::class)->resolveFor($transaction);

    expect($credentials->posId)->toBe('TEST_POS_001');
    Log::shouldHaveReceived('warning')->withArgs(fn (string $message, array $context): bool => str_contains($message, 'another posID') && $context['pos_id'] === 'SOMEONE_ELSE')->once();
});

it('rejects a callback signed by another merchant when no resolver knows it', function (): void {
    $transaction = tenantTransaction('MR-FOREIGN-CB');

    $payload = Sisp::forCredentials(tenantCredentials())->generateSandboxPayload(PaymentRequestData::from([
        'amount' => 50.0,
        'merchantRef' => 'MR-FOREIGN-CB',
        'merchantSession' => 'S-MR-FOREIGN-CB',
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]), 'success');

    $this->post(route('sisp.callback'), $payload->toArray())->assertRedirect();

    expect($transaction->refresh()->status->value)->toBe('pending');
});

it('queries the status by merchant reference with the credentials the stored payment was built for', function (): void {
    bindTenantCredentialsResolver();
    config()->set('sisp.transaction_status.portal_id', 'portal');
    config()->set('sisp.transaction_status.portal_password', 'secret');
    Http::fake(['*' => Http::response(['result' => true, 'transactionSuccess' => true, 'transactionStatusDescription' => 'C', 'msg' => 'ok'])]);

    tenantTransaction('MR-TENANT-BY-REF');

    Sisp::queryTransactionStatus('MR-TENANT-BY-REF');
    Sisp::queryTransactionStatus('MR-UNKNOWN-REF');

    Http::assertSent(fn (Request $request): bool => $request['merchantRef'] === 'MR-TENANT-BY-REF' && $request['posID'] === 'TENANT_POS' && $request['posAuthCode'] === 'TENANT_SECRET');
    Http::assertSent(fn (Request $request): bool => $request['merchantRef'] === 'MR-UNKNOWN-REF' && $request['posID'] === 'TEST_POS_001');
});

it('builds a retry under the credentials the payment was built for', function (): void {
    bindTenantCredentialsResolver();
    $transaction = tenantTransaction('MR-TENANT-RETRY');
    $transaction->update(['status' => 'failed']);

    $retry = resolve(Akira\Sisp\Actions\RetryPaymentAction::class)->handle($transaction->refresh());

    expect($retry->posID)->toBe('TENANT_POS')
        ->and($retry->fingerprint)->toBe(Sisp::forCredentials(tenantCredentials())->buildRequestPayload(PaymentRequestData::from([
            'amount' => 50.0,
            'merchantRef' => 'MR-TENANT-RETRY',
            'merchantSession' => 'S-MR-TENANT-RETRY',
            'timeStamp' => $retry->timeStamp,
            'currency' => '132',
            'transactionCode' => '1',
            'locale' => 'pt',
        ]))->fingerprint);
});
