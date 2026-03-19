<?php

use App\Models\AttendancePeriod;
use App\Models\Convention;
use App\Services\AttendanceReportService;
use Illuminate\Support\Facades\App;

/**
 * Property 32: Maximum Two Reports Per Day
 *
 * For any convention, attempting to start a third attendance report on the same day
 * should be rejected.
 *
 * Validates: Requirements 10.6
 */
it('enforces maximum of 2 attendance reports per day', function () {
    App::setLocale('en');
    for ($i = 0; $i < 3; $i++) {
        $convention = Convention::factory()->create();
        $service = new AttendanceReportService;

        $today = now()->toDateString();

        $morningTime = now()->setHour(9);
        \Illuminate\Support\Facades\Date::setTestNow($morningTime);

        $period1 = $service->startReport($convention);

        expect($period1)->toBeInstanceOf(AttendancePeriod::class)
            ->and($period1->convention_id)->toBe($convention->id)
            ->and($period1->date->toDateString())->toBe($today)
            ->and($period1->period)->toBe('morning')
            ->and($period1->locked)->toBeFalse();

        $afternoonTime = now()->setHour(14);
        \Illuminate\Support\Facades\Date::setTestNow($afternoonTime);

        $period2 = $service->startReport($convention);

        expect($period2)->toBeInstanceOf(AttendancePeriod::class)
            ->and($period2->period)->toBe('afternoon');

        $reportsToday = AttendancePeriod::where('convention_id', $convention->id)
            ->whereDate('date', $today)
            ->count();
        expect($reportsToday)->toBe(2);

        expect(fn () => $service->startReport($convention))
            ->toThrow(\Exception::class, 'Maximum of 2 attendance reports per day');

        $reportsAfter = AttendancePeriod::where('convention_id', $convention->id)
            ->whereDate('date', $today)
            ->count();
        expect($reportsAfter)->toBe(2);

        // Test edge case: Reports from different days should not interfere
        if ($i === 0) {
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

            expect(AttendancePeriod::where('convention_id', $convention->id)
                ->whereDate('date', $yesterday)->count())->toBe(2);
            expect(AttendancePeriod::where('convention_id', $convention->id)
                ->whereDate('date', $today)->count())->toBe(2);
        }

        \Illuminate\Support\Facades\Date::setTestNow();
        $convention->delete();
    }
})->group('property', 'attendance');

/**
 * Property 36: Open Attendance Reporting
 *
 * Any user with section permissions can create or update attendance reports
 * for unlocked periods. Locked periods are immutable.
 *
 * Validates: Requirements 11.5, 11.6
 */
it('allows any user to report attendance and enforces lock immutability', function () {
    App::setLocale('en');
    for ($i = 0; $i < 3; $i++) {
        $convention = Convention::factory()->create();
        $floor = \App\Models\Floor::factory()->create(['convention_id' => $convention->id]);
        $section = \App\Models\Section::factory()->create(['floor_id' => $floor->id]);

        $user1 = \App\Models\User::factory()->create();
        $user2 = \App\Models\User::factory()->create();

        $service = new AttendanceReportService;

        // Create an unlocked attendance period
        $period = AttendancePeriod::create([
            'convention_id' => $convention->id,
            'date' => now()->toDateString(),
            'period' => 'morning',
            'locked' => false,
        ]);

        // User 1 submits attendance
        $initialAttendance = fake()->numberBetween(50, 150);
        $report = $service->reportAttendance($section, $period, $initialAttendance, $user1);

        expect($report)->toBeInstanceOf(\App\Models\AttendanceReport::class)
            ->and($report->attendance)->toBe($initialAttendance)
            ->and($report->section_id)->toBe($section->id)
            ->and($report->attendance_period_id)->toBe($period->id);

        // User 2 can update the same report (open access)
        $updatedAttendance = fake()->numberBetween(50, 150);
        $updatedReport = $service->reportAttendance($section, $period, $updatedAttendance, $user2);

        expect($updatedReport->attendance)->toBe($updatedAttendance)
            ->and($updatedReport->id)->toBe($report->id); // Same report, updated

        // Lock the period
        $service->stopReport($period);
        $period->refresh();
        expect($period->locked)->toBeTrue();

        // Locked period should reject updates
        expect(fn () => $service->reportAttendance($section, $period, 888, $user1))
            ->toThrow(\Exception::class, 'locked and cannot be updated');

        $report->refresh();
        expect($report->attendance)->toBe($updatedAttendance);

        // Cleanup for next iteration
        $convention->delete();
        $user1->delete();
        $user2->delete();
    }
})->group('property', 'attendance');
