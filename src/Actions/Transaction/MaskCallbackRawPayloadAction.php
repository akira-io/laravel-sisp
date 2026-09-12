<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Transaction;

final readonly class MaskCallbackRawPayloadAction
{
    private const string PAN_KEY = 'merchantRespPan';

    /**
     * @param  array<string, mixed>  $raw
     * @return array<string, mixed>
     */
    public function handle(array $raw): array
    {
        $pan = $raw[self::PAN_KEY] ?? null;

        if (! is_string($pan) || $pan === '') {
            return $raw;
        }

        $raw[self::PAN_KEY] = mb_str_pad(mb_substr($pan, -4), mb_strlen($pan), '*', STR_PAD_LEFT);

        return $raw;
    }
}
