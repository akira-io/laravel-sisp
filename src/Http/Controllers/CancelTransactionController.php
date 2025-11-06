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

    public function __invoke(Transaction $transaction, Request $request): RedirectResponse
    {
        $reason = $request->query('reason', 'user_cancelled');

        try {
            $this->cancelTransaction->handle($transaction, $reason);

            return redirect()->back()->with('success', 'Transaction cancelled successfully.');
        } catch (LogicException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
