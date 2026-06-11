<?php

declare(strict_types=1);

use Akira\Sisp\Events\PaymentCompleted;
use Akira\Sisp\Events\PaymentFailed;
use Akira\Sisp\Facades\Sisp;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Pipelines\Callback\CallbackContext;
use Akira\Sisp\Pipelines\Callback\HandleCallbackPipeline;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
});

it('completes a transaction through the callback pipeline with a valid sandbox payload', function (): void {
    Event::fake();

    $transaction = Transaction::factory()->create([
        'merchant_ref' => 'MR-PIPE-CB',
        'merchant_session' => 'MS-PIPE-CB',
        'amount' => 42.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 42.0,
        'merchantRef' => 'MR-PIPE-CB',
        'merchantSession' => 'MS-PIPE-CB',
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $context = resolve(HandleCallbackPipeline::class)->run(new CallbackContext($payload));

    expect($context->failed())->toBeFalse()
        ->and($context->failureReason)->toBeNull()
        ->and($context->transaction()->id)->toBe($transaction->id)
        ->and($context->transaction()->status->value)->toBe('completed');

    Event::assertDispatched(PaymentCompleted::class);
});

it('short-circuits and fails the transaction when the fingerprint is invalid', function (): void {
    Event::fake();

    Transaction::factory()->create([
        'merchant_ref' => 'MR-PIPE-BAD',
        'merchant_session' => 'MS-PIPE-BAD',
        'amount' => 42.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 42.0,
        'merchantRef' => 'MR-PIPE-BAD',
        'merchantSession' => 'MS-PIPE-BAD',
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $tampered = Akira\Sisp\ValueObjects\CallbackPayload::from([
        ...$payload->toArray(),
        'resultFingerPrint' => 'tampered-fingerprint',
    ]);

    $context = resolve(HandleCallbackPipeline::class)->run(new CallbackContext($tampered));

    expect($context->failed())->toBeTrue()
        ->and($context->failureReason)->toBe('invalid_callback_fingerprint')
        ->and($context->transaction()->status->value)->toBe('failed')
        ->and($context->transaction()->merchant_response)->toBe('invalid_callback_fingerprint');

    Event::assertDispatched(PaymentFailed::class);
});

it('fails the transaction when callback details do not match', function (): void {
    Event::fake();

    Transaction::factory()->create([
        'merchant_ref' => 'MR-PIPE-MISMATCH',
        'merchant_session' => 'MS-PIPE-MISMATCH',
        'amount' => 99.0,
        'currency' => '132',
        'transaction_code' => '1',
        'status' => 'pending',
    ]);

    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 42.0,
        'merchantRef' => 'MR-PIPE-MISMATCH',
        'merchantSession' => 'MS-PIPE-MISMATCH',
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $context = resolve(HandleCallbackPipeline::class)->run(new CallbackContext($payload));

    expect($context->failed())->toBeTrue()
        ->and($context->failureReason)->toBe('callback_details_mismatch')
        ->and($context->transaction()->status->value)->toBe('failed');

    Event::assertDispatched(PaymentFailed::class);
});

it('throws when accessing the transaction before resolution', function (): void {
    $payload = Sisp::generateSandboxPayload(PaymentRequestData::from([
        'amount' => 1.0,
        'merchantRef' => 'MR-NONE',
        'merchantSession' => 'MS-NONE',
        'timeStamp' => '2026-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    new CallbackContext($payload)->transaction();
})->throws(LogicException::class, 'The callback transaction has not been resolved yet.');
