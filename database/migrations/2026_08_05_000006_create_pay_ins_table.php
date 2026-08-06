<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('pay_ins', function (Blueprint $table): void {
            $table->uuid('transaction_id')->primary();
            $table->uuid('account_id');
            $table->uuid('payment_method_id');
            $table->bigInteger('fees')->default(0);

            $table->foreign('transaction_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('cascade');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('restrict');

            $table->foreign('payment_method_id')
                ->references('id')
                ->on('payment_methods')
                ->onDelete('restrict');

            $table->index('account_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pay_ins');
    }
};
