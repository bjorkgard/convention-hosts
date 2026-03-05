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
        Schema::create('attendance_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_period_id')->constrained()->onDelete('cascade');
            $table->foreignId('section_id')->constrained()->onDelete('cascade');
            $table->integer('attendance');
            $table->foreignId('reported_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('reported_at');
            $table->timestamps();

            // Unique constraint
            $table->unique(['attendance_period_id', 'section_id']);

            // Indexes
            $table->index('attendance_period_id', 'idx_attendance_reports_period');
            $table->index('section_id', 'idx_attendance_reports_section');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendance_reports');
    }
};
