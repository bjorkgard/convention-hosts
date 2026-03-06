<?php

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\User;
use App\Services\AttendanceReportService;
use Illuminate\Support\Carbon;
use Tests\Helpers\ConventionTestHelper;

/**
 * Property 30: Two Attendance Periods Per Day
 *
 * For any convention day within the start_date to end_date range, exactly two
 * attendance periods should exist: one for morning and one for afternoon.
 * The unique constraint on (convention_id, date, period) prevents duplicates.
 *
 * **Validates: Requirements 10.1, 10.2**
 */
it('allows exactly two periods (morning and afternoon) per convention day', function () {
    for ($i = 0; $i < 10; $i++) {
        $daysSpan = fake()->numberBetween(1, 7);
        $startDate = Carbon::today()->addDays(fake()->numberBetween(1, 30));
        $endDate = $startDate->copy()->addDays($daysSpan);

        $convention = Convention::factory()->create([
            'start_date' => $startDate->toDateString(),
            'end_date' => $endDate->toDateString(),
        ]);

        // Pick a random date within the convention range
        $randomOffset = fake()->numberBetween(0, $daysSpan);
        $testDate = $startDate->copy()->addDays($randomOffset)->toDateString();

        // Create morning period
        $morning = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $testDate,
            'period' => 'morning',
            'locked' => false,
        ]);

        // Create afternoon period
        $afternoon = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $testDate,
            'period' => 'afternoon',
            'locked' => false,
        ]);

        expect($morning->exists)->toBeTrue("Iteration {$i}: morning period should be created");
        expect($afternoon->exists)->toBeTrue("Iteration {$i}: afternoon period should be created");

        // Verify exactly 2 periods exist for this day
        $periodsForDay = AttendancePeriod::where('convention_id', $convention->id)
            ->whereDate('date', $testDate)
            ->get();

        expect($periodsForDay)->toHaveCount(2,
            "Iteration {$i}: exactly 2 periods should exist per day"
        );

        $periodTypes = $periodsForDay->pluck('period')->sort()->values()->all();
        expect($periodTypes)->toBe(['afternoon', 'morning'],
            "Iteration {$i}: periods should be morning and afternoon"
        );
    }
})->group('property', 'attendance');

it('prevents duplicate periods for the same convention, date, and period type', function () {
    for ($i = 0; $i < 10; $i++) {
        $convention = Convention::factory()->create();
        $testDate = Carbon::today()->addDays(fake()->numberBetween(1, 30))->toDateString();
        $period = fake()->randomElement(['morning', 'afternoon']);

        // Create first period
        AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $testDate,
            'period' => $period,
            'locked' => false,
        ]);

        // Attempt to create duplicate should throw
        $duplicateThrew = false;
        try {
            AttendancePeriod::create([
                'convention_id' => $convention->id,
                'date' => $testDate,
                'period' => $period,
                'locked' => false,
            ]);
        } catch (\Throwable $e) {
            $duplicateThrew = true;
        }

        expect($duplicateThrew)->toBeTrue(
            "Iteration {$i}: duplicate period ({$period}) on same date should be rejected by unique constraint"
        );
    }
})->group('property', 'attendance');

it('allows same period type on different dates for the same convention', function () {
    for ($i = 0; $i < 10; $i++) {
        $convention = Convention::factory()->create([
            'start_date' => Carbon::today()->toDateString(),
            'end_date' => Carbon::today()->addDays(30)->toDateString(),
        ]);

        $dateA = Carbon::today()->addDays($i * 2)->toDateString();
        $dateB = Carbon::today()->addDays($i * 2 + 1)->toDateString();
        $period = fake()->randomElement(['morning', 'afternoon']);

        $periodA = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $dateA,
            'period' => $period,
            'locked' => false,
        ]);

        $periodB = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $dateB,
            'period' => $period,
            'locked' => false,
        ]);

        expect($periodA->id)->not->toBe($periodB->id,
            "Iteration {$i}: same period type on different dates should create separate records"
        );
        expect($periodA->period)->toBe($periodB->period);
        expect($periodA->date->toDateString())->not->toBe($periodB->date->toDateString());
    }
})->group('property', 'attendance');

/**
 * Property 31: Attendance Report Data Storage
 *
 * For any attendance report submitted, the system should store the attendance
 * value, reported_by user ID, reported_at timestamp, and the period's locked status.
 *
 * **Validates: Requirements 10.4**
 */
it('stores attendance value, reported_by, and reported_at correctly for each report', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 1,
    ]);
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $service = new AttendanceReportService;

    for ($i = 0; $i < 15; $i++) {
        $user = User::factory()->create();
        $attendanceValue = fake()->numberBetween(0, 500);
        $date = Carbon::today()->addDays($i)->toDateString();

        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $date,
            'period' => 'morning',
            'locked' => false,
        ]);

        $beforeReport = now()->subSecond();

        $report = $service->reportAttendance($section, $period, $attendanceValue, $user);

        // Property: attendance value is stored correctly
        expect($report->attendance)->toBe($attendanceValue,
            "Iteration {$i}: attendance value should be {$attendanceValue}"
        );

        // Property: reported_by stores the user ID
        expect($report->reported_by)->toBe($user->id,
            "Iteration {$i}: reported_by should be the reporting user's ID"
        );

        // Property: reported_at is a valid timestamp
        expect($report->reported_at)->not->toBeNull(
            "Iteration {$i}: reported_at should be set"
        );
        expect($report->reported_at->gte($beforeReport))->toBeTrue(
            "Iteration {$i}: reported_at should be at or after the report time"
        );

        // Verify persistence in database
        $this->assertDatabaseHas('attendance_reports', [
            'id' => $report->id,
            'attendance_period_id' => $period->id,
            'section_id' => $section->id,
            'attendance' => $attendanceValue,
            'reported_by' => $user->id,
        ]);

        // Verify the period's locked status is accessible
        expect($period->locked)->toBeFalse(
            "Iteration {$i}: newly created period should not be locked"
        );
    }
})->group('property', 'attendance');

it('enforces unique constraint on attendance_period_id and section_id', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 3,
    ]);
    $convention = $structure['convention'];
    $service = new AttendanceReportService;

    for ($i = 0; $i < 10; $i++) {
        $section = $structure['sections']->random();
        $user = User::factory()->create();
        $date = Carbon::today()->addDays($i)->toDateString();

        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $date,
            'period' => fake()->randomElement(['morning', 'afternoon']),
            'locked' => false,
        ]);

        // First report should succeed
        $service->reportAttendance($section, $period, fake()->numberBetween(0, 200), $user);

        // Direct duplicate insert should fail due to unique constraint
        $duplicateThrew = false;
        try {
            AttendanceReport::create([
                'attendance_period_id' => $period->id,
                'section_id' => $section->id,
                'attendance' => fake()->numberBetween(0, 200),
                'reported_by' => $user->id,
                'reported_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $duplicateThrew = true;
        }

        expect($duplicateThrew)->toBeTrue(
            "Iteration {$i}: duplicate report for same period+section should be rejected"
        );
    }
})->group('property', 'attendance');

/**
 * Property 35: Attendance Period Locking
 *
 * For any attendance period, when stopped, the locked field should be set to
 * true permanently, preventing any further updates.
 *
 * **Validates: Requirements 11.3**
 */
it('sets locked to true when stopReport is called', function () {
    $convention = Convention::factory()->create();
    $service = new AttendanceReportService;

    for ($i = 0; $i < 15; $i++) {
        $date = Carbon::today()->addDays($i)->toDateString();
        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $date,
            'period' => fake()->randomElement(['morning', 'afternoon']),
            'locked' => false,
        ]);

        expect($period->locked)->toBeFalse(
            "Iteration {$i}: period should start unlocked"
        );
        expect($period->isActive())->toBeTrue(
            "Iteration {$i}: unlocked period should be active"
        );

        $service->stopReport($period);
        $period->refresh();

        expect($period->locked)->toBeTrue(
            "Iteration {$i}: period should be locked after stopReport"
        );
        expect($period->isActive())->toBeFalse(
            "Iteration {$i}: locked period should not be active"
        );

        // Verify persistence
        $this->assertDatabaseHas('attendance_periods', [
            'id' => $period->id,
            'locked' => true,
        ]);
    }
})->group('property', 'attendance');

it('prevents attendance updates on locked periods', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 1,
    ]);
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $service = new AttendanceReportService;

    for ($i = 0; $i < 10; $i++) {
        $user = User::factory()->create();
        $date = Carbon::today()->addDays($i)->toDateString();

        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $date,
            'period' => fake()->randomElement(['morning', 'afternoon']),
            'locked' => false,
        ]);

        // Lock the period
        $service->stopReport($period);
        $period->refresh();

        // Attempt to report attendance on locked period should throw
        $threw = false;
        try {
            $service->reportAttendance($section, $period, fake()->numberBetween(0, 200), $user);
        } catch (\Exception $e) {
            $threw = true;
            expect($e->getMessage())->toContain('locked');
        }

        expect($threw)->toBeTrue(
            "Iteration {$i}: reporting attendance on a locked period should throw an exception"
        );
    }
})->group('property', 'attendance');

it('keeps period locked permanently after stopReport', function () {
    $convention = Convention::factory()->create();
    $service = new AttendanceReportService;

    for ($i = 0; $i < 10; $i++) {
        $date = Carbon::today()->addDays($i)->toDateString();
        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => $date,
            'period' => fake()->randomElement(['morning', 'afternoon']),
            'locked' => false,
        ]);

        // Lock it
        $service->stopReport($period);

        // Refresh multiple times to verify persistence
        for ($j = 0; $j < 3; $j++) {
            $reloaded = AttendancePeriod::find($period->id);
            expect($reloaded->locked)->toBeTrue(
                "Iteration {$i}, reload {$j}: period should remain locked"
            );
        }
    }
})->group('property', 'attendance');
