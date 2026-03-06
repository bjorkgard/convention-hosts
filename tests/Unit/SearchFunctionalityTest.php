<?php

use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

it('filters sections with occupancy below 90 percent', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 1, 'sections_per_floor' => 4]);
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    // Set varying occupancy levels
    $structure['sections'][0]->update(['occupancy' => 20]);
    $structure['sections'][1]->update(['occupancy' => 50]);
    $structure['sections'][2]->update(['occupancy' => 90]);
    $structure['sections'][3]->update(['occupancy' => 100]);

    $response = $this->actingAs($owner)
        ->get(route('search.index', $convention))
        ->assertOk();

    $sections = $response->original->getData()['page']['props']['sections'];
    $sectionData = collect($sections['data']);

    // Only sections with occupancy < 90 should appear
    expect($sectionData)->toHaveCount(2);
    $sectionData->each(function ($section) {
        expect($section['occupancy'])->toBeLessThan(90);
    });
});

it('filters by elder friendly sections', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 1, 'sections_per_floor' => 3]);
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $structure['sections'][0]->update(['elder_friendly' => true, 'occupancy' => 10]);
    $structure['sections'][1]->update(['elder_friendly' => false, 'occupancy' => 20]);
    $structure['sections'][2]->update(['elder_friendly' => true, 'occupancy' => 30]);

    $response = $this->actingAs($owner)
        ->get(route('search.index', ['convention' => $convention, 'elder_friendly' => true]))
        ->assertOk();

    $sections = $response->original->getData()['page']['props']['sections'];
    $sectionData = collect($sections['data']);

    $sectionData->each(function ($section) {
        expect($section['elder_friendly'])->toBeTrue();
    });
});

it('filters by handicap friendly sections', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 1, 'sections_per_floor' => 3]);
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $structure['sections'][0]->update(['handicap_friendly' => true, 'occupancy' => 10]);
    $structure['sections'][1]->update(['handicap_friendly' => false, 'occupancy' => 20]);
    $structure['sections'][2]->update(['handicap_friendly' => true, 'occupancy' => 30]);

    $response = $this->actingAs($owner)
        ->get(route('search.index', ['convention' => $convention, 'handicap_friendly' => true]))
        ->assertOk();

    $sections = $response->original->getData()['page']['props']['sections'];
    $sectionData = collect($sections['data']);

    $sectionData->each(function ($section) {
        expect($section['handicap_friendly'])->toBeTrue();
    });
});

it('sorts search results by occupancy ascending', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 1, 'sections_per_floor' => 4]);
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $structure['sections'][0]->update(['occupancy' => 60]);
    $structure['sections'][1]->update(['occupancy' => 10]);
    $structure['sections'][2]->update(['occupancy' => 40]);
    $structure['sections'][3]->update(['occupancy' => 80]);

    $response = $this->actingAs($owner)
        ->get(route('search.index', $convention))
        ->assertOk();

    $sections = $response->original->getData()['page']['props']['sections'];
    $occupancies = collect($sections['data'])->pluck('occupancy')->toArray();

    // Verify ascending order
    for ($i = 1; $i < count($occupancies); $i++) {
        expect($occupancies[$i])->toBeGreaterThanOrEqual($occupancies[$i - 1]);
    }
});

it('filters by specific floor', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 2, 'sections_per_floor' => 2]);
    $convention = $structure['convention'];
    $owner = $structure['owner'];
    $targetFloor = $structure['floors']->first();

    // Set low occupancy so all sections appear
    $structure['sections']->each(fn ($s) => $s->update(['occupancy' => 10]));

    $response = $this->actingAs($owner)
        ->get(route('search.index', ['convention' => $convention, 'floor_id' => $targetFloor->id]))
        ->assertOk();

    $sections = $response->original->getData()['page']['props']['sections'];
    $sectionData = collect($sections['data']);

    $sectionData->each(function ($section) use ($targetFloor) {
        expect($section['floor_id'])->toBe($targetFloor->id);
    });
});

it('allows any authenticated user to access search regardless of role', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];

    // Create a SectionUser (most restricted role)
    $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
        'section_ids' => [$structure['sections']->first()->id],
    ]);

    $this->actingAs($sectionUser)
        ->get(route('search.index', $convention))
        ->assertOk();
});

it('allows FloorUser to access search without role filtering', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];

    $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
        'floor_ids' => [$structure['floors']->first()->id],
    ]);

    $this->actingAs($floorUser)
        ->get(route('search.index', $convention))
        ->assertOk();
});
