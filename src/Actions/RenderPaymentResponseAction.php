<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Transaction;
use Illuminate\Contracts\View\View;
use Inertia\Inertia;

final readonly class RenderPaymentResponseAction
{
    public function renderBlade(Transaction $transaction, array $payload): View
    {
        return view('sisp::payment-response', [
            'transaction' => $transaction,
            'payload' => $payload,
        ]);
    }

    public function renderInertia(Transaction $transaction, array $payload, string $component = 'Sisp/PaymentResponse'): mixed
    {
        if (!class_exists('Inertia\Inertia')) {
            return $this->renderBlade($transaction, $payload);
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
            'invoice' => $invoice ? [
                'invoice_number' => $invoice->invoice_number,
                'pdf_path' => $invoice->pdf_path,
            ] : null,
            'payload' => $payload,
        ]);
    }
}