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
        Schema::create('attendance_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('convention_id')->constrained()->onDelete('cascade');
            $table->date('date');
            $table->string('period', 20);
            $table->boolean('locked')->default(false);
            $table->timestamps();

            // Unique constraint
            $table->unique(['convention_id', 'date', 'period']);

            // Indexes
            $table->index('convention_id', 'idx_attendance_periods_convention');
            $table->index(['date', 'period'], 'idx_attendance_periods_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_periods');
    }
};
