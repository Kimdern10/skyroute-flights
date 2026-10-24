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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('reference', 6)->unique();
            $table->string('status', 20)->index();
            $table->string('offer_id');
            $table->string('provider_order_id')->nullable();
            $table->string('airline_reference')->nullable();
            $table->string('carrier_code', 3);
            $table->string('carrier_name');
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->dateTime('departs_at');
            $table->dateTime('arrives_at');
            $table->string('cabin', 30)->default('economy');
            $table->unsignedTinyInteger('passengers_count');
            $table->json('segments');
            $table->unsignedBigInteger('fare_cents');
            $table->bigInteger('markup_cents');
            $table->unsignedBigInteger('total_cents');
            $table->string('provider_amount', 20);
            $table->string('provider_currency', 3);
            $table->string('contact_email');
            $table->string('contact_phone', 30);
            $table->text('failure_reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
