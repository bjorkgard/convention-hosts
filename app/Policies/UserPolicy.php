<?php

namespace App\Policies;

use App\Models\Convention;
use App\Models\User;

class UserPolicy
{
    /**
     * Determine whether the user can view another user within a convention context.
     *
     * Only Owner and Administrator can view users.
     */
    public function view(User $user, User $targetUser, Convention $convention): bool
    {
        return $user->hasAnyRole($convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can update another user within a convention context.
     *
     * Only Owner and Administrator can update users.
     */
    public function update(User $user, User $targetUser, Convention $convention): bool
    {
        return $user->hasAnyRole($convention, ['Owner', 'Administrator']);
    }

    /**
     * Determine whether the user can delete another user from a convention.
     *
     * Only Owner and Administrator can delete users.
     */
    public function delete(User $user, User $targetUser, Convention $convention): bool
    {
        return $user->hasAnyRole($convention, ['Owner', 'Administrator']);
    }
}
