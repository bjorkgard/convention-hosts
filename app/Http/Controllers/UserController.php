<?php

namespace App\Http\Controllers;

use App\Actions\InviteUserAction;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    /**
     * Display a listing of users for the convention.
     */
    public function index(Request $request, Convention $convention): Response
    {
        $users = $convention->users()->get();

        // Batch-load roles for all users (avoid N+1)
        $userIds = $users->pluck('id');

        $allRoles = DB::table('convention_user_roles')
            ->where('convention_id', $convention->id)
            ->whereIn('user_id', $userIds)
            ->get()
            ->groupBy('user_id');

        $users = $users->values()->map(function (User $user) use ($allRoles) {
            $user->roles = ($allRoles[$user->id] ?? collect())->pluck('role');

            return $user;
        });

        $userRoles = $request->user()->rolesForConvention($convention);

        return Inertia::render('users/index', [
            'convention' => $convention,
            'users' => $users,
            'userRoles' => $userRoles,
        ]);
    }

    /**
     * Store a newly created user (invite to convention).
     */
    public function store(StoreUserRequest $request, Convention $convention, InviteUserAction $action): RedirectResponse
    {
        $action->execute($request->validated(), $convention);

        return redirect()->back();
    }

    /**
     * Update the specified user's details and roles.
     */
    public function update(UpdateUserRequest $request, Convention $convention, User $user): RedirectResponse
    {
        $this->authorize('update', [$user, $convention]);

        $data = $request->validated();

        DB::transaction(function () use ($user, $data, $convention) {
            // Update user details
            $user->update([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'mobile' => $data['mobile'],
            ]);

            // Sync roles: remove old roles, add new ones
            DB::table('convention_user_roles')
                ->where('convention_id', $convention->id)
                ->where('user_id', $user->id)
                ->delete();

            foreach ($data['roles'] as $role) {
                DB::table('convention_user_roles')->insert([
                    'convention_id' => $convention->id,
                    'user_id' => $user->id,
                    'role' => $role,
                    'created_at' => now(),
                ]);
            }
        });

        return redirect()->back();
    }

    /**
     * Remove the specified user from the convention.
     *
     * Removes all role and pivot records. If user has no remaining
     * conventions, deletes the user record entirely (Requirement 17.2).
     */
    public function destroy(Convention $convention, User $user): RedirectResponse
    {
        $this->authorize('delete', [$user, $convention]);

        DB::transaction(function () use ($user, $convention) {
            // Remove roles for this convention
            DB::table('convention_user_roles')
                ->where('convention_id', $convention->id)
                ->where('user_id', $user->id)
                ->delete();

            // Remove from convention_user pivot
            $convention->users()->detach($user->id);

            // If user has no remaining conventions, delete user entirely
            $remainingConventions = DB::table('convention_user')
                ->where('user_id', $user->id)
                ->count();

            if ($remainingConventions === 0) {
                $user->delete();
            }
        });

        return redirect()->back();
    }

    /**
     * Resend invitation email to the user.
     */
    public function resendInvitation(Request $request, Convention $convention, User $user): RedirectResponse
    {
        // Generate new signed invitation URL (24h expiration)
        $invitationUrl = URL::temporarySignedRoute(
            'invitation.show',
            now()->addHours(24),
            ['user' => $user->id, 'convention' => $convention->id]
        );

        // Send invitation email
        Mail::to($user->email)->send(
            new \App\Mail\UserInvitation($user, $convention, $invitationUrl)
        );

        return redirect()->back();
    }
}
