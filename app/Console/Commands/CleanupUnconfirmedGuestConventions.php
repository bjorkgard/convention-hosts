<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupUnconfirmedGuestConventions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-unconfirmed-guest-conventions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete guest-created conventions whose owner never confirmed their email within 7 days';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $cutoff = now()->subDays(7);

        // Find unconfirmed users created more than 7 days ago
        // who have never logged in (no password set by user = still random password, email not confirmed)
        $users = User::where('email_confirmed', false)
            ->where('created_at', '<', $cutoff)
            ->get();

        $deletedConventions = 0;
        $deletedUsers = 0;

        foreach ($users as $user) {
            // Find conventions where this user is the Owner
            $ownedConventionIds = DB::table('convention_user_roles')
                ->where('user_id', $user->id)
                ->where('role', 'Owner')
                ->pluck('convention_id');

            if ($ownedConventionIds->isEmpty()) {
                continue;
            }

            DB::transaction(function () use ($user, $ownedConventionIds, &$deletedConventions, &$deletedUsers) {
                // Delete conventions owned by this unconfirmed user.
                // Cascading foreign keys handle floors, sections, attendance, pivots, etc.
                $deletedConventions += \App\Models\Convention::whereIn('id', $ownedConventionIds)->delete();

                // If the user has no remaining conventions, delete the user too
                $remainingConventions = DB::table('convention_user')
                    ->where('user_id', $user->id)
                    ->count();

                if ($remainingConventions === 0) {
                    $user->delete();
                    $deletedUsers++;
                }
            });
        }

        $this->info("Cleaned up {$deletedConventions} unconfirmed convention(s) and {$deletedUsers} user(s).");

        return self::SUCCESS;
    }
}
