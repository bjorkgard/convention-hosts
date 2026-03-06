<?php

use App\Actions\ExportConventionAction;
use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

beforeEach(function () {
    // Ensure exports directory exists
    $dir = storage_path('app/private/exports');
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
});

afterEach(function () {
    // Clean up generated export files
    $dir = storage_path('app/private/exports');
    if (is_dir($dir)) {
        foreach (glob("$dir/*") as $file) {
            @unlink($file);
        }
    }
});

it('exports convention data to Excel format', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new ExportConventionAction;

    $filePath = $action->execute($convention, 'xlsx');

    expect($filePath)->toEndWith('.xlsx')
        ->and(file_exists($filePath))->toBeTrue();
});

it('exports convention data to Word format', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new ExportConventionAction;

    $filePath = $action->execute($convention, 'docx');

    expect($filePath)->toEndWith('.docx')
        ->and(file_exists($filePath))->toBeTrue();
});

it('exports convention data to Markdown format', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new ExportConventionAction;

    $filePath = $action->execute($convention, 'md');

    expect($filePath)->toEndWith('.md')
        ->and(file_exists($filePath))->toBeTrue();
});

it('includes convention details in Markdown export', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'convention_attributes' => [
            'name' => 'Export Test Convention',
            'city' => 'Vienna',
            'country' => 'Austria',
        ],
    ]);
    $convention = $structure['convention'];
    $action = new ExportConventionAction;

    $filePath = $action->execute($convention, 'md');
    $content = file_get_contents($filePath);

    expect($content)->toContain('Export Test Convention')
        ->and($content)->toContain('Vienna')
        ->and($content)->toContain('Austria');
});

it('includes floors and sections in Markdown export', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 1, 'sections_per_floor' => 2]);
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();
    $section = $structure['sections']->first();
    $action = new ExportConventionAction;

    $filePath = $action->execute($convention, 'md');
    $content = file_get_contents($filePath);

    expect($content)->toContain('Floors & Sections')
        ->and($content)->toContain($floor->name)
        ->and($content)->toContain($section->name);
});

it('includes attendance history in Markdown export', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 1, 'sections_per_floor' => 1]);
    $convention = $structure['convention'];
    $section = $structure['sections']->first();
    $owner = $structure['owner'];

    $period = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'date' => now()->toDateString(),
        'period' => 'morning',
        'locked' => true,
    ]);

    AttendanceReport::factory()->create([
        'attendance_period_id' => $period->id,
        'section_id' => $section->id,
        'attendance' => 125,
        'reported_by' => $owner->id,
        'reported_at' => now(),
    ]);

    $action = new ExportConventionAction;
    $filePath = $action->execute($convention, 'md');
    $content = file_get_contents($filePath);

    expect($content)->toContain('Attendance History')
        ->and($content)->toContain('125');
});

it('includes users in Markdown export', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];
    $action = new ExportConventionAction;

    $filePath = $action->execute($convention, 'md');
    $content = file_get_contents($filePath);

    expect($content)->toContain('Users')
        ->and($content)->toContain($owner->email);
});

it('throws exception for unsupported export format', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $action = new ExportConventionAction;

    expect(fn () => $action->execute($convention, 'pdf'))
        ->toThrow(\InvalidArgumentException::class);
});
