<?php

declare(strict_types=1);

namespace Tests\Controllers;

use Akira\Sisp\Actions\GetTransactionStatusAction;
use Mockery\MockInterface;

it('returns the transaction status', function () {
    $mockedResponse = [
        'result' => true,
        'transactionSuccess' => true,
        'transactionStatusDescription' => 'C-SUCESSO',
        'msg' => 'Success',
    ];

    $this->mock(GetTransactionStatusAction::class, function (MockInterface $mock) use ($mockedResponse) {
        $mock->shouldReceive('handle')->andReturn($mockedResponse);
    });

    $response = $this->postJson(route('sisp.transaction-status'), [
        'posID' => 12345,
        'posAuthCode' => 'test_code',
        'merchantRef' => 'test_ref',
    ]);

    $response->assertStatus(200);
    $response->assertJson($mockedResponse);
});
