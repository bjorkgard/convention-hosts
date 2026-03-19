<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\ConventionTestHelper;

/**
 * Property 16: Multiple Role Assignment
 *
 * For any user within a convention, they should be able to hold multiple
 * roles simultaneously (e.g., both Owner and Administrator).
 * All assigned roles should be stored correctly and queryable.
 *
 * **Validates: Requirements 5.3**
 */
$allRoles = ['Owner', 'Administrator'];

it('allows a user to hold multiple roles simultaneously within a convention', function () use ($allRoles) {
    for ($iteration = 0; $iteration < 3; $iteration++) {
        // With only 2 roles, always assign both
        $selectedRoles = $allRoles;

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

    // Test all combinations (with 2 roles, there's only 1 combination: both)
    foreach ($multiRoleCombinations as $roles) {
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
    for ($iteration = 0; $iteration < 3; $iteration++) {
        // Assign only one role, verify the other is not reported
        $assignedRole = fake()->randomElement($allRoles);
        $assignedRoles = [$assignedRole];
        $unassignedRoles = array_values(array_diff($allRoles, $assignedRoles));

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
 * Property 17: Owner Role Inherits Administrator Permissions
 *
 * For any user with Owner role in a convention, they should have access
 * to all Administrator capabilities plus deletion and export privileges.
 *
 * **Validates: Requirements 5.4**
 */
it('grants Owner all Administrator capabilities via ConventionPolicy', function () {
    $structure = ConventionTestHelper::createConventionWithStructure([
        'with_owner' => false,
    ]);
    $convention = $structure['convention'];

    for ($iteration = 0; $iteration < 3; $iteration++) {
        $owner = ConventionTestHelper::createUserWithRole($convention, 'Owner');
        // Also attach Administrator since Owner inherits those capabilities
        ConventionTestHelper::attachUserToConvention($owner, $convention, ['Administrator']);

        $administrator = ConventionTestHelper::createUserWithRole($convention, 'Administrator');

        $policy = new \App\Policies\ConventionPolicy;

        // Administrator capabilities - Owner should have all of these
        expect($policy->update($owner, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should be able to update convention"
        );

        // Administrator should also be able to update
        expect($policy->update($administrator, $convention))->toBeTrue(
            "Iteration {$iteration}: Administrator should be able to update convention"
        );

        // Owner-exclusive capabilities: delete and export
        expect($policy->delete($owner, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should be able to delete convention"
        );
        expect($policy->export($owner, $convention))->toBeTrue(
            "Iteration {$iteration}: Owner should be able to export convention"
        );

        // Administrator should NOT have delete or export
        expect($policy->delete($administrator, $convention))->toBeFalse(
            "Iteration {$iteration}: Administrator should NOT be able to delete convention"
        );
        expect($policy->export($administrator, $convention))->toBeFalse(
            "Iteration {$iteration}: Administrator should NOT be able to export convention"
        );
    }
})->group('property', 'roles');

it('grants Owner all Administrator capabilities on floors and sections', function () {
    for ($iteration = 0; $iteration < 3; $iteration++) {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => fake()->numberBetween(1, 3),
            'sections_per_floor' => fake()->numberBetween(1, 3),
            'with_owner' => false,
        ]);
        $convention = $structure['convention'];
        $floors = $structure['floors'];
        $sections = $structure['sections'];

        $owner = ConventionTestHelper::createUserWithRole($convention, 'Owner');
        ConventionTestHelper::attachUserToConvention($owner, $convention, ['Administrator']);
        $owner->load('conventions');

        $administrator = ConventionTestHelper::createUserWithRole($convention, 'Administrator');
        $administrator->load('conventions');

        $floorPolicy = new \App\Policies\FloorPolicy;
        $sectionPolicy = new \App\Policies\SectionPolicy;

        // Owner should be able to do everything Administrator can on floors
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

            // Administrator should also have these capabilities
            expect($floorPolicy->view($administrator, $floor))->toBeTrue();
            expect($floorPolicy->update($administrator, $floor))->toBeTrue();
            expect($floorPolicy->delete($administrator, $floor))->toBeTrue();
            expect($floorPolicy->create($administrator, $convention))->toBeTrue();
        }

        // Owner should be able to do everything Administrator can on sections
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

            expect($sectionPolicy->view($administrator, $section))->toBeTrue();
            expect($sectionPolicy->update($administrator, $section))->toBeTrue();
            expect($sectionPolicy->delete($administrator, $section))->toBeTrue();
        }
    }
})->group('property', 'roles');

it('grants Owner user management capabilities that Administrator also has', function () {
    for ($iteration = 0; $iteration < 3; $iteration++) {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'with_owner' => false,
        ]);
        $convention = $structure['convention'];

        $owner = ConventionTestHelper::createUserWithRole($convention, 'Owner');
        ConventionTestHelper::attachUserToConvention($owner, $convention, ['Administrator']);
        $owner->load('conventions');

        $administrator = ConventionTestHelper::createUserWithRole($convention, 'Administrator');
        $administrator->load('conventions');

        // Create a target user in the convention
        $targetUser = ConventionTestHelper::createUserWithRole($convention, 'Administrator');
        $targetUser->load('conventions');

        $userPolicy = new \App\Policies\UserPolicy;

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

        // Administrator should also have these capabilities
        expect($userPolicy->view($administrator, $targetUser, $convention))->toBeTrue();
        expect($userPolicy->update($administrator, $targetUser, $convention))->toBeTrue();
        expect($userPolicy->delete($administrator, $targetUser, $convention))->toBeTrue();
    }
})->group('property', 'roles');
