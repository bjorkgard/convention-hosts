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
     * Only Owner and Administrator can view sections.
     */
    public function view(User $user, Section $section): bool
    {
        return $user->hasAnyRole($section->floor->convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can create a section for the floor.
     *
     * Only Owner and Administrator can create sections.
     */
    public function create(User $user, Floor $floor): bool
    {
        return $user->hasAnyRole($floor->convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can update the section.
     *
     * Only Owner and Administrator can update sections.
     */
    public function update(User $user, Section $section): bool
    {
        return $user->hasAnyRole($section->floor->convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can delete the section.
     *
     * Only Owner and Administrator can delete sections.
     */
    public function delete(User $user, Section $section): bool
    {
        return $user->hasAnyRole($section->floor->convention, ['Owner', 'Administrator']);
    }
}
