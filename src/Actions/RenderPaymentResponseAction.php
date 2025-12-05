<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Configuration\LoadConfig;
use Akira\Sisp\Enums\ErrorMessageType;
use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\View\View;
use Inertia\Inertia;

final readonly class RenderPaymentResponseAction
{
    public function __construct(
        private GetPaymentErrorResponseAction $getErrorResponse,
        private GetPaymentResponseTranslationsAction $getTranslations,
        private LoadConfig $config,
    ) {}

    public function renderBlade(Transaction $transaction, array $payload): View
    {
        return view('sisp::payment-response', [
            'transaction' => $transaction,
            'payload' => $payload,
            'error' => $this->getStructuredError($transaction),
            'allowRetry' => $this->config->isRetryAllowed(),
        ]);
    }

    public function renderInertia(Transaction $transaction, array $payload, string $component = 'Sisp/PaymentResponse'): mixed
    {
        if (! class_exists(Inertia::class)) {
            return $this->renderBlade($transaction, $payload); // @codeCoverageIgnore
        }

        $invoice = $transaction->invoice;

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
            'allowRetry' => $this->config->isRetryAllowed(),
            'invoice' => $invoice ? [
                'invoice_number' => $invoice->invoice_number,
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
}
