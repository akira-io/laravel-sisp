<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\CreateRetryPaymentAttemptAction;
use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\Actions\RetryPaymentAction;
use Akira\Sisp\Http\Requests\RetryPaymentRequest;
use Akira\Sisp\Models\Transaction;

final readonly class RetryPaymentController
{
    public function __construct(
        private RetryPaymentAction $retryPayment,
        private RenderPaymentFormBasedOnConfigAction $renderForm,
        private CreateRetryPaymentAttemptAction $createRetryAttempt,
    ) {}

    public function __invoke(RetryPaymentRequest $request): mixed
    {
        $transaction = Transaction::query()->findOrFail($request->integer('transaction'));

        if ($request->isMethod('get')) {
            return $this->renderForm->handle(
                $this->retryPayment->handle($transaction, rotateMerchantSession: false),
                $transaction->locale,
            );
        }

        $paymentRequest = $this->createRetryAttempt->handle($transaction);

        return $this->renderForm->handle($paymentRequest, $transaction->locale);
    }
}
