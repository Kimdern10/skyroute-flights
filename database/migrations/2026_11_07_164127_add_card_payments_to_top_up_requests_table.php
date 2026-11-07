<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('top_up_requests', function (Blueprint $table) {
            $table->string('method', 10)->default('bank')->after('user_id');
            $table->unsignedBigInteger('charged_amount')->nullable()->after('amount_cents');
            $table->string('charged_currency', 3)->nullable()->after('charged_amount');
            $table->index(['method', 'payment_reference']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('top_up_requests', function (Blueprint $table) {
            $table->dropIndex(['method', 'payment_reference']);
            $table->dropColumn(['method', 'charged_amount', 'charged_currency']);
        });
    }
};
