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
        $logsTable = config('sisp.tables.transaction_logs', 'sisp_transaction_logs');

        Schema::create($logsTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained($transactionsTable)
                ->onDelete('cascade');
            $table->string('source')->default('model');
            $table->json('changed_attributes');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->timestamps();
            $table->index(['transaction_id', 'created_at']);
            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('sisp.tables.transaction_logs', 'sisp_transaction_logs'));
    }
};
