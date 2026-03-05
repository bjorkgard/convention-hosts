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
 * Property 53: Export Format Serialization
 *
 * For any convention export, the data should be correctly serialized into the
 * requested format (.xlsx, .docx, or .md) with valid syntax for that format.
 *
 * **Validates: Requirements 25.1, 25.2, 25.3**
 */
it('validates Excel format serialization', function () {
    $action = new ExportConventionAction;

    // Run property test with multiple random scenarios
    for ($i = 0; $i < 50; $i++) {
        // Create a convention with random data
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        $section = Section::factory()->create(['floor_id' => $floor->id]);

        // Export to Excel
        $xlsxPath = $action->execute($convention, 'xlsx');

        // Verify file exists
        expect(file_exists($xlsxPath))->toBeTrue('Expected Excel file to exist');

        // Verify file is not empty
        expect(filesize($xlsxPath))->toBeGreaterThan(0, 'Expected Excel file to have content');

        // Verify file has valid Excel signature (PK zip header)
        $handle = fopen($xlsxPath, 'rb');
        $header = fread($handle, 4);
        fclose($handle);

        expect($header)->toBe("PK\x03\x04", 'Expected valid Excel file signature');

        // Clean up
        if (file_exists($xlsxPath)) {
            unlink($xlsxPath);
        }

        $convention->delete();
    }
});

it('validates Word format serialization', function () {
    $action = new ExportConventionAction;

    // Run property test with multiple random scenarios
    for ($i = 0; $i < 50; $i++) {
        // Create a convention with random data
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);
        $section = Section::factory()->create(['floor_id' => $floor->id]);

        // Export to Word
        $docxPath = $action->execute($convention, 'docx');

        // Verify file exists
        expect(file_exists($docxPath))->toBeTrue('Expected Word file to exist');

        // Verify file is not empty
        expect(filesize($docxPath))->toBeGreaterThan(0, 'Expected Word file to have content');

        // Verify file has valid Word signature (PK zip header - docx is a zip file)
        $handle = fopen($docxPath, 'rb');
        $header = fread($handle, 4);
        fclose($handle);

        expect($header)->toBe("PK\x03\x04", 'Expected valid Word file signature');

        // Clean up
        if (file_exists($docxPath)) {
            unlink($docxPath);
        }

        $convention->delete();
    }
});

it('validates Markdown format serialization with valid syntax', function () {
    $action = new ExportConventionAction;

    // Run property test with multiple random scenarios
    for ($i = 0; $i < 100; $i++) {
        // Create a convention with random structure
        $convention = Convention::factory()->create();

        // Create random floors and sections
        $floorCount = rand(1, 3);
        for ($f = 0; $f < $floorCount; $f++) {
            $floor = Floor::factory()->create(['convention_id' => $convention->id]);
            $sectionCount = rand(1, 5);
            for ($s = 0; $s < $sectionCount; $s++) {
                Section::factory()->create(['floor_id' => $floor->id]);
            }
        }

        // Create random users
        $userCount = rand(1, 5);
        for ($u = 0; $u < $userCount; $u++) {
            $user = User::factory()->create();
            $convention->users()->attach($user);
            DB::table('convention_user_roles')->insert([
                'convention_id' => $convention->id,
                'user_id' => $user->id,
                'role' => 'Owner',
                'created_at' => now(),
            ]);
        }

        // Export to Markdown
        $mdPath = $action->execute($convention, 'md');

        // Verify file exists
        expect(file_exists($mdPath))->toBeTrue('Expected Markdown file to exist');

        // Read content
        $content = file_get_contents($mdPath);
        expect($content)->not->toBeEmpty('Expected Markdown content to not be empty');

        // Verify valid Markdown syntax
        // 1. Should start with H1 header
        expect(str_starts_with($content, '#'))->toBeTrue('Expected Markdown to start with H1 header');

        // 2. Should contain H2 headers for sections
        expect(str_contains($content, '## Floors & Sections'))->toBeTrue('Expected Floors & Sections header');
        expect(str_contains($content, '## Attendance History'))->toBeTrue('Expected Attendance History header');
        expect(str_contains($content, '## Users'))->toBeTrue('Expected Users header');

        // 3. Should contain valid table syntax (pipes and dashes)
        expect(str_contains($content, '|'))->toBeTrue('Expected table pipes in Markdown');
        expect(str_contains($content, '---'))->toBeTrue('Expected table separator in Markdown');

        // 4. Should not contain HTML tags (pure Markdown)
        expect(str_contains($content, '<'))->toBeFalse('Expected no HTML tags in Markdown');
        expect(str_contains($content, '>'))->toBeFalse('Expected no HTML tags in Markdown');

        // 5. Verify table structure is valid (tables exist and have consistent structure)
        $lines = explode("\n", $content);
        $inTable = false;
        $tablePipeCount = 0;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);

            if (str_starts_with($trimmedLine, '|')) {
                $pipeCount = substr_count($trimmedLine, '|');

                if (! $inTable) {
                    // Starting a new table
                    $inTable = true;
                    $tablePipeCount = $pipeCount;
                } else {
                    // In a table - check consistency (skip separator lines)
                    if (! str_contains($trimmedLine, '---')) {
                        expect($pipeCount)->toBe($tablePipeCount, 'Expected consistent pipe count within same table');
                    }
                }
            } elseif ($inTable && $trimmedLine === '') {
                // Empty line ends the table
                $inTable = false;
                $tablePipeCount = 0;
            }
        }

        // Clean up
        if (file_exists($mdPath)) {
            unlink($mdPath);
        }

        $convention->delete();
    }
});

it('validates export data structure before serialization', function () {
    $action = new ExportConventionAction;

    // Test with various data structures
    for ($i = 0; $i < 50; $i++) {
        // Create convention with varying complexity
        $convention = Convention::factory()->create();

        // Sometimes add floors, sometimes don't
        if (rand(0, 1)) {
            $floor = Floor::factory()->create(['convention_id' => $convention->id]);

            // Sometimes add sections, sometimes don't
            if (rand(0, 1)) {
                Section::factory()->create(['floor_id' => $floor->id]);
            }
        }

        // Sometimes add users, sometimes don't
        if (rand(0, 1)) {
            $user = User::factory()->create();
            $convention->users()->attach($user);
        }

        // Sometimes add attendance, sometimes don't
        if (rand(0, 1) && $convention->floors->isNotEmpty()) {
            $period = AttendancePeriod::factory()->create([
                'convention_id' => $convention->id,
            ]);

            if ($convention->floors->first()->sections->isNotEmpty()) {
                AttendanceReport::factory()->create([
                    'attendance_period_id' => $period->id,
                    'section_id' => $convention->floors->first()->sections->first()->id,
                    'reported_by' => User::factory()->create()->id,
                ]);
            }
        }

        // All formats should handle any data structure without errors
        $formats = ['xlsx', 'docx', 'md'];
        foreach ($formats as $format) {
            $filePath = $action->execute($convention, $format);

            expect(file_exists($filePath))->toBeTrue("Expected {$format} file to exist for any data structure");
            expect(filesize($filePath))->toBeGreaterThan(0, "Expected {$format} file to have content");

            // Clean up
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $convention->delete();
    }
});

it('rejects invalid export format', function () {
    $action = new ExportConventionAction;
    $convention = Convention::factory()->create();

    // Should throw exception for invalid format
    expect(fn () => $action->execute($convention, 'invalid'))
        ->toThrow(\InvalidArgumentException::class, 'Unsupported export format: invalid');

    $convention->delete();
});
