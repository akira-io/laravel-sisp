<?php

declare(strict_types=1);

use Akira\Sisp\Http\Controllers\CancelTransactionController;
use Akira\Sisp\Models\Transaction;
use Illuminate\Http\Request;

it('cancels a pending transaction and redirects', function (): void {
    $t = Transaction::factory()->create([
        'status' => 'pending',
        'merchant_ref' => 'MR-C',
        'merchant_session' => 'MS-C',
    ]);

    $controller = resolve(CancelTransactionController::class);
    $request = Request::create('/sisp/cancel?reason=user_cancelled&merchantRef=MR-C', 'GET');
    $response = $controller($t, $request);

    expect($response->isRedirect())->toBeTrue();
});
