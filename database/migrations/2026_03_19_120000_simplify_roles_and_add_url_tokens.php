<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function () {
            // Add URL token columns to conventions table
            Schema::table('conventions', function (Blueprint $table) {
                $table->string('floor_url_token', 64)->unique()->nullable()->after('other_info');
                $table->string('section_url_token', 64)->unique()->nullable()->after('floor_url_token');
            });

            // Generate tokens for all existing conventions
            DB::table('conventions')->orderBy('id')->each(function ($convention) {
                DB::table('conventions')
                    ->where('id', $convention->id)
                    ->update([
                        'floor_url_token' => Str::random(64),
                        'section_url_token' => Str::random(64),
                    ]);
            });

            // Rename ConventionUser to Administrator
            DB::table('convention_user_roles')
                ->where('role', 'ConventionUser')
                ->update(['role' => 'Administrator']);

            // Delete FloorUser and SectionUser role entries
            DB::table('convention_user_roles')
                ->whereIn('role', ['FloorUser', 'SectionUser'])
                ->delete();

            // Drop pivot tables
            Schema::dropIfExists('floor_user');
            Schema::dropIfExists('section_user');

            // Remove reported_by column from attendance_reports
            Schema::table('attendance_reports', function (Blueprint $table) {
                $table->dropForeign(['reported_by']);
                $table->dropColumn('reported_by');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::transaction(function () {
            // Re-create floor_user pivot table
            Schema::create('floor_user', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('floor_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('created_at')->nullable();

                $table->unique(['floor_id', 'user_id']);
                $table->index('floor_id', 'idx_floor_user_floor');
                $table->index('user_id', 'idx_floor_user_user');
            });

            // Re-create section_user pivot table
            Schema::create('section_user', function (Blueprint $table) {
                $table->id();
                $table->foreignUuid('section_id')->constrained()->onDelete('cascade');
                $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
                $table->timestamp('created_at')->nullable();

                $table->unique(['section_id', 'user_id']);
                $table->index('section_id', 'idx_section_user_section');
                $table->index('user_id', 'idx_section_user_user');
            });

            // Restore reported_by column on attendance_reports
            Schema::table('attendance_reports', function (Blueprint $table) {
                $table->foreignUuid('reported_by')->nullable()->constrained('users')->onDelete('cascade')->after('attendance');
            });

            // Rename Administrator back to ConventionUser
            DB::table('convention_user_roles')
                ->where('role', 'Administrator')
                ->update(['role' => 'ConventionUser']);

            // Drop URL token columns from conventions
            Schema::table('conventions', function (Blueprint $table) {
                $table->dropUnique(['floor_url_token']);
                $table->dropUnique(['section_url_token']);
                $table->dropColumn(['floor_url_token', 'section_url_token']);
            });
        });
    }
};
