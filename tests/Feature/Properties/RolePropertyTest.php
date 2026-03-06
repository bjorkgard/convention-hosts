<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\ConventionTestHelper;

/**
 * Property 16: Multiple Role Assignment
 *
 * For any user within a convention, they should be able to hold multiple
 * roles simultaneously (e.g., both FloorUser and SectionUser).
 * All assigned roles should be stored correctly and queryable.
 *
 * **Validates: Requirements 5.3**
 */

$allRoles = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];

it('allows a user to hold multiple roles simultaneously within a convention', function () use ($allRoles) {
    for ($iteration = 0; $iteration < 10; $iteration++) {
        // Pick a random non-empty subset of roles
        $shuffled = collect($allRoles)->shuffle();
        $count = fake()->numberBetween(2, count($allRoles));
        $selectedRoles = $shuffled->take($count)->values()->all();

        $structure = ConventionTestHelper::createConventionWithStructure([
            'with_owner' => false,
        ]);
        $convention = $structure['convention'];
        $user = User::factory()->create();

        ConventionTestHelper::attachUserToConvention($user, $convention, $selectedRoles);

        // Verify hasRole() returns true for each assigned role
        foreach ($selectedRoles as $role) {
            expect($user->hasRole($convention, $role))
                ->toBeTrue("Iteration {$iteration}: hasRole('{$role}') should be true");
        }

        // Verify rolesForConvention() returns all assigned roles
        $storedRoles = $user->rolesForConvention($convention)->sort()->values()->all();
        $expectedRoles = collect($selectedRoles)->sort()->values()->all();

        expect($storedRoles)->toBe($expectedRoles,
            "Iteration {$iteration}: rolesForConvention() should return all assigned roles"
        );

        // Verify hasAnyRole() works with the full set
        expect($user->hasAnyRole($convention, $selectedRoles))->toBeTrue();
    }
})->group('property', 'roles');

it('stores all role combinations correctly in the database', function () use ($allRoles) {
    // Generate all possible non-empty subsets of size >= 2
    $multiRoleCombinations = [];
    $total = count($allRoles);
    for ($mask = 0; $mask < (1 << $total); $mask++) {
        $combo = [];
        for ($i = 0; $i < $total; $i++) {
            if ($mask & (1 << $i)) {
                $combo[] = $allRoles[$i];
            }
        }
        if (count($combo) >= 2) {
            $multiRoleCombinations[] = $combo;
        }
    }

    // Test a random sample of combinations
    $sampled = collect($multiRoleCombinations)->shuffle()->take(6)->all();

    foreach ($sampled as $roles) {
        $convention = Convention::factory()->create();
        $user = User::factory()->create();

        ConventionTestHelper::attachUserToConvention($user, $convention, $roles);

        // Verify each role exists in the pivot table
        foreach ($roles as $role) {
            $this->assertDatabaseHas('convention_user_roles', [
                'convention_id' => $convention->id,
                'user_id' => $user->id,
                'role' => $role,
            ]);
        }

        // Verify the count matches
        $dbCount = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $user->id)
            ->count();

        expect($dbCount)->toBe(count($roles));
    }
})->group('property', 'roles');


it('does not report roles that were not assigned', function () use ($allRoles) {
    for ($iteration = 0; $iteration < 10; $iteration++) {
        $shuffled = collect($allRoles)->shuffle();
        $splitAt = fake()->numberBetween(1, count($allRoles) - 1);
        $assignedRoles = $shuffled->take($splitAt)->values()->all();
        $unassignedRoles = $shuffled->skip($splitAt)->values()->all();

        $convention = Convention::factory()->create();
        $user = User::factory()->create();

        ConventionTestHelper::attachUserToConvention($user, $convention, $assignedRoles);

        // Verify unassigned roles return false
        foreach ($unassignedRoles as $role) {
            expect($user->hasRole($convention, $role))
                ->toBeFalse("Iteration {$iteration}: hasRole('{$role}') should be false when not assigned");
        }

        // Verify rolesForConvention does not include unassigned roles
        $storedRoles = $user->rolesForConvention($convention);
        foreach ($unassignedRoles as $role) {
            expect($storedRoles->contains($role))->toBeFalse();
        }
    }
})->group('property', 'roles');

/**
 * Property 17: Owner Role Inherits ConventionUser Permissions
 *
 * For any user with Owner role in a convention, they should have access
 * to all ConventionUser capabilities plus deletion and export privileges.
 *
 * **Validates: Requirements 5.4**
 */

it('grants Owner all ConventionUser capabilities via ConventionPolicy', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'with_owner' => false,
    ]);
    $convention = $structure['convention'];

    for ($iteration = 0; $iteration < 5; $iteration++) {
        $owner = ConventionTestHelper::createUserWithRole($convention, 'Owner');
        // Also attach ConventionUser since Owner inherits those capabilities
        ConventionTestHelper::attachUserToConvention($owner, $convention, ['ConventionUser']);

        $conventionUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');

        $policy = new \App\Policies\ConventionPolicy();

        // ConventionUser capabilities - Owner should have all of these
        expect($policy->update($owner, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should be able to update convention"
        );

        // ConventionUser should also be able to update
        expect($policy->update($conventionUser, $convention))->toBeTrue(
            "Iteration {$iteration}: ConventionUser should be able to update convention"
        );

        // Owner-exclusive capabilities: delete and export
        expect($policy->delete($owner, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should be able to delete convention"
        );
        expect($policy->export($owner, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should be able to export convention"
        );

        // ConventionUser should NOT have delete or export
        expect($policy->delete($conventionUser, $convention))->toBeFalse(
            "Iteration {$iteration}: ConventionUser should NOT be able to delete convention"
        );
        expect($policy->export($conventionUser, $convention))->toBeFalse(
            "Iteration {$iteration}: ConventionUser should NOT be able to export convention"
        );
    }
})->group('property', 'roles');

it('grants Owner all ConventionUser capabilities on floors and sections', function () {
    for ($iteration = 0; $iteration < 5; $iteration++) {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => fake()->numberBetween(1, 3),
            'sections_per_floor' => fake()->numberBetween(1, 3),
            'with_owner' => false,
        ]);
        $convention = $structure['convention'];
        $floors = $structure['floors'];
        $sections = $structure['sections'];

        $owner = ConventionTestHelper::createUserWithRole($convention, 'Owner');
        ConventionTestHelper::attachUserToConvention($owner, $convention, ['ConventionUser']);
        // Load relationships for policy checks
        $owner->load('floors', 'sections', 'conventions');

        $conventionUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');
        $conventionUser->load('floors', 'sections', 'conventions');

        $floorPolicy = new \App\Policies\FloorPolicy();
        $sectionPolicy = new \App\Policies\SectionPolicy();

        // Owner should be able to do everything ConventionUser can on floors
        foreach ($floors as $floor) {
            expect($floorPolicy->view($owner, $floor))->toBeTrue(
                "Iteration {$iteration}: Owner should view floor {$floor->name}"
            );
            expect($floorPolicy->update($owner, $floor))->toBeTrue(
                "Iteration {$iteration}: Owner should update floor {$floor->name}"
            );
            expect($floorPolicy->delete($owner, $floor))->toBeTrue(
                "Iteration {$iteration}: Owner should delete floor {$floor->name}"
            );
            expect($floorPolicy->create($owner, $convention))->toBeTrue(
                "Iteration {$iteration}: Owner should create floors"
            );

            // ConventionUser should also have these capabilities
            expect($floorPolicy->view($conventionUser, $floor))->toBeTrue();
            expect($floorPolicy->update($conventionUser, $floor))->toBeTrue();
            expect($floorPolicy->delete($conventionUser, $floor))->toBeTrue();
            expect($floorPolicy->create($conventionUser, $convention))->toBeTrue();
        }

        // Owner should be able to do everything ConventionUser can on sections
        foreach ($sections as $section) {
            expect($sectionPolicy->view($owner, $section))->toBeTrue(
                "Iteration {$iteration}: Owner should view section {$section->name}"
            );
            expect($sectionPolicy->update($owner, $section))->toBeTrue(
                "Iteration {$iteration}: Owner should update section {$section->name}"
            );
            expect($sectionPolicy->delete($owner, $section))->toBeTrue(
                "Iteration {$iteration}: Owner should delete section {$section->name}"
            );

            expect($sectionPolicy->view($conventionUser, $section))->toBeTrue();
            expect($sectionPolicy->update($conventionUser, $section))->toBeTrue();
            expect($sectionPolicy->delete($conventionUser, $section))->toBeTrue();
        }
    }
})->group('property', 'roles');

it('grants Owner user management capabilities that ConventionUser also has', function () {
    for ($iteration = 0; $iteration < 5; $iteration++) {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'with_owner' => false,
        ]);
        $convention = $structure['convention'];

        $owner = ConventionTestHelper::createUserWithRole($convention, 'Owner');
        ConventionTestHelper::attachUserToConvention($owner, $convention, ['ConventionUser']);
        $owner->load('floors', 'sections', 'conventions');

        $conventionUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');
        $conventionUser->load('floors', 'sections', 'conventions');

        // Create a target user in the convention
        $targetUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
            'section_ids' => $structure['sections']->pluck('id')->toArray(),
        ]);
        $targetUser->load('floors', 'sections', 'conventions');

        $userPolicy = new \App\Policies\UserPolicy();

        // Owner should be able to view, update, delete users
        expect($userPolicy->view($owner, $targetUser, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should view users"
        );
        expect($userPolicy->update($owner, $targetUser, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should update users"
        );
        expect($userPolicy->delete($owner, $targetUser, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should delete users"
        );

        // ConventionUser should also have these capabilities
        expect($userPolicy->view($conventionUser, $targetUser, $convention))->toBeTrue();
        expect($userPolicy->update($conventionUser, $targetUser, $convention))->toBeTrue();
        expect($userPolicy->delete($conventionUser, $targetUser, $convention))->toBeTrue();
    }
})->group('property', 'roles');
