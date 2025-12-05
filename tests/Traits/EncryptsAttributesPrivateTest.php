<?php

declare(strict_types=1);

use Akira\Sisp\Traits\EncryptsAttributes;

it('private isEncrypted returns false for non-string', function (): void {
    // Create an anonymous class using the trait to reflect the method
    $obj = new class {
        use EncryptsAttributes;
        public function callIsEncrypted(mixed $v): bool {
            $ref = new ReflectionClass($this);
            $m = $ref->getMethod('isEncrypted');
            $m->setAccessible(true);
            return $m->invoke($this, $v);
        }
    };

    expect($obj->callIsEncrypted(['not','a','string']))->toBeFalse();
});

