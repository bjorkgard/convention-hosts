<?php

use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

// Feature: section-crud-management, Property 10: Server-side validation rejects invalid data without state change
// Validates: Requirements 7.1, 7.2, 7.3, 7.4, 7.5, 7.6, 3.3, 4.5

it('rejects invalid section data on create without state change', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 0,
    ]);
    $owner = $structure['owner'];
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();

    $invalidPayloads = [
        // Empty name (Req 7.1)
        ['name' => '', 'number_of_seats' => 50],
        // Name exceeds 255 chars (Req 7.1)
        ['name' => str_repeat('A', 256), 'number_of_seats' => 50],
        // Missing name entirely (Req 7.1)
        ['number_of_seats' => 50],
        // number_of_seats < 1 (Req 7.2)
        ['name' => 'Valid Name', 'number_of_seats' => 0],
        ['name' => 'Valid Name', 'number_of_seats' => -5],
        // number_of_seats not integer (Req 7.2)
        ['name' => 'Valid Name', 'number_of_seats' => 'abc'],
        // Missing number_of_seats (Req 7.2)
        ['name' => 'Valid Name'],
        // elder_friendly not boolean (Req 7.3)
        ['name' => 'Valid Name', 'number_of_seats' => 50, 'elder_friendly' => 'yes'],
        // handicap_friendly not boolean (Req 7.4)
        ['name' => 'Valid Name', 'number_of_seats' => 50, 'handicap_friendly' => 'nope'],
        // information not string (Req 7.5)
        ['name' => 'Valid Name', 'number_of_seats' => 50, 'information' => ['array']],
    ];

    foreach ($invalidPayloads as $index => $payload) {
        $sectionCountBefore = Section::count();

        $response = $this->actingAs($owner)
            ->post(route('sections.store', [$convention, $floor]), $payload);

        $response->assertSessionHasErrors();
        expect(Section::count())->toBe($sectionCountBefore, "Payload index {$index} should not create a section");
    }
})->group('property', 'section-crud');

it('rejects invalid section data on update without state change', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'floors' => 1,
        'sections_per_floor' => 1,
    ]);
    $owner = $structure['owner'];
    $section = $structure['sections']->first();
    $originalAttributes = $section->only(['name', 'number_of_seats', 'elder_friendly', 'handicap_friendly', 'information']);

    $invalidPayloads = [
        ['name' => '', 'number_of_seats' => 50],
        ['name' => str_repeat('A', 256), 'number_of_seats' => 50],
        ['number_of_seats' => 50],
        ['name' => 'Valid Name', 'number_of_seats' => 0],
        ['name' => 'Valid Name', 'number_of_seats' => -1],
        ['name' => 'Valid Name', 'number_of_seats' => 'abc'],
        ['name' => 'Valid Name'],
        ['name' => 'Valid Name', 'number_of_seats' => 50, 'elder_friendly' => 'yes'],
        ['name' => 'Valid Name', 'number_of_seats' => 50, 'handicap_friendly' => 'nope'],
        ['name' => 'Valid Name', 'number_of_seats' => 50, 'information' => ['array']],
    ];

    foreach ($invalidPayloads as $index => $payload) {
        $response = $this->actingAs($owner)
            ->put(route('sections.update', $section), $payload);

        $response->assertSessionHasErrors();

        $section->refresh();
        expect($section->only(['name', 'number_of_seats', 'elder_friendly', 'handicap_friendly', 'information']))
            ->toBe($originalAttributes, "Payload index {$index} should not modify the section");
    }
})->group('property', 'section-crud');
