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
     * Display a listing of users for the convention, scoped by role.
     */
    public function index(Request $request, Convention $convention): Response
    {
        $users = $convention->users()->get();

        // Apply role-based scoping from ScopeByRole middleware
        if ($scopedFloorIds = $request->get('scoped_floor_ids')) {
            // FloorUser: show users connected to assigned floors
            $userIdsOnFloors = DB::table('floor_user')
                ->whereIn('floor_id', $scopedFloorIds)
                ->pluck('user_id')
                ->unique();

            $users = $users->filter(fn (User $user) => $userIdsOnFloors->contains($user->id));
        }

        if ($scopedSectionIds = $request->get('scoped_section_ids')) {
            // SectionUser: show users connected to assigned sections
            $userIdsOnSections = DB::table('section_user')
                ->whereIn('section_id', $scopedSectionIds)
                ->pluck('user_id')
                ->unique();

            $users = $users->filter(fn (User $user) => $userIdsOnSections->contains($user->id));
        }

        // Load roles for each user
        $users = $users->values()->map(function (User $user) use ($convention) {
            $user->roles = DB::table('convention_user_roles')
                ->where('convention_id', $convention->id)
                ->where('user_id', $user->id)
                ->pluck('role');

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

            // Sync floor assignments
            DB::table('floor_user')
                ->where('user_id', $user->id)
                ->whereIn('floor_id', $convention->floors()->pluck('id'))
                ->delete();

            if (in_array('FloorUser', $data['roles']) && ! empty($data['floor_ids'])) {
                foreach ($data['floor_ids'] as $floorId) {
                    DB::table('floor_user')->insert([
                        'floor_id' => $floorId,
                        'user_id' => $user->id,
                        'created_at' => now(),
                    ]);
                }
            }

            // Sync section assignments
            $conventionSectionIds = $convention->floors()
                ->with('sections')
                ->get()
                ->flatMap(fn ($floor) => $floor->sections->pluck('id'));

            DB::table('section_user')
                ->where('user_id', $user->id)
                ->whereIn('section_id', $conventionSectionIds)
                ->delete();

            if (in_array('SectionUser', $data['roles']) && ! empty($data['section_ids'])) {
                foreach ($data['section_ids'] as $sectionId) {
                    DB::table('section_user')->insert([
                        'section_id' => $sectionId,
                        'user_id' => $user->id,
                        'created_at' => now(),
                    ]);
                }
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

            // Remove floor assignments for this convention's floors
            $conventionFloorIds = $convention->floors()->pluck('id');
            DB::table('floor_user')
                ->where('user_id', $user->id)
                ->whereIn('floor_id', $conventionFloorIds)
                ->delete();

            // Remove section assignments for this convention's sections
            $conventionSectionIds = $convention->floors()
                ->with('sections')
                ->get()
                ->flatMap(fn ($floor) => $floor->sections->pluck('id'));

            DB::table('section_user')
                ->where('user_id', $user->id)
                ->whereIn('section_id', $conventionSectionIds)
                ->delete();

            // Remove from convention_user pivot
            $convention->users()->detach($user->id);

            // If user has no remaining conventions, delete user entirely (Req 17.2)
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
