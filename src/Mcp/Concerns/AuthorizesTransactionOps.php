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
        $transaction = $this->resolveTransaction((string) $request->get('transaction'));
        $onWeb = $request->user() instanceof Authenticatable;

        if ($transaction instanceof Response) {
            return $onWeb ? $this->notAuthorizedForTransaction($operation) : $transaction;
        }

        return $this->isAuthorized($request, $operation, $transaction)
            ? $transaction
            : $this->notAuthorizedForTransaction($operation);
    }

    private function denyUnlessAuthorized(Request $request, string $operation): ?Response
    {
        return $this->isAuthorized($request, $operation, null)
            ? null
            : Response::error("Not authorized to {$operation} transactions.");
    }

    private function notAuthorizedForTransaction(string $operation): Response
    {
        return Response::error("Not authorized to {$operation} this transaction, or it does not exist.");
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
