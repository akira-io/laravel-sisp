<?php

declare(strict_types=1);

namespace Akira\Sisp\Fields;

final class PurchaseRequest
{
    /**
     * Create a new purchase request.
     *
     * @param  array<string, string>  $data
     */
    public static function make(array $data): string
    {
        return base64_encode((string) json_encode(self::purchaseRequestData($data)));
    }

    /**
     * Get the purchase request data.
     *
     * @param  array<string, string>  $data
     * @return array<string, mixed>
     */
    private static function purchaseRequestData(array $data): array
    {
        return [
            'acctID' => 'x',
            'acctInfo' => [
                'chAccAgeInd' => '05',
                'chAccChange' => '20220328',
                'chAccDate' => '20220328',
                'chAccPwChange' => '20220328',
                'chAccPwChangeInd' => '05',
                'suspiciousAccActivity' => '01',
            ],
            'email' => data_get($data, 'email'),
            'addrMatch' => 'N',
            'billAddrCity' => data_get($data, 'billAddrCity'),
            'billAddrCountry' => data_get($data, 'billAddrCountry'),
            'billAddrLine1' => data_get($data, 'billAddrLine1'),
            'billAddrLine2' => 'Somada',
            'billAddrLine3' => 'Cutelo',
            'billAddrPostCode' => data_get($data, 'billAddrPostCode'),
            'billAddrState' => '18',
            'shipAddrCity' => 'City',
            'shipAddrCountry' => '620',
            'shipAddrLine1' => '000',
            'shipAddrPostCode' => '000',
            'shipAddrState' => '18',
            'workPhone' => [
                'cc' => '123',
                'subscriber' => '2389112233',
            ],
            'mobilePhone' => [
                'cc' => '123',
                'subscriber' => '2389112233',
            ],
        ];
    }
}
