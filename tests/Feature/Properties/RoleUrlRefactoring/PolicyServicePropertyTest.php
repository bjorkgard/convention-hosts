<?php

use App\Actions\CreateConventionAction;
use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use App\Services\AttendanceReportService;
use Illuminate\Support\Facades\DB;

/*
|--------------------------------------------------------------------------
| Property 1: Role system invariant
|--------------------------------------------------------------------------
| Validates: Requirements 1.1, 1.5, 1.6
*/

it('only allows Owner and Administrator roles in convention_user_roles', function () {
    $allowedRoles = ['Owner', 'Administrator'];

    for ($i = 0; $i < 10; $i++) {
        $convention = Convention::factory()->create();
        $user = User::factory()->create();

        DB::table('convention_user')->insert([
            'convention_id' => $convention->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        $rolesToAssign = fake()->randomElements($allowedRoles, fake()->numberBetween(1, 2));
        foreach ($rolesToAssign as $role) {
            DB::table('convention_user_roles')->insert([
                'convention_id' => $convention->id,
                'user_id' => $user->id,
                'role' => $role,
                'created_at' => now(),
            ]);
        }

        $actualRoles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $user->id)
            ->pluck('role')
            ->toArray();

        foreach ($actualRoles as $role) {
            expect(in_array($role, $allowedRoles))->toBeTrue();
        }
    }
})->group('property', 'role-url-refactoring');

it('rejects invalid roles via StoreUserRequest validation', function () {
    $convention = Convention::factory()->create();
    $owner = User::factory()->create();

    DB::table('convention_user')->insert([
        'convention_id' => $convention->id,
        'user_id' => $owner->id,
        'created_at' => now(),
    ]);
    DB::table('convention_user_roles')->insert([
        ['convention_id' => $convention->id, 'user_id' => $owner->id, 'role' => 'Owner', 'created_at' => now()],
        ['convention_id' => $convention->id, 'user_id' => $owner->id, 'role' => 'Administrator', 'created_at' => now()],
    ]);

    $invalidRoles = ['FloorUser', 'SectionUser', 'ConventionUser', 'Admin', 'SuperUser'];

    foreach ($invalidRoles as $invalidRole) {
        $response = $this->actingAs($owner)->post(
            route('users.store', $convention),
            [
                'first_name' => 'Test',
                'last_name' => 'User',
                'email' => fake()->unique()->safeEmail(),
                'mobile' => fake()->phoneNumber(),
                'roles' => [$invalidRole],
            ]
        );

        $response->assertSessionHasErrors('roles.0');
    }
})->group('property', 'role-url-refactoring');

/*
|--------------------------------------------------------------------------
| Property 2: Owner role assignment on convention creation
|--------------------------------------------------------------------------
| Validates: Requirements 1.2
*/

it('assigns Owner role to convention creator', function () {
    $action = app(CreateConventionAction::class);

    for ($i = 0; $i < 10; $i++) {
        $creator = User::factory()->create();

        $convention = $action->execute([
            'name' => fake()->company().' Convention',
            'city' => fake()->city(),
            'country' => fake()->country(),
            'start_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'end_date' => now()->addDays(fake()->numberBetween(31, 60))->toDateString(),
        ], $creator);

        $roles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $creator->id)
            ->pluck('role')
            ->toArray();

        expect($roles)->toContain('Owner');
    }
})->group('property', 'role-url-refactoring');

it('assigns both Owner and Administrator roles to convention creator', function () {
    $action = app(CreateConventionAction::class);

    for ($i = 0; $i < 10; $i++) {
        $creator = User::factory()->create();

        $convention = $action->execute([
            'name' => fake()->company().' Convention',
            'city' => fake()->city(),
            'country' => fake()->country(),
            'start_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'end_date' => now()->addDays(fake()->numberBetween(31, 60))->toDateString(),
        ], $creator);

        $roles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $creator->id)
            ->pluck('role')
            ->toArray();

        expect($roles)->toContain('Owner')
            ->and($roles)->toContain('Administrator');
    }
})->group('property', 'role-url-refactoring');

/*
|--------------------------------------------------------------------------
| Property 10: Attendance report open access
|--------------------------------------------------------------------------
| Validates: Requirements 8.1, 8.2, 8.3, 8.4, 8.5
*/

it('allows any user with permissions to create attendance reports', function () {
    $service = app(AttendanceReportService::class);

    for ($i = 0; $i < 5; $i++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        $section = Section::factory()->create(['floor_id' => $floor->id]);
        $period = AttendancePeriod::factory()->create([
            'convention_id' => $convention->id,
            'locked' => false,
        ]);

        $report = $service->reportAttendance($section, $period, fake()->numberBetween(10, 100), null);

        expect($report)->toBeInstanceOf(AttendanceReport::class)
            ->and($report->section_id)->toBe($section->id)
            ->and($report->attendance_period_id)->toBe($period->id);
    }
})->group('property', 'role-url-refactoring');

it('allows any user to update attendance reports regardless of original reporter', function () {
    $service = app(AttendanceReportService::class);

    for ($i = 0; $i < 5; $i++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        $section = Section::factory()->create(['floor_id' => $floor->id]);
        $period = AttendancePeriod::factory()->create([
            'convention_id' => $convention->id,
            'locked' => false,
        ]);

        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $report1 = $service->reportAttendance($section, $period, fake()->numberBetween(10, 50), $user1);
        $newAttendance = fake()->numberBetween(51, 100);
        $report2 = $service->reportAttendance($section, $period, $newAttendance, $user2);

        expect($report2->id)->toBe($report1->id)
            ->and($report2->attendance)->toBe($newAttendance);
    }
})->group('property', 'role-url-refactoring');

it('allows URL sessions to update reports created by authenticated users', function () {
    $service = app(AttendanceReportService::class);

    for ($i = 0; $i < 5; $i++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        $section = Section::factory()->create(['floor_id' => $floor->id]);
        $period = AttendancePeriod::factory()->create([
            'convention_id' => $convention->id,
            'locked' => false,
        ]);

        $authenticatedUser = User::factory()->create();
        $report1 = $service->reportAttendance($section, $period, fake()->numberBetween(10, 50), $authenticatedUser);

        $newAttendance = fake()->numberBetween(51, 100);
        $report2 = $service->reportAttendance($section, $period, $newAttendance, null);

        expect($report2->id)->toBe($report1->id)
            ->and($report2->attendance)->toBe($newAttendance);
    }
})->group('property', 'role-url-refactoring');

it('prevents updates to locked attendance periods', function () {
    $service = app(AttendanceReportService::class);

    $convention = Convention::factory()->create();
    $floor = Floor::factory()->create(['convention_id' => $convention->id]);
    $section = Section::factory()->create(['floor_id' => $floor->id]);
    $period = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'locked' => true,
    ]);

    expect(fn () => $service->reportAttendance($section, $period, 50, null))
        ->toThrow(\Exception::class, 'This attendance period is locked and cannot be updated.');
})->group('property', 'role-url-refactoring');
