<?php

namespace Akira\Sisp\Commands;

use Illuminate\Console\Command;

class SispCommand extends Command
{
    public $signature = 'laravel-sisp';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
