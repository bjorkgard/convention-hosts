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
            // Split name into first_name and last_name
            $table->string('first_name')->after('id')->nullable();
            $table->string('last_name')->after('first_name')->nullable();

            // Add mobile and email_confirmed fields
            $table->string('mobile')->after('email')->nullable();
            $table->boolean('email_confirmed')->default(false)->after('email_verified_at');
        });

        // Migrate existing name data to first_name/last_name
        DB::table('users')->get()->each(function ($user) {
            $nameParts = explode(' ', $user->name, 2);
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'first_name' => $nameParts[0] ?? '',
                    'last_name' => $nameParts[1] ?? '',
                ]);
        });

        // Make first_name and last_name required and drop name column
        Schema::table('users', function (Blueprint $table) {
            $table->string('first_name')->nullable(false)->change();
            $table->string('last_name')->nullable(false)->change();
            $table->string('mobile')->nullable(false)->change();
            $table->dropColumn('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Recreate name column
            $table->string('name')->after('id');
        });

        // Migrate first_name/last_name back to name
        DB::table('users')->get()->each(function ($user) {
            DB::table('users')
                ->where('id', $user->id)
                ->update([
                    'name' => trim($user->first_name.' '.$user->last_name),
                ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['first_name', 'last_name', 'mobile', 'email_confirmed']);
        });
    }
};
