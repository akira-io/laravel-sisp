<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createTransactionsTable();
        $this->createTransactionItemsTable();
        $this->createInvoicesTable();
        $this->createRequestMetadataTable();
        $this->createRateLimitsTable();
        $this->createBlacklistTable();
    }

    public function down(): void
    {
        $blacklistTable = config('sisp.tables.blacklist', 'sisp_blacklist');
        $rateLimitsTable = config('sisp.tables.rate_limits', 'sisp_rate_limits');
        $metadataTable = config('sisp.tables.request_metadata', 'sisp_request_metadata');
        $invoicesTable = config('sisp.tables.invoices', 'sisp_invoices');
        $itemsTable = config('sisp.tables.transaction_items', 'sisp_transaction_items');
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');

        Schema::dropIfExists($blacklistTable);
        Schema::dropIfExists($rateLimitsTable);
        Schema::dropIfExists($metadataTable);
        Schema::dropIfExists($invoicesTable);
        Schema::dropIfExists($itemsTable);
        Schema::dropIfExists($transactionsTable);
    }

    private function createTransactionsTable(): void
    {
        $tableName = config('sisp.tables.transactions', 'sisp_transactions');

        throw_if(empty($tableName), Exception::class, 'Error: config/sisp.php not loaded. Run [php artisan config:clear] and try again.');

        Schema::create($tableName, function (Blueprint $table): void {
            $table->id();
            $table->string('merchant_ref');
            $table->string('merchant_session');
            $table->float('amount');
            $table->string('currency')->default('132');
            $table->string('status')->default('pending');
            $table->string('transaction_code')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('message_type')->nullable();
            $table->string('response_code')->nullable();
            $table->text('merchant_response')->nullable();
            $table->text('fingerprint')->nullable();
            $table->longText('payload')->nullable();

            // Client information
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_country')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('locale', 5)->default('pt');

            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->timestamps();
            $table->index(['merchant_ref', 'merchant_session', 'status', 'message_type']);
            $table->index(['transaction_id']);
            $table->index(['customer_email']);
        });
    }

    private function createTransactionItemsTable(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $itemsTable = config('sisp.tables.transaction_items', 'sisp_transaction_items');

        Schema::create($itemsTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->constrained($transactionsTable)
                ->onDelete('cascade');
            $table->string('product_id')->nullable();
            $table->string('product_name');
            $table->integer('quantity')->default(1);
            $table->bigInteger('unit_price_cents');
            $table->bigInteger('total_price_cents');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['transaction_id', 'product_id']);
        });
    }

    private function createInvoicesTable(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $invoicesTable = config('sisp.tables.invoices', 'sisp_invoices');

        Schema::create($invoicesTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->unique()
                ->constrained($transactionsTable)
                ->onDelete('cascade');
            $table->string('invoice_number')->unique();
            $table->date('invoice_date');
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');

            // Customer information (denormalized from transaction)
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_city')->nullable();
            $table->string('customer_address')->nullable();
            $table->string('customer_country')->nullable();

            $table->text('notes')->nullable();
            $table->string('pdf_path')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['invoice_number', 'status']);
        });
    }

    private function createRequestMetadataTable(): void
    {
        $transactionsTable = config('sisp.tables.transactions', 'sisp_transactions');
        $metadataTable = config('sisp.tables.request_metadata', 'sisp_request_metadata');

        Schema::create($metadataTable, function (Blueprint $table) use ($transactionsTable): void {
            $table->id();
            $table->foreignId('transaction_id')
                ->nullable()
                ->constrained($transactionsTable)
                ->onDelete('cascade');
            $table->string('ip_address');
            $table->string('user_agent')->nullable();
            $table->string('referer')->nullable();
            $table->string('country_code')->nullable();
            $table->string('country_name')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('isp')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('device_fingerprint')->nullable();
            $table->integer('response_time_ms')->nullable();
            $table->string('api_version')->nullable();
            $table->boolean('is_vpn')->default(false);
            $table->boolean('is_proxy')->default(false);
            $table->boolean('is_mobile')->default(false);
            $table->integer('risk_score')->default(0);
            $table->string('risk_reason')->nullable();
            $table->json('custom_metadata')->nullable();
            $table->timestamps();
            $table->index(['ip_address', 'created_at']);
            $table->index(['country_code']);
            $table->index(['device_fingerprint']);
            $table->index(['risk_score']);
            $table->index(['transaction_id']);
        });
    }

    private function createRateLimitsTable(): void
    {
        $rateLimitsTable = config('sisp.tables.rate_limits', 'sisp_rate_limits');

        Schema::create($rateLimitsTable, function (Blueprint $table): void {
            $table->id();
            $table->string('identifier'); // IP, user ID, or merchant ID
            $table->string('limit_type'); // 'ip', 'user', 'merchant', 'product'
            $table->string('context')->nullable(); // Product ID, merchant ID, etc
            $table->integer('hits')->default(1);
            $table->integer('limit')->default(100); // Max hits allowed
            $table->integer('window_seconds')->default(3600); // 1 hour
            $table->timestamp('reset_at');
            $table->boolean('is_blocked')->default(false);
            $table->timestamp('blocked_until')->nullable();
            $table->timestamps();
            $table->index(['identifier', 'limit_type', 'reset_at']);
            $table->index(['is_blocked']);
            $table->index(['reset_at']);
        });
    }

    private function createBlacklistTable(): void
    {
        $blacklistTable = config('sisp.tables.blacklist', 'sisp_blacklist');

        Schema::create($blacklistTable, function (Blueprint $table): void {
            $table->id();
            $table->string('type'); // 'ip', 'email', 'phone', 'card_hash', 'device_fingerprint'
            $table->string('value');
            $table->string('reason')->nullable();
            $table->string('severity'); // 'low', 'medium', 'high', 'critical'
            $table->text('notes')->nullable();
            $table->string('added_by')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['type', 'value']);
            $table->index(['type', 'value']);
            $table->index(['expires_at']);
            $table->index(['severity']);
        });
    }
};
