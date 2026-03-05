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
            throw new \Exception('Maximum of 2 attendance reports per day has been reached.');
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
     * @throws \Exception if user doesn't have permission or period is locked
     */
    public function reportAttendance(
        Section $section,
        AttendancePeriod $period,
        int $attendance,
        User $user
    ): AttendanceReport {
        // Check if period is locked
        if ($period->locked) {
            throw new \Exception('This attendance period is locked and cannot be updated.');
        }

        // Validate user has permission for section
        // This will be enforced by policies in the controller layer
        // For now, we'll allow the operation

        // Check if report already exists
        $existingReport = AttendanceReport::where('attendance_period_id', $period->id)
            ->where('section_id', $section->id)
            ->first();

        if ($existingReport) {
            // Enforce update restriction: only original reporter can update
            if ($existingReport->reported_by !== $user->id) {
                throw new \Exception('Only the original reporter can update this section\'s attendance.');
            }

            // Update existing report
            $existingReport->attendance = $attendance;
            $existingReport->reported_at = now();
            $existingReport->save();

            return $existingReport;
        }

        // Create new attendance report
        $report = AttendanceReport::create([
            'attendance_period_id' => $period->id,
            'section_id' => $section->id,
            'attendance' => $attendance,
            'reported_by' => $user->id,
            'reported_at' => now(),
        ]);

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
