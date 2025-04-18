<?php

declare(strict_types=1);

namespace Akira\Sisp\Events;

use Illuminate\Foundation\Events\Dispatchable;

final class SispPaymentCancelledByUser
{
    use Dispatchable;

    /**
     * Create a new event instance.
     *
     * @param  array<string, string>  $data
     */
    public function __construct(public array $data) {}
}
