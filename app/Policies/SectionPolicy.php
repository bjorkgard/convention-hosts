<?php

namespace App\Policies;

use App\Models\Floor;
use App\Models\Section;
use App\Models\User;

class SectionPolicy
{
    /**
     * Determine whether the user can view the section.
     *
     * Role-based scoping:
     * - Owner and ConventionUser: Can view all sections
     * - FloorUser: Can view sections on assigned floors
     * - SectionUser: Can view only assigned sections
     */
    public function view(User $user, Section $section): bool
    {
        $convention = $section->floor->convention;

        // Owner and ConventionUser can view all sections
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return true;
        }

        // FloorUser can view sections on assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            return $user->floors->contains($section->floor);
        }

        // SectionUser can view only assigned sections
        if ($user->hasRole($convention, 'SectionUser')) {
            return $user->sections->contains($section);
        }

        return false;
    }

    /**
     * Determine whether the user can create a section for the floor.
     *
     * - Owner and ConventionUser: Can create sections
     * - FloorUser: Can create sections on assigned floors
     * - SectionUser: Cannot create sections
     */
    public function create(User $user, Floor $floor): bool
    {
        $convention = $floor->convention;

        // Owner and ConventionUser can create sections on any floor
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return true;
        }

        // FloorUser can create sections on assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            return $user->floors->contains($floor);
        }

        return false;
    }

    /**
     * Determine whether the user can update the section.
     *
     * - Owner and ConventionUser: Can update all sections
     * - FloorUser: Can update sections on assigned floors
     * - SectionUser: Can update only assigned sections
     */
    public function update(User $user, Section $section): bool
    {
        $convention = $section->floor->convention;

        // Owner and ConventionUser can update all sections
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return true;
        }

        // FloorUser can update sections on assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            return $user->floors->contains($section->floor);
        }

        // SectionUser can update only assigned sections
        if ($user->hasRole($convention, 'SectionUser')) {
            return $user->sections->contains($section);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the section.
     *
     * - Owner and ConventionUser: Can delete all sections
     * - FloorUser: Can delete sections on assigned floors
     * - SectionUser: Cannot delete sections
     */
    public function delete(User $user, Section $section): bool
    {
        $convention = $section->floor->convention;

        // Owner and ConventionUser can delete all sections
        if ($user->hasAnyRole($convention, ['Owner', 'ConventionUser'])) {
            return true;
        }

        // FloorUser can delete sections on assigned floors
        if ($user->hasRole($convention, 'FloorUser')) {
            return $user->floors->contains($section->floor);
        }

        // SectionUser cannot delete sections
        return false;
    }
}
