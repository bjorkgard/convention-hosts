<?php

namespace App\Http\Controllers;

use App\Actions\CreateConventionAction;
use App\Http\Requests\StoreGuestConventionRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class GuestConventionController extends Controller
{
    /**
     * Store a convention created by a guest (unauthenticated) user.
     * Creates or finds the user, creates the convention, logs them in.
     */
    public function store(StoreGuestConventionRequest $request, CreateConventionAction $action): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validated();

        // Find existing user or create a new one
        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'password' => Hash::make(Str::random(32)),
                'email_confirmed' => false,
            ]);
        }

        // Create the convention with this user as owner
        $convention = $action->execute([
            'name' => $validated['name'],
            'city' => $validated['city'],
            'country' => $validated['country'],
            'address' => $validated['address'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'other_info' => $validated['other_info'] ?? null,
        ], $user);

        // Log the user in
        Auth::login($user);

        return redirect()->route('conventions.show', $convention);
    }
}
