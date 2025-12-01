<?php

declare(strict_types=1);

namespace Akira\Sisp\Concerns;

use Akira\Sisp\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait HasSispTransactions
{
    /** @return BelongsTo<Transaction, $this> */
    public function sispTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'sisp_transaction_id');
    }
}
