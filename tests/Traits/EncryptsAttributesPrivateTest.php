<?php

declare(strict_types=1);

use Akira\Sisp\Traits\EncryptsAttributes;

it('private isEncrypted returns false for non-string', function (): void {
    // Create an anonymous class using the trait to reflect the method
    $obj = new class
    {
        use EncryptsAttributes;

        public function callIsEncrypted(mixed $v): bool
        {
            $ref = new ReflectionClass($this);
            $m = $ref->getMethod('isEncrypted');

            return $m->invoke($this, $v);
        }
    };

    expect($obj->callIsEncrypted(['not', 'a', 'string']))->toBeFalse();
});

it('private isEncrypted returns false for json-like strings', function (): void {
    $obj = new class
    {
        use EncryptsAttributes;

        public function callIsEncrypted(mixed $v): bool
        {
            $ref = new ReflectionClass($this);
            $m = $ref->getMethod('isEncrypted');

            return $m->invoke($this, $v);
        }
    };

    expect($obj->callIsEncrypted('{ "a": 1 }'))->toBeFalse()
        ->and($obj->callIsEncrypted('[1,2,3]'))->toBeFalse();
});

it('private isEncrypted returns false for short or invalid payloads', function (): void {
    $obj = new class
    {
        use EncryptsAttributes;

        public function callIsEncrypted(mixed $v): bool
        {
            $ref = new ReflectionClass($this);
            $m = $ref->getMethod('isEncrypted');

            return $m->invoke($this, $v);
        }
    };

    $short = str_repeat('a', 50);
    $invalidBase64 = str_repeat('!', 120);
    $jsonString = base64_encode(json_encode('not-an-array'));
    $jsonLongString = base64_encode(json_encode(str_repeat('x', 150)));
    $missingKeys = base64_encode(json_encode(['iv' => 'iv', 'value' => 'val']));

    expect($obj->callIsEncrypted($short))->toBeFalse()
        ->and($obj->callIsEncrypted($invalidBase64))->toBeFalse()
        ->and($obj->callIsEncrypted($jsonString))->toBeFalse()
        ->and($obj->callIsEncrypted($jsonLongString))->toBeFalse()
        ->and($obj->callIsEncrypted($missingKeys))->toBeFalse();
});

it('private isEncrypted returns true for encrypted payloads', function (): void {
    $obj = new class
    {
        use EncryptsAttributes;

        public function callIsEncrypted(mixed $v): bool
        {
            $ref = new ReflectionClass($this);
            $m = $ref->getMethod('isEncrypted');

            return $m->invoke($this, $v);
        }
    };

    $encrypted = Illuminate\Support\Facades\Crypt::encryptString('hello');

    expect($obj->callIsEncrypted($encrypted))->toBeTrue();
});
