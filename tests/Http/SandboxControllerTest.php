<?php

declare(strict_types=1);

it('returns auto-submitting html form for sandbox callback', function (): void {
    $response = $this->get(route('sisp.sandbox', [
        'status' => 'success',
        'amount' => 12.34,
        'merchantRef' => 'MR-TEST',
        'merchantSession' => 'MS-TEST',
        'timeStamp' => '2024-01-01 00:00:00',
        'currency' => '132',
        'transactionCode' => '1',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/html; charset=utf-8');
    $response->assertSee('form', false);
    $response->assertSee('sisp/callback');
});
