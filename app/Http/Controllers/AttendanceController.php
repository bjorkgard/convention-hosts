<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReportAttendanceRequest;
use App\Models\AttendancePeriod;
use App\Models\Convention;
use App\Models\Section;
use App\Services\AttendanceReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(
        private readonly AttendanceReportService $attendanceReportService
    ) {}

    /**
     * Start a new attendance report period for the convention.
     *
     * Only ConventionUser or Owner roles can start attendance reports.
     */
    public function start(Request $request, Convention $convention): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            abort(403, 'Only convention managers can start attendance reports.');
        }

        try {
            $period = $this->attendanceReportService->startReport($convention);

            return redirect()->back()->with('success', 'Attendance report started for '.$period->period.' period.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Stop (lock) an attendance period.
     *
     * Only ConventionUser or Owner roles can stop attendance reports.
     */
    public function stop(Request $request, Convention $convention, AttendancePeriod $attendancePeriod): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            abort(403, 'Only convention managers can stop attendance reports.');
        }

        $this->attendanceReportService->stopReport($attendancePeriod);

        return redirect()->back()->with('success', 'Attendance report has been locked.');
    }

    /**
     * Submit section attendance for an active period.
     *
     * Any user with section access can report attendance.
     */
    public function report(ReportAttendanceRequest $request, Section $section, AttendancePeriod $attendancePeriod): RedirectResponse
    {
        $user = $request->user();

        try {
            $this->attendanceReportService->reportAttendance(
                $section,
                $attendancePeriod,
                $request->validated('attendance'),
                $user
            );

            return redirect()->back()->with('success', 'Attendance reported successfully.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
