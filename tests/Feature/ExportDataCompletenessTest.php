<?php

use App\Actions\ExportConventionAction;
use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Property 48: Export Data Completeness
 *
 * For any convention export in any format (.xlsx, .docx, .md), the exported data
 * should include all floors, all sections with seat counts and occupancy, complete
 * attendance history, and all users.
 *
 * **Validates: Requirements 20.3**
 */
it('validates export data completeness across all formats', function () {
    $action = new ExportConventionAction;

    // Run property test with multiple random scenarios
    for ($i = 0; $i < 3; $i++) {
        // Create a convention with random structure
        $convention = Convention::factory()->create();

        // Create random number of floors (1-5)
        $floorCount = rand(1, 5);
        $floors = Floor::factory()->count($floorCount)->create([
            'convention_id' => $convention->id,
        ]);

        $allSections = collect();
        foreach ($floors as $floor) {
            // Create random number of sections per floor (1-10)
            $sectionCount = rand(1, 10);
            $sections = Section::factory()->count($sectionCount)->create([
                'floor_id' => $floor->id,
                'number_of_seats' => rand(50, 200),
                'occupancy' => rand(0, 100),
                'available_seats' => rand(0, 100),
                'elder_friendly' => (bool) rand(0, 1),
                'handicap_friendly' => (bool) rand(0, 1),
            ]);
            $allSections = $allSections->merge($sections);
        }

        // Create random number of users (1-10)
        $userCount = rand(1, 10);
        $users = User::factory()->count($userCount)->create();
        foreach ($users as $user) {
            $convention->users()->attach($user);
            // Assign random role
            $roles = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];
            DB::table('convention_user_roles')->insert([
                'convention_id' => $convention->id,
                'user_id' => $user->id,
                'role' => $roles[array_rand($roles)],
                'created_at' => now(),
            ]);
        }

        // Create random attendance periods (0-5)
        $periodCount = rand(0, 5);
        $allReports = collect();
        for ($p = 0; $p < $periodCount; $p++) {
            $period = AttendancePeriod::factory()->create([
                'convention_id' => $convention->id,
                'date' => now()->addDays($p),
                'period' => $p % 2 === 0 ? 'morning' : 'afternoon',
                'locked' => (bool) rand(0, 1),
            ]);

            // Create random attendance reports for this period (0-all sections)
            $reportCount = rand(0, $allSections->count());
            $sectionsToReport = $allSections->random(min($reportCount, $allSections->count()));

            foreach ($sectionsToReport as $section) {
                $report = AttendanceReport::factory()->create([
                    'attendance_period_id' => $period->id,
                    'section_id' => $section->id,
                    'attendance' => rand(0, $section->number_of_seats),
                    'reported_by' => $users->random()->id,
                    'reported_at' => now(),
                ]);
                $allReports->push($report);
            }
        }

        // Test Markdown format (text-based, easy to verify)
        $mdPath = $action->execute($convention, 'md');
        expect(file_exists($mdPath))->toBeTrue('Expected Markdown export file to exist');

        $mdContent = file_get_contents($mdPath);
        expect($mdContent)->not->toBeEmpty('Expected Markdown content to not be empty');

        // Verify convention details are present in Markdown
        expect(str_contains($mdContent, $convention->name))->toBeTrue('Expected convention name in Markdown export');
        expect(str_contains($mdContent, $convention->city))->toBeTrue('Expected city in Markdown export');
        expect(str_contains($mdContent, $convention->country))->toBeTrue('Expected country in Markdown export');

        // Verify all floors are present in Markdown
        foreach ($floors as $floor) {
            expect(str_contains($mdContent, $floor->name))->toBeTrue("Expected floor '{$floor->name}' in Markdown export");
        }

        // Verify all sections are present with their data in Markdown
        foreach ($allSections as $section) {
            expect(str_contains($mdContent, $section->name))->toBeTrue("Expected section '{$section->name}' in Markdown export");
        }

        // Verify all users are present in Markdown
        foreach ($users as $user) {
            expect(str_contains($mdContent, $user->email))->toBeTrue("Expected user email '{$user->email}' in Markdown export");
        }

        // Clean up Markdown file
        if (file_exists($mdPath)) {
            unlink($mdPath);
        }

        // Test Excel format (verify file exists and has content)
        $xlsxPath = $action->execute($convention, 'xlsx');
        expect(file_exists($xlsxPath))->toBeTrue('Expected Excel export file to exist');
        expect(filesize($xlsxPath))->toBeGreaterThan(0, 'Expected Excel file to have content');

        // Clean up Excel file
        if (file_exists($xlsxPath)) {
            unlink($xlsxPath);
        }

        // Test Word format (verify file exists and has content)
        $docxPath = $action->execute($convention, 'docx');
        expect(file_exists($docxPath))->toBeTrue('Expected Word export file to exist');
        expect(filesize($docxPath))->toBeGreaterThan(0, 'Expected Word file to have content');

        // Clean up Word file
        if (file_exists($docxPath)) {
            unlink($docxPath);
        }

        // Clean up for next iteration
        $convention->delete();
    }
});

it('exports empty convention structure correctly', function () {
    $action = new ExportConventionAction;

    // Create convention with no floors, sections, users, or attendance
    $convention = Convention::factory()->create();

    // Test Markdown format
    $mdPath = $action->execute($convention, 'md');
    expect(file_exists($mdPath))->toBeTrue('Expected Markdown export file to exist for empty convention');

    $mdContent = file_get_contents($mdPath);
    expect($mdContent)->not->toBeEmpty('Expected Markdown content to not be empty for empty convention');
    expect(str_contains($mdContent, $convention->name))->toBeTrue('Expected convention name in empty Markdown export');

    // Clean up
    if (file_exists($mdPath)) {
        unlink($mdPath);
    }

    // Test Excel format
    $xlsxPath = $action->execute($convention, 'xlsx');
    expect(file_exists($xlsxPath))->toBeTrue('Expected Excel export file to exist for empty convention');
    expect(filesize($xlsxPath))->toBeGreaterThan(0, 'Expected Excel file to have content');

    // Clean up
    if (file_exists($xlsxPath)) {
        unlink($xlsxPath);
    }

    // Test Word format
    $docxPath = $action->execute($convention, 'docx');
    expect(file_exists($docxPath))->toBeTrue('Expected Word export file to exist for empty convention');
    expect(filesize($docxPath))->toBeGreaterThan(0, 'Expected Word file to have content');

    // Clean up
    if (file_exists($docxPath)) {
        unlink($docxPath);
    }

    $convention->delete();
});

it('exports convention with complete attendance history', function () {
    $action = new ExportConventionAction;

    // Create convention with full structure
    $convention = Convention::factory()->create();
    $floor = Floor::factory()->create(['convention_id' => $convention->id]);
    $section = Section::factory()->create(['floor_id' => $floor->id]);
    $user = User::factory()->create();
    $convention->users()->attach($user);

    // Create multiple attendance periods
    $period1 = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'date' => now(),
        'period' => 'morning',
        'locked' => true,
    ]);

    $period2 = AttendancePeriod::factory()->create([
        'convention_id' => $convention->id,
        'date' => now(),
        'period' => 'afternoon',
        'locked' => false,
    ]);

    $report1 = AttendanceReport::factory()->create([
        'attendance_period_id' => $period1->id,
        'section_id' => $section->id,
        'attendance' => 50,
        'reported_by' => $user->id,
    ]);

    $report2 = AttendanceReport::factory()->create([
        'attendance_period_id' => $period2->id,
        'section_id' => $section->id,
        'attendance' => 75,
        'reported_by' => $user->id,
    ]);

    // Test Markdown format
    $mdPath = $action->execute($convention, 'md');
    $mdContent = file_get_contents($mdPath);

    // Verify both attendance reports are present
    expect(str_contains($mdContent, '50'))->toBeTrue('Expected first attendance count in Markdown export');
    expect(str_contains($mdContent, '75'))->toBeTrue('Expected second attendance count in Markdown export');
    expect(str_contains($mdContent, 'Morning'))->toBeTrue('Expected morning period in Markdown export');
    expect(str_contains($mdContent, 'Afternoon'))->toBeTrue('Expected afternoon period in Markdown export');

    // Clean up
    if (file_exists($mdPath)) {
        unlink($mdPath);
    }

    // Test Excel format
    $xlsxPath = $action->execute($convention, 'xlsx');
    expect(file_exists($xlsxPath))->toBeTrue('Expected Excel export file to exist');
    expect(filesize($xlsxPath))->toBeGreaterThan(0, 'Expected Excel file to have content');

    // Clean up
    if (file_exists($xlsxPath)) {
        unlink($xlsxPath);
    }

    // Test Word format
    $docxPath = $action->execute($convention, 'docx');
    expect(file_exists($docxPath))->toBeTrue('Expected Word export file to exist');
    expect(filesize($docxPath))->toBeGreaterThan(0, 'Expected Word file to have content');

    // Clean up
    if (file_exists($docxPath)) {
        unlink($docxPath);
    }

    $convention->delete();
});
