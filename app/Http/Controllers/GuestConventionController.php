<?php

namespace App\Http\Controllers;

use App\Actions\CreateConventionAction;
use App\Http\Requests\StoreGuestConventionRequest;
use App\Mail\GuestConventionVerification;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GuestConventionController extends Controller
{
    /**
     * Store a convention created by a guest (unauthenticated) user.
     * For existing users: creates convention, logs them in, redirects to convention.
     * For new users: creates user + convention, sends verification email, shows confirmation page.
     */
    public function store(StoreGuestConventionRequest $request, CreateConventionAction $action): \Symfony\Component\HttpFoundation\Response|InertiaResponse
    {
        $validated = $request->validated();

        // Find existing user or determine this is a new user
        $user = User::where('email', $validated['email'])->first();
        $isNewUser = ! $user;

        if ($isNewUser) {
            $user = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'mobile' => $validated['mobile'],
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

        if ($isNewUser) {
            // Generate signed verification URL (expires in 24 hours)
            $verificationUrl = URL::temporarySignedRoute(
                'guest-verification.show',
                now()->addHours(24),
                ['user' => $user->id, 'convention' => $convention->id]
            );

            // Send verification email
            Mail::to($user->email)->send(
                new GuestConventionVerification($user, $convention, $verificationUrl)
            );

            // Redirect to confirmation page (Inertia requires redirect after POST)
            return redirect()->route('guest-convention.confirmation')
                ->with('conventionName', $convention->name)
                ->with('email', $user->email);
        }

        // Existing user: log in and redirect to convention
        Auth::login($user);

        return redirect()->route('conventions.show', $convention);
    }

    /**
     * Show the confirmation page after a guest convention is created.
     */
    public function confirmation(): InertiaResponse
    {
        return Inertia::render('auth/guest-convention-confirmation', [
            'conventionName' => session('conventionName'),
            'email' => session('email'),
        ]);
    }
}
