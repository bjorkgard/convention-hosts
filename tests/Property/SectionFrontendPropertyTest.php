<?php

use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

// Feature: section-crud-management, Property 1: Add Section button visibility is determined by role
// Validates: Requirements 1.1, 1.2, 1.3, 1.4

describe('Property 1: Add Section button visibility by role', function () {
    it('shows Add Section button for Owner, ConventionUser, and FloorUser but hides it for SectionUser', function () {
        $rolesWithAccess = ['Owner', 'ConventionUser', 'FloorUser'];

        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => fake()->numberBetween(1, 3),
                'sections_per_floor' => fake()->numberBetween(1, 2),
            ]);
            $convention = $structure['convention'];
            $floor1 = $structure['floors']->first();

            // Pick a random role that should see the button
            $allowedRole = fake()->randomElement($rolesWithAccess);
            $options = $allowedRole === 'FloorUser'
                ? ['floor_ids' => [$floor1->id]]
                : [];

            $allowedUser = ConventionTestHelper::createUserWithRole($convention, $allowedRole, $options);

            $response = $this->actingAs($allowedUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();

            $props = $response->original->getData()['page']['props'];
            $userRoles = $props['userRoles'];

            $hasAllowedRole = ! empty(array_intersect($userRoles, $rolesWithAccess));
            expect($hasAllowedRole)->toBeTrue(
                "Iteration {$i}: User with role {$allowedRole} should have a role that enables Add Section button"
            );

            // Now test SectionUser — should NOT see the button
            $section = $structure['sections']->first();
            $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
                'section_ids' => [$section->id],
            ]);

            $response = $this->actingAs($sectionUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();

            $props = $response->original->getData()['page']['props'];
            $sectionUserRoles = $props['userRoles'];

            $sectionUserHasAllowedRole = ! empty(array_intersect($sectionUserRoles, $rolesWithAccess));
            expect($sectionUserHasAllowedRole)->toBeFalse(
                "Iteration {$i}: SectionUser-only should not have any role that enables Add Section button"
            );
        }
    });

    it('correctly reports roles for randomly generated role combinations', function () {
        $allRoles = ['Owner', 'ConventionUser', 'FloorUser', 'SectionUser'];
        $rolesWithAccess = ['Owner', 'ConventionUser', 'FloorUser'];

        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => 2,
                'sections_per_floor' => 1,
            ]);
            $convention = $structure['convention'];
            $floor = $structure['floors']->first();
            $section = $structure['sections']->first();

            $role = fake()->randomElement($allRoles);
            $options = match ($role) {
                'FloorUser' => ['floor_ids' => [$floor->id]],
                'SectionUser' => ['section_ids' => [$section->id]],
                default => [],
            };

            $user = ConventionTestHelper::createUserWithRole($convention, $role, $options);

            $response = $this->actingAs($user)
                ->get(route('floors.index', $convention));

            $response->assertOk();

            $props = $response->original->getData()['page']['props'];
            $userRoles = $props['userRoles'];

            $shouldSeeButton = in_array($role, $rolesWithAccess);
            $canSeeButton = ! empty(array_intersect($userRoles, $rolesWithAccess));

            expect($canSeeButton)->toBe($shouldSeeButton,
                "Iteration {$i}: Role '{$role}' — canAddSection should be ".($shouldSeeButton ? 'true' : 'false')
            );
        }
    });
})->group('property', 'section-crud', 'frontend');

// Feature: section-crud-management, Property 2: Floor selector shows exactly the authorized floors
// Validates: Requirements 2.4, 2.5, 2.6

describe('Property 2: Floor selector shows exactly the authorized floors', function () {
    it('Owner and ConventionUser see all convention floors', function () {
        for ($i = 0; $i < 3; $i++) {
            $floorCount = fake()->numberBetween(2, 5);
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => $floorCount,
                'sections_per_floor' => 1,
            ]);
            $convention = $structure['convention'];
            $allFloorIds = $structure['floors']->pluck('id')->sort()->values()->toArray();

            // Test Owner
            $response = $this->actingAs($structure['owner'])
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];
            $returnedFloorIds = collect($props['floors'])->pluck('id')->sort()->values()->toArray();

            expect($returnedFloorIds)->toBe($allFloorIds,
                "Iteration {$i}: Owner should see all {$floorCount} floors"
            );

            // Test ConventionUser
            $conventionUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');

            $response = $this->actingAs($conventionUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];
            $returnedFloorIds = collect($props['floors'])->pluck('id')->sort()->values()->toArray();

            expect($returnedFloorIds)->toBe($allFloorIds,
                "Iteration {$i}: ConventionUser should see all {$floorCount} floors"
            );
        }
    });

    it('FloorUser sees only assigned floors and userFloorIds matches', function () {
        for ($i = 0; $i < 3; $i++) {
            $totalFloors = fake()->numberBetween(3, 5);
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => $totalFloors,
                'sections_per_floor' => 1,
            ]);
            $convention = $structure['convention'];
            $allFloors = $structure['floors'];

            // Assign a random subset of floors (at least 1)
            $assignedCount = fake()->numberBetween(1, $totalFloors - 1);
            $assignedFloors = $allFloors->random($assignedCount);
            $assignedFloorIds = $assignedFloors->pluck('id')->sort()->values()->toArray();

            $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
                'floor_ids' => $assignedFloorIds,
            ]);

            $response = $this->actingAs($floorUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];

            // The page should only show the assigned floors (scoped by middleware)
            $returnedFloorIds = collect($props['floors'])->pluck('id')->sort()->values()->toArray();
            expect($returnedFloorIds)->toBe($assignedFloorIds,
                "Iteration {$i}: FloorUser should see only {$assignedCount} assigned floors out of {$totalFloors}"
            );

            // userFloorIds should contain the assigned floor IDs
            $userFloorIds = collect($props['userFloorIds'])->sort()->values()->toArray();
            expect(array_diff($assignedFloorIds, $userFloorIds))->toBeEmpty(
                "Iteration {$i}: userFloorIds should contain all assigned floor IDs"
            );
        }
    });

    it('SectionUser sees only floors containing assigned sections', function () {
        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => 3,
                'sections_per_floor' => 2,
            ]);
            $convention = $structure['convention'];
            $allSections = $structure['sections'];

            // Assign 1-2 random sections
            $assignedSections = $allSections->random(fake()->numberBetween(1, 2));
            $assignedSectionIds = $assignedSections->pluck('id')->toArray();
            $expectedFloorIds = $assignedSections->pluck('floor_id')->unique()->sort()->values()->toArray();

            $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
                'section_ids' => $assignedSectionIds,
            ]);

            $response = $this->actingAs($sectionUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];

            // SectionUser should only see floors that contain their assigned sections
            $returnedFloorIds = collect($props['floors'])->pluck('id')->sort()->values()->toArray();
            expect($returnedFloorIds)->toBe($expectedFloorIds,
                "Iteration {$i}: SectionUser should see only floors containing assigned sections"
            );

            // userSectionIds should contain the assigned section IDs
            $userSectionIds = $props['userSectionIds'];
            expect(array_diff($assignedSectionIds, $userSectionIds))->toBeEmpty(
                "Iteration {$i}: userSectionIds should contain all assigned section IDs"
            );
        }
    });
})->group('property', 'section-crud', 'frontend');

// Feature: section-crud-management, Property 8: Section action button visibility matches authorization
// Validates: Requirements 4.1, 5.1, 6.2, 6.3, 6.4

describe('Property 8: Section action button visibility matches authorization', function () {
    it('Owner and ConventionUser get userRoles that enable edit/delete on all sections', function () {
        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => fake()->numberBetween(2, 4),
                'sections_per_floor' => fake()->numberBetween(1, 3),
            ]);
            $convention = $structure['convention'];

            // Test Owner
            $response = $this->actingAs($structure['owner'])
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];

            expect(in_array('Owner', $props['userRoles']))->toBeTrue(
                "Iteration {$i}: Owner should have 'Owner' in userRoles"
            );

            // Test ConventionUser
            $conventionUser = ConventionTestHelper::createUserWithRole($convention, 'ConventionUser');

            $response = $this->actingAs($conventionUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];

            expect(in_array('ConventionUser', $props['userRoles']))->toBeTrue(
                "Iteration {$i}: ConventionUser should have 'ConventionUser' in userRoles"
            );
        }
    });

    it('FloorUser gets userFloorIds that enable edit/delete only on assigned floor sections', function () {
        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => 3,
                'sections_per_floor' => 2,
            ]);
            $convention = $structure['convention'];
            $floors = $structure['floors'];

            // Assign to 1 or 2 floors randomly
            $assignedCount = fake()->numberBetween(1, 2);
            $assignedFloors = $floors->random($assignedCount);
            $assignedFloorIds = $assignedFloors->pluck('id')->sort()->values()->toArray();
            $unassignedFloorIds = $floors->pluck('id')->diff($assignedFloorIds)->values()->toArray();

            $floorUser = ConventionTestHelper::createUserWithRole($convention, 'FloorUser', [
                'floor_ids' => $assignedFloorIds,
            ]);

            $response = $this->actingAs($floorUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];

            expect(in_array('FloorUser', $props['userRoles']))->toBeTrue(
                "Iteration {$i}: FloorUser should have 'FloorUser' in userRoles"
            );

            // userFloorIds should contain assigned floors but not unassigned
            $returnedFloorIds = $props['userFloorIds'];
            expect(array_diff($assignedFloorIds, $returnedFloorIds))->toBeEmpty(
                "Iteration {$i}: userFloorIds should contain all assigned floor IDs"
            );
            expect(array_intersect($unassignedFloorIds, $returnedFloorIds))->toBeEmpty(
                "Iteration {$i}: userFloorIds should NOT contain unassigned floor IDs"
            );
        }
    });

    it('SectionUser gets userSectionIds but no role that enables edit/delete buttons', function () {
        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => 2,
                'sections_per_floor' => 3,
            ]);
            $convention = $structure['convention'];
            $allSections = $structure['sections'];

            // Assign random sections
            $assignedSections = $allSections->random(fake()->numberBetween(1, 3));
            $assignedSectionIds = $assignedSections->pluck('id')->sort()->values()->toArray();

            $sectionUser = ConventionTestHelper::createUserWithRole($convention, 'SectionUser', [
                'section_ids' => $assignedSectionIds,
            ]);

            $response = $this->actingAs($sectionUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];

            // SectionUser should NOT have Owner, ConventionUser, or FloorUser roles
            $editDeleteRoles = ['Owner', 'ConventionUser', 'FloorUser'];
            $hasEditDeleteRole = ! empty(array_intersect($props['userRoles'], $editDeleteRoles));
            expect($hasEditDeleteRole)->toBeFalse(
                "Iteration {$i}: SectionUser should not have any role that enables section edit/delete buttons"
            );

            // userSectionIds should contain assigned sections
            expect(array_diff($assignedSectionIds, $props['userSectionIds']))->toBeEmpty(
                "Iteration {$i}: userSectionIds should contain all assigned section IDs"
            );
        }
    });
})->group('property', 'section-crud', 'frontend');

// Feature: section-crud-management, Property 9: Section display contains required information
// Validates: Requirements 6.1

describe('Property 9: Section display contains required information', function () {
    it('floors index returns sections with name, occupancy, and available_seats for each floor', function () {
        for ($i = 0; $i < 3; $i++) {
            $floorCount = fake()->numberBetween(1, 3);
            $sectionsPerFloor = fake()->numberBetween(1, 4);

            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => $floorCount,
                'sections_per_floor' => $sectionsPerFloor,
            ]);
            $convention = $structure['convention'];

            // Set random occupancy values on sections
            foreach ($structure['sections'] as $section) {
                $seats = $section->number_of_seats;
                $occupancy = fake()->randomElement([0, 10, 25, 50, 75, 100]);
                $availableSeats = (int) round($seats * (1 - $occupancy / 100));

                $section->update([
                    'occupancy' => $occupancy,
                    'available_seats' => $availableSeats,
                ]);
            }

            $response = $this->actingAs($structure['owner'])
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];
            $returnedFloors = collect($props['floors']);

            expect($returnedFloors)->toHaveCount($floorCount,
                "Iteration {$i}: Should return {$floorCount} floors"
            );

            foreach ($returnedFloors as $floor) {
                $sections = collect($floor['sections'] ?? []);

                expect($sections)->toHaveCount($sectionsPerFloor,
                    "Iteration {$i}: Floor '{$floor['name']}' should have {$sectionsPerFloor} sections"
                );

                foreach ($sections as $section) {
                    // Each section must have name (non-empty string)
                    expect($section['name'])->toBeString()->not->toBeEmpty();

                    // Each section must have occupancy (integer 0-100)
                    expect($section['occupancy'])->toBeInt()
                        ->toBeGreaterThanOrEqual(0)
                        ->toBeLessThanOrEqual(100);

                    // Each section must have available_seats (integer >= 0)
                    expect($section['available_seats'])->toBeInt()
                        ->toBeGreaterThanOrEqual(0);

                    // Each section must have number_of_seats (integer >= 1)
                    expect($section['number_of_seats'])->toBeInt()
                        ->toBeGreaterThanOrEqual(1);

                    // available_seats should not exceed number_of_seats
                    expect($section['available_seats'])->toBeLessThanOrEqual(
                        $section['number_of_seats'],
                        "Iteration {$i}: Section '{$section['name']}' available_seats should not exceed number_of_seats"
                    );
                }
            }
        }
    });

    it('section data includes accessibility flags for display', function () {
        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => 1,
                'sections_per_floor' => fake()->numberBetween(2, 5),
            ]);
            $convention = $structure['convention'];

            // Randomize accessibility flags
            foreach ($structure['sections'] as $section) {
                $section->update([
                    'elder_friendly' => fake()->boolean(),
                    'handicap_friendly' => fake()->boolean(),
                ]);
            }

            $response = $this->actingAs($structure['owner'])
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];
            $sections = collect($props['floors'][0]['sections'] ?? []);

            foreach ($sections as $section) {
                // Accessibility flags must be present as booleans
                expect($section)->toHaveKey('elder_friendly');
                expect($section)->toHaveKey('handicap_friendly');
                expect($section['elder_friendly'])->toBeBool();
                expect($section['handicap_friendly'])->toBeBool();

                // Verify the returned value matches the database
                $dbSection = Section::find($section['id']);
                expect($section['elder_friendly'])->toBe($dbSection->elder_friendly,
                    "Iteration {$i}: elder_friendly should match database value"
                );
                expect($section['handicap_friendly'])->toBe($dbSection->handicap_friendly,
                    "Iteration {$i}: handicap_friendly should match database value"
                );
            }
        }
    });
})->group('property', 'section-crud', 'frontend');
