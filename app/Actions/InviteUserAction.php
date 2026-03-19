<?php

namespace App\Actions;

use App\Mail\UserInvitation;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class InviteUserAction
{
    /**
     * Invite a user to a convention or create a new user.
     *
     * Only handles Owner and Administrator role assignments.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, Convention $convention): User
    {
        return DB::transaction(function () use ($data, $convention) {
            // Check if user exists by email
            $user = User::where('email', $data['email'])->first();

            if (! $user) {
                // Create new user without password
                $user = User::create([
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'email' => $data['email'],
                    'mobile' => $data['mobile'],
                    'email_confirmed' => false,
                ]);
            }

            // Attach user to convention if not already attached
            if (! $convention->users->contains($user->id)) {
                $convention->users()->attach($user->id);
            }

            // Attach roles via convention_user_roles (only Owner and Administrator)
            foreach ($data['roles'] as $role) {
                DB::table('convention_user_roles')->insertOrIgnore([
                    'convention_id' => $convention->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'created_at' => now(),
                ]);
            }

            // Generate signed invitation URL (24h expiration)
            $invitationUrl = URL::temporarySignedRoute(
                'invitation.show',
                now()->addHours(24),
                ['user' => $user->id, 'convention' => $convention->id]
            );

            // Send invitation email via Mailgun
            Mail::to($user->email)->send(new UserInvitation($user, $convention, $invitationUrl));

            return $user->fresh();
        });
    }
}
