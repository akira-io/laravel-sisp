<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Models\Invoice;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\Support\InertiaAvailability;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;

final readonly class RenderPaymentResponseAction
{
    public function __construct(
        private GetPaymentErrorResponseAction $getErrorResponse,
        private GetPaymentResponseTranslationsAction $getTranslations,
        private CanRetryPaymentAction $canRetryPayment,
        private InertiaAvailability $inertiaAvailability,
    ) {}

    public function renderBlade(Transaction $transaction, array $payload): View
    {
        $allowRetry = $this->canRetryPayment->handle($transaction);

        return view('sisp::payment-response', [
            'transaction' => $transaction,
            'payload' => $payload,
            'error' => $this->getStructuredError($transaction),
            'allowRetry' => $allowRetry,
            'retryUrl' => $allowRetry ? $this->retryUrl($transaction) : null,
        ]);
    }

    public function renderInertia(Transaction $transaction, array $payload, string $component = 'Sisp/PaymentResponse'): mixed
    {
        if (! $this->inertiaAvailability->available()) {
            return $this->renderBlade($transaction, $payload);
        }

        $invoice = $transaction->invoice;
        $allowRetry = $this->canRetryPayment->handle($transaction);

        return Inertia::render($component, [
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'formatted_amount' => $transaction->formatted_amount,
                'currency' => $transaction->currency,
                'merchant_ref' => $transaction->merchant_ref,
                'merchant_session' => $transaction->merchant_session,
                'message_type' => $transaction->message_type,
            ],
            'error' => $this->getStructuredError($transaction),
            'translations' => $this->getTranslations->handle(),
            'allowRetry' => $allowRetry,
            'retryUrl' => $allowRetry ? $this->retryUrl($transaction) : null,
            'invoice' => $invoice instanceof Invoice ? [
                'invoice_number' => $invoice->invoice_number,
                'invoice_date' => $invoice->invoice_date->toDateString(),
                'status' => $invoice->status->value,
                'pdf_path' => $invoice->pdf_path,
                'pdf_url' => $invoice->pdf_url,
            ] : null,
            'payload' => $payload,
        ]);
    }

    private function getStructuredError(Transaction $transaction): ?array
    {
        if (! $transaction->message_type) {
            return null;
        }

        $errorType = ErrorMessageType::tryFrom($transaction->message_type);

        if (! $errorType) {
            return null;
        }

        return $this->getErrorResponse->handle($errorType)->toArray();
    }

    private function retryUrl(Transaction $transaction): string
    {
        return URL::temporarySignedRoute(
            'sisp.retry-payment',
            now()->addMinutes(30),
            ['transaction' => $transaction->id],
        );
    }
}
