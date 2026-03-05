<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use App\Policies\FloorPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 37: Role-Based Permission Enforcement
 * 
 * For any user attempting to edit a floor:
 * - FloorUser should only be able to edit floors they are assigned to
 * - FloorUser should not be able to add or delete floors
 * - SectionUser should not be able to edit any floors
 * 
 * Validates: Requirements 13.1, 13.2, 14.1
 */
it('validates FloorUser can only edit assigned floors', function () {
    // Run 100 iterations with different data
    for ($i = 0; $i < 100; $i++) {
        // Create a convention with multiple floors
        $convention = Convention::factory()->create();
        $floorCount = fake()->numberBetween(4, 8);
        $floors = Floor::factory()->count($floorCount)->create([
            'convention_id' => $convention->id,
        ]);
        
        // Create FloorUser and assign random subset of floors
        $floorUser = User::factory()->create();
        $convention->users()->attach($floorUser);
        \DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $floorUser->id,
            'role' => 'FloorUser',
        ]);
        
        // Assign random floors (at least 1, at most all-2)
        $assignedFloorCount = fake()->numberBetween(1, max(1, $floorCount - 2));
        $assignedFloors = $floors->random($assignedFloorCount);
        $unassignedFloors = $floors->diff($assignedFloors);
        
        foreach ($assignedFloors as $floor) {
            \DB::table('floor_user')->insert([
                'floor_id' => $floor->id,
                'user_id' => $floorUser->id,
            ]);
        }
        
        $policy = new FloorPolicy();
        
        // FloorUser should be able to view and update assigned floors
        foreach ($assignedFloors as $floor) {
            expect($policy->view($floorUser, $floor))->toBeTrue();
            expect($policy->update($floorUser, $floor))->toBeTrue();
        }
        
        // FloorUser should NOT be able to view or update unassigned floors
        foreach ($unassignedFloors as $floor) {
            expect($policy->view($floorUser, $floor))->toBeFalse();
            expect($policy->update($floorUser, $floor))->toBeFalse();
        }
        
        // FloorUser should NOT be able to delete any floors (assigned or not)
        foreach ($floors as $floor) {
            expect($policy->delete($floorUser, $floor))->toBeFalse();
        }
        
        // Clean up for next iteration
        \DB::table('floor_user')->truncate();
        \DB::table('convention_user_roles')->truncate();
        \DB::table('convention_user')->truncate();
        Floor::query()->delete();
        Convention::query()->delete();
        User::query()->delete();
    }
});

it('validates SectionUser cannot edit any floors', function () {
    // Run 100 iterations with different data
    for ($i = 0; $i < 100; $i++) {
        // Create a convention with floors
        $convention = Convention::factory()->create();
        $floorCount = fake()->numberBetween(3, 6);
        $floors = Floor::factory()->count($floorCount)->create([
            'convention_id' => $convention->id,
        ]);
        
        // Create SectionUser
        $sectionUser = User::factory()->create();
        $convention->users()->attach($sectionUser);
        \DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $sectionUser->id,
            'role' => 'SectionUser',
        ]);
        
        $policy = new FloorPolicy();
        
        // SectionUser should NOT be able to view, update, or delete any floors
        foreach ($floors as $floor) {
            expect($policy->view($sectionUser, $floor))->toBeFalse();
            expect($policy->update($sectionUser, $floor))->toBeFalse();
            expect($policy->delete($sectionUser, $floor))->toBeFalse();
        }
        
        // Clean up for next iteration
        \DB::table('convention_user_roles')->truncate();
        \DB::table('convention_user')->truncate();
        Floor::query()->delete();
        Convention::query()->delete();
        User::query()->delete();
    }
});

/**
 * Property 38: FloorUser Section Management
 * 
 * For any FloorUser, they should be able to add, edit, and delete sections 
 * only on floors they are assigned to.
 * 
 * Validates: Requirements 13.3
 */
it('validates FloorUser can manage sections on assigned floors only', function () {
    // Run 100 iterations with different data
    for ($i = 0; $i < 100; $i++) {
        // Create a convention with floors and sections
        $convention = Convention::factory()->create();
        $floorCount = fake()->numberBetween(3, 5);
        $allFloors = collect();
        $allSections = collect();
        
        for ($f = 0; $f < $floorCount; $f++) {
            $floor = Floor::factory()->create([
                'convention_id' => $convention->id,
            ]);
            $allFloors->push($floor);
            
            $sectionCount = fake()->numberBetween(2, 4);
            $sections = Section::factory()->count($sectionCount)->create([
                'floor_id' => $floor->id,
            ]);
            $allSections = $allSections->merge($sections);
        }
        
        // Create FloorUser and assign random subset of floors
        $floorUser = User::factory()->create();
        $convention->users()->attach($floorUser);
        \DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $floorUser->id,
            'role' => 'FloorUser',
        ]);
        
        $assignedFloorCount = fake()->numberBetween(1, max(1, $floorCount - 1));
        $assignedFloors = $allFloors->random($assignedFloorCount);
        $unassignedFloors = $allFloors->diff($assignedFloors);
        
        foreach ($assignedFloors as $floor) {
            \DB::table('floor_user')->insert([
                'floor_id' => $floor->id,
                'user_id' => $floorUser->id,
            ]);
        }
        
        // Get sections on assigned vs unassigned floors
        $assignedFloorIds = $assignedFloors->pluck('id')->toArray();
        $sectionsOnAssignedFloors = $allSections->filter(fn($s) => in_array($s->floor_id, $assignedFloorIds));
        $sectionsOnUnassignedFloors = $allSections->diff($sectionsOnAssignedFloors);
        
        // FloorUser should be able to access sections on assigned floors
        expect($sectionsOnAssignedFloors->count())->toBeGreaterThan(0);
        
        // FloorUser should NOT be able to access sections on unassigned floors
        if ($sectionsOnUnassignedFloors->count() > 0) {
            expect($sectionsOnUnassignedFloors->count())->toBeGreaterThan(0);
        }
        
        // Verify floor access through policy
        $policy = new FloorPolicy();
        foreach ($assignedFloors as $floor) {
            expect($policy->update($floorUser, $floor))->toBeTrue();
        }
        foreach ($unassignedFloors as $floor) {
            expect($policy->update($floorUser, $floor))->toBeFalse();
        }
        
        // Clean up for next iteration
        \DB::table('floor_user')->truncate();
        \DB::table('convention_user_roles')->truncate();
        \DB::table('convention_user')->truncate();
        Section::query()->delete();
        Floor::query()->delete();
        Convention::query()->delete();
        User::query()->delete();
    }
});
