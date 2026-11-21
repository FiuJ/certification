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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('vehicle_id')->primary();
            $table->foreignUuid('user_id');
            $table->string('type', 20);
            $table->string('license_plate', 20)
                ->nullable()
                ->unique();
            $table->string('brand', 100);
            $table->string('model', 100);
            $table->decimal('battery_capacity', 10, 2)->nullable();

            $table->foreign('user_id')
                ->references('id')
                ->on('users')
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
        Schema::dropIfExists('vehicles');
    }
};
