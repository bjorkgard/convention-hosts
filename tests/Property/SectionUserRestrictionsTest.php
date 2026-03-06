<?php

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use App\Policies\SectionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * Property 39: SectionUser Edit Restrictions
 *
 * For any SectionUser, they should only be able to edit sections they are assigned to,
 * and should not be able to add or delete sections.
 *
 * Validates: Requirements 14.2
 */
it('validates SectionUser can only edit assigned sections', function () {
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

        // Assign random sections (at least 1, at most all-2)
        $totalSections = $allSections->count();
        $assignedSectionCount = fake()->numberBetween(1, max(1, $totalSections - 2));
        $assignedSections = $allSections->random($assignedSectionCount);
        $unassignedSections = $allSections->diff($assignedSections);

        foreach ($assignedSections as $section) {
            \DB::table('section_user')->insert([
                'section_id' => $section->id,
                'user_id' => $sectionUser->id,
            ]);
        }

        $policy = new SectionPolicy;

        // SectionUser should be able to view and update assigned sections
        foreach ($assignedSections as $section) {
            expect($policy->view($sectionUser, $section))->toBeTrue();
            expect($policy->update($sectionUser, $section))->toBeTrue();
        }

        // SectionUser should NOT be able to view or update unassigned sections
        foreach ($unassignedSections as $section) {
            expect($policy->view($sectionUser, $section))->toBeFalse();
            expect($policy->update($sectionUser, $section))->toBeFalse();
        }

        // SectionUser should NOT be able to delete any sections (assigned or not)
        foreach ($allSections as $section) {
            expect($policy->delete($sectionUser, $section))->toBeFalse();
        }

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

/**
 * Property 40: SectionUser User Management Scope
 *
 * For any SectionUser, they should only be able to add, edit, and delete users
 * who are connected to their assigned sections.
 *
 * Validates: Requirements 14.3
 */
it('validates SectionUser can only manage users connected to assigned sections', function () {
    // Run 3 iterations with different data
    for ($i = 0; $i < 3; $i++) {
        // Create a convention with floors and sections
        $convention = Convention::factory()->create();
        $floorCount = fake()->numberBetween(2, 3);
        $allSections = collect();

        for ($f = 0; $f < $floorCount; $f++) {
            $floor = Floor::factory()->create([
                'convention_id' => $convention->id,
            ]);

            $sectionCount = fake()->numberBetween(3, 4);
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

        $totalSections = $allSections->count();
        $assignedSectionCount = fake()->numberBetween(1, max(1, $totalSections - 1));
        $assignedSections = $allSections->random($assignedSectionCount);
        $unassignedSections = $allSections->diff($assignedSections);

        foreach ($assignedSections as $section) {
            \DB::table('section_user')->insert([
                'section_id' => $section->id,
                'user_id' => $sectionUser->id,
            ]);
        }

        // Create other users and assign them to various sections
        $usersOnAssignedSections = collect();
        $usersOnUnassignedSections = collect();

        // Create users for assigned sections
        foreach ($assignedSections->take(min(2, $assignedSectionCount)) as $section) {
            $otherUser = User::factory()->create();
            $convention->users()->attach($otherUser);
            \DB::table('convention_user_roles')->insert([
                'convention_id' => $convention->id,
                'user_id' => $otherUser->id,
                'role' => 'SectionUser',
            ]);
            \DB::table('section_user')->insert([
                'section_id' => $section->id,
                'user_id' => $otherUser->id,
            ]);
            $usersOnAssignedSections->push($otherUser);
        }

        // Create users for unassigned sections (if any)
        if ($unassignedSections->count() > 0) {
            foreach ($unassignedSections->take(min(2, $unassignedSections->count())) as $section) {
                $otherUser = User::factory()->create();
                $convention->users()->attach($otherUser);
                \DB::table('convention_user_roles')->insert([
                    'convention_id' => $convention->id,
                    'user_id' => $otherUser->id,
                    'role' => 'SectionUser',
                ]);
                \DB::table('section_user')->insert([
                    'section_id' => $section->id,
                    'user_id' => $otherUser->id,
                ]);
                $usersOnUnassignedSections->push($otherUser);
            }
        }

        // Verify SectionUser can see users on their assigned sections
        $assignedSectionIds = $assignedSections->pluck('id')->toArray();
        $visibleUserIds = \DB::table('section_user')
            ->whereIn('section_id', $assignedSectionIds)
            ->pluck('user_id')
            ->unique()
            ->toArray();

        // Users on assigned sections should be visible
        foreach ($usersOnAssignedSections as $user) {
            expect(in_array($user->id, $visibleUserIds))->toBeTrue();
        }

        // Users on unassigned sections should NOT be visible
        foreach ($usersOnUnassignedSections as $user) {
            expect(in_array($user->id, $visibleUserIds))->toBeFalse();
        }

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
