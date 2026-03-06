<?php

use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\User;
use App\Services\AttendanceReportService;
use Illuminate\Support\Carbon;
use Tests\Helpers\ConventionTestHelper;

it('starts an attendance report for a convention', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $service = new AttendanceReportService;

    $period = $service->startReport($convention);

    expect($period)->toBeInstanceOf(AttendancePeriod::class)
        ->and($period->convention_id)->toBe($convention->id)
        ->and($period->locked)->toBeFalse()
        ->and($period->date->toDateString())->toBe(now()->toDateString());
});

it('determines morning period before noon', function () {
    Carbon::setTestNow(Carbon::today()->setHour(9));

    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $service = new AttendanceReportService;

    $period = $service->startReport($convention);

    expect($period->period)->toBe('morning');

    Carbon::setTestNow();
});

it('determines afternoon period after noon', function () {
    Carbon::setTestNow(Carbon::today()->setHour(14));

    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $service = new AttendanceReportService;

    $period = $service->startReport($convention);

    expect($period->period)->toBe('afternoon');

    Carbon::setTestNow();
});

it('enforces max 2 reports per day', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];

    // Create 2 periods for today
    AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
    ]);
    AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'afternoon',
    ]);

    $service = new AttendanceReportService;

    expect(fn () => $service->startReport($convention))
        ->toThrow(\Exception::class, 'Maximum of 2 attendance reports per day has been reached.');
});

it('reports attendance for a section', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $user = $structure['owner'];

    $period = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => false,
    ]);

    $service = new AttendanceReportService;
    $report = $service->reportAttendance($section, $period, 150, $user);

    expect($report)->toBeInstanceOf(AttendanceReport::class)
        ->and($report->attendance)->toBe(150)
        ->and($report->reported_by)->toBe($user->id)
        ->and($report->reported_at)->not->toBeNull();
});

it('locks an attendance period', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];

    $period = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'locked' => false,
    ]);

    $service = new AttendanceReportService;
    $service->stopReport($period);

    $period->refresh();
    expect($period->locked)->toBeTrue();
});

it('prevents reporting on a locked period', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $user = $structure['owner'];

    $period = AttendancePeriod::factory()->create([
        'convention_id' => $structure['convention']->id,
        'locked' => true,
    ]);

    $service = new AttendanceReportService;

    expect(fn () => $service->reportAttendance($section, $period, 100, $user))
        ->toThrow(\Exception::class, 'This attendance period is locked and cannot be updated.');
});

it('restricts attendance updates to original reporter', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $originalReporter = $structure['owner'];
    $otherUser = User::factory()->create();
    ConventionTestHelper::attachUserToConvention($otherUser, $convention, ['ConventionUser']);

    $period = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'locked' => false,
    ]);

    $service = new AttendanceReportService;

    // Original reporter creates the report
    $service->reportAttendance($section, $period, 100, $originalReporter);

    // Different user tries to update
    expect(fn () => $service->reportAttendance($section, $period, 200, $otherUser))
        ->toThrow(\Exception::class, 'Only the original reporter can update this section\'s attendance.');
});

it('allows original reporter to update their own report', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $user = $structure['owner'];

    $period = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'locked' => false,
    ]);

    $service = new AttendanceReportService;

    $service->reportAttendance($section, $period, 100, $user);
    $updated = $service->reportAttendance($section, $period, 200, $user);

    expect($updated->attendance)->toBe(200);
});

it('calculates total attendance for a period', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $user = $structure['owner'];

    $period = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'locked' => false,
    ]);

    $service = new AttendanceReportService;

    // Report attendance for multiple sections
    foreach ($structure['sections']->take(3) as $section) {
        $service->reportAttendance($section, $period, 50, $user);
    }

    expect($period->totalAttendance())->toBe(150)
        ->and($period->reportedSectionsCount())->toBe(3);
});
