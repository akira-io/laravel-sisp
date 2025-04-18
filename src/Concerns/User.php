<?php

declare(strict_types=1);

namespace Akira\Sisp\Concerns;

use Illuminate\Http\Request;

trait User
{
    /**
     * Check if the payment was cancelled by the user.
     */
    public function isCancelledByUser(Request $request): bool
    {

        return $request->has('UserCancelled')
            && $request->UserCancelled === 'true';
    }
}
