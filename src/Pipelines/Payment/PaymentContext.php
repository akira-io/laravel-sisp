<?php

declare(strict_types=1);

namespace Akira\Sisp\Pipelines\Payment;

use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Illuminate\Http\Request;
use LogicException;

final class PaymentContext
{
    public ?PaymentRequest $paymentRequest = null;

    public ?Transaction $transaction = null;

    public function __construct(
        public readonly PaymentRequestData $data,
        public readonly Request $request,
    ) {}

    public function paymentRequest(): PaymentRequest
    {
        return $this->paymentRequest ?? throw new LogicException('The payment request has not been built yet.');
    }

    public function transaction(): Transaction
    {
        return $this->transaction ?? throw new LogicException('The transaction has not been persisted yet.');
    }
}
