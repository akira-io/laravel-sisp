<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum TransactionStatus: string
{
    case pending = 'pending';
    case completed = 'completed';
    case failed = 'failed';
    case cancelled = 'cancelled';
    case refunded = 'refunded';
}
