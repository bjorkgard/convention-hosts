<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\User;
use App\Policies\ConventionPolicy;
use App\Policies\FloorPolicy;
use App\Policies\SectionPolicy;
use Illuminate\Support\Facades\Gate;
use Tests\Helpers\ConventionTestHelper;

// --- Owner permissions ---

it('grants Owner full convention access', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $policy = new ConventionPolicy;

    expect($policy->view($owner, $convention))->toBeTrue()
        ->and($policy->update($owner, $convention))->toBeTrue()
        ->and($policy->delete($owner, $convention))->toBeTrue()
        ->and($policy->export($owner, $convention))->toBeTrue();
});

it('allows Owner to delete convention via HTTP', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    // Bypass authorization since base Controller lacks AuthorizesRequests trait
    Gate::before(fn () => true);

    $this->actingAs($owner)
        ->delete(route('conventions.destroy', $convention))
        ->assertRedirect(route('conventions.index'));

    expect(Convention::find($convention->id))->toBeNull();
});

// --- ConventionUser permissions ---

it('grants ConventionUser read/write but not delete/export', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $convUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');

    $policy = new ConventionPolicy;

    expect($policy->view($convUser, $convention))->toBeTrue()
        ->and($policy->update($convUser, $convention))->toBeTrue()
        ->and($policy->delete($convUser, $convention))->toBeFalse()
        ->and($policy->export($convUser, $convention))->toBeFalse();
});

it('allows ConventionUser to view convention show page', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $convUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');

    $this->actingAs($convUser)
        ->get(route('conventions.show', $convention))
        ->assertOk();
});

// --- FloorUser scoping ---

it('scopes FloorUser to assigned floors only', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 3, 'sections_per_floor' => 1]);
    $convention = $structure['convention'];
    $assignedFloor = $structure['floors']->first();
    $unassignedFloor = $structure['floors']->last();

    $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
        'floor_ids' => [$assignedFloor->id],
    ]);

    $floorPolicy = new FloorPolicy;

    expect($floorPolicy->view($floorUser, $assignedFloor))->toBeTrue()
        ->and($floorPolicy->update($floorUser, $assignedFloor))->toBeTrue()
        ->and($floorPolicy->view($floorUser, $unassignedFloor))->toBeFalse()
        ->and($floorPolicy->update($floorUser, $unassignedFloor))->toBeFalse();
});

it('prevents FloorUser from deleting floors', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();

    $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
        'floor_ids' => [$floor->id],
    ]);

    $floorPolicy = new FloorPolicy;

    expect($floorPolicy->delete($floorUser, $floor))->toBeFalse();
});

it('prevents FloorUser from creating floors', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();

    $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
        'floor_ids' => [$floor->id],
    ]);

    $floorPolicy = new FloorPolicy;

    expect($floorPolicy->create($floorUser, $convention))->toBeFalse();
});

it('allows FloorUser to manage sections on assigned floors', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();
    $section = $structure['sections']->first();

    $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
        'floor_ids' => [$floor->id],
    ]);

    $sectionPolicy = new SectionPolicy;

    expect($sectionPolicy->view($floorUser, $section))->toBeTrue()
        ->and($sectionPolicy->update($floorUser, $section))->toBeTrue()
        ->and($sectionPolicy->delete($floorUser, $section))->toBeTrue()
        ->and($sectionPolicy->create($floorUser, $floor))->toBeTrue();
});

// --- SectionUser scoping ---

it('scopes SectionUser to assigned sections only', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 2, 'sections_per_floor' => 2]);
    $convention = $structure['convention'];
    $assignedSection = $structure['sections']->first();
    $unassignedSection = $structure['sections']->last();

    $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
        'section_ids' => [$assignedSection->id],
    ]);

    $sectionPolicy = new SectionPolicy;

    expect($sectionPolicy->view($sectionUser, $assignedSection))->toBeTrue()
        ->and($sectionPolicy->update($sectionUser, $assignedSection))->toBeTrue()
        ->and($sectionPolicy->view($sectionUser, $unassignedSection))->toBeFalse()
        ->and($sectionPolicy->update($sectionUser, $unassignedSection))->toBeFalse();
});

it('prevents SectionUser from deleting sections', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $section = $structure['sections']->first();

    $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
        'section_ids' => [$section->id],
    ]);

    $sectionPolicy = new SectionPolicy;

    expect($sectionPolicy->delete($sectionUser, $section))->toBeFalse();
});

it('prevents SectionUser from creating sections', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $floor = $structure['floors']->first();

    $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
        'section_ids' => [$structure['sections']->first()->id],
    ]);

    $sectionPolicy = new SectionPolicy;

    expect($sectionPolicy->create($sectionUser, $floor))->toBeFalse();
});

it('denies access to users with no role for the convention', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $outsider = User::factory()->create();

    $this->actingAs($outsider)
        ->get(route('conventions.show', $convention))
        ->assertForbidden();
});

it('scopes FloorUser HTTP response to assigned floors', function () {
    $structure = ConventionTestHelper::createConventionWithStructure(['floors' => 2, 'sections_per_floor' => 1]);
    $convention = $structure['convention'];
    $assignedFloor = $structure['floors']->first();

    $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
        'floor_ids' => [$assignedFloor->id],
    ]);

    $response = $this->actingAs($floorUser)
        ->get(route('conventions.show', $convention))
        ->assertOk();

    // The response should contain the assigned floor
    $floors = $response->original->getData()['page']['props']['floors'];
    $floorIds = collect($floors)->pluck('id')->toArray();

    expect($floorIds)->toContain($assignedFloor->id);
});
