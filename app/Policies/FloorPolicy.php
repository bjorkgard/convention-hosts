<?php

namespace App\Policies;

use App\Models\Convention;
use App\Models\Floor;
use App\Models\User;

class FloorPolicy
{
    /**
     * Determine whether the user can create a floor for the convention.
     *
     * - Owner and ConventionUser: Can create floors
     * - FloorUser: Cannot create floors
     * - SectionUser: Cannot create floors
     */
    public function create(User $user, Convention $convention): bool
    {
        return $user->hasAnyRole($convention, ['Owner', 'ConventionUser']);
    }

    /**
     * Determine whether the user can view the floor.
     *
     * Role-based scoping:
     * - Owner and ConventionUser: Can view all floors
     * - FloorUser: Can view only assigned floors
     * - SectionUser: Cannot view floors directly
     */
    public function view(User $user, Floor $floor): bool
    {
        $convention = $floor->convention;

        // Owner and ConventionUser can view all floors
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return true;
        }

        // FloorUser can view only assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            return $user->floors->contains($floor);
        }

        return false;
    }

    /**
     * Determine whether the user can update the floor.
     *
     * - Owner and ConventionUser: Can update all floors
     * - FloorUser: Can update only assigned floors
     * - SectionUser: Cannot update floors
     */
    public function update(User $user, Floor $floor): bool
    {
        $convention = $floor->convention;

        // Owner and ConventionUser can update all floors
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return true;
        }

        // FloorUser can update only assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            return $user->floors->contains($floor);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the floor.
     *
     * - Owner and ConventionUser: Can delete floors
     * - FloorUser: Cannot delete floors
     * - SectionUser: Cannot delete floors
     */
    public function delete(User $user, Floor $floor): bool
    {
        $convention = $floor->convention;

        // Only Owner and ConventionUser can delete floors
        return $user->hasAnyRole($convention, ['Owner', 'ConventionUser']);
    }
}
