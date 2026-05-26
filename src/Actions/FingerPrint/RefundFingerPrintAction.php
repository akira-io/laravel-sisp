<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\FingerPrint;

use Akira\Sisp\Actions\PostAutCode;
use Akira\Sisp\Support\SispAmount;

final readonly class RefundFingerPrintAction
{
    public function __construct(private PostAutCode $postAutCode) {}

    /**
     * @param  array<string, float|int|string>  $data
     */
    public function handle(array $data): string
    {
        $fields = [
            $this->postAutCode->handle(),
            mb_trim((string) ($data['timeStamp'] ?? '')),
            SispAmount::toThousandths($data['amount'] ?? 0),
            mb_trim((string) ($data['merchantRef'] ?? '')),
            mb_trim((string) ($data['merchantSession'] ?? '')),
            mb_trim((string) ($data['posID'] ?? '')),
            mb_trim((string) ($data['currency'] ?? '')),
            mb_trim((string) ($data['transactionCode'] ?? '')),
            mb_str_pad(mb_trim((string) ($data['clearingPeriod'] ?? '')), 4, '0', STR_PAD_LEFT),
            mb_str_pad(mb_trim((string) ($data['transactionID'] ?? '')), 8, '0', STR_PAD_LEFT),
        ];

        return base64_encode(hash('sha512', implode('', $fields), true));
    }
}
