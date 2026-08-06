<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_providers', function (Blueprint $table): void {
            $table->json('supported_types')->nullable()->after('configuration');
        });

        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropUnique('payment_methods_account_id_token_unique');
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
            $table->unique(['provider_id', 'token']);
        });
    }

    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table): void {
            $table->dropUnique('payment_methods_provider_id_token_unique');
            $table->uuid('account_id')->nullable();
            $table->foreign('account_id')->references('id')->on('accounts')->onDelete('cascade');
            $table->unique(['account_id', 'token']);
        });

        Schema::table('payment_providers', function (Blueprint $table): void {
            $table->dropColumn('supported_types');
        });
    }
};
