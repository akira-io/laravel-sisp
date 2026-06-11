<?php

declare(strict_types=1);

namespace Akira\Sisp\Facades;

use Akira\Sisp\Contracts\SispDriver;
use Akira\Sisp\Models\Transaction;
use Akira\Sisp\ScopedSisp;
use Akira\Sisp\ValueObjects\CallbackPayload;
use Akira\Sisp\ValueObjects\PaymentRequest;
use Akira\Sisp\ValueObjects\PaymentRequestData;
use Akira\Sisp\ValueObjects\SispCredentials;
use Akira\Sisp\ValueObjects\TransactionData;
use Akira\Sisp\ValueObjects\TransactionStatusResponse;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ScopedSisp forCredentials(SispCredentials $credentials)
 * @method static SispDriver driver(string|null $driver = null)
 * @method static Builder getTransactions()
 * @method static PaymentRequest buildRequestPayload(PaymentRequestData $data)
 * @method static bool validateCallback(CallbackPayload $payload)
 * @method static Transaction handlePaymentCallback(CallbackPayload $payload)
 * @method static TransactionStatusResponse queryTransactionStatus(Transaction|string $transaction)
 * @method static Transaction reconcileTransactionStatus(Transaction $transaction)
 * @method static CallbackPayload generateSandboxPayload(PaymentRequestData $data, string $status = 'success')
 * @method static Transaction storeTransaction(TransactionData $data)
 * @method static string getMerchantReference()
 * @method static string getMerchantSession()
 * @method static string getTimeStamp()
 * @method static string getCurrency()
 * @method static string getPosId()
 * @method static string getPosAutCode()
 * @method static string getIs3Dsec()
 * @method static string getUrlMerchantResponse()
 * @method static string getLanguageMessages()
 * @method static string getFingerprintVersion()
 * @method static string getDefaultTransactionCode()
 * @method static string getUri()
 * @method static array<string, array{alpha2: string, numeric: string, name: string, flag: string}> countries()
 * @method static string getCountryNumericCode(string $alpha2)
 * @method static string getCountryFlag(string $alpha2)
 * @method static string|null getCountryName(string $alpha2)
 */
final class Sisp extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Akira\Sisp\Sisp::class;
    }
}
