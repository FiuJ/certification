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
        Schema::create('charging_records', function (Blueprint $table) {
            $table->uuid('charging_records_id')->primary();
            $table->foreignUuid('rental_id');
            $table->decimal('current_battery_percentage', 5, 2);
            $table->unsignedBigInteger('amount');
            $table->string('payment_path', 255);

            $table->foreign('rental_id')
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
        Schema::dropIfExists('charging_records');
    }
};
