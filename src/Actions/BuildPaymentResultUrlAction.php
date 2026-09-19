<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\Models\Transaction;
use Illuminate\Support\Facades\URL;

final readonly class BuildPaymentResultUrlAction
{
    public function handle(Transaction $transaction): string
    {
        return URL::temporarySignedRoute(
            'sisp.callback',
            now()->addMinutes(30),
            ['ref' => $transaction->merchant_ref],
        );
    }
}
