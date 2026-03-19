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
            static::attachUserToConvention($owner, $convention, ['Owner', 'Administrator']);
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
     * Only Owner and Administrator roles are supported.
     *
     * @param  array{
     *     user?: User|null,
     *     user_attributes?: array<string, mixed>,
     * }  $options
     */
    public static function createUserWithRole(
        Convention $convention,
        string $role,
        array $options = [],
    ): User {
        $user = $options['user'] ?? User::factory()->create($options['user_attributes'] ?? []);

        static::attachUserToConvention($user, $convention, [$role]);

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

    /**
     * Set up a URL session in the Laravel session for testing URL-based access.
     *
     * @param  'floor'|'section'  $type
     */
    public static function setUrlSession(Convention $convention, string $type): void
    {
        $token = $type === 'floor'
            ? $convention->floor_url_token
            : $convention->section_url_token;

        session([
            'url_session' => [
                'convention_id' => $convention->id,
                'type' => $type,
                'token' => $token,
            ],
        ]);
    }
}
