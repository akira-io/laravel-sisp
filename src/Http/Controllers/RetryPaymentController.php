<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\Actions\RetryPaymentAction;
use Akira\Sisp\Models\Transaction;

final readonly class RetryPaymentController
{
    public function __construct(
        private RetryPaymentAction $retryPayment,
        private RenderPaymentFormBasedOnConfigAction $renderForm,
    ) {}

    public function __invoke(Transaction $transaction): mixed
    {
        $paymentRequest = $this->retryPayment->handle($transaction);

        return $this->renderForm->handle($paymentRequest, $transaction->locale);
    }
}
