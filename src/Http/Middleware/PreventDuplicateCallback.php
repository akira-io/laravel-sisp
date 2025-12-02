<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Middleware;

use Akira\Sisp\Models\Transaction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class PreventDuplicateCallback
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if this callback has already been processed
        if ($this->isAlreadyProcessed($request)) {
            return redirect(config('sisp.redirect_url', '/'))->with('info', 'This payment has already been processed.');
        }

        return $next($request);
    }

    private function isAlreadyProcessed(Request $request): bool
    {
        $merchantRef = $request->input('merchantRespMerchantRef');
        $merchantSession = $request->input('merchantRespMerchantSession');
        $request->input('merchantRespTid');

        if (! $merchantRef || ! $merchantSession) {
            return false;
        }

        $transaction = Transaction::query()
            ->where('merchant_ref', $merchantRef)
            ->where('merchant_session', $merchantSession)
            ->first();

        // If transaction exists and has been updated with response data, it's already processed
        return $transaction && $transaction->transaction_id;
    }
}
