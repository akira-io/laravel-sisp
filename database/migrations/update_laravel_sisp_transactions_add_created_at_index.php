<?php

declare(strict_types=1);

use Akira\Sisp\Support\TransactionIndexMigration;

return new class extends TransactionIndexMigration
{
    protected function columns(): array
    {
        return ['created_at'];
    }
};
