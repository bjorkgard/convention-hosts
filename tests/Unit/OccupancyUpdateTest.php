<?php

use App\Actions\UpdateOccupancyAction;
use Illuminate\Support\Facades\Gate;
use Tests\Helpers\ConventionTestHelper;

it('updates occupancy via dropdown percentage', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $user = $structure['owner'];
    $action = new UpdateOccupancyAction;

    $updated = $action->execute($section, ['occupancy' => 75], $user);

    expect($updated->occupancy)->toBe(75);
});

it('calculates available seats when occupancy percentage is set', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $section->update(['number_of_seats' => 200]);
    $section->refresh();
    $user = $structure['owner'];
    $action = new UpdateOccupancyAction;

    $updated = $action->execute($section, ['occupancy' => 50], $user);

    expect($updated->occupancy)->toBe(50)
        ->and($updated->available_seats)->toBe(100);
});

it('sets occupancy to 100 percent via full button', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $user = $structure['owner'];
    $action = new UpdateOccupancyAction;

    $updated = $action->execute($section, ['occupancy' => 100], $user);

    expect($updated->occupancy)->toBe(100)
        ->and($updated->available_seats)->toBe(0);
});

it('calculates occupancy from available seats', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $section->update(['number_of_seats' => 100]);
    $section->refresh();
    $user = $structure['owner'];
    $action = new UpdateOccupancyAction;

    $updated = $action->execute($section, ['available_seats' => 25], $user);

    expect($updated->occupancy)->toBe(75)
        ->and($updated->available_seats)->toBe(25);
});

it('clamps occupancy between 0 and 100', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $section->update(['number_of_seats' => 100]);
    $section->refresh();
    $user = $structure['owner'];
    $action = new UpdateOccupancyAction;

    // More available seats than total seats should clamp to 0% occupancy
    $updated = $action->execute($section, ['available_seats' => 150], $user);

    expect($updated->occupancy)->toBeGreaterThanOrEqual(0)
        ->and($updated->occupancy)->toBeLessThanOrEqual(100);
});

it('records metadata on occupancy update', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $user = $structure['owner'];
    $action = new UpdateOccupancyAction;

    $updated = $action->execute($section, ['occupancy' => 50], $user);

    expect($updated->last_occupancy_updated_by)->toBe($user->id)
        ->and($updated->last_occupancy_updated_at)->not->toBeNull();
});

it('updates occupancy via HTTP endpoint', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $owner = $structure['owner'];

    // Bypass authorization since base Controller lacks AuthorizesRequests trait
    Gate::before(fn () => true);

    $this->actingAs($owner)
        ->patch(route('sections.updateOccupancy', $section), [
            'occupancy' => 75,
        ])
        ->assertRedirect();

    $section->refresh();
    expect($section->occupancy)->toBe(75);
});

it('sets section to full via HTTP endpoint', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $section = $structure['sections']->first();
    $owner = $structure['owner'];

    // Bypass authorization since base Controller lacks AuthorizesRequests trait
    Gate::before(fn () => true);

    $this->actingAs($owner)
        ->post(route('sections.setFull', $section))
        ->assertRedirect();

    $section->refresh();
    expect($section->occupancy)->toBe(100);
});
