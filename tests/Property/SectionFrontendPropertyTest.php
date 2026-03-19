<?php

use App\Models\Section;
use Tests\Helpers\ConventionTestHelper;

// Feature: section-crud-management, Property 1: Add Section button visibility is determined by role
// Validates: Requirements 1.1, 1.2

describe('Property 1: Add Section button visibility by role', function () {
    it('shows Add Section button for Owner and Administrator', function () {
        $rolesWithAccess = ['Owner', 'Administrator'];

        for ($i = 0; $i < 3; $i++) {
            $structure = ConventionTestHelper::createConventionWithStructure([
                'floors' => fake()->numberBetween(1, 3),
                'sections_per_floor' => fake()->numberBetween(1, 2),
            ]);
            $convention = $structure['convention'];

            // Pick a random role that should see the button
            $allowedRole = fake()->randomElement($rolesWithAccess);
            $allowedUser = ConventionTestHelper::createUserWithRole($convention, $allowedRole);

            $response = $this->actingAs($allowedUser)
                ->get(route('floors.index', $convention));

            $response->assertOk();

            $props = $response->original->getData()['page']['props'];
            $userRoles = $props['userRoles'];

            $hasAllowedRole = ! empty(array_intersect($userRoles, $rolesWithAccess));
            expect($hasAllowedRole)->toBeTrue(
                "Iteration {$i}: User with role {$allowedRole} should have a role that enables Add Section button"
            );
        }
    });
})->group('property', 'section-crud', 'frontend');

// Feature: section-crud-management, Property 2: Floor selector shows exactly the authorized floors
// Validates: Requirements 2.4, 2.5

describe('Property 2: Floor selector shows exactly the authorized floors', function () {
    it('Owner and Administrator see all convention floors', function () {
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

            // Test Administrator
            $admin = ConventionTestHelper::createUserWithRole($convention, 'Administrator');

            $response = $this->actingAs($admin)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];
            $returnedFloorIds = collect($props['floors'])->pluck('id')->sort()->values()->toArray();

            expect($returnedFloorIds)->toBe($allFloorIds,
                "Iteration {$i}: Administrator should see all {$floorCount} floors"
            );
        }
    });
})->group('property', 'section-crud', 'frontend');

// Feature: section-crud-management, Property 8: Section action button visibility matches authorization
// Validates: Requirements 4.1, 5.1, 6.2, 6.3

describe('Property 8: Section action button visibility matches authorization', function () {
    it('Owner and Administrator get userRoles that enable edit/delete on all sections', function () {
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

            // Test Administrator
            $admin = ConventionTestHelper::createUserWithRole($convention, 'Administrator');

            $response = $this->actingAs($admin)
                ->get(route('floors.index', $convention));

            $response->assertOk();
            $props = $response->original->getData()['page']['props'];

            expect(in_array('Administrator', $props['userRoles']))->toBeTrue(
                "Iteration {$i}: Administrator should have 'Administrator' in userRoles"
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
                    expect($section['name'])->toBeString()->not->toBeEmpty();
                    expect($section['occupancy'])->toBeInt()
                        ->toBeGreaterThanOrEqual(0)
                        ->toBeLessThanOrEqual(100);
                    expect($section['available_seats'])->toBeInt()
                        ->toBeGreaterThanOrEqual(0);
                    expect($section['number_of_seats'])->toBeInt()
                        ->toBeGreaterThanOrEqual(1);
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
                expect($section)->toHaveKey('elder_friendly');
                expect($section)->toHaveKey('handicap_friendly');
                expect($section['elder_friendly'])->toBeBool();
                expect($section['handicap_friendly'])->toBeBool();

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
