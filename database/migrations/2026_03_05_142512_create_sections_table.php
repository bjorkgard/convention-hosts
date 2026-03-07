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
        Schema::create('sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('floor_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->integer('number_of_seats');
            $table->integer('occupancy')->default(0);
            $table->integer('available_seats')->default(0);
            $table->boolean('elder_friendly')->default(false);
            $table->boolean('handicap_friendly')->default(false);
            $table->text('information')->nullable();
            $table->foreignUuid('last_occupancy_updated_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('last_occupancy_updated_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('floor_id', 'idx_sections_floor');
            $table->index('occupancy', 'idx_sections_occupancy');
            $table->index(['elder_friendly', 'handicap_friendly'], 'idx_sections_accessibility');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sections');
    }
};
