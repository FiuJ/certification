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
        Schema::create('garages', function (Blueprint $table) {
            $table->uuid('garage_id')->primary();
            $table->string('garage_type', 20);
            $table->char('garage_code', 4)->unique();
            $table->unsignedBigInteger('daily_rate');
            $table->unsignedBigInteger('electricity_rate_per_kwh')
                ->nullable();
            $table->string('status', 20)->default('available');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('garages');
    }
};
