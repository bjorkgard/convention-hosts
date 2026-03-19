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
        Schema::table('sections', function (Blueprint $table) {
            $table->boolean('hearing_loop')->default(false)->after('handicap_friendly');

            $table->dropIndex('idx_sections_accessibility');
            $table->index(['elder_friendly', 'handicap_friendly', 'hearing_loop'], 'idx_sections_accessibility');
            $table->index('hearing_loop', 'idx_sections_hearing_loop');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sections', function (Blueprint $table) {
            $table->dropIndex('idx_sections_hearing_loop');
            $table->dropIndex('idx_sections_accessibility');
            $table->index(['elder_friendly', 'handicap_friendly'], 'idx_sections_accessibility');

            $table->dropColumn('hearing_loop');
        });
    }
};
