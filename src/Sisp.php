<?php

namespace Akira\Sisp;

use Illuminate\Database\Eloquent\Collection;

class Sisp
{
    // Build your next great package.

    public function getTransactions(): array|Collection
    {
        return Transaction::all();
    }
}
