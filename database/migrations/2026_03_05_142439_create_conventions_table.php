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
        Schema::create('conventions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('city');
            $table->string('country');
            $table->text('address')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->text('other_info')->nullable();
            $table->timestamps();

            // Indexes for location and dates
            $table->index(['city', 'country'], 'idx_conventions_location');
            $table->index(['start_date', 'end_date'], 'idx_conventions_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conventions');
    }
};
