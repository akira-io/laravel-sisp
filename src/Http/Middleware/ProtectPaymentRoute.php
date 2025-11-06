<?php

declare(strict_types=1);

namespace Akira\Sisp\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class ProtectPaymentRoute
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if transaction already exists to prevent reprocessing
        if ($this->hasProcessedTransaction($request)) {
            return redirect('/')->with('error', 'This transaction has already been processed.');
        }

        return $next($request);
    }

    /**
     * Check if a transaction with the same merchant reference and session already exists.
     */
    private function hasProcessedTransaction(Request $request): bool
    {
        $merchantRef = $request->input('merchantRef');
        $merchantSession = $request->input('merchantSession');

        if (! $merchantRef || ! $merchantSession) {
            return false;
        }

        return \Akira\Sisp\Models\Transaction::where('merchant_ref', $merchantRef)
            ->where('merchant_session', $merchantSession)
            ->whereIn('status', ['completed', 'failed', 'pending'])
            ->exists();
    }
}
