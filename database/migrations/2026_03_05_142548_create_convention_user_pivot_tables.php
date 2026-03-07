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
        // Convention-User pivot table
        Schema::create('convention_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('convention_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->nullable();

            // Unique constraint
            $table->unique(['convention_id', 'user_id']);

            // Indexes
            $table->index('convention_id', 'idx_convention_user_convention');
            $table->index('user_id', 'idx_convention_user_user');
        });

        // Convention-User-Roles pivot table
        Schema::create('convention_user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('convention_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->string('role', 50);
            $table->timestamp('created_at')->nullable();

            // Unique constraint
            $table->unique(['convention_id', 'user_id', 'role']);

            // Index
            $table->index(['convention_id', 'user_id'], 'idx_convention_user_roles_lookup');
        });

        // Floor-User pivot table
        Schema::create('floor_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('floor_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->nullable();

            // Unique constraint
            $table->unique(['floor_id', 'user_id']);

            // Indexes
            $table->index('floor_id', 'idx_floor_user_floor');
            $table->index('user_id', 'idx_floor_user_user');
        });

        // Section-User pivot table
        Schema::create('section_user', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('section_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('created_at')->nullable();

            // Unique constraint
            $table->unique(['section_id', 'user_id']);

            // Indexes
            $table->index('section_id', 'idx_section_user_section');
            $table->index('user_id', 'idx_section_user_user');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_user');
        Schema::dropIfExists('floor_user');
        Schema::dropIfExists('convention_user_roles');
        Schema::dropIfExists('convention_user');
    }
};
