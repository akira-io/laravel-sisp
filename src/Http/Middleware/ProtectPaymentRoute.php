<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Middleware;

use Akira\Sisp\Models\Transaction;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProtectPaymentRoute
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check if transaction already exists to prevent reprocessing
        if ($this->hasProcessedTransaction($request)) {
            return redirect('/')->with('error', 'This transaction has already been processed.');
        }

        return $next($request);
    }

    private function hasProcessedTransaction(Request $request): bool
    {
        $merchantRef = $request->input('merchantRef');
        $merchantSession = $request->input('merchantSession');

        if (! $merchantRef || ! $merchantSession) {
            return false;
        }

        return Transaction::where('merchant_ref', $merchantRef)
            ->where('merchant_session', $merchantSession)
            ->whereIn('status', ['completed', 'failed', 'pending'])
            ->exists();
    }
}
