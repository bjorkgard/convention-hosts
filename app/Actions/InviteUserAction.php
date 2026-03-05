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

            // Attach roles via convention_user_roles
            foreach ($data['roles'] as $role) {
                DB::table('convention_user_roles')->insertOrIgnore([
                    'convention_id' => $convention->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'created_at' => now(),
                ]);
            }

            // Attach to floors if FloorUser role
            if (in_array('FloorUser', $data['roles']) && isset($data['floor_ids'])) {
                foreach ($data['floor_ids'] as $floorId) {
                    DB::table('floor_user')->insertOrIgnore([
                        'floor_id' => $floorId,
                        'user_id' => $user->id,
                        'created_at' => now(),
                    ]);
                }
            }

            // Attach to sections if SectionUser role
            if (in_array('SectionUser', $data['roles']) && isset($data['section_ids'])) {
                foreach ($data['section_ids'] as $sectionId) {
                    DB::table('section_user')->insertOrIgnore([
                        'section_id' => $sectionId,
                        'user_id' => $user->id,
                        'created_at' => now(),
                    ]);
                }
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
