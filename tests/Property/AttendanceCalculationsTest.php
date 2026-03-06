<?php

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;

/**
 * Property 33: Attendance Total Calculation
 *
 * For any attendance period with N reports, the totalAttendance() must equal
 * the sum of all individual report attendance values.
 *
 * Validates: Requirements 10.7
 */
it('calculates total attendance as sum of all section reports', function () {
    for ($i = 0; $i < 3; $i++) {
        // Arrange: Create convention with floors and sections
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);

        $sectionCount = fake()->numberBetween(3, 10);
        $sections = Section::factory()->count($sectionCount)->create([
            'floor_id' => $floor->id,
        ]);

        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'morning',
            'locked' => false,
        ]);

        $reporter = User::factory()->create();

        // Act: Submit random attendance for a random subset of sections
        $reportCount = fake()->numberBetween(1, $sectionCount);
        $reportedSections = $sections->random($reportCount);
        $expectedTotal = 0;

        foreach ($reportedSections as $section) {
            $attendance = fake()->numberBetween(0, 500);
            $expectedTotal += $attendance;

            AttendanceReport::create([
                'attendance_period_id' => $period->id,
                'section_id' => $section->id,
                'attendance' => $attendance,
                'reported_by' => $reporter->id,
                'reported_at' => now(),
            ]);
        }

        // Assert: totalAttendance equals the sum of all reports
        expect($period->totalAttendance())->toBe($expectedTotal);

        // Property: total is always non-negative
        expect($period->totalAttendance())->toBeGreaterThanOrEqual(0);

        // Cleanup
        $convention->delete();
        $reporter->delete();
    }
})->group('property', 'attendance');

/**
 * Property 33 (Edge): Empty period has zero total attendance.
 */
it('returns zero total attendance for period with no reports', function () {
    $convention = Convention::factory()->create();
    $period = AttendancePeriod::create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => false,
    ]);

    expect($period->totalAttendance())->toBe(0)
        ->and($period->reportedSectionsCount())->toBe(0);

    $convention->delete();
})->group('property', 'attendance');

/**
 * Property 34: Reported Sections Counter
 *
 * For any attendance period, reportedSectionsCount() must equal the number
 * of distinct sections that have submitted attendance reports for that period.
 *
 * Validates: Requirements 10.8
 */
it('counts reported sections accurately', function () {
    for ($i = 0; $i < 3; $i++) {
        // Arrange
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);

        $totalSections = fake()->numberBetween(5, 15);
        $sections = Section::factory()->count($totalSections)->create([
            'floor_id' => $floor->id,
        ]);

        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => fake()->randomElement(['morning', 'afternoon']),
            'locked' => false,
        ]);

        $reporter = User::factory()->create();

        // Act: Report attendance for a random subset
        $reportedCount = fake()->numberBetween(0, $totalSections);
        $reportedSections = $reportedCount > 0
            ? $sections->random($reportedCount)
            : collect();

        foreach ($reportedSections as $section) {
            AttendanceReport::create([
                'attendance_period_id' => $period->id,
                'section_id' => $section->id,
                'attendance' => fake()->numberBetween(10, 300),
                'reported_by' => $reporter->id,
                'reported_at' => now(),
            ]);
        }

        // Assert: reportedSectionsCount matches actual reported count
        expect($period->reportedSectionsCount())->toBe($reportedCount);

        // Property: reported count never exceeds total sections
        expect($period->reportedSectionsCount())->toBeLessThanOrEqual($totalSections);

        // Property: reported count is always non-negative
        expect($period->reportedSectionsCount())->toBeGreaterThanOrEqual(0);

        // Cleanup
        $convention->delete();
        $reporter->delete();
    }
})->group('property', 'attendance');
