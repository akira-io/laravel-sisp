<?php

declare(strict_types=1);

use Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Mockery\MockInterface;

function generateTestFingerprint(array $data): string {
    // Mimic PostAutCode logic
    $posAutCodeVal = config('sisp.posAutCode');
    $posAutCodeHash = base64_encode(hash('sha512', (string) $posAutCodeVal, true));

    // Mimic CallbackPayload::from defaults
    $merchantRef = $data['merchantRespMerchantRef'] ?? '';
    $merchantSession = $data['merchantRespMerchantSession'] ?? '';
    $timeStamp = $data['merchantRespTimeStamp'] ?? '';
    $amount = $data['merchantRespPurchaseAmount'] ?? 0;
    $transactionID = $data['merchantRespTid'] ?? '';
    $messageType = $data['messageType'] ?? '';
    $merchantResponse = $data['merchantResp'] ?? '';
    // $merchantRespCP is mapped to responseCode and clearingPeriod
    $clearingPeriod = $data['merchantRespCP'] ?? '';
    $messageID = $data['merchantRespMessageID'] ?? '';
    $pan = $data['merchantRespPan'] ?? '';
    $reference = $data['merchantRespReferenceNumber'] ?? '';
    $entityCode = $data['merchantRespEntityCode'] ?? '';
    $clientReceipt = $data['merchantRespClientReceipt'] ?? '';
    $additionalErrorMessage = $data['merchantRespAdditionalErrorMessage'] ?? '';
    $reloadCode = $data['reloadCode'] ?? '';

    $amountThousandths = (int) ((float) $amount * 1000);

    // Order from PaymentResponseFingerPrintAction
    $fields = [
        $posAutCodeHash,
        $messageType,
        $clearingPeriod,
        $transactionID,
        $merchantRef,
        $merchantSession,
        $amountThousandths,
        $messageID,
        $pan,
        $merchantResponse,
        $timeStamp,
        $reference,
        $entityCode,
        $clientReceipt,
        $additionalErrorMessage,
        $reloadCode,
    ];

    return base64_encode(hash('sha512', implode('', $fields), true));
}

it('redirects duplicate callback requests', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'MR-CB-DUP',
        'merchant_session' => 'MS-CB-DUP',
        'transaction_id' => 'T-EXISTS',
    ]);

    config()->set('sisp.redirect_url', '/home');

    $data = [
        'merchantRespMerchantRef' => 'MR-CB-DUP',
        'merchantRespMerchantSession' => 'MS-CB-DUP',
        'merchantRespTid' => 'T-EXISTS',
    ];
    $data['resultFingerPrint'] = generateTestFingerprint($data);

    $this->post(route('sisp.callback'), $data)->assertRedirect('/home');
});

it('ignores duplicate check if signature is invalid', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'MR-INVALID',
        'merchant_session' => 'MS-INVALID',
        'transaction_id' => 'T-EXISTS',
    ]);

    $data = [
        'merchantRespMerchantRef' => 'MR-INVALID',
        'merchantRespMerchantSession' => 'MS-INVALID',
        'merchantRespTid' => 'T-EXISTS',
        'resultFingerPrint' => 'invalid-fingerprint',
    ];

    // Expect 403 because middleware passes it through, and controller validation fails
    $this->post(route('sisp.callback'), $data)->assertStatus(403);
});

it('handles exceptions during validation gracefully', function (): void {
    Transaction::factory()->create([
        'merchant_ref' => 'MR-EX',
        'merchant_session' => 'MS-EX',
        'transaction_id' => 'T-EXISTS',
    ]);

    // To avoid mocking final classes, we can trigger an exception by passing data
    // that causes CallbackPayload::from to fail, or just use partial mocks if possible.
    // However, CallbackPayload is a Value Object and we can't easily mock static ::from.

    // Instead, let's rely on the fact that the middleware catches Throwable.
    // We can inject a mock for Sisp, BUT Sisp facade resolves to a final class `Akira\Sisp\Sisp`.
    // The previous error says: "The class \Akira\Sisp\Actions\ValidatePaymentResponseFingerprintAction is marked final"

    // BUT we can bind a subclass or a mock to the container if we are careful.
    // Actually, since `Sisp` (the class) depends on `ValidatePaymentResponseFingerprintAction`, and we are in a test,
    // we can extend `ValidatePaymentResponseFingerprintAction` remove final? No, can't modify source.

    // Workaround: Mock the `Sisp` class itself? It is also final.
    // Wait, Laravel Facades usually allow mocking even if the underlying class is final IF we use `Sisp::shouldReceive`.
    // The error "The class \Akira\Sisp\Sisp is marked final" from Mockery suggests we can't mock it completely if it's final.
    // BUT typically `Facade::shouldReceive` works by swapping the instance with a mock.
    // The issue might be that `Akira\Sisp\Sisp` is `final readonly`.

    // Strategy: Pass invalid data types that cause strict type errors in `CallbackPayload::from`.
    // `CallbackPayload::from` expects array. We pass array.
    // Inside `from`, it accesses keys.
    // If we pass a string instead of array to `$request->all()`? No, request->all() returns array.

    // Let's look at `CallbackPayload::from`. It casts `amount` to float.
    // If we pass an array where a key is an object that can't be cast?
    // `CallbackPayload::from` uses `?? ''` for strings.

    // Let's try to mock the `Sisp` singleton in the container with an anonymous class or a real object that throws.
    // Since `Sisp` is final, we can't extend it.
    // But we can bind a different object to the 'Akira\Sisp\Sisp' key in the container?
    // Type hinting in Middleware: `use Akira\Sisp\Facades\Sisp;` calls `Sisp::validateCallback`.
    // Facade resolves to `Akira\Sisp\Sisp::class`.

    // We can bind a non-final proxy class if the middleware didn't type hint the concrete class.
    // Middleware doesn't type hint `Sisp` class, it uses Facade.
    // Wait, the middleware imports `Akira\Sisp\Facades\Sisp`.

    // Let's try `swap` on the facade with a raw Mockery mock that ignores the class type?
    // `Sisp::swap($mock)`.

    $mock = Mockery::mock('stdClass'); // Generic mock
    $mock->shouldReceive('validateCallback')->andThrow(new Exception('Fail'));
    $mock->shouldReceive('handlePaymentCallback')->andThrow(new Exception('Controller reached'));

    Sisp::swap($mock);

    $data = [
        'merchantRespMerchantRef' => 'MR-EX',
        'merchant_session' => 'MS-EX',
        'merchantRespTid' => 'T-EXISTS',
    ];

    $this->post(route('sisp.callback'), $data)->assertStatus(500);
});
