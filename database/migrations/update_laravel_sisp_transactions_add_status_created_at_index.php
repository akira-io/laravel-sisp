<?php

declare(strict_types=1);

use Akira\Sisp\Support\TransactionIndexMigration;

return new class extends TransactionIndexMigration
{
    protected function columns(): array
    {
        return ['status', 'created_at'];
    }
};
