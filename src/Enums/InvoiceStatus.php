<?php

declare(strict_types=1);

namespace Akira\Sisp\Enums;

enum InvoiceStatus: string
{
    case pending = 'pending';
    case issued = 'issued';
    case paid = 'paid';
    case overdue = 'overdue';
    case cancelled = 'cancelled';
}
