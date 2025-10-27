<?php

declare(strict_types=1);

namespace Akira\Sisp;

use Illuminate\Database\Eloquent\Model;

final class Transaction extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'transactionId',
        'merchantRespMerchantRef',
        'merchantRespMerchantSession',
        'merchantRespPurchaseAmount',
        'details',
    ];

    /**
     * Get the table associated with the model.
     */
    public function getTable(): string
    {

        return type(config('sisp.table_name'))->asString();
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {

        return [
            'details' => 'array',
        ];
    }
}
