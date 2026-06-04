<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\RenderPaymentFormBasedOnConfigAction;
use Akira\Sisp\Actions\RetryPaymentAction;
use Akira\Sisp\Enums\TransactionStatus;
use Akira\Sisp\Http\Requests\RetryPaymentRequest;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\TransactionLogContext;

final readonly class RetryPaymentController
{
    public function __construct(
        private RetryPaymentAction $retryPayment,
        private RenderPaymentFormBasedOnConfigAction $renderForm,
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

        $paymentRequest = $this->retryPayment->handle($transaction);

        TransactionLogContext::run(
            'retry',
            fn (): bool => $transaction->update([
                'merchant_session' => $paymentRequest->merchantSession,
                'transaction_id' => null,
                'message_type' => null,
                'merchant_response' => null,
                'response_code' => null,
                'fingerprint' => null,
                'status' => TransactionStatus::pending,
            ])
        );

        return $this->renderForm->handle($paymentRequest, $transaction->locale);
    }
}
