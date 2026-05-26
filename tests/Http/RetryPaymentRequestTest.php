<?php

declare(strict_types=1);

use Akira\Sisp\Http\Requests\RetryPaymentRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

it('validates retry transactions against the configured transaction table', function (): void {
    config()->set('sisp.tables.transactions', 'custom_sisp_transactions');

    Schema::create('custom_sisp_transactions', function (Blueprint $table): void {
        $table->id();
    });

    DB::table('custom_sisp_transactions')->insert(['id' => 123]);

    $validator = Validator::make(['transaction' => 123], new RetryPaymentRequest()->rules());

    expect($validator->passes())->toBeTrue();
});
