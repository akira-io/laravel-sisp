<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Controllers;

use Akira\Sisp\Actions\CancelTransactionAction;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use LogicException;

final readonly class CancelTransactionController
{
    public function __construct(
        private CancelTransactionAction $cancelTransaction,
    ) {}

    public function __invoke(Request $request): RedirectResponse
    {
        $reason = $request->query('reason', 'user_cancelled');
        $transaction = $this->resolveTransaction($request);

        if (! $transaction instanceof Transaction) {
            return back()->with('error', __('laravel-sisp::messages.validation.transaction_not_found'));
        }

        try {
            $this->cancelTransaction->handle($transaction, $reason);

            return to_route('sisp.callback', ['ref' => $transaction->merchant_ref]);

        } catch (LogicException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function resolveTransaction(Request $request): ?Transaction
    {
        $merchantRef = $request->input('merchantRef');
        if (is_string($merchantRef) && $merchantRef !== '') {
            return Transaction::query()
                ->where('merchant_ref', $merchantRef)
                ->first();
        }

        $transactionId = $request->input('transaction_id');
        if (! is_string($transactionId) || $transactionId === '') {
            return null;
        }

        return Transaction::query()->where('transaction_id', $transactionId)->first();
    }
}
