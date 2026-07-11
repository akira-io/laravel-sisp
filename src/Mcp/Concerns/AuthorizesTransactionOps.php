<?php

declare(strict_types=1);

namespace Akira\Sisp\Mcp\Concerns;

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Request;

trait AuthorizesTransactionOps
{
    private function isAuthorized(Request $request, Transaction $transaction): bool
    {
        $ability = config('sisp.mcp.web.ability');

        if ($ability === null) {
            return true;
        }

        $user = $request->user();

        if (! $user instanceof \Illuminate\Contracts\Auth\Authenticatable) {
            return true;
        }

        return Gate::forUser($user)->allows((string) $ability, $transaction);
    }
}
