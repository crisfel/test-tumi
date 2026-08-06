<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->enum('type', ['payin']);
            $table->uuid('client_id');
            $table->bigInteger('amount');
            $table->char('currency', 3);
            $table->enum('status', ['created', 'validated', 'processing', 'processed', 'failed']);
            $table->string('reference', 64)->nullable()->unique();
            $table->uuid('provider_id')->nullable();
            $table->string('provider_transaction_id', 64)->nullable();
            $table->json('provider_response')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->string('error_message', 500)->nullable();
            $table->timestamp('created_at');
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->bigInteger('version')->default(1);

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('restrict');

            $table->foreign('provider_id')
                ->references('id')
                ->on('payment_providers')
                ->onDelete('restrict');

            $table->index(['status', 'created_at']);
            $table->index('client_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE transactions ADD CONSTRAINT transactions_amount_check CHECK (amount >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
