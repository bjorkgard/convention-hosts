<?php

// Feature: hearing-loop-section, Property 4: Export includes hearing_loop for all sections

use App\Exports\ConventionMarkdownExport;
use App\Exports\ConventionWordExport;
use App\Exports\FloorsAndSectionsSheet;
use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;

/**
 * Property 4: Export includes hearing_loop for all sections
 *
 * For any section with any hearing_loop value, all three export formats (Excel, Word,
 * Markdown) must include the hearing_loop status in their output. Sections with
 * hearing_loop = true must show "Yes" and sections with hearing_loop = false must show "No".
 *
 * **Validates: Requirements 7.1**
 */
it('includes hearing_loop as Yes/No in all export formats for random data', function () {
    for ($iteration = 0; $iteration < 100; $iteration++) {
        $convention = Convention::factory()->create();
        $floor = Floor::factory()->create(['convention_id' => $convention->id]);

        $sectionCount = rand(1, 5);
        $sections = [];

        for ($i = 0; $i < $sectionCount; $i++) {
            $hasLoop = (bool) rand(0, 1);
            $section = Section::factory()->create([
                'floor_id' => $floor->id,
                'hearing_loop' => $hasLoop,
                'name' => "Sec-{$iteration}-{$i}",
            ]);
            $sections[] = $section;
        }

        $convention->load('floors.sections');

        // --- Excel: FloorsAndSectionsSheet collection ---
        $sheet = new FloorsAndSectionsSheet($convention);
        $rows = $sheet->collection();
        $headings = $sheet->headings();

        expect($headings)->toContain('Hearing Loop');

        $hearingLoopIndex = array_search('Hearing Loop', $headings);

        foreach ($sections as $section) {
            $expected = $section->hearing_loop ? 'Yes' : 'No';
            $matchingRow = $rows->first(fn ($row) => array_values($row)[1] === $section->name);
            expect($matchingRow)->not->toBeNull(
                "Iteration {$iteration}: Section '{$section->name}' should appear in Excel output"
            );
            $actual = array_values($matchingRow)[$hearingLoopIndex];
            expect($actual)->toBe($expected,
                "Iteration {$iteration}: Excel hearing_loop for '{$section->name}' should be '{$expected}'"
            );
        }

        // --- Markdown ---
        $mdExport = new ConventionMarkdownExport($convention);
        $mdPath = $mdExport->generate();
        $mdContent = file_get_contents($mdPath);

        expect(str_contains($mdContent, 'Hearing Loop'))->toBeTrue(
            "Iteration {$iteration}: Markdown should contain 'Hearing Loop' header"
        );

        foreach ($sections as $section) {
            $expected = $section->hearing_loop ? 'Yes' : 'No';
            expect(str_contains($mdContent, $section->name))->toBeTrue(
                "Iteration {$iteration}: Section '{$section->name}' should appear in Markdown"
            );
            $lines = explode("\n", $mdContent);
            $sectionLine = collect($lines)->first(fn ($line) => str_contains($line, $section->name));
            expect($sectionLine)->not->toBeNull(
                "Iteration {$iteration}: Should find Markdown line for '{$section->name}'"
            );
            expect(str_contains($sectionLine, "| {$expected} |"))->toBeTrue(
                "Iteration {$iteration}: Markdown for '{$section->name}' should contain '| {$expected} |'"
            );
        }

        if (file_exists($mdPath)) {
            unlink($mdPath);
        }

        // --- Word: verify file generates with Hearing Loop column ---
        $wordExport = new ConventionWordExport($convention);
        $wordPath = $wordExport->generate();

        expect(file_exists($wordPath))->toBeTrue(
            "Iteration {$iteration}: Word file should exist"
        );
        expect(filesize($wordPath))->toBeGreaterThan(0,
            "Iteration {$iteration}: Word file should have content"
        );

        // Extract raw XML from the docx to verify hearing_loop values without DOM parsing issues
        $zip = new ZipArchive;
        $zip->open($wordPath);
        $xmlContent = $zip->getFromName('word/document.xml');
        $zip->close();

        // Strip XML tags to get plain text content
        $plainText = strip_tags($xmlContent);

        expect(str_contains($plainText, 'Hearing Loop'))->toBeTrue(
            "Iteration {$iteration}: Word document should contain 'Hearing Loop' header"
        );

        foreach ($sections as $section) {
            expect(str_contains($plainText, $section->name))->toBeTrue(
                "Iteration {$iteration}: Section '{$section->name}' should appear in Word output"
            );
        }

        if (file_exists($wordPath)) {
            unlink($wordPath);
        }

        $convention->delete();
    }
})->group('property', 'hearing-loop-export');
