<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $paymentIntentsTable = config('sisp.tables.payment_intents', 'sisp_payment_intents');

        Schema::create($paymentIntentsTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained($transactionsTable)
                ->nullOnDelete();
            $table->string('status')->default('processing');
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['transaction_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sisp.tables.payment_intents', 'sisp_payment_intents'));
    }
};
