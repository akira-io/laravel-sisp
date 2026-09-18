<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Concerns;

use Akira\Sisp\Models\Transaction;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;

trait AuthorizesTransactionOps
{
    use ResolvesTransaction;

    private const array POLICY_ABILITIES = ['refund' => 'refund'];

    private function authorizedTransaction(Request $request, string $operation): Transaction|Response
    {
        $identifier = (string) $request->get('transaction');
        $transaction = $this->resolveTransaction($identifier);

        if (! $transaction instanceof Transaction) {
            return $this->transactionNotFound($identifier);
        }

        return $this->denyUnlessAuthorized($request, $operation, $transaction) ?? $transaction;
    }

    private function denyUnlessAuthorized(Request $request, string $operation, ?Transaction $transaction = null): ?Response
    {
        return $this->isAuthorized($request, $operation, $transaction)
            ? null
            : Response::error("Not authorized to {$operation} transactions.");
    }

    private function isAuthorized(Request $request, string $operation, ?Transaction $transaction): bool
    {
        $user = $request->user();

        if (! $user instanceof Authenticatable) {
            return request()->route() === null;
        }

        $gate = Gate::forUser($user);

        if ($gate->denies((string) config('sisp.mcp.web.ability', 'sisp-mcp'), [$operation, $transaction])) {
            return false;
        }

        $policyAbility = self::POLICY_ABILITIES[$operation] ?? null;

        return $policyAbility === null || $gate->allows($policyAbility, $transaction);
    }
}
