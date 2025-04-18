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
        'messageType',
        'merchantRespCP',
        'merchantRespTid',
        'merchantRespMerchantRef',
        'merchantRespMerchantSession',
        'merchantRespPurchaseAmount',
        'merchantRespMessageID',
        'merchantRespPan',
        'merchantResp',
        'merchantRespErrorCode',
        'merchantRespErrorDescription',
        'merchantRespErrorDetail',
        'languageMessages',
        'merchantRespTimeStamp',
        'merchantRespReferenceNumber',
        'merchantRespEntityCode',
        'merchantRespClientReceipt',
        'merchantRespAdditionalErrorMessage',
        'merchantRespReloadCode',
        'transactionId',
        'options',
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
            'options' => 'array',
        ];
    }
}
