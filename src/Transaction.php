<?php

namespace Akira\Sisp;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
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
        'resultFingerPrint',
        'resultFingerPrintVersion',
        'user_id',
        'session_id',
        'email',
        'with_tickets',
        'is_pending',
    ];

    public function getTable()
    {

        return config('sisp.table_name');
    }
}
