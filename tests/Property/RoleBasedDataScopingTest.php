<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 18: Role-Based Data Scoping
 *
 * For any user with a specific role:
 * - Owner and ConventionUser should see all floors, sections, and users in the convention
 * - FloorUser should see only their assigned floors and their sections
 * - SectionUser should see only their assigned sections
 *
 * Validates: Requirements 5.5, 5.6, 5.7, 12.1, 12.2, 12.3
 */
it('validates role-based data scoping for Owner and ConventionUser', function () {
    // Run 3 iterations with different data
    for ($i = 0; $i < 3; $i++) {
        // Create a convention with random number of floors and sections
        $convention = Convention::factory()->create();
        $floorCount = fake()->numberBetween(2, 5);
        $floors = Floor::factory()->count($floorCount)->create([
            'convention_id' => $convention->id,
        ]);

        foreach ($floors as $floor) {
            $sectionCount = fake()->numberBetween(2, 4);
            Section::factory()->count($sectionCount)->create([
                'floor_id' => $floor->id,
            ]);
        }

        // Test Owner role
        $owner = User::factory()->create();
        $convention->users()->attach($owner);
        \DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $owner->id,
            'role' => 'Owner',
        ]);

        // Owner should see all floors
        $this->actingAs($owner);
        $request = new \Illuminate\Http\Request;
        $request->setUserResolver(fn () => $owner);
        $request->setRouteResolver(fn () => new class
        {
            public function parameter($key)
            {
                return Convention::first();
            }
        });

        $middleware = new \App\Http\Middleware\ScopeByRole;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response($req);
        });

        // Owner should not have scoped IDs (sees everything)
        expect($request->get('scoped_floor_ids'))->toBeNull();
        expect($request->get('scoped_section_ids'))->toBeNull();

        // Test ConventionUser role
        $conventionUser = User::factory()->create();
        $convention->users()->attach($conventionUser);
        \DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $conventionUser->id,
            'role' => 'ConventionUser',
        ]);

        $this->actingAs($conventionUser);
        $request2 = new \Illuminate\Http\Request;
        $request2->setUserResolver(fn () => $conventionUser);
        $request2->setRouteResolver(fn () => new class
        {
            public function parameter($key)
            {
                return Convention::first();
            }
        });

        $response2 = $middleware->handle($request2, function ($req) {
            return new \Illuminate\Http\Response($req);
        });

        // ConventionUser should not have scoped IDs (sees everything)
        expect($request2->get('scoped_floor_ids'))->toBeNull();
        expect($request2->get('scoped_section_ids'))->toBeNull();

        // Clean up for next iteration
        \DB::table('convention_user_roles')->truncate();
        \DB::table('convention_user')->truncate();
        Section::query()->delete();
        Floor::query()->delete();
        Convention::query()->delete();
        User::query()->delete();
    }
});

it('validates role-based data scoping for FloorUser', function () {
    // Run 3 iterations with different data
    for ($i = 0; $i < 3; $i++) {
        // Create a convention with multiple floors
        $convention = Convention::factory()->create();
        $floorCount = fake()->numberBetween(3, 6);
        $floors = Floor::factory()->count($floorCount)->create([
            'convention_id' => $convention->id,
        ]);

        foreach ($floors as $floor) {
            $sectionCount = fake()->numberBetween(2, 4);
            Section::factory()->count($sectionCount)->create([
                'floor_id' => $floor->id,
            ]);
        }

        // Create FloorUser and assign random subset of floors
        $floorUser = User::factory()->create();
        $convention->users()->attach($floorUser);
        \DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $floorUser->id,
            'role' => 'FloorUser',
        ]);

        // Assign random floors (at least 1, at most all-1)
        $assignedFloorCount = fake()->numberBetween(1, max(1, $floorCount - 1));
        $assignedFloors = $floors->random($assignedFloorCount);

        foreach ($assignedFloors as $floor) {
            \DB::table('floor_user')->insert([
                'floor_id' => $floor->id,
                'user_id' => $floorUser->id,
            ]);
        }

        $this->actingAs($floorUser);
        $request = new \Illuminate\Http\Request;
        $request->setUserResolver(fn () => $floorUser);
        $conventionForRoute = $convention;
        $request->setRouteResolver(fn () => new class($conventionForRoute)
        {
            private Convention $convention;

            public function __construct(Convention $convention)
            {
                $this->convention = $convention;
            }

            public function parameter($key)
            {
                return $this->convention;
            }
        });

        $middleware = new \App\Http\Middleware\ScopeByRole;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response($req);
        });

        // FloorUser should have scoped floor IDs
        $scopedFloorIds = $request->get('scoped_floor_ids');
        expect($scopedFloorIds)->not->toBeNull();
        expect($scopedFloorIds)->toBeArray();
        expect(count($scopedFloorIds))->toBe($assignedFloorCount);

        // Verify the scoped IDs match assigned floors
        $assignedFloorIds = $assignedFloors->pluck('id')->toArray();
        sort($scopedFloorIds);
        sort($assignedFloorIds);
        expect($scopedFloorIds)->toBe($assignedFloorIds);

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

it('validates role-based data scoping for SectionUser', function () {
    // Run 3 iterations with different data
    for ($i = 0; $i < 3; $i++) {
        // Create a convention with floors and sections
        $convention = Convention::factory()->create();
        $floorCount = fake()->numberBetween(2, 4);
        $allSections = collect();

        for ($f = 0; $f < $floorCount; $f++) {
            $floor = Floor::factory()->create([
                'convention_id' => $convention->id,
            ]);

            $sectionCount = fake()->numberBetween(3, 5);
            $sections = Section::factory()->count($sectionCount)->create([
                'floor_id' => $floor->id,
            ]);

            $allSections = $allSections->merge($sections);
        }

        // Create SectionUser and assign random subset of sections
        $sectionUser = User::factory()->create();
        $convention->users()->attach($sectionUser);
        \DB::table('convention_user_roles')->insert([
            'convention_id' => $convention->id,
            'user_id' => $sectionUser->id,
            'role' => 'SectionUser',
        ]);

        // Assign random sections (at least 1, at most all-1)
        $totalSections = $allSections->count();
        $assignedSectionCount = fake()->numberBetween(1, max(1, $totalSections - 1));
        $assignedSections = $allSections->random($assignedSectionCount);

        foreach ($assignedSections as $section) {
            \DB::table('section_user')->insert([
                'section_id' => $section->id,
                'user_id' => $sectionUser->id,
            ]);
        }

        $this->actingAs($sectionUser);
        $request = new \Illuminate\Http\Request;
        $request->setUserResolver(fn () => $sectionUser);
        $conventionForRoute = $convention;
        $request->setRouteResolver(fn () => new class($conventionForRoute)
        {
            private Convention $convention;

            public function __construct(Convention $convention)
            {
                $this->convention = $convention;
            }

            public function parameter($key)
            {
                return $this->convention;
            }
        });

        $middleware = new \App\Http\Middleware\ScopeByRole;
        $response = $middleware->handle($request, function ($req) {
            return new \Illuminate\Http\Response($req);
        });

        // SectionUser should have scoped section IDs
        $scopedSectionIds = $request->get('scoped_section_ids');
        expect($scopedSectionIds)->not->toBeNull();
        expect($scopedSectionIds)->toBeArray();
        expect(count($scopedSectionIds))->toBe($assignedSectionCount);

        // Verify the scoped IDs match assigned sections
        $assignedSectionIds = $assignedSections->pluck('id')->toArray();
        sort($scopedSectionIds);
        sort($assignedSectionIds);
        expect($scopedSectionIds)->toBe($assignedSectionIds);

        // Clean up for next iteration
        \DB::table('section_user')->truncate();
        \DB::table('convention_user_roles')->truncate();
        \DB::table('convention_user')->truncate();
        Section::query()->delete();
        Floor::query()->delete();
        Convention::query()->delete();
        User::query()->delete();
    }
});
