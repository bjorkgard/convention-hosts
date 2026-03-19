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
     * Only Owner and Administrator can create floors.
     */
    public function create(User $user, Convention $convention): bool
    {
        return $user->hasAnyRole($convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can view the floor.
     *
     * Only Owner and Administrator can view floors.
     */
    public function view(User $user, Floor $floor): bool
    {
        return $user->hasAnyRole($floor->convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can update the floor.
     *
     * Only Owner and Administrator can update floors.
     */
    public function update(User $user, Floor $floor): bool
    {
        return $user->hasAnyRole($floor->convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can delete the floor.
     *
     * Only Owner and Administrator can delete floors.
     */
    public function delete(User $user, Floor $floor): bool
    {
        return $user->hasAnyRole($floor->convention, ['Owner', 'Administrator']);
    }
}
