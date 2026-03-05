<?php

use App\Models\AttendancePeriod;
use App\Models\Convention;
use App\Services\AttendanceReportService;

/**
 * Property 32: Maximum Two Reports Per Day
 *
 * For any convention, attempting to start a third attendance report on the same day
 * should be rejected.
 *
 * Validates: Requirements 10.6
 */
it('enforces maximum of 2 attendance reports per day', function () {
    // Run 50 iterations to test the property across different scenarios
    for ($i = 0; $i < 50; $i++) {
        // Arrange: Create a convention
        $convention = Convention::factory()->create();
        $service = new AttendanceReportService;

        $today = now()->toDateString();

        // Act: Start first report (morning)
        // We need to mock the time to ensure we get morning period
        $morningTime = now()->setHour(9); // 9 AM
        \Illuminate\Support\Facades\Date::setTestNow($morningTime);

        $period1 = $service->startReport($convention);

        // Assert: First report should be created successfully
        expect($period1)->toBeInstanceOf(AttendancePeriod::class)
            ->and($period1->convention_id)->toBe($convention->id)
            ->and($period1->date->toDateString())->toBe($today)
            ->and($period1->period)->toBe('morning')
            ->and($period1->locked)->toBeFalse();

        // Act: Start second report (afternoon)
        // Mock afternoon time
        $afternoonTime = now()->setHour(14); // 2 PM
        \Illuminate\Support\Facades\Date::setTestNow($afternoonTime);

        $period2 = $service->startReport($convention);

        // Assert: Second report should be created successfully
        expect($period2)->toBeInstanceOf(AttendancePeriod::class)
            ->and($period2->period)->toBe('afternoon');

        // Verify we now have 2 reports for today
        $reportsToday = AttendancePeriod::where('convention_id', $convention->id)
            ->whereDate('date', $today)
            ->count();
        expect($reportsToday)->toBe(2);

        // Act: Attempt to start a third report
        $exceptionThrown = false;
        try {
            $service->startReport($convention);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            expect($e->getMessage())->toContain('Maximum of 2 attendance reports per day');
        }

        // Assert: Third report should be rejected
        expect($exceptionThrown)->toBeTrue();

        // Verify still only 2 reports for today
        $reportsAfter = AttendancePeriod::where('convention_id', $convention->id)
            ->whereDate('date', $today)
            ->count();
        expect($reportsAfter)->toBe(2);

        // Test edge case: Reports from different days should not interfere
        if ($i % 10 === 0) {
            // Create reports for yesterday
            $yesterday = now()->subDay()->toDateString();
            AttendancePeriod::create([
                'convention_id' => $convention->id,
                'date' => $yesterday,
                'period' => 'morning',
                'locked' => true,
            ]);
            AttendancePeriod::create([
                'convention_id' => $convention->id,
                'date' => $yesterday,
                'period' => 'afternoon',
                'locked' => true,
            ]);

            // Verify yesterday's reports don't affect today's count
            $yesterdayReports = AttendancePeriod::where('convention_id', $convention->id)
                ->whereDate('date', $yesterday)
                ->count();
            expect($yesterdayReports)->toBe(2);

            // Today should still have 2 reports
            $todayReports = AttendancePeriod::where('convention_id', $convention->id)
                ->whereDate('date', $today)
                ->count();
            expect($todayReports)->toBe(2);
        }

        // Cleanup for next iteration
        \Illuminate\Support\Facades\Date::setTestNow(); // Reset time
        $convention->delete();
    }
})->group('property', 'attendance');

/**
 * Property 36: Section User Attendance Update Restriction
 *
 * For any attendance report in an unlocked period, only the user who originally
 * reported the attendance for that section should be able to update it, unless
 * a ConventionUser locks the period.
 *
 * Validates: Requirements 11.5, 11.6
 */
it('restricts attendance updates to original reporter before lock', function () {
    // Run 50 iterations to test the property across different scenarios
    for ($i = 0; $i < 50; $i++) {
        // Arrange: Create convention, section, and users
        $convention = Convention::factory()->create();
        $floor = \App\Models\Floor::factory()->create(['convention_id' => $convention->id]);
        $section = \App\Models\Section::factory()->create(['floor_id' => $floor->id]);

        $originalReporter = \App\Models\User::factory()->create();
        $otherUser = \App\Models\User::factory()->create();

        $service = new AttendanceReportService;

        // Create an unlocked attendance period
        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'morning',
            'locked' => false,
        ]);

        // Act: Original reporter submits attendance
        $initialAttendance = fake()->numberBetween(50, 150);
        $report = $service->reportAttendance($section, $period, $initialAttendance, $originalReporter);

        // Assert: Report should be created successfully
        expect($report)->toBeInstanceOf(\App\Models\AttendanceReport::class)
            ->and($report->attendance)->toBe($initialAttendance)
            ->and($report->reported_by)->toBe($originalReporter->id)
            ->and($report->section_id)->toBe($section->id)
            ->and($report->attendance_period_id)->toBe($period->id);

        // Act: Original reporter updates their own report
        $updatedAttendance = fake()->numberBetween(50, 150);
        $updatedReport = $service->reportAttendance($section, $period, $updatedAttendance, $originalReporter);

        // Assert: Update should succeed
        expect($updatedReport->attendance)->toBe($updatedAttendance)
            ->and($updatedReport->reported_by)->toBe($originalReporter->id)
            ->and($updatedReport->id)->toBe($report->id); // Same report, updated

        // Act: Different user attempts to update the report
        $exceptionThrown = false;
        try {
            $service->reportAttendance($section, $period, 999, $otherUser);
        } catch (\Exception $e) {
            $exceptionThrown = true;
            expect($e->getMessage())->toContain('Only the original reporter can update');
        }

        // Assert: Update by different user should be rejected
        expect($exceptionThrown)->toBeTrue();

        // Verify the attendance value hasn't changed
        $report->refresh();
        expect($report->attendance)->toBe($updatedAttendance)
            ->and($report->reported_by)->toBe($originalReporter->id);

        // Test locked period restriction
        if ($i % 10 === 0) {
            // Lock the period
            $service->stopReport($period);
            $period->refresh();
            expect($period->locked)->toBeTrue();

            // Act: Original reporter attempts to update after lock
            $lockedExceptionThrown = false;
            try {
                $service->reportAttendance($section, $period, 888, $originalReporter);
            } catch (\Exception $e) {
                $lockedExceptionThrown = true;
                expect($e->getMessage())->toContain('locked and cannot be updated');
            }

            // Assert: Update should be rejected even for original reporter
            expect($lockedExceptionThrown)->toBeTrue();

            // Verify the attendance value hasn't changed
            $report->refresh();
            expect($report->attendance)->toBe($updatedAttendance);
        }

        // Cleanup for next iteration
        $convention->delete();
        $originalReporter->delete();
        $otherUser->delete();
    }
})->group('property', 'attendance');
