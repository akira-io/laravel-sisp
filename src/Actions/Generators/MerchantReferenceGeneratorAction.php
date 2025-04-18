<?php

declare(strict_types=1);

namespace Akira\Sisp\Actions\Generators;

final class MerchantReferenceGeneratorAction
{
    /**
     * Generate a unique merchant reference ID.
     */
    public function __invoke(): string
    {
        return mb_trim('R'.now()->format('YmdHis'));
    }
}
