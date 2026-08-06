<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('client_id');
            $table->char('currency', 3);
            $table->bigInteger('balance')->default(0);
            $table->timestamps();

            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->onDelete('cascade');

            $table->unique(['client_id', 'currency']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            Schema::getConnection()->statement('ALTER TABLE accounts ADD CONSTRAINT accounts_balance_check CHECK (balance >= 0)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
