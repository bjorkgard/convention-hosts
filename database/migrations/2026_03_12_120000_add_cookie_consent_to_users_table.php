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
        Schema::table('users', function (Blueprint $table) {
            $table->string('consent_state')->nullable()->after('remember_token');
            $table->unsignedInteger('consent_version')->nullable()->after('consent_state');
            $table->timestamp('consent_decided_at')->nullable()->after('consent_version');
            $table->timestamp('consent_updated_at')->nullable()->after('consent_decided_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'consent_state',
                'consent_version',
                'consent_decided_at',
                'consent_updated_at',
            ]);
        });
    }
};
