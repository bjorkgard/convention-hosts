<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->dropUnique(['floor_url_token']);
            $table->dropColumn('floor_url_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conventions', function (Blueprint $table) {
            $table->string('floor_url_token', 64)->unique()->nullable()->after('other_info');
        });

        // Regenerate tokens for existing conventions
        \Illuminate\Support\Facades\DB::table('conventions')->orderBy('id')->each(function ($convention) {
            \Illuminate\Support\Facades\DB::table('conventions')
                ->where('id', $convention->id)
                ->update(['floor_url_token' => Str::random(64)]);
        });
    }
};
