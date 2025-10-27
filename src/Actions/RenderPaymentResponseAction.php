<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Transaction;
use Illuminate\Contracts\View\View;

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

        return \Inertia\Inertia::render($component, [
            'transaction' => [
                'id' => $transaction->id,
                'status' => $transaction->status,
                'amount' => $transaction->amount,
                'currency' => $transaction->currency,
                'merchant_ref' => $transaction->merchant_ref,
                'merchant_session' => $transaction->merchant_session,
                'message_type' => $transaction->message_type,
            ],
            'payload' => $payload,
        ]);
    }
}