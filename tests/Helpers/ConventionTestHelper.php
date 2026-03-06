<?php

namespace Tests\Helpers;

use App\Models\Convention;
use App\Models\Floor;
use App\Models\Section;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ConventionTestHelper
{
    /**
     * Create a convention with a full hierarchical structure (floors, sections)
     * and optionally an owner user.
     *
     * @param  array{
     *     floors?: int,
     *     sections_per_floor?: int,
     *     convention_attributes?: array<string, mixed>,
     *     with_owner?: bool,
     *     owner?: User|null,
     * }  $options
     * @return array{convention: Convention, floors: \Illuminate\Support\Collection, sections: \Illuminate\Support\Collection, owner: User|null}
     */
    public static function createConventionWithStructure(array $options = []): array
    {
        $floorCount = $options['floors'] ?? 2;
        $sectionsPerFloor = $options['sections_per_floor'] ?? 3;
        $conventionAttributes = $options['convention_attributes'] ?? [];
        $withOwner = $options['with_owner'] ?? true;
        $owner = $options['owner'] ?? null;

        $convention = Convention::factory()->create($conventionAttributes);

        if ($withOwner) {
            $owner = $owner ?? User::factory()->create();
            static::attachUserToConvention($owner, $convention, ['Owner', 'ConventionUser']);
        }

        $floors = collect();
        $sections = collect();

        for ($f = 0; $f < $floorCount; $f++) {
            $floor = Floor::factory()->create([
                'convention_id' => $convention->id,
            ]);
            $floors->push($floor);

            for ($s = 0; $s < $sectionsPerFloor; $s++) {
                $section = Section::factory()->create([
                    'floor_id' => $floor->id,
                ]);
                $sections->push($section);
            }
        }

        return [
            'convention' => $convention,
            'floors' => $floors,
            'sections' => $sections,
            'owner' => $owner,
        ];
    }

    /**
     * Create a user and assign them a specific role for a convention.
     * Handles pivot table attachments for FloorUser and SectionUser roles.
     *
     * @param  array{
     *     user?: User|null,
     *     user_attributes?: array<string, mixed>,
     *     floor_ids?: array<int>,
     *     section_ids?: array<int>,
     * }  $options
     */
    public static function createUserWithRole(
        Convention $convention,
        string $role,
        array $options = [],
    ): User {
        $user = $options['user'] ?? User::factory()->create($options['user_attributes'] ?? []);

        static::attachUserToConvention($user, $convention, [$role]);

        if ($role === 'FloorUser' && ! empty($options['floor_ids'])) {
            foreach ($options['floor_ids'] as $floorId) {
                DB::table('floor_user')->insertOrIgnore([
                    'floor_id' => $floorId,
                    'user_id' => $user->id,
                    'created_at' => now(),
                ]);
            }
        }

        if ($role === 'SectionUser' && ! empty($options['section_ids'])) {
            foreach ($options['section_ids'] as $sectionId) {
                DB::table('section_user')->insertOrIgnore([
                    'section_id' => $sectionId,
                    'user_id' => $user->id,
                    'created_at' => now(),
                ]);
            }
        }

        return $user;
    }

    /**
     * Attach a user to a convention and assign roles via pivot tables.
     *
     * @param  array<string>  $roles
     */
    public static function attachUserToConvention(User $user, Convention $convention, array $roles): void
    {
        // Attach to convention_user pivot (ignore if already attached)
        DB::table('convention_user')->insertOrIgnore([
            'convention_id' => $convention->id,
            'user_id' => $user->id,
            'created_at' => now(),
        ]);

        // Assign roles via convention_user_roles pivot
        foreach ($roles as $role) {
            DB::table('convention_user_roles')->insertOrIgnore([
                'convention_id' => $convention->id,
                'user_id' => $user->id,
                'role' => $role,
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Create an authenticated user with a given role for a convention,
     * useful for quickly setting up test scenarios.
     *
     * @param  array{
     *     floor_ids?: array<int>,
     *     section_ids?: array<int>,
     * }  $options
     * @return array{user: User, convention: Convention}
     */
    public static function createAuthenticatedUser(
        Convention $convention,
        string $role,
        array $options = [],
    ): array {
        $user = static::createUserWithRole($convention, $role, $options);

        return [
            'user' => $user,
            'convention' => $convention,
        ];
    }
}
