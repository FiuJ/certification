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
        Schema::create('rentals', function (Blueprint $table) {
            $table->uuid('rental_id')->primary();
            $table->foreignUuid('vehicle_id');
            $table->foreignUuid('garage_id');
            $table->foreignUuid('original_rental_id')->nullable();
            $table->string('rental_type', 10);
            $table->date('start_date');
            $table->unsignedSmallInteger('duration')->nullable();
            $table->unsignedBigInteger('rental_amount');
            $table->string('payment_path', 255);

            $table->foreign('vehicle_id')
                ->references('vehicle_id')
                ->on('vehicles')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('garage_id')
                ->references('garage_id')
                ->on('garages')
                ->restrictOnDelete()
                ->restrictOnUpdate();

            $table->foreign('original_rental_id')
                ->references('rental_id')
                ->on('rentals')
                ->restrictOnDelete()
                ->restrictOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rentals');
    }
};
