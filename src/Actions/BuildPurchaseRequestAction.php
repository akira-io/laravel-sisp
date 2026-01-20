<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions;

use Akira\Sisp\ValueObjects\ThreeDSecureData;
use JsonException;

final readonly class BuildPurchaseRequestAction
{
    /**
     * @throws JsonException
     */
    public function handle(ThreeDSecureData $data): string
    {
        $payload = [
            'acctID' => 'x',
            'acctInfo' => $this->buildAcctInfo(),
            'email' => $data->email,
            'addrMatch' => 'N',
            'billAddrCity' => $data->billAddrCity,
            'billAddrCountry' => $data->billAddrCountry,
            'billAddrLine1' => $data->billAddrLine1,
            'billAddrLine2' => $data->billAddrLine2 ?? '',
            'billAddrLine3' => $data->billAddrLine3 ?? '',
            'billAddrPostCode' => $data->billAddrPostCode,
            'billAddrState' => $data->billAddrState ?? '',
            'shipAddrCity' => 'City',
            'shipAddrCountry' => '132',
            'shipAddrLine1' => '000',
            'shipAddrPostCode' => '000',
            'shipAddrState' => '',
            'workPhone' => ['cc' => '238', 'subscriber' => '0000000'],
            'mobilePhone' => $this->buildPhone($data->mobilePhone),
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    /**
     * @return array<string, string>
     */
    private function buildAcctInfo(): array
    {
        $date = now()->format('Ymd');

        return [
            'chAccAgeInd' => '05',
            'chAccChange' => $date,
            'chAccDate' => $date,
            'chAccPwChange' => $date,
            'chAccPwChangeInd' => '05',
            'suspiciousAccActivity' => '01',
        ];
    }

    /**
     * @return array{cc: string, subscriber: string}
     */
    private function buildPhone(?string $phone): array
    {
        return [
            'cc' => '238',
            'subscriber' => $phone ?? '0000000',
        ];
    }
}
