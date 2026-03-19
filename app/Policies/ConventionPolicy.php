<?php

namespace App\Policies;

use App\Models\Convention;
use App\Models\User;

class ConventionPolicy
{
    /**
     * Determine whether the user can view the convention.
     *
     * User must have any role for the convention.
     */
    public function view(User $user, Convention $convention): bool
    {
        return $user->conventions->contains($convention);
    }

    /**
     * Determine whether the user can update the convention.
     *
     * User must have Owner or Administrator role.
     */
    public function update(User $user, Convention $convention): bool
    {
        return $user->hasAnyRole($convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can delete the convention.
     *
     * User must have Owner role.
     */
    public function delete(User $user, Convention $convention): bool
    {
        return $user->hasRole($convention, 'Owner');
    }

    /**
     * Determine whether the user can export the convention data.
     *
     * User must have Owner role.
     */
    public function export(User $user, Convention $convention): bool
    {
        return $user->hasRole($convention, 'Owner');
    }
}
