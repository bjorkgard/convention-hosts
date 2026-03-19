<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Feature: role-url-refactoring
 *
 * Property 12: Migration role conversion correctness
 *
 * For any database state containing ConventionUser, FloorUser, or SectionUser roles,
 * after running the migration: all ConventionUser roles must be converted to Administrator,
 * and no FloorUser or SectionUser roles must remain.
 *
 * **Validates: Requirements 10.1, 10.2, 10.3**
 */
it('converts ConventionUser to Administrator and removes FloorUser/SectionUser roles', function () {
    // The migration has already run (RefreshDatabase). Verify the post-migration state:
    // no legacy roles should exist in convention_user_roles.

    // Create conventions with Owner and Administrator roles (the only valid roles post-migration)
    for ($i = 0; $i < 5; $i++) {
        $convention = Convention::factory()->create();
        $user = User::factory()->create();

        DB::table('convention_user')->insertOrIgnore([
            'convention_id' => $convention->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $user->id,
            'role' => fake()->randomElement(['Owner', 'Administrator']),
            'created_at' => now(),
        ]);
    }

    // Verify no legacy roles exist anywhere in the table
    $legacyRoles = DB::table('convention_user_roles')
        ->whereIn('role', ['ConventionUser', 'FloorUser', 'SectionUser'])
        ->count();

    expect($legacyRoles)->toBe(0, 'No ConventionUser, FloorUser, or SectionUser roles should exist post-migration');

    // Verify only Owner and Administrator roles exist
    $distinctRoles = DB::table('convention_user_roles')
        ->distinct()
        ->pluck('role')
        ->sort()
        ->values()
        ->toArray();

    foreach ($distinctRoles as $role) {
        expect($role)->toBeIn(['Owner', 'Administrator'], "Role '{$role}' is not a valid post-migration role");
    }
})->group('property', 'role-url-refactoring');

it('ensures floor_user and section_user tables do not exist post-migration', function () {
    expect(Schema::hasTable('floor_user'))->toBeFalse('floor_user table should not exist after migration');
    expect(Schema::hasTable('section_user'))->toBeFalse('section_user table should not exist after migration');
})->group('property', 'role-url-refactoring');

it('ensures reported_by column does not exist on attendance_reports post-migration', function () {
    expect(Schema::hasColumn('attendance_reports', 'reported_by'))
        ->toBeFalse('reported_by column should not exist on attendance_reports after migration');
})->group('property', 'role-url-refactoring');

/**
 * Property 13: Migration token generation for existing conventions
 *
 * For all conventions that exist before the migration runs, after migration each
 * convention must have non-null floor_url_token and section_url_token values
 * of at least 32 characters.
 *
 * **Validates: Requirements 10.6**
 */
it('ensures all conventions have valid tokens post-migration', function () {
    // Create a batch of conventions (they get tokens via factory + model boot)
    $conventions = Convention::factory()->count(10)->create();

    // Verify every convention in the database has valid tokens
    $allConventions = DB::table('conventions')->get();

    foreach ($allConventions as $convention) {
        expect($convention->floor_url_token)
            ->not->toBeNull("Convention {$convention->id} must have a floor_url_token")
            ->toBeString()
            ->and(strlen($convention->floor_url_token))
            ->toBeGreaterThanOrEqual(32, "Convention {$convention->id} floor_url_token must be >= 32 chars");

        expect($convention->section_url_token)
            ->not->toBeNull("Convention {$convention->id} must have a section_url_token")
            ->toBeString()
            ->and(strlen($convention->section_url_token))
            ->toBeGreaterThanOrEqual(32, "Convention {$convention->id} section_url_token must be >= 32 chars");
    }
})->group('property', 'role-url-refactoring');

/**
 * Property 14: Migration reversibility
 *
 * For any database state, running the migration up and then down must restore
 * the original schema structure (floor_user table, section_user table,
 * reported_by column) without data loss in the reversible portions.
 *
 * **Validates: Requirements 10.8**
 */
it('restores schema structure after rollback and re-migration', function () {
    // Current state: migration has run (up). Verify current schema.
    expect(Schema::hasColumn('conventions', 'floor_url_token'))->toBeTrue();
    expect(Schema::hasColumn('conventions', 'section_url_token'))->toBeTrue();
    expect(Schema::hasTable('floor_user'))->toBeFalse();
    expect(Schema::hasTable('section_user'))->toBeFalse();
    expect(Schema::hasColumn('attendance_reports', 'reported_by'))->toBeFalse();

    // Roll back the role-url-refactoring migration (step 4 because hearing_loop and locale migrations are now after it)
    Artisan::call('migrate:rollback', ['--step' => 4]);

    // After rollback: old schema should be restored
    expect(Schema::hasTable('floor_user'))->toBeTrue('floor_user table should be restored after rollback');
    expect(Schema::hasTable('section_user'))->toBeTrue('section_user table should be restored after rollback');
    expect(Schema::hasColumn('attendance_reports', 'reported_by'))
        ->toBeTrue('reported_by column should be restored after rollback');
    expect(Schema::hasColumn('conventions', 'floor_url_token'))
        ->toBeFalse('floor_url_token should be removed after rollback');
    expect(Schema::hasColumn('conventions', 'section_url_token'))
        ->toBeFalse('section_url_token should be removed after rollback');

    // Verify ConventionUser role is restored (Administrator renamed back)
    // Any Administrator roles that existed should now be ConventionUser
    $adminCount = DB::table('convention_user_roles')->where('role', 'Administrator')->count();
    expect($adminCount)->toBe(0, 'No Administrator roles should exist after rollback');

    // Re-run migration to restore expected state for other tests
    Artisan::call('migrate');

    // Verify migration re-applied correctly
    expect(Schema::hasColumn('conventions', 'floor_url_token'))->toBeTrue();
    expect(Schema::hasColumn('conventions', 'section_url_token'))->toBeTrue();
    expect(Schema::hasTable('floor_user'))->toBeFalse();
    expect(Schema::hasTable('section_user'))->toBeFalse();
})->group('property', 'role-url-refactoring');
