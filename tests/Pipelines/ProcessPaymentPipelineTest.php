<?php

declare(strict_types=1);

use Akira\Sisp\Contracts\PaymentPipe;
use Akira\Sisp\Exceptions\DuplicatePaymentIdentifierException;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Pipelines\Payment\PaymentContext;
use Akira\Sisp\Pipelines\Payment\Pipes\BuildPaymentRequest;
use Akira\Sisp\Pipelines\Payment\Pipes\PersistTransaction;
use Akira\Sisp\Pipelines\Payment\ProcessPaymentPipeline;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Http\Request;

function payment_pipeline_context(float $amount = 25.0): PaymentContext
{
    $request = Request::create('/sisp/payment', 'POST', [
        'amount' => $amount,
        'items' => [[
            'product_name' => 'Pipeline Ticket',
            'quantity' => 1,
            'unit_price' => $amount,
            'total_price' => $amount,
        ]],
        'customer_name' => 'Pipeline Buyer',
    ]);

    return new PaymentContext(
        data: PaymentRequestData::from(['amount' => $amount]),
        request: $request,
    );
}

it('runs the default payment pipeline and persists a transaction', function (): void {
    $context = resolve(ProcessPaymentPipeline::class)->run(payment_pipeline_context());

    expect($context->paymentRequest()->amount)->toBe(25.0)
        ->and($context->transaction())->toBeInstanceOf(Transaction::class)
        ->and($context->transaction()->status->value)->toBe('pending')
        ->and($context->transaction()->items()->count())->toBe(1);
});

it('runs custom pipes configured in sisp.pipelines.payment', function (): void {
    $witness = new class implements PaymentPipe
    {
        public static bool $ran = false;

        public function handle(PaymentContext $context, Closure $next): PaymentContext
        {
            self::$ran = true;

            return $next($context);
        }
    };

    config()->set('sisp.pipelines.payment', [
        BuildPaymentRequest::class,
        PersistTransaction::class,
        $witness,
    ]);

    $context = resolve(ProcessPaymentPipeline::class)->run(payment_pipeline_context(30.0));

    expect($witness::$ran)->toBeTrue()
        ->and($context->transaction()->amount)->toBe(30.0);
});

it('does not retry transaction creation when a downstream pipe reports duplicate identifiers', function (): void {
    $failingPipe = new class implements PaymentPipe
    {
        public function handle(PaymentContext $context, Closure $next): PaymentContext
        {
            throw new DuplicatePaymentIdentifierException;
        }
    };

    config()->set('sisp.pipelines.payment', [
        BuildPaymentRequest::class,
        PersistTransaction::class,
        $failingPipe,
    ]);

    expect(fn () => resolve(ProcessPaymentPipeline::class)->run(payment_pipeline_context(35.0)))
        ->toThrow(DuplicatePaymentIdentifierException::class);

    expect(Transaction::query()->count())->toBe(1)
        ->and(Transaction::query()->sole()->amount)->toBe(35.0);
});

it('throws when accessing the payment request before it is built', function (): void {
    payment_pipeline_context()->paymentRequest();
})->throws(LogicException::class, 'The payment request has not been built yet.');

it('throws when accessing the transaction before it is persisted', function (): void {
    payment_pipeline_context()->transaction();
})->throws(LogicException::class, 'The transaction has not been persisted yet.');
