<?php

declare(strict_types=1);

use Akira\Sisp\Actions\CreateAndStorePaymentTransactionAction;

it('uses Laravel defer helper explicitly when scheduling invoice generation', function (): void {
    $reflection = new ReflectionClass(CreateAndStorePaymentTransactionAction::class);
    $fileName = $reflection->getFileName();

    expect($fileName)->toBeString();

    $source = file_get_contents((string) $fileName);

    expect($source)->toBeString()
        ->and($source)->toContain('use function Illuminate\Support\defer;')
        ->and($source)->toContain('defer(');
});
