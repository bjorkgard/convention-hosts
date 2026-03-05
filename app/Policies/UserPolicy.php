<?php

namespace App\Policies;

use App\Models\Convention;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view another user within a convention context.
     *
     * Role-based scoping:
     * - Owner and ConventionUser: Can view all users in the convention
     * - FloorUser: Can view users on assigned floors
     * - SectionUser: Can view users on assigned sections
     */
    public function view(User $user, User $targetUser, Convention $convention): bool
    {
        // Owner and ConventionUser can view all users
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return true;
        }

        // FloorUser can view users on assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            $userFloorIds = $user->floors()
                ->whereHas('convention', fn ($q) => $q->where('id', $convention->id))
                ->pluck('floors.id');

            $targetUserFloorIds = $targetUser->floors()
                ->whereHas('convention', fn ($q) => $q->where('id', $convention->id))
                ->pluck('floors.id');

            return $userFloorIds->intersect($targetUserFloorIds)->isNotEmpty();
        }

        // SectionUser can view users on assigned sections
        if ($user->hasRole($convention, 'SectionUser')) {
            $userSectionIds = $user->sections()
                ->whereHas('floor.convention', fn ($q) => $q->where('id', $convention->id))
                ->pluck('sections.id');

            $targetUserSectionIds = $targetUser->sections()
                ->whereHas('floor.convention', fn ($q) => $q->where('id', $convention->id))
                ->pluck('sections.id');

            return $userSectionIds->intersect($targetUserSectionIds)->isNotEmpty();
        }

        return false;
    }

    /**
     * Determine whether the user can update another user within a convention context.
     *
     * - Owner and ConventionUser: Can update all users in the convention
     * - FloorUser: Can update users on assigned floors
     * - SectionUser: Can update users on assigned sections
     */
    public function update(User $user, User $targetUser, Convention $convention): bool
    {
        // Same logic as view for now
        return $this->view($user, $targetUser, $convention);
    }

    /**
     * Determine whether the user can delete another user from a convention.
     *
     * - Owner and ConventionUser: Can delete users from the convention
     * - FloorUser: Can delete users on assigned floors
     * - SectionUser: Can delete users on assigned sections
     */
    public function delete(User $user, User $targetUser, Convention $convention): bool
    {
        // Same logic as view for now
        return $this->view($user, $targetUser, $convention);
    }
}
