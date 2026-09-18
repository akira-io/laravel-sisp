<?php

declare(strict_types=1);

namespace Akira\Sisp\Commands\Concerns;

trait ValidatesIntegerOptions
{
    private function rejectsIntegerOption(string $name, int $minimum, string $message): bool
    {
        $value = $this->option($name);

        if ($value === null) {
            return false;
        }

        if (filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => $minimum]]) !== false) {
            return false;
        }

        $this->error($message);

        return true;
    }
}
