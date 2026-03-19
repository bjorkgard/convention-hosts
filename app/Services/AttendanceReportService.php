<?php

namespace App\Services;

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\Section;
use App\Models\User;

class AttendanceReportService
{
    /**
     * Start a new attendance report for the convention.
     *
     * @throws \Exception if max 2 reports per day limit is reached
     */
    public function startReport(Convention $convention): AttendancePeriod
    {
        $today = now()->toDateString();

        // Check if max 2 reports per day limit is reached
        $reportsToday = AttendancePeriod::where('convention_id', $convention->id)
            ->whereDate('date', $today)
            ->count();

        if ($reportsToday >= 2) {
            throw new \Exception(__('attendance.max_reports_reached'));
        }

        // Determine current period (morning/afternoon based on time)
        $currentPeriod = $this->getCurrentPeriod();

        // Create or retrieve attendance period
        $attendancePeriod = AttendancePeriod::firstOrCreate(
            [
                'convention_id' => $convention->id,
                'date' => $today,
                'period' => $currentPeriod,
            ],
            [
                'locked' => false,
            ]
        );

        return $attendancePeriod;
    }

    /**
     * Stop an attendance report by locking the period.
     */
    public function stopReport(AttendancePeriod $period): void
    {
        $period->locked = true;
        $period->save();
    }

    /**
     * Report attendance for a section in a period.
     *
     * Any user with section permissions can create or update reports.
     * The $user parameter is optional (null for URL sessions).
     *
     * @throws \Exception if period is locked
     */
    public function reportAttendance(
        Section $section,
        AttendancePeriod $period,
        int $attendance,
        ?User $user = null
    ): AttendanceReport {
        // Check if period is locked
        if ($period->locked) {
            throw new \Exception(__('attendance.period_locked'));
        }

        // Create or update attendance report (any user with permissions can update)
        $report = AttendanceReport::updateOrCreate(
            [
                'attendance_period_id' => $period->id,
                'section_id' => $section->id,
            ],
            [
                'attendance' => $attendance,
                'reported_at' => now(),
            ]
        );

        return $report;
    }

    /**
     * Determine the current period based on time.
     */
    protected function getCurrentPeriod(): string
    {
        $hour = now()->hour;

        return $hour < 12 ? 'morning' : 'afternoon';
    }
}
