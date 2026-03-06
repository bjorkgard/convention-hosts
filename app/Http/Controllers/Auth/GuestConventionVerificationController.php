<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\SetPasswordRequest;
use App\Models\Convention;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class GuestConventionVerificationController extends Controller
{
    /**
     * Display the password creation form for a guest convention user.
     *
     * The route uses signed middleware to verify the URL signature.
     */
    public function show(Request $request, User $user, Convention $convention): Response
    {
        return Inertia::render('auth/guest-convention-set-password', [
            'user' => $user->only('id', 'first_name', 'last_name', 'email'),
            'convention' => $convention->only('id', 'name'),
        ]);
    }

    /**
     * Set the user's password, confirm their email, log them in, and redirect to the convention.
     */
    public function store(SetPasswordRequest $request, User $user, Convention $convention): RedirectResponse
    {
        $user->update([
            'password' => $request->validated('password'),
            'email_confirmed' => true,
        ]);

        Auth::login($user);

        return redirect()->route('conventions.show', $convention);
    }
}
