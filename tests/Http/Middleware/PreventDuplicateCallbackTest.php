<?php

declare(strict_types=1);

use Akira\Sisp\Models\Transaction;

function generateTestFingerprint(array $data): string {
    // Mimic PostAutCode logic
    $posAutCodeVal = config('sisp.posAutCode');
    $posAutCodeHash = base64_encode(hash('sha512', $posAutCodeVal, true));

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
