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
     * Only Administrator or Owner roles can start attendance reports.
     */
    public function start(Request $request, Convention $convention): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole($convention, ['Owner', 'Administrator'])) {
            abort(403, __('attendance.start.forbidden'));
        }

        try {
            $period = $this->attendanceReportService->startReport($convention);

            return redirect()->back()->with('success', __('attendance.start.success', ['period' => $period->period]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Stop (lock) an attendance period.
     *
     * Only Administrator or Owner roles can stop attendance reports.
     */
    public function stop(Request $request, Convention $convention, AttendancePeriod $attendancePeriod): RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasAnyRole($convention, ['Owner', 'Administrator'])) {
            abort(403, __('attendance.stop.forbidden'));
        }

        $this->attendanceReportService->stopReport($attendancePeriod);

        return redirect()->back()->with('success', __('attendance.stop.success'));
    }

    /**
     * Submit section attendance for an active period.
     *
     * Any user with section access can report attendance.
     */
    public function report(ReportAttendanceRequest $request, Section $section, AttendancePeriod $attendancePeriod): RedirectResponse
    {
        $user = $request->user();
        $attendance = $request->validated('attendance');

        try {
            $this->attendanceReportService->reportAttendance(
                $section,
                $attendancePeriod,
                $attendance,
                $user
            );

            return redirect()->back()->with('success', __('attendance.report.success', ['attendance' => $attendance, 'period' => $attendancePeriod->period]));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
