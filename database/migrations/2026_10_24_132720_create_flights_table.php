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
        Schema::create('flights', function (Blueprint $table) {
            $table->id();
            $table->string('carrier_code', 3);
            $table->string('carrier_name');
            $table->string('flight_number', 8);
            $table->string('origin', 3);
            $table->string('destination', 3);
            $table->dateTime('departs_at');
            $table->dateTime('arrives_at');
            $table->unsignedSmallInteger('seats_total')->nullable();
            $table->unsignedSmallInteger('seats_booked')->default(0);
            $table->unsignedInteger('price_override_cents')->nullable();
            $table->timestamps();

            $table->unique(['carrier_code', 'flight_number', 'departs_at']);
            $table->index('departs_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flights');
    }
};
