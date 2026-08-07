<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('pay_ins', function (Blueprint $table): void {
            $table->uuid('origin_account_id')->nullable()->after('transaction_id');
            $table->foreign('origin_account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('restrict');
            $table->index('origin_account_id');
        });

        Schema::create('account_movements', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('account_id');
            $table->enum('type', ['credit', 'debit']);
            $table->bigInteger('amount');
            $table->char('currency', 3);
            $table->bigInteger('balance_after');
            $table->uuid('pay_in_id')->nullable();
            $table->timestamp('occurred_at');

            $table->foreign('account_id')
                ->references('id')
                ->on('accounts')
                ->onDelete('cascade');

            $table->foreign('pay_in_id')
                ->references('id')
                ->on('transactions')
                ->onDelete('set null');

            $table->index(['account_id', 'occurred_at']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE account_movements ADD CONSTRAINT account_movements_amount_check CHECK (amount >= 0)');
            Schema::getConnection()->statement('ALTER TABLE account_movements ADD CONSTRAINT account_movements_balance_after_check CHECK (balance_after >= 0)');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE account_movements DROP CHECK account_movements_amount_check');
            Schema::getConnection()->statement('ALTER TABLE account_movements DROP CHECK account_movements_balance_after_check');
        }

        Schema::dropIfExists('account_movements');

        Schema::table('pay_ins', function (Blueprint $table): void {
            $table->dropIndex(['origin_account_id']);
            $table->dropForeign(['origin_account_id']);
            $table->dropColumn('origin_account_id');
        });
    }
};
