<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;

it('runs against the driver the environment asks for', function (): void {
    expect(DB::connection()->getDriverName())->toBe(env('SISP_TEST_DB_DRIVER', 'sqlite'));
});
