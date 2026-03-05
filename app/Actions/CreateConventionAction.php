<?php

namespace App\Actions;

use App\Models\Convention;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateConventionAction
{
    /**
     * Create a new convention with the creator assigned as Owner and ConventionUser.
     *
     * @param  array<string, mixed>  $data
     */
    public function execute(array $data, User $creator): Convention
    {
        return DB::transaction(function () use ($data, $creator) {
            // Create the convention
            $convention = Convention::create($data);

            // Attach the creator to the convention via convention_user pivot
            $convention->users()->attach($creator->id);

            // Assign Owner and ConventionUser roles
            DB::table('convention_user_roles')->insert([
                [
                    'convention_id' => $convention->id,
                    'user_id' => $creator->id,
                    'role' => 'Owner',
                    'created_at' => now(),
                ],
                [
                    'convention_id' => $convention->id,
                    'user_id' => $creator->id,
                    'role' => 'ConventionUser',
                    'created_at' => now(),
                ],
            ]);

            // Note: Attendance periods are created lazily on first access
            // as per the design document

            return $convention->fresh();
        });
    }
}
