<?php

use App\Actions\ExportConventionAction;
use App\Models\AttendancePeriod;
use App\Models\AttendanceReport;
use App\Models\Convention;
use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

/**
 * Property 54: Export Data Validation
 *
 * The system should validate export data structure before serialization to prevent
 * malformed output. For any convention with random structure (varying floors, sections,
 * users, attendance), the export action must load all related data correctly and
 * produce valid output for all formats including edge cases.
 *
 * **Validates: Requirements 25.5**
 */
it('validates export data includes all required entities for random convention structures', function () {
    $action = new ExportConventionAction;

    for ($i = 0; $i < 100; $i++) {
        // Generate random structure dimensions
        $floorCount = fake()->numberBetween(1, 4);
        $sectionsPerFloor = fake()->numberBetween(1, 5);
        $userCount = fake()->numberBetween(1, 6);
        $periodCount = fake()->numberBetween(0, 3);

        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => $floorCount,
            'sections_per_floor' => $sectionsPerFloor,
            'with_owner' => false,
        ]);

        $convention = $structure['convention'];
        $floors = $structure['floors'];
        $sections = $structure['sections'];

        // Attach random users with roles
        $users = User::factory()->count($userCount)->create();
        $roles = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];
        foreach ($users as $user) {
            $role = fake()->randomElement($roles);
            ConventionTestHelper::attachUserToConvention($user, $convention, [$role]);
        }

        // Create random attendance periods with reports
        $allReports = collect();
        for ($p = 0; $p < $periodCount; $p++) {
            $period = AttendancePeriod::factory()->create([
                'convention_id' => $convention->id,
                'date' => now()->addDays($p),
                'period' => $p % 2 === 0 ? 'morning' : 'afternoon',
                'locked' => fake()->boolean(),
            ]);

            // Report on a random subset of sections
            $reportCount = fake()->numberBetween(0, min(3, $sections->count()));
            $sectionsToReport = $sections->random(max(1, $reportCount));

            foreach ($sectionsToReport as $section) {
                $report = AttendanceReport::factory()->create([
                    'attendance_period_id' => $period->id,
                    'section_id' => $section->id,
                    'attendance' => fake()->numberBetween(0, $section->number_of_seats),
                    'reported_by' => $users->random()->id,
                    'reported_at' => now(),
                ]);
                $allReports->push($report);
            }
        }

        // Execute export and verify data loading via Markdown (text-inspectable)
        $mdPath = $action->execute($convention, 'md');
        expect(file_exists($mdPath))->toBeTrue("Iteration {$i}: Markdown file should exist");

        $content = file_get_contents($mdPath);

        // Verify all floors present
        foreach ($floors as $floor) {
            expect(str_contains($content, $floor->name))->toBeTrue(
                "Iteration {$i}: Floor '{$floor->name}' should be in export"
            );
        }

        // Verify all sections present
        foreach ($sections as $section) {
            expect(str_contains($content, $section->name))->toBeTrue(
                "Iteration {$i}: Section '{$section->name}' should be in export"
            );
        }

        // Verify all users with roles present
        foreach ($users as $user) {
            expect(str_contains($content, $user->email))->toBeTrue(
                "Iteration {$i}: User '{$user->email}' should be in export"
            );
        }

        // Verify attendance reports present
        foreach ($allReports as $report) {
            expect(str_contains($content, (string) $report->attendance))->toBeTrue(
                "Iteration {$i}: Attendance value '{$report->attendance}' should be in export"
            );
        }

        // Clean up file
        if (file_exists($mdPath)) {
            unlink($mdPath);
        }

        $convention->delete();
    }
})->group('property', 'export-validation');

it('handles edge case of empty convention with no floors', function () {
    $action = new ExportConventionAction;

    for ($i = 0; $i < 20; $i++) {
        $convention = Convention::factory()->create();

        // Export all formats - none should fail
        $formats = ['xlsx', 'docx', 'md'];
        foreach ($formats as $format) {
            $filePath = $action->execute($convention, $format);

            expect(file_exists($filePath))->toBeTrue(
                "Iteration {$i}: {$format} export should succeed for empty convention"
            );
            expect(filesize($filePath))->toBeGreaterThan(0,
                "Iteration {$i}: {$format} file should have content for empty convention"
            );

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $convention->delete();
    }
})->group('property', 'export-validation');

it('handles convention with floors but no attendance data', function () {
    $action = new ExportConventionAction;

    for ($i = 0; $i < 20; $i++) {
        $floorCount = fake()->numberBetween(1, 4);
        $sectionsPerFloor = fake()->numberBetween(1, 5);

        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => $floorCount,
            'sections_per_floor' => $sectionsPerFloor,
            'with_owner' => true,
        ]);

        $convention = $structure['convention'];

        // No attendance periods created - export should still work
        $formats = ['xlsx', 'docx', 'md'];
        foreach ($formats as $format) {
            $filePath = $action->execute($convention, $format);

            expect(file_exists($filePath))->toBeTrue(
                "Iteration {$i}: {$format} export should succeed with no attendance"
            );
            expect(filesize($filePath))->toBeGreaterThan(0,
                "Iteration {$i}: {$format} file should have content with no attendance"
            );

            if ($format === 'md') {
                $content = file_get_contents($filePath);
                // Verify floors and sections are still present
                foreach ($structure['floors'] as $floor) {
                    expect(str_contains($content, $floor->name))->toBeTrue(
                        "Iteration {$i}: Floor '{$floor->name}' should be in export even without attendance"
                    );
                }
            }

            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $convention->delete();
    }
})->group('property', 'export-validation');

it('verifies data completeness by counting entities in export output', function () {
    $action = new ExportConventionAction;

    for ($i = 0; $i < 50; $i++) {
        $floorCount = fake()->numberBetween(1, 3);
        $sectionsPerFloor = fake()->numberBetween(1, 4);

        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => $floorCount,
            'sections_per_floor' => $sectionsPerFloor,
            'with_owner' => false,
        ]);

        $convention = $structure['convention'];
        $expectedSectionCount = $floorCount * $sectionsPerFloor;

        // Attach users with different roles
        $userCount = fake()->numberBetween(1, 4);
        $users = User::factory()->count($userCount)->create();
        $roleOptions = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];
        foreach ($users as $user) {
            ConventionTestHelper::attachUserToConvention(
                $user, $convention, [fake()->randomElement($roleOptions)]
            );
        }

        // Reload convention with all relationships
        $convention->load([
            'floors.sections',
            'users',
            'attendancePeriods.reports',
        ]);

        // Verify loaded data counts match expected
        expect($convention->floors)->toHaveCount($floorCount,
            "Iteration {$i}: Convention should have {$floorCount} floors loaded"
        );

        $totalSections = $convention->floors->sum(fn ($f) => $f->sections->count());
        expect($totalSections)->toBe($expectedSectionCount,
            "Iteration {$i}: Convention should have {$expectedSectionCount} total sections loaded"
        );

        expect($convention->users)->toHaveCount($userCount,
            "Iteration {$i}: Convention should have {$userCount} users loaded"
        );

        // Export to markdown and verify all entities appear
        $mdPath = $action->execute($convention, 'md');
        $content = file_get_contents($mdPath);

        foreach ($convention->floors as $floor) {
            expect(str_contains($content, $floor->name))->toBeTrue(
                "Iteration {$i}: Floor '{$floor->name}' must appear in export"
            );
            foreach ($floor->sections as $section) {
                expect(str_contains($content, $section->name))->toBeTrue(
                    "Iteration {$i}: Section '{$section->name}' must appear in export"
                );
            }
        }

        foreach ($convention->users as $user) {
            expect(str_contains($content, $user->email))->toBeTrue(
                "Iteration {$i}: User '{$user->email}' must appear in export"
            );
        }

        if (file_exists($mdPath)) {
            unlink($mdPath);
        }

        $convention->delete();
    }
})->group('property', 'export-validation');
