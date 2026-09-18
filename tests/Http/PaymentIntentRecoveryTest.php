<?php

declare(strict_types=1);

use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Models\PaymentIntent;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Akira\Sisp\Pipelines\Payment\ProcessPaymentPipeline;

final class FailAfterPersistingTransactionPipe implements PaymentPipe
{
    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        throw new RuntimeException('rendering failed after the transaction was stored');
    }
}

final class DropPaymentIntentRowPipe implements PaymentPipe
{
    public function handle(PaymentContext $context, Closure $next): PaymentContext
    {
        PaymentIntent::query()->delete();

        return $next($context);
    }
}

beforeEach(function (): void {
    config()->set('sisp.sandbox', true);
    config()->set('sisp.rate_limiting.enabled', false);
});

function payment_intent_recovery_payload(string $key): array
{
    return [
        'amount' => 100.0,
        'items' => [[
            'product_name' => 'Ticket',
            'quantity' => 1,
            'unit_price' => 100.0,
            'total_price' => 100.0,
        ]],
        'customer_name' => 'Intent Customer',
        'customer_email' => 'intent@example.test',
        'checkout_intent_id' => $key,
    ];
}

it('reclaims an intent stuck in processing once the timeout has passed', function (): void {
    PaymentIntent::query()->create(['idempotency_key' => 'stuck-intent', 'status' => 'processing']);
    PaymentIntent::query()->where('idempotency_key', 'stuck-intent')->update(['updated_at' => now()->subMinutes(11)]);

    $this->post(route('sisp.payment'), payment_intent_recovery_payload('stuck-intent'))->assertOk();

    expect(PaymentIntent::query()->sole())
        ->status->toBe('submitted')
        ->transaction_id->not->toBeNull();
});

it('keeps refusing an intent that is still processing within the timeout', function (): void {
    PaymentIntent::query()->create(['idempotency_key' => 'busy-intent', 'status' => 'processing']);
    PaymentIntent::query()->where('idempotency_key', 'busy-intent')->update(['updated_at' => now()->subMinutes(9)]);

    $this->post(route('sisp.payment'), payment_intent_recovery_payload('busy-intent'));

    expect(PaymentIntent::query()->sole()->status)->toBe('processing')
        ->and(Transaction::query()->count())->toBe(0);
});

it('never reclaims a processing intent when the timeout is zero', function (): void {
    config()->set('sisp.idempotency.processing_timeout_seconds', 0);
    PaymentIntent::query()->create(['idempotency_key' => 'pinned-intent', 'status' => 'processing']);
    PaymentIntent::query()->where('idempotency_key', 'pinned-intent')->update(['updated_at' => now()->subDay()]);

    $this->post(route('sisp.payment'), payment_intent_recovery_payload('pinned-intent'));

    expect(PaymentIntent::query()->sole()->status)->toBe('processing')
        ->and(Transaction::query()->count())->toBe(0);
});

it('records the transaction on a failure after it was stored so a retry does not create another', function (): void {
    config()->set('sisp.pipelines.payment', [...ProcessPaymentPipeline::DEFAULT_PIPES, FailAfterPersistingTransactionPipe::class]);

    $this->post(route('sisp.payment'), payment_intent_recovery_payload('late-failure-intent'));

    $intent = PaymentIntent::query()->sole();
    $transaction = Transaction::query()->sole();

    expect($intent->status)->toBe('failed')
        ->and($intent->transaction_id)->toBe($transaction->id);

    config()->set('sisp.pipelines.payment', ProcessPaymentPipeline::DEFAULT_PIPES);

    $this->post(route('sisp.payment'), payment_intent_recovery_payload('late-failure-intent'));

    expect(Transaction::query()->count())->toBe(1);
});

it('marks the intent submitted even when its row disappeared during the pipeline', function (): void {
    config()->set('sisp.pipelines.payment', [...ProcessPaymentPipeline::DEFAULT_PIPES, DropPaymentIntentRowPipe::class]);

    $this->post(route('sisp.payment'), payment_intent_recovery_payload('vanished-intent'))->assertOk();

    expect(PaymentIntent::query()->sole())
        ->idempotency_key->toBe('vanished-intent')
        ->status->toBe('submitted')
        ->transaction_id->toBe(Transaction::query()->sole()->id);
});
