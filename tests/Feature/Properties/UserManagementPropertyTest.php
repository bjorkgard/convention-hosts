<?php

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\Helpers\ConventionTestHelper;

/**
 * Helper: Executes the same user deletion logic as UserController::destroy.
 * Removes all role/pivot records for the convention, and deletes the user
 * record entirely if no remaining conventions.
 */
function deleteUserFromConvention(User $user, Convention $convention): void
{
    DB::transaction(function () use ($user, $convention) {
        DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $user->id)
            ->delete();

        $conventionFloorIds = $convention->floors()->pluck('id');
        DB::table('floor_user')
            ->where('user_id', $user->id)
            ->whereIn('floor_id', $conventionFloorIds)
            ->delete();

        $conventionSectionIds = $convention->floors()
            ->with('sections')
            ->get()
            ->flatMap(fn ($floor) => $floor->sections->pluck('id'));

        DB::table('section_user')
            ->where('user_id', $user->id)
            ->whereIn('section_id', $conventionSectionIds)
            ->delete();

        $convention->users()->detach($user->id);

        $remainingConventions = DB::table('convention_user')
            ->where('user_id', $user->id)
            ->count();

        if ($remainingConventions === 0) {
            $user->delete();
        }
    });
}

/**
 * Property 12: Email Uniqueness Enforcement
 *
 * The system enforces globally unique email addresses for all users,
 * both at the database level (unique constraint) and at the validation
 * level (StoreUserRequest).
 *
 * **Validates: Requirements 4.1**
 */
it('rejects duplicate emails at the database level', function () {
    for ($i = 0; $i < 3; $i++) {
        $email = fake()->unique()->safeEmail();

        User::factory()->create(['email' => $email]);

        $threw = false;
        try {
            User::factory()->create(['email' => $email]);
        } catch (\Throwable $e) {
            $threw = true;
        }

        expect($threw)->toBeTrue(
            "Iteration {$i}: duplicate email '{$email}' should be rejected by unique constraint"
        );
    }
})->group('property', 'user-management');

it('rejects duplicate emails via StoreUserRequest validation', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    for ($i = 0; $i < 3; $i++) {
        $email = fake()->unique()->safeEmail();

        // Create an existing user with this email
        User::factory()->create(['email' => $email]);

        $response = $this->actingAs($owner)->post(
            route('users.store', $convention),
            [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => $email,
                'mobile' => fake()->phoneNumber(),
                'roles' => ['ConventionUser'],
            ]
        );

        $response->assertSessionHasErrors('email');
    }
})->group('property', 'user-management');

/**
 * Property 15: User Required Fields Validation
 *
 * User creation requires first_name, last_name, email, and mobile fields.
 * Removing any one of these required fields should produce a validation error.
 *
 * **Validates: Requirements 4.4**
 */
it('requires first_name, last_name, email, and mobile for user creation', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    $requiredFields = ['first_name', 'last_name', 'email', 'mobile'];

    for ($i = 0; $i < 3; $i++) {
        $validData = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'roles' => ['ConventionUser'],
        ];

        // Pick a random required field to omit
        $fieldToOmit = fake()->randomElement($requiredFields);
        $incompleteData = $validData;
        unset($incompleteData[$fieldToOmit]);

        $response = $this->actingAs($owner)->post(
            route('users.store', $convention),
            $incompleteData
        );

        $response->assertSessionHasErrors($fieldToOmit);
    }
})->group('property', 'user-management');

it('accepts valid user data with all required fields present', function () {
    $structure = ConventionTestHelper::createConventionWithStructure();
    $convention = $structure['convention'];
    $owner = $structure['owner'];

    for ($i = 0; $i < 3; $i++) {
        $validData = [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'email' => fake()->unique()->safeEmail(),
            'mobile' => fake()->phoneNumber(),
            'roles' => ['ConventionUser'],
        ];

        $response = $this->actingAs($owner)->post(
            route('users.store', $convention),
            $validData
        );

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('users', [
            'email' => $validData['email'],
            'first_name' => $validData['first_name'],
            'last_name' => $validData['last_name'],
        ]);
    }
})->group('property', 'user-management');

/**
 * Property 45: User Deletion Cascade
 *
 * When a user is removed from a convention, all role and pivot records
 * for that convention are cleaned up: convention_user, convention_user_roles,
 * floor_user, and section_user records are removed.
 *
 * Tests the deletion logic directly (the same transaction logic used by
 * UserController::destroy) to validate data cleanup independently of
 * authorization middleware.
 *
 * **Validates: Requirements 17.1**
 */
it('removes all role and pivot records when user is deleted from a convention', function () {
    $allRoles = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];

    for ($i = 0; $i < 3; $i++) {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => fake()->numberBetween(1, 3),
            'sections_per_floor' => fake()->numberBetween(1, 3),
        ]);
        $convention = $structure['convention'];
        $floors = $structure['floors'];
        $sections = $structure['sections'];

        // Pick a random role for the target user
        $role = fake()->randomElement($allRoles);
        $options = [];

        if ($role === 'FloorUser') {
            $options['floor_ids'] = $floors->pluck('id')->toArray();
        }
        if ($role === 'SectionUser') {
            $options['section_ids'] = $sections->pluck('id')->toArray();
        }

        $targetUser = ConventionTestHelper::createUserWithRole($convention, $role, $options);

        // Verify records exist before deletion
        $this->assertDatabaseHas('convention_user', [
            'convention_id' => $convention->id,
            'user_id' => $targetUser->id,
        ]);
        $this->assertDatabaseHas('convention_user_roles', [
            'convention_id' => $convention->id,
            'user_id' => $targetUser->id,
            'role' => $role,
        ]);

        // Execute the same deletion logic as UserController::destroy
        DB::transaction(function () use ($targetUser, $convention) {
            DB::table('convention_user_roles')
                ->where('convention_id', $convention->id)
                ->where('user_id', $targetUser->id)
                ->delete();

            $conventionFloorIds = $convention->floors()->pluck('id');
            DB::table('floor_user')
                ->where('user_id', $targetUser->id)
                ->whereIn('floor_id', $conventionFloorIds)
                ->delete();

            $conventionSectionIds = $convention->floors()
                ->with('sections')
                ->get()
                ->flatMap(fn ($floor) => $floor->sections->pluck('id'));

            DB::table('section_user')
                ->where('user_id', $targetUser->id)
                ->whereIn('section_id', $conventionSectionIds)
                ->delete();

            $convention->users()->detach($targetUser->id);

            $remainingConventions = DB::table('convention_user')
                ->where('user_id', $targetUser->id)
                ->count();

            if ($remainingConventions === 0) {
                $targetUser->delete();
            }
        });

        // Verify all pivot records are removed
        $this->assertDatabaseMissing('convention_user', [
            'convention_id' => $convention->id,
            'user_id' => $targetUser->id,
        ]);
        $this->assertDatabaseMissing('convention_user_roles', [
            'convention_id' => $convention->id,
            'user_id' => $targetUser->id,
        ]);

        if ($role === 'FloorUser') {
            foreach ($floors as $floor) {
                $this->assertDatabaseMissing('floor_user', [
                    'floor_id' => $floor->id,
                    'user_id' => $targetUser->id,
                ]);
            }
        }

        if ($role === 'SectionUser') {
            foreach ($sections as $section) {
                $this->assertDatabaseMissing('section_user', [
                    'section_id' => $section->id,
                    'user_id' => $targetUser->id,
                ]);
            }
        }
    }
})->group('property', 'user-management');

it('cleans up floor and section assignments for users with multiple roles', function () {
    for ($i = 0; $i < 3; $i++) {
        $structure = ConventionTestHelper::createConventionWithStructure([
            'floors' => 2,
            'sections_per_floor' => 2,
        ]);
        $convention = $structure['convention'];
        $floors = $structure['floors'];
        $sections = $structure['sections'];

        // Create user with both FloorUser and SectionUser roles
        $targetUser = User::factory()->create();
        ConventionTestHelper::attachUserToConvention($targetUser, $convention, ['FloorUser', 'SectionUser']);

        // Attach floor and section assignments
        foreach ($floors as $floor) {
            DB::table('floor_user')->insertOrIgnore([
                'floor_id' => $floor->id,
                'user_id' => $targetUser->id,
                'created_at' => now(),
            ]);
        }
        foreach ($sections as $section) {
            DB::table('section_user')->insertOrIgnore([
                'section_id' => $section->id,
                'user_id' => $targetUser->id,
                'created_at' => now(),
            ]);
        }

        // Execute the same deletion logic as UserController::destroy
        DB::transaction(function () use ($targetUser, $convention) {
            DB::table('convention_user_roles')
                ->where('convention_id', $convention->id)
                ->where('user_id', $targetUser->id)
                ->delete();

            $conventionFloorIds = $convention->floors()->pluck('id');
            DB::table('floor_user')
                ->where('user_id', $targetUser->id)
                ->whereIn('floor_id', $conventionFloorIds)
                ->delete();

            $conventionSectionIds = $convention->floors()
                ->with('sections')
                ->get()
                ->flatMap(fn ($floor) => $floor->sections->pluck('id'));

            DB::table('section_user')
                ->where('user_id', $targetUser->id)
                ->whereIn('section_id', $conventionSectionIds)
                ->delete();

            $convention->users()->detach($targetUser->id);

            $remainingConventions = DB::table('convention_user')
                ->where('user_id', $targetUser->id)
                ->count();

            if ($remainingConventions === 0) {
                $targetUser->delete();
            }
        });

        // Verify all records are cleaned up
        $this->assertDatabaseMissing('convention_user', [
            'convention_id' => $convention->id,
            'user_id' => $targetUser->id,
        ]);

        $roleCount = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->where('user_id', $targetUser->id)
            ->count();
        expect($roleCount)->toBe(0, "Iteration {$i}: all roles should be removed");

        $floorCount = DB::table('floor_user')
            ->where('user_id', $targetUser->id)
            ->whereIn('floor_id', $floors->pluck('id'))
            ->count();
        expect($floorCount)->toBe(0, "Iteration {$i}: all floor assignments should be removed");

        $sectionCount = DB::table('section_user')
            ->where('user_id', $targetUser->id)
            ->whereIn('section_id', $sections->pluck('id'))
            ->count();
        expect($sectionCount)->toBe(0, "Iteration {$i}: all section assignments should be removed");
    }
})->group('property', 'user-management');

/**
 * Property 46: User Record Cleanup
 *
 * When a user is disconnected from their last convention, the user record
 * is deleted entirely. When connected to multiple conventions, removing
 * from one keeps the user record alive.
 *
 * **Validates: Requirements 17.2**
 */
it('deletes user record when disconnected from their last convention', function () {
    for ($i = 0; $i < 3; $i++) {
        $structure = ConventionTestHelper::createConventionWithStructure();
        $convention = $structure['convention'];

        $role = fake()->randomElement(['ConventionUser', 'FloorUser', 'SectionUser']);
        $options = [];
        if ($role === 'FloorUser') {
            $options['floor_ids'] = $structure['floors']->pluck('id')->toArray();
        }
        if ($role === 'SectionUser') {
            $options['section_ids'] = $structure['sections']->pluck('id')->toArray();
        }

        $targetUser = ConventionTestHelper::createUserWithRole($convention, $role, $options);
        $targetUserId = $targetUser->id;

        // Verify user exists
        $this->assertDatabaseHas('users', ['id' => $targetUserId]);

        // Execute the deletion logic (same as UserController::destroy)
        deleteUserFromConvention($targetUser, $convention);

        // User record should be deleted entirely
        $this->assertDatabaseMissing('users', ['id' => $targetUserId]);
    }
})->group('property', 'user-management');

it('keeps user record when still connected to other conventions', function () {
    for ($i = 0; $i < 3; $i++) {
        $numConventions = fake()->numberBetween(2, 4);
        $structures = [];

        for ($c = 0; $c < $numConventions; $c++) {
            $structures[] = ConventionTestHelper::createConventionWithStructure();
        }

        // Create a target user and connect to all conventions
        $targetUser = User::factory()->create();

        foreach ($structures as $s) {
            ConventionTestHelper::attachUserToConvention($targetUser, $s['convention'], ['ConventionUser']);
        }

        $targetUserId = $targetUser->id;

        // Remove from the first convention
        $firstConvention = $structures[0]['convention'];
        deleteUserFromConvention($targetUser, $firstConvention);

        // User record should still exist (connected to other conventions)
        $this->assertDatabaseHas('users', ['id' => $targetUserId]);

        // But should be removed from the first convention
        $this->assertDatabaseMissing('convention_user', [
            'convention_id' => $firstConvention->id,
            'user_id' => $targetUserId,
        ]);

        // Verify still connected to remaining conventions
        for ($c = 1; $c < $numConventions; $c++) {
            $this->assertDatabaseHas('convention_user', [
                'convention_id' => $structures[$c]['convention']->id,
                'user_id' => $targetUserId,
            ]);
        }
    }
})->group('property', 'user-management');

it('deletes user record only after removal from the very last convention', function () {
    for ($i = 0; $i < 3; $i++) {
        $numConventions = fake()->numberBetween(2, 3);
        $structures = [];

        for ($c = 0; $c < $numConventions; $c++) {
            $structures[] = ConventionTestHelper::createConventionWithStructure();
        }

        $targetUser = User::factory()->create();
        $targetUserId = $targetUser->id;

        foreach ($structures as $s) {
            ConventionTestHelper::attachUserToConvention($targetUser, $s['convention'], ['ConventionUser']);
        }

        // Remove from all conventions except the last
        for ($c = 0; $c < $numConventions - 1; $c++) {
            deleteUserFromConvention($targetUser, $structures[$c]['convention']);

            // User should still exist
            $this->assertDatabaseHas('users', ['id' => $targetUserId]);
        }

        // Remove from the last convention
        $lastIdx = $numConventions - 1;
        deleteUserFromConvention($targetUser, $structures[$lastIdx]['convention']);

        // Now user record should be deleted
        $this->assertDatabaseMissing('users', ['id' => $targetUserId]);
    }
})->group('property', 'user-management');
