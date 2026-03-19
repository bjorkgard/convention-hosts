<?php

use App\Models\Convention;
use App\Models\User;
use Tests\Helpers\ConventionTestHelper;

it('creates a convention with default structure', function () {
    $result = ConventionTestHelper::createConventionWithStructure();

    expect($result['convention'])->toBeInstanceOf(Convention::class)
        ->and($result['floors'])->toHaveCount(2)
        ->and($result['sections'])->toHaveCount(6)
        ->and($result['owner'])->toBeInstanceOf(User::class);

    // Verify owner has correct roles
    $roles = $result['owner']->rolesForConvention($result['convention']);
    expect($roles)->toContain('Owner')
        ->and($roles)->toContain('Administrator');

    // Verify sections belong to correct floors
    foreach ($result['floors'] as $floor) {
        $floorSections = $result['sections']->where('floor_id', $floor->id);
        expect($floorSections)->toHaveCount(3);
    }
});

it('creates a convention with custom structure', function () {
    $result = ConventionTestHelper::createConventionWithStructure([
        'floors' => 3,
        'sections_per_floor' => 5,
        'convention_attributes' => ['name' => 'Test Convention'],
    ]);

    expect($result['convention']->name)->toBe('Test Convention')
        ->and($result['floors'])->toHaveCount(3)
        ->and($result['sections'])->toHaveCount(15);
});

it('creates a convention without owner', function () {
    $result = ConventionTestHelper::createConventionWithStructure([
        'with_owner' => false,
    ]);

    expect($result['owner'])->toBeNull();
});

it('creates a convention with a specific owner', function () {
    $existingUser = User::factory()->create();

    $result = ConventionTestHelper::createConventionWithStructure([
        'owner' => $existingUser,
    ]);

    expect($result['owner']->id)->toBe($existingUser->id);
    expect($result['owner']->hasRole($result['convention'], 'Owner'))->toBeTrue();
});

it('creates a user with Owner role', function () {
    $convention = Convention::factory()->create();

    $user = ConventionTestHelper::createUserWithRole($convention, 'Owner');

    expect($user->hasRole($convention, 'Owner'))->toBeTrue();
    expect($user->conventions->contains($convention))->toBeTrue();
});

it('reuses an existing user when provided', function () {
    $convention = Convention::factory()->create();
    $existingUser = User::factory()->create();

    $user = ConventionTestHelper::createUserWithRole($convention, 'Administrator', [
        'user' => $existingUser,
    ]);

    expect($user->id)->toBe($existingUser->id);
    expect($user->hasRole($convention, 'Administrator'))->toBeTrue();
});

it('creates an authenticated user setup', function () {
    $result = ConventionTestHelper::createConventionWithStructure(['with_owner' => false]);

    $auth = ConventionTestHelper::createAuthenticatedUser($result['convention'], 'Owner');

    expect($auth['user'])->toBeInstanceOf(User::class)
        ->and($auth['convention']->id)->toBe($result['convention']->id)
        ->and($auth['user']->hasRole($auth['convention'], 'Owner'))->toBeTrue();
});

it('can use authenticated user with actingAs for HTTP tests', function () {
    $result = ConventionTestHelper::createConventionWithStructure();

    $this->actingAs($result['owner'])
        ->get('/conventions')
        ->assertStatus(200);
});
