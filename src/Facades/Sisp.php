<?php

declare(strict_types=1);

namespace Akira\Sisp\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Akira\Sisp\ScopedSisp forCredentials(\Akira\Sisp\ValueObjects\SispCredentials $credentials)
 * @method static \Illuminate\Database\Eloquent\Collection getTransactions()
 * @method static \Akira\Sisp\ValueObjects\PaymentRequest buildRequestPayload(\Akira\Sisp\ValueObjects\PaymentRequestData $data)
 * @method static bool validateCallback(\Akira\Sisp\ValueObjects\CallbackPayload $payload)
 * @method static \Akira\Sisp\Models\Transaction handlePaymentCallback(\Akira\Sisp\ValueObjects\CallbackPayload $payload)
 * @method static \Akira\Sisp\ValueObjects\CallbackPayload generateSandboxPayload(\Akira\Sisp\ValueObjects\PaymentRequestData $data, string $status = 'success')
 * @method static \Akira\Sisp\Models\Transaction storeTransaction(\Akira\Sisp\ValueObjects\TransactionData $data)
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
 *
 * @see \Akira\Sisp\Sisp
 */
final class Sisp extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return \Akira\Sisp\Sisp::class;
    }
}
